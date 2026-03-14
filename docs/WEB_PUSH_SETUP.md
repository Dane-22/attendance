# Web Push Notification Setup Guide

## Overview
This document explains how to set up and use the Web Push Notification system for JAJR Company Attendance & Audit Dashboard.

## VAPID Key Generation

VAPID (Voluntary Application Server Identification) keys are required for authenticating with browser push services.

### Generate VAPID Keys

Use one of these methods to generate your VAPID key pair:

#### Method 1: Using web-push (Node.js)
```bash
# Install web-push globally
npm install -g web-push

# Generate VAPID keys
web-push generate-vapid-keys

# Output will be:
# ========================================
# Public Key:
# BEl62i... (85 characters)
# Private Key:
# oIHfI4... (43 characters)
# ========================================
```

#### Method 2: Using web-push-php (PHP)
```bash
# Install web-push-php via Composer
composer require minishlink/web-push

# Use this PHP script to generate keys:
<?php
use Minishlink\WebPush\VAPID;

require 'vendor/autoload.php';
$keys = VAPID::createVapidKeys();
echo "Public Key: " . $keys['publicKey'] . "\n";
echo "Private Key: " . $keys['privateKey'] . "\n";
?>
```

#### Method 3: Online Generator
Visit: https://web-push-codelab.glitch.me/

### Configure Environment Variables

Add the generated keys to your `.env` file:

```bash
# VAPID Keys for Web Push Notifications
VAPID_PUBLIC_KEY=YourGeneratedPublicKeyHere
VAPID_PRIVATE_KEY=YourGeneratedPrivateKeyHere
VAPID_SUBJECT=mailto:admin@jajr.com
```

## Database Setup

Run the SQL script to create the push_subscriptions table:

```bash
mysql -u your_username -p attendance_db < dbschema/push_subscriptions.sql
```

Or execute directly in MySQL:
```sql
source /path/to/dbschema/push_subscriptions.sql
```

## Usage Examples

### Send Push Notification from PHP

```php
<?php
require_once 'conn/db_connection.php';
require_once 'functions.php';

// Send notification to user ID 6 (Super Admin)
$result = sendPushNotification(
    $db,           // Database connection
    6,             // User ID
    'New Attendance Alert',  // Title
    'Employee John Doe has clocked in at Main Branch',  // Message
    '/employee/dashboard.php'  // URL to open when clicked (optional)
);

if ($result['success']) {
    echo "Notification sent successfully to {$result['sent']} device(s)";
} else {
    echo "Failed to send notification: " . implode(', ', $result['errors']);
}
?>
```

### Send Notification on Specific Events

Example: Notify when overtime is requested:

```php
<?php
// In your overtime request processing code
require_once '../functions.php';

// After saving overtime request
$superAdminId = 6; // ID of Super Admin to notify
$employeeName = $employee['first_name'] . ' ' . $employee['last_name'];

sendPushNotification(
    $db,
    $superAdminId,
    'New Overtime Request',
    "$employeeName has requested {$hours} hours overtime on {$date}",
    '/employee/notification.php'
);
?>
```

## Files Created/Modified

### New Files:
1. `/sw.js` - Service Worker for handling push events
2. `/employee/api/save_push_subscription.php` - API to save push subscriptions
3. `/employee/api/get_vapid_key.php` - API to get VAPID public key
4. `/dbschema/push_subscriptions.sql` - SQL to create subscriptions table

### Modified Files:
1. `/functions.php` - Added `sendPushNotification()` and helper functions
2. `/employee/audit.php` - Added push notification UI for Super Admin

## Testing

1. Log in as Super Admin and visit `/employee/audit.php`
2. Click "Enable Notifications" in the widget at bottom-right
3. Allow notification permission in browser
4. Check browser console for success messages
5. Use the test script below to send a test notification:

```php
<?php
// test_push_notification.php
require_once 'conn/db_connection.php';
require_once 'functions.php';

if ($_SESSION['position'] !== 'Super Admin') {
    die('Super Admin access required');
}

$userId = $_SESSION['employee_id'];
$result = sendPushNotification(
    $db,
    $userId,
    'Test Notification',
    'This is a test notification from JAJR Dashboard',
    '/employee/dashboard.php'
);

echo '<pre>';
print_r($result);
echo '</pre>';
?>
```

## Browser Compatibility

- Chrome/Edge: Fully supported (Chromium-based)
- Firefox: Fully supported
- Safari: Supported on macOS 13+ and iOS 16.4+ (with limitations)
- Mobile browsers: Generally supported on Android Chrome

## Troubleshooting

### "VAPID keys not configured" error
- Ensure `.env` file contains VAPID_PUBLIC_KEY and VAPID_PRIVATE_KEY
- Restart web server after updating .env file

### "No push subscriptions found for user" error
- User hasn't enabled notifications yet
- Check browser console for JavaScript errors
- Verify Service Worker is registered at `/sw.js`

### Notification not received
- Check browser notification settings (should not be blocked)
- Verify subscription is saved in database
- Check push service endpoint is accessible
- Review error logs for detailed messages

### "Subscription expired" error
- This is normal; expired subscriptions are automatically removed
- User will need to re-enable notifications

## Security Notes

1. **Keep VAPID private key secret** - Never expose it in client-side code
2. **Validate user permissions** - Only Super Admin can receive/subscribe
3. **Use HTTPS** - Push notifications require secure context in production
4. **Clean up expired subscriptions** - System automatically removes 404/410 responses

## Performance Considerations

- Push subscriptions are stored per user, not per session
- Database queries use proper indexing for fast lookups
- Expired subscriptions are automatically cleaned up
- cURL timeout is set to 10 seconds to prevent blocking

## Support

For issues or questions:
1. Check browser console for JavaScript errors
2. Review PHP error logs
3. Verify database table exists: `SHOW TABLES LIKE 'push_subscriptions'`
4. Confirm VAPID keys are properly configured
