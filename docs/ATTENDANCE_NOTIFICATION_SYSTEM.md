# Attendance Notification System Documentation

## Overview

This document outlines the notification system for time-in and time-out reminders across all user roles in the attendance monitoring system.

## Notification Recipients

### 1. **Admin & Super Admin**
- **Purpose**: Oversee attendance compliance and exceptions
- **Notifications**:
  - Daily attendance summary (before 9:00 AM cutoff)
  - Employees who haven't timed in by cutoff
  - Overtime request approvals
  - Absent notes requiring review
  - Cash advance requests

### 2. **Developer**
- **Purpose**: System monitoring and technical oversight
- **Notifications**:
  - All Admin-level notifications
  - System errors or failures
  - Database sync issues
  - Push notification delivery failures

### 3. **Engineers**
- **Purpose**: Personal attendance tracking and team oversight
- **Notifications**:
  - **Before Time In**: Reminder to clock in (if not timed in by 8:45 AM)
  - **Before Time Out**: End-of-shift reminder (if still clocked in after 5:45 PM)
  - Pending overtime request status
  - Cash advance request status

### 4. **Workers/Employees**
- **Purpose**: Personal attendance compliance
- **Notifications**:
  - **Before Time In**: Reminder at 8:30 AM if not yet clocked in
  - **Before Time Out**: Reminder at 5:30 PM if still on shift
  - Shift transfer approvals
  - Overtime request responses

---

## Notification Types

### Time-In Reminders

| Role | Trigger Time | Condition | Message |
|------|-------------|-----------|---------|
| Worker | 8:30 AM | Not timed in | "Good morning! Don't forget to time in for your shift." |
| Engineer | 8:45 AM | Not timed in | "Reminder: Please time in. Cutoff is 9:00 AM." |
| Admin/Super Admin | 9:00 AM | Summary | "Daily attendance report: X employees absent/pending" |

### Time-Out Reminders

| Role | Trigger Time | Condition | Message |
|------|-------------|-----------|---------|
| Worker | 5:30 PM | Still clocked in | "Your shift ends at 6:00 PM. Remember to time out." |
| Engineer | 5:45 PM | Still clocked in | "Reminder: Time out in 15 minutes." |
| Admin/Super Admin | 6:30 PM | Summary | "Employees still on shift after hours: [List]" |

---

## Implementation Files

### Backend Notification Logic
- `employee/function/attendance.php` - Overtime request notifications
- `employee/eng_dashboard.php` - Engineer overtime/cash advance notifications
- `api/clock_in.php` & `api/clock_out.php` - Time tracking notifications

### Frontend Push Notification Setup
- `employee/audit.php` - Push notification enablement (Admin/Super Admin)
- `employee/api/save_push_subscription.php` - Subscription management
- `sw.js` - Service Worker for push notifications

### Database Tables
- `employee_notifications` - Stores all notification records
- `push_subscriptions` - Stores push notification subscriptions

---

## Configuration

### Cron Jobs (Scheduled Tasks)

```bash
# Time-in reminders (runs at 8:30 AM daily)
30 8 * * * php /path/to/cron/send_timein_reminders.php

# Time-out reminders (runs at 5:30 PM daily)
30 17 * * * php /path/to/cron/send_timeout_reminders.php

# Daily attendance summary (runs at 9:00 AM daily)
0 9 * * * php /path/to/cron/send_daily_summary.php
```

### Push Notification Requirements

1. **VAPID Keys**: Required for Web Push
   - Generate using: `php generate_vapid.php`
   - Store in environment variables

2. **Browser Permissions**: Users must enable notifications
   - Chrome/Edge: Via lock icon → Site Settings → Notifications
   - Firefox: Via permission prompt or address bar icon

3. **Supported Browsers**:
   - Chrome 50+
   - Firefox 44+
   - Edge 17+
   - Safari 16.4+ (macOS Ventura+)

---

## Notification Flow

### Time-In Notification Flow

```
Cron Job (8:30 AM)
    ↓
Check employees without time_in for today
    ↓
Filter by role (Workers, Engineers)
    ↓
Insert into employee_notifications table
    ↓
Send push notification (if subscribed)
    ↓
Display in notification bell icon
```

### Time-Out Notification Flow

```
Cron Job (5:30 PM)
    ↓
Check employees with time_in but no time_out
    ↓
Filter by role (Workers, Engineers)
    ↓
Insert into employee_notifications table
    ↓
Send push notification (if subscribed)
    ↓
Display in notification bell icon
```

### Admin Summary Notification Flow

```
Cron Job (9:00 AM)
    ↓
Generate attendance summary
    ↓
Count: Present / Absent / Pending
    ↓
Send to Admin + Super Admin + Developer
    ↓
Insert into employee_notifications table
    ↓
Send push notification (if subscribed)
```

---

## Testing Notifications

### Test Push Notifications
1. Log in as Admin/Super Admin
2. Go to `employee/audit.php`
3. Click "Enable Notifications"
4. Accept browser permission
5. Use test button to verify delivery

### Test Time-In/Time-Out Reminders
1. Manually run cron job script:
   ```bash
   php employee/cron/send_timein_reminders.php
   php employee/cron/send_timeout_reminders.php
   ```
2. Check `employee_notifications` table for new entries
3. Verify push notification received

---

## Troubleshooting

### Common Issues

| Issue | Cause | Solution |
|-------|-------|----------|
| No notifications received | Browser permission denied | Re-enable in browser settings |
| Push subscription 403 error | Session expired or role mismatch | Re-login as Admin/Super Admin |
| Duplicate notifications | Multiple subscriptions | Clear `push_subscriptions` table for user |
| Notification not showing in bell | Database insert failed | Check MySQL connection and permissions |
| Mobile not receiving | Service Worker not registered | Clear cache and re-register |

### Debug Commands

```php
// Check if user has push subscription
SELECT * FROM push_subscriptions WHERE employee_id = [ID];

// Check recent notifications
SELECT * FROM employee_notifications 
WHERE employee_id = [ID] 
ORDER BY created_at DESC 
LIMIT 10;

// Test notification insert
INSERT INTO employee_notifications 
(employee_id, notification_type, title, message, is_read, created_at) 
VALUES ([ID], 'test', 'Test', 'Test message', 0, NOW());
```

---

## Related Documentation

- `PUSH_NOTIFICATION_ACTIVITIES.md` - Push notification implementation details
- `SCHEDULED_NOTIFICATIONS_SETUP.md` - Cron job configuration
- `API_DOCUMENTATION.md` - API endpoints for notifications
- `OVERTIME_API.md` - Overtime notification specifics

---

## Updates

- **March 14, 2026**: Updated to include both Admin and Super Admin in overtime notifications
- **March 14, 2026**: Separated Developer notifications for better role management
