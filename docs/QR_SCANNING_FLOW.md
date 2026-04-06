# QR Code Scanning Flow Documentation

## Overview

The QR Code Scanning feature allows employees to clock in and out by scanning a personalized QR code. The system supports multiple API endpoints and flows for different use cases.

---

## Architecture

```
┌─────────────────┐     ┌─────────────────────┐     ┌──────────────────┐
│  QR Code Scan   │────▶│  select_employee.php │────▶│  Clock In/Out    │
│  (Mobile/Web)   │     │  (Auth Bypass)       │     │  (Database)      │
└─────────────────┘     └─────────────────────┘     └──────────────────┘
                                │
                                ▼
                       ┌─────────────────────┐
                       │  performClockIn()   │
                       │  performClockOut()  │
                       └─────────────────────┘
```

---

## QR Code Generation

**Location:** `employee/employees.php:418-458`

### QR Code Content Format

The QR code contains a URL with the following format:

```
https://{domain}/employee/select_employee.php?auto_timein=1&emp_id={EMPLOYEE_ID}&emp_code={EMPLOYEE_CODE}
```

**Example:**
```
https://jajr.xandree.com/employee/select_employee.php?auto_timein=1&emp_id=61&emp_code=E0051
```

### QR Code Generation Code

```javascript
function generateQRCode(event, id, name, code, email, position) {
    const baseUrl = window.location.origin + '/employee/select_employee.php';
    const qrUrl = `${baseUrl}?auto_timein=1&emp_id=${id}&emp_code=${encodeURIComponent(code)}`;
    
    currentQRCode = new QRCode(qrContainer, {
        text: qrUrl,
        width: 280,
        height: 280,
        colorDark: '#000000',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.H
    });
}
```

---

## Main QR Scan Flow (Web)

**Entry Point:** `employee/select_employee.php`

### 1. Authentication Bypass for QR Scans (Lines 8-32)

```php
$isQRScan = isset($_GET['auto_timein']) && isset($_GET['emp_id']);

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    if ($isQRScan) {
        // Create temporary authenticated session for QR scan
        $_SESSION['logged_in'] = true;
        $_SESSION['employee_id'] = intval($_GET['emp_id']);
        $_SESSION['employee_code'] = isset($_GET['emp_code']) ? $_GET['emp_code'] : '';
        $_SESSION['position'] = 'QR Scan';
        $_SESSION['qr_temp_session'] = true; // Mark as temporary
    }
}
```

### 2. Auto Time-In/Out Logic (Lines 38-100)

```php
if (isset($_GET['auto_timein']) && isset($_GET['emp_id'])) {
    $qrEmployeeId = intval($_GET['emp_id']);
    
    // Fetch employee details and branch
    $empStmt = mysqli_prepare($db, "SELECT e.id, e.first_name, e.last_name, e.employee_code, b.branch_name 
        FROM employees e 
        LEFT JOIN branches b ON b.id = e.branch_id 
        WHERE e.id = ? LIMIT 1");
    
    // Call clock-in function directly
    $clockInResult = performClockIn($db, $qrEmployeeId, $employee['employee_code'], $branchName);
    
    if ($clockInResult['success']) {
        $qrScanResult = [
            'success' => true,
            'message' => $employee['first_name'] . ' ' . $employee['last_name'] . ' time-in recorded'
        ];
    } else {
        // If already clocked in, auto-trigger clock out
        if (stripos($msg, 'already clocked in') !== false) {
            $clockOutResult = performClockOut($db, $qrEmployeeId, $employee['employee_code'], $branchName);
        }
    }
}
```

### 3. Display Result Banner (Lines 144-149)

```php
<?php if ($qrScanResult): ?>
<div class="qr-result-banner <?php echo $qrScanResult['success'] ? 'success' : 'error'; ?>">
    <i class="fas <?php echo $qrScanResult['success'] ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
    <?php echo htmlspecialchars($qrScanResult['message']); ?>
</div>
<?php endif; ?>
```

### 4. Auto-Select Branch (Lines 442-462)

```javascript
window.qrScanData = {
    enabled: true/false,
    employeeBranch: "Branch Name"
};

// Auto-select the employee's branch after QR scan
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        const branchCards = document.querySelectorAll('.branch-card');
        branchCards.forEach(function(card) {
            if (card.dataset.branch === empBranch) {
                card.click();
            }
        });
    }, 1000);
});
```

---

## API Endpoints

### 1. Main QR Clock API (Root Level)

**File:** `qr_clock_api.php`

**Features:**
- Full geolocation support (latitude, longitude, accuracy)
- Auto clock-out if already clocked in
- Location verification
- Comprehensive error handling
- Activity logging

**Endpoint:** `POST /qr_clock_api.php`

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `action` | string | No | 'in' or 'out' (default: 'in') |
| `employee_id` | int | Yes | Employee database ID |
| `employee_code` | string | Yes | Employee unique code |
| `latitude` | float | No | GPS latitude |
| `longitude` | float | No | GPS longitude |
| `accuracy` | float | No | Location accuracy in meters |
| `location_verified` | int | No | 1 if verified, 0 otherwise |

**Success Response (Clock In):**
```json
{
    "success": true,
    "message": "John Doe time-in recorded at 09:30 AM",
    "time_in": "09:30 AM"
}
```

**Success Response (Auto Clock Out):**
```json
{
    "success": true,
    "message": "John Doe time-out recorded at 06:00 PM",
    "time_out": "06:00 PM",
    "auto_clock_out": true
}
```

### 2. Simple QR Clock API (Employee Folder)

**File:** `employee/api/qr_clock.php`

**Features:**
- No session required
- Simple check action to verify status
- Basic clock in/out

**Endpoint:** `POST /employee/api/qr_clock.php`

**Actions:**
- `check` - Check if employee is already clocked in
- `in` - Clock in
- `out` - Clock out

**Check Response:**
```json
{
    "success": true,
    "already_in": true/false,
    "employee_name": "John Doe",
    "employee_id": 61
}
```

### 3. QR Time-In (HTML Response)

**File:** `employee/api/qr_timein.php`

**Features:**
- Returns HTML page with result
- GET-based endpoint
- Visual success/error display

**Endpoint:** `GET /employee/api/qr_timein.php?id={EMPLOYEE_ID}&code={EMPLOYEE_CODE}`

---

## Flow Diagrams

### Complete QR Scan Flow (Web)

```
┌─────────────┐
│ Employee    │
│ Scans QR    │
└──────┬──────┘
       │
       ▼
┌─────────────────────────────────────────┐
│ select_employee.php?auto_timein=1      │
│ &emp_id=61&emp_code=E0051              │
└─────────────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────┐
│ 1. Detect QR params (auto_timein, emp_id)│
│ 2. Create temp session                   │
│    - $_SESSION['logged_in'] = true      │
│    - $_SESSION['qr_temp_session'] = true │
└─────────────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────┐
│ 3. Fetch employee + branch info          │
└─────────────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────┐
│ 4. Call performClockIn()                 │
└─────────────────────────────────────────┘
       │
       ├────────────┬────────────┐
       ▼            ▼            ▼
   Success      Already In    Error
       │            │            │
       ▼            ▼            ▼
   Record    performClockOut()  Show
   Time-In       │            Error
            Success/Error       │
                 │              │
                 ▼              │
       ┌─────────────────┐      │
       │ Show result     │◄─────┘
       │ banner on page  │
       └─────────────────┘
                 │
                 ▼
       ┌─────────────────┐
       │ Auto-select       │
       │ employee branch   │
       └─────────────────┘
```

### API Decision Flow

```
                    ┌─────────────────┐
                    │ QR Scan Request │
                    └────────┬────────┘
                             │
              ┌──────────────┼──────────────┐
              │              │              │
              ▼              ▼              ▼
    ┌─────────────────┐ ┌──────────────┐ ┌─────────────────┐
    │ Web Browser     │ │ Mobile App   │ │ Simple Device   │
    │ (Needs UI)      │ │ (Needs JSON) │ │ (Basic)         │
    └────────┬────────┘ └──────┬───────┘ └────────┬────────┘
             │                 │                  │
             ▼                 ▼                  ▼
    ┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐
    │ select_employee │ │ qr_clock_api.php│ │ api/qr_clock.php│
    │ .php            │ │                 │ │                 │
    │ (HTML + Auth)   │ │ (Full Geo)      │ │ (No Session)    │
    └─────────────────┘ └─────────────────┘ └─────────────────┘
```

---

## Database Schema

### Relevant Tables

**employees**
```sql
- id (PK)
- employee_code (unique)
- first_name
- last_name
- branch_id (FK)
- status ('Active', 'Inactive')
```

**attendance**
```sql
- id (PK)
- employee_id (FK)
- branch_id (FK)
- branch_name
- attendance_date
- time_in
- time_out
- status ('Present', 'Absent', 'Late')
- clock_in_lat (optional)
- clock_in_lng (optional)
- clock_out_lat (optional)
- clock_out_lng (optional)
- location_accuracy (optional)
- location_verified (optional)
```

**branches**
```sql
- id (PK)
- branch_name
- order_number (optional)
- address (optional)
```

---

## Files Involved

| File | Purpose |
|------|---------|
| `employee/employees.php` | Generates QR codes for employees |
| `employee/select_employee.php` | Handles QR scan, auth bypass, clock in/out |
| `employee/function/clock_functions.php` | `performClockIn()`, `performClockOut()` |
| `qr_clock_api.php` | Main QR clock API with geolocation |
| `employee/api/qr_clock.php` | Simple QR clock API (no session) |
| `employee/api/qr_timein.php` | HTML-based QR time-in |

---

## Security Considerations

1. **Temporary Session Creation**: QR scans create temporary authenticated sessions marked with `qr_temp_session` flag

2. **Employee Verification**: All APIs verify that `employee_id` and `employee_code` match

3. **Active Status Check**: APIs check if employee status is 'Active' before allowing clock in/out

4. **CORS Headers**: `qr_clock_api.php` includes CORS headers for mobile app access

5. **Input Validation**: All inputs are sanitized using prepared statements

---

## Error Handling

### Common Error Responses

**Employee Not Found:**
```json
{
    "success": false,
    "message": "Employee not found"
}
```

**Already Clocked In:**
```json
{
    "success": false,
    "message": "Already clocked in",
    "already_in": true
}
```

**No Active Time-In (Clock Out):**
```json
{
    "success": false,
    "message": "No active time-in found. Please clock in first."
}
```

**Inactive Employee:**
```json
{
    "success": false,
    "message": "Employee account is not active"
}
```

---

## Testing

### Test QR Code URL
```
https://jajr.xandree.com/employee/select_employee.php?auto_timein=1&emp_id=61&emp_code=E0051
```

### API Test (curl)

**Clock In:**
```bash
curl -X POST https://jajr.xandree.com/qr_clock_api.php \
  -d "action=in&employee_id=61&employee_code=E0051&latitude=14.5995&longitude=120.9842"
```

**Clock Out:**
```bash
curl -X POST https://jajr.xandree.com/qr_clock_api.php \
  -d "action=out&employee_id=61&employee_code=E0051"
```

---

## Implementation Notes

1. **Timezone**: All times are recorded in `Asia/Manila` (Philippine Time, UTC+8)

2. **Auto Clock-Out**: If an employee scans their QR code while already clocked in, the system automatically performs a clock-out instead

3. **Branch Auto-Selection**: After a successful QR scan, the page automatically selects the employee's assigned branch in the UI

4. **Geolocation**: The main `qr_clock_api.php` supports geolocation tracking with latitude, longitude, and accuracy

---

## New QR Scanner Flow (login.php) with Branch Selection

**File:** `login.php` (lines 566-1325)

This is a standalone QR scanner on the login page that does NOT require user authentication. It allows workers to clock in/out by scanning their QR code and selecting their working branch.

### Flow

```
┌─────────────────┐
│ Click QR Icon   │
│ on Login Page   │
└────────┬────────┘
         │
         ▼
┌─────────────────────────┐
│ 1. Camera starts        │
│ 2. Scan employee QR     │
│    (contains emp_id)    │
└────────┬────────────────┘
         │
         ▼
┌─────────────────────────┐
│ 3. Show branch selection│
│    modal with all       │
│    active branches      │
└────────┬────────────────┘
         │
         ▼
┌─────────────────────────┐
│ 4. Worker selects       │
│    working branch       │
│    (e.g., Sto. Rosario) │
└────────┬────────────────┘
         │
         ▼
┌─────────────────────────┐
│ 5. GPS location check   │
│    (geofence validation)│
└────────┬────────────────┘
         │
         ▼
┌─────────────────────────┐
│ 6. Worker confirms      │
│    time-in or time-out  │
└────────┬────────────────┘
         │
         ▼
┌─────────────────────────┐
│ 7. POST to qr_clock.php │
│    with branch_id and   │
│    branch_name          │
└─────────────────────────┘
```

### Key Code Sections

**Branch Data Structure** (line 740-756):
```javascript
branches.forEach(branch => {
  const card = document.createElement('div');
  card.dataset.branchId = branch.id;              // ← ID sent to API
  card.dataset.branchName = branch.branch_name;   // ← Name sent to API
  card.dataset.lat = branch.latitude;
  card.dataset.lng = branch.longitude;
  card.dataset.radius = branch.geofence_radius || 200;
  
  // When clicked, verifyLocationForBranch(card.dataset, empInfo)
});
```

**Location Verification** (lines 823-908):
```javascript
async function verifyLocationForBranch(branchData, empInfo) {
  const position = await getCurrentPosition();
  
  // Calculate distance using Haversine formula
  const distance = calculateDistance(
    position.latitude, position.longitude,
    parseFloat(branchData.lat), parseFloat(branchData.lng)
  );
  
  const radius = parseInt(branchData.radius) || 200;
  
  if (distance <= radius) {
    showLocationSuccess();  // Enable confirm button
  } else {
    showLocationError(`You are not in the location yet. Distance: ${Math.round(distance)}m`);
  }
}
```

**API Call** (lines 1101-1146):
```javascript
async function processClockIn(empId, empCode, branchData) {
  const url = `${window.location.origin}/employee/api/qr_clock.php`;
  const formData = new FormData();
  formData.append('action', 'in');
  formData.append('employee_id', empId);
  formData.append('employee_code', empCode);
  formData.append('branch_id', branchData.branchId);      // ← Selected branch ID
  formData.append('branch_name', branchData.branchName);  // ← Selected branch name
  formData.append('latitude', currentPosition.latitude);
  formData.append('longitude', currentPosition.longitude);
  formData.append('accuracy', currentPosition.accuracy);
  
  const response = await fetch(url, { method: 'POST', body: formData });
  const data = await response.json();
  return data;
}
```

---

## Known Issue: Wrong Branch Recorded in Attendance

**Symptom:** Worker selects "Sto. Rosario" in the QR scanner, but attendance audit shows "BCDA - Admin"

### Root Causes

#### 1. API Not Using branch_name Parameter

**File:** `employee/api/qr_clock.php`

The API may be ignoring the `branch_name` POST parameter and using the employee's assigned branch instead.

**Check this code:**
```php
$branchName = $_POST['branch_name'] ?? null;
if (empty($branchName)) {
    // Fallback to employee's assigned branch
    $branchName = $employee['branch_name'];
}
```

#### 2. Database Schema Mismatch

**Check:** Does the `attendance` table have both `branch_id` AND `branch_name` columns?

```sql
DESCRIBE attendance;
```

If only `branch_name` exists (no `branch_id` FK), the API might be using employee's default branch.

#### 3. Employee Assigned Branch Override

**Check:** In `qr_clock.php`, look for code that fetches employee's branch:
```php
// WRONG: Using employee's assigned branch instead of selected branch
$branchStmt = mysqli_prepare($db, "SELECT branch_name FROM employees WHERE id = ?");
// Should use: $_POST['branch_name']
```

### Debug Steps

#### 1. Check Browser Network Request

1. Open Chrome DevTools (F12) → Network tab
2. Scan QR code, select branch, click Confirm
3. Find the `qr_clock.php` POST request
4. Check **Request Headers** → **Form Data**:
```
action: in
employee_id: 123
branch_id: 10
branch_name: Sto. Rosario
latitude: 16.6147
longitude: 120.3639
```

#### 2. Check API Response

Look at the **Response** tab for `qr_clock.php`:
```json
{
  "success": true,
  "message": "Time-in recorded",
  "branch_name": "Sto. Rosario"  // ← Should match selected branch
}
```

#### 3. Verify Database Record

```sql
SELECT a.id, a.employee_id, e.first_name, e.last_name, 
       a.branch_name, a.time_in, a.created_at
FROM attendance a
JOIN employees e ON e.id = a.employee_id
WHERE a.attendance_date = CURDATE()
ORDER BY a.time_in DESC
LIMIT 10;
```

### Solution

Ensure `employee/api/qr_clock.php` uses the `branch_name` from POST:

```php
<?php
// employee/api/qr_clock.php

// ... get employee_id, employee_code ...

// GET BRANCH FROM POST (worker selected this)
$branchName = trim($_POST['branch_name'] ?? '');
$branchId = intval($_POST['branch_id'] ?? 0);

// Fallback only if not provided
if (empty($branchName)) {
    $branchName = $employee['branch_name'] ?? 'Main Branch';
}

// ... insert attendance with $branchName ...
$sql = "INSERT INTO attendance (employee_id, branch_name, attendance_date, time_in, status) 
        VALUES (?, ?, CURDATE(), NOW(), 'Present')";
$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, "is", $employeeId, $branchName);
```

### Quick Test

1. Go to `login.php`
2. Open DevTools → Console
3. Run this to simulate the API call:
```javascript
const formData = new FormData();
formData.append('action', 'in');
formData.append('employee_id', '123');
formData.append('branch_id', '10');
formData.append('branch_name', 'Sto. Rosario');

fetch('/employee/api/qr_clock.php', {
  method: 'POST',
  body: formData
}).then(r => r.json()).then(console.log);
```
4. Check if response shows `branch_name: "Sto. Rosario"`
5. Check database if correct branch was saved

---

## Geofence Troubleshooting

### "You are not in the location yet" Error

**Debug console output** (added to `login.php`):
```javascript
console.log('QR Location Debug:');
console.log('Your GPS:', position.latitude, position.longitude);
console.log('Branch GPS:', parseFloat(branchData.lat), parseFloat(branchData.lng));
console.log('Distance:', Math.round(distance), 'meters');
console.log('Radius allowed:', radius, 'meters');
```

**Fix options:**
1. **Increase geofence radius**:
```sql
UPDATE branches SET geofence_radius_meters = 500 WHERE id = 10;
```

2. **Update branch coordinates** via `employee/branch_location_manager.php`

3. **Check branch has coordinates**:
```sql
SELECT branch_name, lat, `long`, geofence_radius_meters 
FROM branches WHERE branch_name = 'Sto. Rosario';
```

