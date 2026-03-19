# Time Tracking Inconsistency Investigation Report

## Issue Summary

**Problem:** Engineer times in via mobile app manually (NOT using QR), but `audit.php` shows the Engineer as "Completed" (both time_in AND time_out recorded), while the mobile app still shows the timer running (not timed out).

**Impact:** Data inconsistency between mobile app display and database records, leading to incorrect attendance status.

---

## Root Cause Analysis

### Clarification from User
The Engineer **manually clicks the Time In button** in the mobile app interface - she does NOT use the QR code scanning feature. This is a critical distinction that changes where the bug must be located.

### Mobile App Code Flow (Manual Time In)

**File:** `c:\wamp64\www\main\employee\eng_dashboard.php`  
**Lines:** 966-980 (Button rendering), 1259-1388 (JavaScript handlers)

The mobile app uses **separate buttons** for Time In and Time Out:

```php
// Button states determined at page load (lines 966-980)
<button type="button" class="btn-time btn-time-in" id="btnTimeIn" 
        data-employee-id="<?php echo $employeeId; ?>"
        <?php echo $hasOpenShift ? 'disabled' : ''; ?>>  // Disabled if already clocked in
    <i class="fas fa-play"></i> Time In
</button>

<button type="button" class="btn-time btn-time-out" id="btnTimeOut"
        data-employee-id="<?php echo $employeeId; ?>"
        data-shift-id="<?php echo $shiftId ?? ''; ?>"
        <?php echo !$hasOpenShift ? 'disabled' : ''; ?>>  // Disabled if NOT clocked in
    <i class="fas fa-stop"></i> Time Out
</button>
```

**JavaScript handlers (lines 1259-1388):**
- Time In button calls `api/clock_in.php`
- Time Out button calls `api/clock_out.php`

### Potential Root Causes

Since the QR auto clock-out is NOT the cause (user confirmed manual click), here are the **actual possible causes**:

#### 1. Stale Page Data / Button State Confusion (Most Likely)
**File:** `c:\wamp64\www\main\employee\eng_dashboard.php`  
**Lines:** 102-113

The `$hasOpenShift` variable is determined **at page load time**:

```php
$attQuery = "SELECT id, time_in, time_out FROM attendance 
             WHERE employee_id = ? AND attendance_date = CURDATE() 
             ORDER BY id DESC LIMIT 1";
// ...
$hasOpenShift = !empty($row['time_in']) && empty($row['time_out']);
```

**The Bug Scenario:**
1. Engineer opens mobile app (page loads) - at this moment, she is NOT clocked in
2. `$hasOpenShift = false`, so **Time In button is ENABLED**, Time Out is disabled
3. Something else clocks her in (admin action, another device, etc.)
4. Engineer clicks what she thinks is "Time In" button
5. **BUT** - the page was loaded with old state, and she may have actually clicked the **Time Out** button (which became enabled after the external clock-in)
6. Result: Database shows both time_in and time_out (Completed), but mobile app display hasn't refreshed

#### 2. Double-Click / Race Condition
The engineer may have accidentally double-clicked or the form submitted twice, causing both clock in and clock out to execute rapidly.

#### 3. Admin/Other Device Clock-Out
An admin or another device may have clocked the engineer out after they clocked in, but the mobile app hasn't refreshed to show the updated status.

### Code Analysis - Why Mobile App Shows Running Timer

In `eng_dashboard.php` lines 1259-1388, the Time In handler:
```javascript
fetch('api/clock_in.php', {
    method: 'POST',
    body: formData
})
.then(r => r.json())
.then(data => {
    if (data.success) {
        alert('Time In recorded successfully');
        location.reload();  // <-- Page reloads on success
    }
})
```

The page reloads after successful clock-in. If the engineer sees the timer running after this, it means:
1. The clock-in was successful
2. But something immediately set time_out ( Completed status)
3. The timer display is based on `$hasOpenShift` at the new page load

---

## Reproduction Steps

### Scenario A: Stale Button State
1. Engineer opens mobile app when NOT clocked in
2. Page loads with `$hasOpenShift = false`, Time In button ENABLED
3. Admin clocks engineer in via web interface (or other method)
4. Time Out button becomes ENABLED in UI (via AJAX or refresh)
5. Engineer clicks what she thinks is Time In, but actually clicks Time Out
6. Database shows Completed, mobile app shows "Not Clocked In" or wrong state

### Scenario B: Accidental Time Out Click
1. Engineer is already clocked in but mobile app page is stale/not refreshed
2. Engineer clicks Time Out button thinking it's Time In
3. Clock out is recorded immediately
4. Mobile app shows timer still running because display hasn't updated

---

## Recommended Fixes

### Fix 1: Real-Time Button State Validation (Recommended)
**File:** `c:\wamp64\www\main\employee\eng_dashboard.php`

Add a check before executing clock-in/out to verify the current state matches the intended action:

```javascript
// Before executing Time In
fetch('api/check_status.php?employee_id=' + employeeId)
    .then(r => r.json())
    .then(status => {
        if (status.hasOpenShift && action === 'time_in') {
            alert('You are already clocked in. Please refresh the page.');
            return;
        }
        // Proceed with clock in
    });
```

### Fix 2: AJAX State Refresh Before Action
**File:** `c:\wamp64\www\main\employee\eng_dashboard.php`  
**Lines:** 1259-1388

Refresh the shift status via AJAX before allowing any clock action:

```javascript
document.getElementById('btnTimeIn').addEventListener('click', function() {
    // First check current status from server
    fetch('api/get_current_status.php?employee_id=' + employeeId)
        .then(r => r.json())
        .then(status => {
            if (status.hasOpenShift) {
                alert('You are already clocked in!');
                location.reload();
                return;
            }
            // Show branch modal only if truly not clocked in
            showBranchModal();
        });
});
```

### Fix 3: Auto-Refresh Page Periodically
Add automatic page refresh every few minutes to ensure button states stay current:

```javascript
// Auto-refresh every 2 minutes to ensure state accuracy
setInterval(() => {
    location.reload();
}, 120000);
```

### Fix 4: Disable Double-Submit
Add protection against double-clicks:

```javascript
let isProcessing = false;

document.getElementById('btnTimeIn').addEventListener('click', function() {
    if (isProcessing) return;
    isProcessing = true;
    btn.disabled = true;
    // ... rest of handler
});
```

---

## Database Query to Verify Issue

```sql
-- Check the Engineer's attendance record for today
SELECT 
    id,
    employee_id,
    attendance_date,
    time_in,
    time_out,
    TIMESTAMPDIFF(MINUTE, time_in, COALESCE(time_out, NOW())) as minutes_worked,
    CASE 
        WHEN time_in IS NOT NULL AND time_out IS NOT NULL THEN 'Completed'
        WHEN time_in IS NOT NULL THEN 'Present (Running)'
        ELSE 'Not Clocked In'
    END as actual_status
FROM attendance 
WHERE employee_id = [ENGINEER_ID] 
AND attendance_date = CURDATE()
ORDER BY id DESC;
```

---

## Conclusion

Since the user confirmed the Engineer uses **manual Time In button** (not QR), the bug is most likely caused by:

1. **Stale page data** - The button states are determined at page load and may not reflect current database state
2. **Button confusion** - Engineer may have clicked Time Out thinking it was Time In due to outdated UI state

**Immediate Action Required:**
- Add server-side status validation before executing clock in/out actions
- Implement real-time state checking to prevent actions on stale data
- Add visual indicators showing last refresh time

**Previous QR-related findings are NOT applicable** to this specific case since the user confirmed manual button click is used.
