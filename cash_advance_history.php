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
$empStmt = $conn->prepare($empQuery);
$empStmt->bind_param("i", $employeeId);
$empStmt->execute();
$empResult = $empStmt->get_result();
$employee = $empResult->fetch_assoc();

if (!$employee) {
    echo json_encode(['success' => false, 'message' => 'Employee not found']);
    exit;
}

// Get transactions
$transQuery = "SELECT * FROM cash_advances 
               WHERE employee_id = ? 
               ORDER BY request_date ASC";
$transStmt = $conn->prepare($transQuery);
$transStmt->bind_param("i", $employeeId);
$transStmt->execute();
$transResult = $transStmt->get_result();

$transactions = [];
$balance = 0;

while ($row = $transResult->fetch_assoc()) {
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
