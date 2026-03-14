# Time-In and Time-Out Push Notification Schedule

## Overview

This document details the scheduled push notification times for time-in and time-out reminders sent to different employee roles in the JAJR Attendance System.

---

## Notification Schedule by Role

### Engineers

| Notification | Time | Days | Message Content |
|--------------|------|------|-----------------|
| **Time In** | 6:50 AM | Monday - Saturday | "Good morning! Please don't forget to time in for your shift. Have a great day!" |
| **Time Out** | 3:50 PM (15:50) | Monday - Saturday | "Reminder: Please don't forget to time out before leaving. Have a safe trip home!" |

**Work Schedule Assumption:** 7:00 AM - 4:00 PM (10 minutes early reminder)

---

### Admin, Developer & Super Admin

| Notification | Time | Days | Message Content |
|--------------|------|------|-----------------|
| **Time In** | 7:30 AM | Monday - Saturday | "Good morning! Please don't forget to time in for your shift. Have a productive day!" |
| **Time Out** | 4:50 PM (16:50) | Monday - Saturday | "Reminder: Please don't forget to time out before leaving. Have a great evening!" |

**Work Schedule Assumption:** 
- **Admin:** 8:00 AM - 5:00 PM (Mon-Sat) - Office hours
- **Developer:** 8:00 AM - 5:00 PM (Mon-Sat) - Office hours
- **Super Admin:** 8:00 AM - 5:00 PM (Mon-Sat) - Office hours

---

### Workers & Employees

| Notification | Time | Days | Message Content |
|--------------|------|------|-----------------|
| **Time In** | 8:00 AM | Monday - Saturday | "Good morning! Please don't forget to time in for your shift. Have a great day!" |
| **Time Out** | 5:00 PM (17:00) | Monday - Saturday | "Reminder: Please don't forget to time out before leaving. Have a safe trip home!" |

**Work Schedule Assumption:** 8:00 AM - 5:00 PM (standard office hours)

---

## Summary Table

| Role | Time In | Time Out | Days Active |
|------|---------|----------|-------------|
| **Engineer** | 6:50 AM | 3:50 PM | Mon-Sat |
| **Admin** | 7:30 AM | 4:50 PM | Mon-Sat |
| **Developer** | 7:30 AM | 4:50 PM | Mon-Sat |
| **Super Admin** | 7:30 AM | 4:50 PM | Mon-Sat |
| **Worker** | 8:00 AM | 5:00 PM | Mon-Sat |
| **Employee** | 8:00 AM | 5:00 PM | Mon-Sat |

---

## No Notifications on Sunday

All roles follow this rule:
- **Sunday = NO notifications**
- The system automatically exits without sending any reminders on Sundays

---

## Smart Notification Logic

The system includes intelligent checks to avoid unnecessary notifications:

### Time-In Notifications
- **Skip if:** Employee has already timed in for the day
- **Check:** Queries `attendance` table for `time_in IS NOT NULL`

### Time-Out Notifications
- **Skip if:** Employee has already timed out for the day
- **Skip if:** Employee never timed in (no attendance record with `time_in`)
- **Check:** Queries `attendance` table for both `time_out` and `time_in`

---

## Technical Implementation

### Source File
```
/employee/scheduled_attendance_notifications.php
```

### Schedule Configuration
```php
$schedules = [
    // Engineer Time-in: 6:50 AM, Mon-Sat
    [
        'time' => '06:50',
        'positions' => ['Engineer'],
        'type' => 'time_in',
        'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
    ],
    // Engineer Time-out: 3:50 PM, Mon-Sat
    [
        'time' => '15:50',
        'positions' => ['Engineer'],
        'type' => 'time_out',
        'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
    ],
    // Admin, Developer & Super Admin Time-in: 7:30 AM, Mon-Sat
    [
        'time' => '07:30',
        'positions' => ['Admin', 'Developer', 'Super Admin'],
        'type' => 'time_in',
        'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
    ],
    // Admin, Developer & Super Admin Time-out: 4:50 PM, Mon-Sat
    [
        'time' => '16:50',
        'positions' => ['Admin', 'Developer', 'Super Admin'],
        'type' => 'time_out',
        'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
    ],
    // Worker Time-in: 8:00 AM, Mon-Sat
    [
        'time' => '08:00',
        'positions' => ['Worker', 'Employee'],
        'type' => 'time_in',
        'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
    ],
    // Worker Time-out: 5:00 PM, Mon-Sat
    [
        'time' => '17:00',
        'positions' => ['Worker', 'Employee'],
        'type' => 'time_out',
        'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
    ],
];
```

### Time Window
- Notifications are sent within a **1-minute window** of the scheduled time
- This allows for slight variations in cron job / Task Scheduler timing

---

## Windows Task Scheduler Setup

To enable these notifications, create these scheduled tasks:

| Task Name | Time | Script |
|-----------|------|--------|
| Attendance Notification - Engineer Time-In | 6:50 AM | `php.exe scheduled_attendance_notifications.php` |
| Attendance Notification - Engineer Time-Out | 3:50 PM | `php.exe scheduled_attendance_notifications.php` |
| Attendance Notification - Admin Dev Time-In | 7:30 AM | `php.exe scheduled_attendance_notifications.php` |
| Attendance Notification - Admin Dev Time-Out | 4:50 PM | `php.exe scheduled_attendance_notifications.php` |
| Attendance Notification - Worker Time-In | 8:00 AM | `php.exe scheduled_attendance_notifications.php` |
| Attendance Notification - Worker Time-Out | 5:00 PM | `php.exe scheduled_attendance_notifications.php` |

See `SCHEDULED_NOTIFICATIONS_SETUP.md` for detailed setup instructions.

---

## Modifying Worker Schedule

Workers are now included in the default schedule with:
- **Time-in:** 8:00 AM, Mon-Sat
- **Time-out:** 5:00 PM, Mon-Sat

If you need to change these times, edit `scheduled_attendance_notifications.php`:

```php
// Worker Time-in: Modify the time here
[
    'time' => '08:00',  // Change to your desired time
    'positions' => ['Worker', 'Employee'],
    'type' => 'time_in',
    'title' => 'Time In Reminder',
    'message' => 'Good morning! Please don\'t forget to time in for your shift.',
    'url' => '/employee/attendance.php',
    'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
],
```

**Important:** Ensure the position names match exactly what's in the `employees.position` database column (case-sensitive).

---

## Troubleshooting

### Notifications Not Received

1. **Check subscription:** Employee must click "Enable Notifications" on their dashboard
2. **Check position:** Verify the employee's `position` column in database matches the schedule
3. **Check status:** Employee must have `status = 'Active'` in database
4. **Check attendance:** If already timed in/out, no reminder is sent
5. **Check cron/Task Scheduler:** Verify scheduled tasks are running

### Verify Position Names

Run this SQL query to see exact position names:
```sql
SELECT DISTINCT position FROM employees WHERE status = 'Active';
```

Ensure these match the `'positions'` array in the schedule configuration.

---

## Notification Content Examples

### Engineer - Time In (6:50 AM)
- **Title:** "Time In Reminder"
- **Body:** "Good morning! Please don't forget to time in for your shift. Have a great day!"
- **Click Action:** Opens `/employee/attendance.php`

### Engineer - Time Out (3:50 PM)
- **Title:** "Time Out Reminder"
- **Body:** "Reminder: Please don't forget to time out before leaving. Have a safe trip home!"
- **Click Action:** Opens `/employee/attendance.php`

### Admin/Developer/Super Admin - Time In (7:30 AM)
- **Title:** "Time In Reminder"
- **Body:** "Good morning! Please don't forget to time in for your shift. Have a productive day!"
- **Click Action:** Opens `/employee/attendance.php`

### Admin/Developer/Super Admin - Time Out (4:50 PM)
- **Title:** "Time Out Reminder"
- **Body:** "Reminder: Please don't forget to time out before leaving. Have a great evening!"
- **Click Action:** Opens `/employee/attendance.php`

### Worker/Employee - Time In (8:00 AM)
- **Title:** "Time In Reminder"
- **Body:** "Good morning! Please don't forget to time in for your shift. Have a great day!"
- **Click Action:** Opens `/employee/attendance.php`

### Worker/Employee - Time Out (5:00 PM)
- **Title:** "Time Out Reminder"
- **Body:** "Reminder: Please don't forget to time out before leaving. Have a safe trip home!"
- **Click Action:** Opens `/employee/attendance.php`

---

## Related Documentation

- `SCHEDULED_NOTIFICATIONS_SETUP.md` - Windows Task Scheduler setup guide
- `ATTENDANCE_NOTIFICATION_SYSTEM.md` - Overall notification system overview
- `PUSH_NOTIFICATION_COMPATIBILITY.md` - Platform support (Android/iOS)
- `VAPID_KEY_SECURITY.md` - Push notification security

---

## Last Updated

March 2026
