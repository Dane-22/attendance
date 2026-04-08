# QR Code Scanning Issue: Cross-Branch Time-In After Forgotten Time-Out

## Issue Summary

**Reported Problem:** A worker who timed in at Sto. Rosario branch but forgot to time out was unable to time in at BCDA-Admin branch the following day.

## Scenario Details

| Day | Location | Action | Expected | Actual |
|-----|----------|--------|----------|--------|
| Day 1 | Sto. Rosario | Time In | Success | Success |
| Day 1 | Sto. Rosario | Time Out | Required | **Forgotten** - Record stays open |
| Day 2 | BCDA-Admin | Time In | Should allow new time-in | **Blocked** |

## Root Cause Analysis

### Current System Behavior

The QR scanning system uses `CURDATE()` in its database queries to check for existing attendance records:

```php
// From: employee/function/clock_functions.php:64
$checkSql = "SELECT id FROM attendance 
    WHERE employee_id = ? 
    AND attendance_date = CURDATE()  
    AND time_in IS NOT NULL 
    AND time_out IS NULL";
```

This query pattern appears in multiple files:
- `employee/function/clock_functions.php:64`
- `employee/api/qr_clock.php:80`
- `employee/select_employee.php:144`

### The Problem

When a worker forgets to time out, their attendance record remains in the database with:
- `attendance_date` = Day 1's date
- `time_in` = Day 1's time-in timestamp
- `time_out` = NULL (open record)

On Day 2, when the worker tries to time in at a different branch:
1. The system queries with `CURDATE()` (Day 2's date)
2. The query does NOT find the open record from Day 1
3. The system should allow a new time-in

However, the user reports the time-in was blocked, suggesting there may be additional validation or a different query path that checks for **any** open record regardless of date.

## Affected Components

### Primary Files
| File | Function | Line |
|------|----------|------|
| `employee/function/clock_functions.php` | `performClockIn()` | 64-77 |
| `employee/api/qr_clock.php` | Clock-in check | 80-92 |
| `employee/select_employee.php` | QR scan handling | 144-150 |

### Database Table
- Table: `attendance`
- Key Columns: `employee_id`, `attendance_date`, `time_in`, `time_out`

## Potential Solutions

### Solution 1: Auto-Close Previous Day's Open Records
Before allowing a new time-in, check for and auto-close any open records from previous days:

```php
// Pseudocode for new validation
function closePreviousDayOpenRecords($db, $employeeId) {
    $sql = "UPDATE attendance 
            SET time_out = CONCAT(attendance_date, ' 23:59:59'),
                status = 'Auto-Closed'
            WHERE employee_id = ? 
            AND attendance_date < CURDATE()
            AND time_in IS NOT NULL 
            AND time_out IS NULL";
    // Execute and log the auto-close action
}
```

### Solution 2: Admin Override Interface
Provide an admin interface to manually close forgotten time-out records before workers can time in at a different branch.

### Solution 3: Worker Self-Service Time-Out
Allow workers to time out remotely or from a different branch for the previous day's open shift.

### Solution 4: Midnight Auto-Close Cron Job
Run a daily cron job at midnight to automatically close all open records from the previous day.

```php
// midnight_cleanup.php - Run at 00:00 daily
$sql = "UPDATE attendance 
        SET time_out = CONCAT(attendance_date, ' 23:59:59'),
            notes = CONCAT(COALESCE(notes, ''), ' [Auto-closed at midnight]')
        WHERE attendance_date < CURDATE()
        AND time_in IS NOT NULL 
        AND time_out IS NULL";
```

## Recommended Implementation

**Immediate Fix (Short-term):**
Implement Solution 4 (Midnight Auto-Close Cron Job) to prevent the database from accumulating open records.

**Long-term Enhancement:**
Implement Solution 1 to handle edge cases where the cron job may have missed records.

## Testing Checklist

- [ ] Simulate time-in at Branch A without time-out
- [ ] Attempt time-in at Branch B the next day
- [ ] Verify auto-close mechanism works
- [ ] Verify attendance reports show correct hours
- [ ] Test with multiple consecutive days of forgotten time-outs
- [ ] Verify payroll calculations remain accurate after auto-close

## Related Documentation

- `docs/QR_SCANNING_FLOW.md` - Main QR scanning workflow documentation
- `employee/QR_TIMEIN_API.md` - QR Time-in API documentation
- `docs/SELECT_EMPLOYEE_DOCUMENTATION.md` - Employee selection page documentation

## Log Reference

**Issue Reported:** April 8, 2026
**Reported By:** User (via worklog)
**Affected Branches:** Sto. Rosario → BCDA-Admin
**Affected Workers:** Workers who forget to time out
