# Site Salary Works But Employer Share is Zero

## Problem Description

**Symptom**: 
- ✅ **Site Salary (Total Salary per Branch)** shows data correctly
- ❌ **Employer Share Contribution (SSS, PhilHealth, Pag-IBIG)** shows all zeros

| Report Type | Status | Data Source |
|-------------|--------|-------------|
| Site Salary | ✅ Working | `daily_payroll_reports` → Fallback to `attendance` |
| Employer Share | ❌ Zero | `daily_payroll_reports` only (no fallback) |

---

## Root Cause: Different Data Sources and Fallback Logic

### 1. Site Salary Has Fallback Mechanism

The Site Salary report uses **two-tier data retrieval**:

**Tier 1**: Try `daily_payroll_reports` table
```php
$sql = "SELECT 
            COALESCE(b.branch_name, 'Unassigned') as branch_name,
            COUNT(DISTINCT dpr.employee_id) as employee_count,
            SUM(dpr.basic_pay) as total_basic_pay,
            ...
        FROM daily_payroll_reports dpr
        ...
        WHERE dpr.report_date BETWEEN ? AND ?";
```

**Tier 2**: If empty, fallback to `attendance` + `employees` tables
```php
if (empty($data)) {
    $sql = "SELECT 
                COALESCE(b.branch_name, 'Unassigned') as branch_name,
                SUM(e.daily_rate * (CASE WHEN a.time_out IS NOT NULL THEN 1 ELSE 0 END)) as total_basic_pay,
                SUM((e.daily_rate / 8) * COALESCE(a.total_ot_hrs, 0)) as total_ot_pay,
                ...
            FROM attendance a
            LEFT JOIN employees e ON a.employee_id = e.id
            ...";
}
```

**Location in code**: `employee/billing.php` lines 56-99

> **Result**: Site Salary can calculate from raw attendance data even if payroll reports don't exist.

---

### 2. Employer Share Has NO Fallback

The Employer Share report **only** queries `daily_payroll_reports`:

```php
case 'employer_share':
    $filterTitle = 'Employer Share Contribution (SSS, PhilHealth, Pag-IBIG)';
    $sql = "SELECT 
                'SSS' as contribution_type,
                SUM(dpr.sss_deduction) as total_employee_share,
                SUM(dpr.sss_deduction) * 0.0733 as estimated_employer_share,
                ...
            FROM daily_payroll_reports dpr
            WHERE dpr.report_date BETWEEN ? AND ? AND dpr.sss_deduction > 0
            
            UNION ALL
            ...
            FROM daily_payroll_reports dpr
            WHERE dpr.report_date BETWEEN ? AND ? AND dpr.philhealth_deduction > 0
            
            UNION ALL
            ...
            FROM daily_payroll_reports dpr
            WHERE dpr.report_date BETWEEN ? AND ? AND dpr.pagibig_deduction > 0";
```

**Location in code**: `employee/billing.php` lines 186-222

> **Result**: If `daily_payroll_reports` is empty or deductions are zero, Employer Share shows nothing.

---

## Why This Happens

### Scenario 1: No Payroll Aggregation Run

The `daily_payroll_reports` table is populated by the **weekly aggregation script**:

**Script**: `employee/cron/weekly_aggregate_non_branch33.php`

**What it does**:
1. Reads attendance data
2. Calculates daily rates and overtime
3. **Computes SSS, PhilHealth, Pag-IBIG deductions**
4. Inserts into `daily_payroll_reports`

**If not run**:
- ❌ `daily_payroll_reports` is empty
- ✅ Site Salary falls back to `attendance` table → works
- ❌ Employer Share has no data → shows zero

---

### Scenario 2: Deduction Columns Are Zero/NULL

Even if payroll aggregation runs, deductions might not be calculated:

```sql
-- Query requires deductions > 0
WHERE dpr.report_date BETWEEN ? AND ? AND dpr.sss_deduction > 0
```

**Possible causes**:
1. Deduction calculation logic is commented out
2. Employee salary is below deduction threshold
3. Deduction formulas not implemented in aggregation script
4. Columns contain NULL instead of calculated values

---

## Verification Steps

### Step 1: Check if `daily_payroll_reports` exists but is empty or has no deductions

```sql
-- Check table structure and sample data
SELECT 
    report_date,
    employee_id,
    sss_deduction,
    philhealth_deduction,
    pagibig_deduction
FROM daily_payroll_reports
WHERE report_date BETWEEN '2026-03-01' AND '2026-03-31'
LIMIT 5;
```

### Step 2: Compare record counts

```sql
-- Count records in payroll reports
SELECT COUNT(*) as payroll_records 
FROM daily_payroll_reports 
WHERE report_date BETWEEN '2026-03-01' AND '2026-03-31';

-- Count records in attendance (what Site Salary uses)
SELECT COUNT(*) as attendance_records 
FROM attendance 
WHERE attendance_date BETWEEN '2026-03-01' AND '2026-03-31';
```

### Step 3: Check aggregation script logs

Look for error logs in:
- `employee/cron/weekly_aggregate_non_branch33.php` output
- Server error logs
- Any `.log` files in `employee/cron/` directory

---

## Solutions

### Solution 1: Run the Weekly Aggregation Script

**Via Billing Page**:
1. Go to **Finance → Billing**
2. Click **"Generate Report"** button
3. Wait for completion message
4. Refresh the page

**Direct URL**:
```
https://jajrandcoc.com/employee/cron/weekly_aggregate_non_branch33.php
```

---

### Solution 2: Verify Aggregation Script Calculates Deductions

Check the aggregation script to ensure it calculates deductions:

**File**: `employee/cron/weekly_aggregate_non_branch33.php`

Look for code that calculates:
```php
// SSS calculation
$sss_deduction = ...;

// PhilHealth calculation  
$philhealth_deduction = ...;

// Pag-IBIG calculation
$pagibig_deduction = ...;
```

**Sample deduction calculation logic**:
```php
// Example: Calculate SSS based on salary bracket
function calculateSSS($monthly_salary) {
    // SSS contribution table logic here
    // Return employee share amount
}

function calculatePhilHealth($monthly_salary) {
    // 4% of monthly salary, 50% employee, 50% employer
    return $monthly_salary * 0.04 * 0.5;
}

function calculatePagIBIG($monthly_salary) {
    // 2% of monthly salary (max ₱100)
    return min($monthly_salary * 0.02, 100);
}
```

---

### Solution 3: Manual Data Verification

Check if deductions are actually being computed:

```sql
-- Check if any deductions exist in the table
SELECT 
    COUNT(CASE WHEN sss_deduction > 0 THEN 1 END) as sss_count,
    COUNT(CASE WHEN philhealth_deduction > 0 THEN 1 END) as philhealth_count,
    COUNT(CASE WHEN pagibig_deduction > 0 THEN 1 END) as pagibig_count,
    SUM(sss_deduction) as total_sss,
    SUM(philhealth_deduction) as total_philhealth,
    SUM(pagibig_deduction) as total_pagibig
FROM daily_payroll_reports
WHERE report_date BETWEEN '2026-03-01' AND '2026-03-31';
```

---

## Code Comparison

### Site Salary (With Fallback)
```php
// First attempt: daily_payroll_reports
$data = fetchFromPayrollReports();

// Fallback: Calculate from attendance
if (empty($data)) {
    $data = calculateFromAttendance();
}
```

### Employer Share (No Fallback)
```php
// Only attempt: daily_payroll_reports
$data = fetchDeductionsFromPayrollReports();

// No fallback - if empty, returns zero
```

---

## Recommendation

To fix Employer Share showing zero while Site Salary works:

1. **Run the aggregation script** to populate `daily_payroll_reports`
2. **Verify the aggregation script** includes deduction calculations
3. **Check deduction logic** in the aggregation script if data exists but is zero

---

## Related Documentation

- `docs/EMPLOYER_SHARE_CONTRIBUTION.md` - How Employer Share calculation works
- `docs/EMPLOYER_SHARE_ZERO_VALUES_TROUBLESHOOTING.md` - General zero value troubleshooting
- `employee/cron/weekly_aggregate_non_branch33.php` - Payroll aggregation script
