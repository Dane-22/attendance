# Security Guard - Copy & Paste Templates

## For dashboard.php

**Location**: Top of `employee/dashboard.php` (right after `<?php`)

```php
<?php
// Security Guard: Only allow Admin/Super Admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$userRole = isset($_SESSION['position']) ? $_SESSION['position'] : 'Employee';

if ($userRole === 'Employee') {
    header("Location: select_employee.php");
    exit;
}
// Admin/Super Admin access granted
?>
```

---

## For billing.php

**Location**: Top of `employee/billing.php` (right after `<?php`)

```php
<?php
// Security Guard: Only allow Admin/Super Admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$userRole = isset($_SESSION['position']) ? $_SESSION['position'] : 'Employee';

if ($userRole === 'Employee') {
    header("Location: select_employee.php");
    exit;
}
// Admin/Super Admin access granted
?>
```

---

## For weekly_report.php

**Location**: Top of `employee/weekly_report.php` (right after `<?php`)

```php
<?php
// Security Guard: Only allow Admin/Super Admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$userRole = isset($_SESSION['position']) ? $_SESSION['position'] : 'Employee';

if ($userRole === 'Employee') {
    header("Location: select_employee.php");
    exit;
}
// Admin/Super Admin access granted
?>
```

---

## For documents.php

**Location**: Top of `employee/documents.php` (right after `<?php`)

```php
<?php
// Security Guard: Only allow Admin/Super Admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$userRole = isset($_SESSION['position']) ? $_SESSION['position'] : 'Employee';

if ($userRole === 'Employee') {
    header("Location: select_employee.php");
    exit;
}
// Admin/Super Admin access granted
?>
```

---

## For settings.php

**Location**: Top of `employee/settings.php` (right after `<?php`)

```php
<?php
// Security Guard: Only allow Admin/Super Admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$userRole = isset($_SESSION['position']) ? $_SESSION['position'] : 'Employee';

if ($userRole === 'Employee') {
    header("Location: select_employee.php");
    exit;
}
// Admin/Super Admin access granted
?>
```

---

## For admin/logs.php

**Location**: Top of `admin/logs.php` (right after `<?php`)

```php
<?php
// Security Guard: Only allow Admin/Super Admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$userRole = isset($_SESSION['position']) ? $_SESSION['position'] : 'Employee';

if ($userRole === 'Employee') {
    header("Location: ../employee/select_employee.php");
    exit;
}
// Admin/Super Admin access granted
?>
```

---

## How to Apply

### Step 1: Open the file
Example: Open `employee/dashboard.php` in VS Code

### Step 2: Find the opening PHP tag
Look for `<?php` at the very top of the file

### Step 3: Copy the template for that file
Copy the code from the section above

### Step 4: Paste after `<?php`
Make sure it's the FIRST code after the opening tag (before any existing code)

### Step 5: Save the file
Ctrl+S to save

### Step 6: Test
- Login as employee
- Try to access that page
- Should be redirected to select_employee.php

---

## ✅ Implementation Checklist

- [ ] dashboard.php - Guard added
- [ ] billing.php - Guard added
- [ ] weekly_report.php - Guard added (optional)
- [ ] documents.php - Guard added (optional)
- [ ] settings.php - Guard added (optional)
- [ ] admin/logs.php - Guard added (optional)

---

## 🧪 Quick Test

### Test as Employee
1. Login with employee account
2. Sidebar shows: Site Attendance, Employee List, Log Out
3. Try to go to dashboard.php
4. Should redirect to select_employee.php ✓

### Test as Admin
1. Login with admin account
2. Sidebar shows all items
3. Can access dashboard.php ✓
4. Can access billing.php ✓

---

**Note**: The base code is identical for all files. Only change the redirect path for `admin/logs.php` (uses `../employee/select_employee.php` instead of `select_employee.php`).
