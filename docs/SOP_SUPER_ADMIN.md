# Standard Operating Procedure (SOP) - Super Admin Role

## Document Information
- **Role:** Super Administrator
- **Access Level:** 2 - Full Administrative Access
- **Version:** 1.0
- **Effective Date:** April 2026

---

## Table of Contents
1. [Role Overview](#1-role-overview)
2. [Access & Permissions](#2-access--permissions)
3. [Employee Lifecycle Management](#3-employee-lifecycle-management)
4. [Attendance & Payroll Override](#4-attendance--payroll-override)
5. [Approval Workflows](#5-approval-workflows)
6. [System Configuration](#6-system-configuration)
7. [User Management](#7-user-management)
8. [Audit & Compliance](#8-audit--compliance)
9. [Emergency Procedures](#9-emergency-procedures)

---

## 1. Role Overview

### 1.1 Purpose
The Super Admin has comprehensive system access to manage all administrative functions, approve critical requests, configure system settings, and ensure operational compliance. This role is responsible for system integrity and data accuracy.

### 1.2 Responsibilities
- Create and manage all employee records
- Approve overtime and cash advance requests
- Override system restrictions when necessary
- Configure system settings
- Manage user roles and permissions
- Perform payroll recalculations
- Handle escalated issues
- Maintain system security
- Generate audit reports

### 1.3 Authority Limits
- Can override any attendance record
- Can approve any request type
- Can access all system functions
- Can modify system configurations
- Can delete/archive records (with caution)

---

## 2. Access & Permissions

### 2.1 Full System Access

| Module | Access | Special Functions |
|--------|--------|-------------------|
| **Dashboard** | Full | All metrics, system status |
| **Employees** | Full CRUD | Create, edit, delete employees |
| **Attendance** | Full | Override any record, bulk operations |
| **Payroll** | Full | Recalculate, adjust, process |
| **Overtime** | Full | Approve/reject requests |
| **Cash Advance** | Full | Approve/reject, adjust balances |
| **Reports** | Full | All reports including audit |
| **Branch Manager** | Full | Create, edit, delete branches |
| **Transfer Module** | Full | Approve transfers, modify records |
| **Settings** | Full | System configuration |
| **Notification Center** | Full | Admin notifications, approvals |
| **API Management** | Full | Generate keys, set permissions |

### 2.2 Exclusive Super Admin Functions

**Employee Management:**
- Create new employee accounts
- Delete employee records
- Reset any password
- Change any employee data
- Bulk import employees

**Payroll:**
- Run manual payroll calculations
- Adjust deduction settings
- Override deduction rules
- Process special payroll runs

**Approvals:**
- Overtime requests (all types)
- Cash advance requests
- Large amount transactions
- Policy exceptions

**System:**
- Configure cron jobs
- Manage API keys
- Access audit logs
- System diagnostics
- Database maintenance

---

## 3. Employee Lifecycle Management

### 3.1 Creating New Employee Accounts

**Navigate to:** `employee/employees.php`

**Prerequisites:**
- New hire documentation complete
- Position and rate approved
- Branch assignment confirmed

**Steps:**
1. Click "Add Employee" button
2. Enter Employee Information:
   ```
   - Employee Code: Auto-generate or manual (format: E0001, ENG-YYYY-XXXX)
   - First Name: (required)
   - Last Name: (required)
   - Middle Name: (optional)
   - Email: (required, unique)
   - Position: (dropdown - Worker, Engineer, Admin, etc.)
   - Daily Rate: (required for payroll)
   - Branch: (select from active branches)
   - Status: Active
   ```
3. Configure Settings:
   - `has_deduction`: 1 (default) or 0 (Security Guards)
   - `is_active`: 1
4. Generate temporary password
5. Save employee record
6. System auto-generates QR code
7. Print QR code for employee ID
8. Send credentials securely to employee

**Quality Checklist:**
- [ ] Employee code is unique
- [ ] Email format valid and not duplicate
- [ ] Daily rate matches approved amount
- [ ] Branch assignment correct
- [ ] Position appropriate for role
- [ ] Deduction flag set correctly
- [ ] QR code generated successfully

### 3.2 Employee Termination/Offboarding

**Important:** NEVER delete employee records. Set status to "Inactive" instead.

**Steps:**
1. Navigate to employee profile
2. Generate final payroll report
3. Process any outstanding cash advances
4. Set status to "Inactive"
5. Disable login (password change)
6. Document termination reason
7. Archive employee files
8. Notify relevant departments

**Final Checklist:**
- [ ] Final payroll calculated and paid
- [ ] Cash advances settled
- [ ] Leave balances paid out (if applicable)
- [ ] Access revoked
- [ ] Data archived for retention period

### 3.3 Bulk Employee Operations

**Bulk Import:**
1. Prepare CSV file with columns:
   - employee_code, first_name, last_name, email, position, daily_rate, branch_id
2. Navigate to import function
3. Upload CSV
4. Validate data preview
5. Process import
6. Review error log for failures
7. Generate credentials for new employees

**Bulk Updates:**
- Use database queries for mass updates (with backup)
- Document all bulk operations
- Test on staging first
- Execute during low-traffic hours

---

## 4. Attendance & Payroll Override

### 4.1 Overriding Attendance Records

**When to Override:**
- System error caused wrong status
- Emergency situations
- Retroactive corrections beyond policy
- Geofence false positives

**Steps:**
1. Navigate to `employee/attendance.php`
2. Select date and employee
3. Click "Edit" on attendance record
4. Modify status (Present/Absent/Late)
5. Add detailed reason for override
6. Check "Admin Override" checkbox
7. Enter your credentials
8. Save changes
9. System logs override with timestamp and admin ID

**Documentation Required:**
- Reason for override
- Supporting evidence
- Employee acknowledgment
- Supervisor approval (if applicable)

### 4.2 Manual Payroll Recalculation

**Navigate to:** `employee/cron/generate_daily_payroll.php`

**When to Recalculate:**
- Bulk attendance corrections
- Rate changes retroactive
- System error in calculation
- Policy changes

**Steps:**
1. Select date range for recalculation
2. Choose recalculation type:
   - Full recalculation
   - Specific employees only
   - Specific branches only
3. Preview changes
4. Confirm recalculation
5. Monitor progress
6. Verify results
7. Document reason for recalculation

**Post-Recalculation:**
- Generate comparison report
- Notify affected employees
- Update finance department
- Archive old calculations

### 4.3 Handling Special Cases

**Security Guards (No Deductions):**
1. Verify position = "Security Guard"
2. Ensure `has_deduction = 0`
3. Confirm no SSS/PhilHealth/Pag-IBIG deducted
4. Payroll = Gross pay only

**Branch 33 (Monthly Deductions):**
1. Deductions apply only Week 4
2. Weeks 1-3: No deductions
3. Monitor for correct application
4. Handle exceptions manually

**Multi-Branch Employees:**
1. Verify split day calculations
2. Check 0.5 day per branch
3. Confirm payroll aggregation
4. Validate deduction application to primary branch

---

## 5. Approval Workflows

### 5.1 Overtime Request Approval

**Navigate to:** `employee/notification.php`

**Review Process:**
1. View pending overtime requests
2. Click on request to view details:
   - Employee name
   - Date and hours requested
   - Reason provided
   - Current balance/status
3. Evaluate request:
   - Business necessity
   - Budget availability
   - Compliance with labor laws
   - Employee eligibility
4. Decision:
   - **Approve:** Click "Approve", add notes if needed
   - **Reject:** Click "Reject", provide detailed reason
5. Employee receives notification
6. Approved OT auto-included in payroll

**Approval Criteria:**
- Request submitted at least 24 hours in advance (preferred)
- Hours within weekly/monthly limits
- Reason is business-related
- Budget code available
- No disciplinary issues

### 5.2 Cash Advance Approval

**Navigate to:** `employee/notification.php` → Cash Advance tab

**Review Checklist:**
- [ ] Amount within limit (50% of monthly salary)
- [ ] No outstanding balance > ₱5,000
- [ ] Employee in good standing
- [ ] Reason documented
- [ ] Digital signature verified

**Steps:**
1. Review request details
2. Check employee's CA history
3. Verify current balance
4. Evaluate reason
5. **Approve:** Set deduction schedule
6. **Reject:** Provide reason and alternatives
7. System auto-deducts from next payroll(s)

**Special Cases:**
- Emergency requests: Can approve outside normal limits
- Repayment plans: Can split across multiple pay periods
- Partial approval: Approve lower amount

### 5.3 Transfer Approval

**Navigate to:** `employee/transfer_module.php`

**Review Process:**
1. View pending transfers
2. Verify:
   - Employee eligibility
   - Receiving branch capacity
   - Business justification
3. Check transfer date feasibility
4. **Approve:** Update employee branch assignment
5. **Reject:** Provide reason
6. Notify both branch managers
7. Update payroll location

---

## 6. System Configuration

### 6.1 Branch Management

**Create New Branch:**
1. Navigate to `employee/branch_location_manager.php`
2. Click "Add Branch"
3. Enter:
   - Branch Name (unique)
   - Order Number
   - Complete Address
   - GPS Latitude/Longitude
   - Geofence Radius (default: 100m)
4. Set status: Active
5. Save
6. Test geofence with mobile device

**Edit Branch:**
1. Select branch from list
2. Modify fields as needed
3. Save changes
4. Update affected employees if needed

**Deactivate Branch:**
1. Ensure all employees transferred
2. Generate final reports
3. Set status: Inactive
4. Document closure reason

### 6.2 Geofence Configuration

**Adjust Radius:**
1. Navigate to Branch Location Manager
2. Select branch
3. Edit "Geofence Radius" field
4. Save
5. Test with employee clock-in

**Disable Geofence (Temporary):**
1. Set radius to 0 or large number
2. Document reason
3. Re-enable when appropriate

### 6.3 Cron Job Management

**Monitor Cron Jobs:**
1. Check log files in `employee/cron/`
2. Review execution times
3. Verify successful completion
4. Address any errors

**Windows Task Scheduler:**
- Daily Payroll: Daily 12:00 AM
- Weekly Payroll: Friday 12:00 AM
- Leave Credit: 1st of month 12:00 AM
- Cleanup: Daily 3:00 AM

**Troubleshooting:**
- Check PHP path in task
- Verify file permissions
- Review error logs
- Test manual execution

---

## 7. User Management

### 7.1 Role Assignment

**Change Employee Role:**
1. Navigate to employee profile
2. Edit position field
3. Save changes
4. Verify new permissions applied
5. Notify employee of role change

**Available Roles:**
- Super Admin
- Admin
- Engineer
- Worker/Employee
- Security Guard

### 7.2 Password Management

**Reset Any Password:**
1. Navigate to employee profile
2. Click "Reset Password"
3. Generate temporary password
4. Force password change on next login
5. Communicate securely to employee

**Bulk Password Reset:**
- Use only for security incidents
- Notify all affected users
- Require immediate password change
- Document security event

### 7.3 Access Revocation

**Disable Account:**
1. Set status to "Inactive"
2. Clear all sessions
3. Revoke API keys
4. Document reason

**Re-enable Account:**
1. Set status to "Active"
2. Reset password
3. Verify employee information current
4. Test login

---

## 8. Audit & Compliance

### 8.1 Audit Trail Review

**Navigate to:** `employee/audit.php`

**Monthly Audit Process:**
1. Generate audit report for previous month
2. Review all admin overrides
3. Verify attendance corrections
4. Check payroll adjustments
5. Validate data integrity
6. Document findings
7. Report to management

**Key Areas to Audit:**
- Manual attendance entries
- Payroll overrides
- Employee data changes
- Approval decisions
- System configuration changes

### 8.2 Compliance Monitoring

**Philippine Labor Law Compliance:**
- Minimum wage verification
- Overtime rate compliance
- Government deduction accuracy
- Record retention adherence

**Internal Policy Compliance:**
- Approval authority adherence
- Data access logs
- Change documentation
- Security protocol compliance

### 8.3 Data Retention Management

**Retention Schedule:**
- Payroll records: 7 years
- Attendance data: 5 years
- Audit logs: 10 years
- System logs: 2 years

**Archive Procedure:**
1. Identify data beyond active period
2. Export to archive format
3. Store in secure location
4. Remove from active database
5. Maintain index for retrieval

---

## 9. Emergency Procedures

### 9.1 System Outage Response

**Immediate (0-15 min):**
1. Assess outage scope
2. Notify stakeholders
3. Enable manual processes
4. Document incident start time

**Recovery (15 min - 2 hours):**
1. Coordinate with Developer/IT
2. Implement workaround if available
3. Maintain manual attendance log
4. Communicate status updates

**Post-Recovery:**
1. Backfill missed data
2. Recalculate affected payroll
3. Verify data integrity
4. Document lessons learned

### 9.2 Data Breach Response

**Immediate Actions:**
1. Isolate affected systems
2. Revoke compromised credentials
3. Preserve evidence
4. Notify security team

**Investigation:**
1. Review access logs
2. Identify affected data
3. Assess exposure scope
4. Document findings

**Recovery:**
1. Patch vulnerabilities
2. Reset all passwords
3. Enhance monitoring
4. Update policies

### 9.3 Payroll Emergency

**Failed Calculation:**
1. Stop payment processing
2. Revert to last good state
3. Identify root cause
4. Manual calculation if needed
5. Emergency payment processing
6. Fix and verify

**Critical Payroll Errors:**
- Contact Finance immediately
- Prepare manual payment list
- Document all corrections
- Expedite recalculation

---

## Quick Reference Commands

### Database Queries (Use with Caution)

```sql
-- View employee payroll for date range
SELECT * FROM daily_payroll_reports 
WHERE employee_id = ? AND report_date BETWEEN ? AND ?;

-- Check attendance status
SELECT * FROM attendance 
WHERE employee_id = ? AND attendance_date = ?;

-- Verify cron job last run
SHOW PROCESSLIST;
```

### File Locations

| Function | Path |
|----------|------|
| Cron Logs | `employee/cron/*.log` |
| Error Logs | `api_debug.log` |
| Uploads | `uploads/profile_images/` |
| Backups | `/backups/` |

---

**Document Version:** 1.0  
**Last Updated:** April 2026  
**Next Review:** July 2026

---

**END OF SUPER ADMIN SOP**
