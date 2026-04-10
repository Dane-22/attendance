# Weekly Report vs Calendar Days Worked Discrepancy Analysis

## Issue Summary

**Reported By:** User via screenshots
**Date:** April 10, 2026
**Affected Files:** 
- `employee/weekly_report.php`
- `employee/function/report.php`
- `get_employee_attendance_calendar.php`

## Observed Discrepancy

| Employee | Calendar Shows | Weekly Report Shows | Difference |
|----------|---------------|---------------------|------------|
| Kyle Arrieta | 5+ days (Apr 1, 4, 6, 7, 8, 9, 10) | 5 days | ✓ Matches for Week 2 |
| Cesar Abubo | 5+ days (Apr 1, 6, 7, 8, 9, 10) | 4 days | ✗ Missing 1 day |
| Santi Abubo | Not shown in calendar | 4 days | - |
| Marlon Aguilar | Not shown in calendar | 4 days | - |

*Note: All employees' weekly reports show Week 2: Apr 06 - Apr 10, 2026*

## Root Cause Analysis

### 1. Different Data Sources

The discrepancy exists because **two different data sources** are used:

#### Weekly Report (`employee/function/report.php`)
```php
// Primary source: daily_payroll_reports table (lines 164-214)
$payroll_query = "SELECT 
    dpr.employee_id,
    dpr.report_date,
    dpr.days_worked,
    ...
 FROM daily_payroll_reports dpr
 ...
 WHERE dpr.report_date BETWEEN ? AND ?";

// Fallback source: attendance table (lines 217-242)
$attendance_query = "SELECT a.employee_id, a.attendance_date, ...
 FROM attendance a
 WHERE a.attendance_date BETWEEN ? AND ?";
```

**Key Logic:**
- Primary data comes from `daily_payroll_reports` table
- Attendance table is ONLY used as fallback for dates NOT in daily_payroll_reports
- Lines 422-425 explicitly skip attendance data if payroll record exists:
```php
// Skip if this date is already covered by daily_payroll_reports
if (isset($employee_payroll[$emp_id]['_has_payroll_record'][$attendance_date])) {
    continue;
}
```

#### Calendar Modal (`get_employee_attendance_calendar.php`)
```php
// Direct query to attendance table ONLY
$sql = "
    SELECT
        a.attendance_date AS work_date,
        COUNT(*) AS log_count,
        ...
    FROM attendance a
    WHERE a.employee_id = ?
      AND a.attendance_date BETWEEN ? AND ?
    GROUP BY a.attendance_date
";
```

**Key Logic:**
- Queries ONLY the `attendance` table
- Does NOT check `daily_payroll_reports`
- Counts any day with `time_in OR status IN ('present', 'late')`

### 2. Why Cesar Shows 4 Days Instead of 5

For Week 2 (April 6-10, 2026):

**Cesar's Calendar (from attendance table):**
- Apr 6: 2 records (#1 Late, #2 present) - BCDA - Admin
- Apr 7: Present - BCDA - Admin
- Apr 8: Present - BCDA - Admin
- Apr 9: Present - BCDA - Admin
- Apr 10: Present - BCDA - Admin
**Total: 5 days with attendance records**

**Cesar's Weekly Report (from daily_payroll_reports):**
- Shows only 4 days worked

**Likely Cause:**
One of Cesar's days (most likely **April 6**) is either:
1. Missing from `daily_payroll_reports` table entirely, OR
2. Has `days_worked = 0` in the payroll record

The April 6 entry shows 2 separate time records (#1 and #2), which may have caused the daily payroll calculation cron job to fail or skip that day due to:
- Duplicate record handling logic
- Merge threshold issues (records within 15 minutes are merged)
- Incomplete time_out for one of the records

### 3. Why Kyle Shows Correct 5 Days

Kyle's April 6 entry shows only 1 time record with a total of 17.13 hours. The daily payroll cron job successfully processed this record, which is why all 5 days appear correctly in the weekly report.

## Technical Details

### Daily Payroll Reports Generation

The `daily_payroll_reports` table is populated by cron jobs (likely `cron/generate_daily_payroll.php` or similar):

```php
// From report.php lines 365-406
while ($row = mysqli_fetch_assoc($payroll_result)) {
    $emp_id = $row['employee_id'];
    if (isset($employee_payroll[$emp_id])) {
        $report_date = $row['report_date'];
        // Mark this date as having payroll data
        $employee_payroll[$emp_id]['_has_payroll_record'][$report_date] = true;
        
        // Accumulate totals from daily records
        $employee_payroll[$emp_id]['days_worked'] += floatval($row['days_worked'] ?? 0);
        ...
    }
}
```

### Attendance Record Requirements

For attendance to count as a "day worked" in the fallback logic:
```php
// Lines 432-436 in report.php
$time_in = $row['time_in'] ?? null;
$time_out = $row['time_out'] ?? null;
if (empty($time_in) || empty($time_out)) {
    continue;  // SKIP if missing time_in or time_out
}
```

## Recommendations

### Immediate Fix

1. **Check `daily_payroll_reports` for Cesar's April 6 record:**
```sql
SELECT * FROM daily_payroll_reports 
WHERE employee_id = (SELECT id FROM employees WHERE last_name = 'Abubo' AND first_name = 'Cesar')
AND report_date = '2026-04-06';
```

2. **Compare with attendance records for same date:**
```sql
SELECT * FROM attendance 
WHERE employee_id = (SELECT id FROM employees WHERE last_name = 'Abubo' AND first_name = 'Cesar')
AND attendance_date = '2026-04-06';
```

3. **If missing, manually regenerate daily payroll for April 6:**
Run the daily payroll generation cron job or manually insert the missing record.

### Long-term Fix

1. **Synchronize Data Sources:**
   - Ensure calendar modal uses the same `daily_payroll_reports` table as weekly report, OR
   - Make weekly report use attendance table as primary source (not recommended due to performance)

2. **Add Data Consistency Checks:**
   - Create a daily reconciliation report that flags discrepancies between `attendance` and `daily_payroll_reports`
   - Alert admins when attendance exists but no payroll record is found

3. **Fix Cron Job Logic:**
   - Review `cron/generate_daily_payroll.php` to ensure it handles multiple attendance records per day correctly
   - Ensure merged records are properly accounted for

## SQL Verification Queries

```sql
-- Check all employees with attendance but missing payroll records for Week 2 April 2026
SELECT 
    e.id,
    e.first_name,
    e.last_name,
    a.attendance_date,
    a.time_in,
    a.time_out,
    a.status
FROM attendance a
JOIN employees e ON a.employee_id = e.id
LEFT JOIN daily_payroll_reports dpr ON a.employee_id = dpr.employee_id 
    AND a.attendance_date = dpr.report_date
WHERE a.attendance_date BETWEEN '2026-04-06' AND '2026-04-10'
    AND dpr.id IS NULL
    AND a.time_in IS NOT NULL
    AND a.time_out IS NOT NULL
ORDER BY e.last_name, a.attendance_date;

-- Check daily payroll records for Week 2 April 2026
SELECT 
    e.first_name,
    e.last_name,
    dpr.report_date,
    dpr.days_worked,
    dpr.total_hours
FROM daily_payroll_reports dpr
JOIN employees e ON dpr.employee_id = e.id
WHERE dpr.report_date BETWEEN '2026-04-06' AND '2026-04-10'
ORDER BY e.last_name, dpr.report_date;
```

## Conclusion

The discrepancy is **NOT a bug in the calculation logic** but rather a **data synchronization issue** between the `attendance` table (real-time) and the `daily_payroll_reports` table (batch processed by cron). Cesar Abubo's April 6 attendance record exists in the `attendance` table (showing in the calendar) but is missing or zero-value in the `daily_payroll_reports` table (used by the weekly report).

**Action Required:**
- Verify and fix the missing `daily_payroll_reports` record for Cesar Abubo on April 6, 2026
- Investigate why the cron job failed to process Cesar's April 6 record with multiple time entries
