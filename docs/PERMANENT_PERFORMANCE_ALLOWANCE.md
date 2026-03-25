# Permanent Performance Allowance - Implementation Guide

## Overview

Performance allowance now **persists permanently** for each employee until explicitly changed.

## How It Works

### Data Flow

```
Admin sets allowance to 500
         │
         ▼
┌─────────────────────────────────────┐
│ 1. Saves to weekly_payroll_reports  │
│    (for this specific week/month)   │
└─────────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────┐
│ 2. Also saves to employees table    │
│    (as permanent default)           │
└─────────────────────────────────────┘
         │
         ▼
Next week loads → Shows 500 (from employee default)
         │
         ▼
Admin can change anytime → Updates both tables
```

## Key Behavior

### ✅ Permanent Until Changed

Once you set an employee's allowance to **500**:
- Week 1: Shows 500
- Week 2: Shows 500 (auto-loaded from employee profile)
- Week 3: Shows 500
- Month 1: Shows 500
- **Forever** until you change it

### Override Still Possible

If you need a different value for a specific week:
- Change it in that week's report
- It saves to weekly_payroll_reports (override)
- Also updates employees table (new permanent default)
- All future weeks will use this new value

## Implementation Details

### Database

**Column added to employees table:**
```sql
performance_allowance DECIMAL(10,2) DEFAULT 0.00
```

**Migration file:** `employee/cron/migrate_performance_allowance.php`

### File Changes

| File | Change |
|------|--------|
| `employee/function/report.php:187` | Added `e.performance_allowance` to SELECT |
| `employee/function/report.php:222` | Initialize with employee default |
| `employee/function/report.php:552-559` | Load default, apply weekly override if exists |
| `employee/update_allowance.php:81-89` | Save to employees table as permanent default |

### Loading Priority

```php
// 1. Load employee's permanent default
$payroll['performance_allowance'] = floatval($emp['performance_allowance'] ?? 0);

// 2. Check for week-specific override
if (isset($weekly_allowances[$emp_id])) {
    $payroll['performance_allowance'] = $weekly_allowances[$emp_id];
}
```

**Result:** Weekly override takes priority if it exists, otherwise uses permanent default.

## Usage

### Setting Permanent Allowance

1. Go to weekly report
2. Enter allowance value in input field
3. Value auto-saves
4. Now it's the **permanent default** for that employee

### Changing Permanent Allowance

1. Any week you view the employee
2. Change the value
3. It becomes the **new permanent default**
4. All future weeks show this new value

### Checking Current Default

```sql
SELECT first_name, last_name, performance_allowance 
FROM employees 
WHERE id = [employee_id];
```

## Testing

1. Set allowance to 500 for employee
2. Check next week's report → should show 500
3. Check monthly view → should show 500
4. Change to 600 → should update immediately
5. Check following week → should show 600

## Summary

- **Before:** Allowance was per-week only (reset each week)
- **After:** Allowance is permanent (stored in employee profile)
- **Flexibility:** Can still override per-week if needed
- **Persistence:** Survives page refreshes, new weeks, new months
