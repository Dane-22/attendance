# Final Auto-Rejection Rules

Implement automatic rejection for attendance records that meet severe violation criteria, with clarified logic: incomplete records (NULL time_out) are excluded from long-shift detection, rapid reentry is allowed to accommodate accidental clock-outs, and future dates are rejected as potential tampering.

## Clarified Rules

### Rule 1: Extreme Short Shift (< 30 minutes)
```php
if ($worked_hours < 0.5) {  // Less than 30 minutes
    $action = 'auto_reject';
    $reason = "Extremely short: only {$worked_hours} hours - likely system error";
}
```
**Applies when**: time_in AND time_out both exist, difference < 30 minutes
**Example**: 06:28 AM → 06:31 AM (3 minutes) = **AUTO-REJECT**

---

### Rule 2: Extreme Long Shift (> 16 hours) - CLARIFIED
```php
// This rule ONLY applies when BOTH time_in AND time_out are NOT NULL
// Records with NULL time_out are handled separately (incomplete attendance)

if ($time_in !== null && $time_out !== null && $worked_hours > 16.0) {
    $action = 'auto_reject';
    $reason = "Extremely long: {$worked_hours} hours - exceeds maximum allowed";
}
```

**Important**: If time_out is NULL, this rule does NOT trigger. The system sees incomplete attendance, not 41 hours.

**Example that triggers this rule**:
- Clock in: 7:00 AM Monday
- Clock out: 11:59 PM Tuesday (recorded 2 days later)
- System sees: ~41 hours (BOTH times exist) = **AUTO-REJECT**

**Example that does NOT trigger**:
- Clock in: 7:00 AM Monday
- Clock out: NULL (forgot to clock out)
- System sees: Incomplete = Not counted in payroll (separate handling)

---

### Rule 3: Excessive Same-Day Records (5+)
```php
$same_day_count = getSameDayRecordCount($record);
if ($same_day_count >= 5) {
    $action = 'auto_reject';
    $reason = "{$same_day_count} records in one day - system abuse or malfunction";
}
```
**Example**: 5 separate clock-in/clock-out pairs in one day = **AUTO-REJECT**

---

### ~~Rule 4: Immediate Reentry (< 1 minute)~~ - **REMOVED**

**Rationale**: Allow accidental clock-outs to be corrected immediately.

**Scenario you described**:
- 06:28 AM - Clock in (Record #1)
- 06:31 AM - Accidentally clocked out (Record #1)
- 06:31 AM - Clock in again (Record #2) ← Keep this, it's valid
- 04:04 PM - Clock out properly (Record #2)

**Result**: 
- Record #1 (3 min): **AUTO-REJECT** (Rule 1 - extreme short)
- Record #2 (9.5 hrs): **AUTO-APPROVE** (valid work day)

The existing merge logic in `function/report.php` already handles combining these records if they're close together.

---

### Rule 5: Future Date - KEEP
```php
if (strtotime($attendance_date) > strtotime('+1 day')) {
    $action = 'auto_reject';
    $reason = "Future date detected - possible data tampering";
}
```
**Example**: Today is April 10, but attendance recorded for April 15 = **AUTO-REJECT**

---

## Final Auto-Rejection Rules Summary

| Rule | Condition | Action |
|------|-----------|--------|
| **Extreme Short** | < 30 minutes worked | **AUTO-REJECT** |
| **Extreme Long** | > 16 hours worked (both times exist) | **AUTO-REJECT** |
| **Excessive Records** | 5+ records same day | **AUTO-REJECT** |
| **Future Date** | Date > today + 1 day | **AUTO-REJECT** |
| ~~Immediate Reentry~~ | ~~< 1 minute gap~~ | ~~(REMOVED)~~ |

---

## Flag for Review Rules (Medium Severity)

| Rule | Condition | Action |
|------|-----------|--------|
| Short Shift | 2-4 hours worked | Flag for review |
| Long Shift | 12-16 hours worked | Flag for review |
| Multiple Records | 3-4 records same day | Flag for review |
| Sunday Work | Any Sunday attendance | Flag for review |

---

## Implementation Logic

```php
function evaluateAttendance($record) {
    // Check auto-rejection first
    
    // 1. Extreme Short (< 30 min)
    $worked_hours = calculateHours($record['time_in'], $record['time_out']);
    if ($worked_hours < 0.5) {
        return ['action' => 'auto_reject', 'reason' => 'EXTREME_SHORT'];
    }
    
    // 2. Extreme Long (> 16 hrs) - only if both times exist
    if ($worked_hours > 16.0) {
        return ['action' => 'auto_reject', 'reason' => 'EXTREME_LONG'];
    }
    
    // 3. Excessive Records (5+)
    $count = getSameDayCount($record);
    if ($count >= 5) {
        return ['action' => 'auto_reject', 'reason' => 'EXCESSIVE_RECORDS'];
    }
    
    // 4. Future Date
    if (strtotime($record['attendance_date']) > time() + 86400) {
        return ['action' => 'auto_reject', 'reason' => 'FUTURE_DATE'];
    }
    
    // Check medium severity flags
    $flags = [];
    
    if ($worked_hours < 4.0) {
        $flags[] = ['rule' => 'SHORT_SHIFT', 'severity' => 'medium'];
    }
    if ($worked_hours > 12.0 && $worked_hours <= 16.0) {
        $flags[] = ['rule' => 'LONG_SHIFT', 'severity' => 'medium'];
    }
    if ($count >= 3 && $count < 5) {
        $flags[] = ['rule' => 'MULTIPLE_RECORDS', 'severity' => 'medium'];
    }
    if (date('w', strtotime($record['attendance_date'])) == 0) {
        $flags[] = ['rule' => 'SUNDAY_WORK', 'severity' => 'medium'];
    }
    
    if (!empty($flags)) {
        return ['action' => 'flag_for_review', 'flags' => $flags];
    }
    
    // Clean record
    return ['action' => 'auto_approve'];
}
```

---

## Example: MENEL BENITEZ - Apr 6

| Record | Time | Duration | Evaluation | Action |
|--------|------|----------|------------|--------|
| #1 | 06:28 → 06:31 AM | 3 min | < 30 min | **AUTO-REJECT** |
| #2 | 06:31 → 04:04 PM | 9.5 hrs | No issues | **AUTO-APPROVE** |

**Day Result**: 1 day worked (only Record #2 counts)

---

## Ready to Implement

These 4 auto-rejection rules + 4 flag-for-review rules provide:
- **Automatic filtering** of clearly invalid records
- **Human review** for suspicious but possibly legitimate cases
- **Smooth workflow** for normal attendance

Confirm to proceed with implementation.
