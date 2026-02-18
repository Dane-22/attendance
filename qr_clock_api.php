<?php
/**
 * qr_clock_api.php - QR Code Clock In/Out API
 * 
 * This file should be placed in the root of the attendance system
 * (e.g., /var/www/jajr-project/qr_clock_api.php)
 * 
 * Required: conn/db_connection.php must exist relative to this file
 */

// Set timezone
date_default_timezone_set('Asia/Manila');

// Error handling - ensure JSON output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set JSON header
header('Content-Type: application/json');

// Helper function to send JSON response
function jsonResponse($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

// CORS headers for mobile app access
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Only POST requests allowed']);
}

try {
    // Try to load database connection
    // Assumes this file is in root and db_connection.php is in conn/
    $dbPath = __DIR__ . '/conn/db_connection.php';
    
    if (!file_exists($dbPath)) {
        jsonResponse(['success' => false, 'message' => 'Database configuration not found']);
    }
    
    require_once $dbPath;
    
    // Verify $db connection exists
    if (!isset($db) || !$db) {
        jsonResponse(['success' => false, 'message' => 'Database connection failed']);
    }

    // Get POST parameters
    $action = $_POST['action'] ?? 'in'; // 'in' or 'out'
    $employeeId = intval($_POST['employee_id'] ?? 0);
    $employeeCode = $_POST['employee_code'] ?? '';

    // Validate employee ID
    if (!$employeeId) {
        jsonResponse(['success' => false, 'message' => 'Missing employee ID']);
    }

    // Get employee info (using 'status' column instead of 'is_active')
    $empStmt = mysqli_prepare($db, "SELECT id, first_name, last_name, employee_code, branch_id, status 
                                    FROM employees 
                                    WHERE id = ? 
                                    LIMIT 1");
    
    if (!$empStmt) {
        jsonResponse(['success' => false, 'message' => 'Database prepare error: ' . mysqli_error($db)]);
    }
    
    mysqli_stmt_bind_param($empStmt, 'i', $employeeId);
    mysqli_stmt_execute($empStmt);
    $empResult = mysqli_stmt_get_result($empStmt);
    $employee = mysqli_fetch_assoc($empResult);
    mysqli_stmt_close($empStmt);

    if (!$employee) {
        jsonResponse(['success' => false, 'message' => 'Employee not found']);
    }

    // Check if employee is active (status = 'Active')
    if (isset($employee['status']) && $employee['status'] !== 'Active') {
        jsonResponse(['success' => false, 'message' => 'Employee account is not active']);
    }

    // Get employee's branch name
    $branchId = $employee['branch_id'] ?? null;
    $branchName = 'Main Office';
    
    if ($branchId) {
        $bStmt = mysqli_prepare($db, "SELECT branch_name FROM branches WHERE id = ? LIMIT 1");
        if ($bStmt) {
            mysqli_stmt_bind_param($bStmt, 'i', $branchId);
            mysqli_stmt_execute($bStmt);
            $bResult = mysqli_stmt_get_result($bStmt);
            if ($bRow = mysqli_fetch_assoc($bResult)) {
                $branchName = $bRow['branch_name'];
            }
            mysqli_stmt_close($bStmt);
        }
    }

    $empName = trim($employee['first_name'] . ' ' . $employee['last_name']);

    // Process clock-in
    if ($action === 'in') {
        // Check if already clocked in today (open shift - has time_in but no time_out)
        $checkSql = "SELECT id, time_in FROM attendance 
                     WHERE employee_id = ? 
                     AND attendance_date = CURDATE() 
                     AND time_in IS NOT NULL 
                     AND time_out IS NULL
                     LIMIT 1";
        
        $checkStmt = mysqli_prepare($db, $checkSql);
        if (!$checkStmt) {
            jsonResponse(['success' => false, 'message' => 'Database error checking attendance']);
        }
        
        mysqli_stmt_bind_param($checkStmt, "i", $employeeId);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        $existing = mysqli_fetch_assoc($checkResult);
        mysqli_stmt_close($checkStmt);
        
        if ($existing) {
            // Already clocked in - trigger clock-out automatically
            // Find the open attendance record
            $findSql = "SELECT id, time_in FROM attendance 
                        WHERE employee_id = ? 
                        AND attendance_date = CURDATE() 
                        AND time_in IS NOT NULL 
                        AND time_out IS NULL 
                        ORDER BY time_in DESC 
                        LIMIT 1";
            
            $findStmt = mysqli_prepare($db, $findSql);
            mysqli_stmt_bind_param($findStmt, 'i', $employeeId);
            mysqli_stmt_execute($findStmt);
            $findResult = mysqli_stmt_get_result($findStmt);
            $row = mysqli_fetch_assoc($findResult);
            mysqli_stmt_close($findStmt);
            
            if ($row) {
                $attendanceId = $row['id'];
                
                // Update with time-out
                $updateSql = "UPDATE attendance SET time_out = NOW(), updated_at = NOW() WHERE id = ?";
                $updateStmt = mysqli_prepare($db, $updateSql);
                mysqli_stmt_bind_param($updateStmt, 'i', $attendanceId);
                
                if (mysqli_stmt_execute($updateStmt)) {
                    $timeOut = date('h:i A');
                    mysqli_stmt_close($updateStmt);
                    jsonResponse([
                        'success' => true,
                        'message' => "$empName time-out recorded at $timeOut",
                        'time_out' => $timeOut,
                        'auto_clock_out' => true
                    ]);
                } else {
                    mysqli_stmt_close($updateStmt);
                    jsonResponse(['success' => false, 'message' => 'Failed to record time-out']);
                }
            }
        }
        
        // Insert new time-in record
        $insertSql = "INSERT INTO attendance (employee_id, branch_name, attendance_date, time_in, status, is_overtime_running, is_time_running, total_ot_hrs, created_at) 
                       VALUES (?, ?, CURDATE(), NOW(), 'Present', 0, 0, '0', NOW())";
        
        $insertStmt = mysqli_prepare($db, $insertSql);
        if (!$insertStmt) {
            jsonResponse(['success' => false, 'message' => 'Database error preparing insert: ' . mysqli_error($db)]);
        }
        
        mysqli_stmt_bind_param($insertStmt, "is", $employeeId, $branchName);
        
        if (mysqli_stmt_execute($insertStmt)) {
            $timeIn = date('h:i A');
            mysqli_stmt_close($insertStmt);
            jsonResponse([
                'success' => true,
                'message' => "$empName time-in recorded at $timeIn",
                'time_in' => $timeIn
            ]);
        } else {
            mysqli_stmt_close($insertStmt);
            jsonResponse(['success' => false, 'message' => 'Database error: ' . mysqli_error($db)]);
        }
        
    } else { // clock out
        // Find open attendance record for today
        $findSql = "SELECT id, time_in FROM attendance 
                    WHERE employee_id = ? 
                    AND attendance_date = CURDATE() 
                    AND time_in IS NOT NULL 
                    AND time_out IS NULL 
                    ORDER BY time_in DESC 
                    LIMIT 1";
        
        $findStmt = mysqli_prepare($db, $findSql);
        if (!$findStmt) {
            jsonResponse(['success' => false, 'message' => 'Database error finding attendance record']);
        }
        
        mysqli_stmt_bind_param($findStmt, 'i', $employeeId);
        mysqli_stmt_execute($findStmt);
        $findResult = mysqli_stmt_get_result($findStmt);
        $row = mysqli_fetch_assoc($findResult);
        mysqli_stmt_close($findStmt);
        
        if (!$row) {
            jsonResponse(['success' => false, 'message' => 'No active time-in found. Please clock in first.']);
        }
        
        $attendanceId = $row['id'];
        
        // Update with time-out
        $updateSql = "UPDATE attendance SET time_out = NOW(), updated_at = NOW() WHERE id = ?";
        $updateStmt = mysqli_prepare($db, $updateSql);
        
        if (!$updateStmt) {
            jsonResponse(['success' => false, 'message' => 'Database error preparing update']);
        }
        
        mysqli_stmt_bind_param($updateStmt, 'i', $attendanceId);
        
        if (mysqli_stmt_execute($updateStmt)) {
            $timeOut = date('h:i A');
            mysqli_stmt_close($updateStmt);
            jsonResponse([
                'success' => true,
                'message' => "$empName time-out recorded at $timeOut",
                'time_out' => $timeOut
            ]);
        } else {
            mysqli_stmt_close($updateStmt);
            jsonResponse(['success' => false, 'message' => 'Failed to record time-out: ' . mysqli_error($db)]);
        }
    }
    
} catch (Throwable $e) {
    http_response_code(500);
    jsonResponse(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
