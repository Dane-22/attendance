<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/update_allowance_errors.log');
error_log("[update_allowance.php] ========== SCRIPT STARTED ==========");

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
$performanceAllowance = isset($_REQUEST['performance_allowance']) ? (float)$_REQUEST['performance_allowance'] : 0;
$year = isset($_REQUEST['year']) ? (int)$_REQUEST['year'] : 0;
$month = isset($_REQUEST['month']) ? (int)$_REQUEST['month'] : 0;
$week = isset($_REQUEST['week']) ? (int)$_REQUEST['week'] : 1;
$viewType = isset($_REQUEST['view_type']) ? trim($_REQUEST['view_type']) : 'weekly';
// Validate view_type - only allow specific values
if (!in_array($viewType, ['monthly', 'weekly', 'range'])) {
    $viewType = 'weekly';
}

if ($employeeId <= 0 || $year <= 0 || $month < 1 || $month > 12 || $week < 1 || $week > 5) {
    fail('Missing or invalid payroll allowance parameters.');
}

mysqli_begin_transaction($db);

error_log("[update_allowance.php] Starting transaction for emp=$employeeId, year=$year, month=$month, week=$week, view=$viewType");

try {
    error_log("[update_allowance.php] About to update employees table");

    // Check if performance_allowance column exists in employees table
    $columnCheck = mysqli_query($db, "SHOW COLUMNS FROM employees LIKE 'performance_allowance'");
    if (mysqli_num_rows($columnCheck) > 0) {
        $employeeStmt = mysqli_prepare($db, "UPDATE employees SET performance_allowance = ? WHERE id = ?");
        if (!$employeeStmt) {
            error_log("[update_allowance.php] Failed to prepare employee statement: " . mysqli_error($db));
            throw new Exception(mysqli_error($db));
        }
        mysqli_stmt_bind_param($employeeStmt, 'di', $performanceAllowance, $employeeId);
        mysqli_stmt_execute($employeeStmt);
        error_log("[update_allowance.php] Updated employees table, rows affected: " . mysqli_stmt_affected_rows($employeeStmt));
        mysqli_stmt_close($employeeStmt);
    } else {
        error_log("[update_allowance.php] performance_allowance column doesn't exist in employees table, skipping");
    }

    error_log("[update_allowance.php] About to query weekly_payroll_reports");

    $selectStmt = mysqli_prepare(
        $db,
        "SELECT id, gross_pay, ot_amount, ca_deduction, total_deductions
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
        $totalDeductions = (float)$existingRow['total_deductions'];
        $grossPlusAllowance = $grossPay + $performanceAllowance;
        $takeHomePay = $grossPay + $performanceAllowance + $otAmount - $totalDeductions - $caDeduction;

        $updateStmt = mysqli_prepare(
            $db,
            "UPDATE weekly_payroll_reports
             SET performance_allowance = ?, gross_plus_allowance = ?, take_home_pay = ?
             WHERE id = ?"
        );
        if (!$updateStmt) {
            throw new Exception(mysqli_error($db));
        }
        $reportId = (int)$existingRow['id'];
        mysqli_stmt_bind_param($updateStmt, 'dddi', $performanceAllowance, $grossPlusAllowance, $takeHomePay, $reportId);
        mysqli_stmt_execute($updateStmt);
        mysqli_stmt_close($updateStmt);
    }

    mysqli_commit($db);
    echo json_encode([
        'success' => true,
        'message' => 'Performance allowance updated.',
    ]);
} catch (Exception $error) {
    mysqli_rollback($db);
    fail('Failed to update allowance: ' . $error->getMessage(), 500);
}

mysqli_close($db);
?>
