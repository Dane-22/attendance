# Automatic Clock Out Feature in select_employee.php

## Overview

The `select_employee.php` file includes an **automatic clock out** feature as part of its QR scan functionality. When an employee scans their QR code while already being clocked in, the system automatically records a time-out instead of failing with an error.

## How It Works

### Trigger Condition

The automatic clock out is triggered when:
1. A QR scan request is received (`auto_timein` and `emp_id` parameters present)
2. The employee is **already clocked in** (the clock-in attempt returns a message containing "already clocked in")

### Code Location

```php
@/employee/select_employee.php:38-100
```

### Implementation Flow

1. **Initial Clock-In Attempt** (line 59)
   ```php
   $clockInResult = performClockIn($db, $qrEmployeeId, $employee['employee_code'], $branchName);
   ```

2. **Check for Already Clocked In** (line 71)
   ```php
   if (stripos($msg, 'already clocked in') !== false) {
   ```

3. **Automatic Clock-Out Trigger** (line 72)
   ```php
   $clockOutResult = performClockOut($db, $qrEmployeeId, $employee['employee_code'], $branchName);
   ```

4. **Result Handling**
   - If clock-out succeeds: Success message displayed with time-out timestamp
   - If clock-out fails: Error message returned with failure reason

### User Experience

When an already clocked-in employee scans their QR code:
- Instead of an error: "Time-out recorded at [HH:MM AM/PM]"
- The system seamlessly transitions from clock-in attempt to clock-out completion

## Dependencies

- `performClockIn()` - Located in `@/employee/function/clock_functions.php`
- `performClockOut()` - Located in `@/employee/function/clock_functions.php`

## Security Considerations

- Employee ID is validated via `intval()` before processing (line 41)
- Database queries use prepared statements for employee lookup (lines 45-53)
- Branch information is fetched alongside employee data for audit logging

## Related Files

- `@/employee/function/clock_functions.php` - Core clock-in/clock-out logic
- `@/employee/select_employee.php` - Main file with QR scan handling
- `@/docs/select_employee.md` - General select_employee documentation
