# Admin & Developer Saturday Schedule Explanation

## What "No Saturday Time-Out" Means

### Current Schedule

| Day | Admin Time-In | Admin Time-Out | Developer Time-In | Developer Time-Out |
|-----|---------------|----------------|-------------------|--------------------|
| **Monday-Friday** | 7:30 AM ✅ | 4:50 PM ✅ | 7:30 AM ✅ | 4:50 PM ✅ |
| **Saturday** | 7:30 AM ✅ | **NO NOTIFICATION** | 7:30 AM ✅ | **NO NOTIFICATION** |
| **Sunday** | NO NOTIFICATION | NO NOTIFICATION | NO NOTIFICATION | NO NOTIFICATION |

---

## Explanation

### "No Sat Time-Out" = No Push Notification Reminder

**This does NOT mean:**
- ❌ Admin/Developer don't work on Saturday
- ❌ Admin/Developer don't need to time out on Saturday
- ❌ Saturday is a day off

**This DOES mean:**
- ✅ They still work Saturday (half-day or full-day)
- ✅ They still need to time out manually
- ✅ They just **won't receive a push notification reminder** at 4:50 PM on Saturday
- ✅ They still get the **time-in reminder** at 7:30 AM on Saturday

---

## Why This Design?

### Common Saturday Work Patterns

Most offices have different Saturday schedules:

| Schedule Type | Description | Time-Out Time |
|---------------|-------------|---------------|
| **Half-Day Saturday** | Work 8:00 AM - 12:00 PM (noon) | 12:00 PM |
| **Full-Day Saturday** | Work 8:00 AM - 5:00 PM | 5:00 PM |
| **Flexible Saturday** | Variable hours based on workload | Varies |

Since Saturday work hours are **variable and inconsistent**, the system **doesn't send a fixed 4:50 PM reminder** that might be:
- Too early (if working full day until 5:00 PM)
- Too late (if doing half-day and left at 12:00 PM)
- Irrelevant (if not working Saturday at all)

---

## Comparison: Engineer vs Admin/Developer Saturday

| Role | Saturday Schedule | Time-In | Time-Out |
|------|-------------------|---------|----------|
| **Engineer** | Fixed schedule (7:00 AM - 4:00 PM) | 6:50 AM ✅ | 3:50 PM ✅ |
| **Admin** | Variable/flexible hours | 7:30 AM ✅ | **Manual only** |
| **Developer** | Variable/flexible hours | 7:30 AM ✅ | **Manual only** |

**Engineers** have a fixed site-based schedule, so they get both reminders.

**Admin/Developer** have office-based flexible Saturday schedules, so they only get the morning reminder.

---

## How This Affects Users

### Admin/Developer on Saturday:

1. **7:30 AM** - Receive push notification: "Good morning! Please don't forget to time in..."
2. **Work their Saturday hours** (could be half-day or full-day)
3. **Time out manually** when leaving (no push reminder)
4. **No 4:50 PM notification** will be sent

### Important:
- They **must still time out** through the web interface
- The attendance system **still tracks** their time-out
- They just **won't get a notification reminder** to do so

---

## How to Change This (If Needed)

If your office has **fixed Saturday hours** for Admin/Developer, you can add the time-out notification by editing:

**File:** `scheduled_attendance_notifications.php`

### Option 1: Add Saturday to Existing Schedule

Find this code block (around line 68-77):

```php
// Admin & Developer Time-out: 4:50 PM, Mon-Fri only
[
    'time' => '16:50',
    'positions' => ['Admin', 'Developer'],
    'type' => 'time_out',
    'title' => 'Time Out Reminder',
    'message' => 'Reminder: Please don't forget to time out before leaving. Have a great evening!',
    'url' => '/employee/attendance.php',
    'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']
],
```

Add `'Saturday'` to the days array:

```php
    'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
```

### Option 2: Create Separate Saturday Schedule

Add a new schedule entry for different Saturday time:

```php
// Admin & Developer Time-out: 12:00 PM (noon) on Saturday only
[
    'time' => '12:00',
    'positions' => ['Admin', 'Developer'],
    'type' => 'time_out',
    'title' => 'Time Out Reminder',
    'message' => 'Reminder: Please don't forget to time out before leaving. Enjoy your weekend!',
    'url' => '/employee/attendance.php',
    'days' => ['Saturday']  // Saturday only
],
```

---

## Frequently Asked Questions

### Q: Does this mean Admin/Developer don't work on Saturday?
**A:** No. They still work Saturday. They just don't get an automatic time-out reminder push notification.

### Q: Will they still be paid for Saturday work?
**A:** Yes. As long as they manually time in and time out, attendance is recorded normally for payroll.

### Q: Can they still receive time-out notifications on Saturday?
**A:** Yes, but only if you modify the schedule in `scheduled_attendance_notifications.php` (see "How to Change This" above).

### Q: Why do Engineers get Saturday time-out but Admin don't?
**A:** Engineers have a fixed 7:00 AM - 4:00 PM schedule at construction sites. Admin/Developer have flexible Saturday hours in the office.

### Q: What if Admin/Developer forget to time out on Saturday?
**A:** They'll need to manually time out later, or an admin can correct their attendance record. No automatic reminder is sent.

---

## Technical Details

### Source Code Reference

**File:** `scheduled_attendance_notifications.php`

```php
// Admin & Developer Time-out: 4:50 PM, Mon-Fri only (NO SATURDAY)
[
    'time' => '16:50',
    'positions' => ['Admin', 'Developer'],
    'type' => 'time_out',
    'title' => 'Time Out Reminder',
    'message' => 'Reminder: Please don't forget to time out before leaving. Have a great evening!',
    'url' => '/employee/attendance.php',
    'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']  // Saturday NOT included
],
```

### Why the Code Excludes Saturday

The `'days'` array intentionally does NOT include `'Saturday'` for Admin/Developer time-out, while Engineers have:

```php
'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']  // Engineers include Saturday
```

---

## Summary

| Question | Answer |
|----------|--------|
| Do Admin work Saturday? | Yes |
| Do Developer work Saturday? | Yes |
| Do they get time-in reminder? | Yes (7:30 AM) |
| Do they get time-out reminder? | **No** (not on Saturday) |
| Do they need to time out? | Yes, manually |
| Can this be changed? | Yes, edit the PHP file |

---

## Related Documents

- `TIME_IN_OUT_NOTIFICATION_SCHEDULE.md` - Complete schedule for all roles
- `SCHEDULED_NOTIFICATIONS_SETUP.md` - How to configure Task Scheduler
- `scheduled_attendance_notifications.php` - Source code

---

Last Updated: March 2026
