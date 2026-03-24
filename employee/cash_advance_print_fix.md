# Cash Advance Print Error Handling Fix

## Date
March 20, 2026

## Problem
When clicking the **Print** button on the Cash Advance page, the error message "Error loading employee data" was displayed without any specific details about what went wrong.

## Root Cause
The `quickPrintEmployee()` function in `employee/cash_advance.php` was calling the API `api/get_employee_ca.php` to fetch employee data, but the error handling was too generic:
- API responses with `success: false` showed a generic error
- Network failures showed the same generic error
- The actual error message from the API was not being displayed

## Solution
Improved error handling in `employee/cash_advance.php` (lines 1722-1734) to display specific error messages:

### Changes Made
```javascript
// Before:
} else {
    alert('Error loading employee data');
}
.catch(error => {
    console.error('Error:', error);
    alert('Error loading employee data');
});

// After:
} else {
    alert('Error: ' + (data.message || 'Failed to load employee data'));
}
.catch(error => {
    console.error('Error:', error);
    alert('Network error loading employee data. Check console for details.');
});
```

## Error Messages Now Shown
- **"Not logged in"** - Session expired, user needs to log in again
- **"Invalid employee ID"** - The employee ID passed was invalid
- **"Unauthorized"** - Non-admin user trying to view another employee's data
- **"Employee not found"** - The employee ID doesn't exist in the database
- **"Network error..."** - Fetch failed, check browser console

## File Modified
- `c:/wamp64/www/main/employee/cash_advance.php` (lines 1728, 1733)

## API Endpoint
- `employee/api/get_employee_ca.php`
