# Cash Advance System - API Documentation

## Overview
The Cash Advance system allows employees to request cash advances and administrators to manage these requests. This document explains how the cash advance system works and the available API endpoints for mobile app integration.

## Database Schema

### cash_advances Table
```sql
CREATE TABLE IF NOT EXISTS `cash_advances` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `particular` varchar(255) NOT NULL,
  `reason` text,
  `status` enum('pending','approved','rejected','paid') DEFAULT 'pending',
  `request_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `approved_date` timestamp NULL DEFAULT NULL,
  `paid_date` timestamp NULL DEFAULT NULL,
  `approved_by` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_status` (`status`),
  KEY `idx_request_date` (`request_date`)
);
```

### e_signatures Table (for digital signatures)
```sql
CREATE TABLE IF NOT EXISTS `e_signatures` (
  `employee_id` int NOT NULL,
  `signature_type` varchar(50) NOT NULL,
  `signature_image` varchar(255) DEFAULT NULL,
  `signature_data` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`employee_id`, `signature_type`)
);
```

## Cash Advance Workflow

### 1. Request Process
1. **Employee submits request** with amount and reason
2. **Request status = 'pending'**
3. **Administrator reviews** and approves/rejects
4. **If approved**: Status changes to 'approved'
5. **When paid**: Status changes to 'paid'

### 2. Transaction Types
- **Cash Advance**: Credit to employee balance
- **Payment**: Debit from employee balance (repayment)

### 3. Status Flow
```
pending → approved → paid
pending → rejected
```

## API Endpoints for Mobile Apps

### 1. Submit Cash Advance Request
**Endpoint:** `POST /employee/api/cash_advance_request.php` (if exists) or create new endpoint

**Required Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `employee_id` | integer | Employee's database ID |
| `employee_code` | string | Employee's unique code |
| `amount` | decimal | Cash advance amount requested |
| `particular` | string | Transaction type (usually "Cash Advance") |
| `reason` | text | Reason for cash advance |

**Example Request:**
```javascript
const submitCashAdvance = async (employeeId, employeeCode, amount, reason) => {
  const formData = new FormData();
  formData.append('employee_id', employeeId);
  formData.append('employee_code', employeeCode);
  formData.append('amount', amount);
  formData.append('particular', 'Cash Advance');
  formData.append('reason', reason);

  const response = await fetch('https://jajr.xandree.com/employee/api/cash_advance_request.php', {
    method: 'POST',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: formData,
  });

  return await response.json();
};
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Cash advance request submitted successfully",
  "request_id": 123
}
```

### 2. Get Cash Advance History
**Endpoint:** `GET /employee/api/get_employee_ca.php` (existing)

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `emp_id` | integer | Employee ID (required) |

**Example Response:**
```json
{
  "success": true,
  "employee": {
    "id": 12,
    "first_name": "John",
    "last_name": "Doe",
    "employee_code": "E0012",
    "position": "Employee"
  },
  "transactions": [
    {
      "id": 1,
      "employee_id": 12,
      "amount": 500.00,
      "particular": "Cash Advance",
      "reason": "Emergency expense",
      "status": "approved",
      "request_date": "2026-02-10 11:29:26",
      "approved_date": "2026-02-10 14:30:00",
      "paid_date": null,
      "approved_by": "Admin",
      "running_balance": 500.00
    },
    {
      "id": 2,
      "employee_id": 12,
      "amount": 250.00,
      "particular": "Payment",
      "reason": "Partial repayment",
      "status": "paid",
      "request_date": "2026-02-10 11:36:48",
      "approved_date": null,
      "paid_date": "2026-02-11 09:15:00",
      "approved_by": null,
      "running_balance": 250.00
    }
  ],
  "balance": 250.00
}
```

### 3. Approve/Reject Cash Advance
**Endpoint:** `POST /employee/api/approve_cash_advance.php` (if exists) or create new endpoint

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `request_id` | integer | Cash advance request ID |
| `action` | string | 'approve' or 'reject' |
| `rejection_reason` | text | Required if action is 'reject' |
| `approved_by` | string | Name of approver |

**Example Request:**
```javascript
const approveCashAdvance = async (requestId, approvedBy) => {
  const formData = new FormData();
  formData.append('request_id', requestId);
  formData.append('action', 'approve');
  formData.append('approved_by', approvedBy);

  const response = await fetch('https://jajr.xandree.com/employee/api/approve_cash_advance.php', {
    method: 'POST',
    body: formData,
  });

  return await response.json();
};
```

### 4. Upload Digital Signature
**Endpoint:** `POST /employee/api/upload_signature.php` (if exists) or create new endpoint

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `employee_id` | integer | Employee ID |
| `signature_data` | string | Base64 encoded signature image |
| `signature_type` | string | Type of signature ('employee', 'cash_advance') |

**Example Request:**
```javascript
const uploadSignature = async (employeeId, signatureData) => {
  const formData = new FormData();
  formData.append('employee_id', employeeId);
  formData.append('signature_data', signatureData);
  formData.append('signature_type', 'cash_advance');

  const response = await fetch('https://jajr.xandree.com/employee/api/upload_signature.php', {
    method: 'POST',
    body: formData,
  });

  return await response.json();
};
```

### 5. Get Cash Advance Summary
**Endpoint:** `GET /employee/api/cash_advance_summary.php` (if exists) or create new endpoint

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `employee_id` | integer | Employee ID |
| `month` | integer | Month (1-12) (optional) |
| `year` | integer | Year (optional) |

**Example Response:**
```json
{
  "success": true,
  "summary": {
    "total_requested": 1500.00,
    "total_approved": 1200.00,
    "total_paid": 800.00,
    "outstanding_balance": 400.00,
    "pending_requests": 1,
    "approved_requests": 3,
    "rejected_requests": 0
  }
}
```

## Balance Calculation Logic

### Running Balance Algorithm
```php
$balance = 0;
foreach ($transactions as $transaction) {
    if ($transaction['particular'] === 'Payment') {
        $balance -= $transaction['amount']; // Debit
    } else {
        $balance += $transaction['amount']; // Credit
    }
    $transaction['running_balance'] = $balance;
}
```

### Balance Rules
1. **Cash Advance** = Increases balance (credit)
2. **Payment** = Decreases balance (debit)
3. **Running Balance** = Current outstanding amount
4. **Maximum Advance** = Usually limited to monthly salary percentage

## Mobile App Implementation Guide

### 1. Cash Advance Request Flow
```javascript
const handleCashAdvanceRequest = async () => {
  // Validate input
  if (amount <= 0 || amount > maxAllowedAmount) {
    showToast('Invalid amount. Maximum allowed: ₱' + maxAllowedAmount);
    return;
  }

  // Submit request
  const result = await submitCashAdvance(
    employeeId,
    employeeCode,
    amount,
    reason
  );

  if (result.success) {
    showToast('Cash advance request submitted successfully');
    // Refresh history
    await fetchCashAdvanceHistory();
  } else {
    showToast('Failed to submit: ' + result.message);
  }
};
```

### 2. Digital Signature Integration
```javascript
const captureSignature = async () => {
  // Get signature from canvas or signature pad
  const signatureData = signaturePad.toDataURL();
  
  // Upload signature
  const result = await uploadSignature(employeeId, signatureData);
  
  if (result.success) {
    showToast('Signature uploaded successfully');
  } else {
    showToast('Failed to upload signature');
  }
};
```

### 3. Real-time Balance Updates
```javascript
// Poll for balance updates every 30 seconds
setInterval(async () => {
  const history = await getCashAdvanceHistory(employeeId);
  updateBalanceDisplay(history.balance);
}, 30000);
```

## Security Considerations

1. **Authentication**: Verify employee credentials before processing requests
2. **Authorization**: Only employees can request their own cash advances
3. **Rate Limiting**: Limit number of requests per month
4. **Amount Validation**: Validate against employee's salary and existing balance
5. **Audit Trail**: Log all request actions and approvals
6. **Digital Signatures**: Require signatures for cash advance disbursement

## Business Rules

1. **Maximum Amount**: Usually 50% of monthly salary
2. **Outstanding Balance**: Employee cannot have unpaid advances
3. **Approval Required**: All cash advances need admin approval
4. **Repayment Schedule**: Deducted from future salary payments
5. **Interest**: Usually interest-free for employee cash advances
6. **Processing Time**: 1-2 business days for approval

## Error Handling

Common error responses:
```json
{
  "success": false,
  "message": "Employee has outstanding cash advance"
}
```

```json
{
  "success": false,
  "message": "Requested amount exceeds maximum allowed"
}
```

```json
{
  "success": false,
  "message": "Duplicate cash advance request for this period"
}
```

## Testing

**Sample Test Data:**
```sql
INSERT INTO cash_advances (
  employee_id, amount, particular, reason, status, request_date
) VALUES (
  12, 500.00, 'Cash Advance', 'Emergency medical expense', 
  'pending', NOW()
);
```

**Test API Calls:**
```bash
curl -X POST "https://jajr.xandree.com/employee/api/cash_advance_request.php" \
  -d "employee_id=12&employee_code=E0012&amount=500&particular=Cash Advance&reason=Testing"
```

## Files Involved

| File | Purpose |
|------|---------|
| `cash_advance.php` | Main cash advance management interface |
| `api/get_employee_ca.php` | Fetch employee cash advance history |
| `notification.php` | Handles cash advance notifications |
| `billing.php` | Cash advance billing and reporting |

## Implementation Checklist

- [ ] Create `cash_advance_request.php` API endpoint
- [ ] Create `approve_cash_advance.php` API endpoint  
- [ ] Create `upload_signature.php` API endpoint
- [ ] Create `cash_advance_summary.php` API endpoint
- [ ] Implement mobile app UI for request submission
- [ ] Add digital signature capture
- [ ] Implement balance tracking
- [ ] Add push notifications for status updates
- [ ] Implement offline support
- [ ] Add rate limiting and validation
- [ ] Create admin approval interface
- [ ] Add repayment scheduling
- [ ] Create audit logging system

## Integration with Payroll

### Deduction Processing
Cash advances are typically deducted from future salary payments:
```sql
-- Get total cash advance deductions for payroll
SELECT SUM(amount) as total_deduction
FROM cash_advances 
WHERE employee_id = ? 
AND status = 'paid'
AND paid_date BETWEEN ? AND ?
```

### Payroll Integration
```php
// In payroll calculation
$cashAdvanceDeduction = getCashAdvanceDeduction($employeeId, $payrollPeriod);
$netPay = $grossPay - $cashAdvanceDeduction - $otherDeductions;
```

## Mobile App UI Components

### 1. Request Form
- Amount input with validation
- Reason text area
- Maximum amount indicator
- Submit button with loading state

### 2. History Screen
- Transaction list with running balance
- Filter by date range
- Status indicators (pending/approved/paid)

### 3. Signature Screen
- Canvas for signature capture
- Clear and save buttons
- Preview of captured signature

### 4. Balance Dashboard
- Current outstanding balance
- Available credit limit
- Quick request buttons
- Recent transaction summary
