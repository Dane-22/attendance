# Auto-Detection Attendance Review System

Implement an automated validation system that reviews completed attendance records against configurable rules, flags suspicious patterns for admin review, and prevents questionable records from being included in weekly payroll reports until approved.

## System Overview

**Purpose**: Add a "review queue" layer between raw attendance and payroll reports
**Trigger**: Automated detection runs when records are completed (both time_in and time_out exist)
**Outcome**: Records marked as "Approved", "Flagged", or "Rejected" before payroll calculation

## Detection Rules (Configurable)

### Rule 1: Minimum Hours Threshold
```php
// Flag if worked less than 4 hours (half day)
if ($worked_hours < 4.0) {
    $flags[] = 'SHORT_SHIFT: Only ' . round($worked_hours, 2) . ' hours worked';
}
```

### Rule 2: Maximum Hours Threshold
```php
// Flag if worked more than 12 hours (possible error)
if ($worked_hours > 12.0) {
    $flags[] = 'LONG_SHIFT: ' . round($worked_hours, 2) . ' hours exceeds limit';
}
```

### Rule 3: Rapid Clock-out then Clock-in
```php
// Flag if employee clocked out and back in within 15 minutes at same branch
if ($gap_between_records < 15 && $same_branch) {
    $flags[] = 'RAPID_REENTRY: ' . $gap . ' minutes between clock-out and clock-in';
}
```

### Rule 4: Late Night Clock-out
```php
// Flag if clock-out is after 10 PM (suspicious for day workers)
if (strtotime($time_out) > strtotime('22:00:00')) {
    $flags[] = 'LATE_CLOCKOUT: Worked until ' . date('h:i A', strtotime($time_out));
}
```

### Rule 5: Very Early Clock-in
```php
// Flag if clock-in is before 5 AM
if (strtotime($time_in) < strtotime('05:00:00')) {
    $flags[] = 'EARLY_CLOCKIN: Started at ' . date('h:i A', strtotime($time_in));
}
```

### Rule 6: Weekend/Sunday Work
```php
// Flag Sunday work (requires special approval)
$day_of_week = date('w', strtotime($attendance_date));
if ($day_of_week == 0) {
    $flags[] = 'SUNDAY_WORK: Requires manager approval';
}
```

### Rule 7: Duplicate Same-Day Records
```php
// Flag if employee has 3+ records on same day at same branch
if ($record_count >= 3 && count($unique_branches) == 1) {
    $flags[] = 'MULTIPLE_RECORDS: ' . $record_count . ' clock-ins at same branch';
}
```

## Database Schema Changes

### New Table: attendance_review_queue
```sql
CREATE TABLE attendance_review_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attendance_id INT NOT NULL,
    employee_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    flags JSON NOT NULL,  -- Array of detected issues
    status ENUM('pending', 'approved', 'rejected', 'auto_approved') DEFAULT 'pending',
    reviewed_by INT NULL,
    reviewed_at TIMESTAMP NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_employee_date (employee_id, attendance_date),
    INDEX idx_status (status)
);
```

### Modify: attendance table
```sql
ALTER TABLE attendance 
ADD COLUMN review_status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
ADD COLUMN flagged_reason TEXT NULL;
```

## Implementation Architecture

### Phase 1: Detection Engine

**New File**: `employee/cron/attendance_auto_detection.php`

```php
<?php
/**
 * Attendance Auto-Detection System
 * Runs every 15 minutes or after each clock-out
 */

require_once __DIR__ . '/../../conn/db_connection.php';

// Load detection rules from config
$rules = [
    'min_hours' => 4.0,
    'max_hours' => 12.0,
    'rapid_reentry_minutes' => 15,
    'late_clockout_time' => '22:00:00',
    'early_clockin_time' => '05:00:00',
    'max_records_per_day' => 3
];

// Find recently completed attendance records
$sql = "SELECT a.*, e.first_name, e.last_name, e.position
        FROM attendance a
        JOIN employees e ON a.employee_id = e.id
        WHERE a.time_in IS NOT NULL 
          AND a.time_out IS NOT NULL
          AND a.review_status = 'approved'  -- Only check unprocessed records
          AND a.id NOT IN (SELECT attendance_id FROM attendance_review_queue)
        ORDER BY a.time_out DESC
        LIMIT 100";

$result = mysqli_query($db, $sql);

while ($record = mysqli_fetch_assoc($result)) {
    $flags = detectAnomalies($record, $rules, $db);
    
    if (!empty($flags)) {
        // Add to review queue
        addToReviewQueue($record, $flags, $db);
        
        // Mark attendance as pending review
        markAttendancePending($record['id'], $db);
        
        // Send notification to admin
        notifyAdmin($record, $flags);
    }
}

function detectAnomalies($record, $rules, $db) {
    $flags = [];
    
    // Calculate worked hours
    $worked_hours = calculateWorkedHours($record['time_in'], $record['time_out']);
    
    // Rule 1: Minimum hours
    if ($worked_hours < $rules['min_hours']) {
        $flags[] = [
            'rule' => 'SHORT_SHIFT',
            'severity' => 'medium',
            'message' => "Only {$worked_hours} hours worked (minimum: {$rules['min_hours']})"
        ];
    }
    
    // Rule 2: Maximum hours
    if ($worked_hours > $rules['max_hours']) {
        $flags[] = [
            'rule' => 'LONG_SHIFT',
            'severity' => 'high',
            'message' => "{$worked_hours} hours exceeds limit (max: {$rules['max_hours']})"
        ];
    }
    
    // Rule 3: Rapid reentry
    $previous_record = getPreviousRecord($record, $db);
    if ($previous_record) {
        $gap = calculateGap($previous_record['time_out'], $record['time_in']);
        if ($gap < $rules['rapid_reentry_minutes'] && 
            $previous_record['branch_name'] == $record['branch_name']) {
            $flags[] = [
                'rule' => 'RAPID_REENTRY',
                'severity' => 'medium',
                'message' => "{$gap} minutes between records at same branch"
            ];
        }
    }
    
    // Rule 4: Late clock-out
    if (date('H:i:s', strtotime($record['time_out'])) > $rules['late_clockout_time']) {
        $flags[] = [
            'rule' => 'LATE_CLOCKOUT',
            'severity' => 'low',
            'message' => 'Clock-out after 10 PM'
        ];
    }
    
    // Rule 5: Early clock-in
    if (date('H:i:s', strtotime($record['time_in'])) < $rules['early_clockin_time']) {
        $flags[] = [
            'rule' => 'EARLY_CLOCKIN',
            'severity' => 'low',
            'message' => 'Clock-in before 5 AM'
        ];
    }
    
    // Rule 6: Sunday work
    if (date('w', strtotime($record['attendance_date'])) == 0) {
        $flags[] = [
            'rule' => 'SUNDAY_WORK',
            'severity' => 'medium',
            'message' => 'Sunday work requires approval'
        ];
    }
    
    // Rule 7: Multiple same-day records
    $same_day_count = getSameDayRecordCount($record, $db);
    if ($same_day_count >= $rules['max_records_per_day']) {
        $flags[] = [
            'rule' => 'MULTIPLE_RECORDS',
            'severity' => 'medium',
            'message' => "{$same_day_count} records on same day"
        ];
    }
    
    return $flags;
}
```

### Phase 2: Admin Review Interface

**New File**: `employee/attendance_review.php`

```php
<?php
// Admin interface to review flagged attendance records
// - List all pending reviews
// - Filter by severity, rule type, date
// - Approve/Reject with notes
// - Bulk actions
?>
```

**Features**:
- Table showing: Employee | Date | Flagged Rules | Severity | Status | Actions
- Filter sidebar: By rule type, by severity, by date range
- Detail modal: Show full attendance info with flag explanations
- Quick actions: Approve, Reject, Request Info
- Bulk select: Multi-select and batch approve/reject

### Phase 3: Modify Payroll Calculation

**Modify**: `employee/cron/daily_payroll_calculation.php`

```php
// Only include approved attendance in payroll
$attendance_query = "SELECT a.* 
                     FROM attendance a
                     WHERE a.attendance_date = ?
                       AND a.employee_id = ?
                       AND a.time_in IS NOT NULL 
                       AND a.time_out IS NOT NULL
                       AND (a.review_status = 'approved' 
                            OR a.review_status IS NULL)  -- Backward compatible
                       AND a.id NOT IN (
                           SELECT attendance_id 
                           FROM attendance_review_queue 
                           WHERE status = 'pending'
                       )";
```

**Modify**: `employee/function/report.php`

```php
// Add review status check to attendance fallback query
$attendance_query = "SELECT a.employee_id, a.attendance_date, a.status, 
                            a.branch_name, a.time_in, a.time_out, a.total_ot_hrs,
                            e.first_name, e.last_name, e.employee_code, 
                            e.daily_rate, e.position
                     FROM attendance a
                     JOIN employees e ON a.employee_id = e.id
                     WHERE a.attendance_date BETWEEN ? AND ?
                       AND e.status = 'Active'
                       AND LOWER(e.position) = 'worker'
                       AND a.time_in IS NOT NULL 
                       AND a.time_out IS NOT NULL
                       AND (a.review_status = 'approved' OR a.review_status IS NULL)
                       AND a.id NOT IN (
                           SELECT attendance_id 
                           FROM attendance_review_queue 
                           WHERE status IN ('pending', 'rejected')
                       )";
```

### Phase 4: Notifications

**New File**: `employee/api/notify_review_needed.php`

```php
<?php
// Send notifications to admin when attendance needs review
// Supports: Email, In-app notification, Slack webhook

function sendReviewNotification($record, $flags) {
    $admin_emails = getAdminEmails();
    $severity = getHighestSeverity($flags);
    
    $subject = "[Attendance Review] {$record['first_name']} {$record['last_name']} - {$record['attendance_date']}";
    
    $body = "Attendance record requires review:\n\n";
    $body .= "Employee: {$record['first_name']} {$record['last_name']}\n";
    $body .= "Date: {$record['attendance_date']}\n";
    $body .= "Time: {$record['time_in']} - {$record['time_out']}\n";
    $body .= "Branch: {$record['branch_name']}\n\n";
    $body .= "Flags detected:\n";
    foreach ($flags as $flag) {
        $body .= "- [{$flag['severity']}] {$flag['rule']}: {$flag['message']}\n";
    }
    $body .= "\nReview at: https://yoursite.com/employee/attendance_review.php";
    
    // Send email
    mail($admin_emails, $subject, $body);
    
    // Add in-app notification
    addInAppNotification($record, $flags);
}
```

### Phase 5: Audit Trail

**New Table**: attendance_review_log
```sql
CREATE TABLE attendance_review_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attendance_id INT NOT NULL,
    action ENUM('flagged', 'approved', 'rejected', 'auto_approved') NOT NULL,
    performed_by INT,
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    flags JSON,
    notes TEXT,
    INDEX idx_attendance (attendance_id),
    INDEX idx_date (performed_at)
);
```

## Workflow

```
Employee clocks out
        ↓
Auto-detection runs (cron or webhook)
        ↓
Rules evaluated against completed record
        ↓
┌─────────────────┬─────────────────┐
│  Flags Found    │   No Flags      │
│     (Yes)       │    (Clean)      │
└────────┬────────┴────────┬────────┘
         ↓                 ↓
   Add to review      Auto-approve
   queue (pending)    Include in payroll
         ↓
   Notify admin
         ↓
   Admin reviews in
   attendance_review.php
         ↓
┌─────────────────┬─────────────────┐
│    Approved     │    Rejected     │
└────────┬────────┴────────┬────────┘
         ↓                 ↓
   Include in        Exclude from
   weekly_report     weekly_report
   Log action        Log action
```

## Configuration Options

**File**: `config/attendance_rules.php`

```php
<?php
return [
    'auto_detection' => [
        'enabled' => true,
        'run_interval' => '15 minutes',
        'auto_approve_clean' => true,  // Records with no flags auto-approved
    ],
    'rules' => [
        'min_hours' => ['enabled' => true, 'threshold' => 4.0],
        'max_hours' => ['enabled' => true, 'threshold' => 12.0],
        'rapid_reentry' => ['enabled' => true, 'minutes' => 15],
        'late_clockout' => ['enabled' => true, 'time' => '22:00'],
        'early_clockin' => ['enabled' => true, 'time' => '05:00'],
        'sunday_work' => ['enabled' => true],
        'multiple_records' => ['enabled' => true, 'max' => 3],
    ],
    'notifications' => [
        'email_admins' => true,
        'in_app' => true,
        'slack_webhook' => null,  // Set to webhook URL
    ],
    'severity_levels' => [
        'low' => ['color' => 'yellow', 'notify' => false],
        'medium' => ['color' => 'orange', 'notify' => true],
        'high' => ['color' => 'red', 'notify' => true, 'urgent' => true],
    ]
];
```

## Benefits

1. **Prevents payroll errors**: Suspicious records reviewed before payment
2. **Catches mistakes**: Employee typos, forgotten clock-outs caught early
3. **Audit trail**: Complete history of reviews and decisions
4. **Configurable**: Adjust rules based on company policy
5. **Non-blocking**: Clean records flow through automatically

## Implementation Phases

### Phase 1 (Week 1): Core Detection
- [ ] Create `attendance_review_queue` table
- [ ] Build `attendance_auto_detection.php` cron
- [ ] Implement 3 basic rules (min hours, max hours, rapid reentry)

### Phase 2 (Week 2): Admin Interface
- [ ] Build `attendance_review.php` UI
- [ ] Add approve/reject functionality
- [ ] Implement notification system

### Phase 3 (Week 3): Payroll Integration
- [ ] Modify `daily_payroll_calculation.php`
- [ ] Modify `function/report.php`
- [ ] Test end-to-end workflow

### Phase 4 (Week 4): Advanced Features
- [ ] Add remaining detection rules
- [ ] Build audit trail logging
- [ ] Create configuration UI
- [ ] Add analytics dashboard

---

**Ready to implement?** This system adds a safety layer that catches attendance issues before they affect payroll, while maintaining a smooth workflow for normal cases.
