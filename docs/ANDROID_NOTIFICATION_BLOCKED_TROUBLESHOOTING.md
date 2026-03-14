# Android Push Notification "Blocked" Troubleshooting Guide

## Problem: "Notifications blocked" Message on Android

When an Admin user on Android sees **"Notifications blocked"** in the push notification widget instead of "Enable Notifications", this means the browser or Android system has denied notification permissions.

---

## Common Causes & Solutions

### 1. User Previously Denied Permission (Most Common)

**What happened:**
- The user clicked "Block" or "Don't Allow" when first prompted
- Browser remembers this choice and won't ask again

**How to Fix:**

#### For Chrome:
1. Open Chrome browser
2. Tap the **3-dot menu** (⋮) in top-right corner
3. Go to **Settings** → **Site settings** → **Notifications**
4. Find `jajr.xandree.com` in the blocked list
5. Tap it and select **Allow**
6. OR tap **Reset permissions** to clear all and re-prompt

#### Alternative Method:
1. Visit the JAJR Attendance website
2. Tap the **lock icon** (🔒) in the address bar
3. Tap **Site settings**
4. Find **Notifications** and change from "Block" to "Allow"
5. Refresh the page

---

### 2. Android System-Level Blocking

**What happened:**
- Chrome app notifications are disabled at the Android system level
- This affects ALL websites, not just JAJR

**How to Fix:**

1. Open Android **Settings**
2. Go to **Apps** or **Applications**
3. Find and tap **Chrome** (or your browser)
4. Tap **Notifications**
5. Ensure **All Chrome notifications** is **ON**
6. Check that **Sites** category is enabled
7. Go back and check **Mobile data** → Ensure **Background data** is ON

**Check Data Saver:**
- Settings → **Network & Internet** → **Data Saver**
- If ON, tap **Unrestricted data** and enable for Chrome

---

### 3. Private/Incognito Mode

**What happened:**
- User opened the site in Incognito/Private mode
- Push notifications are disabled in private browsing

**How to Fix:**
- Close the private tab
- Open a regular Chrome window
- Log in normally
- Click "Enable Notifications"

---

### 4. Multiple Denials (Browser "Don't Ask Again")

**What happened:**
- User denied permission multiple times
- Browser stopped asking

**How to Fix (Chrome):**
1. Go to **Settings** → **Privacy and security** → **Site settings** → **Notifications**
2. Look for the JAJR site under "Blocked"
3. Tap the trash icon to delete the entry
4. Revisit the site and click "Enable Notifications" again

---

### 5. Work Profile / Managed Device Restrictions

**What happened:**
- Device is managed by company (MDM - Mobile Device Management)
- IT policy blocks browser notifications

**How to Fix:**
- Contact your IT administrator
- Ask them to whitelist browser notifications
- Or request an exception for the JAJR attendance domain

---

## Quick Diagnostic Steps

### Step 1: Check if it's a browser issue
```
1. Open Chrome
2. Go to chrome://settings/content/notifications
3. Check if you see the JAJR website in "Block" section
4. If yes, remove it
```

### Step 2: Check Android notification settings
```
1. Settings → Apps → Chrome
2. Check Notifications are ON
3. Check "Other notifications" is ON (for Service Workers)
```

### Step 3: Test with a simple site
- Visit https://web-push-book.gauntface.com/demos/notification-examples/
- Try the "Request Permission" button
- If this also fails, it's a system-wide issue

---

## Prevention: Best Practices for Admins

### When First Enabling Notifications:

1. **Don't rush** - When the permission popup appears, read it carefully
2. **Click "Allow"** - Not "Block" or "Don't Allow"
3. **If you make a mistake** - Follow the reset steps above immediately

### For IT Administrators:

**Pre-configure devices:**
- Use Android Enterprise to set default notification permissions
- Whitelist the JAJR domain in managed browser configurations
- Push a configuration that allows notifications for attendance system

---

## Alternative: Manual Workaround

If notifications cannot be enabled, Admins can still receive alerts through:

### 1. In-App Badge Counts (Always Works)
- The red notification badge on the sidebar bell icon
- Shows pending overtime/cash advance requests
- Updates on page refresh

### 2. Email Notifications
- Configure email alerts in user settings
- Requires SMTP setup in the system

### 3. Manual Checking
- Regularly visit the Notification page
- Set a bookmark for quick access
- Check at the start of each workday

---

## Error Messages Explained

| Message | Meaning | Solution |
|---------|---------|----------|
| "Notifications blocked" | User denied permission | Reset site permissions in Chrome settings |
| "Notifications not supported" | Old browser/Android version | Update Chrome/Android OS |
| "Permission error" | System blocking | Check Android notification settings |
| "Subscription failed" | Server/VAPID issue | Contact IT support |

---

## Android Version-Specific Issues

### Android 13+ (API 33+)
- **New behavior:** Apps must request notification permission explicitly
- **Fix:** Go to Settings → Apps → Chrome → Notifications → Enable

### Android 12 (API 31-32)
- **Issue:** Do Not Disturb can block web notifications
- **Fix:** Check Settings → Sound → Do Not Disturb

### Android 10-11 (API 29-30)
- **Issue:** Background restrictions kill Service Workers
- **Fix:** Settings → Apps → Chrome → Battery → No restrictions

### Android 9 and below (API 28-)
- **Issue:** Limited Service Worker support
- **Fix:** Update to latest Chrome version available

---

## Testing Checklist

After fixing the issue, verify notifications work:

- [ ] Refresh the JAJR page
- [ ] Click "Enable Notifications" button
- [ ] Tap "Allow" when prompted
- [ ] Widget should change to "Notifications enabled" with green bell
- [ ] Log out and close Chrome completely
- [ ] Send a test notification from server
- [ ] Check if notification appears in Android notification shade

---

## Contact Support

If none of these solutions work:

1. **Note your:**
   - Android version (Settings → About phone)
   - Chrome version (Chrome → ⋮ → Settings → About Chrome)
   - Device model

2. **Take screenshots of:**
   - The blocked message
   - Chrome notification settings
   - Android app notification settings for Chrome

3. **Contact IT with this information**

---

## Summary

**Most common fix:**
1. Chrome → Settings → Site settings → Notifications
2. Find jajr.xandree.com
3. Change from "Block" to "Allow"
4. Refresh page and try again

**Second most common:**
1. Android Settings → Apps → Chrome → Notifications
2. Turn ON all notification categories
3. Ensure "Background data" is enabled
