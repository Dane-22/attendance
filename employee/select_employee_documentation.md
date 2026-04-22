# select_employee.php - Detailed Documentation

## Overview

**File**: `employee/select_employee.php`  
**Purpose**: Main attendance management interface that handles:
- Project/branch selection for employee deployment
- QR code scanning for quick clock-in/out
- GPS-based location verification
- Real-time attendance tracking
- Employee status management (Present/Absent)

**Total Lines**: ~1,110 lines (PHP + HTML + CSS + JavaScript)

---

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                        select_employee.php                       │
├─────────────────────────────────────────────────────────────────┤
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────────┐  │
│  │   PHP Logic │  │   HTML UI   │  │    JavaScript          │  │
│  │   (1-194)   │  │  (195-768)  │  │    (769-1107)          │  │
│  ├─────────────┤  ├─────────────┤  ├─────────────────────────┤  │
│  │• Session    │  │• Sidebar    │  │• QR Branch Selection    │  │
│  │  Handling   │  │• Branch Grid│  │• GPS Verification      │  │
│  │• QR Scan    │  │• Employee   │  │• Haversine Distance     │  │
│  │  Processing │  │  List      │  │• AJAX Clock In/Out      │  │
│  │• AJAX       │  │• Modals     │  │• Auto Branch Select     │  │
│  │  Handlers   │  │• Stats      │  │                         │  │
│  └─────────────┘  └─────────────┘  └─────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 1. PHP Backend Logic (Lines 1-194)

### 1.1 Session & Authentication (Lines 1-33)

```php
// Timezone Configuration
date_default_timezone_set('Asia/Manila');

// QR Scan Detection
$isQRScan = isset($_GET['auto_timein']) && isset($_GET['emp_id']);
$isBranchSelectMode = isset($_GET['select_branch']) && $_GET['select_branch'] == '1';
```

**Session Handling Logic:**

| Scenario | Action |
|----------|--------|
| Regular user (logged in) | Continue normally |
| QR scan (not logged in) | Create temporary session with `qr_temp_session = true` |
| AJAX without session | Return JSON error |
| Regular access without session | Redirect to `login.php` |

**Temporary Session Variables (QR Scan):**
```php
$_SESSION['logged_in'] = true;           // Authenticated flag
$_SESSION['employee_id'] = intval($_GET['emp_id']);
$_SESSION['employee_code'] = $_GET['emp_code'];
$_SESSION['position'] = 'QR Scan';       // Special role marker
$_SESSION['qr_temp_session'] = true;    // Temporary session flag
```

### 1.2 QR Scan Auto Time-In/Out (Lines 39-110)

**Two Modes:**

#### Mode A: Branch Selection Mode (`select_branch=1`)
```php
if ($isBranchSelectMode) {
    $qrScanResult = [
        'success' => true,
        'select_branch' => true,
        'message' => 'Please select your branch/project'
    ];
}
```
- Shows branch selector modal
- Requires GPS verification before clock-in

#### Mode B: Direct Auto Clock-In
```php
// 1. Fetch employee details with branch
$empStmt = mysqli_prepare($db, "SELECT e.id, e.first_name, e.last_name, 
    e.employee_code, b.branch_name 
    FROM employees e 
    LEFT JOIN branches b ON b.id = e.branch_id 
    WHERE e.id = ? AND e.status = 'Active' LIMIT 1");

// 2. Attempt Clock-In
$clockInResult = performClockIn($db, $qrEmployeeId, $employee['employee_code'], $branchName);

// 3. If already clocked in → Auto Clock-Out
if (stripos($msg, 'already clocked in') !== false) {
    $clockOutResult = performClockOut($db, $qrEmployeeId, $employee['employee_code'], $branchName);
}
```

**QR Scan Result Structure:**
```php
$qrScanResult = [
    'success' => true|false,
    'message' => 'Employee time-in recorded at 08:30 AM',
    'time_in' => '08:30 AM',      // Optional
    'time_out' => '05:30 PM',     // Optional
    'select_branch' => true     // For branch selection mode
];
```

### 1.3 AJAX Handler: QR Clock with Branch (Lines 112-192)

**Endpoint**: `POST select_employee.php`  
**Action**: `qr_clock_with_branch`

**Request Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `action` | string | `qr_clock_with_branch` |
| `employee_id` | int | Employee database ID |
| `employee_code` | string | Employee code |
| `branch_name` | string | Selected branch name |
| `branch_id` | int | Selected branch ID |
| `latitude` | float | GPS latitude |
| `longitude` | float | GPS longitude |
| `accuracy` | float | GPS accuracy in meters |
| `location_verified` | int | 1 if within geofence |

**Process Flow:**

```
1. Validate parameters (employee_id, branch_name required)
2. Verify employee exists and is active
3. Check if already clocked in
   ├─ Yes → performClockOut()
   └─ No → performClockIn()
4. If success + has location data:
   ├─ Insert into location_logs table
   └─ Update attendance with location data
5. Return JSON response
```

**Database Operations:**

```php
// 1. Insert location log
INSERT INTO location_logs 
    (employee_id, attendance_id, action_type, latitude, longitude, 
     accuracy_meters, branch_id, is_validated, created_at) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())

// 2. Update attendance record
UPDATE attendance 
SET clock_in_lat = ?, clock_in_lng = ?, 
    location_accuracy = ?, location_verified = ?
WHERE id = ?
```

---

## 2. HTML Frontend Structure (Lines 195-768)

### 2.1 Page Layout

```html
<!doctype html>
<html>
<head>
    <!-- Meta, Fonts, Icons, CSS -->
    <link rel="stylesheet" href="css/select_employee.css">
    <link rel="stylesheet" href="css/light-theme.css">
</head>
<body>
    <div class="app-shell">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <main class="main-content">
            <!-- Components -->
        </main>
    </div>
</body>
</html>
```

### 2.2 UI Components

#### A. QR Result Banner (Lines 420-425)
```php
<?php if ($qrScanResult): ?>
<div class="qr-result-banner <?php echo $qrScanResult['success'] ? 'success' : 'error'; ?>">
    <i class="fas <?php echo $qrScanResult['success'] ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
    <?php echo htmlspecialchars($qrScanResult['message']); ?>
</div>
<?php endif; ?>
```

**Banner Styles:**
```css
.qr-result-banner.success {
    background: rgba(16, 185, 129, 0.2);
    border: 2px solid #10b981;
    color: #10b981;
}
.qr-result-banner.error {
    background: rgba(239, 68, 68, 0.2);
    border: 2px solid #ef4444;
    color: #ef4444;
}
```

#### B. QR Branch Selector Modal (Lines 428-475)

**Structure:**
```html
<div id="qrBranchSelector" class="qr-branch-selector-modal">
    <div class="qr-branch-selector-content">
        <h3><i class="fas fa-building"></i> Select Your Project/Branch</h3>
        
        <!-- Branch Grid -->
        <div class="qr-branch-grid" id="qrBranchGrid">
            <!-- Branch cards rendered from PHP $branches array -->
        </div>
        
        <!-- Location Status -->
        <div id="locationStatus" class="location-status">
            <div class="location-checking">...</div>
            <div class="location-valid">...</div>
            <div class="location-invalid">...</div>
        </div>
        
        <!-- Actions -->
        <div class="qr-branch-actions">
            <button id="confirmBranchBtn" class="btn-confirm" disabled>
                <i class="fas fa-check"></i> Confirm & Clock In
            </button>
            <button id="retryLocationBtn" class="btn-secondary" style="display: none;">
                <i class="fas fa-refresh"></i> Retry Location
            </button>
        </div>
    </div>
</div>
```

**Branch Card Data Attributes:**
```html
<div class="qr-branch-card" 
     data-branch="Main Office"
     data-branch-id="1"
     data-lat="14.5995"
     data-lng="120.9842"
     data-radius="200">
```

#### C. Project Selection Section (Lines 504-530)

**Components:**
- **Header**: "Select Deployment Project" + Add Project button (Super Admin only)
- **Search Bar**: Filter projects by name
- **Branch Grid**: Clickable project cards
- **Branch Pager**: Pagination controls

**Branch Card Structure:**
```html
<div class="branch-card" data-branch-id="1" data-branch="Main Office">
    <button class="btn-remove-branch" onclick="removeBranch(...)">
        <i class="fas fa-times"></i>
    </button>
    <div class="branch-name">Main Office</div>
    <div class="branch-desc">Deploy employees to this project for attendance</div>
</div>
```

#### D. Add Project Modal (Lines 533-594)

**Super Admin Only** - Fields:
- Project Name (required, 2-255 chars)
- Order Number (optional, 9 digits)
- Exact Address (textarea)

#### E. Branch Statistics (Lines 597-612)

**Three Stat Cards:**
```html
<div class="branch-stats">
    <div class="stat-card">
        <div class="stat-label">Total Workers</div>
        <div class="stat-value" id="statTotalWorkers">--</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Present</div>
        <div class="stat-value" id="statPresent">--</div>
        <div class="stat-list" id="statPresentList"></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Absent</div>
        <div class="stat-value" id="statAbsent">--</div>
        <div class="stat-list" id="statAbsentList"></div>
    </div>
</div>
```

#### F. Filter & Search (Lines 643-681)

**Status Filter Pills:**
- `available` (default) - Available employees
- `all` - Summary view
- `present` - Currently clocked in
- `absent` - Marked absent

**Search Bar:**
```html
<input type="text" id="searchInput" placeholder="Search employees by name or ID...">
```

**Global Undo Button:**
```html
<button id="btnGlobalUndo" class="btn-global-undo">
    <i class="fas fa-rotate-left"></i>
    <span>Undo</span>
</button>
```

#### G. Employee List Container (Lines 684-689)

```html
<div id="employeeContainer">
    <div class="no-employees">
        <i class="fas fa-users"></i>
        <div>Please select a deployment project to view all available employees</div>
    </div>
</div>
```

*Note: Actual employee list is populated by `attendance.js` via AJAX*

### 2.3 Additional Modals

| Modal | Purpose | Lines |
|-------|---------|-------|
| `timeLogsModal` | Display employee time logs for today | 614-624 |
| `profileImageModal` | Show enlarged profile image | 627-639 |
| `addBranchModal` | Add new project (Super Admin) | 533-594 |

---

## 3. JavaScript Functionality (Lines 769-1107)

### 3.1 PHP-to-JavaScript Data Transfer (Lines 731-765)

```javascript
// Attendance configuration
window.attendanceConfig = {
    cutoffTime: "<?php echo $cutoffTime; ?>",
    currentTime: "<?php echo $currentTime; ?>"
};

// Branch data for JS use
window.branchesFromPHP = <?php echo json_encode($branches); ?>;

// QR scan data for auto branch selection
window.qrScanData = {
    enabled: true|false,
    employeeBranch: "Main Office"
};
```

### 3.2 QR Auto-Select Branch (Lines 770-789)

**Purpose**: When QR scan opens page, automatically select employee's assigned branch.

```javascript
(function() {
    if (!window.qrScanData || !window.qrScanData.enabled || !window.qrScanData.employeeBranch) return;
    
    const empBranch = window.qrScanData.employeeBranch;
    
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            const branchCards = document.querySelectorAll('.branch-card');
            branchCards.forEach(function(card) {
                if (card.dataset.branch === empBranch) {
                    card.click();  // Trigger branch selection
                }
            });
        }, 1000);  // 1 second delay for DOM ready
    });
})();
```

### 3.3 QR Branch Selection with Location Verification (Lines 895-1107)

**Main IIFE (Immediately Invoked Function Expression):**

```javascript
(function() {
    const isBranchSelectMode = true|false;  // From PHP
    if (!isBranchSelectMode) return;
    
    // DOM Elements
    const branchCards = document.querySelectorAll('.qr-branch-card');
    const confirmBtn = document.getElementById('confirmBranchBtn');
    const retryBtn = document.getElementById('retryLocationBtn');
    const locationStatus = document.getElementById('locationStatus');
    
    // State Variables
    let selectedBranch = null;
    let selectedBranchData = null;
    let currentPosition = null;
    let isLocationValid = false;
})();
```

#### Branch Selection Flow

```
1. User clicks branch card
   ├─ Remove 'selected' class from all cards
   ├─ Add 'selected' class to clicked card
   ├─ Store branch data (id, name, lat, lng, radius)
   └─ Call verifyLocation()

2. verifyLocation()
   ├─ Show "Getting your location..." spinner
   ├─ Call navigator.geolocation.getCurrentPosition()
   ├─ On success: validatePosition(position)
   └─ On error: showLocationError(msg)

3. validatePosition(position)
   ├─ Extract lat, lng, accuracy from position
   ├─ If branch has no coordinates: allow anyway
   ├─ Calculate distance using Haversine formula
   ├─ If distance <= radius: showLocationValid()
   └─ If distance > radius: showLocationError(distance)

4. User clicks Confirm button
   ├─ Check isLocationValid and selectedBranch
   ├─ Build FormData with all parameters
   ├─ POST to select_employee.php (AJAX)
   ├─ On success: hide modal, show banner, autoSelectBranch()
   └─ On error: alert error message
```

#### Haversine Distance Calculation (Lines 1008-1022)

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

**Formula Explanation:**
- Converts coordinates from degrees to radians
- Calculates the great-circle distance between two points on a sphere
- Accuracy: ~0.5% error for distances up to ~100km

#### GPS Configuration (Lines 971-975)

```javascript
navigator.geolocation.getCurrentPosition(
    successCallback,
    errorCallback,
    {
        enableHighAccuracy: true,  // Use GPS if available
        timeout: 10000,            // 10 second timeout
        maximumAge: 60000        // Accept cached positions up to 1 min old
    }
);
```

#### Location Status UI States

| State | Visual | Button State |
|-------|--------|--------------|
| Checking | Yellow spinner + "Getting your location..." | Disabled |
| Valid | Green check + "Location verified!" | Enabled |
| Invalid | Red warning + distance error | Disabled + Show Retry |

---

## 4. Database Schema Reference

### 4.1 Tables Used

| Table | Purpose |
|-------|---------|
| `employees` | Employee records (id, code, name, status, branch_id) |
| `branches` | Project/branch data (id, name, lat, long, geofence_radius_meters) |
| `attendance` | Time-in/out records (id, employee_id, date, time_in, time_out, location data) |
| `location_logs` | GPS tracking logs (employee_id, attendance_id, lat, lng, accuracy, action_type) |

### 4.2 Key Queries

**Fetch Employee with Branch:**
```sql
SELECT e.id, e.first_name, e.last_name, e.employee_code, b.branch_name 
FROM employees e 
LEFT JOIN branches b ON b.id = e.branch_id 
WHERE e.id = ? AND e.status = 'Active' LIMIT 1
```

**Check If Already Clocked In:**
```sql
SELECT id FROM attendance 
WHERE employee_id = ? 
    AND attendance_date = CURDATE() 
    AND time_in IS NOT NULL 
    AND time_out IS NULL
```

---

## 5. External Dependencies

### 5.1 Included Files

| File | Purpose |
|------|---------|
| `../conn/db_connection.php` | Database connection |
| `function/attendance.php` | Attendance helper functions |
| `function/clock_functions.php` | `performClockIn()`, `performClockOut()` |
| `sidebar.php` | Navigation sidebar |
| `css/select_employee.css` | Page-specific styles |
| `css/light-theme.css` | Light/dark theme support |
| `js/theme.js` | Theme switching |
| `../assets/js/sidebar-toggle.js` | Sidebar toggle functionality |
| `js/attendance.js?v=4` | Main attendance management logic |

### 5.2 External Libraries

| Library | CDN |
|---------|-----|
| Font Awesome | `https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css` |
| Google Fonts (Inter) | `https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap` |

---

## 6. Security Features

| Feature | Implementation |
|---------|----------------|
| Session Validation | Checks `$_SESSION['logged_in']` or creates QR temp session |
| Input Sanitization | `intval()`, `htmlspecialchars()`, prepared statements |
| CSRF Protection | Not explicitly shown (relies on session) |
| Role-based Access | Super Admin checks for project management |
| Location Verification | GPS geofencing with configurable radius |
| Employee Status Check | Only 'Active' employees can clock in |

---

## 7. Mobile Responsiveness

**Breakpoints:**

| Width | Adjustments |
|-------|-------------|
| `< 640px` | Reduced padding, full-width modal |
| `< 380px` | Further reduced padding |

**QR Branch Selector Mobile Styles (Lines 392-410):**
```css
@media (max-width: 640px) {
    .qr-branch-selector-content {
        padding: 24px 20px;
        max-width: 95%;
        width: 95%;
        margin: 0 10px;
    }
    .qr-branch-selector-content h3 {
        font-size: 18px;
    }
}
```

---

## 8. Error Handling

### 8.1 GPS Errors

| Code | User Message |
|------|--------------|
| `PERMISSION_DENIED` | "Location access denied. Please enable location permissions." |
| `POSITION_UNAVAILABLE` | "Location information unavailable." |
| `TIMEOUT` | "Location request timed out." |

### 8.2 Validation Errors

| Scenario | Message |
|----------|---------|
| Outside geofence | "You are not in the location yet. Distance: Xm (allowed: Ym)" |
| Employee not found | "Employee not found" |
| Missing parameters | "Missing required parameters" |
| Clock-in failed | Returns `performClockIn()` error message |
| Clock-out failed | Returns `performClockOut()` error message |

---

## 9. Key JavaScript Functions Reference

| Function | Purpose | Location |
|----------|---------|----------|
| `verifyLocation()` | Gets GPS position and validates | Line 937 |
| `validatePosition(position)` | Calculates distance, checks geofence | Line 980 |
| `calculateDistance(lat1, lng1, lat2, lng2)` | Haversine formula | Line 1009 |
| `showLocationValid()` | UI update for valid location | Line 1024 |
| `showLocationError(msg)` | UI update for invalid location | Line 1033 |
| `autoSelectBranch(branchName)` | Triggers branch card click | Line 1098 |

---

## 10. Usage Flow Examples

### Example 1: QR Scan with Branch Selection

```
1. Employee scans QR code
   URL: /select_employee.php?auto_timein=1&select_branch=1&emp_id=42&emp_code=EMP-001

2. PHP creates temporary session

3. PHP detects $isBranchSelectMode = true
   → Sets $qrScanResult with 'select_branch' => true

4. HTML renders QR Branch Selector Modal

5. Employee selects branch card
   → JavaScript calls verifyLocation()

6. Browser requests GPS permission
   → Employee grants permission

7. JavaScript calculates distance using Haversine
   → Employee is within 200m radius

8. Confirm button enabled
   → Employee clicks Confirm

9. AJAX POST to select_employee.php
   → PHP calls performClockIn()
   → PHP saves location_logs entry
   → PHP updates attendance with GPS data

10. Success banner displayed
    → Modal closes
    → Branch auto-selected in main UI
```

### Example 2: Direct Auto Clock-In (Legacy Mode)

```
1. Employee scans QR code (without select_branch)
   URL: /select_employee.php?auto_timein=1&emp_id=42&emp_code=EMP-001

2. PHP creates temporary session

3. PHP fetches employee + assigned branch

4. PHP calls performClockIn()
   → If already clocked in
   → Automatically calls performClockOut()

5. $qrScanResult populated with success/error

6. HTML displays result banner (success/error)

7. Employee can interact with main attendance UI
```

---

## 11. File Summary

| Metric | Value |
|--------|-------|
| Total Lines | 1,110 |
| PHP Logic | 1-194 |
| HTML Structure | 195-768 |
| JavaScript | 769-1107 |
| Inline CSS | 209-411 |
| External CSS Files | 3 |
| External JS Files | 4 |
| Database Tables | 4 |
| Main Features | 6 (Auth, QR Scan, Branch Selection, GPS, Attendance, Undo) |

---

**Last Updated**: April 2026  
**Maintained By**: JAJR Development Team
