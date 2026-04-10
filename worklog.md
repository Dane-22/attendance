# worklog.md

## Work Log for Attendance System

### 2026-04-10

**Task:** Implement Soft Delete for Branch Deletion

**Status:** ✅ Completed

**Problem:** Branch deletion was using hard `DELETE` queries, permanently removing branches from the database. This caused potential data integrity issues with historical attendance records and prevented recovery of accidentally deleted branches.

**Solution:** Changed all branch deletion logic to use soft delete by updating `is_active` column to `0` instead of hard-deleting records. Updated all branch queries to filter by `is_active = 1` to only show active branches.

**Changes Made:**

**1. Modified `employee/branch_actions.php` (lines 114-127):**
- Changed: `DELETE FROM branches WHERE id = ?`
- To: `UPDATE branches SET is_active = 0 WHERE id = ?`

**2. Modified `employee/select_emp.php` (lines 94, 123, 143):**
- Added `AND is_active = 1` to branch existence check query
- Added `AND is_active = 1` to branch lookup query
- Changed hard delete to soft delete: `UPDATE branches SET is_active = 0 WHERE id = ?`

**3. Modified `employee/function/attendance.php` (lines 395, 457, 533, 579-623):**
- Added `AND is_active = 1` to add_branch duplicate check
- Added `AND is_active = 1` to delete_branch lookup
- Changed: `DELETE FROM branches` → `UPDATE branches SET is_active = 0`
- Enhanced `undo_delete_branch` to reactivate soft-deleted branches if they exist

**4. Modified `employee/eng_dashboard.php` (line 91):**
- Added `AND is_active = 1` to branch name query

**Result:** 
- Deleted branches are now hidden from UI but preserved in database
- Historical attendance records remain intact (no broken foreign key references)
- Undo functionality can restore soft-deleted branches by reactivating them
- All branch listings only show active branches (`is_active = 1`)

---

### 2026-04-10

**Task:** Fix Branch Employee Modal Showing Zero Data Mismatch

**Status:** ✅ Completed

**Problem:** The billing page showed aggregated salary data for "BCDA - Fence" with ₱2,500 net pay and 2 employees, but clicking the branch name opened a modal displaying "Total (0 employees)" with all values at ₱0.00. The modal data didn't match the main table.

**Root Cause:** Data source mismatch between the main billing query and the modal's employee detail query:
- **Main table (`billing.php`):** Queried `daily_payroll_reports` joined via `dpr.branch_id`, showing payroll records by work location
- **Modal API (`get_branch_employees.php`):** Queried `employees` table joined via `e.branch_id`, filtering by employee's current assigned branch

When employees had worked at "BCDA - Fence" but their current profile showed a different branch assignment (or were inactive), they appeared in the main totals but not in the modal.

**Solution:** Modified `get_branch_employees.php` to query from `daily_payroll_reports` as the primary table, matching the main billing aggregation logic.

**Changes Made:**

**`employee/api/get_branch_employees.php` (lines 52-78):**
- Changed FROM clause from `employees e` to `daily_payroll_reports dpr`
- Changed join: `JOIN branches b ON dpr.branch_id = b.id` (was: `e.branch_id = b.id`)
- Changed join: `JOIN employees e ON dpr.employee_id = e.id` (was: `LEFT JOIN daily_payroll_reports`)
- Removed `e.status = 'Active'` filter so inactive employees still show in historical payroll data
- Updated parameter binding order: `sss` with `branchName, startDate, endDate`

**Before:**
```sql
FROM employees e
JOIN branches b ON e.branch_id = b.id
LEFT JOIN daily_payroll_reports dpr ON e.id = dpr.employee_id
WHERE b.branch_name = ? AND e.status = 'Active'
```

**After:**
```sql
FROM daily_payroll_reports dpr
JOIN branches b ON dpr.branch_id = b.id
JOIN employees e ON dpr.employee_id = e.id
WHERE b.branch_name = ? AND dpr.report_date BETWEEN ? AND ?
```

**Result:** Modal now correctly displays employees based on where they actually worked (payroll records), matching the aggregated totals in the main billing table.

---

### 2026-04-10

**Task:** Sync Billing Deductions with Weekly Report

**Status:** ✅ Completed

**Problem:** The "Employees with Government Deductions" report in `billing.php` showed different deduction amounts than `weekly_report.php`. For example, Cesar Abubo showed ₱120 total deduction in billing but ₱250 in weekly report for April 6-10 (Week 2).

**Root Cause:** 
- `billing.php` used cumulative deductions (Week 1 + Week 2 = 650)
- `weekly_report.php` uses single week deductions (Week 2 only = 250)
- Both had different week calculation logic

**Solution:** Created shared `week_calculator.php` helper and updated `billing.php` to use single-week deductions matching weekly_report.php logic.

**Changes Made:**

**`employee/function/week_calculator.php` (new file):**
- `calculateWorkWeekBoundaries()` - Calculates work weeks (Mon-Sat, excluding Sundays)
- `getWeeklyGovernmentDeductions()` - Returns prorated deductions per week (Week 1: 400, Week 2: 250, Week 3: 250)
- `calculateCumulativeDeductions()` - Sums deductions for weeks 1-N
- `getWeekNumberForDate()` - Determines which week a date falls into
- `getCurrentWorkWeek()` - Gets current week based on today's date

**`employee/billing.php` (lines 232-297):**
- Added `require_once 'function/week_calculator.php'`
- Modified `employees_with_deductions` case to use shared week calculation
- Changed from cumulative to single-week deductions based on report end date
- Now determines which week the date range falls into and applies that week's deduction amount
- Override deductions with weekly calculated values from week calculator

**Before:**
```php
// Used cumulative deductions
$maxDeductionWeek = min($currentWeek, 3);
$cumulativeDeductions = calculateCumulativeDeductions($maxDeductionWeek);
$row['total_deductions'] = $cumulativeDeductions['total']; // 650 for Week 2
```

**After:**
```php
// Uses single week deductions matching weekly_report.php
$deductionWeek = min($reportWeek, 3);
$weeklyDeductions = getWeeklyGovernmentDeductions($deductionWeek);
$row['total_deductions'] = $weeklyDeductions['total']; // 250 for Week 2
```

**Result:** Billing deductions now match weekly report exactly. For April 6-10 (Week 2), all employees with deductions now show ₱250 (SSS: 100 + PhilHealth: 100 + Pag-IBIG: 50).

---

### 2026-04-10

**Task:** Implement Search Functionality in Notification Pages

**Status:** ✅ Completed

**Problem:** The notification pages (`admin_notification.php` and `notification.php`) displayed all requests without a way to filter or search. Administrators needed to quickly find specific requests by employee name, branch, reason, amount, or date without manually scanning through all records.

**Solution:** Added real-time search functionality to both notification pages with client-side filtering, debounced input, and clear visual feedback.

**Actions Taken:**

**1. Added Search UI Components:**
- Search input field with placeholder text: "Search by employee name, branch, reason, amount, or date..."
- Search icon positioned inside input field
- Clear button (X) that appears when text is entered
- Results counter showing "Showing X of Y results"
- Dark/light theme styling support

**2. Implemented Client-Side Search Logic:**
- Added `filterRequests(searchTerm)` function to filter the `currentRequests` array
- Search scope includes: employee name, branch name, reason text, amount, request date, requested date, leave date, leave type
- Case-insensitive matching using `toLowerCase()`
- 300ms debounce to prevent excessive re-rendering while typing
- Escape key clears search and returns focus to input

**3. Updated Data Loading and Rendering:**
- Added `filteredRequests` array to track filtered subset
- Modified `loadRequests()` to initialize `filteredRequests` and clear search on tab/type switch
- Updated all render functions (`renderRequests`, `renderCashAdvanceRequests`, `renderLeaveRequests`) to use `filteredRequests`
- Search clears automatically when switching between request types or status tabs

**4. Files Modified:**
- `employee/notification.php` (Super Admin dashboard):
  - Added search CSS styles (lines 670-767)
  - Added search HTML structure (lines 785-793)
  - Added JavaScript search functions: `filterRequests()`, `handleSearchInput()`, `clearSearch()`, `updateSearchResultsCount()` (lines 853-945)
  - Updated `loadRequests()` to reset search on data load (lines 1011-1031)

- `employee/admin_notification.php` (Admin dashboard):
  - Added search CSS styles (lines 832-929)
  - Added search HTML structure (lines 957-965)
  - Added JavaScript search functions with support for leave requests (lines 1014-1112)
  - Updated `loadRequests()` to reset search on data load (lines 1180-1201)

**Features:**
- Real-time filtering as user types (300ms debounce)
- Searches across all visible request fields
- Clear button to reset search instantly
- Results counter feedback
- Preserves search within current tab (clears on tab switch)
- No backend changes required (client-side only)
- Mobile responsive styling

---

### 2026-04-10

**Task:** Create Cash Advance Per Employee Documentation

**Status:** ✅ Completed

**Problem:** Need documentation explaining how cash advance per employee report works in billing.php and its relationship with cash advance request APIs.

**Solution:** Created comprehensive documentation file explaining the data flow, database schema, API endpoints, and implementation details.

**Actions Taken:**

**1. Analyzed Cash Advance Integration:**
- Reviewed `billing.php` cash advance query (lines 152-185)
- Reviewed `cash_advance_request.php` - Submit request API
- Reviewed `cash_advance_history.php` - Get history API
- Reviewed `approve_cash_advance.php` - Admin approval API
- Reviewed database schema for `cash_advances` table

**2. Key Findings Documented:**
- `billing.php` queries `cash_advances` table directly (not via API)
- Query aggregates total cash advance per employee within date range
- Shows: Employee Code, Name, Branch, Total Amount, Request Count, Latest Status
- Date filtering based on `request_date` field
- Only employees with cash advances shown (`HAVING total_cash_advance > 0`)

**3. API Endpoints Documented:**
- `POST /cash_advance_request.php` - Submit new request with validation (max 50% of monthly salary)
- `GET /cash_advance_history.php?emp_id={id}` - Get employee transaction history with running balance
- `POST /approve_cash_advance.php` - Admin approve/reject/pay actions

**4. Status Workflow:**
- Pending → Approved → Paid
- Pending → Rejected

**Files Created:**
- `docs/cash_advance_billing_integration.md` - Complete documentation including:
  - Architecture diagram showing data flow
  - Database table schema
  - SQL query explanation
  - API endpoint reference
  - Print preview integration details

---

### 2026-04-10

**Task:** Implement Branch-Specific Employee Detail Modal in Billing Page

**Status:** ✅ Completed

**Problem:** The billing page (`billing.php`) displayed aggregated salary data by branch, but administrators needed to see the detailed employee-level breakdown for each branch to verify payroll calculations and identify discrepancies.

**Solution:** Made branch names clickable in the Site Salary and Office Salary reports, opening a modal that displays detailed employee payroll data for the selected branch.

**Actions Taken:**

**1. Created API Endpoint:**
- `employee/api/get_branch_employees.php` - New API endpoint that:
  - Accepts `branch_name`, `start_date`, `end_date` parameters
  - Queries `daily_payroll_reports` joined with `employees` table
  - Handles "Unassigned" branches (employees without branch assignments)
  - Returns employee details: name, code, position, daily rate, days worked, basic pay, OT, deductions, net pay
  - Calculates totals (employee count, sums for all monetary fields)
  - Returns JSON response with structured data

**2. Added Modal HTML Structure to `employee/billing.php`:**
- Added `branchDetailModal` container with header, body, and footer
- Modal header shows branch name and close button
- Loading spinner state while fetching data
- Error message container for failed requests
- Table structure with columns: Employee, Position, Days, Basic Pay, OT, Deductions, Net Pay
- Dynamic totals row showing employee count and all monetary totals

**3. Implemented JavaScript Functions:**
- `openBranchModal(branchName)` - Opens modal, shows loading state, fetches data from API
- `renderBranchEmployees(data)` - Populates table with employee data and updates totals
- `closeBranchModal()` - Closes modal and restores body scroll
- `formatCurrency(amount)` - Helper for consistent currency formatting
- `escapeHtml(text)` - XSS prevention for dynamic content
- Event listeners: ESC key closes modal, click outside closes modal
- Current date range passed from PHP to JavaScript for API calls

**4. Added CSS Styling to `employee/css/billing.css`:**
- `.branch-link` - Gold-colored clickable branch names with hover effects and eye icon
- `.branch-modal` - Modal overlay with dark background (z-index: 1001)
- `.branch-modal-content` - Card-style container with gold border accent
- `.branch-modal-header` - Dark header with gold title text
- `.branch-modal-body` - Scrollable content area with period display
- `.branch-detail-table` - Styled table with sticky headers and hover effects
- `.loading-spinner` - Animated loading indicator
- `.branch-modal-error` - Styled error message container
- Mobile responsive styles: converts table to card view on screens <768px

**5. Made Branch Names Clickable:**
- Modified table rows for `site_salary` and `office_salary` filters
- Wrapped branch names in `<span class="branch-link">` with onclick handler
- Used `htmlspecialchars(addslashes())` for XSS protection in JavaScript strings

**Files Created:**
- `employee/api/get_branch_employees.php` - API endpoint for branch employee data

**Files Modified:**
- `employee/billing.php` - Added modal HTML (lines 472-519), JavaScript functions (lines 746-851), clickable branch links (lines 399-403)
- `employee/css/billing.css` - Added modal and branch link styles (lines 872-1151)

**Features:**
- Click any branch name in Site/Office Salary reports to view detailed breakdown
- Shows all employees in the branch with their payroll details
- Displays totals: employee count, days worked, basic pay, OT, deductions, net pay
- Loading spinner while fetching data
- Error handling for failed API requests
- Close via X button, outside click, or ESC key
- Mobile responsive: table converts to card view on small screens
- Respects current date range filter from billing page
- Handles "Unassigned" branches (employees without branch assignments)

---

### 2026-04-10

**Task:** Increase Overtime Request Limit from 1 to 3 Per Day Per Employee

**Status:** ✅ Completed

**Problem:** The system only allowed 1 pending overtime request per employee per day. Users requested the ability to submit up to 3 overtime requests per day for the same employee.

**Solution:** Updated the validation logic in both overtime request endpoints to count pending requests and allow up to 3 instead of rejecting at 1.

**Changes Made:**

**1. Updated `@/wamp64/www/main/employee/function/attendance.php` (lines 2670-2704):**
- Changed from `SELECT id ... LIMIT 1` to `SELECT COUNT(*) as pending_count`
- Changed condition from `if ($existingPending)` to `if ($pendingCount >= 3)`
- Updated error message: "Maximum of 3 pending overtime requests allowed per day"

**2. Updated `@/wamp64/www/main/overtime_request.php` (lines 71-85):**
- Changed from checking any non-rejected request to counting only pending requests
- Updated query: `SELECT COUNT(*) as pending_count FROM overtime_requests WHERE employee_id = ? AND request_date = ? AND status = 'pending'`
- Changed condition from `if (mysqli_num_rows($check_result) > 0)` to `if ($pending_count >= 3)`
- Updated error message and HTTP 409 response

**Files Modified:**
- `employee/function/attendance.php` - Core overtime request validation logic
- `overtime_request.php` - API endpoint validation
- `docs/overtime_requests_per_day.md` - Updated documentation to reflect new limit

**Behavior:**
- Employees can now have up to 3 pending overtime requests for the same date
- Once a request is approved or rejected, a new one can be submitted
- Error message clearly states the 3-request limit when exceeded
- All other validation (max 4 hours per request) remains unchanged

---

### 2026-04-09

**Task:** Audit Page - Auto-Absent Feature for Employees Without Time-In Records

**Status:** ✅ Completed

**Problem:** The audit page (`audit.php`) only showed employees who had existing attendance records. Employees who didn't show up and had no attendance record were invisible in the audit view, creating an incomplete picture of daily attendance.

**Solution:** Modified the audit page to automatically display employees without attendance records as "Absent (Auto)" when:
- The selected date is a past date (Monday-Saturday)
- The selected date is today AND the current time is 8:30 AM or later
- Sundays are excluded from auto-absent

**Key Changes:**

**1. Auto-Absent Logic:**
- Added 8:30 AM cutoff time configuration
- Logic for different date scenarios:
  - Past dates (Mon-Sat): Always apply auto-absent
  - Today before 8:30 AM: Show only existing records
  - Today after 8:30 AM: Apply auto-absent
  - Sundays: Auto-absent disabled
  - Future dates: Don't apply

**2. Database Query Changes:**
- Changed all queries to use `employees` as base table with LEFT JOIN to `attendance`
- Modified count queries to count active employees, not just attendance records
- Updated summary statistics to include auto-absent employees in absent count
- Added `branches` table join for proper branch name resolution
- Detail queries now select from `employees` to show all active employees

**3. Status Detection:**
- Updated `getAttendanceStatus()` to accept auto-absent parameters
- Added On Leave detection via `leave_transactions` table check
- Returns `is_auto` flag for UI styling of auto-absent records
- Priority: On Leave > Present/Completed/Late > Auto Absent

**4. Filter Updates:**
- Modified `buildStatusFilterCondition()` to include no-record employees when absent filter is selected
- Absent filter now shows both explicitly marked absent AND auto-absent employees

**5. UI Improvements:**
- Added "On Leave" status badge styling (purple)
- Added auto-absent row highlighting with tooltip
- Added info banner when auto-absent mode is active
- Added Sunday notification banner when viewing Sundays
- Fixed branch search to use COALESCE for NULL attendance records

**Files Modified:**
- `employee/audit.php`:
  - Added auto-absent configuration and date logic (lines 78-115)
  - Modified `getAttendanceStatus()` with On Leave and auto-absent detection
  - Updated `buildStatusFilterCondition()` for absent filter
  - Rewrote count, summary, and detail queries to use employees as base table
  - Added CSS styling for On Leave status and auto-absent rows
  - Added info banners for auto-absent mode and Sundays
  - **Fixed:** Changed `MAX(id)` to `ORDER BY time_in ASC LIMIT 1` to show earliest (morning) attendance record instead of latest
- `employee/api/get_employee_attendance_detailed.php`:
  - **Fixed:** Modified query to select earliest time_in record per day using subquery with `MIN(time_in)`
  - This ensures the employee calendar modal shows the morning time-in (e.g., 06:33 AM) instead of afternoon re-entry (e.g., 04:04 PM)

---

### 2026-04-09

**Task:** Update Notification Pages - 4-Column Grid, Reject Buttons, See More Toggle

**Status:** ✅ Completed

**Problem:** The notification pages (`admin_notification.php` and `notification.php`) needed UI improvements:
1. Single column layout was inefficient for viewing many requests
2. No reject functionality for overtime and cash advance requests
3. Long reason text overflowed the card boundaries

**Actions Taken:**

**1. 4-Column Grid Layout:**
- Changed `requests-grid` from `grid-template-columns: 1fr` to `repeat(4, minmax(0, 1fr))`
- Added responsive breakpoints: 4 cols (desktop), 3 cols (1200px), 2 cols (992px), 1 col (768px)
- Added inline grid styles to JavaScript render functions for immediate effect
- Added CSS cache-busting with `?v=<?php echo time(); ?>` to force CSS reload

**2. Reject Functionality:**
- Added `reject_request` POST handler for overtime rejection (already existed in notification.php)
- Added `reject_cash_advance` POST handler for cash advance rejection
- Added `showRejectModal()` and `confirmReject()` JavaScript functions
- Added reject buttons next to "Noted" buttons on pending requests
- Modal-based rejection with optional reason input
- Sends notifications to employees when rejected
- Logs rejection activity

**3. "See More" Toggle:**
- Added `truncateText(text, maxLength)` helper function
- Added `generateReasonHtml(reason, requestId)` to create truncated text with toggle
- Added `toggleSeeMore(btn)` to handle expand/collapse
- Truncates reason text to 60 characters with ellipsis
- Added CSS for `.see-more-btn` (gold color, hover underline)
- Fixed text wrapping with `word-wrap: break-word`, `word-break: break-word`, `overflow-wrap: break-word`

**4. Card Layout Improvements:**
- Added `width: 100%`, `min-width: 0`, `box-sizing: border-box` to cards
- Constrained `.info-row.reason .value` to prevent overflow
- Added `page-container` and `.main-content` full-width styles

**Files Modified:**
- `employee/admin_notification.php`:
  - Added reject handlers for overtime and cash advance
  - Updated render functions with inline grid styles
  - Added `generateReasonHtml()`, `toggleSeeMore()` helper functions
  - Added `showRejectModal()`, `closeRejectModal()`, `confirmReject()` functions
  - Added CSS cache-busting
- `employee/notification.php`:
  - Added `generateReasonHtml()`, `toggleSeeMore()` helper functions
  - Updated render functions with inline grid styles and see more toggle
  - Removed duplicate JavaScript functions
  - Added CSS cache-busting
- `employee/css/notification.css`:
  - Updated `.requests-grid` to 4 columns with responsive breakpoints
  - Added `.see-more-btn` styles
  - Added `.reason-text` text wrapping styles
  - Added full-width container styles

---

### 2026-04-09

**Task:** Fix Branch Calendar Showing Future Dates with Records

**Status:** ✅ Completed

**Problem:** The branch calendar modal was showing attendance records for ALL days in the month, including future/upcoming days (e.g., April 10-30 showing the same employee data as April 9 when today is April 9).

**Root Cause:** Two issues:
1. The API was querying all attendance records for the entire month without capping at today's date
2. **Critical bug:** Line 196 used `$date` instead of `$dateStr` - this caused every day in the calendar to display the same employee data (from whatever date `$date` happened to hold from the last loop iteration)

**Fixes Applied:**
1. Added logic to cap the query end date at today (`date('Y-m-d')`) - if the month's end date is in the future, it's limited to today's date (lines 93-97)
2. Fixed variable typo: changed `$attendanceByDate[$date]` to `$attendanceByDate[$dateStr]` on line 196 - this ensures each day correctly looks up its own attendance data by the properly formatted date string

**Files Modified:**
- `employee/api/get_branch_attendance_detailed.php`:
  - Added `$today` check to cap end date (lines 93-97)
  - Fixed variable typo `$date` → `$dateStr` (line 196)

---

### 2026-04-09

**Task:** Fix Branch Calendar Month Navigation Bug in Audit Page

**Status:** ✅ Completed

**Problem:** When clicking the left arrow in the branch calendar modal to navigate to previous months, clicking once from April went to February (skipping March). This was caused by timezone conversion issues with `Date.toISOString()`.

**Root Cause:** The `navigateBranchCalendar()` function used `date.toISOString().slice(0, 7)` which converts the date to UTC. Depending on the timezone offset (e.g., UTC+8), this could shift the date backward across month boundaries (e.g., April 1st midnight in UTC+8 becomes March 31st in UTC).

**Fix Applied:**
- Replaced Date-based calculation with explicit year/month arithmetic
- When direction is -1 (previous month): decrement month, if month < 1 wrap to 12 and decrement year
- When direction is +1 (next month): increment month, if month > 12 wrap to 1 and increment year
- Format result as `YYYY-MM` using string template without Date conversion

**Files Modified:**
- `employee/audit.php` - Fixed `navigateBranchCalendar()` function (lines 2451-2467)

---

### 2026-04-09

**Task:** Refactor Engineer Dashboard - Modal-Based Request Forms

**Status:** ✅ Completed

**Problem:** The `eng_dashboard.php` had cluttered inline request forms (Cash Advance, Overtime, Leave) and redundant summary cards taking up space. Needed a cleaner, more focused dashboard layout.

**Actions Taken:**
1. Removed summary cards section (Active Sites, Site Personnel, Recent Transfers, Site Attendance)
2. Removed inline request forms (Cash Advance, Overtime, Leave) from main dashboard
3. Added quick action cards row with 3 clickable cards:
   - Request Cash Advance (gold theme)
   - Request Overtime (blue theme)
   - Request Leave (green theme)
   - Shows pending count badges where applicable
4. Added 3 Bootstrap modals with dark theme styling:
   - Cash Advance Modal
   - Overtime Modal
   - Leave Modal
5. Moved existing forms into modals preserving all functionality:
   - All form validation preserved
   - AJAX submission handlers updated for modal form IDs
   - Alert notifications work inside modals
6. Added CSS for quick action cards:
   - Responsive grid (3-col desktop, 1-col mobile)
   - Hover effects with gold accent glow
   - Icon containers with themed colors

**Files Modified:**
- `employee/eng_dashboard.php` - Complete dashboard refactor:
  - Removed summary cards section
  - Added quick action cards HTML
  - Added 3 Bootstrap modals with forms
  - Added quick action card CSS styling
  - Updated JavaScript form handlers for modal IDs

**Dashboard Flow Now:**
1. Engineer clocks in/out
2. Quick action cards row (click to open request modals)
3. Analytics sections (Admin only)
4. Consecutive attendance issues
5. Data monitoring section

---

### 2026-04-09

**Task:** Add Status-Based Sorting to Attendance Audit Table

**Status:** ✅ Completed

**Problem:** The attendance audit table was sorted only by date/time, making it difficult to quickly identify employees by their attendance status (Present, Completed, Late, Absent).

**Actions Taken:**
1. Modified `employee/audit.php` to add custom ORDER BY clause to all three SQL queries (day, week, month filters)
2. Implemented CASE-based sorting with priority: Present (1) → Completed (2) → Late (3) → Absent (4)
3. Sorting logic matches the existing PHP status display logic:
   - **Present (1):** `time_in` exists, no `time_out`, not late
   - **Completed (2):** Both `time_in` and `time_out` exist, not late
   - **Late (3):** `time_in` exists, worker position, time >= 07:15:00
   - **Absent (4):** No `time_in` record
4. Secondary sort by date/time within each status group

**Files Modified:**
- `employee/audit.php` - Updated ORDER BY clauses in three SQL queries:
  - Lines 236-246: Week filter query
  - Lines 271-281: Month filter query
  - Lines 306-316: Day filter query

**Result:** Attendance records now display in status order: Present employees first, then Completed shifts, then Late arrivals, then Absent employees. Within each group, records are sorted by most recent first.

---

### 2026-04-09

**Task:** Implement Branch Attendance Calendar Modal

**Status:** ✅ Completed

**Problem:** Need a way to view all employee attendance records for a specific branch in a calendar view, accessible by clicking the branch name in the audit table.

**Actions Taken:**
1. Created new API endpoint `employee/api/get_branch_attendance_detailed.php`
   - Fetches attendance data filtered by branch name and month
   - Calculates employee status (Present, Completed, Late, Absent) per day
   - Provides daily summary counts and employee list with times
   - Implements PHP session-based rate limiting (60 requests/minute)

2. Enhanced `employee/audit.php`:
   - Made branch names in the attendance table clickable with `openBranchCalendar()`
   - Added branch calendar modal HTML structure with header, loading state, calendar grid, and legend
   - Added comprehensive CSS styling for modal, calendar grid, day cells, and employee items
   - Implemented JavaScript functions:
     - `openBranchCalendar()` - opens modal and initializes state
     - `closeBranchCalendar()` - closes modal and clears state
     - `navigateBranchCalendar()` - month navigation (prev/next)
     - `loadBranchCalendarData()` - fetches data from API with error handling
     - `renderBranchCalendar()` - renders calendar grid with attendance data
     - `toggleDayEmployees()` - expands/collapses employee list per day
     - `createBranchDayElement()` - creates day cells with employee list and status colors

3. Features:
   - Shows all attendance statuses (Present, Completed, Late, Absent)
   - Displays up to 10 employees per day with "+X more" button to expand
   - Color-coded status badges (Green=Present, Blue=Completed, Orange=Late, Red=Absent)
   - Month navigation with previous/next buttons
   - Rate limiting: 60 requests per minute to prevent abuse
   - Responsive design for mobile/tablet
   - Escape key and backdrop click close the modal

**Files Created:**
- `employee/api/get_branch_attendance_detailed.php` - API endpoint with rate limiting

**Files Modified:**
- `employee/audit.php` - Added branch modal HTML, CSS, and JavaScript (lines 909-915, 1434-1510, 1898-2181, 2380-2635)

---

### 2026-04-08

**Task:** Add Clickable Profile Image Modal in select_employee.php

**Status:** ✅ Completed

**Problem:** Profile images in the employee selection page were static and couldn't be viewed in full size.

**Actions Taken:**
- Added `profileImageModal` HTML structure to `select_employee.php` (lines 626-639)
- Modal displays employee name in header and full-size centered image
- Click outside image or X button closes the modal
- Uses existing `showProfileModal()` and `closeProfileModal()` functions from `attendance.js`
- Modal styling: max 90vw width, max 90vh height, dark theme consistent with app

**Files Modified:**
- `employee/select_employee.php` - Added profile image modal HTML

---

**Task:** Fix 413 Request Entity Too Large Error + Add Client-Side Image Compression

**Status:** ✅ Partially Completed

**Problem:** 
1. Employees page showing 413 error when navigating to page 2 (nginx 1.24.0 blocking POST requests)
2. Profile image uploads limited to 5MB without compression

**Actions Taken:**

**1. Fixed SQL Syntax Error in employees_function.php:**
- Removed incomplete SQL fragment `b.bra` that was breaking the search query
- Fixed at line 372 in the `$searchCondition` variable

**2. Added Cache Headers to Prevent POST Issues:**
- Added `Cache-Control: no-cache, no-store, must-revalidate` headers to employees.php
- Added `Pragma: no-cache` and `Expires: 0` headers
- Prevents service worker/caching issues that may cause POST requests with stale data

**3. Implemented Client-Side Image Compression:**
- Increased upload limit from 5MB to 10MB
- Added JavaScript compression before upload:
  - Images >500KB are auto-compressed
  - Max width: 1200px, JPEG quality: 80%
  - Results in ~400-600KB final size regardless of original
- Added compression status indicator ("Compressing..." spinner)
- Updated UI text: "Max file size: 10MB • Auto-compressed to ~500KB"
- Fallback to original file if compression fails

**Files Modified:**
- `employee/employees.php` - Added cache headers and image compression JS
- `employee/function/employees_function.php` - Fixed SQL syntax, updated 5MB→10MB limits

**Files Affected:**
- Line 5-8: Added cache-control headers in employees.php
- Lines 374, 591-673: Added `handleImageUpload()` and `compressImage()` functions
- Line 181, 220, 278, 328: Updated 5MB references to 10MB
- Line 372: Removed incomplete SQL `b.bra` fragment

**Note:** The 413 error on pagination requires nginx `client_max_body_size` increase by hosting provider. The code changes help with image uploads but the server config issue remains.

---

### 2026-04-07

**Task:** Implement Custom Date Range in Payroll Report

**Status:** ✅ Completed

**Problem:** The payroll report only supported fixed Weekly (1-7 days per week) and Monthly (full calendar month) views. Users needed flexibility to generate payroll reports for any arbitrary date range (e.g., Jan 15-25, or across month boundaries like Jan 28 - Feb 5).

**Actions Taken:**
1. Added new 'range' view type to `employee/function/report.php` alongside existing 'weekly' and 'monthly' views
2. Implemented handling for `start_date` and `end_date` GET parameters
3. Added date validation with fallback to current week if dates are invalid
4. Updated date range label to display "Custom Range: Jan 01, 2026 - Jan 15, 2026" format
5. Added "Date Range" toggle button to the view switcher in `employee/weekly_report.php`
6. Created conditional date picker inputs (Start Date, End Date) that appear only in range view
7. Updated header title logic to show "Custom Date Range Payroll Report"
8. Updated all branch filter links and pagination links to preserve date range parameters
9. Modified `employee/js/report.js` `changeView()` function to handle 'range' view type with default 7-day range

**Files Modified:**
- `employee/function/report.php` - Added 'range' view type handling:
  - Lines 19: Updated view_type comment to include 'range'
  - Lines 22-24: Added start_date and end_date parameter handling
  - Lines 55-67: Added custom date range logic with validation and fallback
- `employee/weekly_report.php` - Added UI for date range selection:
  - Lines 43-49: Dynamic header title for range view
  - Lines 54-60: Dynamic subtitle showing date range label
  - Lines 83-86: New "Date Range" toggle button
  - Lines 103-116: Conditional date picker inputs for range view
  - Lines 163, 168: Branch filter links preserve date range params
  - Lines 180, 197, 207, 215, 222: Pagination links preserve date range params
- `employee/js/report.js` - Updated view switching logic:
  - Lines 73-98: Enhanced `changeView()` to handle 'range' view with default dates

**Features:**
- Users can now select any custom date range for payroll reporting
- Default range is last 7 days when switching to Date Range view
- Branch filters and pagination preserve the selected date range
- Invalid date combinations fall back to safe defaults
- Date range label clearly shows the selected period

**Usage:**
1. Click "Date Range" toggle button
2. Select Start Date and End Date using the date pickers
3. Report automatically updates when dates change
4. Use branch filters as normal - date range is preserved

---

### 2026-04-07

**Task:** Investigate MapLibre/CartoDB Costs and "Need to Pay" QR Scan Error

**Status:** ✅ Investigation Complete

**Problem:** Workers reported "need to pay" message when scanning QR codes for time-in. User suspected MapLibre or CartoDB had associated costs.

**Investigation Findings:**
1. **MapLibre GL JS** - Free and open-source, no API key required for basic usage
2. **CartoDB Positron** - Free basemap style, no API key required
3. **Browser Geolocation API** - Built into browsers, no external service costs
4. **Haversine Formula** - Mathematical calculation, no external service costs

**Root Cause Identified:**
The "need to pay" message is NOT from map/geolocation services. The QR scanning system does NOT use MapLibre/CartoDB at all for location validation. It uses:
- Native browser `navigator.geolocation.getCurrentPosition()`
- JavaScript Haversine formula for distance calculation
- No external APIs or paid services involved

**Conclusion:** The error message is likely from:
- Geofencing validation failing (outside 500m radius)
- Workers being physically outside the geofence area
- Poor GPS accuracy causing false "outside" readings

**Files Created:**
- `MAPLIBRE_COST_INVESTIGATION.md` - Complete investigation report documenting:
  - Why MapLibre/CartoDB have no costs
  - Why QR scanning doesn't use map services
  - Actual cause of "need to pay" error (geofence-related)
  - Recommendations for widening geofence radius

**Result:** Investigation concluded that no payment is required for any map/geolocation services. The issue is geofence radius too small (500m).

---

### 2026-04-07

**Task:** Widen Geofence Radius from 500m to 1000m

**Status:** ✅ Completed

**Problem:** Workers unable to clock in via QR scan due to strict 500m geofence radius. Investigation showed this was the real cause of clock-in failures, not payment requirements.

**Actions Taken:**
1. Updated all PHP files with new 1000m default:
   - `validate_geofence.php` - Lines 221, 228: `?? 500` → `?? 1000`
   - `check_branch_location.php` - Line 34: `?: 500` → `?: 1000`
   - `get_branch_location_api.php` - Line 50: `?? 500` → `?? 1000`
   - `save_attendance_location.php` - Lines 90, 113: `500` → `1000`
   - `employee/api/validate_geofence.php` - Line 78: `?? 500` → `?? 1000`

2. Updated JavaScript configuration:
   - `assets/js/geolocation.js` - Line 18: `defaultRadius: 500` → `defaultRadius: 1000`

3. Updated admin interface:
   - `employee/branch_location_manager.php` - Multiple updates:
     - Default radius: 500 → 1000 (lines 114, 129, 185, 229, 485)
     - Slider max: 1000m → 2000m (line 187)

4. Created debug tool:
   - `debug_qr_scan.php` - Diagnostic page for testing GPS location against branch geofences

5. Database update (user executed):
   ```sql
   UPDATE branches SET geofence_radius_meters = 1000 
   WHERE geofence_radius_meters IS NULL 
      OR geofence_radius_meters = 0 
      OR geofence_radius_meters < 1000;
   ```

6. Committed all changes to git and deployed

**Files Created:**
- `debug_qr_scan.php` - GPS diagnostic and geofence testing tool
- `GEOFENCE_RADIUS_CHANGE_LOG.md` - Documentation of all changes made

**Files Modified:**
- `validate_geofence.php`
- `check_branch_location.php`
- `get_branch_location_api.php`
- `save_attendance_location.php`
- `employee/api/validate_geofence.php`
- `assets/js/geolocation.js`
- `employee/branch_location_manager.php`

**Result:** Geofence radius now 1000m across entire system. Workers can clock in from up to 1000 meters from any branch. Admin can set radius up to 2000m via slider.

---

### 2026-04-07

**Task:** Investigate Localhost vs Production Access Issue

**Status:** ✅ Investigation Complete

**Problem:** Site works on PC via ethernet, but not accessible via mobile WiFi. User suspected server/code issues.

**Investigation Process:**
1. Verified all code files are correct and deployed
2. Tested with mobile data → ✅ Worked
3. Tested with PLDT ISP → ✅ Worked  
4. Tested with Converge ICT WiFi → ❌ Failed
5. Confirmed other websites work on Converge WiFi → ✅ General connectivity OK

**Root Cause CONFIRMED:**
- **ISP:** Converge ICT
- **Issue:** CGNAT (Carrier-Grade NAT) IP flagging
- **Shared Public IP:** 119.93.99.226
- **Cause:** Hostinger firewall flagged the shared IP due to suspicious traffic from another user on same IP

**Technical Details:**
- Converge ICT uses CGNAT: thousands of users share one public IP
- Another user on 119.93.99.226 generated suspicious traffic
- Hostinger's firewall blocked the entire IP
- Result: All Converge users behind this IP cannot access the site

**Not Application Issues:**
- ✅ Code is correct
- ✅ Server is working
- ✅ Database connected
- ✅ Works on Mobile Data, PLDT, PC ethernet
- ❌ Only Converge ICT blocked

**Files Created:**
- `LOCALHOST_VS_PRODUCTION_ISSUE.md` - Complete investigation report:
  - Initial localhost vs production analysis
  - Database configuration findings
  - Network troubleshooting steps
  - Final CGNAT IP flagging confirmation
  - Solutions and workarounds

**Solutions Provided:**
1. **Immediate:** Use mobile data for site access
2. **Quick Fix:** Change mobile DNS to 8.8.8.8 (Google DNS)
3. **Long-term:** Contact Converge ICT for IP refresh or CGNAT exemption
4. **Alternative:** Contact Hostinger to whitelist domain from flagged IP

**Result:** Investigation confirmed this is NOT a code or server issue. The application is fully functional. The issue is external ISP-level IP blocking outside our control.

---

### 2026-04-07

**Task:** Implement Individual Employee Report in Attendance Audit

**Status:** ✅ Completed

**Problem:** The attendance audit page only showed bulk attendance records. Administrators needed a way to generate detailed individual reports for specific employees showing their attendance history, summary statistics, and daily breakdown over a custom date range.

**Actions Taken:**
1. Created `individual_report_selector.php` - A dedicated page for selecting an employee and date range
2. Added predefined date range quick selectors (Today, This Week, Last Week, This Month, Last Month)
3. Implemented employee search functionality within the dropdown
4. Created date preview showing selected period and duration
5. Integrated with `export_individual_excel.php` for Excel report generation
6. Added "Individual Report" button to `audit.php` page linking to the selector
7. Added Individual Report option to the existing Export Excel form in audit.php

**Files Created:**
- `employee/individual_report_selector.php` - Employee and date range selector page:
  - Employee dropdown with search/filter functionality
  - Quick date range preset buttons (Today, This Week, Last Week, This Month, Last Month)
  - Custom date range inputs with validation
  - Date preview showing period and duration
  - Links to `export_individual_excel.php` for report generation

**Files Modified:**
- `employee/audit.php` - Added Individual Report integration:
  - Line 559-562: New "Individual Report" button linking to `individual_report_selector.php`
  - Lines 607-612: Individual Report option in Export Excel form

**Features:**
- Quick date range selection with preset buttons
- Employee search/filter in dropdown
- Real-time date preview with duration calculation
- Seamless integration with existing Excel export functionality
- Responsive design with sidebar navigation

**Usage:**
1. Click "Individual Report" button on audit page
2. Search and select an employee from the dropdown
3. Choose a date range (quick select or custom dates)
4. Click "Generate Excel Report" to download the individual report

---

### 2026-04-07

**Task:** Add Rate Limiter to Payroll Report and Update Date Range Filter UI

**Status:** ✅ Completed

**Problem:**
1. The payroll report date range inputs auto-submitted on every date change, causing excessive server requests
2. No protection against rapid page refreshes or automated requests that could overload the server

**Actions Taken:**
1. **Updated `employee/weekly_report.php` - Added Rate Limiting:**
   - Implemented session-based rate limiting following the pattern from `audit.php`
   - Set limit: 60 requests per 60-second window
   - Added automatic cleanup of expired request timestamps
   - Returns HTTP 429 with retry message when limit exceeded
   - 60-second block when limit is hit

2. **Updated `employee/weekly_report.php` - Added Filter Button:**
   - Removed `onchange="document.getElementById('filterForm').submit()"` from date inputs
   - Added a "Filter" button with search icon for Date Range view
   - Users must now manually click Filter to apply date range changes
   - Reduced unnecessary server requests from accidental date changes

3. **Updated `employee/function/report.php` - Added Debug Logging:**
   - Added logging to track date range parameters received
   - Added row count logging for payroll and attendance queries
   - Helps diagnose data fetching issues for custom date ranges

**Files Modified:**
- `employee/weekly_report.php` - Rate limiting and Filter button:
  - Lines 7-44: Rate limiting implementation
  - Lines 115-120: Filter button replacing auto-submit
- `employee/function/report.php` - Debug logging:
  - Line 26: Log received date parameters
  - Lines 149-150: Log payroll query results
  - Lines 176-177: Log attendance query results

**Rate Limiter Behavior:**
- Allows up to 60 page loads/requests per minute per user session
- Blocks user for 60 seconds if limit exceeded
- Counter resets after 60 seconds from first request
- Prevents server overload from rapid clicking or automated scraping

**Filter Button Behavior:**
- Date Range view now shows Start Date, End Date, and Filter button
- Users select dates first, then click Filter to apply
- Prevents accidental submissions while typing dates
- Branch filters and pagination preserve date range after filtering

---

### 2026-04-07

**Task:** Implement Late Status Detection for Workers in Attendance Audit

**Status:** ✅ Completed

**Problem:** The attendance audit only showed generic status (Present, Completed, Absent). Administrators needed to identify workers who arrived late (after 7:00 AM official start time) for attendance monitoring and payroll adjustments.

**Actions Taken:**
1. Added late detection logic in `employee/audit.php` attendance table display
2. Set threshold at 7:15 AM - workers time in at or after 7:15 are marked as "Late"
3. Added CSS styling for late status badge (orange background)
4. Applied late status to both "Present" and "Completed" attendance states

**Files Modified:**
- `employee/audit.php` - Late status implementation:
  - Lines 477-480: Added `.status-late` CSS class with orange styling
  - Lines 826-855: Added late detection logic:
    - Check if position is 'worker'
    - Compare time_in against 7:15 AM threshold
    - Override status to "Late" if worker is late

**Late Detection Logic:**
- Only applies to employees with position = 'worker'
- Threshold: 7:15 AM (official start time is 7:00 AM)
- Workers who time in at exactly 7:15:00 or later get "Late" status
- Shows "Late" instead of "Present" or "Completed"
- Other positions (Admin, Super Admin, etc.) are not affected

**Usage:**
1. Navigate to Attendance Audit page
2. Select a date with attendance records
3. Workers who timed in at 7:15 or later will display orange "Late" status badge
4. This helps identify tardiness patterns for HR/payroll review

---

### 2026-04-07

**Task:** Implement QR Scan Overtime Detection Feature

**Status:** ✅ Completed | Later Reverted

**Problem:** Workers and Drivers who clocked out after 4:15 PM needed a way to report overtime hours automatically. The system needed to detect when workers exceeded regular hours and prompt them to confirm if the extra time was overtime.

**Requirements:**
- Trigger overtime prompt when clock-out is 4:15 PM or later
- Calculate overtime hours from 4:00 PM to actual clock-out time
- Only apply to 'Worker' and 'Driver' positions
- Prompt worker to confirm if time was overtime
- If yes, submit overtime request automatically with reason
- Integrate with existing notification and logging systems

**Actions Taken:**
1. Created overtime detection modal in `select_employee.php` with:
   - Hours display showing calculated overtime
   - YES/NO buttons for confirmation
   - Reason textarea (minimum 10 characters)
   - Mobile responsive styling with media queries
2. Modified `api/qr_clock.php` to detect overtime:
   - Check if position is Worker/Driver
   - Compare clock-out time against 4:15 PM threshold
   - Calculate hours from 4:00 PM to clock-out time
   - Return overtime data in JSON response
3. Modified `api/clock_out.php` with same overtime detection logic
4. Updated `overtime_request.php` to accept QR-generated requests:
   - Allow up to 8 hours for QR requests (vs 4 for manual)
   - Set `requested_by = 'QR Clock-out'` for tracking
   - Added logging for QR overtime requests
5. Added `checkOvertimeOnClockOut()` function call in `attendance.js`
6. Fixed HTML/PHP structure issue in `select_employee.php` where PHP leaked into JavaScript
7. Added mobile responsive CSS with breakpoints at 640px and 380px

**Files Modified:**
- `employee/select_employee.php` - Overtime modal HTML, CSS, JavaScript handlers
- `employee/api/qr_clock.php` - Overtime detection logic for QR clock-out
- `employee/api/clock_out.php` - Overtime detection for regular clock-out
- `overtime_request.php` - QR-specific validation and logging
- `employee/js/attendance.js` - Overtime check integration in performClockOut()

**Feature Behavior:**
- Clock-out at 4:16 PM → Shows modal: "Overtime Detected: 0.27 hrs (4:00 PM - 4:16 PM)"
- Worker clicks "Yes, It Was Overtime" → Shows reason form
- Worker enters reason (min 10 chars) → Submits overtime request
- Request appears in admin notifications with "QR Clock-out" as requester
- Activity logged to logs.php

**Revert Decision:** Feature was later reverted per user request. See below entry for revert details.

---

### 2026-04-07

**Task:** Revert QR Scan Overtime Detection Feature

**Status:** ✅ Completed

**Problem:** User requested to undo/remove the QR scan overtime feature that was implemented earlier in the day.

**Actions Taken:**
1. Reverted `select_employee.php`:
   - Removed overtime modal HTML
   - Removed overtime CSS styles (180+ lines)
   - Removed overtime JavaScript handlers (100+ lines)
2. Reverted `api/qr_clock.php`:
   - Removed overtime detection logic (35 lines)
   - Simplified JSON response to remove overtime fields
3. Reverted `api/clock_out.php`:
   - Removed overtime detection logic (33 lines)
   - Removed overtime data from response
4. Reverted `overtime_request.php`:
   - Changed max hours back to 4 (removed QR 8-hour exception)
   - Removed QR source tracking (`requested_by = 'QR Clock-out'`)
   - Removed QR-specific logging
5. Reverted `employee/js/attendance.js`:
   - Removed `checkOvertimeOnClockOut()` call from performClockOut()

**Files Modified:**
- `employee/select_employee.php` - Removed overtime modal, CSS, and JavaScript
- `employee/api/qr_clock.php` - Removed overtime detection
- `employee/api/clock_out.php` - Removed overtime detection
- `overtime_request.php` - Reverted to original 4-hour max validation
- `employee/js/attendance.js` - Removed overtime check integration

**Files Created:**
- `UNDO_QR_OVERTIME.md` - Complete guide on how to undo the feature with all code snippets

**Result:** QR overtime feature fully reverted. Clock-out now works normally without prompting for overtime. Manual overtime requests via the kebab menu still work as before.

---

### 2026-04-07

**Task:** Individual Employee Attendance Calendar

**Status:** ✅ Completed

**Problem:** HR needed a way to view individual employee attendance patterns in a calendar format. The existing audit.php only showed records in a list view by date range, making it difficult to identify attendance patterns, late days, and absent days at a glance.

**Requirements:**
- Click employee name to view their monthly attendance calendar
- Display time-in and time-out for each day
- Mark absent days clearly
- Show status: Present (green), Late (orange), Absent (red)
- Late detection for Workers (7:15 AM threshold)
- Month navigation (previous/next)
- Mobile responsive design

**Actions Taken:**
1. Created new API endpoint `employee/api/get_employee_attendance_detailed.php`:
   - Returns employee info (name, position)
   - Queries attendance records for specified month
   - Formats time_in/time_out as h:i A
   - Calculates Late status for Workers (7:15 AM threshold)
   - Returns array of all days with status
2. Modified `employee/audit.php`:
   - Made employee names clickable with hover effect
   - Added calendar modal HTML with dark/gold theme
   - Implemented month navigation (prev/next buttons)
   - Added loading state while fetching data
   - Created calendar grid rendering (7 columns, Sun-Sat)
   - Added status color coding (green/orange/red)
   - Included legend at bottom
   - Added responsive CSS for mobile/tablet
3. JavaScript functions implemented:
   - `openEmployeeCalendar(employeeId, name)` - Opens modal, loads data
   - `closeIndividualCalendar()` - Closes modal
   - `navigateIndividualCalendar(direction)` - Month navigation
   - `loadIndividualCalendarData()` - Fetches API data
   - `renderIndividualCalendar(data)` - Renders calendar grid
   - `createDayElement()` - Creates individual day cells
4. Calendar features:
   - Shows previous/next month days grayed out
   - Highlights today with gold border
   - Displays time_in (green) and time_out (yellow-green)
   - Status badge at bottom of each day cell
   - Click outside or Escape key to close

**Files Created:**
- `employee/api/get_employee_attendance_detailed.php` - API endpoint for detailed attendance data

**Files Modified:**
- `employee/audit.php` - Added calendar modal, CSS, and JavaScript

**Usage:**
1. Navigate to Attendance Audit page
2. Click on any employee name in the attendance table
3. Calendar modal opens showing current month's attendance
4. View daily time-in/time-out and status (Present/Late/Absent)
5. Use arrows to navigate to previous/next months
6. Click X, press Escape, or click backdrop to close

**Technical Details:**
- API accepts `employee_id` and `month` (YYYY-MM) parameters
- Late status calculated for Workers only (time_in >= 07:15:00)
- Mobile responsive: Full screen on mobile, hides times on very small screens
- Theme matches existing dark/gold audit.php styling

---

### 2026-04-08

**Task:** Fix update_loan.php SQL Parameter Mismatch Bug

**Status:** ✅ Completed

**Problem:** update_loan.php was throwing 500 Internal Server Error when saving SSS loan. Error log showed "ArgumentCountError: The number of variables must match the number of parameters in the prepared statement."

**Root Cause:** INSERT statement had 9 `?` placeholders but `bind_param` was trying to bind 10 values. The `take_home_pay` column was hardcoded to `0` instead of using a placeholder.

**Fix Applied:**
- Line 124: Added missing `?` placeholder for `take_home_pay` column
- Changed: `VALUES (..., ?, ?, 0, 'Not Paid', NOW())` → `VALUES (..., ?, ?, ?, 'Not Paid', NOW())`

**Files Modified:**
- `employee/update_loan.php` - Fixed INSERT statement placeholder count

---

### 2026-04-08

**Task:** Fix employees_function.php Syntax Error

**Status:** ✅ Completed

**Problem:** Parse error: syntax error, unexpected token "}" in employees_function.php on line 65

**Root Cause:** Duplicate `$msg` assignment on line 64 was missing a semicolon, causing PHP parser error.

**Fix Applied:**
- Removed duplicate line: `$msg = 'Error Only Super Admin can modify employee records'` (missing semicolon)

**Files Modified:**
- `employee/function/employees_function.php` - Removed malformed duplicate line

---

### 2026-04-08

**Task:** Change "Absent" to "No Record" for Future Days in Attendance Calendar

**Status:** ✅ Completed

**Problem:** Individual employee calendar showed "Absent" (red) for future/upcoming days that haven't occurred yet. This was misleading as employees aren't actually absent, there's just no attendance record yet.

**Actions Taken:**
1. Modified `employee/api/get_employee_attendance_detailed.php`:
   - Changed default status from `'Absent'` to `'No Record'` for days without attendance data

2. Modified `employee/audit.php`:
   - Updated JavaScript to handle "No Record" status with space-to-hyphen conversion
   - Added CSS styling for `.day-no-record` class (gray/neutral appearance)
   - Added "No Record" to calendar legend
   - Added cache-control meta tags to prevent browser caching issues

**Files Modified:**
- `employee/api/get_employee_attendance_detailed.php` - Changed default status to 'No Record'
- `employee/audit.php` - Added No Record handling, CSS, and legend

---

### 2026-04-08

**Task:** Make SSS Loan Apply to All Weeks (Monthly Loan Behavior)

**Status:** ✅ Completed

**Problem:** When setting SSS loan in Week 2, switching to Week 1 showed no loan value. Users expected the loan to persist across all weeks in the month.

**Root Cause:** SSS loan was stored per-week in `weekly_payroll_reports` table. Each week had its own independent loan value.

**Solution Implemented:**
Modified `update_loan.php` to apply the SSS loan to ALL weeks in the month when set in any week:

1. After updating existing record (lines 95-107):
   - Added second UPDATE query to set same loan value for all weeks of that employee/month

2. After inserting new record (lines 155-167):
   - Added UPDATE query to propagate loan to all other weeks in the month

**Files Modified:**
- `employee/update_loan.php` - Added monthly loan propagation logic:
  - Lines 95-107: Update all weeks after single record update
  - Lines 155-167: Update all weeks after new record insert

**Result:** SSS loan now behaves as a monthly value. Setting it in any week automatically applies it to all weeks (1-4/5) for that employee and month.

---

### 2026-04-08

**Task:** Fix Weekly Report Date Calculation

**Status:** ✅ Completed

**Problem:** Weekly date calculation was incorrect. Week 1 was showing April 1-6 instead of April 1-4, and weeks were not properly excluding Sundays.

**Actions Taken:**
1. Modified `employee/function/report.php` to generate work days excluding Sundays
2. Implemented proper week boundary detection that breaks weeks on Saturday→Monday transitions
3. Fixed logic that was blocking Saturday-to-Monday break detection in Week 1
4. Week calculation now properly groups work days into 5-day work weeks (Mon-Fri)

**Files Modified:**
- `employee/function/report.php` - Lines 50-121: Complete rewrite of weekly date calculation logic
  - Generates work days array excluding Sundays
  - Calculates week boundaries with max 5 days per week
  - Breaks weeks on Saturday→Monday transition
  - Week 1: April 1-4 (Tue-Fri), Week 2: April 6-10 (Mon-Fri), etc.

**Result:** Weekly date ranges now correctly match payroll week definitions. Week 1 starts on the 1st of the month and subsequent weeks are Mon-Fri blocks excluding Sundays.

---

### 2026-04-08

**Task:** Clean Up Settings Page Header

**Status:** ✅ Completed

**Problem:** Settings page showed notification bell and user profile info ("User: Super Admin") in the header, which was redundant with the profile information displayed in the settings form.

**Actions Taken:**
1. Modified `employee/header.php` to conditionally hide notification section on settings.php
2. Added condition to hide profile section (user name and role display) on settings.php
3. Used `$currentPage` check to identify settings page

**Files Modified:**
- `employee/header.php` - Lines 221-224, 226, 341, 343-358: Added conditional blocks to hide notification bell and profile section when on settings.php

**Result:** Settings page now has a cleaner header showing only the page title. Notification bell and user profile info are hidden on settings.php but remain visible on all other pages.

---

