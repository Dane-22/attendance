<?php
// get_employee_data.php
require_once __DIR__ . '/../conn/db_connection.php';
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

$employeeId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($employeeId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid employee ID']);
    exit;
}

if (!isset($db) || !$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$sql = "SELECT * FROM employees WHERE id = ?";
$stmt = mysqli_prepare($db, $sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . mysqli_error($db)]);
    exit;
}

mysqli_stmt_bind_param($stmt, 'i', $employeeId);

if (!mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => false, 'message' => 'Execute failed: ' . mysqli_stmt_error($stmt)]);
    exit;
}

$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    echo json_encode([
        'success' => true,
        'employee' => [
            'id' => $row['id'],
            'employee_code' => $row['employee_code'],
            'first_name' => $row['first_name'],
            'middle_name' => $row['middle_name'],
            'last_name' => $row['last_name'],
            'email' => $row['email'],
            'position' => $row['position'],
            'status' => $row['status'],
            'profile_image' => $row['profile_image'] ?? '',
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Employee not found']);
}

mysqli_stmt_close($stmt);
mysqli_close($db);
?>