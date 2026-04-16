<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/update_days_worked_errors.log');
error_log("[update_days_worked.php] ========== SCRIPT STARTED ==========");

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
$daysWorked = isset($_REQUEST['days_worked']) ? (float)$_REQUEST['days_worked'] : 0;
$year = isset($_REQUEST['year']) ? (int)$_REQUEST['year'] : 0;
$month = isset($_REQUEST['month']) ? (int)$_REQUEST['month'] : 0;
$week = isset($_REQUEST['week']) ? (int)$_REQUEST['week'] : 1;
$viewType = isset($_REQUEST['view_type']) ? trim($_REQUEST['view_type']) : 'weekly';
$startDate = isset($_REQUEST['start_date']) ? trim($_REQUEST['start_date']) : null;
$endDate = isset($_REQUEST['end_date']) ? trim($_REQUEST['end_date']) : null;

// Validate view_type - only allow specific values
if (!in_array($viewType, ['monthly', 'weekly', 'range'])) {
    $viewType = 'weekly';
}

if ($employeeId <= 0 || $year <= 0 || $month < 1 || $month > 12 || $week < 1 || $week > 5) {
    fail('Missing or invalid payroll days worked parameters.');
}

if (!$startDate || !$endDate) {
    fail('Start date and end date are required.');
}

error_log("[update_days_worked.php] Parameters: emp=$employeeId, days=$daysWorked, year=$year, month=$month, week=$week, view=$viewType, start=$startDate, end=$endDate");

mysqli_begin_transaction($db);

try {
    // Fetch employee's daily rate and other details
    // Check which columns exist first
    $columnCheck = mysqli_query($db, "SHOW COLUMNS FROM employees LIKE 'sss_loan'");
    $hasSssLoanColumn = mysqli_num_rows($columnCheck) > 0;
    mysqli_free_result($columnCheck);
    
    $columnCheck2 = mysqli_query($db, "SHOW COLUMNS FROM employees LIKE 'has_deduction'");
    $hasHasDeductionColumn = mysqli_num_rows($columnCheck2) > 0;
    mysqli_free_result($columnCheck2);
    
    $columnCheck3 = mysqli_query($db, "SHOW COLUMNS FROM employees LIKE 'performance_allowance'");
    $hasPerformanceAllowanceColumn = mysqli_num_rows($columnCheck3) > 0;
    mysqli_free_result($columnCheck3);
    
    // Build query based on available columns
    $selectColumns = "daily_rate, branch_id";
    if ($hasPerformanceAllowanceColumn) {
        $selectColumns .= ", performance_allowance";
    }
    if ($hasSssLoanColumn) {
        $selectColumns .= ", sss_loan";
    }
    if ($hasHasDeductionColumn) {
        $selectColumns .= ", has_deduction";
    }
    
    $empQuery = "SELECT $selectColumns FROM employees WHERE id = ? AND status = 'Active'";
    $empStmt = mysqli_prepare($db, $empQuery);
    if (!$empStmt) {
        throw new Exception("Failed to prepare employee query: " . mysqli_error($db));
    }
    mysqli_stmt_bind_param($empStmt, 'i', $employeeId);
    mysqli_stmt_execute($empStmt);
    $empResult = mysqli_stmt_get_result($empStmt);
    $employee = mysqli_fetch_assoc($empResult);
    mysqli_stmt_close($empStmt);

    if (!$employee) {
        throw new Exception("Employee not found or not active.");
    }

    $dailyRate = floatval($employee['daily_rate'] ?? 0);
    $performanceAllowance = floatval($employee['performance_allowance'] ?? 0);
    $sssLoan = floatval($employee['sss_loan'] ?? 0);
    $branchId = $employee['branch_id'] ?? null;
    $hasDeduction = $employee['has_deduction'] ?? 0;

    // Calculate payroll values
    $basicPay = $dailyRate * $daysWorked;
    $grossPay = $basicPay;
    $grossPlusAllowance = $grossPay + $performanceAllowance;

    // Calculate prorated deductions based on view type and week
    $sssDeduction = 0;
    $philhealthDeduction = 0;
    $pagibigDeduction = 0;

    if ($daysWorked > 0 && $hasDeduction) {
        if ($viewType === 'monthly') {
            $sssDeduction = 450.00;
            $philhealthDeduction = 250.00;
            $pagibigDeduction = 200.00;
        } else {
            // Weekly prorated deductions
            switch ($week) {
                case 1:
                    $sssDeduction = 250.00;
                    $philhealthDeduction = 100.00;
                    $pagibigDeduction = 50.00;
                    break;
                case 2:
                    $sssDeduction = 100.00;
                    $philhealthDeduction = 100.00;
                    $pagibigDeduction = 50.00;
                    break;
                case 3:
                    $sssDeduction = 100.00;
                    $philhealthDeduction = 50.00;
                    $pagibigDeduction = 100.00;
                    break;
                case 4:
                case 5:
                default:
                    $sssDeduction = 0.00;
                    $philhealthDeduction = 0.00;
                    $pagibigDeduction = 0.00;
                    break;
            }
        }
    }

    $totalDeductions = $sssDeduction + $philhealthDeduction + $pagibigDeduction + $sssLoan;
    $caDeduction = 0; // Will be updated separately via update_allowance.php if needed
    $takeHomePay = $grossPlusAllowance - $totalDeductions - $caDeduction;

    error_log("[update_days_worked.php] Calculated values: basic=$basicPay, gross=$grossPay, gross+allowance=$grossPlusAllowance, take_home=$takeHomePay");

    // Check if record exists in daily_payroll_reports for this date range
    $checkStmt = mysqli_prepare($db, 
        "SELECT id FROM daily_payroll_reports 
         WHERE employee_id = ? AND report_date BETWEEN ? AND ? 
         ORDER BY report_date DESC LIMIT 1"
    );
    if (!$checkStmt) {
        throw new Exception("Failed to prepare check statement: " . mysqli_error($db));
    }
    mysqli_stmt_bind_param($checkStmt, 'iss', $employeeId, $startDate, $endDate);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);
    $existingRecord = mysqli_fetch_assoc($checkResult);
    mysqli_stmt_close($checkStmt);

    // Parse start date to get day, year, month for the record
    $startTs = strtotime($startDate);
    $reportDay = date('j', $startTs);
    $reportYear = date('Y', $startTs);
    $reportMonth = date('n', $startTs);

    if ($existingRecord) {
        // Update existing record
        // Check if is_manual_adjustment column exists
        $colCheck = mysqli_query($db, "SHOW COLUMNS FROM daily_payroll_reports LIKE 'is_manual_adjustment'");
        $hasManualCol = mysqli_num_rows($colCheck) > 0;
        mysqli_free_result($colCheck);
        
        if ($hasManualCol) {
            $updateStmt = mysqli_prepare($db,
                "UPDATE daily_payroll_reports 
                 SET days_worked = ?, 
                     total_hours = ?,
                     basic_pay = ?, 
                     gross_pay = ?, 
                     gross_plus_allowance = ?, 
                     sss_deduction = ?,
                     philhealth_deduction = ?,
                     pagibig_deduction = ?,
                     total_deductions = ?,
                     take_home_pay = ?,
                     is_manual_adjustment = 1,
                     updated_at = NOW()
                 WHERE id = ?"
            );
        } else {
            $updateStmt = mysqli_prepare($db,
                "UPDATE daily_payroll_reports 
                 SET days_worked = ?, 
                     total_hours = ?,
                     basic_pay = ?, 
                     gross_pay = ?, 
                     gross_plus_allowance = ?, 
                     sss_deduction = ?,
                     philhealth_deduction = ?,
                     pagibig_deduction = ?,
                     total_deductions = ?,
                     take_home_pay = ?,
                     updated_at = NOW()
                 WHERE id = ?"
            );
        }
        if (!$updateStmt) {
            throw new Exception("Failed to prepare update statement: " . mysqli_error($db));
        }
        
        $totalHours = $daysWorked * 8; // Assume 8 hours per day
        $recordId = $existingRecord['id'];
        
        mysqli_stmt_bind_param($updateStmt, 'ddddddddddi', 
            $daysWorked, 
            $totalHours,
            $basicPay, 
            $grossPay, 
            $grossPlusAllowance, 
            $sssDeduction,
            $philhealthDeduction,
            $pagibigDeduction,
            $totalDeductions,
            $takeHomePay,
            $recordId
        );
        mysqli_stmt_execute($updateStmt);
        $rowsAffected = mysqli_stmt_affected_rows($updateStmt);
        mysqli_stmt_close($updateStmt);
        
        error_log("[update_days_worked.php] Updated existing record ID=$recordId, rows affected=$rowsAffected");
    } else {
        // Insert new record
        // Check if is_manual_adjustment column exists
        $colCheck = mysqli_query($db, "SHOW COLUMNS FROM daily_payroll_reports LIKE 'is_manual_adjustment'");
        $hasManualCol = mysqli_num_rows($colCheck) > 0;
        mysqli_free_result($colCheck);
        
        if ($hasManualCol) {
            $insertStmt = mysqli_prepare($db,
                "INSERT INTO daily_payroll_reports 
                 (employee_id, report_date, report_year, report_month, report_day, week_number, branch_id,
                  days_worked, total_hours, daily_rate, basic_pay, ot_hours, ot_rate, ot_amount,
                  performance_allowance, gross_pay, gross_plus_allowance, ca_deduction,
                  sss_deduction, philhealth_deduction, pagibig_deduction, sss_loan, total_deductions, take_home_pay, status, is_manual_adjustment)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 1)"
            );
        } else {
            $insertStmt = mysqli_prepare($db,
                "INSERT INTO daily_payroll_reports 
                 (employee_id, report_date, report_year, report_month, report_day, week_number, branch_id,
                  days_worked, total_hours, daily_rate, basic_pay, ot_hours, ot_rate, ot_amount,
                  performance_allowance, gross_pay, gross_plus_allowance, ca_deduction,
                  sss_deduction, philhealth_deduction, pagibig_deduction, sss_loan, total_deductions, take_home_pay, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')"
            );
        }
        if (!$insertStmt) {
            throw new Exception("Failed to prepare insert statement: " . mysqli_error($db));
        }
        
        $totalHours = $daysWorked * 8; // Assume 8 hours per day
        $otRate = $dailyRate / 8;
        
        mysqli_stmt_bind_param($insertStmt, 'isiiiiidddddddddddddddd',
            $employeeId,
            $startDate,
            $reportYear,
            $reportMonth,
            $reportDay,
            $week,
            $branchId,
            $daysWorked,
            $totalHours,
            $dailyRate,
            $basicPay,
            $otRate,
            $performanceAllowance,
            $grossPay,
            $grossPlusAllowance,
            $caDeduction,
            $sssDeduction,
            $philhealthDeduction,
            $pagibigDeduction,
            $sssLoan,
            $totalDeductions,
            $takeHomePay
        );
        mysqli_stmt_execute($insertStmt);
        $newId = mysqli_insert_id($db);
        mysqli_stmt_close($insertStmt);
        
        error_log("[update_days_worked.php] Inserted new record ID=$newId");
    }

    // Delete any other records for this employee in the same date range
    // This consolidates manual adjustments to a single record
    $recordIdToKeep = $existingRecord['id'] ?? $newId ?? null;
    if ($recordIdToKeep) {
        $deleteStmt = mysqli_prepare($db,
            "DELETE FROM daily_payroll_reports 
             WHERE employee_id = ? 
             AND report_date BETWEEN ? AND ? 
             AND id != ?"
        );
        if ($deleteStmt) {
            mysqli_stmt_bind_param($deleteStmt, 'issi', $employeeId, $startDate, $endDate, $recordIdToKeep);
            mysqli_stmt_execute($deleteStmt);
            $deletedCount = mysqli_stmt_affected_rows($deleteStmt);
            mysqli_stmt_close($deleteStmt);
            error_log("[update_days_worked.php] Deleted $deletedCount other records in range, kept ID=$recordIdToKeep");
        }
    }

    // Also update weekly_payroll_reports if it exists (optional - don't fail if table missing)
    try {
        $weeklyCheckStmt = mysqli_prepare($db,
            "SELECT id FROM weekly_payroll_reports 
             WHERE employee_id = ? AND report_year = ? AND report_month = ? AND week_number = ? AND view_type = ?
             LIMIT 1"
        );
        if ($weeklyCheckStmt) {
            mysqli_stmt_bind_param($weeklyCheckStmt, 'iiiis', $employeeId, $year, $month, $week, $viewType);
            mysqli_stmt_execute($weeklyCheckStmt);
            $weeklyResult = mysqli_stmt_get_result($weeklyCheckStmt);
            $weeklyRecord = mysqli_fetch_assoc($weeklyResult);
            mysqli_stmt_close($weeklyCheckStmt);

            if ($weeklyRecord) {
                // Update weekly report
                $weeklyUpdateStmt = mysqli_prepare($db,
                    "UPDATE weekly_payroll_reports 
                     SET days_worked = ?, basic_pay = ?, gross_pay = ?, gross_plus_allowance = ?,
                         sss_deduction = ?, philhealth_deduction = ?, pagibig_deduction = ?,
                         total_deductions = ?, take_home_pay = ?, updated_at = NOW()
                     WHERE id = ?"
                );
                if ($weeklyUpdateStmt) {
                    $weeklyId = $weeklyRecord['id'];
                    mysqli_stmt_bind_param($weeklyUpdateStmt, 'dddddddddi',
                        $daysWorked, $basicPay, $grossPay, $grossPlusAllowance,
                        $sssDeduction, $philhealthDeduction, $pagibigDeduction,
                        $totalDeductions, $takeHomePay, $weeklyId
                    );
                    mysqli_stmt_execute($weeklyUpdateStmt);
                    mysqli_stmt_close($weeklyUpdateStmt);
                    error_log("[update_days_worked.php] Updated weekly_payroll_reports ID=$weeklyId");
                }
            }
        }
    } catch (Exception $weeklyError) {
        // Log but don't fail - weekly report update is optional
        error_log("[update_days_worked.php] Weekly report update skipped: " . $weeklyError->getMessage());
    }

    mysqli_commit($db);
    
    echo json_encode([
        'success' => true,
        'message' => 'Days worked updated successfully.',
        'data' => [
            'employee_id' => $employeeId,
            'days_worked' => $daysWorked,
            'basic_pay' => $basicPay,
            'gross_pay' => $grossPay,
            'gross_plus_allowance' => $grossPlusAllowance,
            'take_home_pay' => $takeHomePay,
            'total_deductions' => $totalDeductions
        ]
    ]);
    
    error_log("[update_days_worked.php] ========== SCRIPT COMPLETED SUCCESSFULLY ==========");
    
} catch (Exception $error) {
    mysqli_rollback($db);
    error_log("[update_days_worked.php] ERROR: " . $error->getMessage());
    fail('Failed to update days worked: ' . $error->getMessage(), 500);
}

mysqli_close($db);
?>
