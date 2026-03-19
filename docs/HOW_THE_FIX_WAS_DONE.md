# How the Employer Share Zero Values Issue Was Fixed

## Problem Summary

The **Employer Share Contribution (SSS, PhilHealth, Pag-IBIG)** report showed all zeros even though **Site Salary** report displayed data correctly.

| Report | Status |
|--------|--------|
| Site Salary | ✅ Working |
| Employer Share | ❌ All zeros |

---

## Error Display

### What the User Saw

The Employer Share Contribution report displayed the following:

```
Employer Share Contribution (SSS, PhilHealth, Pag-IBIG)
Period: March 01, 2026 - March 31, 2026

| Contribution Type | Employee Count | Employee Share | Employer Share | Total Contribution |
|-------------------|----------------|----------------|------------------|-------------------|
| SSS               | 0              | ₱0.00          | ₱0.00            | ₱0.00             |
| PhilHealth        | 0              | ₱0.00          | ₱0.00            | ₱0.00             |
| Pag-IBIG          | 0              | ₱0.00          | ₱0.00            | ₱0.00             |
```

**Screenshot Evidence**: The billing page showed all contribution types with zero employees and zero amounts for the selected date range, while Site Salary report displayed actual payroll data for the same period.

### Expected vs Actual

| Expected | Actual |
|----------|--------|
| Employee Count > 0 | Employee Count = 0 |
| Employee Share > ₱0.00 | Employee Share = ₱0.00 |
| Employer Share > ₱0.00 | Employer Share = ₱0.00 |
| Total Contribution > ₱0.00 | Total Contribution = ₱0.00 |

---

## Root Cause Analysis

### 1. Different Data Sources

| Report | Data Source | Fallback |
|--------|-------------|----------|
| **Site Salary** | `daily_payroll_reports` → `attendance` table | ✅ Yes |
| **Employer Share** | `daily_payroll_reports` only | ❌ No |

### 2. The Real Issue: Wrong Script Called

The **"Generate Report"** button in `employee/billing.php` was calling the wrong script:

```php
// BEFORE (Line 28):
$cronUrl = "$protocol://$host$basePath/employee/cron/weekly_aggregate_non_branch33.php";
```

**Problem**: `weekly_aggregate_non_branch33.php` does **NOT** populate `daily_payroll_reports` table with deduction data.

### 3. Missing Payroll Data

The `daily_payroll_reports` table was **empty** because:
- No daily payroll generation script had been run
- The aggregation script doesn't calculate individual deductions
- No automation (cron job) was set up

---

## Investigation Process

### Step 1: Reviewed `employee/billing.php`

Found the Employer Share query at lines 186-222:
```php
case 'employer_share':
    $sql = "SELECT 
                'SSS' as contribution_type,
                SUM(dpr.sss_deduction) as total_employee_share,
                SUM(dpr.sss_deduction) * 0.0733 as estimated_employer_share,
                ...
            FROM daily_payroll_reports dpr
            WHERE dpr.report_date BETWEEN ? AND ? AND dpr.sss_deduction > 0
            ...";
```

> **Key finding**: Only queries `daily_payroll_reports`, no fallback to attendance.

### Step 2: Found Payroll Generation Scripts

Located two scripts that populate `daily_payroll_reports`:

| Script | Purpose | How to Run |
|--------|---------|------------|
| `generate_daily_payroll.php` | Backfill date ranges | Browser/HTTP |
| `daily_payroll_calculation.php` | Daily automation | CLI/Cron only |

### Step 3: Identified the Fix

The **"Generate Report"** button needed to call `generate_daily_payroll.php` instead of `weekly_aggregate_non_branch33.php`.

---

## Solution Implementation

### File Modified: `employee/billing.php`

#### Change 1: Updated the Script URL (Line 28)

**Before:**
```php
$cronUrl = "$protocol://$host$basePath/employee/cron/weekly_aggregate_non_branch33.php";
```

**After:**
```php
$cronUrl = "$protocol://$host$basePath/employee/cron/generate_daily_payroll.php?start_date=$startDate&end_date=$endDate";
```

#### Change 2: Increased Timeout (Line 34)

**Before:**
```php
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
```

**After:**
```php
curl_setopt($ch, CURLOPT_TIMEOUT, 120); // Increased timeout for date range processing
```

#### Change 3: Updated Success Message (Line 295)

**Before:**
```php
Weekly payroll aggregation completed.
```

**After:**
```php
Daily payroll generation completed.
```

---

## Verification

### Step 1: Tested the Payroll Generation Script

Manually ran the script with date range:
```
https://jajrandcoc.com/employee/cron/generate_daily_payroll.php?start_date=2026-03-01&end_date=2026-03-31
```

**Result**:
```
=== Daily Payroll Report Generator ===
Period: 2026-03-01 to 2026-03-31
Started: 2026-03-17 15:30:25

INSERTED: Employee 11 (AARIZ MARLOU) - 2026-03-03 - Gross: ₱875.00
INSERTED: Employee 12 (CESAR ABUBO) - 2026-03-03 - Gross: ₱550.00
...
=== Summary ===
Processed: 262 records
Skipped: 46 records (already exist)
Errors: 0 records
```

### Step 2: Verified Deductions Calculation

Checked that deductions are calculated in `generate_daily_payroll.php` (lines 106-110):

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

---

## Result

After the fix:

1. **"Generate Report"** button now calls the correct script
2. `daily_payroll_reports` table gets populated with:
   - Employee payroll data
   - SSS, PhilHealth, Pag-IBIG deductions
3. **Employer Share Contribution** report displays values instead of zeros

---

## Documentation Created

Six documentation files were created during this investigation:

| File | Purpose |
|------|---------|
| `docs/EMPLOYER_SHARE_CONTRIBUTION.md` | Overview of Employer Share system |
| `docs/EMPLOYER_SHARE_CALCULATION.md` | Detailed calculation formulas |
| `docs/EMPLOYER_SHARE_ZERO_VALUES_TROUBLESHOOTING.md` | General zero value troubleshooting |
| `docs/EMPLOYER_SHARE_VS_SITE_SALARY_ISSUE.md` | Why Site Salary works but Employer Share doesn't |
| `docs/SITE_SALARY_COMPUTATION.md` | How Site Salary is calculated |
| `docs/DAILY_PAYROLL_REPORTS_EMPTY.md` | Why daily_payroll_reports is empty |

---

## Related Code Changes

### Commit Details

```
[main dc90470] asgdf
 7 files changed, 1499 insertions(+), 5 deletions(-)
 create mode 100644 docs/DAILY_PAYROLL_REPORTS_EMPTY.md
 create mode 100644 docs/EMPLOYER_SHARE_CALCULATION.md
 create mode 100644 docs/EMPLOYER_SHARE_CONTRIBUTION.md
 create mode 100644 docs/EMPLOYER_SHARE_VS_SITE_SALARY_ISSUE.md
 create mode 100644 docs/EMPLOYER_SHARE_ZERO_VALUES_TROUBLESHOOTING.md
 create mode 100644 docs/SITE_SALARY_COMPUTATION.md
```

**Modified**: `employee/billing.php` (3 lines changed)

---

## Lessons Learned

1. **Different reports use different data sources** - Always verify which tables each report queries
2. **Check the "Generate" button logic** - It may be calling the wrong script
3. **Fallback mechanisms matter** - Site Salary works because it has a fallback to attendance data
4. **Documentation is crucial** - Complex payroll systems need clear documentation

---

## For Future Reference

### To Manually Generate Payroll Data

Navigate to:
```
https://jajrandcoc.com/employee/cron/generate_daily_payroll.php?start_date=YYYY-MM-DD&end_date=YYYY-MM-DD
```

### To Set Up Automation

Set up Windows Task Scheduler to run:
- **Program**: `C:\wamp64\bin\php\php8.x.x\php.exe`
- **Arguments**: `c:\wamp64\www\main\employee\cron\daily_payroll_calculation.php`
- **Schedule**: Daily at 12:00:00 AM

---

## Files Referenced

- `employee/billing.php` - Main billing report page
- `employee/cron/generate_daily_payroll.php` - Payroll generation script
- `employee/cron/daily_payroll_calculation.php` - Daily automation script
- `employee/cron/weekly_aggregate_non_branch33.php` - Old script (not for payroll)
