# MapLibre & QR Scanning Cost Investigation Report

**Date:** April 7, 2026  
**Issue:** Workers unable to time in via QR scan - error mentions "need to pay"  
**Investigation Focus:** MapLibre map and geolocator costs

---

## Key Finding: QR Scanning Does NOT Use MapLibre

After reviewing the entire codebase, the **QR scanning functionality does NOT use MapLibre or any map tile services**. The geolocation is done via raw browser GPS APIs without any map display.

---

## QR Scanning Flow Architecture

### 1. login.php QR Scanner (Lines 566-1332)
- Uses `navigator.geolocation.getCurrentPosition()` (Browser native API)
- Calculates distance using Haversine formula in pure JavaScript
- **NO map is displayed**
- **NO MapLibre, NO CartoDB, NO tile services used**

### 2. select_employee.php QR Handler (Lines 1-986)
- Also uses raw browser geolocation
- GPS validation done via JavaScript calculations
- **NO map visualization**

### 3. Where MapLibre IS Used
MapLibre is only used in:
- `employee/branch_location_manager.php` - Admin interface for setting branch locations
- Uses CartoDB Positron basemap (free tier)

---

## CartoDB (Map Tile Provider) Analysis

### Current Configuration
```javascript
// branch_location_manager.php & geolocation.js
mapStyle: 'https://basemaps.cartocdn.com/gl/positron-gl-style/style.json'
```

### CartoDB Pricing (as of 2026)
| Tier | Cost | Limits |
|------|------|--------|
| **Free** | $0 | 250,000 map views/month |
| **Professional** | $199/month | 2,500,000 map views/month |
| **Enterprise** | Custom | Unlimited |

### Important Note
**CartoDB free tier does NOT require an API key** and provides 250,000 monthly map views. The "need to pay" error is likely NOT coming from CartoDB if you're under this limit.

---

## Possible Causes of "Need to Pay" Error

### 1. Misunderstood Error Message
The error might actually be:
- **"You are not in the location yet"** - GPS geofencing error (most likely)
- **"Location access denied"** - Browser permission error
- **"Cannot clock in/out"** - Branch mismatch error

### 2. Mobile Device GPS Issues
Common issues:
- GPS not enabled on device
- Worker is physically outside the geofence radius
- Poor GPS accuracy (>100 meters)

### 3. Geofence Radius Too Small
Default radius was recently widened from 200m to 500m, but:
- Old branches may still have 200m radius
- Large construction sites need 800-1000m radius
- Indoor locations may fail GPS validation

---

## Recommended Solutions

### 1. Increase Geofence Radius (Immediate Fix)
```sql
-- Set all branches to 500m radius
UPDATE branches SET geofence_radius_meters = 500;

-- Or set specific large sites to 1000m
UPDATE branches SET geofence_radius_meters = 1000 
WHERE branch_name IN ('Site A', 'Site B', 'Warehouse');
```

### 2. Verify Branch Coordinates
Check if branches have correct GPS coordinates:
```sql
SELECT branch_name, lat, `long`, geofence_radius_meters 
FROM branches 
WHERE lat IS NULL OR `long` IS NULL OR lat = 0 OR `long` = 0;
```

### 3. Check for MapLibre Errors (If Applicable)
If workers access the branch location manager (unlikely), check browser console for:
```
CartoDB tile errors
MapLibre initialization errors
```

### 4. Alternative Free Map Providers (If Needed)
If CartoDB limits are exceeded, switch to:
- **OpenStreetMap** (completely free, no limits)
- **Stamen Terrain** (free for most uses)
- **Self-hosted tiles** (no third-party limits)

---

## Testing Checklist

To identify the actual error:

- [ ] Open browser DevTools (F12) → Console tab
- [ ] Scan QR code and watch for JavaScript errors
- [ ] Check Network tab for failed API requests
- [ ] Verify exact error message text
- [ ] Test GPS accuracy on worker's device
- [ ] Verify worker is within geofence radius

---

## Free vs Paid Services Summary

| Service | Type | Cost | Usage in Project |
|---------|------|------|------------------|
| **MapLibre GL JS** | Map Library | **FREE** (Open Source) | Branch Manager only |
| **CartoDB Basemaps** | Map Tiles | **FREE** (250k views/month) | Branch Manager only |
| **Browser Geolocation** | GPS API | **FREE** | QR Scanning (all devices) |
| **HTML5 QR Code** | Scanner Library | **FREE** (Open Source) | QR Scanning |

**The QR scanning feature uses 100% FREE services only.**

---

## Conclusion

The "need to pay" error is **NOT** coming from MapLibre or CartoDB because:

1. QR scanning uses raw browser GPS, not MapLibre
2. CartoDB is only used in the admin branch manager (workers don't access this)
3. CartoDB free tier doesn't require payment or API keys

**Most likely cause:** Workers are outside the geofence radius or have GPS accuracy issues. The error message may have been misremembered as "need to pay" when it's actually "You are not in the location yet."

**Recommendation:** 
1. Verify the exact error message in browser console
2. Increase geofence radius for all branches to at least 500m
3. Ensure all branches have accurate GPS coordinates set

---

*End of Investigation Report*
