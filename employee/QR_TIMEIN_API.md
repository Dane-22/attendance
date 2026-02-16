# QR Time-In Feature - API Documentation

## Overview
The QR Time-In feature allows employees to record their attendance by scanning a QR code. This document explains how the system works and what API endpoints to use for mobile app implementation.

## How It Works

### 1. QR Code Generation
Each employee has a unique QR code that contains a URL with their employee information.

**QR Code URL Format:**
```
https://jajr.xandree.com/employee/select_employee.php?auto_timein=1&emp_id={EMPLOYEE_ID}&emp_code={EMPLOYEE_CODE}
```

**Example:**
```
https://jajr.xandree.com/employee/select_employee.php?auto_timein=1&emp_id=61&emp_code=E0051
```

### 2. Web Flow (Current Implementation)

When a user scans the QR code with their phone:

1. **Open URL** → Opens `select_employee.php` with query parameters
2. **Bypass Authentication** → Creates temporary session for QR scan
3. **Call Clock In API** → Server-side cURL call to `clock_in.php`
4. **Display Result** → Shows success/error banner on the page
5. **Auto-select Branch** → JavaScript selects the employee's branch

### 3. Authentication Bypass for QR Scans

The `select_employee.php` detects QR scan parameters and creates a temporary authenticated session:

```php
$isQRScan = isset($_GET['auto_timein']) && isset($_GET['emp_id']);

if ($isQRScan) {
    $_SESSION['logged_in'] = true;
    $_SESSION['employee_id'] = intval($_GET['emp_id']);
    $_SESSION['position'] = 'QR Scan';
    $_SESSION['qr_temp_session'] = true;
}
```

## Mobile App Implementation

### For Mobile Apps - Direct API Call

Your mobile app should call the `clock_in.php` API directly instead of opening the web URL.

**API Endpoint:**
```
POST https://jajr.xandree.com/employee/api/clock_in.php
```

**Required Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `employee_id` | integer | Employee's database ID |
| `employee_code` | string | Employee's unique code (e.g., E0051) |
| `branch_name` | string | Employee's assigned branch/project name |

**Optional Headers:**
```
X-Requested-With: XMLHttpRequest
```

### Example API Call (Mobile App)

**JavaScript/TypeScript (React Native):**
```javascript
const recordTimeIn = async (employeeId, employeeCode, branchName) => {
  const formData = new FormData();
  formData.append('employee_id', employeeId);
  formData.append('employee_code', employeeCode);
  formData.append('branch_name', branchName);

  try {
    const response = await fetch('https://jajr.xandree.com/employee/api/clock_in.php', {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: formData,
    });

    const data = await response.json();
    
    if (data.success) {
      console.log('Time-in recorded:', data.time_in);
      return { success: true, timeIn: data.time_in };
    } else {
      console.error('Time-in failed:', data.message);
      return { success: false, error: data.message };
    }
  } catch (error) {
    console.error('API Error:', error);
    return { success: false, error: 'Network error' };
  }
};
```

**PHP (Backend Proxy):**
```php
<?php
// mobile_timein.php - For your mobile app backend

function recordMobileTimeIn($employeeId, $employeeCode, $branchName) {
    $apiUrl = 'https://jajr.xandree.com/employee/api/clock_in.php';
    
    $postData = [
        'employee_id' => $employeeId,
        'employee_code' => $employeeCode,
        'branch_name' => $branchName
    ];
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-Requested-With: XMLHttpRequest'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}
```

## QR Code Content Options

### Option 1: URL-based (Current Web Implementation)
QR contains a URL that opens the web page:
```
https://jajr.xandree.com/employee/select_employee.php?auto_timein=1&emp_id=61&emp_code=E0051
```

### Option 2: JSON Data (Recommended for Mobile Apps)
QR contains JSON data that your mobile app parses:
```json
{
  "emp_id": 61,
  "emp_code": "E0051",
  "name": "John Doe",
  "branch": "Main Office"
}
```

**Mobile App QR Scanning Flow:**
1. User scans QR code
2. App parses JSON data
3. App extracts `emp_id`, `emp_code`, `branch`
4. App calls `clock_in.php` API directly
5. App shows success/error toast notification

## API Response Format

**Success Response:**
```json
{
  "success": true,
  "message": "Time-in recorded successfully",
  "time_in": "09:30 AM",
  "shift_id": 123,
  "auto_transferred": false
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "Employee already clocked in today"
}
```

## Database Schema (Relevant Tables)

### employees
- `id` - Employee ID
- `employee_code` - Unique employee code
- `branch_id` - Foreign key to branches table
- `first_name`, `last_name`

### branches
- `id` - Branch ID
- `branch_name` - Branch/project name

### attendance
- `id` - Record ID
- `employee_id` - Foreign key
- `branch_id` - Branch where clocked in
- `attendance_date` - Date
- `time_in` - Time in
- `status` - Present/Absent/Late

## Files Involved

| File | Purpose |
|------|---------|
| `employees.php` | Generates QR codes with employee URLs |
| `select_employee.php` | Handles QR scan, bypasses auth, calls API |
| `api/clock_in.php` | Records time-in to database |

## Implementation Checklist for Mobile App

- [ ] Generate QR codes with employee data (JSON format recommended)
- [ ] Implement QR scanner in mobile app
- [ ] Parse QR data to extract employee info
- [ ] Call `clock_in.php` API endpoint
- [ ] Handle API response (success/error)
- [ ] Show appropriate notifications to user
- [ ] Handle offline scenarios (optional)

## Security Considerations

1. **Authentication**: The web implementation creates a temporary session. For mobile apps, consider adding API tokens.
2. **HTTPS**: Always use HTTPS for API calls in production.
3. **Rate Limiting**: Consider implementing rate limiting to prevent abuse.
4. **Employee Verification**: The API verifies `employee_id` and `employee_code` match.

## Testing

**Test QR Code URL:**
```
https://jajr.xandree.com/employee/select_employee.php?auto_timein=1&emp_id=61&emp_code=E0051
```

Replace `emp_id` and `emp_code` with actual employee values from your database.
