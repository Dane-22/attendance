# worklog.md

## Work Log for Attendance System

---

### 2026-04-06

**Task:** Implement Unified Header Notifications with Consecutive Late/Absent Integration

**Status:** ✅ Completed

**Feature:** Unified header component with notification dropdown showing both regular notifications and consecutive late/absent workers

**Files Created:**
- `employee/header.php` - Unified header component with:
  - Dynamic page title and icon based on variables
  - Profile section linking to settings.php
  - Notification bell with dropdown card
  - "Mark all as read" functionality
  - Consecutive late/absent workers section for Admin/Engineer roles
- `employee/mark_all_notifications_read.php` - AJAX endpoint to mark all notifications as read
- `employee/cron/consecutive_attendance_check.php` - Automated script to detect and notify on consecutive attendance issues

**Files Modified:**
- `employee/css/dashboard.css` - Added styles for:
  - Unified header component
  - Notification dropdown card with tabs
  - Mark all as read button
  - Consecutive late/absent section with yellow warning styling
- `employee/dashboard.php` - Replaced old top-navbar with unified header include
- `employee/eng_dashboard.php` - Replaced old top-navbar with unified header include
- `employee/settings.php` - Replaced old header with unified header include

**Features:**
- Notification bell shows popup card with tabs for "All" and "Unread"
- "Mark all as read" button updates all notifications and hides badge
- Consecutive late/absent workers (3+ days) shown at top of dropdown
- Links to audit.php when clicking consecutive attendance notifications
- Role-based notification destinations (notification.php, admin_notification.php, my_notifications.php)
- Real-time notification count badge on bell icon
- Consistent header across all employee-facing pages

---

### 2026-04-06

**Task:** Fix QR scanner recording wrong branch in attendance

**Status:** ✅ Completed

**Problem:** Worker selected "Sto. Rosario" in QR scanner but attendance audit showed "BCDA - Admin" (employee's assigned branch)

**Root Cause:** `employee/api/qr_clock.php` was ignoring `branch_id` and `branch_name` POST parameters and always using the employee's assigned branch from the database.

**Files Modified:**
- `employee/api/qr_clock.php` - Fixed to use selected branch from QR scanner
  - Now reads `$_POST['branch_id']` and `$_POST['branch_name']` first
  - Falls back to employee's assigned branch only if not provided
  - Lines 33-51 updated with proper branch parameter handling

**Files Created:**
- `docs/QR_SCANNING_FLOW.md` - Updated with new login.php QR scanner flow documentation
  - Added section on "New QR Scanner Flow (login.php) with Branch Selection"
  - Documented the "Wrong Branch Recorded" bug and fix
  - Added geofence troubleshooting section
- `check_branch_location.php` - Diagnostic tool for branch geofence settings

**Testing:**
- Verified branch parameters are sent correctly from login.php QR scanner
- API now correctly records selected branch (e.g., Sto. Rosario) instead of defaulting to employee's branch

---

### 2026-04-06 (Update)

**Task:** Implement Consecutive Attendance Notifications for Admin and Engineer

**Status:** ✅ Completed

**Feature:** Automated notification system that alerts Admin and Engineer position users when workers have 3+ consecutive days of Late/Absent attendance.

**Requirements:**
- Threshold: 3 consecutive days with status `Late` or `Absent`
- Workdays: Monday-Saturday (Sundays excluded)
- Monitor: Only 'Worker' position employees
- Notify: 'Admin' and 'Engineer' position users
- Frequency: Once per streak (no spam)

**Files Created:**
- `employee/cron/consecutive_attendance_check.php` - Main scheduled script
  - Runs daily at 9:30 AM via cron
  - Detects consecutive attendance issues across Mon-Sat workdays
  - Sends push notifications to Admin and Engineer users
  - Uses `attendance_notification_log` table for deduplication
  - Logs all activity to `activity_logs`
- `docs/CONSECUTIVE_ATTENDANCE_NOTIFICATIONS.md` - Complete documentation
  - Installation instructions
  - Cron setup guide
  - Manual testing procedures
  - Troubleshooting guide
  - SQL queries for verification

**Database:**
- Auto-creates `attendance_notification_log` table on first run
- Stores notification history to prevent duplicate alerts
- Tracks: employee_id, issue_count, issue_dates, latest_issue_date

**Notification Format:**
- Title: "Attendance Alert: Consecutive Issues"
- Message: Worker name, employee code, consecutive days count, dates with statuses, branch
- URL: Links to audit page filtered by employee code

**Cron Setup:**
```bash
30 9 * * * cd /var/www/jajr-project && php employee/cron/consecutive_attendance_check.php
```

---

### 2026-04-01

**Task:** Implement "Noted" direct approval workflow and fix branch selection detection

**Status:** ✅ Completed

**Files Created:**
- `docs/APPROVAL_WORKFLOW.md` - Complete documentation for overtime and cash advance approval system
  - Covers request types, status workflow, AJAX API endpoints, database schema
  - UI components, notification system, activity logging
- `docs/PRE_APPROVAL_WORKFLOW.md` - Documentation for pre-approval multi-level workflow
  - Status flow diagrams, use cases, code examples
  - Database implementation, notification flow

**Files Modified:**
- `login.php` - Fixed branch selection detection for QR scanner
  - Changed confirm button to use `selectedBranchData` variable instead of DOM style searching
  - Resolves "Please select a branch first" error after location verification
- `employee/notification.php` - Removed Pre-Approved tab, changed button to "Noted"
  - Updated pending count functions to exclude pre_approved status
  - Changed button labels from "Approve"/"Final Approve" to "Noted"
  - Removed Pre-Approved filter tab
- `employee/admin_notification.php` - Converted pre-approval to direct approval
  - Changed badge from "Can Pre-Approve" to "Can Approve" (green)
  - Removed Pre-Approved tab from filter options
  - Button label changed from "Pre-Approve" to "Noted"
  - PHP action `pre_approve_request` → `approve_request` (direct approval)
  - PHP action `pre_approve_cash_advance` → `approve_cash_advance` (direct approval)
  - Attendance record now created/updated on approval
  - Employee receives immediate approval notification

**Features:**
- Admin clicking "Noted" immediately marks request as approved (no secondary approval)
- Single-step approval workflow for both overtime and cash advance
- Branch selection in QR scanner now works reliably after location verification
- Documentation for approval system architecture

---

### 2026-03-31

**Task:** Create QA Automation Test Suite for Geolocation Phase 2

**Files Created:**
- `test/geo_mock.js` - JavaScript GPS mocking utility for browser testing
  - Overrides navigator.geolocation.getCurrentPosition
  - Methods: enable(), disable(), setLocation(), setSpoofedTimestamp()
  - Preset test functions: testInsideGeofence(), testOutsideGeofenceWorker(), testSpoofedTimestamp()
- `test/test_geofence_logic.php` - Backend PHP unit testing suite
  - 5 automated test cases: Inside-Worker, Outside-Worker, Outside-Manager, Spoofed-Timestamp, Low-Accuracy
  - Simulates geofence validation logic without production code modification
  - CLI and browser compatible with JSON output option (?format=json)
  - Configured for Branch 21 (BCDA - Admin): 16.5969775, 120.3077657, 250m radius
- `test/check_logs.sql` - Database verification script
  - Queries geofence_violations, activity_logs, manager_overrides, location_logs
  - Includes logical checks with PASS/FAIL indicators
  - Verification sections: Violations, Activity Logs, Overrides, Location Logs
- `test/QA_TEST_SUITE.md` - Complete QA documentation
  - Test coverage matrix, usage instructions, troubleshooting guide
  - Configuration guide for employee IDs and branch settings

**Testing Results:**
- Fixed SQL reserved keyword issue (`long` → `` `long` ``)
- Updated branch configuration to actual database values
- All test infrastructure ready for validation

**Notes:**
- Run tests: `php test/test_geofence_logic.php`
- Web mock: Include geo_mock.js in login.php, use GeoMock.enable()
- Database verification: Run check_logs.sql in PHPMyAdmin

---

### 2026-03-31 (Update)

**Task:** Create comprehensive geolocator technical documentation

**Files Created:**
- `docs/HOW_GEOLOCATOR_WORKS.md` - Complete technical documentation
  - Haversine formula explanation with code
  - Validation flow: Browser GPS → Server validation → Response
  - Enforcement modes: Hard vs Soft based on employee role
  - Anti-spoofing mechanisms (timestamp validation, device fingerprinting)
  - Manager override workflow with audit trail
  - API endpoint documentation with request/response examples
  - QA test suite usage guide
  - Troubleshooting common issues
  - Configuration guides for radius, thresholds, and roles

**Features:**
- Complete technical reference for geofencing system
- Security considerations and best practices
- Database schema documentation
- Map dashboard visualization explained

---

### 2026-03-27

**Task:** Review login.php and create documentation file. Initialize worklog.md.

**Files Created:**
- `docs/LOGIN_DOCUMENTATION.md` - Complete documentation for login.php
- `worklog.md` - This worklog file for tracking all updates

**Notes:**
- Worklog system initialized to track all future updates to the codebase.
- Login system documentation covers authentication flow, session management, QR scanner feature, and procurement API integration.

---

### 2026-03-27 (Update)

**Task:** Implement confirmation dialogs for QR scanner time-in/out

**Files Modified:**
- `login.php` - Added confirmation dialog before processing QR scans
  - Added `checkAttendanceStatus()` function to check employee status
  - Added `showConfirmation()` function for modal dialog with:
    - Time-in: "Are you sure you want to time in [worker]?"
    - Time-out: "Done for today, are you sure?"
  - Updated scan handler to pause, check status, show confirmation, then proceed
- `employee/api/qr_clock.php` - Added 'check' action to return status without modifying data

**Features:**
- Visual confirmation with sun (☀️) emoji for time-in, moon (🌙) for time-out
- Green button for time-in confirmation, red button for time-out
- Cancel option resumes scanning
- Escape key or backdrop click cancels dialog

---

### 2026-03-27 (Update)

**Task:** Implement clickable employee lists in branch-stats section

**Files Modified:**
- `employee/js/attendance.js` - Added click-to-show functionality for employee lists
  - Commented out auto-render calls in `updateBranchStats()` to hide lists by default
  - Added click handlers to Present/Absent stat numbers
  - Implemented `toggleStatList(type)` function to show/hide lists on click
  - Implemented `expandStatList(type)` function for "show more" functionality
  - Lists show first 5 employees with "and X more" button to expand
  - "show less" button collapses expanded lists
  - Stored employee data in global variables (`window._presentEmployees`, `window._absentEmployees`)
- `employee/css/select_employee.css` - Added hover effects for clickable stat values
  - Scale transform (1.1x) on hover
  - Gold glow text-shadow effect
  - Smooth transition animation

**Features:**
- Employee name lists hidden by default in branch-stats
- Click stat number (e.g., "9 Present") to toggle list visibility
- Click again to hide the list
- "and X more" button shows all employees when list > 5 items
- "show less" button returns to collapsed view

---

### 2026-03-27 (Update)

**Task:** Remove top pagination and fix JavaScript errors

**Files Modified:**
- `employee/select_employee.php` - Removed top pagination container
  - Deleted `paginationTop` div with page info, page size selector, and pagination buttons
  - Kept only bottom pagination (`paginationBottom`) for cleaner UI
  - Updated cache-busting version from `?v=2` to `?v=3` to force fresh JavaScript load
- `employee/js/attendance.js` - Fixed JavaScript errors from removed elements
  - Updated `showPagination()` to only reference `paginationBottom`
  - Updated `hidePagination()` to only reference `paginationBottom`
  - Made `showPaginationLoading()` a no-op (was tied to removed top pagination)
  - Updated `updatePaginationControls()` to remove references to `paginationFrom`, `paginationTo`, `paginationTotal`, `paginationButtonsTop`
  - Bottom pagination now shows only: "Page X of Y" with controls

**Notes:**
- Browser was caching old JS (`?v=2`) causing null reference errors
- Cache-busting version bump ensures clients get the fixed code
- UI is cleaner with single pagination at bottom of employee list

---

### 2026-03-27 (Update)

**Task:** Implement QR scanner time-based access control

**Files Modified:**
- `login.php` - Added time restriction for QR scanner
  - Added PHP time check: scanner enabled at 6:40 AM (20 min before 7:00 AM work start)
  - Added `data-scanner-enabled` attribute to QR button with server-side value
  - Modified `openModal()` JavaScript to check enabled state before opening
  - Shows alert "QR scanner is only available from 6:40 AM onwards" when disabled
  - Added visual feedback: reduced opacity and `cursor-not-allowed` when before 6:40 AM

**Features:**
- QR scanner disabled before 6:40 AM
- Alert message when user tries to open scanner before allowed time
- Visual indication (dimmed button) when scanner is unavailable
- Server-side time validation prevents bypassing the restriction

---

### 2026-03-27 (Update)

**Task:** Improve pagination interface styling

**Files Modified:**
- `employee/css/select_employee.css` - Enhanced pagination layout
  - Added container styling with background, border, and padding
  - Improved spacing between pagination elements
  - Enhanced button styling with better hover effects
  - Fixed page-jump alignment to stay inline with controls
  - Improved responsive layout for mobile devices

**Features:**
- Cleaner, more compact pagination layout
- Better visual separation from employee list
- Improved button hover states with gold accent
- Responsive design maintains usability on mobile

---

### 2026-03-27 (Update)

**Task:** Make pagination fit on single line for mobile

**Files Modified:**
- `employee/css/select_employee.css` - Mobile pagination layout
  - Changed flex-direction from column to row for single-line layout
  - Hidden page-size-selector ("Show: X" dropdown) on mobile to save space
  - Reduced font sizes, padding, and button sizes for compact layout
  - All pagination elements now fit on one horizontal line

**Features:**
- Mobile pagination displays as: "Page X of Y [buttons] [Go input]"
- More compact design frees up vertical space
- Maintains full functionality on small screens

---

### 2026-03-27 (Update)

**Task:** Exclude "main branch" and "main office" from Excel export

**Files Modified:**
- `employee/export_attendance_excel.php` - Added branch exclusion filter
  - Added SQL condition to exclude branches containing "main branch" or "main office" (case-insensitive)
  - Added secondary safety check in output loop to skip these branches

**Features:**
- Excel export now automatically filters out "main branch" and "main office" records
- Case-insensitive matching ensures all variations are caught
- Double protection: SQL filter + output loop check

---

### 2026-03-27 (Update)

**Task:** Implement proper Excel export using PhpSpreadsheet to eliminate format warning

**Files Modified:**
- `composer.json` - Added PhpSpreadsheet dependency and disabled security blocking
- `employee/export_attendance_excel.php` - Complete rewrite using PhpSpreadsheet
  - Uses native .xlsx format (application/vnd.openxmlformats-officedocument.spreadsheetml.sheet)
  - Same color-coded formatting: gold headers, green names, blue times, orange status
  - Proper cell borders and alignment
  - Same branch exclusion for "main branch" and "main office"

**Features:**
- No more Excel format warning dialog
- Native Excel file that opens directly without security prompts
- Maintains all existing functionality and visual styling

---

### 2026-03-28 (Update)

**Task:** Make profile image clickable in all filter options

**Files Modified:**
- `employee/js/attendance.js` - Modified renderEmployees function
  - Removed `isSummaryView` conditional from avatar click handler
  - Profile image now clickable in all filters: Available, Summary, Present, Absent

**Features:**
- Employee avatars open profile modal in all filter views
- Consistent user experience across all filter options

---

### 2026-03-28 (Update)

**Task:** Display actual profile images in select_employee.php from employees.php uploads

**Files Modified:**
- `employee/css/select_employee.css` - Updated employee-avatar styles
  - Added `overflow: hidden`, `cursor: pointer`, and `position: relative`
  - Added CSS for `.employee-avatar img` with `object-fit: cover`
  
- `employee/js/attendance.js` - Updated avatar rendering and modal
  - Avatar now renders `<img>` tag when `profile_image` exists
  - Falls back to initials text if image fails to load or doesn't exist
  - Updated `showProfileModal()` to use correct path `uploads/` instead of `../uploads/profile_images/`

**Features:**
- Employee avatars now display actual profile images from `employee/uploads/` folder
- Images are properly fitted within the circular avatar using `object-fit: cover`
- Fallback to initials if image is missing or fails to load
- Profile modal also uses correct uploads folder path


---

### 2026-03-28 (Update)

**Task:** Plan implementation of monthly leave credits system (o1 leave per month)

**Status:** Planning phase complete, awaiting implementation approval

**Files Created:**
- `C:\Users\averi\.windsurf\plans\monthly-leave-credits-f9a03d.md` - Implementation plan document

**Planned Database Tables:**
- `employee_leaves` - Stores leave balance per employee
- `leave_transactions` - Audit trail for all leave credits/usage

**Key Features Planned:**
- Monthly cron job to credit 1 leave per active employee
- Leave balance tracking (total, used, remaining)
- Leave history/transaction log
- Admin interface for manual adjustments
- Employee leave balance widget
- Integration with attendance marking

**Notes:**
- Awaiting user go signal before implementation begins
- Plan created in .windsurf/plans/ directory for review


---

### 2026-03-28 (Update)

**Task:** Plan location for leave balance display in employee/settings.php

**Status:** Planning phase complete, awaiting implementation approval

**File Reviewed:**
- `employee/settings.php` - Analyzed tab-based layout structure

**Files Created:**
- `C:\Users\averi\.windsurf\plans\leave-display-location-f9a03d.md` - Location options plan

**Recommended Approach:**
Add new "Leave & Benefits" tab between Profile and Security tabs with:
- Large leave balance number display
- Stats cards for Total/Used leaves
- Next credit date info
- Action buttons for history view

**Alternative Options Considered:**
1. Profile tab widget - Rejected to avoid cluttering personal info section
2. Header badge - Rejected due to limited space for meaningful information

**Notes:**
- Plan recommends dedicated tab for future scalability (leave history, request forms)
- Can reuse existing CSS classes: `.tool-card`, `.form-grid`, `.settings-tabs`
- Awaiting user go signal before implementation

---

### 2026-03-28 (Update)

**Task:** Implement leave balance display in employee/settings.php

**Status:** Implementation completed

**Files Created:**
- `dbschema/create_leave_tables.sql` - SQL migration for leave tables
- `employee/setup_leave_system.php` - Browser-based setup script for tables

**Files Modified:**
- `employee/settings.php` - Added Leave & Benefits tab with:
  - PHP code to fetch leave balance from `employee_leaves` table
  - New "Leave & Benefits" tab between Profile and Security
  - Leave balance card showing remaining days
  - Stats grid showing Total/Used leaves
  - Next credit date display
  - Leave policy information box
  - CSS styles for all leave components

**Database Tables Created:**
- `employee_leaves` - Stores leave balance per employee (total, used, remaining, last_credited_month)
- `leave_transactions` - Audit trail for leave credits/usage

**Features:**
- Real-time leave balance display
- Visual status indicators (available/unavailable)
- Next credit date calculation
- Responsive design for mobile devices
- Fallback handling if tables don't exist yet

---

### 2026-03-28 (Update)

**Task:** Implement leave request notification system

**Status:** Implementation completed and pushed to GitHub

**Files Created:**
- `dbschema/create_leave_requests_table.sql` - SQL for leave_requests table
- `dbschema/add_leave_request_to_notifications.sql` - Migration to add leave_request_id column
- `NOTIFICATION_SYSTEM_DOCUMENTATION.md` - Complete documentation for notification system

**Files Modified:**
- `employee/eng_dashboard.php` - Added leave request form and AJAX handler
  - Form with leave date, type, days, and reason fields
  - Server-side validation for available leave credits
  - Output buffering and error handling for robust JSON responses
  - Notifications created for both employee and admins on submission
- `employee/my_notifications.php` - Added leave request notification display
  - New notification types: leave_submitted, leave_approved, leave_rejected
  - Join with leave_requests table for detailed info
- `employee/admin_notification.php` - Added leave request approval/rejection
  - New AJAX handlers: load_leave_requests, approve_leave, reject_leave
  - Leave tab added to request type tabs
  - Deducts leave balance on approval, creates notification for employee
- `employee/notification.php` - Added leave request support for Super Admin

**Features:**
- Employees can submit leave requests with date, type, days, and reason
- Server-side validation prevents requests without available leave credits
- Admins can approve/reject leave requests
- Employees receive notifications on approval/rejection
- Leave balance automatically deducted upon approval
- Transaction logging for audit trail

---

### 2026-03-28 (Update)

**Task:** Fix mobile responsiveness for notification pages

**Files Modified:**
- `employee/css/notification.css` - Mobile responsive updates
  - Changed desktop grid from 5 columns to 1 column (full width cards)
  - Added mobile styles for `.request-type-tabs` with flex-wrap
  - `.type-tab` now flexes to fill available space on mobile
- `employee/css/my_notifications.css` - Mobile responsive updates
  - Changed desktop grid from 5 columns to 1 column (full width cards)

**Features:**
- All notification pages now display cards in single column layout
- Request type tabs wrap properly on mobile screens
- Improved readability on small devices

---

### 2026-03-28 (Update)

**Task:** Fix notification widget visibility and 404 errors

**Files Modified:**
- `employee/eng_dashboard.php` - Fixed duplicate CSS reference causing 404
  - Removed duplicate `dashboard.css` reference (line 502)
- `employee/my_notifications.php` - Hide notification widget when active
  - Added `widget.style.display = 'none'` when permission is 'granted'
- `employee/eng_dashboard.php` - Hide notification widget when active
  - Added same hide logic for push notification widget

**Features:**
- Notification widget hidden when already enabled (prevents content blocking)
- Fixed 404 error for dashboard.css resource

---

### 2026-03-28 (Update)

**Task:** Implement comprehensive request logging system

**Status:** ✅ Completed

**Files Modified:**
- `employee/eng_dashboard.php` - Added `logActivity()` calls for request submissions
- `employee/admin_notification.php` - Added `logActivity()` calls for pre-approvals and leave approvals/rejections  
- `employee/notification.php` - Added `logActivity()` calls for final approvals/rejections
- `functions.php` - Verified `logActivity()` function exists and is functional
- `employee/logs.php` - Verified existing query structure supports new log types

**Features:**
- Cash advance requests logged (submission, pre-approval, final approval/rejection)
- Overtime requests logged (submission, pre-approval, final approval/rejection)
- Leave requests logged (submission, approval, rejection)
- All actions include relevant details: request ID, amounts, hours, dates, employee names
- Logs stored in `activity_logs` table with user ID, action type, details, IP address, and timestamp
- Both admin and employee log viewers can display request-related activities

---

### 2026-03-30

**Task:** Implement employee account deactivation (soft delete)

**Status:** ✅ Completed

**Files Modified:**
- `employee/function/employees_function.php`
  - Changed `action === 'delete'` from `DELETE` query to `UPDATE status = 'Inactive'`
  - Added `WHERE e.status = 'Active'` filter to employee queries
- `employee/js/employees.js.php`
  - Updated confirm message from "delete" to "deactivate"
- `employee/employees.php`
  - Changed button text from "Delete" to "Deactivate"
  - Changed icon from `fa-trash` to `fa-user-slash`

**Features:**
- Employee records preserved when deactivated (attendance history intact)
- Inactive employees hidden from main employee list
- Can reactivate by editing employee status back to "Active"

---

### 2026-03-29 to 2026-03-30

**Task:** Geolocation Feature Implementation using MapLibre GL JS

**Status:** 🔄 In Progress (Phase 1 Complete, Phase 2 In Progress)

**Files Created:**
- `assets/js/geolocation.js` - Core geolocation module with MapLibre integration
- `assets/css/geolocation.css` - Styles for map components and UI
- `dbschema/geolocation_migration.sql` - Database schema changes
- `employee/api/save_attendance_location.php` - API to save location data
- `employee/api/validate_geofence.php` - Geofence validation API
- `employee/api/update_branch_location.php` - Branch location update API
- `employee/branch_location_manager.php` - Admin interface for branch location management
- `GEOLOCATION_DOCUMENTATION.md` - Comprehensive documentation

**Files Modified:**
- `time_in_api.php` - Added latitude, longitude, accuracy, location_verified parameters
- `time_out_api.php` - Added location parameters for clock-out
- `qr_clock_api.php` - Added location capture for QR-based clock-in/out

**Features Implemented:**
- MapLibre GL JS with CartoDB Positron map style
- Geofence validation (200m default radius per branch)
- Hybrid enforcement policy (soft for regular, hard for high-compliance)
- Location accuracy warnings (>100m accuracy flagged)
- Admin branch location picker with map interface
- Database columns: `geofence_radius_meters`, `clock_in_lat/lng`, `clock_out_lat/lng`, `location_verified`

**Pending:**
- Admin map dashboard showing all branches
- Hybrid geofence validation enforcement
- Warning modal for out-of-range clock-ins
- Notification system integration

---

### 2026-03-30 (Update)

**Task:** Enhance Excel export with date range and branch selection

**Status:** ✅ Completed

**Files Modified:**
- `employee/audit.php`
  - Changed "Export Excel" link to toggle button
  - Added export form with date range inputs (start_date, end_date)
  - Added branch dropdown with "All Branches" option
  - Populated branch list from database
- `employee/export_attendance_excel.php`
  - Added `branch` parameter handling
  - Added SQL condition for branch filtering
  - Added branch filter info to Excel header
  - Updated filename to include branch name or "All_Branches"

**Features:**
- Custom date range selection for exports
- Select specific branch or export all branches
- Branch name included in exported filename
- Form hidden by default, toggled via button click

---

Each entry should include:
- Date
- Task description
- Files created/modified
- Brief notes on changes made

