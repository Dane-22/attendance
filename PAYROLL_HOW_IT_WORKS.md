# How Payroll Works

## Overview

The payroll system is an automated daily and weekly calculation process that tracks employee attendance, computes wages based on daily rates, handles overtime, and applies government-mandated deductions.

---

## Core Components

### 1. Time Tracking (Clock In/Out)

Employees must clock in at the start of their shift and clock out at the end.

**Clock In** (`employee/function/clock_functions.php:41-376`)
- Records `time_in` timestamp
- Sets status to "Present"
- Auto-assigns employee to selected branch
- Checks for existing active time-in (prevents double clock-in)

**Clock Out** (`employee/function/clock_functions.php:382-488`)
- Records `time_out` timestamp
- Calculates total hours worked: `(time_out - time_in) / 3600`
- Updates `total_hours` field

---

### 2. Daily Payroll Calculation (Cron Job)

**Script**: `employee/cron/daily_payroll_calculation.php`

**When**: Runs every midnight (automated via Windows Task Scheduler)

**What it does**:
1. Loads all active employees with their daily rates
2. Loads attendance records for the previous day
3. Calculates hours worked from `time_in` and `time_out`
4. Computes payroll per employee per branch

**Formula**:
```
gross_pay = daily_rate × days_worked
ot_rate = daily_rate / 8
ot_amount = total_ot_hrs × ot_rate
gross_plus_allowance = gross_pay + ot_amount
net_pay = gross_plus_allowance - total_deductions
```

**Output**: Saves to `daily_payroll_reports` table

---

### 3. Weekly Payroll Calculation (Cron Job)

**Script**: `employee/cron/weekly_payroll_calculation.php`

**When**: Runs every Sunday midnight

**What it does**:
- Aggregates daily payroll data into weekly summaries
- Handles multi-branch scenarios (split days when employee transfers)
- Applies government deductions based on branch rules

**Deduction Rules**:
- **Non-Branch 33**: Deductions applied on Weeks 1-3 only
- **Branch 33**: Deductions applied on Week 4 only (monthly basis)

**Standard Deductions** (for non-Security Guard positions):
| Deduction | Amount |
|-----------|--------|
| SSS | ₱800 |
| PhilHealth | ₱300 |
| Pag-IBIG | ₱200 |
| **Total** | **₱1,300** |

---

### 4. Multi-Branch Handling

When an employee works at multiple branches in a single day:

- **2 branches**: Each gets `0.5` day credit (transfer scenario)
- **1 branch**: Full `1.0` day credit

Example from `weekly_payroll_calculation.php:179-207`:
```php
if (count($branches) === 2) {
    // Split day for 2 branches
    foreach ($branches as $bName => $bData) {
        $payroll['_branches'][$bName]['days'] += 0.5;
    }
    $payroll['days_worked'] += 1.0;
} else {
    // Full day at one branch
    $payroll['_branches'][$bName]['days'] += 1.0;
    $payroll['days_worked'] += 1.0;
}
```

---

### 5. Overtime Calculation

**OT Rate**: `daily_rate / 8` (hourly rate)

**OT Hours**: Taken from `attendance.total_ot_hrs` field (must be pre-approved)

**OT Pay**: `ot_hours × ot_rate`

---

## Does It Count If Employee Forgot To Time Out?

**NO** — If an employee forgets to time out, that day's attendance **will NOT be counted** in payroll calculations.

### The Rule (from `employee/function/report.php:282-286`):

```php
// Only include attendance in report totals AFTER employee has timed out
$time_in = $row['time_in'] ?? null;
$time_out = $row['time_out'] ?? null;
if (empty($time_in) || empty($time_out)) {
    continue;  // SKIP this record - no pay for this day
}
```

### Why?

1. **Cannot calculate hours worked** without `time_out`
2. **Cannot verify actual attendance** — was it a forgotten clock-out or a no-show?
3. **Prevents payroll fraud** — ensures accurate time tracking

### What Employees See:

- If they time in but don't time out → Status shows as clocked in
- Daily payroll calculation runs at midnight → Record is skipped
- Weekly report shows 0 days worked for that date
- **Result**: No pay for that day

### How to Fix:

Administrators can manually edit attendance records to add missing `time_out` times before the daily cron job runs (before midnight). Once both `time_in` and `time_out` are present, the record will be included in the next payroll calculation.

---

## Payroll Tables

### 1. `daily_payroll_reports`
- Stores per-day, per-branch payroll data
- Updated by daily cron job
- Fields: `days_worked`, `total_hours`, `basic_pay`, `ot_hours`, `ot_amount`, `gross_pay`, `total_deductions`, `take_home_pay`

### 2. `weekly_payroll_reports`
- Stores aggregated weekly payroll data
- Updated by weekly cron job
- Includes payment status tracking (`Pending`, `Paid`, etc.)

### 3. `attendance`
- Raw time tracking data
- Fields: `time_in`, `time_out`, `total_hours`, `total_ot_hrs`, `status`, `branch_name`

---

## Manual Payroll View

**Page**: `employee/payroll.php`

Features:
- Filter by month/week/branch
- View type: Weekly or Monthly
- Shows: Days worked, total hours, gross pay, deductions, net pay
- Export capabilities

---

## Security Notes

1. **CLI-only cron jobs** — Cannot be accessed via browser
2. **Admin-only access** — Payroll viewing restricted to Admin/Super Admin
3. **Payment status tracking** — Prevents double-payments

---

## Summary

| Component | Purpose |
|-----------|---------|
| Clock In/Out | Capture actual work hours |
| Daily Cron | Calculate daily pay per branch |
| Weekly Cron | Aggregate and apply deductions |
| `daily_payroll_reports` | Store daily calculations |
| `weekly_payroll_reports` | Store weekly summaries |

**Important**: For payroll to count, employees **must** have both `time_in` AND `time_out` recorded. Missing either means no pay for that day.
