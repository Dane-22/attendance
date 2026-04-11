# Standard Operating Procedure (SOP) - Admin Role

## Document Information
- **Role:** Admin (Department Administrator)
- **Access Level:** 3 - Administrative Functions
- **Version:** 1.0
- **Effective Date:** April 2026

---

## Table of Contents
1. [Role Overview](#1-role-overview)
2. [Access & Permissions](#2-access--permissions)
3. [Daily Operations](#3-daily-operations)
4. [Employee Management](#4-employee-management)
5. [Attendance Management](#5-attendance-management)
6. [Payroll Review](#6-payroll-review)
7. [Reporting](#7-reporting)
8. [Troubleshooting](#8-troubleshooting)

---

## 1. Role Overview

### 1.1 Purpose
The Admin role manages day-to-day operations including employee records, attendance verification, payroll review, and branch coordination. Admins support Super Admins by handling routine tasks and first-level approvals.

### 1.2 Responsibilities
- Manage employee data (view and edit, no create/delete)
- Mark and correct attendance
- Review payroll calculations
- Generate reports
- Approve routine requests
- Monitor branch operations
- Handle employee inquiries

---

## 2. Access & Permissions

### 2.1 Available Modules

| Module | Access | Notes |
|--------|--------|-------|
| **Dashboard** | Full | View all metrics and summaries |
| **Employees** | View/Edit | Cannot add/delete employees |
| **Attendance** | Full | Mark, edit, and correct attendance |
| **Payroll** | View/Review | View and verify, no processing |
| **Overtime** | View | Track OT requests, cannot approve |
| **Reports** | Full | Generate all reports |
| **Branch Manager** | View | View locations, no edit |
| **Transfer Module** | View | View transfers, initiate requests |
| **Settings** | Personal | Own profile only |

### 2.2 Permission Restrictions

**CANNOT:**
- Create new employee records (Super Admin only)
- Delete employees
- Approve overtime requests (Super Admin only)
- Approve cash advances (Super Admin only)
- Access system settings
- Edit branch GPS coordinates
- Override geofence restrictions
- Access Developer tools

---

## 3. Daily Operations

### 3.1 Morning Routine (8:00 AM)

**Daily Checklist:**
- [ ] Log in to system
- [ ] Check dashboard for alerts
- [ ] Review pending attendance from previous day
- [ ] Check for employee issues or requests
- [ ] Review transfer notifications

**Process:**
1. Navigate to `employee/dashboard.php`
2. Review Summary Cards:
   - Total Employees
   - Active Branches
   - Transfers Today
   - Pending Payroll
3. Check Recent Activity for any issues

### 3.2 Attendance Monitoring

**9:00 AM - Morning Attendance Check:**
1. Navigate to `employee/attendance.php`
2. Select today's date
3. Switch to "Unmarked" view
4. Identify employees without clock-in
5. Contact supervisors for status
6. Mark absent if no-show confirmed

**5:00 PM - End of Day Check:**
1. Return to Attendance page
2. Switch to "Marked" view
3. Verify all employees clocked out
4. Note any missing time-outs
5. Follow up next day

### 3.3 Employee Inquiries

**Common Requests:**
- Password resets
- Attendance corrections
- Payroll questions
- Schedule clarifications

**Response Time:**
- Urgent (payroll-related): Within 1 hour
- Standard: Within 4 hours
- Non-urgent: Within 24 hours

---

## 4. Employee Management

### 4.1 Viewing Employee Records

**Steps:**
1. Navigate to `employee/employees.php`
2. Use search bar to find employee
3. Click on employee name to view details
4. Review:
   - Personal information
   - Attendance history
   - Payroll records
   - Branch assignment

### 4.2 Editing Employee Information

**Allowed Edits:**
- Contact information
- Profile photo
- Position (with documentation)
- Daily rate (with approval)
- Branch assignment (with transfer process)

**Steps:**
1. Search for employee in Employees list
2. Click "Edit" button
3. Modify allowed fields
4. Document reason for change in notes
5. Save changes
6. Notify employee of updates

### 4.3 Handling New Hires

**When New Employee Arrives:**
1. Verify Super Admin has created account
2. Confirm QR code generated
3. Assist with first login
4. Explain clock-in methods
5. Verify attendance working
6. Report any issues to Super Admin

**First Day Checklist:**
- [ ] Account created by Super Admin
- [ ] QR code printed/generated
- [ ] Employee logged in successfully
- [ ] Test clock-in/out
- [ ] Profile photo uploaded
- [ ] Employee briefed on system use

---

## 5. Attendance Management

### 5.1 Manual Attendance Marking

**When to Use:**
- Employee forgot QR card
- Device malfunction
- Remote work authorization
- Retroactive correction (within policy)

**Steps:**
1. Navigate to `employee/attendance.php`
2. Select appropriate date
3. View "Unmarked" employees
4. Find employee in list
5. Click "Mark Present" or "Mark Absent"
6. Add note explaining reason
7. Confirm action

**Required Documentation:**
- Date of incident
- Reason for manual entry
- Employee confirmation
- Supervisor approval (if applicable)

### 5.2 Attendance Corrections

**Correction Policy:**
- Same-day corrections: No approval needed
- Next-day corrections: Document reason
- >3 days old: Requires Super Admin approval

**Steps:**
1. Identify incorrect attendance record
2. Navigate to Attendance page
3. Select date of record
4. Switch to "Marked" view
5. Find employee and click "Edit"
6. Change status (Present ↔ Absent)
7. Document reason thoroughly
8. Notify employee of correction

### 5.3 Handling Branch Transfers (Day-of)

**Process:**
1. Employee notifies of transfer
2. Verify in Transfer Module
3. Employee clocks in at new branch
4. System auto-detects location change
5. Attendance split: 0.5 day each branch
6. Verify both entries recorded

---

## 6. Payroll Review

### 6.1 Weekly Payroll Review (Every Friday 9:00 AM)

**Navigate to:** `employee/payroll.php`

**Review Checklist:**
- [ ] All days have attendance records
- [ ] No unmarked employees
- [ ] Overtime properly recorded
- [ ] Deductions calculated correctly
- [ ] New employees included
- [ ] Terminated employees excluded

**Steps:**
1. Select current week
2. Review Weekly View
3. Check each employee's:
   - Days worked
   - Daily rate accuracy
   - OT hours
   - Deductions (SSS, PhilHealth, Pag-IBIG)
4. Flag discrepancies for correction
5. Generate summary report
6. Submit to Finance for processing

### 6.2 Identifying Payroll Issues

**Common Issues to Check:**

| Issue | How to Spot | Action |
|-------|-------------|--------|
| Missing attendance | Days worked = 0 | Check attendance, mark if needed |
| Wrong rate | Rate doesn't match contract | Document correct rate, notify Super Admin |
| Missing OT | OT hours not showing | Verify OT approved and recorded |
| Wrong deductions | SSS/PhilHealth incorrect | Check has_deduction flag |

### 6.3 Payroll Correction Procedure

**When Error Found:**
1. Stop and document the issue
2. Note affected employee(s)
3. Identify root cause
4. Correct source data (attendance, rates)
5. Notify Super Admin for recalculation
6. Verify correction in next report

---

## 7. Reporting

### 7.1 Weekly Deployment Report

**Navigate to:** `employee/weekly_report.php`

**Generate Report:**
1. Select view type (Weekly/Monthly/Range)
2. Select week/month
3. Select branch (or "all")
4. Click "Generate Report"
5. Review summary data
6. Export to Excel/PDF if needed

**Report Contents:**
- Employee deployment by branch
- Present/Absent/Late counts
- Total employees per branch
- Attendance percentages

**Distribution:**
- Operations Manager
- Branch Supervisors
- HR Department

### 7.2 Attendance Summary Report

**Daily Generation:**
1. Navigate to Attendance page
2. Select date
3. View "Marked" employees
4. Note summary counts:
   - Total Present
   - Total Absent
   - Total Late
5. Document any anomalies

### 7.3 Individual Employee Reports

**When Needed:**
- Employee request
- Dispute resolution
- Performance review

**Steps:**
1. Navigate to `employee/individual_report_selector.php`
2. Select employee
3. Select report type:
   - Attendance History
   - Payroll History
   - Overtime Summary
4. Set date range
5. Generate report
6. Export or print
7. Provide to requestor

---

## 8. Troubleshooting

### 8.1 Common Issues & Solutions

#### Employee Cannot Login
**Symptoms:** "Invalid credentials" error
**Steps:**
1. Verify employee code/email is correct
2. Check account status is "Active"
3. Reset password if needed
4. Test login with new credentials
5. If still failing, escalate to Super Admin

#### Attendance Not Recording
**Symptoms:** Clock-in fails
**Steps:**
1. Check if outside geofence (if applicable)
2. Verify device has internet
3. Try alternate method (QR vs Manual entry)
4. Mark attendance manually as temporary fix
5. Report technical issue to Developer

#### Payroll Discrepancy
**Symptoms:** Employee reports wrong pay
**Steps:**
1. Generate individual payroll report
2. Compare with employee's records
3. Check attendance for missing days
4. Verify rate and deductions
5. If error confirmed, follow correction procedure

### 8.2 Escalation Matrix

| Issue Type | First Contact | Escalate To | When |
|------------|---------------|-------------|------|
| Password reset | Admin | Super Admin | If system error |
| Attendance correction | Admin | Super Admin | >3 days old |
| Payroll error | Admin | Super Admin | Affects multiple employees |
| System bug | Developer | - | - |
| Access issues | Admin | Super Admin | Cannot resolve |

### 8.3 Contact Reference

| Role | Contact Method | Response Time |
|------|----------------|---------------|
| Super Admin | [Admin Email] | 2 hours |
| Developer | [Dev Email] | 4 hours (non-urgent) |
| HR Manager | [HR Email] | 24 hours |
| IT Support | [IT Email] | 1 hour |

---

## Quick Reference: Admin Actions

### Daily Tasks
- [ ] Morning attendance check (9:00 AM)
- [ ] Afternoon attendance check (5:00 PM)
- [ ] Review dashboard alerts
- [ ] Respond to employee inquiries

### Weekly Tasks
- [ ] Payroll review (Friday 9:00 AM)
- [ ] Generate deployment report
- [ ] Review transfer requests

### Monthly Tasks
- [ ] Verify all branches active
- [ ] Review attendance trends
- [ ] Check for policy compliance

---

**Document Version:** 1.0  
**Last Updated:** April 2026  
**Next Review:** July 2026

---

**END OF ADMIN SOP**
