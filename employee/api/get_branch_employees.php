<?php
session_start();
require_once '../../conn/db_connection.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get parameters
$branchName = isset($_GET['branch_name']) ? $_GET['branch_name'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

if (empty($branchName)) {
    http_response_code(400);
    echo json_encode(['error' => 'Branch name is required']);
    exit;
}

// Handle "Unassigned" branch (NULL branch_id)
if ($branchName === 'Unassigned') {
    // Query for employees without a branch assignment
    $sql = "SELECT 
                e.id,
                e.employee_code,
                CONCAT(e.first_name, ' ', COALESCE(e.middle_name, ''), ' ', e.last_name) as full_name,
                e.position,
                e.daily_rate,
                COALESCE(SUM(dpr.days_worked), 0) as days_worked,
                COALESCE(SUM(dpr.basic_pay), 0) as basic_pay,
                COALESCE(SUM(dpr.ot_amount), 0) as ot_amount,
                COALESCE(SUM(dpr.gross_pay), 0) as gross_pay,
                COALESCE(SUM(dpr.total_deductions), 0) as total_deductions,
                COALESCE(SUM(dpr.take_home_pay), 0) as take_home_pay
            FROM employees e
            LEFT JOIN daily_payroll_reports dpr ON e.id = dpr.employee_id 
                AND dpr.report_date BETWEEN ? AND ?
            WHERE e.branch_id IS NULL
              AND e.status = 'Active'
            GROUP BY e.id, e.employee_code, e.first_name, e.middle_name, e.last_name, 
                     e.position, e.daily_rate
            HAVING days_worked > 0 OR basic_pay > 0
            ORDER BY e.last_name, e.first_name";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ss", $startDate, $endDate);
} else {
    // Query for employees in the specified branch (based on payroll records, not current assignment)
    $sql = "SELECT 
                e.id,
                e.employee_code,
                CONCAT(e.first_name, ' ', COALESCE(e.middle_name, ''), ' ', e.last_name) as full_name,
                e.position,
                e.daily_rate,
                COALESCE(SUM(dpr.days_worked), 0) as days_worked,
                COALESCE(SUM(dpr.basic_pay), 0) as basic_pay,
                COALESCE(SUM(dpr.ot_amount), 0) as ot_amount,
                COALESCE(SUM(dpr.gross_pay), 0) as gross_pay,
                COALESCE(SUM(dpr.total_deductions), 0) as total_deductions,
                COALESCE(SUM(dpr.take_home_pay), 0) as take_home_pay
            FROM daily_payroll_reports dpr
            JOIN branches b ON dpr.branch_id = b.id
            JOIN employees e ON dpr.employee_id = e.id
            WHERE b.branch_name = ?
              AND dpr.report_date BETWEEN ? AND ?
            GROUP BY e.id, e.employee_code, e.first_name, e.middle_name, e.last_name, 
                     e.position, e.daily_rate
            HAVING days_worked > 0 OR basic_pay > 0
            ORDER BY e.last_name, e.first_name";

    $stmt = $db->prepare($sql);
    $stmt->bind_param("sss", $branchName, $startDate, $endDate);
}

$stmt->execute();
$result = $stmt->get_result();
$employees = $result->fetch_all(MYSQLI_ASSOC);

// Calculate totals
$totals = [
    'employee_count' => count($employees),
    'total_days_worked' => array_sum(array_column($employees, 'days_worked')),
    'total_basic_pay' => array_sum(array_column($employees, 'basic_pay')),
    'total_ot_amount' => array_sum(array_column($employees, 'ot_amount')),
    'total_gross_pay' => array_sum(array_column($employees, 'gross_pay')),
    'total_deductions' => array_sum(array_column($employees, 'total_deductions')),
    'total_take_home' => array_sum(array_column($employees, 'take_home_pay'))
];

echo json_encode([
    'success' => true,
    'branch_name' => $branchName,
    'start_date' => $startDate,
    'end_date' => $endDate,
    'employees' => $employees,
    'totals' => $totals
]);
