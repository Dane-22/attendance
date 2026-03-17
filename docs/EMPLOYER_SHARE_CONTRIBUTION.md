# Employer Share Contribution System

## Overview

This document explains how the **Employer Share Contribution** system works for SSS, PhilHealth, and Pag-IBIG in the JAJR Construction payroll system.

## Location

- **Implementation File**: `employee/billing.php`
- **Filter Name**: `employer_share`
- **Database Table**: `daily_payroll_reports`

---

## Contribution Calculation Logic

The system calculates employer contributions based on the **employee deductions** stored in `daily_payroll_reports`. Each contribution type uses a different calculation method:

### 1. SSS (Social Security System)

| Component | Calculation |
|-----------|-------------|
| **Employee Share** | `sss_deduction` (from payroll) |
| **Employer Share** | `sss_deduction × 0.0733` (7.33%) |
| **Total Contribution** | `sss_deduction × 1.0733` |

**Formula**:
```sql
SELECT 'SSS' as contribution_type,
       SUM(dpr.sss_deduction) as total_employee_share,
       SUM(dpr.sss_deduction) * 0.0733 as estimated_employer_share,
       SUM(dpr.sss_deduction) * 1.0733 as total_contribution,
       COUNT(DISTINCT dpr.employee_id) as employee_count
FROM daily_payroll_reports dpr
WHERE dpr.report_date BETWEEN ? AND ? AND dpr.sss_deduction > 0
```

> **Note**: The 7.33% employer share rate is an estimate used for reporting purposes.

---

### 2. PhilHealth

| Component | Calculation |
|-----------|-------------|
| **Employee Share** | `philhealth_deduction` (from payroll) |
| **Employer Share** | `philhealth_deduction` (100% match) |
| **Total Contribution** | `philhealth_deduction × 2` |

**Formula**:
```sql
SELECT 'PhilHealth' as contribution_type,
       SUM(dpr.philhealth_deduction) as total_employee_share,
       SUM(dpr.philhealth_deduction) as estimated_employer_share,
       SUM(dpr.philhealth_deduction) * 2 as total_contribution,
       COUNT(DISTINCT dpr.employee_id) as employee_count
FROM daily_payroll_reports dpr
WHERE dpr.report_date BETWEEN ? AND ? AND dpr.philhealth_deduction > 0
```

> **Note**: PhilHealth uses a **50/50 split** between employer and employee, hence the employer matches the employee's contribution 100%.

---

### 3. Pag-IBIG (Home Development Mutual Fund)

| Component | Calculation |
|-----------|-------------|
| **Employee Share** | `pagibig_deduction` (from payroll) |
| **Employer Share** | `pagibig_deduction` (100% match) |
| **Total Contribution** | `pagibig_deduction × 2` |

**Formula**:
```sql
SELECT 'Pag-IBIG' as contribution_type,
       SUM(dpr.pagibig_deduction) as total_employee_share,
       SUM(dpr.pagibig_deduction) as estimated_employer_share,
       SUM(dpr.pagibig_deduction) * 2 as total_contribution,
       COUNT(DISTINCT dpr.employee_id) as employee_count
FROM daily_payroll_reports dpr
WHERE dpr.report_date BETWEEN ? AND ? AND dpr.pagibig_deduction > 0
```

> **Note**: Pag-IBIG also uses a **50/50 split** between employer and employee contributions.

---

## Report Output

When viewing the Employer Share report, the following columns are displayed:

| Column | Description |
|--------|-------------|
| **Contribution Type** | SSS, PhilHealth, or Pag-IBIG |
| **Employee Count** | Number of employees with deductions for this type |
| **Employee Share** | Total employee contribution amount |
| **Employer Share** | Total employer contribution amount |
| **Total Contribution** | Combined employee + employer contribution |

---

## Date Range Filtering

The report supports date range filtering:

- **Start Date**: First day of current month (default)
- **End Date**: Last day of current month (default)

Users can customize the date range via the filter form on the billing page.

---

## Print Preview Integration

The Employer Share Contribution data is included in the **Payment Request Form** print preview:

```php
if ($filter === 'employer_share' && !empty($data)): 
    foreach ($data as $row): 
        $employerShareTotal += ($row['total_contribution'] ?? 0);
?>
<tr>
    <td><?php echo $row['contribution_type']; ?> EMPLOYER CONTRIBUTION 1st week</td>
    <td class="amount-right"><?php echo formatCurrency($row['total_contribution']); ?></td>
</tr>
<?php endforeach; endif; ?>
```

---

## Data Requirements

For the Employer Share report to display data:

1. **Payroll Reports Must Exist**: Records must exist in `daily_payroll_reports` table
2. **Deductions Must Be > 0**: Only employees with positive deductions are counted
3. **Date Range Match**: Records must fall within the selected date range

---

## Summary Table

| Contribution | Employee % | Employer % | Total % |
|--------------|------------|------------|---------|
| **SSS** | ~93.17% | ~6.83% | 100% |
| **PhilHealth** | 50% | 50% | 100% |
| **Pag-IBIG** | 50% | 50% | 100% |

---

## Related Files

- `employee/billing.php` - Main billing report page
- `employee/cron/weekly_aggregate_non_branch33.php` - Payroll aggregation logic
- `docs/PAYROLL_HOW_IT_WORKS.md` - General payroll documentation

---

## Important Notes

1. **SSS Calculation**: The system uses a simplified 7.33% multiplier for employer share estimation. Actual SSS contributions follow a more complex bracket-based system.

2. **Data Source**: All calculations are derived from `daily_payroll_reports` table. If payroll data is missing, no contribution data will appear.

3. **Aggregation**: Running the "Generate Report" button triggers the weekly aggregation script to ensure daily payroll data is up-to-date.
