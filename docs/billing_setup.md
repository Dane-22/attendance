# Billing System Setup Guide

## Overview

The `billing.php` module provides billing and payroll reporting for JAJR Construction. It aggregates payroll data from the `weekly_payroll_reports` table to generate reports for Site Salary, Office Salary, Cash Advances, and Employer Share contributions.

---

## How billing.php Works

### Data Flow

```
┌─────────────────┐     ┌─────────────────────────────┐     ┌─────────────────┐
│   attendance    │────▶│  weekly_aggregate_non_      │────▶│ weekly_payroll  │
│   employees     │     │  branch33.php (Cron)          │     │ _reports        │
└─────────────────┘     └─────────────────────────────┘     └────────┬────────┘
                                                                     │
                             ┌──────────────┐                        │
                             │  cash_advances│◀───────────────────────┘
                             └──────────────┘                        │
                                                                      ▼
                                                             ┌──────────────┐
                                                             │  billing.php  │
                                                             │  (Reports)    │
                                                             └──────────────┘
```

### Required Database Tables

| Table | Purpose | Source of Data |
|-------|---------|----------------|
| **`weekly_payroll_reports`** | Main data source for billing reports | Aggregated by cron from `daily_payroll_reports` |
| **`daily_payroll_reports`** | Daily payroll records | Populated during clock-out/time-out |
| **`employees`** | Employee information, daily rates | HR management |
| **`branches`** | Branch names and IDs | Branch management |
| **`cash_advances`** | Cash advance requests | Cash advance module |
| **`attendance`** | Attendance records with branch info | Clock in/out system |

---

## Prerequisites to Make Billing Work

### 1. Database Tables Must Exist

Ensure these tables are created in your database:

#### weekly_payroll_reports (Critical)
```sql
CREATE TABLE IF NOT EXISTS `weekly_payroll_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `report_year` int NOT NULL,
  `report_month` int NOT NULL,
  `week_number` int NOT NULL COMMENT 'Week 1-5',
  `view_type` enum('weekly','monthly') DEFAULT 'weekly',
  `branch_id` int DEFAULT NULL,
  `days_worked` int DEFAULT '0',
  `total_hours` int DEFAULT '0',
  `daily_rate` decimal(10,2) DEFAULT '0.00',
  `basic_pay` decimal(10,2) DEFAULT '0.00',
  `ot_hours` decimal(5,2) DEFAULT '0.00',
  `ot_rate` decimal(10,2) DEFAULT '0.00',
  `ot_amount` decimal(10,2) DEFAULT '0.00',
  `performance_allowance` decimal(10,2) DEFAULT '0.00',
  `gross_pay` decimal(10,2) DEFAULT '0.00',
  `gross_plus_allowance` decimal(10,2) DEFAULT '0.00',
  `ca_deduction` decimal(10,2) DEFAULT '0.00',
  `sss_deduction` decimal(10,2) DEFAULT '0.00',
  `philhealth_deduction` decimal(10,2) DEFAULT '0.00',
  `pagibig_deduction` decimal(10,2) DEFAULT '0.00',
  `sss_loan` decimal(10,2) DEFAULT '0.00',
  `total_deductions` decimal(10,2) DEFAULT '0.00',
  `take_home_pay` decimal(10,2) DEFAULT '0.00',
  `status` enum('Draft','Finalized','Processed') DEFAULT 'Draft',
  `payment_status` enum('Paid','Not Paid') DEFAULT 'Not Paid',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_employee_period_week` (`employee_id`,`report_year`,`report_month`,`week_number`,`view_type`,`branch_id`),
  KEY `idx_year_month_week` (`report_year`,`report_month`,`week_number`),
  KEY `idx_branch_id` (`branch_id`)
) ENGINE=InnoDB;
```

#### daily_payroll_reports (Source Data)
```sql
CREATE TABLE IF NOT EXISTS `daily_payroll_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `report_date` date NOT NULL,
  `report_year` int NOT NULL,
  `report_month` int NOT NULL,
  `report_day` int NOT NULL,
  `week_number` int NOT NULL DEFAULT '1',
  `branch_id` int DEFAULT NULL,
  `days_worked` decimal(4,1) DEFAULT '0.0',
  `total_hours` decimal(8,2) DEFAULT '0.00',
  `daily_rate` decimal(10,2) DEFAULT '0.00',
  `basic_pay` decimal(10,2) DEFAULT '0.00',
  `ot_hours` decimal(6,2) DEFAULT '0.00',
  `ot_rate` decimal(10,2) DEFAULT '0.00',
  `ot_amount` decimal(10,2) DEFAULT '0.00',
  `performance_allowance` decimal(10,2) DEFAULT '0.00',
  `gross_pay` decimal(10,2) DEFAULT '0.00',
  `gross_plus_allowance` decimal(10,2) DEFAULT '0.00',
  `ca_deduction` decimal(10,2) DEFAULT '0.00',
  `sss_deduction` decimal(10,2) DEFAULT '0.00',
  `philhealth_deduction` decimal(10,2) DEFAULT '0.00',
  `pagibig_deduction` decimal(10,2) DEFAULT '0.00',
  `sss_loan` decimal(10,2) DEFAULT '0.00',
  `total_deductions` decimal(10,2) DEFAULT '0.00',
  `take_home_pay` decimal(10,2) DEFAULT '0.00',
  `status` varchar(20) DEFAULT 'Pending',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_emp_date_branch` (`employee_id`,`report_date`,`branch_id`),
  KEY `idx_report_date` (`report_date`)
) ENGINE=InnoDB;
```

### 2. Cron Script Must Run

The `billing.php` relies on `weekly_payroll_reports` having data. This data is populated by:

**File:** `employee/cron/weekly_aggregate_non_branch33.php`

This script:
- Aggregates daily payroll data into weekly records
- Runs (or should run) every Friday at midnight via cron/Task Scheduler
- Excludes Branch 33 (special handling)
- Can be triggered manually via the "Generate Report" button in billing.php

#### Manual Trigger (via billing.php)
When you click "Generate Report" in billing.php, it triggers the cron via HTTP:
```php
$ch = curl_init($cronUrl);
$response = curl_exec($ch);
```

#### Automated Setup (Windows Task Scheduler)
```
Schedule: Weekly on Friday at 12:00 AM
Action: Start a program
Program: C:\wamp64\bin\php\php8.x.x\php.exe
Arguments: c:\wamp64\www\main\employee\cron\weekly_aggregate_non_branch33.php
```

### 3. Required Related Files

| File | Purpose | Status Required |
|------|---------|-----------------|
| `conn/db_connection.php` | Database connection | ✅ Must exist |
| `sidebar.php` | Navigation | ✅ Must exist |
| `css/billing.css` | Styling | ✅ Must exist |
| `cron/weekly_aggregate_non_branch33.php` | Data aggregation | ✅ Must exist & be runnable |

---

## Report Types Explained

### 1. Site Salary Report
**Query Logic:** (billing.php lines 50-70)
- Aggregates `weekly_payroll_reports` by branch
- Excludes 'Main Branch' (office employees)
- Shows: employee_count, basic_pay, ot_pay, gross_pay, deductions, net_pay

**SQL Pattern:**
```sql
SELECT b.branch_name, COUNT(DISTINCT wpr.employee_id), 
       SUM(wpr.basic_pay), SUM(wpr.ot_amount), SUM(wpr.take_home_pay)
FROM weekly_payroll_reports wpr
LEFT JOIN employees e ON wpr.employee_id = e.id
LEFT JOIN branches b ON e.branch_id = b.id
WHERE wpr.report_year = YEAR(?) AND wpr.report_month = MONTH(?)
  AND b.branch_name != 'Main Branch'
GROUP BY b.branch_name
```

### 2. Office Salary Report
**Query Logic:** (billing.php lines 74-94)
- Same as Site Salary but filters FOR 'Main Branch' only
- Shows office payroll totals

### 3. Cash Advance Report
**Query Logic:** (billing.php lines 96-129)
- Uses `cash_advances` table directly
- Joins with `employees` and `attendance` for employee details
- Shows per-employee cash advance totals and latest status

### 4. Employer Share Report
**Query Logic:** (billing.php lines 131-168)
- Calculates government contributions using formulas:
  - **SSS:** Employee share × 0.733 = Employer share (73.3%)
  - **PhilHealth:** Employee share × 1.0 = Employer share (100% match)
  - **Pag-IBIG:** Employee share × 1.0 = Employer share (100% match)

---

## Troubleshooting: Billing Shows "No Data"

### Step 1: Check if Tables Exist
```sql
SHOW TABLES LIKE 'weekly_payroll_reports';
SHOW TABLES LIKE 'daily_payroll_reports';
```

### Step 2: Check if Data Exists
```sql
-- Check weekly payroll data for current month
SELECT COUNT(*) FROM weekly_payroll_reports 
WHERE report_year = YEAR(CURDATE()) 
AND report_month = MONTH(CURDATE());

-- Check daily payroll data
SELECT COUNT(*) FROM daily_payroll_reports 
WHERE report_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY);
```

### Step 3: Run Cron Manually
1. Open browser and visit:
   ```
   http://your-server/employee/cron/weekly_aggregate_non_branch33.php
   ```
2. Check the response - should show JSON with records aggregated
3. Check log file: `employee/cron/weekly_aggregate_non_branch33.log`

### Step 4: Verify daily_payroll_reports Has Data
If `weekly_payroll_reports` is empty after running cron, check source:
```sql
SELECT * FROM daily_payroll_reports 
WHERE report_date BETWEEN '2026-02-01' AND '2026-02-28'
LIMIT 10;
```

### Step 5: Check attendance Records
If `daily_payroll_reports` is empty, check if employees are clocking out:
```sql
SELECT * FROM attendance 
WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
AND time_out IS NOT NULL
LIMIT 10;
```

---

## How Data Gets Into the System

### Complete Data Flow

```
1. Employee clocks IN via QR/time_in → attendance table (time_in set)
2. Employee clocks OUT via QR/time_out → 
   - attendance table (time_out, total_ot_hrs updated)
   - daily_payroll_reports table (INSERT/UPDATE via time_out_api.php or similar)
3. Weekly (Friday midnight) OR manually via "Generate Report":
   - weekly_aggregate_non_branch33.php runs
   - Aggregates daily_payroll_reports → weekly_payroll_reports
4. User visits billing.php:
   - Queries weekly_payroll_reports
   - Displays aggregated data
```

### Key Integration Points

**Where daily_payroll_reports gets populated:**
- Typically in `time_out_api.php` or `clock_out.php`
- When employee clocks out, daily payroll record is created

**Check your time_out logic:**
```php
// Look for code that calculates and saves daily payroll
// Example pattern:
INSERT INTO daily_payroll_reports 
(employee_id, report_date, basic_pay, ot_amount, ...)
VALUES (?, ?, ?, ?, ...)
ON DUPLICATE KEY UPDATE ...
```

---

## Quick Start Checklist

- [ ] Verify `weekly_payroll_reports` table exists
- [ ] Verify `daily_payroll_reports` table exists  
- [ ] Verify `cash_advances` table exists
- [ ] Check that employees have `daily_rate` set
- [ ] Run cron script manually: `employee/cron/weekly_aggregate_non_branch33.php`
- [ ] Check if data appears in `weekly_payroll_reports`
- [ ] Navigate to Finance → Billing in the application
- [ ] Select date range and click "Generate Report"
- [ ] Verify reports display data

---

## Important Notes

1. **Branch 33 Exclusion:** The cron script specifically excludes branch_id = 33. If employees are in branch 33, they won't appear in billing reports.

2. **Date Range:** billing.php defaults to current month (1st to last day). Make sure you have data for the selected month.

3. **Manual Aggregation:** Clicking "Generate Report" in billing.php triggers the aggregation script via HTTP cURL - this only works if the cron file is accessible via web.

4. **Payment Status:** The billing report pulls `payment_status` from `weekly_payroll_reports` - this is managed separately, usually through the weekly_report.php interface.

5. **Cash Advance:** This report type queries `cash_advances` directly (not `weekly_payroll_reports`), so it may show data even if payroll aggregation hasn't run.
