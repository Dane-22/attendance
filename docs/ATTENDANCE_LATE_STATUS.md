# Attendance Late Status Documentation

## Overview

This document explains how the system determines if a worker is **Late** or **Present** based on their time-in.

## Cutoff Time

**Official Cutoff: 7:15 AM (Philippine Time)**

Workers start at 7:00 AM with a 15-minute grace period.

```php
// From: employee/function/attendance.php (line 25)
$cutoffTime = '07:15'; // 7:15 AM cutoff - workers start at 7:00 AM, 15 min grace period
```

## Status Determination

### Present (On Time)
- Worker clocks in **before or at 7:15 AM**
- `time_in <= 07:15:00`
- Status set to: `Present`

### Late
- Worker clocks in **after 7:15 AM**
- `time_in > 07:15:00`
- Status set to: `Late`

### Absent
- Worker has **no time-in record** for the day
- No attendance record exists, OR
- Attendance record exists but `time_in` is NULL
- Status treated as: `Absent`

## Time Zone

All times are in **Philippine Time (PHT, UTC+08:00)**

```php
// Database connection sets timezone
date_default_timezone_set('Asia/Manila');
```

## How It Works in Code

### During Clock-In (select_employee.php)

```php
// 1. Get current Philippine time
$currentTime = date('H:i'); // Format: 08:45

// 2. Compare against cutoff
$cutoffTime = '09:00';
$isBeforeCutoff = $currentTime < $cutoffTime;

// 3. Set status based on time
if ($isBeforeCutoff) {
    $status = 'Present';
} else {
    $status = 'Late';
}
```

### Database Insert

```sql
INSERT INTO attendance (
    employee_id, 
    attendance_date, 
    time_in, 
    status,  -- 'Present' or 'Late'
    branch_name,
    created_at
) VALUES (?, CURDATE(), NOW(), ?, ?, NOW());
```

## Examples

| Clock-In Time | Status | Reason |
|--------------|--------|--------|
| 06:30 AM | Present | Before 7:15 AM cutoff |
| 07:00 AM | Present | Before 7:15 AM cutoff |
| 07:14 AM | Present | Before 7:15 AM cutoff |
| 07:15 AM | Present | Exactly at cutoff (grace period end) |
| 07:16 AM | Late | After 7:15 AM cutoff |
| 08:30 AM | Late | After 7:15 AM cutoff |
| No time-in | Absent | No attendance recorded |

## QR Scanner Time Restriction

The QR scanner on the login page has an additional time restriction:

```php
// From: login.php
$scannerStartTime = '06:40:00';  // QR scanner enabled from 6:40 AM
$scannerEnabled = $currentTime >= $scannerStartTime;
```

This means workers can scan QR codes starting at **6:40 AM**, but will be marked **Late** if they clock in after 9:00 AM.

## Scheduled Notifications

The consecutive attendance notification system runs at:

```bash
# Cron schedule
30 9 * * *  # Daily at 9:30 AM (after cutoff time)
```

This ensures all time-ins for the day have been recorded before checking for consecutive lates.

## Viewing Late Records

### For Admins/Engineers
Navigate to: **Employee Audit Page**
```
/employee/audit.php
```

Filter by:
- Date range
- Employee name/code
- Branch

### Database Query

```sql
-- Get all late records for today
SELECT 
    a.id,
    e.first_name,
    e.last_name,
    e.employee_code,
    a.attendance_date,
    a.time_in,
    a.status,
    a.branch_name
FROM attendance a
JOIN employees e ON e.id = a.employee_id
WHERE a.attendance_date = CURDATE()
  AND a.status = 'Late'
ORDER BY a.time_in DESC;
```

## Related Files

| File | Purpose |
|------|---------|
| `employee/function/attendance.php` | Core attendance logic with cutoff time |
| `employee/select_employee.php` | Clock-in interface and status determination |
| `login.php` | QR scanner with time restrictions |
| `employee/audit.php` | View attendance records and filter by status |

## Configuration

To change the cutoff time, edit:

```php
// employee/function/attendance.php (line 25)
$cutoffTime = '09:00'; // Change to desired time (e.g., '08:30')
```

**Note:** Changing this will only affect NEW time-ins. Existing records remain unchanged.

## Summary

- **Cutoff:** 9:00 AM Philippine Time
- **Present:** Clock in at or before 9:00 AM
- **Late:** Clock in after 9:00 AM
- **Absent:** No time-in recorded
- **QR Scanner Opens:** 6:40 AM
