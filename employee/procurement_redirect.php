<?php
// procurement_redirect.php - Redirects to Procurement website
// Note: The remote procurement.xandree.com does not support SSO/auto-login
// Users will need to log in manually with their credentials

session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

// Simply redirect to Procurement website
// Users will need to log in manually
header('Location: https://procurement.xandree.com');
exit;
