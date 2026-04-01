# QR Branch Selection Implementation - Modified Files Summary

This document lists all files that were modified to implement the QR Branch Selection with Location Verification feature.

## Modified Files

### 1. `employee/select_employee.php`
**Purpose:** Main entry point for QR scanning with branch selection and location verification

**Changes:**
- Added `$isBranchSelectMode` variable to detect `select_branch=1` URL parameter (line 10)
- Modified QR scan logic to show branch selection modal instead of auto clock-in when in branch selection mode (lines 44-51)
- Added AJAX handler for `qr_clock_with_branch` action to process clock-in/out with selected branch and GPS data (lines 112-192)
- Added branch selection modal HTML with location status display (lines 407-455)
- Added CSS styles for branch selector modal, branch cards, location status, and action buttons (lines 227-391)
- Added JavaScript for GPS geolocation, haversine distance calculation, and clock-in/out API calls (lines 770-983)

### 2. `employee/employees.php`
**Purpose:** Employee management page where QR codes are generated

**Changes:**
- Updated QR URL generation to include `select_branch=1` parameter (line 431)
- New QR URL format: `{baseUrl}?auto_timein=1&select_branch=1&emp_id={id}&emp_code={code}`

## Feature Flow

```
Employee Scans QR Code
    ↓
select_employee.php detects select_branch=1
    ↓
Shows Branch Selection Modal (branch grid)
    ↓
Employee Clicks Branch Card
    ↓
GPS Location Request (browser geolocation API)
    ↓
Calculate Distance (haversine formula)
    ↓
Check Against Geofence Radius
    ↓
[Valid Location] → Enable Confirm Button → Clock In/Out
[Invalid Location] → Show Alert: "You are not in the location yet"
```

## Database Tables Used

- `attendance` - Stores clock-in/out records with location data
- `location_logs` - Logs GPS coordinates for audit trail
- `branches` - Stores branch coordinates and geofence radius
- `employees` - Employee data verification

## URL Parameters

- `auto_timein=1` - Triggers QR scan mode
- `select_branch=1` - Enables branch selection (new)
- `emp_id={id}` - Employee ID
- `emp_code={code}` - Employee code for verification

## Technical Notes

- Location verification is done client-side using JavaScript haversine formula
- GPS accuracy threshold can be configured
- Default geofence radius: 200 meters
- Supports retry for failed GPS attempts
- Location data saved to database for audit purposes
