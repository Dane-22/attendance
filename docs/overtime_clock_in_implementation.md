# Approved Overtime Hours During Clock-In - Implementation Documentation

## Overview

This document describes the implementation of **Option 2** from the overtime analysis: checking for approved overtime requests when an employee clocks in and pre-populating the `total_ot_hrs` field in the attendance record.

## Problem Solved

**Before this fix:**
- Admin approves overtime request (before employee clocks in) → UPDATE fails (no attendance record exists) → Overtime hours lost
- Employee clocks in → INSERT creates record with `total_ot_hrs = 0` → Approved overtime never applied

**After this fix:**
- Admin approves overtime request (anytime) → Stored in `overtime_requests` table
- Employee clocks in → System checks for approved overtime → INSERT creates record with `total_ot_hrs = {approved_hours}`

## Implementation Details

### File Modified
- `employee/function/clock_functions.php`

### Changes Made

#### 1. New Helper Function (lines 8-35)

```php
/**
 * Get approved overtime hours for an employee on a specific date
 * Returns float value of approved overtime hours, or 0 if none found
 */
function getApprovedOvertimeHours($db, $employeeId, $date = null) {
    if ($date === null) {
        $date = date('Y-m-d');
    }
    
    $sql = "SELECT COALESCE(SUM(requested_hours), 0) as total_hours 
            FROM overtime_requests 
            WHERE employee_id = ? 
            AND request_date = ? 
            AND status = 'approved'";
    
    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) {
        return 0;
    }
    
    mysqli_stmt_bind_param($stmt, 'is', $employeeId, $date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    return floatval($row['total_hours'] ?? 0);
}
```

**Purpose:** Queries the `overtime_requests` table for all approved overtime requests for the employee on the specified date.

**Key Features:**
- Uses `COALESCE(SUM(requested_hours), 0)` to handle NULL values and sum multiple approved requests
- Supports custom date parameter (defaults to today)
- Returns `0` on database errors (graceful degradation)
- Only looks for `status = 'approved'` records

#### 2. Modified performClockIn() Function (lines 242-244)

Added overtime lookup before the INSERT operations:

```php
// Check for approved overtime hours before inserting
$approvedOtHours = $hasTotalOtHrsCol ? getApprovedOvertimeHours($db, $employeeId) : 0;
$approvedOtHoursStr = strval($approvedOtHours);
```

#### 3. Updated INSERT Statements (lines 251-339)

All 16 INSERT statement variants now use `$approvedOtHoursStr` instead of hardcoded `0`:

**Before:**
```php
"INSERT INTO attendance (..., total_ot_hrs) VALUES (..., 0)"
```

**After:**
```php
"INSERT INTO attendance (..., total_ot_hrs) VALUES (..., {$approvedOtHoursStr})"
```

## Flow Diagram

```
Employee requests overtime for {date}
           ↓
Admin approves overtime request
           ↓
    [overtime_requests table]
    status = 'approved'
    requested_hours = X
           ↓
Employee clocks in on {date}
           ↓
    [performClockIn() function]
           ↓
    ┌──────────────────────────────────────┐
    │ getApprovedOvertimeHours(employee_id)  │
    │ - Query overtime_requests              │
    │ - WHERE status='approved'              │
    │ - AND request_date = CURDATE()         │
    │ - SUM(requested_hours)                 │
    └──────────────────────────────────────┘
           ↓
    ┌──────────────────────────────────────┐
    │ Found approved OT hours?            │
    └──────────────────────────────────────┘
           ↓                    ↓
         YES                   NO
           ↓                    ↓
    total_ot_hrs = X         total_ot_hrs = 0
           ↓                    ↓
    ┌──────────────┐       ┌──────────────┐
    │ INSERT with  │       │ INSERT with  │
    │ OT hours     │       │ OT hours = 0 │
    └──────────────┘       └──────────────┘
```

## Database Schema Requirements

### Required Table: `overtime_requests`

| Column | Type | Description |
|--------|------|-------------|
| `id` | int (PK) | Request ID |
| `employee_id` | int (FK) | Employee who requested overtime |
| `request_date` | date | Date overtime is requested for |
| `requested_hours` | decimal(5,2) | Number of overtime hours |
| `status` | enum | 'pending', 'approved', 'rejected', 'pre-approved' |

### Target Table: `attendance`

| Column | Type | Description |
|--------|------|-------------|
| `id` | int (PK) | Attendance record ID |
| `employee_id` | int (FK) | Employee ID |
| `attendance_date` | date | Date of attendance |
| `time_in` | datetime | Clock-in timestamp |
| `total_ot_hrs` | decimal/varchar | Total approved overtime hours |

## Testing Scenarios

### Scenario 1: Overtime Approved Before Time-In ✓
1. Employee requests 5 hours overtime for tomorrow
2. Admin approves the request today
3. Employee clocks in tomorrow
4. **Expected:** `attendance.total_ot_hrs = 5`

### Scenario 2: Multiple Approved Requests ✓
1. Employee has 2 approved requests: 3 hours + 4 hours = 7 hours total
2. Employee clocks in
3. **Expected:** `attendance.total_ot_hrs = 7`

### Scenario 3: No Approved Overtime ✓
1. Employee has no approved overtime requests
2. Employee clocks in
3. **Expected:** `attendance.total_ot_hrs = 0`

### Scenario 4: Pending Request (Not Approved) ✓
1. Employee requests overtime but admin hasn't approved
2. Employee clocks in
3. **Expected:** `attendance.total_ot_hrs = 0`

### Scenario 5: Approved Then Rejected ✓
1. Overtime was approved, then later rejected
2. Employee clocks in
3. **Expected:** `attendance.total_ot_hrs = 0` (status='rejected' excluded)

## Edge Cases Handled

1. **Database error during lookup:** Returns 0 (graceful fallback)
2. **Multiple approved requests:** Sums all approved hours using `SUM()`
3. **No total_ot_hrs column:** Skips lookup entirely (`$hasTotalOtHrsCol` check)
4. **Future/past dates:** Uses current date by default (can be extended)
5. **Partial hours:** `decimal(5,2)` supports values like 2.5 hours

## Backward Compatibility

- ✅ No database schema changes required
- ✅ Works with or without `total_ot_hrs` column
- ✅ Graceful degradation if `overtime_requests` table missing
- ✅ All existing clock-in flows unchanged (just with OT hours populated)

## Related Files

| File | Purpose |
|------|---------|
| `employee/function/clock_functions.php` | Clock-in logic with OT lookup |
| `approve_overtime.php` | Admin approval endpoint (unchanged) |
| `set_attendance_ot_hrs_api.php` | Manual OT hours API (fallback) |

## Notes

- This implementation only handles the **initial clock-in INSERT**
- For existing attendance records, `approve_overtime.php` still attempts UPDATE
- If both mechanisms fail, `set_attendance_ot_hrs_api.php` provides manual override
