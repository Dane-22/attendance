<?php
/**
 * ============================================================================
 * MONITORING DASHBOARD - INTEGRATION GUIDE
 * ============================================================================
 * 
 * This document provides complete integration instructions for the
 * Professional Monitoring Dashboard with Dark Engineering Theme.
 * 
 * FILES PROVIDED:
 * ===============
 * 1. monitoring_dashboard.php
 *    - Standalone complete page with HTML/CSS/PHP
 *    - Can be accessed directly at: /employee/monitoring_dashboard.php
 *    - Includes all styling and layout
 * 
 * 2. monitoring_dashboard_component.php
 *    - Modular component meant to be included in dashboard.php
 *    - No HTML wrapper - only component code
 *    - All CSS is scoped to avoid conflicts
 *    - Perfect for integrating into existing dashboard layout
 * 
 * ============================================================================
 * OPTION 1: USE AS STANDALONE PAGE
 * ============================================================================
 * 
 * The monitoring_dashboard.php file is a complete standalone page.
 * 
 * Access it at: http://your-domain.com/employee/monitoring_dashboard.php
 * 
 * Features:
 * - Professional Dark Engineering theme (Gold #d4af37 / Black #0a0a0a)
 * - Glassmorphism cards with hover effects
 * - Real-time data from database
 * - Responsive design (mobile-friendly)
 * - FontAwesome icons
 * - Smooth animations
 * 
 * ============================================================================
 * OPTION 2: INTEGRATE INTO EXISTING DASHBOARD.PHP
 * ============================================================================
 * 
 * To integrate the monitoring dashboard into your existing dashboard.php:
 * 
 * STEP 1: Open your dashboard.php
 * --------
 * File: /employee/dashboard.php
 * 
 * STEP 2: Add the include statement
 * --------
 * Add this line in the main content area (after the PHP header section):
 * 
 * <?php
 *     // ... your existing dashboard code ...
 *     
 *     // Include the monitoring dashboard component
 *     include __DIR__ . '/monitoring_dashboard_component.php';
 *     
 *     // ... rest of your dashboard code ...
 * ?>
 * 
 * STEP 3: Add CSS to head section (if not already present)
 * --------
 * Ensure your <head> section includes:
 * 
 *   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
 *   <script src="https://cdn.tailwindcss.com"></script>
 * 
 * STEP 4: Optional - Add custom positioning
 * --------
 * Wrap the include in a container if needed:
 * 
 * <div style="padding: 2rem; max-width: 100%;">
 *     <?php include __DIR__ . '/monitoring_dashboard_component.php'; ?>
 * </div>
 * 
 * ============================================================================
 * FEATURES OVERVIEW
 * ============================================================================
 * 
 * 1. REAL-TIME SQL QUERIES
 * -------
 * ✓ Total Employees: SELECT COUNT(*) FROM employees WHERE status = 'Active'
 * ✓ Present Today: SELECT COUNT(*) FROM attendance 
 *                  WHERE attendance_date = CURDATE() AND status = 'Present'
 * ✓ Absent Today: SELECT COUNT(*) FROM attendance 
 *                 WHERE attendance_date = CURDATE() AND status = 'Absent'
 * ✓ Today's Deployment: SELECT branch_name, COUNT(*) FROM attendance 
 *                       WHERE attendance_date = CURDATE() AND status = 'Present'
 *                       GROUP BY branch_name
 * 
 * 2. DASHBOARD CARDS (Glassmorphism)
 * -------
 * ✓ Total Manpower: Shows total active employees
 * ✓ On-Site Today: Shows present employees with attendance percentage
 * ✓ Absent Today: Shows absent employees with absence percentage
 * ✓ Active Branches: Shows number of branches with active deployment
 * 
 * 3. BRANCH HEADCOUNT TABLE
 * -------
 * ✓ Lists all branches with active employees
 * ✓ Shows present/total employees ratio
 * ✓ Gold progress bar showing capacity percentage
 * ✓ Status indicator (Optimal/Normal/Low)
 * ✓ Hover effects for interactivity
 * 
 * 4. RECENT ACTIVITY TICKER
 * -------
 * ✓ Shows last 5 attendance records
 * ✓ Employee name highlighted in gold (#d4af37)
 * ✓ Branch name and status displayed
 * ✓ Relative timestamps (Just now, 5 minutes ago, etc.)
 * ✓ Status icons (Present = green check, Absent = red X)
 * 
 * ============================================================================
 * STYLING & CUSTOMIZATION
 * ============================================================================
 * 
 * DARK ENGINEERING THEME COLORS:
 * 
 *   --gold: #d4af37 (Primary accent color)
 *   --gold-light: #FFD700 (Lighter gold for highlights)
 *   --black: #0a0a0a (Dark background)
 *   --dark-gray: #1a1a1a (Card background)
 *   --medium-gray: #2d2d2d (Secondary background)
 *   --light-gray: #3a3a3a (Tertiary background)
 * 
 * FONTS:
 * - Primary: 'Inter', system-ui, -apple-system, sans-serif
 * - Font Awesome 6.4.0 for icons
 * 
 * TO CUSTOMIZE COLORS:
 * 
 * In monitoring_dashboard_component.php, find the :root section:
 * 
 *   .monitoring-dashboard {
 *       --gold: #d4af37;  <-- Change this
 *       --gold-light: #FFD700;  <-- Change this
 *       --black: #0a0a0a;  <-- Change this
 *   }
 * 
 * ============================================================================
 * RESPONSIVE BEHAVIOR
 * ============================================================================
 * 
 * Desktop (> 768px):
 * - 4-column grid for summary cards
 * - Full table display for branch headcount
 * - Horizontal layout for progress bars
 * 
 * Tablet (480px - 768px):
 * - 2-column grid for summary cards
 * - Responsive table with adjusted font sizes
 * - Vertical progress bar layout
 * 
 * Mobile (< 480px):
 * - 1-column grid for summary cards
 * - Simplified table layout
 * - Stacked progress container
 * 
 * ============================================================================
 * DATABASE REQUIREMENTS
 * ============================================================================
 * 
 * The dashboard requires these tables with the following columns:
 * 
 * TABLE: employees
 * ├── id (INT, PK)
 * ├── first_name (VARCHAR)
 * ├── last_name (VARCHAR)
 * ├── status (VARCHAR) - 'Active' or 'Inactive'
 * └── branch_name (VARCHAR)
 * 
 * TABLE: attendance
 * ├── id (INT, PK)
 * ├── employee_id (INT, FK)
 * ├── status (VARCHAR) - 'Present' or 'Absent'
 * ├── branch_name (VARCHAR)
 * ├── attendance_date (DATE)
 * └── created_at (TIMESTAMP)
 * 
 * ============================================================================
 * PERFORMANCE OPTIMIZATION
 * ============================================================================
 * 
 * The component uses prepared statements for all queries to prevent SQL injection.
 * 
 * For better performance with large datasets, consider:
 * 
 * 1. Add indexes to your attendance table:
 *    CREATE INDEX idx_attendance_date ON attendance(attendance_date);
 *    CREATE INDEX idx_attendance_status ON attendance(status);
 *    CREATE INDEX idx_attendance_branch ON attendance(branch_name);
 * 
 * 2. Add indexes to employees table:
 *    CREATE INDEX idx_employee_status ON employees(status);
 *    CREATE INDEX idx_employee_branch ON employees(branch_name);
 * 
 * 3. Cache the data if needed using PHP:
 *    - Store results in session for 5 minutes
 *    - Use Redis for distributed caching
 * 
 * ============================================================================
 * TROUBLESHOOTING
 * ============================================================================
 * 
 * ISSUE: "Database connection not found"
 * SOLUTION: Ensure $db variable is initialized before including component.
 *           Verify db_connection.php is required at the top of dashboard.php
 * 
 * ISSUE: Styling looks broken
 * SOLUTION: Ensure Font Awesome and Tailwind CSS are loaded:
 *           - Check <link> tags in <head>
 *           - Check for CSS conflicts from other stylesheets
 * 
 * ISSUE: No data displaying
 * SOLUTION: Verify:
 *           - attendance_date column exists (not created_at)
 *           - Employee status values are exactly 'Active'
 *           - Attendance status values are exactly 'Present' or 'Absent'
 *           - Database connection is working (test with test_db.php)
 * 
 * ISSUE: Cards appear empty
 * SOLUTION: Check if data queries are returning results:
 *           - Run the SQL queries directly in phpMyAdmin
 *           - Verify there's data for today (CURDATE())
 * 
 * ============================================================================
 * SECURITY NOTES
 * ============================================================================
 * 
 * ✓ All user input is properly escaped with htmlspecialchars()
 * ✓ All database queries use prepared statements with parameterized bindings
 * ✓ Session check ensures only logged-in users access the data
 * ✓ No sensitive data is exposed in the HTML/CSS
 * 
 * ============================================================================
 * EXAMPLE INTEGRATION CODE
 * ============================================================================
 * 
 * Here's a complete example of how to integrate into dashboard.php:
 * 
 * <?php
 * session_start();
 * if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
 *     header('Location: ../login.php');
 *     exit();
 * }
 * 
 * require('../conn/db_connection.php');
 * 
 * // ... your existing dashboard PHP code ...
 * 
 * ?>
 * <!DOCTYPE html>
 * <html lang="en">
 * <head>
 *     <meta charset="UTF-8">
 *     <meta name="viewport" content="width=device-width, initial-scale=1">
 *     <title>Dashboard - JAJR</title>
 *     <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
 *     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
 *     <script src="https://cdn.tailwindcss.com"></script>
 *     <link rel="stylesheet" href="../assets/css/style.css">
 * </head>
 * <body>
 *     <!-- Your existing sidebar/header -->
 *     <?php include __DIR__ . '/sidebar.php'; ?>
 *     
 *     <main class="main-content">
 *         <!-- Include the monitoring dashboard -->
 *         <?php include __DIR__ . '/monitoring_dashboard_component.php'; ?>
 *         
 *         <!-- Rest of your dashboard content -->
 *     </main>
 * </body>
 * </html>
 * 
 * ============================================================================
 * SUPPORT & UPDATES
 * ============================================================================
 * 
 * For issues or feature requests:
 * - Check the troubleshooting section above
 * - Verify all database tables and columns exist
 * - Test individual SQL queries in phpMyAdmin
 * - Inspect browser console for JavaScript errors
 * 
 * ============================================================================
 */
?>
