# Overtime Requests Per Day - System Documentation

## Overview

This document explains how overtime requests are handled in the attendance system, specifically focusing on the limits and counting of requests per day.

---

## Request Limits Per Day

### Per-Employee Limit
**Maximum: 3 pending overtime requests per employee per day**

The system enforces a limit where each employee can have up to **3 pending overtime requests** for any given date. This is implemented in:

- `@/wamp64/www/main/employee/function/attendance.php:2670-2704`
- `@/wamp64/www/main/overtime_request.php:71-85`

### How the Limit Works

```php
// Check if employee has reached the daily limit of 3 pending requests
$checkPendingSql = "SELECT COUNT(*) as pending_count FROM overtime_requests 
    WHERE employee_id = ? 
    AND request_date = CURDATE() 
    AND status = 'pending'";
```

If the employee already has 3 pending requests, the system returns:
```json
{
    "success": false,
    "message": "Maximum of 3 pending overtime requests allowed per day"
}
```

---

## Database Schema

### Table: `overtime_requests`

| Column | Type | Description |
|--------|------|-------------|
| `id` | int (PK) | Auto-increment primary key |
| `employee_id` | int (FK) | Reference to employees table |
| `branch_name` | varchar(255) | Branch where overtime will be worked |
| `request_date` | date | **The date for which overtime is requested** |
| `requested_hours` | decimal(5,2) | Number of overtime hours requested |
| `overtime_reason` | text | Reason for overtime request |
| `status` | enum | pending, approved, rejected, pre-approved |
| `requested_by` | varchar(255) | Name of requester |
| `requested_by_user_id` | int | ID of requester (for notifications) |
| `requested_at` | timestamp | **When the request was created** |
| `approved_by` | varchar(255) | Name of approver |
| `approved_at` | timestamp | When request was approved/rejected |
| `rejection_reason` | text | Reason if rejected |
| `attendance_id` | int (FK) | Links to attendance record |

### Key Indexes

```sql
KEY `idx_employee_date` (`employee_id`,`request_date`),
KEY `idx_status` (`status`),
KEY `idx_requested_at` (`requested_at`)
```

---

## Important Distinctions

### `request_date` vs `requested_at`

| Field | Meaning | Example |
|-------|---------|---------|
| `request_date` | The date the employee will work overtime | "2026-04-10" - OT for April 10 |
| `requested_at` | Timestamp when request was submitted | "2026-04-08 14:30:00" - submitted April 8 |

**Note**: An employee can submit a request in advance (e.g., request on April 8 for overtime on April 10).

---

## Query Examples

### Count Requests Per Day (by request_date)

```sql
-- Count how many requests exist for each date
SELECT 
    request_date,
    COUNT(*) as total_requests,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_count,
    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_count
FROM overtime_requests
GROUP BY request_date
ORDER BY request_date DESC;
```

### Count Requests Submitted Per Day (by requested_at)

```sql
-- Count how many requests were submitted on each date
SELECT 
    DATE(requested_at) as submission_date,
    COUNT(*) as total_submitted,
    COUNT(DISTINCT employee_id) as unique_employees
FROM overtime_requests
GROUP BY DATE(requested_at)
ORDER BY submission_date DESC;
```

### Check Employee's Daily Request Count

```sql
-- Check if employee has pending request for today
SELECT COUNT(*) as pending_count
FROM overtime_requests
WHERE employee_id = ?
AND request_date = CURDATE()
AND status = 'pending';
```

---

## API Endpoints

### Submit Overtime Request

**Endpoint**: `POST /overtime_request.php` or `POST /employee/function/attendance.php`

**Parameters**:
```json
{
    "action": "request_overtime",
    "employee_id": 123,
    "branch": "Main Branch",
    "total_ot_hrs": 2.5,
    "overtime_reason": "Project deadline"
}
```

**Validation Rules**:
1. `requested_hours` must be > 0 (max 4 hours in some endpoints)
2. `overtime_reason` cannot be empty
3. `branch_name` is required
4. **Only 1 pending request allowed per employee per `request_date`**

### Get Overtime Requests

**Endpoint**: `GET /get_overtime_requests.php`

**Query Parameters**:
- `employee_id` - Filter by employee
- `status` - pending, approved, rejected
- `start_date` / `end_date` - Date range filter

---

## Status Flow

```
[Employee submits request]
         ↓
    ┌─────────┐
    │ PENDING │ ← Only 1 allowed per employee per day
    └────┬────┘
         ↓
    ┌────┴────┐
    │         │
APPROVED  REJECTED
    │         │
    ↓         ↓
[OT hours  [Notification
 recorded]  sent]
```

---

## Business Rules Summary

| Rule | Implementation |
|------|---------------|
| 3 pending requests per employee per day | `employee_id` + `request_date` + `status='pending'` count >= 3 |
| Max 4 hours per request | Validation in `@/wamp64/www/main/overtime_request.php:57-63` |
| Multiple approved requests allowed | Same employee can have multiple approved OT entries for same date via attendance |
| Notifications sent to Admin/Super Admin | All admins get notified on new request |

---

## Related Files

| File | Purpose |
|------|---------|
| `@/wamp64/www/main/overtime_request.php` | API endpoint for submitting requests |
| `@/wamp64/www/main/get_overtime_requests.php` | API for retrieving requests |
| `@/wamp64/www/main/employee/function/attendance.php:2618-2799` | Core request submission logic |
| `@/wamp64/www/main/employee/notification.php:333-570` | Request approval/rejection handling |
| `@/wamp64/www/main/employee/eng_dashboard.php:327-432` | Engineer dashboard request submission |
| `@/wamp64/www/main/employee/overtime.php` | Overtime reporting page |
| `@/wamp64/www/main/dbschema/overtime_requests.sql` | Database schema |
