# Why Didn't I Receive the Notification? Troubleshooting Guide

## Quick Diagnosis: The Most Common Reason

**If it's 4:50 PM and no notification arrived, 99% of the time it's because:**

> ⚠️ **The Windows Task Scheduler is NOT set up to run the script at that time.**

The PHP script `scheduled_attendance_notifications.php` knows **when** to send notifications, but something needs to **trigger** it to run at those times.

---

## How It Actually Works

```
┌──────────────────┐     ┌──────────────────┐     ┌──────────────────┐
│                  │     │                  │     │                  │
│  Windows Task    │ ──► │   PHP Script     │ ──► │  Push Notification│
│  Scheduler       │     │   Runs & Checks  │     │  Sent to Phone   │
│  (Triggers at    │     │   Current Time   │     │                  │
│   4:50 PM)       │     │   & Sends        │     │                  │
│                  │     │                  │     │                  │
└──────────────────┘     └──────────────────┘     └──────────────────┘
        ↑
   YOU NEED THIS!
   (Not set up by default)
```

**Without the Task Scheduler:** The script never runs → No one gets notified

---

## Step-by-Step Fix

### Step 1: Check If Task Scheduler Is Set Up

1. Press `Win + R`
2. Type `taskschd.msc` and press Enter
3. Look for tasks named:
   - `Attendance Notification - Engineer Time-In`
   - `Attendance Notification - Engineer Time-Out`
   - `Attendance Notification - Admin Dev Time-In`
   - `Attendance Notification - Admin Dev Time-Out`
   - `Attendance Notification - Worker Time-In`
   - `Attendance Notification - Worker Time-Out`

**If you DON'T see these tasks → That's the problem!**

---

### Step 2: Set Up the Tasks (One-Time Setup)

#### Option A: Quick Setup Using Batch File

1. Open Task Scheduler (`Win + R` → `taskschd.msc`)
2. Click **"Create Task"** (right side panel, NOT "Create Basic Task")
3. **General Tab:**
   - Name: `Attendance Notification - Admin Time-Out`
   - Check: **"Run whether user is logged on or not"**
   - Check: **"Run with highest privileges"**
4. **Triggers Tab:**
   - Click **"New"**
   - Begin the task: **On a schedule**
   - Settings: **Daily**
   - Start: Today's date, Time: `4:50:00 PM`
   - Click **OK**
5. **Actions Tab:**
   - Click **"New"**
   - Action: **Start a program**
   - Program/script: `C:\wamp64\bin\php\php8.0.30\php.exe`
   - Add arguments: `C:\wamp64\www\main\employee\scheduled_attendance_notifications.php`
   - Click **OK**
6. **Settings Tab:**
   - Check: **"Allow task to be run on demand"**
   - Check: **"Run task as soon as possible after a scheduled start is missed"**
   - Click **OK**
7. Enter your Windows password when prompted

Repeat for other times:
- 6:50 AM (Engineer Time-In)
- 3:50 PM (Engineer Time-Out)
- 7:30 AM (Admin/Dev/Super Admin Time-In)
- 4:50 PM (Admin/Dev/Super Admin Time-Out) ← **This is the one you need!**
- 8:00 AM (Worker Time-In)
- 5:00 PM (Worker Time-Out)

---

#### Option B: Test Run Right Now

Want to test if it works? Run the script manually:

1. Open Command Prompt
2. Run:
   ```cmd
   C:\wamp64\bin\php\php8.0.30\php.exe C:\wamp64\www\main\employee\scheduled_attendance_notifications.php
   ```

Or simply double-click:
```
C:\wamp64\www\main\run_attendance_notifications.bat
```

**What you should see:**
```
Processing schedule: Time Out Reminder for Admin, Developer, Super Admin
Found 3 employees
  - Sending to John Smith (Admin)
    ✓ Sent successfully (1 notifications)
  - Sending to Jane Doe (Developer)
    ✓ Sent successfully (1 notifications)
  - Sending to Bob Wilson (Super Admin)
    ✓ Sent successfully (1 notifications)
```

If you see this, the system works - you just need the Task Scheduler to run it automatically.

---

### Step 3: Verify the Admin Is Configured Correctly

Even if the scheduler runs, notifications won't send if:

#### Check 1: Admin Has Push Subscription

1. Ask the admin to log into the system
2. Look for "Enable Notifications" button on their dashboard
3. If they see "Enable Notifications" → They haven't subscribed yet!
4. They must click it and allow browser notifications

#### Check 2: Admin's Position in Database

Run this SQL query to verify:
```sql
SELECT id, first_name, last_name, position, status 
FROM employees 
WHERE position IN ('Admin', 'Developer', 'Super Admin') 
AND status = 'Active';
```

Make sure the admin's `position` column says exactly `Admin`, `Developer`, or `Super Admin` (case-sensitive).

#### Check 3: Admin Already Timed Out

The system is smart - it won't send a time-out reminder if the admin already timed out today.

Check the attendance table:
```sql
SELECT e.first_name, e.last_name, a.time_out
FROM employees e
LEFT JOIN attendance a ON e.id = a.employee_id AND a.attendance_date = CURDATE()
WHERE e.position IN ('Admin', 'Developer', 'Super Admin')
AND e.status = 'Active';
```

If `time_out` is NOT NULL, they already timed out → No notification sent (by design).

---

## Common Issues & Solutions

### Issue 1: "I set up Task Scheduler but still no notification"

**Check the Task History:**
1. Open Task Scheduler
2. Find your task
3. Right-click → **Properties** → **History** tab
4. Is it showing "Task completed" or errors?

**Check if PHP path is correct:**
```cmd
# Run this in Command Prompt
C:\wamp64\bin\php\php8.0.30\php.exe -v
```

If you get "not recognized", the PHP path is wrong.

---

### Issue 2: "Task runs but notification not received on phone"

**Check list:**
- [ ] Admin enabled notifications on their phone (clicked "Enable Notifications")
- [ ] Admin allowed browser notification permissions
- [ ] Admin's phone has internet connection
- [ ] Admin didn't already time out today
- [ ] Admin's position is exactly `Admin`, `Developer`, or `Super Admin` in database

**Test the subscription:**
```sql
SELECT employee_id, COUNT(*) as subscription_count 
FROM push_subscriptions 
WHERE employee_id = [ADMIN_ID]
GROUP BY employee_id;
```

If `subscription_count` is 0, the admin never enabled notifications.

---

### Issue 3: "It worked yesterday but not today"

**Possible causes:**
1. **Server/WAMP was restarted** → Need to restart WAMP
2. **Task Scheduler service stopped** → Check Windows Services
3. **Admin's subscription expired** → They need to re-enable
4. **Database connection issue** → Check WAMP is running

---

### Issue 4: "Only some admins get it, others don't"

**Each admin must:**
1. Log in on their own device
2. Click "Enable Notifications" 
3. Allow browser permissions
4. Keep the subscription active

**Check individual subscriptions:**
```sql
SELECT 
    e.first_name, 
    e.last_name, 
    e.position,
    CASE WHEN ps.id IS NOT NULL THEN 'Subscribed' ELSE 'Not Subscribed' END as status
FROM employees e
LEFT JOIN push_subscriptions ps ON e.id = ps.employee_id
WHERE e.position IN ('Admin', 'Developer', 'Super Admin')
AND e.status = 'Active';
```

---

## Quick Checklist: Before 4:50 PM Tomorrow

Make sure ALL of these are done:

- [ ] **Task Scheduler** has a task for 4:50 PM
- [ ] **Task is enabled** (not disabled)
- [ ] **WAMP Server** is running
- [ ] **Admin has clicked** "Enable Notifications" on their phone
- [ ] **Admin allowed** browser notification permission
- [ ] **Admin's position** in database is `Admin`, `Developer`, or `Super Admin`
- [ ] **Admin hasn't** already timed out today

---

## Alternative: Manual Testing

If you want to send a notification RIGHT NOW (without waiting for 4:50 PM):

**Option 1: Run the script manually**
```cmd
C:\wamp64\bin\php\php8.0.30\php.exe C:\wamp64\www\main\employee\scheduled_attendance_notifications.php
```

**Option 2: Send to a specific admin via test page**

You could create a simple test page or use the existing notification system to send a test push.

---

## Summary

| Question | Answer |
|----------|--------|
| Do I need Task Scheduler? | **YES** - Without it, nothing runs at 4:50 PM |
| Why no notification today? | Task Scheduler not set up OR admin not subscribed |
| How to fix? | Set up Task Scheduler + Have admin enable notifications |
| Does it run automatically? | **Only if** Task Scheduler is configured |
| Can I test it now? | **Yes** - Run the PHP script manually |

**Bottom Line:** The code is ready, but Windows needs to be told to run it at 4:50 PM. That's what Task Scheduler does.

---

## Related Documentation

- `SCHEDULED_NOTIFICATIONS_SETUP.md` - Full Task Scheduler setup guide
- `TIME_IN_OUT_NOTIFICATION_SCHEDULE.md` - Notification times by role
- `PUSH_NOTIFICATION_COMPATIBILITY.md` - Platform support
- `NOTIFICATIONS_BROWSER_CLOSED.md` - How push notifications work

---

Last Updated: March 2026
