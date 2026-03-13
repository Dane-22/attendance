# Performance Allowance Bug Report - weekly_report.php

## Issue Summary

**Problem:** When an Admin user edits the Performance Allowance field and presses Enter, the value is not saved to the database. When a Super Admin logs in and views the same report, they see `0` for the Performance Allowance instead of the value entered by the Admin.

**Root Cause:** The Performance Allowance is stored only in the browser's JavaScript memory and is never persisted to the database. When the page reloads or a different user accesses the report, the allowance resets to `0`.

---

## Technical Analysis

### 1. HTML Input Field (weekly_report.php:296-305)

The allowance input is a plain HTML input with a hardcoded value of `0`:

```php
<input type="number" 
       name="allowance_<?php echo $emp_id; ?>" 
       id="allowance_<?php echo $emp_id; ?>"
       value="0" 
       min="0"
       step="0.01"
       class="w-20 bg-gray-800 border border-gray-600 rounded px-2 py-1 text-right text-blue-400 focus:border-yellow-500 focus:outline-none allowance-input"
       data-emp-id="<?php echo $emp_id; ?>"
       onchange="updateCalculations(<?php echo $emp_id; ?>)">
```

**Issue:** The `value="0"` is hardcoded - it does not fetch any existing allowance from the database.

### 2. PHP Data Initialization (weekly_report.php:262)

In the PHP loop, the allowance is initialized as `0`:

```php
$allowance = 0; // Placeholder for performance allowance - will be filled by user input
$gross_plus_allowance = $payroll['gross_pay'] + $allowance;
```

### 3. Database Query (function/report.php:78-109)

The query fetching from `daily_payroll_reports` includes `performance_allowance` column:

```php
$payroll_query = "SELECT 
                    dpr.employee_id,
                    dpr.report_date,
                    ...
                    dpr.performance_allowance,
                    ...
                 FROM daily_payroll_reports dpr
                 ...
                 WHERE dpr.report_date BETWEEN ? AND ?";
```

**However:** The `performance_allowance` value fetched from the database is never used in the display logic.

### 4. JavaScript Calculation (report.js or inline script)

The `updateCalculations()` function only updates the UI totals but does NOT make any API call to save the data:

```javascript
function updateCalculations(empId) {
    const allowance = parseFloat(document.getElementById('allowance_' + empId)?.value || 0);
    const ca = parseFloat(document.getElementById('ca_' + empId)?.value || 0);
    // ... updates local calculations only
}
```

### 5. Payment Status Save (weekly_report.php:606-648)

There IS a working example of how to save data - the Payment Status has a proper save mechanism:

```javascript
function updatePaymentStatus(empId, status) {
    // ... sends AJAX request to update_payment_status.php
    fetch('update_payment_status.php', {
        method: 'POST',
        body: formData
    })
    // ...
}
```

**The Performance Allowance lacks this save mechanism.**

---

## Data Flow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│  Admin User View                                                │
│  ─────────────────                                              │
│  1. Admin enters allowance value in input field               │
│  2. onchange triggers updateCalculations()                     │
│  3. UI totals update (JavaScript only)                         │
│  4. ❌ NO database save occurs                                   │
│  5. Value exists only in browser memory                         │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  Page Reload / Super Admin View                                 │
│  ────────────────────────────────                               │
│  1. PHP fetches data from database                              │
│  2. allowance is initialized to 0                               │
│  3. value="0" is rendered in HTML                               │
│  4. Super Admin sees 0 instead of Admin's value                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Affected Files

| File | Line(s) | Issue |
|------|---------|-------|
| `employee/weekly_report.php` | 262, 296-305 | Hardcoded allowance = 0 |
| `employee/weekly_report.php` | 308 | Display uses hardcoded allowance |
| `employee/function/report.php` | 88 | Fetches performance_allowance but unused |

---

## Required Fixes

### Fix 1: Populate Allowance from Database in PHP

Modify `function/report.php` to include `performance_allowance` in the employee_payroll array:

```php
while ($row = mysqli_fetch_assoc($payroll_result)) {
    $emp_id = $row['employee_id'];
    
    if (isset($employee_payroll[$emp_id])) {
        // ... existing code ...
        
        // Store the performance allowance from database
        $employee_payroll[$emp_id]['performance_allowance'] = floatval($row['performance_allowance'] ?? 0);
    }
}
```

### Fix 2: Pass Allowance to Display in weekly_report.php

Modify the PHP loop to use the fetched allowance value:

```php
<?php foreach ($employee_payroll as $emp_id => $payroll): ?>
<?php
    // ... existing calculations ...
    $allowance = floatval($payroll['performance_allowance'] ?? 0);
?>
```

And update the input field:

```php
<input type="number" 
       name="allowance_<?php echo $emp_id; ?>" 
       id="allowance_<?php echo $emp_id; ?>"
       value="<?php echo number_format($allowance, 2, '.', ''); ?>" 
       min="0"
       step="0.01"
       class="w-20 bg-gray-800 border border-gray-600 rounded px-2 py-1 text-right text-blue-400 focus:border-yellow-500 focus:outline-none allowance-input"
       data-emp-id="<?php echo $emp_id; ?>"
       onchange="updateCalculations(<?php echo $emp_id; ?>); saveAllowance(<?php echo $emp_id; ?>, this.value);">
```

### Fix 3: Create saveAllowance() JavaScript Function

Add to `weekly_report.php` script section:

```javascript
function saveAllowance(empId, allowanceValue) {
    const formData = new FormData();
    formData.append('employee_id', empId);
    formData.append('performance_allowance', allowanceValue);
    formData.append('year', <?php echo $year; ?>);
    formData.append('month', <?php echo $month; ?>);
    formData.append('week', <?php echo $selected_week; ?>);
    formData.append('view_type', '<?php echo $view_type; ?>');
    
    fetch('update_allowance.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Performance allowance saved', 'success');
        } else {
            showToast('Failed to save allowance', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error saving allowance', 'error');
    });
}
```

### Fix 4: Create update_allowance.php API Endpoint

Create a new file `employee/update_allowance.php`:

```php
<?php
require_once __DIR__ . '/../conn/db_connection.php';
require_once __DIR__ . '/../functions.php';
session_start();

header('Content-Type: application/json');

// Check authentication
if (empty($_SESSION['logged_in']) || !in_array($_SESSION['position'], ['Admin', 'Super Admin', 'Developer'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get input data
$employee_id = intval($_POST['employee_id'] ?? 0);
$allowance = floatval($_POST['performance_allowance'] ?? 0);
$year = intval($_POST['year'] ?? 0);
$month = intval($_POST['month'] ?? 0);
$week = intval($_POST['week'] ?? 0);
$view_type = $_POST['view_type'] ?? 'weekly';

if (!$employee_id || !$year || !$month) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Update daily_payroll_reports table
$update_query = "UPDATE daily_payroll_reports 
                 SET performance_allowance = ?, 
                     updated_at = NOW()
                 WHERE employee_id = ? 
                 AND YEAR(report_date) = ? 
                 AND MONTH(report_date) = ?";

$stmt = mysqli_prepare($db, $update_query);
mysqli_stmt_bind_param($stmt, 'diiii', $allowance, $employee_id, $year, $month);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($db)]);
}

mysqli_stmt_close($stmt);
```

---

## Temporary Workaround (Until Fix is Implemented)

Until the proper fix is implemented, users should:

1. **Document the allowance values separately** (e.g., in a spreadsheet or note)
2. **Communicate allowance changes** directly to the Super Admin
3. **Enter the allowance at the time of payroll processing** when all stakeholders are present

---

## Impact Assessment

| Impact Area | Severity | Description |
|-------------|----------|-------------|
| Data Consistency | **High** | Different users see different allowance values |
| Payroll Accuracy | **High** | Take-home pay calculations are incorrect for viewers |
| Audit Trail | **Medium** | No record of who changed allowance values |
| User Trust | **Medium** | Users may lose confidence in the system |

---

## Recommended Priority

**HIGH** - This should be fixed before the next payroll cycle to ensure accurate payroll processing.

---

*Report generated: March 13, 2026*
