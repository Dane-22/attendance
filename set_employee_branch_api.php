<?php
if (file_exists(__DIR__ . '/conn/db_connection.php')) {
    require_once __DIR__ . '/conn/db_connection.php';
} else {
    require_once __DIR__ . '/db_connection.php';
}

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

function employeesHasColumn($db, $columnName) {
    $safe = mysqli_real_escape_string($db, $columnName);
    $sql = "SHOW COLUMNS FROM `employees` LIKE '{$safe}'";
    $result = mysqli_query($db, $sql);
    return $result && mysqli_num_rows($result) > 0;
}

$employeeId = isset($_POST['employee_id']) ? (int)$_POST['employee_id'] : 0;
$branchId = isset($_POST['branch_id']) ? (int)$_POST['branch_id'] : 0;

if ($employeeId <= 0 || $branchId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing or invalid employee_id or branch_id']);
    exit();
}

// Ensure branch exists and is active
$branchSql = "SELECT id FROM branches WHERE id = ? AND is_active = 1 LIMIT 1";
$branchStmt = mysqli_prepare($db, $branchSql);
if (!$branchStmt) {
    echo json_encode(['success' => false, 'message' => 'Database error (prepare failed)']);
    exit();
}
mysqli_stmt_bind_param($branchStmt, 'i', $branchId);
mysqli_stmt_execute($branchStmt);
$branchRes = mysqli_stmt_get_result($branchStmt);
$branchRow = $branchRes ? mysqli_fetch_assoc($branchRes) : null;
mysqli_stmt_close($branchStmt);

if (!$branchRow) {
    echo json_encode(['success' => false, 'message' => 'Branch not found or inactive']);
    exit();
}

$hasUpdatedAt = employeesHasColumn($db, 'updated_at');
$updateSql = $hasUpdatedAt
    ? "UPDATE employees SET branch_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? LIMIT 1"
    : "UPDATE employees SET branch_id = ? WHERE id = ? LIMIT 1";
$updateStmt = mysqli_prepare($db, $updateSql);
if (!$updateStmt) {
    echo json_encode(['success' => false, 'message' => 'Database error (prepare failed)']);
    exit();
}
mysqli_stmt_bind_param($updateStmt, 'ii', $branchId, $employeeId);
$ok = mysqli_stmt_execute($updateStmt);
$affected = mysqli_stmt_affected_rows($updateStmt);
$err = mysqli_error($db);
mysqli_stmt_close($updateStmt);

if (!$ok) {
    echo json_encode(['success' => false, 'message' => 'Failed to update employee branch: ' . $err]);
    exit();
}

if ($affected <= 0) {
    echo json_encode(['success' => false, 'message' => 'Employee not found or branch unchanged']);
    exit();
}

echo json_encode(['success' => true, 'message' => 'Employee branch updated', 'employee_id' => $employeeId, 'branch_id' => $branchId]);

mysqli_close($db);
?>
