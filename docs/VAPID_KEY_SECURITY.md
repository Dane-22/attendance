# VAPID Key Security Guide

## Overview

VAPID (Voluntary Application Server Identification) keys are used to secure Web Push notifications. This guide explains how VAPID keys are managed in the JAJR Attendance System and the security measures in place.

---

## What Are VAPID Keys?

VAPID uses a **key pair** consisting of:
- **Public Key** - Shared with browsers to identify your server (safe to expose)
- **Private Key** - Used to sign push messages (**NEVER expose this**)

### Key Characteristics
- **Format**: ECDSA P-256 keys in Base64URL encoding
- **Length**: ~87 characters for each key
- **Starts with**: `BN...` or similar Base64URL-safe characters

---

## Current Security Status

### ✅ Secure Implementations Found

| Component | Status | Details |
|-----------|--------|---------|
| `.env` file | ✅ Protected | Blocked by `.htaccess`, listed in `.gitignore` |
| `.env.new` | ⚠️ Partial | Contains only public key (safe), but should be renamed |
| `get_vapid_key.php` | ✅ Secure | Only returns PUBLIC key to authenticated users |
| `functions.php` | ✅ Secure | Loads keys from environment, never hardcoded |
| JavaScript | ✅ Secure | Fetches public key via API, never hardcoded |

---

## Security Measures in Place

### 1. File Access Protection (`.htaccess`)

```apache
<FilesMatch "^(\.env|\.git|\.htaccess|\.htpasswd)$">
    Require all denied
</FilesMatch>
```

**What this does:**
- Blocks all web access to `.env` files
- Returns 403 Forbidden if someone tries to access `/.env`

### 2. Git Protection (`.gitignore`)

```gitignore
# Environment files
.env
.env.local
.env.*.local
```

**What this does:**
- Prevents `.env` files from being committed to git
- Ensures private keys don't end up in repositories

### 3. PHP Access Control (`get_vapid_key.php`)

```php
// Only returns PUBLIC key
// Requires authenticated session
if (empty($_SESSION['logged_in']) || empty($_SESSION['employee_id'])) {
    http_response_code(403);
    exit;
}
```

**What this does:**
- Only logged-in users can get the public key
- Private key is never exposed via this endpoint

---

## Key Management Best Practices

### ✅ DO

1. **Store keys in `.env` file**
   ```
   VAPID_PUBLIC_KEY=BKyvFnHq0kFWpxv...
   VAPID_PRIVATE_KEY=<your-private-key-here>
   VAPID_SUBJECT=mailto:admin@jajr.com
   ```

2. **Generate new keys periodically** (every 6-12 months)
   ```bash
   php generate_vapid.php
   ```

3. **Keep `.env` outside web root if possible**
   - Current: `/wamp64/www/main/.env`
   - Better: `/wamp64/www/.env` (outside project folder)

4. **Use environment variables on production**
   - Server-level env vars are more secure than files

### ❌ DON'T

1. **Never commit keys to git**
   ```bash
   # Check if keys are in git history
   git log --all --full-history -- '.env'
   ```

2. **Never hardcode keys in PHP files**
   ```php
   // WRONG
   $vapidPrivateKey = 'BKxxxx...';
   
   // RIGHT
   $vapidPrivateKey = getenv('VAPID_PRIVATE_KEY');
   ```

3. **Never expose private key in JavaScript**
   ```javascript
   // WRONG
   const privateKey = 'BKxxxx...';
   
   // RIGHT
   // Only public key is used in browser
   ```

4. **Never share private key in logs/errors**
   ```php
   // WRONG
   error_log("VAPID Error: $vapidPrivateKey");
   
   // RIGHT
   error_log("VAPID Error: Key configuration issue");
   ```

---

## Generating New VAPID Keys

### Step 1: Generate Keys

Run the provided script:
```bash
cd /wamp64/www/main
php generate_vapid.php
```

Output:
```
=== New VAPID Keys ===
VAPID_PUBLIC_KEY=BKxxxxxxxx...
VAPID_PRIVATE_KEY=BKxxxxxxxx...

Add these to your .env file
```

### Step 2: Update `.env` File

Edit `.env` in project root:
```
VAPID_PUBLIC_KEY=BKxxxxxxxx...
VAPID_PRIVATE_KEY=BKxxxxxxxx...
VAPID_SUBJECT=mailto:admin@jajr.com
```

### Step 3: Restart Web Server

```bash
# WAMP: Restart Apache via system tray icon
# OR
cd C:\wamp64\bin\apache\apache2.4.xx\bin
httpd -k restart
```

### Step 4: Test Notifications

1. Visit `test_push_notification.php`
2. Click "Enable Notifications"
3. Send a test notification

---

## Security Audit Checklist

Run this checklist periodically:

### File Permissions
- [ ] `.env` file has restricted permissions (600 or 640)
- [ ] `.env.new` file removed or renamed
- [ ] No backup files (`.env.bak`, `.env~`) exist

### Code Review
- [ ] Search codebase for hardcoded VAPID keys
  ```bash
  grep -r "VAPID_PRIVATE_KEY" --include="*.php" --include="*.js" .
  ```
- [ ] Verify no keys in comments
- [ ] Check no keys in error messages

### Access Control
- [ ] `.htaccess` blocks `.env` access
- [ ] Web server returns 403 for `/.env`
- [ ] Test: `curl -I http://yourdomain/.env` should fail

### Git Safety
- [ ] `.env` in `.gitignore`
- [ ] Check git history for accidental commits
- [ ] Verify no keys in past commits

---

## Potential Vulnerabilities to Check

### 1. SQL Database Exposure

**Risk**: If database is compromised, check `push_subscriptions` table

**Mitigation**: 
- This table only contains subscription endpoints (not VAPID keys)
- VAPID keys remain in `.env` only

### 2. Log File Leakage

**Risk**: Error logs might contain key information

**Check**:
```bash
# Search logs for VAPID keys
grep -r "VAPID" /wamp64/www/main/*.log
grep -r "BK[A-Za-z0-9_-]" /wamp64/www/main/*.log
```

### 3. Backup File Exposure

**Risk**: Backup files might be accessible

**Check**:
```bash
# Find backup files
find /wamp64/www/main -name "*.bak" -o -name "*.backup" -o -name "*~"
```

### 4. PHP Info Exposure

**Risk**: `phpinfo()` might reveal environment variables

**Check**:
```bash
# Search for phpinfo
grep -r "phpinfo" --include="*.php" .
```

---

## Incident Response: What If Keys Are Exposed?

### Immediate Actions (Within 1 Hour)

1. **Revoke/Regenerate Keys**
   ```bash
   php generate_vapid.php
   ```

2. **Update `.env` with new keys**

3. **Restart Apache**

4. **Clear all push subscriptions** (users must re-subscribe)
   ```sql
   TRUNCATE TABLE push_subscriptions;
   ```

### Follow-up Actions

1. **Investigate how keys were exposed**
   - Check web server access logs
   - Review git history
   - Check for compromised accounts

2. **Notify users**
   - All users must re-enable notifications
   - Previous subscriptions are invalid

3. **Review security measures**
   - Verify `.htaccess` rules
   - Check file permissions
   - Audit access logs

---

## Files Handling VAPID Keys

| File | Purpose | Key Type Accessed |
|------|---------|-------------------|
| `.env` | Configuration | Both (storage) |
| `functions.php` | Send notifications | Both (read from env) |
| `get_vapid_key.php` | API endpoint | Public only |
| `generate_vapid.php` | Key generation | Creates new keys |

---

## Testing Security

### Test 1: Verify `.env` Access is Blocked

```bash
curl -I http://yourdomain.com/.env
# Expected: HTTP/1.1 403 Forbidden
```

### Test 2: Verify Public Key API

```bash
curl http://yourdomain.com/employee/api/get_vapid_key.php
# Expected: 403 (no session)

# With session cookie:
curl -b "PHPSESSID=xxx" http://yourdomain.com/employee/api/get_vapid_key.php
# Expected: JSON with publicKey only
```

### Test 3: Check Git Safety

```bash
git status
# .env should not appear as "new file" or "modified"
```

---

## Current System Status

**Date**: March 2026
**Status**: ✅ SECURE

### Findings:
1. ✅ `.env` file protected by `.htaccess`
2. ✅ `.env` listed in `.gitignore`
3. ✅ Only public key exposed via API
4. ✅ Private key never transmitted to browser
5. ✅ Keys loaded from environment variables

### Recommendations:
1. Consider renaming `.env.new` to avoid confusion
2. Add `VAPID_SUBJECT` to `.env` if not present
3. Schedule key rotation every 6 months
4. Document key generation process for team

---

## Contact & Support

If you suspect VAPID key compromise:
1. Immediately regenerate keys using `generate_vapid.php`
2. Update `.env` file with new keys
3. Restart web server
4. Clear `push_subscriptions` table
5. Notify all users to re-enable notifications
