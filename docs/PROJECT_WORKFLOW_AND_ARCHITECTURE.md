# JAJR Attendance Management System
## Technical Architecture & DevOps Documentation

**Document Type:** System Architecture & Operations Guide  
**Author:** Software Engineer & DevOps  
**Date:** April 14, 2026  
**Version:** 1.0

---

## Table of Contents

1. [System Overview](#system-overview)
2. [Architecture Deep Dive](#architecture-deep-dive)
3. [Data Flow & Workflows](#data-flow--workflows)
4. [Deployment Architecture](#deployment-architecture)
5. [Infrastructure Setup](#infrastructure-setup)
6. [Operational Procedures](#operational-procedures)
7. [Monitoring & Maintenance](#monitoring--maintenance)
8. [Troubleshooting Guide](#troubleshooting-guide)

---

# System Overview

## What This System Does

The JAJR Attendance Management System is an enterprise-grade employee tracking and payroll platform designed for companies with multiple branches and mobile workforces.

### Core Capabilities

| Module | Functionality |
|--------|---------------|
| **Attendance** | Clock-in/out via web, QR code, face recognition, or geolocation |
| **Payroll** | Automated salary calculation with Philippine government deductions |
| **Branch Management** | Multi-location support with geofencing enforcement |
| **Overtime** | OT request, approval workflow, and tracking |
| **Cash Advance** | Employee loan management with approval chains |
| **Reporting** | Weekly/monthly deployment and attendance analytics |
| **Notifications** | Push notifications via WebPush API |
| **Face Recognition** | AI-powered biometric verification using DeepFace |

### User Roles

```
┌─────────────────────────────────────────────────────────────┐
│                    ROLE HIERARCHY                          │
├─────────────────────────────────────────────────────────────┤
│  Developer         │ Full system access, API management      │
│  Super Admin       │ Company-wide configuration access       │
│  Admin             │ Branch management, payroll processing   │
│  Manager           │ Employee oversight, approval authority  │
│  Supervisor        │ Team attendance monitoring              │
│  Engineer          │ Engineering-specific dashboard          │
│  Employee          │ Personal attendance, requests           │
└─────────────────────────────────────────────────────────────┘
```

---

# Architecture Deep Dive

## 3-Tier Architecture

```
┌────────────────────────────────────────────────────────────────────┐
│                         PRESENTATION LAYER                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐            │
│  │   Web UI     │  │  QR Scanner  │  │   Admin      │            │
│  │  (PHP/HTML)  │  │   (Mobile)   │  │   Dashboard  │            │
│  └──────────────┘  └──────────────┘  └──────────────┘            │
└────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌────────────────────────────────────────────────────────────────────┐
│                      APPLICATION LAYER                              │
│                                                                     │
│  ┌───────────────────────────────────────────────────────────┐   │
│  │                  PHP Application Server                    │   │
│  │                                                           │   │
│  │  ┌─────────────────┐  ┌─────────────────┐              │   │
│  │  │   Web Routes    │  │    API Routes   │              │   │
│  │  │                 │  │                 │              │   │
│  │  │  /login.php     │  │  /login_api.php │              │   │
│  │  │  /employee/*    │  │  /employee/api/*│              │   │
│  │  │  /index.php     │  │  /*_api.php     │              │   │
│  │  └─────────────────┘  └─────────────────┘              │   │
│  │                                                           │   │
│  │  ┌───────────────────────────────────────────────────┐   │   │
│  │  │              Business Logic Layer                  │   │   │
│  │  │                                                   │   │   │
│  │  │  • Authentication (Session + API Key)          │   │   │
│  │  │  • Attendance Processing                         │   │   │
│  │  │  • Payroll Calculation                           │   │   │
│  │  │  • Geofence Validation                           │   │   │
│  │  │  • Notification Dispatch                         │   │   │
│  │  └───────────────────────────────────────────────────┘   │   │
│  │                                                           │   │
│  │  ┌───────────────────────────────────────────────────┐   │   │
│  │  │              Data Access Layer                     │   │   │
│  │  │                                                   │   │   │
│  │  │  • MySQLi Prepared Statements                     │   │   │
│  │  │  • File Upload Handler                           │   │   │
│  │  │  • Session Management                            │   │   │
│  │  │  • Cache (File-based)                          │   │   │
│  │  └───────────────────────────────────────────────────┘   │   │
│  └───────────────────────────────────────────────────────────┘   │
│                                                                     │
│  ┌───────────────────────────────────────────────────────────┐   │
│  │              Microservice Integration                      │   │
│  │                                                           │   │
│  │  Face Recognition Service (Python/FastAPI)               │   │
│  │  • Endpoint: http://localhost:8000                       │   │
│  │  • Model: VGG-Face via DeepFace                          │   │
│  │  • Storage: File-based embeddings                        │   │
│  └───────────────────────────────────────────────────────────┘   │
└────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌────────────────────────────────────────────────────────────────────┐
│                        DATA LAYER                                   │
│  ┌──────────────────┐  ┌──────────────────┐                      │
│  │   MySQL 8.4.7    │  │   File System    │                      │
│  │   attendance_db  │  │                  │                      │
│  │                  │  │  • uploads/      │                      │
│  │  Tables:         │  │  • profile imgs  │                      │
│  │  - employees     │  │  • signatures    │                      │
│  │  - attendance    │  │  • face data     │                      │
│  │  - branches      │  │                  │                      │
│  │  - activity_logs │  │  • logs/         │                      │
│  │  - api_keys      │  │  • debug logs    │                      │
│  │  - notifications │  │                  │                      │
│  └──────────────────┘  └──────────────────┘                      │
└────────────────────────────────────────────────────────────────────┘
```

---

## Component Interactions

### 1. Authentication Flow

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│    User      │────▶│   login.php  │────▶│  Database    │────▶│   Session    │
│  (Browser)   │     │              │     │  (employees) │     │  ($_SESSION) │
└──────────────┘     └──────────────┘     └──────────────┘     └──────────────┘
                           │
                           ▼
                    ┌──────────────┐
                    │ Procurement  │
                    │    API       │
                    │  (SSO Check) │
                    └──────────────┘
```

**Process:**
1. User submits credentials via form or JSON API
2. System validates against `employees` table
3. Supports dual password verification (MD5 legacy + bcrypt modern)
4. Auto-upgrades MD5 passwords to bcrypt on successful login
5. Checks procurement API for SSO integration
6. Initializes attendance record for current date
7. Sets session variables: `user_id`, `position`, `branch`, `logged_in`
8. Redirects to role-appropriate dashboard

### 2. Clock-In Flow

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│   Employee   │────▶│  clock_in.php│────▶│  Geofence    │────▶│  Database    │
│  (Mobile/Web)│     │   (API)      │     │  Validation  │     │  (attendance)│
└──────────────┘     └──────────────┘     └──────────────┘     └──────────────┘
                           │                                           │
                           ▼                                           ▼
                    ┌──────────────┐                          ┌──────────────┐
                    │ Face Verify  │                          │  Activity    │
                    │   (Python)   │                          │    Log       │
                    └──────────────┘                          └──────────────┘
```

**Process:**
1. Employee accesses clock interface (web or QR)
2. System validates employee ID and branch assignment
3. Geofence validation checks location against branch coordinates
4. If face recognition enabled: calls Python microservice
5. Creates or updates attendance record
6. Prevents double clock-in
7. Auto-transfers branch if clocking at different branch
8. Logs activity to `activity_logs`
9. Triggers push notification (if subscribed)

### 3. Payroll Calculation Flow

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│    Admin     │────▶│  payroll.php │────▶│  Attendance  │────▶│  Employee    │
│  (Dashboard) │     │              │     │   Data       │     │   Rates      │
└──────────────┘     └──────────────┘     └──────────────┘     └──────────────┘
                           │
                           ▼
                    ┌──────────────┐
                    │  Philippine  │
                    │  Deductions  │
                    │  (SSS/PH/PI) │
                    └──────────────┘
```

**Process:**
1. Admin selects pay period (weekly/monthly)
2. System fetches attendance records for date range
3. Calculates days present, late, absent for each employee
4. Retrieves employee daily rates from `employees` table
5. Computes basic pay: `days_present × daily_rate`
6. Calculates OT pay: `ot_hours × (daily_rate / 8)`
7. Applies government deductions (SSS, PhilHealth, Pag-IBIG)
8. Generates net pay amount
9. Displays in admin dashboard with export options

---

# Data Flow & Workflows

## Database Schema Relationships

```
                    ┌─────────────────────┐
                    │     branches      │
                    │───────────────────│
                    │ id (PK)           │
                    │ branch_name       │
                    │ lat, long         │
                    │ geofence_radius   │
                    └─────────┬─────────┘
                              │
              ┌───────────────┼───────────────┐
              │               │               │
              ▼               ▼               ▼
    ┌──────────────┐  ┌──────────────┐  ┌──────────────┐
    │   employees  │  │  attendance  │  │   transfers  │
    │──────────────│  │──────────────│  │──────────────│
    │ id (PK)      │  │ id (PK)      │  │ id (PK)      │
    │ branch_id FK │  │ employee_id  │  │ employee_id  │
    │ daily_rate   │  │ branch_name  │  │ from_branch  │
    │ position     │  │ status       │  │ to_branch    │
    │ password_hash│  │ time_in/out  │  │ transfer_date│
    │ sss_number   │  │ ot_hours     │  └──────────────┘
    └──────────────┘  └──────────────┘
           │
           │
           ▼
    ┌──────────────┐  ┌──────────────┐
    │ cash_advance │  │  overtime    │
    │──────────────│  │──────────────│
    │ id (PK)      │  │ id (PK)      │
    │ employee_id  │  │ employee_id  │
    │ amount       │  │ ot_hours     │
    │ status       │  │ status       │
    └──────────────┘  └──────────────┘
```

## Key Workflows

### A. Employee Onboarding Workflow

```
Step 1: Admin creates employee record
        └─▶ employee/api/add_employee.php
        └─▶ Insert into `employees` table
        └─▶ Generate employee_code (e.g., E0001)

Step 2: Set daily rate and branch assignment
        └─▶ employee/employees.php (edit mode)
        └─▶ Update `employees.daily_rate`
        └─▶ Update `employees.current_branch`

Step 3: Employee enrolls face (optional)
        └─▶ Scan face via mobile/web
        └─▶ POST to face-recognition-v2/enroll
        └─▶ Store embedding in face_recognition.json

Step 4: Employee first login
        └─▶ login.php
        └─▶ Verify credentials
        └─▶ Initialize session
        └─▶ Redirect to dashboard
```

### B. Daily Attendance Workflow

```
06:00 AM ─┬─▶ System: Cron job checks table structure
          │
06:40 AM ─┼─▶ Scanner: QR time-in enabled (configurable)
          │
07:00 AM ─┼─▶ Employee: Clock-in via web/QR/face
          │   └─▶ clock_in.php validates geofence
          │   └─▶ Creates attendance record
          │   └─▶ status = 'Present' or 'Late'
          │
12:00 PM ─┼─▶ (Optional) Clock-out for lunch
          │
01:00 PM ─┼─▶ (Optional) Clock-in after lunch
          │
05:00 PM ─┼─▶ Employee: Clock-out
          │   └─▶ clock_out.php calculates hours
          │   └─▶ Updates attendance.total_ot_hrs
          │
11:59 PM ─┴─▶ System: Mark absent employees
              └─▶ Cron: mark_attendance_absent_api.php
              └─▶ Sets status = 'Absent' for no-shows
```

### C. Payroll Processing Workflow

```
┌─────────────────────────────────────────────────────────────────────┐
│                    PAYROLL PROCESSING PIPELINE                      │
└─────────────────────────────────────────────────────────────────────┘

Week 1-4: Data Collection
├── Daily attendance recorded
├── Overtime requests submitted and approved
├── Cash advance requests processed
└── Late arrivals flagged

End of Week:
├── Admin: Access employee/payroll.php
├── Select: Week number or Monthly view
├── System: Aggregate attendance data
│   ├── Count days present/late/absent per employee
│   ├── Sum total_ot_hrs per employee
│   └── Calculate basic pay + OT pay
├── System: Apply deductions
│   ├── SSS: min(gross × 0.045, 1125)
│   ├── PhilHealth: min(gross × 0.035, 2450)
│   ├── Pag-IBIG: 100 (fixed)
│   └── Tax: based on taxable income brackets
└── Output: Net pay per employee

Admin Actions:
├── Review calculated amounts
├── Export to Excel (if needed)
├── Mark as "processed" in system
└── Generate payslips (external process)
```

---

# Deployment Architecture

## Current Deployment: Single Server (WAMP)

```
┌─────────────────────────────────────────────────────────────────────┐
│                     WINDOWS SERVER (WAMP64)                         │
│  ┌─────────────────────────────────────────────────────────────┐ │
│  │                    Apache HTTP Server                        │ │
│  │                    Port: 80 / 443                            │ │
│  │  ┌─────────────────────────────────────────────────────┐   │ │
│  │  │              DocumentRoot: c:/wamp64/www/main          │   │ │
│  │  │                                                      │   │ │
│  │  │  • PHP 8.3.28 (mod_php)                             │   │ │
│  │  │  • .htaccess rewrite rules                          │   │ │
│  │  │  • SSL certificates (if configured)                 │   │ │
│  │  └─────────────────────────────────────────────────────┘   │ │
│  └─────────────────────────────────────────────────────────────┘ │
│                              │                                      │
│                              ▼                                      │
│  ┌─────────────────────────────────────────────────────────────┐ │
│  │                   MySQL 8.4.7 Server                         │ │
│  │                   Port: 3306                                 │ │
│  │  • Database: attendance_db                                   │ │
│  │  • Storage: InnoDB + MyISAM (mixed)                        │ │
│  │  • Charset: utf8mb4                                          │ │
│  └─────────────────────────────────────────────────────────────┘ │
│                              │                                      │
│                              ▼                                      │
│  ┌─────────────────────────────────────────────────────────────┐ │
│  │              Python Face Recognition Service                 │ │
│  │              Port: 8000 (FastAPI/Uvicorn)                   │ │
│  │  • DeepFace model loading                                    │ │
│  │  • File-based face embeddings storage                        │ │
│  │  • Runs as separate process (not service)                   │ │
│  └─────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────┘
```

## Production Deployment Recommendations

### Recommended: Docker Containerized Deployment

```yaml
# docker-compose.yml structure
version: '3.8'

services:
  web:
    image: php:8.3-apache
    ports:
      - "80:80"
    volumes:
      - ./:/var/www/html
    depends_on:
      - db
      - face-service

  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MYSQL_DATABASE: attendance_db
    volumes:
      - mysql_data:/var/lib/mysql

  face-service:
    build: ./face-recognition-v2
    ports:
      - "8000:8000"
    volumes:
      - face_data:/app/database

  # Optional: Redis for session storage
  redis:
    image: redis:alpine
    volumes:
      - redis_data:/data
```

### Recommended: Cloud Architecture (AWS)

```
┌─────────────────────────────────────────────────────────────────────┐
│                         AWS CLOUD ARCHITECTURE                      │
└─────────────────────────────────────────────────────────────────────┘

                                ┌──────────────┐
                                │   Route 53   │
                                │    (DNS)     │
                                └──────┬───────┘
                                       │
                                       ▼
┌─────────────────────────────────────────────────────────────────────┐
│                         CloudFront (CDN)                            │
│                    Static asset caching                             │
└─────────────────────────────────────────────────────────────────────┘
                                       │
                                       ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      Application Load Balancer                      │
│                   SSL termination, health checks                    │
└─────────────────────────────────────────────────────────────────────┘
                                       │
           ┌───────────────────────────┼───────────────────────────┐
           ▼                           ▼                           ▼
┌─────────────────────┐  ┌─────────────────────┐  ┌─────────────────────┐
│   ECS/Fargate       │  │   ECS/Fargate       │  │   ECS/Fargate       │
│   (PHP App)         │  │   (PHP App)         │  │   (PHP App)         │
│                     │  │                     │  │                     │
│  • Auto-scaling     │  │  • Auto-scaling     │  │  • Auto-scaling     │
│  • Health checks    │  │  • Health checks    │  │  • Health checks    │
└─────────┬───────────┘  └─────────┬───────────┘  └─────────┬───────────┘
          │                        │                        │
          └────────────────────────┼────────────────────────┘
                                   ▼
                    ┌─────────────────────────────┐
                    │      ElastiCache (Redis)    │
                    │   Session storage, caching  │
                    └─────────────────────────────┘
                                   │
                                   ▼
                    ┌─────────────────────────────┐
                    │      RDS (MySQL 8.0)        │
                    │   Multi-AZ, backups         │
                    └─────────────────────────────┘
                                   │
                                   ▼
                    ┌─────────────────────────────┐
                    │   S3 (File storage)         │
                    │   Profile images, backups   │
                    └─────────────────────────────┘
```

---

# Infrastructure Setup

## Environment Requirements

### Server Specifications (Minimum)

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| CPU | 4 cores | 8+ cores |
| RAM | 8 GB | 16+ GB |
| Disk | 100 GB SSD | 500 GB SSD |
| OS | Windows Server 2019 | Windows Server 2022 / Linux |
| PHP | 8.1 | 8.3+ |
| MySQL | 8.0 | 8.0+ |
| Python | 3.9 | 3.11+ |

### PHP Extensions Required

```
Required:
- mysqli (MySQL connectivity)
- gd (Image processing)
- openssl (Encryption)
- mbstring (UTF-8 support)
- json (API handling)

Recommended:
- redis (Session storage)
- opcache (Performance)
- intl (Internationalization)
- curl (External APIs)
```

### MySQL Configuration

```ini
# my.cnf recommendations
[mysqld]
# Character set
character-set-server=utf8mb4
collation-server=utf8mb4_unicode_ci

# Buffer sizes
innodb_buffer_pool_size=4G
innodb_log_file_size=512M

# Connection limits
max_connections=200
wait_timeout=28800

# Query cache (if enabled)
query_cache_type=1
query_cache_size=256M

# Logging (development only)
general_log=0
slow_query_log=1
long_query_time=2
```

## Configuration Files

### 1. Environment Variables (.env)

```bash
# Database Configuration
DB_HOST=localhost
DB_USER=attendance_user
DB_PASSWORD=secure_password_here
DB_NAME=attendance_db

# Application Settings
APP_ENV=production
APP_DEBUG=false
APP_URL=https://attendance.jajr.com

# Security
SESSION_LIFETIME=120
MAX_LOGIN_ATTEMPTS=5
LOCKOUT_DURATION=300

# Face Recognition Service
FACE_API_URL=http://localhost:8000
FACE_API_TIMEOUT=30

# Push Notifications
VAPID_PUBLIC_KEY=your_vapid_public_key
VAPID_PRIVATE_KEY=your_vapid_private_key
VAPID_SUBJECT=mailto:admin@jajr.com

# Email (for notifications)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=notifications@jajr.com
SMTP_PASSWORD=app_password

# File Upload Limits
MAX_UPLOAD_SIZE=5M
ALLOWED_IMAGE_TYPES=jpg,jpeg,png
```

### 2. Apache VirtualHost Configuration

```apache
<VirtualHost *:80>
    ServerName attendance.jajr.com
    DocumentRoot "c:/wamp64/www/main"
    
    # Redirect to HTTPS
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</VirtualHost>

<VirtualHost *:443>
    ServerName attendance.jajr.com
    DocumentRoot "c:/wamp64/www/main"
    
    SSLEngine on
    SSLCertificateFile "c:/wamp64/ssl/attendance.crt"
    SSLCertificateKeyFile "c:/wamp64/ssl/attendance.key"
    
    # PHP Configuration
    php_value upload_max_filesize 5M
    php_value post_max_size 5M
    php_value max_execution_time 300
    php_value memory_limit 256M
    
    # Enable rewrite
    <Directory "c:/wamp64/www/main">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Security headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set X-Content-Type-Options "nosniff"
    
    # Logging
    ErrorLog "logs/attendance-error.log"
    CustomLog "logs/attendance-access.log" combined
</VirtualHost>
```

### 3. Face Recognition Service Setup

```bash
# Windows Service Setup (using NSSM)
# 1. Install Python 3.11+
# 2. Create virtual environment
cd c:\wamp64\www\main\face-recognition-v2
python -m venv venv
venv\Scripts\activate

# 3. Install dependencies
pip install fastapi uvicorn deepface pydantic python-multipart

# 4. Test service
uvicorn main:app --host 0.0.0.0 --port 8000

# 5. Create Windows Service (using NSSM)
nssm install FaceRecognition "c:\wamp64\www\main\face-recognition-v2\venv\Scripts\python.exe"
nssm set FaceRecognition AppDirectory "c:\wamp64\www\main\face-recognition-v2"
nssm set FaceRecognition AppParameters "-m uvicorn main:app --host 0.0.0.0 --port 8000"
nssm start FaceRecognition
```

---

# Operational Procedures

## Daily Operations

### 1. Morning Startup Checklist

```bash
# 1. Check services are running
net start wampapache64
net start wampmysqld64
sc query FaceRecognition | find "RUNNING"

# 2. Verify database connectivity
mysql -u root -p -e "SELECT 1 FROM attendance_db.employees LIMIT 1"

# 3. Check disk space
dir c:\ | find "bytes free"

# 4. Review error logs
tail -n 50 c:\wamp64\logs\attendance-error.log

# 5. Test face recognition service
curl http://localhost:8000/health
```

### 2. Database Backup Procedure

```bash
# Daily backup script (backup_daily.bat)
@echo off
set TIMESTAMP=%date:~-4,4%%date:~-10,2%%date:~-7,2%_%time:~0,2%%time:~3,2%%time:~6,2%
set TIMESTAMP=%TIMESTAMP: =0%
set BACKUP_DIR=C:\backups\attendance
set DB_NAME=attendance_db
set DB_USER=root
set DB_PASS=your_password

# Create backup directory if not exists
if not exist %BACKUP_DIR% mkdir %BACKUP_DIR%

# Full database dump
mysqldump -u%DB_USER% -p%DB_PASS% %DB_NAME% > %BACKUP_DIR%\%DB_NAME%_%TIMESTAMP%.sql

# Compress backup
7z a %BACKUP_DIR%\%DB_NAME%_%TIMESTAMP%.sql.7z %BACKUP_DIR%\%DB_NAME%_%TIMESTAMP%.sql

# Delete uncompressed
del %BACKUP_DIR%\%DB_NAME%_%TIMESTAMP%.sql

# Keep only last 30 days
forfiles /P %BACKUP_DIR% /S /M *.7z /D -30 /C "cmd /c del @path"

# Log backup
echo %date% %time% - Backup completed: %DB_NAME%_%TIMESTAMP%.sql.7z >> %BACKUP_DIR%\backup.log
```

### 3. Log Rotation

```powershell
# PowerShell script for log rotation (rotate_logs.ps1)
$logDir = "C:\wamp64\www\main\logs"
$archiveDir = "C:\wamp64\www\main\logs\archive"
$date = Get-Date -Format "yyyyMMdd"

# Create archive directory
if (!(Test-Path $archiveDir)) {
    New-Item -ItemType Directory -Path $archiveDir
}

# Compress logs older than 7 days
Get-ChildItem $logDir -File | Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-7) } | ForEach-Object {
    Compress-Archive -Path $_.FullName -DestinationPath "$archiveDir\$($_.BaseName)_$date.zip"
    Remove-Item $_.FullName
}

# Delete archives older than 90 days
Get-ChildItem $archiveDir -File | Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-90) } | Remove-Item
```

## Weekly Operations

### 1. Performance Review

```sql
-- Check slow queries
SELECT 
    DIGEST_TEXT as query,
    COUNT_STAR as exec_count,
    AVG_TIMER_WAIT/1000000000 as avg_latency_ms,
    MAX_TIMER_WAIT/1000000000 as max_latency_ms
FROM performance_schema.events_statements_summary_by_digest
ORDER BY AVG_TIMER_WAIT DESC
LIMIT 10;

-- Check table sizes
SELECT 
    table_name,
    ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb,
    table_rows
FROM information_schema.tables
WHERE table_schema = 'attendance_db'
ORDER BY size_mb DESC;

-- Check for deadlocks
SHOW ENGINE INNODB STATUS;
```

### 2. Security Audit

```bash
# Check for unauthorized access attempts
grep -i "unauthorized\|failed login\|invalid password" c:\wamp64\logs\attendance-error.log

# Review file permissions
icacls "c:\wamp64\www\main" /T

# Check for modified files (if version controlled)
cd c:\wamp64\www\main
git status
```

## Monthly Operations

### 1. Database Maintenance

```sql
-- Optimize tables
OPTIMIZE TABLE attendance;
OPTIMIZE TABLE activity_logs;

-- Analyze tables for query optimizer
ANALYZE TABLE attendance;
ANALYZE TABLE employees;

-- Check for orphaned records
SELECT a.* 
FROM attendance a 
LEFT JOIN employees e ON a.employee_id = e.id 
WHERE e.id IS NULL;

-- Delete old activity logs (keep 90 days)
DELETE FROM activity_logs 
WHERE timestamp < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Verify backup integrity
-- (Restore to test environment and validate)
```

### 2. Certificate Renewal

```bash
# If using Let's Encrypt (on Windows with WACS)
wacs.exe --renew --baseuri "https://acme-v02.api.letsencrypt.org/"

# Restart Apache after renewal
net stop wampapache64
net start wampapache64
```

---

# Monitoring & Maintenance

## Health Check Endpoints

### Application Health Check

```php
<?php
// health_check.php - System status endpoint

header('Content-Type: application/json');

$checks = [
    'database' => false,
    'face_service' => false,
    'disk_space' => false,
    'memory' => false
];
$status = 'healthy';

// Database check
try {
    require_once 'conn/db_connection.php';
    $result = mysqli_query($db, "SELECT 1");
    $checks['database'] = mysqli_fetch_row($result)[0] === 1;
} catch (Exception $e) {
    $checks['database'] = false;
    $status = 'unhealthy';
}

// Face service check
$faceHealth = @file_get_contents('http://localhost:8000/health');
$checks['face_service'] = $faceHealth !== false;
if (!$checks['face_service']) $status = 'degraded';

// Disk space check
$free = disk_free_space('C:');
$total = disk_total_space('C:');
$checks['disk_space'] = ($free / $total) > 0.1; // Alert if < 10% free
if (!$checks['disk_space']) $status = 'critical';

// Memory check
$checks['memory'] = memory_get_usage(true) < (256 * 1024 * 1024); // < 256MB

$response = [
    'status' => $status,
    'timestamp' => date('c'),
    'checks' => $checks,
    'metrics' => [
        'disk_free_gb' => round($free / 1024 / 1024 / 1024, 2),
        'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2)
    ]
];

http_response_code($status === 'healthy' ? 200 : ($status === 'degraded' ? 200 : 503));
echo json_encode($response);
```

## Monitoring Dashboard Setup

### Using Prometheus + Grafana (Recommended)

```yaml
# prometheus.yml
scrape_configs:
  - job_name: 'attendance-app'
    static_configs:
      - targets: ['localhost:80']
    metrics_path: '/health_check.php'
    scrape_interval: 30s
  
  - job_name: 'mysql'
    static_configs:
      - targets: ['localhost:9104']  # mysqld_exporter
  
  - job_name: 'face-service'
    static_configs:
      - targets: ['localhost:8000']
```

### Key Metrics to Monitor

| Metric | Warning Threshold | Critical Threshold |
|--------|-------------------|-------------------|
| Database connections | > 80% of max | > 95% of max |
| Disk usage | > 80% | > 90% |
| Memory usage | > 70% | > 85% |
| HTTP 5xx errors | > 1% | > 5% |
| API response time | > 500ms | > 2000ms |
| Failed login attempts | > 10/min | > 30/min |

## Alerting Rules

```yaml
# alerts.yml
alerts:
  - name: database_down
    condition: health_check{check="database"} == 0
    severity: critical
    message: "Database connectivity lost"
    
  - name: high_error_rate
    condition: rate(http_errors_total[5m]) > 0.05
    severity: warning
    message: "Error rate above 5%"
    
  - name: disk_space_low
    condition: disk_free_percent < 10
    severity: critical
    message: "Disk space below 10%"
    
  - name: face_service_down
    condition: health_check{check="face_service"} == 0
    severity: warning
    message: "Face recognition service unavailable"
```

---

# Troubleshooting Guide

## Common Issues & Solutions

### Issue 1: Login Not Working

**Symptoms:**
- "Invalid credentials" error
- Redirect loop
- Session not persisting

**Diagnosis Steps:**
```bash
# 1. Check session storage
ls -la c:\wamp64\tmp\sess_*

# 2. Verify session configuration in php.ini
grep "session." c:\wamp64\bin\php\php8.3.28\php.ini

# 3. Check for session corruption
type c:\wamp64\tmp\sess_<session_id>

# 4. Review login errors
grep -i "login\|session\|password" c:\wamp64\logs\attendance-error.log
```

**Solutions:**
1. Clear session files: `del c:\wamp64\tmp\sess_*`
2. Check session.save_path permissions
3. Verify database connection in conn/db_connection.php
4. Ensure session_start() is called before any output

---

### Issue 2: Clock-In Fails

**Symptoms:**
- "Already clocked in" when not
- Geofence validation always fails
- Face recognition timeout

**Diagnosis Steps:**
```sql
-- Check for stuck records
SELECT employee_id, time_in, time_out, status 
FROM attendance 
WHERE time_out IS NULL 
AND attendance_date < CURDATE();

-- Check geofence configuration
SELECT branch_name, lat, long, geofence_radius_meters 
FROM branches 
WHERE branch_name = 'Problem Branch';

-- Test face service
curl -X POST http://localhost:8000/verify \
  -H "Content-Type: application/json" \
  -d '{"employee_id": "E0001", "image": "base64_data"}'
```

**Solutions:**
1. Clear stuck time_out records for past dates
2. Update branch coordinates if geofence is wrong
3. Restart face recognition service
4. Check employee face enrollment status

---

### Issue 3: Payroll Calculation Wrong

**Symptoms:**
- Missing employees in report
- Incorrect day counts
- OT hours not calculated

**Diagnosis Steps:**
```sql
-- Check employee daily rates
SELECT id, first_name, daily_rate, status 
FROM employees 
WHERE status = 'Active' AND (daily_rate IS NULL OR daily_rate = 0);

-- Verify attendance records for period
SELECT 
    employee_id, 
    COUNT(*) as total_records,
    SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_days,
    SUM(total_ot_hrs) as total_ot
FROM attendance 
WHERE attendance_date BETWEEN '2026-04-01' AND '2026-04-30'
GROUP BY employee_id;

-- Check for data type issues
SHOW COLUMNS FROM attendance WHERE Field = 'total_ot_hrs';
```

**Solutions:**
1. Set daily rates for employees showing as 0
2. Run database migration if total_ot_hrs is VARCHAR
3. Recalculate attendance status for disputed dates

---

### Issue 4: Face Recognition Not Working

**Symptoms:**
- "Face not enrolled" error
- Verification always fails
- Service timeout

**Diagnosis Steps:**
```bash
# 1. Check service status
sc query FaceRecognition

# 2. Test service health
curl http://localhost:8000/

# 3. Check face data directory
ls -la c:\wamp64\www\main\face-recognition-v2\database\

# 4. Review Python logs
type c:\wamp64\www\main\face-recognition-v2\service.log
```

**Solutions:**
1. Restart service: `net stop FaceRecognition && net start FaceRecognition`
2. Reinstall DeepFace: `pip install --force-reinstall deepface`
3. Clear and re-enroll face data
4. Check firewall rules for port 8000

---

### Issue 5: Push Notifications Not Sending

**Symptoms:**
- No notifications received
- VAPID errors in logs
- Subscriptions not saving

**Diagnosis Steps:**
```sql
-- Check VAPID keys in database
SELECT * FROM vapid_keys LIMIT 1;

-- Verify push subscriptions
SELECT COUNT(*) as sub_count FROM push_subscriptions;

-- Check notification queue
SELECT * FROM notifications WHERE sent = 0 AND retry_count < 3;
```

**Solutions:**
1. Regenerate VAPID keys: `php generate_vapid.php`
2. Update service worker: `sw.js`
3. Check browser console for subscription errors
4. Verify SSL certificate is valid (required for WebPush)

---

### Issue 6: High Server Load

**Symptoms:**
- Slow response times
- Apache max connections reached
- MySQL CPU at 100%

**Diagnosis Steps:**
```bash
# Check Apache connections
netstat -an | find "ESTABLISHED" | find ":80" | wc -l

# Check MySQL processes
mysqladmin processlist

# Review slow query log
type c:\wamp64\logs\mysql-slow.log

# Check for long-running queries
SELECT * FROM information_schema.processlist 
WHERE command != 'Sleep' AND time > 60;
```

**Solutions:**
1. Enable OPcache in PHP
2. Add missing database indexes
3. Implement Redis for session storage
4. Enable MySQL query cache
5. Consider load balancer with multiple app servers

---

## Emergency Procedures

### Complete System Failure Recovery

```
1. STOP all services
   net stop wampapache64
   net stop wampmysqld64
   net stop FaceRecognition

2. Assess damage
   - Check disk space (full disk = system failure)
   - Review Windows Event Logs
   - Check for hardware failures

3. If database corruption suspected:
   a. Restore from last backup
   b. mysql -u root -p attendance_db < backup_file.sql
   
4. If file corruption suspected:
   a. Restore from git: git reset --hard HEAD
   b. Or restore from file backup

5. Clear temporary files
   del c:\wamp64\tmp\*
   del c:\wamp64\www\main\logs\*

6. Restart services
   net start wampmysqld64
   net start wampapache64
   net start FaceRecognition

7. Verify recovery
   - Test login
   - Test clock-in/out
   - Verify payroll calculations
```

---

## Performance Tuning

### Database Optimization

```sql
-- Add recommended indexes
CREATE INDEX idx_attendance_date ON attendance(attendance_date);
CREATE INDEX idx_employee_date ON attendance(employee_id, attendance_date);
CREATE INDEX idx_status ON attendance(status);
CREATE INDEX idx_branch ON attendance(branch_name);
CREATE INDEX idx_activity_timestamp ON activity_logs(timestamp);

-- Convert MyISAM to InnoDB (for transaction support)
ALTER TABLE attendance ENGINE=InnoDB;
ALTER TABLE activity_logs ENGINE=InnoDB;

-- Analyze tables
ANALYZE TABLE attendance;
ANALYZE TABLE employees;
ANALYZE TABLE activity_logs;
```

### PHP Optimization

```ini
; php.ini optimizations
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.validate_timestamps=0 ; Set to 1 in development

; Realpath cache
realpath_cache_size=4096K
realpath_cache_ttl=600
```

---

## Support Contacts & Resources

| Resource | Contact/Location |
|----------|------------------|
| PHP Documentation | https://php.net/docs.php |
| MySQL Documentation | https://dev.mysql.com/doc/ |
| DeepFace Library | https://github.com/serengil/deepface |
| FastAPI | https://fastapi.tiangolo.com/ |
| WebPush VAPID | https://web-push-codelab.glitch.me/ |
| Local Logs | c:\wamp64\logs\ |
| Application Logs | c:\wamp64\www\main\logs\ |

---

**End of Documentation**

*This technical architecture and operations guide should be maintained alongside code changes. Review quarterly for accuracy.*
