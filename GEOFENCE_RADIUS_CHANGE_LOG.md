# Geofence Radius Widening - Change Log

**Date:** April 7, 2026  
**Task:** Widen geolocation radius to allow more flexibility for employee clock-in/out

---

## Summary of Changes

The geofence radius has been widened from **200 meters (default) / 500 meters (max)** to **500 meters (default) / 1000 meters (max)** across all relevant files in the attendance management system.

---

## Files Modified

### 1. Backend API Files

| File | Change | Line(s) |
|------|--------|---------|
| `validate_geofence.php` | Default radius: 200m → 500m | 221, 228 |
| `check_branch_location.php` | Default radius: 200m → 500m | 34 |
| `get_branch_location_api.php` | Default radius: 200m → 500m | 50 |
| `save_attendance_location.php` | Default radius: 200m → 500m | 90, 113 |
| `employee/api/validate_geofence.php` | Default radius: 200m → 500m | 78 |

### 2. Frontend/JavaScript Files

| File | Change | Line(s) |
|------|--------|---------|
| `assets/js/geolocation.js` | `defaultRadius`: 200 → 500 | 18 |
| `employee/branch_location_manager.php` | Multiple updates (see below) | 114, 129, 185, 188-191, 229, 309, 336-337, 485 |

#### Branch Location Manager Changes:
- **Default radius value**: 200m → 500m (displayed in branch cards and form)
- **Slider max value**: 500m → 1000m
- **Slider default value**: 200 → 500
- **Batch import default**: 200 → 500

---

## Impact Assessment

### What This Affects:
1. **New branches** will now default to a 500m geofence radius instead of 200m
2. **Existing branches** with no radius set will now use 500m as fallback
3. **Admin interface** slider now allows up to 1000m radius configuration
4. **All geofence validation** uses the new 500m default when no radius is specified

### What This Does NOT Affect:
- **Existing branches with explicit radius values** - their settings remain unchanged
- **Database schema** - no migration needed
- **Geofence logic** - only the default value changed, not the validation algorithm

---

## Recommended Radius Settings by Branch Type

| Branch Type | Recommended Radius | Notes |
|-------------|---------------------|-------|
| Office | 300-500m | Standard office building coverage |
| Warehouse | 500-800m | Larger facility areas |
| Field Site | 500-1000m | Construction sites, remote locations |
| Retail/Store | 300-500m | Storefront with parking |

---

## How to Customize Individual Branch Radius

1. Go to **Dashboard → Branch Location Manager**
2. Select the branch from the list
3. Adjust the **Geofence Radius** slider (now supports 50m - 1000m)
4. Click **Save Location**

---

## Rollback Instructions

To revert to the original 200m default, change these values back to `200` in all files listed above:

```php
// PHP files - change from:
$radius = (int)($branch['geofence_radius_meters'] ?? 500);
// back to:
$radius = (int)($branch['geofence_radius_meters'] ?? 200);
```

```javascript
// JavaScript files - change from:
defaultRadius: 500,
// back to:
defaultRadius: 200,
```

```php
// Branch location manager - change slider from:
max="1000" value="500"
// back to:
max="500" value="200"
```

---

## Testing Checklist

- [ ] Clock in within 500m of branch location - should be valid
- [ ] Clock in between 500m-1000m (if radius set to 1000m) - should be valid
- [ ] Check that existing branches with custom radii still work correctly
- [ ] Verify admin slider shows 50-1000m range
- [ ] Verify new branches default to 500m radius

---

## Related Documentation

- `GEOLOCATION_DOCUMENTATION.md` - Full geolocation system documentation
- `GEOFENCING_PHASE2_IMPLEMENTATION.md` - Geofencing implementation details
- `employee/branch_location_manager.php` - Admin interface for managing branch locations

---

*End of Change Log*
