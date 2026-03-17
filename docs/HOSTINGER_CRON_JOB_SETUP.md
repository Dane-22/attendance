# How to Set Up Scheduled Notifications on Hostinger (Cron Jobs)

## Overview

Since your website is hosted on Hostinger, you need to use **Hostinger's Cron Jobs** feature instead of Windows Task Scheduler. Cron Jobs automatically run your PHP script at specific times to send push notifications.

---

## Why Windows Task Scheduler Won't Work

Your website is live at `https://jajr.xandree.com/` which means:
- ❌ The database is on Hostinger's server, not your local computer
- ❌ Windows Task Scheduler on your PC cannot access the remote database
- ✅ You must use Hostinger's built-in Cron Job system

---

## Step-by-Step Setup

### Step 1: Log into Hostinger hPanel

1. Go to [https://hpanel.hostinger.com/](https://hpanel.hostinger.com/)
2. Log in with your Hostinger credentials
3. Select your website (jajr.xandree.com)

---

### Step 2: Find the Cron Jobs Section

1. Scroll down to the **"Advanced"** section
2. Click **"Cron Jobs"**
3. OR use the search bar and type "cron"

![Cron Jobs Location](https://support.hostinger.com/assets/images/cron-jobs-location.png)

---

### Step 3: Find Your PHP Script Path

Before creating cron jobs, you need the full path to your script.

**Method 1: Check File Manager**
1. In hPanel, click **"Files"** → **"File Manager"**
2. Navigate to the `employee` folder
3. Look for `scheduled_attendance_notifications.php`
4. Note the full path (usually `/home/username/public_html/employee/`)

**Method 2: Common Hostinger Paths**
```
/home/u123456789/public_html/employee/scheduled_attendance_notifications.php
/home/username/public_html/employee/scheduled_attendance_notifications.php
/home/yourdomain/public_html/employee/scheduled_attendance_notifications.php
```

**To find your exact path:**
1. Create a temporary file `test.php` in File Manager
2. Add this code:
   ```php
   <?php echo __DIR__; ?>
   ```
3. Visit `https://jajr.xandree.com/test.php`
4. It will show the full path

---

### Step 4: Create Cron Jobs

You need to create **6 separate cron jobs** for each notification time.

#### Cron Job 1: Engineer Time-In (6:50 AM)

| Setting | Value |
|---------|-------|
| **Command** | `/usr/bin/php /home/YOUR_USERNAME/public_html/employee/scheduled_attendance_notifications.php` |
| **Common Settings** | Custom |
| **Minute** | 50 |
| **Hour** | 6 |
| **Day** | * (every day) |
| **Month** | * (every month) |
| **Weekday** | 1-6 (Monday to Saturday) |

Click **"Create"**

---

#### Cron Job 2: Engineer Time-Out (3:50 PM)

| Setting | Value |
|---------|-------|
| **Command** | Same as above |
| **Minute** | 50 |
| **Hour** | 15 (3 PM in 24-hour format) |
| **Day** | * |
| **Month** | * |
| **Weekday** | 1-6 |

Click **"Create"**

---

#### Cron Job 3: Admin Time-In (7:30 AM)

| Setting | Value |
|---------|-------|
| **Command** | Same as above |
| **Minute** | 30 |
| **Hour** | 7 |
| **Day** | * |
| **Month** | * |
| **Weekday** | 1-6 |

Click **"Create"**

---

#### Cron Job 4: Admin Time-Out (4:50 PM) ⭐ **MOST IMPORTANT**

| Setting | Value |
|---------|-------|
| **Command** | Same as above |
| **Minute** | 50 |
| **Hour** | 16 (4 PM in 24-hour format) |
| **Day** | * |
| **Month** | * |
| **Weekday** | 1-6 |

Click **"Create"**

---

#### Cron Job 5: Worker Time-In (8:00 AM)

| Setting | Value |
|---------|-------|
| **Command** | Same as above |
| **Minute** | 0 |
| **Hour** | 8 |
| **Day** | * |
| **Month** | * |
| **Weekday** | 1-6 |

Click **"Create"**

---

#### Cron Job 6: Worker Time-Out (5:00 PM)

| Setting | Value |
|---------|-------|
| **Command** | Same as above |
| **Minute** | 0 |
| **Hour** | 17 (5 PM in 24-hour format) |
| **Day** | * |
| **Month** | * |
| **Weekday** | 1-6 |

Click **"Create"**

---

### Step 5: Verify Your Cron Jobs

After creating all 6, your Cron Jobs list should look like this:

| # | Command | Schedule |
|---|---------|----------|
| 1 | `/usr/bin/php ...` | 6:50 AM, Mon-Sat |
| 2 | `/usr/bin/php ...` | 3:50 PM, Mon-Sat |
| 3 | `/usr/bin/php ...` | 7:30 AM, Mon-Sat |
| 4 | `/usr/bin/php ...` | 4:50 PM, Mon-Sat |
| 5 | `/usr/bin/php ...` | 8:00 AM, Mon-Sat |
| 6 | `/usr/bin/php ...` | 5:00 PM, Mon-Sat |

---

### Step 6: Test a Cron Job

**Important:** Test if it's working before waiting for the scheduled time.

**Method 1: Run Now Button**
1. In the Cron Jobs list, find any job
2. Click the **"Run Now"** button (play icon)
3. Wait 1-2 minutes
4. Check if notifications were sent

**Method 2: Manual Test via Browser**
1. Create a test URL by visiting:
   ```
   https://jajr.xandree.com/employee/scheduled_attendance_notifications.php
   ```
   in your browser
2. You should see output like:
   ```
   Processing schedule: Time Out Reminder for Admin, Developer, Super Admin
   Found 3 employees
   - Sending to John Smith (Admin)
     ✓ Sent successfully (1 notifications)
   ```
3. If you see this, the script works!

**Note:** The browser test won't actually send at the exact time - it runs all schedules. But it confirms the script works.

---

## Alternative: Single Cron Job with Multiple Times

If Hostinger limits your cron jobs, you can create **one cron job that runs every minute**:

| Setting | Value |
|---------|-------|
| **Command** | `/usr/bin/php /home/YOUR_USERNAME/public_html/employee/scheduled_attendance_notifications.php` |
| **Minute** | * (every minute) |
| **Hour** | * (every hour) |
| **Day** | * |
| **Month** | * |
| **Weekday** | 1-6 |

**Pros:**
- Only 1 cron job needed
- Script checks current time and only sends when appropriate

**Cons:**
- Runs every minute (6 times per hour, 60 times a day)
- Uses more server resources
- May trigger rate limits on some hosting plans

---

## Troubleshooting

### "Command not found" Error

**Problem:** Path to PHP is wrong

**Solution:**
Try these common PHP paths:
```
/usr/bin/php
/usr/local/bin/php
/opt/alt/php74/usr/bin/php
/opt/alt/php80/usr/bin/php
/opt/alt/php81/usr/bin/php
```

**Find the correct path:**
1. In hPanel, go to **"PHP"** → **"PHP Info"**
2. Look for "Configure Command" or ask Hostinger support

---

### "File not found" Error

**Problem:** Path to script is wrong

**Solution:**
1. Check File Manager for exact path
2. Common Hostinger paths:
   - `/home/u123456789/public_html/employee/`
   - `/home/username/domains/yourdomain.com/public_html/employee/`

---

### Cron Job Runs But No Notifications Sent

**Check these:**

1. **Are employees subscribed?**
   ```sql
   SELECT COUNT(*) FROM push_subscriptions;
   ```
   If 0, no one has enabled notifications yet.

2. **Are employees already timed in/out?**
   The script skips people who already timed in/out for the day.

3. **Is the database connected?**
   Check if `db_connection.php` has correct credentials for live server.

4. **Are VAPID keys configured?**
   Check if `.env` file exists with VAPID keys on the server.

---

### Notifications Work When Testing but Not From Cron

**Problem:** Environment variables not loaded

**Solution:**
The cron job runs in a minimal environment. Make sure your PHP script loads `.env` correctly.

In `scheduled_attendance_notifications.php`, check:
```php
// Load environment variables
require_once __DIR__ . '/../vendor/autoload.php'; // If using Composer
// OR
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    // Load .env manually
}
```

---

## Summary Table

| Notification | Time | Minute | Hour | Weekday |
|--------------|------|--------|------|---------|
| Engineer Time-In | 6:50 AM | 50 | 6 | 1-6 |
| Engineer Time-Out | 3:50 PM | 50 | 15 | 1-6 |
| Admin Time-In | 7:30 AM | 30 | 7 | 1-6 |
| Admin Time-Out | 4:50 PM | 50 | 16 | 1-6 |
| Worker Time-In | 8:00 AM | 0 | 8 | 1-6 |
| Worker Time-Out | 5:00 PM | 0 | 17 | 1-6 |

**Note:** Weekday 1-6 means Monday (1) through Saturday (6). Sunday is 0 or 7.

---

## Important Notes

### Server Time Zone
Hostinger servers are typically in UTC or the data center's local time zone. Make sure your cron times match the **server time**, not your local time.

**Check server time:**
Create a file with:
```php
<?php echo date('Y-m-d H:i:s'); ?>
```

### No Notifications on Sunday
The script automatically skips Sunday. You don't need to configure this - it's built into the PHP code.

### One Script, Multiple Times
Remember: The **same** script runs at all times. It checks the current time and decides who to notify.

```
6:50 AM → Script runs → Sends to Engineers
3:50 PM → Script runs → Sends to Engineers
7:30 AM → Script runs → Sends to Admin/Dev/Super Admin
4:50 PM → Script runs → Sends to Admin/Dev/Super Admin
8:00 AM → Script runs → Sends to Workers
5:00 PM → Script runs → Sends to Workers
```

---

## FAQ

### Q: Do I need to keep my computer on?
**A:** No! Cron jobs run on Hostinger's servers 24/7.

### Q: Will this work if my computer is off?
**A:** Yes! The server handles everything.

### Q: How do I know if it's working?
**A:** 
- Test via "Run Now" button in hPanel
- Wait for the scheduled time and check with employees
- Check Hostinger's cron job logs (if available)

### Q: Can I use Windows Task Scheduler instead?
**A:** No - it won't have access to Hostinger's database. Must use hPanel Cron Jobs.

### Q: What if Hostinger limits my cron jobs?
**A:** Use the "Alternative" method above - one cron job running every minute.

### Q: Do I need to set up anything else?
**A:** Make sure employees have clicked "Enable Notifications" on their phones and allowed browser permissions.

---

## Next Steps

After setting up cron jobs:

1. **Delete Windows Task Scheduler tasks** (they won't work)
2. **Test the cron job** using "Run Now" button
3. **Ask employees** to enable notifications on their phones
4. **Wait for scheduled time** (e.g., 4:50 PM for Admin time-out)
5. **Verify notifications** are received

---

## Related Documentation

- `SCHEDULED_NOTIFICATIONS_SETUP.md` - Windows Task Scheduler (for local servers only)
- `TIME_IN_OUT_NOTIFICATION_SCHEDULE.md` - Notification times by role
- `NOTIFICATION_TROUBLESHOOTING.md` - General troubleshooting
- `PUSH_NOTIFICATION_COMPATIBILITY.md` - Platform support (Android/iOS)

---

Last Updated: March 2026
