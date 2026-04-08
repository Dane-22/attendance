# Geofence Error Analysis: "Outside Geofence" Issues

## Overview

This document explains the geofencing system, why "Outside Geofence" errors occur, and how to resolve them. The specific error message format is:

> "Outside Geofence, You are {X}m outside the branch area ({Y}m radius)"

**Example**: "You are 404m outside the branch area (1000m radius)"

This means the worker is **1404 meters** away from the branch (1000m radius + 404m outside).

---

## How Geofence Validation Works

### 1. Distance Calculation (Haversine Formula)

The system uses the Haversine formula to calculate the great-circle distance between two GPS coordinates:

```php
// validate_geofence.php:16-29
function haversineMeters($lat1, $lon1, $lat2, $lon2) {
    $R = 6371000.0; // Earth radius in meters
    $phi1 = deg2rad($lat1);
    $phi2 = deg2rad($lat2);
    $dPhi = deg2rad($lat2 - $lat1);
    $dLambda = deg2rad($lon2 - $lon1);

    $a = sin($dPhi / 2) * sin($dPhi / 2) +
         cos($phi1) * cos($phi2) *
         sin($dLambda / 2) * sin($dLambda / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $R * $c; // Distance in meters
}
```

### 2. Validation Logic

```php
// validate_geofence.php:277-281
$distance = haversineMeters($lat, $lng, $branchLat, $branchLng);
$isValid = ($distance <= $radius);

$remaining = $isValid ? ($radius - $distance) : 0;
$outsideBy = $isValid ? 0 : ($distance - $radius);
```

### 3. Error Message Construction

```php
// validate_geofence.php:292
$overrideReason = sprintf('Outside geofence by %d meters', $outsideBy);
```

This creates the message: "Outside geofence by 404 meters"

---

## Understanding the Error Message

### Breaking Down: "You are 404m outside the branch area (1000m radius)"

| Component | Value | Meaning |
|-----------|-------|---------|
| **Radius** | 1000m | Maximum allowed distance from branch center |
| **Outside By** | 404m | How far beyond the radius the worker is |
| **Actual Distance** | 1404m | Total distance from branch (1000 + 404) |
| **Result** | BLOCKED | Cannot time in without override |

### Visual Representation

```
Branch Location (Center)
    📍
     \
      \\  1000m radius [ALLOWED ZONE]
       \\
        📍 Worker Location (1404m away)
        
    ❌ BLOCKED: 404m outside allowed area
```

---

## Common Causes of "Outside Geofence" Errors

### 1. **GPS Inaccuracy (Most Common)**

**Problem**: GPS accuracy can vary significantly based on environment:
- **Indoors**: 50-200m accuracy (or worse)
- **Urban canyons**: 20-100m accuracy
- **Open sky**: 5-15m accuracy
- **Poor weather**: Degraded accuracy

**Impact**: A worker physically 800m from the branch may report a GPS position 1200m away due to 400m accuracy error.

**Detection**: Check the `accuracy_meters` value in the violation log:
```sql
SELECT employee_id, distance_from_branch, geofence_radius, accuracy_meters,
       (distance_from_branch - geofence_radius) as outside_by
FROM geofence_violations 
WHERE violation_date = CURDATE()
ORDER BY created_at DESC;
```

### 2. **Incorrect Branch Coordinates**

**Problem**: Branch GPS coordinates in the database may be wrong:
- Address geocoding errors
- Coordinates set at street level instead of building center
- Branch moved but coordinates not updated
- Typo in latitude/longitude values

**Detection**: Compare branch coordinates with actual location:
```sql
SELECT branch_name, lat, `long`, geofence_radius_meters
FROM branches 
WHERE branch_name = 'Problem Branch Name';
```

**Verify on Google Maps**:
1. Go to https://www.google.com/maps
2. Enter coordinates: `lat,long` (e.g., `14.6091,121.0223`)
3. Check if marker is at correct building

### 3. **Worker Actually Outside Radius**

**Problem**: Worker is legitimately beyond the 1000m radius:
- Taking lunch off-site
- Working from nearby location
- Stopped for errands before clocking in
- Living nearby and trying to clock in from home

**Solution**: This is intended behavior - worker must be within radius.

### 4. **Database Radius Value Issues**

**Problem**: The `geofence_radius_meters` value may be:
- `NULL` (falls back to 1000m)
- Set to smaller value than expected (e.g., old 500m default)
- Different per branch causing confusion

**Check Current Radius Settings**:
```sql
SELECT branch_name, geofence_radius_meters,
       CASE 
           WHEN geofence_radius_meters IS NULL THEN 'Using default (1000m)'
           WHEN geofence_radius_meters < 1000 THEN 'Below standard (1000m)'
           ELSE 'OK'
       END as status
FROM branches
ORDER BY geofence_radius_meters DESC;
```

**Fix Radius if Needed**:
```sql
-- Update specific branch to 1500m
UPDATE branches 
SET geofence_radius_meters = 1500 
WHERE branch_name = 'Problem Branch';

-- Or update all branches to ensure 1000m minimum
UPDATE branches 
SET geofence_radius_meters = 1000 
WHERE geofence_radius_meters IS NULL 
   OR geofence_radius_meters < 1000;
```

### 5. **Mobile Device Issues**

**Problem**: Device-specific GPS problems:
- **Mock location apps**: Fake GPS position
- **VPN/Location spoofing**: Reported location differs from actual
- **Old device**: Poor GPS hardware
- **Low battery**: GPS accuracy reduced to save power
- **Airplane mode**: Using cached/stale location

**Detection**: Check for patterns:
```sql
-- Find employees with frequent violations
SELECT employee_id, COUNT(*) as violation_count,
       AVG(accuracy_meters) as avg_accuracy,
       MAX(distance_from_branch) as max_distance
FROM geofence_violations 
WHERE violation_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAYS)
GROUP BY employee_id
HAVING violation_count > 5
ORDER BY violation_count DESC;
```

### 6. **Timestamp/Location Spoofing**

**Problem**: Workers attempting to bypass geofence using:
- Fake GPS apps
- VPN location spoofing
- Sending old cached location

**System Protection**: `validate_geofence.php:37-47` checks timestamp freshness:
```php
function validateLocationTimestamp($gpsTimestamp, $maxDiffSeconds = 300) {
    $serverTime = time();
    $timeDiff = abs($serverTime - $gpsTimestamp);
    
    return [
        'is_valid' => $timeDiff <= $maxDiffSeconds,
        'time_diff_seconds' => $timeDiff,
        // ...
    ];
}
```

---

## System Flow: Time In with Geofence Validation

```
┌─────────────────────────────────────────────────────────────────┐
│  EMPLOYEE INITIATES TIME IN                                      │
└──────────────────────────────────┬──────────────────────────────┘
                                   │
                                   ▼
┌─────────────────────────────────────────────────────────────────┐
│  1. GPS ACQUISITION                                              │
│     - Browser requests location                                   │
│     - Device returns: lat, lng, accuracy, timestamp              │
└──────────────────────────────────┬──────────────────────────────┘
                                   │
                                   ▼
┌─────────────────────────────────────────────────────────────────┐
│  2. CLIENT-SIDE VALIDATION (geolocation.js)                    │
│     - Calculate distance to branch                               │
│     - Show visual feedback                                       │
└──────────────────────────────────┬──────────────────────────────┘
                                   │
                                   ▼
┌─────────────────────────────────────────────────────────────────┐
│  3. SERVER VALIDATION (validate_geofence.php)                  │
│     - Haversine distance calculation                           │
│     - Compare to radius (1000m)                                │
│     - Check role (soft vs hard enforcement)                      │
│     - Log violation if outside                                   │
└──────────────────────────────────┬──────────────────────────────┘
                                   │
              ┌────────────────────┴────────────────────┐
              │                                        │
              ▼                                        ▼
┌─────────────────────────┐              ┌──────────────────────────┐
│  WITHIN RADIUS          │              │  OUTSIDE RADIUS          │
│  distance <= 1000m      │              │  distance > 1000m        │
│                         │              │                          │
│  ✓ ALLOW TIME IN        │              │  ✗ BLOCK/WARN              │
└─────────────────────────┘              └──────────────────────────┘
                                                    │
                          ┌─────────────────────────┼─────────────────────────┐
                          │                         │                         │
                          ▼                         ▼                         ▼
                ┌─────────────────┐    ┌─────────────────┐    ┌──────────────────┐
                │ WORKER ROLE     │    │ MANAGER ROLE    │    │ SPOOFING DETECTED │
                │ (soft enforce)  │    │ (hard enforce)  │    │                   │
                │                 │    │                 │    │                   │
                │ Show warning    │    │ Require         │    │ Security block  │
                │ Allow override  │    │ manager         │    │ Admin alert      │
                └─────────────────┘    │ override        │    └──────────────────┘
                                       └─────────────────┘
```

---

## Immediate Troubleshooting Steps

### For the Current Error (404m outside)

1. **Check Actual Distance**:
   ```sql
   SELECT 
       b.branch_name,
       b.lat as branch_lat,
       b.`long` as branch_lng,
       gv.latitude as worker_lat,
       gv.longitude as worker_lng,
       gv.distance_from_branch,
       gv.geofence_radius as radius,
       gv.accuracy_meters,
       (gv.distance_from_branch - gv.geofence_radius) as outside_by
   FROM geofence_violations gv
   JOIN branches b ON gv.branch_id = b.id
   WHERE gv.violation_date = CURDATE()
     AND (gv.distance_from_branch - gv.geofence_radius) = 404
   ORDER BY gv.created_at DESC
   LIMIT 1;
   ```

2. **Verify Branch Coordinates**:
   - Open Google Maps
   - Enter branch coordinates: `lat,long`
   - Check if marker is at the correct building
   - If wrong: Update branch coordinates in admin panel

3. **Check GPS Accuracy**:
   - If `accuracy_meters` > 500: Poor signal, try outdoors
   - If `accuracy_meters` < 50: Good signal, worker is actually outside

4. **Quick Fix Options**:
   - **Option A**: Worker moves closer to branch (within 1000m)
   - **Option B**: Increase branch radius temporarily
   - **Option C**: Admin uses override (if available)

---

## Recommendations

### Short-Term (Immediate)

1. **Verify Branch GPS Coordinates**
   ```sql
   -- List all branches with coordinates
   SELECT id, branch_name, lat, `long`, geofence_radius_meters,
          CONCAT('https://www.google.com/maps/search/?api=1&query=', lat, ',', `long`) as map_link
   FROM branches;
   ```
   Click each map_link to verify location accuracy.

2. **Check Recent Violations Pattern**:
   ```sql
   -- See if this is a widespread issue
   SELECT 
       DATE(violation_date) as date,
       COUNT(*) as total_violations,
       AVG(distance_from_branch - geofence_radius) as avg_outside_by,
       AVG(accuracy_meters) as avg_accuracy
   FROM geofence_violations
   WHERE violation_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAYS)
   GROUP BY DATE(violation_date)
   ORDER BY date DESC;
   ```

3. **Increase Radius for Problem Branches**:
   ```sql
   -- Increase to 1500m for branches with frequent violations
   UPDATE branches 
   SET geofence_radius_meters = 1500,
       updated_at = NOW()
   WHERE id IN (
       SELECT branch_id 
       FROM geofence_violations 
       WHERE violation_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAYS)
       GROUP BY branch_id 
       HAVING COUNT(*) > 10
   );
   ```

### Medium-Term (System Improvements)

1. **Add Accuracy-Based Tolerance**:
   Consider allowing a tolerance buffer based on GPS accuracy:
   ```php
   // Pseudocode for accuracy-adjusted validation
   $effectiveRadius = $radius + min($accuracy, 200); // Add up to 200m tolerance
   $isValid = $distance <= $effectiveRadius;
   ```

2. **Implement Progressive Warnings**:
   - 0-800m: No warning
   - 800-1000m: "Getting close to boundary"
   - 1000-1200m: "You are near the edge, please move closer"
   - >1200m: "Outside allowed area"

3. **Add Retry with Better Accuracy**:
   If accuracy > 100m, prompt worker to:
   - Step outside building
   - Wait for GPS to stabilize
   - Retry with better accuracy

4. **Manager Override UI**:
   For hard enforcement roles (managers), show:
   - Map with worker location and branch boundary
   - Distance information
   - "Approve Override" button with reason field

### Long-Term (Strategic)

1. **Multi-Factor Location Validation**:
   - Combine GPS with WiFi fingerprinting
   - Use cell tower triangulation as fallback
   - Implement beacon-based indoor positioning

2. **Dynamic Radius Based on Location Quality**:
   ```
   High accuracy (<20m): Use standard radius
   Medium accuracy (20-100m): Radius + 50m buffer
   Low accuracy (>100m): Radius + 100m buffer + accuracy warning
   ```

3. **Historical Pattern Analysis**:
   - Learn typical GPS variance per branch
   - Adjust effective radius based on historical accuracy patterns
   - Flag suspicious location patterns (possible spoofing)

4. **Indoor Location Solutions**:
   - Install Bluetooth beacons at branch locations
   - Use WiFi access point mapping
   - Implement NFC/QR code backup for indoor clock-in

---

## Configuration Reference

### Current Default Values

| Setting | Default Value | Location |
|---------|----------------|----------|
| Geofence Radius | 1000m | `validate_geofence.php:221` |
| Max GPS Accuracy | 100m (warning) | `geolocation.js:22` |
| Timestamp Tolerance | 300 seconds | `validate_geofence.php:37` |
| High Accuracy Roles | Admin, Manager, Supervisor | `validate_geofence.php:255` |
| Soft Enforcement | Workers get warnings | `validate_geofence.php:269` |
| Hard Enforcement | Managers get blocked | `validate_geofence.php:270` |

### Changing Default Radius

**System-wide default** (affects branches with NULL radius):
```php
// validate_geofence.php:221
$radius = (int)($branch['geofence_radius_meters'] ?? 1000);
// Change 1000 to desired default (e.g., 1500)
```

**Per-branch radius** (via admin panel or SQL):
```sql
UPDATE branches SET geofence_radius_meters = 1500 WHERE id = {branch_id};
```

---

## Summary

| Issue | Cause | Quick Fix | Prevention |
|-------|-------|-----------|------------|
| 404m outside (1000m radius) | Worker 1404m from branch | Move closer or increase radius | Verify branch coordinates |
| Poor GPS accuracy | Indoor/urban environment | Step outside, retry | Add accuracy tolerance |
| Wrong branch location | Incorrect coordinates in DB | Update branch GPS | Regular coordinate audits |
| Frequent violations | Small radius for area | Increase to 1500m | Analyze violation patterns |
| Spoofing attempts | Fake GPS apps | Block with timestamp check | Monitor violation logs |

---

*Generated for Attendance System Project - Geofence Error Analysis*
