<?php
// get_employee_data.php
require_once __DIR__ . '/../conn/db_connection.php';
header('Content-Type: application/json');

$employeeId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($employeeId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid employee ID']);
    exit;
}

$sql = "SELECT * FROM employees WHERE id = ?";
$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, 'i', $employeeId);
mysqli_stmt_execute($stmt);
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
            'phone' => $row['phone'] ?? '',
            'position' => $row['position'],
            'department' => $row['department'] ?? '',
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