# Implementation: Option A - Exclude Short Records from Payroll

Add minimum hours check (30 minutes) to weekly_report.php to exclude extremely short attendance records from payroll calculation, resolving the discrepancy where weekly_report.php shows more days than audit.php.

## Changes Required

### 1. File: employee/function/report.php (Line 508-511)

**Current Code:**
```php
// Filter out records without valid times
$all_records = array_filter($all_records, function($r) {
    return !empty($r['time_in']) && !empty($r['time_out']);
});
```

**Replace With:**
```php
// Filter out records without valid times and short records
$all_records = array_filter($all_records, function($r) {
    // Must have both time_in and time_out
    if (empty($r['time_in']) || empty($r['time_out'])) {
        return false;
    }
    // Must be at least 30 minutes (0.5 hours)
    $start_ts = strtotime($r['time_in']);
    $end_ts = strtotime($r['time_out']);
    if ($start_ts === false || $end_ts === false) {
        return false;
    }
    $hours = ($end_ts - $start_ts) / 3600;
    return $hours >= 0.5;
});
```

### 2. File: employee/cron/daily_payroll_calculation.php

**Find the attendance validation query and add hours check:**

**Current:**
```php
if ($time_in && $time_out) {
    $start_ts = strtotime($time_in);
    $end_ts = strtotime($time_out);
    if ($start_ts !== false && $end_ts !== false && $end_ts > $start_ts) {
        $worked_hours = ($end_ts - $start_ts) / 3600;
        // ... rest of calculation
    }
}
```

**Add minimum hours check before processing:**
```php
if ($time_in && $time_out) {
    $start_ts = strtotime($time_in);
    $end_ts = strtotime($time_out);
    if ($start_ts !== false && $end_ts !== false && $end_ts > $start_ts) {
        $worked_hours = ($end_ts - $start_ts) / 3600;
        
        // Skip if less than 30 minutes
        if ($worked_hours < 0.5) {
            error_log("[Daily Payroll] Skipping short record: {$worked_hours} hours");
            continue;
        }
        
        // ... rest of calculation
    }
}
```

## Result

- **Before**: weekly_report.php counts any record with time_in + time_out (including 3-minute records)
- **After**: weekly_report.php only counts records with ≥ 30 minutes worked

**Worker 1 Example:**
- Apr 6, Record #1: 06:28 → 06:31 AM (3 minutes) = **EXCLUDED**
- Apr 6, Record #2: 06:31 → 04:04 PM (9.5 hours) = **COUNTED**
- Week 2 total: 4 days (not 5)

## Testing

After implementation, verify:
1. weekly_report.php shows 4 days for Worker 1 (not 5)
2. Matches audit.php calendar count (4 days)
3. 3-minute record on Apr 6 is excluded from payroll
4. Normal work days (8+ hours) are still counted correctly

## Go Signal

Ready to implement. Confirm to proceed with the changes.
