<?php
/**
 * API endpoint to get current attendance status for an employee
 * Used by eng_dashboard.php to validate button states before clock in/out
 */

header('Content-Type: application/json');
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

try {
    require_once __DIR__ . '/../../conn/db_connection.php';
    
    $employeeId = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;
    
    if (!$employeeId) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing employee ID'
        ]);
        exit();
    }
    
    // Get today's attendance record
    $sql = "SELECT id, time_in, time_out, status, branch_name 
            FROM attendance 
            WHERE employee_id = ? AND attendance_date = CURDATE() 
            ORDER BY time_in DESC LIMIT 1";
    
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $employeeId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $record = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    $hasOpenShift = false;
    $shiftId = null;
    $timeIn = null;
    $timeOut = null;
    
    if ($record) {
        $hasOpenShift = !empty($record['time_in']) && empty($record['time_out']);
        $shiftId = $record['id'];
        $timeIn = $record['time_in'];
        $timeOut = $record['time_out'];
    }
    
    echo json_encode([
        'success' => true,
        'hasOpenShift' => $hasOpenShift,
        'shiftId' => $shiftId,
        'timeIn' => $timeIn,
        'timeOut' => $timeOut,
        'status' => $record['status'] ?? null,
        'branchName' => $record['branch_name'] ?? null,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
