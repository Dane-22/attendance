# Weekly Report Deductions Documentation

## Overview

This document details the deduction system implemented in `employee/weekly_report.php` and its supporting logic in `employee/function/report.php`.

---

## Deduction Columns (Table Headers)

The payroll table displays **6 deduction columns** (defined at lines 231-253):

| Column | Code | Description | CSS Class |
|--------|------|-------------|-----------|
| **CA** | Cash Advance | Manual input for cash advance deduction | `text-red-300 bg-red-900/20` |
| **SSS** | Social Security System | Government mandated contribution | `text-red-300 bg-red-900/20` |
| **PHIC** | PhilHealth | Health insurance contribution | `text-red-300 bg-red-900/20` |
| **HDMF** | Pag-IBIG | Home Development Mutual Fund | `text-red-300 bg-red-900/20` |
| **SSS Loan** | SSS Loan Repayment | Loan deduction (placeholder) | `text-red-300 bg-red-900/20` |
| **Total** | Total Deductions | Sum of all deductions | `text-red-300 bg-red-900/20` |

---

## Government Deduction Constants

Defined in `employee/function/report.php` (lines 158-161):

```php
$MONTHLY_PHILHEALTH = 250.00;  // ₱250/month
$MONTHLY_SSS        = 450.00;  // ₱450/month  
$MONTHLY_PAGIBIG    = 200.00;  // ₱200/month
```

**Total Monthly Government Deductions: ₱900.00**

---

## Weekly View Deduction Logic

For **weekly view**, deductions are calculated as follows (lines 169-180):

| Week | SSS | PhilHealth | Pag-IBIG | Total |
|------|-----|------------|----------|-------|
| Week 1 | ₱250.00 | ₱100 | ₱50| ₱400.00 |
| Week 2 | ₱100.00 | ₱100 | ₱50 | ₱250.00 |
| Week 3 | ₱100.00 | ₱50 | ₱100 | ₱250.00 |
| Week 4 | ₱0.00 | ₱0.00 | ₱0.00 | ₱0.00 |
| Week 5 | ₱0.00 | ₱0.00 | ₱0.00 | ₱0.00 |

**Formula:** Monthly ÷ 3 for weeks 1-3, zero for weeks 4-5

---

## Monthly View Deduction Logic

For **monthly view**, full monthly deductions are applied (lines 164-168):

| Deduction | Amount |
|-----------|--------|
| SSS | ₱450.00 |
| PhilHealth | ₱250.00 |
| Pag-IBIG | ₱200.00 |
| **Total** | **₱900.00** |

---

## Deduction Application Rules

### When Deductions Are Applied (report.php:406-412)

```php
// Apply deductions only if employee has attendance records
if ($days_worked > 0) {
    $payroll['sss_deduction'] = $sss_deduction;
    $payroll['philhealth_deduction'] = $philhealth_deduction;
    $payroll['pagibig_deduction'] = $pagibig_deduction;
    $payroll['total_deductions'] = $total_deductions_amount;
}
```

**Rule:** Employees with **zero days worked** receive **NO deductions**.

---

## Cash Advance (CA) Deduction

### Current Implementation (weekly_report.php:264, 310-319)

```php
$ca_deduction = 0; // Placeholder
```

- CA is a **manual input field** in the UI
- Default value is **0**
- Not persisted to database in the current implementation
- Included in `total_deductions` calculation: `$payroll['sss_deduction'] + $payroll['philhealth_deduction'] + $payroll['pagibig_deduction'] + $ca_deduction + $sss_loan`

### UI Input (lines 310-319)

```html
<input type="number" 
       name="ca_<?php echo $emp_id; ?>" 
       id="ca_<?php echo $emp_id; ?>"
       value="0" 
       min="0"
       step="0.01"
       class="w-20 bg-gray-800 border border-gray-600 rounded px-2 py-1 text-right text-red-400"
       onchange="updateCalculations(<?php echo $emp_id; ?>)">
```

---

## SSS Loan Deduction

### Current Implementation (weekly_report.php:265)

```php
$sss_loan = 0; // Placeholder for SSS loan
```

- Currently a **placeholder only**
- No UI input field exists
- Reserved for future implementation

---

## Take Home Pay Calculation

### Formula (weekly_report.php:266-267)

```php
$gross_plus_allowance = $payroll['gross_pay'] + $allowance;
$total_deductions = $payroll['sss_deduction'] + $payroll['philhealth_deduction'] + 
                    $payroll['pagibig_deduction'] + $ca_deduction + $sss_loan;
$take_home = $gross_plus_allowance - $total_deductions;
```

**Note:** Overtime amount is added to Gross Pay in the display but the calculation logic needs verification.

---

## Issues and Observations

### 1. **Inconsistent Deduction Total in Grand Total Row**

The grand total row (line 396) uses `$sum_total_deductions` which is accumulated from individual employee `total_deductions`, but this may not include CA or SSS Loan values since those are input fields.

### 2. **CA and SSS Loan Not Persisted**

Cash Advance and SSS Loan values are:
- Entered in the UI
- Used in client-side calculations
- **NOT saved to the database**
- Reset to 0 on page refresh

### 3. **Week 4-5 Zero Deductions**

Weeks 4 and 5 have **zero government deductions**, which means:
- If an employee works only in week 4, they pay **no SSS/PhilHealth/Pag-IBIG**
- This may be intentional to cap monthly deductions at ₱900

### 4. **Deduction Proration Logic**

The ÷3 split for weekly deductions assumes exactly 4 weeks per month:
- Weeks 1-3: ₱300 each = ₱900 total
- Week 4: ₱0
- This means the full monthly amount is collected within the first 3 weeks

### 5. **Negative Net Pay Protection**

```php
$payroll['net_pay'] = max(0, $net_pay); // Ensure no negative net pay
```

Net pay is capped at minimum ₱0 (employees cannot have negative take-home).

---

## Related Database Tables

### 1. `daily_payroll_reports`

Stores daily deduction data per employee:
- `sss_deduction`
- `philhealth_deduction`
- `pagibig_deduction`
- `ca_deduction`
- `sss_loan`
- `total_deductions`
- `take_home_pay`

### 2. `weekly_payroll_reports`

Stores aggregated weekly data:
- Contains deduction columns
- Used for loading historical performance allowance and payment status
- Does NOT store CA or SSS Loan (placeholders in UI only)

---

## File References

| File | Purpose |
|------|---------|
| `employee/weekly_report.php` | Main report UI with deduction display |
| `employee/function/report.php` | Deduction calculation logic |
| `employee/update_allowance.php` | Saves performance allowance (AJAX) |
| `employee/update_payment_status.php` | Saves payment status (AJAX) |

---

## Recommendations

1. **Persist CA Deductions:** Create API endpoint to save Cash Advance values to `weekly_payroll_reports` table
2. **SSS Loan Implementation:** Add input field and persistence logic when loan feature is needed
3. **Week 5 Deductions:** Consider if deductions should apply to week 5 for months with 31 days
4. **Validation:** Add server-side validation to ensure deductions don't exceed gross pay (unless negative net pay is intended to be allowed)
