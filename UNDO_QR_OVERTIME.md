# How to Undo QR Scan Overtime Request Feature

This guide explains how to completely remove the QR scan overtime request feature from the attendance system.

## Overview of Changes Made

The following files were modified to implement the QR scan overtime detection:

1. `employee/select_employee.php` - Added overtime modal UI and handlers
2. `employee/api/qr_clock.php` - Added overtime detection logic
3. `employee/api/clock_out.php` - Added overtime detection logic  
4. `overtime_request.php` - Modified to accept QR-generated requests
5. `employee/js/attendance.js` - Added overtime check integration

---

## Step 1: Revert `employee/select_employee.php`

### Remove Overtime Modal HTML (around line 892-922)
Find and remove this HTML block:
```html
<!-- Overtime Detection Modal -->
<div id="overtimeModal" class="overtime-modal-overlay" style="display: none;">
  <div class="overtime-modal-content">
    <h3><i class="fas fa-clock"></i> Overtime Detected</h3>
    <p>You worked beyond 4:00 PM today.</p>
    <div class="overtime-hours-display">
      <div class="hours" id="overtimeHours">0 hrs</div>
      <div class="period" id="overtimePeriod">4:00 PM - 5:00 PM</div>
    </div>
    <p>Was this overtime?</p>
    <div class="overtime-actions" id="overtimeInitialActions">
      <button class="btn-overtime-yes" onclick="showOvertimeReasonForm()">
        <i class="fas fa-check"></i> Yes, It Was Overtime
      </button>
      <button class="btn-overtime-no" onclick="closeOvertimeModal()">
        <i class="fas fa-times"></i> No
      </button>
    </div>
    <div id="overtimeReasonForm" style="display: none;">
      <p>Please provide a reason for the overtime:</p>
      <textarea id="overtimeReason" class="overtime-reason-textarea" 
        placeholder="Enter reason (minimum 10 characters)..." minlength="10"></textarea>
      <div class="overtime-actions">
        <button class="btn-submit-overtime" id="submitOvertimeBtn" onclick="submitOvertimeRequest()">
          <i class="fas fa-paper-plane"></i> Submit Overtime Request
        </button>
        <button class="btn-overtime-no" onclick="closeOvertimeModal()">
          <i class="fas fa-times"></i> Cancel
        </button>
      </div>
    </div>
  </div>
</div>
```

### Remove Overtime CSS Styles (around line 391-571)
Find and remove the CSS block starting with:
```css
/* Overtime Detection Modal */
.overtime-modal-overlay {
```

### Remove Overtime JavaScript (around line 1255-1345)
Find and remove this script block:
```html
<!-- Overtime Detection Handling -->
<script>
// Store overtime data globally
let currentOvertimeData = null;

// Show overtime modal with data
function showOvertimeModal(data) { ... }

// Show reason form after clicking YES
function showOvertimeReasonForm() { ... }

// Close overtime modal
function closeOvertimeModal() { ... }

// Submit overtime request
function submitOvertimeRequest() { ... }

// Check for overtime on clock-out response
function checkOvertimeOnClockOut(response) { ... }
</script>
```

---

## Step 2: Revert `employee/api/qr_clock.php`

### Remove Overtime Detection Logic

Around line 100-130, find the overtime detection block and remove it:

```php
// Overtime detection for workers and drivers
$overtime_start = '16:00:00'; // 4:00 PM
$overtime_trigger = '16:15:00'; // 4:15 PM
$show_overtime = false;
$overtime_hours = 0;
$overtime_period_start = '';
$overtime_period_end = '';

if (in_array($position, ['Worker', 'Driver']) && $time_out >= $overtime_trigger) {
    // Calculate overtime hours from 4:00 PM to actual clock-out
    $overtime_start_dt = new DateTime($overtime_start);
    $time_out_dt = new DateTime($time_out);
    
    if ($time_out_dt > $overtime_start_dt) {
        $diff = $overtime_start_dt->diff($time_out_dt);
        $hours = $diff->h + ($diff->i / 60);
        $overtime_hours = round($hours, 2);
        
        if ($overtime_hours > 0) {
            $show_overtime = true;
            $overtime_period_start = $overtime_start_dt->format('g:i A');
            $overtime_period_end = $time_out_dt->format('g:i A');
        }
    }
}
```

### Simplify the JSON Response

Remove overtime data from the response array:
```php
// Remove these lines from the response:
'show_overtime_prompt' => $show_overtime,
'overtime_hours' => $overtime_hours,
'overtime_start' => $overtime_period_start,
'overtime_end' => $overtime_period_end,
'source' => 'qr_clockout'
```

---

## Step 3: Revert `employee/api/clock_out.php`

### Remove Overtime Detection Logic

Similar to `qr_clock.php`, find and remove the overtime detection block (around line 80-110).

### Simplify the JSON Response

Remove overtime data from the response.

---

## Step 4: Revert `overtime_request.php`

### Remove QR-Specific Modifications

1. **Remove source check** (around line 56-65):
   ```php
   // Remove the 8-hour check for QR requests
   if ($source === 'qr_clockout' && $requested_hours > 8) { ... }
   ```

2. **Remove requested_by modification** (around line 67-75):
   ```php
   // Remove QR-specific requested_by logic
   if ($source === 'qr_clockout') {
       $requested_by = 'QR Clock-out';
   }
   ```

3. **Remove source from INSERT** (around line 109-127):
   Remove the `source` column from the INSERT statement if it was added.

---

## Step 5: Revert `employee/js/attendance.js`

### Remove Overtime Check Integration

Find and remove the `checkOvertimeOnClockOut` call in the `performClockOut` function (around line 1143-1164):

```javascript
// Remove this block:
// Check for overtime after clock-out
if (data.show_overtime_prompt && typeof checkOvertimeOnClockOut === 'function') {
    checkOvertimeOnClockOut(data);
}
```

---

## Step 6: Database Cleanup (Optional)

If you added a `source` column to the `overtime_requests` table:

```sql
ALTER TABLE overtime_requests DROP COLUMN source;
```

---

## Alternative: Git Revert (If Commits Are Clean)

If all changes were committed together or in identifiable commits:

```bash
# View commit history to find the relevant commits
git log --oneline

# Revert specific commits (replace with actual commit hashes)
git revert <commit-hash-1>
git revert <commit-hash-2>

# Or reset to before the feature (destructive - use with caution)
git reset --hard <commit-before-feature>
```

**Note:** The recent commits include:
- `478e67a` - "asdf" (most recent, includes overtime changes)
- `8b06e61` - previous commit

---

## Testing After Revert

1. **Clock Out Test**: Ensure clock-out works without showing overtime modal
2. **QR Scan Test**: Verify QR clock-in/out works normally
3. **Manual Overtime Test**: Confirm manual overtime requests still work (if that feature existed)
4. **Check Logs**: Review `logs.php` to ensure overtime logging is also removed if desired

---

## Files to Delete (If Created)

If any new files were created specifically for this feature, remove them:
- Check for any `overtime_*.php` files that may have been added
- Check for new JavaScript files in `employee/js/`

---

## Summary Checklist

- [ ] Remove overtime modal HTML from `select_employee.php`
- [ ] Remove overtime CSS from `select_employee.php`
- [ ] Remove overtime JavaScript from `select_employee.php`
- [ ] Remove overtime detection from `qr_clock.php`
- [ ] Remove overtime detection from `clock_out.php`
- [ ] Revert `overtime_request.php` to original state
- [ ] Remove overtime check from `attendance.js`
- [ ] Test clock-out functionality
- [ ] Test QR scan functionality
- [ ] (Optional) Drop `source` column from database

---

## Need Help?

If you encounter issues during the revert process:
1. Check browser console for JavaScript errors
2. Check PHP error logs for backend issues
3. Verify database schema matches expectations
4. Test incrementally - revert one file at a time and test
