# Weekly Payroll Report Documentation

## Overview

The `weekly_report.php` file is an admin-facing payroll reporting module that displays weekly and monthly deployment and attendance reports for workers. It calculates payroll data including days worked, hours, overtime, deductions, and net pay.

---

## Main File

### `employee/weekly_report.php`

**Location:** `c:\wamp64\www\main\employee\weekly_report.php`

**Purpose:** Admin panel page for viewing and managing weekly/monthly payroll reports

**Access Control:**
- Requires user to be logged in
- User position must be: `Admin`, `Super Admin`, or `Developer`
- Redirects to `../login.php` if unauthorized

**Key Features:**
- Weekly and Monthly view toggle
- Branch filtering with pagination
- Employee search functionality
- Editable Performance Allowance (per employee)
- Editable Cash Advance deduction
- Payment status tracking (Paid/Not Paid)
- Individual payslip generation and printing
- Excel export functionality
- Real-time calculation updates

---

## Core Logic File

### `employee/function/report.php`

**Location:** `c:\wamp64\www\main\employee\function\report.php`

**Purpose:** Contains all data fetching and calculation logic for the payroll report

**Key Functions:**
- Date range calculation (weekly/monthly)
- Payroll data aggregation from `daily_payroll_reports` and `attendance` tables
- Government deductions calculation (SSS, PhilHealth, Pag-IBIG)
- Per-branch attendance tracking
- Performance allowance loading from `weekly_payroll_reports`
- Payment status retrieval

---

## Supporting Files

### JavaScript: `employee/js/report.js`

**Location:** `c:\wamp64\www\main\employee\js\report.js`

**Features:**
- Employee search filtering
- View toggle (weekly/monthly) handling
- Excel export using SheetJS (XLSX)
- Real-time calculation updates for CA and Allowance inputs
- Grand total recalculation
- Mobile sidebar toggle

### CSS: `employee/css/report.css`

**Location:** `c:\wamp64\www\main\employee\css\report.css`

**Styling:**
- Dark theme with gold accents
- Responsive mobile card view (table transforms to cards on mobile)
- Print-optimized styles
- Modal styling for payslip
- Toast notification styles
- Branch badge styling

---

## API Endpoints

### `employee/update_payment_status.php`

**Purpose:** AJAX endpoint to update employee payment status

**Parameters:**
- `employee_id` (int)
- `payment_status` (string: 'Paid' or 'Not Paid')
- `year` (int)
- `month` (int)
- `week` (int)
- `view_type` (string: 'weekly' or 'monthly')

**Response:** JSON `{ success: true/false, error?: string }`

### `employee/update_allowance.php`

**Purpose:** AJAX endpoint to save performance allowance

**Parameters:**
- `employee_id` (int)
- `performance_allowance` (decimal)
- `year` (int)
- `month` (int)
- `week` (int)
- `view_type` (string)

**Actions:**
- Upserts to `weekly_payroll_reports` table
- Updates default allowance in `employees` table
- Falls back to updating `daily_payroll_reports` if needed

---

## Database Tables

### 1. `weekly_payroll_reports`

**Purpose:** Stores aggregated weekly payroll data and payment status

**Schema:**
```sql
CREATE TABLE `weekly_payroll_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `report_year` int NOT NULL,
  `report_month` int NOT NULL,
  `week_number` int NOT NULL COMMENT 'Week 1-5',
  `view_type` enum('weekly','monthly') DEFAULT 'weekly',
  `branch_id` int DEFAULT NULL,
  `days_worked` int DEFAULT '0',
  `total_hours` int DEFAULT '0',
  `daily_rate` decimal(10,2) DEFAULT '0.00',
  `basic_pay` decimal(10,2) DEFAULT '0.00',
  `ot_hours` decimal(5,2) DEFAULT '0.00',
  `ot_rate` decimal(10,2) DEFAULT '0.00',
  `ot_amount` decimal(10,2) DEFAULT '0.00',
  `performance_allowance` decimal(10,2) DEFAULT '0.00',
  `gross_pay` decimal(10,2) DEFAULT '0.00',
  `gross_plus_allowance` decimal(10,2) DEFAULT '0.00',
  `ca_deduction` decimal(10,2) DEFAULT '0.00',
  `sss_deduction` decimal(10,2) DEFAULT '0.00',
  `philhealth_deduction` decimal(10,2) DEFAULT '0.00',
  `pagibig_deduction` decimal(10,2) DEFAULT '0.00',
  `sss_loan` decimal(10,2) DEFAULT '0.00',
  `total_deductions` decimal(10,2) DEFAULT '0.00',
  `take_home_pay` decimal(10,2) DEFAULT '0.00',
  `status` enum('Draft','Finalized','Processed') DEFAULT 'Draft',
  `payment_status` enum('Paid','Not Paid') DEFAULT 'Not Paid',
  `created_by` int DEFAULT NULL,
  `finalized_by` int DEFAULT NULL,
  `finalized_at` timestamp NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_employee_period_week` (`employee_id`,`report_year`,`report_month`,`week_number`,`view_type`,`branch_id`)
) ENGINE=InnoDB;
```

### 2. `daily_payroll_reports`

**Purpose:** Stores daily payroll records (primary data source)

**Schema:**
```sql
CREATE TABLE `daily_payroll_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `report_date` date NOT NULL,
  `report_year` int NOT NULL,
  `report_month` int NOT NULL,
  `report_day` int NOT NULL,
  `week_number` int DEFAULT '1',
  `branch_id` int DEFAULT NULL,
  `days_worked` decimal(4,1) DEFAULT '0.0',
  `total_hours` decimal(8,2) DEFAULT '0.00',
  `daily_rate` decimal(10,2) DEFAULT '0.00',
  `basic_pay` decimal(10,2) DEFAULT '0.00',
  `ot_hours` decimal(6,2) DEFAULT '0.00',
  `ot_rate` decimal(10,2) DEFAULT '0.00',
  `ot_amount` decimal(10,2) DEFAULT '0.00',
  `performance_allowance` decimal(10,2) DEFAULT '0.00',
  `gross_pay` decimal(10,2) DEFAULT '0.00',
  `gross_plus_allowance` decimal(10,2) DEFAULT '0.00',
  `ca_deduction` decimal(10,2) DEFAULT '0.00',
  `sss_deduction` decimal(10,2) DEFAULT '0.00',
  `philhealth_deduction` decimal(10,2) DEFAULT '0.00',
  `pagibig_deduction` decimal(10,2) DEFAULT '0.00',
  `sss_loan` decimal(10,2) DEFAULT '0.00',
  `total_deductions` decimal(10,2) DEFAULT '0.00',
  `take_home_pay` decimal(10,2) DEFAULT '0.00',
  `status` varchar(20) DEFAULT 'Pending',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_emp_date_branch` (`employee_id`,`report_date`,`branch_id`)
) ENGINE=InnoDB;
```

### 3. `attendance`

**Purpose:** Stores individual attendance records (fallback data source)

**Schema:**
```sql
CREATE TABLE `attendance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `status` enum('Present','Late','Absent','System') DEFAULT NULL,
  `branch_name` varchar(50) NOT NULL,
  `attendance_date` date NOT NULL,
  `time_in` datetime DEFAULT NULL,
  `time_out` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL,
  `is_auto_absent` tinyint(1) DEFAULT '0',
  `auto_absent_applied` tinyint(1) DEFAULT '0',
  `absent_notes` text,
  `is_overtime_running` tinyint(1) NOT NULL,
  `is_time_running` tinyint(1) NOT NULL,
  `total_ot_hrs` varchar(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_attendance_employee_date` (`employee_id`,`attendance_date`)
) ENGINE=MyISAM;
```

### 4. `employees`

**Purpose:** Employee master data including default performance allowance

**Schema:**
```sql
CREATE TABLE `employees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_code` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `position` varchar(50) DEFAULT 'Employee',
  `status` varchar(50) DEFAULT 'Active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE,
  `profile_image` varchar(255) DEFAULT NULL,
  `daily_rate` decimal(10,2) DEFAULT '600.00',
  `branch_id` int DEFAULT NULL,
  `performance_allowance` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_code` (`employee_code`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_employees_branch` (`branch_id`)
) ENGINE=MyISAM;
```

### 5. `branches`

**Purpose:** Branch reference data for filtering

**Schema:**
```sql
CREATE TABLE `branches` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_number` varchar(10) DEFAULT NULL,
  `branch_name` varchar(50) NOT NULL,
  `branch_address` varchar(55) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `is_active` tinyint DEFAULT '1',
  `lat` varchar(20) DEFAULT NULL COMMENT 'Latitude',
  `long` varchar(20) DEFAULT NULL COMMENT 'Longitude',
  PRIMARY KEY (`id`),
  UNIQUE KEY `branch_name` (`branch_name`)
) ENGINE=MyISAM;
```

### 6. `weekly_report_summaries`

**Purpose:** Aggregated summary reports for export/archival

**Schema:**
```sql
CREATE TABLE `weekly_report_summaries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `report_year` int NOT NULL,
  `report_month` int NOT NULL,
  `week_number` int NOT NULL,
  `view_type` enum('weekly','monthly') DEFAULT 'weekly',
  `branch_id` int DEFAULT NULL,
  `branch_filter_name` varchar(100) DEFAULT NULL,
  `total_employees` int DEFAULT '0',
  `total_days_worked` int DEFAULT '0',
  `total_basic_pay` decimal(12,2) DEFAULT '0.00',
  `total_ot_amount` decimal(12,2) DEFAULT '0.00',
  `total_allowances` decimal(12,2) DEFAULT '0.00',
  `total_gross_pay` decimal(12,2) DEFAULT '0.00',
  `total_ca_deductions` decimal(12,2) DEFAULT '0.00',
  `total_sss_deductions` decimal(12,2) DEFAULT '0.00',
  `total_philhealth_deductions` decimal(12,2) DEFAULT '0.00',
  `total_pagibig_deductions` decimal(12,2) DEFAULT '0.00',
  `total_sss_loans` decimal(12,2) DEFAULT '0.00',
  `total_deductions` decimal(12,2) DEFAULT '0.00',
  `total_take_home_pay` decimal(12,2) DEFAULT '0.00',
  `status` enum('Draft','Finalized','Exported') DEFAULT 'Draft',
  `exported_at` timestamp NULL,
  `exported_by` int DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_period_view_branch` (`report_year`,`report_month`,`week_number`,`view_type`,`branch_id`)
) ENGINE=InnoDB;
```

---

## Data Flow

1. **Data Collection:**
   - Daily attendance records stored in `attendance` table
   - Cron jobs aggregate daily data into `daily_payroll_reports`

2. **Report Generation:**
   - `report.php` queries `daily_payroll_reports` as primary source
   - Falls back to `attendance` table for dates without payroll records
   - Filters by date range, branch, and employee position='Worker'

3. **Calculations:**
   - Days worked: Aggregated from daily records
   - Gross pay: `daily_rate × days_worked`
   - OT amount: `ot_hours × (daily_rate / 8)`
   - Deductions: Government deductions applied based on week
   - Take home: `gross_pay + performance_allowance + ot_amount - total_deductions - ca_deduction`

4. **Data Persistence:**
   - Performance allowance saved to `weekly_payroll_reports` and `employees`
   - Payment status saved to `weekly_payroll_reports`

---

## Government Deductions Schedule

### Weekly View (Prorated):

| Week | SSS | PhilHealth | Pag-IBIG |
|------|-----|------------|----------|
| Week 1 | 250.00 | 100.00 | 50.00 |
| Week 2 | 100.00 | 100.00 | 50.00 |
| Week 3 | 100.00 | 50.00 | 100.00 |
| Week 4 | 0.00 | 0.00 | 0.00 |
| Week 5 | 0.00 | 0.00 | 0.00 |

### Monthly View (Full):

| Deduction | Amount |
|-----------|--------|
| SSS | 450.00 |
| PhilHealth | 250.00 |
| Pag-IBIG | 200.00 |

---

## URL Parameters

- `view` - 'weekly' or 'monthly'
- `month` - YYYY-MM format (e.g., '2026-03')
- `week` - Week number (1-5)
- `branch` - Branch ID or 'all'
- `branch_page` - Pagination page for branch filter

---

## Connected Files Summary

| File | Purpose |
|------|---------|
| `weekly_report.php` | Main report page UI |
| `function/report.php` | Data fetching and calculation logic |
| `js/report.js` | Client-side functionality |
| `css/report.css` | Styling and responsive design |
| `update_payment_status.php` | AJAX endpoint for payment status |
| `update_allowance.php` | AJAX endpoint for performance allowance |
| `sidebar.php` | Navigation sidebar include |
| `light-theme.css` | Light theme styling |
| `theme.js` | Theme switching functionality |

---

## Security Features

- Session-based authentication
- Role-based access control (Admin, Super Admin, Developer only)
- Prepared SQL statements for all database queries
- Input validation and sanitization
- Error logging to file (not displayed to users)

---

## Mobile Responsiveness

The report table transforms into card view on mobile devices (< 767px):
- Table headers hidden
- Each row becomes a card with labeled data fields
- Inputs and dropdowns full-width
- Sticky total row at bottom
