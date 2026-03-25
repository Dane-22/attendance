<?php
// update_allowance.php - Save Performance Allowance to Database

// Disable ALL error display - MUST be at very top
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);

// Log errors to file instead
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/update_allowance_errors.log');

// Start output buffering immediately to catch any output
ob_start();

try {
    require_once __DIR__ . '/../conn/db_connection.php';
    session_start();
    
    // Clear any buffered output
    ob_end_clean();
    
    // Set JSON response header
    header('Content-Type: application/json');
    
    // Log session info for debugging
    error_log('Session: logged_in=' . ($_SESSION['logged_in'] ?? 'not set') . ', position=' . ($_SESSION['position'] ?? 'not set'));
    
    // Check database connection
    if (!isset($db) || !$db) {
        throw new Exception('Database connection failed');
    }

    // Check if user is logged in and has appropriate permissions
    if (empty($_SESSION['logged_in']) || !in_array($_SESSION['position'], ['Admin', 'Super Admin', 'Developer'])) {
        throw new Exception('Unauthorized. Position: ' . ($_SESSION['position'] ?? 'none'));
    }

    // Get POST parameters
    $employee_id = intval($_POST['employee_id'] ?? 0);
    $performance_allowance = floatval($_POST['performance_allowance'] ?? 0);
    $year = intval($_POST['year'] ?? 0);
    $month = intval($_POST['month'] ?? 0);
    $week = intval($_POST['week'] ?? 0);
    $view_type = $_POST['view_type'] ?? 'weekly';

    // Validate required fields
    if (!$employee_id || !$year || !$month) {
        throw new Exception('Missing required fields');
    }

    $week_num_for_db = ($view_type === 'monthly') ? 0 : $week;

    $upsert_query = "INSERT INTO weekly_payroll_reports 
                     (employee_id, report_year, report_month, week_number, view_type, performance_allowance, payment_status, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, 'Not Paid', NOW(), NOW())
                     ON DUPLICATE KEY UPDATE 
                     performance_allowance = VALUES(performance_allowance),
                     updated_at = NOW()";

    $stmt = @mysqli_prepare($db, $upsert_query);

    if (!$stmt) {
        throw new Exception('Database prepare error: ' . mysqli_error($db));
    }

    // 6 params: employee_id(i), year(i), month(i), week_num(i), view_type(s), performance_allowance(d)
    @mysqli_stmt_bind_param($stmt, 'iiiisd', $employee_id, $year, $month, $week_num_for_db, $view_type, $performance_allowance);

    if (!@mysqli_stmt_execute($stmt)) {
        throw new Exception('Database execute error: ' . mysqli_stmt_error($stmt));
    }
    
    $affected_rows = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    
    error_log("UPSERT result: affected_rows=$affected_rows for emp=$employee_id, year=$year, month=$month, week=$week_num_for_db");
    
    // affected_rows = 1 for insert, 2 for update
    if ($affected_rows > 0) {
        // Also update employee's default performance allowance (permanent)
        $update_employee = "UPDATE employees SET performance_allowance = ? WHERE id = ?";
        $emp_stmt = @mysqli_prepare($db, $update_employee);
        if ($emp_stmt) {
            @mysqli_stmt_bind_param($emp_stmt, 'di', $performance_allowance, $employee_id);
            @mysqli_stmt_execute($emp_stmt);
            @mysqli_stmt_close($emp_stmt);
            error_log("Updated employee $employee_id default allowance to $performance_allowance");
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Performance allowance saved successfully',
            'affected_rows' => $affected_rows
        ]);
        exit;
    }

    // If upsert failed, try updating daily records as fallback
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);

    if ($view_type === 'weekly') {
        $week_start_day = 1 + (($week - 1) * 7);
        $week_end_day = min($week_start_day + 6, $days_in_month);
        $start_date = sprintf('%04d-%02d-%02d', $year, $month, $week_start_day);
        $end_date = sprintf('%04d-%02d-%02d', $year, $month, $week_end_day);
    } else {
        $start_date = sprintf('%04d-%02d-01', $year, $month);
        $end_date = sprintf('%04d-%02d-%02d', $year, $month, $days_in_month);
    }

    $update_daily = "UPDATE daily_payroll_reports 
                     SET performance_allowance = ?, 
                         updated_at = NOW()
                     WHERE employee_id = ? 
                     AND report_date BETWEEN ? AND ?";

    $stmt2 = @mysqli_prepare($db, $update_daily);

    if (!$stmt2) {
        throw new Exception('Database prepare error (daily): ' . mysqli_error($db));
    }

    @mysqli_stmt_bind_param($stmt2, 'diss', $performance_allowance, $employee_id, $start_date, $end_date);

    if (!@mysqli_stmt_execute($stmt2)) {
        throw new Exception('Database execute error (daily): ' . mysqli_stmt_error($stmt2));
    }
    
    $daily_affected = mysqli_stmt_affected_rows($stmt2);
    mysqli_stmt_close($stmt2);
    
    if ($daily_affected > 0) {
        error_log("Saved allowance for emp $employee_id: $performance_allowance");
        echo json_encode([
            'success' => true, 
            'message' => 'Performance allowance saved to daily records',
            'table' => 'daily_payroll_reports',
            'affected_rows' => $daily_affected
        ]);
    } else {
        echo json_encode([
            'success' => true, 
            'message' => 'Allowance value accepted but no payroll records exist yet for this period.',
            'warning' => 'No payroll records found. Run payroll aggregation cron jobs first.',
            'affected_rows' => 0
        ]);
    }

} catch (Exception $e) {
    error_log('Error in update_allowance.php: ' . $e->getMessage());
    // Clear any buffered output
    if (ob_get_length()) {
        ob_end_clean();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

exit;
