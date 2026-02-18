<?php
// api/qr_clock.php - Simple QR clock-in/out without session requirement
try {
    header('Content-Type: application/json');
    error_reporting(E_ALL);
    ini_set('display_errors', 0);

    require_once __DIR__ . '/../../conn/db_connection.php';

    $action = $_POST['action'] ?? 'in'; // 'in' or 'out'
    $employeeId = intval($_POST['employee_id'] ?? 0);
    $employeeCode = $_POST['employee_code'] ?? '';

    if (!$employeeId) {
        echo json_encode(['success' => false, 'message' => 'Missing employee ID']);
        exit();
    }

    // Get employee info
    $empStmt = mysqli_prepare($db, "SELECT id, first_name, last_name, employee_code, branch_id FROM employees WHERE id = ? AND status = 'Active' LIMIT 1");
    mysqli_stmt_bind_param($empStmt, 'i', $employeeId);
    mysqli_stmt_execute($empStmt);
    $empResult = mysqli_stmt_get_result($empStmt);
    $employee = mysqli_fetch_assoc($empResult);
    mysqli_stmt_close($empStmt);

    if (!$employee) {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        exit();
    }

    // Use employee's assigned branch or default
    $branchId = $employee['branch_id'] ?? null;
    $branchName = 'Main Office';
    if ($branchId) {
        $bStmt = mysqli_prepare($db, "SELECT branch_name FROM branches WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($bStmt, 'i', $branchId);
        mysqli_stmt_execute($bStmt);
        $bResult = mysqli_stmt_get_result($bStmt);
        if ($bRow = mysqli_fetch_assoc($bResult)) {
            $branchName = $bRow['branch_name'];
        }
        mysqli_stmt_close($bStmt);
    }

    $empName = $employee['first_name'] . ' ' . $employee['last_name'];

    if ($action === 'in') {
        // Check if already clocked in
        $checkSql = "SELECT id FROM attendance WHERE employee_id = ? AND attendance_date = CURDATE() AND time_in IS NOT NULL AND time_out IS NULL";
        $stmt = mysqli_prepare($db, $checkSql);
        mysqli_stmt_bind_param($stmt, "i", $employeeId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            mysqli_stmt_close($stmt);
            // Already clocked in - return success with message to trigger clock-out
            echo json_encode(['success' => false, 'message' => 'Already clocked in', 'already_in' => true]);
            exit();
        }
        mysqli_stmt_close($stmt);
        
        // Simple insert with just required fields
        $insertSql = "INSERT INTO attendance (employee_id, branch_name, attendance_date, time_in, status) 
                       VALUES (?, ?, CURDATE(), NOW(), 'Present')";
        $insertStmt = mysqli_prepare($db, $insertSql);
        mysqli_stmt_bind_param($insertStmt, "is", $employeeId, $branchName);
        
        if (mysqli_stmt_execute($insertStmt)) {
            $timeIn = date('h:i A');
            echo json_encode([
                'success' => true,
                'message' => "$empName time-in recorded at $timeIn",
                'time_in' => $timeIn
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($db)]);
        }
        mysqli_stmt_close($insertStmt);
        
    } else { // clock out
        // Find open attendance record
        $findSql = "SELECT id, time_in FROM attendance 
                    WHERE employee_id = ? AND attendance_date = CURDATE() 
                    AND time_in IS NOT NULL AND time_out IS NULL 
                    ORDER BY time_in DESC LIMIT 1";
        $findStmt = mysqli_prepare($db, $findSql);
        mysqli_stmt_bind_param($findStmt, 'i', $employeeId);
        mysqli_stmt_execute($findStmt);
        $findResult = mysqli_stmt_get_result($findStmt);
        $row = mysqli_fetch_assoc($findResult);
        mysqli_stmt_close($findStmt);
        
        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'No active time-in found']);
            exit();
        }
        
        $attendanceId = $row['id'];
        
        // Update with time-out
        $updateSql = "UPDATE attendance SET time_out = NOW() WHERE id = ?";
        $updateStmt = mysqli_prepare($db, $updateSql);
        mysqli_stmt_bind_param($updateStmt, 'i', $attendanceId);
        
        if (mysqli_stmt_execute($updateStmt)) {
            $timeOut = date('h:i A');
            echo json_encode([
                'success' => true,
                'message' => "$empName time-out recorded at $timeOut",
                'time_out' => $timeOut
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to record time-out']);
        }
        mysqli_stmt_close($updateStmt);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
