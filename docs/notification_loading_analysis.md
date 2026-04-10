# Notification Page Auto-Refresh Analysis

## Overview

Both notification pages implement a 30-second auto-refresh mechanism to keep the request data current without requiring a full page reload.

---

## Files Affected

| File | Path | User Role |
|------|------|-----------|
| `notification.php` | `/employee/notification.php` | Super Admin, Developer |
| `admin_notification.php` | `/employee/admin_notification.php` | Admin |

---

## Auto-Refresh Mechanism

### JavaScript Implementation

Both files contain the same auto-refresh logic at the end of their `<script>` blocks:

```javascript
// Load initial data
document.addEventListener('DOMContentLoaded', () => {
    loadRequests('pending');
});

// Auto-refresh every 30 seconds
setInterval(() => {
    loadRequests(currentTab);
}, 30000);
```

### Location in Files

- **`notification.php`**: Lines 1529-1537
- **`admin_notification.php`**: Lines 1910-1918

---

## Request Flow

### 1. Trigger
- Every 30 seconds (30,000 milliseconds)
- Calls `loadRequests(currentTab)`
- `currentTab` tracks the currently active status filter (e.g., 'pending', 'approved', 'rejected', 'all')

### 2. AJAX Request

**For Overtime Requests:**
```javascript
const formData = new FormData();
formData.append('action', 'load_requests');
formData.append('status', status);

const response = await fetch('notification.php', {
    method: 'POST',
    body: formData
});
```

**For Cash Advance Requests:**
```javascript
const formData = new FormData();
formData.append('action', 'load_cash_advance_requests');
formData.append('status', status);

const response = await fetch('notification.php', {
    method: 'POST',
    body: formData
});
```

**For Leave Requests (admin_notification.php only):**
```javascript
const formData = new FormData();
formData.append('action', 'load_leave_requests');
formData.append('status', status);

const response = await fetch('admin_notification.php', {
    method: 'POST',
    body: formData
});
```

### 3. Server-Side Processing

The PHP backend handles the POST request:
1. Checks user authentication and role permissions
2. Queries the database for requests matching the status filter
3. Returns JSON response with:
   - `success` (boolean)
   - `requests` (array of request objects)
   - `counts` (object with counts per status)

### 4. UI Update
- Displays loading spinner during fetch
- Updates the request cards grid
- Updates tab count badges
- Maintains current search filter (if any)

---

## Database Tables Queried

| Request Type | Table | Status Values |
|--------------|-------|---------------|
| Overtime | `overtime_requests` | pending, pre-approved, approved, rejected |
| Cash Advance | `cash_advances` | pending, pre_approved, approved, rejected |
| Leave | `leave_requests` | pending, approved, rejected |

---

## Performance Considerations

### Current Behavior
- **Interval**: 30 seconds (30000ms)
- **Request Type**: POST with FormData
- **Data Transfer**: Full request list for current tab
- **UI Impact**: Shows loading state during refresh

### Potential Optimizations

1. **Increase Interval**: Consider 60 seconds for less frequent updates
2. **Conditional Refresh**: Only refresh if page is visible (using Page Visibility API)
3. **Incremental Updates**: Use timestamp to fetch only new/changed requests
4. **Debounce**: Clear pending refresh on manual tab change

---

## Code References

### notification.php
```javascript
// Lines 1529-1537
document.addEventListener('DOMContentLoaded', () => {
    loadRequests('pending');
});

// Auto-refresh every 30 seconds
setInterval(() => {
    loadRequests(currentTab);
}, 30000);
```

### admin_notification.php
```javascript
// Lines 1910-1918
document.addEventListener('DOMContentLoaded', () => {
    loadRequests('pending');
});

// Auto-refresh every 30 seconds
setInterval(() => {
    loadRequests(currentTab);
}, 30000);
```

---

## Functions Involved

| Function | Purpose | File |
|----------|---------|------|
| `loadRequests(status)` | Main fetch and render function | Both |
| `renderRequests(requests)` | Renders overtime request cards | Both |
| `renderCashAdvanceRequests(requests)` | Renders cash advance cards | Both |
| `renderLeaveRequests(requests)` | Renders leave request cards | admin_notification.php only |
| `updateCounts(counts)` | Updates tab badge numbers | Both |
| `updatePendingBadge()` | Updates sidebar notification badge | Both |

---

## Troubleshooting

### High Server Load
If server experiences high load from frequent polling:
1. Increase the interval value (30000 → 60000 for 60 seconds)
2. Implement page visibility check to pause when tab is inactive
3. Add exponential backoff on errors

### Stale Data
If data appears stale:
1. Check browser console for JavaScript errors
2. Verify database connection in PHP
3. Check server response times

### Network Errors
If requests fail:
1. Check `console.error` logs
2. Verify session hasn't expired
3. Check network connectivity
