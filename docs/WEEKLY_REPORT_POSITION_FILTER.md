# Weekly Report - Position Display Filter

## Current Behavior

The `employee/weekly_report.php` report **only displays employees with position = "Worker"** (case-insensitive match).

## Filter Locations

The position filter is applied in `employee/function/report.php` at three query points:

| Line | Query | Filter Clause |
|------|-------|---------------|
| 207 | Payroll data from `daily_payroll_reports` | `AND LOWER(e.position) = 'worker'` |
| 236 | Attendance data from `attendance` table | `AND LOWER(e.position) = 'worker'` |
| 371 | Active employees list from `employees` | `AND LOWER(e.position) = 'worker'` |

## What This Means

- **Only Workers** appear in the weekly payroll report
- **Excluded positions** (not shown in report):
  - Admin
  - Super Admin
  - Developer
  - Security Guard
  - Engineer
  - Supervisor
  - Manager
  - Any custom position other than "Worker"

## To Include Other Positions

To display additional positions in the weekly report, modify the three SQL queries in `employee/function/report.php`:

### Option 1: Include specific positions
```sql
AND LOWER(e.position) IN ('worker', 'security guard', 'engineer')
```

### Option 2: Exclude only admin positions
```sql
AND LOWER(e.position) NOT IN ('admin', 'super admin', 'developer')
```

### Option 3: Show all active employees
Remove the position filter entirely from all three queries (lines 207, 236, 371).

## Admin Access

Note: While only Workers appear in the report, the report itself is accessible to users with positions:
- Admin
- Super Admin
- Developer

(See `weekly_report.php:47` for access control)
