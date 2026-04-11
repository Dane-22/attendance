# Standard Operating Procedure (SOP) - Engineer Role

## Document Information
- **Role:** Engineer / Site Engineer
- **Access Level:** 4 - Site Management & Engineering Functions
- **Version:** 1.0
- **Effective Date:** April 2026

---

## Table of Contents
1. [Role Overview](#1-role-overview)
2. [Access & Permissions](#2-access--permissions)
3. [Daily Operations](#3-daily-operations)
4. [Site Management](#4-site-management)
5. [Attendance & Time Tracking](#5-attendance--time-tracking)
6. [Transfer Management](#6-transfer-management)
7. [Reporting](#7-reporting)
8. [Communication & Notifications](#8-communication--notifications)

---

## 1. Role Overview

### 1.1 Purpose
The Engineer role supports site-based operations including project tracking, site management, employee coordination at project locations, and personal attendance tracking. Engineers have enhanced visibility into branch operations and can manage transfers between sites.

### 1.2 Responsibilities
- Track personal attendance at project sites
- View site deployment and manpower
- Request and manage site transfers
- Monitor overtime requests
- Coordinate with site teams
- View project-related reports
- Manage engineering dashboard

### 1.3 Typical Users
- Site Engineers
- Project Engineers
- Field Engineers
- Engineering Supervisors

---

## 2. Access & Permissions

### 2.1 Available Modules

| Module | Access | Notes |
|--------|--------|-------|
| **Engineer Dashboard** | Full | Site overview, projects, transfers |
| **Personal Attendance** | Full | Clock in/out, view history |
| **Site Overview** | View | Branches, employee counts |
| **Transfer Module** | Request/View | Request transfers, view status |
| **Overtime** | Request | Submit OT requests |
| **Reports** | Personal | Own attendance and payroll only |
| **Settings** | Personal | Profile, password, signature |

### 2.2 Permission Details

**CAN DO:**
- View own attendance records
- Clock in/out at assigned sites
- View site deployment data
- Request site transfers
- Submit overtime requests
- View personal payroll reports
- Update own profile

**CANNOT:**
- View other employees' detailed records
- Mark attendance for others
- Approve any requests
- Access payroll calculations
- Edit branch information
- Create/delete employees
- Access admin functions

---

## 3. Daily Operations

### 3.1 Morning Routine

**Upon Arrival at Site:**
1. Open system on mobile/desktop
2. Navigate to `employee/eng_dashboard.php`
3. Click "Clock In" or scan QR code
4. Verify location detected correctly
5. Confirm clock-in success

**Dashboard Review:**
- Check "Today's Status" card
- Review assigned branch
- View pending requests
- Check notifications

### 3.2 End of Day

**Before Leaving Site:**
1. Open Engineer Dashboard
2. Click "Clock Out"
3. Verify time recorded
4. Confirm site location correct
5. Check tomorrow's assignment if changed

**If Transferring to Another Site:**
1. Clock out at current site
2. Travel to new site
3. Clock in at new site
4. System records split day

### 3.3 Site Transfers During Day

**Scenario 1: Pre-Planned Transfer**
1. Submit transfer request in advance
2. Wait for approval
3. On transfer day:
   - Clock out at Site A
   - Travel to Site B
   - Clock in at Site B
4. Both sites recorded

**Scenario 2: Emergency Transfer**
1. Contact Admin/Supervisor
2. Request temporary assignment
3. Clock out at current site
4. Clock in at new site
5. Admin updates records retroactively

---

## 4. Site Management

### 4.1 Engineer Dashboard Overview

**Navigate to:** `employee/eng_dashboard.php`

**Dashboard Cards:**

| Card | Information Displayed |
|------|---------------------|
| **Total Sites** | Number of active project sites |
| **Active Projects** | Projects with current activity |
| **Pending Requests** | Your pending OT and transfer requests |
| **Today's Status** | Clock-in status for current day |
| **Current Branch** | Assigned project site |

### 4.2 Site Selection

**When Assigned to Multiple Sites:**
1. Dashboard shows "Select Branch" option
2. Choose current working site from dropdown
3. System validates location
4. Proceed with clock-in

**Location Validation:**
- Must be within geofence radius
- GPS coordinates verified
- If outside radius: Contact Admin for override

### 4.3 Viewing Site Information

**Available Site Data:**
- Branch name and address
- GPS coordinates
- Active employee count
- Recent transfers
- Site status

**Navigation:**
1. From Dashboard, click on site name
2. View site details
3. See who else is assigned
4. Check site contact information

---

## 5. Attendance & Time Tracking

### 5.1 Clock-In Methods for Engineers

**Method 1: Engineer Dashboard Clock-In**
1. Navigate to `employee/eng_dashboard.php`
2. Locate "Clock In" button
3. System auto-detects branch
4. Click to record time-in
5. Confirmation message displays

**Method 2: QR Code Scan**
1. Open `employee/select_employee.php`
2. Click "Scan QR Code"
3. Hold personal QR code to camera
4. Auto-records time and location
5. Visual/audio confirmation

**Method 3: Mobile Access**
- Use smartphone browser
- Access same URLs as desktop
- GPS location required
- Mobile-optimized interface

### 5.2 Viewing Attendance History

**Steps:**
1. Navigate to attendance section
2. Select date range
3. View calendar or list view
4. Check status for each day:
   - 🟢 Present
   - 🟡 Late
   - 🔴 Absent
   - 🔵 On Leave

**Report Generation:**
1. Select "Individual Report"
2. Choose "Attendance History"
3. Set date range
4. Generate report
5. Export if needed for personal records

### 5.3 Handling Attendance Issues

**Forgot to Clock In:**
1. Contact immediate supervisor
2. Request admin to mark attendance
3. Provide exact time of arrival
4. Supervisor confirms
5. Admin marks retroactively

**Wrong Site Recorded:**
1. Note the error
2. Contact Admin immediately
3. Provide correct site information
4. Admin will correct record
5. Verify correction made

**GPS/Geofence Issues:**
1. Ensure location services enabled
2. Check internet connection
3. Try manual clock-in option
4. Contact Admin if persistent
5. Admin can override geofence

---

## 6. Transfer Management

### 6.1 Requesting Site Transfer

**Navigate to:** `employee/transfer_module.php` or Dashboard

**Steps:**
1. Click "Request Transfer"
2. Fill transfer form:
   - Current site (auto-filled)
   - Destination site (dropdown)
   - Transfer date
   - Duration (temporary/permanent)
   - Reason for transfer
3. Submit request
4. System notifies Admin/Supervisor
5. Await approval

**Approval Timeline:**
- Standard requests: 24-48 hours
- Emergency requests: Same day (contact directly)

### 6.2 Transfer Types

**Temporary Transfer:**
- Duration: 1 day to 2 weeks
- Returns to home site after
- Payroll follows actual work location

**Permanent Transfer:**
- Changes home branch assignment
- Long-term or permanent reassignment
- Requires management approval

**Day Transfer (Same Day):**
- Work morning at Site A
- Work afternoon at Site B
- Both sites recorded automatically

### 6.3 Tracking Transfer Status

**View Pending Transfers:**
1. Open Engineer Dashboard
2. Check "Pending Requests" card
3. Click to view details
4. Status shown:
   - Pending (awaiting approval)
   - Approved (ready to execute)
   - Completed (finished)
   - Rejected (see reason)

**After Approval:**
1. Receive notification
2. Confirm transfer details
3. Execute on approved date
4. Clock out at old site
5. Clock in at new site

---

## 7. Reporting

### 7.1 Personal Payroll Reports

**Navigate to:** `employee/individual_report_selector.php`

**Generate Payroll Report:**
1. Select report type: "Payroll History"
2. Choose date range
3. Click "Generate"
4. Review:
   - Days worked
   - Daily rate
   - Basic pay
   - Overtime pay
   - Deductions
   - Net pay
5. Export to PDF or Excel

**Understanding Your Payslip:**
- **Basic Pay:** Days worked × Daily rate
- **OT Pay:** Overtime hours × (Daily rate ÷ 8) × 1.25
- **Gross Pay:** Basic + OT + Allowances
- **Deductions:** SSS + PhilHealth + Pag-IBIG + CA
- **Net Pay:** What you receive

### 7.2 Attendance Reports

**Monthly Attendance Summary:**
1. Select "Attendance History"
2. Set month range
3. Generate report
4. Review:
   - Total days present
   - Days absent
   - Late arrivals
   - On leave days

**For Dispute Resolution:**
- Save report as evidence
- Note any discrepancies
- Contact Admin for corrections

### 7.3 Overtime Reports

**View Your OT History:**
1. Navigate to Overtime section
2. View list of OT requests
3. Status indicators:
   - 🟡 Pending
   - 🟢 Approved
   - 🔴 Rejected
4. Check hours and pay calculation

---

## 8. Communication & Notifications

### 8.1 My Notifications

**Navigate to:** `employee/my_notifications.php`

**Notification Types:**
- Transfer request updates
- Overtime request responses
- Attendance confirmations
- System announcements
- Policy updates

**Managing Notifications:**
- View all notifications
- Mark as read
- Archive old notifications
- Set notification preferences

### 8.2 Push Notifications Setup

**Enable Push Notifications:**
1. Go to Settings
2. Click "Notifications"
3. Click "Enable Push"
4. Allow browser permission
5. Test notification

**Benefits:**
- Instant transfer approval alerts
- Clock-in reminders
- OT request responses
- System updates

### 8.3 Contact & Support

**For Engineers - Who to Contact:**

| Issue | Contact | Method |
|-------|---------|--------|
| Attendance correction | Admin | System message or email |
| Transfer approval delay | Supervisor | Direct contact |
| Payroll questions | Payroll Admin | Email |
| Site/technical issues | Developer | Through Admin |
| Access issues | Super Admin | Email |

**Emergency Contacts:**
- Site Supervisor: [Local number]
- Admin Office: [Admin email]
- IT Support: [IT email]

---

## Quick Reference

### Daily Checklist
- [ ] Clock in at assigned site (morning)
- [ ] Verify correct site recorded
- [ ] Check dashboard for updates
- [ ] Clock out at end of day
- [ ] Confirm time-out recorded

### Weekly Tasks
- [ ] Review attendance for accuracy
- [ ] Submit any OT requests
- [ ] Check transfer status (if applicable)
- [ ] Review notifications

### Monthly Tasks
- [ ] Generate personal payroll report
- [ ] Verify all days accounted for
- [ ] Report any discrepancies
- [ ] Update profile if needed

### Common URL Quick Access

| Function | URL |
|----------|-----|
| Engineer Dashboard | `/employee/eng_dashboard.php` |
| Clock In/Out | `/employee/select_employee.php` |
| Transfer Request | `/employee/transfer_module.php` |
| My Reports | `/employee/individual_report_selector.php` |
| Settings | `/employee/settings.php` |
| Notifications | `/employee/my_notifications.php` |

---

## Troubleshooting for Engineers

### Cannot Clock In
**Issue:** Geofence error or system not responding
**Solution:**
1. Check GPS is enabled on device
2. Verify internet connection
3. Try refreshing page
4. Contact Admin for manual entry
5. Document time of attempt

### Transfer Not Approved
**Issue:** Request pending too long
**Solution:**
1. Check notification for updates
2. Contact supervisor directly
3. Verify request details correct
4. Resubmit if needed
5. Escalate to Super Admin if urgent

### Wrong Site Assignment
**Issue:** Dashboard shows wrong branch
**Solution:**
1. Check "Current Branch" card
2. Contact Admin to verify assignment
3. Request correction if needed
4. Confirm in employee profile

### Overtime Not Showing in Payroll
**Issue:** Worked OT but not paid
**Solution:**
1. Check OT request status (should be "Approved")
2. Verify correct date
3. Contact Admin to check if OT recorded in attendance
4. Request correction if missing

---

**Document Version:** 1.0  
**Last Updated:** April 2026  
**Next Review:** July 2026

---

**END OF ENGINEER SOP**
