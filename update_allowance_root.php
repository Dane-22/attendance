<?php
/**
 * Root-level update_allowance.php
 *
 * Drop this file into the root of your web app so the mobile app can call:
 *   https://your-domain.com/update_allowance.php
 *
 * Expected POST/REQUEST params:
 * - employee_id
 * - performance_allowance
 * - year
 * - month
 * - week
 * - view_type
 */

if (file_exists(__DIR__ . '/conn/db_connection.php')) {
    require_once __DIR__ . '/conn/db_connection.php';
} elseif (file_exists(__DIR__ . '/db_connection.php')) {
    require_once __DIR__ . '/db_connection.php';
} elseif (file_exists(dirname(__DIR__) . '/conn/db_connection.php')) {
    require_once dirname(__DIR__) . '/conn/db_connection.php';
} else {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Database connection file not found.',
    ]);
    exit;
}

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

function respond_json($statusCode, $payload) {
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

$employeeId = isset($_REQUEST['employee_id']) ? (int)$_REQUEST['employee_id'] : 0;
$performanceAllowance = isset($_REQUEST['performance_allowance']) ? (float)$_REQUEST['performance_allowance'] : 0.0;
$year = isset($_REQUEST['year']) ? (int)$_REQUEST['year'] : 0;
$month = isset($_REQUEST['month']) ? (int)$_REQUEST['month'] : 0;
$week = isset($_REQUEST['week']) ? (int)$_REQUEST['week'] : 1;
$viewType = isset($_REQUEST['view_type']) ? trim($_REQUEST['view_type']) : 'weekly';
$viewType = $viewType === 'monthly' ? 'monthly' : 'weekly';

if ($employeeId <= 0) {
    respond_json(400, [
        'success' => false,
        'message' => 'employee_id is required.',
    ]);
}

if ($year <= 0 || $month < 1 || $month > 12 || $week < 1 || $week > 5) {
    respond_json(400, [
        'success' => false,
        'message' => 'Invalid payroll period parameters.',
    ]);
}

mysqli_begin_transaction($db);

try {
    // Update the employee's default allowance as documented by the web flow.
    $employeeStmt = mysqli_prepare(
        $db,
        "UPDATE employees SET performance_allowance = ? WHERE id = ?"
    );

    if (!$employeeStmt) {
        throw new Exception('Failed to prepare employee update: ' . mysqli_error($db));
    }

    mysqli_stmt_bind_param($employeeStmt, 'di', $performanceAllowance, $employeeId);
    mysqli_stmt_execute($employeeStmt);
    mysqli_stmt_close($employeeStmt);

    // Try to update the matching weekly payroll report row.
    $selectStmt = mysqli_prepare(
        $db,
        "SELECT id, gross_pay, ot_amount, ca_deduction, total_deductions
         FROM weekly_payroll_reports
         WHERE employee_id = ?
           AND report_year = ?
           AND report_month = ?
           AND week_number = ?
           AND view_type = ?
         ORDER BY id DESC
         LIMIT 1"
    );

    if (!$selectStmt) {
        throw new Exception('Failed to prepare payroll lookup: ' . mysqli_error($db));
    }

    mysqli_stmt_bind_param($selectStmt, 'iiiis', $employeeId, $year, $month, $week, $viewType);
    mysqli_stmt_execute($selectStmt);
    $result = mysqli_stmt_get_result($selectStmt);
    $reportRow = mysqli_fetch_assoc($result);
    mysqli_stmt_close($selectStmt);

    if ($reportRow) {
        $grossPay = (float)$reportRow['gross_pay'];
        $otAmount = (float)$reportRow['ot_amount'];
        $caDeduction = (float)$reportRow['ca_deduction'];
        $totalDeductions = (float)$reportRow['total_deductions'];
        $grossPlusAllowance = $grossPay + $performanceAllowance;
        $takeHomePay = $grossPay + $performanceAllowance + $otAmount - $totalDeductions - $caDeduction;
        $reportId = (int)$reportRow['id'];

        $updateStmt = mysqli_prepare(
            $db,
            "UPDATE weekly_payroll_reports
             SET performance_allowance = ?,
                 gross_plus_allowance = ?,
                 take_home_pay = ?
             WHERE id = ?"
        );

        if (!$updateStmt) {
            throw new Exception('Failed to prepare payroll update: ' . mysqli_error($db));
        }

        mysqli_stmt_bind_param($updateStmt, 'dddi', $performanceAllowance, $grossPlusAllowance, $takeHomePay, $reportId);
        mysqli_stmt_execute($updateStmt);
        mysqli_stmt_close($updateStmt);
    }

    mysqli_commit($db);

    respond_json(200, [
        'success' => true,
        'message' => $reportRow
            ? 'Performance allowance updated successfully.'
            : 'Employee default allowance updated. No matching weekly payroll report row was found for the selected period.',
        'updated_employee_id' => $employeeId,
        'report_found' => (bool)$reportRow,
    ]);
} catch (Exception $error) {
    mysqli_rollback($db);
    respond_json(500, [
        'success' => false,
        'message' => 'Failed to update allowance.',
        'error' => $error->getMessage(),
    ]);
}

mysqli_close($db);
?>
