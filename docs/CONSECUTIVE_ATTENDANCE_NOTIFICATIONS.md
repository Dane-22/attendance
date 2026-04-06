# Consecutive Attendance Notifications Documentation

## Overview

This feature automatically detects workers with 3+ consecutive days of Late/Absent attendance and sends notifications to Admin and Engineer position users.

## How It Works

### Detection Logic

1. **Schedule**: Script runs daily at 9:30 AM (after typical time-in window)
2. **Workdays**: Monday-Saturday (Sundays are excluded)
3. **Threshold**: 3 consecutive days with status `Late` or `Absent`
4. **Target Employees**: Only workers with `position = 'Worker'`
5. **Recipients**: All active `Admin` and `Engineer` position users

### Notification Behavior

- **Once per streak**: Notification sent when 3rd consecutive day detected
- **No spam**: No repeat notifications for same streak
- **Auto-reset**: New alert only when fresh streak detected (worker has a "Present" day between streaks)
- **Deduplication**: Uses `attendance_notification_log` table to track sent notifications

## File Structure

```
employee/
└── cron/
    └── consecutive_attendance_check.php    # Main scheduled script
docs/
└── CONSECUTIVE_ATTENDANCE_NOTIFICATIONS.md  # This documentation
```

## Installation

### 1. Ensure Dependencies Exist

The script uses existing system functions:
- `sendPushNotification()` - For web push notifications
- `logActivity()` - For activity logging
- Database connection from `conn/db_connection.php`

### 2. Create Log Table (Auto-Created)

The script automatically creates the `attendance_notification_log` table on first run:

```sql
CREATE TABLE IF NOT EXISTS attendance_notification_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    notification_type VARCHAR(50) NOT NULL,
    issue_count INT NOT NULL,
    issue_dates VARCHAR(255) NOT NULL,
    latest_issue_date DATE NOT NULL,
    notified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_employee_type (employee_id, notification_type),
    INDEX idx_latest_date (latest_issue_date)
) ENGINE=InnoDB;
```

### 3. Set Up Cron Job

Add to crontab:

```bash
# Run daily at 9:30 AM
30 9 * * * cd /var/www/jajr-project && php employee/cron/consecutive_attendance_check.php >> /var/log/attendance_alerts.log 2>&1
```

Or for Windows Task Scheduler:
- Program: `php.exe`
- Arguments: `C:\wamp64\www\main\employee\cron\consecutive_attendance_check.php`
- Schedule: Daily at 9:30 AM

## Manual Testing

Run manually to test:

```bash
cd /var/www/jajr-project
php employee/cron/consecutive_attendance_check.php
```

Expected output:
```
========================================
Consecutive Attendance Check
Started: 2026-04-06 09:30:00
========================================

Today: Monday - Running check...

Found X active workers to check

Checking: John Doe (W001)... OK (no consecutive issues)
Checking: Jane Smith (W002)... ALERT (3 consecutive issues)
  -> Notifying 2 recipients
  -> Sent to Admin User (Admin)
  -> Sent to Engineer User (Engineer)

========================================
Summary
========================================
Workers checked: X
Workers with consecutive issues: 1
Notifications sent: 2
Completed at: 2026-04-06 09:30:01
```

## Database Queries

### Check Notification Log

```sql
SELECT 
    anl.employee_id,
    e.first_name,
    e.last_name,
    anl.issue_count,
    anl.issue_dates,
    anl.latest_issue_date,
    anl.notified_at
FROM attendance_notification_log anl
JOIN employees e ON e.id = anl.employee_id
WHERE anl.notification_type = 'consecutive_attendance'
ORDER BY anl.notified_at DESC
LIMIT 20;
```

### Find Workers with Consecutive Issues (Manual Check)

```sql
WITH WorkerAttendance AS (
  SELECT 
    a.employee_id,
    a.attendance_date,
    a.status,
    ROW_NUMBER() OVER (PARTITION BY a.employee_id ORDER BY a.attendance_date DESC) as rn
  FROM attendance a
  JOIN employees e ON e.id = a.employee_id
  WHERE a.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    AND DAYOFWEEK(a.attendance_date) != 1  -- Exclude Sunday
    AND e.position = 'Worker'
    AND e.status = 'Active'
)
SELECT 
  employee_id,
  COUNT(*) as consecutive_count,
  GROUP_CONCAT(CONCAT(attendance_date, ':', status) ORDER BY attendance_date DESC SEPARATOR ' | ') as details
FROM WorkerAttendance
WHERE rn <= 3
  AND status IN ('Late', 'Absent')
GROUP BY employee_id
HAVING consecutive_count >= 3;
```

## Notification Message Format

**Title:** `Attendance Alert: Consecutive Issues`

**Message:**
```
John Doe (W001) has 3 consecutive attendance issues:
- Apr 06: Late
- Apr 05: Absent
- Apr 04: Late

Branch: Main Office
```

**Link:** `/employee/audit.php?search=W001&search_type=all`

## Configuration

Edit `employee/cron/consecutive_attendance_check.php` to customize:

```php
// Threshold Settings
$consecutiveThreshold = 3;  // Days of consecutive issues to trigger
$workdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$monitorPositions = ['Worker'];  // Which positions to monitor
$notifyPositions = ['Admin', 'Engineer'];  // Which positions to notify
$lookbackDays = 14;  // How many days to look back
```

## Troubleshooting

### Issue: No notifications being sent

**Check:**
1. Are there workers with `position = 'Worker'` and `status = 'Active'`?
2. Do they have 3+ consecutive days with status `Late` or `Absent`?
3. Is the script running on a workday (Mon-Sat)?
4. Are there Admin/Engineer users with `status = 'Active'`?

**Debug:**
```sql
-- Check worker attendance status
SELECT 
    a.employee_id,
    e.first_name,
    e.last_name,
    a.attendance_date,
    a.status,
    a.time_in
FROM attendance a
JOIN employees e ON e.id = a.employee_id
WHERE e.position = 'Worker'
  AND a.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 5 DAY)
ORDER BY a.employee_id, a.attendance_date DESC;
```

### Issue: Duplicate notifications

**Check:**
```sql
-- Check if notification log table exists and has records
SELECT COUNT(*) as total_logs 
FROM attendance_notification_log 
WHERE notification_type = 'consecutive_attendance';
```

**Fix:** The deduplication logic checks `latest_issue_date` to prevent duplicates.

### Issue: Push notifications not working

**Check:**
1. VAPID keys configured in `.env` file
2. Users have push subscriptions in `push_subscriptions` table
3. Web Push library installed (`composer require minishlink/web-push`)

## Related Files

- `functions.php` - Contains `sendPushNotification()` function
- `employee/notification.php` - Super Admin notification dashboard
- `employee/admin_notification.php` - Admin notification dashboard
- `scheduled_attendance_notifications.php` - Other scheduled notifications

## Activity Logging

All alerts are logged to `activity_logs` table:
- **Action:** `Attendance Alert`
- **Details:** Contains worker name, consecutive days count, and recipient count

Example:
```
Consecutive attendance alert sent for John Doe (W001): 
3 consecutive days with issues. Notified 2 staff.
```

## Future Enhancements

Possible improvements:
1. Configurable threshold per branch
2. Email notifications in addition to push
3. Weekly summary reports
4. Integration with HR escalation workflow
5. Configurable workdays per employee/branch
