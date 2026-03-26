# Performance Allowance Implementation - Dev Log

## Summary

Implemented **permanent performance allowance** feature that persists across all weeks/months until explicitly changed.

---

## Changes Made

### 1. `employee/function/report.php`

#### Added column existence check (lines 187-204)
```php
// Check if performance_allowance column exists
$column_check = mysqli_query($db, "SHOW COLUMNS FROM employees LIKE 'performance_allowance'");
$has_allowance_column = mysqli_num_rows($column_check) > 0;

if ($has_allowance_column) {
    $all_employees_query = "SELECT e.id, ..., e.performance_allowance, ...";
} else {
    // Fallback query without performance_allowance column
    $all_employees_query = "SELECT e.id, ...";
}
```

#### Initialize with employee default (line 235)
```php
'performance_allowance' => floatval($emp['performance_allowance'] ?? 0),
```

#### Load default + override logic (lines 552-559)
```php
// Load employee's default performance allowance
$default_allowance = floatval($payroll['employee']['performance_allowance'] ?? 0);
$payroll['performance_allowance'] = $default_allowance;

// Override with weekly-specific value if exists
if (isset($weekly_allowances[$emp_id])) {
    $payroll['performance_allowance'] = $weekly_allowances[$emp_id];
}
```

### 2. `employee/update_allowance.php`

#### Save to employees table as permanent default (lines 81-89)
```php
// Also update employee's default performance allowance (permanent)
$update_employee = "UPDATE employees SET performance_allowance = ? WHERE id = ?";
$emp_stmt = @mysqli_prepare($db, $update_employee);
if ($emp_stmt) {
    @mysqli_stmt_bind_param($emp_stmt, 'di', $performance_allowance, $employee_id);
    @mysqli_stmt_execute($emp_stmt);
    @mysqli_stmt_close($emp_stmt);
}
```

---

## Error Encountered & Fixed

### ❌ 500 Internal Server Error

**Cause:** `performance_allowance` column did not exist in `employees` table on production server.

**Error Log:**
```
Unknown column 'e.performance_allowance' in 'field list'
```

**Fix:** Made code backward compatible by checking if column exists before querying it:
- Query with column if it exists
- Fallback query without column if it doesn't
- Use `?? 0` null coalescing to handle missing values

---

## SQL Required

Run this to enable permanent performance allowance:

```sql
ALTER TABLE employees 
ADD COLUMN performance_allowance DECIMAL(10,2) DEFAULT 0.00 
AFTER daily_rate;
```

**Migration file:** `employee/cron/migrate_performance_allowance.php`

---

## How It Works

### Data Flow
```
1. Admin sets allowance to 500 for Week 1
   ↓
2. Saves to weekly_payroll_reports (Week 1 record)
   ↓
3. ALSO saves to employees.performance_allowance (permanent default)
   ↓
4. Week 2 loads → Shows 500 (from employee default)
   ↓
5. Admin changes to 600 in Week 3
   ↓
6. Updates both tables → New permanent default is 600
   ↓
7. All future weeks show 600
```

### Priority Order
1. **Weekly override** (if exists in weekly_payroll_reports)
2. **Employee default** (from employees table)
3. **Zero** (fallback)

---

## Testing Status

| Test | Status | Notes |
|------|--------|-------|
| Page loads without 500 | ✅ Fixed | Backward compatible code |
| Allowance saves to weekly table | ✅ Working | Existing functionality |
| Allowance saves to employees table | ⚠️ Needs column | Run SQL first |
| Default loads for new weeks | ⚠️ Needs column | Requires column to exist |
| Override still works | ✅ Working | Can change per-week if needed |

---

## Files Modified

1. `employee/function/report.php` - 3 changes (column check, init, load logic)
2. `employee/update_allowance.php` - 1 change (save to employees table)

---

## Known Issues / Notes

1. **Column must exist:** Feature only works after running the SQL ALTER statement
2. **No UI indicator:** Users can't see what the "permanent default" is vs weekly override
3. **All employees affected:** Changing an employee's allowance affects all their future reports
4. **No history:** Previous allowance values are not tracked (only current default saved)

---

## Related Documentation Created

- `docs/PERMANENT_PERFORMANCE_ALLOWANCE.md` - Implementation guide (deleted by user)
- `docs/HOW_WEEKLY_REPORT_WORKS.md` - How report works (deleted by user)

---

## Git History

Commit: `2eff673` - "performance allowance should be static now"
- 9 files changed
- 879 insertions, 5 deletions
- Pushed to origin main
