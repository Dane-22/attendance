# JAJR Attendance & Payroll System - User Manual

## Table of Contents
1. [Introduction](#1-introduction)
2. [Getting Started](#2-getting-started)
3. [User Roles & Permissions](#3-user-roles--permissions)
4. [Login & Authentication](#4-login--authentication)
5. [Dashboards](#5-dashboards)
6. [Employee Management](#6-employee-management)
7. [Attendance Tracking](#7-attendance-tracking)
8. [Payroll Management](#8-payroll-management)
9. [Branch & Location Management](#9-branch--location-management)
10. [Overtime Management](#10-overtime-management)
11. [Cash Advance & Loans](#11-cash-advance--loans)
12. [Leave Management](#12-leave-management)
13. [Reports & Analytics](#13-reports--analytics)
14. [Notifications](#14-notifications)
15. [Settings & Profile](#15-settings--profile)
16. [System Administration](#16-system-administration)
17. [API Reference](#17-api-reference)
18. [Troubleshooting](#18-troubleshooting)

---

## 1. Introduction

### 1.1 System Overview
The **JAJR Attendance & Payroll System** is a comprehensive web-based solution designed for JAJR Company to manage employee attendance, track work hours, process payroll, and handle related HR operations. The system supports multiple user roles, branch locations, and automated payroll calculations.

### 1.2 Key Features
- **Multi-Role Support:** Employee, Admin, Super Admin, Developer, Engineer, Security Guard
- **Multiple Clock-In Methods:** QR Code, Face Recognition, Manual Admin Entry
- **Geolocation & Geofencing:** GPS-based location validation for attendance
- **Automated Payroll:** Daily and weekly automated calculations
- **Overtime Management:** Request and approval workflow
- **Cash Advance Tracking:** Employee loans and deductions
- **Branch Management:** Multiple location support with transfers
- **Real-time Notifications:** Push notifications for approvals and alerts
- **Comprehensive Reporting:** Weekly, monthly, and custom date range reports

### 1.3 System Requirements
- **Browser:** Chrome, Firefox, Safari, Edge (latest versions)
- **Internet:** Stable internet connection
- **Camera:** Required for QR scanning and Face Recognition
- **GPS:** Required for location-based clock-in

---

## 2. Getting Started

### 2.1 Accessing the System
1. Open your web browser
2. Navigate to: `https://your-domain.com/main/`
3. Click **"Log In"** button on the landing page
4. Enter your credentials (see Section 4)

### 2.2 Landing Page
The public landing page (`index.php`) displays:
- Company information and branding
- Services overview
- Project portfolio
- Contact information
- Login button

### 2.3 First-Time Login
New employees should:
1. Receive login credentials from HR/Admin
2. Log in and immediately change password
3. Update profile information
4. Upload profile photo (optional)

---

## 3. User Roles & Permissions

### 3.1 Role Hierarchy

| Role | Description | Access Level |
|------|-------------|--------------|
| **Employee** | Regular staff member | Personal attendance, profile, requests |
| **Security Guard** | Security personnel | No deductions, basic attendance |
| **Engineer** | Engineering staff | Site management, project tracking |
| **Admin** | Department administrator | Employee management, reports, payroll review |
| **Super Admin** | System administrator | Full access, approvals, configuration |
| **Developer** | Technical support | System configuration, debugging |

### 3.2 Permission Matrix

| Feature | Employee | Security | Engineer | Admin | Super Admin | Developer |
|---------|----------|----------|----------|-------|-------------|-----------|
| Clock In/Out | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| View Own Attendance | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| View All Attendance | ✗ | ✗ | ✗ | ✓ | ✓ | ✓ |
| Mark Attendance (Others) | ✗ | ✗ | ✗ | ✓ | ✓ | ✓ |
| Payroll Reports | Own only | Own only | Own only | All | All | All |
| Employee Management | ✗ | ✗ | ✗ | ✓ | ✓ | ✓ |
| Overtime Approval | ✗ | ✗ | ✗ | ✗ | ✓ | ✓ |
| Cash Advance Approval | ✗ | ✗ | ✗ | ✗ | ✓ | ✓ |
| System Settings | ✗ | ✗ | ✗ | ✗ | ✓ | ✓ |
| Branch Management | ✗ | ✗ | ✗ | ✓ | ✓ | ✓ |

---

## 4. Login & Authentication

### 4.1 Login Methods
The system supports multiple authentication methods:

#### 4.1.1 Standard Login
1. Navigate to `login.php`
2. Enter **Employee Code** or **Email**
3. Enter **Password**
4. Click **"Log In"**

#### 4.1.2 Procurement SSO Integration
- Employees can use Procurement API credentials
- Automatic sync with procurement system
- Single sign-on capability

### 4.2 Password Requirements
- Minimum 8 characters
- Mix of letters and numbers recommended
- Special characters supported

### 4.3 Password Reset
1. Contact HR/Admin to request password reset
2. Admin will generate temporary password
3. Employee must change password on next login

### 4.4 Session Management
- Sessions expire after 24 hours of inactivity
- Auto-logout for security
- QR scan sessions are temporary (marked with `qr_temp_session`)

---

## 5. Dashboards

### 5.1 Admin Dashboard (`employee/dashboard.php`)

**Access:** Admin, Super Admin only

**Features:**
- **Summary Cards:**
  - Total Employees count
  - Active Branches count
  - Transfers Today count
  - Pending Payroll count
- **Recent Transfers:** Last 5 employee transfers
- **Recent Activity:** Last 5 system activities
- **Quick Actions:** Links to common tasks

### 5.2 Engineer Dashboard (`employee/eng_dashboard.php`)

**Access:** Engineer, Admin, Developer

**Features:**
- **Sites Overview:** Total active sites/branches
- **Pending Requests:** Overtime and leave requests count
- **Current Branch:** Display assigned branch
- **Attendance Status:** Today's clock-in status
- **Transfer Options:** Request branch transfers

### 5.3 Employee Portal (`employee/select_employee.php`)

**Access:** All employees

**Features:**
- Personal attendance recording (QR scan mode)
- Branch selection for transfers
- Today's status display
- Clock In/Out buttons

---

## 6. Employee Management

### 6.1 Employee List (`employee/employees.php`)

**Access:** Admin, Super Admin, Developer

**Features:**
- View all employees in list or grid view
- Search by name, code, email, or position
- Pagination (10/25/50/100 per page)
- View employee profiles
- Add new employees (Super Admin only)

### 6.2 Adding New Employees

**Prerequisites:** Super Admin role

**Steps:**
1. Click **"Add Employee"** button
2. Fill in required fields:
   - Employee Code (unique)
   - First Name, Last Name
   - Email address
   - Position/Role
   - Daily Rate
   - Branch assignment
3. Generate initial password
4. Upload profile photo (optional)
5. Save record

### 6.3 Employee Profile Management

**Editable Fields:**
- Personal information (name, contact)
- Position and daily rate
- Branch assignment
- Profile photo
- Deduction flags
- Status (Active/Inactive)

### 6.4 QR Code Generation
Each employee has a unique QR code for clock-in/out:
- Auto-generated upon employee creation
- Can be regenerated if needed
- Used with mobile scanner or webcam

---

## 7. Attendance Tracking

### 7.1 Clock-In Methods

#### 7.1.1 QR Code Scanning
**File:** `employee/select_employee.php`

**Process:**
1. Navigate to employee portal
2. Click **"Scan QR Code"**
3. Position QR code in camera view
4. System auto-detects and records time-in
5. Scan again to time-out

**Requirements:**
- Camera access enabled
- Valid employee QR code
- Within geofence radius (if enabled)

#### 7.1.2 Face Recognition
**Files:** `verify_face_api.php`, `enroll_face_api.php`

**Enrollment (First Time):**
1. Admin enrolls employee face
2. Multiple angles captured
3. Face data stored securely

**Verification:**
1. Select "Face Recognition" mode
2. Look at camera
3. System matches face to employee
4. Records attendance

#### 7.1.3 Manual Admin Entry
**File:** `employee/attendance.php`

**Use Cases:**
- Employee forgot phone/QR card
- System malfunction
- Field work without app access
- Retroactive corrections

**Process:**
1. Admin navigates to Attendance page
2. Selects date
3. Finds employee
4. Marks as **Present** or **Absent**
5. System logs admin action

### 7.2 Attendance Status Types

| Status | Description | Icon |
|--------|-------------|------|
| **Present** | Normal attendance | 🟢 Green |
| **Late** | Arrived after schedule | 🟡 Yellow |
| **Absent** | Did not report | 🔴 Red |
| **On Leave** | Approved leave | 🔵 Blue |
| **Half-Day** | Partial attendance | 🟠 Orange |

### 7.3 Geofencing & Location Validation
**File:** `employee/api/validate_geofence.php`

**Features:**
- GPS coordinates validation
- Configurable radius per branch (default: 100m)
- Location verification before clock-in
- Transfer detection for multi-branch work

### 7.4 Attendance Reports
**File:** `employee/attendance.php`

**Views:**
- **Unmarked:** Employees without attendance for date
- **Marked:** Employees with recorded attendance
- **Branch Filter:** Filter by specific branch

---

## 8. Payroll Management

### 8.1 Payroll Dashboard (`employee/payroll.php`)

**Access:** Admin, Super Admin, Developer

**Views:**
- **Weekly View:** Week-by-week breakdown
- **Monthly View:** Full month summary

### 8.2 Payroll Calculation Components

#### 8.2.1 Basic Pay
```
Basic Pay = Days Worked × Daily Rate
```

#### 8.2.2 Overtime Pay
```
Hourly Rate = Daily Rate ÷ 8
OT Pay = Total OT Hours × Hourly Rate
```

#### 8.2.3 Standard Deductions

| Deduction | Rate | Cap |
|-----------|------|-----|
| **SSS** | 4.5% of gross | ₱1,125 |
| **PhilHealth** | 3.5% of gross | ₱2,450 |
| **Pag-IBIG** | Fixed | ₱100 |

#### 8.2.4 Special Deduction Rules

**Security Guards:**
- No SSS, PhilHealth, or Pag-IBIG deductions
- Full gross pay received

**Branch 33 (Monthly):**
- Deductions applied only on Week 4

**Other Branches (Weekly):**
- Deductions applied on Weeks 1-3
- No deductions on Week 4

### 8.3 Automated Payroll Processes

#### 8.3.1 Daily Calculation
**Script:** `employee/cron/daily_payroll_calculation.php`
**Schedule:** Daily at 12:00 AM
**Function:** Calculates payroll for previous day

#### 8.3.2 Weekly Calculation
**Script:** `employee/cron/weekly_payroll_calculation.php`
**Schedule:** Every Friday at 12:00 AM
**Function:** Aggregates daily data into weekly reports

### 8.4 Payroll Reports

**Available Reports:**
1. **Weekly Payroll Report** - Week-by-week pay summary
2. **Monthly Payroll Report** - Full month aggregation
3. **Individual Employee Report** - Single employee history
4. **Branch Payroll Report** - Filtered by branch

**Report Columns:**
- Employee Name & Code
- Days Worked
- Daily Rate
- Basic Pay
- OT Hours & Amount
- Allowances
- Deductions (SSS, PhilHealth, Pag-IBIG)
- Cash Advance Deductions
- Net Pay

---

## 9. Branch & Location Management

### 9.1 Branch List (`employee/branch_location_manager.php`)

**Access:** Admin, Super Admin

**Features:**
- View all branches on interactive map
- Edit GPS coordinates
- Set geofence radius
- Activate/Deactivate branches
- Batch import coordinates

### 9.2 Adding New Branches

**Steps:**
1. Navigate to Branch Manager
2. Click "Add Branch"
3. Enter:
   - Branch Name
   - Address
   - GPS Coordinates (lat/long)
   - Geofence Radius
4. Save

### 9.3 Geofence Configuration

**Default Settings:**
- Radius: 100 meters
- Verification: Required for clock-in

**Per-Branch Customization:**
- Adjust radius based on site size
- Disable for remote workers (if applicable)

### 9.4 Employee Transfers

**Process:**
1. Employee requests transfer (if applicable)
2. Admin approves and updates branch assignment
3. System tracks transfer date
4. Payroll follows new branch rules

---

## 10. Overtime Management

### 10.1 Overtime Types

| Type | Rate Multiplier | Approval Required |
|------|-----------------|-------------------|
| Regular OT | 1.25x | Yes |
| Night Differential | +10% | Yes |
| Rest Day | 1.3x | Yes |
| Holiday | 2.0x | Yes |

### 10.2 Requesting Overtime

**For Employees:**
1. Navigate to Overtime page
2. Click "Request Overtime"
3. Select date and hours
4. Provide reason
5. Submit for approval

**For Admins (Pre-approved OT):**
1. Direct entry in attendance record
2. Mark OT hours
3. Auto-approved status

### 10.3 Approval Workflow
**File:** `employee/notification.php`

**Steps:**
1. Request submitted → Status: Pending
2. Super Admin/Admin reviews
3. **Approve:** OT added to payroll
4. **Reject:** Reason provided, employee notified

### 10.4 Overtime Reports
**File:** `employee/overtime.php`

**Features:**
- Filter by date range
- Filter by branch
- Filter by status (Pending/Approved/Rejected)
- Export to Excel/PDF

---

## 11. Cash Advance & Loans

### 11.1 Cash Advance Request
**File:** `employee/cash_advance.php`

**Process:**
1. Employee requests cash advance
2. Enters amount and reason
3. Digital signature capture
4. Submits for approval
5. Admin reviews and approves/rejects

### 11.2 Loan Tracking

**Features:**
- View outstanding balance
- Payment history
- Automatic payroll deduction
- SSS loan tracking

### 11.3 Deduction in Payroll

Cash advances are automatically deducted from payroll:
- Deducted from net pay
- Configurable deduction amount
- Priority over other deductions

---

## 12. Leave Management

### 12.1 Leave Types

| Type | Description | Credit Source |
|------|-------------|---------------|
| **Sick Leave** | Health-related absences | Monthly credit |
| **Vacation Leave** | Personal time off | Monthly credit |
| **Emergency Leave** | Urgent matters | Balance available |

### 12.2 Leave Credits

**Monthly Credit:**
- Auto-credited via cron job
- Based on employment status
- Accrues over time

**File:** `employee/cron/monthly_leave_credit.php`

### 12.3 Leave Request Process

1. Employee submits leave request
2. Select dates and leave type
3. Provide reason/documentation
4. Manager/Admin approval
5. Deducted from available balance

### 12.4 Leave Balance Inquiry

Employees can view:
- Available sick leave balance
- Available vacation leave balance
- Used leave history
- Pending requests

---

## 13. Reports & Analytics

### 13.1 Weekly Deployment Report
**File:** `employee/weekly_report.php`

**Features:**
- Employee deployment by branch
- Attendance summary
- Present/Absent/Late counts
- Export options (Excel, PDF)

### 13.2 Audit Reports
**File:** `employee/audit.php`

**Tracks:**
- All attendance changes
- Who made changes
- Timestamps
- Before/after values

### 13.3 Individual Reports
**File:** `employee/individual_report_selector.php`

**Generate reports for single employee:**
- Attendance history
- Payroll history
- Overtime summary
- Cash advance ledger

### 13.4 Analytics Dashboard
**File:** `employee/analytics.php`

**Visual Charts:**
- Attendance trends
- Branch utilization
- Employee statistics
- Payroll summaries

### 13.5 Export Options

**Formats:**
- **Excel (.xlsx):** `export_attendance_excel.php`
- **PDF:** `export_logs_analytics_pdf.php`
- **CSV:** Available in most reports

---

## 14. Notifications

### 14.1 Notification Types

| Type | Trigger | Recipients |
|------|---------|------------|
| **Overtime Request** | Employee submits OT | Admin, Super Admin |
| **Cash Advance** | Employee requests CA | Admin, Super Admin |
| **Transfer Request** | Employee requests transfer | Admin |
| **Leave Request** | Employee requests leave | Manager, Admin |
| **System Alerts** | Errors, failures | Developers |
| **Payroll Ready** | Weekly calculation complete | Admin |

### 14.2 My Notifications
**File:** `employee/my_notifications.php`

**Features:**
- View all notifications
- Mark as read/unread
- Filter by type
- Archive old notifications

### 14.3 Admin Notification Center
**File:** `employee/admin_notification.php`

**Dashboard for:**
- Pending approvals count
- Quick approve/reject actions
- Notification history
- Bulk actions

### 14.4 Push Notifications
**API:** `employee/api/save_push_subscription.php`

**Setup:**
1. Browser asks for notification permission
2. Service worker registration
3. Push subscription saved
4. Real-time notifications enabled

---

## 15. Settings & Profile

### 15.1 Profile Settings (`employee/settings.php`)

**Editable Information:**
- Profile photo
- Contact details
- Password change
- Notification preferences

### 15.2 System Tools (Admin Only)

**Available to:** Super Admin, Admin

**Tools:**
- Database optimization
- Cache clearing
- Log viewing
- System diagnostics

### 15.3 Signature Settings
**File:** `employee/signature_settings.php`

**Purpose:**
- Upload digital signature
- Required for cash advance requests
- E-signature validation

**Process:**
1. Navigate to Signature Settings
2. Draw or upload signature image
3. Save to profile
4. Used for document signing

### 15.4 Theme Settings

**Available Themes:**
- Light Theme
- Dark Theme (default)
- Auto (system preference)

---

## 16. System Administration

### 16.1 API Key Management
**File:** `employee/api_key_management.php`

**Features:**
- Generate API keys
- Set permissions
- Monitor usage
- Revoke access

### 16.2 Cron Job Monitoring
**Location:** `employee/cron/`

**Monitor Logs:**
- `daily_payroll_calculation.log`
- `weekly_payroll_calculation.log`
- `monthly_leave_credit.log`

### 16.3 Database Maintenance

**Common Tasks:**
- Backup database
- Optimize tables
- Clear old logs
- Archive historical data

### 16.4 User Management

**Actions:**
- Reset passwords
- Lock/unlock accounts
- Change roles
- Deactivate employees

---

## 17. API Reference

### 17.1 Authentication APIs

#### Login API
**Endpoint:** `login_api.php`
**Method:** POST
**Parameters:**
- `identifier` (email or employee_code)
- `password`

**Response:**
```json
{
  "success": true,
  "employee_id": 123,
  "position": "Employee",
  "token": "..."
}
```

### 17.2 Attendance APIs

#### Clock In
**Endpoint:** `employee/api/clock_in.php`
**Method:** POST
**Parameters:**
- `employee_id`
- `branch_name`
- `latitude` (optional)
- `longitude` (optional)

#### Clock Out
**Endpoint:** `employee/api/clock_out.php`
**Method:** POST
**Parameters:**
- `employee_id`
- `branch_name`

#### QR Clock
**Endpoint:** `employee/api/qr_clock.php`
**Method:** POST
**Parameters:**
- `employee_code`
- `qr_data`

#### Validate Geofence
**Endpoint:** `employee/api/validate_geofence.php`
**Method:** POST
**Parameters:**
- `latitude`
- `longitude`
- `branch_id`

### 17.3 Employee Data APIs

#### Get Employee Attendance
**Endpoint:** `employee/api/get_employee_attendance_detailed.php`
**Method:** GET
**Parameters:**
- `employee_id`
- `start_date`
- `end_date`

#### Get Branch Employees
**Endpoint:** `employee/api/get_branch_employees.php`
**Method:** GET
**Parameters:**
- `branch_id`

### 17.4 Payroll APIs

#### Get Payroll Report
**Endpoint:** `get_payroll_report.php`
**Method:** GET
**Parameters:**
- `month` (YYYY-MM)
- `week` (1-5)
- `branch` (optional)

### 17.5 Dashboard Analytics

#### Get Dashboard Data
**Endpoint:** `employee/api/get_dashboard_analytics.php`
**Method:** GET
**Response:** Employee counts, attendance stats, branch data

---

## 18. Troubleshooting

### 18.1 Common Issues & Solutions

#### Cannot Clock In - "Outside Geofence"
**Cause:** GPS location outside branch radius
**Solution:**
1. Verify you're at the correct branch
2. Check GPS is enabled
3. Contact admin to verify coordinates
4. Admin can adjust geofence radius

#### QR Code Not Scanning
**Cause:** Camera issues or damaged QR
**Solution:**
1. Allow camera permissions in browser
2. Clean camera lens
3. Ensure good lighting
4. Try manual admin entry

#### Payroll Calculation Incorrect
**Cause:** Missing attendance or wrong rate
**Solution:**
1. Verify all days have attendance marked
2. Check daily rate in employee profile
3. Review deductions configuration
4. Re-run payroll calculation

#### Cannot Login - "Invalid Credentials"
**Cause:** Wrong password or inactive account
**Solution:**
1. Check caps lock
2. Verify employee code/email
3. Contact admin to reset password
4. Verify account is "Active"

#### Session Expired Error
**Cause:** Inactivity timeout
**Solution:**
1. Log in again
2. Save work frequently
3. Check session settings with admin

### 18.2 Contact Support

| Issue Type | Contact |
|------------|---------|
| Login Problems | HR Department |
| Attendance Disputes | Direct Supervisor |
| Payroll Issues | Payroll Admin |
| Technical Errors | IT Support / Developer |
| Feature Requests | System Admin |

### 18.3 System Status

**Check these locations for issues:**
- `employee/cron/*.log` - Cron job logs
- `api_debug.log` - API error logs
- `update_allowance_errors.log` - Payroll errors

### 18.4 Backup & Recovery

**Database Backup:**
- Automated daily backups
- 30-day retention
- Contact Developer for restoration

**Data Export:**
- Regular exports recommended
- Use Excel/PDF export features
- Keep local copies of important reports

---

## Appendix A: Quick Reference

### A.1 File Locations

| Module | File Path |
|--------|-----------|
| Login | `login.php` |
| Dashboard | `employee/dashboard.php` |
| Employees | `employee/employees.php` |
| Attendance | `employee/attendance.php` |
| Payroll | `employee/payroll.php` |
| Overtime | `employee/overtime.php` |
| Cash Advance | `employee/cash_advance.php` |
| Reports | `employee/weekly_report.php` |
| Settings | `employee/settings.php` |
| Notifications | `employee/my_notifications.php` |

### A.2 Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| `Ctrl + S` | Save form |
| `Esc` | Close modal |
| `Ctrl + F` | Search page |

### A.3 Philippine Payroll Calendar 2026

| Month | Week 4/5 Payday |
|-------|-----------------|
| January | Jan 30 |
| February | Feb 27 |
| March | Mar 30 |
| April | Apr 29 |
| May | May 28 |
| June | Jun 29 |
| July | Jul 30 |
| August | Aug 28 |
| September | Sep 29 |
| October | Oct 29 |
| November | Nov 27 |
| December | Dec 23 |

---

**Document Version:** 1.0  
**Last Updated:** April 2026  
**System Version:** JAJR Attendance & Payroll System  
**Support Email:** jajrconstruction5@gmail.com

---

**END OF USER MANUAL**
