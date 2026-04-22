# QR Code Scanning Workflow Documentation

## Overview
The QR code scanning system enables employees to quickly clock in/out by scanning a QR code containing their employee information. The system supports automatic time-in/out and location-based verification via GPS.

---

## QR Code Scanning Flow

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────────┐
│  Employee scans │────▶│  select_employee │────▶│  Branch Selection   │
│   QR Code       │     │     .php         │     │     (if enabled)    │
└─────────────────┘     └──────────────────┘     └─────────────────────┘
                                                               │
                                                               ▼
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────────┐
│  Display Result │◀────│  Clock In/Out    │◀────│  GPS Location       │
│                 │     │  (attendance)    │     │  Verification       │
└─────────────────┘     └──────────────────┘     └─────────────────────┘
```

---

## QR Code URL Format

The QR code contains a URL with the following structure:

```
/employee/select_employee.php?auto_timein=1&select_branch=1&emp_id={ID}&emp_code={CODE}
```

### Parameters

| Parameter | Value | Description |
|-----------|-------|-------------|
| `auto_timein` | `1` | Triggers automatic clock-in processing |
| `select_branch` | `1` | Enables branch selection + location verification mode |
| `emp_id` | `{ID}` | Employee database ID |
| `emp_code` | `{CODE}` | Employee code (e.g., EMP-001) |

---

## Scanning Process

### 1. Initial Detection (`select_employee.php:8-10`)

```php
$isQRScan = isset($_GET['auto_timein']) && isset($_GET['emp_id']);
$isBranchSelectMode = isset($_GET['select_branch']) && $_GET['select_branch'] == '1';
```

### 2. Session Handling (`select_employee.php:13-33`)

For QR scans without existing login:
- Creates temporary authenticated session
- Marks session with `qr_temp_session = true`
- Sets `position = 'QR Scan'`

### 3. Branch Selection Mode (`select_employee.php:44-110`)

When `select_branch=1`:
- Shows branch selector modal instead of auto clock-in
- Requires employee to select their project/branch
- Proceeds to location verification

### 4. Direct Auto Clock-In Mode

When `auto_timein=1` without branch selection:
- Fetches employee and their assigned branch
- Calls `performClockIn()` directly
- If already clocked in → auto-triggers `performClockOut()`

---

## Location Verification Flow

### Step 1: Branch Selection
- Employee selects a branch/project from the grid
- Branch data includes: `id`, `name`, `lat`, `lng`, `radius`

### Step 2: GPS Acquisition (`select_employee.php:848-873`)

```javascript
navigator.geolocation.getCurrentPosition(
  successCallback,
  errorCallback,
  {
    enableHighAccuracy: true,
    timeout: 10000,
    maximumAge: 60000
  }
);
```

### Step 3: Distance Calculation (`select_employee.php:1008-1022`)

Uses **Haversine Formula** to calculate distance between GPS coordinates:

```javascript
function calculateDistance(lat1, lng1, lat2, lng2) {
  const R = 6371000; // Earth's radius in meters
  const phi1 = lat1 * Math.PI / 180;
  const phi2 = lat2 * Math.PI / 180;
  const deltaPhi = (lat2 - lat1) * Math.PI / 180;
  const deltaLambda = (lng2 - lng1) * Math.PI / 180;
  
  const a = Math.sin(deltaPhi/2) * Math.sin(deltaPhi/2) +
            Math.cos(phi1) * Math.cos(phi2) *
            Math.sin(deltaLambda/2) * Math.sin(deltaLambda/2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
  
  return R * c; // Distance in meters
}
```

### Step 4: Geofence Validation (`select_employee.php:979-1005`)

- If branch has no coordinates → allow anyway
- If distance ≤ radius → location valid
- If distance > radius → show error with distance info

### Step 5: Clock In/Out with Location (`select_employee.php:112-192`)

AJAX POST to `select_employee.php` with:
- `action: 'qr_clock_with_branch'`
- `employee_id`, `employee_code`, `branch_name`, `branch_id`
- `latitude`, `longitude`, `accuracy`
- `location_verified: 1`

---

## Location Data Storage

### location_logs Table

| Field | Type | Description |
|-------|------|-------------|
| `employee_id` | int | Employee ID |
| `attendance_id` | int | Linked attendance record |
| `action_type` | varchar | 'qr_scan' or 'clock_out' |
| `latitude` | decimal | GPS latitude |
| `longitude` | decimal | GPS longitude |
| `accuracy_meters` | float | GPS accuracy |
| `branch_id` | int | Selected branch |
| `is_validated` | tinyint | Location verified within geofence |
| `created_at` | datetime | Timestamp |

### attendance Table (Updated)

| Field | Type | Description |
|-------|------|-------------|
| `clock_in_lat` | decimal | Clock-in latitude |
| `clock_in_lng` | decimal | Clock-in longitude |
| `location_accuracy` | float | GPS accuracy in meters |
| `location_verified` | tinyint | 1 if within geofence |

---

## Error Handling

### GPS Errors

| Error Code | Message |
|------------|---------|
| `PERMISSION_DENIED` | "Location access denied. Please enable location permissions." |
| `POSITION_UNAVAILABLE` | "Location information unavailable." |
| `TIMEOUT` | "Location request timed out." |

### Validation Errors

- **Outside geofence**: "You are not in the location yet. Distance: Xm (allowed: Ym)"
- **Employee not found**: "Employee not found"
- **Already clocked in**: Auto-triggers clock-out instead

---

## User Interface Flow

1. **Scan QR Code** → Opens `select_employee.php`
2. **Branch Selector Modal** appears (gold border, dark background)
3. **Select Branch** → triggers GPS verification
4. **Location Status Display**:
   - Checking: "Getting your location..." (spinning icon)
   - Valid: "Location verified! You are within the project area." (green)
   - Invalid: Shows distance error (red)
5. **Confirm Button** → enabled only when location valid
6. **Clock In/Out** → AJAX call records attendance
7. **Success Banner** → displays result and auto-selects branch

---

## Related Files

| File | Purpose |
|------|---------|
| `employee/select_employee.php` | Main QR scan landing page |
| `employee/employees.php` | QR code generation |
| `employee/function/clock_functions.php` | `performClockIn()`, `performClockOut()` |
| `employee/function/attendance.php` | Attendance helper functions |

---

## Security Considerations

1. **Temporary Sessions**: QR scans create temporary sessions marked with `qr_temp_session`
2. **Location Verification**: Prevents clock-in from remote locations
3. **Geofence Radius**: Configurable per branch (default 200m)
4. **Active Employee Check**: Only active employees can clock in via QR
