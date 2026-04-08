# JAJR Attendance Management System - Complete Technical Documentation

## Table of Contents
1. [Executive Summary](#executive-summary)
2. [System Architecture](#system-architecture)
3. [Technology Stack](#technology-stack)
4. [Database Schema](#database-schema)
5. [Core Features](#core-features)
6. [API Endpoints](#api-endpoints)
7. [Security Architecture](#security-architecture)
8. [Frontend Assets](#frontend-assets)
9. [File Structure](#file-structure)
10. [User Roles & Permissions](#user-roles--permissions)
11. [Integration Points](#integration-points)
12. [Installation & Setup](#installation--setup)
13. [Maintenance & Operations](#maintenance--operations)

---

## Executive Summary

**Project**: JAJR Attendance Management System  
**Company**: JAJR Company (Engineering & Construction)  
**Owner**: Arzadon  
**Timezone**: Asia/Manila (UTC+08:00)  
**Version**: 1.0.0  
**Status**: Production Ready

The JAJR Attendance Management System is a comprehensive web-based application designed for multi-branch employee management, attendance tracking, payroll processing, and administrative operations. The system supports real-time QR-based attendance, geolocation tracking with geofencing, cash advance management, overtime processing, and comprehensive reporting capabilities.

---

## System Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        Client Layer                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐           │
│  │  Web Browser │  │  Mobile Web  │  │   QR Scanner │           │
│  └──────────────┘  └──────────────┘  └──────────────┘           │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Web Server (Apache)                         │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │                    Application Layer                        │  │
│  │  ┌─────────────────┐  ┌─────────────────────────────┐   │  │
│  │  │   PHP 8.3+      │  │   Session Management        │   │  │
│  │  │   (REST APIs)   │  │   ├─ Session Authentication │   │  │
│  │  │                 │  │   ├─ Role-based Access      │   │  │
│  │  │                 │  │   └─ IP-based Logging       │   │  │
│  │  └─────────────────┘  └─────────────────────────────┘   │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Data Layer                                  │
│  ┌──────────────────┐  ┌──────────────────┐                     │
│  │   MySQL 8.4+     │  │   File Storage   │                     │
│  │   (Primary DB)   │  │   ├─ Profile Images                 │
│  │                  │  │   ├─ Signatures                      │
│  │                  │  │   └─ Documents                       │
│  └──────────────────┘  └──────────────────┘                     │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                   External Integrations                          │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────┐  │
│  │ Procurement API  │  │  Web Push (VAPID)│  │ MapLibre GL  │  │
│  │ (xandree.com)   │  │  Notifications   │  │   (Maps)     │  │
│  └──────────────────┘  └──────────────────┘  └──────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

---

## Technology Stack

### Backend Technologies
| Component | Technology | Version |
|-----------|------------|---------|
| Language | PHP | 8.3+ |
| Database | MySQL | 8.4+ |
| Web Server | Apache | 2.4+ |
| Session Management | PHP Sessions | Native |
| Password Hashing | bcrypt + MD5 (legacy) | - |
| API Format | JSON | RESTful |

### Frontend Technologies
| Component | Technology | Version |
|-----------|------------|---------|
| CSS Framework | Tailwind CSS | 3.4+ |
| CSS Framework | Bootstrap | 5.3 |
| Icons | Font Awesome | 6.4 |
| Mapping | MapLibre GL JS | Latest |
| QR Scanning | html5-qrcode | Latest |
| Build Tool | Node.js/npm | - |

### External Dependencies (Composer)
```json
{
  "minishlink/web-push": "^8.0",
  "phpoffice/phpspreadsheet": "^1.29",
  "ezyang/htmlpurifier": "^4.17",
  "brick/math": "^0.11"
}
```

---

## Database Schema

### Core Tables Overview

#### 1. **employees**
Primary employee information and credentials table.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Auto-increment primary key |
| employee_code | VARCHAR(50) | Unique employee identifier (E0001, ENG-2026-0001, etc.) |
| first_name | VARCHAR(100) | Employee first name |
| last_name | VARCHAR(100) | Employee last name |
| middle_name | VARCHAR(100) | Middle name (optional) |
| email | VARCHAR(100) | Email address |
| password_hash | VARCHAR(255) | Password (MD5 or bcrypt) |
| position | ENUM | 'Super Admin', 'Admin', 'Engineer', 'Worker', 'Developer' |
| status | ENUM | 'Active', 'Inactive' |
| branch_id | INT (FK) | Assigned branch reference |
| daily_rate | DECIMAL(10,2) | Daily salary rate |
| monthly_rate | DECIMAL(10,2) | Monthly salary rate |
| profile_image | VARCHAR(255) | Profile photo filename |
| created_at | TIMESTAMP | Record creation time |
| updated_at | TIMESTAMP | Last update time |

#### 2. **attendance**
Daily attendance tracking with time-in/time-out.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Auto-increment primary key |
| employee_id | INT (FK) | Reference to employees table |
| status | ENUM | 'Present', 'Late', 'Absent', 'System' |
| branch_name | VARCHAR(50) | Branch where attendance recorded |
| attendance_date | DATE | Date of attendance |
| time_in | DATETIME | Clock-in timestamp |
| time_out | DATETIME | Clock-out timestamp |
| is_time_running | TINYINT | Flag for active session |
| is_overtime_running | TINYINT | Overtime session flag |
| total_ot_hrs | VARCHAR(10) | Accumulated overtime hours |
| is_auto_absent | TINYINT | Auto-marked absent flag |
| auto_absent_applied | TINYINT | Auto-absent processing flag |
| absent_notes | TEXT | Notes for absence |
| created_at | TIMESTAMP | Record creation |
| updated_at | TIMESTAMP | Last update |

#### 3. **branches**
Multi-branch management with geolocation.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Auto-increment primary key |
| order_number | VARCHAR(10) | Project/order reference |
| branch_name | VARCHAR(50) | Unique branch name |
| branch_address | VARCHAR(55) | Physical address |
| lat | VARCHAR(20) | Latitude for geofencing |
| long | VARCHAR(20) | Longitude for geofencing |
| is_active | TINYINT | Active status flag |
| created_at | TIMESTAMP | Creation time |

#### 4. **activity_logs**
Comprehensive audit trail for all system actions.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Auto-increment primary key |
| user_id | INT (FK) | User who performed action |
| action | VARCHAR(255) | Action type (Logged In, Clock In, etc.) |
| details | TEXT | Detailed description |
| ip_address | VARCHAR(45) | IP address of user |
| created_at | TIMESTAMP | Action timestamp |

**Indexes**: idx_user_id, idx_action, idx_created_at

#### 5. **employee_transfers**
Employee branch transfer history.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Auto-increment primary key |
| employee_id | INT (FK) | Employee reference |
| from_branch | VARCHAR(255) | Source branch |
| to_branch | VARCHAR(255) | Destination branch |
| transfer_date | DATETIME | When transfer occurred |
| status | ENUM | 'pending', 'completed', 'cancelled' |
| created_at | TIMESTAMP | Record creation |
| updated_at | TIMESTAMP | Last update |

#### 6. **cash_advance**
Cash advance request and tracking.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Auto-increment primary key |
| employee_id | INT (FK) | Employee reference |
| type | ENUM | 'Cash Advance', 'Payment' |
| amount | DECIMAL(10,2) | Transaction amount |
| particular | TEXT | Description/purpose |
| transaction_date | DATE | Transaction date |
| status | ENUM | 'Pending', 'Approved', 'Rejected' |
| approved_by | INT (FK) | Approver reference |
| created_at | TIMESTAMP | Record creation |

#### 7. **overtime_requests**
Overtime request workflow management.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Auto-increment primary key |
| employee_id | INT (FK) | Employee reference |
| branch_name | VARCHAR(255) | Project/branch location |
| request_date | DATE | Date of overtime |
| requested_hours | DECIMAL(5,2) | Hours requested |
| overtime_reason | TEXT | Justification |
| status | ENUM | 'pending', 'pre-approved', 'approved', 'rejected' |
| requested_by | VARCHAR(255) | Requester name |
| requested_by_user_id | INT | Requester ID |
| requested_at | TIMESTAMP | Request timestamp |
| approved_by | VARCHAR(255) | Approver name |
| approved_at | TIMESTAMP | Approval timestamp |
| rejection_reason | TEXT | Reason if rejected |
| attendance_id | INT (FK) | Linked attendance record |

#### 8. **employee_notifications**
In-app notification system.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Auto-increment primary key |
| employee_id | INT (FK) | Recipient employee |
| overtime_request_id | INT (FK) | Related OT request (optional) |
| cash_advance_id | INT (FK) | Related CA request (optional) |
| notification_type | VARCHAR(50) | 'overtime_request', 'cash_advance', etc. |
| title | VARCHAR(255) | Notification title |
| message | TEXT | Notification body |
| is_read | TINYINT | Read status flag |
| created_at | TIMESTAMP | Creation time |
| read_at | TIMESTAMP | When read |

#### 9. **push_subscriptions**
Web Push notification subscriptions.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Auto-increment primary key |
| user_id | INT (FK) | Subscriber |
| endpoint | TEXT | Push service endpoint |
| p256dh | VARCHAR(255) | Public key |
| auth | VARCHAR(255) | Authentication secret |
| created_at | TIMESTAMP | Subscription time |

#### 10. **e_signatures**
Digital signature storage.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Auto-increment primary key |
| employee_id | INT (FK) | Employee reference |
| signature_type | VARCHAR(50) | 'employee', 'admin', etc. |
| signature_image | VARCHAR(255) | Image filename |
| signature_data | LONGTEXT | Base64 image data |
| is_active | TINYINT | Active flag |
| created_at | TIMESTAMP | Creation time |
| updated_at | TIMESTAMP | Last update |

#### 11. **login_attempts**
Security tracking for failed logins.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Auto-increment primary key |
| ip_address | VARCHAR(45) | Source IP |
| identifier | VARCHAR(255) | Username/email attempted |
| attempts | INT | Number of attempts |
| last_attempt | TIMESTAMP | Last attempt time |
| locked_until | TIMESTAMP | Lockout expiration |

---

## Core Features

### 1. Authentication System
- **Dual Password Verification**: Supports both MD5 (legacy) and bcrypt (modern) password hashes
- **Auto-upgrade**: Automatically converts MD5 to bcrypt on successful login
- **Procurement SSO**: Integration with external procurement system (xandree.com)
- **Session Management**: Secure PHP sessions with IP-based activity logging
- **Account Lockout**: Automatic lockout after failed login attempts

### 2. Attendance Management
- **QR Code Clock In/Out**: Real-time attendance via QR scanning
- **Geolocation Validation**: GPS-based location verification with geofencing
- **Multi-Branch Support**: Track attendance across multiple project sites
- **Auto-Absent Detection**: Automatic absence marking for no-shows
- **Midnight Session Reset**: Automatic closure of open attendance sessions at 23:59:59
- **Overtime Tracking**: Integrated overtime request and approval workflow

### 3. Employee Management
- **Role-Based System**: Super Admin, Admin, Engineer, Worker, Developer
- **Branch Assignment**: Dynamic branch transfers with history tracking
- **Profile Management**: Photo upload, e-signature capture, contact details
- **Rate Management**: Daily and monthly salary rates per employee
- **Status Control**: Active/Inactive employee lifecycle

### 4. Financial Management
- **Cash Advances**: Request and approval workflow
- **Payroll Processing**: Weekly payroll with automatic calculations
- **Deductions**: Cash advance deductions from payroll
- **Payment Tracking**: Status monitoring for all financial transactions
- **Receipt Generation**: Printable receipts for cash transactions

### 5. Notification System
- **Web Push Notifications**: Real-time browser notifications using VAPID
- **In-App Notifications**: Dashboard notification center
- **Workflow Notifications**: Automatic notifications for:
  - Overtime requests
  - Cash advance requests  
  - Approval status changes
- **Unread Badges**: Visual indicators for pending notifications

### 6. Reporting & Analytics
- **Daily Reports**: Attendance summaries by branch
- **Weekly Reports**: Comprehensive weekly payroll and attendance
- **Monthly Analytics**: Trend analysis and statistics
- **Excel Export**: PHPSpreadsheet-based export functionality
- **PDF Generation**: Report exports to PDF format
- **Audit Reports**: Complete activity log history

### 7. Geolocation & Geofencing
- **GPS Tracking**: High-accuracy location capture
- **Geofence Validation**: Verify employee is within branch radius
- **Manager Override**: Allow clock-in outside geofence for managers
- **Map Visualization**: MapLibre GL JS integration for branch maps
- **Offline Support**: Cached location data for poor connectivity

### 8. AI Assistant
- **Integrated Chat Widget**: AI-powered assistant for employee queries
- **Role-Based Instructions**: Different AI behavior per user role
- **Natural Language**: Conversational interface for data queries
- **Employee Lookup**: Quick access to employee information

---

## API Endpoints

### Authentication APIs

#### POST /login_api.php
Authenticate employee and create session.

**Request:**
```json
{
  "identifier": "E0001 or email@company.com",
  "password": "userpassword",
  "branch_name": "Main Branch"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "user_data": {
    "id": 1,
    "employee_code": "E0001",
    "first_name": "John",
    "last_name": "Doe",
    "position": "Worker",
    "assigned_branch": "Main Branch",
    "daily_branch": "Main Branch"
  }
}
```

#### POST /change-password-api.php
Change password with procurement sync.

**Headers:** `X-API-Key: qwertyuiopasdfghjklzxcvbnm`

**Request:**
```json
{
  "employee_code": "E0001",
  "current_password": "oldpass",
  "new_password": "newpass",
  "confirm_password": "newpass"
}
```

### Attendance APIs

#### POST /time_in_api.php
Record employee clock-in.

**Parameters:**
- `employee_id` (required): Employee ID
- `branch_name` (required): Branch name
- `debug` (optional): Set to 1 for debug info

**Response:**
```json
{
  "success": true,
  "message": "Time in recorded",
  "attendance_id": 123,
  "time_in": "2026-04-07 08:30:00",
  "is_time_running": true
}
```

#### POST /time_out_api.php
Record employee clock-out.

**Parameters:**
- `employee_id` (required): Employee ID
- `branch_name` (required): Branch name

#### POST /qr_clock_api.php
QR-based clock-in/out with auto-detection.

**Parameters:**
- `employee_id` (required): Employee ID
- `action` (optional): 'in' or 'out' (auto-detected if omitted)
- `employee_code` (optional): For verification

#### GET /employees_today_status_api.php
Get all employees with today's attendance status.

**Response:**
```json
{
  "success": true,
  "date": "2026-04-07",
  "count": 25,
  "employees": [
    {
      "id": 1,
      "employee_code": "E0001",
      "first_name": "John",
      "last_name": "Doe",
      "today_status": "Present",
      "time_in": "08:30:00",
      "time_out": null,
      "is_timed_in": 1,
      "total_ot_hrs": "2"
    }
  ]
}
```

### Branch Management APIs

#### GET /get_branches_api.php
List all active branches.

#### POST /set_employee_branch_api.php
Update employee's assigned branch.

**Parameters:**
- `employee_id` (required)
- `branch_id` (required)

#### POST /transfer_branch_api.php
Transfer employee between branches.

**Parameters:**
- `employee_id` (required)
- `from_branch` (required)
- `to_branch` (required)

### Overtime APIs

#### POST /set_attendance_ot_hrs_api.php
Set overtime hours for attendance record.

**Parameters:**
- `employee_id` (required)
- `ot_hours` (required): Numeric hours
- `date` (optional): Defaults to today

### Geolocation APIs

#### POST /employee/api/validate_geofence.php
Validate if location is within branch geofence.

**Parameters:**
- `branch_id` (required)
- `latitude` (required)
- `longitude` (required)
- `accuracy` (optional): GPS accuracy in meters

**Response:**
```json
{
  "success": true,
  "inside_geofence": true,
  "distance": 45.2,
  "radius": 100,
  "branch": "Main Branch"
}
```

### Notification APIs

#### POST /employee/api/save_push_subscription.php
Save browser push subscription.

**Parameters:**
- `endpoint` (required)
- `p256dh` (required)
- `auth` (required)

---

## Security Architecture

### Authentication Security
1. **Dual Hash Support**: MD5 for legacy, bcrypt for modern passwords
2. **Auto-Migration**: MD5 hashes upgraded to bcrypt on login
3. **Session Security**: PHP sessions with secure configuration
4. **Account Lockout**: Failed attempt tracking in `login_attempts` table
5. **IP Logging**: All actions logged with IP address

### Data Protection
1. **SQL Injection Prevention**: Prepared statements throughout
2. **XSS Protection**: `htmlspecialchars()` encoding for output
3. **CSRF Protection**: Token-based validation on forms
4. **Input Validation**: Server-side validation for all inputs
5. **File Upload Security**: Type checking and size limits

### API Security
1. **API Key Authentication**: Required for sensitive endpoints
2. **CORS Configuration**: Controlled cross-origin access
3. **Rate Limiting**: Built-in throttling mechanisms
4. **Request Logging**: All API calls logged to `api_debug.log`

### Encryption
1. **Password Storage**: bcrypt with cost factor 10
2. **VAPID Keys**: For Web Push notification security
3. **Environment Variables**: Sensitive config in `.env` file
4. **Database**: UTF8MB4 charset for full Unicode support

---

## Frontend Assets

### JavaScript Files (`/assets/js/`)

| File | Purpose | Size |
|------|---------|------|
| `geolocation.js` | MapLibre integration, GPS tracking, geofencing | 18KB |
| `ai_chat.js` | AI assistant chat widget functionality | 4KB |
| `employee.js` | Employee management functions | 5KB |
| `auth.js` | Authentication helpers | 714B |
| `main.js` | Core application logic | 4KB |
| `theme.js` | Dark/light mode switching | 3KB |
| `sidebar-toggle.js` | Navigation sidebar controls | 1KB |

### CSS Files (`/assets/css/`)

| File | Purpose |
|------|---------|
| `style.css` | Main application styles |
| `ai_chat.css` | AI widget styling |
| `geolocation.css` | Map and location UI styles |
| `style_auth.css` | Authentication page styles |

### Key Frontend Features
1. **Responsive Design**: Mobile-first Tailwind CSS approach
2. **Theme Support**: Dark/Light mode with localStorage persistence
3. **QR Scanning**: html5-qrcode library for camera-based scanning
4. **Map Visualization**: MapLibre GL JS for branch location display
5. **Real-time Updates**: JavaScript polling for live attendance status
6. **Offline Support**: Service worker for basic offline functionality

---

## File Structure

```
c:\wamp64\www\main\
├── 📁 api/                          # API endpoints
│   └── send_branches.php           # Branch sync to procurement
│
├── 📁 assets/                       # Static assets
│   ├── 📁 css/                     # Stylesheets
│   │   ├── style.css
│   │   ├── ai_chat.css
│   │   ├── geolocation.css
│   │   └── style_auth.css
│   ├── 📁 js/                      # JavaScript files
│   │   ├── geolocation.js         # GPS & mapping
│   │   ├── ai_chat.js             # AI assistant
│   │   ├── employee.js            # Employee functions
│   │   ├── auth.js                # Authentication
│   │   ├── main.js                # Core logic
│   │   ├── theme.js               # Theme switching
│   │   └── sidebar-toggle.js      # Navigation
│   └── 📁 img/profile/             # Profile images
│
├── 📁 conn/                        # Database connection
│   └── db_connection.php          # MySQL connection with .env support
│
├── 📁 dbschema/                    # Database schemas
│   ├── attendance_db (2).sql      # Main schema dump
│   ├── geolocation_migration.sql  # Geofencing tables
│   ├── overtime_requests.sql      # OT workflow tables
│   └── push_subscriptions.sql     # Web push tables
│
├── 📁 docs/                        # Documentation
│   ├── LOGIN_DOCUMENTATION.md
│   ├── QR_SCANNING_FLOW.md
│   ├── SELECT_EMPLOYEE_DOCUMENTATION.md
│   └── [18 more documentation files]
│
├── 📁 employee/                    # Main application
│   ├── 📁 api/                     # Internal APIs (17 files)
│   │   ├── clock_in.php
│   │   ├── clock_out.php
│   │   ├── validate_geofence.php
│   │   ├── save_push_subscription.php
│   │   └── [13 more API files]
│   ├── 📁 cron/                    # Scheduled tasks (18 files)
│   │   ├── check_daily_table.php
│   │   ├── weekly_payroll_calculation.php
│   │   └── [16 more cron jobs]
│   ├── 📁 css/                     # Module styles (8 files)
│   ├── 📁 js/                      # Module scripts (6 files)
│   ├── 📁 function/                # Helper functions (8 files)
│   ├── 📁 docs/                    # Module docs (2 files)
│   ├── dashboard.php              # Admin dashboard
│   ├── employees.php              # Employee management
│   ├── attendance.php             # Manual attendance
│   ├── select_employee.php        # Site attendance (QR)
│   ├── payroll.php                # Payroll processing
│   ├── weekly_report.php          # Weekly reports
│   ├── cash_advance.php           # Cash advance module
│   ├── billing.php                # Billing management
│   ├── documents.php              # Document storage
│   ├── notification.php           # OT request approvals
│   ├── my_notifications.php       # User notification center
│   ├── transfer_module.php        # Branch transfers
│   ├── settings.php               # User settings
│   ├── signature_settings.php     # E-signature config
│   ├── logs.php                   # Activity logs
│   ├── audit.php                  # Audit reports
│   ├── analytics.php              # Analytics dashboard
│   └── [35 more PHP files]
│
├── 📁 include/                     # Shared components
│   ├── ai_chat_widget.php         # AI assistant component
│   ├── ai_instructions.php        # AI configuration
│   └── 📁 ai_instructions/         # Role-based AI prompts
│       ├── default.md
│       ├── employees.md
│       └── select_employee.md
│
├── 📁 uploads/                     # User uploads
│   ├── 📁 profile_images/        # Employee photos
│   └── 📁 signatures/            # E-signatures
│
├── 📁 vendor/                      # Composer dependencies
│   ├── autoload.php
│   └── [Package directories]
│
├── 📁 test/                        # Testing files
│   ├── QA_TEST_SUITE.md
│   ├── test_geofence_logic.php
│   └── geo_mock.js
│
├── 📁 face-recognition-v2/         # Face recognition module
│   ├── main.py
│   ├── Dockerfile
│   └── docker-compose.yml
│
├── 📁 migrations/                  # Database migrations
│   └── add_geofence_radius.php
│
├── 📄 index.php                    # Public landing page
├── 📄 login.php                    # User authentication
├── 📄 logout.php                   # Logout handler
├── 📄 signup.php                   # User registration
├── 📄 functions.php                # Global utility functions
├── 📄 sw.js                        # Service worker
├── 📄 .env                         # Environment configuration
├── 📄 composer.json                # PHP dependencies
├── 📄 tailwind.config.js           # Tailwind CSS config
└── 📄 [50+ API endpoint files]
```

---

## User Roles & Permissions

### Super Admin
- **Full System Access**: All features and all branches
- **User Management**: Create, edit, delete all users
- **System Configuration**: Settings, API keys, branches
- **Financial Control**: All cash advance and overtime approvals
- **Audit Access**: View all logs and reports
- **Data Export**: Full data export capabilities

### Admin
- **Branch Management**: View assigned branches
- **Employee Management**: Add, edit employees (assigned branches only)
- **Attendance Monitoring**: View and manage attendance
- **Payroll Processing**: Generate and manage payroll
- **Cash Advance Approval**: Approve/reject cash advances
- **Overtime Pre-Approval**: Pre-approve OT requests (requires Super Admin final approval)
- **Reports**: Generate branch-specific reports

### Engineer
- **Personal Dashboard**: View own attendance and stats
- **Overtime Requests**: Submit overtime requests
- **Cash Advances**: Request cash advances
- **Procurement Access**: Link to procurement system
- **Profile Management**: Update own profile
- **View Reports**: Personal and team reports (if assigned)

### Worker
- **Basic Attendance**: Clock in/out via QR
- **Profile View**: View own profile and attendance
- **Overtime Requests**: Submit OT requests
- **Cash Advances**: Request advances
- **Limited Reports**: Personal attendance only

### Developer
- **System Access**: Technical access for maintenance
- **API Management**: API key configuration
- **Debug Tools**: Access to debug utilities
- **All Worker Permissions**: Plus technical features

---

## Integration Points

### 1. Procurement System (xandree.com)
**Purpose**: Single Sign-On and password synchronization

**Endpoints:**
- `POST https://procurement-api.xandree.com/api/auth/login` - SSO authentication
- `POST https://procurement-api.xandree.com/api/auth/sync-password/` - Password sync

**Features:**
- Cross-system authentication
- Automatic password synchronization
- Employee data sharing

### 2. Web Push Notifications
**Purpose**: Real-time browser notifications

**Technology**: VAPID (Voluntary Application Server Identification)

**Features:**
- Push subscription management
- Notification delivery to subscribed devices
- Expired subscription cleanup

### 3. MapLibre GL JS
**Purpose**: Map visualization and geofencing

**Features:**
- Branch location display
- GPS position tracking
- Geofence radius visualization
- Distance calculations

### 4. QR Code Scanning
**Purpose**: Quick attendance marking

**Library**: html5-qrcode

**Features:**
- Camera-based QR scanning
- Auto clock-in/out detection
- Time restrictions (scanner enabled at 6:40 AM)

---

## Installation & Setup

### Prerequisites
- PHP 8.3 or higher
- MySQL 8.4 or higher
- Apache 2.4+ with mod_rewrite
- Composer (dependency management)
- Node.js (for Tailwind CSS build)
- SSL Certificate (recommended for Web Push)

### Installation Steps

#### 1. Database Setup
```sql
CREATE DATABASE attendance_db 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;
```

#### 2. Import Schema
```bash
mysql -u root -p attendance_db < dbschema/attendance_db\ \(2\).sql
```

#### 3. Environment Configuration
Create `.env` file in root:
```env
# Database Configuration
DB_HOST=127.0.0.1:3306
DB_USER=your_username
DB_PASS=your_password
DB_SCHEMA=attendance_db

# VAPID Keys for Web Push
VAPID_PUBLIC_KEY=your_public_key
VAPID_PRIVATE_KEY=your_private_key
VAPID_SUBJECT=mailto:admin@jajr.com

# API Keys
INTERNAL_API_KEY=qwertyuiopasdfghjklzxcvbnm
```

#### 4. Install Dependencies
```bash
# PHP dependencies
composer install

# Node dependencies (for Tailwind)
npm install
```

#### 5. File Permissions
```bash
chmod 755 uploads/
chmod 755 uploads/profile_images/
chmod 755 uploads/signatures/
chmod 644 conn/db_connection.php
```

#### 6. Create Admin Account
```sql
INSERT INTO employees (
  employee_code, first_name, last_name, email, 
  password_hash, position, status, branch_id
) VALUES (
  'SA-2026-001', 'Super', 'Admin', 'admin@jajr.com',
  '$2y$10$...', 'Super Admin', 'Active', 1
);
```

---

## Maintenance & Operations

### Scheduled Tasks (Cron Jobs)

#### 1. Midnight Session Reset
**File**: `employee/cron/check_daily_table.php`  
**Schedule**: Daily at 23:59  
**Purpose**: Closes open attendance sessions to prevent overnight tracking

#### 2. Weekly Payroll Calculation
**File**: `employee/cron/weekly_payroll_calculation.php`  
**Schedule**: Weekly (Sunday at 23:00)  
**Purpose**: Calculates regular and overtime hours for payroll

#### 3. Consecutive Attendance Check
**File**: `employee/cron/consecutive_attendance_check.php`  
**Schedule**: Daily  
**Purpose**: Identifies employees with consecutive absences

#### 4. Database Cleanup
**File**: `employee/cron/cleanup_duplicates.php`  
**Schedule**: Weekly  
**Purpose**: Removes duplicate attendance records

### Log Files

| File | Purpose | Rotation |
|------|---------|----------|
| `api_debug.log` | API request/response logging | Manual |
| `employee/update_allowance_errors.log` | Payroll calculation errors | Manual |
| `activity_logs` (DB table) | User action audit trail | Archive quarterly |

### Backup Strategy

#### Database Backups
```bash
# Daily backup
mysqldump -u root -p attendance_db > backups/attendance_db_$(date +%Y%m%d).sql

# Weekly full backup (with data)
mysqldump -u root -p --complete-insert attendance_db > backups/full_$(date +%Y%m%d).sql
```

#### File Backups
- Daily: `uploads/profile_images/`
- Daily: `uploads/signatures/`
- Weekly: Configuration files

### Performance Optimization

1. **Database Indexing**: All foreign keys and frequently queried columns indexed
2. **Query Optimization**: Prepared statements for all database operations
3. **Asset Minification**: CSS and JS files minified for production
4. **Image Optimization**: Profile images resized on upload
5. **Caching**: Browser caching for static assets

### Monitoring Checklist

- [ ] Daily: Check `api_debug.log` for errors
- [ ] Daily: Verify cron jobs executed successfully
- [ ] Weekly: Review activity logs for anomalies
- [ ] Weekly: Check disk space on uploads directory
- [ ] Monthly: Archive old activity logs
- [ ] Monthly: Review and optimize database tables
- [ ] Quarterly: Update VAPID keys for Web Push
- [ ] Quarterly: Security audit of user permissions

---

## Troubleshooting

### Common Issues

#### Database Connection Failed
1. Verify `.env` credentials
2. Check MySQL service status
3. Confirm database exists: `SHOW DATABASES;`
4. Test connection: `php -r "mysqli_connect('host', 'user', 'pass', 'db');"`

#### Login Issues
1. Clear browser cookies and cache
2. Verify employee status is 'Active'
3. Check password hash format (MD5 vs bcrypt)
4. Review `login_attempts` table for lockouts

#### QR Scanner Not Working
1. Verify HTTPS (required for camera access)
2. Check browser camera permissions
3. Verify time restriction (scanner enabled at 6:40 AM)
4. Test with different lighting conditions

#### Push Notifications Not Received
1. Verify VAPID keys in `.env`
2. Check browser notification permissions
3. Verify service worker registration
4. Test subscription in `push_subscriptions` table

#### Geofence Validation Failing
1. Check branch has valid lat/long coordinates
2. Verify GPS accuracy is acceptable (< 100m)
3. Test with `test/test_geofence_logic.php`
4. Check browser geolocation permissions

### Debug Mode

Enable debug output in APIs:
```php
// Add to API calls
?debug=1
```

View detailed error logs:
```bash
tail -f api_debug.log
tail -f employee/update_allowance_errors.log
```

---

## License & Copyright

© 2026 JAJR Company. All rights reserved.

**Developer**: Arzadon  
**System Administrator**: [IT Team]  
**Last Updated**: April 7, 2026

---

*This documentation is maintained as part of the JAJR Attendance Management System. For questions or updates, contact the system administrator.*
