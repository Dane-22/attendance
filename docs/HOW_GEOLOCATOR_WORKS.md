# How the Geolocator Works

## Overview

The JAJR Attendance System's geolocation module validates employee clock-in/out locations against defined branch geofences. It uses a multi-layered approach combining browser GPS, server-side validation, and role-based enforcement.

---

## Core Components

### 1. Database Schema

**branches table:**
```sql
- lat (VARCHAR) - Branch latitude
- `long` (VARCHAR) - Branch longitude  
- geofence_radius_meters (INT) - Geofence radius (default: 200m)
```

**attendance table:**
```sql
- clock_in_lat / clock_in_lng - Clock-in coordinates
- clock_out_lat / clock_out_lng - Clock-out coordinates
- location_accuracy - GPS accuracy in meters
- flagged_accuracy - Boolean if accuracy > 100m
- gps_timestamp - Device GPS timestamp
- is_suspicious_location - Boolean if spoofing detected
```

**geofence_violations table:**
```sql
- employee_id, branch_id, violation_date
- latitude, longitude, accuracy_meters
- distance_from_branch - How far outside geofence
- status (active/resolved)
```

---

## How Geofencing Works

### Haversine Formula

The system calculates distance between two GPS coordinates using the Haversine formula:

```php
function haversineMeters($lat1, $lon1, $lat2, $lon2) {
    $R = 6371000; // Earth's radius in meters
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

**Accuracy:** ±0.5% for distances under 100km

---

## Validation Flow

### Step 1: Browser GPS Capture (Frontend)

When employee scans QR code:
```javascript
navigator.geolocation.getCurrentPosition(
    (position) => {
        const data = {
            lat: position.coords.latitude,
            lng: position.coords.longitude,
            accuracy: position.coords.accuracy,
            timestamp: Date.now() // Current time for spoofing check
        };
        // Send to validation API
    },
    (error) => {
        // Handle GPS errors
    },
    { enableHighAccuracy: true, timeout: 10000 }
);
```

### Step 2: Server-Side Validation

**validate_geofence.php** performs:

1. **Distance Calculation**
   - Gets branch coordinates from database
   - Calculates distance using Haversine formula
   - Compares against geofence_radius_meters

2. **Accuracy Check**
   - If accuracy > 100m → `flagged_accuracy = true`
   - Attendance recorded but flagged

3. **Timestamp Validation (Anti-Spoofing)**
   - GPS timestamp must be within ±5 minutes of server time
   - Prevents using old/saved location data
   - If outside window → `is_suspicious_location = true`

4. **Role-Based Enforcement**
   ```php
   $hardRoles = ['Worker', 'Staff']; // Must be inside geofence
   $softRoles = ['Admin', 'Super Admin', 'Manager', 'Supervisor']; // Can override
   ```

### Step 3: Response Actions

| Distance | Employee Role | Action |
|----------|---------------|--------|
| Inside radius | Any | `success` - Proceed with clock-in |
| Outside radius | Worker/Staff | `block` - Clock-in denied |
| Outside radius | Manager/Admin | `allow_override` - Manager can approve |
| Any distance | Any | `accuracy_block` - If accuracy > 500m |
| Old timestamp | Any | `security_block` - Spoofing detected |

---

## Enforcement Modes

### Hard Enforcement (Workers)
- **Rule:** Must be within geofence radius
- **Outside geofence:** Clock-in blocked with message
- **Violation logged:** To `geofence_violations` table
- **Repeated violations:** Admin notification after 3+ violations

### Soft Enforcement (Managers/Admins)
- **Rule:** Can clock in from anywhere
- **Outside geofence:** Override dialog appears
- **Requires reason:** Manager must provide justification
- **Audit trail:** Logged to `manager_overrides` table

---

## Anti-Spoofing Mechanisms

### 1. Timestamp Validation
```php
$gpsTime = $gps_timestamp;      // From device
$serverTime = time();          // Server time
$diff = abs($serverTime - $gpsTime);

if ($diff > 300) { // 5 minutes
    return 'security_block'; // Possible spoofing
}
```

### 2. Device Fingerprinting
- User agent string recorded
- IP address logged
- Pattern analysis for anomalies

### 3. Accuracy Thresholds
- **< 10m:** Excellent accuracy
- **10-100m:** Good accuracy (green marker on map)
- **> 100m:** Flagged (yellow marker on map)
- **> 500m:** Blocked (unreliable location)

---

## Manager Override Flow

1. Manager attempts clock-in outside geofence
2. System detects violation but allows override (role check)
3. Override dialog appears: "You are X meters outside [Branch]"
4. Manager enters reason: "Client meeting at site"
5. System logs:
   - Override to `manager_overrides` table
   - Original distance and radius
   - Manager ID and timestamp
   - Override reason
6. Clock-in proceeds with `override_used = true`

---

## Admin Map Dashboard

The **map_dashboard.php** provides real-time visualization:

### Features:
- **Branch markers:** Blue circles at branch locations
- **Geofence circles:** Transparent blue circles showing radius
- **Employee markers:** 
  - Green = Good accuracy (< 100m)
  - Yellow = Poor accuracy (> 100m)
- **Violation markers:** Red pulsing dots
- **Live statistics:** Total branches, active employees, violations

### Data Sources:
- `branches` - Branch locations and radii
- `attendance` - Today's clock-in/out with coordinates
- `geofence_violations` - Active violations

---

## API Endpoints

### 1. validate_geofence.php
```http
POST /validate_geofence.php
Content-Type: application/json

{
    "employee_id": 123,
    "branch_id": 21,
    "lat": 16.5969775,
    "lng": 120.3077657,
    "accuracy": 15,
    "gps_timestamp": 1774938074
}

Response:
{
    "success": true/false,
    "action": "success|block|allow_override|accuracy_block|security_block",
    "distance": 45.2,  // meters from branch
    "radius": 250,     // geofence radius
    "accuracy": 15,    // GPS accuracy
    "enforcement": "hard|soft",
    "can_override": true/false
}
```

### 2. save_attendance_location.php
```http
POST /save_attendance_location.php
Content-Type: application/json

{
    "employee_id": 123,
    "branch_id": 21,
    "lat": 16.5969775,
    "lng": 120.3077657,
    "accuracy": 15,
    "gps_timestamp": 1774938074,
    "override_reason": "Client meeting"  // Optional
}
```

### 3. geofence_notification_handler.php
Admin notifications for repeated violations.

---

## Testing the Geolocator

### QA Test Suite (test/)

**test_geofence_logic.php** simulates scenarios:
1. Inside geofence (Worker) → `success`
2. Outside geofence (Worker) → `block`
3. Outside geofence (Manager) → `allow_override`
4. Spoofed timestamp → `security_block`
5. Low accuracy (>500m) → `accuracy_block`

**test/geo_mock.js** - Browser GPS mocking:
```javascript
GeoMock.enable();
GeoMock.setLocation(16.5969775, 120.3077657, 10);
GeoMock.testOutsideGeofenceWorker();
```

---

## Database Verification

**test/check_logs.sql** queries:
```sql
-- Check violations were logged
SELECT * FROM geofence_violations 
WHERE violation_date = CURDATE();

-- Check manager overrides
SELECT * FROM manager_overrides 
WHERE created_at >= CURDATE();

-- Check suspicious locations
SELECT * FROM attendance 
WHERE is_suspicious_location = 1 
AND attendance_date = CURDATE();
```

---

## Security Considerations

1. **Client-side validation is bypassable** - Always validate server-side
2. **GPS can be spoofed** - Use timestamp + device fingerprinting
3. **Override audit trail** - Every override logged with reason
4. **Rate limiting** - Prevent brute force location attempts
5. **HTTPS required** - Prevent location interception

---

## Troubleshooting

### Common Issues:

| Issue | Cause | Solution |
|-------|-------|----------|
| "GPS accuracy too low" | Indoors/poor signal | Move outside or use WiFi positioning |
| "Outside geofence" | Wrong branch selected | Verify employee's assigned branch |
| "Security block" | Device time wrong | Sync device time automatically |
| Map shows 0 branches | Migration not run | Run `geolocation_phase2_migration.sql` |
| Map tiles not loading | Invalid API key | Using CartoDB tiles (no key needed) |

---

## Configuration

### Adjusting Geofence Radius
```sql
UPDATE branches 
SET geofence_radius_meters = 300 
WHERE id = 21;
```

### Changing Accuracy Threshold
Edit `validate_geofence.php`:
```php
const ACCURACY_THRESHOLD = 100; // meters
const MAX_ACCEPTABLE_ACCURACY = 500; // meters
```

### Updating Enforcement Roles
Edit `$hardRoles` array in `validate_geofence.php`:
```php
$hardRoles = ['Worker', 'Staff', 'Laborer']; // Add roles
```

---

*Document Version: 1.0*
*Last Updated: March 31, 2026*
*Applies to: Geolocation Phase 2*
