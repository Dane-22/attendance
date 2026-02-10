<?php
// api/get_all_transactions.php - Fetch all transactions with running balances
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
$employeeId = $_SESSION['employee_id'] ?? null;

// Fetch transactions
if ($isAdmin) {
    $query = "SELECT ca.*, e.first_name, e.last_name, e.employee_code 
              FROM cash_advances ca 
              JOIN employees e ON ca.employee_id = e.id 
              ORDER BY e.last_name, e.first_name, ca.request_date ASC";
    $result = mysqli_query($db, $query);
} else {
    $query = "SELECT ca.*, e.first_name, e.last_name, e.employee_code 
              FROM cash_advances ca 
              JOIN employees e ON ca.employee_id = e.id 
              WHERE ca.employee_id = ? 
              ORDER BY ca.request_date ASC";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, 'i', $employeeId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
}

// Calculate running balance per employee
$employeeBalances = [];
$transactions = [];

while ($row = mysqli_fetch_assoc($result)) {
    $empId = $row['employee_id'];
    if (!isset($employeeBalances[$empId])) {
        $employeeBalances[$empId] = 0;
    }
    
    // Cash Advance increases balance, Payment decreases balance
    if (in_array($row['status'], ['Approved', 'Paid'])) {
        if ($row['particular'] === 'Payment') {
            $employeeBalances[$empId] -= $row['amount'];
        } else {
            $employeeBalances[$empId] += $row['amount'];
        }
    }
    
    $row['running_balance'] = $employeeBalances[$empId];
    $transactions[] = $row;
}

// Reverse to show newest first
$transactions = array_reverse($transactions);

echo json_encode([
    'success' => true,
    'transactions' => $transactions
]);
