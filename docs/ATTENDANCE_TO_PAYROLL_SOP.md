# Standard Operating Procedure (SOP): Attendance to Payroll Process

## Document Information
- **Document Title:** Attendance to Payroll Process SOP
- **Version:** 1.0
- **Effective Date:** April 2026
- **Review Cycle:** Quarterly
- **Owner:** HR/Payroll Department

---

## 1. Purpose and Scope

### 1.1 Purpose
This SOP establishes the standardized procedures for capturing employee attendance data and processing it into accurate payroll calculations. It ensures consistency, compliance, and timely payment to all employees.

### 1.2 Scope
This procedure applies to:
- All active employees across all branches
- HR and Payroll administrators
- Branch managers reviewing attendance
- System administrators managing automated processes

### 1.3 Payroll Cycle
- **Weekly Payroll:** Calculated every Friday (Weeks 1-3)
- **Monthly Payroll:** Calculated for Week 4/5 (end of month)
- **Special Branch Rules:** Branch 33 follows monthly deduction schedule

---

## 2. Roles and Responsibilities

| Role | Responsibilities |
|------|----------------|
| **Employee** | Clock in/out using approved methods (QR, Face Recognition, or Manual); Submit overtime requests |
| **Branch Manager** | Verify daily attendance; Approve overtime requests; Review branch reports |
| **HR Admin** | Mark attendance for employees who forgot to clock; Process corrections; Handle disputes |
| **Payroll Admin** | Run payroll calculations; Verify deductions; Generate payroll reports; Process payments |
| **System Admin** | Monitor cron jobs; Verify automated calculations; Handle system issues |
| **Super Admin/Developer** | System configuration; Access all functions; Override when necessary |

---

## 3. Attendance Recording Methods

### 3.1 Primary Clock-In Methods

#### 3.1.1 QR Code Scanning (`qr_clock_api.php`)
- Employee scans their unique QR code at branch location
- System validates geofence location
- Records time_in or time_out automatically
- **Use Case:** Standard method for most employees

#### 3.1.2 Face Recognition (`verify_face_api.php`)
- Biometric verification for enhanced security
- Requires prior enrollment (`enroll_face_api.php`)
- **Use Case:** High-security areas or backup verification

#### 3.1.3 Manual Admin Entry (`employee/attendance.php`)
- HR/Admin marks attendance for employees who forgot to clock
- Admin selects: **Present** or **Absent**
- **Use Case:** Corrections, field employees, system failures

### 3.2 Attendance Status Definitions

| Status | Definition | Payroll Impact |
|--------|------------|----------------|
| **Present** | Employee clocked in and out normally | Full day's pay |
| **Late** | Employee arrived after scheduled time | Full day's pay (marked as present for pay) |
| **Absent** | Employee did not report to work | No pay for the day |
| **On Leave** | Approved leave day | Pay based on leave type |
| **Half-Day** | Partial attendance (transfers) | 0.5 day pay per branch |

---

## 4. Daily Attendance Workflow

### 4.1 Employee Daily Process

```
1. Arrive at branch location
2. Clock IN using QR scan or Face Recognition
   └── System validates geofence
   └── Records time_in with GPS coordinates
3. Work scheduled hours
4. Clock OUT using same method
   └── Records time_out
   └── Calculates total hours
5. System auto-calculates OT if applicable
```

### 4.2 Branch Transfer Handling
When an employee works at multiple branches in one day:
- System splits the day (0.5 day per branch)
- Each branch is tracked separately in payroll
- Total pay = Sum of all branch contributions

### 4.3 Daily Payroll Calculation (Automated)
- **Cron Job:** `daily_payroll_calculation.php`
- **Schedule:** Daily at 12:00 AM (midnight)
- **Function:** Calculates payroll for the previous day
- **Output:** Saves to `daily_payroll_reports` table

---

## 5. Overtime Management

### 5.1 Overtime Types

| Type | Hours | Rate Multiplier | Approval Required |
|------|-------|-----------------|-------------------|
| **Regular OT** | After 8 hours | 1.25x (hourly rate) | Yes |
| **Night Differential** | 10 PM - 6 AM | Additional 10% | Yes |
| **Rest Day OT** | Rest days | 1.3x | Yes |
| **Holiday OT** | Holidays | 2.0x | Yes |

### 5.2 Overtime Request Workflow

```
1. Employee or Manager submits OT request
   └── Via: employee/overtime.php
   └── Required: Date, hours requested, reason
2. Manager/Admin reviews request
3. Approve or Reject
   └── Approved: OT hours added to attendance record
   └── Rejected: Reason logged, employee notified
4. Approved OT auto-calculated in payroll
```

### 5.3 OT Calculation Formula
```
Hourly Rate = Daily Rate ÷ 8
OT Pay = Total OT Hours × Hourly Rate
```

---

## 6. Weekly Payroll Process

### 6.1 Weekly Cycle Timeline

| Day | Action | Responsible |
|-----|--------|-------------|
| **Monday-Thursday** | Daily attendance recording | All Employees |
| **Friday 12:00 AM** | Automated weekly payroll calculation | System |
| **Friday Morning** | Payroll review by admin | Payroll Admin |
| **Friday Afternoon** | Payment processing | Finance |

### 6.2 Weekly Payroll Calculation (Automated)
- **Cron Job:** `weekly_payroll_calculation.php`
- **Schedule:** Every Friday at 12:00 AM
- **Input:** Attendance records for Monday-Friday
- **Output:** Saves to `weekly_payroll_reports` table

### 6.3 Deduction Schedule by Branch

| Branch Type | SSS | PhilHealth | Pag-IBIG | Schedule |
|-------------|-----|------------|----------|----------|
| **Branch 33** | Yes | Yes | Yes | Week 4 only (Monthly) |
| **All Others** | Yes | Yes | Yes | Weeks 1-3 (Weekly) |
| **Security Guards** | No | No | No | Never |

### 6.4 Standard Deductions (Philippines)

| Deduction | Calculation | Cap |
|-----------|-------------|-----|
| **SSS** | 4.5% of gross pay | ₱1,125 |
| **PhilHealth** | 3.5% of gross pay | ₱2,450 |
| **Pag-IBIG** | Fixed amount | ₱100 |

---

## 7. Monthly Payroll Process

### 7.1 Month-End Timeline

| Date Range | Action |
|------------|--------|
| **Days 1-28/31** | Daily attendance recording |
| **Last Friday** | Final weekly calculation (Week 4 or 5) |
| **Month End** | Monthly reconciliation |
| **1st of Next Month** | Payment disbursement |

### 7.2 Monthly Reconciliation Checklist

- [ ] Verify all attendance records are complete
- [ ] Check for any unmarked days
- [ ] Review all approved overtime
- [ ] Validate all deductions (SSS, PhilHealth, Pag-IBIG)
- [ ] Process cash advance deductions
- [ ] Generate monthly payroll report
- [ ] Obtain management approval
- [ ] Disburse payments

---

## 8. Payroll Reports and Review

### 8.1 Available Reports

| Report | Location | Purpose |
|--------|----------|---------|
| **Weekly Payroll** | `employee/payroll.php` | Week-by-week pay calculation |
| **Monthly Payroll** | `employee/payroll.php?view=monthly` | Full month summary |
| **Deployment Report** | `employee/weekly_report.php` | Branch deployment & attendance |
| **Individual Report** | `employee/individual_report_selector.php` | Single employee history |
| **Overtime Report** | `employee/overtime.php` | All OT hours and approvals |
| **Audit Report** | `employee/audit.php` | Changes and corrections log |

### 8.2 Report Review Process

```
1. Generate report for period (weekly/monthly)
2. Check totals and verify calculations
3. Review flagged discrepancies
4. Compare with attendance records
5. Obtain sign-off from department head
6. Archive report for compliance
```

---

## 9. Handling Discrepancies and Corrections

### 9.1 Common Discrepancy Types

| Issue | Cause | Resolution |
|-------|-------|------------|
| **Missing Attendance** | Employee forgot to clock | Admin marks via attendance.php |
| **Wrong Branch** | Geofence error | Edit attendance record |
| **Incorrect OT** | System or manual error | Update OT hours in attendance |
| **Wrong Rate** | Employee data error | Update employee daily_rate |
| **Missing Deductions** | has_deduction flag = 0 | Update employee record |

### 9.2 Correction Workflow

```
1. Identify discrepancy via audit or report
2. Log issue with employee name, date, and details
3. Make correction in source system
4. Document reason for correction
5. Re-run payroll calculation if needed
6. Notify affected employee if necessary
```

### 9.3 Audit Trail
All changes are logged in:
- **Activity Logs:** `employee/activity_logs.php`
- **Audit Report:** `employee/audit.php`
- **Cron Logs:** `employee/cron/*.log`

---

## 10. Automated System Processes (Cron Jobs)

### 10.1 Daily Processes

| Script | Schedule | Purpose |
|--------|----------|---------|
| `daily_payroll_calculation.php` | Daily 12:00 AM | Calculate previous day's payroll |
| `check_daily_table.php` | Daily | Verify table structure |
| `fix_attendance_discrepancy.php` | As needed | Fix data inconsistencies |

### 10.2 Weekly Processes

| Script | Schedule | Purpose |
|--------|----------|---------|
| `weekly_payroll_calculation.php` | Friday 12:00 AM | Calculate weekly payroll |
| `generate_daily_payroll.php` | As configured | Generate payroll reports |

### 10.3 Monitoring Automated Jobs

```
1. Check cron logs in employee/cron/ directory
2. Verify successful execution messages
3. Review any ERROR entries
4. Address failures immediately
5. Document any manual interventions
```

---

## 11. Key Database Tables

### 11.1 Core Tables

| Table | Purpose | Key Fields |
|-------|---------|------------|
| **attendance** | Daily attendance records | employee_id, attendance_date, status, time_in, time_out, total_ot_hrs |
| **employees** | Employee master data | id, daily_rate, position, branch_id, has_deduction, status |
| **branches** | Branch information | id, branch_name, is_active |
| **daily_payroll_reports** | Daily calculated payroll | employee_id, report_date, days_worked, gross_pay, deductions, take_home_pay |
| **weekly_payroll_reports** | Weekly calculated payroll | employee_id, report_year, report_month, week_number, all pay fields |
| **overtime_requests** | OT approval tracking | employee_id, request_date, hours, status, approved_by |
| **performance_adjustments** | Bonuses and adjustments | employee_id, bonus_amount, adjustment_date |

### 11.2 Payroll Calculation Data Flow

```
attendance (raw data)
    ↓
[Daily Cron Job]
    ↓
daily_payroll_reports (daily calculated)
    ↓
[Weekly Cron Job - Aggregation]
    ↓
weekly_payroll_reports (weekly summary)
    ↓
[Review & Approval]
    ↓
Payment Processing
```

---

## 12. Security and Access Control

### 12.1 Role-Based Access

| Function | Admin | Super Admin | Developer |
|----------|-------|-------------|-----------|
| View Attendance | ✓ | ✓ | ✓ |
| Mark Attendance | ✓ | ✓ | ✓ |
| Edit Past Attendance | ✗ | ✓ | ✓ |
| Run Payroll | ✓ | ✓ | ✓ |
| Edit Deductions | ✗ | ✓ | ✓ |
| Configure System | ✗ | ✗ | ✓ |

### 12.2 Data Protection
- All payroll data is encrypted in transit (HTTPS)
- Database access restricted to application only
- Audit logs maintained for all changes
- Backup procedures run daily

---

## 13. Emergency Procedures

### 13.1 System Outage

```
1. Document outage time and affected employees
2. Switch to manual attendance log (paper/Excel)
3. When system recovers, batch-enter missed records
4. Verify all data is captured
5. Re-run payroll calculation
```

### 13.2 Payroll Calculation Errors

```
1. Identify affected employees and incorrect amounts
2. Stop payment processing immediately
3. Correct source data (attendance/rates)
4. Re-run affected payroll calculations
5. Verify corrections
6. Process corrected payments
7. Document incident and resolution
```

---

## 14. Compliance Requirements

### 14.1 Record Retention
- **Attendance Records:** 5 years minimum
- **Payroll Reports:** 7 years minimum
- **Audit Logs:** 10 years minimum

### 14.2 Required Documentation
- [ ] Signed attendance records (monthly)
- [ ] Approved overtime forms
- [ ] Payroll register with signatures
- [ ] Proof of payment (payslips, bank records)
- [ ] Government remittance receipts

---

## 15. Quick Reference: Key File Locations

| File | Purpose |
|------|---------|
| `employee/payroll.php` | Main payroll viewing and calculation |
| `employee/attendance.php` | Manual attendance marking |
| `employee/weekly_report.php` | Deployment and attendance reports |
| `employee/overtime.php` | Overtime management |
| `employee/audit.php` | Audit trail and corrections |
| `employee/cron/daily_payroll_calculation.php` | Daily automated calculation |
| `employee/cron/weekly_payroll_calculation.php` | Weekly automated calculation |
| `functions.php` | Shared payroll functions |

---

## 16. Contact and Support

| Issue | Contact |
|-------|---------|
| System Access | IT Administrator |
| Payroll Questions | Payroll Manager |
| Attendance Disputes | HR Department |
| Technical Issues | System Developer |
| Emergency Override | Super Admin |

---

## 17. Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | April 2026 | System Administrator | Initial SOP creation |

---

## Appendix A: Payroll Calculation Formula Summary

### Basic Pay
```
Basic Pay = Days Worked × Daily Rate
```

### Overtime Pay
```
Hourly Rate = Daily Rate ÷ 8
OT Pay = Total OT Hours × Hourly Rate
```

### Gross Pay
```
Gross Pay = Basic Pay + OT Pay + Performance Bonus + Allowances
```

### Deductions
```
SSS = min(Gross Pay × 0.045, 1125)
PhilHealth = min(Gross Pay × 0.035, 2450)
Pag-IBIG = 100 (fixed)
Total Deductions = SSS + PhilHealth + Pag-IBIG + SSS Loan + CA Deduction
```

### Net Pay
```
Net Pay = Gross Pay + Allowances - Total Deductions
```

---

**END OF SOP DOCUMENT**
