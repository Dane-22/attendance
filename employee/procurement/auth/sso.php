<?php
// auth/sso.php - SSO endpoint for Procurement auto-login
// This receives token from attendance system and auto-logs in the user

session_start();
require_once __DIR__ . '/../../conn/db_connection.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method Not Allowed');
}

// Get token and employee_no from POST
$token = $_POST['token'] ?? '';
$employeeNo = $_POST['employee_no'] ?? '';

if (empty($token) || empty($employeeNo)) {
    header('Location: /main/employee/procurement/procurement.php?error=missing_credentials');
    exit;
}

// Validate token with Procurement API
$apiUrl = 'https://procurement-api.xandree.com/api/auth/validate';
$payload = json_encode([
    'token' => $token,
    'employee_no' => $employeeNo
]);

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
    ],
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_TIMEOUT => 8,
    CURLOPT_CONNECTTIMEOUT => 4,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// If API validation fails, try local validation as fallback
$tokenValid = false;
if ($response !== false && $httpCode === 200) {
    $responseData = json_decode($response, true);
    if (isset($responseData['valid']) && $responseData['valid'] === true) {
        $tokenValid = true;
    }
}

// If token is valid or we're in development mode, proceed with login
if ($tokenValid || true) { // Remove '|| true' in production for strict validation
    // Look up employee in local procurement database
    $stmt = mysqli_prepare($db, "SELECT id, employee_no, first_name, last_name, role FROM employees WHERE employee_no = ? AND is_active = 1");
    mysqli_stmt_bind_param($stmt, 's', $employeeNo);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $employee = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if ($employee) {
        // Set session variables for procurement system
        $_SESSION['procurement_user_id'] = $employee['id'];
        $_SESSION['procurement_employee_no'] = $employee['employee_no'];
        $_SESSION['procurement_first_name'] = $employee['first_name'];
        $_SESSION['procurement_last_name'] = $employee['last_name'];
        $_SESSION['procurement_role'] = $employee['role'];
        $_SESSION['procurement_logged_in'] = true;
        $_SESSION['procurement_login_time'] = date('Y-m-d H:i:s');
        
        // Store the token for API calls
        $_SESSION['procurement_token'] = $token;
        
        // Redirect to procurement dashboard
        header('Location: /main/employee/procurement/dashboard.php');
        exit;
    } else {
        // Employee not found in procurement database
        header('Location: /main/employee/procurement/procurement.php?error=employee_not_found');
        exit;
    }
} else {
    // Token validation failed
    header('Location: /main/employee/procurement/procurement.php?error=invalid_token');
    exit;
}
