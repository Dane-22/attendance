# Flag for Review Process

Implement a complete workflow for flagged attendance records, including admin notification, review queue interface, approval/rejection actions, payroll integration, and audit trail.

## Overview

When the auto-detection system flags a record as suspicious, it enters a **review queue** where an admin must approve or reject it before it can be included in payroll.

## Step-by-Step Process

### Step 1: Detection & Flagging

```php
// In attendance_auto_detection.php

function flagForReview($record, $flags, $priority, $db) {
    // 1. Insert into review queue
    $sql = "INSERT INTO attendance_review_queue 
            (attendance_id, employee_id, attendance_date, flags, priority, status, created_at)
            VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
    
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 'iisss', 
        $record['id'],
        $record['employee_id'], 
        $record['attendance_date'],
        json_encode($flags),
        $priority
    );
    mysqli_stmt_execute($stmt);
    
    // 2. Mark attendance as pending review
    $update_sql = "UPDATE attendance 
                   SET review_status = 'pending_review', 
                       flagged_at = NOW()
                   WHERE id = ?";
    $update_stmt = mysqli_prepare($db, $update_sql);
    mysqli_stmt_bind_param($update_stmt, 'i', $record['id']);
    mysqli_stmt_execute($update_stmt);
    
    // 3. Send notifications
    notifyAdmin($record, $flags, $priority);
    
    // 4. Log to audit trail
    logReviewAction($record['id'], 'flagged', $flags, null, $db);
}
```

### Step 2: Admin Notification

**Urgent (High Priority)** - Immediate notification:
```php
function notifyAdminUrgent($record, $flags) {
    $subject = "[URGENT] Attendance Needs Review - {$record['first_name']} {$record['last_name']}";
    $body = "
        Priority: URGENT
        Employee: {$record['first_name']} {$record['last_name']}
        Date: {$record['attendance_date']}
        Time: {$record['time_in']} - {$record['time_out']}
        
        Issues detected:
    ";
    foreach ($flags as $flag) {
        $body .= "- [{$flag['severity']}] {$flag['rule']}: {$flag['message']}\n";
    }
    $body .= "\nReview now: https://yoursite.com/employee/attendance_review.php?filter=urgent";
    
    // Send email + in-app notification
    mail(getAdminEmails(), $subject, $body);
    createInAppNotification($subject, 'urgent', $record);
}
```

**Normal Priority** - Daily digest:
```php
function notifyAdminNormal($records) {
    // Group all normal-priority flags into one daily email
    $subject = "Daily Attendance Review Summary - " . count($records) . " records flagged";
    // ... send at end of day
}
```

### Step 3: Review Queue Interface

**New Page**: `employee/attendance_review.php`

```php
<?php
// attendance_review.php - Admin review interface

require_once 'includes/auth_check.php';  // Admin only
require_once 'includes/header.php';

// Get filter from URL
$filter = $_GET['filter'] ?? 'pending';  // pending, urgent, approved, rejected, all
$priority = $_GET['priority'] ?? null;
?>

<div class="review-container">
    <h1>Attendance Review Queue</h1>
    
    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <a href="?filter=urgent" class="tab urgent <?= $filter == 'urgent' ? 'active' : '' ?>">
            Urgent <span class="badge"><?= getCount('urgent') ?></span>
        </a>
        <a href="?filter=pending" class="tab pending <?= $filter == 'pending' ? 'active' : '' ?>">
            Pending <span class="badge"><?= getCount('pending') ?></span>
        </a>
        <a href="?filter=approved" class="tab approved <?= $filter == 'approved' ? 'active' : '' ?>">
            Approved
        </a>
        <a href="?filter=rejected" class="tab rejected <?= $filter == 'rejected' ? 'active' : '' ?>">
            Rejected
        </a>
    </div>
    
    <!-- Review Table -->
    <table class="review-table">
        <thead>
            <tr>
                <th>Priority</th>
                <th>Employee</th>
                <th>Date</th>
                <th>Time</th>
                <th>Duration</th>
                <th>Flags</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (getReviewQueue($filter) as $item): ?>
            <tr class="priority-<?= $item['priority'] ?>">
                <td>
                    <span class="badge <?= $item['priority'] ?>">
                        <?= strtoupper($item['priority']) ?>
                    </span>
                </td>
                <td><?= $item['first_name'] ?> <?= $item['last_name'] ?></td>
                <td><?= $item['attendance_date'] ?></td>
                <td><?= date('h:i A', strtotime($item['time_in'])) ?> - <?= date('h:i A', strtotime($item['time_out'])) ?></td>
                <td><?= calculateHours($item['time_in'], $item['time_out']) ?> hrs</td>
                <td>
                    <?php foreach (json_decode($item['flags'], true) as $flag): ?>
                        <span class="flag-badge" title="<?= $flag['message'] ?>">
                            <?= $flag['rule'] ?>
                        </span>
                    <?php endforeach; ?>
                </td>
                <td>
                    <button onclick="approveRecord(<?= $item['attendance_id'] ?>)" class="btn-approve">
                        ✓ Approve
                    </button>
                    <button onclick="rejectRecord(<?= $item['attendance_id'] ?>)" class="btn-reject">
                        ✗ Reject
                    </button>
                    <button onclick="viewDetails(<?= $item['attendance_id'] ?>)" class="btn-view">
                        👁 View
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal for Reject/Approve with Notes -->
<div id="actionModal" class="modal" style="display:none;">
    <div class="modal-content">
        <h3 id="modalTitle">Review Record</h3>
        <div id="recordDetails"></div>
        <textarea id="adminNotes" placeholder="Add notes (optional)..." rows="4"></textarea>
        <div class="modal-actions">
            <button onclick="confirmAction('approve')" class="btn-approve">Approve</button>
            <button onclick="confirmAction('reject')" class="btn-reject">Reject</button>
            <button onclick="closeModal()" class="btn-cancel">Cancel</button>
        </div>
    </div>
</div>

<script>
// JavaScript for handling actions
let currentRecordId = null;

function approveRecord(id) {
    currentRecordId = id;
    document.getElementById('modalTitle').textContent = 'Approve Record';
    document.getElementById('actionModal').style.display = 'block';
}

function rejectRecord(id) {
    currentRecordId = id;
    document.getElementById('modalTitle').textContent = 'Reject Record';
    document.getElementById('actionModal').style.display = 'block';
}

function confirmAction(action) {
    const notes = document.getElementById('adminNotes').value;
    
    fetch('api/review_attendance.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            attendance_id: currentRecordId,
            action: action,
            notes: notes
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Record ' + action + 'ed successfully');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}
</script>
```

### Step 4: Review Action API

**New File**: `employee/api/review_attendance.php`

```php
<?php
// review_attendance.php - Handle approve/reject actions

require_once '../../conn/db_connection.php';
require_once '../includes/auth_check.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$attendance_id = $data['attendance_id'] ?? null;
$action = $data['action'] ?? null;  // 'approve' or 'reject'
$notes = $data['notes'] ?? '';
$admin_id = $_SESSION['user_id'] ?? null;

if (!$attendance_id || !$action || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Start transaction
mysqli_begin_transaction($db);

try {
    // 1. Update attendance record
    $new_status = ($action == 'approve') ? 'approved' : 'rejected';
    $attendance_sql = "UPDATE attendance 
                       SET review_status = ?,
                           reviewed_by = ?,
                           reviewed_at = NOW(),
                           review_notes = ?
                       WHERE id = ?";
    $stmt = mysqli_prepare($db, $attendance_sql);
    mysqli_stmt_bind_param($stmt, 'sisi', $new_status, $admin_id, $notes, $attendance_id);
    mysqli_stmt_execute($stmt);
    
    // 2. Update review queue
    $queue_sql = "UPDATE attendance_review_queue 
                   SET status = ?,
                       reviewed_by = ?,
                       reviewed_at = NOW(),
                       admin_notes = ?
                   WHERE attendance_id = ?";
    $queue_stmt = mysqli_prepare($db, $queue_sql);
    mysqli_stmt_bind_param($queue_stmt, 'sisi', $action, $admin_id, $notes, $attendance_id);
    mysqli_stmt_execute($queue_stmt);
    
    // 3. Log to audit trail
    $audit_sql = "INSERT INTO attendance_review_log 
                   (attendance_id, action, performed_by, notes, created_at)
                   VALUES (?, ?, ?, ?, NOW())";
    $audit_stmt = mysqli_prepare($db, $audit_sql);
    mysqli_stmt_bind_param($audit_stmt, 'isis', $attendance_id, $action, $admin_id, $notes);
    mysqli_stmt_execute($audit_stmt);
    
    // 4. If rejected, notify employee
    if ($action == 'reject') {
        notifyEmployeeRejection($attendance_id, $notes, $db);
    }
    
    // Commit transaction
    mysqli_commit($db);
    
    // 5. Recalculate payroll if this affects current period
    if ($action == 'approve') {
        queuePayrollRecalculation($attendance_id, $db);
    }
    
    echo json_encode(['success' => true, 'message' => "Record {$action}ed successfully"]);
    
} catch (Exception $e) {
    mysqli_rollback($db);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
```

### Step 5: Payroll Integration

**Modified**: `employee/function/report.php`

```php
// Only include approved attendance in payroll
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

**Modified**: `employee/cron/daily_payroll_calculation.php`

```php
// Same logic - only approved records
$validate_sql = "SELECT id, time_in, time_out, review_status 
                 FROM attendance 
                 WHERE employee_id = ? 
                   AND attendance_date = ?
                   AND time_in IS NOT NULL 
                   AND time_out IS NOT NULL
                   AND (review_status = 'approved' OR review_status IS NULL)";
```

### Step 6: Audit Trail

**Table**: `attendance_review_log`

```sql
CREATE TABLE attendance_review_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attendance_id INT NOT NULL,
    action ENUM('flagged', 'approved', 'rejected', 'auto_rejected') NOT NULL,
    performed_by INT,
    flags JSON NULL,  -- What flags were detected (for 'flagged' action)
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_attendance (attendance_id),
    INDEX idx_date (created_at)
);
```

**Usage**: Complete history of every decision made on attendance records.

### Step 7: Employee Notification (for Rejections)

```php
function notifyEmployeeRejection($attendance_id, $admin_notes, $db) {
    // Get employee details
    $sql = "SELECT e.email, e.first_name, e.last_name, a.attendance_date, 
                   a.time_in, a.time_out
            FROM attendance a
            JOIN employees e ON a.employee_id = e.id
            WHERE a.id = ?";
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $attendance_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $employee = mysqli_fetch_assoc($result);
    
    $subject = "Attendance Record Rejected - {$employee['attendance_date']}";
    $body = "
        Dear {$employee['first_name']},
        
        Your attendance record for {$employee['attendance_date']} has been rejected:
        
        Time: {$employee['time_in']} - {$employee['time_out']}
        
        Reason: {$admin_notes}
        
        This record will NOT be included in your payroll. Please contact HR if you 
        believe this was an error or if you need to correct your attendance.
        
        Best regards,
        HR Department
    ";
    
    mail($employee['email'], $subject, $body);
}
```

## Database Schema

```sql
-- Main review queue table
CREATE TABLE attendance_review_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attendance_id INT NOT NULL,
    employee_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    flags JSON NOT NULL,  -- Array of detected issues
    priority ENUM('urgent', 'normal') DEFAULT 'normal',
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    reviewed_by INT NULL,
    reviewed_at TIMESTAMP NULL,
    admin_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_attendance (attendance_id),
    INDEX idx_employee_date (employee_id, attendance_date),
    INDEX idx_status_priority (status, priority),
    INDEX idx_created (created_at)
);

-- Audit trail
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

-- Add columns to attendance table
ALTER TABLE attendance 
ADD COLUMN review_status ENUM('pending_review', 'approved', 'rejected') NULL,
ADD COLUMN reviewed_by INT NULL,
ADD COLUMN reviewed_at TIMESTAMP NULL,
ADD COLUMN review_notes TEXT NULL,
ADD COLUMN flagged_at TIMESTAMP NULL,
ADD INDEX idx_review_status (review_status);
```

## Complete Workflow Diagram

```
Employee clocks out
        ↓
Auto-detection runs
        ↓
┌─────────────────────────────────────┐
│  Extreme Short (< 30 min)           │ → AUTO-REJECT → Notify employee
│  Future Date                        │
└─────────────────────────────────────┘
        ↓ (if not auto-rejected)
Flags detected?
        ↓
┌─────────────────────────────────────┐
│  Urgent: > 16 hrs, 5+ same branch    │ → Flag + URGENT notification
│  Normal: 2-4 hrs, Sunday, 3-4 recs   │ → Flag + Daily digest
└─────────────────────────────────────┘
        ↓
Added to Review Queue (status: pending)
        ↓
Admin opens attendance_review.php
        ↓
Admin reviews record + flags
        ↓
┌─────────────────────────────────────┐
│  APPROVE                            │ → Update status → Include in payroll
│  REJECT + notes                     │ → Update status → Exclude + Notify employee
└─────────────────────────────────────┘
        ↓
Log action to audit trail
        ↓
Recalculate payroll if needed
```

## Summary

**Flag for Review Process** includes:
1. **Detection** - System identifies suspicious record
2. **Notification** - Admin gets alerted (urgent = immediate, normal = daily)
3. **Queue** - Record appears in review interface with all details
4. **Review** - Admin sees flags and decides approve/reject
5. **Action** - Status updated, audit logged, payroll adjusted
6. **Notification** - Employee notified if rejected

**Ready to implement this workflow?**
