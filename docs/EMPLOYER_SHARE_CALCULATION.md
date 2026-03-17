# Employer Share Contribution Calculation

## Overview

This document provides detailed formulas and calculation logic for **Employer Share Contribution** in the JAJR Construction payroll system.

---

## Data Source

All calculations are based on the **`daily_payroll_reports`** table:

| Column | Description |
|--------|-------------|
| `sss_deduction` | Employee's SSS contribution |
| `philhealth_deduction` | Employee's PhilHealth contribution |
| `pagibig_deduction` | Employee's Pag-IBIG contribution |
| `report_date` | Date of the payroll record |
| `employee_id` | Employee identifier |

---

## 1. SSS (Social Security System)

### Formula

```
Employee Share = SUM(sss_deduction)
Employer Share = SUM(sss_deduction) × 0.0733
Total Contribution = SUM(sss_deduction) × 1.0733
```

### SQL Query

```sql
SELECT 
    'SSS' as contribution_type,
    SUM(dpr.sss_deduction) as total_employee_share,
    SUM(dpr.sss_deduction) * 0.0733 as estimated_employer_share,
    SUM(dpr.sss_deduction) * 1.0733 as total_contribution,
    COUNT(DISTINCT dpr.employee_id) as employee_count
FROM daily_payroll_reports dpr
WHERE dpr.report_date BETWEEN ? AND ? 
  AND dpr.sss_deduction > 0
```

### Calculation Breakdown

| Component | Formula | Example (Employee Share = ₱1,000) |
|-----------|---------|-----------------------------------|
| Employee Share | `SUM(sss_deduction)` | ₱1,000.00 |
| Employer Share | `Employee Share × 0.0733` | ₱1,000 × 0.0733 = **₱73.30** |
| Total Contribution | `Employee Share × 1.0733` | ₱1,000 × 1.0733 = **₱1,073.30** |

### Important Notes

- **7.33% multiplier** is an estimation used in the system
- Actual SSS uses a **bracket-based contribution table**
- Employer share is typically calculated based on employee's monthly salary credit
- The formula assumes employee share represents ~93.17% of total contribution

---

## 2. PhilHealth

### Formula

```
Employee Share = SUM(philhealth_deduction)
Employer Share = SUM(philhealth_deduction)
Total Contribution = SUM(philhealth_deduction) × 2
```

### SQL Query

```sql
SELECT 
    'PhilHealth' as contribution_type,
    SUM(dpr.philhealth_deduction) as total_employee_share,
    SUM(dpr.philhealth_deduction) as estimated_employer_share,
    SUM(dpr.philhealth_deduction) * 2 as total_contribution,
    COUNT(DISTINCT dpr.employee_id) as employee_count
FROM daily_payroll_reports dpr
WHERE dpr.report_date BETWEEN ? AND ? 
  AND dpr.philhealth_deduction > 0
```

### Calculation Breakdown

| Component | Formula | Example (Employee Share = ₱200) |
|-----------|---------|-----------------------------------|
| Employee Share | `SUM(philhealth_deduction)` | ₱200.00 |
| Employer Share | `Employee Share` (100% match) | ₱200.00 |
| Total Contribution | `Employee Share × 2` | ₱200 × 2 = **₱400.00** |

### Important Notes

- **50/50 split** between employer and employee
- PhilHealth contribution is **4% of monthly basic salary** (as of recent updates)
- Employee pays 2%, Employer pays 2%
- System assumes `philhealth_deduction` already represents the employee's 2% share

### Standard PhilHealth Calculation

```
Monthly Basic Salary × 4% = Total Contribution
Employee Share = Total Contribution ÷ 2
Employer Share = Total Contribution ÷ 2
```

| Monthly Salary | Total Contribution (4%) | Employee (2%) | Employer (2%) |
|----------------|------------------------|---------------|---------------|
| ₱10,000 | ₱400 | ₱200 | ₱200 |
| ₱20,000 | ₱800 | ₱400 | ₱400 |
| ₱30,000 | ₱1,200 | ₱600 | ₱600 |

---

## 3. Pag-IBIG (Home Development Mutual Fund)

### Formula

```
Employee Share = SUM(pagibig_deduction)
Employer Share = SUM(pagibig_deduction)
Total Contribution = SUM(pagibig_deduction) × 2
```

### SQL Query

```sql
SELECT 
    'Pag-IBIG' as contribution_type,
    SUM(dpr.pagibig_deduction) as total_employee_share,
    SUM(dpr.pagibig_deduction) as estimated_employer_share,
    SUM(dpr.pagibig_deduction) * 2 as total_contribution,
    COUNT(DISTINCT dpr.employee_id) as employee_count
FROM daily_payroll_reports dpr
WHERE dpr.report_date BETWEEN ? AND ? 
  AND dpr.pagibig_deduction > 0
```

### Calculation Breakdown

| Component | Formula | Example (Employee Share = ₱100) |
|-----------|---------|-----------------------------------|
| Employee Share | `SUM(pagibig_deduction)` | ₱100.00 |
| Employer Share | `Employee Share` (100% match) | ₱100.00 |
| Total Contribution | `Employee Share × 2` | ₱100 × 2 = **₱200.00** |

### Important Notes

- **50/50 split** between employer and employee
- Standard contribution is **2% of monthly basic salary**
- **Maximum contribution is ₱100** per employee per month (employee + employer = ₱200 total)
- For salaries above ₱5,000, the contribution is capped

### Standard Pag-IBIG Calculation

```
Monthly Basic Salary × 2% = Employee Share (capped at ₱100)
Employer Share = Employee Share (100% match)
Total = Employee Share × 2
```

| Monthly Salary | Calculation | Employee Share | Employer Share | Total |
|----------------|-------------|----------------|------------------|-------|
| ₱4,000 | ₱4,000 × 2% | ₱80 | ₱80 | ₱160 |
| ₱5,000 | ₱5,000 × 2% | ₱100 | ₱100 | ₱200 |
| ₱10,000 | Capped | ₱100 | ₱100 | ₱200 |
| ₱50,000 | Capped | ₱100 | ₱100 | ₱200 |

---

## Complete UNION Query

The billing page combines all three contributions using `UNION ALL`:

```sql
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
    SUM(dagibig_deduction) * 2 as total_contribution,
    COUNT(DISTINCT dpr.employee_id) as employee_count
FROM daily_payroll_reports dpr
WHERE dpr.report_date BETWEEN ? AND ? AND dpr.pagibig_deduction > 0
```

---

## PHP Implementation

From `employee/billing.php` (lines 186-222):

```php
case 'employer_share':
    $filterTitle = 'Employer Share Contribution (SSS, PhilHealth, Pag-IBIG)';
    $sql = "SELECT 
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
            WHERE dpr.report_date BETWEEN ? AND ? AND dpr.pagibig_deduction > 0";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ssssss", $startDate, $endDate, $startDate, $endDate, $startDate, $endDate);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    break;
```

---

## Report Output

| Column | Description | Formula |
|--------|-------------|---------|
| **Contribution Type** | SSS / PhilHealth / Pag-IBIG | Label |
| **Employee Count** | Unique employees with deductions | `COUNT(DISTINCT employee_id)` |
| **Employee Share** | Total employee contributions | `SUM(deduction)` |
| **Employer Share** | Total employer contributions | See formulas above |
| **Total Contribution** | Combined total | See formulas above |

---

## Important Considerations

### 1. Date Range Parameters

The query requires **6 date parameters** (2 per contribution type):

```php
$stmt->bind_param("ssssss", 
    $startDate, $endDate,  // For SSS
    $startDate, $endDate,  // For PhilHealth
    $startDate, $endDate   // For Pag-IBIG
);
```

### 2. Zero Value Filtering

Records with `deduction = 0` are excluded:

```sql
AND dpr.sss_deduction > 0
AND dpr.philhealth_deduction > 0
AND dpr.pagibig_deduction > 0
```

### 3. Multiplier Summary

| Contribution | Employee Share | Employer Share Multiplier | Total Multiplier |
|--------------|------------------|---------------------------|------------------|
| **SSS** | 100% | × 0.0733 | × 1.0733 |
| **PhilHealth** | 100% | × 1.0 | × 2.0 |
| **Pag-IBIG** | 100% | × 1.0 | × 2.0 |

---

## Related Documentation

- `docs/EMPLOYER_SHARE_CONTRIBUTION.md` - System overview
- `docs/EMPLOYER_SHARE_ZERO_VALUES_TROUBLESHOOTING.md` - Zero value issues
- `docs/EMPLOYER_SHARE_VS_SITE_SALARY_ISSUE.md` - Comparison with Site Salary
- `employee/billing.php` - Implementation source code
