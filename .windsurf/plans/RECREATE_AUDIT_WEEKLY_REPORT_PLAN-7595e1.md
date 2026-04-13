# Recreation Plan: Audit.php and Weekly_Report.php

A structured plan for recreating the attendance audit and payroll reporting modules with modern architecture and enhanced features.

---

## Phase 1: Database Schema Setup

### 1.1 Existing Tables to Verify
- [ ] `attendance` - Verify all columns including void fields
- [ ] `daily_payroll_reports` - Verify triggers are in place
- [ ] `employees` - Check has_deduction and sss_loan columns
- [ ] `branches` - Verify geofence columns
- [ ] `overtime_requests` - Status enum values
- [ ] `cash_advances` - For payroll deductions

### 1.2 New Tables to Create

#### audit_logs (NEW)
```sql
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(50) NOT NULL,
    record_id INT NOT NULL,
    action ENUM('INSERT', 'UPDATE', 'DELETE', 'VOID', 'RESTORE') NOT NULL,
    old_values JSON,
    new_values JSON,
    performed_by INT NOT NULL,
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    INDEX idx_table_record (table_name, record_id),
    INDEX idx_performed_at (performed_at),
    INDEX idx_action (action)
) ENGINE=InnoDB;
```

#### report_templates (NEW)
```sql
CREATE TABLE report_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(100) NOT NULL,
    report_type ENUM('weekly', 'monthly', 'custom', 'audit') NOT NULL,
    columns JSON NOT NULL,
    default_filters JSON,
    sort_order JSON,
    created_by INT NOT NULL,
    is_system_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
```

#### payroll_periods (NEW)
```sql
CREATE TABLE payroll_periods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    period_type ENUM('weekly', 'monthly') NOT NULL,
    year INT NOT NULL,
    month INT NOT NULL,
    week_number INT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_closed TINYINT(1) DEFAULT 0,
    closed_by INT,
    closed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_period (year, month, week_number, period_type)
) ENGINE=InnoDB;
```

#### attendance_audit_cache (NEW - for performance)
```sql
CREATE TABLE attendance_audit_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cache_date DATE NOT NULL,
    filter_hash VARCHAR(32) NOT NULL,
    employee_count INT DEFAULT 0,
    present_count INT DEFAULT 0,
    late_count INT DEFAULT 0,
    absent_count INT DEFAULT 0,
    voided_count INT DEFAULT 0,
    cached_data JSON,
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cache_date (cache_date),
    INDEX idx_filter_hash (filter_hash)
) ENGINE=InnoDB;
```

---

## Phase 2: Core Architecture

### 2.1 Base Classes Structure
```
/classes
├── Database.php (existing - verify compatibility)
├── Auth.php (authentication middleware)
├── BaseModel.php (base ORM-style class)
├── Validator.php (input validation)
└── Logger.php (audit logging)
```

### 2.2 Model Classes
```
/models
├── Employee.php
├── Attendance.php
├── PayrollReport.php
├── Branch.php
├── OvertimeRequest.php
└── AuditLog.php (new)
```

### 2.3 API Controllers
```
/api/v1
├── AuthController.php
├── AttendanceController.php
├── PayrollController.php
├── ReportController.php
└── ExportController.php
```

---

## Phase 3: Audit Module Recreation

### 3.1 Backend Components

#### AttendanceController Methods
- [ ] `getCalendarData($month, $year)` - Days with attendance counts
- [ ] `getAttendanceList($filters, $pagination)` - Paginated records
- [ ] `getAttendanceSummary($date, $filter)` - Stats for dashboard
- [ ] `voidAttendance($id, $reason, $adminId)` - Void with logging
- [ ] `searchAttendance($query, $type, $filters)` - Search functionality
- [ ] `exportAttendance($format, $filters)` - Excel/CSV export

#### API Endpoints
```
GET  /api/v1/attendance/calendar?month=&year=
GET  /api/v1/attendance/list?date=&filter=&page=&search=
GET  /api/v1/attendance/summary?date=&filter=
POST /api/v1/attendance/:id/void
GET  /api/v1/attendance/export?format=excel&start_date=&end_date=
```

### 3.2 Frontend Components

#### New File Structure
```
/employee/audit-v2/
├── index.php (main entry)
├── components/
│   ├── Calendar.php
│   ├── AttendanceTable.php
│   ├── FilterBar.php
│   ├── SearchBox.php
│   └── Pagination.php
├── modals/
│   ├── VoidModal.php
│   ├── EmployeeCalendarModal.php
│   └── BranchCalendarModal.php
├── js/
│   ├── audit.js
│   ├── calendar.js
│   └── export.js
└── css/
    └── audit-v2.css
```

#### Features to Implement
- [ ] Interactive calendar with data indicators
- [ ] Real-time status filtering
- [ ] Advanced search (debounced)
- [ ] Bulk void operations (optional)
- [ ] Export with progress indicator
- [ ] Individual employee drill-down
- [ ] Branch-level view
- [ ] Push notification integration (Super Admin)

### 3.3 Rate Limiting Strategy
- [ ] Implement Redis-based rate limiting
- [ ] Different limits per role (Admin vs Super Admin)
- [ ] Separate limits for export operations

---

## Phase 4: Payroll Module Recreation

### 4.1 Backend Components

#### PayrollController Methods
- [ ] `getPayrollData($viewType, $dateRange, $branch)`
- [ ] `calculatePayroll($employeeIds, $dateRange)`
- [ ] `updateAllowance($employeeId, $amount, $period)`
- [ ] `updateLoan($employeeId, $amount, $period)`
- [ ] `updatePaymentStatus($employeeId, $status, $period)`
- [ ] `generatePayslip($employeeId, $period)`
- [ ] `exportPayroll($format, $filters)`
- [ ] `getPayrollSummary($dateRange)`

#### Report Engine (/classes/ReportEngine.php)
- [ ] Payroll calculation logic
- [ ] Attendance merging (15-min threshold)
- [ ] Transfer scenario handling
- [ ] Government deduction proration
- [ ] Overtime integration

#### API Endpoints
```
GET  /api/v1/payroll?type=weekly|monthly|range&start=&end=&branch=
POST /api/v1/payroll/allowance
POST /api/v1/payroll/loan
POST /api/v1/payroll/payment-status
GET  /api/v1/payroll/payslip/:employeeId?period=
GET  /api/v1/payroll/export?format=excel
GET  /api/v1/payroll/summary
```

### 4.2 Frontend Components

#### New File Structure
```
/employee/payroll-v2/
├── index.php
├── components/
│   ├── ViewToggle.php (weekly/monthly/range)
│   ├── BranchFilter.php
│   ├── PayrollTable.php
│   ├── SummaryCards.php
│   └── Pagination.php
├── modals/
│   ├── PayslipModal.php
│   ├── BundlePrintModal.php
│   └── EditFieldModal.php
├── js/
│   ├── payroll.js
│   ├── calculations.js
│   ├── payslip.js
│   └── export.js
└── css/
    └── payroll-v2.css
```

#### Features to Implement
- [ ] Week/month/range view toggle
- [ ] Branch filter with pagination
- [ ] Editable inline fields with auto-save
- [ ] Real-time calculation updates
- [ ] Individual payslip modal
- [ ] Bundle print (multi-page)
- [ ] Payment status tracking
- [ ] Excel export
- [ ] Payroll summary dashboard

### 4.3 Calculation Engine
- [ ] Days worked calculation (8+ hours = 1 day)
- [ ] Hours aggregation with merging logic
- [ ] OT rate calculation (daily_rate / 8)
- [ ] Government deductions proration
- [ ] Transfer scenario (0.5 day per branch)
- [ ] Net pay calculation with validation

---

## Phase 5: Enhanced Features

### 5.1 Audit Logging System
- [ ] Log all void operations
- [ ] Log payment status changes
- [ ] Log allowance/loan updates
- [ ] View audit trail in UI
- [ ] Export audit logs

### 5.2 Caching Layer
- [ ] Redis for calendar data
- [ ] Cache payroll calculations
- [ ] Cache filter results
- [ ] Auto-expire caches on data changes

### 5.3 Advanced Reporting
- [ ] Custom report templates
- [ ] Scheduled report generation
- [ ] Email report delivery
- [ ] Report comparison (period-over-period)

### 5.4 Mobile Responsiveness
- [ ] Mobile-optimized calendar
- [ ] Collapsible tables
- [ ] Touch-friendly controls
- [ ] Simplified mobile views

---

## Phase 6: Testing & Deployment

### 6.1 Testing Checklist
- [ ] Unit tests for calculation engine
- [ ] API endpoint testing
- [ ] UI/UX testing
- [ ] Performance testing (1000+ records)
- [ ] Security testing (SQL injection, XSS)
- [ ] Mobile responsiveness testing

### 6.2 Migration Strategy
- [ ] Parallel deployment (old + new)
- [ ] Data migration scripts
- [ ] Rollback procedures
- [ ] User training documentation

### 6.3 Documentation
- [ ] API documentation
- [ ] User manual updates
- [ ] Admin configuration guide
- [ ] Troubleshooting guide

---

## Implementation Timeline (Estimated)

| Phase | Duration | Dependencies |
|-------|----------|--------------|
| Phase 1: Database | 1-2 days | None |
| Phase 2: Architecture | 2-3 days | Phase 1 |
| Phase 3: Audit Module | 5-7 days | Phase 2 |
| Phase 4: Payroll Module | 7-10 days | Phase 2 |
| Phase 5: Enhancements | 5-7 days | Phase 3, 4 |
| Phase 6: Testing | 3-5 days | Phase 3, 4, 5 |
| **Total** | **23-34 days** | - |

---

## Technical Stack Recommendations

### Backend
- **PHP 8.3+** (existing)
- **MySQL 8.0+** (existing)
- **PDO** for database abstraction
- **Composer** for dependency management

### Frontend
- **Vanilla JS / jQuery** (maintain consistency)
- **TailwindCSS** (existing)
- **Chart.js** (for analytics)
- **SheetJS** (for Excel export)

### Infrastructure
- **Redis** (for caching)
- **Service Worker** (for push notifications)
- **Web Push API** (for notifications)

---

## Notes for Developer

1. **Preserve Existing Data**: Ensure all existing attendance and payroll data remains intact
2. **Backward Compatibility**: Maintain existing API endpoints during transition
3. **Incremental Deployment**: Deploy features incrementally, not all at once
4. **User Feedback**: Gather user feedback during beta testing
5. **Performance Monitoring**: Monitor query performance with New Relic or similar

---

*Plan created for recreation of audit.php and weekly_report.php*
*Generated: April 13, 2026*
*Status: Awaiting approval to proceed*
