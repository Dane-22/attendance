# Why is daily_payroll_reports Empty?

## Overview

The `daily_payroll_reports` table is the central data source for payroll calculations including Employer Share Contribution. When this table is empty, reports show zero values.

---

## How daily_payroll_reports Gets Populated

There are **three ways** data gets inserted into this table:

### 1. Daily Payroll Calculation Script (CLI/Cron)

**File**: `employee/cron/daily_payroll_calculation.php`

**Purpose**: Runs every midnight to calculate payroll for the previous day

**Requirements**:
- Must be run from **command line (CLI)** only
- Requires Windows Task Scheduler or cron job setup
- Processes only **one day at a time** (yesterday's attendance)

**Code restriction (lines 21-25)**:
```php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("This script can only be run from command line.\n");
}
```

**Deduction logic (lines 253-270)**:
```php
// Branch 33: Apply deductions only on week 4 (monthly)
if ($branch_id == 33) {
    $apply_deductions = ($week_number == 4);
} else {
    // Non-Branch 33: Apply deductions on weeks 1-3 only
    $apply_deductions = ($week_number >= 1 && $week_number <= 3);
}
```

> **Problem**: If this script is not scheduled or fails to run, no daily records are created.

---

### 2. Generate Daily Payroll Script (Browser/URL)

**File**: `employee/cron/generate_daily_payroll.php`

**Purpose**: Backfill or update daily payroll records for a date range

**Usage**:
```
https://jajrandcoc.com/employee/cron/generate_daily_payroll.php?start_date=2026-03-01&end_date=2026-03-31
```

**Features**:
- Can process **multiple days at once**
- Accessible via **browser or HTTP request**
- Skips existing records (line 75-78)

**Deduction calculation (lines 106-110)**:
```php
// Government deduction constants (monthly)
$MONTHLY_PHILHEALTH = 250.00;
$MONTHLY_SSS = 450.00;
$MONTHLY_PAGIBIG = 200.00;

// Pro-rated daily
$days_in_month = cal_days_in_month(CAL_GREGORIAN, ...);
$sss_deduction = $MONTHLY_SSS / $days_in_month;
$philhealth_deduction = $MONTHLY_PHILHEALTH / $days_in_month;
$pagibig_deduction = $MONTHLY_PAGIBIG / $days_in_month;
```

**Employee filter (line 50)**:
```php
AND LOWER(e.position) = 'worker'
```

> **Problem**: Only processes employees with position = 'worker', not 'Security Guard' or others.

---

### 3. Billing Page "Generate Report" Button

**File**: `employee/billing.php` (lines 18-46)

**Trigger**: Clicking "Generate Report" button

**What it does**:
```php
$cronUrl = "$protocol://$host$basePath/employee/cron/weekly_aggregate_non_branch33.php";
$ch = curl_init($cronUrl);
```

**Note**: This calls `weekly_aggregate_non_branch33.php`, NOT `generate_daily_payroll.php`

> **Problem**: The weekly aggregation may not populate `daily_payroll_reports` properly.

---

## Reasons Why Table is Empty

### 1. No Automation Set Up

The daily calculation script requires scheduled task setup:

**Windows Task Scheduler Setup**:
```
Program: C:\wamp64\bin\php\php8.x.x\php.exe
Arguments: c:\wamp64\www\main\employee\cron\daily_payroll_calculation.php
Schedule: Daily at 12:00:00 AM
```

**Without this**: No automatic daily records are created.

---

### 2. Wrong Script Called from Billing Page

The billing page calls `weekly_aggregate_non_branch33.php`, but the actual payroll generation is in:
- `generate_daily_payroll.php` (for backfill)
- `daily_payroll_calculation.php` (for daily automation)

**Line 28 in billing.php**:
```php
$cronUrl = "$protocol://$host$basePath/employee/cron/weekly_aggregate_non_branch33.php";
```

> This may not actually populate `daily_payroll_reports`.

---

### 3. No Attendance Data

Both scripts require attendance records:

```sql
-- From generate_daily_payroll.php
SELECT a.employee_id, a.attendance_date, a.time_in, a.time_out, a.total_ot_hrs, ...
FROM attendance a
JOIN employees e ON a.employee_id = e.id
WHERE a.attendance_date BETWEEN ? AND ?
  AND a.time_out IS NOT NULL
  AND e.status = 'Active'
  AND LOWER(e.position) = 'worker'
```

**Requirements**:
- Employee must have `status = 'Active'`
- Employee must have `position = 'worker'` (lowercase)
- Attendance must have `time_out IS NOT NULL`

---

### 4. Employee Position Filter

**generate_daily_payroll.php** filters by position:
```php
AND LOWER(e.position) = 'worker'
```

If employees have positions like:
- "Worker" (uppercase)
- "Security Guard"
- "Supervisor"
- "Foreman"

They will **NOT** be processed.

---

### 5. Branch/Deduction Timing Logic

The `daily_payroll_calculation.php` has complex deduction timing:

| Branch Type | Week 1 | Week 2 | Week 3 | Week 4 |
|-------------|--------|--------|--------|--------|
| **Branch 33** | No deductions | No deductions | No deductions | **Deductions applied** |
| **Other Branches** | **Deductions** | **Deductions** | **Deductions** | No deductions |

This means:
- For **Branch 33**: Only week 4 has deductions
- For **Other Branches**: Only weeks 1-3 have deductions

If you're looking at the wrong week, deductions may be zero.

---

### 6. Deduction Constants Not Set

In `daily_payroll_calculation.php` (lines 73-77):
```php
$sss_deduction = ($row['position'] !== 'Security Guard') ? 800 : 0;
$philhealth_deduction = ($row['position'] !== 'Security Guard') ? 300 : 0;
$pagibig_deduction = ($row['position'] !== 'Security Guard') ? 200 : 0;
$total_deductions = ($row['position'] !== 'Security Guard') ? 1300 : 0;
```

**Security Guards get ZERO deductions** automatically.

---

## How to Fix It

### Option 1: Run Generate Daily Payroll Script

Navigate to this URL in your browser:
```
https://jajrandcoc.com/employee/cron/generate_daily_payroll.php?start_date=2026-03-01&end_date=2026-03-31
```

This will:
1. Process all attendance for the date range
2. Calculate daily payroll for each employee
3. Insert records into `daily_payroll_reports`
4. Show output of processed/skipped/error counts

---

### Option 2: Check and Fix Position Values

```sql
-- Check employee positions
SELECT position, COUNT(*) as count 
FROM employees 
WHERE status = 'Active'
GROUP BY position;
```

**If positions are not lowercase 'worker'**, update them:
```sql
UPDATE employees 
SET position = 'worker' 
WHERE LOWER(position) LIKE '%worker%' OR position IS NULL;
```

---

### Option 3: Remove Position Filter (Quick Fix)

Edit `generate_daily_payroll.php` line 50:
```php
// Remove or comment out this filter:
// AND LOWER(e.position) = 'worker'
```

Or change to include all active employees:
```php
AND e.status = 'Active'
```

---

### Option 4: Fix Billing Page Button

Edit `employee/billing.php` line 28:
```php
// Change from:
$cronUrl = "$protocol://$host$basePath/employee/cron/weekly_aggregate_non_branch33.php";

// To:
$cronUrl = "$protocol://$host$basePath/employee/cron/generate_daily_payroll.php?start_date=$startDate&end_date=$endDate";
```

---

### Option 5: Set Up Windows Task Scheduler

1. Open Task Scheduler (`taskschd.msc`)
2. Create Basic Task
3. Name: "Daily Payroll Calculation"
4. Trigger: Daily at 12:00:00 AM
5. Action: Start a program
6. Program: `C:\wamp64\bin\php\php8.x.x\php.exe`
7. Arguments: `c:\wamp64\www\main\employee\cron\daily_payroll_calculation.php`

---

## Verification Queries

### Check if table exists
```sql
SHOW TABLES LIKE 'daily_payroll_reports';
```

### Check table structure
```sql
DESCRIBE daily_payroll_reports;
```

### Check for records
```sql
SELECT COUNT(*) as total_records,
       MIN(report_date) as earliest,
       MAX(report_date) as latest
FROM daily_payroll_reports;
```

### Check deductions
```sql
SELECT 
    SUM(sss_deduction) as total_sss,
    SUM(philhealth_deduction) as total_philhealth,
    SUM(pagibig_deduction) as total_pagibig,
    COUNT(DISTINCT employee_id) as employee_count
FROM daily_payroll_reports
WHERE report_date BETWEEN '2026-03-01' AND '2026-03-31';
```

### Check employee positions
```sql
SELECT id, first_name, last_name, position, status, daily_rate
FROM employees
WHERE status = 'Active'
ORDER BY position;
```

---

## Summary

| Cause | Solution |
|-------|----------|
| No automation | Set up Windows Task Scheduler for daily script |
| Wrong script called | Fix billing.php to call generate_daily_payroll.php |
| Position = 'worker' filter | Update positions or remove filter |
| No attendance data | Check attendance records for date range |
| Security Guard employees | These get zero deductions by design |
| Wrong week for deductions | Check branch timing logic (Branch 33 = week 4 only) |

---

## Related Files

- `employee/cron/generate_daily_payroll.php` - Main script to populate table
- `employee/cron/daily_payroll_calculation.php` - Daily automation script
- `employee/cron/weekly_aggregate_non_branch33.php` - Currently called by billing
- `employee/billing.php` - Report page with "Generate Report" button
