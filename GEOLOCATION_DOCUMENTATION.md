# Geolocation Feature Documentation

## Overview

This document describes the implementation of the Geolocation Feature for the Attendance Management System using **MapLibre GL JS**. The feature enables location-based attendance tracking with geofencing capabilities.

**Version:** 1.0  
**Created:** March 28, 2026  
**Map Library:** MapLibre GL JS v3.6.0  
**Map Style:** CartoDB Positron

---

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Database Schema](#database-schema)
3. [Core Components](#core-components)
4. [Frontend Implementation](#frontend-implementation)
5. [Backend API](#backend-api)
6. [Admin Interface](#admin-interface)
7. [Configuration](#configuration)
8. [Usage Guide](#usage-guide)
9. [Troubleshooting](#troubleshooting)

---

## Architecture Overview

### System Flow

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│   Employee      │────▶│  Geolocation JS  │────▶│  Browser GPS    │
│   Dashboard     │     │  (MapLibre)      │     │  API            │
└─────────────────┘     └──────────────────┘     └─────────────────┘
         │                       │
         │                       ▼
         │              ┌──────────────────┐
         │              │  Geofence        │
         │              │  Validation      │
         │              └──────────────────┘
         ▼                       │
┌─────────────────┐              ▼
│  Attendance API │◄────┌──────────────────┐
│  (time_in/out)  │     │  Save Location   │
└─────────────────┘     └──────────────────┘
         │
         ▼
┌─────────────────┐
│   Database      │
│ (MySQL/MariaDB) │
└─────────────────┘
```

### Key Features

- **GPS Location Capture:** Records employee GPS coordinates during clock-in/out
- **Geofence Validation:** Validates if employee is within branch radius
- **Hybrid Enforcement:** Soft warning for regular employees, hard block for admins/managers
- **Audit Trail:** All location data logged for 90 days
- **Admin Map Interface:** Visual branch location management with MapLibre

---

## Database Schema

### Modified Tables

#### 1. Branches Table (Updated)

```sql
ALTER TABLE `branches` 
ADD COLUMN `geofence_radius_meters` INT DEFAULT 200 AFTER `long`,
ADD COLUMN `location_verified` TINYINT(1) DEFAULT 0 AFTER `geofence_radius_meters`,
ADD INDEX `idx_branch_location` (`lat`, `long`);
```

**Columns:**
- `lat` (VARCHAR(20)) - Existing latitude column
- `long` (VARCHAR(20)) - Existing longitude column  
- `geofence_radius_meters` (INT) - Geofence radius in meters (default: 200m)
- `location_verified` (TINYINT) - Flag indicating verified location

#### 2. Attendance Table (Updated)

```sql
ALTER TABLE `attendance` 
ADD COLUMN `clock_in_lat` DECIMAL(10, 8) NULL AFTER `time_in`,
ADD COLUMN `clock_in_lng` DECIMAL(11, 8) NULL AFTER `clock_in_lat`,
ADD COLUMN `clock_out_lat` DECIMAL(10, 8) NULL AFTER `time_out`,
ADD COLUMN `clock_out_lng` DECIMAL(11, 8) NULL AFTER `clock_out_lat`,
ADD COLUMN `location_verified` TINYINT(1) DEFAULT 0 AFTER `clock_out_lng`,
ADD COLUMN `location_accuracy` FLOAT NULL AFTER `location_verified`,
ADD INDEX `idx_attendance_location` (`clock_in_lat`, `clock_in_lng`);
```

**Columns:**
- `clock_in_lat/lng` - GPS coordinates at clock-in
- `clock_out_lat/lng` - GPS coordinates at clock-out
- `location_verified` - Whether location passed geofence validation
- `location_accuracy` - GPS accuracy in meters

### New Tables

#### 3. Location Logs (Audit Trail)

```sql
CREATE TABLE `location_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `attendance_id` INT NULL,
  `action_type` ENUM('clock_in', 'clock_out', 'qr_scan', 'manual_override') NOT NULL,
  `latitude` DECIMAL(10, 8) NOT NULL,
  `longitude` DECIMAL(11, 8) NOT NULL,
  `accuracy_meters` FLOAT NULL,
  `branch_id` INT NULL,
  `distance_from_branch_meters` INT NULL,
  `device_info` VARCHAR(255) NULL,
  `ip_address` VARCHAR(45) NULL,
  `is_validated` TINYINT(1) DEFAULT 0,
  `validation_failure_reason` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_employee_date` (`employee_id`, `created_at`),
  INDEX `idx_location` (`latitude`, `longitude`),
  INDEX `idx_attendance_id` (`attendance_id`),
  INDEX `idx_branch_id` (`branch_id`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB COMMENT='Audit trail for geolocation data - 90 day retention';
```

#### 4. Employee Location Consent

```sql
CREATE TABLE `employee_location_consent` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL UNIQUE,
  `consent_given` TINYINT(1) DEFAULT 0,
  `consent_date` TIMESTAMP NULL,
  `consent_ip` VARCHAR(45) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_employee_id` (`employee_id`)
) ENGINE=InnoDB COMMENT='Track employee consent for location tracking';
```

---

## Core Components

### File Structure

```
main/
├── assets/
│   ├── js/
│   │   └── geolocation.js          # Core MapLibre module
│   └── css/
│       └── geolocation.css         # Map and UI styles
├── employee/
│   ├── api/
│   │   ├── save_attendance_location.php    # Save location data
│   │   ├── validate_geofence.php             # Validate geofence
│   │   └── update_branch_location.php        # Admin update endpoint
│   └── branch_location_manager.php # Admin interface
├── time_in_api.php                 # Modified for location
├── time_out_api.php                # Modified for location
├── qr_clock_api.php                # Modified for location
└── dbschema/
    └── geolocation_migration.sql   # Database migration
```

---

## Frontend Implementation

### MapLibre GL JS Integration

**Library CDN:**
```html
<link href="https://unpkg.com/maplibre-gl@3.6.0/dist/maplibre-gl.css" rel="stylesheet" />
<script src="https://unpkg.com/maplibre-gl@3.6.0/dist/maplibre-gl.js"></script>
```

### Core JavaScript Module (`assets/js/geolocation.js`)

The module provides a `GeoLocator` singleton with the following capabilities:

#### 1. Map Initialization

```javascript
GeoLocator.initMap('map-container', 16.6149, 120.3190, 14);
```

**Configuration:**
- **Style:** `https://basemaps.cartocdn.com/gl/positron-gl-style/style.json` (CartoDB Positron)
- **Tile Server:** CartoDB (free, no API key required)
- **Attribution:** CartoDB, OpenStreetMap contributors
- **Default Center:** La Union, Philippines (16.6149, 120.3190)

#### 2. GPS Position Tracking

```javascript
GeoLocator.getCurrentPosition(
    (position) => {
        console.log('Lat:', position.latitude);
        console.log('Lng:', position.longitude);
        console.log('Accuracy:', position.accuracy);
    },
    (error) => console.error(error),
    { highAccuracy: true, timeout: 10000 }
);
```

#### 3. Geofence Validation

```javascript
const result = GeoLocator.validateGeofence(
    employeeLat, 
    employeeLng, 
    branchLat, 
    branchLng, 
    radiusMeters
);
// Returns: { isValid, distance, remainingMeters, outsideByMeters }
```

**Haversine Formula:**
```javascript
function haversineDistance(lat1, lon1, lat2, lon2) {
    const R = 6371000; // Earth's radius in meters
    const φ1 = lat1 * Math.PI / 180;
    const φ2 = lat2 * Math.PI / 180;
    const Δφ = (lat2 - lat1) * Math.PI / 180;
    const Δλ = (lon2 - lon1) * Math.PI / 180;

    const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ/2) * Math.sin(Δλ/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

    return R * c; // Distance in meters
}
```

#### 4. Visual Geofence Circles

```javascript
GeoLocator.addGeofenceCircle(branchLat, branchLng, radiusMeters, {
    color: '#3B82F6',
    fillOpacity: 0.15,
    dashed: true
});
```

#### 5. Draggable Markers

```javascript
GeoLocator.addMarker(lat, lng, {
    color: '#10B981',
    draggable: true,
    popup: 'Drag to adjust location',
    onDragEnd: (newLat, newLng) => {
        // Update form fields
    }
});
```

### Styling (`assets/css/geolocation.css`)

Key CSS classes:

```css
/* Map Container */
.maplibregl-map { border-radius: 8px; }

/* Location Status Indicator */
.location-status { 
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
}
.location-status.inside { background: #D1FAE5; color: #065F46; }
.location-status.outside { background: #FEE2E2; color: #991B1B; }

/* Geofence Warning Modal */
.geofence-warning-modal { 
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

/* Accuracy Warning */
.accuracy-warning { 
    color: #92400E; 
    font-size: 12px; 
    margin-top: 4px; 
}
```

---

## Backend API

### API Endpoints

#### 1. Save Attendance Location
**File:** `employee/api/save_attendance_location.php`

**POST Parameters:**
```
attendance_id      (int)    - Attendance record ID
employee_id        (int)    - Employee ID
latitude           (float)  - GPS latitude
longitude          (float)  - GPS longitude
accuracy           (float)  - GPS accuracy in meters
action             (string) - 'clock_in', 'clock_out', 'qr_scan'
branch_id          (int)    - Branch ID (optional)
is_validated       (int)    - 1 if passed geofence, 0 if not
validation_failure_reason (string) - Reason if failed
```

**Response:**
```json
{
    "success": true,
    "message": "Location saved successfully",
    "data": {
        "attendance_id": 1234,
        "action": "clock_in",
        "latitude": 16.6149,
        "longitude": 120.3190,
        "accuracy": 15.5,
        "is_validated": 1
    }
}
```

#### 2. Validate Geofence
**File:** `employee/api/validate_geofence.php`

**GET/POST Parameters:**
```
branch_id   (int)   - Branch ID to validate against
lat         (float) - Employee latitude
lng         (float) - Employee longitude
employee_id (int)   - Optional, for enforcement policy
```

**Response:**
```json
{
    "success": true,
    "is_valid": false,
    "distance_meters": 350,
    "radius_meters": 200,
    "remaining_meters": 0,
    "outside_by_meters": 150,
    "enforcement": "soft",
    "action": "warn",
    "can_override": true,
    "branch": {
        "id": 1,
        "name": "Main Office",
        "latitude": 16.6149,
        "longitude": 120.3190
    }
}
```

**Enforcement Types:**
- `soft` - Regular employees (warning allowed with reason)
- `hard` - Admins/Managers/Supervisors (must be within range)

#### 3. Update Branch Location (Admin Only)
**File:** `employee/api/update_branch_location.php`

**POST Parameters:**
```
branch_id       (int)    - Branch ID
latitude        (float)  - New latitude
longitude       (float)  - New longitude
radius          (int)    - Geofence radius (50-1000m)
branch_address  (string) - Optional address update
```

**Permissions:** Admin, Super Admin only

---

## Admin Interface

### Branch Location Manager

**URL:** `employee/branch_location_manager.php`

**Features:**
1. **Branch List Sidebar**
   - Shows all branches with location status
   - Green badge: Location set ✓
   - Red badge: Missing location !

2. **Interactive Map**
   - Click branch to center map
   - Drag marker to adjust location
   - Visual geofence circle (dashed blue)
   - CartoDB Positron basemap

3. **Location Editor Form**
   - Latitude/Longitude inputs (auto-update on marker drag)
   - Radius slider (50-500m)
   - Address field
   - Save/Cancel buttons

4. **Batch Import**
   - CSV paste format: `branch_name,latitude,longitude,radius`
   - Example:
     ```
     Main Office,16.6149775,120.3077657,250
     Branch B,16.6139774,120.3186517,150
     ```

### Usage Instructions

1. **Set Branch Location:**
   - Click branch from list
   - Drag marker to exact location on map
   - Adjust radius slider (default 200m)
   - Click "Save Location"

2. **Check Status:**
   - Dashboard shows count of branches needing setup
   - Missing locations highlighted in red

---

## Configuration

### Map Style

**Current:** CartoDB Positron (light theme, clean for business use)

**Alternative Styles:**
```javascript
// Voyager (CartoDB)
'https://basemaps.cartocdn.com/gl/voyager-gl-style/style.json'

// Dark Matter (CartoDB)
'https://basemaps.cartocdn.com/gl/dark-matter-gl-style/style.json'

// OpenStreetMap
'https://tiles.openstreetmap.org/styles/osm-bright/style.json'
```

### Geofence Radius Defaults

| Branch Type | Default Radius |
|-------------|----------------|
| Office      | 200 meters     |
| Warehouse   | 300 meters     |
| Field Site  | 500 meters     |

### Enforcement Policy

**High-Compliance Roles (Hard Enforcement):**
- Admin
- Super Admin
- Manager
- Supervisor

**Regular Roles (Soft Enforcement):**
- Engineer
- Staff
- All other positions

---

## Usage Guide

### For Employees

1. **Clock In with Location:**
   - System automatically requests GPS permission
   - Shows location accuracy indicator
   - Warning if >100m accuracy
   - Clock-in proceeds with location data

2. **Out-of-Range Handling:**
   - Warning modal appears
   - Shows distance from branch
   - Can provide reason for override (soft enforcement)
   - Or must move within range (hard enforcement)

### For Admins

1. **Setting Up Branch Locations:**
   ```
   Dashboard → Branch Location Manager
   → Select branch
   → Drag marker to location
   → Set radius
   → Save
   ```

2. **Monitoring:**
   - View all branches on map
   - Identify missing locations (red badges)
   - Batch import via CSV

---

## Troubleshooting

### Common Issues

#### 1. GPS Not Available
**Symptom:** "Geolocation is not supported by this browser"
**Solution:** 
- Use HTTPS in production
- Check browser permissions
- Ensure GPS enabled on mobile

#### 2. Map Not Loading
**Symptom:** Blank map area
**Solution:**
- Check internet connection
- Verify MapLibre GL JS CDN loaded
- Check browser console for errors

#### 3. Database Errors
**Symptom:** "Column not found" errors
**Solution:**
- Run `geolocation_migration.sql`
- Check column names match schema
- Verify table engines (InnoDB)

### Browser Support

- **Chrome:** Full support (v90+)
- **Firefox:** Full support (v88+)
- **Safari:** Full support (v14+)
- **Edge:** Full support (v90+)
- **Mobile:** iOS Safari, Chrome Android

### HTTPS Requirements

**Development:** Can use HTTP on localhost
**Production:** HTTPS required for GPS access

---

## Migration Script

**File:** `dbschema/geolocation_migration.sql`

**To run:**
1. Open phpMyAdmin
2. Select `attendance_db` database
3. Import or copy-paste SQL
4. Execute

**Verification:**
```sql
-- Check new columns
SHOW COLUMNS FROM branches WHERE Field IN ('geofence_radius_meters', 'location_verified');
SHOW COLUMNS FROM attendance WHERE Field LIKE '%lat%' OR Field LIKE '%lng%';

-- Check new tables
SHOW TABLES LIKE 'location_logs';
SHOW TABLES LIKE 'employee_location_consent';
```

---

## Security Considerations

1. **Data Retention:** Location logs kept for 90 days then purged
2. **Privacy:** Employee consent tracked in `employee_location_consent`
3. **Access Control:** Branch updates restricted to Admin/Super Admin
4. **Audit Trail:** All location data logged with IP and device info
5. **HTTPS:** Required for GPS API access in production

---

## Future Enhancements

- [ ] Real-time location tracking (optional)
- [ ] Geofence entry/exit notifications
- [ ] Location-based reports and analytics
- [ ] Mobile app integration
- [ ] Offline caching for poor connectivity areas
- [ ] Indoor positioning (WiFi/Beacon support)

---

## Support

For technical issues or feature requests, contact the development team.

**Related Documentation:**
- MapLibre GL JS Docs: https://maplibre.org/maplibre-gl-js/docs/
- CartoDB Basemaps: https://carto.com/basemaps/
- Geolocation API: https://developer.mozilla.org/en-US/docs/Web/API/Geolocation_API

---

*End of Documentation*
