<?php
// cash_advance_history.php - Fetch employee cash advance history
require_once __DIR__ . '/conn/db_connection.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$employeeId = intval($_GET['emp_id'] ?? 0);

if ($employeeId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid employee ID']);
    exit;
}

// Get employee info
$empQuery = "SELECT id, first_name, last_name, employee_code, position FROM employees WHERE id = ?";
$empStmt = mysqli_prepare($db, $empQuery);
mysqli_stmt_bind_param($empStmt, 'i', $employeeId);
mysqli_stmt_execute($empStmt);
$empResult = mysqli_stmt_get_result($empStmt);
$employee = mysqli_fetch_assoc($empResult);

if (!$employee) {
    echo json_encode(['success' => false, 'message' => 'Employee not found']);
    exit;
}

// Get transactions
$transQuery = "SELECT * FROM cash_advances 
               WHERE employee_id = ? 
               ORDER BY request_date ASC";
$transStmt = mysqli_prepare($db, $transQuery);
mysqli_stmt_bind_param($transStmt, 'i', $employeeId);
mysqli_stmt_execute($transStmt);
$transResult = mysqli_stmt_get_result($transStmt);

$transactions = [];
$balance = 0;

while ($row = mysqli_fetch_assoc($transResult)) {
    if ($row['particular'] === 'Payment') {
        $balance -= $row['amount'];
    } else {
        $balance += $row['amount'];
    }
    $row['running_balance'] = $balance;
    $transactions[] = $row;
}

// Reverse to show newest first
$transactions = array_reverse($transactions);

echo json_encode([
    'success' => true,
    'employee' => $employee,
    'transactions' => $transactions,
    'balance' => $balance
]);
?>
