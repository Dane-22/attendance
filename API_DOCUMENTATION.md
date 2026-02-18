# API Documentation

Complete documentation of all API endpoints in the Attendance Management System.

## Table of Contents

- [Authentication APIs](#authentication-apis)
- [Attendance APIs](#attendance-apis)
- [Employee APIs](#employee-apis)
- [Branch APIs](#branch-apis)
- [QR Clock APIs](#qr-clock-apis)
- [Password Management APIs](#password-management-apis)
- [Overtime APIs](#overtime-apis)
- [External Integration APIs](#external-integration-apis)

---

## Authentication APIs

### Login API
**File:** `login_api.php`

Authenticates employees using email/employee_code and password. Supports dual password verification (MD5 and bcrypt).

- **URL:** `/login_api.php`
- **Method:** `POST`
- **Content-Type:** `application/x-www-form-urlencoded` or `application/json`

**Parameters:**
| Name | Type | Required | Description |
|------|------|----------|-------------|
| identifier | string | Yes | Email or employee_code |
| password | string | Yes | Employee password |
| branch_name | string | Yes | Branch for attendance tracking |

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "user_data": {
    "id": 1,
    "employee_code": "EMP-001",
    "first_name": "John",
    "last_name": "Doe",
    "position": "Worker",
    "assigned_branch": "Main",
    "daily_branch": "Branch 1"
  }
}
```

---

### Login API (Simple)
**File:** `login_api_simple.php`

Simplified version of the login API for basic authentication needs.

- **URL:** `/login_api_simple.php`
- **Method:** `POST`

---

## Attendance APIs

### Time In API
**File:** `time_in_api.php`

Records employee time-in for attendance tracking. Supports multiple sessions at same branch.

- **URL:** `/time_in_api.php`
- **Method:** `POST`
- **Content-Type:** `application/x-www-form-urlencoded`

**Parameters:**
| Name | Type | Required | Description |
|------|------|----------|-------------|
| employee_id | int | Yes | Employee ID |
| branch_name | string | Yes | Branch name |
| debug | int | No | Set to 1 for debug info |

**Response:**
```json
{
  "success": true,
  "message": "Time in recorded",
  "attendance_id": 123,
  "time_in": "2024-01-15 08:30:00",
  "is_time_running": true
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "Already timed in at another branch today",
  "branch_name": "Main Branch",
  "time_in": "08:30:00"
}
```

---

### Time Out API
**File:** `time_out_api.php`

Records employee time-out for attendance tracking.

- **URL:** `/time_out_api.php`
- **Method:** `POST`

**Parameters:**
| Name | Type | Required | Description |
|------|------|----------|-------------|
| employee_id | int | Yes | Employee ID |
| branch_name | string | Yes | Branch name |

**Response:**
```json
{
  "success": true,
  "message": "Time out recorded",
  "attendance_id": 123,
  "time_out": "2024-01-15 17:30:00",
  "is_time_running": false
}
```

---

### Clock Out API
**File:** `clock_out_api.php`

Alternative endpoint for clocking out with optional branch name update.

- **URL:** `/clock_out_api.php`
- **Method:** `POST`

**Parameters:**
| Name | Type | Required | Description |
|------|------|----------|-------------|
| employee_id | int | Yes | Employee ID |
| branch_name | string | No | Branch name (optional, updates record) |

---

### Submit Attendance API
**File:** `submit_attendance_api.php`

Submits manual attendance entries.

- **URL:** `/submit_attendance_api.php`
- **Method:** `POST`

---

### Get Shift Logs API
**File:** `get_shift_logs_api.php`

Retrieves shift logs and attendance history for employees.

- **URL:** `/get_shift_logs_api.php`
- **Method:** `GET/POST`

---

### Get Attendance Absent Notes API
**File:** `get_attendance_absent_notes_api.php`

Retrieves absent notes for attendance records.

- **URL:** `/get_attendance_absent_notes_api.php`
- **Method:** `GET/POST`
- **CORS:** Enabled

**Parameters:**
| Name | Type | Required | Description |
|------|------|----------|-------------|
| employee_id | int | Yes | Employee ID |
| date | string | No | Date (YYYY-MM-DD), defaults to today |

**Response:**
```json
{
  "success": true,
  "absent_notes": "Sick leave - medical certificate attached"
}
```

---

### Mark Attendance Absent API
**File:** `mark_attendance_absent_api.php`

Marks attendance records as absent with notes.

- **URL:** `/mark_attendance_absent_api.php`
- **Method:** `POST`

---

## Employee APIs

### Employees Today Status API
**File:** `employees_today_status_api.php`

Returns all employees with their today's attendance status, including time-in/out status and overtime info.

- **URL:** `/employees_today_status_api.php`
- **Method:** `GET/POST`
- **CORS:** Enabled

**Parameters:**
| Name | Type | Required | Description |
|------|------|----------|-------------|
| branch_name | string | No | Branch name (kept for backward compatibility) |

**Response:**
```json
[
  {
    "id": 1,
    "employee_code": "EMP-001",
    "first_name": "John",
    "last_name": "Doe",
    "position": "Worker",
    "branch_id": 1,
    "assigned_branch_name": "Main Branch",
    "branch_name": "Branch 1",
    "today_status": "Present",
    "time_in": "08:30:00",
    "time_out": null,
    "is_auto_absent": false,
    "is_time_running": true,
    "is_overtime_running": false,
    "is_timed_in": true,
    "total_ot_hrs": "2"
  }
]
```

**Features:**
- Auto-closes yesterday's open sessions at midnight
- Returns all employees regardless of branch
- Includes attendance and overtime status

---

### Get Available Employees API
**File:** `get_available_employees_api.php`

Retrieves list of available/active employees.

- **URL:** `/get_available_employees_api.php`
- **Method:** `GET/POST`

---

### Set Employee Branch API
**File:** `set_employee_branch_api.php`

Updates employee's assigned branch.

- **URL:** `/set_employee_branch_api.php`
- **Method:** `POST`
- **CORS:** Enabled

**Parameters:**
| Name | Type | Required | Description |
|------|------|----------|-------------|
| employee_id | int | Yes | Employee ID |
| branch_id | int | Yes | Branch ID to assign |

**Response:**
```json
{
  "success": true,
  "message": "Employee branch updated",
  "employee_id": 1,
  "branch_id": 2
}
```

---

### Transfer Branch API
**File:** `transfer_branch_api.php`

Handles employee branch transfers with history tracking.

- **URL:** `/transfer_branch_api.php`
- **Method:** `POST`

---

### Update Profile API
**File:** `update_profile_api.php`

Updates employee profile information.

- **URL:** `/update_profile_api.php`
- **Method:** `POST`

---

## Branch APIs

### Get Branches API
**File:** `get_branches_api.php`

Returns list of all active branches.

- **URL:** `/get_branches_api.php`
- **Method:** `GET`

---

### Get Branch API
**File:** `get_branch_api.php`

Returns details of a specific branch.

- **URL:** `/get_branch_api.php`
- **Method:** `GET`

**Parameters:**
| Name | Type | Required | Description |
|------|------|----------|-------------|
| branch_id | int | Yes | Branch ID |

---

## QR Clock APIs

### QR Clock API
**File:** `qr_clock_api.php`

QR Code based clock-in/out system. Supports auto clock-out if already clocked in.

- **URL:** `/qr_clock_api.php`
- **Method:** `POST`
- **CORS:** Enabled

**Parameters:**
| Name | Type | Required | Description |
|------|------|----------|-------------|
| action | string | No | 'in' or 'out', defaults to 'in' |
| employee_id | int | Yes | Employee ID |
| employee_code | string | No | Employee code for verification |

**Response (Clock In):**
```json
{
  "success": true,
  "message": "John Doe time-in recorded at 08:30 AM",
  "time_in": "08:30 AM"
}
```

**Response (Auto Clock Out):**
```json
{
  "success": true,
  "message": "John Doe time-out recorded at 05:30 PM",
  "time_out": "05:30 PM",
  "auto_clock_out": true
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "Employee account is not active"
}
```

**Features:**
- Auto-detects if employee is already clocked in and triggers clock-out
- Validates employee status
- Returns formatted time strings

---

## Password Management APIs

### Change Password API
**File:** `change-password-api.php`

Changes employee password with validation and procurement system sync.

- **URL:** `/change-password-api.php`
- **Method:** `POST`
- **Headers:** `X-API-Key: qwertyuiopasdfghjklzxcvbnm`
- **Content-Type:** `application/json` or `application/x-www-form-urlencoded`

**Parameters:**
| Name | Type | Required | Description |
|------|------|----------|-------------|
| employee_code | string | Yes | Employee code |
| current_password | string | Yes | Current password |
| new_password | string | Yes | New password (min 6 chars) |
| confirm_password | string | Yes | Must match new_password |

**Response:**
```json
{
  "success": true,
  "message": "Password updated successfully",
  "data": {
    "employee_id": 1,
    "employee_code": "EMP-001",
    "password_updated": true,
    "procurement_sync": {
      "success": true,
      "message": "Password synced successfully"
    }
  }
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "Current password is incorrect"
}
```

**Features:**
- Supports both MD5 and bcrypt password verification
- Auto-upgrades MD5 to bcrypt on successful verification
- Syncs password to procurement system

---

### Change Password Receiver API
**File:** `change-password-receiver-api`

Node.js/Express endpoint for receiving password sync from attendance system (for procurement system).

- **URL:** `POST /api/sync-password`
- **Method:** `POST`
- **Headers:** `X-API-KEY: <SECRET_KEY>`

**Parameters:**
| Name | Type | Required | Description |
|------|------|----------|-------------|
| employee_id | int | Yes | Employee ID |
| new_password_hash | string | Yes | Bcrypt hashed password |

**Response:**
```json
{
  "status": "success",
  "message": "Sync successful!"
}
```

---

## Overtime APIs

### Set Attendance OT Hours API
**File:** `set_attendance_ot_hrs_api.php`

Updates overtime hours for an attendance record.

- **URL:** `/set_attendance_ot_hrs_api.php`
- **Method:** `POST/GET`
- **CORS:** Enabled

**Parameters:**
| Name | Type | Required | Description |
|------|------|----------|-------------|
| employee_id | int | Yes | Employee ID |
| ot_hours | string | Yes | OT hours (numeric only) |
| date | string | No | Date (YYYY-MM-DD), defaults to today |

**Response:**
```json
{
  "success": true,
  "message": "OT hours updated",
  "attendance_id": 123,
  "employee_id": 1,
  "date": "2024-01-15",
  "total_ot_hrs": "3"
}
```

---

## External Integration APIs

### Procurement API
**File:** `procurement-api.php`

Internal library for syncing data with external procurement system.

**Function:** `syncPasswordToProcurement($employee_no, $password)`

- **External URL:** `https://procurement-api.xandree.com/api/auth/sync-password/`
- **Method:** `POST`
- **Headers:** `x-api-key: qwertyuiopasdfghjklzxcvbnm`

**Returns:**
```php
[
  'success' => true,
  'message' => 'Password synced successfully'
]
```

**Function:** `logProcurementError($message, $context = [])`

Logs procurement sync errors to `/logs/procurement_sync.log`

---

## CORS Configuration

Most APIs with CORS enabled use the following headers:

```php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header('Content-Type: application/json');
```

---

## Error Response Format

Standard error responses follow this format:

```json
{
  "success": false,
  "message": "Error description here"
}
```

With HTTP status codes:
- `200` - Success
- `400` - Bad Request (missing/invalid parameters)
- `401` - Unauthorized (invalid API key)
- `404` - Not Found (employee/record not found)
- `405` - Method Not Allowed
- `500` - Internal Server Error

---

## Database Connection

All APIs use the shared database connection from:
- `conn/db_connection.php` (preferred)
- `db_connection.php` (fallback)

---

## File Structure

```
/main/
├── login_api.php
├── login_api_simple.php
├── time_in_api.php
├── time_out_api.php
├── clock_out_api.php
├── qr_clock_api.php
├── submit_attendance_api.php
├── get_shift_logs_api.php
├── get_attendance_absent_notes_api.php
├── mark_attendance_absent_api.php
├── employees_today_status_api.php
├── get_available_employees_api.php
├── set_employee_branch_api.php
├── transfer_branch_api.php
├── update_profile_api.php
├── get_branches_api.php
├── get_branch_api.php
├── set_attendance_ot_hrs_api.php
├── change-password-api.php
├── procurement-api.php
└── change-password-receiver-api (Node.js)
```

---

*Generated: February 2026*
