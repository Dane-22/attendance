<?php
// clock_out_api.php
require_once __DIR__ . '/conn/db_connection.php';
header('Content-Type: application/json');

// Input
$employeeId = $_POST['employee_id'] ?? null;
$branchName = $_POST['branch_name'] ?? null;

if (!$employeeId) {
    echo json_encode(['success' => false, 'message' => 'Missing employee_id']);
    exit();
}

// Find today's open attendance row (time_in set, time_out NULL)
$sql = "SELECT id FROM attendance WHERE employee_id = ? AND attendance_date = CURDATE() AND time_in IS NOT NULL AND time_out IS NULL ORDER BY id DESC LIMIT 1";
$stmt = mysqli_prepare($db, $sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error (prepare select)']);
    exit();
}
mysqli_stmt_bind_param($stmt, "i", $employeeId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'No open attendance (clock in) found for today']);
    exit();
}
$attendanceId = intval($row['id']);

// Update time_out
if ($branchName !== null && $branchName !== '') {
    $updateSql = "UPDATE attendance SET time_out = NOW(), branch_name = ? WHERE id = ?";
    $updateStmt = mysqli_prepare($db, $updateSql);
    if (!$updateStmt) {
        echo json_encode(['success' => false, 'message' => 'Database error (prepare update)']);
        exit();
    }
    mysqli_stmt_bind_param($updateStmt, "si", $branchName, $attendanceId);
} else {
    $updateSql = "UPDATE attendance SET time_out = NOW() WHERE id = ?";
    $updateStmt = mysqli_prepare($db, $updateSql);
    if (!$updateStmt) {
        echo json_encode(['success' => false, 'message' => 'Database error (prepare update)']);
        exit();
    }
    mysqli_stmt_bind_param($updateStmt, "i", $attendanceId);
}

if (mysqli_stmt_execute($updateStmt)) {
    $timeOut = date('H:i:s');
    echo json_encode([
        'success' => true,
        'message' => 'Clocked out successfully',
        'time_out' => $timeOut,
        'attendance_id' => $attendanceId
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($db)]);
}
mysqli_stmt_close($updateStmt);
?>
