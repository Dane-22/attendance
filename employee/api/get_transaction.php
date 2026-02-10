<?php
// api/get_transaction.php - Fetch single transaction detail
require_once __DIR__ . '/../../conn/db_connection.php';
require_once __DIR__ . '/../../functions.php';
session_start();

header('Content-Type: application/json');

// Check if user is logged in
if (empty($_SESSION['logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$isAdmin = in_array($_SESSION['position'] ?? '', ['Admin', 'Super Admin']);
$currentEmployeeId = $_SESSION['employee_id'] ?? null;

$transId = intval($_GET['id'] ?? 0);

if ($transId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid transaction ID']);
    exit;
}

// Get transaction with employee info
$query = "SELECT ca.*, e.first_name, e.last_name, e.employee_code 
          FROM cash_advances ca 
          JOIN employees e ON ca.employee_id = e.id 
          WHERE ca.id = ?";
$stmt = mysqli_prepare($db, $query);
mysqli_stmt_bind_param($stmt, 'i', $transId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$transaction = mysqli_fetch_assoc($result);

if (!$transaction) {
    echo json_encode(['success' => false, 'message' => 'Transaction not found']);
    exit;
}

// Non-admins can only view their own transactions
if (!$isAdmin && $transaction['employee_id'] != $currentEmployeeId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Calculate running balance up to this transaction
$empId = $transaction['employee_id'];
$balanceQuery = "SELECT * FROM cash_advances 
                 WHERE employee_id = ? AND status IN ('Approved', 'Paid')
                 ORDER BY request_date ASC";
$balanceStmt = mysqli_prepare($db, $balanceQuery);
mysqli_stmt_bind_param($balanceStmt, 'i', $empId);
mysqli_stmt_execute($balanceStmt);
$balanceResult = mysqli_stmt_get_result($balanceStmt);

$runningBalance = 0;
while ($row = mysqli_fetch_assoc($balanceResult)) {
    if ($row['particular'] === 'Payment') {
        $runningBalance -= $row['amount'];
    } else {
        $runningBalance += $row['amount'];
    }
    
    // Stop when we reach the current transaction
    if ($row['id'] == $transId) {
        break;
    }
}

$transaction['running_balance'] = $runningBalance;

echo json_encode([
    'success' => true,
    'transaction' => $transaction
]);
