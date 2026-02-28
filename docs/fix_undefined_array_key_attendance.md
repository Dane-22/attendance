# Fix: PHP Undefined Array Key Warning in attendance.php

## Error Description

**Error Message:**
```
PHP Warning: Undefined array key "first_name" in /var/www/jajr-project/employee/function/attendance.php on line 5
PHP Warning: Undefined array key "last_name" in /var/www/jajr-project/employee/function/attendance.php on line 5
```

**Root Cause:**
The code was directly accessing `$_SESSION['first_name']` and `$_SESSION['last_name']` without checking if these keys exist, causing PHP warnings when the session variables weren't set.

## Solution

**File:** `employee/function/attendance.php`

### Before (Line 5-7)
```php
$employeeName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$employeeCode = $_SESSION['employee_code'];
```

### After (Line 5-7)
```php
$employeeName = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
$employeeCode = $_SESSION['employee_code'] ?? '';
```

## Technical Details

**Null Coalescing Operator (`??`)**
- Returns the left operand if it exists and is not null
- Returns the right operand (default value) if left operand doesn't exist

**Benefits:**
1. Prevents PHP warnings from undefined array keys
2. Provides default empty string values when session data is missing
3. Maintains application functionality without breaking

## Deployment

1. Upload the modified `attendance.php` to the server
2. Verify the nginx error logs no longer show the warnings
3. Check that employee pages load correctly

## Verification Command

```bash
sudo tail -f /var/log/nginx/error.log | grep "Undefined array key"
```

If no output appears when accessing the employee pages, the fix is working.

## Additional Notes

- Same pattern should be used for other session variables to prevent similar warnings
- Consider implementing session validation to ensure required data exists
- Debug logs showing user roles are informational and not errors
