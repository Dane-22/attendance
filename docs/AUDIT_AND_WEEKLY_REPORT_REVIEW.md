# Audit.php and Weekly_Report.php Review Documentation

## Executive Summary

This document provides a comprehensive review of the `audit.php` and `weekly_report.php` files in the employee module, analyzing their functionality, database dependencies, and architecture to support a planned recreation effort.

---

## 1. AUDIT.PHP Analysis

### 1.1 Purpose
Attendance audit interface for administrators to review, filter, search, and manage employee attendance records with calendar-based navigation.

### 1.2 File Location
`c:\wamp64\www\main\employee\audit.php` (3,459 lines)

### 1.3 Core Features

#### A. Rate Limiting (Lines 9-46)
- 60 requests per minute maximum
- Session-based tracking
- 429 HTTP response on limit exceeded
- 60-second blocking window

#### B. Authentication & Authorization (Lines 48-56)
- Requires login session
- Allowed roles: Admin, Super Admin, Developer
- Role-based feature access ($isAdmin, $isSuperAdmin)

#### C. Filter System (Lines 63-106)
- **Date Filters**: Day (single date), Week (Monday-Sunday), Month (full month)
- **Search Types**: All fields, Employee Name, Employee Code, Branch
- **Status Filters**: All, Present, Late, Completed, Absent, Voided
- **Auto-Absent Logic**: Automatic absent marking after 8:30 AM cutoff (excluding Sundays)

#### D. Calendar Component (Lines 592-620)
- Month navigation (previous/next)
- Days with attendance highlighted
- Selected date indicator
- Today marker
- Click to select date

#### E. Attendance Status Logic (Lines 157-184)
- **Present**: Time-in recorded, no time-out
- **Late**: Workers checking in after 7:15 AM
- **Completed**: Both time-in and time-out recorded
- **Absent**: No time-in or explicitly marked
- **Voided**: Admin-voided records (strikethrough display)

#### F. Pagination (Lines 58-62, 1429-1502)
- 25 records per page
- Page number navigation with ellipsis
- Record count display
- Filter persistence across pages

#### G. Export Functionality (Lines 1020-1121)
- Excel export with date range selection
- Branch filter for exports
- Individual employee report export
- All employees export

#### H. Void Functionality (Lines 851-955, Admin-only)
- Modal-based void interface
- Reason required for voiding
- Only completed records can be voided
- Visual indicator for voided records

#### I. Push Notifications (Lines 1520-1851, Super Admin only)
- Service Worker registration
- VAPID key-based subscription
- Browser permission handling
- Real-time notification widget

#### J. Calendar Modals
- **Individual Employee Calendar** (Lines 1853-1923): Monthly attendance view per employee
- **Branch Calendar** (Lines 1925-2005): Branch-wide attendance view

### 1.4 Database Queries

#### Main Attendance Query (UNION approach)
```sql
-- Real attendance records
(SELECT a.id, a.attendance_date, a.time_in, a.time_out, ...
 FROM attendance a
 INNER JOIN employees e ON a.employee_id = e.id
 LEFT JOIN branches b ON e.branch_id = b.id
 WHERE a.attendance_date = ?)

UNION ALL

-- Auto-absent employees (no attendance record)
(SELECT NULL as attendance_id, e.id as employee_id, ? as attendance_date, ...
 FROM employees e
 LEFT JOIN attendance a ON e.id = a.employee_id AND a.attendance_date = ?
 WHERE a.id IS NULL)
```

#### Calendar Data Query
```sql
SELECT DISTINCT attendance_date, 
    COUNT(*) as count,
    SUM(CASE WHEN time_in IS NOT NULL AND time_out IS NULL THEN 1 ELSE 0 END) as open_shifts
FROM attendance 
WHERE attendance_date LIKE ?
GROUP BY attendance_date
```

### 1.5 UI/UX Features
- Dark theme with orange/gold accents
- Responsive grid layout
- Status badges with color coding
- Hover effects and transitions
- Loading states for modals
- Toast notifications

---

## 2. WEEKLY_REPORT.PHP Analysis

### 2.1 Purpose
Payroll/deployment report interface showing employee work hours, earnings, deductions, and payment status for weekly/monthly periods.

### 2.2 File Location
`c:\wamp64\www\main\employee\weekly_report.php` (1,263 lines)

### 2.3 Core Features

#### A. View Types (Lines 113-126)
- **Weekly View**: Week 1-5 of selected month
- **Monthly View**: Full month payroll
- **Date Range**: Custom start/end date selection

#### B. Branch Filtering (Lines 203-282)
- "All Branches" quick filter
- Individual branch badges
- Pagination for branch list (10 per page)
- Active branch highlighting

#### C. Payroll Table Columns
1. Employee selection checkbox
2. Employee name
3. Date/Period
4. Days Worked
5. Hours Worked
6. Daily Rate
7. Basic Pay
8. OT Hours
9. OT Amount
10. Gross Pay
11. Performance Allowance (editable)
12. Gross + Allowance
13. Cash Advance (editable)
14. SSS Deduction
15. PhilHealth Deduction
16. PagIBIG Deduction
17. SSS Loan (editable)
18. Total Deductions
19. Take Home Pay
20. Payslip button
21. Payment Status dropdown

#### D. Editable Fields with AJAX Save
- **Performance Allowance** (Lines 813-846): Saves to `update_allowance.php`
- **Cash Advance** (Lines 405-414): Input field, manual entry
- **SSS Loan** (Lines 855-893): Saves to `update_loan.php`
- **Payment Status** (Lines 740-782): Saves to `update_payment_status.php`

#### E. Payslip Features
- **Individual Payslip Modal** (Lines 514-536): Detailed earnings/deductions breakdown
- **Bundle Print** (Lines 538-562): Multi-page print for selected employees
- **Print Styling**: Optimized 4x7 inch thermal receipt format
- **Signature Section**: Employee and authorized signatures

#### F. Export Functionality
- Excel export (Lines 192-194)
- Uses SheetJS library

### 2.4 Supporting File: function/report.php

#### Key Logic Components
- **Date Range Calculation** (Lines 63-153): Complex week boundary logic excluding Sundays
- **Payroll Data Aggregation**: Merges `daily_payroll_reports` + `attendance` + `overtime_requests`
- **Attendance Merging** (Lines 519-697): 15-minute threshold for merging multiple clock-ins
- **Transfer Scenario Handling**: Employees working at 2 branches same day
- **Government Deductions**: SSS (₱450), PhilHealth (₱250), PagIBIG (₱200) - prorated weekly

#### Database Queries

**Payroll Reports (Primary Source)**
```sql
SELECT dpr.employee_id, dpr.report_date, dpr.days_worked, dpr.total_hours,
       dpr.daily_rate, dpr.basic_pay, dpr.ot_hours, dpr.performance_allowance,
       dpr.gross_pay, dpr.sss_deduction, dpr.philhealth_deduction, 
       dpr.pagibig_deduction, dpr.sss_loan, dpr.total_deductions, dpr.take_home_pay
FROM daily_payroll_reports dpr
JOIN employees e ON dpr.employee_id = e.id
LEFT JOIN branches b ON dpr.branch_id = b.id
WHERE dpr.report_date BETWEEN ? AND ?
```

**Attendance (Fallback Source)**
```sql
SELECT a.employee_id, a.attendance_date, a.status, a.branch_name, 
       a.time_in, a.time_out, a.total_ot_hrs, e.daily_rate
FROM attendance a
JOIN employees e ON a.employee_id = e.id
WHERE a.attendance_date BETWEEN ? AND ?
AND a.is_voided = 0
```

**Approved Overtime (Authoritative)**
```sql
SELECT employee_id, request_date, requested_hours as ot_hours
FROM overtime_requests
WHERE request_date BETWEEN ? AND ?
AND status IN ('approved', 'pre-approved')
```

---

## 3. DATABASE SCHEMA REVIEW

### 3.1 Core Tables for Audit & Payroll

#### attendance (Lines 411-442 in SQL dump)
| Field | Type | Description |
|-------|------|-------------|
| id | int PK | Auto-increment |
| employee_id | int FK | References employees.id |
| status | enum | Present, Late, Absent, System |
| branch_name | varchar(50) | Location of clock-in |
| attendance_date | date | Date of attendance |
| time_in | datetime | Clock-in timestamp |
| clock_in_lat | decimal(10,8) | GPS latitude |
| clock_in_lng | decimal(11,8) | GPS longitude |
| time_out | datetime | Clock-out timestamp |
| clock_out_lat | decimal(10,8) | GPS latitude |
| clock_out_lng | decimal(11,8) | GPS longitude |
| is_voided | tinyint(1) | Void flag (0=active, 1=voided) |
| void_reason | text | Reason for voiding |
| voided_by | int | Admin user ID |
| voided_at | timestamp | Void timestamp |
| total_ot_hrs | varchar(10) | Overtime hours |

#### daily_payroll_reports (Lines 667-703)
| Field | Type | Description |
|-------|------|-------------|
| id | int PK | Auto-increment |
| employee_id | int FK | References employees.id |
| report_date | date | Payroll date |
| week_number | int | Week 1-5 |
| branch_id | int FK | References branches.id |
| days_worked | decimal(4,1) | Days count |
| total_hours | decimal(8,2) | Hours worked |
| daily_rate | decimal(10,2) | Rate per day |
| basic_pay | decimal(10,2) | Base pay |
| ot_hours | decimal(6,2) | Overtime hours |
| ot_amount | decimal(10,2) | OT pay |
| performance_allowance | decimal(10,2) | Allowance |
| gross_pay | decimal(10,2) | Total earnings |
| sss_deduction | decimal(10,2) | SSS contribution |
| philhealth_deduction | decimal(10,2) | PhilHealth |
| pagibig_deduction | decimal(10,2) | PagIBIG |
| sss_loan | decimal(10,2) | Loan deduction |
| total_deductions | decimal(10,2) | Sum of deductions |
| take_home_pay | decimal(10,2) | Net pay |
| status | varchar(20) | Pending, Approved, etc. |

#### employees (Lines 857-880)
| Field | Type | Description |
|-------|------|-------------|
| id | int PK | Auto-increment |
| employee_code | varchar(50) | Unique code (E0001, etc.) |
| first_name | varchar(100) | First name |
| last_name | varchar(100) | Last name |
| email | varchar(100) | Email address |
| password_hash | varchar(255) | Password |
| position | varchar(50) | Worker, Admin, Engineer, etc. |
| status | varchar(50) | Active, Inactive |
| daily_rate | decimal(10,2) | Default: 600.00 |
| branch_id | int FK | Assigned branch |
| has_deduction | tinyint(1) | Eligible for gov't deductions |
| sss_loan | decimal(10,2) | Default loan amount |

#### branches (Lines 556-573)
| Field | Type | Description |
|-------|------|-------------|
| id | int PK | Auto-increment |
| branch_name | varchar(50) | Location name |
| branch_address | varchar(55) | Address |
| lat | varchar(20) | Latitude for geofence |
| long | varchar(20) | Longitude for geofence |
| geofence_radius_meters | int | Default: 200-300m |
| is_active | tinyint | Active status |

#### overtime_requests
| Field | Type | Description |
|-------|------|-------------|
| id | int PK | Auto-increment |
| employee_id | int FK | Requester |
| request_date | date | Overtime date |
| requested_hours | decimal(5,2) | Hours requested |
| status | enum | Pending, Pre-Approved, Approved, Rejected |
| overtime_reason | text | Justification |

### 3.2 Database Triggers

**Validate Attendance Before DPR Insert**
```sql
BEFORE INSERT ON daily_payroll_reports
-- Prevents creating payroll records without valid attendance
```

### 3.3 Indexes

| Table | Index | Purpose |
|-------|-------|---------|
| attendance | idx_attendance_employee_date | Employee + date lookup |
| attendance | idx_attendance_location | GPS coordinates |
| attendance | idx_voided | Filter voided records |
| daily_payroll_reports | unique_emp_date_branch | Prevent duplicates |
| daily_payroll_reports | idx_report_date | Date filtering |

---

## 4. PROPOSED NEW TABLES (For Recreation)

### 4.1 audit_logs Table (Suggested Enhancement)
```sql
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(50) NOT NULL,
    record_id INT NOT NULL,
    action VARCHAR(20) NOT NULL, -- INSERT, UPDATE, DELETE, VOID
    old_values JSON,
    new_values JSON,
    performed_by INT NOT NULL,
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    INDEX idx_table_record (table_name, record_id),
    INDEX idx_performed_at (performed_at)
) ENGINE=InnoDB;
```

### 4.2 report_templates Table (For Report Customization)
```sql
CREATE TABLE report_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(100) NOT NULL,
    report_type ENUM('weekly', 'monthly', 'custom') NOT NULL,
    columns JSON NOT NULL, -- Which columns to display
    filters JSON, -- Default filter settings
    created_by INT NOT NULL,
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
```

### 4.3 payroll_periods Table (For Period Management)
```sql
CREATE TABLE payroll_periods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    period_type ENUM('weekly', 'monthly') NOT NULL,
    year INT NOT NULL,
    month INT NOT NULL,
    week_number INT, -- NULL for monthly
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_closed TINYINT(1) DEFAULT 0,
    closed_by INT,
    closed_at TIMESTAMP NULL,
    UNIQUE KEY unique_period (year, month, week_number)
) ENGINE=InnoDB;
```

---

## 5. FILE DEPENDENCIES

### 5.1 audit.php Dependencies
```
audit.php
├── ../conn/db_connection.php
├── ../functions.php
├── sidebar.php (UI component)
├── export_attendance_excel.php (Export functionality)
├── export_employee_individual.php (Individual export)
├── individual_report_selector.php (Report selection)
├── audit_report_selector.php (Report generation)
├── api/get_vapid_key.php (Push notifications)
├── api/save_push_subscription.php (Push notifications)
└── ../sw.js (Service Worker)
```

### 5.2 weekly_report.php Dependencies
```
weekly_report.php
├── ../conn/db_connection.php
├── ../functions.php
├── function/report.php (Core payroll logic)
├── sidebar.php (UI component)
├── css/report.css
├── js/report.js
├── js/theme.js
├── update_allowance.php (AJAX endpoint)
├── update_loan.php (AJAX endpoint)
└── update_payment_status.php (AJAX endpoint)
```

---

## 6. SECURITY CONSIDERATIONS

### 6.1 Current Security Measures
- Session-based authentication
- Role-based access control (RBAC)
- Rate limiting on audit.php
- Prepared statements for SQL queries
- Input sanitization with `htmlspecialchars()`
- Output encoding

### 6.2 Potential Improvements for Recreation
- CSRF tokens for all state-changing operations
- Row-level security for branch-specific data
- Audit logging for all void operations
- Input validation layers
- API rate limiting expansion

---

## 7. PERFORMANCE CONSIDERATIONS

### 7.1 Current Bottlenecks
- Large UNION queries in audit.php
- Multiple nested queries for summary counts
- Client-side calculation of some totals
- No server-side caching

### 7.2 Recommended Improvements
- Database views for complex aggregations
- Redis/Memcached for calendar data
- Pagination for all list views
- Lazy loading for calendar modals
- Background processing for exports

---

## 8. RECREATION CHECKLIST

### Phase 1: Foundation
- [ ] Set up database schema (existing + new tables)
- [ ] Create base PHP classes/models
- [ ] Implement authentication middleware
- [ ] Set up API routing structure

### Phase 2: Audit Module
- [ ] Calendar component with data highlighting
- [ ] Attendance listing with pagination
- [ ] Search and filter functionality
- [ ] Void functionality with audit logging
- [ ] Export functionality
- [ ] Individual/branch calendar modals

### Phase 3: Payroll Module
- [ ] Date range/week/month selectors
- [ ] Branch filtering with pagination
- [ ] Payroll calculation engine
- [ ] Editable fields with AJAX
- [ ] Payslip generation
- [ ] Bundle print functionality

### Phase 4: Enhancement
- [ ] Push notification system
- [ ] Advanced reporting
- [ ] Dashboard analytics
- [ ] Mobile responsiveness

---

*Document generated for recreation planning purposes.*
*Review completed on April 13, 2026*
