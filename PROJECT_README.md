# JAJR Company — Employee Attendance Management System

> **Engineering the Future** — A comprehensive web-based employee management, attendance tracking, and payroll system for JAJR Company.

---

## Table of Contents

- [Overview](#overview)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Database Schema](#database-schema)
- [Features](#features)
- [Installation & Setup](#installation--setup)
- [Configuration](#configuration)
- [API Endpoints](#api-endpoints)
- [User Roles](#user-roles)
- [File Reference](#file-reference)

---

## Overview

This is a full-featured **Employee Attendance and Management System** developed for **JAJR Company**, a construction and engineering firm. The system handles employee attendance tracking, payroll management, branch assignments, cash advances, billing, and comprehensive reporting.

### Key Capabilities

- **Attendance Tracking** — Clock in/out with automatic time calculations
- **Multi-Branch Support** — Manage employees across multiple construction sites
- **Payroll Management** — Weekly payroll reports with automatic calculations
- **Cash Advances** — Track and manage employee cash advance requests
- **Analytics Dashboard** — Visual reports with Chart.js integration
- **Activity Logging** — Full audit trail of system actions
- **Role-Based Access** — Super Admin, Admin, and Employee access levels
- **AI Chat Assistant** — Built-in AI support widget for employee queries

---

## Tech Stack

| Layer | Technology |
|-------|------------|
| **Backend** | PHP 8.3+ (Procedural & OOP) |
| **Database** | MySQL 8.4 (MariaDB compatible) |
| **Frontend** | HTML5, Tailwind CSS (CDN), Vanilla JavaScript |
| **Charts** | Chart.js 4.x |
| **Icons** | Font Awesome 6.4 |
| **Fonts** | Google Fonts (Inter) |
| **Session** | PHP Native Sessions |
| **Security** | password_hash(), MD5 (legacy migration), Prepared Statements |

---

## Project Structure

```
c:\wamp64\www\main\
├── index.php                     # Public landing page
├── login.php                     # Employee login (dual auth: email/code)
├── signup.php                    # Account registration
├── logout.php                    # Session termination
├── functions.php                 # Global utility functions (logging, etc.)
│
├── conn/
│   └── db_connection.php         # Database connection with .env support
│
├── employee/                     # Protected employee dashboard modules
│   ├── dashboard.php             # Main dashboard with analytics
│   ├── select_employee.php       # Employee selection interface (attendance)
│   ├── attendance.php            # Attendance management page
│   ├── payroll.php               # Weekly payroll reports
│   ├── cash_advance.php          # Cash advance management
│   ├── weekly_report.php         # Weekly project reports
│   ├── billing.php               # Billing module
│   ├── employees.php             # Employee directory
│   ├── documents.php             # Document management
│   ├── analytics.php             # Advanced analytics
│   ├── logs.php                  # Activity logs viewer
│   ├── settings.php              # User settings
│   ├── sidebar.php               # Navigation sidebar component
│   ├── monitoring_dashboard.php  # Real-time monitoring
│   └── ...                       # (See full list below)
│
│   ├── api/                      # REST API endpoints
│   │   ├── clock_in.php          # Clock in functionality
│   │   ├── clock_out.php         # Clock out functionality
│   │   ├── get_employee_ca.php   # Fetch cash advance data
│   │   ├── get_transaction.php   # Transaction details
│   │   └── get_all_transactions.php
│   │
│   ├── function/                 # Business logic functions
│   │   ├── attendance.php        # Attendance functions
│   │   ├── billing_function.php  # Billing calculations
│   │   ├── get_billing_data.php  # Data retrieval
│   │   └── get_employee_data.php # Employee queries
│   │
│   ├── css/                      # Module-specific styles
│   │   ├── dashboard.css
│   │   ├── attendance.css
│   │   ├── billing.css
│   │   ├── employees.css
│   │   └── payroll.css
│   │
│   └── js/                       # Module-specific JavaScript
│       ├── dashboard.js
│       ├── attendance.js
│       ├── billing.js
│       └── cash_advance.js
│
├── assets/
│   ├── css/                      # Global styles
│   │   ├── style.css             # Main stylesheet
│   │   └── ai_chat.css           # AI widget styles
│   ├── js/                       # Global scripts
│   │   ├── auth.js               # Authentication logic
│   │   ├── employee.js           # Employee dashboard JS
│   │   ├── main.js               # Landing page JS
│   │   └── ai_chat.js            # AI chat widget
│   └── img/
│       └── profile/              # Profile images
│
├── dbschema/                     # SQL schema files
│   ├── attendance_db (7).sql     # Full database dump
│   ├── mysql-schema.sql          # Core schema
│   ├── 2026_02_09_create_payroll_tables.sql
│   ├── 2026_02_09_create_weekly_report_tables.sql
│   ├── cash_advances_table.sql
│   └── ...                       # Migration files
│
├── include/
│   ├── ai_chat_widget.php        # AI assistant UI component
│   ├── ai_instructions.php       # AI system prompts
│   └── ai_instructions/          # Role-specific instructions
│       ├── default.md
│       ├── employees.md
│       └── select_employee.md
│
├── uploads/                      # User uploads
│   └── profile_images/           # Profile photo storage
│
└── md/                           # Documentation notes
    ├── ARCHITECTURE_DIAGRAM.md
    ├── BRANCH_MANAGEMENT_IMPLEMENTATION.md
    ├── COMPLETION_SUMMARY.md
    └── ...                       # (21+ documentation files)
```

---

## Database Schema

### Core Tables

| Table | Purpose |
|-------|---------|
| `employees` | Employee profiles, credentials, branch assignments |
| `attendance` | Daily attendance records (clock in/out, OT hours) |
| `branches` | Branch/location management |
| `cash_advances` | Employee cash advance tracking |
| `weekly_reports` | Project weekly reports |
| `payroll_records` | Weekly payroll calculations |
| `billing` | Client billing records |
| `activity_logs` | System audit trail |
| `shift_logs` | Time tracking detailed logs |
| `performance_evaluations` | Employee performance data |
| `documents` | Uploaded document metadata |

### Key Relationships

```
employees (1) ───< (N) attendance
employees (1) ───< (N) cash_advances
employees (1) ───< (N) weekly_reports
employees (1) ───< (N) performance_evaluations
branches (1) ───< (N) employees
branches (1) ───< (N) attendance (via branch_name)
```

---

## Features

### 1. Employee Management
- **Registration** — Signup with employee code, email, position assignment
- **Profiles** — Photo upload, contact info, branch assignment
- **Status Tracking** — Active/Inactive employee states
- **Position Hierarchy** — Super Admin, Admin, Staff levels

### 2. Attendance System
- **Clock In/Out** — One-click time tracking with JavaScript timers
- **Automatic Calculations** — Total hours, overtime tracking
- **Branch-Based** — Attendance tracked per work location
- **Daily Monitoring** — Real-time who's present/absent
- **Absent Notes** — Reason capture for absences

### 3. Payroll Module
- **Weekly Reports** — Sunday-to-Saturday payroll periods
- **Rate Calculation** — Based on employee position/rate
- **Cash Advance Deductions** — Automatic deduction tracking
- **Excel Export** — Download payroll as spreadsheet
- **Multi-Week View** — Navigate historical payroll periods

### 4. Cash Advance
- **Request System** — Employees can request cash advances
- **Repayment Tracking** — Automatic deduction from payroll
- **Remaining Balance** — Real-time balance updates
- **Transaction History** — Complete audit trail

### 5. Branch Management
- **Multi-Location** — Support for multiple construction sites
- **Employee Assignment** — Assign employees to home branches
- **Daily Branch Selection** — Track where employee worked each day
- **Super Admin Override** — View all branches or filter by location

### 6. Analytics & Reporting
- **Dashboard Charts** — Chart.js visualizations
  - Monthly attendance trends
  - Weekly patterns (day-of-week analysis)
  - Employee rankings
  - Present/Absent ratios
- **Monitoring Dashboard** — Real-time company-wide stats
- **Activity Logs** — Filterable system audit trail

### 7. AI Assistant
- **Chat Widget** — Floating AI support in bottom-right
- **Role-Aware** — Different instructions per user role
- **Employee Help** — Answers about attendance, payroll, policies
- **Admin Tools** — System management guidance

---

## Installation & Setup

### Prerequisites
- PHP 8.3+ with MySQLi extension
- MySQL 8.4+ or MariaDB 10.6+
- Apache/Nginx with mod_rewrite
- WAMP/XAMPP (for Windows development)

### Step 1: Database Setup
```bash
# Import the main schema
mysql -u root -p attendance_db < dbschema/attendance_db\ \(7\).sql
```

### Step 2: Environment Configuration
Create `.env` file in project root:
```env
DB_HOST=localhost
DB_USER=root
DB_PASS=your_password
DB_SCHEMA=attendance_db
```

### Step 3: Web Server
Point document root to `c:\wamp64\www\main\` or equivalent.

### Step 4: Permissions
Ensure `uploads/` directory is writable:
```bash
chmod 755 uploads/
chmod 755 uploads/profile_images/
```

---

## Configuration

### Session Configuration
- **Timezone**: Asia/Manila (UTC+08:00)
- **Session Path**: Default PHP session handling
- **Timeout**: Browser session (configurable)

### Database Timezone
```php
// conn/db_connection.php
@mysqli_query($db, "SET time_zone = '+08:00'");
```

### Security Features
- **Password Hashing**: password_hash() with auto MD5 migration
- **SQL Injection Protection**: Prepared statements throughout
- **XSS Prevention**: htmlspecialchars() on output
- **Session Security**: Regenerate on privilege change

---

## API Endpoints

### Clock In/Out
| Endpoint | Method | Description |
|----------|--------|-------------|
| `employee/api/clock_in.php` | POST | Record clock in time |
| `employee/api/clock_out.php` | POST | Record clock out, calculate hours |

### Data Retrieval
| Endpoint | Method | Description |
|----------|--------|-------------|
| `get_available_employees_api.php` | GET | List employees for attendance |
| `mark_attendance_absent_api.php` | POST | Mark employee absent |
| `get_shift_logs_api.php` | GET | Retrieve time logs |
| `set_attendance_ot_hrs_api.php` | POST | Set overtime hours |

### Branch Management
| Endpoint | Method | Description |
|----------|--------|-------------|
| `get_branches_api.php` | GET | List all branches |
| `set_employee_branch_api.php` | POST | Assign employee to branch |
| `transfer_branch_api.php` | POST | Transfer between branches |

### Payroll
| Endpoint | Method | Description |
|----------|--------|-------------|
| `get_transaction.php` | GET | Fetch transaction details |
| `get_employee_ca.php` | GET | Get cash advance balance |
| `get_all_transactions.php` | GET | List all transactions |

---

## User Roles

### Super Admin
- View all branches
- Manage all employees
- Full payroll access
- System configuration
- Activity log access

### Admin
- View assigned branch only (unless overridden)
- Manage branch employees
- Branch-level payroll
- Limited settings access

### Employee
- Personal dashboard only
- Clock in/out for own record
- View personal attendance stats
- Request cash advances
- Submit weekly reports

---

## File Reference

### Core Application Files

| File | Purpose | Lines |
|------|---------|-------|
| `index.php` | Public landing page | ~166 |
| `login.php` | Authentication with dual password support | ~395 |
| `functions.php` | Global utilities (logging) | ~32 |
| `conn/db_connection.php` | Database connection with .env | ~66 |

### Main Dashboard Modules

| File | Purpose | Size |
|------|---------|------|
| `employee/dashboard.php` | Main dashboard with analytics | ~447 lines |
| `employee/select_employee.php` | Employee selection for attendance | ~15098 bytes |
| `employee/attendance.php` | Attendance management interface | ~25540 bytes |
| `employee/payroll.php` | Weekly payroll reports | ~35013 bytes |
| `employee/cash_advance.php` | Cash advance management | ~36805 bytes |
| `employee/weekly_report.php` | Project reporting | ~33405 bytes |
| `employee/billing.php` | Client billing module | ~21274 bytes |
| `employee/employees.php` | Employee directory | ~21739 bytes |
| `employee/documents.php` | Document management | ~66980 bytes |
| `employee/analytics.php` | Advanced analytics | ~28803 bytes |
| `employee/logs.php` | Activity log viewer | ~24079 bytes |
| `employee/settings.php` | User settings | ~47572 bytes |

### API Endpoints

| File | Purpose |
|------|---------|
| `employee/api/clock_in.php` | Clock in functionality |
| `employee/api/clock_out.php` | Clock out with calculations |
| `employee/api/get_employee_ca.php` | Cash advance data |
| `employee/api/get_transaction.php` | Transaction details |
| `employee/api/get_all_transactions.php` | All transactions |

### Supporting APIs (Root Level)

| File | Purpose |
|------|---------|
| `login_api.php` | AJAX login endpoint |
| `time_in_api.php` | Time in recording |
| `time_out_api.php` | Time out recording |
| `submit_attendance_api.php` | Attendance submission |
| `get_available_employees_api.php` | Employee list |
| `mark_attendance_absent_api.php` | Absent marking |
| `get_branches_api.php` | Branch list |
| `get_branch_api.php` | Single branch data |
| `set_employee_branch_api.php` | Branch assignment |
| `transfer_branch_api.php` | Branch transfer |
| `set_attendance_ot_hrs_api.php` | OT hours setting |
| `get_shift_logs_api.php` | Shift logs retrieval |
| `update_profile_api.php` | Profile updates |

---

## Development Notes

### Code Organization
- **Separation of Concerns**: Business logic in `function/`, presentation in root module files
- **API Pattern**: Root-level APIs for cross-module access, `employee/api/` for module-specific
- **Component Reuse**: `sidebar.php`, `ai_chat_widget.php` included across modules

### JavaScript Architecture
- **Vanilla JS** — No frameworks, jQuery-free
- **Chart.js** — For analytics visualizations
- **Modular Scripts** — Each module has dedicated JS file
- **PHP-to-JS Data** — `window.dashboardData` pattern for passing server data

### CSS Architecture
- **Tailwind CDN** — Utility-first styling
- **Module CSS** — Specific styles in `employee/css/`
- **Global Styles** — Common components in `assets/css/`

### Security Considerations
1. All user inputs use prepared statements
2. Session validation on every protected page
3. Password migration from MD5 to password_hash()
4. Role-based access control (RBAC)
5. IP logging in activity logs

---

## Maintenance & Support

### Activity Monitoring
All major actions are logged to `activity_logs`:
- User logins/logouts
- Attendance marking
- Payroll views
- Settings changes
- Profile updates

### Backup Strategy
SQL dumps stored in `dbschema/` with date-stamped migration files.

### Common Issues
1. **Session Timeout** — Ensure PHP session settings accommodate workday
2. **Timezone** — Server must be set to Asia/Manila (PHT)
3. **Upload Limits** — PHP `upload_max_filesize` for profile photos
4. **Branch Visibility** — Super Admin vs regular user branch filter logic

---

**Last Updated**: February 10, 2026  
**System Version**: 2.0  
**Developer**: JAJR Company IT Division
