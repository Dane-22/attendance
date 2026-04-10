# Same-Day Implementation Plan (ASAP)

Deploy a minimal viable solution today that resolves the audit.php/weekly_report.php discrepancy by implementing the core fix, basic auto-detection for severe violations, and essential admin review capability without full workflow automation.

## Hour-by-Hour Timeline

### Hour 1-2: Core Discrepancy Fix (Critical)

**File**: `employee/api/get_employee_attendance_detailed.php`

```php
// Line 94-95: Add time_out requirement
$sql = "SELECT
    a.attendance_date,
    a.time_in,
    a.time_out,
    a.status,
    a.branch_name,
    TIMESTAMPDIFF(MINUTE, a.time_in, a.time_out) / 60 as hours
FROM attendance a
WHERE a.employee_id = ?
  AND a.attendance_date BETWEEN ? AND ?
  AND a.time_in IS NOT NULL
  AND a.time_out IS NOT NULL  -- ADD THIS LINE
ORDER BY a.attendance_date ASC, a.time_in ASC";
```

**Test**: Verify Worker 1 now shows 4 days in audit.php calendar

---

### Hour 3-4: Database Setup

```sql
-- Quick database changes

-- 1. Add review status to attendance (takes 30 seconds)
ALTER TABLE attendance 
ADD COLUMN review_status ENUM('approved', 'rejected') NULL,
ADD COLUMN rejected_reason TEXT NULL,
ADD COLUMN processed_at TIMESTAMP NULL;

-- 2. Create simple review log (takes 30 seconds)
CREATE TABLE attendance_review_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attendance_id INT NOT NULL,
    employee_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    action ENUM('auto_rejected', 'manual_rejected') NOT NULL,
    reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_employee_date (employee_id, attendance_date),
    INDEX idx_date (created_at)
);
```

---

### Hour 5-6: Simple Auto-Detection Script

**New File**: `employee/cron/quick_attendance_check.php`

```php
<?php
/**
 * Quick attendance check - runs every hour
 * Auto-rejects only extreme violations
 */

require_once __DIR__ . '/../../conn/db_connection.php';

// Find records that should be auto-rejected
$sql = "SELECT id, employee_id, attendance_date, time_in, time_out,
        TIMESTAMPDIFF(MINUTE, time_in, time_out) / 60 as hours
        FROM attendance 
        WHERE (review_status IS NULL OR review_status = 'approved')
        AND (
            -- Extreme short: < 30 minutes
            TIMESTAMPDIFF(MINUTE, time_in, time_out) < 30
            OR 
            -- Future date
            attendance_date > CURDATE() + INTERVAL 1 DAY
        )";

$result = mysqli_query($db, $sql);

while ($record = mysqli_fetch_assoc($result)) {
    $reason = ($record['hours'] < 0.5) 
        ? "Extremely short: only {$record['hours']} hours"
        : "Future date detected";
    
    // Mark as rejected
    $update = "UPDATE attendance 
               SET review_status = 'rejected', 
                   rejected_reason = ?,
                   processed_at = NOW()
               WHERE id = ?";
    $stmt = mysqli_prepare($db, $update);
    mysqli_stmt_bind_param($stmt, 'si', $reason, $record['id']);
    mysqli_stmt_execute($stmt);
    
    // Log it
    $log = "INSERT INTO attendance_review_log 
            (attendance_id, employee_id, attendance_date, action, reason)
            VALUES (?, ?, ?, 'auto_rejected', ?)";
    $log_stmt = mysqli_prepare($db, $log);
    mysqli_stmt_bind_param($log_stmt, 'iiss', 
        $record['id'], 
        $record['employee_id'],
        $record['attendance_date'],
        $reason
    );
    mysqli_stmt_execute($log_stmt);
    
    error_log("[Auto-Rejected] Attendance ID {$record['id']}: {$reason}");
}

echo "Processed " . mysqli_num_rows($result) . " records\n";
```

**Add to cron**:
```bash
# Run every hour
0 * * * * php /var/www/html/employee/cron/quick_attendance_check.php >> /var/log/attendance_check.log 2>&1
```

---

### Hour 7-8: Update Payroll Queries

**File**: `employee/function/report.php` (around line 400)

```php
// Add to WHERE clause:
AND (a.review_status IS NULL OR a.review_status = 'approved')
```

**File**: `employee/cron/daily_payroll_calculation.php` (around line 150)

```php
// Add to validation query:
AND (a.review_status IS NULL OR a.review_status = 'approved')
```

---

### Hour 9-10: Simple Admin View

**New File**: `employee/attendance_rejected.php`

```php
<?php
// Simple page to view rejected records
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

$sql = "SELECT r.*, e.first_name, e.last_name, a.time_in, a.time_out,
        TIMESTAMPDIFF(MINUTE, a.time_in, a.time_out) / 60 as hours
        FROM attendance_review_log r
        JOIN employees e ON r.employee_id = e.id
        JOIN attendance a ON r.attendance_id = a.id
        WHERE r.created_at >= CURDATE() - INTERVAL 7 DAY
        ORDER BY r.created_at DESC";

$result = mysqli_query($db, $sql);
?>

<h1>Rejected Attendance (Last 7 Days)</h1>
<table>
    <tr>
        <th>Date</th>
        <th>Employee</th>
        <th>Time</th>
        <th>Hours</th>
        <th>Reason</th>
        <th>Rejected At</th>
    </tr>
    <?php while ($row = mysqli_fetch_assoc($result)): ?>
    <tr>
        <td><?= $row['attendance_date'] ?></td>
        <td><?= $row['first_name'] ?> <?= $row['last_name'] ?></td>
        <td><?= $row['time_in'] ?> - <?= $row['time_out'] ?></td>
        <td><?= round($row['hours'], 2) ?>h</td>
        <td><?= $row['reason'] ?></td>
        <td><?= $row['created_at'] ?></td>
    </tr>
    <?php endwhile; ?>
</table>
```

---

### Hour 11-12: Testing & Verification

**Test Checklist**:
- [ ] Worker 1 calendar shows 4 days (not 5)
- [ ] weekly_report.php shows 4 days (matches)
- [ ] Run quick_attendance_check.php - verify it works
- [ ] Check that 3-minute record is auto-rejected
- [ ] Verify payroll query excludes rejected records
- [ ] View rejected records page works

---

## What This Gives You Today

| Before | After |
|--------|-------|
| audit.php: 5 days | audit.php: 4 days ✓ |
| weekly_report.php: 4 days | weekly_report.php: 4 days ✓ |
| < 30 min records counted | < 30 min records auto-rejected ✓ |
| No visibility on rejections | Admin can view rejected records ✓ |
| Payroll includes bad data | Payroll excludes rejected records ✓ |

## What You DON'T Get Today (Phase 2)

- Email notifications (add tomorrow)
- Admin approve/reject buttons (add tomorrow)
- Full flag-for-review workflow (add tomorrow)
- Multiple detection rules (only extreme short + future date today)

## Go-Live Checklist

Before end of day:
- [ ] Core fix deployed (audit.php shows 4 days)
- [ ] Database migrated (columns added)
- [ ] Auto-detection cron running
- [ ] Payroll queries updated
- [ ] Admin can view rejected records
- [ ] Tested with real data

**Risk**: Minimal - only auto-rejects obviously bad data (< 30 min, future dates)

---

**Ready to implement this TODAY? Confirm for go signal.**
