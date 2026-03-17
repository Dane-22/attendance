# Employer Share Contribution - Zero Values Troubleshooting

## Problem

The Employer Share Contribution report shows **zero values** for all contribution types (SSS, PhilHealth, Pag-IBIG):

| Contribution Type | Employee Count | Employee Share | Employer Share | Total Contribution |
|-------------------|----------------|----------------|----------------|-------------------|
| SSS | 0 | ₱0.00 | ₱0.00 | ₱0.00 |
| PhilHealth | 0 | ₱0.00 | ₱0.00 | ₱0.00 |
| Pag-IBIG | 0 | ₱0.00 | ₱0.00 | ₱0.00 |

---

## Root Causes

### 1. Missing Payroll Data in `daily_payroll_reports`

The Employer Share report queries the `daily_payroll_reports` table for deduction data:

```php
$sql = "SELECT 
            'SSS' as contribution_type,
            SUM(dpr.sss_deduction) as total_employee_share,
            ...
        FROM daily_payroll_reports dpr
        WHERE dpr.report_date BETWEEN ? AND ? AND dpr.sss_deduction > 0"
```

**If this table is empty or has no records for the selected date range, all values will be zero.**

---

### 2. Weekly Aggregation Not Run

The `daily_payroll_reports` table is populated by the **weekly aggregation script**:

**File**: `employee/cron/weekly_aggregate_non_branch33.php`

This script:
1. Calculates daily payroll for each employee
2. Computes deductions (SSS, PhilHealth, Pag-IBIG)
3. Inserts records into `daily_payroll_reports`

**Triggering Aggregation Manually**:
- Go to **Billing & Payroll Reports**
- Click the **"Generate Report"** button
- This triggers the aggregation via HTTP request

**Code in billing.php (lines 18-46)**:
```php
if (isset($_GET['generate_report']) && $_GET['generate_report'] === '1') {
    $cronUrl = "$protocol://$host$basePath/employee/cron/weekly_aggregate_non_branch33.php";
    $ch = curl_init($cronUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $response = curl_exec($ch);
    ...
}
```

---

### 3. No Employee Deductions Configured

The report only includes employees where deductions are **greater than zero**:

```sql
WHERE dpr.report_date BETWEEN ? AND ? AND dpr.sss_deduction > 0
```

If the payroll aggregation runs but deductions are calculated as 0 (or NULL), they won't appear in the report.

**Check the aggregation script to verify deduction calculations**:
- SSS deduction calculation
- PhilHealth deduction calculation
- Pag-IBIG deduction calculation

---

### 4. Date Range Mismatch

The default date range is the **current month** (March 2026 in screenshot):
- Start: `2026-03-01`
- End: `2026-03-31`

**If payroll data exists in a different date range**, the report will show zeros.

**Solutions**:
- Adjust the date range to match when payroll was processed
- Or run the aggregation for the current date range

---

### 5. Missing Database Table or Columns

Verify the `daily_payroll_reports` table exists with the required columns:

```sql
DESCRIBE daily_payroll_reports;
```

**Required columns**:
- `report_date` (date)
- `employee_id` (int)
- `sss_deduction` (decimal)
- `philhealth_deduction` (decimal)
- `pagibig_deduction` (decimal)

---

## Troubleshooting Steps

### Step 1: Check if `daily_payroll_reports` has data

```sql
SELECT COUNT(*) as total_records,
       MIN(report_date) as earliest_date,
       MAX(report_date) as latest_date
FROM daily_payroll_reports;
```

### Step 2: Check for deductions in the date range

```sql
SELECT 
    COUNT(DISTINCT employee_id) as employees_with_sss,
    SUM(sss_deduction) as total_sss,
    SUM(philhealth_deduction) as total_philhealth,
    SUM(pagibig_deduction) as total_pagibig
FROM daily_payroll_reports
WHERE report_date BETWEEN '2026-03-01' AND '2026-03-31';
```

### Step 3: Run the aggregation script manually

Navigate to:
```
https://jajrandcoc.com/employee/cron/weekly_aggregate_non_branch33.php
```

Or click **"Generate Report"** in the billing page.

### Step 4: Verify aggregation script output

Check the response from the aggregation script:
- Success: Returns "Aggregation completed successfully"
- Error: Returns error message with details

---

## Solution Checklist

| Check | Action | Status |
|-------|--------|--------|
| 1 | Verify `daily_payroll_reports` table exists | ☐ |
| 2 | Run weekly aggregation script | ☐ |
| 3 | Check date range matches payroll data | ☐ |
| 4 | Verify deductions are calculated (> 0) | ☐ |
| 5 | Refresh billing page after aggregation | ☐ |

---

## Related Files

- `employee/billing.php` - Report display logic (lines 186-222)
- `employee/cron/weekly_aggregate_non_branch33.php` - Payroll aggregation
- `employee/cron/weekly_aggregate.php` - Alternative aggregation script

---

## Quick Fix

1. Go to **Finance → Billing**
2. Select **"Employer Share Contribution"** from Report Type dropdown
3. Set appropriate date range
4. Click **"Generate Report"** button
5. Wait for aggregation to complete
6. Refresh the page to see updated values

> **Note**: If issues persist after running aggregation, check the aggregation script logs in `employee/cron/` directory for error messages.
