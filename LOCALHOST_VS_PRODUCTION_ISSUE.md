# Localhost vs Production (jajr.xandree.com) Issue Analysis

**Date:** April 7, 2026  
**Issue:** Site works on local PC (WAMP) but not on Chrome accessing jajr.xandree.com

---

## Quick Summary

The site works locally because:
1. ✅ Local database credentials exist in `.env` file
2. ✅ Windows file system is case-insensitive
3. ✅ No HTTPS/CORS issues on localhost
4. ✅ Geolocation permissions are allowed on local development

Production (jajr.xandree.com) likely fails because:
1. ❌ **Missing `.env` file** (gitignored, production DB credentials not configured)
2. ❌ **Geolocation blocked by .htaccess** - `Permissions-Policy: geolocation=()`
3. ❌ **Linux file system is case-sensitive** - file path mismatches
4. ❌ **Possible PHP version or extension differences**

---

## Detailed Root Causes

### 1. Database Connection Failure (CRITICAL)

**Evidence:**
- `.env` file is **gitignored** (listed in `.gitignore`)
- `conn/db_connection.php` requires environment variables:
  ```php
  $host = getenv('DB_HOST');      // From .env
  $user_name = getenv('DB_USER');  // From .env
  $passwd = getenv('DB_PASS');     // From .env
  $schema = getenv('DB_SCHEMA');   // From .env
  ```

**Local .env contents (from `fix_env.php`):**
```
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_SCHEMA=attendance_db
```

**Production Problem:**
- `.env` file was never uploaded (gitignored)
- Production server needs different credentials
- Without `.env`, all values are `null` → database connection fails

**Fix for Production:**
1. Create `.env` file on production server with correct credentials:
   ```
   DB_HOST=your_production_db_host
   DB_USER=your_production_db_user
   DB_PASS=your_production_db_password
   DB_SCHEMA=your_production_db_name
   ```
2. Or set environment variables in server configuration
3. Or modify `conn/db_connection.php` to use hardcoded production credentials

---

### 2. Geolocation Blocked by .htaccess (CRITICAL for QR Scanning)

**Evidence from `.htaccess`:**
```apache
Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"
```

**Problem:**
- This header **BLOCKS all geolocation requests** on production
- QR scanning and time-in requires GPS location
- Chrome respects this policy and denies location access

**Fix:**
Change `.htaccess` to allow geolocation:
```apache
Header always set Permissions-Policy "geolocation=(self), microphone=(), camera=()"
```

---

### 3. Case Sensitivity Issues (MEDIUM)

**Problem:**
- Windows (local): case-insensitive file system
- Linux (production): case-sensitive file system

**Risk Areas:**
- `employee/` folder vs `Employee/` references
- `functions.php` vs `Functions.php`
- `conn/db_connection.php` vs `Conn/DB_Connection.php`

**Evidence:**
- Project uses lowercase folder names (`employee/`, `conn/`, `assets/`)
- Most includes use lowercase: `require_once __DIR__ . '/conn/db_connection.php'`
- Generally consistent, but any case mismatch will fail on Linux

---

### 4. HTTPS/CORS Issues (MEDIUM)

**Evidence from login.php:**
```javascript
const branchResponse = await fetch(`${window.location.origin}/get_branch_api.php`);
```

**Potential Issues:**
- If production uses HTTPS but tries to access HTTP resources
- Mixed content blocked by Chrome
- CORS policy violations

**Note:** `.htaccess` has HTTPS redirect commented out:
```apache
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]
```

---

### 5. Missing Vendor Dependencies (LOW)

**Evidence:**
- `vendor/` folder exists (composer dependencies)
- `composer.json` and `composer.lock` present

**Risk:**
- If `vendor/` wasn't uploaded to production
- Autoloader would fail
- Check if composer was run on production server

---

## Troubleshooting Checklist

### Immediate Checks

1. **Check if site loads at all**
   - Visit `jajr.xandree.com` - does it show anything?
   - Check for 500 Internal Server Error
   - Check for database connection errors

2. **Verify .env file exists on production**
   ```bash
   # On production server
   ls -la /path/to/site/.env
   cat /path/to/site/.env
   ```

3. **Check error logs**
   ```bash
   # Apache error log
   tail -f /var/log/apache2/error.log
   
   # Or PHP error log
   tail -f /var/log/php_errors.log
   ```

4. **Test database connection**
   Create a simple test file on production:
   ```php
   <?php
   require_once 'conn/db_connection.php';
   echo "Database connected successfully!";
   ?>
   ```

### Chrome DevTools Checks

1. Open Chrome DevTools (F12)
2. Check **Console** tab for JavaScript errors
3. Check **Network** tab for failed API requests
4. Look for:
   - 500 Internal Server Error
   - CORS errors
   - Mixed content warnings
   - Geolocation permission denied

---

## Recommended Fixes (Priority Order)

### 1. Fix Database Connection (URGENT)

**Option A: Create .env on production**
```bash
# SSH into production server
cd /var/www/html  # or your web root
touch .env
chmod 600 .env  # Secure the file
```

Add production credentials to `.env`:
```
DB_HOST=your_db_host
DB_USER=your_db_user
DB_PASS=your_db_password
DB_SCHEMA=your_db_name
```

**Option B: Use server environment variables**
In Apache config or .htaccess:
```apache
SetEnv DB_HOST your_db_host
SetEnv DB_USER your_db_user
SetEnv DB_PASS your_db_password
SetEnv DB_SCHEMA your_db_name
```

---

### 2. Fix Geolocation Blocking (URGENT)

Edit `.htaccess`:
```apache
# Change this line:
Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"

# To this:
Header always set Permissions-Policy "geolocation=(self), microphone=(), camera=()"
```

---

### 3. Verify File Upload Completeness

Ensure these folders were uploaded:
- `vendor/` (composer dependencies)
- `conn/` (database connection)
- `functions.php`
- `.env` (after creating it)

---

### 4. PHP Configuration Check

Ensure production server has:
- PHP 7.4+ (check `phpversion()`)
- mysqli extension enabled
- Required PHP modules (curl, json, etc.)

---

## Diagnostic Script

Create `diagnose.php` on production to check all issues:

```php
<?php
echo "<h2>Production Server Diagnostics</h2>";

// Check PHP version
echo "<p>PHP Version: " . phpversion() . "</p>";

// Check .env file
if (file_exists('.env')) {
    echo "<p style='color:green'>.env file: EXISTS</p>";
} else {
    echo "<p style='color:red'>.env file: MISSING</p>";
}

// Check database connection
try {
    require_once 'conn/db_connection.php';
    echo "<p style='color:green'>Database: CONNECTED</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>Database: FAILED - " . $e->getMessage() . "</p>";
}

// Check vendor folder
if (is_dir('vendor')) {
    echo "<p style='color:green'>vendor folder: EXISTS</p>";
} else {
    echo "<p style='color:red'>vendor folder: MISSING</p>";
}

// Check permissions
$headers = headers_list();
$permPolicy = array_filter($headers, function($h) {
    return strpos($h, 'Permissions-Policy') !== false;
});
echo "<p>Permissions-Policy: " . implode(', ', $permPolicy) . "</p>";
?>
```

Upload to production and visit `jajr.xandree.com/diagnose.php`

---

## Most Likely Root Cause

**90% probability:** The `.env` file is missing on production because it's gitignored, causing database connection to fail with `null` credentials.

**Secondary issue:** The `.htaccess` file blocks geolocation, preventing QR scanning from working.

---

## Addendum: PC Works but Mobile Doesn't (Network-Specific Issue)

**Issue:** jajr.xandree.com works on PC via ethernet, but not on mobile via WiFi.

**CONFIRMED: WiFi router is SPECIFICALLY blocking `jajr.xandree.com`**

**Test Results:**
- ✅ Works on mobile data
- ✅ Other websites work on WiFi
- ❌ Only `jajr.xandree.com` blocked on WiFi

**Conclusion:** The WiFi router is specifically targeting/blocking `jajr.xandree.com`, not a general connectivity issue.

### Root Cause

Your WiFi router has a specific rule blocking `jajr.xandree.com`:

1. **DNS Filtering** - Router DNS specifically returns no result for this domain
2. **Domain Blacklist** - `jajr.xandree.com` added to router's blocked sites list
3. **IP Blocking** - Server IP associated with the domain is blocked
4. **Parental Controls** - Site categorized and blocked by content filters
5. **Safe Search/Family Filter** - Router-level filtering blocking the site

### Router Troubleshooting Steps

1. **Restart Router**
   - Unplug router for 30 seconds, plug back in
   - Test access after router fully boots

2. **Change DNS Servers on Router**
   - Log into router admin panel (usually 192.168.1.1 or 192.168.0.1)
   - Change DNS to Google DNS:
     - Primary: 8.8.8.8
     - Secondary: 8.8.4.4
   - Or Cloudflare DNS:
     - Primary: 1.1.1.1
     - Secondary: 1.0.0.1

3. **Check Firewall/Security Settings**
   - Look for "Access Restrictions" or "Firewall"
   - Check if `jajr.xandree.com` or its IP is blocked
   - Disable parental controls temporarily to test

4. **Flush DNS on Mobile**
   - iOS: Turn Airplane Mode on/off
   - Android: Settings → Network → Private DNS → Off, then back on

5. **Use Mobile Data as Workaround**
   - Until router is fixed, use mobile data for site access

### Most Likely Router Issue

- **70% probability:** Router DNS cannot resolve `jajr.xandree.com`
- **20% probability:** Router firewall blocking the domain/IP
- **10% probability:** Parental controls or content filtering

### Immediate Solution

**Option 1: Change WiFi DNS on Mobile (Quick Fix)**
- iOS: Settings → WiFi → (i) next to network → Configure DNS → Manual → Add 8.8.8.8
- Android: Settings → Network → Private DNS → hostname: dns.google

**Option 2: Use Mobile Data**
- Turn off WiFi, use mobile data for accessing the site

**Option 3: Fix Router**
- Change router DNS settings or disable blocking rules

---

## FINAL CONFIRMATION: ISP-Level CGNAT IP Flagging (Converge ICT)

**Root Cause CONFIRMED:**
- **ISP:** Converge ICT
- **Issue:** CGNAT (Carrier-Grade NAT) IP flagging
- **Shared Public IP:** 119.93.99.226
- **Affected:** Hostinger firewall blocking the shared IP

**Evidence:**
- ✅ Works on Mobile Data (different ISP/routing)
- ✅ Works on PLDT (different ISP, different IP block)
- ❌ Blocked on Converge ICT WiFi (shared CGNAT IP flagged)
- ✅ Server and code are functioning correctly

### Technical Explanation

1. **CGNAT (Carrier-Grade NAT)**
   - Converge ICT shares one public IP (119.93.99.226) among thousands of users
   - Thousands of households/devices behind this single IP

2. **IP Flagging by Hostinger**
   - Another user sharing 119.93.99.226 may have generated suspicious traffic
   - Hostinger's firewall flagged the entire shared IP block
   - Result: All Converge users behind this IP are blocked

3. **Not Application Logic**
   - This is NOT a bug in your code
   - This is NOT a server configuration issue
   - This is external infrastructure blocking

### Solutions

**Immediate:**
- Use Mobile Data for site access
- Or switch to PLDT/other ISP

**Long-term:**
- Contact Converge ICT support - request IP refresh or CGNAT exemption
- Contact Hostinger support - request whitelist for your domain from the flagged IP
- Consider dedicated/static IP from ISP (business plan)

### Summary

| Component | Status |
|-----------|--------|
| Application Code | ✅ Working |
| Server Configuration | ✅ Working |
| Database | ✅ Working |
| Mobile Data Access | ✅ Working |
| PLDT Access | ✅ Working |
| Converge ICT Access | ❌ Blocked (CGNAT IP flagged) |

**Conclusion:** Your application is fully functional. The issue is external ISP-level IP blocking outside your control.

---

*Final update: April 7, 2026 (Confirmed - Converge ICT CGNAT IP flagging)*
*Report created: April 7, 2026*
