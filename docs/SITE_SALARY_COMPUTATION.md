# Site Salary Computation

## Overview

The **Site Salary (Total Salary per Branch)** report calculates total payroll costs per branch for employees working at construction sites (excluding Main Office).

## Location

- **File**: `employee/billing.php`
- **Filter**: `site_salary`
- **Code Range**: Lines 52-100

---

## Computation Logic

### Data Retrieval Strategy

Site Salary uses a **two-tier fallback approach**:

#### Tier 1: Daily Payroll Reports (Preferred Source)

```php
$sql = "SELECT 
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
        ORDER BY b.branch_name";
```

**Fields from `daily_payroll_reports`**:

| Field | Description |
|-------|-------------|
| `basic_pay` | Employee's daily rate for days worked |
| `ot_amount` | Overtime pay calculation |
| `gross_pay` | `basic_pay + ot_amount` |
| `total_deductions` | SSS + PhilHealth + Pag-IBIG + Cash Advance + Other deductions |
| `take_home_pay` | `gross_pay - total_deductions` (Net pay) |

---

#### Tier 2: Attendance + Employees (Fallback Calculation)

If no payroll report data exists, the system calculates from raw attendance:

```php
$sql = "SELECT 
            COALESCE(b.branch_name, 'Unassigned') as branch_name,
            COUNT(DISTINCT a.employee_id) as employee_count,
            SUM(e.daily_rate * (CASE WHEN a.time_out IS NOT NULL THEN 1 ELSE 0 END)) as total_basic_pay,
            SUM((e.daily_rate / 8) * COALESCE(a.total_ot_hrs, 0)) as total_ot_pay,
            SUM(e.daily_rate * (CASE WHEN a.time_out IS NOT NULL THEN 1 ELSE 0 END)) + 
                SUM((e.daily_rate / 8) * COALESCE(a.total_ot_hrs, 0)) as total_gross_pay,
            0 as total_deductions,
            SUM(e.daily_rate * (CASE WHEN a.time_out IS NOT NULL THEN 1 ELSE 0 END)) + 
                SUM((e.daily_rate / 8) * COALESCE(a.total_ot_hrs, 0)) as total_net_pay
        FROM attendance a
        LEFT JOIN employees e ON a.employee_id = e.id
        LEFT JOIN branches b ON a.branch_name = b.branch_name
        WHERE a.attendance_date BETWEEN ? AND ?
          AND a.time_out IS NOT NULL
          AND (b.branch_name IS NULL OR UPPER(b.branch_name) != 'MAIN OFFICE')
        GROUP BY b.branch_name
        ORDER BY b.branch_name";
```

---

## Formula Breakdown

### 1. Basic Pay Calculation

```
Basic Pay = Employee Daily Rate × Days Worked

Where:
- Days Worked = COUNT of days with time_out IS NOT NULL
- Daily Rate = e.daily_rate from employees table
```

**SQL Logic**:
```sql
SUM(e.daily_rate * (CASE WHEN a.time_out IS NOT NULL THEN 1 ELSE 0 END))
```

> **Note**: An employee must clock out to count as a day worked.

---

### 2. Overtime Pay Calculation

```
Overtime Pay = (Daily Rate ÷ 8 hours) × Total Overtime Hours

Where:
- Hourly Rate = e.daily_rate / 8
- OT Hours = a.total_ot_hrs from attendance table
```

**SQL Logic**:
```sql
SUM((e.daily_rate / 8) * COALESCE(a.total_ot_hrs, 0))
```

> **Assumption**: Standard 8-hour workday for overtime computation.

---

### 3. Gross Pay Calculation

```
Gross Pay = Basic Pay + Overtime Pay
```

**SQL Logic**:
```sql
SUM(e.daily_rate * (CASE WHEN a.time_out IS NOT NULL THEN 1 ELSE 0 END)) + 
    SUM((e.daily_rate / 8) * COALESCE(a.total_ot_hrs, 0))
```

---

### 4. Deductions

**From Payroll Reports**:
```
Total Deductions = SSS + PhilHealth + Pag-IBIG + Cash Advance + Other
```

**From Attendance Fallback**:
```
Deductions = 0 (not calculated in fallback mode)
```

---

### 5. Net Pay (Take Home Pay)

```
Net Pay = Gross Pay - Total Deductions
```

**From Attendance Fallback**:
```
Net Pay = Gross Pay (since deductions = 0)
```

---

## Branch Filter

Site Salary **excludes** Main Office employees:

```sql
AND (b.branch_name IS NULL OR UPPER(b.branch_name) != 'MAIN OFFICE')
```

**Included**: All branches except "MAIN OFFICE"
**Excluded**: Main Office employees

---

## Summary Table

| Component | Formula | Source |
|-----------|---------|--------|
| **Basic Pay** | `SUM(daily_rate × days_worked)` | Payroll or Attendance |
| **Overtime Pay** | `SUM((daily_rate/8) × ot_hours)` | Payroll or Attendance |
| **Gross Pay** | `Basic Pay + Overtime Pay` | Computed |
| **Deductions** | `SSS + PhilHealth + Pag-IBIG + ...` | Payroll only (0 if fallback) |
| **Net Pay** | `Gross Pay - Deductions` | Computed |

---

## Report Output Columns

| Column | Description |
|--------|-------------|
| **Branch Name** | Construction site/branch location |
| **Employee Count** | Unique employees who worked at branch |
| **Basic Pay** | Total basic salary for all employees |
| **OT Pay** | Total overtime compensation |
| **Gross Pay** | Basic + OT (before deductions) |
| **Total Deductions** | All government and cash advance deductions |
| **Net Pay** | Amount to be paid to employees |

---

## Important Notes

1. **Attendance Required**: An employee must have both `time_in` and `time_out` to count as present.

2. **Date Range**: Default is current month (1st to last day). Can be customized via filter.

3. **Unassigned Branch**: Employees without a branch assignment show under "Unassigned".

4. **Fallback Behavior**: When payroll reports don't exist, the system shows gross pay as net pay (no deductions applied).

5. **Main Office Exclusion**: Office staff are reported separately under "Office Salary" filter.

---

## Related Files

- `employee/billing.php` - Main billing report (Site Salary: lines 52-100)
- `employee/cron/weekly_aggregate_non_branch33.php` - Payroll aggregation script
- `docs/EMPLOYER_SHARE_VS_SITE_SALARY_ISSUE.md` - Comparison with Employer Share
