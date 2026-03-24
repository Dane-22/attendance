# Attendance Audit Module

## Overview

The `employee/audit.php` module provides an admin interface for reviewing employee attendance records with calendar navigation, advanced search, and filtering capabilities.

---

## File Location

```
employee/audit.php
```

---

## Features

### 1. Rate Limiting
- **Max Requests:** 60 per minute
- **Window:** 60 seconds
- **Behavior:** Session-based tracking with blocking mechanism
- **HTTP 429:** Returned when limit exceeded

### 2. Authentication & Authorization
- **Required Role:** Admin, Super Admin, or Developer
- **Redirect:** Unauthenticated users redirected to `../login.php`

### 3. Pagination
- **Records Per Page:** 25
- **Navigation:** Previous/Next with page numbers and ellipsis
- **URL Parameters:** `page`, `date`, `month`, `year`, `filter`, `search`, `search_type`

### 4. Search Functionality
| Search Type | Description |
|-------------|-------------|
| `all` | Search across name, code, and branch |
| `name` | First name or last name |
| `code` | Employee code |
| `branch` | Branch name |

### 5. Calendar View
- **Month Navigation:** Previous/Next month buttons
- **Visual Indicators:**
  - **Orange background:** Selected date
  - **Gold border:** Has attendance records
  - **Yellow border:** Today
  - **Dot indicator:** Shows record count on dates with data
- **Quick Select:** Click any day to view that date's records

### 6. Filter Options
| Filter | Description |
|--------|-------------|
| `day` | Single day view (default) |
| `week` | Current week (Monday-Sunday) |
| `month` | Current month |

---

## Database Schema Dependencies

### Tables Used
- `attendance` - Attendance records
- `employees` - Employee information

### Key Fields
```sql
attendance:
- id, employee_id, attendance_date
- time_in, time_out, branch_name, status

employees:
- id, first_name, last_name, employee_code, position
```

---

## Code Structure

### PHP Logic Sections

```php
// 1. Rate Limiting (Lines 8-44)
$RATE_LIMIT_MAX_REQUESTS = 60;
$RATE_LIMIT_WINDOW = 60;

// 2. Authentication Check (Lines 47-50)
if (empty($_SESSION['logged_in']) || !in_array($_SESSION['position'], ['Admin', 'Super Admin', 'Developer']))

// 3. Search & Filter Parameters (Lines 57-67)
$searchQuery, $searchType, $selectedDate, $filter

// 4. Database Queries (Lines 119-296)
// - Count query for pagination
// - Summary query for stats
// - Detail query for table data

// 5. Calendar Data (Lines 299-317)
$daysWithAttendance array with record counts
```

### Statistics Displayed
| Stat | Description |
|------|-------------|
| Total Records | Total attendance entries for selected period |
| Currently Present | Time in but no time out |
| Completed Shifts | Both time in and time out recorded |
| Absent | Status marked as 'Absent' |

---

## UI Components

### Main Layout
```
┌─────────────────────────────────────────────────────────────┐
│  Attendance Audit Header + Search Form                      │
├─────────────────────────────────────────────────────────────┤
│  Filter Buttons (Week | Month | Today) + Generate Report    │
├───────────────────────────┬─────────────────────────────────┤
│                         │  Stats Cards (4 columns)          │
│  Calendar Widget        ├───────────────────────────────────┤
│  (Month View)           │  Selected Date Header             │
│                         ├───────────────────────────────────┤
│                         │  Attendance Table                 │
│                         │  - Employee info                  │
│                         │  - Time in/out                    │
│                         │  - Hours worked                     │
│                         │  - Status badge                     │
│                         ├───────────────────────────────────┤
│                         │  Pagination Controls              │
└─────────────────────────┴─────────────────────────────────┘
```

### CSS Classes
| Class | Purpose |
|-------|---------|
| `calendar-grid` | 7-column grid for calendar days |
| `calendar-day` | Individual day cell styling |
| `calendar-day.selected` | Selected date highlighting |
| `calendar-day.has-data` | Date with records indicator |
| `audit-card` | Glass-morphism card container |
| `stat-card` | Statistics card styling |
| `attendance-table` | Data table with hover effects |
| `status-badge` | Status indicator (Present/Completed/Absent) |

---

## Status Badges

| Status | Class | Color |
|--------|-------|-------|
| Present | `status-present` | Green (#4CAF50) |
| Completed | `status-completed` | Blue (#2196F3) |
| Absent | `status-absent` | Red (#F44336) |

---

## URL Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `page` | integer | Pagination page number |
| `date` | YYYY-MM-DD | Selected date for filtering |
| `month` | 1-12 | Calendar month display |
| `year` | YYYY | Calendar year display |
| `filter` | day/week/month | Time range filter |
| `search` | string | Search query string |
| `search_type` | all/name/code/branch | Search field scope |

---

## Push Notification Widget

**Visibility:** Super Admin only (`$isAdmin || $isSuperAdmin`)

**Features:**
- Browser push notification subscription
- VAPID key integration
- Service Worker registration (`../sw.js`)
- UI states: enabled/denied/unsupported

**API Endpoints:**
- `api/get_vapid_key.php` - Fetch VAPID public key
- `api/save_push_subscription.php` - Save subscription to server

---

## Security Considerations

1. **SQL Injection Prevention:** All queries use prepared statements with bound parameters
2. **XSS Prevention:** `htmlspecialchars()` used on all output
3. **Rate Limiting:** Session-based request throttling
4. **Access Control:** Role-based authentication check

---

## Dependencies

### Required Files
```php
require_once __DIR__ . '/../conn/db_connection.php';
require_once __DIR__ . '/../functions.php';
```

### Included Components
```php
include_once __DIR__ . '/sidebar.php';
```

### External Libraries
- Tailwind CSS (CDN)
- Font Awesome 6.4.0 (CDN)
- Google Fonts: Inter

---

## Related Files

| File | Purpose |
|------|---------|
| `employee/audit_report_selector.php` | PDF report generation (Super Admin) |
| `api/get_vapid_key.php` | VAPID key API |
| `api/save_push_subscription.php` | Push subscription API |
| `../sw.js` | Service Worker for notifications |

---

## TODOs / Improvements

- [ ] Add export functionality for filtered data
- [ ] Implement date range picker for custom periods
- [ ] Add sorting options for table columns
- [ ] Include overtime hours in summary stats
