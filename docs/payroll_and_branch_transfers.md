# Payroll and Branch Transfer Documentation

## Overview

This document explains how the payroll system handles time tracking, branch transfers, and the specific scenario where a worker accidentally clocks in at the wrong branch.

### Key Behavior: Auto-Transfer on Clock-In

**Workers can clock in at ANY branch, and the system automatically handles the transfer.**

**Example:**
- Yesterday: Worker was assigned to **Branch A**, worked there
- Today: Worker goes to **Branch B** and scans QR code
- **Result:** 
  - ✅ Worker is automatically clocked in at **Branch B**
  - ✅ Worker's assignment automatically updates to **Branch B**
  - ✅ Tomorrow they can go to **Branch C** and it will switch again

**No manual transfer needed** - Just scan and work. The system follows where the worker actually is.

## How Payroll Calculation Works

### Daily Calculation Process
The payroll system runs daily at midnight via `employee/cron/daily_payroll_calculation.php` to calculate pay for the previous day.

### Key Payroll Components

1. **Days Worked**: Count of days with attendance records (excluding 'Absent' status)
2. **Hours Worked**: Calculated from `time_in` and `time_out` timestamps
3. **Overtime Hours**: Tracked in `total_ot_hrs` field
4. **Daily Rate**: Employee's configured daily rate from `employees.daily_rate`
5. **Branch Assignment**: Payroll is calculated per-branch for multi-branch workdays

### Payroll Formula
```
Gross Pay = Daily Rate × Days Worked
OT Rate = Daily Rate ÷ 8
OT Amount = OT Hours × OT Rate
Gross + Allowance = Gross Pay + OT Amount
Net Pay = Gross + Allowance - Total Deductions
```

## Branch Transfer Mechanics

### What Happens During a Branch Transfer

When a worker transfers from Branch A to Branch B (via `transfer_branch_api.php`):

1. **Clock Out from Current Branch**: 
   - System records `time_out = NOW()` for the open attendance record at Branch A
   - Marks `is_time_running = 0`

2. **Clock In to New Branch**:
   - Creates a NEW attendance record for Branch B
   - Records `time_in = NOW()`
   - Marks `is_time_running = 1`

3. **Employee Assignment Update**:
   - Updates `employees.branch_id` to the new branch

### Database Records After Transfer

**Before Transfer (Working at Branch A for 2 hours):**
```
attendance table:
- Record 1: branch_name='Branch A', time_in='08:00:00', time_out=NULL, ...
```

**After Transfer to Branch B:**
```
attendance table:
- Record 1: branch_name='Branch A', time_in='08:00:00', time_out='10:00:00', ...
- Record 2: branch_name='Branch B', time_in='10:00:00', time_out=NULL, ...
```

## Scenario: Accidental Clock-in at Wrong Branch

### The Problem

Worker accidentally clocks in at **Branch A** (wrong branch) at 8:00 AM. After 2 hours (10:00 AM), they realize the mistake and transfer to their designated **Branch B**.

### What Happens to Payroll

#### Option 1: Worker Transfers Mid-Day (Recommended)

**System Behavior:**
- Branch A gets credit for 2 hours worked (8:00 AM - 10:00 AM)
- Branch B gets credit for remaining hours (10:00 AM - end of shift)
- **Payroll Calculation:**
  - If total hours across both branches ≥ standard work hours: Counts as 1 full day
  - Each branch gets proportional hours credited

**Payroll Impact:**
```php
// From daily_payroll_calculation.php (lines 155-184)
if (count($branches) === 2) {
    // Split day for 2 branches (transfer scenario)
    foreach ($branches as $bName => $bData) {
        $payroll['_branches'][$bName]['days'] += 0.5;  // Each gets 0.5 day
        $payroll['_branches'][$bName]['hours'] += floatval($bData['hours']);
    }
    $payroll['days_worked'] += 1.0;  // Still counts as 1 full day total
}
```

**Result:**
- ✅ Worker gets paid for full day (1.0 days worked)
- ✅ Branch A gets 0.5 days credit (2 hours of work)
- ✅ Branch B gets 0.5 days credit (remaining hours)
- ✅ Labor costs are properly distributed between branches

#### Option 2: Admin Corrects the Record

If the transfer didn't happen automatically or was done incorrectly, an admin can:

1. **Manually update the attendance record** in the database
2. **Delete the wrong branch record** and create a new one for the correct branch
3. **Use the attendance editing interface** (if available) to change branch_name

**Payroll Recalculation:**
- Run `employee/cron/daily_payroll_calculation.php` manually for the specific date
- Or wait for the next scheduled run (midnight daily)

## Key Files Involved

### Branch Transfer Logic
- **`transfer_branch_api.php`**: Handles the transfer API calls
  - Lines 134-145: Times out from current branch
  - Lines 152-179: Creates new attendance record for target branch

### Payroll Calculation
- **`employee/cron/daily_payroll_calculation.php`**: Daily payroll processing
  - Lines 128-135: Calculates worked hours from time_in/time_out
  - Lines 155-184: Handles split-day (2 branches) scenario
  
- **`employee/cron/weekly_payroll_calculation.php`**: Weekly payroll aggregation
  - Lines 178-207: Similar logic for weekly calculations

### Clock Functions
- **`employee/function/clock_functions.php`**: Core clock-in/out logic
  - `performClockIn()`: Creates attendance records
  - `performClockOut()`: Closes attendance records

## Important Notes

### Partial Day Credit System
When a worker spends time at multiple branches in one day:
- Each branch gets **0.5 days** credit (if 2 branches)
- Worker still gets **1.0 full day** pay
- Hours are tracked separately per branch for accurate labor cost reporting

### Example Payroll Distribution

**Scenario:** Worker at Branch A (2 hrs) + Branch B (6 hrs) = 8 hour day

| Branch | Hours | Days Credit | Payroll Impact |
|--------|-------|-------------|----------------|
| Branch A | 2 | 0.5 | Pays 50% of daily rate to Branch A labor costs |
| Branch B | 6 | 0.5 | Pays 50% of daily rate to Branch B labor costs |
| **Total** | **8** | **1.0** | Worker receives 100% daily rate |

### Overtime Handling
Overtime is calculated separately per branch:
```php
$ot_rate = $daily_rate / 8;
$ot_amount = $payroll['total_ot_hrs'] * $ot_rate;
```

## Best Practices

1. **Transfer immediately** when a wrong branch clock-in is discovered
2. **Don't delete records** - The system handles split-days automatically
3. **Verify transfers** by checking attendance records in the database
4. **Monitor payroll reports** to ensure proper branch credit distribution

## Troubleshooting

### Issue: Worker not getting full day pay after transfer
**Cause:** One of the attendance records might have incorrect time_in/time_out
**Solution:** Verify both records have valid timestamps and check `status` field is not 'Absent'

### Issue: Hours not splitting correctly between branches
**Cause:** Missing `time_out` on first branch record
**Solution:** Ensure the transfer API successfully executed the clock-out before clock-in

### Issue: Payroll calculation showing wrong branch
**Cause:** `employees.branch_id` might not match the attendance `branch_name`
**Solution:** The transfer API updates `employees.branch_id`, but payroll uses attendance records for calculations

## Database Schema Reference

### Key Tables

**`attendance`** - Time tracking records
- `employee_id`: Employee identifier
- `branch_name`: Branch where work occurred
- `attendance_date`: Date of attendance
- `time_in`: Clock-in timestamp
- `time_out`: Clock-out timestamp
- `total_ot_hrs`: Overtime hours
- `status`: 'Present', 'Late', 'Absent', etc.

**`employees`** - Employee master data
- `id`: Employee identifier
- `branch_id`: Current assigned branch
- `daily_rate`: Standard daily pay rate
- `status`: 'Active' or 'Inactive'

**`branches`** - Branch master data
- `id`: Branch identifier
- `branch_name`: Branch name
- `is_active`: Branch status

## Summary

When a worker accidentally clocks in at the wrong branch:

1. ✅ **Their pay is protected** - They still get full daily rate
2. ✅ **Labor costs are distributed** - Each branch pays for the hours worked there
3. ✅ **Records remain accurate** - Both branch records are preserved
4. ✅ **System handles automatically** - No manual payroll adjustment needed

The system is designed to handle multi-branch workdays seamlessly, ensuring fair pay for workers and accurate cost tracking for branches.

## Recommended Actions

Based on the system architecture, here are the recommended implementations:

### 1. **Immediate Transfer When Mistake is Discovered**

**Action:** Train workers/supervisors to use the branch transfer feature immediately upon discovering the wrong branch clock-in.

**Implementation:**
- Use the QR scan or manual transfer interface in `select_employee.php`
- The system automatically:
  - Times out from wrong branch (records actual hours worked)
  - Times in to correct branch
  - Updates employee assignment

**Why:** Minimizes time tracking errors and ensures accurate payroll distribution

### 2. **Add Transfer Confirmation Notification**

**Action:** Implement a notification/confirmation when a branch transfer occurs.

**Implementation Location:** `transfer_branch_api.php` after successful transfer

**Suggested Addition:**
```php
// After successful transfer (around line 161)
// Send notification to admin/supervisor
$notificationMessage = "Employee {$employeeName} transferred from {$actualFromBranch} to {$resolvedToBranchName}. ";
$notificationMessage .= "Payroll will be split: 0.5 days to each branch for this workday.";
// Insert into notifications table or send email
```

**Why:** Increases transparency and allows supervisors to verify correct transfers

### 3. **Add Branch Mismatch Warning**

**Action:** Display a warning when worker clocks in at a branch different from their assigned branch.

**Implementation Location:** `employee/function/clock_functions.php` in `performClockIn()`

**Suggested Addition:**
```php
// After line 111 in clock_functions.php, add warning if branch mismatch
if ($currentAssignedBranchId !== $targetBranchId) {
    $result['warning'] = "You are clocking in at {$targetBranchName}, but your assigned branch is {$currentAssignedBranchName}. ";
    $result['warning'] .= "This will be treated as a branch transfer.";
}
```

**Why:** Prevents accidental clock-ins at wrong branches

### 4. **Implement Transfer Audit Log**

**Action:** Enhance the existing logging in `transfer_branch_api.php` to include more details for payroll review.

**Current Implementation:** Line 161 logs basic transfer info

**Recommended Enhancement:**
```php
// Enhanced logging for payroll audit
logApiActivity($db, $employeeId, 'Branch Transferred', 
    "Employee {$employeeId} transferred from {$actualFromBranch} to {$resolvedToBranchName}. " .
    "Time out: {$timeOutFromA}, Time in: {$timeInToB}. " .
    "Payroll split: Branch A gets {$hoursAtA}hrs, Branch B gets {$hoursAtB}hrs.");
```

**Why:** Provides clear audit trail for payroll disputes

### 5. **Add Admin Override for Wrong-Branch Records**

**Action:** Create an admin interface to correct branch assignments without creating transfer records.

**Use Case:** When a worker clocks in at wrong branch but never actually worked there (e.g., clicked wrong button)

**Implementation:**
- New admin page: `employee/admin/attendance_correction.php`
- Allow admin to:
  - Delete incorrect attendance record
  - Create new record at correct branch with original time_in
  - Add note explaining the correction

**Why:** Handles edge cases where transfer logic doesn't apply

### 6. **Daily Payroll Report Enhancement**

**Action:** Add visual indicator in payroll reports for split-day employees.

**Implementation Location:** `employee/reports/payroll_view.php` or similar

**Suggested Display:**
```
Employee: John Doe | Total Days: 1.0
├── Branch A: 0.5 days (2 hrs)
└── Branch B: 0.5 days (6 hrs)
```

**Why:** Makes payroll verification easier for HR

### 7. **Worker Education**

**Action:** Add informational tooltip/popup in the clock-in interface explaining the transfer process.

**Implementation:** 
- Add to `select_employee.php` UI
- Brief message: "Clocked in at the wrong branch? Don't worry - transfer to your correct branch and your pay will be protected. Both branches will share the cost."

**Why:** Reduces worker anxiety about accidental wrong-branch clock-ins

---

**Document Version:** 1.0  
**Last Updated:** April 6, 2026  
**Related Files:** 
- `transfer_branch_api.php`
- `employee/cron/daily_payroll_calculation.php`
- `employee/function/clock_functions.php`
