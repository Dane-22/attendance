# Geolocation Phase 2 - QA Automation Test Suite

## 📋 Executive Summary

This QA test suite provides automated testing capabilities for the Geolocation Phase 2 implementation without requiring physical location changes. The suite includes:

- **Web Mock Location Injector** (`test/geo_mock.js`) - Simulates GPS coordinates in browser
- **Backend Unit Testing** (`test/test_geofence_logic.php`) - Tests PHP geofence validation logic
- **Database Verification** (`test/check_logs.sql`) - Validates test data persistence

---

## 🎯 Test Coverage Matrix

| Test Case | Expected Result | Test File | Verification |
|-----------|----------------|-----------|--------------|
| Inside Geofence (Worker) | `success` | `test_geofence_logic.php` | No violation recorded |
| Outside Geofence (Worker) | `block` | `test_geofence_logic.php` | Violation logged |
| Outside Geofence (Manager) | `allow_override` | `test_geofence_logic.php` | Override option available |
| Spoofed Timestamp | `security_block` | `test_geofence_logic.php` | Security alert logged |
| Low Accuracy (>500m) | `accuracy_block` | `test_geofence_logic.php` | Accuracy flag set |

---

## 🚀 Quick Start Guide

### Prerequisites

- WAMP/LAMP server running
- Database migrated with Phase 2 schema
- Test employees configured (IDs: 1=Worker, 2=Manager)

### Run All Tests in 3 Steps

```bash
# Step 1: Run Backend PHP Tests
php test/test_geofence_logic.php

# Step 2: Verify Database Records
# (Run test/check_logs.sql in PHPMyAdmin)

# Step 3: Test Web Mock (Browser)
# Open login.php with geo_mock.js included
```

---

## 📁 Test Suite Components

### 1. Mock Location Injector (`test/geo_mock.js`)

**Purpose:** Override browser's native geolocation API for controlled testing

#### Features
- Manual GPS coordinate injection
- Accuracy simulation (poor, normal, critical)
- Timestamp spoofing for security testing
- Preset test scenarios
- Browser console integration

#### Usage

**Basic Setup:**
```html
<!-- Include in login.php or test page -->
<script src="test/geo_mock.js"></script>
```

**Manual Testing:**
```javascript
// Enable mocking
GeoMock.enable();

// Set specific location
GeoMock.setLocation(14.6091, 121.0223, 10);

// Test poor accuracy
GeoMock.setAccuracy(600);

// Test spoofed timestamp
GeoMock.setSpoofedTimestamp();
```

**Quick Test Functions:**
```javascript
// Test Case 1: Inside Geofence
testInsideGeofence();

// Test Case 2: Outside Geofence (Worker - Should Block)
testOutsideGeofenceWorker();

// Test Case 3: Outside Geofence (Manager - Override)
testOutsideGeofenceManager();

// Test Case 4: Spoofed Timestamp
testSpoofedTimestamp();

// Test Case 5: Low Accuracy
testLowAccuracy();
```

**Auto-Enable via URL:**
```
http://localhost/main/login.php?mock_geo=true
```

---

### 2. Backend Unit Testing (`test/test_geofence_logic.php`)

**Purpose:** Automated PHP testing of geofence validation logic

#### How to Run

**Option A: Browser (Recommended for Visual Output)**
```
http://localhost/main/test/test_geofence_logic.php
```

**Option B: Command Line**
```bash
cd c:\wamp64\www\main
php test/test_geofence_logic.php
```

**Option C: With JSON Output (for API testing)**
```
http://localhost/main/test/test_geofence_logic.php?format=json
```

#### Test Cases Executed

| # | Test Name | Employee Role | Location | Accuracy | Timestamp | Expected |
|---|-----------|---------------|----------|----------|-------------|----------|
| 1 | Inside Geofence (Worker) | Worker | Inside (50m) | 10m | Current | `success` |
| 2 | Outside Geofence (Worker) | Worker | Outside (220m) | 15m | Current | `block` |
| 3 | Outside Geofence (Manager) | Manager | Outside (220m) | 15m | Current | `allow_override` |
| 4 | Spoofed Timestamp | Worker | Inside (50m) | 10m | 2 hours ago | `security_block` |
| 5 | Low Accuracy (>500m) | Worker | Inside (50m) | 600m | Current | `accuracy_block` |

#### Expected Output

```
======================================================================
TEST CASE: CASE 1: Inside Geofence (Worker)
======================================================================
✅ PASSED - Action: success

Test Parameters:
{
    "branch_id": 1,
    "employee_id": 1,
    "lat": 14.60955,
    "lng": 121.0223,
    "accuracy": 10,
    "gps_timestamp": 1711872000
}

Response:
{
    "success": true,
    "action": "success",
    "is_valid": true,
    "distance_meters": 49.95,
    ...
}
```

---

### 3. Database Verification (`test/check_logs.sql`)

**Purpose:** Validate that test actions were properly recorded in database

#### How to Run

1. **Open PHPMyAdmin**
   - Navigate to: `http://localhost/phpmyadmin`
   - Select the attendance database

2. **Execute the SQL Script**
   - Go to the "SQL" tab
   - Copy entire contents of `test/check_logs.sql`
   - Click "Go"

3. **Review Results**
   - Look for ✅ PASS indicators
   - Verify record counts match test executions
   - Check violation details

#### Verification Sections

| Section | Checks | Expected Result |
|---------|--------|-----------------|
| Geofence Violations | Records in `geofence_violations` | 4+ records (Cases 2-5) |
| Activity Logs | QA test entries in `activity_logs` | 5+ records |
| Manager Overrides | Override entries | 0+ (if override tests run) |
| Location Logs | GPS tracking records | 5+ records |
| Master Verification | Combined pass/fail status | All checks PASS |

---

## 🔧 Configuration

### Test Employee Setup

Update these IDs in `test/test_geofence_logic.php`:

```php
$testEmployees = [
    'worker' => [
        'id' => 1,           // <-- Change to actual Worker ID
        'role' => 'Worker',
        'description' => 'Regular Employee'
    ],
    'manager' => [
        'id' => 2,           // <-- Change to actual Manager ID
        'role' => 'Manager',
        'description' => 'Manager'
    ]
];
```

### Branch Configuration

```php
$testConfig = [
    'branch_id' => 1,           // Test branch ID
    'branch_lat' => 14.6091,    // Branch latitude
    'branch_lng' => 121.0223,   // Branch longitude
    'geofence_radius' => 100,   // Geofence radius (meters)
];
```

---

## 📊 Test Results Interpretation

### Success Criteria

| Component | Success Rate | Action |
|-----------|--------------|--------|
| Backend Tests | 100% (5/5) | ✅ Production Ready |
| Database Logs | 100% (5/5) | ✅ Audit Trail Working |
| Web Mock | Functional | ✅ Frontend Testable |

### Common Issues & Solutions

#### Issue: "Employee not found"
**Solution:** Update `$testEmployees` array with correct IDs from your database

#### Issue: "Branch not found"
**Solution:** Update `$testConfig['branch_id']` with valid branch ID

#### Issue: "Database connection failed"
**Solution:** Verify WAMP is running and `conn/db_connection.php` is accessible

#### Issue: "Mock not working in browser"
**Solution:** Check browser console for errors; ensure script loads before geofence validation

---

## 🔒 Security Testing

### Spoofing Detection Test

**Purpose:** Verify timestamp validation prevents location spoofing

**How to Test:**
```javascript
// In browser console with geo_mock.js loaded
GeoMock.enable()
    .setLocation(14.6091, 121.0223, 10)
    .setSpoofedTimestamp();  // 2 hours ago
```

**Expected Result:**
- Action: `security_block`
- Message: "Possible location spoofing detected"
- No override option (security blocks cannot be bypassed)

---

## 📈 Performance Testing

### API Response Time Benchmarks

| Operation | Target | Acceptable | Notes |
|-----------|--------|------------|-------|
| Geofence Validation | <200ms | <500ms | Haversine calculation |
| Distance Calculation | <10ms | <50ms | Math operations |
| Database Insert | <100ms | <300ms | Location logging |

**Monitor in test output:**
```
Response Time: XXms
```

---

## 🧪 Extended Test Scenarios

### Boundary Testing

```php
// Exactly at geofence edge (100m)
$edgeCase = [
    'lat' => $branchLat + 0.0009,
    'lng' => $branchLng,
    'accuracy' => 1
];

// Just inside (99m)
$justInside = [
    'lat' => $branchLat + 0.00089,
    'lng' => $branchLng,
    'accuracy' => 1
];

// Just outside (101m)
$justOutside = [
    'lat' => $branchLat + 0.00091,
    'lng' => $branchLng,
    'accuracy' => 1
];
```

### Accuracy Threshold Testing

```php
// Exactly at 100m threshold
$threshold100 = ['accuracy' => 100];  // Should pass

// Exactly at 500m critical threshold  
$threshold500 = ['accuracy' => 500];  // Should pass (at limit)

// Just over 500m
$over500 = ['accuracy' => 501];       // Should block
```

---

## 🔄 Regression Testing

### After Code Changes

Run this checklist after any modifications:

- [ ] Execute `test_geofence_logic.php` - All 5 tests pass
- [ ] Run `check_logs.sql` - All verifications show ✅
- [ ] Test mock in browser - GeoMock functions work
- [ ] Verify no production data affected
- [ ] Check for new PHP warnings/notices
- [ ] Validate JSON output format unchanged

---

## 📱 Integration with CI/CD

### Automated Testing Script

```bash
#!/bin/bash
# ci_test.sh - Run in GitHub Actions/Jenkins

echo "Starting Geofence QA Tests..."

# Run PHP tests
php test/test_geofence_logic.php > test_results.json

# Check for failures
if grep -q '"passed": 5' test_results.json; then
    echo "✅ All tests passed"
    exit 0
else
    echo "❌ Tests failed - check test_results.json"
    exit 1
fi
```

---

## 🎯 Production Deployment Checklist

Before deploying Phase 2 to production:

- [ ] All 5 test cases passing
- [ ] Database verification shows records
- [ ] Mock injector removed from production code
- [ ] Test files excluded from deployment
- [ ] Error logging configured
- [ ] Admin notifications tested
- [ ] Override workflow verified
- [ ] Rollback plan prepared

---

## 🐛 Debugging Guide

### Enable Debug Mode

**In `test_geofence_logic.php`:**
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

**In `validate_geofence.php`:**
```php
// Add to top of file
$debug = true;
if ($debug) {
    error_log("Geofence Debug Input: " . print_r($_POST, true));
}
```

### Check Database Records

```sql
-- Quick check for recent violations
SELECT id, employee_id, distance_from_branch, created_at 
FROM geofence_violations 
WHERE DATE(created_at) = CURDATE() 
ORDER BY id DESC 
LIMIT 10;
```

### Browser Console Debugging

```javascript
// Check if mock is active
console.log(GeoMock.getConfig());

// Monitor geolocation calls
navigator.geolocation.getCurrentPosition = function(success, error, options) {
    console.log('Geolocation requested', options);
    // ... rest of implementation
};
```

---

## 📚 Additional Resources

- [Main Implementation Doc](../GEOFENCING_PHASE2_IMPLEMENTATION.md)
- [Database Migration](../dbschema/geolocation_phase2_migration.sql)
- [API Reference](../validate_geofence.php)

---

## ✅ QA Sign-Off Template

```
GEOFENCE PHASE 2 QA VALIDATION

Date: ___________
Tester: ___________
Version: ___________

TEST RESULTS:
□ Backend Tests: ___/5 passed
□ Database Verification: All checks PASS
□ Web Mock: Functional
□ Security Tests: Spoofing detected correctly
□ Override Tests: Manager bypass working

SIGN-OFF:
[ ] QA Approved for Production
[ ] Issues Found - See Notes:

Notes:
_________________________________
_________________________________
```

---

**Last Updated:** March 31, 2026  
**Version:** 1.0.0  
**Status:** Ready for Testing
