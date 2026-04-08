<?php
// Suppress all error output to ensure clean JSON response
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/update_loan_errors.log');

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

// Debug logging
error_log("[update_loan.php] ========== Request received ==========");
error_log("Employee ID: " . ($_REQUEST['employee_id'] ?? 'NOT SET'));
error_log("SSS Loan: " . ($_REQUEST['sss_loan'] ?? 'NOT SET'));
error_log("Year: " . ($_REQUEST['year'] ?? 'NOT SET'));
error_log("Month: " . ($_REQUEST['month'] ?? 'NOT SET'));
error_log("Week: " . ($_REQUEST['week'] ?? 'NOT SET'));
error_log("View Type: " . ($_REQUEST['view_type'] ?? 'NOT SET'));

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

    error_log("[update_loan.php] Query: emp=$employeeId, year=$year, month=$month, week=$week, view=$viewType");
    error_log("[update_loan.php] Record found: " . ($existingRow ? 'YES (id=' . $existingRow['id'] . ')' : 'NO'));

    if ($existingRow) {
        // UPDATE existing record
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

        error_log("[update_loan.php] Updated existing record id=$reportId with loan=$sssLoan");
    } else {
        // INSERT new record - weekly payroll record doesn't exist yet
        // Get employee info to create minimal record
        $empStmt = mysqli_prepare(
            $db,
            "SELECT id, daily_rate, branch_id FROM employees WHERE id = ? LIMIT 1"
        );
        if (!$empStmt) {
            throw new Exception(mysqli_error($db));
        }
        mysqli_stmt_bind_param($empStmt, 'i', $employeeId);
        mysqli_stmt_execute($empStmt);
        $empResult = mysqli_stmt_get_result($empStmt);
        $empRow = mysqli_fetch_assoc($empResult);
        mysqli_stmt_close($empStmt);

        if (!$empRow) {
            throw new Exception("Employee not found");
        }

        // Insert minimal record with just the loan
        $insertStmt = mysqli_prepare(
            $db,
            "INSERT INTO weekly_payroll_reports
             (employee_id, report_year, report_month, week_number, view_type, branch_id,
              days_worked, total_hours, daily_rate, gross_pay, gross_plus_allowance,
              sss_deduction, philhealth_deduction, pagibig_deduction, ca_deduction,
              sss_loan, total_deductions, take_home_pay, payment_status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, 0, 0, ?, 0, 0, 0, 0, 0, 0, ?, ?, ?, 'Not Paid', NOW())"
        );
        if (!$insertStmt) {
            throw new Exception(mysqli_error($db));
        }

        $dailyRate = (float)$empRow['daily_rate'];
        $branchId = (int)$empRow['branch_id'];

        mysqli_stmt_bind_param($insertStmt, 'iiiisidddd',
            $employeeId, $year, $month, $week, $viewType, $branchId,
            $dailyRate, $sssLoan, $sssLoan, $sssLoan
        );
        mysqli_stmt_execute($insertStmt);
        $newId = mysqli_insert_id($db);
        mysqli_stmt_close($insertStmt);

        error_log("[update_loan.php] Created new record id=$newId with loan=$sssLoan");
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