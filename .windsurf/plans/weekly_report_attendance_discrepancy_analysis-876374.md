# Weekly Report vs Audit Attendance Discrepancy Analysis

## Summary
Employee Menuel Benitez shows **5 days worked** in `weekly_report.php` but only **4 attendance records** in `audit.php` calendar view. This document analyzes the root cause and provides a remediation plan.

---

## Root Cause Analysis

### 1. Different Data Sources and Validation Logic

| System | Data Source | Time-in Requirement | Time-out Requirement |
|--------|-------------|---------------------|----------------------|
| `audit.php` calendar | `attendance` table | `time_in IS NOT NULL` | **NOT REQUIRED** |
| `weekly_report.php` | `daily_payroll_reports` + `attendance` fallback | Required | **BOTH REQUIRED** |

### 2. Key Code Differences

**audit.php** (`api/get_employee_attendance_detailed.php:85-96`):
```php
SELECT a.attendance_date, a.time_in, a.time_out, a.status, a.branch_name
FROM attendance a
WHERE a.employee_id = ?
  AND a.attendance_date BETWEEN ? AND ?
  AND a.time_in IS NOT NULL  -- ONLY checks time_in exists
```
- **Shows ANY record with time_in**, even if time_out is NULL
- Days with incomplete clock-out still appear in calendar

**report.php** (`function/report.php:431-436`):
```php
// Only include attendance in report totals AFTER employee has timed out
$time_in = $row['time_in'] ?? null;
$time_out = $row['time_out'] ?? null;
if (empty($time_in) || empty($time_out)) {
    continue;  // SKIPS records without BOTH time_in AND time_out
}
```

**report.php** (`function/report.php:508-515`):
```php
// Filter out records without valid times
$all_records = array_filter($all_records, function($r) {
    return !empty($r['time_in']) && !empty($r['time_out']);
});

if (empty($all_records)) {
    continue;  // Day doesn't count if no valid time_out
}
```

### 3. The Actual Discrepancy Mechanism

The mismatch occurs because:

1. **Employee clocks in but doesn't clock out** (time_in exists, time_out is NULL)
2. **audit.php calendar** (`get_employee_attendance_detailed.php`): 
   - Query only filters `time_in IS NOT NULL`
   - Shows the day as having attendance (5 days visible)
3. **weekly_report.php** (`report.php`):
   - Requires BOTH `time_in` AND `time_out` (lines 434-436, 510)
   - Incomplete records are filtered out with `continue`
   - Only counts days with complete clock-in/out (4 days counted)

### 4. Cron Job Behavior

**daily_payroll_calculation.php** (line 128-136):
```php
// Calculate worked hours
$worked_hours = 0;
if ($time_in && $time_out) {  // BOTH required for calculation
    $start_ts = strtotime($time_in);
    $end_ts = strtotime($time_out);
    if ($start_ts !== false && $end_ts !== false && $end_ts > $start_ts) {
        $worked_hours = ($end_ts - $start_ts) / 3600;
    }
}
```
- If time_out is missing, worked_hours = 0
- Days with 0 hours may not be saved to `daily_payroll_reports`
- OR may be saved as 0 days worked depending on downstream logic

---

## Evidence from Screenshots

### Calendar View (audit.php)
- Shows actual `attendance` table records
- April 2026: Records on April 1, 2, 6, 7, 8, 9, 10 visible
- Some days show "Late" status, multiple records per day

### Weekly Report (weekly_report.php)
- Week 2: Apr 06 - Apr 10, 2026
- Shows: **5 days worked**
- Data source: `daily_payroll_reports` table

### The Gap
If audit.php shows fewer records than weekly_report.php, the extra days in weekly_report are coming from `daily_payroll_reports` entries that either:
1. Have no corresponding `attendance` record
2. Were inserted manually or by scripts
3. Are stale/obsolete records from data migrations

---

## Impact Assessment

### Affected Components
1. **Payroll Accuracy**: Employees may be paid for days not actually worked
2. **Audit Integrity**: Audit view doesn't match payroll view
3. **Data Trust**: Stakeholders see different numbers in different screens

### Risk Level: **HIGH**
- Payroll overpayment possible
- Compliance issues with labor record keeping
- Difficulty reconciling attendance for disputes

---

## Remediation Plan

### Phase 1: Data Investigation (Immediate)

#### 1.1 Query to Find Incomplete Clock-out Records
```sql
-- Find attendance records with time_in but NO time_out
-- These show in audit.php but NOT counted in weekly_report.php

SELECT 
    a.employee_id,
    e.first_name,
    e.last_name,
    a.attendance_date,
    a.time_in,
    a.time_out,
    a.status,
    a.branch_name,
    TIMESTAMPDIFF(MINUTE, a.time_in, NOW()) as minutes_since_clock_in
FROM attendance a
JOIN employees e ON a.employee_id = e.id
WHERE a.time_in IS NOT NULL 
  AND a.time_out IS NULL  -- Missing clock-out!
  AND a.attendance_date >= '2026-04-01'
ORDER BY a.attendance_date DESC, e.last_name;
```

#### 1.2 Count Days with Missing Time-out per Employee
```sql
-- Count how many days each employee has incomplete attendance
SELECT 
    a.employee_id,
    e.first_name,
    e.last_name,
    COUNT(DISTINCT a.attendance_date) as incomplete_days,
    GROUP_CONCAT(DISTINCT a.attendance_date ORDER BY a.attendance_date) as dates_missing_timeout
FROM attendance a
JOIN employees e ON a.employee_id = e.id
WHERE a.time_in IS NOT NULL 
  AND a.time_out IS NULL
  AND a.attendance_date >= '2026-04-01'
GROUP BY a.employee_id, e.first_name, e.last_name
ORDER BY incomplete_days DESC;
```

#### 1.3 Verify Specific Employee (Menuel Benitez - ID 1)
```sql
-- Check all attendance records for Worker 1 in April 2026
SELECT 
    a.attendance_date,
    a.time_in,
    a.time_out,
    CASE 
        WHEN a.time_in IS NOT NULL AND a.time_out IS NOT NULL THEN 'Complete'
        WHEN a.time_in IS NOT NULL THEN 'Missing Clock-out'
        ELSE 'No Record'
    END as record_status
FROM attendance a
WHERE a.employee_id = 1
  AND a.attendance_date BETWEEN '2026-04-01' AND '2026-04-30'
ORDER BY a.attendance_date;
```

### Phase 2: Data Fix Options

#### Option A: Fix Missing Clock-outs (Recommended for legitimate records)
If the employee actually worked but forgot to clock out:
```sql
-- Update records with estimated clock-out time (e.g., 5:00 PM)
-- WARNING: Only do this after verifying with HR/employee!
UPDATE attendance 
SET time_out = CONCAT(attendance_date, ' 17:00:00'),
    status = 'Present',
    -- Calculate hours worked
    total_hours = TIMESTAMPDIFF(MINUTE, time_in, CONCAT(attendance_date, ' 17:00:00')) / 60
WHERE employee_id = 1  -- Specific employee
  AND attendance_date = '2026-04-XX'  -- Specific date
  AND time_in IS NOT NULL 
  AND time_out IS NULL;
```

#### Option B: Mark as Absent (If employee didn't actually work)
```sql
-- If the clock-in was a mistake or employee left immediately
UPDATE attendance 
SET status = 'Absent',
    time_in = NULL  -- Clear the mistaken clock-in
WHERE employee_id = 1
  AND attendance_date = '2026-04-XX'
  AND time_in IS NOT NULL 
  AND time_out IS NULL;
```

#### Option C: Regenerate daily_payroll_reports after fix
```bash
# After fixing attendance records, regenerate payroll data
php employee/cron/generate_daily_payroll.php?start_date=2026-04-01&end_date=2026-04-30
```

### Phase 3: Code Fix (Prevent Future Discrepancies)

#### 3.1 Fix audit.php Calendar to Match Report Logic
**File**: `employee/api/get_employee_attendance_detailed.php` (around line 95)

**Current**:
```php
WHERE a.employee_id = ?
  AND a.attendance_date BETWEEN ? AND ?
  AND a.time_in IS NOT NULL
```

**Fix Options**:

**Option A - Show status indicator for incomplete records**:
```php
// Add visual indicator in calendar for records missing time_out
// In the API response, add flag for incomplete attendance
$recordStatus = 'Incomplete';
if ($row['time_in'] && $row['time_out']) {
    // Check for late status
    ...
} elseif ($row['time_in']) {
    $recordStatus = 'Clocked In Only';  // Special status
}
```

**Option B - Filter to match weekly_report logic**:
```php
// Change query to require both time_in AND time_out
WHERE a.employee_id = ?
  AND a.attendance_date BETWEEN ? AND ?
  AND a.time_in IS NOT NULL
  AND a.time_out IS NOT NULL  -- ADD THIS LINE
```

#### 3.2 Add Visual Indicator in Calendar UI
**File**: `employee/audit.php` JavaScript (in renderIndividualCalendar function)

Add visual cue when time_out is missing:
```javascript
// In renderIndividualCalendar() around line 2717+
if (dayData.records.length > 0) {
    const hasIncomplete = dayData.records.some(r => !r.time_out);
    if (hasIncomplete) {
        dayStatusClass = 'status-incomplete';  // New CSS class
        dayStatusText = 'Incomplete';
    }
}
```

**CSS** (add to audit.php styles):
```css
.status-incomplete {
    background: rgba(156, 39, 176, 0.2);
    color: #9C27B0;
}
```

### Phase 4: Long-term Prevention

#### 4.1 Alert on Missing Clock-outs
Add to admin dashboard or daily notification:
```php
// New file: employee/api/get_incomplete_attendance.php
// Returns list of employees who clocked in but haven't clocked out
// Query from Phase 1.1 above
```

#### 4.2 End-of-Day Auto-Cleanup Job
Add to `daily_payroll_calculation.php` or create new cron:
```php
// At midnight, flag or auto-correct incomplete attendance from previous day
$yesterday = date('Y-m-d', strtotime('-1 day'));

// Find and alert about incomplete records
$incomplete_query = "SELECT a.employee_id, e.first_name, e.last_name, a.time_in
                     FROM attendance a
                     JOIN employees e ON a.employee_id = e.id
                     WHERE a.attendance_date = ?
                       AND a.time_in IS NOT NULL 
                       AND a.time_out IS NULL";
// Send notification to admin
```

#### 4.3 Reconciliation Report
Create new admin view:
```php
// New file: employee/attendance_reconciliation.php
// Shows: Date | Employee | Has Time In | Has Time Out | Counted in Payroll | Status
// This helps identify discrepancies before payroll is finalized
```

---

## Implementation Checklist

### Investigation Phase
- [ ] Run Phase 1.3 query to verify Worker 1's specific records
- [ ] Run Phase 1.1 query to find all employees with missing time_out
- [ ] Identify which day for Worker 1 has time_in but NO time_out
- [ ] Confirm this explains the 5 vs 4 day discrepancy

### Decision Phase
- [ ] Determine if missing time_out was legitimate (employee forgot to clock out)
- [ ] Decide: Fix data (Option A/B) OR keep as-is and fix display (Option C)
- [ ] Get approval from HR/Admin before modifying any attendance records

### Fix Phase (Choose One Path)
**If fixing data:**
- [ ] Backup `attendance` table before modifications
- [ ] Run Option A (add estimated time_out) OR Option B (mark as absent)
- [ ] Regenerate `daily_payroll_reports` for affected date range
- [ ] Verify weekly_report.php now shows correct day count

**If fixing display:**
- [ ] Modify `get_employee_attendance_detailed.php` to require time_out
- [ ] OR add visual indicator for incomplete records in calendar
- [ ] Update CSS in audit.php for new status styling

### Verification Phase
- [ ] Compare audit.php calendar count vs weekly_report.php days_worked
- [ ] Verify both systems now show matching numbers
- [ ] Test with other employees to ensure no regression

### Prevention Phase
- [ ] Implement Phase 4.1: Create API endpoint for incomplete attendance alerts
- [ ] Add dashboard widget showing employees with missing clock-outs
- [ ] Document the time_in/time_out requirement for future developers

---

## Questions for Stakeholders

1. **Data Integrity**: Should we backfill missing time_out values for past records, or only fix going forward?
2. **Policy**: What is the policy for employees who forget to clock out - do we estimate based on schedule or mark as absent?
3. **Source of Truth**: Should `daily_payroll_reports` be recalculated from `attendance` each time, or kept as historical record?
4. **UX**: Should incomplete records (time_in only) show in the calendar with a warning, or be hidden entirely?
5. **Alerting**: Should admin receive daily alerts about employees who haven't clocked out by end of shift?

---

## Appendix: Affected Files

| File | Purpose | Key Line Numbers |
|------|---------|------------------|
| `employee/api/get_employee_attendance_detailed.php` | Calendar API - audit.php data source | 85-96 (query filters) |
| `employee/function/report.php` | Payroll calculation - requires both time_in & time_out | 431-436, 508-515 (validation) |
| `employee/cron/daily_payroll_calculation.php` | Daily payroll cron - requires both times | 128-136 (hour calc) |
| `employee/audit.php` | Calendar UI - render function | 2702+ (renderIndividualCalendar) |
| `employee/weekly_report.php` | Report display - uses report.php data | N/A (display only) |

---

## Quick Reference: Root Cause

```
DISCREPANCY: audit.php shows 4 days, weekly_report.php shows 5 days

ACTUAL CAUSE: 
  - audit.php counts records with time_in only (shows incomplete days)
  - weekly_report.php requires BOTH time_in AND time_out
  
Worker 1 likely has:
  - 4 days with complete clock-in/out (counted in both systems)
  - 1 day with clock-in but NO clock-out (shown in audit, NOT counted in weekly_report)
  
SOLUTION: Either complete the missing time_out data, or make audit.php 
          filter match weekly_report.php logic
```

---

*Document generated: April 10, 2026*
*Issue ID: 876374*
*Analysis corrected: April 10, 2026*
