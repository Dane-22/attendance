# Weekly Report & Overtime Module Documentation

## Files Overview

| File | Purpose | Location |
|------|---------|----------|
| `weekly_report.php` | Payroll report with employee earnings, deductions, and net pay | `employee/weekly_report.php` |
| `overtime.php` | Dedicated overtime management and reporting page | `employee/overtime.php` |

---

## weekly_report.php

### Description
Weekly/Monthly payroll report that displays employee attendance, earnings, deductions, and net pay calculations. This is the main payroll processing interface for Admin and Super Admin users.

### Features

#### View Modes
- **Weekly View**: Shows payroll data for a specific week (Week 1-5)
- **Monthly View**: Aggregates payroll data for the entire month

#### Displayed Data
| Column | Description |
|--------|-------------|
| Employee | Full name and employee code |
| Days Worked | Number of days and total hours worked |
| Daily Rate | Employee's daily rate |
| Basic Pay | Days worked × Daily rate |
| Overtime Amount | OT hours × (daily_rate / 8) |
| Gross Pay | Basic Pay + Overtime Amount |
| Performance Allowance | Editable input field |
| Gross + Allowance | Gross Pay + Allowance |
| Deductions | CA, SSS, PHIC, HDMF, SSS Loan |
| Take Home Pay | Net pay after deductions |
| Actions | Payslip button |
| Remarks | Payment status (Paid/Not Paid) |

#### Government Deductions (Weekly)
| Week | SSS | PhilHealth | Pag-IBIG |
|------|-----|------------|----------|
| Week 1-3 | ₱150 | ₱83.33 | ₱66.67 |
| Week 4 | ₱0 | ₱0 | ₱0 |

#### Filters
- Month selection (last 12 months)
- Week selection (1-5, depending on month)
- Branch filter with pagination
- Employee search

#### Export
- Excel export functionality via `exportToExcel()` JavaScript function

#### Modal
- **Payslip Modal**: Shows detailed employee payslip with earnings, deductions, and net pay
- Print functionality with thermal printer optimization

### Database Dependencies
- `attendance` table - for attendance records and OT hours
- `employees` table - for employee details and rates
- `branches` table - for branch filtering
- `weekly_payroll_reports` table - for payment status

### Included Files
```php
require_once __DIR__ . '/../conn/db_connection.php';
require_once __DIR__ . '/../functions.php';
include __DIR__ . '/function/report.php';
```

### Access Control
- **Allowed Roles**: Admin, Super Admin
- **Redirect**: Non-admin users redirected to `../login.php`

---

## overtime.php

### Description
Dedicated overtime management page that provides detailed overtime reporting with employee-wise breakdown. Shows all employees who worked overtime with detailed entry logs.

### Features

#### View Modes
- **Weekly View**: OT data for specific week
- **Monthly View**: OT data for entire month

#### Summary Cards (Top Section)
| Card | Description |
|------|-------------|
| Total Employees with OT | Count of unique employees with OT |
| Total OT Hours | Sum of all overtime hours |
| Total OT Amount | Sum of all overtime pay |

#### Table Columns
| Column | Description |
|--------|-------------|
| Employee | Name, code, and daily rate |
| Date | Attendance date |
| Time In | Clock-in time |
| Time Out | Clock-out time |
| Branch | Branch where OT was worked |
| OT Hours | Overtime hours for that entry |
| OT Amount | Calculated OT pay |

#### Grouping
- Entries are grouped by employee
- Each employee section ends with a **Total Row** showing:
  - Employee's total OT hours
  - Employee's total OT amount

#### Filters
- Month selection
- Week selection (weekly view only)
- Branch dropdown filter
- Employee search (real-time filtering)

#### Export
- CSV export with filename format: `Overtime_Report_YYYY-MM_WeekX.csv`

### Overtime Calculation
```php
$ot_rate = $daily_rate / 8;  // Hourly rate
$ot_amount = $ot_hours * $ot_rate;
```

### Database Dependencies
- `attendance` table - for OT hours, time in/out, dates
- `employees` table - for employee details
- `branches` table - for branch filtering

### Included Files
```php
require_once __DIR__ . '/../conn/db_connection.php';
require_once __DIR__ . '/../functions.php';
```

### Access Control
- **Allowed Roles**: Admin, Super Admin
- **Redirect**: Non-admin users redirected to `../login.php`

---

## Shared Components

### Sidebar Navigation
Both pages are accessible under the **Finance** dropdown in `sidebar.php`:

```
Finance (💰)
├── Weekly Report
├── Overtime        ← overtime.php
├── Billing
└── Cash Advance
```

### Common CSS Files
- `../assets/css/style.css` - Main stylesheet
- `css/report.css` - Report-specific styles
- `css/light-theme.css` - Theme styles

### Common JavaScript
- `js/theme.js` - Theme switching
- Tailwind CSS CDN
- Font Awesome icons

### Date Range Calculation
Both files use the same date range logic:
```php
// Weekly
$week_start_day = 1 + (($selected_week - 1) * 7);
$week_end_day = min($week_start_day + 6, $days_in_month);

// Monthly
$start_date = sprintf('%04d-%02d-01', $year, $month);
$end_date = sprintf('%04d-%02d-%02d', $year, $month, $days_in_month);
```

---

## Integration Notes

### Data Flow
1. Employee clocks in/out → `attendance` table updated with `total_ot_hrs`
2. `weekly_report.php` reads OT data for payroll calculation
3. `overtime.php` provides detailed OT view for management review

### OT Hours Source
- OT hours are recorded in the `attendance.total_ot_hrs` column
- These are calculated during the clock-out process

### Future Enhancements
Consider adding:
- OT approval workflow
- OT rate configuration (currently fixed at daily_rate/8)
- OT history by employee
- OT analytics dashboard
