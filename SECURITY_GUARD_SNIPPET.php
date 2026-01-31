<?php
/**
 * Security Guard for Admin-Only Pages
 * 
 * Place this code at the TOP of dashboard.php and billing.php
 * 
 * This checks if the user is an 'Employee' and redirects them to select_employee.php
 * Allows 'Admin' and 'Super Admin' roles to access the page
 */

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Get user role from session
$userRole = isset($_SESSION['position']) ? $_SESSION['position'] : 'Employee';

// If user is an Employee, redirect them to select_employee.php
if ($userRole === 'Employee') {
    header("Location: select_employee.php");
    exit;
}

// User is Admin or Super Admin - allow access to continue
// The rest of the page code will execute
?>
