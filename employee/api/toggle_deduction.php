<?php
/**
 * AJAX endpoint to toggle employee deduction status
 * Used for quick toggle in employee list
 */

session_start();
require_once __DIR__ . '/../../conn/db_connection.php';

header('Content-Type: application/json');

// Check if user is Super Admin or Admin
$isSuperAdmin = false;
$isAdmin = false;
$sessionPosition = $_SESSION['position'] ?? '';
$sessionRole = $_SESSION['role'] ?? '';
$sessionUserRole = $_SESSION['user_role'] ?? '';

if ($sessionPosition === 'Super Admin' || $sessionRole === 'Super Admin' || $sessionUserRole === 'Super Admin') {
    $isSuperAdmin = true;
    $isAdmin = true;
}
if ($sessionPosition === 'Admin' || $sessionRole === 'Admin' || $sessionUserRole === 'Admin') {
    $isAdmin = true;
}

if (!$isSuperAdmin && !$isAdmin) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Only Super Admin or Admin can modify deduction status']);
    exit;
}

// Get parameters
$employeeId = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
$hasDeduction = isset($_POST['has_deduction']) ? intval($_POST['has_deduction']) : 1;

if ($employeeId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid employee ID']);
    exit;
}

// Update employee deduction status
$stmt = mysqli_prepare($db, "UPDATE employees SET has_deduction = ?, updated_at = NOW() WHERE id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database prepare failed: ' . mysqli_error($db)]);
    exit;
}

mysqli_stmt_bind_param($stmt, 'ii', $hasDeduction, $employeeId);

if (mysqli_stmt_execute($stmt)) {
    $affectedRows = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    
    if ($affectedRows > 0) {
        $statusText = $hasDeduction ? 'WITH deductions' : 'NO deductions';
        echo json_encode([
            'success' => true, 
            'message' => "Employee deduction status updated to: {$statusText}",
            'employee_id' => $employeeId,
            'has_deduction' => $hasDeduction
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Employee not found or no changes made']);
    }
} else {
    $error = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $error]);
}

mysqli_close($db);
?>
