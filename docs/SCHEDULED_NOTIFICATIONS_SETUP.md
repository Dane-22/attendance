# Scheduled Attendance Notifications Setup Guide

## Overview

This system sends automated push notifications to employees reminding them to time in and time out according to their work schedules.

## Notification Schedule

### Engineers
| Type | Time | Days |
|------|------|------|
| Time In | 6:50 AM | Monday - Saturday |
| Time Out | 3:50 PM | Monday - Saturday |

### Admin & Developer
| Type | Time | Days |
|------|------|------|
| Time In | 7:30 AM | Monday - Saturday |
| Time Out | 4:50 PM | Monday - Friday |

*Note: No notifications are sent on Sundays.*

## Setup Instructions (Windows Task Scheduler)

### Step 1: Test the Script Manually

Before setting up automation, test the script manually:

1. Open Command Prompt as Administrator
2. Run:
   ```cmd
   C:\wamp64\bin\php\php8.0.30\php.exe C:\wamp64\www\main\employee\scheduled_attendance_notifications.php
   ```

Or simply double-click:
```
C:\wamp64\www\main\run_attendance_notifications.bat
```

### Step 2: Create Scheduled Tasks

You need to create **4 separate scheduled tasks** for the different times. Here's how:

#### Task 1: Engineer Time-In (6:50 AM)

1. Press `Win + R`, type `taskschd.msc`, press Enter
2. In Task Scheduler, click **"Create Basic Task"** (right side)
3. **Name:** `Attendance Notification - Engineer Time-In`
4. **Description:** `Send push notification to Engineers at 6:50 AM Mon-Sat`
5. Click **Next**
6. Select **"Daily"** and click **Next**
7. **Start:** Today's date, **Time:** `06:50:00`
8. Click **Next**
9. Select **"Start a program"** and click **Next**
10. **Program/script:** `C:\wamp64\bin\php\php8.0.30\php.exe`
11. **Add arguments:** `C:\wamp64\www\main\employee\scheduled_attendance_notifications.php`
12. Click **Next**, then **Finish**
13. Find the task in the list, right-click → **Properties**
14. Go to **"Triggers"** tab, click **Edit**
15. Check **"Repeat task every"** → set to `1 day`
16. Check **"Stop task if it runs longer than"** → set to `30 minutes`
17. Go to **"Settings"** tab:
    - Check **"Allow task to be run on demand"**
    - Check **"Run task as soon as possible after a scheduled start is missed"**
    - Uncheck **"Stop the task if it runs longer than"**
18. Click **OK**

#### Task 2: Engineer Time-Out (3:50 PM)

Repeat the above steps but:
- **Name:** `Attendance Notification - Engineer Time-Out`
- **Time:** `15:50:00` (3:50 PM)

#### Task 3: Admin & Developer Time-In (7:30 AM)

Repeat the above steps but:
- **Name:** `Attendance Notification - Admin Dev Time-In`
- **Time:** `07:30:00` (7:30 AM)

#### Task 4: Admin & Developer Time-Out (4:50 PM)

Repeat the above steps but:
- **Name:** `Attendance Notification - Admin Dev Time-Out`
- **Time:** `16:50:00` (4:50 PM)

### Alternative: Use the Batch File

If the above doesn't work, use the batch file instead:

**Program/script:** `C:\wamp64\www\main\run_attendance_notifications.bat`
**Add arguments:** *(leave blank)*

### Step 3: Configure Days (Important!)

For each task, you need to configure which days it runs:

1. Right-click the task → **Properties**
2. Go to **"Triggers"** tab
3. Select the trigger and click **Edit**
4. Click **"Weekly"** instead of Daily
5. Set **"Recur every"** to `1` weeks
6. Select the appropriate days:
   - For Mon-Sat tasks: Check Monday, Tuesday, Wednesday, Thursday, Friday, Saturday
   - For Mon-Fri tasks: Check Monday, Tuesday, Wednesday, Thursday, Friday only
7. Click **OK**

### Step 4: Run as Administrator

Ensure the tasks run with proper permissions:

1. Right-click each task → **Properties**
2. Go to **"General"** tab
3. At bottom, select **"Run whether user is logged on or not"**
4. Check **"Run with highest privileges"**
5. Click **Change User or Group**
6. Type your Windows username and click **Check Names**, then **OK**
7. Click **OK** on Properties (you may need to enter your password)

## Testing

### Test Notifications Immediately

To test without waiting for scheduled times, you can temporarily modify the script:

Open `scheduled_attendance_notifications.php` and find these lines:
```php
'schedules' => [
    // Engineer Time-in: 6:50 AM, Mon-Sat
    [
        'time' => '06:50',
```

Change `'06:50'` to the current time + 1 minute, save, then run the script manually.

### View Task History

In Task Scheduler:
1. Select your task
2. Click **"History"** tab (right side)
3. Look for any errors or success messages

## Troubleshooting

### Notifications Not Sending

1. **Check PHP path:** Ensure `C:\wamp64\bin\php\php8.0.30\php.exe` exists
2. **Check web-push library:** Ensure `vendor/autoload.php` exists in `C:\wamp64\www\main\`
3. **Run manually:** Double-click `run_attendance_notifications.bat` and check for errors
4. **Check Task History:** In Task Scheduler, view the task history for errors
5. **Check push subscriptions:** Ensure employees have allowed push notifications in their browsers

### Script Runs But No Notifications

1. **Check VAPID keys:** Ensure `.env` file has valid `VAPID_PUBLIC_KEY` and `VAPID_PRIVATE_KEY`
2. **Check browser subscriptions:** Employees must visit the dashboard and allow notifications
3. **Check browser console:** Press F12 → Console to see any JavaScript errors

### Task Scheduler Issues

1. **Task won't run:** Check that the user account has password and "Run whether user is logged on or not" is selected
2. **Task shows "Running" forever:** Set a timeout in task properties → Settings tab
3. **Task history is empty:** Enable task history in Task Scheduler → Action → Enable All Tasks History

## Monitoring

### Log Output

The script outputs to console. To save logs:

1. Create a log folder: `C:\wamp64\www\main\logs\`
2. Modify the batch file:
   ```batch
   @echo off
   echo %date% %time% >> C:\wamp64\www\main\logs\attendance_notif.log
   C:\wamp64\bin\php\php8.0.30\php.exe "C:\wamp64\www\main\employee\scheduled_attendance_notifications.php" >> C:\wamp64\www\main\logs\attendance_notif.log 2>&1
   echo. >> C:\wamp64\www\main\logs\attendance_notif.log
   ```

### Email Alerts (Optional)

To receive email when notifications fail, add this to the end of the PHP script:

```php
// Email admin if there were errors
if (!empty($errors)) {
    $to = 'admin@jajr.com';
    $subject = 'Attendance Notification Errors - ' . date('Y-m-d H:i');
    $body = "The following errors occurred:\n\n" . implode("\n", $errors);
    mail($to, $subject, $body);
}
```

## File Locations

| File | Path |
|------|------|
| PHP Script | `C:\wamp64\www\main\employee\scheduled_attendance_notifications.php` |
| Batch File | `C:\wamp64\www\main\run_attendance_notifications.bat` |
| Functions | `C:\wamp64\www\main\functions.php` |
| Web Push Library | `C:\wamp64\www\main\vendor\autoload.php` |

## Quick Commands

Run manually from Command Prompt:
```cmd
cd C:\wamp64\www\main\employee
C:\wamp64\bin\php\php8.0.30\php.exe scheduled_attendance_notifications.php
```

Check task from command line:
```cmd
schtasks /query /tn "Attendance Notification - Engineer Time-In" /v
```

Delete and recreate task:
```cmd
schtasks /delete /tn "Attendance Notification - Engineer Time-In" /f
```

## Support

If issues persist:
1. Check Windows Event Viewer (Event Viewer → Windows Logs → Application)
2. Review PHP error logs in `C:\wamp64\logs\php_error.log`
3. Verify MySQL is running in WAMP
4. Ensure all employees have valid push subscriptions in the `push_subscriptions` table
