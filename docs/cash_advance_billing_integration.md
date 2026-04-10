# Cash Advance Per Employee - Billing Integration Documentation

## Overview

This document describes how the **Cash Advance (Per Employee)** report in `billing.php` fetches and displays cash advance data per employee.

## Architecture

### Files Involved

| File | Path | Purpose |
|------|------|---------|
| `billing.php` | `/employee/billing.php` | Main billing UI with cash advance report filter |
| `cash_advance_request.php` | `/cash_advance_request.php` | API for employees to submit cash advance requests |
| `cash_advance_history.php` | `/cash_advance_history.php` | API to fetch employee's cash advance history |
| `approve_cash_advance.php` | `/approve_cash_advance.php` | API for admin to approve/reject/pay requests |

### Database Table

**Table:** `cash_advances`

| Column | Type | Description |
|--------|------|-------------|
| `id` | int (PK) | Auto-increment ID |
| `employee_id` | int (FK) | Reference to employees table |
| `amount` | decimal(10,2) | Cash advance amount |
| `particular` | varchar(50) | Default: 'Cash Advance' |
| `reason` | text | Employee's reason for request |
| `status` | enum | 'Pending', 'Approved', 'Rejected', 'Pre-Approved' |
| `request_date` | datetime | When request was submitted |
| `approved_date` | datetime | When request was approved |
| `paid_date` | datetime | When cash was disbursed |
| `approved_by` | varchar(100) | Admin who processed the request |
| `rejection_reason` | text | Reason if rejected |

## Data Flow

### Important Note

`billing.php` does **NOT** fetch from `cash_advance_request.php`. Instead, it queries the `cash_advances` table **directly** for reporting purposes.

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│  Employee View  │────▶│ cash_advance_    │────▶│ cash_advances   │
│  (Submit Request) │     │ request.php      │     │ (Database)      │
└─────────────────┘     └──────────────────┘     └─────────────────┘
                                                          │
┌─────────────────┐     ┌──────────────────┐              │
│  Admin View     │◄────│ billing.php      │◄─────────────┘
│  (Reports)      │     │ (Direct SQL)     │     (Query)
└─────────────────┘     └──────────────────┘
```

## Implementation Details

### billing.php - Cash Advance Report (Lines 152-185)

```php
case 'cash_advance':
    $filterTitle = 'Cash Advance (Total per Employee)';
    $sql = "SELECT e.id, 
               e.employee_code,
               CONCAT(e.first_name, ' ', COALESCE(e.middle_name, ''), ' ', e.last_name) as full_name,
               COALESCE(a.branch_name, 'Unassigned') as branch_name,
               SUM(ca.amount) as total_cash_advance,
               COUNT(ca.id) as request_count,
               ca2.status as latest_status
        FROM employees e
        LEFT JOIN (
            SELECT DISTINCT employee_id, branch_name
            FROM attendance
            WHERE attendance_date BETWEEN ? AND ?
        ) a ON e.id = a.employee_id
        LEFT JOIN cash_advances ca ON e.id = ca.employee_id 
            AND ca.request_date >= ? AND ca.request_date <= ?
        LEFT JOIN (
            SELECT employee_id, status
            FROM cash_advances ca1
            WHERE request_date = (
                SELECT MAX(request_date) 
                FROM cash_advances 
                WHERE employee_id = ca1.employee_id
            )
        ) ca2 ON e.id = ca2.employee_id
        GROUP BY e.id, e.employee_code, e.first_name, e.middle_name, e.last_name, a.branch_name, ca2.status
        HAVING total_cash_advance > 0
        ORDER BY total_cash_advance DESC";
```

### Query Logic

1. **Base Table**: `employees` - Lists all employees
2. **Branch Lookup**: Subquery on `attendance` table to get employee's branch within date range
3. **Cash Advance Aggregation**: LEFT JOIN with `cash_advances` filtered by `request_date` range
4. **Latest Status**: Subquery gets the most recent status per employee
5. **Grouping**: Groups by employee details
6. **Filtering**: `HAVING total_cash_advance > 0` excludes employees with no cash advances
7. **Sorting**: Orders by highest cash advance amount first

### Report Display (Lines 361-369, 408-421)

**Table Columns:**
- Employee Code
- Employee Name
- Branch
- Total Cash Advance (sum of all requests)
- Request Count (number of requests)
- Latest Status (most recent request status)

**Grand Total Row:**
- Shows sum of all cash advances across all employees

### Print Preview Integration (Lines 569-586)

The cash advance data also appears in the Payment Request Form print preview:

```php
<!-- Cash Advance Section -->
<tr class="section-header">
    <td colspan="2"><strong>CASH ADVANCE</strong></td>
</tr>
<?php 
$cashAdvanceTotal = 0;
if ($filter === 'cash_advance' && !empty($data)): 
    foreach ($data as $row): 
        $cashAdvanceTotal += ($row['total_cash_advance'] ?? 0);
?>
<tr>
    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
    <td class="amount-right"><?php echo formatCurrency($row['total_cash_advance']); ?></td>
</tr>
```

## API Endpoints Reference

### 1. Submit Cash Advance Request
**Endpoint:** `POST /cash_advance_request.php`

**Parameters:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `employee_id` | int | Yes | Employee ID |
| `employee_code` | string | Yes | Employee code |
| `amount` | float | Yes | Request amount |
| `particular` | string | No | Default: 'Cash Advance' |
| `reason` | string | Yes | Reason for request |

**Validation:**
- Amount must be > 0
- Only one pending request allowed per employee
- Max amount = 50% of monthly salary or ₱10,000 (whichever is lower)

**Response:**
```json
{
  "success": true,
  "message": "Cash advance request submitted successfully",
  "request_id": 123
}
```

### 2. Get Employee Cash Advance History
**Endpoint:** `GET /cash_advance_history.php?emp_id={employee_id}`

**Response:**
```json
{
  "success": true,
  "employee": {...},
  "transactions": [...],
  "balance": 5000.00
}
```

### 3. Approve/Reject/Pay Cash Advance
**Endpoint:** `POST /approve_cash_advance.php`

**Parameters:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `request_id` | int | Yes | Cash advance request ID |
| `action` | string | Yes | 'approve', 'reject', or 'pay' |
| `rejection_reason` | string | Conditional | Required if action='reject' |
| `approved_by` | string | No | Admin username |

## Status Workflow

```
[PENDING] ──▶ [APPROVED] ──▶ [PAID]
     │
     └──────▶ [REJECTED]
```

## Key Findings

1. **billing.php queries database directly**, not via API endpoints
2. **Date filtering** is based on `request_date` in `cash_advances` table
3. **Only employees with cash advances** are shown (`HAVING total_cash_advance > 0`)
4. **Branch name** comes from the `attendance` table (most recent assignment)
5. **Latest status** shows the most recent request status per employee
6. **Grand total** is calculated and displayed at the bottom of the report

## Potential Improvements

1. **Status consistency**: The billing query fetches `latest_status` but filters by date range. Consider whether status should reflect requests outside the date range.

2. **Branch source**: Currently pulls from attendance records. Could use `employees.branch_id` for more accurate current assignment.

3. **Status enum mismatch**: Database uses 'Pending' (capital P) but code sometimes checks for 'pending' (lowercase).

## File Locations

- `@/employee/billing.php:152-185` - Cash advance SQL query
- `@/employee/billing.php:361-369` - Cash advance table headers
- `@/employee/billing.php:408-421` - Cash advance table rows
- `@/employee/billing.php:569-586` - Print preview cash advance section
- `@/cash_advance_request.php` - Submit request API
- `@/cash_advance_history.php` - Get history API
- `@/approve_cash_advance.php` - Approve/reject/pay API
- `@/attendance_db.sql:502-517` - Database schema
