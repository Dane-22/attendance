# Overtime System - API Documentation

## Overview
The overtime system allows employees to request overtime hours and administrators to approve/reject them. This document explains how the overtime system works and the available API endpoints for mobile app integration.

## Database Schema

### overtime_requests Table
```sql
CREATE TABLE IF NOT EXISTS `overtime_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `branch_name` varchar(255) NOT NULL,
  `request_date` date NOT NULL,
  `requested_hours` decimal(5,2) NOT NULL,
  `overtime_reason` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `requested_by` varchar(255) NOT NULL,
  `requested_by_user_id` int DEFAULT NULL,
  `requested_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `approved_by` varchar(255) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text,
  `attendance_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_employee_date` (`employee_id`,`request_date`),
  KEY `idx_status` (`status`),
  KEY `idx_requested_at` (`requested_at`),
  KEY `idx_requested_by_user` (`requested_by_user_id`)
);
```

### attendance Table (Overtime Columns)
The attendance table tracks overtime hours with these columns:
- `total_ot_hrs` - Total overtime hours for the day
- `is_overtime_running` - Flag indicating if overtime is currently active

## Overtime Workflow

### 1. Overtime Request Process
1. **Employee submits request** with hours and reason
2. **Request status = 'pending'**
3. **Administrator reviews** and approves/rejects
4. **If approved**: Hours added to `attendance.total_ot_hrs`
5. **If rejected**: Rejection reason recorded

### 2. Overtime Status Flow
```
pending → approved → (hours added to attendance)
pending → rejected → (rejection reason stored)
```

## API Endpoints for Mobile Apps

### 1. Submit Overtime Request
**Endpoint:** `POST /employee/api/overtime_request.php` (if exists) or create new endpoint

**Required Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `employee_id` | integer | Employee's database ID |
| `employee_code` | string | Employee's unique code |
| `branch_name` | string | Employee's assigned branch |
| `requested_hours` | decimal | Number of overtime hours requested |
| `overtime_reason` | text | Reason for overtime request |

**Example Request:**
```javascript
const submitOvertimeRequest = async (employeeId, employeeCode, branchName, hours, reason) => {
  const formData = new FormData();
  formData.append('employee_id', employeeId);
  formData.append('employee_code', employeeCode);
  formData.append('branch_name', branchName);
  formData.append('requested_hours', hours);
  formData.append('overtime_reason', reason);

  const response = await fetch('https://jajr.xandree.com/employee/api/overtime_request.php', {
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
  "message": "Overtime request submitted successfully",
  "request_id": 123
}
```

### 2. Get Overtime Requests
**Endpoint:** `GET /employee/api/get_overtime_requests.php` (if exists)

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `employee_id` | integer | Filter by employee (optional) |
| `status` | string | Filter by status: pending/approved/rejected (optional) |
| `start_date` | date | Filter by date range start (optional) |
| `end_date` | date | Filter by date range end (optional) |

**Example Response:**
```json
{
  "success": true,
  "requests": [
    {
      "id": 123,
      "employee_id": 24,
      "branch_name": "BCDA - Admin",
      "request_date": "2026-02-14",
      "requested_hours": 5.00,
      "overtime_reason": "Project deadline",
      "status": "approved",
      "requested_by": "KELVIN CALDERON",
      "requested_at": "2026-02-14 01:36:43",
      "approved_by": "Admin",
      "approved_at": "2026-02-14 01:37:00",
      "rejection_reason": null
    }
  ]
}
```

### 3. Approve/Reject Overtime Request
**Endpoint:** `POST /employee/api/approve_overtime.php` (if exists)

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `request_id` | integer | Overtime request ID |
| `action` | string | 'approve' or 'reject' |
| `rejection_reason` | text | Required if action is 'reject' |
| `approved_by` | string | Name of approver |

**Example Request:**
```javascript
const approveOvertime = async (requestId, approvedBy) => {
  const formData = new FormData();
  formData.append('request_id', requestId);
  formData.append('action', 'approve');
  formData.append('approved_by', approvedBy);

  const response = await fetch('https://jajr.xandree.com/employee/api/approve_overtime.php', {
    method: 'POST',
    body: formData,
  });

  return await response.json();
};
```

### 4. Get Employee Overtime Summary
**Endpoint:** `GET /employee/api/overtime_summary.php` (if exists)

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
    "total_requested_hours": 15.5,
    "total_approved_hours": 12.0,
    "total_rejected_hours": 3.5,
    "pending_requests": 2,
    "approved_requests": 5,
    "rejected_requests": 1
  }
}
```

## Integration with Attendance System

### Clock In/Out with Overtime
The attendance system tracks overtime through:

1. **Clock In**: Sets `is_overtime_running = 0`
2. **Clock Out**: Can include overtime hours
3. **Overtime Request**: Adds to `total_ot_hrs` when approved

### Overtime Hours Calculation
```sql
-- Get total overtime hours for an employee
SELECT SUM(total_ot_hrs) as total_ot
FROM attendance 
WHERE employee_id = ? 
AND attendance_date BETWEEN ? AND ?
```

## Mobile App Implementation Guide

### 1. Overtime Request Flow
```javascript
// Complete overtime request flow
const handleOvertimeRequest = async () => {
  // 1. Submit request
  const submitResult = await submitOvertimeRequest(
    employeeId, 
    employeeCode, 
    branchName, 
    requestedHours, 
    overtimeReason
  );
  
  if (submitResult.success) {
    // 2. Show success message
    showToast('Overtime request submitted successfully');
    
    // 3. Refresh requests list
    await fetchOvertimeRequests();
  } else {
    showToast('Failed to submit request: ' + submitResult.message);
  }
};
```

### 2. Real-time Updates
Use WebSocket or polling to check request status:
```javascript
// Poll for status updates every 30 seconds
setInterval(async () => {
  const requests = await getOvertimeRequests(employeeId);
  updateUI(requests);
}, 30000);
```

### 3. Offline Support
Store requests locally when offline:
```javascript
// Store request for later sync
const storeOfflineRequest = (requestData) => {
  const offlineRequests = JSON.parse(localStorage.getItem('offline_requests') || '[]');
  offlineRequests.push({
    ...requestData,
    timestamp: Date.now(),
    synced: false
  });
  localStorage.setItem('offline_requests', JSON.stringify(offlineRequests));
};
```

## Security Considerations

1. **Authentication**: Verify employee credentials before processing requests
2. **Authorization**: Only employees can request their own overtime
3. **Rate Limiting**: Limit number of requests per day
4. **Validation**: Validate hours (e.g., max 12 hours per day)
5. **Audit Trail**: Log all request actions

## Business Rules

1. **Maximum Hours**: Typically 8 hours regular + 4 hours overtime per day
2. **Approval Required**: All overtime requests need admin approval
3. **Date Validation**: Cannot request overtime for future dates
4. **Duplicate Prevention**: One request per employee per day
5. **Reason Required**: All requests must include a valid reason

## Error Handling

Common error responses:
```json
{
  "success": false,
  "message": "Duplicate overtime request for this date"
}
```

```json
{
  "success": false,
  "message": "Requested hours exceed maximum allowed (4 hours)"
}
```

```json
{
  "success": false,
  "message": "Cannot request overtime for future dates"
}
```

## Testing

**Sample Test Data:**
```sql
INSERT INTO overtime_requests (
  employee_id, branch_name, request_date, requested_hours, 
  overtime_reason, status, requested_by, requested_by_user_id
) VALUES (
  24, 'BCDA - Admin', '2026-02-14', 5.00, 
  'Project deadline', 'pending', 'KELVIN CALDERON', 63
);
```

**Test API Calls:**
```bash
curl -X POST "https://jajr.xandree.com/employee/api/overtime_request.php" \
  -d "employee_id=24&employee_code=E0014&branch_name=BCDA - Admin&requested_hours=3.5&overtime_reason=Testing"
```

## Files Involved

| File | Purpose |
|------|---------|
| `notification.php` | Handles overtime approval notifications |
| `attendance.php` | Updates attendance with overtime hours |
| `weekly_report.php` | Calculates overtime pay |
| `payroll.php` | Includes overtime in payroll calculations |

## Implementation Checklist

- [ ] Create `overtime_request.php` API endpoint
- [ ] Create `get_overtime_requests.php` API endpoint  
- [ ] Create `approve_overtime.php` API endpoint
- [ ] Create `overtime_summary.php` API endpoint
- [ ] Implement mobile app UI for request submission
- [ ] Add push notifications for status updates
- [ ] Implement offline support
- [ ] Add rate limiting and validation
- [ ] Create admin approval interface
- [ ] Add audit logging
