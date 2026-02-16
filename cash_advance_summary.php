<?php
// cash_advance_summary.php - Get cash advance summary for an employee
require_once __DIR__ . '/../../conn/db_connection.php';
require_once __DIR__ . '/../../functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get parameters
$employee_id = intval($_GET['employee_id'] ?? 0);
$month = intval($_GET['month'] ?? 0);
$year = intval($_GET['year'] ?? 0);

if ($employee_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid employee ID']);
    exit;
}

// Build date filter
$dateFilter = "";
if ($month > 0 && $year > 0) {
    $dateFilter = "AND MONTH(request_date) = $month AND YEAR(request_date) = $year";
}

// Get summary statistics
$statsQuery = "SELECT 
    COUNT(*) as total_requests,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_requests,
    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_requests,
    SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as total_pending_amount,
    SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as total_approved_amount,
    SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as total_paid_amount
FROM cash_advances 
WHERE employee_id = ? $dateFilter";

$statsStmt = $conn->prepare($statsQuery);
$statsStmt->bind_param("i", $employee_id);
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_assoc();

// Calculate outstanding balance
$balanceQuery = "SELECT 
    SUM(CASE 
        WHEN particular = 'Payment' AND status IN ('approved', 'paid') THEN -amount 
        WHEN status IN ('approved', 'paid') THEN amount 
        ELSE 0 
    END) as balance
FROM cash_advances 
WHERE employee_id = ?";

$balanceStmt = $conn->prepare($balanceQuery);
$balanceStmt->bind_param("i", $employee_id);
$balanceStmt->execute();
$balance = floatval($balanceStmt->get_result()->fetch_assoc()['balance'] ?? 0);

echo json_encode([
    'success' => true,
    'summary' => [
        'total_requested' => floatval($stats['total_pending_amount'] ?? 0),
        'total_approved' => floatval($stats['total_approved_amount'] ?? 0),
        'total_paid' => floatval($stats['total_paid_amount'] ?? 0),
        'outstanding_balance' => $balance,
        'pending_requests' => intval($stats['pending_requests'] ?? 0),
        'approved_requests' => intval($stats['approved_requests'] ?? 0),
        'rejected_requests' => intval($stats['rejected_requests'] ?? 0),
        'total_requests' => intval($stats['total_requests'] ?? 0)
    ]
]);
?>
