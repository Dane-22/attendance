# Payroll Working Days Calculation Fix Plan

## Problem Summary

**Issue:** Employees are showing 5 working days instead of 4 because the system counts each attendance record as a separate day, even when an employee accidentally times out and then times back in within minutes.

**Example Scenario:**
- Employee clocks in at 06:47 AM
- Accidentally clocks out at 06:50 AM (3 minutes later)
- Clocks back in at 06:51 AM
- Works the full day and clocks out normally

**Result:** System counts this as 2 working days instead of 1.

---

## Root Cause Analysis

**File:** `employee/function/report.php` (lines 460-509)

The current logic in the `// Finalize day/hour totals from per-day/per-branch attendance` section counts 1 day for every attendance record that has both `time_in` and `time_out` values:

```php
// Default: count 1 day if any timed-out attendance exists for the date
$payroll['days_worked'] += 1.0;
```

This does not account for multiple records on the same day that should be merged into a single workday.

---

## Proposed Solutions

### Option 1: Merge Records Within Time Threshold (RECOMMENDED)

**Logic:** When processing attendance records for the same day, merge records where the gap between a clock-out and the next clock-in is less than a defined threshold (e.g., 15 minutes).

**Implementation:**
1. Sort attendance records by time_in for each day
2. Calculate gap between consecutive records (current record time_in - previous record time_out)
3. If gap < threshold (e.g., 15 minutes), treat as same workday
4. Count only 1 day regardless of how many records exist for that date

**Pros:**
- Simple to implement
- Handles accidental quick clock-outs naturally
- Configurable threshold

**Cons:**
- May need tuning of threshold value

### Option 2: Minimum Working Hours Per Day

**Logic:** Only count a day if the total worked hours across all records for that day meets a minimum threshold (e.g., 2 hours).

**Implementation:**
1. Sum all worked hours for the date across all records
2. If total hours >= minimum threshold, count as 1 day
3. Otherwise, count as 0 days (or partial day based on hours)

**Pros:**
- Accidental 3-minute clock-outs won't count as a day
- More accurate for actual work performed

**Cons:**
- May undercount legitimate short work days
- Threshold needs to be defined

### Option 3: Detect and Flag Suspicious Records

**Logic:** Mark records with very short duration (< 5 minutes) as "suspicious" and exclude them from day counting.

**Implementation:**
1. Calculate duration for each record (time_out - time_in)
2. If duration < 5 minutes, skip this record for day counting
3. Log suspicious records for admin review

**Pros:**
- Catches obvious accidental clock-outs
- Provides audit trail

**Cons:**
- Doesn't handle edge cases well
- Requires admin review overhead

---

## Recommended Implementation (Option 1)

### Step 1: Modify Attendance Processing Logic

**File:** `employee/function/report.php`

**Location:** Lines 460-509 (Finalize day/hour totals section)

**Changes:**
1. Add a constant/variable for the merge threshold (e.g., 15 minutes)
2. Sort records by time_in before processing each day
3. Calculate gap between consecutive records
4. Only count 1 day per date regardless of record count

**Pseudocode:**
```php
// Define threshold in minutes
$MERGE_THRESHOLD_MINUTES = 15;

foreach ($payroll['_daily'] as $attendance_date => $branches) {
    // ... existing validation ...
    
    // Collect all records for this date across all branches
    $all_records = [];
    foreach ($branches as $bName => $bData) {
        $all_records[] = [
            'branch' => $bName,
            'time_in' => $bData['time_in'],
            'time_out' => $bData['time_out'],
            'hours' => $bData['hours'],
            'ot_hours' => $bData['ot_hours']
        ];
    }
    
    // Sort by time_in
    usort($all_records, function($a, $b) {
        return strtotime($a['time_in']) - strtotime($b['time_in']);
    });
    
    // Calculate total hours and merge records within threshold
    $total_hours_for_day = 0;
    $merged_records = [];
    $current_merge = null;
    
    foreach ($all_records as $record) {
        if ($current_merge === null) {
            $current_merge = $record;
        } else {
            $gap = strtotime($record['time_in']) - strtotime($current_merge['time_out']);
            if ($gap < ($MERGE_THRESHOLD_MINUTES * 60)) {
                // Merge - extend time_out and accumulate hours
                $current_merge['time_out'] = $record['time_out'];
                $current_merge['hours'] += $record['hours'];
                $current_merge['ot_hours'] += $record['ot_hours'];
            } else {
                // Save current merge and start new one
                $merged_records[] = $current_merge;
                $current_merge = $record;
            }
        }
    }
    if ($current_merge !== null) {
        $merged_records[] = $current_merge;
    }
    
    // Count days based on merged records, not raw records
    // Still count 1 day per date (not per merged record)
    $payroll['days_worked'] += 1.0;
    
    // Accumulate hours from merged records
    foreach ($merged_records as $merged) {
        $payroll['total_hours'] += $merged['hours'];
        $payroll['total_ot_hrs'] += $merged['ot_hours'];
    }
}
```

### Step 2: Add Admin Configuration (Optional)

**File:** Create or modify admin settings page

Add a setting to configure the merge threshold:
- Field: `attendance_merge_threshold_minutes`
- Default: 15 minutes
- Range: 5-60 minutes

### Step 3: Add Reporting/Logging

**File:** `employee/function/report.php`

Add logging to track when records are merged:
```php
error_log("[report.php] Merged {$record_count} attendance records for employee {$emp_id} on {$attendance_date}");
```

---

## Testing Plan

### Test Case 1: Accidental Quick Clock-Out
**Setup:**
- Employee: Test Employee
- Date: Any work day
- Records:
  1. Time in: 06:47:00, Time out: 06:50:00 (3 min)
  2. Time in: 06:51:00, Time out: 17:00:00 (full day)

**Expected Result:** 1 day worked

### Test Case 2: Legitimate Two-Session Day
**Setup:**
- Employee: Test Employee
- Date: Any work day
- Records:
  1. Time in: 08:00:00, Time out: 12:00:00 (4 hours)
  2. Time in: 13:00:00, Time out: 17:00:00 (4 hours)
  
**Expected Result:** 1 day worked (gap is 1 hour, exceeds 15-min threshold, but still 1 day)

### Test Case 3: Multiple Accidental Clock-Outs
**Setup:**
- Records with multiple quick clock-outs throughout the day

**Expected Result:** 1 day worked

### Test Case 4: Branch Transfer (2 Branches)
**Setup:**
- Employee works at 2 different branches on same day

**Expected Result:** 0.5 day per branch (existing logic should handle this)

---

## Implementation Checklist

- [ ] Modify `employee/function/report.php` to implement merging logic
- [ ] Add configurable threshold constant/setting
- [ ] Add logging for merged records
- [ ] Test with the specific employee case mentioned
- [ ] Verify existing branch transfer logic still works
- [ ] Update any related documentation

---

## Files to Modify

1. `employee/function/report.php` - Main fix implementation
2. Optional: Admin settings page for threshold configuration
3. Optional: Database migration if adding new setting field

---

## Notes

- The existing branch transfer logic (2 branches = 0.5 day each) should remain intact
- This fix specifically addresses the "accidental clock-out" scenario
- Consider adding a daily summary report showing merged records for transparency
