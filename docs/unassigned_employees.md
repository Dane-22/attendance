# Unassigned Employees in Site Salary Report

## Overview

**Context:** Site Salary (Total Salary per Branch) Report in `billing.php`  
**Label:** "Unassigned"  
**Location:** Billing & Payroll Reports → Site Salary (Per Branch)

---

## What Does "Unassigned" Mean?

"Unassigned" refers to **employees who have payroll or attendance records but are not linked to any specific branch** in the database. This occurs when:

1. The employee's `branch_id` is NULL in the `daily_payroll_reports` table
2. The employee's `branch_name` in `attendance` table doesn't match any entry in the `branches` table
3. The LEFT JOIN between payroll/attendance data and the branches table returns no matching branch

---

## Technical Explanation

### SQL Logic

The "Unassigned" category is created by the `COALESCE` function in the SQL queries:

```sql
SELECT 
    COALESCE(b.branch_name, 'Unassigned') as branch_name,
    ...
FROM daily_payroll_reports dpr
LEFT JOIN branches b ON dpr.branch_id = b.id
```

**How it works:**
- If `b.branch_name` exists → Display the actual branch name
- If `b.branch_name` is NULL → Display 'Unassigned'

### Where Unassigned Data Comes From

#### 1. Daily Payroll Reports (Primary Source)

```sql
FROM daily_payroll_reports dpr
LEFT JOIN employees e ON dpr.employee_id = e.id
LEFT JOIN branches b ON dpr.branch_id = b.id
WHERE dpr.report_date BETWEEN ? AND ?
  AND (b.branch_name IS NULL OR UPPER(b.branch_name) != 'MAIN OFFICE')
```

**Unassigned occurs when:**
- `dpr.branch_id` is NULL
- `dpr.branch_id` contains a value that doesn't exist in `branches.id`

#### 2. Attendance Table (Fallback Source)

```sql
FROM attendance a
LEFT JOIN employees e ON a.employee_id = e.id
LEFT JOIN branches b ON a.branch_name = b.branch_name
WHERE a.attendance_date BETWEEN ? AND ?
  AND a.time_out IS NOT NULL
  AND (b.branch_name IS NULL OR UPPER(b.branch_name) != 'MAIN OFFICE')
```

**Unassigned occurs when:**
- `a.branch_name` is NULL
- `a.branch_name` contains a value that doesn't match any `branches.branch_name`

---

## Common Causes of Unassigned Status

### 1. Missing Branch Assignment During Payroll Generation

When the weekly aggregation script (`weekly_aggregate_non_branch33.php`) or daily payroll generator runs, if an employee doesn't have a valid branch_id assigned, their records go into "Unassigned".

**Affected Tables:**
- `daily_payroll_reports.branch_id` → NULL or invalid ID

### 2. Branch Name Mismatch in Attendance

If an employee clocks in and the system records a `branch_name` that:
- Contains typos
- Uses different casing (e.g., "bcda-admin" vs "BCDA - Admin")
- Is a deprecated/renamed branch
- Doesn't exist in the `branches` table

**Affected Tables:**
- `attendance.branch_name` → Non-matching value

### 3. Employee Transfers

When an employee is transferred between branches:
- Old attendance records may reference the previous branch
- If the old branch was deleted/renamed, records become "Unassigned"
- Payroll reports may have NULL branch_id during transition

### 4. Deleted Branches

If a branch is deleted from the `branches` table but:
- Old `daily_payroll_reports` still reference the deleted `branch_id`
- Old `attendance` records still have the deleted `branch_name`

These orphaned records appear as "Unassigned".

### 5. Data Import/Export Issues

When importing data from:
- Excel spreadsheets
- Other systems
- Manual bulk updates

Branch IDs or names may not properly map to existing branches.

### 6. System Bugs or Edge Cases

- Clock-in system fails to capture branch location
- Mobile app sync issues
- API integrations not properly setting branch_id

---

## Database Schema Reference

### Relevant Tables

```
branches
├── id (INT, PK)
├── branch_name (VARCHAR)
└── ...

daily_payroll_reports
├── id (INT, PK)
├── employee_id (INT, FK)
├── branch_id (INT, FK, nullable)
├── report_date (DATE)
├── basic_pay (DECIMAL)
├── ot_amount (DECIMAL)
├── gross_pay (DECIMAL)
├── total_deductions (DECIMAL)
├── take_home_pay (DECIMAL)
└── ...

attendance
├── id (INT, PK)
├── employee_id (INT, FK)
├── branch_name (VARCHAR, nullable)
├── attendance_date (DATE)
├── time_in (TIME)
├── time_out (TIME)
├── total_ot_hrs (DECIMAL)
└── ...

employees
├── id (INT, PK)
├── employee_code (VARCHAR)
├── first_name (VARCHAR)
├── last_name (VARCHAR)
├── daily_rate (DECIMAL)
└── ...
```

### Foreign Key Relationships

```
daily_payroll_reports.branch_id → branches.id (nullable, not enforced by FK)
attendance.branch_name → branches.branch_name (not enforced, string match)
```

---

## How to Identify Specific Unassigned Employees

### Query to Find Unassigned Employees (from daily_payroll_reports)

```sql
SELECT 
    dpr.employee_id,
    e.employee_code,
    CONCAT(e.first_name, ' ', e.last_name) as employee_name,
    dpr.report_date,
    dpr.basic_pay,
    dpr.branch_id,
    'No matching branch in branches table' as reason
FROM daily_payroll_reports dpr
LEFT JOIN employees e ON dpr.employee_id = e.id
LEFT JOIN branches b ON dpr.branch_id = b.id
WHERE dpr.report_date BETWEEN '2026-03-01' AND '2026-03-31'
  AND b.branch_name IS NULL
ORDER BY dpr.employee_id, dpr.report_date;
```

### Query to Find Unassigned Employees (from attendance)

```sql
SELECT 
    a.employee_id,
    e.employee_code,
    CONCAT(e.first_name, ' ', e.last_name) as employee_name,
    a.attendance_date,
    a.branch_name,
    'Branch name not found in branches table' as reason
FROM attendance a
LEFT JOIN employees e ON a.employee_id = e.id
LEFT JOIN branches b ON a.branch_name = b.branch_name
WHERE a.attendance_date BETWEEN '2026-03-01' AND '2026-03-31'
  AND a.time_out IS NOT NULL
  AND b.branch_name IS NULL
ORDER BY a.employee_id, a.attendance_date;
```

---

## How to Fix Unassigned Status

### Option 1: Update Branch ID in Daily Payroll Reports

```sql
-- Assign unassigned records to a specific branch
UPDATE daily_payroll_reports
SET branch_id = [correct_branch_id]
WHERE branch_id IS NULL
  AND report_date BETWEEN '2026-03-01' AND '2026-03-31';
```

### Option 2: Update Branch Name in Attendance Records

```sql
-- Fix misspelled or outdated branch names
UPDATE attendance
SET branch_name = 'Correct Branch Name'
WHERE branch_name = 'Old/Misspelled Name'
  AND attendance_date BETWEEN '2026-03-01' AND '2026-03-31';
```

### Option 3: Re-run Payroll Aggregation

If the issue is with the weekly aggregation, re-run:
```
https://your-server.com/employee/cron/weekly_aggregate_non_branch33.php
```

### Option 4: Verify Employee Branch Assignment

Check if employees have proper branch assignments in their profile:
```sql
SELECT 
    e.id,
    e.employee_code,
    e.first_name,
    e.last_name,
    e.branch_id,
    b.branch_name
FROM employees e
LEFT JOIN branches b ON e.branch_id = b.id
WHERE e.branch_id IS NULL OR b.id IS NULL;
```

---

## Prevention Strategies

### 1. Data Validation

Add validation to ensure branch_id is set before:
- Recording attendance
- Generating payroll reports
- Importing employee data

### 2. Database Constraints

Consider adding:
- Foreign key constraints (if not already present)
- Triggers to validate branch_id before insert/update
- Default branch assignment for new employees

### 3. UI Improvements

In the billing report interface:
- Add a "View Unassigned Employees" button
- Show a warning when Unassigned count > 0
- Provide quick links to fix unassigned records

### 4. Regular Audits

Run monthly queries to check for:
- Employees with NULL branch_id
- Attendance records with unmatched branch names
- Orphaned payroll records

---

## Impact of Unassigned Status

### Financial Reporting
- Unassigned employees' salaries are still calculated and paid
- They appear as a separate line item in the Site Salary report
- Grand totals include all unassigned amounts

### Data Integrity
- Makes it harder to track costs per branch
- Complicates branch-level budgeting
- May affect compliance reporting

### User Experience
- Managers can't filter by branch for these employees
- Reporting becomes less clear
- May cause confusion in payroll processing

---

## Related Code in billing.php

### Display Logic

```php
// Line ~344 in billing.php
<td><?php echo htmlspecialchars($row['branch_name'] ?? 'N/A'); ?></td>
```

The branch name is displayed directly from the SQL result. When `COALESCE` returns 'Unassigned', that's what appears in the table.

### Filter Logic

```php
// Lines 69 and 92 in billing.php
AND (b.branch_name IS NULL OR UPPER(b.branch_name) != 'MAIN OFFICE')
```

This ensures:
- Main office employees are excluded from Site Salary
- Unassigned employees (NULL branch) are included
- All other branches are included

---

## Summary

| Aspect | Details |
|--------|---------|
| **What** | Employees without valid branch assignments |
| **Why** | Missing branch_id or non-matching branch_name |
| **Where** | daily_payroll_reports, attendance tables |
| **Impact** | Included in reports but not attributed to any branch |
| **Fix** | Update branch_id/branch_name or re-run aggregation |

---

## Questions to Investigate

If you have Unassigned employees showing up, ask:

1. **Which specific employees are affected?** (Run queries above)
2. **When did they become Unassigned?** (Check date ranges)
3. **Is it a recent issue or ongoing?** (Compare historical data)
4. **Are these new employees or transfers?** (Check employee records)
5. **Did a branch get deleted recently?** (Check branches table history)

---

*Documentation generated for JAJR Construction Attendance System*  
*File: `docs/unassigned_employees.md`*
