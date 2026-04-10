# Billing Integration Analysis

## Overview
This document analyzes the existing payroll, overtime, cash advance, and employer contribution calculations to support the billing.php integration.

---

## 1. Salary Per Branch (Site Salary)

### Data Source: `employee/weekly_report.php` + `employee/function/report.php`

### Calculation Logic
The salary per branch is calculated from the `daily_payroll_reports` table as the primary source, with attendance table as fallback.

#### Key Queries (from `billing.php` lines 58-100):
```php
// Primary source: daily_payroll_reports table
SELECT 
    COALESCE(b.branch_name, 'Unassigned') as branch_name,
    COUNT(DISTINCT dpr.employee_id) as employee_count,
    SUM(dpr.basic_pay) as total_basic_pay,
    SUM(dpr.ot_amount) as total_ot_pay,
    SUM(dpr.gross_pay) as total_gross_pay,
    SUM(dpr.total_deductions) as total_deductions,
    SUM(dpr.take_home_pay) as total_net_pay
FROM daily_payroll_reports dpr
LEFT JOIN employees e ON dpr.employee_id = e.id
LEFT JOIN branches b ON dpr.branch_id = b.id
WHERE dpr.report_date BETWEEN ? AND ?
  AND (b.branch_name IS NULL OR UPPER(b.branch_name) != 'MAIN OFFICE')
GROUP BY b.branch_name
ORDER BY b.branch_name
```

### Payroll Calculation Fields (from `function/report.php` lines 166-186):
| Field | Source | Description |
|-------|--------|-------------|
| `basic_pay` | daily_rate × days_worked | Base salary |
| `ot_amount` | ot_hours × (daily_rate / 8) | Overtime pay |
| `gross_pay` | basic_pay + ot_amount | Before deductions |
| `performance_allowance` | employees.performance_allowance | Variable allowance |
| `total_deductions` | sss + philhealth + pagibig + ca_deduction + sss_loan | All deductions |
| `take_home_pay` | gross_plus_allowance - total_deductions | Net pay |

### Government Deductions (from `function/report.php` lines 244-281):

**Monthly Deductions:**
- PhilHealth: ₱250.00
- SSS: ₱450.00
- Pag-IBIG: ₱200.00

**Weekly Prorated Deductions:**
| Week | SSS | PhilHealth | Pag-IBIG |
|------|-----|------------|----------|
| Week 1 | ₱250 | ₱100 | ₱50 |
| Week 2 | ₱100 | ₱100 | ₱50 |
| Week 3 | ₱100 | ₱50 | ₱100 |
| Week 4-5 | ₱0 | ₱0 | ₱0 |

### Branch Aggregation Logic
The system uses `daily_payroll_reports.branch_id` to group salary data by branch.

---

## 2. Cash Advance Per Employee

### Data Source: `employee/cash_advance.php`

### Database Structure
**Table: `cash_advances`**
| Field | Type | Description |
|-------|------|-------------|
| `id` | int | Primary key |
| `employee_id` | int | FK to employees |
| `amount` | decimal | CA amount |
| `particular` | varchar | 'Cash Advance' or 'Payment' |
| `reason` | text | Request reason |
| `request_date` | datetime | Date of request |
| `status` | enum | 'pending', 'approved', 'paid', 'rejected' |

### Calculation Logic (from `cash_advance.php` lines 320-376):
```php
// Running balance calculation per employee
$balance = 0;
$totalCA = 0;
$totalPaid = 0;

while ($ca = mysqli_fetch_assoc($caResult)) {
    if ($ca['particular'] === 'Payment') {
        $balance -= $ca['amount'];
        $totalPaid += $ca['amount'];
    } else {
        $balance += $ca['amount'];
        $totalCA += $ca['amount'];
    }
}
```

### Query (from `billing.php` lines 154-184):
```php
SELECT e.id, 
       e.employee_code,
       CONCAT(e.first_name, ' ', COALESCE(e.middle_name, ''), ' ', e.last_name) as full_name,
       COALESCE(a.branch_name, 'Unassigned') as branch_name,
       SUM(ca.amount) as total_cash_advance,
       COUNT(ca.id) as request_count,
       ca2.status as latest_status
FROM employees e
LEFT JOIN (
    SELECT DISTINCT employee_id, branch_name
    FROM attendance
    WHERE attendance_date BETWEEN ? AND ?
) a ON e.id = a.employee_id
LEFT JOIN cash_advances ca ON e.id = ca.employee_id 
    AND ca.request_date >= ? AND ca.request_date <= ?
LEFT JOIN (
    SELECT employee_id, status
    FROM cash_advances ca1
    WHERE request_date = (
        SELECT MAX(request_date) 
        FROM cash_advances 
        WHERE employee_id = ca1.employee_id
    )
) ca2 ON e.id = ca2.employee_id
GROUP BY e.id
HAVING total_cash_advance > 0
ORDER BY total_cash_advance DESC
```

### Cash Advance Integration with Payroll
From `weekly_report.php` (line 343, 389-398, 421):
- CA deduction is deducted from gross pay in payroll calculations
- Input field allows manual CA entry: `<input type="number" name="ca_<?php echo $emp_id; ?>" id="ca_<?php echo $emp_id; ?>" value="0">`
- CA is included in total deductions calculation

---

## 3. Employer Share Contribution

### Data Source: `billing.php` lines 187-223

### Calculation Logic
Employer contributions are estimated based on employee deduction amounts:

| Contribution Type | Employee Share | Employer Share Rate | Total Contribution |
|-------------------|----------------|---------------------|-------------------|
| **SSS** | SUM(sss_deduction) | × 0.733 (73.3%) | Employee + Employer |
| **PhilHealth** | SUM(philhealth_deduction) | × 1.0 (100%) | Employee + Employer (50/50) |
| **Pag-IBIG** | SUM(pagibig_deduction) | × 1.0 (100%) | Employee + Employer (50/50) |

### Query (from `billing.php` lines 189-222):
```php
SELECT 
    'SSS' as contribution_type,
    SUM(dpr.sss_deduction) as total_employee_share,
    SUM(dpr.sss_deduction) * 0.0733 as estimated_employer_share,
    SUM(dpr.sss_deduction) * 1.0733 as total_contribution,
    COUNT(DISTINCT dpr.employee_id) as employee_count
FROM daily_payroll_reports dpr
WHERE dpr.report_date BETWEEN ? AND ? AND dpr.sss_deduction > 0

UNION ALL

SELECT 
    'PhilHealth' as contribution_type,
    SUM(dpr.philhealth_deduction) as total_employee_share,
    SUM(dpr.philhealth_deduction) as estimated_employer_share,
    SUM(dpr.philhealth_deduction) * 2 as total_contribution,
    COUNT(DISTINCT dpr.employee_id) as employee_count
FROM daily_payroll_reports dpr
WHERE dpr.report_date BETWEEN ? AND ? AND dpr.philhealth_deduction > 0

UNION ALL

SELECT 
    'Pag-IBIG' as contribution_type,
    SUM(dpr.pagibig_deduction) as total_employee_share,
    SUM(dpr.pagibig_deduction) as estimated_employer_share,
    SUM(dpr.pagibig_deduction) * 2 as total_contribution,
    COUNT(DISTINCT dpr.employee_id) as employee_count
FROM daily_payroll_reports dpr
WHERE dpr.report_date BETWEEN ? AND ? AND dpr.pagibig_deduction > 0
```

### Employer Share Formula Reference
| Type | Employee Pays | Employer Pays | Formula |
|------|---------------|---------------|---------|
| SSS | 100% | 73.3% | Employee × 0.733 |
| PhilHealth | 50% | 50% | Employee × 1.0 |
| Pag-IBIG | 50% | 50% | Employee × 1.0 |

---

## 4. Overtime Calculation

### Data Source: `employee/overtime.php`

### Calculation Logic (from `overtime.php` lines 93-126):
```php
$ot_rate = floatval($row['daily_rate']) / 8;  // Hourly rate = daily / 8 hours
$ot_amount = $ot_hours * $ot_rate;           // OT pay = hours × hourly rate
```

### Overtime Table Structure
**Table: `attendance`**
| Field | Description |
|-------|-------------|
| `total_ot_hrs` | Accumulated overtime hours |
| `time_in` | Clock in time |
| `time_out` | Clock out time |
| `branch_name` | Branch where OT occurred |

### OT Rate Formula
```
Hourly Rate = Daily Rate ÷ 8
OT Amount = OT Hours × Hourly Rate
```

---

## 5. Integration Points for billing.php

### Current billing.php Filters (lines 53-224):
1. `site_salary` - Aggregated by branch (excludes Main Office)
2. `office_salary` - Main Office only
3. `cash_advance` - Per employee cash advances
4. `employer_share` - SSS, PhilHealth, Pag-IBIG contributions

### Data Flow
1. **Daily Payroll Generation** (`cron/generate_daily_payroll.php`)
   - Processes attendance records
   - Calculates basic pay, OT, allowances
   - Applies deductions
   - Stores in `daily_payroll_reports`

2. **Billing Report** (`billing.php`)
   - Queries aggregated data from `daily_payroll_reports`
   - Groups by branch, employee, or contribution type
   - Displays in table format

### Key Database Tables
| Table | Purpose |
|-------|---------|
| `daily_payroll_reports` | Pre-calculated daily payroll per employee |
| `attendance` | Raw clock-in/out records |
| `cash_advances` | Cash advance transactions |
| `employees` | Employee master data |
| `branches` | Branch master data |

---

## 6. Suggested Improvements for billing.php

### 1. Combined Summary View
Create a filter that shows all categories in one view:
```php
$filter = 'all_summary';
// Returns: Site Salary + Office Salary + Cash Advance + Employer Share totals
```

### 2. Branch Breakdown Enhancement
Add drill-down capability:
- Click branch → See employee list for that branch
- Click employee → See daily breakdown

### 3. Period Comparison
Add month-over-month or week-over-week comparison:
```php
$filter = 'period_comparison';
// Shows: Current Period vs Previous Period vs Variance
```

### 4. Export Enhancements
- PDF generation with official format
- CSV export for accounting software
- BIR-compatible report format

### 5. Real-time Calculation
Instead of relying on pre-generated daily_payroll_reports, add option for real-time calculation from attendance data.

---

## File References

| File | Purpose |
|------|---------|
| `employee/weekly_report.php` | Weekly payroll report with detailed deductions |
| `employee/function/report.php` | Core payroll calculation logic |
| `employee/overtime.php` | Overtime tracking and calculation |
| `employee/cash_advance.php` | Cash advance ledger per employee |
| `employee/billing.php` | Main billing and aggregation report |
| `employee/cron/generate_daily_payroll.php` | Daily payroll pre-calculation |

---

*Document generated for billing.php integration analysis*
