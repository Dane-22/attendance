# JAJR Company - Attendance Management System

## Overview

The **JAJR Company Attendance Management System** is a comprehensive web-based application designed to manage employee attendance, payroll, cash advances, billing, and document management. The system supports multi-branch operations with role-based access control.

**Company**: JAJR Company (Engineering/Construction)  
**Owner**: Arzadon  
**Technology Stack**: PHP, MySQL, Tailwind CSS, Bootstrap 5  
**Timezone**: Asia/Manila (UTC+08:00)

---

## Table of Contents

1. [System Architecture](#system-architecture)
2. [Database Schema](#database-schema)
3. [Features](#features)
4. [User Roles & Permissions](#user-roles--permissions)
5. [API Documentation](#api-documentation)
6. [Directory Structure](#directory-structure)
7. [Configuration](#configuration)
8. [Installation & Setup](#installation--setup)
9. [Usage Guide](#usage-guide)
10. [Integrations](#integrations)

---

## System Architecture

### Technology Stack
- **Backend**: PHP 8.3+
- **Database**: MySQL 8.4+
- **Frontend**: HTML5, CSS3, JavaScript
- **CSS Frameworks**: Tailwind CSS, Bootstrap 5.3
- **Icons**: Font Awesome 6.4
- **Authentication**: Session-based with dual password hashing (MD5 & password_hash)

### Key Components
```
┌─────────────────────────────────────────────────────────┐
│                    Web Server (Apache)                    │
├─────────────────────────────────────────────────────────┤
│  Frontend Layer          │  Backend Layer               │
│  - HTML/Tailwind CSS     │  - PHP API endpoints         │
│  - Bootstrap 5         │  - MySQL Database            │
│  - JavaScript          │  - Session Management        │
├─────────────────────────────────────────────────────────┤
│              External Integrations                       │
│  - Procurement API (xandree.com)                       │
└─────────────────────────────────────────────────────────┘
```

---

## Database Schema

### Core Tables

#### 1. **employees**
Stores employee information and credentials.
```sql
CREATE TABLE employees (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_code VARCHAR(50) UNIQUE,
  first_name VARCHAR(100),
  last_name VARCHAR(100),
  middle_name VARCHAR(100),
  email VARCHAR(100),
  password_hash VARCHAR(255),
  position ENUM('Admin','Super Admin','Worker','Engineer'),
  branch_id INT,
  status ENUM('Active','Inactive'),
  daily_rate DECIMAL(10,2),
  monthly_rate DECIMAL(10,2),
  hire_date DATE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP
);
```

#### 2. **attendance**
Tracks daily attendance with time-in/time-out.
```sql
CREATE TABLE attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT,
  branch_name VARCHAR(100),
  attendance_date DATE,
  status ENUM('Present','Absent','Late'),
  time_in TIMESTAMP NULL,
  time_out TIMESTAMP NULL,
  is_time_running TINYINT DEFAULT 0,
  is_overtime_running TINYINT DEFAULT 0,
  total_ot_hrs VARCHAR(50),
  is_auto_absent TINYINT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (employee_id) REFERENCES employees(id)
);
```

#### 3. **branches**
Manages branch/office locations.
```sql
CREATE TABLE branches (
  id INT AUTO_INCREMENT PRIMARY KEY,
  branch_name VARCHAR(100),
  is_active TINYINT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### 4. **activity_logs**
System audit trail for all user actions.
```sql
CREATE TABLE activity_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  action VARCHAR(255),
  details TEXT,
  ip_address VARCHAR(45),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_id (user_id),
  INDEX idx_action (action),
  INDEX idx_created_at (created_at)
);
```

#### 5. **employee_transfers**
Records employee branch transfers.
```sql
CREATE TABLE employee_transfers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT,
  from_branch VARCHAR(100),
  to_branch VARCHAR(100),
  transfer_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (employee_id) REFERENCES employees(id)
);
```

#### 6. **cash_advance**
Manages cash advance transactions.
```sql
CREATE TABLE cash_advance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT,
  type ENUM('Cash Advance','Payment'),
  amount DECIMAL(10,2),
  particular TEXT,
  transaction_date DATE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (employee_id) REFERENCES employees(id)
);
```

#### 7. **payroll_records**
Weekly payroll data storage.
```sql
CREATE TABLE payroll_records (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT,
  week_start DATE,
  week_end DATE,
  regular_hours DECIMAL(8,2),
  overtime_hours DECIMAL(8,2),
  regular_pay DECIMAL(10,2),
  overtime_pay DECIMAL(10,2),
  cash_advance_deduction DECIMAL(10,2),
  net_pay DECIMAL(10,2),
  status ENUM('Draft','Finalized'),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### 8. **employee_notifications**
In-app notification system.
```sql
CREATE TABLE employee_notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT,
  title VARCHAR(255),
  message TEXT,
  type ENUM('overtime','general','system'),
  is_read TINYINT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### 9. **branch_reset_log**
Tracks automatic midnight session resets.
```sql
CREATE TABLE branch_reset_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  reset_date DATE,
  employees_affected INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## Features

### 1. **Authentication & Security**
- Dual password verification (MD5 legacy + modern password_hash)
- Automatic password hash upgrade on login
- Session-based authentication
- Role-based access control
- Activity logging with IP tracking
- Procurement SSO integration

### 2. **Attendance Management**
- Time-in/Time-out tracking per branch
- Real-time attendance status monitoring
- Manual attendance marking (Present/Absent)
- Automatic midnight session closure
- Multi-branch attendance support
- Auto-absent detection

### 3. **Employee Management**
- Employee profile management
- Branch assignment and transfers
- Position/role management
- Photo upload support
- E-signature capture
- Status management (Active/Inactive)

### 4. **Payroll System**
- Weekly payroll calculation
- Regular and overtime hours tracking
- Cash advance deduction integration
- Payroll export functionality
- Rate-based calculations (daily/monthly)

### 5. **Cash Advance**
- Cash advance requests
- Payment recording
- Balance tracking per employee
- Receipt printing support
- Transaction history

### 6. **Billing & Documents**
- Billing record management
- Document upload and storage
- Medical/health records tracking
- File attachment system

### 7. **Notifications**
- Overtime request workflow
- Employee notification system
- Unread message badges
- Real-time alerts

### 8. **Analytics & Reporting**
- Dashboard with summary cards
- Employee attendance reports
- Weekly performance reports
- Activity log monitoring
- System logs viewer

### 9. **AI Assistant**
- Integrated AI chat widget
- Role-based AI instructions
- Employee data queries
- Natural language interaction

---

## User Roles & Permissions

### **Super Admin**
- Full system access
- View all branches
- Manage all employees
- Access to all reports
- System configuration
- Overtime approval

### **Admin**
- Dashboard access
- Employee management
- Attendance monitoring
- Payroll management
- Cash advance approval
- Billing and documents
- Activity logs
- Cannot see all branches (assigned only)

### **Worker**
- Time-in/Time-out
- View own attendance
- My notifications
- Profile settings
- Request overtime

### **Engineer**
- Worker permissions +
- Procurement access

---

## API Documentation

### Authentication APIs

#### Login API
```http
POST /login_api.php
Content-Type: application/json

{
  "identifier": "E12345 or email@company.com",
  "password": "userpassword"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "user_data": {
    "id": 1,
    "employee_code": "E12345",
    "first_name": "John",
    "last_name": "Doe",
    "position": "Worker",
    "assigned_branch": "Main Branch",
    "daily_branch": "Main Branch"
  }
}
```

### Attendance APIs

#### Time In
```http
POST /time_in_api.php
Content-Type: application/x-www-form-urlencoded

employee_id=123&branch_name=Main%20Branch
```

**Response:**
```json
{
  "success": true,
  "message": "Time in recorded",
  "attendance_id": 456,
  "time_in": "2026-02-14 08:30:00",
  "is_time_running": true
}
```

#### Time Out
```http
POST /time_out_api.php
Content-Type: application/x-www-form-urlencoded

employee_id=123&branch_name=Main%20Branch
```

#### Transfer Branch
```http
POST /transfer_branch_api.php
Content-Type: application/x-www-form-urlencoded

employee_id=123&from_branch=Main%20Branch&to_branch=BCDA
```

**Response:**
```json
{
  "success": true,
  "message": "Transferred and timed in to new branch",
  "from_branch": "Main Branch",
  "to_branch": "BCDA",
  "timed_out": true,
  "timed_in": true
}
```

#### Get Today's Status
```http
GET /employees_today_status_api.php
```

**Response:**
```json
{
  "success": true,
  "date": "2026-02-14",
  "count": 25,
  "employees": [
    {
      "id": 123,
      "employee_code": "E12345",
      "first_name": "John",
      "last_name": "Doe",
      "assigned_branch_name": "Main Branch",
      "today_status": "Present",
      "time_in": "08:30:00",
      "time_out": null,
      "is_timed_in": 1
    }
  ]
}
```

### Data APIs

#### Get Branches
```http
GET /get_branches_api.php
```

#### Get Available Employees
```http
GET /get_available_employees_api.php?branch_name=Main%20Branch
```

#### Mark Attendance Absent
```http
POST /mark_attendance_absent_api.php
Content-Type: application/x-www-form-urlencoded

employee_id=123&date=2026-02-14&note=Sick%20leave
```

#### Set Overtime Hours
```http
POST /set_attendance_ot_hrs_api.php
Content-Type: application/x-www-form-urlencoded

employee_id=123&date=2026-02-14&ot_hrs=2.5
```

---

## Directory Structure

```
c:\wamp64\www\main\
├── index.php                    # Public landing page
├── login.php                    # User login
├── logout.php                   # Logout handler
├── signup.php                   # User registration
├── functions.php                # Utility functions
├── conn/
│   └── db_connection.php        # Database connection
│
├── assets/
│   ├── css/
│   │   ├── style.css           # Main styles
│   │   ├── ai_chat.css         # AI widget styles
│   │   └── style_auth.css      # Auth page styles
│   ├── js/
│   │   ├── auth.js             # Auth JavaScript
│   │   ├── employee.js         # Employee functions
│   │   └── ai_chat.js          # AI chat widget
│   └── img/profile/            # Profile images
│
├── employee/                    # Main application
│   ├── dashboard.php           # Admin dashboard
│   ├── select_employee.php     # Site attendance
│   ├── attendance.php          # Manual attendance
│   ├── employees.php           # Employee list
│   ├── payroll.php             # Payroll management
│   ├── weekly_report.php       # Weekly reports
│   ├── cash_advance.php        # Cash advance module
│   ├── billing.php             # Billing module
│   ├── documents.php           # Document management
│   ├── notification.php        # Overtime requests
│   ├── my_notifications.php    # Employee notifications
│   ├── logs.php                # Activity logs
│   ├── settings.php            # User settings
│   ├── signature_settings.php  # E-signature config
│   ├── transfer_module.php     # Branch transfers
│   ├── sidebar.php             # Navigation sidebar
│   │
│   ├── api/                    # Internal APIs
│   │   ├── clock_in.php
│   │   ├── clock_out.php
│   │   ├── get_all_transactions.php
│   │   ├── get_employee_ca.php
│   │   └── get_transaction.php
│   │
│   ├── function/               # Business logic
│   │   ├── attendance.php
│   │   └── attendance_db (15).sql  # Database dump
│   │
│   ├── css/                    # Module styles
│   │   ├── dashboard.css
│   │   ├── billing.css
│   │   └── employees.css
│   │
│   ├── js/                     # Module scripts
│   │   └── dashboard.js
│   │
│   ├── cron/                   # Scheduled tasks
│   │   ├── check_daily_table.php
│   │   └── check_structure.php
│   │
│   └── procurement/            # Procurement module
│       └── ...
│
├── uploads/                     # User uploads
│   ├── profile_images/         # Profile photos
│   └── signatures/            # E-signatures
│
├── include/                     # Shared components
│   ├── ai_chat_widget.php      # AI chat component
│   ├── ai_instructions.php     # AI configuration
│   └── ai_instructions/       # Role-based AI prompts
│       ├── default.md
│       ├── employees.md
│       └── select_employee.md
│
├── api/                         # External integration
│   └── send_branches.php       # Procurement sync
│
├── dbschema/                    # Database schemas
│
└── backups/                     # System backups
```

---

## Configuration

### Environment Variables (.env)
Create a `.env` file in the root directory:

```env
# Database Configuration
DB_HOST=127.0.0.1:3306
DB_USER=your_username
DB_PASS=your_password
DB_SCHEMA=attendance_db

# Optional: Procurement API
PROCUREMENT_API_URL=https://procurement-api.xandree.com
```

### Database Connection
Location: `@/conn/db_connection.php`

Features:
- Environment variable loading from `.env`
- UTF-8 mb4 charset support
- Philippines timezone (UTC+08:00)
- Connection error handling

### Session Configuration
- Session-based authentication
- 24-hour session timeout (default)
- Secure session handling
- Role-based session variables

---

## Installation & Setup

### Prerequisites
- PHP 8.3 or higher
- MySQL 8.4 or higher
- Apache/Nginx web server
- WAMP/LAMP/XAMPP stack (recommended for Windows)

### Installation Steps

1. **Clone/Extract Files**
   ```bash
   # Extract to web root
   c:\wamp64\www\main\
   ```

2. **Create Database**
   ```sql
   CREATE DATABASE attendance_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. **Import Schema**
   ```bash
   # Use the SQL dump file
   mysql -u root -p attendance_db < employee/function/attendance_db\ \(15\).sql
   ```

4. **Configure Environment**
   ```bash
   # Create .env file
   cp .env.example .env
   # Edit with your database credentials
   ```

5. **Set Permissions**
   ```bash
   # Ensure write permissions for uploads
   chmod 755 uploads/
   chmod 755 uploads/profile_images/
   chmod 755 uploads/signatures/
   ```

6. **Access Application**
   ```
   http://localhost/main/
   ```

### Default Admin Account
After installation, create a Super Admin account through the signup page or insert directly:

```sql
INSERT INTO employees (
  employee_code, first_name, last_name, email, 
  password_hash, position, status, branch_id
) VALUES (
  'ADMIN001', 'Super', 'Admin', 'admin@jajr.com',
  '$2y$10$...', 'Super Admin', 'Active', 1
);
```

---

## Usage Guide

### Daily Workflow

1. **Morning - Employee Login**
   - Employees log in via `login.php`
   - System automatically records attendance
   - Redirect to site attendance page

2. **Time In**
   - Select branch on site attendance page
   - Click "Time In" button
   - System records time and branch

3. **Time Out**
   - Click "Time Out" button
   - System calculates hours worked
   - Ready for next session

4. **Branch Transfer**
   - Use transfer module to move between branches
   - Automatically times out from current branch
   - Times in to new branch

### Admin Workflow

1. **Dashboard Monitoring**
   - View summary cards (employees, branches, transfers)
   - Check recent activity
   - Monitor pending payroll

2. **Attendance Management**
   - View site attendance for all branches
   - Mark employees as Present/Absent
   - Handle overtime requests

3. **Payroll Processing**
   - Generate weekly reports
   - Calculate regular and overtime hours
   - Deduct cash advances
   - Export/finalize payroll

4. **Employee Management**
   - Add/edit employees
   - Manage branch assignments
   - Set rates and positions
   - Upload documents

---

## Integrations

### Procurement System Integration
- **API Endpoint**: `https://procurement-api.xandree.com/api/auth/login`
- **Features**:
  - SSO (Single Sign-On) with procurement
  - Token-based authentication
  - Branch synchronization
  - Employee data sync

### AI Assistant Integration
- **Location**: `@/include/ai_chat_widget.php`
- **Features**:
  - Natural language queries
  - Employee data lookup
  - Attendance status checks
  - Role-based responses

---

## Security Features

1. **Password Security**
   - Dual verification (MD5 + bcrypt)
   - Automatic hash migration
   - Secure password storage

2. **Session Security**
   - Secure session handling
   - IP-based activity logging
   - Role verification on each request

3. **Data Protection**
   - Prepared SQL statements (prevent SQL injection)
   - XSS protection via htmlspecialchars
   - CSRF protection on forms

4. **Access Control**
   - Role-based menu visibility
   - Page-level permission checks
   - API endpoint authentication

---

## Maintenance & Cron Jobs

### Automatic Tasks

1. **Midnight Session Reset**
   - Closes open attendance sessions at 23:59:59
   - Prevents overnight time tracking issues
   - Logs affected employees

2. **Database Checks**
   - Column existence verification
   - Table structure validation
   - Auto-migration detection

### Manual Maintenance

1. **Log Rotation**
   - Clear `api_debug.log` periodically
   - Archive old activity logs

2. **Backup Strategy**
   - Regular database dumps
   - File backup for uploads folder

---

## Support & Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Check `.env` credentials
   - Verify MySQL service is running
   - Confirm database exists

2. **Login Issues**
   - Clear browser cookies
   - Check employee status is 'Active'
   - Verify password hash format

3. **Permission Errors**
   - Check folder permissions (755)
   - Verify Apache user ownership
   - Check PHP error logs

### Debug Mode
Enable debug in API calls:
```http
POST /time_in_api.php
employee_id=123&branch_name=Main&debug=1
```

---

## License & Copyright

© 2026 JAJR Company. All rights reserved.

**Developer**: Arzadon

---

*Documentation generated on February 14, 2026*
