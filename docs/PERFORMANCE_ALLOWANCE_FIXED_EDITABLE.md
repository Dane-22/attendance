# Performance Allowance - Fixed but Editable System

## Overview

Each employee now has a **default performance allowance** stored in their profile. This value:
- Auto-populates in the weekly report for each worker
- Can be edited per week if needed (overrides the default)
- Persists the override in `weekly_payroll_reports` table

---

## How It Works

### Data Flow

```
Employee Profile (performance_allowance)
         │
         │ Default value
         ▼
┌─────────────────────────────────────┐
│  Weekly Report Load                 │
│  1. Load employee default           │
│  2. Check weekly_payroll_reports    │
│  3. If weekly value exists → use it │
│  4. Else → use employee default     │
└─────────────────────────────────────┘
         │
         │ Display in UI (editable)
         ▼
┌─────────────────────────────────────┐
│  Admin Edits Value                  │
│  → Saves to weekly_payroll_reports  │
│  (Overrides default for this week)  │
└─────────────────────────────────────┘
```

---

## Database Changes

### 1. Employees Table
Added column:
```sql
ALTER TABLE employees 
ADD COLUMN performance_allowance DECIMAL(10,2) DEFAULT 0.00 
AFTER daily_rate;
```

**Migration file:** `employee/cron/migrate_performance_allowance.php`

### 2. Data Priority (report.php)

```php
// 1. Load employee's default allowance
$employee_payroll[$emp_id]['performance_allowance'] = floatval($emp['performance_allowance'] ?? 0);

// 2. Check for weekly-specific override
if (isset($weekly_allowances[$emp_id]) && $weekly_allowances[$emp_id] > 0) {
    $payroll['performance_allowance'] = $weekly_allowances[$emp_id];
}
```

---

## Usage Example

### Scenario 1: Default Allowance
1. Worker "John" has `performance_allowance = 500` in employees table
2. Week 1 report loads → shows 500 in input field
3. Admin doesn't change it → John gets 500 for Week 1

### Scenario 2: Override for One Week
1. Week 2 report loads → shows 500 (default)
2. Admin changes to 600 for Week 2
3. Saves to `weekly_payroll_reports` → Week 2 override = 600
4. Week 3 report loads → shows 500 again (default restored)

### Scenario 3: Update Default
1. Admin updates John's profile: `performance_allowance = 550`
2. All future weeks show 550 by default
3. Previous week overrides remain unchanged

---

## File Changes

| File | Change |
|------|--------|
| `employee/cron/migrate_performance_allowance.php` | Migration script for new column |
| `employee/function/report.php:187` | Added `performance_allowance` to SELECT |
| `employee/function/report.php:222` | Initialize with employee default |
| `employee/function/report.php:552-559` | Priority: weekly override > employee default |

---

## Key Features

1. **Fixed Default**: Each worker has a standard allowance in their profile
2. **Editable Per Week**: Can be changed for specific weeks
3. **Persistence**: Week-specific values saved to `weekly_payroll_reports`
4. **Fallback**: If no weekly override, uses employee default

---

## To Set Employee Default Allowance

Run SQL:
```sql
UPDATE employees 
SET performance_allowance = 500.00 
WHERE id = [employee_id];
```

Or add UI field in employee management page.
