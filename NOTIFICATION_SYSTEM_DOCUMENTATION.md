# Notification System Documentation

A comprehensive overview of the notification system files in the JAJR Attendance Management System.

---

## Files Overview

| File | Location | Purpose | Access Level |
|------|----------|---------|--------------|
| `my_notifications.php` | `employee/my_notifications.php` | Employee notification center | All logged-in users |
| `notification.php` | `employee/notification.php` | Super Admin approval dashboard | Super Admin, Developer |
| `admin_notification.php` | `employee/admin_notification.php` | Admin view-only dashboard | Admin only |

---

## 1. my_notifications.php

### Purpose
Employee-facing notification center that displays overtime request status updates, cash advance notifications, and system alerts.

### Access Control
```php
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}
```

### Key Features

#### AJAX Actions
| Action | Method | Description |
|--------|--------|-------------|
| `load_notifications` | POST | Load notifications with filter (all/unread) |
| `mark_read` | POST | Mark single notification as read |
| `mark_all_read` | POST | Mark all notifications as read |
| `delete_notification` | POST | Delete a notification |

#### Database Queries

**Load Notifications:**
```sql
SELECT n.*, r.requested_hours, r.request_date, r.branch_name, r.rejection_reason, r.status as request_status,
       c.amount as ca_amount, c.reason as ca_reason, c.status as ca_status, c.rejection_reason as ca_rejection_reason
FROM employee_notifications n
LEFT JOIN overtime_requests r ON n.overtime_request_id = r.id
LEFT JOIN cash_advances c ON n.cash_advance_id = c.id
WHERE n.employee_id = {user_id}
ORDER BY n.created_at DESC
```

**Get Counts:**
```sql
SELECT is_read, COUNT(*) as cnt 
FROM employee_notifications 
WHERE employee_id = ? 
GROUP BY is_read
```

#### Notification Types Supported

| Type | Icon | Status Class | Description |
|------|------|--------------|-------------|
| `overtime_approved` | fa-check-circle | approved | Overtime request approved |
| `overtime_submitted` | fa-clock | pending | Overtime request pending |
| `overtime_rejected` | fa-times-circle | rejected | Overtime request rejected |
| `cash_advance_pending` | fa-clock | pending | Cash advance pending |
| `cash_advance_approved` | fa-check-circle | approved | Cash advance approved |
| `cash_advance_rejected` | fa-times-circle | rejected | Cash advance rejected |

#### UI Components

**Filter Tabs:**
- All (with total count)
- Unread (with unread count)

**Notification Card Structure:**
- Status icon (color-coded)
- Title and timestamp
- Message content
- Meta information (hours, amount, dates)
- Action buttons (mark read, delete)

#### JavaScript Functions

| Function | Purpose |
|----------|---------|
| `loadNotifications(filter)` | Fetch and display notifications |
| `renderNotifications(data)` | Render notification cards |
| `markRead(id)` | Mark single notification as read |
| `markAllRead()` | Mark all as read |
| `deleteNotification(id)` | Delete notification |
| `updateCounts(unread, total)` | Update tab counters |
| `formatDateTime()` | Relative time formatting (Just now, 5m ago, etc.) |

#### Push Notifications
- Service Worker integration
- VAPID key-based subscription
- Browser notification permission handling

---

## 2. notification.php

### Purpose
Super Admin and Developer dashboard for approving/rejecting overtime requests and cash advances.

### Access Control
```php
$userRole = $_SESSION['position'] ?? '';
$isAdmin = ($userRole === 'Super Admin' || $userRole === 'Developer');
if (!$isAdmin) {
    header('Location: dashboard.php');
    exit();
}
```

### Key Features

#### Helper Functions

| Function | Purpose |
|----------|---------|
| `getPendingOvertimeCount($db)` | Count pending/pre-approved overtime requests |
| `getPendingCashAdvanceCount($db)` | Count pending/pre-approved cash advances |

#### AJAX Actions

| Action | Request Type | Description |
|--------|--------------|-------------|
| `load_requests` | Overtime | Load overtime requests by status |
| `load_cash_advance_requests` | Cash Advance | Load cash advance requests |
| `approve_request` | Overtime | Final approve overtime request |
| `reject_request` | Overtime | Reject overtime request |
| `approve_cash_advance` | Cash Advance | Approve cash advance |
| `reject_cash_advance` | Cash Advance | Reject cash advance |

#### Approval Workflow

**Overtime Approval:**
1. Validate request exists and is pending/pre-approved
2. Find or create attendance record for today
3. Update attendance with overtime hours
4. Update request status to 'approved'
5. Create notification for requester
6. Send push notification
7. Log activity

**Cash Advance Approval:**
1. Validate request exists and is pending
2. Ensure status column supports 'approved' (modify if ENUM)
3. Update request status to 'approved'
4. Create notification for employee
5. Send push notification
6. Log activity

#### UI Components

**Request Type Tabs:**
- Overtime (with pending count)
- Cash Advance (with pending count)

**Status Tabs:**
- Pending
- Pre-Approved
- Approved
- Rejected
- All

**Request Card Elements:**
- Employee avatar/initials
- Employee name
- Status badge (color-coded)
- Request details (hours/amount, reason, dates)
- Approval/rejection info (if processed)
- Action buttons (Approve, Reject)

#### Rejection Modal
- Backdrop click to close
- Reason textarea (required)
- Cancel and Confirm buttons
- Supports both overtime and cash advance rejections

#### Database Operations

**Approve Overtime:**
```sql
-- Find attendance record
SELECT id FROM attendance 
WHERE employee_id = ? AND attendance_date = CURDATE() 
ORDER BY id DESC LIMIT 1

-- Update or insert attendance with OT hours
UPDATE attendance SET total_ot_hrs = ? WHERE id = ?
-- OR
INSERT INTO attendance (employee_id, attendance_date, branch_name, status, total_ot_hrs, is_overtime_running, is_time_running, created_at) 
VALUES (?, CURDATE(), ?, 'Present', ?, 0, 0, NOW())

-- Update request
UPDATE overtime_requests 
SET status = 'approved', approved_by = ?, approved_at = NOW(), attendance_id = ? 
WHERE id = ?
```

**Approve Cash Advance:**
```sql
-- Ensure columns exist
ALTER TABLE cash_advances MODIFY COLUMN status VARCHAR(20) DEFAULT 'Pending'
ALTER TABLE cash_advances MODIFY COLUMN approved_by VARCHAR(100) DEFAULT NULL
ALTER TABLE cash_advances ADD COLUMN approved_at DATETIME DEFAULT NULL

-- Update request
UPDATE cash_advances 
SET status = 'approved', approved_by = ?, approved_at = NOW() 
WHERE id = ? AND (status = 'pending' OR status = 'Pending' OR status = 'pre_approved')
```

---

## 3. admin_notification.php

### Purpose
Read-only notification dashboard for Admin users to view overtime and cash advance requests without approval privileges.

### Access Control
```php
$isAdmin = ($_SESSION['position'] ?? '') === 'Admin';
if (!$isAdmin) {
    header('Location: dashboard.php');
    exit();
}
```

### Key Differences from notification.php

| Feature | notification.php (Super Admin) | admin_notification.php (Admin) |
|---------|--------------------------------|--------------------------------|
| Approve requests | Yes | No (view only) |
| Reject requests | Yes | No (view only) |
| Pre-approve | Yes | No |
| Final approval | Yes | No |
| View all statuses | Yes | Yes |
| Status counts | Includes pre-approved | Includes pre-approved |

### AJAX Actions (View Only)

| Action | Description |
|--------|-------------|
| `load_requests` | Load overtime requests (view only) |
| `load_cash_advance_requests` | Load cash advance requests (view only) |

### UI Components

Same structure as `notification.php` but without action buttons:
- Request type tabs (Overtime, Cash Advance)
- Status filter tabs (Pending, Pre-Approved, Approved, Rejected, All)
- Request cards with employee info
- Status badges
- No Approve/Reject buttons

---

## Database Schema Dependencies

### Required Tables

| Table | Purpose |
|-------|---------|
| `employee_notifications` | Store notification records |
| `overtime_requests` | Store overtime request data |
| `cash_advances` | Store cash advance request data |
| `attendance` | Updated when overtime is approved |
| `employees` | Join for employee information |
| `activity_logs` | Log approval/rejection actions |

### employee_notifications Table Structure

```sql
CREATE TABLE `employee_notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `overtime_request_id` int DEFAULT NULL,
  `cash_advance_id` int DEFAULT NULL,
  `notification_type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_employee_read` (`employee_id`,`is_read`),
  KEY `idx_created` (`created_at` DESC),
  KEY `overtime_request_id` (`overtime_request_id`),
  KEY `cash_advance_id` (`cash_advance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Notification Types Reference

| Type | Source | Recipient | Trigger |
|------|--------|-----------|---------|
| `overtime_submitted` | System | Requester | Employee submits overtime request |
| `overtime_request` | System | Admin/Super Admin | New overtime request submitted |
| `overtime_pre_approved` | System | Super Admin | Admin pre-approves request |
| `overtime_approved` | System | Requester | Super Admin approves request |
| `overtime_rejected` | System | Requester | Request is rejected |
| `cash_advance_pending` | System | Requester | Employee submits cash advance |
| `cash_advance_request` | System | Admin/Super Admin | New cash advance request |
| `cash_advance_approved` | System | Employee | Cash advance approved |
| `cash_advance_rejected` | System | Employee | Cash advance rejected |
| `leave_submitted` | System | Requester | Employee submits leave request |
| `leave_request` | System | Admin/Super Admin | New leave request submitted |

---

## Activity Logging

All notification-related actions are logged to `activity_logs`:

| Action | Description |
|--------|-------------|
| `Notification Marked Read` | User marks notification as read |
| `All Notifications Marked Read` | User marks all as read |
| `Notification Deleted` | User deletes notification |
| `Overtime Approved` | Super Admin approves overtime |
| `Overtime Rejected` | Super Admin rejects overtime |
| `Cash Advance Approved` | Super Admin approves cash advance |
| `Cash Advance Rejected` | Super Admin rejects cash advance |

---

## CSS/Styling

### Files Used
- `../assets/css/style.css` - Base styles
- `css/my_notifications.css` - Employee notification styles
- `css/notification.css` - Admin/Super Admin dashboard styles
- `css/light-theme.css` - Light/dark theme support

### Common Style Classes

| Class | Purpose |
|-------|---------|
| `.notification-card` | Individual notification container |
| `.status-badge.{status}` | Color-coded status indicators |
| `.request-card` | Request display card |
| `.tab-btn` | Filter tab buttons |
| `.loading-state` | Loading spinner container |
| `.empty-state` | No results display |
| `.unread-dot` | Unread indicator dot |

---

## Integration Points

### Push Notification API
- `api/get_vapid_key.php` - Get VAPID public key
- `api/save_push_subscription.php` - Save subscription
- `../sw.js` - Service Worker for notifications

### Related Files
- `functions.php` - `sendPushNotification()` function
- `eng_dashboard.php` - Submits overtime requests
- `dashboard.php` - Displays notification badge in sidebar

---

## Security Considerations

1. **Access Control**: Each file checks user role before processing
2. **SQL Injection**: All queries use prepared statements
3. **XSS Prevention**: Output is escaped using `htmlspecialchars()`
4. **CSRF**: Form submissions include session validation
5. **Error Suppression**: Production mode suppresses PHP errors for clean JSON

---

## Future Enhancements

Potential improvements for the notification system:

1. **Real-time Updates**: WebSocket integration for instant notifications
2. **Email Notifications**: SMTP integration for email alerts
3. **SMS Notifications**: Twilio integration for critical alerts
4. **Notification Preferences**: User-configurable notification settings
5. **Bulk Actions**: Select and delete multiple notifications
6. **Search/Filter**: Search notifications by content or date range
7. **Archive**: Move old notifications to archive instead of delete
