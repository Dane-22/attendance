# Push Notification Activities Documentation

## Overview

This document details all activities and events that trigger push notifications in the JAJR Attendance & Audit System. Push notifications are sent exclusively to users with the **Super_Admin** role to keep them informed of critical system events in real-time.

---

## Notification Recipients

| Role | Receives Notifications | Notes |
|------|------------------------|-------|
| Super_Admin | ✅ Yes | All notification types |
| Admin | ❌ No | - |
| Manager | ❌ No | - |
| Employee | ❌ No | - |

---

## Triggered Activities

### 1. Attendance Activities

#### 1.1 Time In/Out Events
| Activity | Trigger | Notification Content | Priority |
|----------|---------|---------------------|----------|
| Employee Time In | Employee clocks in via QR, API, or web | "[Employee Name] clocked in at [Time] - [Branch]" | Normal |
| Employee Time Out | Employee clocks out | "[Employee Name] clocked out at [Time] - Worked: [Hours] hrs" | Normal |
| Late Arrival | Employee clocks in after shift start time | "Late Arrival: [Employee Name] arrived [X] minutes late" | High |
| Early Departure | Employee clocks out before shift end | "Early Departure: [Employee Name] left [X] minutes early" | High |
| Missed Clock Out | Employee failed to clock out | "⚠️ [Employee Name] missed clock out for [Date]" | High |

#### 1.2 Attendance Status Changes
| Activity | Trigger | Notification Content | Priority |
|----------|---------|---------------------|----------|
| Absent Marked | Admin marks employee as absent | "[Employee Name] marked ABSENT for [Date]" | Normal |
| Present Override | Admin changes attendance status | "Status updated: [Employee Name] - [Old] → [New]" | Normal |
| Overtime Recorded | Employee works beyond shift hours | "Overtime: [Employee Name] - [X] hours OT recorded" | Normal |
| Undertime Detected | Employee works less than required | "Undertime: [Employee Name] - [X] hours short" | Normal |

### 2. Employee Management Activities

#### 2.1 Employee Account Events
| Activity | Trigger | Notification Content | Priority |
|----------|---------|---------------------|----------|
| New Employee Registered | New account created | "🆕 New employee: [Name] ([Position]) added" | Normal |
| Employee Profile Updated | Profile information changed | "Profile updated: [Employee Name] - [Field] changed" | Low |
| Employee Activated | Deactivated employee reactivated | "✅ [Employee Name] account activated" | Normal |
| Employee Deactivated | Employee account disabled | "⛔ [Employee Name] account deactivated" | High |
| Password Reset Requested | Employee requests password reset | "Password reset: [Employee Name]" | Normal |

#### 2.2 Branch & Position Changes
| Activity | Trigger | Notification Content | Priority |
|----------|---------|---------------------|----------|
| Branch Transfer | Employee moved to different branch | "Transfer: [Name] moved from [Old Branch] → [New Branch]" | Normal |
| Position Changed | Employee role/position updated | "Position change: [Name] - [Old] → [New]" | Normal |
| Shift Change | Employee assigned new shift | "Shift update: [Name] now on [Shift Name]" | Low |

### 3. Leave & Request Activities

#### 3.1 Request Submissions
| Activity | Trigger | Notification Content | Priority |
|----------|---------|---------------------|----------|
| Leave Request Submitted | Employee submits leave request | "📋 Leave request: [Name] - [Type] from [Date]" | Normal |
| Cash Advance Request | Employee requests cash advance | "💰 Cash advance: [Name] - ₱[Amount]" | Normal |
| Overtime Request | Employee requests OT approval | "⏰ OT request: [Name] - [Hours] hrs on [Date]" | Normal |
| Schedule Change Request | Employee requests schedule change | "📅 Schedule change: [Name] requests [Details]" | Normal |

#### 3.2 Request Approvals/Rejections
| Activity | Trigger | Notification Content | Priority |
|----------|---------|---------------------|----------|
| Request Approved | Admin approves any request type | "✅ Approved: [Name]'s [Request Type]" | Normal |
| Request Rejected | Admin denies any request | "❌ Rejected: [Name]'s [Request Type] - [Reason]" | Normal |
| Request Cancelled | Employee cancels pending request | "Cancelled: [Name] withdrew [Request Type]" | Low |

### 4. Payroll Activities

#### 4.1 Payroll Processing
| Activity | Trigger | Notification Content | Priority |
|----------|---------|---------------------|----------|
| Payroll Generated | Monthly payroll is calculated | "💵 Payroll ready: [Month] - [X] employees processed" | High |
| Salary Adjusted | Employee salary modified | "Salary change: [Name] - [Old] → [New]" | High |
| Bonus Added | Performance/incentive bonus added | "Bonus: [Name] - +₱[Amount] ([Reason])" | Normal |
| Deduction Added | Salary deduction applied | "Deduction: [Name] - -₱[Amount] ([Reason])" | Normal |
| Cash Advance Deducted | CA deducted from payroll | "CA Deducted: [Name] - ₱[Amount] from [Period] payroll" | Normal |

### 5. System & Security Activities

#### 5.1 Authentication & Access
| Activity | Trigger | Notification Content | Priority |
|----------|---------|---------------------|----------|
| Failed Login Attempt | 3+ failed login attempts | "⚠️ Failed logins: [Username] - [Count] attempts" | High |
| Suspicious Activity | Unusual access pattern detected | "🚨 Security alert: Unusual activity from [IP]" | Critical |
| New Device Login | Login from unrecognized device | "New device: [User] logged in from [Device/Browser]" | Normal |
| After-Hours Login | Login outside business hours | "Late access: [User] logged in at [Time]" | Normal |

#### 5.2 System Events
| Activity | Trigger | Notification Content | Priority |
|----------|---------|---------------------|----------|
| API Key Generated | New API key created | "🔑 API key generated for [Application Name]" | Normal |
| API Key Revoked | API key disabled/deleted | "⛔ API key revoked: [Key ID]" | High |
| Database Backup | Automated backup completes | "💾 Backup complete: [Timestamp] - [Size]" | Low |
| System Error | Critical error occurs | "🔴 Error: [Error Type] - [Brief Description]" | Critical |
| Low Disk Space | Storage warning | "⚠️ System alert: Low disk space ([X]% remaining)" | High |

### 6. Audit & Compliance Activities

#### 6.1 Data Modifications
| Activity | Trigger | Notification Content | Priority |
|----------|---------|---------------------|----------|
| Attendance Edited | Historical attendance modified | "📝 Edit: [Admin] modified [Name]'s attendance [Date]" | High |
| Bulk Import Completed | Mass employee/data import | "📥 Import complete: [X] records imported" | Normal |
| Data Export | Sensitive data downloaded | "📤 Export: [Admin] exported [Data Type]" | Normal |
| Record Deleted | Employee/attendance record deleted | "🗑️ Deletion: [Admin] deleted [Record Type]" | Critical |

#### 6.2 Audit Events
| Activity | Trigger | Notification Content | Priority |
|----------|---------|---------------------|----------|
| Audit Log Cleared | Admin clears system logs | "⚠️ Audit: [Admin] cleared system logs" | Critical |
| Configuration Changed | System settings modified | "Settings changed: [Admin] updated [Setting Name]" | High |
| Policy Updated | Company policy document updated | "📄 Policy updated: [Policy Name] by [Admin]" | Normal |

### 7. Communication Activities

#### 7.1 System Announcements
| Activity | Trigger | Notification Content | Priority |
|----------|---------|---------------------|----------|
| Announcement Posted | New company announcement | "📢 New announcement: [Title]" | Normal |
| Policy Reminder | Automated policy notification | "📋 Reminder: [Policy Name] - [Brief Description]" | Low |
| Deadline Alert | Payroll/attendance deadline approaching | "⏰ Deadline: [Task] due in [Hours] hours" | High |

#### 7.2 Employee Communications
| Activity | Trigger | Notification Content | Priority |
|----------|---------|---------------------|----------|
| Message Sent | Admin sends message to employee | "Message sent to [Employee Name]" | Low |
| Notice Delivered | Official notice delivered | "📨 Notice delivered: [Type] to [Name]" | Normal |

---

## Notification Priority Levels

| Priority | Description | Badge Color | Sound |
|----------|-------------|-------------|-------|
| **Critical** | Immediate action required (security, errors) | 🔴 Red | Yes - Alert |
| **High** | Important events needing attention | 🟠 Orange | Yes - Chime |
| **Normal** | Standard operational notifications | 🔵 Blue | Yes - Default |
| **Low** | Informational, no action needed | ⚪ Gray | No - Silent |

---

## Notification Format

### Standard JSON Payload Structure
```json
{
  "title": "Activity Name",
  "body": "Detailed description of the activity",
  "icon": "/uploads/profile_images/profile_0_1769993901.png",
  "badge": "/uploads/profile_images/profile_0_1769993901.png",
  "tag": "jajr-notification-[timestamp]",
  "url": "/employee/dashboard.php?section=[relevant_section]",
  "notificationId": 1234567890,
  "requireInteraction": true,
  "priority": "normal",
  "category": "attendance",
  "data": {
    "employeeId": 123,
    "activityType": "time_in",
    "timestamp": "2026-03-14T10:30:00Z",
    "branchId": 5,
    "details": {}
  }
}
```

---

## Technical Implementation

### Trigger Points in Codebase

| Activity | File | Function/Hook |
|----------|------|---------------|
| Time In/Out | `time_in_api.php`, `time_out_api.php` | After successful clock operation |
| Attendance Edit | `mark_attendance_absent_api.php` | After status change |
| Employee CRUD | `employee/api/*.php` | After database operation |
| Request Actions | `approve_*.php` | After approval/rejection |
| Payroll Gen | `payroll/generate.php` | After payroll calculation |
| Security Events | `login_api.php` | After failed login detection |

### Notification Function
```php
// Usage in code
sendPushNotification($db, $superAdminUserId, $title, $message, $optionalUrl);
```

### Example Implementation
```php
// In time_in_api.php - after successful clock-in
if ($result['success']) {
    // Notify Super Admin
    $superAdminId = getSuperAdminId($db); // Function to get Super Admin user ID
    sendPushNotification(
        $db,
        $superAdminId,
        "Employee Clocked In",
        "$employeeName clocked in at $time - $branchName",
        "/employee/attendance.php?date=$today"
    );
}
```

---

## Configuration

### Environment Variables (.env)
```env
VAPID_PUBLIC_KEY=BKyvFnHq0kFWpxvQzyb8VxujX4UTwvriApiwxrWzhQd78Lh0SAriugpMyOqidm3MPVfiRRaZGo6MTsM8Xdi0Rzs
VAPID_PRIVATE_KEY=FK3kGs6a6XA-s9fc985L56MxtqRg9WID-1EPo6vAfyE
VAPID_SUBJECT=mailto:admin@jajr.com
```

### Service Worker
- **Path:** `/main/sw.js`
- **Scope:** `/main/`
- **Supported Events:** `push`, `notificationclick`, `install`, `activate`

### Browser Compatibility
| Browser | Push Support | Notification Support |
|---------|--------------|---------------------|
| Chrome | ✅ Yes | ✅ Yes |
| Firefox | ✅ Yes | ✅ Yes |
| Edge | ✅ Yes | ✅ Yes |
| Safari | ❌ No (desktop) | ✅ Yes (macOS 10.14+) |
| Mobile Chrome | ✅ Yes (Android) | ✅ Yes |
| Mobile Safari | ✅ Yes (iOS 16.4+) | ✅ Yes |

---

## Database Schema

### push_subscriptions Table
```sql
CREATE TABLE push_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    endpoint VARCHAR(512) NOT NULL,
    p256dh VARCHAR(255) NOT NULL,
    auth VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES employees(id) ON DELETE CASCADE,
    UNIQUE KEY unique_endpoint (endpoint)
);
```

---

## Testing

### Test Page
- **URL:** `http://localhost/main/test_push_notification.php`
- **Access:** Super Admin only
- **Features:** 
  - Manual notification sending
  - Subscription status check
  - VAPID key validation

### Debug Mode
Enable detailed logging by checking browser console:
```javascript
// In browser console at audit.php
localStorage.setItem('push_debug', 'true');
```

---

## Security Considerations

1. **VAPID Keys** - Keep private key secure; never expose in frontend code
2. **HTTPS Required** - Push notifications require secure context (HTTPS or localhost)
3. **Permission** - Users must explicitly grant notification permission
4. **Rate Limiting** - Implement delays between notifications to prevent spam
5. **Data Privacy** - Never include sensitive data (salaries, SSN, etc.) in notification body

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| No notifications received | Check browser permission, service worker registration, VAPID keys |
| "Invalid JWT" error | Verify VAPID keys are correctly set in .env |
| 404 on sw.js | Ensure service worker path is `/main/sw.js` |
| Subscription expired | Auto-cleanup removes expired subscriptions on next send attempt |
| Multiple notifications | Check for duplicate subscription entries in database |

---

## Future Enhancements

- [ ] Notification preferences (enable/disable specific types)
- [ ] Batch notifications (digest mode for non-urgent alerts)
[ ] Rich notifications with images and actions
- [ ] Notification history/log
- [ ] SMS fallback for critical alerts
- [ ] Slack/Discord webhook integration

---

*Document Version: 1.0*
*Last Updated: March 14, 2026*
*System: JAJR Attendance & Audit Dashboard v2.0*
