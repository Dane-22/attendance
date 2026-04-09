# QR Code Scan Geolocation Issue

## Issue Summary

**Problem:** Engineers encounter an error when scanning QR codes that states they are in **"BDCA - Admin"** but their chosen branch in the branch selection is **"Main Office"** - **even though the engineer is physically located at Main Office**.

**Impact:** Engineers are unable to clock in via QR code scan because the system incorrectly matches their GPS location to the wrong branch (BDCA - Admin instead of Main Office).

---

## How the Geolocation Validation Works

### 1. QR Code Scan Flow

```
Engineer scans QR code
    ↓
System parses employee ID from QR
    ↓
Branch selection modal appears (if multiple branches)
    ↓
Engineer selects a branch (e.g., "Main Office")
    ↓
System validates GPS location against selected branch's geofence
    ↓
If valid → Allow clock-in
If invalid → Show error message
```

### 2. Key Files Involved

| File | Purpose |
|------|---------|
| `login.php` | QR scanning interface and geofence validation (lines 620-960) |
| `get_branch_api.php` | Fetches all branches or specific employee's assigned branch |
| `validate_geofence.php` | Validates GPS coordinates against branch geofence |
| `employee/api/validate_geofence.php` | Alternative API endpoint for geofence validation |
| `employee/select_employee.php` | Branch selection UI with location verification |

### 3. Geofence Validation Logic

The system uses the **Haversine formula** to calculate distance between GPS coordinates:

```javascript
// From login.php lines 704-718
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
    
    return R * c;
}
```

---

## Root Cause Analysis

### The Issue

When an engineer scans a QR code at **Main Office**, the system incorrectly identifies their location as **"BDCA - Admin"**. This happens because:

1. The system gets the engineer's current GPS coordinates (at Main Office)
2. **BUG:** The geofence validation logic incorrectly matches the GPS coordinates to BDCA - Admin's geofence instead of Main Office's
3. System displays error: "You are in BDCA - Admin but your chosen branch is Main Office"

### Why This Happens

**Possible Causes:**

1. **Incorrect Branch Coordinates in Database**
   - Main Office coordinates may be wrong or NULL
   - BDCA - Admin coordinates may be incorrectly set or overlapping

2. **Geofence Radius Too Large for BDCA - Admin**
   - If BDCA - Admin has a large geofence radius, it may encompass Main Office's physical location
   - Default radius is 200m but could be set higher

3. **Main Office Missing Coordinates**
   - If Main Office has no coordinates set (`lat` or `long` is NULL/0), validation may default to another branch

4. **Distance Calculation Bug**
   - The Haversine formula calculation may have incorrect coordinate mapping
   - Branch ID mismatch between selection and validation

### Data to Check

```sql
-- Check coordinates for both branches
SELECT id, branch_name, lat, `long`, geofence_radius_meters, is_active
FROM branches
WHERE branch_name IN ('BDCA - Admin', 'Main Office');
```

---

## Current Error Messages

### Location Validation Error
```javascript
// From login.php line 878-880
const errorMsg = `You are not in the location yet. Distance: ${Math.round(distance)}m (allowed: ${radius}m)`;
showLocationError(errorMsg);
alert('You are not in the location yet. ' + errorMsg);
```

### Console Debug Output
```javascript
// From login.php lines 868-873
console.log('QR Location Debug:');
console.log('Your GPS:', position.latitude, position.longitude);
console.log('Branch GPS:', parseFloat(branchData.lat), parseFloat(branchData.lng));
console.log('Distance:', Math.round(distance), 'meters');
console.log('Radius allowed:', radius, 'meters');
console.log('Is valid:', distance <= radius);
```

---

## Possible Solutions

### Option 1: Auto-Detect Nearest Branch
Instead of validating against the selected branch, the system could:
1. Calculate distance to ALL branches
2. Find the nearest branch
3. If nearest branch matches selected branch → Allow
4. If different → Suggest the actual detected branch

### Option 2: Allow Override with Reason
For soft enforcement roles (non-managers), allow clock-in with:
- Warning notification
- Required reason for being outside geofence
- Flagged for admin review

### Option 3: Multiple Branch Assignment
Allow engineers to be assigned to multiple branches so they can select any of their authorized locations.

### Option 4: Temporary Branch Transfer
Add functionality for temporary branch assignment when engineers are deployed to different sites.

---

## Database Schema Reference

### Branches Table
```sql
SELECT id, branch_name, branch_address, lat, `long`, geofence_radius_meters, is_active
FROM branches
WHERE is_active = 1
```

### Employee Branch Assignment
```sql
SELECT b.id, b.branch_name, b.lat, b.`long`, b.geofence_radius_meters
FROM employees e
LEFT JOIN branches b ON b.id = e.branch_id
WHERE e.id = ?
```

### Default Geofence Radius
- Default: `200` meters (if not set in database)
- Configurable per branch via `geofence_radius_meters` column

---

## Enforcement Levels

### Hard Enforcement (Block)
Applies to: Admin, Super Admin, Manager, Supervisor
- Action: `block`
- Requires override reason
- Cannot proceed without manager approval

### Soft Enforcement (Warn)
Applies to: All other roles (including Engineers)
- Action: `warn` or `allow`
- Can proceed with warning
- Violation is logged for audit

---

## Debugging Steps

1. **Check browser console** for "QR Location Debug" output
2. **Verify branch coordinates** in database:
   ```sql
   SELECT branch_name, lat, `long`, geofence_radius_meters
   FROM branches
   WHERE branch_name IN ('BDCA - Admin', 'Main Office');
   ```
3. **Test GPS coordinates** manually using the validate_geofence.php endpoint:
   ```
   GET /validate_geofence.php?branch_id=X&lat=XX.XXXX&lng=XX.XXXX
   ```
4. **Check employee's assigned branch**:
   ```sql
   SELECT e.id, e.first_name, e.last_name, b.branch_name
   FROM employees e
   LEFT JOIN branches b ON b.id = e.branch_id
   WHERE e.id = ?;
   ```

---

## Recommendations

### Immediate Actions Required

1. **Verify Branch Coordinates**
   - Access `check_branch_location.php` to view all branch coordinates
   - Ensure Main Office has correct lat/long values set
   - Ensure BDCA - Admin coordinates don't overlap with Main Office

2. **Use Debug Tool**
   - Access `debug_qr_scan.php` from the engineer's device at Main Office
   - Click "Get My Location" to verify actual GPS coordinates
   - Test both branches to see which one the system matches

3. **Check Database Records**
   ```sql
   -- Verify Main Office has coordinates
   SELECT * FROM branches WHERE branch_name = 'Main Office';
   
   -- Check if coordinates are NULL or 0
   SELECT id, branch_name, lat, `long`, 
          CASE WHEN lat IS NULL OR lat = 0 THEN 'MISSING' ELSE 'OK' END as lat_status,
          CASE WHEN `long` IS NULL OR `long` = 0 THEN 'MISSING' ELSE 'OK' END as lng_status
   FROM branches WHERE is_active = 1;
   ```

### Potential Fixes

1. **If Main Office coordinates are missing:**
   - Set them via `employee/branch_location_manager.php`
   - Use Google Maps to get precise coordinates

2. **If BDCA - Admin geofence is too large:**
   - Reduce `geofence_radius_meters` in database
   - Set to 100-200 meters for precise location

3. **If coordinates overlap:**
   - Recalibrate both branches with precise GPS coordinates
   - Ensure geofence radii don't overlap

---

## Related Issues

- Geofence violations are logged in `geofence_violations` table
- Admin notifications triggered after 3 violations per day
- Location accuracy warnings shown if GPS accuracy > 100 meters
