# Employee Selection & Attendance Management Documentation

## Overview

`employee/select_employee.php` is the core attendance management interface for the JAJR Attendance System. It provides a comprehensive dashboard for supervisors and administrators to manage employee attendance across different deployment projects (branches).

---

## File Location

```
c:\wamp64\www\main\employee\select_employee.php
```

---

## Features

### 1. QR Scan Auto Time-In/Out
- **Automatic Authentication**: Creates temporary session for QR scan requests
- **Dual Action**: Automatically handles both time-in and time-out based on current status
- **Direct Function Calls**: Uses `performClockIn()` and `performClockOut()` without HTTP/cURL
- **Result Display**: Shows success/error banner after scan

### 2. Project/Branch Selection
- **Project Grid**: Visual card-based selection of deployment projects
- **Search**: Real-time filtering of projects by name
- **Add Project**: Super Admin can create new projects with name, order number, and address
- **Delete Project**: Remove unused projects (with usage validation)
- **Auto-Select**: After QR scan, automatically selects the employee's assigned project

### 3. Employee Management
- **Status Filters**:
  - Available (default) - Shows unmarked employees
  - Summary - Shows all employees with status
  - Present - Shows clocked-in employees
  - Absent - Shows marked absent employees
- **Search**: Real-time search by employee name or ID
- **Pagination**: Configurable page sizes (10, 25, 50, 100)

### 4. Attendance Actions
- **Time In**: Records clock-in with Philippine timezone
- **Time Out**: Records clock-out and calculates hours worked
- **Mark Absent**: Marks employee as absent with optional notes
- **Undo**: Global and per-employee undo functionality
- **Overtime**: View and manage overtime hours
- **Transfer**: Move employees between projects

### 5. Real-Time Statistics
- **Total Workers**: Count of active employees in selected project
- **Present**: Employees currently clocked in
- **Absent**: Employees marked absent today
- **Name Lists**: Expandable lists showing employee names for each status

---

## Dependencies

### Required Files
| File | Purpose |
|------|---------|
| `../conn/db_connection.php` | Database connection |
| `function/attendance.php` | Attendance helper functions and AJAX handlers |
| `function/clock_functions.php` | Clock-in/out core logic |
| `sidebar.php` | Navigation sidebar |
| `../assets/css/style.css` | Global styles |
| `css/select_employee.css` | Page-specific styles |
| `css/light-theme.css` | Light/dark theme support |
| `js/theme.js` | Theme switching |
| `js/attendance.js` | Main JavaScript functionality |
| `../assets/js/sidebar-toggle.js` | Sidebar toggle |

---

## Database Schema Usage

### Tables Accessed

| Table | Usage |
|-------|-------|
| `employees` | Employee data, branch assignments |
| `branches` | Project/branch definitions |
| `attendance` | Clock-in/out records |
| `overtime_requests` | Approved overtime hours |

### Key Columns Used
- `attendance.time_in`, `attendance.time_out` - Clock times
- `attendance.is_time_running` - Active shift flag
- `attendance.is_overtime_running` - Overtime flag
- `attendance.total_ot_hrs` - Overtime hours
- `attendance.status` - Present/Absent status
- `employees.branch_id` - Current assignment

---

## Session Management

### Standard Session
```php
$_SESSION['logged_in'] = true;
$_SESSION['employee_id'] = int;
$_SESSION['employee_code'] = string;
$_SESSION['position'] = string;
$_SESSION['role'] = string;
```

### QR Temporary Session
```php
$_SESSION['qr_temp_session'] = true; // Marks session as temporary
```

---

## AJAX Endpoints (via attendance.php)

### POST Actions

| Action | Parameters | Description |
|--------|------------|-------------|
| `load_employees` | branch, status_filter, page, per_page, search_term | Load paginated employee list |
| `mark_absent` | employee_id, branch_name | Mark employee absent |
| `undo_absent` | employee_id | Undo absent marking |
| `get_shift_logs` | employee_id, limit | Get today's time logs |
| `add_branch` | branch_name, branch_address, order_number | Create new project |
| `delete_branch` | branch_id | Delete project |

---

## Clock Functions Reference

### performClockIn($db, $employeeId, $employeeCode, $branchName)
**Returns**: Array with `success`, `message`, `time_in`, `shift_id`, `auto_transferred`

**Features**:
- Validates branch and auto-transfers employee if needed
- Checks for existing clock-in to prevent duplicates
- Updates existing empty attendance rows
- Handles various database schema configurations
- Captures approved overtime hours

### performClockOut($db, $employeeId, $employeeCode, $branchName)
**Returns**: Array with `success`, `message`, `time_out`, `hours_worked`

**Features**:
- Calculates hours worked from time-in to time-out
- Handles cross-branch clock-outs with logging
- Updates running flags and totals

---

## JavaScript Configuration

### Global Config Object
```javascript
window.attendanceConfig = {
  isBeforeCutoff: boolean,    // Before 9:00 AM
  cutoffTime: string,         // "09:00"
  currentTime: string       // Current PH time
};

window.branchesFromPHP = array;  // Available branches

window.qrScanData = {
  enabled: boolean,
  employeeBranch: string    // Auto-select branch after QR scan
};
```

---

## Key JavaScript Functions

### Employee Management
| Function | Purpose |
|----------|---------|
| `loadEmployees(branch, page, perPage, filter, search)` | Load employee data via AJAX |
| `reloadEmployees()` | Refresh current view |
| `renderEmployees(employees)` | Render employee table |
| `updateBranchStats(summary)` | Update statistics cards |

### Attendance Actions
| Function | Purpose |
|----------|---------|
| `toggleShift(empId, empName)` | Clock in or out |
| `markAbsent(empId, empName)` | Mark as absent |
| `undoLastAction(empId, empName)` | Undo last action |
| `performClockIn(empId, empName, branch)` | AJAX clock-in |
| `performClockOut(empId, shiftId, empName)` | AJAX clock-out |

### UI Functions
| Function | Purpose |
|----------|---------|
| `updatePaginationControls()` | Update pagination UI |
| `changePageSize(size)` | Change items per page |
| `jumpToPage()` | Go to specific page |
| `toggleEmployeeMenu(menuId, empId)` | Show/hide kebab menu |
| `openTimeLogsModal(empId, empName)` | View time logs |
| `showOvertimeModal(...)` | Manage overtime |
| `showTransferDropdown(...)` | Transfer employee |

---

## CSS Classes Reference

### Layout
- `.app-shell` - Main application container
- `.main-content` - Content area
- `.branch-selection` - Project selection section
- `.branch-grid` - Project cards container
- `.branch-card` - Individual project card

### Components
- `.stat-card` - Statistics display
- `.stat-label`, `.stat-value` - Stat text
- `.stat-list` - Expandable employee lists
- `.employee-table-wrap` - Table container
- `.employee-table` - Employee data table
- `.employee-cell` - Employee info cell
- `.employee-avatar` - Initials avatar

### Actions
- `.btn-present` - Time In button
- `.btn-present-late` - Time Out button (active shift)
- `.btn-absent` - Mark Absent button
- `.kebab-menu` - Options dropdown
- `.kebab-dropdown` - Dropdown menu
- `.kebab-item` - Menu item

### Pagination
- `.pagination-container` - Pagination wrapper
- `.pagination-info` - "Showing X to Y of Z"
- `.pagination-controls` - Buttons container
- `.page-size-selector` - Items per page dropdown
- `.page-jump` - Go to page input

---

## Time Zone Handling

All times use **Philippine Time (Asia/Manila, UTC+8)**:

```php
date_default_timezone_set('Asia/Manila');
```

### Cutoff Time
- **9:00 AM** is the daily cutoff
- Before cutoff: Encourages marking attendance
- After cutoff: Unmarked employees auto-marked as absent

---

## Security Features

1. **Authentication Check**: Verifies `$_SESSION['logged_in']`
2. **AJAX Detection**: Returns JSON for XHR requests, redirect for regular
3. **Super Admin Authorization**: Restricts add/delete project actions
4. **Prepared Statements**: All database queries use parameter binding
5. **Output Escaping**: `htmlspecialchars()` for all dynamic output
6. **CSRF Protection**: Session-based validation

---

## Error Handling

### PHP Error Handling
```php
// JSON error response for AJAX
jsonFail($message, $extra = []);

// Exception handler for fatal errors
set_exception_handler(...);
register_shutdown_function(...);
```

### JavaScript Error Handling
- Network errors show user-friendly messages
- Console logging for debugging
- Graceful degradation for missing elements

---

## Usage Flow

### Standard Attendance Flow
1. User selects a **deployment project**
2. System loads employees for that project
3. User can:
   - **Time In** employee (creates attendance record)
   - **Time Out** employee (closes shift, calculates hours)
   - **Mark Absent** (with optional notes)
4. Statistics update in real-time

### QR Scan Flow
1. QR code scanned with `?auto_timein=1&emp_id=X`
2. System creates temporary session
3. `performClockIn()` called directly
4. If already clocked in → auto `performClockOut()`
5. Result displayed as banner
6. Project auto-selected based on employee assignment

---

## Browser Compatibility

- **Chrome/Edge**: Full support
- **Firefox**: Full support
- **Mobile browsers**: Responsive design supported
- **Requirements**: JavaScript enabled, modern browser (ES6+)

---

## Related Documentation

- `ATTENDANCE_FIX_REPORT.md` - Recent attendance fixes
- `BRANCH_MANAGEMENT_CODE_BLOCKS.txt` - Branch management code reference
- `CLOCK_FUNCTIONS_DOCUMENTATION.md` - Clock function details
- `SIDEBAR_DOCUMENTATION.md` - Navigation documentation
- `THEME_FIX_REPORT.md` - UI theme implementation

---

## File History

| Date | Change |
|------|--------|
| 2025-03 | Added QR scan auto time-in/out |
| 2025-02 | Implemented pagination and search |
| 2025-01 | Added branch statistics cards |
| 2024-12 | Merged attendance functions |
| 2024-11 | Initial employee selection interface |

---

## Maintenance Notes

### Rate Limiter
```php
$rateLimitEnabled = false; // Set to true to enable
$rateLimitWindow = 60;     // Seconds
$rateLimitMaxRequests = 30;
```

### Debug Mode
Press `Ctrl+Shift+D` to show debug info panel with:
- User role and position
- Current time
- Timezone settings

### Database Column Detection
The system auto-detects available columns in the attendance table for backward compatibility with different schema versions.
