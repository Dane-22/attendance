<?php
require_once __DIR__ . '/../conn/db_connection.php';

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
$sssLoan = isset($_REQUEST['sss_loan']) ? (float)$_REQUEST['sss_loan'] : 0;
$year = isset($_REQUEST['year']) ? (int)$_REQUEST['year'] : 0;
$month = isset($_REQUEST['month']) ? (int)$_REQUEST['month'] : 0;
$week = isset($_REQUEST['week']) ? (int)$_REQUEST['week'] : 1;
$viewType = isset($_REQUEST['view_type']) ? trim($_REQUEST['view_type']) : 'weekly';
$viewType = $viewType === 'monthly' ? 'monthly' : 'weekly';

if ($employeeId <= 0 || $year <= 0 || $month < 1 || $month > 12 || $week < 1 || $week > 5) {
    fail('Missing or invalid payroll loan parameters.');
}

mysqli_begin_transaction($db);

try {
    $selectStmt = mysqli_prepare(
        $db,
        "SELECT id, gross_pay, ot_amount, ca_deduction, sss_deduction, philhealth_deduction, pagibig_deduction, performance_allowance
         FROM weekly_payroll_reports
         WHERE employee_id = ? AND report_year = ? AND report_month = ? AND week_number = ? AND view_type = ?
         LIMIT 1"
    );
    if (!$selectStmt) {
        throw new Exception(mysqli_error($db));
    }
    mysqli_stmt_bind_param($selectStmt, 'iiiis', $employeeId, $year, $month, $week, $viewType);
    mysqli_stmt_execute($selectStmt);
    $existing = mysqli_stmt_get_result($selectStmt);
    $existingRow = mysqli_fetch_assoc($existing);
    mysqli_stmt_close($selectStmt);

    if ($existingRow) {
        $grossPay = (float)$existingRow['gross_pay'];
        $otAmount = (float)$existingRow['ot_amount'];
        $caDeduction = (float)$existingRow['ca_deduction'];
        $sssDeduction = (float)$existingRow['sss_deduction'];
        $philhealthDeduction = (float)$existingRow['philhealth_deduction'];
        $pagibigDeduction = (float)$existingRow['pagibig_deduction'];
        $performanceAllowance = (float)$existingRow['performance_allowance'];
        
        $totalDeductions = $sssDeduction + $philhealthDeduction + $pagibigDeduction + $caDeduction + $sssLoan;
        $takeHomePay = $grossPay + $performanceAllowance + $otAmount - $totalDeductions;

        $updateStmt = mysqli_prepare(
            $db,
            "UPDATE weekly_payroll_reports
             SET sss_loan = ?, total_deductions = ?, take_home_pay = ?
             WHERE id = ?"
        );
        if (!$updateStmt) {
            throw new Exception(mysqli_error($db));
        }
        $reportId = (int)$existingRow['id'];
        mysqli_stmt_bind_param($updateStmt, 'dddi', $sssLoan, $totalDeductions, $takeHomePay, $reportId);
        mysqli_stmt_execute($updateStmt);
        mysqli_stmt_close($updateStmt);
    }

    mysqli_commit($db);
    echo json_encode([
        'success' => true,
        'message' => 'SSS Loan updated.',
    ]);
} catch (Exception $error) {
    mysqli_rollback($db);
    fail('Failed to update loan: ' . $error->getMessage(), 500);
}

mysqli_close($db);
?>