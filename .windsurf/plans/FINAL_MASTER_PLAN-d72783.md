# Final Master Plan: Attendance Discrepancy Resolution & Auto-Detection System

Implement a comprehensive solution to resolve audit.php/weekly_report.php count discrepancies by adding attendance validation with tiered response: auto-approve clean records, auto-reject clear errors, and flag suspicious patterns for admin review before payroll inclusion.

## Phase 1: Fix Core Discrepancy (Week 1)

### Problem
- audit.php shows 5 days, weekly_report.php shows 4 days for same employee
- Root cause: audit.php requires only `time_in`, weekly_report.php requires both `time_in` AND `time_out`

### Solution
**File**: `employee/api/get_employee_attendance_detailed.php`

**Change**: Line 95
```php
// BEFORE:
AND a.time_in IS NOT NULL

// AFTER:
AND a.time_in IS NOT NULL
AND a.time_out IS NOT NULL
```

**Result**: Calendar only shows complete days that count toward payroll

---

## Phase 2: Auto-Detection & Validation System (Weeks 2-3)

### 2.1 Database Changes

```sql
-- Add review columns to attendance table
ALTER TABLE attendance 
ADD COLUMN review_status ENUM('pending_review', 'approved', 'rejected', 'auto_rejected') NULL,
ADD COLUMN reviewed_by INT NULL,
ADD COLUMN reviewed_at TIMESTAMP NULL,
ADD COLUMN review_notes TEXT NULL,
ADD COLUMN flagged_at TIMESTAMP NULL,
ADD COLUMN auto_processed BOOLEAN DEFAULT FALSE,
ADD INDEX idx_review_status (review_status);

-- Create review queue table
CREATE TABLE attendance_review_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attendance_id INT NOT NULL,
    employee_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    flags JSON NOT NULL,
    priority ENUM('urgent', 'normal') DEFAULT 'normal',
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    reviewed_by INT NULL,
    reviewed_at TIMESTAMP NULL,
    admin_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_attendance (attendance_id),
    INDEX idx_employee_date (employee_id, attendance_date),
    INDEX idx_status_priority (status, priority)
);

-- Create audit log table
CREATE TABLE attendance_review_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attendance_id INT NOT NULL,
    action ENUM('flagged', 'approved', 'rejected', 'auto_rejected') NOT NULL,
    performed_by INT,
    flags JSON NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_attendance (attendance_id),
    INDEX idx_date (created_at)
);
```

### 2.2 Auto-Detection Engine

**New File**: `employee/cron/attendance_auto_detection.php`

```php
<?php
/**
 * Attendance Auto-Detection System
 * Run every 15 minutes via cron
 */

require_once __DIR__ . '/../../conn/db_connection.php';

function evaluateAttendance($record, $all_day_records) {
    $worked_hours = calculateHours($record['time_in'], $record['time_out']);
    
    // ===== AUTO-REJECT RULES =====
    
    // 1. Extreme Short (< 30 min)
    if ($worked_hours < 0.5) {
        return [
            'action' => 'auto_reject',
            'reason' => 'EXTREME_SHORT',
            'message' => "Only {$worked_hours}h - system error"
        ];
    }
    
    // 2. Future Date
    if (strtotime($record['attendance_date']) > time() + 86400) {
        return [
            'action' => 'auto_reject',
            'reason' => 'FUTURE_DATE',
            'message' => 'Future date - possible tampering'
        ];
    }
    
    // ===== FLAG FOR REVIEW RULES =====
    
    $flags = [];
    $priority = 'normal';
    
    // Urgent: Long Shift (> 16 hrs)
    if ($worked_hours > 16.0) {
        $flags[] = ['rule' => 'LONG_SHIFT', 'severity' => 'high', 'message' => "{$worked_hours}h - verify clock-out"];
        $priority = 'urgent';
    }
    
    // Urgent: Excessive Same-Branch (5+)
    $branch_counts = countRecordsPerBranch($all_day_records);
    $max_branch = max($branch_counts);
    if ($max_branch >= 5) {
        $flags[] = ['rule' => 'EXCESSIVE_SAME_BRANCH', 'severity' => 'high', 'message' => "{$max_branch} at same branch"];
        $priority = 'urgent';
    }
    
    // Urgent: Excessive Total (8+)
    $total_count = count($all_day_records);
    if ($total_count >= 8) {
        $flags[] = ['rule' => 'EXCESSIVE_TOTAL', 'severity' => 'high', 'message' => "{$total_count} records"];
        $priority = 'urgent';
    }
    
    // Normal: Short Shift (2-4 hrs)
    if ($worked_hours < 4.0 && $worked_hours >= 0.5) {
        $flags[] = ['rule' => 'SHORT_SHIFT', 'severity' => 'medium', 'message' => "Only {$worked_hours}h"];
    }
    
    // Normal: Multiple Records (3-7)
    if ($total_count >= 3 && $total_count < 8 && $max_branch < 5) {
        $flags[] = ['rule' => 'MULTIPLE_RECORDS', 'severity' => 'medium', 'message' => "{$total_count} records"];
    }
    
    // Normal: Sunday Work
    if (date('w', strtotime($record['attendance_date'])) == 0) {
        $flags[] = ['rule' => 'SUNDAY_WORK', 'severity' => 'medium', 'message' => 'Sunday work'];
    }
    
    if (!empty($flags)) {
        return [
            'action' => 'flag_for_review',
            'priority' => $priority,
            'flags' => $flags
        ];
    }
    
    // ===== AUTO-APPROVE =====
    return ['action' => 'auto_approve'];
}

// Main processing loop
$sql = "SELECT * FROM attendance 
        WHERE time_in IS NOT NULL AND time_out IS NOT NULL
        AND (review_status IS NULL OR review_status = 'approved')
        AND auto_processed = FALSE
        LIMIT 100";

$result = mysqli_query($db, $sql);

while ($record = mysqli_fetch_assoc($result)) {
    // Get all records for this employee on this date
    $day_records = getDayRecords($record['employee_id'], $record['attendance_date'], $db);
    
    $evaluation = evaluateAttendance($record, $day_records);
    
    switch ($evaluation['action']) {
        case 'auto_approve':
            markApproved($record['id'], $db);
            break;
            
        case 'auto_reject':
            markAutoRejected($record['id'], $evaluation['reason'], $db);
            notifyEmployeeRejection($record, $evaluation['message']);
            break;
            
        case 'flag_for_review':
            addToReviewQueue($record, $evaluation['flags'], $evaluation['priority'], $db);
            notifyAdmin($record, $evaluation['flags'], $evaluation['priority']);
            break;
    }
}
```

### 2.3 Cron Job Setup

Add to crontab:
```bash
# Run auto-detection every 15 minutes
*/15 * * * * php /var/www/html/employee/cron/attendance_auto_detection.php >> /var/log/attendance_detection.log 2>&1
```

---

## Phase 3: Admin Review Interface (Week 3)

### 3.1 Review Queue Page

**New File**: `employee/attendance_review.php`

```php
<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

$filter = $_GET['filter'] ?? 'pending';
$queue = getReviewQueue($filter);
?>

<div class="review-container">
    <h1>Attendance Review Queue</h1>
    
    <!-- Tabs -->
    <div class="filter-tabs">
        <a href="?filter=urgent" class="tab urgent">Urgent (<?= getCount('urgent') ?>)</a>
        <a href="?filter=pending" class="tab pending">Pending (<?= getCount('pending') ?>)</a>
        <a href="?filter=approved" class="tab approved">Approved</a>
        <a href="?filter=rejected" class="tab rejected">Rejected</a>
    </div>
    
    <!-- Table -->
    <table class="review-table">
        <thead>
            <tr>
                <th>Priority</th>
                <th>Employee</th>
                <th>Date</th>
                <th>Time</th>
                <th>Hours</th>
                <th>Flags</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($queue as $item): ?>
            <tr>
                <td><span class="badge <?= $item['priority'] ?>"><?= $item['priority'] ?></span></td>
                <td><?= $item['name'] ?></td>
                <td><?= $item['attendance_date'] ?></td>
                <td><?= formatTime($item['time_in']) ?> - <?= formatTime($item['time_out']) ?></td>
                <td><?= calculateHours($item['time_in'], $item['time_out']) ?>h</td>
                <td><?= displayFlags($item['flags']) ?></td>
                <td>
                    <button onclick="approve(<?= $item['attendance_id'] ?>)" class="btn-approve">✓ Approve</button>
                    <button onclick="reject(<?= $item['attendance_id'] ?>)" class="btn-reject">✗ Reject</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
```

### 3.2 Review Action API

**New File**: `employee/api/review_attendance.php`

```php
<?php
require_once '../../conn/db_connection.php';
require_once '../includes/auth_check.php';

$data = json_decode(file_get_contents('php://input'), true);
$attendance_id = $data['attendance_id'];
$action = $data['action'];  // 'approve' or 'reject'
$notes = $data['notes'] ?? '';
$admin_id = $_SESSION['user_id'];

mysqli_begin_transaction($db);

try {
    // Update attendance
    $status = ($action == 'approve') ? 'approved' : 'rejected';
    $sql = "UPDATE attendance SET review_status = ?, reviewed_by = ?, reviewed_at = NOW(), review_notes = ? WHERE id = ?";
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 'sisi', $status, $admin_id, $notes, $attendance_id);
    mysqli_stmt_execute($stmt);
    
    // Update queue
    $queue_sql = "UPDATE attendance_review_queue SET status = ?, reviewed_by = ?, reviewed_at = NOW(), admin_notes = ? WHERE attendance_id = ?";
    $queue_stmt = mysqli_prepare($db, $queue_sql);
    mysqli_stmt_bind_param($queue_stmt, 'sisi', $action, $admin_id, $notes, $attendance_id);
    mysqli_stmt_execute($queue_stmt);
    
    // Log
    $log_sql = "INSERT INTO attendance_review_log (attendance_id, action, performed_by, notes) VALUES (?, ?, ?, ?)";
    $log_stmt = mysqli_prepare($db, $log_sql);
    mysqli_stmt_bind_param($log_stmt, 'isis', $attendance_id, $action, $admin_id, $notes);
    mysqli_stmt_execute($log_stmt);
    
    // Notify if rejected
    if ($action == 'reject') {
        notifyEmployeeRejection($attendance_id, $notes, $db);
    }
    
    mysqli_commit($db);
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    mysqli_rollback($db);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
```

---

## Phase 4: Payroll Integration (Week 3)

### 4.1 Modify Payroll Queries

**File**: `employee/function/report.php`

```php
// Add review status filter
$attendance_query = "SELECT a.*, e.first_name, e.last_name, e.daily_rate
                     FROM attendance a
                     JOIN employees e ON a.employee_id = e.id
                     WHERE a.attendance_date BETWEEN ? AND ?
                       AND a.time_in IS NOT NULL 
                       AND a.time_out IS NOT NULL
                       AND (a.review_status = 'approved' OR a.review_status IS NULL)
                       AND a.id NOT IN (
                           SELECT attendance_id 
                           FROM attendance_review_queue 
                           WHERE status = 'pending'
                       )";
```

**File**: `employee/cron/daily_payroll_calculation.php`

```php
// Same filter in daily payroll
$validate_sql = "SELECT id, time_in, time_out, review_status 
                 FROM attendance 
                 WHERE employee_id = ? 
                   AND attendance_date = ?
                   AND time_in IS NOT NULL 
                   AND time_out IS NOT NULL
                   AND (review_status = 'approved' OR review_status IS NULL)";
```

---

## Phase 5: Notifications (Week 4)

### 5.1 Admin Notification

```php
function notifyAdmin($record, $flags, $priority) {
    if ($priority == 'urgent') {
        // Immediate email
        $subject = "[URGENT] Attendance Review: {$record['first_name']} {$record['last_name']}";
        sendAdminEmail($subject, buildUrgentEmail($record, $flags));
        createInAppAlert($record, $flags);
    } else {
        // Add to daily digest queue
        addToDailyDigest($record, $flags);
    }
}
```

### 5.2 Employee Notification (on Rejection)

```php
function notifyEmployeeRejection($record, $reason) {
    $subject = "Attendance Record Rejected - {$record['attendance_date']}";
    $body = "Your attendance for {$record['attendance_date']} was rejected.\n\nReason: {$reason}\n\nThis will not be included in payroll.";
    mail($record['email'], $subject, $body);
}
```

---

## Summary of Changes

### Files to Create
1. `employee/cron/attendance_auto_detection.php` - Detection engine
2. `employee/attendance_review.php` - Admin interface
3. `employee/api/review_attendance.php` - Action handler
4. `employee/api/get_incomplete_attendance.php` - For notifications

### Files to Modify
1. `employee/api/get_employee_attendance_detailed.php` - Add time_out filter
2. `employee/function/report.php` - Add review_status filter
3. `employee/cron/daily_payroll_calculation.php` - Add review_status filter

### Database Changes
1. Add columns to `attendance` table
2. Create `attendance_review_queue` table
3. Create `attendance_review_log` table

---

## Timeline

| Phase | Duration | Deliverables |
|-------|----------|--------------|
| Phase 1 | Week 1 | Core discrepancy fixed, calendar shows 4 days |
| Phase 2 | Weeks 2-3 | Auto-detection running, database ready |
| Phase 3 | Week 3 | Admin can review and approve/reject |
| Phase 4 | Week 3 | Payroll only includes approved records |
| Phase 5 | Week 4 | Notifications working, system complete |

---

## Expected Results

**Before**:
- audit.php: 5 days (shows incomplete records)
- weekly_report.php: 4 days (only complete records)
- Confusion about discrepancy

**After**:
- audit.php: 4 days (only complete records shown)
- weekly_report.php: 4 days (only approved records counted)
- Suspicious records auto-rejected or flagged for review
- Admin has control over edge cases
- Clear audit trail of all decisions

---

**Ready to implement? Confirm to proceed.**
