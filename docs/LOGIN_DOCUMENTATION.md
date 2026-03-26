# Login System Documentation

**File:** `login.php`  
**Purpose:** Authentication entry point for the JAJR Attendance System

---

## Overview

The login page serves as the primary authentication gateway for employees. It supports dual-login methods (Employee Code or Email), handles password verification with legacy MD5 upgrade support, manages session initialization, records attendance automatically, and includes a QR scanner for kiosk-style clock-in/out.

---

## Authentication Flow

### 1. POST Request Handling (Lines 60-258)

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
```

**Process:**
1. Sanitizes input identifier and password
2. Validates that fields are not empty
3. Determines lookup method (email vs employee_code)
4. Queries `employees` table for active users only

### 2. Dual Password Verification (Lines 86-106)

The system supports both modern `password_hash()` and legacy MD5 hashes:

```php
// Check if it's a password_hash() format
if (strpos($stored_hash, '$2y$') === 0) {
    if (password_verify($password, $stored_hash)) {
        $password_valid = true;
    }
} else {
    // Try MD5 and auto-upgrade to password_hash()
    if (md5($password) === $stored_hash) {
        $password_valid = true;
        // AUTO-UPGRADE: Convert MD5 hash to password_hash()
        $new_hash = password_hash($password, PASSWORD_DEFAULT);
        // ... update in database
    }
}
```

**Features:**
- **Backward Compatible:** Supports legacy MD5 passwords
- **Auto-Migration:** Automatically upgrades MD5 to bcrypt on successful login
- **Secure:** Uses PHP's `password_hash()` with `PASSWORD_DEFAULT`

### 3. Session Initialization (Lines 109-144)

```php
$_SESSION['employee_id']    = $user['id'];
$_SESSION['employee_code']  = $user['employee_code'];
$_SESSION['first_name']     = $user['first_name'];
$_SESSION['last_name']      = $user['last_name'];
$_SESSION['email']          = $user['email'];
$_SESSION['position']       = $user['position'];
$_SESSION['logged_in']      = true;
$_SESSION['login_time']     = date('Y-m-d H:i:s');
```

**Procurement SSO Integration (Lines 121-144):**
- Attempts login to procurement API using same credentials
- Non-blocking: Local login succeeds even if procurement login fails
- Stores procurement token in `$_SESSION['procurement_token']`

### 4. Branch Assignment Logic (Lines 146-160)

```php
// Daily branch (where working today)
$_SESSION['daily_branch'] = 'Main Branch';

// Assigned branch (permanent assignment)
$_SESSION['assigned_branch'] = $user['branch_name'] ?? 'Main Branch';

// Branch filtering for attendance
if ($user['position'] === 'Super Admin') {
    $_SESSION['branch_name'] = 'all'; // Super Admin sees all branches
} else {
    $_SESSION['branch_name'] = $user['branch_name'] ?? 'Main Branch';
}
```

**Super Admin Privilege:** Super Admin users see attendance records from all branches (`'all'`), while regular users are restricted to their assigned branch.

### 5. Attendance Recording (Lines 162-244)

**Features:**
- Detects if attendance record already exists for today
- Auto-detects database columns to handle schema variations
- Inserts new record or updates existing one with daily branch

```php
$check_sql = "SELECT id FROM attendance 
             WHERE employee_id = ? AND attendance_date = CURDATE()";
```

**Default Values for Problematic Columns:**
- `is_time_running` = 0
- `is_overtime_running` = 0  
- `total_ot_hrs` = 0.00
- `total_hours` = 0.00
- `overtime_hours` = 0.00

### 6. Activity Logging (Line 243-244)

```php
$user_name = $user['first_name'] . ' ' . $user['last_name'];
logActivity($db, 'Logged In', "User {$user_name} logged in from branch: {$daily_branch}");
```

### 7. Redirect (Lines 246-248)

```php
header('Location: employee/select_employee.php');
exit();
```

---

## Procurement API Integration

**Function:** `procurementApiLogin()` (Lines 10-58)

```php
function procurementApiLogin(string $employeeNo, string $password): array {
    $url = 'https://procurement-api.xandree.com/api/auth/login';
    // ... cURL request with 8s timeout
    return ['ok' => true, 'response' => $json];
}
```

**Behavior:**
- Attempts SSO login to external procurement system
- 8-second timeout with 4-second connect timeout
- Returns structured array with success/error info
- Does NOT block local login on failure

---

## Frontend Features

### Password Toggle (Lines 469-479, 499-519)

```html
<div class="password-wrapper">
  <input type="password" name="password" id="passwordInput" class="password-field" />
  <button type="button" class="password-toggle" id="togglePassword">
    <i class="fas fa-eye"></i>
  </button>
</div>
```

**Implementation:** JavaScript toggles between `type="password"` and `type="text"`, switching the icon between `fa-eye` and `fa-eye-slash`.

### Super Admin Indicator (Lines 438-443)

Displays a visual banner when Super Admin credentials are detected:

```php
echo '<div class="super-admin-note">
        <i class="fa-solid fa-crown mr-2"></i>
        Super Admin detected: You will see ALL branches in attendance
      </div>';
```

### Error/Warning Display (Lines 447-457)

- **Errors:** Red background (`bg-red-900/20`) for invalid credentials
- **Warnings:** Yellow background (`bg-yellow-900/20`) for procurement login failures

---

## QR Scanner (Kiosk Mode)

**Purpose:** Allow employees to clock in/out without logging in by scanning their QR code.

**Location:** Modal dialog (Lines 392-409) triggered by QR button (Line 416)

**Library:** `html5-qrcode` (Line 497)

### Key Functions:

**Parse QR Data (Lines 576-595):**
```javascript
function parseEmployeeFromQR(text) {
  // Extract from URL: ?auto_timein=1&emp_id=123&emp_code=ABC
  // Or use plain text employee code
}
```

**Clock In/Out (Lines 598-659):**
- `processClockIn(empId, empCode)` - Calls `employee/api/qr_clock.php`
- `processClockOut(empId, empCode)` - Auto-triggered if already clocked in

**Scanner Lifecycle:**
1. Click QR button → Open modal
2. Request camera permission
3. Start scanning with `fps: 10`
4. On successful scan: Parse employee → Call API → Show result
5. Can scan another or close modal

---

## Security Features

| Feature | Implementation |
|---------|---------------|
| Password hashing | bcrypt via `password_hash()` |
| Legacy support | MD5 with auto-upgrade to bcrypt |
| Input sanitization | `trim()`, `htmlspecialchars()` |
| SQL injection prevention | Prepared statements with parameter binding |
| Session security | Regenerates session on login |
| CSRF | Not implemented (add if needed) |
| Rate limiting | Not implemented (add if needed) |

---

## Dependencies

**PHP:**
- `conn/db_connection.php` - Database connection
- `functions.php` - Helper functions including `logActivity()`

**Frontend:**
- Tailwind CSS (CDN)
- Font Awesome 6.4.0
- html5-qrcode library
- Custom stylesheets:
  - `assets/css/style.css`
  - `assets/css/theme-variables.css`
  - `assets/style_auth.css`
  - `assets/js/theme.js`
  - `assets/js/auth.js`

---

## Database Tables Used

- `employees` - User authentication data
- `attendance` - Daily attendance records
- `information_schema.COLUMNS` - Schema introspection for column detection

---

## Configuration

| Setting | Value | Location |
|---------|-------|----------|
| Procurement API URL | `https://procurement-api.xandree.com/api/auth/login` | Line 11 |
| Default daily branch | `Main Branch` | Line 63 |
| cURL timeout | 8 seconds | Line 34 |
| cURL connect timeout | 4 seconds | Line 35 |

---

## Future Improvements

1. **Rate limiting** - Add brute-force protection
2. **2FA support** - Optional two-factor authentication
3. **Remember me** - Persistent login cookies
4. **Account lockout** - After N failed attempts
5. **Password reset** - Self-service password recovery

---

## Related Files

- `employee/select_employee.php` - Post-login redirect destination
- `employee/api/qr_clock.php` - QR clock in/out API endpoint
- `logout.php` - Session destruction
- `functions.php` - `logActivity()` helper

---

*Documentation generated: 2026-03-27*
