# JAJR Attendance System — System Flow Documentation

## 1) Purpose

This document describes the end-to-end **system flow** of the JAJR Attendance System (web app + JSON API endpoints), including:

- Entry points (pages and APIs)
- Authentication/session model
- Role-based access control (RBAC)
- Core workflows (attendance marking, reporting, logs)
- Data model (tables/fields inferred from queries)
- Key invariants and operational behaviors

## 2) High-Level Architecture

### 2.1 Runtime Environment

- **Web server**: Apache (WAMP)
- **Language**: PHP
- **Database**: MySQL (`attendance_db`)
- **DB access**: `mysqli` (procedural style) via `conn/db_connection.php`
- **Auth**: PHP session cookies (`session_start()`)
- **UI**: Server-rendered PHP pages with Tailwind/CSS assets
- **Mobile integration**: JSON endpoints with simple CORS headers

### 2.2 Component Map

- **Public pages**
  - `index.php` (landing)
  - `login.php` (web login)
  - `signup.php` (web signup)
  - `logout.php`

- **Web app (authenticated)**
  - `employee/` module (contains both “employee-facing” and “admin-facing” pages)
  - `admin/` module (currently: activity logs viewer)

- **API endpoints (JSON)**
  - `login_api.php` (mobile login)
  - `submit_attendance_api.php` (mobile attendance insert)
  - `get_available_employees_api.php` (mobile list employees eligible for attendance today)
  - `attendance_insert.php` (JSON insert helper; overlaps with submit endpoint)
  - `employee/api/clock_in.php`, `employee/api/clock_out.php` (shift time-in/out endpoints)

- **Shared**
  - `conn/db_connection.php` (DB connection)
  - `functions.php` (`logActivity()`)

## 3) Request/Response and Navigation Flow

### 3.1 Public Entry

1. User opens `index.php`
2. If session already contains `$_SESSION['employee_id']`, user is redirected:
   - `index.php` → `employee/dashboard.php`
3. Otherwise user clicks **Log In** → `login.php`

### 3.2 Web Login Flow (`login.php`)

**Input**: `identifier` (email or employee code), `password`, `branch_name` (daily branch selection)

**Steps**:

1. Validate fields.
2. Query `employees` by either `email` or `employee_code` where `status = 'Active'`.
3. Verify password: `md5($password) === employees.password_hash`.
4. Set session values (authoritative fields):

   - `$_SESSION['employee_id'] = employees.id`
   - `$_SESSION['employee_code']`
   - `$_SESSION['first_name']`, `$_SESSION['last_name']`, `$_SESSION['email']`
   - `$_SESSION['position']` (role)
   - `$_SESSION['logged_in'] = true`
   - `$_SESSION['daily_branch']` (selected branch for today)
   - `$_SESSION['assigned_branch']` (employee’s stored `branch_name`)

   Branch filtering session:

   - If `position == 'Super Admin'` → `$_SESSION['branch_name'] = 'all'`
   - Else → `$_SESSION['branch_name'] = employees.branch_name` (assigned branch)

5. Upsert attendance for today (`attendance_date = CURDATE()`):

   - If no record exists for employee today:
     - Insert attendance row with `status='Present'` and `branch_name = daily_branch`
   - Else:
     - Update today’s attendance row `branch_name = daily_branch`

6. Audit log:

   - `logActivity($db, 'Logged In', "User X logged in from branch: Y")`

7. Redirect:

   - `login.php` → `employee/select_employee.php`

### 3.3 Logout Flow (`logout.php`)

- Calls `session_destroy()`
- Redirects to `index.php`

## 4) Role-Based Access Control (RBAC)

Roles are represented via `$_SESSION['position']`.

Observed roles in code:

- `Employee`
- `Admin`
- `Super Admin`

### 4.1 UI-Level Filtering (Sidebar)

`employee/sidebar.php`:

- Computes `isAdmin = in_array($_SESSION['position'], ['Admin', 'Super Admin'])`
- Menu items:
  - Always visible:
    - `employee/select_employee.php` (Site Attendance)
    - `employee/employees.php` (Employee List)
    - `logout.php`
  - Admin/Super Admin only:
    - `employee/dashboard.php` (Dashboard)
    - `employee/weekly_report.php` (Reports)
    - `employee/billing.php`
    - `employee/documents.php`
    - `admin/logs.php` (Activity Logs)
    - `employee/settings.php`

### 4.2 Backend Page Guards

Some pages explicitly guard admin access. Example:

- `admin/logs.php`:
  - Requires `$_SESSION['logged_in']` and position in `['Admin','Super Admin']`

Other pages in `employee/` vary: some check only `logged_in`, some check admin-only. For consistent enforcement, admin-only pages should implement the same guard pattern.

## 5) Core Business Flows

## 5.1 “Site Attendance” Web Flow (`employee/select_employee.php`)

This page is used to manage who is marked present (and after cutoff, absent).

### 5.1.1 Page Initialization

- Requires authenticated session: `$_SESSION['logged_in'] === true`.
- Defines cutoff time:
  - `cutoffTime = '09:00'`
  - `isBeforeCutoff = (currentTime < cutoffTime)`
- Loads available branches:
  - `SELECT DISTINCT branch_name FROM employees WHERE branch_name IS NOT NULL AND branch_name != ''`

### 5.1.2 AJAX: Load Employees

Triggered when client posts `action=load_employees`.

**Inputs**:

- `branch` (required)
- `show_marked` (string: `true`/`false`)

**Cutoff behavior**:

- If after cutoff and auto-absent not yet applied today:
  - calls `applyAutoAbsent($db, today)`

**Employee selection queries** (note: current implementation is largely global and does not filter by the `branch` input in the SQL; branch selection is primarily a UI choice):

- If `show_marked=true`:
  - left join attendance for today
  - returns all active employees with their attendance status

- If `show_marked=false`:
  - before cutoff: returns only active employees with no attendance record today
  - after cutoff: returns active employees missing attendance or not present

**Output**:

- JSON with:
  - `employees[]` (id, code, name, position, branches, status flags)
  - cutoff and time flags

### 5.1.3 AJAX: Mark Present

Triggered when client posts `action=mark_present`.

**Inputs**:

- `employee_id`
- `branch` (used as `attendance.branch_name`)

**Logic**:

- If attendance exists for employee today:
  - Update to `status='Present'`, set `branch_name`, set `is_auto_absent=0`, update `updated_at`
- Else:
  - Insert today’s attendance with `status='Present'`, set `branch_name`, `is_auto_absent=0`

### 5.1.4 Auto-Absent System Behavior

`applyAutoAbsent($db, $date)`:

- Finds active employees without an attendance row for the given date.
- Inserts attendance rows for them:
  - `status='Absent'`
  - `is_auto_absent=1`
  - `branch_name = employees.branch_name`

It also inserts a “marker” row to indicate auto-absent has been applied:

- `employee_id = 0`, `status='System'`, `branch_name='System'`, `auto_absent_applied=1`

**Implication**: `attendance` contains both real employee rows and system marker rows.

## 5.2 Manual Attendance Admin Flow (`employee/attendance.php`)

Purpose: manually set attendance for a selected date (and optionally branch-filtered lists).

**Inputs**:

- `GET date` (defaults to today)
- `GET branch` (optional)
- `GET view` (`unmarked` or `marked`)

**Actions**:

- POST to insert or update attendance for a given date.
- Derives `branch_name` from `employees.branch_name`.
- Logs actions via `logActivity()`:
  - `Marked Attendance`
  - `Updated Attendance`

**Views**:

- `unmarked`: employees with no attendance row on selected date
- `marked`: employees with attendance row on selected date

## 5.3 Admin Dashboard Overview (`employee/dashboard.php`)

Used for aggregated counts:

- Total active employees
- Present/Absent today

It enforces:

- Must be logged in
- Redirects super admin to `admin/dashboard.php` (note: the file reference exists but wasn’t confirmed in this workspace listing)

## 5.4 Reports (“Deployment Report”) (`employee/weekly_report.php`)

Admin/Super Admin-only.

- Supports **weekly** and **monthly** views.
- Queries attendance rows with:
  - `attendance_date BETWEEN start_date AND end_date`
  - `status = 'Present'`

Outputs a matrix of:

- Date rows
- Branch columns
- Present employees listed per cell

Also supports:

- Printing
- CSV export (client-side generated)

## 5.5 Activity Logs (Audit Trail) (`admin/logs.php` + `functions.php`)

### 5.5.1 Logging

`functions.php` provides:

- `logActivity($db, $action, $details)`
  - reads `$_SESSION['employee_id']` if available
  - captures requester IP
  - inserts into `activity_logs`

### 5.5.2 Viewing

`admin/logs.php`:

- Admin/Super Admin-only
- Query joins:
  - `activity_logs al LEFT JOIN employees e ON al.user_id = e.id`
- Supports filtering:
  - user name/code search
  - action search
- Paginates results

## 6) Mobile/External API Flows

## 6.1 Mobile Login (`login_api.php`)

**Inputs** (JSON body or form POST):

- `identifier`
- `password`
- `branch_name` (daily branch)

**Output**:

- `success: true/false`
- `user_data` (id, employee_code, names, position, assigned_branch, daily_branch)

**Note**: This endpoint does not create PHP session state; it returns data for the mobile app.

## 6.2 Mobile “Available Employees” (`get_available_employees_api.php`)

**Purpose**: return employees of a branch who do NOT yet have attendance today.

**Inputs**: `branch_name` (GET/POST)

**Behavior**:

- Enables permissive CORS (`Access-Control-Allow-Origin: *`)
- Filters:
  - `employees.branch_name = ?`
  - `employees.status='Active'`
  - `employees.id NOT IN (SELECT employee_id FROM attendance WHERE attendance_date = today)`

**Output**: JSON array of employees.

## 6.3 Mobile Submit Attendance (`submit_attendance_api.php`)

**Inputs** (POST):

- `employee_id`
- `branch_name`

**Behavior**:

- Default status: `Present`
- Anti-duplicate check:
  - if attendance exists for employee+today, reject
- Insert attendance:
  - `employee_id, branch_name, attendance_date, status`

**Output**:

- success message and debug info

## 6.4 Clock In / Clock Out (`employee/api/clock_in.php`, `employee/api/clock_out.php`)

This flow treats attendance table like a **shift log**:

- `clock_in.php`
  - checks for existing row with `time_out IS NULL`
  - inserts `attendance(employee_id, time_in=NOW())`

- `clock_out.php`
  - requires `shift_id` and `employee_id`
  - updates `attendance.time_out=NOW()` for that row

**Important**: This is a separate conceptual model from the date-based `status Present/Absent` model. The system currently uses the same `attendance` table for both patterns.

## 7) Data Model (Inferred)

### 7.1 Database Connection

`conn/db_connection.php`:

- host: `localhost`
- user: `root`
- schema: `attendance_db`

### 7.2 Tables

#### 7.2.1 `employees`

Used fields in code:

- `id` (PK)
- `employee_code` (unique-ish identifier)
- `first_name`, `middle_name`, `last_name`
- `email`
- `password_hash` (MD5)
- `position` (role)
- `branch_name` (assigned branch)
- `status` ('Active' expected)
- `profile_image` (path)
- `created_at`, `updated_at` (implied)

#### 7.2.2 `attendance`

Observed fields in code:

- `id` (PK)
- `employee_id` (FK-ish to employees.id; also used with `0` for system marker)
- `attendance_date` (date)
- `status` ('Present', 'Absent', 'System')
- `branch_name`
- `created_at`, `updated_at`

Auto-absent fields:

- `is_auto_absent` (0/1)
- `auto_absent_applied` (0/1)

Shift/clock fields:

- `time_in` (datetime)
- `time_out` (datetime)

#### 7.2.3 `activity_logs`

Defined by `activity_logs_schema.sql`:

- `id` (PK)
- `user_id` (nullable)
- `action` (varchar)
- `details` (text)
- `ip_address`
- `created_at`

## 8) End-to-End Sequence Flows

### 8.1 Web: Login → Mark Attendance

1. `index.php` → `login.php`
2. Submit credentials + daily branch
3. `login.php`:
   - authenticate
   - write session
   - upsert today’s attendance as present
   - insert activity log
4. Redirect → `employee/select_employee.php`
5. User selects a branch (UI)
6. Browser POST (AJAX) `action=load_employees`
7. If after cutoff and not applied:
   - system inserts absent records for unmarked employees
8. User marks selected employee present:
   - Browser POST (AJAX) `action=mark_present`
   - attendance row inserted/updated

### 8.2 Mobile: List Eligible Employees → Submit Attendance

1. App calls `login_api.php` (gets user profile + daily branch)
2. App calls `get_available_employees_api.php?branch_name=X`
3. App selects employee and submits to `submit_attendance_api.php` (POST)
4. API rejects duplicates for the day, otherwise inserts attendance

### 8.3 Admin: View Audit Logs

1. Admin logs in via `login.php`
2. Navigates to `admin/logs.php`
3. Server checks role
4. Server queries `activity_logs` joined to `employees`
5. Admin filters/paginates

## 9) Operational Notes / Invariants

- **Passwords**: stored as MD5 (`employees.password_hash`).
- **Attendance “today present”** can be created automatically on login (`login.php`).
- **Auto-absent** after cutoff is implemented by inserting rows into `attendance`.
- **System marker row** for auto-absent uses `employee_id=0` and `status='System'`.
- **Attendance table is dual-purpose**:
  - day-based presence (`attendance_date` + `status`)
  - shift-based clock-in/out (`time_in/time_out`)

## 10) Module/Route Index (Quick Reference)

### Public

- `GET /attendance_web/` → `index.php`
- `GET|POST /attendance_web/login.php`
- `GET|POST /attendance_web/signup.php`
- `GET /attendance_web/logout.php`

### Authenticated (Web)

- `GET /attendance_web/employee/select_employee.php`
  - `POST action=load_employees` (JSON)
  - `POST action=mark_present` (JSON)
- `GET|POST /attendance_web/employee/attendance.php` (manual)
- `GET /attendance_web/employee/dashboard.php` (admin overview)
- `GET /attendance_web/employee/weekly_report.php` (admin)
- `GET|POST /attendance_web/employee/employees.php` (CRUD-ish)
- `GET|POST /attendance_web/employee/settings.php` (profile, password, backups)
- `GET /attendance_web/admin/logs.php` (admin)

### JSON APIs (Mobile/External)

- `POST /attendance_web/login_api.php`
- `GET|POST /attendance_web/get_available_employees_api.php`
- `POST /attendance_web/submit_attendance_api.php`
- `POST /attendance_web/attendance_insert.php`
- `POST /attendance_web/employee/api/clock_in.php`
- `POST /attendance_web/employee/api/clock_out.php`
