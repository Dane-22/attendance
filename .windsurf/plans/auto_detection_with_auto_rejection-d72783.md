# Auto-Detection with Automatic Rejection

Implement attendance validation with tiered response: auto-approve clean records, flag anomalies for review, and automatically reject severe violations that clearly indicate errors or policy violations.

## Severity Levels & Actions

| Severity | Action | Examples |
|----------|--------|----------|
| **LOW** | Auto-approve | Late clock-out (10-11 PM), Early clock-in (4-5 AM) |
| **MEDIUM** | Flag for review | Short shift (2-4 hrs), Multiple records (3-4 times), Sunday work |
| **HIGH** | Auto-reject | Extreme short (< 30 min), Extreme long (> 16 hrs), 5+ same-day records |

## Auto-Rejection Rules (Severe Cases)

### Rule 1: Extreme Short Shift (< 30 minutes)
```php
if ($worked_hours < 0.5) {  // Less than 30 minutes
    $action = 'auto_reject';
    $reason = "Extremely short: only {$worked_hours} hours - likely system error";
}
```
**Example**: Apr 6 - 06:28 AM → 06:31 AM (3 minutes) = **AUTO-REJECT**

### Rule 2: Extreme Long Shift (> 16 hours)
```php
if ($worked_hours > 16.0) {
    $action = 'auto_reject';
    $reason = "Extremely long: {$worked_hours} hours - exceeds maximum allowed";
}
```

### Rule 3: Excessive Same-Day Records (5+)
```php
if ($same_day_count >= 5) {
    $action = 'auto_reject';
    $reason = "{$same_day_count} records in one day - system abuse or malfunction";
}
```

### Rule 4: Immediate Reentry (< 1 minute)
```php
if ($gap_seconds < 60) {
    $action = 'auto_reject';
    $reason = "Immediate reentry: {$gap_seconds} seconds - likely duplicate/error";
}
```

### Rule 5: Future Date/Tampering
```php
if (strtotime($attendance_date) > strtotime('+1 day')) {
    $action = 'auto_reject';
    $reason = "Future date detected - possible data tampering";
}
```

## Implementation

### Detection Logic

```php
<?php
// employee/cron/attendance_auto_detection.php

function evaluateAttendance($record, $rules) {
    $flags = [];
    $highest_severity = 'low';
    $action = 'auto_approve';  // Default
    
    $worked_hours = calculateWorkedHours($record['time_in'], $record['time_out']);
    
    // Check auto-rejection rules first (highest priority)
    
    // EXTREME SHORT - Auto-reject
    if ($worked_hours < 0.5) {
        return [
            'action' => 'auto_reject',
            'severity' => 'critical',
            'flags' => [['rule' => 'EXTREME_SHORT', 'message' => "Only {$worked_hours}h - system error"]],
            'reason' => 'Auto-rejected: Extremely short attendance record'
        ];
    }
    
    // EXTREME LONG - Auto-reject
    if ($worked_hours > 16.0) {
        return [
            'action' => 'auto_reject',
            'severity' => 'critical',
            'flags' => [['rule' => 'EXTREME_LONG', 'message' => "{$worked_hours}h exceeds 16h limit"]],
            'reason' => 'Auto-rejected: Exceeds maximum shift length'
        ];
    }
    
    // EXCESSIVE RECORDS - Auto-reject
    $same_day_count = getSameDayRecordCount($record);
    if ($same_day_count >= 5) {
        return [
            'action' => 'auto_reject',
            'severity' => 'critical',
            'flags' => [['rule' => 'EXCESSIVE_RECORDS', 'message' => "{$same_day_count} records same day"]],
            'reason' => 'Auto-rejected: Excessive attendance records'
        ];
    }
    
    // IMMEDIATE REENTRY - Auto-reject
    $previous = getPreviousRecord($record);
    if ($previous) {
        $gap = strtotime($record['time_in']) - strtotime($previous['time_out']);
        if ($gap < 60) {
            return [
                'action' => 'auto_reject',
                'severity' => 'critical',
                'flags' => [['rule' => 'IMMEDIATE_REENTRY', 'message' => "{$gap}s gap - duplicate"]],
                'reason' => 'Auto-rejected: Immediate clock-in after clock-out'
            ];
        }
    }
    
    // Check flag rules (medium severity - require admin review)
    
    // SHORT SHIFT - Flag
    if ($worked_hours < 4.0) {
        $flags[] = ['rule' => 'SHORT_SHIFT', 'severity' => 'medium', 'message' => "Only {$worked_hours}h worked"];
        $highest_severity = 'medium';
        $action = 'flag_for_review';
    }
    
    // LONG SHIFT - Flag
    if ($worked_hours > 12.0 && $worked_hours <= 16.0) {
        $flags[] = ['rule' => 'LONG_SHIFT', 'severity' => 'medium', 'message' => "{$worked_hours}h exceeds 12h"];
        $highest_severity = 'medium';
        $action = 'flag_for_review';
    }
    
    // MULTIPLE RECORDS - Flag
    if ($same_day_count >= 3 && $same_day_count < 5) {
        $flags[] = ['rule' => 'MULTIPLE_RECORDS', 'severity' => 'medium', 'message' => "{$same_day_count} records today"];
        $highest_severity = 'medium';
        $action = 'flag_for_review';
    }
    
    // SUNDAY WORK - Flag
    if (date('w', strtotime($record['attendance_date'])) == 0) {
        $flags[] = ['rule' => 'SUNDAY_WORK', 'severity' => 'medium', 'message' => 'Sunday attendance'];
        $highest_severity = 'medium';
        $action = 'flag_for_review';
    }
    
    // LOW severity - Auto-approve with note
    if ($highest_severity == 'low' || empty($flags)) {
        return [
            'action' => 'auto_approve',
            'severity' => 'low',
            'flags' => $flags,
            'reason' => 'Auto-approved: No issues detected'
        ];
    }
    
    return [
        'action' => $action,
        'severity' => $highest_severity,
        'flags' => $flags,
        'reason' => null
    ];
}
```

### Processing Results

```php
function processAttendance($record, $db) {
    $evaluation = evaluateAttendance($record, $rules);
    
    switch ($evaluation['action']) {
        case 'auto_approve':
            updateAttendanceStatus($record['id'], 'approved', $db);
            logDecision($record['id'], 'auto_approved', $evaluation, $db);
            break;
            
        case 'flag_for_review':
            addToReviewQueue($record, $evaluation['flags'], 'pending', $db);
            updateAttendanceStatus($record['id'], 'pending_review', $db);
            notifyAdmin($record, $evaluation['flags']);
            logDecision($record['id'], 'flagged', $evaluation, $db);
            break;
            
        case 'auto_reject':
            updateAttendanceStatus($record['id'], 'rejected', $db);
            logDecision($record['id'], 'auto_rejected', $evaluation, $db);
            notifyEmployeeAndAdmin($record, $evaluation);
            break;
    }
}
```

### Database Updates

```sql
-- Add status and rejection reason to attendance table
ALTER TABLE attendance 
ADD COLUMN review_status ENUM('approved', 'pending_review', 'rejected') DEFAULT 'approved',
ADD COLUMN review_reason TEXT NULL,
ADD COLUMN auto_processed BOOLEAN DEFAULT FALSE,
ADD COLUMN processed_at TIMESTAMP NULL;
```

## Notification Templates

### Auto-Reject Notification (to Employee)
```
Subject: Attendance Record Auto-Rejected - [Date]

Your attendance record for [Date] has been automatically rejected:

Time: [time_in] - [time_out]
Duration: [X hours]
Reason: [Auto-rejection reason]

This record will NOT be included in your payroll. 
Please contact HR if you believe this is an error.

[Company Name]
```

### Auto-Reject Notification (to Admin)
```
Subject: [CRITICAL] Attendance Auto-Rejected - [Employee Name]

An attendance record was automatically rejected due to severe violation:

Employee: [Name]
Date: [Date]
Time: [time_in] - [time_out]
Issue: [Rejection reason]
Flags: [List of rules triggered]

Action required: Review if employee needs training or system has issues.

View details: [Link to attendance record]
```

### Flag for Review Notification (to Admin)
```
Subject: Attendance Needs Review - [Employee Name] - [Date]

An attendance record requires your review:

Employee: [Name]
Date: [Date]
Time: [time_in] - [time_out]
Duration: [X hours]

Flags detected:
- [Rule]: [Description]

Please review and approve or reject within 24 hours.

Review now: [Link]
```

## Admin Review Interface Updates

### Review Queue Page
```php
// employee/attendance_review.php

// Filter tabs
$TABS = [
    'pending' => 'Needs Review',
    'auto_rejected' => 'Auto-Rejected',
    'approved' => 'Approved',
    'all' => 'All Records'
];

// For auto-rejected records, show:
// - Red warning badge
// - Auto-rejection reason
// - Option to "Override" if mistake
```

### Override Auto-Rejection
```php
// Allow admin to approve an auto-rejected record in special cases
function overrideRejection($attendance_id, $admin_notes, $db) {
    updateAttendanceStatus($attendance_id, 'approved', $db);
    logOverride($attendance_id, $admin_notes, $db);
    notifyEmployeeOfOverride($attendance_id, $db);
}
```

## Payroll Integration

```php
// Modified queries in report.php and daily_payroll_calculation.php

// Only include approved records
$where_clause = "AND a.review_status = 'approved'";

// Exclude rejected and pending review
$where_clause .= "AND a.review_status NOT IN ('rejected', 'pending_review')";
```

## Real Example: MENEL BENITEZ - Apr 6

**Record #1**: 06:28 AM → 06:31 AM (3 minutes)
- Worked hours: 0.05 hours
- Rule triggered: EXTREME_SHORT (< 30 min)
- **Action: AUTO-REJECT**
- Result: Not counted in payroll

**Record #2**: 06:31 AM → 04:04 PM (normal)
- No flags
- **Action: AUTO-APPROVE**
- Result: Counted in payroll

**Day total**: 1 day worked (only record #2 counts)

## Configuration

```php
// config/attendance_validation.php

return [
    'auto_rejection' => [
        'enabled' => true,
        'rules' => [
            'extreme_short' => ['enabled' => true, 'threshold' => 0.5],  // < 30 min
            'extreme_long' => ['enabled' => true, 'threshold' => 16.0],   // > 16 hrs
            'excessive_records' => ['enabled' => true, 'max' => 5],       // 5+ records
            'immediate_reentry' => ['enabled' => true, 'seconds' => 60],  // < 1 min gap
        ],
        'notifications' => [
            'email_employee' => true,
            'email_admin' => true,
            'urgent_for_critical' => true
        ]
    ],
    
    'flag_for_review' => [
        'rules' => [
            'short_shift' => ['enabled' => true, 'threshold' => 4.0],     // < 4 hrs
            'long_shift' => ['enabled' => true, 'threshold' => 12.0],      // > 12 hrs
            'multiple_records' => ['enabled' => true, 'max' => 3],        // 3+ records
            'sunday_work' => ['enabled' => true],
            'late_clockout' => ['enabled' => true, 'time' => '22:00'],
            'early_clockin' => ['enabled' => true, 'time' => '05:00'],
        ]
    ]
];
```

## Implementation Checklist

### Phase 1: Core Auto-Rejection
- [ ] Add review_status columns to attendance table
- [ ] Create auto-detection engine with rejection logic
- [ ] Implement 5 auto-rejection rules
- [ ] Test with historical data (MENEL BENITEZ Apr 6 record)

### Phase 2: Review Queue
- [ ] Create review queue database table
- [ ] Build admin review interface
- [ ] Add override functionality for auto-rejected records
- [ ] Implement notification system

### Phase 3: Payroll Integration
- [ ] Modify daily_payroll_calculation.php
- [ ] Modify function/report.php
- [ ] Ensure rejected records excluded from payroll
- [ ] Test end-to-end workflow

### Phase 4: Refinement
- [ ] Add configuration file
- [ ] Create admin dashboard for rule tuning
- [ ] Add analytics (rejection rates, common issues)
- [ ] Document for HR team

## Summary

**Auto-Reject**: Extreme violations that are clearly errors (< 30 min, > 16 hrs, 5+ records)
**Flag for Review**: Suspicious but possibly legitimate (2-4 hrs, 3-4 records, Sunday)
**Auto-Approve**: Normal attendance or minor deviations

This prevents payroll errors while minimizing admin workload - only truly problematic records need human review.
