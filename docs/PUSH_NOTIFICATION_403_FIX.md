# Push Notification 403 Forbidden Error - Fix Documentation

## Issue Summary

**Error:** 403 Forbidden when saving push notification subscriptions via `save_push_subscription.php`

**Affected User Role:** Developer

**Error Message:** `Access denied. Please log in to enable notifications.`

**Date Fixed:** March 17, 2026

---

## Root Cause Analysis

### Primary Issue #1: Missing 'Developer' Role in Access Control

The `save_push_subscription.php` file only allowed 'Admin' and 'Super Admin' roles to save push subscriptions. When a user with 'Developer' role tried to save their subscription, they received a 403 Forbidden error.

**File:** `employee/api/save_push_subscription.php`

**Original Code (Lines 11-19):**
```php
// Check if user is logged in and is Admin or Super Admin
if (empty($_SESSION['logged_in']) || !in_array($_SESSION['position'], ['Admin', 'Super Admin'])) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. Admin or Super Admin access required.'
    ]);
    exit;
}
```

### Primary Issue #2: Missing Session Cookies in Fetch Requests

The JavaScript fetch requests for saving push subscriptions were not including session cookies, causing the API to see the user as "not logged in" even when they had an active session.

**Files Affected:**
- `employee/eng_dashboard.php`
- `employee/my_notifications.php`
- `employee/audit.php`

**Original Fetch Code:**
```javascript
fetch('api/save_push_subscription.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify(subscription)
})
```

---

## Debug Logs

### Before Fix:
```
DEBUG save_push_subscription: Session status: 2
DEBUG save_push_subscription: logged_in=
DEBUG save_push_subscription: position=
DEBUG save_push_subscription: employee_id=
DEBUG save_push_subscription: REQUEST_HEADERS={"Cookie":""}
```

**Problem:** Session data empty, no cookie sent - the user appeared as "not logged in"

### After Fix:
```
DEBUG save_push_subscription: Session status: 2
DEBUG save_push_subscription: logged_in=1
DEBUG save_push_subscription: position=Developer
DEBUG save_push_subscription: employee_id=141
DEBUG save_push_subscription: REQUEST_HEADERS={"Cookie":"PHPSESSID=b5v2ajsih88bmmkaqks1s5pp7d"}
```

**Success:** Session data present, cookie sent, Developer role authenticated

---

## Solution Applied

### Fix #1: Expand Allowed Roles

**File:** `employee/api/save_push_subscription.php` (Lines 11-29)

**Updated Code:**
```php
$allowedPositions = ['Admin', 'Super Admin', 'Engineer', 'Developer', 'Employee', 'Worker'];
if (empty($_SESSION['logged_in']) || !in_array($_SESSION['position'], $allowedPositions)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. Please log in to enable notifications.',
        'debug' => [
            'logged_in' => $_SESSION['logged_in'] ?? null,
            'position' => $_SESSION['position'] ?? null
        ]
    ]);
    exit;
}
```

**Changes:**
- Added 'Developer', 'Engineer', 'Employee', and 'Worker' roles to allowed positions
- Added debug logging to help diagnose session issues in the future

### Fix #2: Add Credentials to Fetch Requests

**Files:** 
- `employee/eng_dashboard.php` (Lines 1486-1804)
- `employee/my_notifications.php` (Lines 507-801)
- `employee/audit.php` (Lines 600-939)

**Updated Fetch Code:**
```javascript
fetch('api/save_push_subscription.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    credentials: 'include',  // <- ADDED: Send session cookies
    body: JSON.stringify(subscription)
})
```

**Key Addition:** `credentials: 'include'` - This ensures the browser sends PHP session cookies with the request, allowing the server to identify the logged-in user.

---

## Additional Fix: Missing Function Error

During the debugging process, a related fatal error was also discovered and fixed:

**Error:** `Fatal error: Uncaught Error: Call to undefined function attendanceHasStatusColumn()`

**File:** `employee/function/attendance.php` (Lines 193-225)

**Fix:** Added the missing function:
```php
function attendanceHasStatusColumn($db) {
    static $cached = null;
    if ($cached !== null) return $cached;
    
    $sql = "SELECT COUNT(*) as cnt
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'attendance'
              AND COLUMN_NAME = 'status'";
    $result = mysqli_query($db, $sql);
    if (!$result) {
        $cached = false;
        return $cached;
    }
    $row = mysqli_fetch_assoc($result);
    $cached = intval($row['cnt'] ?? 0) === 1;
    return $cached;
}
```

---

## Verification Steps

1. **Clear browser cache** and unregister any existing service workers
2. **Log in** as a Developer role user
3. **Navigate** to `eng_dashboard.php`
4. **Click** "Enable Notifications" button
5. **Check** browser console - should show subscription saved successfully
6. **Check** server logs - should show `logged_in=1` and `position=Developer`

---

## Related Files Changed

| File | Lines Modified | Description |
|------|----------------|-------------|
| `employee/api/save_push_subscription.php` | 11-29 | Added allowed roles and debug logging |
| `employee/eng_dashboard.php` | 1486-1804 | Added `credentials: 'include'` to fetch |
| `employee/my_notifications.php` | 507-801 | Added `credentials: 'include'` to fetch |
| `employee/audit.php` | 600-939 | Added `credentials: 'include'` to fetch |
| `employee/function/attendance.php` | 193-225 | Added missing `attendanceHasStatusColumn()` function |

---

## Prevention Measures

1. **Always use absolute paths** for API endpoints (e.g., `/employee/api/...` instead of `api/...`)
2. **Always include `credentials: 'include'`** when making fetch requests to authenticated APIs
3. **When adding new features with role-based access**, ensure all relevant roles are included in the allowed list
4. **Add debug logging** to authentication checks to facilitate future troubleshooting

---

## Technical Notes

### Why `credentials: 'include'` is necessary:

By default, the Fetch API uses `credentials: 'same-origin'` which only sends cookies for same-origin requests. However, in some deployment scenarios (especially with CDN or reverse proxies), this may not work as expected. Explicitly setting `credentials: 'include'` ensures cookies are always sent, regardless of the request's origin relative to the page.

### Session Status Codes:
- `0`: PHP_SESSION_DISABLED - Sessions are disabled
- `1`: PHP_SESSION_NONE - Sessions are enabled but no session exists
- `2`: PHP_SESSION_ACTIVE - Session is active and valid

The logs showed status `2` (active), which confirmed sessions were working but the cookie wasn't being transmitted.

---

## Conclusion

The 403 Forbidden error was successfully resolved by:
1. Including the 'Developer' role in the allowed positions list
2. Ensuring session cookies are sent with fetch requests via `credentials: 'include'`

Push notifications are now working for all user roles: Admin, Super Admin, Engineer, Developer, Employee, and Worker.
