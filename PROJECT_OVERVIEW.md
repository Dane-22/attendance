# JAJR Company - Complete Project Overview

## 🏢 Company Information
**Company**: JAJR Company (Engineering/Construction)  
**Owner**: Arzadon  
**Industry**: Engineering & Construction  
**Timezone**: Asia/Manila (UTC+08:00)  
**Project Name**: JAJR Attendance Management System  

---

## 📋 Executive Summary

The JAJR Attendance Management System is a comprehensive web-based application designed to streamline employee management, attendance tracking, payroll processing, and administrative operations for a multi-branch engineering company. The system supports real-time QR-based attendance, geolocation tracking, cash advance management, overtime processing, and comprehensive reporting capabilities.

---

## 🏗️ System Architecture

### Technology Stack
- **Backend**: PHP 8.3+
- **Database**: MySQL 8.4+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **CSS Frameworks**: Tailwind CSS 3.4+, Bootstrap 5.3
- **UI Components**: Custom components with Font Awesome 6.4
- **Build Tools**: Node.js, npm, Tailwind CSS CLI
- **Authentication**: Session-based with dual password hashing
- **API Integration**: RESTful APIs with procurement system integration

### External Dependencies
- **Minishlink/Web-Push**: Web push notifications
- **PHPOffice/PhpSpreadsheet**: Excel export functionality
- **Procurement API**: External authentication integration (xandree.com)

---

## 📁 Project Structure

```
c:\wamp64\www\main\
├── 📁 api/                          # API endpoints
├── 📁 assets/                       # Static assets
│   ├── 📁 css/                     # Stylesheets
│   ├── 📁 js/                      # JavaScript files
│   └── 📁 img/                     # Images
├── 📁 conn/                        # Database connections
├── 📁 dbschema/                    # Database schemas
├── 📁 docs/                        # Documentation
├── 📁 employee/                    # Employee management system
│   ├── 📁 api/                     # Employee APIs
│   ├── 📁 cron/                    # Scheduled tasks
│   ├── 📁 css/                     # Employee-specific styles
│   ├── 📁 docs/                    # Employee documentation
│   ├── 📁 function/                # Helper functions
│   ├── 📁 js/                      # Employee JavaScript
│   └── 📄 *.php                    # Employee management pages
├── 📁 include/                     # Shared components
├── 📁 vendor/                      # Composer dependencies
├── 📄 *.php                        # Root-level PHP files
├── 📄 *.md                         # Documentation files
└── 📄 *.json                       # Configuration files
```

---

## 🗄️ Database Schema

### Core Tables

#### 1. **employees**
- Employee information and credentials
- Position-based role management
- Branch assignments
- Salary rates (daily/monthly)

#### 2. **attendance**
- Daily attendance tracking
- Time-in/time-out records
- Overtime tracking
- Geolocation data
- Auto-absent functionality

#### 3. **branches**
- Multi-branch management
- Branch locations and settings
- Active/inactive status

#### 4. **cash_advance**
- Cash advance requests
- Approval workflow
- Payment tracking

#### 5. **overtime_requests**
- Overtime request management
- Approval process
- Payment integration

#### 6. **activity_logs**
- System activity tracking
- User action logging
- IP address recording

#### 7. **notifications**
- Push notification management
- User subscriptions
- Message delivery tracking

---

## 👥 User Roles & Permissions

### 1. **Super Admin**
- Full system access
- User management
- System configuration
- Branch management
- All administrative functions

### 2. **Admin**
- Employee management
- Attendance monitoring
- Report generation
- Cash advance approval
- Overtime approval

### 3. **Engineer**
- Attendance tracking
- Report viewing
- Request submissions
- Profile management

### 4. **Worker**
- Basic attendance tracking
- Profile management
- Request submissions
- Limited reporting

---

## 🚀 Core Features

### 1. **Attendance Management**
- **QR Code Scanner**: Real-time time-in/time-out
- **Geolocation Tracking**: GPS-based location verification
- **Auto-absent Detection**: Automatic absence marking
- **Time Tracking**: Precise hours calculation
- **Overtime Management**: OT request and approval

### 2. **Employee Management**
- **Profile Management**: Complete employee records
- **Branch Assignment**: Multi-branch support
- **Position Tracking**: Role-based access
- **Status Management**: Active/inactive employees

### 3. **Financial Management**
- **Cash Advances**: Request and approval workflow
- **Payroll Processing**: Automated calculations
- **Billing System**: Client billing management
- **Deduction Tracking**: Various deduction types

### 4. **Reporting & Analytics**
- **Daily Reports**: Attendance summaries
- **Weekly Reports**: Comprehensive weekly data
- **Monthly Reports**: Payroll and analytics
- **Export Functionality**: Excel/CSV exports
- **Audit Reports**: Compliance tracking

### 5. **Notifications**
- **Web Push Notifications**: Real-time alerts
- **Email Notifications**: System alerts
- **SMS Integration**: Text message alerts
- **In-app Notifications**: Dashboard alerts

### 6. **Document Management**
- **File Uploads**: Document storage
- **Profile Pictures**: Employee photos
- **Report Generation**: Dynamic document creation
- **Digital Signatures**: Approval workflows

---

## 🔐 Security Features

### Authentication
- **Dual Password Hashing**: MD5 + password_hash
- **Session Management**: Secure session handling
- **API Key Management**: Secure API access
- **External API Integration**: Procurement system auth

### Data Protection
- **Input Validation**: XSS prevention
- **SQL Injection Protection**: Prepared statements
- **CSRF Protection**: Token validation
- **IP Logging**: Security audit trail

### Access Control
- **Role-based Access**: Permission levels
- **Branch Restrictions**: Location-based access
- **API Rate Limiting**: Request throttling
- **Activity Logging**: Comprehensive audit trail

---

## 📱 Mobile & Responsive Features

### Responsive Design
- **Mobile-first Approach**: Tailwind CSS responsive utilities
- **Touch-friendly UI**: Optimized for mobile devices
- **Progressive Web App**: PWA capabilities
- **Offline Support**: Service worker implementation

### QR Code Integration
- **Camera Access**: Mobile camera scanning
- **Time Restrictions**: Scheduled scanner availability
- **Confirmation Dialogs**: User confirmation prompts
- **Real-time Processing**: Instant attendance updates

---

## 🔧 API Endpoints

### Authentication APIs
- `login_api.php` - User authentication
- `login_api_simple.php` - Simplified login
- `change-password-api.php` - Password management

### Attendance APIs
- `time_in_api.php` - Time-in processing
- `time_out_api.php` - Time-out processing
- `qr_clock_api.php` - QR scanner integration
- `submit_attendance_api.php` - Attendance submission

### Employee Management APIs
- `get_employee_data.php` - Employee data retrieval
- `update_profile_api.php` - Profile updates
- `set_employee_branch_api.php` - Branch assignments

### Financial APIs
- `approve_cash_advance.php` - Cash advance approval
- `approve_overtime.php` - Overtime approval
- `get_billing_data.php` - Billing information

### Notification APIs
- `send_branches.php` - Branch notifications
- Web push subscription management

---

## 📊 Key Modules

### 1. **Dashboard System**
- **Admin Dashboard**: System overview
- **Employee Dashboard**: Personal dashboard
- **Engineering Dashboard**: Specialized views
- **Monitoring Dashboard**: Real-time tracking

### 2. **Attendance Module**
- **Daily Attendance**: Time tracking
- **Attendance History**: Records viewing
- **Branch Statistics**: Location-based data
- **Absentee Management**: Absence tracking

### 3. **Payroll Module**
- **Payroll Processing**: Salary calculations
- **Deduction Management**: Various deductions
- **Payment Tracking**: Status monitoring
- **Report Generation**: Payroll reports
- **Multi-Branch Payroll**: Automatic branch transfer handling with split-day calculations
- **Auto-Transfer on Clock-In**: Workers can clock in at any branch; system automatically updates assignment and splits payroll costs proportionally

### 4. **Cash Advance Module**
- **Request System**: Advance requests
- **Approval Workflow**: Multi-level approval
- **Payment Processing**: Disbursement tracking
- **History Management**: Request history

### 5. **Overtime Module**
- **OT Requests**: Overtime applications
- **Approval Process**: Management approval
- **Calculation Engine**: OT computation
- **Integration**: Payroll integration

---

## 🎨 UI/UX Features

### Design System
- **Modern UI**: Clean, professional interface
- **Dark Mode**: Dark theme support
- **Responsive Layout**: Mobile-optimized
- **Accessibility**: WCAG compliance

### Interactive Elements
- **Real-time Updates**: Live data refresh
- **Modal Dialogs**: User-friendly interactions
- **Loading States**: Progress indicators
- **Error Handling**: User-friendly messages

### Navigation
- **Sidebar Navigation**: Collapsible menu
- **Breadcrumb Navigation**: Path tracking
- **Quick Actions**: Shortcut buttons
- **Search Functionality**: Global search

---

## 📈 Performance Features

### Optimization
- **Database Indexing**: Query optimization
- **Caching Strategy**: Data caching
- **Lazy Loading**: Progressive content loading
- **Minification**: Asset optimization

### Monitoring
- **Activity Logging**: Performance tracking
- **Error Logging**: Issue tracking
- **API Debugging**: Request monitoring
- **Database Monitoring**: Query performance

---

## 🔧 Configuration

### Environment Setup
- **.env Configuration**: Environment variables
- **Database Settings**: Connection parameters
- **API Keys**: External service keys
- **Timezone Settings**: Asia/Manila

### Build Process
- **Tailwind CSS**: CSS compilation
- **Asset Minification**: File optimization
- **Development Mode**: Watch functionality
- **Production Build**: Optimized assets

---

## 📚 Documentation Structure

### Technical Documentation
- `PROJECT_DOCUMENTATION.md` - Complete system docs
- `API_DOCUMENTATION.md` - API reference
- `SECURITY_REVIEW.md` - Security analysis
- `CSS_DOCUMENTATION.md` - Styling guide

### Feature Documentation
- `GEOLOCATION_DOCUMENTATION.md` - Location tracking
- `NOTIFICATION_SYSTEM_DOCUMENTATION.md` - Notifications
- `PAYROLL_HOW_IT_WORKS.md` - Payroll process
- `TIME_TRACKING_FIX_REPORT.md` - Time tracking fixes
- `payroll_and_branch_transfers.md` - Branch transfer and multi-branch payroll calculations

### Development Documentation
- `worklog.md` - Development log
- `docs/` - Additional documentation
- `employee/docs/` - Employee module docs

---

## 🧪 Testing & QA

### Geolocation Phase 2 Test Suite

Comprehensive automated testing suite for geofence validation:

#### Test Files
- `test/geo_mock.js` - Browser GPS mocking utility
- `test/test_geofence_logic.php` - Backend PHP unit tests
- `test/check_logs.sql` - Database verification script
- `test/QA_TEST_SUITE.md` - Testing documentation

#### Test Coverage
| Test Case | Scenario | Expected Result |
|-----------|----------|-----------------|
| Case 1 | Inside Geofence (Worker) | `success` |
| Case 2 | Outside Geofence (Worker) | `block` |
| Case 3 | Outside Geofence (Manager) | `allow_override` |
| Case 4 | Spoofed Timestamp (2hr old) | `security_block` |
| Case 5 | Low Accuracy (>500m) | `accuracy_block` |

#### Running Tests
```bash
# Backend tests
php test/test_geofence_logic.php

# With JSON output
php test/test_geofence_logic.php?format=json

# Database verification
# Run test/check_logs.sql in PHPMyAdmin
```

---

## 🚀 Installation & Deployment

### Requirements
- **PHP**: 8.3 or higher
- **MySQL**: 8.4 or higher
- **Apache**: Web server with mod_rewrite
- **Node.js**: For CSS compilation
- **Composer**: PHP dependency management

### Setup Steps
1. **Database Setup**: Import schema files
2. **Environment Configuration**: Set up .env
3. **Dependencies**: Install PHP and Node packages
4. **Asset Compilation**: Build CSS files
5. **Permissions**: Set file permissions
6. **Configuration**: Update system settings

---

## 🔮 Future Enhancements

### Planned Features
- **Mobile App**: Native mobile application
- **Biometric Integration**: Fingerprint/facial recognition
- **Advanced Analytics**: AI-powered insights
- **Integration Hub**: Third-party system connections
- **Cloud Deployment**: Cloud infrastructure migration

### Scalability
- **Load Balancing**: Multi-server support
- **Database Sharding**: Performance scaling
- **CDN Integration**: Asset delivery optimization
- **Microservices**: Modular architecture

---

## 📞 Support & Maintenance

### Monitoring
- **System Health**: Performance monitoring
- **Error Tracking**: Automated error reporting
- **Backup Systems**: Data protection
- **Update Management**: Patch management

### Documentation Updates
- **Version Control**: Git-based tracking
- **Change Logs**: Update documentation
- **API Versioning**: Backward compatibility
- **User Guides**: End-user documentation

---

## 📊 Project Statistics

### Code Metrics
- **PHP Files**: 150+ application files
- **Documentation**: 20+ markdown files
- **API Endpoints**: 30+ REST APIs
- **Database Tables**: 15+ core tables
- **Dependencies**: 10+ external packages

### Feature Coverage
- **User Management**: ✅ Complete
- **Attendance Tracking**: ✅ Complete
- **Payroll Processing**: ✅ Complete
- **Reporting**: ✅ Complete
- **Notifications**: ✅ Complete
- **Mobile Support**: ✅ Complete

---

## 🏁 Conclusion

The JAJR Attendance Management System represents a comprehensive, enterprise-grade solution for employee management and attendance tracking. With its robust architecture, extensive feature set, and focus on security and usability, the system provides a solid foundation for efficient workforce management in a multi-branch engineering environment.

The system's modular design allows for easy expansion and customization, while its comprehensive documentation ensures maintainability and scalability for future development.

---

*Last Updated: April 6, 2026*  
*Version: 1.0.0*  
*Status: Production Ready*
