# Inactive Employee Filter Implementation

## Overview
This document describes the implementation of filtering inactive employees from the attendance selection interface (`select_employee.php`).

## Purpose
Ensure that employees with `status = 'Inactive'` are excluded from:
- QR scan auto clock-in/out functionality
- Manual attendance marking interface
- Branch selection for clock-in

## Files Modified

### 1. `employee/select_employee.php`

#### Change 1: QR Scan Auto Time-in Employee Lookup (Line 55-58)
**Before:**
```php
$empStmt = mysqli_prepare($db, "SELECT e.id, e.first_name, e.last_name, e.employee_code, b.branch_name 
    FROM employees e 
    LEFT JOIN branches b ON b.id = e.branch_id 
    WHERE e.id = ? LIMIT 1");
```

**After:**
```php
$empStmt = mysqli_prepare($db, "SELECT e.id, e.first_name, e.last_name, e.employee_code, b.branch_name 
    FROM employees e 
    LEFT JOIN branches b ON b.id = e.branch_id 
    WHERE e.id = ? AND e.status = 'Active' LIMIT 1");
```

#### Change 2: QR Clock with Branch Selection - Employee Verification (Line 131)
**Before:**
```php
$empStmt = mysqli_prepare($db, "SELECT id, first_name, last_name FROM employees WHERE id = ? AND employee_code = ? LIMIT 1");
```

**After:**
```php
$empStmt = mysqli_prepare($db, "SELECT id, first_name, last_name FROM employees WHERE id = ? AND employee_code = ? AND status = 'Active' LIMIT 1");
```

## Existing Coverage

The following files already had active status filtering in place:

### `employee/select_emp.php`
All employee loading queries already include `WHERE e.status = 'Active'`:
- Line 214: Count query for showing all employees
- Line 222: Count query for unmarked employees (before cutoff)
- Line 228: Count query for employees not marked present (after cutoff)
- Line 269: Main query for showing all employees
- Line 293: Main query for unmarked employees
- Line 315: Main query for employees not marked present

### `employee/function/employees_function.php`
- Line 127: Employee update function handles status field
- Line 342: Search condition for employee listing includes `e.status = 'Active'`

## Behavior

### When an employee is marked as Inactive:
1. **QR Scan Attempts**: Will return "Employee not found" error
2. **Manual Attendance**: Employee will not appear in the selectable list
3. **Clock-in/out**: Prevented for inactive employees

### When an employee is reactivated (status changed back to Active):
1. All functionality is restored immediately
2. Employee appears in lists again
3. QR scans work normally

## Database Schema Reference

**Table**: `employees`
**Field**: `status` (ENUM: 'Active', 'Inactive')
**Default**: 'Active'

## Testing Checklist

- [ ] Mark an employee as Inactive in `employee/employees.php`
- [ ] Verify employee does not appear in `select_employee.php` interface
- [ ] Attempt QR scan with inactive employee ID - should fail with "not found"
- [ ] Reactivate employee and verify functionality is restored
- [ ] Confirm existing Active employees are unaffected

## Implementation Date
April 6, 2026

## Notes
- This is a data filtering layer, not a deletion mechanism
- Inactive employees remain in the database for historical record keeping
- All attendance records for inactive employees are preserved
- The filter only affects the ability to clock in/out going forward
