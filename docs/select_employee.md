# Select Employee (Project Selection & Attendance)

## Overview

The `select_employee.php` page serves as the main hub for **project deployment selection** and **employee attendance management**. It allows administrators to select a project/branch, view employees assigned to that project, and mark attendance (time-in/time-out) or absences.

**File Location:** `c:\wamp64\www\main\employee\select_employee.php`

---

## Features

### 1. QR Scan Auto Time-In/Out
- **Auto-authentication**: QR scans create temporary sessions for employees
- **Dual-mode operation**: 
  - First scan: Records time-in
  - Second scan: Records time-out (if already clocked in)
- **Direct function calls**: Uses `performClockIn()` and `performClockOut()` without HTTP/cURL
- **Result display**: Shows success/error banners with employee name and timestamp

### 2. Project (Branch) Selection
- **Project grid**: Displays all available deployment projects
- **Search projects**: Real-time filtering of project list
- **Add project**: Super Admin can add new projects via modal
- **Delete project**: Remove projects with confirmation
- **Auto-select**: QR scans automatically select the employee's assigned project

### 3. Employee Attendance Management
- **Status filters**:
  - Available (default)
  - Summary (all)
  - Present
  - Absent
- **Search employees**: Filter by name or employee ID
- **Pagination**: Configurable page sizes (10, 25, 50, 100)
- **Undo functionality**: Global undo button to reverse last action
- **Branch statistics**: Real-time counts of total workers, present, and absent

### 4. Session Management
- **Authentication check**: Validates `$_SESSION['logged_in']`
- **AJAX-aware**: Returns JSON for expired sessions on AJAX requests
- **QR temp sessions**: Special handling for QR scan requests with `qr_temp_session` flag

---

## Dependencies

### Required Files
| File | Purpose |
|------|---------|
| `conn/db_connection.php` | Database connection |
| `function/attendance.php` | Attendance helper functions |
| `function/clock_functions.php` | Clock in/out functions |
| `sidebar.php` | Navigation sidebar |

### External Resources
- **Google Fonts**: Inter (400, 600, 700, 800)
- **Font Awesome**: 6.4.0 for icons
- **Stylesheets**:
  - `assets/css/style.css`
  - `css/select_employee.css`
  - `css/light-theme.css`
- **Scripts**:
  - `js/theme.js`
  - `assets/js/sidebar-toggle.js`
  - `js/attendance.js`

---

## URL Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `auto_timein` | int (0/1) | Trigger QR scan auto time-in mode |
| `emp_id` | int | Employee ID for QR scan |
| `emp_code` | string | Employee code for QR scan |

---

## Database Interactions

### Tables Accessed
- `employees` - Employee details and branch assignment
- `branches` - Project/branch information

### Key Queries
1. **Employee lookup by ID** (for QR scan)
   ```sql
   SELECT e.id, e.first_name, e.last_name, e.employee_code, b.branch_name 
   FROM employees e 
   LEFT JOIN branches b ON b.id = e.branch_id 
   WHERE e.id = ? LIMIT 1
   ```

2. **Employee branch lookup** (for QR auto-select)
   ```sql
   SELECT b.branch_name 
   FROM employees e 
   LEFT JOIN branches b ON b.id = e.branch_id 
   WHERE e.id = ? LIMIT 1
   ```

---

## JavaScript Configuration

The page exposes global configuration objects:

```javascript
window.attendanceConfig = {
  isBeforeCutoff: boolean,
  cutoffTime: string,
  currentTime: string
};

window.branchesFromPHP = Array; // All branches data

window.qrScanData = {
  enabled: boolean,
  employeeBranch: string
};
```

---

## Role-Based Access

| Feature | Super Admin | Other Roles |
|---------|-------------|-------------|
| Add Project | ✅ | ❌ |
| Delete Project | ✅ (via JS) | ❌ |
| Mark Attendance | ✅ | ✅ |
| View Statistics | ✅ | ✅ |

---

## How to Request Overtime

### Overview
The overtime system allows employees to request overtime hours and administrators to approve/reject them. Overtime requests are stored in the `overtime_requests` table and linked to the `attendance` table.

### Database Schema

**overtime_requests Table:**
```sql
CREATE TABLE IF NOT EXISTS `overtime_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `branch_name` varchar(255) NOT NULL,
  `request_date` date NOT NULL,
  `requested_hours` decimal(5,2) NOT NULL,
  `overtime_reason` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `requested_by` varchar(255) NOT NULL,
  `requested_by_user_id` int DEFAULT NULL,
  `requested_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `approved_by` varchar(255) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text,
  `attendance_id` int DEFAULT NULL,
  PRIMARY KEY (`id`)
);
```

### Overtime Request Workflow

```
1. Employee submits request (hours + reason)
          ↓
2. Request status = 'pending'
          ↓
3. Administrator reviews
          ↓
    ┌─────┴─────┐
    ↓           ↓
 Approved     Rejected
    ↓           ↓
 Hours added  Reason stored
 to attendance
```

### API Endpoints

#### 1. Submit Overtime Request
**Endpoint:** `POST /employee/api/overtime_request.php`

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `employee_id` | int | Employee's database ID |
| `employee_code` | string | Employee's unique code |
| `branch_name` | string | Employee's assigned branch |
| `requested_hours` | decimal | Number of overtime hours (e.g., 2.5) |
| `overtime_reason` | text | Reason for requesting overtime |

**Example Request:**
```javascript
const formData = new FormData();
formData.append('employee_id', 24);
formData.append('employee_code', 'E0014');
formData.append('branch_name', 'BCDA - Admin');
formData.append('requested_hours', 3.5);
formData.append('overtime_reason', 'Project deadline');

fetch('https://jajr.xandree.com/employee/api/overtime_request.php', {
  method: 'POST',
  headers: { 'X-Requested-With': 'XMLHttpRequest' },
  body: formData
});
```

**Success Response:**
```json
{
  "success": true,
  "message": "Overtime request submitted successfully",
  "request_id": 123
}
```

#### 2. Get Overtime Requests
**Endpoint:** `GET /employee/api/get_overtime_requests.php`

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `employee_id` | int | Filter by employee (optional) |
| `status` | string | Filter by status: pending/approved/rejected (optional) |
| `start_date` | date | Date range start (optional) |
| `end_date` | date | Date range end (optional) |

#### 3. Approve/Reject Request
**Endpoint:** `POST /employee/api/approve_overtime.php`

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `request_id` | int | Overtime request ID |
| `action` | string | 'approve' or 'reject' |
| `rejection_reason` | text | Required if rejecting |
| `approved_by` | string | Name of approver |

### Business Rules

1. **Maximum Hours**: Typically 4 hours overtime per day (in addition to 8 regular hours)
2. **Approval Required**: All requests must be approved by an administrator
3. **Date Validation**: Cannot request overtime for future dates
4. **Duplicate Prevention**: One request per employee per day
5. **Reason Required**: All requests must include a valid reason

### Common Error Responses

```json
{ "success": false, "message": "Duplicate overtime request for this date" }
```

```json
{ "success": false, "message": "Requested hours exceed maximum allowed (4 hours)" }
```

```json
{ "success": false, "message": "Cannot request overtime for future dates" }
```

### Related Files

| File | Purpose |
|------|---------|
| `employee/notification.php` | Handles overtime approval notifications |
| `employee/function/attendance.php` | Updates attendance with overtime hours |
| `employee/weekly_report.php` | Calculates overtime pay |

---

## Related Files

| File | Description |
|------|-------------|
| `function/clock_functions.php` | `performClockIn()`, `performClockOut()` functions |
| `js/attendance.js` | Frontend attendance management logic |
| `css/select_employee.css` | Page-specific styles |

---

## Usage Flow

1. **Normal Access**: Login → Select Project → View Employees → Mark Attendance
2. **QR Scan**: Scan QR Code → Auto Time-In/Out → Display Result → Auto-select Project

---

## Notes

- Time zone is hardcoded to `Asia/Manila` (Philippine Time, UTC+8)
- QR scans bypass normal login by creating temporary authenticated sessions
- The page uses a dark theme with gold/yellow (`#FFD700`) accent colors
- Philippine time cutoff at 9:00 AM logic exists but is commented out in the HTML
