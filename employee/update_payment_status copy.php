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

function fail($message, $statusCode = 400) {
    http_response_code($statusCode);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

$employeeId = isset($_REQUEST['employee_id']) ? (int)$_REQUEST['employee_id'] : 0;
$paymentStatus = isset($_REQUEST['payment_status']) ? trim($_REQUEST['payment_status']) : 'Not Paid';
$year = isset($_REQUEST['year']) ? (int)$_REQUEST['year'] : 0;
$month = isset($_REQUEST['month']) ? (int)$_REQUEST['month'] : 0;
$week = isset($_REQUEST['week']) ? (int)$_REQUEST['week'] : 1;
$viewType = isset($_REQUEST['view_type']) ? trim($_REQUEST['view_type']) : 'weekly';
$viewType = $viewType === 'monthly' ? 'monthly' : 'weekly';
$paymentStatus = $paymentStatus === 'Paid' ? 'Paid' : 'Not Paid';

if ($employeeId <= 0 || $year <= 0 || $month < 1 || $month > 12 || $week < 1 || $week > 5) {
    fail('Missing or invalid payroll payment status parameters.');
}

$stmt = mysqli_prepare(
    $db,
    "UPDATE weekly_payroll_reports
     SET payment_status = ?
     WHERE employee_id = ? AND report_year = ? AND report_month = ? AND week_number = ? AND view_type = ?"
);

if (!$stmt) {
    fail('Failed to prepare payment status update.', 500);
}

mysqli_stmt_bind_param($stmt, 'siiiis', $paymentStatus, $employeeId, $year, $month, $week, $viewType);
mysqli_stmt_execute($stmt);
$affectedRows = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);
mysqli_close($db);

echo json_encode([
    'success' => $affectedRows >= 0,
    'message' => $affectedRows > 0 ? 'Payment status updated.' : 'No payroll row matched the requested period.',
]);
?>
