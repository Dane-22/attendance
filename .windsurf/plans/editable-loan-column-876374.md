# Editable Loan Column Implementation

Implement an editable SSS Loan column in the weekly payroll report, similar to how the Performance Allowance column works.

## Changes Required

### 1. weekly_report.php - Change loan display to editable input
**Location:** Lines ~409-411 where loan is displayed

Replace the static display:
```php
<td class="px-2 py-2 text-right text-sm text-red-400">
    <?php echo ($sss_loan > 0) ? number_format($sss_loan, 0) : '-'; ?>
</td>
```

With an editable input:
```php
<td class="px-2 py-2 text-right text-sm">
    <input type="number" 
           name="loan_<?php echo $emp_id; ?>" 
           id="loan_<?php echo $emp_id; ?>"
           value="<?php echo number_format($sss_loan, 2, '.', ''); ?>" 
           min="0"
           step="0.01"
           class="w-20 bg-gray-800 border border-gray-600 rounded px-2 py-1 text-right text-red-400 focus:border-yellow-500 focus:outline-none loan-input"
           data-emp-id="<?php echo $emp_id; ?>"
           onchange="updateCalculations(<?php echo $emp_id; ?>); saveLoan(<?php echo $emp_id; ?>, this.value);">
</td>
```

Also update line ~344 to initialize from database value:
```php
$sss_loan = floatval($payroll['sss_loan'] ?? 0);
```

### 2. Create update_loan.php (new file)
Create `employee/update_loan.php` based on `update_allowance.php`. Key differences:
- Accept `sss_loan` parameter instead of `performance_allowance`
- Update `sss_loan` column in weekly_payroll_reports table
- Recalculate `total_deductions` and `take_home_pay`

### 3. weekly_report.php - Add saveLoan JavaScript function
Add after line ~795 (after saveAllowance function):
```javascript
function saveLoan(empId, loanValue) {
    const input = document.getElementById('loan_' + empId);
    const row = input.closest('tr');
    const empName = row.querySelector('td:first-child .font-medium').textContent.trim();
    
    const formData = new FormData();
    formData.append('employee_id', empId);
    formData.append('sss_loan', loanValue);
    formData.append('year', <?php echo $year; ?>);
    formData.append('month', <?php echo $month; ?>);
    formData.append('week', <?php echo $selected_week; ?>);
    formData.append('view_type', '<?php echo $view_type; ?>');
    
    fetch('update_loan.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(`SSS Loan saved for ${empName}`, 'success');
        } else {
            showToast('Failed to save loan. Please try again.', 'error');
        }
    })
    .catch(error => {
        showToast('Error saving loan. Please check your connection.', 'error');
    });
}
```

### 4. Database migration (if needed)
Ensure `weekly_payroll_reports` table has `sss_loan` column:
```sql
ALTER TABLE weekly_payroll_reports 
ADD COLUMN sss_loan DECIMAL(10,2) NOT NULL DEFAULT 0.00;
```

## Files to Create/Modify
- `employee/weekly_report.php` - Make loan column editable
- `employee/update_loan.php` - New endpoint (copy from update_allowance.php)
- `employee/function/report.php` - Include sss_loan in queries (if not already)
