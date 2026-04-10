# Auto-Rejection Rules v3 (Final)

Implement automatic rejection only for extreme violations while flagging suspicious patterns for human review, accounting for legitimate scenarios like forgotten clock-outs and branch transfers.

## Revised Rules

### Auto-Reject (2 Rules Only)

| Rule | Condition | Reason |
|------|-----------|--------|
| **Extreme Short** | < 30 minutes worked | Clear system error |
| **Future Date** | Attendance date > today + 1 day | Data tampering |

---

### Changed: Extreme Long (> 16 hours) → **FLAG for Review**

```php
if ($worked_hours > 16.0) {
    $action = 'flag_for_review';  // Was: auto_reject
    $reason = "Long shift: {$worked_hours} hours - verify if forgot clock-out or legitimate overtime";
    $severity = 'high';  // High priority but needs human decision
}
```

**Why flag instead of reject?**
- Could be: Forgot clock-out yesterday, clocked out today (41 hours showing)
- Could be: Legitimate overtime with manager approval
- Admin decides: Approve (with note) or Reject and fix the clock-out time

**Example**: 7:00 AM Monday → 11:59 PM Tuesday = 41 hours
- **Action**: Flag for urgent review
- **Admin sees**: "Employee likely forgot to clock out on Monday"
- **Admin can**: Approve with correction, or reject and have employee fix record

---

### Changed: Excessive Records (5+) → **Per-Branch Count**

```php
// Count records per branch separately
$branch_counts = [];
foreach ($same_day_records as $record) {
    $branch = $record['branch_name'];
    $branch_counts[$branch] = ($branch_counts[$branch] ?? 0) + 1;
}

// Only flag if SINGLE branch has 5+ records
$max_single_branch = max($branch_counts);
if ($max_single_branch >= 5) {
    $action = 'flag_for_review';
    $reason = "{$max_single_branch} records at same branch - possible error";
}

// If multiple branches with 2-3 records each = legitimate transfer
// No flag needed - will be merged by existing logic
```

**Why per-branch?**
- Transferring between branches = legitimate (2-3 records at each branch)
- 5+ records at SAME branch = likely system error or confusion

**Examples**:

| Scenario | Branch A | Branch B | Branch C | Result |
|----------|----------|----------|----------|--------|
| Transferring | 2 records | 2 records | 2 records | ✅ **OK** - No flag |
| System error | 5 records | 0 | 0 | ⚠️ **FLAG** - Same branch |
| Many transfers | 3 records | 3 records | 2 records | ✅ **OK** - Distributed |

---

### New Rule: Excessive Total Records (8+) → **Flag for Review**

```php
$total_records = count($same_day_records);
if ($total_records >= 8) {
    $action = 'flag_for_review';
    $reason = "{$total_records} total records today - verify transfers are legitimate";
}
```

**Catches**: Extreme case of excessive clock-ins across all branches (likely error)

---

## Final Rule Summary

### Auto-Reject (Automatic, No Human Review)
| Rule | Condition | Example |
|------|-----------|---------|
| Extreme Short | < 30 minutes | 3 min record = **REJECT** |
| Future Date | Date in future | Apr 15 recorded on Apr 10 = **REJECT** |

### Flag for High-Priority Review (Human Decision)
| Rule | Condition | Example |
|------|-----------|---------|
| Long Shift | > 16 hours | 41 hours = **FLAG** (admin decides) |
| Excessive Same-Branch | 5+ records at 1 branch | 5x at Branch A = **FLAG** |
| Excessive Total | 8+ records total | 9 records anywhere = **FLAG** |

### Flag for Normal Review
| Rule | Condition | Example |
|------|-----------|---------|
| Short Shift | 2-4 hours | 3 hours = **FLAG** |
| Multiple Records | 3-4 records | 3 records = **FLAG** |
| Sunday Work | Any Sunday | Sunday shift = **FLAG** |

---

## Implementation Logic

```php
function evaluateAttendance($record, $all_day_records) {
    $worked_hours = calculateHours($record['time_in'], $record['time_out']);
    
    // ===== AUTO-REJECT (Severe violations) =====
    
    // 1. Extreme Short (< 30 min)
    if ($worked_hours < 0.5) {
        return [
            'action' => 'auto_reject',
            'reason' => 'EXTREME_SHORT',
            'message' => "Only {$worked_hours}h - clear system error"
        ];
    }
    
    // 2. Future Date
    if (strtotime($record['attendance_date']) > time() + 86400) {
        return [
            'action' => 'auto_reject',
            'reason' => 'FUTURE_DATE',
            'message' => 'Future date - possible tampering'
        ];
    }
    
    // ===== HIGH-PRIORITY FLAGS (Urgent review) =====
    
    $flags = [];
    $priority = 'normal';
    
    // 3. Long Shift (> 16 hrs) - High priority flag
    if ($worked_hours > 16.0) {
        $flags[] = [
            'rule' => 'LONG_SHIFT',
            'severity' => 'high',
            'message' => "{$worked_hours}h - verify clock-out time"
        ];
        $priority = 'urgent';
    }
    
    // 4. Excessive Same-Branch (5+ at same branch)
    $branch_counts = countRecordsPerBranch($all_day_records);
    $max_branch = max($branch_counts);
    if ($max_branch >= 5) {
        $flags[] = [
            'rule' => 'EXCESSIVE_SAME_BRANCH',
            'severity' => 'high',
            'message' => "{$max_branch} records at same branch"
        ];
        $priority = 'urgent';
    }
    
    // 5. Excessive Total (8+ anywhere)
    $total_count = count($all_day_records);
    if ($total_count >= 8) {
        $flags[] = [
            'rule' => 'EXCESSIVE_TOTAL',
            'severity' => 'high',
            'message' => "{$total_count} records total today"
        ];
        $priority = 'urgent';
    }
    
    // ===== NORMAL FLAGS (Standard review) =====
    
    if ($worked_hours < 4.0 && $worked_hours >= 0.5) {
        $flags[] = [
            'rule' => 'SHORT_SHIFT',
            'severity' => 'medium',
            'message' => "Only {$worked_hours}h worked"
        ];
    }
    
    if ($total_count >= 3 && $total_count < 8 && $max_branch < 5) {
        $flags[] = [
            'rule' => 'MULTIPLE_RECORDS',
            'severity' => 'medium',
            'message' => "{$total_count} records today"
        ];
    }
    
    if (date('w', strtotime($record['attendance_date'])) == 0) {
        $flags[] = [
            'rule' => 'SUNDAY_WORK',
            'severity' => 'medium',
            'message' => 'Sunday attendance'
        ];
    }
    
    // Return result
    if (!empty($flags)) {
        return [
            'action' => 'flag_for_review',
            'priority' => $priority,  // 'urgent' or 'normal'
            'flags' => $flags
        ];
    }
    
    return ['action' => 'auto_approve'];
}
```

---

## Example Scenarios

### Scenario 1: Forgot Clock-Out
- Record: 7:00 AM Mon → 11:59 PM Tue (41 hours)
- **Result**: FLAG for URGENT review
- **Admin action**: Contact employee, correct clock-out to Monday 4 PM, approve corrected record

### Scenario 2: Branch Transfer (Legitimate)
- Branch A: 7:00 AM → 12:00 PM (5 hours)
- Branch B: 12:30 PM → 5:00 PM (4.5 hours)
- **Result**: ✅ AUTO-APPROVE (2 records total, distributed)

### Scenario 3: System Error
- Branch A: 7:00 AM → 7:05 AM (5 min) ← AUTO-REJECT (extreme short)
- Branch A: 7:05 AM → 7:06 AM (1 min) ← AUTO-REJECT (extreme short)
- Branch A: 7:06 AM → 4:00 PM (9 hours) ← AUTO-APPROVE (valid)
- **Day total**: 1 day worked (only last record counts)

### Scenario 4: Excessive at Same Branch
- Branch A: 7:00 AM → 8:00 AM
- Branch A: 8:05 AM → 9:00 AM
- Branch A: 9:05 AM → 10:00 AM
- Branch A: 10:05 AM → 11:00 AM
- Branch A: 11:05 AM → 12:00 PM (5 records at same branch)
- **Result**: FLAG for review (possible confusion or system issue)

---

## Admin Review Queue

### Urgent Queue (High Priority)
- Long shifts (> 16 hrs)
- Excessive same-branch (5+)
- Excessive total (8+)

### Normal Queue (Standard Priority)
- Short shifts (2-4 hrs)
- Multiple records (3-7 total)
- Sunday work

### Auto-Approved (No Review)
- Normal attendance
- Clean transfers between branches

---

## Configuration

```php
return [
    'auto_reject' => [
        'extreme_short' => ['enabled' => true, 'threshold' => 0.5],  // < 30 min
        'future_date' => ['enabled' => true],
    ],
    'flag_urgent' => [
        'long_shift' => ['enabled' => true, 'threshold' => 16.0],    // > 16 hrs
        'excessive_same_branch' => ['enabled' => true, 'max' => 5],  // 5+ at 1 branch
        'excessive_total' => ['enabled' => true, 'max' => 8],        // 8+ total
    ],
    'flag_normal' => [
        'short_shift' => ['enabled' => true, 'threshold' => 4.0],   // < 4 hrs
        'multiple_records' => ['enabled' => true, 'max' => 3],       // 3+ records
        'sunday_work' => ['enabled' => true],
    ]
];
```

---

## Summary

**Auto-Reject**: Only clear errors (< 30 min, future dates)
**Urgent Flag**: Need quick admin attention (> 16 hrs, 5+ same branch, 8+ total)
**Normal Flag**: Review when convenient (short shifts, Sunday, 3-4 records)
**Auto-Approve**: Everything else including legitimate branch transfers

This balances automation with human judgment for edge cases.
