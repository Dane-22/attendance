# Performance Allowance System Documentation

## Overview

The Performance Allowance system allows administrators to add discretionary bonuses to employee payroll. This is a weekly/monthly configurable amount that gets added to the employee's gross pay.

---

## How It Works

### 1. Data Flow

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│  Admin Input    │────▶│  AJAX Save       │────▶│  Database       │
│  (UI Input)     │     │  update_allowance.php│  │  weekly_payroll_reports │
└─────────────────┘     └──────────────────┘     └─────────────────┘
         │                                              │
         │                                              │
         ▼                                              ▼
┌─────────────────┐                            ┌─────────────────┐
│  Report Display │◀─────────────────────────────│  Report Load    │
│  (weekly_report)│                            │  (report.php)   │
└─────────────────┘                            └─────────────────┘
```

### 2. User Interface

**Location:** `employee/weekly_report.php:296-306`

The allowance is displayed as an editable input field in the payroll table:

```php
<input type="number" 
       name="allowance_<?php echo $emp_id; ?>" 
       id="allowance_<?php echo $emp_id; ?>"
       value="<?php echo number_format($allowance, 2, '.', ''); ?>" 
       min="0"
       step="0.01"
       class="w-20 bg-gray-800 border border-gray-600 rounded px-2 py-1 text-right text-blue-400 allowance-input"
       data-emp-id="<?php echo $emp_id; ?>"
       onchange="updateCalculations(<?php echo $emp_id; ?>); saveAllowance(<?php echo $emp_id; ?>, this.value);">
```

**Key Features:**
- Input field accepts decimal values (step="0.01")
- Styled with blue text (`text-blue-400`)
- Auto-saves on change (`onchange` handler)
- Minimum value: 0

---

## JavaScript Functions

### `saveAllowance(empId, allowanceValue)`
**Location:** `employee/weekly_report.php:679-713`

Sends AJAX request to save the allowance to database:

```javascript
function saveAllowance(empId, allowanceValue) {
    // Get employee name for toast notification
    const row = input.closest('tr');
    const empName = row.querySelector('td:first-child .font-medium').textContent.trim();
    
    // Send AJAX to update_allowance.php
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
            showToast(`Performance allowance saved for ${empName}`, 'success');
        }
    });
}
```

### `updateCalculations(empId)`
**Location:** `employee/js/report.js:126-173`

Updates the gross pay, total deductions, and take-home pay calculations when allowance changes:

```javascript
function updateCalculations(empId) {
    const caInput = document.getElementById('ca_' + empId);
    const caValue = parseFloat(caInput.value) || 0;
    
    // Get values from cells
    const grossPay = parseFloat(cells[4].textContent.replace(/,/g, '')) || 0;
    const otAmount = parseFloat(cells[6].textContent.replace(/,/g, '')) || 0;
    const allowance = parseFloat(document.getElementById('allowance_' + empId).value) || 0;
    
    // Calculate totals
    const grossPlusAllowance = grossPay + allowance + otAmount;
    const totalDeductions = sss + phic + hdmf + caValue + sssLoan;
    const takeHome = grossPlusAllowance - totalDeductions;
    
    // Update cells
    cells[15].textContent = numberFormat(totalDeductions);
    cells[16].textContent = numberFormat(takeHome);
    
    updateGrandTotals();
}
```

---

## Database Storage

### Primary Table: `weekly_payroll_reports`

The allowance is stored in the `weekly_payroll_reports` table with these key fields:

| Field | Type | Description |
|-------|------|-------------|
| `employee_id` | INT | Employee ID reference |
| `report_year` | INT | Year (e.g., 2026) |
| `report_month` | INT | Month (1-12) |
| `week_number` | INT | Week number (1-5, or 0 for monthly view) |
| `view_type` | VARCHAR | 'weekly' or 'monthly' |
| `performance_allowance` | DECIMAL(10,2) | The allowance amount |
| `payment_status` | VARCHAR | 'Paid' or 'Not Paid' |

**UPSERT Query** (`update_allowance.php:54-59`):
```sql
INSERT INTO weekly_payroll_reports 
(employee_id, report_year, report_month, week_number, view_type, performance_allowance, payment_status, created_at, updated_at)
VALUES (?, ?, ?, ?, ?, ?, 'Not Paid', NOW(), NOW())
ON DUPLICATE KEY UPDATE 
performance_allowance = VALUES(performance_allowance),
updated_at = NOW()
```

### Fallback: `daily_payroll_reports`

If the upsert to `weekly_payroll_reports` fails, the system falls back to updating `daily_payroll_reports` for the date range (`update_allowance.php:102-121`).

---

## Loading Allowances

### Data Retrieval
**Location:** `employee/function/report.php:519-557`

When the report page loads, it retrieves existing allowances:

```php
$payment_status_query = "SELECT employee_id, payment_status, performance_allowance 
                       FROM weekly_payroll_reports AS wpr1 
                       WHERE report_year = ? AND report_month = ? 
                       AND week_number = ? AND view_type = ?
                       AND id = (SELECT MAX(id) FROM weekly_payroll_reports AS wpr2 
                                 WHERE wpr2.employee_id = wpr1.employee_id 
                                 AND wpr2.report_year = wpr1.report_year 
                                 AND wpr2.report_month = wpr1.report_month 
                                 AND wpr2.week_number = wpr1.week_number 
                                 AND wpr2.view_type = wpr1.view_type)";
```

The subquery ensures only the **latest record** per employee/week is loaded.

### Merging into Payroll Data
**Location:** `employee/function/report.php:550-557`

```php
foreach ($employee_payroll as $emp_id => &$payroll) {
    $payroll['payment_status'] = $payment_statuses[$emp_id] ?? 'Not Paid';
    // Override with weekly_allowance if it exists
    if (isset($weekly_allowances[$emp_id]) && $weekly_allowances[$emp_id] > 0) {
        $payroll['performance_allowance'] = $weekly_allowances[$emp_id];
    }
}
```

---

## Payroll Calculation

### Formula

```
Gross Pay = (Daily Rate × Days Worked) + Overtime Amount + Performance Allowance

Net Pay = Gross Pay - (SSS + PhilHealth + Pag-IBIG + Cash Advance + SSS Loan)
```

**Location:** `employee/weekly_report.php:260-267, 287-309`

```php
$allowance = floatval($payroll['performance_allowance'] ?? 0);
$gross_plus_allowance = $payroll['gross_pay'] + $allowance;
$total_deductions = $payroll['sss_deduction'] + $payroll['philhealth_deduction'] + 
                    $payroll['pagibig_deduction'] + $ca_deduction + $sss_loan;
$take_home = $gross_plus_allowance - $total_deductions;
```

---

## Payslip Integration

The Performance Allowance appears on the payslip if > 0:

**Location:** `employee/weekly_report.php:473`

```javascript
${allowance > 0 ? `<div class="payslip-row">
    <span class="text-gray-300">Performance Allowance</span>
    <span class="text-white">₱${numberFormat(allowance)}</span>
</div>` : ''}
```

---

## Permissions

Only users with these positions can modify allowances:
- Admin
- Super Admin
- Developer

**Check:** `update_allowance.php:35`
```php
if (empty($_SESSION['logged_in']) || !in_array($_SESSION['position'], ['Admin', 'Super Admin', 'Developer'])) {
    throw new Exception('Unauthorized');
}
```

---

## Error Handling & Logging

- Errors are logged to `employee/update_allowance_errors.log`
- Toast notifications show success/error messages
- Silent failures return JSON with error details
- Database errors are caught and reported via AJAX response

---

## Related Files

| File | Purpose |
|------|---------|
| `employee/weekly_report.php` | UI display and JavaScript handlers |
| `employee/function/report.php` | Data loading and calculation |
| `employee/update_allowance.php` | AJAX endpoint for saving |
| `employee/js/report.js` | Client-side calculation updates |

---

## Important Notes

1. **Weekly vs Monthly View:** The allowance is stored separately for weekly (week_number 1-5) and monthly (week_number 0) views
2. **Persistence:** Once saved, the allowance persists for that specific week/month period
3. **No Default Value:** Allowance defaults to 0 if not previously set
4. **Real-time Updates:** Calculations update immediately on input change
5. **Cascade Updates:** The grand total row updates automatically when any allowance changes
