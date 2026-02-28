# Handling Forgotten Clock-Out in select_employee.php

## Overview

When an employee **forgets to clock out**, the system handles this through a combination of **automatic detection**, **manual admin correction**, and **QR scan auto-recovery**.

## What Happens When Clock-Out Is Missed

### 1. Attendance Record Stays Open

When an employee clocks in but forgets to clock out:
- The attendance record remains with `time_out IS NULL`
- The `is_time_running` flag stays at `1` (if column exists)
- The employee appears as "Present" with an "open shift" in the UI

**Database State:**
```sql
SELECT id, employee_id, time_in, time_out, is_time_running 
FROM attendance 
WHERE attendance_date = CURDATE() 
AND time_in IS NOT NULL 
AND time_out IS NULL;
```

### 2. Payroll Impact

**Records without time_out are EXCLUDED from payroll calculations** (`@/employee/function/report.php:188-192`):

```php
// Only include attendance in report totals AFTER employee has timed out
$time_in = $row['time_in'] ?? null;
$time_out = $row['time_out'] ?? null;
if (empty($time_in) || empty($time_out)) {
    continue;  // Skip this record - no pay calculated
}
```

**Consequence:** Employee won't get paid for that day until clock-out is recorded.

## How to Resolve Forgotten Clock-Out

### Option 1: Admin Manual Clock-Out (Same Day)

An admin or supervisor can manually clock out the employee through the employee list interface:

1. Go to `select_employee.php`
2. Select the employee's project/branch
3. Find the employee with "Time Out" button showing
4. Click **Time Out** button

**Code Reference:**
- `@/employee/js/attendance.js:753-764` - `toggleShift()` function
- `@/employee/js/attendance.js:613-618` - Shows "Time Out" button for `hasOpenShift`

### Option 2: QR Scan Auto Clock-Out (Next Day)

When the employee returns the next day and scans their QR code:

**Flow:**
1. QR scan attempts clock-in (`@/employee/select_employee.php:59`)
2. System detects "already clocked in" from previous day (`@/employee/select_employee.php:71`)
3. **Automatically triggers clock-out** instead (`@/employee/select_employee.php:72`)
4. Then allows the new clock-in

```php
// Lines 70-85 in select_employee.php
if (stripos($msg, 'already clocked in') !== false) {
    $clockOutResult = performClockOut($db, $qrEmployeeId, $employee['employee_code'], $branchName);
    
    if ($clockOutResult['success']) {
        $qrScanResult = [
            'success' => true,
            'message' => $employee['first_name'] . ' ' . $employee['last_name'] . ' time-out recorded at ' . ($clockOutResult['time_out'] ?? date('h:i A')),
            'time_out' => $clockOutResult['time_out'] ?? null
        ];
    }
}
```

### Option 3: Direct performClockOut() Call

For custom implementations, use the core function:

```php
require('function/clock_functions.php');

$result = performClockOut($db, $employeeId, $employeeCode, $branchName);

if ($result['success']) {
    echo "Clocked out at: " . $result['time_out'];
    echo "Hours worked: " . $result['hours_worked'];
} else {
    echo "Error: " . $result['message'];
}
```

**Location:** `@/employee/function/clock_functions.php:382-488`

## Technical Details

### Detection of Open Shifts

**Clock-in check** (`@/employee/function/clock_functions.php:64-76`):
```php
$checkSql = "SELECT id FROM attendance 
             WHERE employee_id = ? 
             AND attendance_date = CURDATE() 
             AND time_in IS NOT NULL 
             AND time_out IS NULL";
```

### Hours Calculation on Late Clock-Out

When clock-out happens the next day, hours are calculated from:
- `time_in` (previous day) to `NOW()` (current time)

**Note:** This may result in inflated hours (e.g., 24+ hours if overnight). Consider admin review for accuracy.

## No Automatic End-of-Day Clock-Out

**Important:** The system does NOT automatically clock out employees at midnight.

**Cron jobs** (`@/employee/cron/daily_payroll_calculation.php`) only process completed records:
```php
// Calculate worked hours
$worked_hours = 0;
if ($time_in && $time_out) {  // Only if BOTH exist
    $start_ts = strtotime($time_in);
    $end_ts = strtotime($time_out);
    if ($start_ts !== false && $end_ts !== false && $end_ts > $start_ts) {
        $worked_hours = ($end_ts - $start_ts) / 3600;
    }
}
```

## Best Practices

1. **Monitor open shifts** - Check for employees with `time_out IS NULL` at end of day
2. **Proactive admin clock-out** - Supervisors should clock out remaining employees before leaving
3. **Employee training** - Ensure employees understand QR scan auto-recovery feature
4. **Payroll review** - Verify all open shifts are resolved before running payroll

## Related Files

- `@/employee/select_employee.php:70-85` - QR scan auto clock-out logic
- `@/employee/function/clock_functions.php:382-488` - Core performClockOut() function
- `@/employee/function/clock_functions.php:41-376` - Core performClockIn() function
- `@/employee/js/attendance.js:613-618` - UI "Time Out" button display
- `@/employee/function/report.php:188-192` - Payroll exclusion of open records
