# Select Employee - Overtime Hours Analysis

## Summary

**Question:** If an overtime request has been approved, will the "Total overtime hours" be inserted into the database table even if the employee has not timed in?

**Answer: NO** - Approved overtime hours will **NOT** be inserted into the attendance table if the employee has not timed in.

## Current System Behavior

### 1. When Overtime is Approved (`approve_overtime.php`)

When an admin approves an overtime request, the system attempts to update the attendance record:

```php
// Lines 113-123 in approve_overtime.php
$attendance_sql = "UPDATE attendance 
                  SET is_overtime_running = 1, 
                      total_ot_hrs = ? 
                  WHERE employee_id = ? 
                  AND attendance_date = ?
                  AND (is_overtime_running = 0 OR total_ot_hrs = '0')";
$attendance_stmt = mysqli_prepare($db, $attendance_sql);
$total_ot_hrs = strval($request['requested_hours']);
mysqli_stmt_bind_param($attendance_stmt, "sis", $total_ot_hrs, $request['employee_id'], $request['request_date']);
mysqli_stmt_execute($attendance_stmt);
```

**Key Issue:** This is an `UPDATE` statement that requires an existing attendance record. If the employee hasn't timed in, no attendance record exists for that date, and the UPDATE affects **0 rows**.

### 2. When Employee Times In (`employee/function/clock_functions.php`)

When an employee clocks in, the `performClockIn()` function inserts a new attendance record:

```php
// Lines 217-219 in clock_functions.php
$sql = $hasTotalOtHrsCol
    ? "INSERT INTO attendance (employee_id, branch_name, attendance_date, time_in, status, is_time_running, is_overtime_running, total_ot_hrs) VALUES (?, ?, CURDATE(), NOW(), 'Present', 1, 0, 0)"
    : "INSERT INTO attendance (employee_id, branch_name, attendance_date, time_in, status, is_time_running, is_overtime_running) VALUES (?, ?, CURDATE(), NOW(), 'Present', 1, 0)";
```

**Key Issue:** The INSERT statement always sets `total_ot_hrs = 0`. It does NOT check for approved overtime requests and apply them.

### 3. Manual OT Hours API (`set_attendance_ot_hrs_api.php`)

The API that sets overtime hours explicitly requires an existing attendance record:

```php
// Lines 51-66 in set_attendance_ot_hrs_api.php
$findSql = "SELECT id FROM attendance WHERE employee_id = ? AND attendance_date = ? ORDER BY id DESC LIMIT 1";
// ...
if (!$row) {
    echo json_encode(['success' => false, 'message' => 'No attendance record found for the given date']);
    exit();
}
```

## Flow Diagram

```
Employee requests overtime → Admin approves overtime
                                    ↓
                    [approve_overtime.php tries to UPDATE attendance]
                                    ↓
                    ┌───────────────────────────────┐
                    │ Has employee timed in today?  │
                    └───────────────────────────────┘
                           ↓                ↓
                         YES                NO
                           ↓                ↓
              [UPDATE succeeds]      [UPDATE affects 0 rows]
              total_ot_hrs set         Overtime hours LOST
                           ↓                ↓
                    ┌──────────────┐   ┌──────────────┐
                    │ OT hours     │   │ OT hours NOT │
                    │ saved        │   │ saved        │
                    └──────────────┘   └──────────────┘
```

## Root Cause

The system has a **design gap** where:

1. **Overtime approval** assumes an attendance record already exists
2. **Clock-in** doesn't check for approved overtime to pre-populate `total_ot_hrs`
3. There's no mechanism to retroactively apply approved overtime hours when an employee eventually times in

## Impact

- **Scenario 1:** Employee requests overtime for tomorrow → Admin approves it today → Employee times in tomorrow → **OT hours are NOT applied** (lost during approval UPDATE)
- **Scenario 2:** Admin approves overtime after employee has timed in → **OT hours ARE applied** (UPDATE succeeds)

## Files Involved

| File | Purpose | Lines of Interest |
|------|---------|-------------------|
| `approve_overtime.php` | Updates attendance when OT is approved | 113-123 |
| `employee/function/clock_functions.php` | Clock-in logic that creates attendance record | 207-256 |
| `set_attendance_ot_hrs_api.php` | API to manually set OT hours | 51-66 |

## Potential Fix Approaches

### Option 1: Check/Create Attendance Record During Approval
When approving overtime, if no attendance record exists, create one with `status = 'Pending'` and the `total_ot_hrs` set.

### Option 2: Check Approved Overtime During Clock-In
When employee clocks in, query the `overtime_requests` table for approved requests on that date and pre-populate `total_ot_hrs`.

### Option 3: Deferred Application
Store the approved overtime request ID in the attendance record when clocking in, then trigger a background process to apply the hours.

## Recommendation

**Option 2** is recommended because:
- It maintains data consistency (attendance records only created when employee actually clocks in)
- It handles the edge case where overtime is approved before time-in
- It requires minimal changes to the existing clock-in flow
