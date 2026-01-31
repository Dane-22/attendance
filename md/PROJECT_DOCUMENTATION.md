# JAJR Attendance Management System - Complete Documentation

## 📋 Project Overview

**JAJR Attendance Management System** is a comprehensive web-based application designed for JAJR Construction Company to manage employee attendance, payroll, and operational workflows. The system features role-based access control, real-time monitoring, and multi-branch support.

**Technology Stack:**
- **Backend:** PHP 8.3+ with MySQL/MariaDB
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
- **Database:** MySQL 8.4.7
- **Server:** WAMP64 (Windows Apache MySQL PHP)
- **UI Framework:** Custom dark theme with gold accents

---

## 🏗️ System Architecture

### Core Components

```
├── Public Layer (/)
│   ├── index.php              # Landing page
│   ├── login.php              # Authentication
│   └── signup.php             # User registration
│
├── Employee Portal (/employee/)
│   ├── dashboard.php          # Main employee dashboard
│   ├── attendance.php         # Manual attendance marking
│   ├── employees.php          # Employee management
│   ├── select_employee.php    # Employee selection interface
│   ├── billing.php            # Payroll & billing system
│   ├── monitoring_dashboard.php # Real-time monitoring
│   ├── ai_assistant.php       # AI chat interface
│   └── api/                   # REST API endpoints
│       ├── clock_in.php       # Clock-in functionality
│       └── clock_out.php      # Clock-out functionality
│
├── Admin Portal (/admin/)
│   └── logs.php               # Activity logs viewer
│
├── Assets (assets/)
│   ├── css/                   # Stylesheets
│   ├── js/                    # JavaScript files
│   └── img/                   # Images and icons
│
└── Core Files
    ├── conn/db_connection.php  # Database connection
    ├── functions.php          # Utility functions
    └── Various config files
```

### Database Schema

#### Core Tables

**1. employees**
```sql
- id (INT, PRIMARY KEY)
- employee_code (VARCHAR(50), UNIQUE)
- first_name, middle_name, last_name (VARCHAR(100))
- email (VARCHAR(100), UNIQUE)
- branch_name (VARCHAR(50))
- password_hash (VARCHAR(255))
- position (VARCHAR(50), DEFAULT 'Employee')
- status (VARCHAR(50), DEFAULT 'Active')
- profile_image (VARCHAR(255), NULL)
- daily_rate (DECIMAL(10,2), DEFAULT 600.00)
- created_at, updated_at (TIMESTAMP)
```

**2. attendance**
```sql
- id (INT, PRIMARY KEY)
- employee_id (INT, FOREIGN KEY)
- attendance_date (DATE)
- time_in, time_out (TIME)
- status (ENUM: 'Present', 'Absent')
- branch_name (VARCHAR(50))
- created_at, updated_at (TIMESTAMP)
```

**3. branches**
```sql
- id (INT, PRIMARY KEY)
- branch_name (VARCHAR(255), UNIQUE)
- created_at, updated_at (TIMESTAMP)
- is_active (TINYINT, DEFAULT 1)
```

**4. activity_logs**
```sql
- id (INT, PRIMARY KEY)
- user_id (INT, FOREIGN KEY)
- action (VARCHAR(255))
- details (TEXT)
- ip_address (VARCHAR(45))
- created_at (TIMESTAMP)
```

**5. employee_transfers**
```sql
- id (INT, PRIMARY KEY)
- employee_id (INT, FOREIGN KEY)
- from_branch, to_branch (VARCHAR(50))
- transfer_date (DATE)
- reason (TEXT)
- approved_by (INT)
- created_at (TIMESTAMP)
```

---

## 👥 User Roles & Permissions

### 1. Super Admin
- **Full System Access**
- All employee portal features
- Admin logs access
- System configuration
- User: `admin@jajrconstruction.com`

### 2. Admin/Manager/Supervisor
- **Employee Management**
- Manual attendance marking
- Branch management
- Payroll & billing access
- Document management
- Activity logs access

### 3. Regular Employee
- **Limited Access**
- Personal dashboard
- Clock in/out functionality
- View own attendance
- Basic employee directory
- AI assistant access

---

## 🚀 Key Features

### 1. **Authentication & Security**
- Secure login/logout system
- Session-based authentication
- Role-based access control (RBAC)
- Activity logging with IP tracking
- Rate limiting protection
- Password hashing (MD5)

### 2. **Attendance Management**
- **Real-time Clock In/Out:** AJAX-based time tracking
- **Manual Attendance Marking:** Admin can set attendance for employees
- **Multi-branch Support:** Track attendance across different locations
- **Status Tracking:** Present/Absent with timestamps
- **Historical Records:** Complete attendance history

### 3. **Employee Management**
- **CRUD Operations:** Add, edit, delete employees
- **Profile Pictures:** Upload and display employee photos
- **Branch Assignment:** Assign employees to specific branches
- **Position Management:** Track employee roles and positions
- **Status Management:** Active/Inactive employee status

### 4. **Branch Management**
- **Dynamic Branch Creation:** Add new branches on-the-fly
- **Branch-based Filtering:** Filter employees and attendance by branch
- **Branch Transfer Tracking:** Log employee movements between branches
- **Branch Statistics:** Monitor deployment by location

### 5. **Payroll & Billing System**
- **Daily Rate Calculation:** Configurable daily rates per employee
- **Automated Payroll:** Weekly/monthly salary calculations
- **Billing Reports:** Generate detailed billing statements
- **Rate Management:** Update employee compensation rates

### 6. **Monitoring Dashboard**
- **Real-time Metrics:** Live attendance statistics
- **Branch Deployment:** Current employee distribution
- **Performance Tracking:** Attendance rates and trends
- **Visual Analytics:** Charts and graphs for insights

### 7. **AI Assistant Integration**
- **Chat Interface:** Interactive AI assistant widget
- **Contextual Help:** AI-powered support system
- **Real-time Responses:** Dynamic conversation handling

### 8. **Activity Logging**
- **Comprehensive Audit Trail:** Log all system activities
- **IP Address Tracking:** Monitor access locations
- **User Action History:** Complete activity timeline
- **Search & Filter:** Advanced log querying capabilities

---

## 🔧 Technical Implementation

### Database Connection
```php
// conn/db_connection.php
$db = mysqli_connect('127.0.0.1:3306', 'root', '', 'attendance_db');
```

### Session Management
```php
// Standard session variables
$_SESSION['employee_id']      // User ID
$_SESSION['employee_code']    // Unique employee code
$_SESSION['first_name']       // User's first name
$_SESSION['last_name']        // User's last name
$_SESSION['position']         // User role (Employee/Admin/etc.)
$_SESSION['logged_in']        // Authentication flag
```

### Security Guards
```php
// Role-based access control
if (!isset($_SESSION['logged_in'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SESSION['position'] === 'super_admin') {
    header('Location: ../admin/dashboard.php');
    exit;
}
```

### AJAX API Endpoints
- `POST /employee/api/clock_in.php` - Clock in employee
- `POST /employee/api/clock_out.php` - Clock out employee
- `POST /employee/upload_profile.php` - Upload profile image

---

## 📱 User Interface

### Design System
- **Color Scheme:** Dark theme (#0b0b0b background, #FFD700 gold accents)
- **Typography:** Clean, modern fonts with proper hierarchy
- **Layout:** Responsive grid system with mobile-first approach
- **Components:** Consistent button styles, form elements, and cards

### View Modes
- **Grid View:** Card-based layout for employee lists
- **List View:** Compact list format
- **Details View:** Expanded information display
- **Mobile Responsive:** Optimized for all screen sizes

### Navigation
- **Sidebar Menu:** Role-based menu items
- **Breadcrumb Navigation:** Clear page hierarchy
- **Quick Actions:** Context-sensitive action buttons

---

## 🔄 Business Workflows

### 1. **Daily Attendance Process**
1. Employee logs into system
2. Clicks "Clock In" button
3. System records time_in timestamp
4. Employee performs work activities
5. Clicks "Clock Out" button
6. System calculates total hours worked
7. Attendance record is stored with status

### 2. **Employee Onboarding**
1. Admin creates new employee record
2. Assigns employee code, branch, and position
3. Sets daily rate for compensation
4. Employee receives login credentials
5. Employee logs in and sets up profile picture
6. System logs onboarding activity

### 3. **Branch Management**
1. Admin identifies need for new branch
2. Creates branch via interface
3. Assigns employees to new branch
4. System tracks branch transfers
5. Updates attendance records accordingly

### 4. **Payroll Processing**
1. System calculates working days per period
2. Multiplies by employee daily rate
3. Generates payroll reports
4. Admin reviews and approves payments
5. System logs payroll activities

---

## 📊 Data Analytics & Reporting

### Real-time Metrics
- **Attendance Rate:** Present vs total employees
- **Branch Utilization:** Employees per location
- **Time Tracking:** Hours worked analysis
- **Trend Analysis:** Historical performance data

### Reporting Features
- **Date Range Filtering:** Custom report periods
- **Branch-specific Reports:** Location-based analytics
- **Employee Performance:** Individual attendance history
- **Export Capabilities:** Data export functionality

---

## 🔒 Security Features

### Authentication
- **Password Hashing:** MD5 encryption for passwords
- **Session Security:** Secure session management
- **Login Attempts Tracking:** Monitor failed login attempts
- **Account Lockout:** Prevent brute force attacks

### Access Control
- **Role-based Permissions:** Granular access control
- **Page-level Guards:** Protect sensitive pages
- **Action Logging:** Audit all user activities
- **IP Tracking:** Monitor access locations

### Data Protection
- **SQL Injection Prevention:** Prepared statements
- **XSS Protection:** Input sanitization
- **File Upload Security:** Type and size validation
- **Secure File Storage:** Controlled upload directories

---

## 🚀 Deployment & Setup

### System Requirements
- **PHP:** 8.3.28 or higher
- **MySQL:** 8.4.7 or compatible
- **Apache:** 2.4+ (via WAMP64)
- **Browser:** Modern browsers with JavaScript enabled

### Installation Steps
1. **Database Setup:**
   ```sql
   CREATE DATABASE attendance_db;
   -- Import attendance_db (3).sql
   ```

2. **File Permissions:**
   - `employee/uploads/` - 0755 (writeable)
   - `conn/db_connection.php` - Configure database credentials

3. **Initial Admin Account:**
   - Email: `admin@jajrconstruction.com`
   - Default password: `password`

4. **Web Server Configuration:**
   - Document root: `c:\wamp64\www\attendance_web_Copy\`
   - URL: `http://localhost/attendance_web_Copy/`

---

## 🔧 Maintenance & Support

### Regular Tasks
- **Database Backups:** Daily automated backups
- **Log Rotation:** Archive old activity logs
- **Performance Monitoring:** Track system metrics
- **Security Updates:** Keep PHP/MySQL updated

### Troubleshooting
- **Common Issues:**
  - Session timeout problems
  - Database connection errors
  - File upload failures
  - Permission issues

- **Debug Mode:** Enable error reporting in development
- **Log Analysis:** Use activity logs for issue diagnosis

---

## 📈 Future Enhancements

### Planned Features
- **Mobile App:** Native iOS/Android applications
- **Biometric Authentication:** Fingerprint/face recognition
- **GPS Tracking:** Location-based attendance
- **Advanced Analytics:** Machine learning insights
- **API Integration:** Third-party system connections
- **Multi-language Support:** Internationalization

### Technical Improvements
- **Password Security:** Upgrade to bcrypt hashing
- **API Documentation:** RESTful API endpoints
- **Testing Framework:** Unit and integration tests
- **Performance Optimization:** Caching and optimization
- **Security Audits:** Regular security assessments

---

## 📞 Support & Contact

**System Administrator:** JAJR Construction IT Team
**Technical Support:** Available during business hours
**Documentation Version:** 1.0
**Last Updated:** January 30, 2026

---

*This documentation provides a comprehensive overview of the JAJR Attendance Management System. For specific implementation details or troubleshooting, refer to the individual code files and their inline comments.*</content>
<parameter name="filePath">c:\wamp64\www\attendance_web_Copy\PROJECT_DOCUMENTATION.md