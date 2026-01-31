# JAJR Attendance Management System - User Manual

## 📖 Welcome to JAJR Attendance System

This user manual provides step-by-step instructions for using the JAJR Construction Company Attendance Management System. Whether you're an employee, supervisor, or administrator, this guide will help you navigate and utilize all system features effectively.

---

## 🚀 Getting Started

### System Access
**URL:** `http://localhost/attendance_web_Copy/`

### First Time Login
1. Open your web browser
2. Navigate to the system URL
3. Click **"Log In"** on the landing page
4. Enter your credentials:
   - **Email:** Your company email address
   - **Password:** Your assigned password

### Default Admin Account
- **Email:** `admin@jajrconstruction.com`
- **Password:** `password`
- **Note:** Change password after first login

---

## 👤 User Roles & Permissions

### Regular Employee
Access to personal dashboard, clock in/out, and basic employee directory.

### Supervisor/Admin
All employee permissions plus attendance management, employee administration, and reporting.

### Super Admin
Full system access including system logs and advanced configuration.

---

## 🏠 Employee Dashboard

### Overview
The dashboard displays your daily attendance status, company statistics, and quick actions.

### Key Dashboard Elements

#### Today's Status Card
- **Clock In/Out Status:** Shows current attendance state
- **Today's Hours:** Total hours worked today
- **Attendance Streak:** Consecutive attendance days

#### Quick Actions
- **Clock In/Out Button:** Primary time-tracking action
- **View Attendance:** Access your attendance history
- **Update Profile:** Modify personal information

#### Company Statistics
- **Total Employees:** Company headcount
- **Present Today:** Current attendance count
- **Active Branches:** Number of operational sites

---

## ⏰ Daily Time Tracking

### Clocking In
1. **From Dashboard:** Click the large **"Clock In"** button
2. **From Sidebar:** Navigate to **"Site Attendance"**
3. **Confirmation:** System confirms successful clock-in with timestamp

### Clocking Out
1. **From Dashboard:** Click **"Clock Out"** when button appears
2. **Automatic Calculation:** System calculates total hours worked
3. **Confirmation:** Displays total time worked for the day

### Manual Attendance (Admin Only)
1. Go to **"Site Attendance"** in sidebar
2. Select date using calendar picker
3. Choose branch filter (optional)
4. For each employee:
   - Click **"Present"** or **"Absent"** radio button
   - System saves automatically

---

## 👥 Employee Management

### Viewing Employee Directory
1. Click **"Employees"** in sidebar
2. Choose view mode:
   - **Grid View:** Card-based layout (default)
   - **List View:** Compact list format
   - **Details View:** Expanded information

### Employee Information Display
Each employee card shows:
- **Profile Picture:** Employee photo (if uploaded)
- **Name:** Full name with employee code
- **Position:** Job title
- **Email:** Contact information
- **Branch:** Assigned location

### Searching Employees
- Use the **search bar** at the top
- Search by name, employee code, or email
- Results filter in real-time

---

## 🏢 Branch Management (Admin Only)

### Selecting Work Branch
1. Click **"Select Deployment Branch"** section
2. View available branches in grid format
3. Click on desired branch card
4. System loads employees for that branch

### Adding New Branches
1. In branch selection area, click **yellow "Add Branch"** button
2. Enter branch name (2-255 characters)
3. Click **"Add Branch"**
4. New branch appears immediately

### Deleting Branches
1. Hover over branch card
2. Click red **"X"** button that appears
3. Confirm deletion in popup dialog
4. **Note:** Cannot delete branches with assigned employees

---

## 💰 Payroll & Billing

### Viewing Your Payroll
1. Click **"Billing"** in sidebar
2. View your compensation details:
   - **Daily Rate:** Your hourly/daily pay rate
   - **Monthly Salary:** Calculated monthly earnings
   - **Weekly Earnings:** Current week calculation

### Admin Payroll Management
1. Access **"Billing"** section
2. View all employees' compensation
3. Filter by date range:
   - **Daily:** Today's earnings
   - **Weekly:** Current week (Mon-Sat)
   - **Monthly:** Current month
4. Export or print reports as needed

---

## 📊 Monitoring Dashboard

### Real-Time Statistics
- **Total Employees:** Active company headcount
- **Present Today:** Current attendance numbers
- **Branch Deployment:** Employees per location
- **Attendance Trends:** Historical performance

### Branch Distribution
- **Active Branches:** Number of operational sites
- **Employees per Branch:** Headcount distribution
- **Today's Deployment:** Current location assignments

---

## 🤖 AI Assistant

### Accessing AI Help
1. Look for **chat widget** in bottom-right corner
2. Click to open AI assistant interface
3. Type your question or request help

### AI Capabilities
- **General Questions:** Ask about system features
- **Attendance Help:** Get guidance on time tracking
- **Troubleshooting:** Resolve common issues
- **Policy Information:** Learn about company procedures

---

## ⚙️ Profile Management

### Updating Your Profile
1. Click **"Settings"** in sidebar
2. Update personal information:
   - **Name:** First and last name
   - **Email:** Contact email
   - **Position:** Job title (admin only)
3. Upload profile picture (optional)

### Profile Picture Upload
1. In profile settings, find **"Profile Image"** section
2. Click **"Choose File"** button
3. Select image file (JPG, PNG, GIF, WebP, max 5MB)
4. Click **"Save"** to upload
5. Image appears on your employee card

---

## 📋 Reports & Analytics

### Attendance Reports (Admin)
1. Click **"Reports"** in sidebar
2. Select date range or specific dates
3. Filter by branch or employee
4. View detailed attendance records
5. Export data if needed

### Personal Attendance History
1. From dashboard, click **"View Attendance"**
2. See your complete attendance record
3. Filter by date range
4. View clock in/out times and total hours

---

## 📁 Document Management

### Accessing Documents
1. Click **"Documents"** in sidebar
2. View available company documents
3. Download files as needed
4. Upload new documents (admin only)

### Uploading Documents (Admin)
1. In Documents section, click **"Upload"** button
2. Select file from your computer
3. Add description or category
4. Click **"Upload"** to save

---

## 📊 Activity Logs (Admin Only)

### Viewing System Logs
1. Navigate to **Admin → Logs**
2. View all system activities
3. Filter by:
   - **User:** Search by employee name or code
   - **Action:** Filter by activity type
   - **Date:** View logs by time period

### Log Information
Each log entry shows:
- **User:** Who performed the action
- **Action:** What was done (e.g., "Logged In", "Marked Attendance")
- **Details:** Additional context
- **IP Address:** Where the action occurred
- **Timestamp:** When it happened

---

## 🔧 System Settings (Admin)

### General Settings
1. Click **"Settings"** in sidebar
2. Configure system preferences:
   - **Working Hours:** Set standard business hours
   - **Attendance Rules:** Configure attendance policies
   - **Notification Settings:** Email and alert preferences

### User Management (Admin)
1. Access **"Employees"** section
2. Add new employees:
   - Enter employee code, name, email
   - Assign branch and position
   - Set daily rate
3. Edit existing employees
4. Deactivate/reactivate accounts

---

## 🚨 Troubleshooting

### Common Issues & Solutions

#### Can't Clock In/Out
- **Check:** Ensure you're logged in
- **Check:** Verify internet connection
- **Solution:** Refresh page and try again
- **Contact:** IT support if issue persists

#### Wrong Attendance Marked
- **Action:** Contact your supervisor
- **Admin:** Go to "Site Attendance" and correct manually
- **Note:** Changes are logged for audit purposes

#### Forgot Password
- **Action:** Contact HR or IT department
- **Admin:** Reset password in employee management
- **Security:** Passwords are encrypted and secure

#### Profile Picture Won't Upload
- **Check:** File size (must be under 5MB)
- **Check:** File type (JPG, PNG, GIF, WebP only)
- **Solution:** Resize image or convert format

#### Branch Not Showing
- **Check:** Confirm branch was created properly
- **Admin:** Verify branch status in branch management
- **Refresh:** Reload page to update branch list

#### System Running Slow
- **Check:** Internet connection speed
- **Action:** Clear browser cache
- **Contact:** IT support for performance issues

---

## 📞 Getting Help

### Support Resources

#### AI Assistant
- Available 24/7 in chat widget
- Provides instant answers to common questions
- Guides you through system features

#### Supervisor/Manager
- Contact for attendance corrections
- Help with branch assignments
- Approval for special requests

#### IT Support
- Technical issues and system errors
- Password resets and account issues
- Feature requests and bug reports

#### HR Department
- Employee onboarding questions
- Policy and procedure clarification
- Payroll and compensation inquiries

### Contact Information
- **IT Support:** it@jajrconstruction.com
- **HR Department:** hr@jajrconstruction.com
- **Emergency:** Call main office during business hours

---

## 📱 Mobile Usage

### Mobile Browser Access
- System works on all modern mobile browsers
- Optimized for phones and tablets
- Touch-friendly interface

### Mobile Best Practices
- Use WiFi when possible for better performance
- Close other browser tabs to save memory
- Update browser regularly for best experience

---

## 🔒 Security Best Practices

### Password Security
- Use strong, unique passwords
- Change default passwords immediately
- Never share login credentials
- Log out when using shared computers

### Account Protection
- Report suspicious activity immediately
- Don't leave sessions open unattended
- Use company-approved devices when possible
- Be cautious with public WiFi

### Data Handling
- Don't share sensitive employee information
- Use system features for official communications
- Report security concerns to IT immediately

---

## 📈 Performance Tips

### Daily Efficiency
- Clock in/out promptly at start/end of day
- Update profile information when it changes
- Review attendance regularly for accuracy
- Use search features to find information quickly

### Admin Productivity
- Set up branches before assigning employees
- Review attendance daily for corrections
- Use filters and search to manage large datasets
- Export reports regularly for record-keeping

---

## 🔄 System Updates

### Staying Current
- System updates happen automatically
- New features announced via email or dashboard
- Training provided for major changes
- User feedback helps improve the system

### Version Information
- Current Version: 1.0
- Last Updated: January 30, 2026
- Next Major Update: Planned for Q2 2026

---

## 📋 Quick Reference

### Keyboard Shortcuts
- **Ctrl+F:** Search within pages
- **F5:** Refresh page
- **Ctrl+Enter:** Submit forms (where applicable)

### Important URLs
- **Main System:** `/`
- **Employee Dashboard:** `/employee/dashboard.php`
- **Admin Logs:** `/admin/logs.php`
- **API Endpoints:** `/employee/api/`

### File Size Limits
- **Profile Pictures:** 5MB maximum
- **Document Uploads:** 10MB maximum
- **Report Exports:** No size limit

---

## 🎯 Success Metrics

### For Employees
- **Perfect Attendance:** Consistent daily clock in/out
- **On-time Arrival:** Clock in within 15 minutes of start time
- **Profile Completion:** Updated photo and information

### For Supervisors
- **Team Coverage:** 95%+ attendance rate
- **Accurate Records:** Minimal attendance corrections needed
- **Timely Reporting:** Weekly/monthly reports completed on schedule

### For Administrators
- **System Uptime:** 99.9% availability
- **User Adoption:** 100% employee registration
- **Data Accuracy:** Complete and accurate attendance records

---

## 📞 Emergency Procedures

### System Outage
1. **Stay Calm:** System outages are rare but possible
2. **Manual Tracking:** Use paper timesheets if needed
3. **Contact IT:** Report outage immediately
4. **Alternative Access:** Use mobile devices if available

### Data Loss Concerns
1. **Don't Panic:** System has automatic backups
2. **Report Issue:** Contact IT support immediately
3. **Preserve Evidence:** Note what you were doing when issue occurred
4. **Follow Recovery:** IT will guide restoration process

---

## 🙏 Acknowledgments

Thank you for using the JAJR Attendance Management System. Your participation ensures accurate time tracking, fair compensation, and efficient operations.

**Remember:** Accurate attendance tracking benefits everyone - it ensures fair pay, proper project staffing, and helps our company succeed.

---

*For technical documentation, see PROJECT_DOCUMENTATION.md*

**Last Updated:** January 30, 2026
**Version:** 1.0
**Contact:** support@jajrconstruction.com</content>
<parameter name="filePath">c:\wamp64\www\attendance_web_Copy\USER_MANUAL.md