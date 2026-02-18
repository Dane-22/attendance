# QR Code Scanner - Fixed & Working

## Overview

The QR code scanner in `login.php` allows employees to clock in/out without logging in. This kiosk-mode feature works independently of user sessions and now successfully records attendance via AJAX.

## Architecture

```
┌─────────────────┐
│  User opens QR  │
│  scanner modal  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Camera starts  │
│  (rear-facing)  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  QR scanned     │
│  Extract emp_id │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  POST to        │
│  qr_clock.php   │
│  action=in      │
└────────┬────────┘
         │
    ┌────┴────┐
    │         │
    ▼         ▼
┌────────┐ ┌────────┐
│Already │ │ Insert │
│clocked?│ │ new    │
└────┬───┘ │ record │
     │     └────┬───┘
     │          │
     ▼          ▼
┌──────────────────┐
│  Show success    │
│  message +       │
│  "Scan Another"  │
└──────────────────┘
```

## Key Files

| File | Purpose |
|------|---------|
| `login.php` | QR scanner modal, camera init, AJAX calls |
| `employee/api/qr_clock.php` | Clock-in/out API (session-less) |

## Database Schema Requirements

### employees table
```sql
CREATE TABLE `employees` (
  `id` int AUTO_INCREMENT,
  `employee_code` varchar(50),
  `first_name` varchar(100),
  `last_name` varchar(100),
  `branch_id` int,
  `status` varchar(50) DEFAULT 'Active',  -- NOT is_active!
  ...
);
```

### attendance table
```sql
CREATE TABLE `attendance` (
  `id` int AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `branch_name` varchar(50),
  `attendance_date` date,
  `time_in` datetime,
  `time_out` datetime,
  `status` enum('Present','Late','Absent','System'),
  `is_overtime_running` tinyint(1) NOT NULL,  -- Required!
  `is_time_running` tinyint(1) NOT NULL,        -- Required!
  `total_ot_hrs` varchar(10) NOT NULL,         -- Required!
  ...
);
```

## Critical Fixes Applied

### 1. Column Name Fix
**Error:** `Unknown column 'is_active' in 'WHERE'`

**Fix:** Changed from `is_active = 1` to `status = 'Active'`
```php
// WRONG
$empStmt = mysqli_prepare($db, "SELECT ... FROM employees WHERE id = ? AND is_active = 1");

// CORRECT
$empStmt = mysqli_prepare($db, "SELECT ... FROM employees WHERE id = ? AND status = 'Active'");
```

### 2. Required Columns Fix
**Error:** `Field 'is_overtime_running' doesn't have a default value`

**Fix:** Added all required NOT NULL columns to INSERT
```php
// WRONG
$insertSql = "INSERT INTO attendance (employee_id, branch_name, attendance_date, time_in, status) ...";

// CORRECT
$insertSql = "INSERT INTO attendance (employee_id, branch_name, attendance_date, time_in, status, is_overtime_running, is_time_running, total_ot_hrs) 
               VALUES (?, ?, CURDATE(), NOW(), 'Present', 0, 1, '0')";
```

### 3. Error Handling
Added try-catch wrapper to capture all errors:
```php
try {
    // ... API logic
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
```

## JavaScript Functions

### parseEmployeeFromQR(text)
Extracts employee data from QR URL:
```javascript
// URL format: .../select_employee.php?auto_timein=1&emp_id=123&emp_code=ABC
const empIdMatch = text.match(/[?&]emp_id=(\d+)/);
const empCodeMatch = text.match(/[?&]emp_code=([^&]+)/);
```

### processClockIn(empId, empCode)
- POST to `qr_clock.php?action=in`
- If already clocked in → auto-triggers clock-out
- Returns `{success, message}`

### processClockOut(empId, empCode)
- POST to `qr_clock.php?action=out`
- Updates existing row with `time_out = NOW()`

## API Endpoint: qr_clock.php

### Request
```http
POST /employee/api/qr_clock.php
Content-Type: multipart/form-data

action=in&employee_id=123&employee_code=E0001
```

### Success Response (Clock In)
```json
{
  "success": true,
  "message": "ALFREDO BAGUIO time-in recorded at 10:30 AM",
  "time_in": "10:30 AM"
}
```

### Success Response (Clock Out)
```json
{
  "success": true,
  "message": "ALFREDO BAGUIO time-out recorded at 06:00 PM",
  "time_out": "06:00 PM"
}
```

### Already Clocked In (Triggers Auto Clock-Out)
```json
{
  "success": false,
  "message": "Already clocked in",
  "already_in": true
}
```

### Error Response
```json
{
  "success": false,
  "message": "Employee not found"
}
```

## Testing Steps

1. Open `https://jajr.xandree.com/login.php` on mobile
2. Click QR icon (top right of login form)
3. Allow camera permission
4. Scan employee QR code
5. Should see green success: "[Name] time-in recorded at [time]"
6. Click "Scan Another"
7. Scan same QR code again
8. Should see green success: "[Name] time-out recorded at [time]"

## Troubleshooting

| Error | Cause | Fix |
|-------|-------|-----|
| "Unknown column 'is_active'" | Wrong column name | Use `status = 'Active'` |
| "Field doesn't have default value" | Missing columns | Add `is_overtime_running`, `is_time_running`, `total_ot_hrs` |
| "HTTP 500: Empty response" | PHP fatal error | Add try-catch, check error logs |
| "Employee not found" | Inactive employee or wrong ID | Check employee exists and status='Active' |
| "No active time-in found" | Trying to clock out without clocking in | Must clock in first |

## Mobile Requirements

- HTTPS connection (required for camera access)
- Chrome camera permission: Settings → Site Settings → Camera → Allow
- Rear camera (`facingMode: 'environment'`)

## No Session Required

The QR scanner works without login because:
- `qr_clock.php` doesn't check `$_SESSION`
- Employee ID comes from QR code, not session
- Branch is auto-detected from employee record
