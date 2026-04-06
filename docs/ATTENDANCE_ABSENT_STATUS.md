# Attendance Absent Status Documentation

## Overview

This document explains how the system determines if a worker is **Absent** and when the status is explicitly set or implied.

## How Absent Status Works

### 1. Implied Absent (Most Common)

**Definition:** Worker has no attendance record for the day.

**How it works:**
- The system does NOT automatically create "Absent" records
- Absence is determined by the **lack of a time-in record**
- When querying attendance, if no record exists for a worker on a given date, they are considered Absent

**Example:**
```sql
-- Worker with NO record for today = Absent
SELECT * FROM attendance 
WHERE employee_id = 123 
  AND attendance_date = CURDATE();
-- Returns: 0 rows = Absent
```

### 2. Explicit Absent Status

**Definition:** Attendance record exists but has `status = 'Absent'`.

**When this happens:**
- Manual entry by admin marking worker as Absent
- Import/CSV upload sets status to Absent
- Legacy records from old system
- Some automated processes may set it

**Example:**
```sql
-- Worker with explicit Absent status
SELECT 
    employee_id, 
    attendance_date, 
    status, 
    time_in
FROM attendance 
WHERE employee_id = 123 
  AND attendance_date = CURDATE();
-- Returns: status = 'Absent', time_in = NULL
```

### 3. Null Time-In Treatment

**Rule:** If `time_in` is NULL, the worker is treated as Absent regardless of status column.

**Code Logic (from consecutive_attendance_check.php):**
```php
$status = $record['status'] ?? 'Absent';

// If no time_in and status is Present/Late, treat as Absent
if (empty($record['time_in']) && in_array($status, ['Present', 'Late'])) {
    $status = 'Absent';
}
```

## Status vs. Time-In Relationship

| time_in Value | status Column | Effective Status | Meaning |
|--------------|---------------|------------------|---------|
| `07:00:00` | `Present` | Present | On time |
| `08:30:00` | `Late` | Late | Late arrival |
| `NULL` | `Absent` | Absent | Did not come |
| `NULL` | `Present` | Absent | No time-in = Absent |
| `NULL` | (NULL) | Absent | No record at all |

## Key Insight: Status is Set Only on Clock-In

**Important:** The `status` column is ONLY set when a worker actually clocks in:

```php
// From clock_functions.php - performClockIn()
if ($currentTime < $cutoffTime) {
    $status = 'Present';  // Before 7:15 AM
} else {
    $status = 'Late';     // After 7:15 AM
}

// Insert with status
INSERT INTO attendance (..., time_in, status, ...) 
VALUES (..., NOW(), 'Present', ...);
```

**If worker never clocks in:**
- No INSERT happens
- No status is set
- Record simply doesn't exist
- System treats as Absent by implication

## Detecting Absences in Queries

### Query 1: Find Explicit Absent Records
```sql
SELECT 
    a.id,
    e.first_name,
    e.last_name,
    e.employee_code,
    a.attendance_date,
    a.status,
    a.time_in
FROM attendance a
JOIN employees e ON e.id = a.employee_id
WHERE a.status = 'Absent'
  AND a.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
ORDER BY a.attendance_date DESC;
```

### Query 2: Find Implied Absences (No Record)
```sql
-- Workers who have no attendance record for today
SELECT 
    e.id,
    e.first_name,
    e.last_name,
    e.employee_code,
    e.branch_name
FROM employees e
LEFT JOIN attendance a ON a.employee_id = e.id 
    AND a.attendance_date = CURDATE()
WHERE e.status = 'Active'
  AND e.position = 'Worker'
  AND a.id IS NULL;  -- No attendance record = Absent
```

### Query 3: Find All Absences (Explicit + Implied)
```sql
-- All workers who are absent today (both explicit and implied)
SELECT 
    e.id,
    e.first_name,
    e.last_name,
    e.employee_code,
    e.branch_name,
    CASE 
        WHEN a.id IS NULL THEN 'Implied Absent (No Record)'
        WHEN a.status = 'Absent' THEN 'Explicit Absent'
        WHEN a.time_in IS NULL THEN 'No Time-In (Absent)'
        ELSE a.status
    END as absence_type
FROM employees e
LEFT JOIN attendance a ON a.employee_id = e.id 
    AND a.attendance_date = CURDATE()
WHERE e.status = 'Active'
  AND e.position = 'Worker'
  AND (a.id IS NULL OR a.status = 'Absent' OR a.time_in IS NULL);
```

## Consecutive Absences Detection

The consecutive attendance check script handles both explicit and implied absences:

```php
// From: consecutive_attendance_check.php
$status = $record['status'] ?? 'Absent';

// If no time_in and status is Present/Late, treat as Absent
if (empty($record['time_in']) && in_array($status, ['Present', 'Late'])) {
    $status = 'Absent';
}

// Check if ALL 3 records have issues (Late or Absent)
if (in_array($status, ['Late', 'Absent'])) {
    // Count as consecutive issue day
}
```

This means:
- **Day 1:** No record → Treated as Absent
- **Day 2:** status='Absent', time_in=NULL → Absent
- **Day 3:** No record → Treated as Absent
→ **3 Consecutive Absences = Alert Sent**

## Why Not Auto-Create Absent Records?

**Reasons:**
1. **Performance:** Would require daily batch job to create records for all workers
2. **Storage:** Millions of "Absent" records over time
3. **Flexibility:** Easier to query "who didn't clock in" than "who has Absent status"
4. **Legacy Support:** Some workers may have different schedules

**Alternative Approach:**
Most reports use LEFT JOIN queries where `NULL` = Absent, which is more efficient than storing explicit Absent rows.

## Admin Actions for Absences

### Manually Mark as Absent
```sql
-- Admin manually creates absent record
INSERT INTO attendance (
    employee_id, 
    attendance_date, 
    status, 
    branch_name,
    created_at
) VALUES (
    123, 
    CURDATE(), 
    'Absent', 
    'Main Office',
    NOW()
);
```

### Bulk Mark Absences (End of Day)
Some systems run a cron job at end of day to create explicit Absent records:

```php
// Pseudocode for end-of-day absence marking
$workersWithoutTimeIn = getActiveWorkersWithNoAttendanceToday();
foreach ($workersWithoutTimeIn as $worker) {
    insertAttendanceRecord(
        employee_id: $worker->id,
        status: 'Absent',
        time_in: null
    );
}
```

**Note:** This project does NOT currently auto-create Absent records. Absence is implied by lack of record.

## Summary

| Question | Answer |
|----------|--------|
| Is status ever set to 'Absent' automatically? | **No** - Only on manual entry or imports |
| How is absence determined? | **By lack of attendance record** or NULL time_in |
| Do we create 'Absent' records daily? | **No** - Implied absence via missing record |
| Can admins mark workers Absent? | **Yes** - Manual INSERT with status='Absent' |
| Does consecutive check handle absences? | **Yes** - Treats missing records as Absent |

## Related Documentation

- [Attendance Late Status](./ATTENDANCE_LATE_STATUS.md) - How Late status is determined
- [Consecutive Attendance Notifications](./CONSECUTIVE_ATTENDANCE_NOTIFICATIONS.md) - How 3+ consecutive issues trigger alerts
