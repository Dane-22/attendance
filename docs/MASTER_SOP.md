# JAJR Attendance & Payroll System - Master Standard Operating Procedure (SOP)

## Document Control

| Field | Value |
|-------|-------|
| **Document Title** | Master Standard Operating Procedure |
| **System** | JAJR Attendance & Payroll System |
| **Version** | 1.0 |
| **Effective Date** | April 2026 |
| **Review Cycle** | Quarterly |
| **Owner** | System Administrator / HR Director |
| **Approved By** | [To be filled] |

---

## Table of Contents

1. [System Overview & Architecture](#1-system-overview--architecture)
2. [System Access & Security SOP](#2-system-access--security-sop)
3. [Employee Lifecycle Management SOP](#3-employee-lifecycle-management-sop)
4. [Daily Attendance Operations SOP](#4-daily-attendance-operations-sop)
5. [Payroll Processing SOP](#5-payroll-processing-sop)
6. [Branch & Location Management SOP](#6-branch--location-management-sop)
7. [Overtime Management SOP](#7-overtime-management-sop)
8. [Leave Management SOP](#8-leave-management-sop)
9. [Cash Advance & Loans SOP](#9-cash-advance--loans-sop)
10. [Billing & Cost Allocation SOP](#10-billing--cost-allocation-sop)
11. [Transfer Management SOP](#11-transfer-management-sop)
12. [Reporting & Audit SOP](#12-reporting--audit-sop)
13. [System Maintenance & Backup SOP](#13-system-maintenance--backup-sop)
14. [Emergency Procedures](#14-emergency-procedures)
15. [Appendices](#15-appendices)

---

## 1. System Overview & Architecture

### 1.1 Purpose
This Master SOP defines the complete operational procedures for the JAJR Attendance & Payroll System, covering all modules from employee onboarding to payroll disbursement, ensuring standardized, compliant, and efficient operations across all departments.

### 1.2 System Scope

**Core Modules:**
- Employee Management (HR)
- Attendance Tracking (Multi-method)
- Payroll Processing (Automated & Manual)
- Branch & Location Management
- Overtime Management
- Leave Management
- Cash Advance & Loans
- Billing & Cost Allocation
- Transfer Management
- Reporting & Analytics
- System Administration

### 1.3 Technology Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│  CLIENT LAYER: Web Browser, Mobile Web, QR Scanner               │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  APPLICATION LAYER: PHP 8.3+, Apache 2.4+, REST APIs              │
│  - Session Management                                          │
│  - Role-based Access Control                                   │
│  - Geolocation & Geofencing                                     │
│  - Face Recognition Integration                               │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  DATA LAYER: MySQL 8.4+, File Storage                          │
│  - Employee Records                                             │
│  - Attendance Logs                                              │
│  - Payroll Reports                                              │
│  - Activity Audit Trail                                         │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  INTEGRATIONS: Procurement API, Web Push, MapLibre            │
└─────────────────────────────────────────────────────────────────┘
```

### 1.4 User Role Hierarchy

| Role | Level | Access Scope |
|------|-------|--------------|
| **Developer** | 1 - Highest | Full system access, configuration, debugging |
| **Super Admin** | 2 | All admin functions + approvals + system settings |
| **Admin** | 3 | Employee management, payroll review, reports |
| **Engineer** | 4 | Site management, transfers, attendance |
| **Employee** | 5 | Personal attendance, requests, profile |
| **Security Guard** | 6 | Basic attendance only, no deductions |

---

## 2. System Access & Security SOP

### 2.1 Account Creation Procedure

**Responsible:** Super Admin / HR Admin
**Frequency:** Per new employee onboarding

**Steps:**
1. Receive new hire documentation from HR
2. Verify employee details (name, position, branch assignment, daily rate)
3. Navigate to `employee/employees.php`
4. Click "Add Employee" (Super Admin only)
5. Generate unique employee code (auto-format: E0001, ENG-YYYY-XXXX)
6. Input required fields:
   - First Name, Last Name, Middle Name
   - Email address
   - Position (dropdown selection)
   - Daily Rate
   - Branch assignment
   - Deduction flag (default: ON)
7. Generate temporary password
8. Save record
9. Generate QR code (auto-generated)
10. Communicate login credentials securely to employee
11. Log activity: `logActivity($db, 'Employee Created', "Created employee $code")`

**Quality Check:**
- [ ] Employee code is unique
- [ ] Email format is valid
- [ ] Daily rate matches employment contract
- [ ] Branch assignment is correct
- [ ] QR code generated successfully

### 2.2 Password Management SOP

**Password Requirements:**
- Minimum 8 characters
- Mixed case letters required
- At least one number
- Special characters recommended

**Password Reset Procedure:**
1. Employee requests reset via supervisor or HR
2. Verify employee identity (employee code + security question)
3. Admin navigates to employee profile
4. Generate new temporary password
5. Communicate securely (in-person or encrypted email)
6. Employee must change password on first login
7. Log password reset action

**Security Protocols:**
- Passwords hashed with bcrypt (legacy MD5 migration in progress)
- Session timeout: 24 hours inactivity
- IP logging for all authentication attempts
- Failed login attempts: 5 attempts = 15-minute lockout

### 2.3 Session & Access Control

**Session Management:**
- Sessions stored server-side (PHP native sessions)
- Session regeneration on privilege escalation
- QR scan sessions marked as temporary (`qr_temp_session`)
- Concurrent session handling: Single active session per user

**Role Verification:**
```php
// Standard role check pattern
if (!in_array($_SESSION['position'], ['Admin', 'Super Admin', 'Developer'])) {
    header('Location: ../login.php');
    exit;
}
```

### 2.4 API Security

**API Key Management:**
- Navigate to `employee/api_key_management.php`
- Generate keys with specific permissions
- Set expiration dates
- Monitor usage logs
- Revoke compromised keys immediately

**Authentication Methods:**
1. Session-based (Web interface)
2. API Key (External integrations)
3. Procurement SSO (Single sign-on)

---

## 3. Employee Lifecycle Management SOP

### 3.1 Employee Onboarding Workflow

**Day 1 - Pre-arrival:**
1. Create employee record in system
2. Generate QR code and print ID card
3. Set up email account
4. Prepare welcome packet with login instructions

**Day 1 - Arrival:**
1. Conduct system orientation
2. Assist with first login
3. Guide through password change
4. Explain clock-in methods
5. Set up digital signature (for cash advance requests)

**Week 1:**
1. Verify attendance recording correctly
2. Check geofence validation (if applicable)
3. Enroll face recognition (optional)
4. Review payroll setup

### 3.2 Employee Profile Updates

**Self-Service Updates (Employees can edit):**
- Profile photo
- Contact information
- Password
- Notification preferences

**Admin-Required Updates:**
- Position/Role changes
- Daily rate adjustments
- Branch transfers
- Deduction flag changes
- Status (Active/Inactive)

**Update Procedure:**
1. Navigate to `employee/employees.php`
2. Search for employee
3. Click "Edit"
4. Modify fields
5. Document reason for change
6. Save
7. Log activity with before/after values

### 3.3 Employee Offboarding

**Termination Procedure:**
1. Set employee status to "Inactive" (do NOT delete)
2. Generate final payroll report
3. Process any outstanding cash advances
4. Archive employee data
5. Disable login access
6. Revoke API keys
7. Log termination reason

**Data Retention:**
- Employee records: 7 years (legal requirement)
- Attendance data: 5 years
- Payroll records: 7 years
- Audit logs: 10 years

### 3.4 Position & Rate Changes

**Promotion/Demotion Procedure:**
1. HR submits position change request
2. Super Admin approves
3. Update employee record:
   - New position
   - Effective date
   - New daily rate (if applicable)
4. Verify deduction flags (Security Guards = no deductions)
5. Generate confirmation letter
6. Log change in activity logs

---

## 4. Daily Attendance Operations SOP

### 4.1 Clock-In Methods Standard Operating Procedure

#### Method 1: QR Code Scanning (Primary Method)

**Equipment Required:**
- Device with camera (smartphone, tablet, or computer webcam)
- Internet connection
- Valid employee QR code

**Standard Operating Procedure:**
1. Navigate to `employee/select_employee.php`
2. Click "Scan QR Code"
3. Allow camera permissions when prompted
4. Position QR code 4-6 inches from camera
5. Hold steady until beep/visual confirmation
6. System auto-records:
   - Employee ID
   - Timestamp (Asia/Manila timezone)
   - GPS coordinates (if enabled)
   - Branch location
7. Confirmation message displays

**Troubleshooting:**
- **Camera not working:** Check browser permissions, refresh page
- **QR not scanning:** Clean camera lens, ensure good lighting, try different angle
- **Invalid QR:** Regenerate QR code in employee profile
- **Outside geofence:** Verify location or contact admin to adjust geofence

#### Method 2: Face Recognition (Secondary/Backup Method)

**Prerequisites:**
- Face enrollment completed by admin
- Camera access enabled
- Good lighting conditions

**SOP for Enrollment:**
1. Navigate to face enrollment interface
2. Position face in frame
3. System captures multiple angles
4. Verify enrollment success
5. Test recognition

**SOP for Daily Use:**
1. Select "Face Recognition" mode
2. Look at camera
3. System matches biometric data
4. Records attendance automatically

#### Method 3: Manual Admin Entry (Exception Handling)

**Use Cases:**
- Employee forgot phone/QR card
- Device malfunction
- Remote work without system access
- Retroactive corrections

**SOP:**
1. Navigate to `employee/attendance.php`
2. Select date (default: today)
3. Switch to "Unmarked" view
4. Find employee in list
5. Click "Mark Present" or "Mark Absent"
6. Provide reason in notes field
7. Confirm entry
8. System logs admin action with IP and timestamp

**Approval Required For:**
- Backdated entries (>3 days)
- Bulk entries (>5 employees)
- Status changes (Present ↔ Absent)

### 4.2 Geofencing & Location Validation SOP

**Geofence Configuration:**
- Default radius: 100 meters
- Configurable per branch
- GPS coordinates validated on clock-in

**Location Validation Procedure:**
1. Employee attempts clock-in
2. System captures GPS coordinates
3. Validates against branch coordinates
4. Calculates distance using Haversine formula
5. If within radius: Allow clock-in
6. If outside radius: Reject with option for admin override

**Geofence Override Procedure:**
1. Employee receives "Outside geofence" error
2. Contacts supervisor/admin
3. Admin verifies legitimate reason:
   - Remote work assignment
   - Field work
   - System error
4. Admin manually marks attendance
5. Documents reason in notes

**Branch Location Management:**
- Navigate to `employee/branch_location_manager.php`
- Update GPS coordinates as needed
- Adjust geofence radius per site requirements
- Verify location quarterly

### 4.3 Attendance Status Definitions & Handling

| Status | Definition | Payroll Impact | Who Can Set |
|--------|------------|----------------|-------------|
| **Present** | Normal attendance, clocked in/out | Full day pay | System/Admin |
| **Late** | Arrived after scheduled start | Full day pay (flagged) | System/Admin |
| **Absent** | No attendance recorded | No pay | System/Admin |
| **On Leave** | Approved leave day | Per leave policy | Admin |
| **Half-Day** | Worked partial day | 0.5 day pay | System |

**Auto-Absent Marking:**
- System runs daily at 8:30 AM
- Marks employees as absent if no clock-in
- Excludes Sundays
- Excludes approved leave days
- Admin can override

### 4.4 Branch Transfers During Day

**Multi-Branch Workday Handling:**
1. Employee clocks in at Branch A
2. Employee transfers to Branch B during day
3. Clocks out at Branch B
4. System records:
   - 0.5 day at Branch A
   - 0.5 day at Branch B
5. Payroll splits accordingly

**Transfer Documentation:**
- Log transfer in `employee_transfers` table
- Record from_branch, to_branch, transfer_date
- Status: pending → completed

---

## 5. Payroll Processing SOP

### 5.1 Payroll Calendar & Schedule

**Weekly Payroll Cycle (Weeks 1-3):**

| Day | Time | Action | Responsible |
|-----|------|--------|-------------|
| Monday-Thursday | 6:40 AM - 5:00 PM | Daily attendance recording | All Employees |
| Friday | 12:00 AM | Automated weekly calculation | System (Cron) |
| Friday | 9:00 AM | Payroll review and verification | Payroll Admin |
| Friday | 2:00 PM | Payment processing | Finance |

**Monthly Payroll Cycle (Week 4/5):**

| Date | Action | Responsible |
|------|--------|-------------|
| Last Friday | Final weekly + monthly reconciliation | Payroll Admin |
| Last Day | Generate monthly payroll report | System |
| 1st of Next Month | Disburse payments | Finance |

### 5.2 Automated Payroll Calculation SOP

**Daily Calculation (Automated):**
- **Script:** `employee/cron/daily_payroll_calculation.php`
- **Schedule:** Daily at 12:00 AM
- **Input:** Previous day's attendance records
- **Output:** `daily_payroll_reports` table

**Weekly Calculation (Automated):**
- **Script:** `employee/cron/weekly_payroll_calculation.php`
- **Schedule:** Every Friday at 12:00 AM
- **Input:** Week's daily payroll records
- **Output:** `weekly_payroll_reports` table

**Monitoring:**
1. Check cron logs: `employee/cron/*.log`
2. Verify successful execution message
3. Review any ERROR entries
4. Address failures immediately

### 5.3 Manual Payroll Review & Adjustment SOP

**Pre-Payment Checklist:**
- [ ] All attendance records complete for period
- [ ] No unmarked days (except approved leave)
- [ ] Overtime properly approved and recorded
- [ ] Cash advance deductions calculated
- [ ] Government deductions verified (SSS, PhilHealth, Pag-IBIG)
- [ ] Special cases reviewed (transfers, new hires, terminations)

**Deduction Schedule by Branch:**

| Branch Type | SSS | PhilHealth | Pag-IBIG | Schedule |
|-------------|-----|------------|----------|----------|
| **Branch 33** | Yes | Yes | Yes | Week 4 only (Monthly) |
| **All Others** | Yes | Yes | Yes | Weeks 1-3 (Weekly) |
| **Security Guards** | No | No | No | Never |

**Standard Deductions (Philippines):**

| Deduction | Calculation | Cap |
|-----------|-------------|-----|
| **SSS** | 4.5% of gross pay | ₱1,125 |
| **PhilHealth** | 3.5% of gross pay | ₱2,450 |
| **Pag-IBIG** | Fixed amount | ₱100 |

### 5.4 Payroll Calculation Formulas

**Basic Pay:**
```
Basic Pay = Days Worked × Daily Rate
```

**Overtime Pay:**
```
Hourly Rate = Daily Rate ÷ 8
OT Pay = Total OT Hours × Hourly Rate × 1.25 (regular OT rate)
```

**Gross Pay:**
```
Gross Pay = Basic Pay + OT Pay + Performance Allowance
```

**Total Deductions:**
```
Total Deductions = SSS + PhilHealth + Pag-IBIG + CA Deduction + SSS Loan
```

**Net Pay:**
```
Net Pay = Gross Pay - Total Deductions
```

### 5.5 Payroll Correction Procedure

**When Corrections Are Needed:**
1. Incorrect daily rate used
2. Missing attendance entries
3. Wrong deduction calculations
4. Unrecorded overtime

**Correction Steps:**
1. Identify error and affected employees
2. Stop payment processing if in progress
3. Correct source data (attendance/rates)
4. Re-run payroll calculation
5. Generate correction report
6. Document reason for audit trail
7. Process corrected payment

---

## 6. Branch & Location Management SOP

### 6.1 Branch Creation Procedure

**Prerequisites:**
- Physical address confirmed
- GPS coordinates obtained
- Geofence radius determined

**Steps:**
1. Navigate to `employee/branch_location_manager.php`
2. Click "Add Branch"
3. Enter details:
   - Branch Name (unique)
   - Order Number (project reference)
   - Branch Address
   - GPS Latitude
   - GPS Longitude
   - Geofence Radius (default: 100m)
4. Save branch
5. Verify on map view
6. Set status to "Active"

**Quality Verification:**
- [ ] Coordinates verified via Google Maps
- [ ] Geofence radius appropriate for site size
- [ ] Branch name follows naming convention
- [ ] Address complete and accurate

### 6.2 Branch Maintenance SOP

**Quarterly Review:**
1. Review all active branches
2. Verify GPS coordinates still accurate
3. Check geofence radius appropriateness
4. Update addresses if changed
5. Deactivate closed/completed project branches

**Coordinate Updates:**
1. Navigate to Branch Location Manager
2. Select branch
3. Edit latitude/longitude
4. Save changes
5. Test geofence with mobile device

### 6.3 Branch Deactivation/Closure

**Procedure:**
1. Ensure all employees transferred to other branches
2. Generate final payroll for branch
3. Set branch status to "Inactive"
4. Archive branch records
5. Document closure reason

---

## 7. Overtime Management SOP

### 7.1 Overtime Types & Rates

| Type | Rate Multiplier | When Applicable |
|------|-----------------|-----------------|
| **Regular OT** | 1.25x | After 8 hours on regular day |
| **Night Differential** | +10% | 10:00 PM - 6:00 AM |
| **Rest Day OT** | 1.3x | Work on scheduled rest day |
| **Holiday OT** | 2.0x | Work on official holiday |

### 7.2 Overtime Request Procedure

**Employee-Initiated Request:**
1. Navigate to `employee/overtime.php`
2. Click "Request Overtime"
3. Fill request form:
   - Date
   - Expected hours
   - Reason/justification
4. Submit for approval
5. System notifies supervisor/admin

**Pre-Approved OT (Admin Entry):**
1. Admin navigates to attendance record
2. Click "Add OT Hours"
3. Enter hours and rate type
4. Mark as "Pre-approved"
5. Auto-included in payroll

### 7.3 Overtime Approval Workflow

**Routing:**
1. Employee submits request
2. Immediate supervisor reviews
3. Super Admin/Admin approves/rejects
4. Employee receives notification

**Approval Criteria:**
- Business necessity documented
- Budget availability confirmed
- Compliance with labor laws
- Employee eligibility (no disciplinary issues)

**Rejection Handling:**
1. Document reason for rejection
2. Communicate to employee
3. Offer alternative if applicable
4. Log decision

### 7.4 Overtime Reporting

**Navigate to:** `employee/overtime.php`

**Available Filters:**
- Date range
- Branch
- Status (Pending/Approved/Rejected)
- Employee

**Export Options:**
- Excel (.xlsx)
- PDF
- CSV

---

## 8. Leave Management SOP

### 8.1 Leave Types & Entitlements

| Leave Type | Monthly Credit | Max Accumulation | Use It or Lose It |
|------------|-----------------|------------------|-------------------|
| **Sick Leave** | 1 day | 30 days | No (accumulates) |
| **Vacation Leave** | 1 day | 15 days | Yes (annual reset) |
| **Emergency Leave** | From balance | N/A | N/A |

### 8.2 Leave Credit Accrual

**Automated Monthly Credit:**
- **Script:** `employee/cron/monthly_leave_credit.php`
- **Schedule:** 1st of each month at 12:00 AM
- **Process:**
  1. Identify active employees
  2. Calculate credits based on tenure
  3. Add to leave balance
  4. Log credit transaction

**Manual Adjustments:**
- Super Admin can adjust balances
- Document reason for adjustment
- Notify employee of change

### 8.3 Leave Request Procedure

**Employee Steps:**
1. Navigate to leave request page
2. Select leave type
3. Select date(s)
4. Provide reason
5. Attach supporting documents (medical certificate for sick leave >2 days)
6. Submit request

**Approval Workflow:**
1. Supervisor reviews request
2. Verifies leave balance availability
3. Checks operational requirements
4. Approves/Rejects
5. System updates balance if approved

### 8.4 Leave Balance Inquiry

**Employee Self-Service:**
- View current balance
- See leave history
- Track pending requests
- Check expiry dates (vacation leave)

---

## 9. Cash Advance & Loans SOP

### 9.1 Cash Advance Request Procedure

**Employee Steps:**
1. Navigate to `employee/cash_advance.php`
2. Click "Request Cash Advance"
3. Enter amount (subject to limit)
4. Provide reason
5. Submit digital signature
6. Submit request

**Limits:**
- Maximum: 50% of monthly salary
- Frequency: Once per month maximum
- Outstanding balance must be < ₱5,000

### 9.2 Cash Advance Approval Workflow

**Review Process:**
1. Admin receives notification
2. Reviews employee standing
3. Verifies no outstanding balance
4. Checks amount against limit
5. Approves/Rejects

**Deduction Setup:**
1. Upon approval, deduction scheduled
2. Amount deducted from next payroll
3. Can be split across multiple pay periods if needed

### 9.3 Loan (SSS Loan) Management

**SSS Loan Recording:**
1. Admin enters loan details
2. Specify monthly amortization
3. System auto-deducts from payroll
4. Tracks remaining balance

**Loan Reporting:**
- View all active loans
- Check amortization status
- Generate loan summary reports

### 9.4 Balance Tracking & Repayment

**Running Balance Calculation:**
```
Balance = Total CA - Total Payments
```

**Repayment Methods:**
1. Payroll deduction (automatic)
2. Cash payment (recorded manually)
3. Bank transfer (with proof of payment)

---

## 10. Billing & Cost Allocation SOP

### 10.1 Site Salary Billing

**Purpose:** Allocate labor costs to specific branches/sites for project accounting.

**Navigate to:** `employee/billing.php`

**Report Types:**
1. **Site Salary** - Total salary per branch
2. **Cash Advance Summary** - CA per employee
3. **Employer Contributions** - Government contributions per branch

### 10.2 Generating Billing Reports

**Steps:**
1. Select report type
2. Set date range
3. Click "Generate Report"
4. System populates `daily_payroll_reports` with deductions
5. View aggregated data by branch
6. Export to Excel/PDF

### 10.3 Cost Allocation Methodology

**Employee Work Distribution:**
- Full day at one branch: 100% to that branch
- Split day (transfer): 50% to each branch
- Multiple branches: Proportional based on hours

**Deduction Allocation:**
- Deductions follow primary branch (most days worked)
- Branch 33: Monthly deductions (Week 4)
- Others: Weekly deductions (Weeks 1-3)

### 10.4 Billing Reconciliation

**Monthly Process:**
1. Generate billing report for month
2. Compare with project budgets
3. Verify all costs allocated correctly
4. Reconcile with finance department
5. Archive for audit

---

## 11. Transfer Management SOP

### 11.1 Employee Transfer Request

**Navigate to:** `employee/transfer_module.php`

**Request Types:**
- Permanent transfer (change home branch)
- Temporary assignment (specific dates)
- Day transfer (single day at different branch)

### 11.2 Transfer Approval Procedure

**Steps:**
1. Employee/Admin initiates transfer
2. Select from_branch and to_branch
3. Set transfer date
4. Provide reason
5. Submit for approval
6. Receiving branch manager approves
7. HR/Super Admin final approval
8. System updates employee branch assignment

### 11.3 Transfer Payroll Impact

**Calculation:**
- Days before transfer: Old branch payroll
- Days after transfer: New branch payroll
- Transfer day: Split if applicable

**Deduction Changes:**
- If transferring to Branch 33: Monthly deduction schedule
- If transferring from Branch 33: Weekly deduction schedule

### 11.4 Transfer Reporting

**Available Reports:**
- Transfer history by employee
- Transfer summary by date range
- Pending transfer requests

---

## 12. Reporting & Audit SOP

### 12.1 Standard Reports Schedule

| Report | Frequency | Generated By | Reviewed By |
|--------|-----------|--------------|-------------|
| **Weekly Payroll** | Weekly | System (Auto) | Payroll Admin |
| **Monthly Payroll** | Monthly | System (Auto) | Finance Manager |
| **Attendance Summary** | Daily | System (Auto) | HR Admin |
| **Overtime Report** | Weekly | System (Auto) | Department Heads |
| **Audit Trail** | Monthly | System (Auto) | Compliance Officer |
| **Branch Deployment** | Weekly | Admin | Operations Manager |

### 12.2 Audit Trail Management

**Navigate to:** `employee/audit.php`

**Tracked Actions:**
- Login/Logout events
- Attendance marking (who, when, what changed)
- Payroll calculations
- Employee data modifications
- Approval actions

**Audit Review Procedure:**
1. Generate audit report for period
2. Review flagged discrepancies
3. Verify authorized changes
4. Investigate unauthorized changes
5. Document findings
6. Report to management

### 12.3 Data Export Procedures

**Export Formats:**
- **Excel (.xlsx):** For analysis, pivot tables
- **PDF:** For distribution, printing
- **CSV:** For data import to other systems

**Export Security:**
- Log all exports (who, what, when)
- Password protect sensitive files
- Limit bulk export permissions
- Auto-delete temporary files

### 12.4 Report Retention Policy

| Report Type | Retention Period | Storage Location |
|-------------|------------------|------------------|
| Payroll Reports | 7 years | Secure server + backup |
| Attendance Logs | 5 years | Database + archive |
| Audit Reports | 10 years | Immutable storage |
| Billing Reports | 7 years | Secure server |
| System Logs | 2 years | Rotated logs |

---

## 13. System Maintenance & Backup SOP

### 13.1 Daily Maintenance Tasks

**Automated (Cron Jobs):**
- Daily payroll calculation (12:00 AM)
- Attendance aggregation
- Log rotation
- Backup verification

**Manual Checks:**
- Review error logs
- Check disk space
- Verify cron job execution
- Monitor system performance

### 13.2 Database Backup Procedure

**Automated Backups:**
- **Frequency:** Daily at 2:00 AM
- **Location:** `/backups/database/`
- **Retention:** 30 days
- **Format:** SQL dump + compressed

**Manual Backup:**
```bash
# MySQL dump command
mysqldump -u root -p attendance_db > backup_$(date +%Y%m%d).sql
```

**Backup Verification:**
1. Check backup file size
2. Test restore on staging environment
3. Verify data integrity
4. Log backup success/failure

### 13.3 System Update Procedure

**Pre-Update:**
1. Create full system backup
2. Document current version
3. Review release notes
4. Schedule maintenance window
5. Notify users

**Update Process:**
1. Put system in maintenance mode
2. Apply updates
3. Run database migrations
4. Clear caches
5. Test critical functions
6. Remove maintenance mode

**Post-Update:**
1. Monitor error logs
2. Verify all functions working
3. Document update completion
4. Address any issues

### 13.4 Cron Job Management

**Windows Task Scheduler Setup:**

| Job Name | Schedule | Script |
|----------|----------|--------|
| Daily Payroll | Daily 12:00 AM | `daily_payroll_calculation.php` |
| Weekly Payroll | Friday 12:00 AM | `weekly_payroll_calculation.php` |
| Leave Credit | 1st of Month 12:00 AM | `monthly_leave_credit.php` |
| Cleanup | Daily 3:00 AM | `cleanup_duplicates.php` |

**Monitoring:**
- Check log files daily
- Set up email alerts for failures
- Review execution times
- Adjust schedules as needed

---

## 14. Emergency Procedures

### 14.1 System Outage Response

**Immediate Actions (0-15 minutes):**
1. Acknowledge outage
2. Assess scope (partial/full)
3. Notify stakeholders
4. Initiate emergency protocols

**Short-term Response (15-60 minutes):**
1. Attempt system restoration
2. Switch to manual processes if needed
3. Document all attendance manually
4. Communicate with employees

**Recovery (1-24 hours):**
1. Restore system
2. Verify data integrity
3. Backfill missed attendance
4. Recalculate affected payroll
5. Resume normal operations

### 14.2 Data Loss Recovery

**Steps:**
1. Identify scope of data loss
2. Locate most recent clean backup
3. Restore database from backup
4. Re-apply any transactions since backup
5. Verify data accuracy
6. Document incident

### 14.3 Security Breach Response

**Immediate:**
1. Isolate affected systems
2. Revoke compromised credentials
3. Notify security team
4. Preserve evidence

**Investigation:**
1. Review access logs
2. Identify entry point
3. Assess data exposure
4. Document findings

**Recovery:**
1. Patch vulnerabilities
2. Reset all passwords
3. Enhance monitoring
4. Update security policies

### 14.4 Payroll Emergency Procedures

**Scenario: Failed Payroll Calculation**
1. Stop payment processing
2. Identify affected employees
3. Revert to last good calculation
4. Manual calculation if needed
5. Process emergency payments
6. Fix root cause
7. Document incident

---

## 15. Appendices

### Appendix A: File Reference Guide

| Module | Primary File | Supporting Files |
|--------|--------------|------------------|
| **Login** | `login.php` | `login_api.php`, `login_api_simple.php` |
| **Dashboard** | `employee/dashboard.php` | `employee/eng_dashboard.php` |
| **Employees** | `employee/employees.php` | `employee/function/employees_function.php` |
| **Attendance** | `employee/attendance.php` | `employee/select_employee.php` |
| **Payroll** | `employee/payroll.php` | `employee/cron/daily_payroll_calculation.php`, `employee/cron/weekly_payroll_calculation.php` |
| **Overtime** | `employee/overtime.php` | `employee/notification.php` |
| **Cash Advance** | `employee/cash_advance.php` | `employee/cash_advance_history.php` |
| **Billing** | `employee/billing.php` | `employee/get_billing_data.php` |
| **Reports** | `employee/weekly_report.php` | `employee/audit.php`, `employee/generate_audit_report.php` |
| **Transfers** | `employee/transfer_module.php` | `employee/branch_location_manager.php` |
| **Settings** | `employee/settings.php` | `employee/signature_settings.php` |

### Appendix B: Database Table Reference

| Table | Purpose | Key Fields |
|-------|---------|------------|
| **employees** | Employee master data | id, employee_code, daily_rate, branch_id |
| **attendance** | Daily attendance records | employee_id, attendance_date, status, time_in, time_out |
| **branches** | Branch locations | id, branch_name, lat, long, geofence_radius_meters |
| **daily_payroll_reports** | Daily calculated payroll | employee_id, report_date, days_worked, take_home_pay |
| **weekly_payroll_reports** | Weekly payroll summary | employee_id, week_number, gross_pay, total_deductions |
| **overtime_requests** | OT approval tracking | employee_id, request_date, hours, status |
| **cash_advances** | Cash advance ledger | employee_id, amount, status, particular |
| **activity_logs** | System audit trail | user_id, action, details, ip_address |
| **employee_transfers** | Transfer history | employee_id, from_branch, to_branch, transfer_date |
| **push_subscriptions** | Web push data | user_id, endpoint, p256dh, auth |

### Appendix C: API Endpoint Quick Reference

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/login_api.php` | POST | User authentication |
| `/time_in_api.php` | POST | Record clock-in |
| `/time_out_api.php` | POST | Record clock-out |
| `/employee/api/clock_in.php` | POST | Clock-in with location |
| `/employee/api/validate_geofence.php` | POST | Verify location |
| `/get_payroll_report.php` | GET | Retrieve payroll data |
| `/employee/api/get_dashboard_analytics.php` | GET | Dashboard statistics |

### Appendix D: Troubleshooting Quick Guide

| Problem | Likely Cause | Solution |
|---------|--------------|----------|
| Cannot clock in - geofence | Outside radius | Verify location or contact admin |
| QR not scanning | Camera issue | Check permissions, clean lens |
| Payroll incorrect | Missing attendance | Verify all days marked |
| Cannot login | Wrong password | Reset via admin |
| No notifications | Permission denied | Re-enable browser notifications |
| Slow loading | Large date range | Reduce date range filter |

### Appendix E: Contact Information

| Role | Contact | When to Contact |
|------|---------|-----------------|
| **System Developer** | [Dev Email] | Technical issues, bugs |
| **Super Admin** | [Admin Email] | Access issues, approvals |
| **HR Manager** | [HR Email] | Employee issues, policy |
| **Payroll Admin** | [Payroll Email] | Payroll questions |
| **IT Support** | [IT Email] | Login, device issues |

### Appendix F: Philippine Payroll Compliance

**Government Remittance Schedule:**
- **SSS:** Monthly (by 15th)
- **PhilHealth:** Monthly (by end of month)
- **Pag-IBIG:** Monthly (by 10th)

**Record Retention:**
- Payroll records: 7 years
- Time records: 3 years
- Government forms: 5 years

**Labor Law Compliance:**
- Minimum wage adherence
- Overtime rate compliance
- Rest day premium pay
- Holiday pay regulations

---

## Document Sign-off

| Role | Name | Signature | Date |
|------|------|-----------|------|
| **Prepared By** | | | |
| **Reviewed By** | | | |
| **Approved By** | | | |

---

**Document Version History**

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | April 2026 | System Administrator | Initial comprehensive SOP |

---

**END OF MASTER SOP DOCUMENT**
