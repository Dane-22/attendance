# Admin User Manual
## JAJR Attendance & Payroll System

**Version:** 1.0  
**Last Updated:** April 2026  
**For:** System Administrators

---

## Table of Contents
1. [Getting Started](#1-getting-started)
2. [Dashboard Navigation](#2-dashboard-navigation)
3. [Employee Management](#3-employee-management)
4. [Attendance Operations](#4-attendance-operations)
5. [Payroll Review](#5-payroll-review)
6. [Reports](#6-reports)
7. [Common Tasks](#7-common-tasks)
8. [Troubleshooting](#8-troubleshooting)

---

## 1. Getting Started

### 1.1 Logging In

1. Open your web browser
2. Navigate to: `https://[your-domain]/login.php`
3. Enter your credentials:
   - **Email:** Your registered email address
   - **Password:** Your secure password
4. Click **"Log In"**
5. You will be redirected to the Admin Dashboard

**Note:** If you forget your password, contact the Super Admin to reset it.

### 1.2 First-Time Setup

Upon first login:
1. **Update Your Profile Photo**
   - Go to Settings (top right menu)
   - Click on your profile
   - Upload a professional photo
   - Save changes

2. **Verify Your Information**
   - Check your email address
   - Verify your position shows "Admin"
   - Confirm branch assignment is correct

3. **Enable Notifications** (Recommended)
   - Go to Settings → Notifications
   - Click "Enable Browser Notifications"
   - This helps you receive alerts for pending approvals

---

## 2. Dashboard Navigation

### 2.1 Dashboard Overview

**URL:** `employee/dashboard.php`

The Admin Dashboard provides a quick overview of the entire system. Here's what you'll see:

#### Summary Cards (Top Row)

| Card | What It Shows | Action |
|------|---------------|--------|
| **Total Employees** | Count of all active employees | Click to view full list |
| **Active Branches** | Number of operational sites | Click to view branch details |
| **Transfers Today** | Employees who transferred today | Click for transfer details |
| **Pending Payroll** | Payroll awaiting review | Click to access payroll page |

#### Recent Activity Log (Bottom Section)

Shows the latest system activities:
- Employee clock-ins/outs
- Profile updates
- Attendance changes
- Login/logout events

**Use this to:** Monitor system usage and spot unusual activity.

### 2.2 Main Navigation Menu

**Left Sidebar Menu Items:**

| Menu Item | What You Can Do |
|-----------|-----------------|
| **Dashboard** | View system overview |
| **Employees** | View and edit employee records |
| **Attendance** | Mark and correct attendance |
| **Payroll** | Review payroll calculations |
| **Reports** | Generate various reports |
| **Overtime** | View overtime requests (cannot approve) |
| **Settings** | Update your profile and preferences |

**Note:** As an Admin, you can VIEW and EDIT but cannot CREATE or DELETE employees. Only Super Admin has those permissions.

### 2.3 Quick Access Icons

**Top Navigation Bar:**
- **Bell Icon** - View notifications and pending items
- **User Icon** - Access your profile and logout
- **Search** - Quick search for employees

---

## 3. Employee Management

### 3.1 Viewing the Employee List

**Step-by-Step:**
1. Click **"Employees"** in the left menu
2. You'll see the employee list with columns:
   - Employee Code
   - Name
   - Position
   - Branch
   - Status

3. **Using the Search Bar:**
   - Type a name or employee code
   - Press Enter or click Search
   - Results filter automatically

4. **Using Filters:**
   - Click the filter dropdown
   - Select by: Position, Branch, or Status
   - Click "Apply" to filter

### 3.2 Viewing Employee Details

**To see full employee information:**
1. Find the employee in the list
2. Click on their **Name** or the **"View"** button
3. Employee profile opens showing:
   - Personal Information
   - Contact Details
   - Employment Details
   - Attendance History
   - Payroll History

### 3.3 Editing Employee Information

**What You Can Edit:**
- Contact information (email, phone)
- Profile photo
- Position (with proper documentation)
- Daily rate (requires approval reason)
- Branch assignment (use transfer process)

**Steps:**
1. Navigate to the employee's profile
2. Click the **"Edit"** button (top right)
3. Modify the allowed fields
4. **Important:** Add a reason in the "Notes" field
5. Click **"Save Changes"**
6. System logs your change with timestamp

**Cannot Edit (Super Admin Only):**
- Employee Code
- Create new employees
- Delete employees
- Change deduction flags
- System-level settings

### 3.4 Searching for Employees

**Quick Search:**
- Use the search bar at the top of the Employees page
- Search by: First name, Last name, or Employee Code
- Results appear instantly

**Advanced Search:**
- Use filters for Position, Branch, or Status
- Combine multiple filters
- Clear filters with "Reset" button

---

## 4. Attendance Operations

### 4.1 Viewing Today's Attendance

**URL:** `employee/attendance.php`

**Steps:**
1. Click **"Attendance"** in the left menu
2. Today's date is selected by default
3. Two views available:
   - **"Marked"** - Employees who have clocked in
   - **"Unmarked"** - Employees without attendance

**The Unmarked View is Critical:**
- Shows who hasn't clocked in yet
- Check this at 9:00 AM daily
- Follow up with supervisors for status

### 4.2 Marking Attendance Manually

**When to Use:**
- Employee forgot QR card
- Device malfunction
- Remote work authorized
- Retroactive correction

**Steps:**
1. Go to Attendance page
2. Select the **date** (today or past date)
3. Switch to **"Unmarked"** view
4. Find the employee in the list
5. Click **"Mark Present"** or **"Mark Absent"**
6. **Required:** Add a note explaining why
   - Example: "Employee forgot card, verified by supervisor"
7. Click **"Confirm"**

**Same-Day Corrections:**
- No special approval needed
- Document reason clearly
- Employee will see update immediately

**Past-Day Corrections:**
- 1-3 days old: Document reason
- >3 days old: **Requires Super Admin approval**
- Always provide detailed explanation

### 4.3 Correcting Attendance Errors

**If an employee was marked wrong:**
1. Go to Attendance page
2. Select the date of the error
3. Switch to **"Marked"** view
4. Find the employee
5. Click **"Edit"** next to their name
6. Change status (Present ↔ Absent)
7. **Document the correction reason**
8. Save changes

**After Correction:**
- Employee notified automatically
- Payroll recalculates on next run
- Action logged in audit trail

### 4.4 Handling Branch Transfers

**When employees work at multiple sites:**

**Scenario:** Employee works morning at Branch A, afternoon at Branch B

**What Happens:**
- Employee clocks out at Branch A
- Clocks in at Branch B
- System records 0.5 day at each
- Payroll splits accordingly

**Your Role:**
- Verify transfers are legitimate
- Check that both entries recorded
- No action needed if system handled it

**If System Doesn't Record Correctly:**
1. Note the correct hours at each branch
2. Mark attendance manually if needed
3. Document the actual work locations
4. Notify Super Admin for payroll adjustment

---

## 5. Payroll Review

### 5.1 Accessing the Payroll Page

**URL:** `employee/payroll.php`

**Navigation:**
1. Click **"Payroll"** in the left menu
2. Default view shows current week

**Available Views:**
- **Daily** - Day-by-day breakdown
- **Weekly** - Weekly summary by employee
- **Monthly** - Monthly aggregation

### 5.2 Weekly Payroll Review

**When:** Every Friday at 9:00 AM

**Steps:**
1. Select **"Weekly"** view
2. Choose the current week
3. Select branch (or "All" for company-wide)
4. Review the data table showing:
   - Employee Name
   - Days Worked
   - Daily Rate
   - Basic Pay (Days × Rate)
   - OT Hours
   - OT Pay
   - Gross Pay
   - SSS Deduction
   - PhilHealth Deduction
   - Pag-IBIG Deduction
   - Total Deductions
   - Net Pay

### 5.3 Payroll Checklist

**Before approving payroll for processing:**

- [ ] All employees have attendance records
- [ ] No "Unmarked" days (unless on approved leave)
- [ ] Overtime properly recorded and approved
- [ ] New employees included in payroll
- [ ] Terminated employees excluded
- [ ] Daily rates match employment contracts
- [ ] Deductions calculated correctly
- [ ] Cash advances deducted if applicable
- [ ] Branch 33 employees: Deductions only in Week 4
- [ ] Security Guards: Zero deductions

### 5.4 Identifying Payroll Issues

**Common Issues and Solutions:**

| Issue | How to Spot | What to Do |
|-------|-------------|------------|
| **Missing Attendance** | Days worked = 0 | Check attendance, mark if needed |
| **Wrong Rate** | Rate doesn't match contract | Document correct rate, notify Super Admin |
| **Missing OT** | OT hours not showing | Verify OT was approved and recorded |
| **Wrong Deductions** | SSS/PhilHealth amount incorrect | Check `has_deduction` flag in employee profile |
| **Cash Advance Missing** | CA deduction not showing | Check cash advance ledger |

### 5.5 Generating Payroll Reports

**Steps:**
1. On Payroll page, set filters:
   - Date range
   - Branch
   - View type (Summary/Detailed)
2. Click **"Generate Report"**
3. Preview report on screen
4. **Export Options:**
   - Click **"Export to Excel"** for spreadsheet
   - Click **"Export to PDF"** for document
5. Save or print as needed

**Report Distribution:**
- Send to Finance for payment processing
- Keep copy for records
- Provide individual reports to employees on request

---

## 6. Reports

### 6.1 Weekly Deployment Report

**URL:** `employee/weekly_report.php`

**What It Shows:**
- Employee deployment by branch
- Attendance counts (Present/Absent/Late)
- Total manpower per site
- Attendance percentages

**Steps to Generate:**
1. Select **"View Type"**:
   - Weekly
   - Monthly
   - Date Range
2. Select period (week/month)
3. Select branch (or "All")
4. Click **"Generate Report"**
5. Review summary cards and table
6. Export if needed

**Use For:**
- Operations planning
- Branch staffing analysis
- Management reporting

### 6.2 Attendance Summary Report

**Steps:**
1. Go to Attendance page
2. Select date range
3. View summary at top:
   - Total Present
   - Total Absent
   - Total Late
4. Click **"Generate Summary Report"**
5. Export or print

### 6.3 Individual Employee Reports

**URL:** `employee/individual_report_selector.php`

**When to Use:**
- Employee requests their records
- Dispute resolution
- Performance reviews

**Steps:**
1. Select employee from dropdown
2. Choose report type:
   - Attendance History
   - Payroll History
   - Overtime Summary
3. Set date range
4. Click **"Generate"**
5. Review report
6. Export to PDF or Excel
7. Provide to employee

**Note:** Only provide an employee their OWN records. Never share other employees' data.

### 6.4 Audit Trail Report

**URL:** `employee/audit.php`

**What It Shows:**
- All system actions
- Who made changes
- What was changed
- When it happened

**Admin Use:**
- Review your own actions
- Track attendance corrections
- Monitor system usage
- Identify unusual activity

**Note:** Full audit access may be limited to Super Admin.

---

## 7. Common Tasks

### 7.1 Resetting an Employee's Password

**Steps:**
1. Go to Employees page
2. Find the employee
3. Click on their name to open profile
4. Click **"Reset Password"** button
5. System generates temporary password
6. **Securely communicate** the password to employee
   - In person, or
   - Via secure company email
7. Employee must change password on first login
8. **Do not email passwords** to personal email addresses

### 7.2 Updating Your Own Profile

**Steps:**
1. Click your name/profile icon (top right)
2. Select **"My Profile"** or **"Settings"**
3. Update allowed fields:
   - Profile photo
   - Email address
   - Phone number
   - Password
4. Click **"Save"**

### 7.3 Handling Employee Inquiries

**Common Requests and Responses:**

**"I forgot to clock in"**
- Response: "I'll mark you present. What time did you arrive?"
- Action: Go to Attendance, mark present, add note

**"My pay is wrong"**
- Response: "Let me check your records. What specifically looks incorrect?"
- Action: Generate individual payroll report, verify calculations

**"I need my attendance record"**
- Response: "I'll generate that for you. What date range?"
- Action: Generate individual attendance report, provide PDF

**"Can you change my branch?"**
- Response: "You need to submit a transfer request. I can guide you."
- Action: Direct to Transfer Module or process if Super Admin approved

**Response Time Standards:**
- Payroll issues: Within 1 hour
- Attendance corrections: Same day
- General inquiries: Within 4 hours

### 7.4 Daily Morning Routine

**At 8:00 AM - 9:00 AM:**

1. **Log In**
   - Access the system
   - Check dashboard for alerts

2. **Review Dashboard**
   - Check summary cards
   - Review recent activity
   - Note any unusual items

3. **Check Attendance**
   - Go to Attendance page
   - Select "Unmarked" view
   - Note employees without clock-in
   - Contact supervisors for status

4. **Check Notifications**
   - Click bell icon
   - Review any alerts
   - Address urgent items

5. **Review Overnight Activity**
   - Check for system issues
   - Verify cron jobs ran
   - Note any errors

### 7.5 End-of-Day Routine

**At 5:00 PM:**

1. **Final Attendance Check**
   - Go to Attendance page
   - Switch to "Marked" view
   - Verify all employees clocked out
   - Note any missing time-outs

2. **Review Pending Items**
   - Check for items needing follow-up
   - Document any issues for next day

3. **Log Out**
   - Click your profile icon
   - Select "Logout"
   - Close browser (recommended)

---

## 8. Troubleshooting

### 8.1 Common Issues

#### Issue: Cannot Log In
**Symptoms:** "Invalid credentials" error

**Steps to Resolve:**
1. Verify caps lock is off
2. Check you're using correct email (not employee code)
3. Try password again
4. If still failing → Contact Super Admin for password reset

#### Issue: Page Won't Load
**Symptoms:** Blank page or error message

**Steps:**
1. Refresh the page (F5)
2. Clear browser cache
3. Try different browser
4. Check internet connection
5. If still failing → Contact Developer/IT

#### Issue: Attendance Won't Save
**Symptoms:** Error when marking attendance

**Steps:**
1. Check all required fields filled
2. Verify date selected
3. Add required note
4. Try again
5. If still failing → Document manually, notify Super Admin

#### Issue: Report Won't Generate
**Symptoms:** Loading forever or error

**Steps:**
1. Reduce date range (try 1 week instead of 1 month)
2. Filter by specific branch
3. Try again
4. If still failing → Contact Super Admin

### 8.2 Contact Escalation

| Issue | First Contact | Escalate To |
|-------|---------------|-------------|
| Password reset | Super Admin | - |
| System error | Developer | - |
| Payroll calculation error | Super Admin | Developer |
| Policy question | HR Manager | - |
| Access issues | Super Admin | Developer |

### 8.3 Emergency Contacts

**Keep These Accessible:**

| Role | Contact | When to Contact |
|------|---------|-----------------|
| Super Admin | [Admin Email/Phone] | Approval needed, system issues |
| Developer | [Dev Email] | Technical bugs, errors |
| HR Manager | [HR Email] | Policy questions |
| IT Support | [IT Email] | Login, browser issues |

---

## Quick Reference Card

### Most Used URLs
```
Dashboard:        /employee/dashboard.php
Employees:        /employee/employees.php
Attendance:       /employee/attendance.php
Payroll:          /employee/payroll.php
Reports:          /employee/weekly_report.php
Settings:         /employee/settings.php
```

### Daily Task Checklist
- [ ] 8:00 AM - Log in
- [ ] 9:00 AM - Check unmarked attendance
- [ ] 9:30 AM - Follow up on missing clock-ins
- [ ] 2:00 PM - Handle employee inquiries
- [ ] 5:00 PM - Final attendance check
- [ ] 5:15 PM - Log out

### Weekly Task Checklist
- [ ] Friday 9:00 AM - Payroll review
- [ ] Friday 10:00 AM - Generate deployment report
- [ ] Friday 2:00 PM - Send reports to management

### Monthly Task Checklist
- [ ] 1st of month - Verify leave credits posted
- [ ] Last Friday - Final monthly payroll review
- [ ] Monthly - Check audit trail

---

**Document Version:** 1.0  
**Last Updated:** April 2026  
**Next Review:** July 2026

---

**END OF ADMIN USER MANUAL**
