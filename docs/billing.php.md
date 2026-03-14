# billing.php Documentation

## Overview

**File:** `c:\wamp64\www\main\employee\billing.php`  
**Purpose:** Billing and Payroll Reports module for JAJR Construction Attendance System  
**Type:** PHP/HTML Page with MySQL database integration  

This page provides a comprehensive interface for generating various payroll and billing reports including site salaries, office salaries, cash advances, and employer contribution shares.

---

## Table of Contents

1. [Authentication & Security](#authentication--security)
2. [GET Parameters](#get-parameters)
3. [Weekly Aggregation Feature](#weekly-aggregation-feature)
4. [Report Types & SQL Queries](#report-types--sql-queries)
5. [Helper Functions](#helper-functions)
6. [HTML Structure](#html-structure)
7. [Print Preview Modal](#print-preview-modal)
8. [JavaScript Functions](#javascript-functions)
9. [Database Dependencies](#database-dependencies)
10. [CSS Dependencies](#css-dependencies)

---

## Authentication & Security

```php
<?php
session_start();
require_once '../conn/db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['employee_id'])) {
    header('Location: ../login.php');
    exit;
}
```

- **Session-based authentication** required
- Redirects to `login.php` if not authenticated
- Requires `employee_id` in session
- Database connection via `db_connection.php`

---

## GET Parameters

| Parameter | Default Value | Description |
|-----------|---------------|-------------|
| `filter` | `site_salary` | Report type filter |
| `start_date` | First day of current month | Report start date (YYYY-MM-DD) |
| `end_date` | Last day of current month | Report end date (YYYY-MM-DD) |
| `generate_report` | `0` | Triggers weekly aggregation when set to `1` |
| `aggregated` | N/A | Flag indicating aggregation was completed |

### Available Filters

| Filter Value | Title | Description |
|--------------|-------|-------------|
| `site_salary` | Site Salary (Total Salary per Branch) | Aggregates payroll data by branch, excluding Main Branch |
| `office_salary` | Office Salary (Main Branch Total) | Shows payroll data specifically for Main Branch employees |
| `cash_advance` | Cash Advance (Total per Employee) | Lists all cash advance requests per employee |
| `employer_share` | Employer Share Contribution | Shows SSS, PhilHealth, and Pag-IBIG contributions |

---

## Weekly Aggregation Feature

When `generate_report=1` is triggered, the system runs an HTTP request to a cron job that performs weekly payroll aggregation.

```php
if (isset($_GET['generate_report']) && $_GET['generate_report'] === '1') {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $cronUrl = "$protocol://$host/employee/cron/weekly_aggregate_non_branch33.php";
    
    // cURL request with 60-second timeout
    $ch = curl_init($cronUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Store result in session for display
    $_SESSION['aggregation_result'] = [
        'success' => $httpCode === 200,
        'output' => $response ?: "HTTP Error: $httpCode"
    ];
}
```

**Cron Script:** `employee/cron/weekly_aggregate_non_branch33.php`

---

## Report Types & SQL Queries

### 1. Site Salary (`site_salary`)

**Data Sources:** `daily_payroll_reports` (primary) → `attendance` (fallback)

**Primary Query (daily_payroll_reports):**
```sql
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
  AND COALESCE(b.branch_name, '') != 'Main Branch'
GROUP BY b.branch_name
ORDER BY b.branch_name
```

**Fallback Query (attendance table):**
```sql
SELECT 
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
  AND COALESCE(b.branch_name, '') != 'Main Branch'
GROUP BY b.branch_name
```

**Columns Displayed:**
- Branch Name
- Employee Count
- Basic Pay
- OT Pay
- Gross Pay
- Total Deductions
- Net Pay

---

### 2. Office Salary (`office_salary`)

Similar to Site Salary but filters **only** for `Main Branch`.

**Condition:** `COALESCE(b.branch_name, '') = 'Main Branch'`

---

### 3. Cash Advance (`cash_advance`)

**Tables Used:** `employees`, `attendance`, `cash_advances`

**Query:**
```sql
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
GROUP BY e.id, e.employee_code, e.first_name, e.middle_name, e.last_name, a.branch_name, ca2.status
HAVING total_cash_advance > 0
ORDER BY total_cash_advance DESC
```

**Columns Displayed:**
- Employee Code
- Employee Name
- Branch
- Total Cash Advance
- Request Count
- Latest Status (with status badge styling)

---

### 4. Employer Share Contribution (`employer_share`)

**Tables Used:** `daily_payroll_reports`

**Query (UNION of 3 contributions):**
```sql
-- SSS Contribution
SELECT 
    'SSS' as contribution_type,
    SUM(dpr.sss_deduction) as total_employee_share,
    SUM(dpr.sss_deduction) * 0.0733 as estimated_employer_share,
    SUM(dpr.sss_deduction) * 1.0733 as total_contribution,
    COUNT(DISTINCT dpr.employee_id) as employee_count
FROM daily_payroll_reports dpr
WHERE dpr.report_date BETWEEN ? AND ? AND dpr.sss_deduction > 0

UNION ALL

-- PhilHealth Contribution (50/50 split)
SELECT 
    'PhilHealth' as contribution_type,
    SUM(dpr.philhealth_deduction) as total_employee_share,
    SUM(dpr.philhealth_deduction) as estimated_employer_share,
    SUM(dpr.philhealth_deduction) * 2 as total_contribution,
    COUNT(DISTINCT dpr.employee_id) as employee_count
FROM daily_payroll_reports dpr
WHERE dpr.report_date BETWEEN ? AND ? AND dpr.philhealth_deduction > 0

UNION ALL

-- Pag-IBIG Contribution (50/50 split)
SELECT 
    'Pag-IBIG' as contribution_type,
    SUM(dpr.pagibig_deduction) as total_employee_share,
    SUM(dpr.pagibig_deduction) as estimated_employer_share,
    SUM(dpr.pagibig_deduction) * 2 as total_contribution,
    COUNT(DISTINCT dpr.employee_id) as employee_count
FROM daily_payroll_reports dpr
WHERE dpr.report_date BETWEEN ? AND ? AND dpr.pagibig_deduction > 0
```

**Calculation Rules:**
| Contribution | Employee Share | Employer Share | Total Multiplier |
|--------------|----------------|----------------|------------------|
| SSS | 100% of sss_deduction | 7.33% of sss_deduction | 1.0733x |
| PhilHealth | 100% of philhealth_deduction | 100% of philhealth_deduction | 2x |
| Pag-IBIG | 100% of pagibig_deduction | 100% of pagibig_deduction | 2x |

**Columns Displayed:**
- Contribution Type
- Employee Count
- Employee Share
- Employer Share
- Total Contribution

---

## Helper Functions

### formatCurrency()

```php
function formatCurrency($amount) {
    return '₱' . number_format($amount ?? 0, 2);
}
```

**Purpose:** Formats numeric amounts as Philippine Peso currency  
**Parameters:** `$amount` - Numeric value (nullable)  
**Returns:** String with ₱ prefix and 2 decimal places  
**Default:** Returns `₱0.00` for null values

---

## HTML Structure

### Page Layout

```
.app-shell
├── sidebar.php (included)
└── .main-content
    └── .billing-container
        ├── .billing-header (h1 title)
        ├── .filter-section (form with filters)
        ├── .alert (aggregation result message)
        └── .report-section
            ├── h2 (filter title)
            ├── p.date-range
            ├── .no-data (if empty)
            └── table.billing-table
```

### Filter Form

```html
<form method="GET" class="filter-form">
    <div class="filter-group">
        <label for="filter">Report Type:</label>
        <select name="filter" id="filter" onchange="this.form.submit()">
            <option value="site_salary">Site Salary (Per Branch)</option>
            <option value="office_salary">Office Salary (Main Branch)</option>
            <option value="cash_advance">Cash Advance (Per Employee)</option>
            <option value="employer_share">Employer Share Contribution</option>
        </select>
    </div>
    
    <div class="filter-group">
        <label for="start_date">Start Date:</label>
        <input type="date" name="start_date" id="start_date">
    </div>
    
    <div class="filter-group">
        <label for="end_date">End Date:</label>
        <input type="date" name="end_date" id="end_date">
    </div>
    
    <button type="submit" name="generate_report" value="1" class="filter-btn">
        Generate Report
    </button>
    <button type="button" class="filter-btn print-btn" onclick="openPrintPreview()">
        <i class="fas fa-print"></i> Print Preview
    </button>
</form>
```

### Data Table Structure

Tables vary based on filter type:

**Site/Office Salary Table:**
| Branch Name | Employee Count | Basic Pay | OT Pay | Gross Pay | Deductions | Net Pay |

**Cash Advance Table:**
| Employee Code | Employee Name | Branch | Total Cash Advance | Request Count | Latest Status |

**Employer Share Table:**
| Contribution Type | Employee Count | Employee Share | Employer Share | Total Contribution |

**Table Footer (Grand Totals):**
- Site/Office Salary: Grand Total Net Pay
- Cash Advance: Grand Total Cash Advance
- Employer Share: No grand total displayed

---

## Print Preview Modal

The page includes a **Payment Request Form** modal that can be printed.

### Modal Structure

```
#printModal.print-modal
└── .print-modal-content
    ├── .print-modal-header (title + close button)
    ├── .print-modal-body
    │   └── #paymentForm.payment-form
    │       ├── .form-header (company info + form info table)
    │       ├── h2.form-title
    │       ├── .payee-section
    │       ├── table.payment-table
    │       └── .signature-section
    └── .print-modal-footer (Print + Close buttons)
```

### Payment Form Sections

1. **Header Section**
   - Company: JAJR Construction
   - Address: #55 P. Zamora St. Barangay II, San Fernando City, La Union
   - Contact: (072) 607-1150
   - Email: jajrconstruction@yahoo.com
   - Form Reference: PRF/Year-Month-Seq. No. (2026-02-0001)
   - Date: Current date

2. **Payee Section**
   - Default Payee: ELAINE MARICRIS T. AGUILAR
   - TIN field (blank)
   - Address field (blank)
   - Payment method checkboxes (Check, Bank Transfer, Others)

3. **Payment Table Sections**
   - **SALARY (SITE)** - Shows site_salary data
   - **OFFICE SALARY** - Shows office_salary data
   - **CASH ADVANCE** - Shows cash_advance data
   - **EMPLOYER SHARE CONTRIBUTION** - Shows employer_share data
   - **Total Row** - Grand total of all visible sections

4. **Signature Section**
   - Prepared by: Accounting Staff
   - Reviewed by: Accountant
   - Approved by: President

---

## JavaScript Functions

### 1. Form Auto-Submit

```javascript
document.getElementById('filter').addEventListener('change', function() {
    this.form.submit();
});
```

Auto-submits form when filter dropdown changes.

### 2. Print Preview Controls

```javascript
function openPrintPreview() {
    document.getElementById('printModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closePrintPreview() {
    document.getElementById('printModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}
```

### 3. Print Function

```javascript
function printPaymentForm() {
    var printContent = document.getElementById('paymentForm').innerHTML;
    var originalContent = document.body.innerHTML;
    
    // Replace body with just the payment form
    document.body.innerHTML = '<div class="payment-form">' + printContent + '</div>';
    window.print();
    
    // Restore original content
    document.body.innerHTML = originalContent;
    
    // Re-attach event listeners
    document.getElementById('filter').addEventListener('change', function() {
        this.form.submit();
    });
}
```

### 4. Modal Click-Outside Close

```javascript
window.onclick = function(event) {
    var modal = document.getElementById('printModal');
    if (event.target == modal) {
        closePrintPreview();
    }
}
```

---

## Database Dependencies

### Required Tables

| Table | Purpose |
|-------|---------|
| `employees` | Employee information (id, employee_code, names, daily_rate) |
| `attendance` | Clock-in/out records with branch and OT hours |
| `branches` | Branch information (id, branch_name) |
| `daily_payroll_reports` | Pre-calculated daily payroll data |
| `cash_advances` | Cash advance requests (amount, status, request_date) |

### Required Columns in `daily_payroll_reports`

- `employee_id`
- `report_date`
- `basic_pay`
- `ot_amount`
- `gross_pay`
- `total_deductions`
- `take_home_pay`
- `sss_deduction`
- `philhealth_deduction`
- `pagibig_deduction`
- `branch_id`

---

## CSS Dependencies

### External Stylesheets

```html
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/billing.css">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/theme-variables.css">
<link rel="stylesheet" href="css/light-theme.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
```

### Theme Script

```html
<script src="js/theme.js"></script>
```

### Included Components

- `sidebar.php` - Navigation sidebar

---

## Session Variables

| Variable | Purpose |
|----------|---------|
| `$_SESSION['employee_id']` | Authenticated user ID |
| `$_SESSION['aggregation_result']` | Temporary storage for aggregation status message |

---

## File Dependencies

### Required Files

| File | Path | Purpose |
|------|------|---------|
| Database Connection | `../conn/db_connection.php` | MySQLi database connection |
| Sidebar Component | `sidebar.php` | Navigation sidebar |
| Billing Styles | `css/billing.css` | Page-specific styling |
| Global Styles | `../assets/css/style.css` | Application-wide styles |
| Theme Variables | `../assets/css/theme-variables.css` | CSS custom properties |
| Light Theme | `css/light-theme.css` | Light theme overrides |
| Theme Script | `js/theme.js` | Theme switching functionality |

### External Cron Script

| File | Path | Purpose |
|------|------|---------|
| Weekly Aggregator | `employee/cron/weekly_aggregate_non_branch33.php` | Payroll data aggregation |

---

## Status Badges

For cash advance status display:

```html
<span class="status-badge <?php echo strtolower($row['latest_status'] ?? 'pending'); ?>">
    <?php echo $row['latest_status'] ?? 'No Status'; ?>
</span>
```

Status classes applied: `pending`, `approved`, `rejected` (CSS-defined in billing.css)

---

## Usage Examples

### View Site Salary for January 2024

```
/employee/billing.php?filter=site_salary&start_date=2024-01-01&end_date=2024-01-31
```

### Generate Report with Aggregation

```
/employee/billing.php?filter=office_salary&start_date=2024-01-01&end_date=2024-01-31&generate_report=1
```

### View Cash Advances

```
/employee/billing.php?filter=cash_advance&start_date=2024-01-01&end_date=2024-01-31
```

---

## Notes

1. **Data Fallback:** Site and Office salary queries first check `daily_payroll_reports`, then fall back to calculating from `attendance` table if no data exists.

2. **Branch Filtering:** Main Branch is treated as office, all other branches are considered site branches.

3. **OT Calculation:** Overtime pay is calculated as `(daily_rate / 8) * total_ot_hrs` (assumes 8-hour workday).

4. **Print Preview:** The payment form shows hardcoded payee "ELAINE MARICRIS T. AGUILAR" and PRF reference numbers.

5. **Session Messages:** Aggregation results are stored in session and displayed once, then cleared.
