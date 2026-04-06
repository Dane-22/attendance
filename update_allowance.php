<?php
require_once __DIR__ . '/conn/db_connection.php';
require_once __DIR__ . '/payroll_report_helpers.php';

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

    $computedRow = payroll_find_report_row($db, $year, $month, $viewType, $week, $employeeId, null);
    if (!$computedRow) {
        throw new Exception('No worker payroll data found for the selected employee and period.');
    }

    $resolvedBranchId = isset($computedRow['branch_id']) && $computedRow['branch_id'] !== null
        ? (int)$computedRow['branch_id']
        : null;
    $branchIdParam = $resolvedBranchId !== null ? $resolvedBranchId : 0;
    $reportId = is_numeric($computedRow['id']) ? (int)$computedRow['id'] : 0;

    $computedRow['performance_allowance'] = $performanceAllowance;
    $computedRow['gross_plus_allowance'] = payroll_to_float($computedRow['gross_pay']) + $performanceAllowance;
    $computedRow['take_home_pay'] =
        payroll_to_float($computedRow['gross_pay']) +
        $performanceAllowance +
        payroll_to_float($computedRow['ot_amount']) -
        payroll_to_float($computedRow['total_deductions']) -
        payroll_to_float($computedRow['ca_deduction']);

    if ($reportId > 0) {
        $updateStmt = mysqli_prepare(
            $db,
            "UPDATE weekly_payroll_reports
             SET branch_id = NULLIF(?, 0),
                 days_worked = ?,
                 total_hours = ?,
                 daily_rate = ?,
                 basic_pay = ?,
                 ot_hours = ?,
                 ot_rate = ?,
                 ot_amount = ?,
                 performance_allowance = ?,
                 gross_pay = ?,
                 gross_plus_allowance = ?,
                 ca_deduction = ?,
                 sss_deduction = ?,
                 philhealth_deduction = ?,
                 pagibig_deduction = ?,
                 sss_loan = ?,
                 total_deductions = ?,
                 take_home_pay = ?
             WHERE id = ?"
        );

        if (!$updateStmt) {
            throw new Exception('Failed to prepare payroll update: ' . mysqli_error($db));
        }

        mysqli_stmt_bind_param(
            $updateStmt,
            'idddddddddddddddddi',
            $branchIdParam,
            $computedRow['days_worked'],
            $computedRow['total_hours'],
            $computedRow['daily_rate'],
            $computedRow['basic_pay'],
            $computedRow['ot_hours'],
            $computedRow['ot_rate'],
            $computedRow['ot_amount'],
            $computedRow['performance_allowance'],
            $computedRow['gross_pay'],
            $computedRow['gross_plus_allowance'],
            $computedRow['ca_deduction'],
            $computedRow['sss_deduction'],
            $computedRow['philhealth_deduction'],
            $computedRow['pagibig_deduction'],
            $computedRow['sss_loan'],
            $computedRow['total_deductions'],
            $computedRow['take_home_pay'],
            $reportId
        );
        mysqli_stmt_execute($updateStmt);
        mysqli_stmt_close($updateStmt);
    } else {
        $insertStmt = mysqli_prepare(
            $db,
            "INSERT INTO weekly_payroll_reports (
                employee_id,
                report_year,
                report_month,
                week_number,
                view_type,
                branch_id,
                days_worked,
                total_hours,
                daily_rate,
                basic_pay,
                ot_hours,
                ot_rate,
                ot_amount,
                performance_allowance,
                gross_pay,
                gross_plus_allowance,
                ca_deduction,
                sss_deduction,
                philhealth_deduction,
                pagibig_deduction,
                sss_loan,
                total_deductions,
                take_home_pay,
                status,
                payment_status
             ) VALUES (?, ?, ?, ?, ?, NULLIF(?, 0), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$insertStmt) {
            throw new Exception('Failed to prepare payroll insert: ' . mysqli_error($db));
        }

        mysqli_stmt_bind_param(
            $insertStmt,
            'iiiisiddddddddddddddddss',
            $employeeId,
            $year,
            $month,
            $week,
            $viewType,
            $branchIdParam,
            $computedRow['days_worked'],
            $computedRow['total_hours'],
            $computedRow['daily_rate'],
            $computedRow['basic_pay'],
            $computedRow['ot_hours'],
            $computedRow['ot_rate'],
            $computedRow['ot_amount'],
            $computedRow['performance_allowance'],
            $computedRow['gross_pay'],
            $computedRow['gross_plus_allowance'],
            $computedRow['ca_deduction'],
            $computedRow['sss_deduction'],
            $computedRow['philhealth_deduction'],
            $computedRow['pagibig_deduction'],
            $computedRow['sss_loan'],
            $computedRow['total_deductions'],
            $computedRow['take_home_pay'],
            $computedRow['status'],
            $computedRow['payment_status']
        );
        mysqli_stmt_execute($insertStmt);
        $reportId = mysqli_insert_id($db);
        mysqli_stmt_close($insertStmt);
    }

    mysqli_commit($db);
    mysqli_close($db);

    respond_json(200, [
        'success' => true,
        'message' => 'Performance allowance updated successfully.',
        'updated_employee_id' => $employeeId,
        'report_id' => $reportId,
        'report_row' => $computedRow,
    ]);
} catch (Exception $error) {
    mysqli_rollback($db);
    mysqli_close($db);
    respond_json(500, [
        'success' => false,
        'message' => 'Failed to update allowance.',
        'error' => $error->getMessage(),
    ]);
}
?>
