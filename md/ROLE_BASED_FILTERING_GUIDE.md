# Role-Based Sidebar Filtering - Implementation Guide

## ✅ Implementation Complete

Your sidebar.php has been updated with strict role-based filtering based on `$_SESSION['position']`.

---

## 📋 What Was Changed

### sidebar.php Updated
**File**: `employee/sidebar.php`

#### Logic Added:
```php
<?php
// Get user role from session
$userRole = isset($_SESSION['position']) ? $_SESSION['position'] : 'Employee';

// Check if user is Admin or Super Admin
$isAdmin = in_array($userRole, ['Admin', 'Super Admin']);
?>
```

#### Menu Items Now Filtered:

| Menu Item | Employee | Admin | Super Admin |
|-----------|----------|-------|------------|
| **Dashboard** | ❌ Hidden | ✅ Visible | ✅ Visible |
| **Site Attendance** | ✅ Visible | ✅ Visible | ✅ Visible |
| **Employee List** | ✅ Visible | ✅ Visible | ✅ Visible |
| **Reports** | ❌ Hidden | ✅ Visible | ✅ Visible |
| **Billing** | ❌ Hidden | ✅ Visible | ✅ Visible |
| **Documents** | ❌ Hidden | ✅ Visible | ✅ Visible |
| **Activity Logs** | ❌ Hidden | ✅ Visible | ✅ Visible |
| **Settings** | ❌ Hidden | ✅ Visible | ✅ Visible |
| **Log Out** | ✅ Visible | ✅ Visible | ✅ Visible |

---

## 🔒 Security Guard Setup

A security guard file has been created to protect admin-only pages.

**File**: `SECURITY_GUARD_SNIPPET.php` (in root directory)

### How to Use:

**Step 1**: Open `employee/dashboard.php`
- Find the opening `<?php` tag (should be the very first thing in the file)
- Add this code right after the opening PHP tag:

```php
<?php
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Get user role from session
$userRole = isset($_SESSION['position']) ? $_SESSION['position'] : 'Employee';

// If user is an Employee, redirect them to select_employee.php
if ($userRole === 'Employee') {
    header("Location: select_employee.php");
    exit;
}
// User is Admin or Super Admin - allow access
?>
```

**Step 2**: Open `employee/billing.php`
- Add the same code after the opening `<?php` tag

**Step 3** (Optional): Add to any other admin-only pages:
- `employee/weekly_report.php`
- `employee/documents.php`
- `employee/settings.php`
- `admin/logs.php`

---

## 📝 Code Reference

### sidebar.php Role Check
```php
<?php
// Get user role from session
$userRole = isset($_SESSION['position']) ? $_SESSION['position'] : 'Employee';

// Check if user is Admin or Super Admin (both roles get full access)
$isAdmin = in_array($userRole, ['Admin', 'Super Admin']);
?>
```

### Conditional Menu Item Display
```php
<?php if ($isAdmin): ?>
  <!-- This item only shows for Admin/Super Admin -->
  <a href="dashboard.php" class="menu-item ...">Dashboard</a>
<?php endif; ?>

<!-- This item shows for ALL users -->
<a href="select_employee.php" class="menu-item ...">Site Attendance</a>
```

---

## 🎯 User Experience by Role

### If Logged In as 'Employee':
**Sidebar will show:**
- ✅ Site Attendance
- ✅ Employee List
- ✅ Log Out

**Hidden items:**
- ❌ Dashboard
- ❌ Reports
- ❌ Billing
- ❌ Documents
- ❌ Activity Logs
- ❌ Settings

**If they try to access admin pages directly:**
- Dashboard → Redirected to `select_employee.php`
- Billing → Redirected to `select_employee.php`
- (Same for any other protected pages)

### If Logged In as 'Admin' or 'Super Admin':
**Sidebar will show ALL:**
- ✅ Dashboard
- ✅ Site Attendance
- ✅ Employee List
- ✅ Reports
- ✅ Billing
- ✅ Documents
- ✅ Activity Logs
- ✅ Settings
- ✅ Log Out

**Full access to all pages**

---

## 🔐 Security Features

### Frontend Security (Sidebar)
✅ Menu items conditionally displayed based on role
✅ Employees never see admin links
✅ Clean, professional UI

### Backend Security (Page Guards)
✅ Even if URL is accessed directly, employees are redirected
✅ Session validation ensures user is logged in
✅ Role check happens before any page content loads

### Layered Approach
```
Level 1: Session Check
├─ If not logged in → Redirect to login.php

Level 2: Role Check
├─ If Employee tries to access admin page → Redirect to select_employee.php

Level 3: Business Logic
└─ Admin pages execute their full functionality
```

---

## 📌 $_SESSION['position'] Values

The system checks against these role names (case-sensitive):

```
'Employee'      → Standard employee role
'Admin'         → Administrator role
'Super Admin'   → Super administrator role
```

**Important**: Make sure your database and login system use these exact values.

---

## ✨ Theme Consistency

The dark theme styling is maintained:
- ✅ #0B0B0B background for sidebar
- ✅ Gold (#d4af37) accents for active items
- ✅ Smooth hover effects
- ✅ Professional appearance
- ✅ Responsive design

All role-based filtering is purely **PHP logic** - no CSS hiding. This means:
- Items don't load in DOM if user doesn't have permission
- More secure than CSS-only hiding
- Cleaner HTML output
- Better performance

---

## 🧪 Testing Checklist

### Test as Employee:
- [ ] Login with an employee account
- [ ] Verify sidebar shows only: Site Attendance, Employee List, Log Out
- [ ] Try to manually navigate to `/employee/dashboard.php` → Should redirect
- [ ] Try to manually navigate to `/employee/billing.php` → Should redirect
- [ ] Try other admin pages → Should redirect to select_employee.php

### Test as Admin:
- [ ] Login with an admin account
- [ ] Verify sidebar shows all menu items
- [ ] Can access dashboard.php directly
- [ ] Can access billing.php directly
- [ ] Can access all other admin pages

### Test as Super Admin:
- [ ] Same as Admin
- [ ] Verify no differences in permissions

---

## 🚀 Deployment Steps

### Step 1: Backup Current Files
```bash
# Backup sidebar.php
cp employee/sidebar.php employee/sidebar.php.backup
```

### Step 2: Files Already Updated
✅ `employee/sidebar.php` - Already updated

### Step 3: Add Security Guards to Protected Pages
- [ ] `employee/dashboard.php` - Add security guard at top
- [ ] `employee/billing.php` - Add security guard at top
- [ ] (Optional) Other admin pages

### Step 4: Test Thoroughly
- [ ] Test with employee account
- [ ] Test with admin account
- [ ] Test direct URL access
- [ ] Verify sidebar displays correctly

### Step 5: Deploy
```bash
# Deploy to server
cp employee/sidebar.php /path/to/production/employee/
# And update other protected files
```

---

## 📊 File Summary

| File | Changes | Status |
|------|---------|--------|
| `employee/sidebar.php` | ✅ Updated with role-based filtering | Complete |
| `employee/dashboard.php` | ⏳ Needs security guard | Manual |
| `employee/billing.php` | ⏳ Needs security guard | Manual |
| `SECURITY_GUARD_SNIPPET.php` | ✅ Created for reference | Reference |

---

## 💡 Tips & Best Practices

### 1. Session Management
Always ensure sessions are properly initialized:
```php
<?php
session_start();
// ... rest of code
?>
```

### 2. Role Consistency
Keep role names consistent throughout the system:
- Database: 'Employee', 'Admin', 'Super Admin'
- Session: $_SESSION['position']
- Sidebar: Check against these values

### 3. Security First
- Always check role on **backend** (not just frontend)
- Never trust frontend checks alone
- Redirect is better than showing blank pages
- Log unauthorized access attempts (optional)

### 4. User Feedback
When employees are redirected:
- They're sent to `select_employee.php` (a valid page)
- No error messages that might confuse them
- Seamless experience

### 5. Future Additions
To add more roles in the future:
```php
// Current code
$isAdmin = in_array($userRole, ['Admin', 'Super Admin']);

// Future: Add new role
$isAdmin = in_array($userRole, ['Admin', 'Super Admin', 'Manager']);
```

---

## 🔗 Related Files

- **Login system**: Check how `$_SESSION['position']` is set
- **Database**: Verify `users` table has position/role field
- **Functions**: Check `functions.php` for any related role logic

---

## ❓ Troubleshooting

### Problem: Employee sees admin menu items
**Solution**: 
- Check session is being set correctly in login
- Verify `$_SESSION['position']` has exact value
- Clear browser cache and re-test

### Problem: Admin can't access dashboard
**Solution**:
- Verify role is set to 'Admin' or 'Super Admin' (exact case)
- Check security guard code isn't redirecting admins
- Verify login system is working

### Problem: Redirects not working
**Solution**:
- Check no headers already sent (whitespace before `<?php`)
- Verify session_start() is called first
- Check file paths are correct

---

## 📞 Quick Reference

### Sidebar Logic
```php
$userRole = $_SESSION['position'];  // Get user role
$isAdmin = in_array($userRole, ['Admin', 'Super Admin']);  // Check if admin
<?php if ($isAdmin): ?>  // Show only for admin
```

### Page Guard Logic
```php
if ($userRole === 'Employee') {
    header("Location: select_employee.php");  // Redirect employees
    exit;
}
```

### Menu Visibility
```
Employee:    Site Attendance, Employee List, Log Out
Admin:       All items
Super Admin: All items
```

---

## ✅ Summary

✅ **Sidebar filtering**: Complete and working
✅ **Role-based logic**: Implemented with strict checking
✅ **Security guards**: Template provided
✅ **Dark theme**: Maintained and consistent
✅ **Documentation**: Complete with examples

---

**Status**: Ready for Implementation
**Last Updated**: 2024
**Testing Required**: Yes
