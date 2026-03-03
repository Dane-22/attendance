# Billing System Documentation

## Overview

The Billing System (`billing.php`) is a payroll and billing reporting module for JAJR Construction's employee management system. It provides comprehensive financial reports including site salaries, office salaries, cash advances, and employer contribution shares.

**File Location:** `c:\wamp64\www\main\employee\billing.php`

---

## Core Features

### 1. Report Types

The system supports four main report filters:

| Filter | Description | Data Source |
|--------|-------------|-------------|
| **Site Salary** | Total salary per construction branch (excludes Main Branch) | `weekly_payroll_reports` table, grouped by branch |
| **Office Salary** | Main Branch total salary | `weekly_payroll_reports` table, filtered for 'Main Branch' |
| **Cash Advance** | Total cash advance per employee | `cash_advances` table, aggregated by employee |
| **Employer Share** | Government contribution summary (SSS, PhilHealth, Pag-IBIG) | `weekly_payroll_reports` with calculation formulas |

### 2. Key Functionality

- **Date Range Filtering:** Users can select custom start and end dates (defaults to current month)
- **Automatic Aggregation:** Triggers weekly payroll aggregation when generating reports
- **Print Preview:** Generates a formal "Payment Request Form" with JAJR company letterhead
- **Responsive UI:** Mobile-optimized card view for smaller screens

---

## Workflow

```
1. User selects report type and date range
2. Clicks "Generate Report" button
3. System triggers weekly payroll aggregation (calls cron script)
4. Data is fetched from database based on filter
5. Table displays results with grand totals
6. Optional: Open Print Preview for Payment Request Form
```

---

## Database Tables Used

| Table | Purpose |
|-------|---------|
| `weekly_payroll_reports` | Stores aggregated weekly payroll data per employee |
| `employees` | Employee information and branch assignments |
| `branches` | Branch/location data (e.g., 'Main Branch', site locations) |
| `cash_advances` | Cash advance request records |
| `attendance` | Used for branch lookup when cash advance data is fetched |
| `daily_payroll_reports` | Source data for weekly aggregation cron job |

### Key Fields in weekly_payroll_reports

- `employee_id` - Links to employees table
- `report_year`, `report_month`, `week_number` - Date grouping
- `basic_pay`, `ot_amount`, `gross_pay` - Earnings
- `sss_deduction`, `philhealth_deduction`, `pagibig_deduction` - Deductions
- `total_deductions`, `take_home_pay` - Net calculations
- `branch_id` - Links to branches table

---

## Employer Share Calculation Logic

| Contribution | Employee Share | Employer Share Formula | Total Formula |
|--------------|----------------|----------------------|---------------|
| **SSS** | From `sss_deduction` | × 0.733 (73.3%) | × 1.733 |
| **PhilHealth** | From `philhealth_deduction` | × 1.0 (100%, equal match) | × 2.0 |
| **Pag-IBIG** | From `pagibig_deduction` | × 1.0 (100%, equal match) | × 2.0 |

---

## File Dependencies

### PHP Includes

```php
require_once '../conn/db_connection.php';  // Database connection
include 'sidebar.php';                      // Navigation sidebar
```

### CSS Files

| File | Purpose |
|------|---------|
| `css/billing.css` | Main billing styles, print preview styles, mobile grid |
| `../assets/css/style.css` | Global styles |
| `../assets/css/theme-variables.css` | Theme variables |
| `css/light-theme.css` | Light theme overrides |
| Font Awesome (CDN) | Icons |
| Inter (Google Fonts) | Typography |

### JavaScript

- Inline JavaScript handles:
  - Form auto-submit on filter change
  - Print preview modal open/close
  - Print functionality
  - Modal backdrop click handler

---

## Related Files

### Core Application Files

| File | Relationship |
|------|--------------|
| `sidebar.php` | Navigation sidebar with Finance menu (includes Billing link) |
| `cron/weekly_aggregate_non_branch33.php` | Aggregates daily payroll → weekly (triggered by Generate Report) |
| `conn/db_connection.php` | MySQL database connection handler |

### Finance Module Files

| File | Relationship |
|------|--------------|
| `weekly_report.php` | Detailed payroll report |
| `overtime.php` | Overtime management |
| `cash_advance.php` | Cash advance approval/management |

### Styling

| File | Purpose |
|------|---------|
| `css/billing.css` | Complete styling including dark theme, mobile grid, print preview |

---

## Code Structure

### PHP Logic Flow (Lines 1-174)

```php
1-9    Session validation and login check
11-16  Parameter handling (filter, date range)
19-42  Weekly aggregation trigger via cURL
44-168 Data fetching with switch statement based on filter
170-173 Currency formatting helper function
```

### HTML Structure (Lines 175-572)

```
head        Stylesheets and scripts
body
├── app-shell
│   ├── sidebar.php (included)
│   └── main-content
│       ├── billing-header
│       ├── filter-section (form)
│       ├── alert (aggregation result message)
│       └── report-section (dynamic table)
└── printModal (Payment Request Form preview)
```

---

## Print Preview Features

The Payment Request Form includes:

- **Company Header:** JAJR Construction name, address, contact info
- **Form Info Table:** Reference PRF, date, PO number fields
- **Payee Section:** Fixed payee (Elaine Maricris T. Aguilar), TIN, address, payment method
- **Payment Table Sections:**
  - Site Salary (when filter = site_salary)
  - Office Salary (when filter = office_salary)
  - Cash Advance (when filter = cash_advance)
  - Employer Share Contribution (when filter = employer_share)
- **Signature Section:** Prepared by (Accounting Staff), Reviewed by (Accountant), Approved by (President)

---

## Mobile Responsive Design

For screens under 767px:
- Table converts to card-based layout
- Column headers hidden
- CSS `::before` pseudo-elements add labels to cells
- Status badges expand to full width
- Grand totals highlighted with gold styling

---

## Security Features

- Session-based authentication check
- Prepared statements for all database queries
- `htmlspecialchars()` for output sanitization
- Branch 33 exclusion in aggregation logic

---

## Usage Instructions

1. **Navigate** to Finance → Billing in the sidebar menu (Admin/Super Admin only)
2. **Select Report Type** from dropdown
3. **Set Date Range** (defaults to current month)
4. Click **Generate Report** to trigger aggregation and view data
5. Click **Print Preview** to generate Payment Request Form
6. Click **Print** button in modal to print or save as PDF

---

## Notes

- Only users with `Admin` or `Super Admin` roles can access Billing via sidebar
- Weekly aggregation updates the `weekly_payroll_reports` table
- Branch 33 is excluded from weekly aggregation (as per cron script logic)
- Employer share calculations are estimates based on standard contribution formulas
