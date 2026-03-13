# Sidebar Navigation Documentation

**File**: `employee/sidebar.php`

The sidebar is the main navigation component for the employee management system. It provides role-based access control, displaying different menu items based on the user's position.

---

## Role-Based Access Matrix

| Menu Item | Engineer | Admin | Super Admin | Employee | Developer |
|-----------|----------|-------|-------------|----------|-----------|
| Dashboard (Eng) | ✅ | ✅ | ❌ | ❌ | ✅ |
| Dashboard (Admin) | ❌ | ❌ | ✅ | ❌ | ❌ |
| Site Attendance | ✅ | ✅ | ✅ | ✅ | ✅ |
| Notifications (Manage) | ❌ | ❌ | ✅ | ❌ | ✅ |
| Notifications (View) | ❌ | ✅ | ❌ | ❌ | ❌ |
| My Notifications | ❌ | ❌ | ❌ | ✅ | ❌ |
| Employee List | ✅ | ✅ | ✅ | ✅ | ✅ |
| Documents | ❌ | ✅ | ✅ | ❌ | ✅ |
| Activity Logs | ❌ | ❌ | ✅ | ❌ | ✅ |
| Attendance Audit | ❌ | ✅ | ✅ | ❌ | ✅ |
| Finance (Dropdown) | ❌ | ✅ | ✅ | ❌ | ✅ |
| Procurement | ✅ | ✅ | ✅ | ❌ | ✅ |
| Settings | ✅ | ✅ | ✅ | ✅ | ✅ |
| Log Out | ✅ | ✅ | ✅ | ✅ | ✅ |

---

## Menu Items Detail

### 1. Dashboard

**Engineer Dashboard** (`eng_dashboard.php`)
- Available to: Engineer, Admin
- Purpose: Engineer-specific dashboard view

**Super Admin Dashboard** (`dashboard.php`)
- Available to: Super Admin only
- Purpose: Main admin dashboard with system overview

---

### 2. Site Attendance (`select_employee.php`)

- **Available to**: All users
- **Icon**: 📋
- **Purpose**: Clock in/out interface for employee attendance tracking
- **Active state**: `$current === 'select_employee.php'`

---

### 3. Notifications

**Notification Management** (`notification.php`)
- Available to: Super Admin only
- Icon: 🔔
- Badge: Shows pending overtime count (`$pendingOvertimeCount`)
- Purpose: Approve/reject overtime requests

**Notifications View** (`admin_notification.php`)
- Available to: Admin only
- Icon: 🔔
- Badge: Shows pending overtime count
- Purpose: Read-only view of notifications

**My Notifications** (`my_notifications.php`)
- Available to: Non-admin users (Employees)
- Icon: 📨
- Badge: Shows unread count (`$unreadNotifCount`)
- Purpose: Personal notifications for regular employees

---

### 4. Employee List (`employees.php`)

- **Available to**: All users
- **Icon**: 👥
- **Purpose**: View and manage employee records
- **Active state**: `$current === 'employees.php'`

---

### 5. Documents (`documents.php`)

- **Available to**: Admin, Super Admin
- **Icon**: 🏥
- **Purpose**: Employee document management (medical, IDs, etc.)
- **Active state**: `$current === 'documents.php'`

---

### 6. Activity Logs (`logs.php`)

- **Available to**: Super Admin only
- **Icon**: 🗂️
- **Purpose**: System activity tracking and audit trail
- **Active state**: `$current === 'logs.php'`

---

### 7. Attendance Audit (`audit.php`)

- **Available to**: Admin, Super Admin
- **Icon**: 📊
- **Purpose**: Review and audit attendance records
- **Active state**: `$current === 'audit.php'`

---

### 8. Finance (Dropdown Menu)

- **Available to**: Admin, Super Admin
- **Icon**: 💰
- **Toggle**: JavaScript dropdown (`toggleDropdown()`)

**Submenu Items**:

| Item | File | Description |
|------|------|-------------|
| Payroll | `weekly_report.php` | Weekly/monthly payroll reports |
| Overtime | `overtime.php` | Overtime management and approval |
| Billing | `billing.php` | Client billing and invoicing |
| Cash Advance | `cash_advance.php` | Employee cash advance requests |

**Dropdown behavior**:
- Opens/closes via `toggleDropdown()` JavaScript function
- Arrow rotates 180° when open
- Active state persists based on current page

---

### 9. Procurement

- **Available to**: Admin, Super Admin, Engineer
- **Icon**: 🛒
- **Link**: `procurement_redirect.php`
- **Note**: External link with dynamic base path handling

---

### 10. Settings (`settings.php`)

- **Available to**: All users
- **Icon**: ⚙️
- **Purpose**: User profile and system settings
- **Active state**: `$current === 'settings.php'`

---

### 11. Log Out (`../logout.php`)

- **Available to**: All users
- **Icon**: 🚪
- **Class**: `logout` (special styling)
- **Position**: Fixed at bottom of sidebar

---

## Technical Implementation

### Role Detection

```php
// Get user role from session
$userRole = isset($_SESSION['position']) ? $_SESSION['position'] : 'Employee';

// Role flags
$isAdmin = in_array($userRole, ['Admin', 'Super Admin']);
$isSuperAdmin = ($userRole === 'Super Admin');
```

### Active Page Detection

```php
$current = basename($_SERVER['PHP_SELF']);
```

Usage in menu items:
```php
<a href="dashboard.php" class="menu-item <?= $current === 'dashboard.php' ? 'active' : '' ?>">
```

### Dynamic Base Path

```php
// Detect if we're being included from outside the employee folder
$scriptDir = dirname($_SERVER['PHP_SELF']);
$isInEmployeeFolder = strpos($scriptDir, '/employee') !== false;

// Set base path for links
$basePath = (!str_contains($scriptDir, 'employee')) ? 'employee/' : '';
```

---

## Notification Badges

### Pending Overtime Count (Admin/Super Admin)

```php
$pendingOvertimeCount = 0;
if ($isAdmin && function_exists('getPendingOvertimeCount') && isset($db)) {
    $pendingOvertimeCount = getPendingOvertimeCount($db);
}
```

Displayed as red badge on notification bell icon.

### Unread Notification Count (Employees)

```php
function getUnreadNotificationCount($db, $employeeId) {
    // Checks employee_notifications table
    // Returns count of is_read = 0 records
}
```

---

## Styling

### CSS Variables Used

- `--gold-2`: Brand color for company name (#FFD700)
- `--text-primary`: Primary text color (#E5E7EB)

### Dropdown Styles

Located in embedded `<style>` block (`lines 171-242`):
- `.menu-dropdown`: Container
- `.dropdown-toggle`: Button with arrow
- `.dropdown-menu`: Submenu container
- `.dropdown-item`: Individual submenu items

### Mobile Support

- Mobile open button: `#mobileOpenBtn`
- Backdrop: `#sidebarBackdrop`
- Controlled via `main.js`

---

## JavaScript Functions

### Dropdown Toggle

```javascript
function toggleDropdown(button) {
  const dropdown = button.nextElementSibling;
  const arrow = button.querySelector('.dropdown-arrow');
  dropdown.classList.toggle('show');
  arrow.style.transform = dropdown.classList.contains('show') 
    ? 'rotate(180deg)' 
    : 'rotate(0deg)';
}
```

---

## File Structure

```
sidebar.php
├── PHP: Role detection & variables (lines 1-50)
├── HTML: Sidebar container (lines 51-168)
│   ├── Brand header
│   ├── Menu items (role-conditional)
│   └── Log out button
├── CSS: Dropdown styles (lines 170-242)
├── JS: Dropdown toggle (lines 245-252)
└── Script include: main.js (line 254)
```

---

## Security Notes

1. **Session-based role checking**: All menu visibility controlled by `$_SESSION['position']`
2. **Server-side validation**: Links are hidden but actual page access must be validated separately
3. **Database dependency**: Notification counts require `$db` connection

---

## Related Files

- `notification.php` - Overtime approval interface
- `my_notifications.php` - Employee notification view
- `weekly_report.php` - Payroll reports
- `functions.php` - `getPendingOvertimeCount()` definition
- `main.js` - Mobile sidebar toggle logic
