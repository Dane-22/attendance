<?php
// employee/select_employee.php
session_start();

// ===== SET PHILIPPINE TIME ZONE =====
date_default_timezone_set('Asia/Manila'); // Philippine Time (UTC+8)

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

require('../conn/db_connection.php');

$employeeName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$employeeCode = $_SESSION['employee_code'];
$position = $_SESSION['position'] ?? 'Employee';
$userRole = $_SESSION['role'] ?? 'Employee';

// DEBUG: Log role for debugging
error_log("DEBUG select_employee.php: User Role = '$userRole'");

// Get current PHILIPPINE time
$currentTime = date('H:i'); // Philippine time
$cutoffTime = '09:00'; // 9 AM cutoff (Philippine time)

// Determine if we're before or after cutoff time
$isBeforeCutoff = $currentTime < $cutoffTime;

// ===== RATE LIMITER CONFIGURATION =====
// COMMENT OUT MUNA ANG RATE LIMITER PARA MA-TEST
$rateLimitEnabled = false; // CHANGE TO false PARA I-DISABLE MUNA
$rateLimitWindow = 60; // 60 seconds
$rateLimitMaxRequests = 30; // Maximum requests per window

function checkRateLimit() {
    global $rateLimitEnabled, $rateLimitWindow, $rateLimitMaxRequests;
    
    if (!$rateLimitEnabled) return true;
    
    // Huwag gamitin ang session_start() dito, naka-start na sa taas
    $currentTime = time();
    $userId = $_SESSION['employee_code'] ?? 'anonymous';
    $rateLimitKey = "ratelimit_$userId";
    
    if (!isset($_SESSION[$rateLimitKey])) {
        $_SESSION[$rateLimitKey] = [
            'count' => 1,
            'window_start' => $currentTime
        ];
        return true;
    }
    
    $rateData = $_SESSION[$rateLimitKey];
    
    // Reset if window has passed
    if ($currentTime - $rateData['window_start'] > $rateLimitWindow) {
        $_SESSION[$rateLimitKey] = [
            'count' => 1,
            'window_start' => $currentTime
        ];
        return true;
    }
    
    // Check if limit exceeded
    if ($rateData['count'] >= $rateLimitMaxRequests) {
        return false;
    }
    
    // Increment count
    $_SESSION[$rateLimitKey]['count']++;
    return true;
}

// ===== BRANCH MANAGEMENT ACTIONS (INTEGRATED) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['branch_action'])) {
        header('Content-Type: application/json');
        
        // DEBUG version - force admin access
        $isAdmin = true; // Force true for debugging
        
        if ($_POST['branch_action'] === 'add_branch') {
            if (!$isAdmin) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit();
            }
            $branch_name = isset($_POST['branch_name']) ? trim($_POST['branch_name']) : '';
            if (empty($branch_name) || strlen($branch_name) < 2 || strlen($branch_name) > 255) {
                echo json_encode(['success' => false, 'message' => 'Branch name must be 2-255 characters']);
                exit();
            }
            $checkQuery = "SELECT id FROM branches WHERE branch_name = ?";
            $checkStmt = mysqli_prepare($db, $checkQuery);
            mysqli_stmt_bind_param($checkStmt, 's', $branch_name);
            mysqli_stmt_execute($checkStmt);
            if (mysqli_stmt_get_result($checkStmt)->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Branch already exists']);
                exit();
            }
            $insertQuery = "INSERT INTO branches (branch_name, created_at) VALUES (?, NOW())";
            $insertStmt = mysqli_prepare($db, $insertQuery);
            mysqli_stmt_bind_param($insertStmt, 's', $branch_name);
            if (mysqli_stmt_execute($insertStmt)) {
                echo json_encode(['success' => true, 'message' => 'Branch added successfully', 'branch_id' => mysqli_insert_id($db), 'branch_name' => $branch_name]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error adding branch: ' . mysqli_error($db)]);
            }
            exit();
        }
        
        if ($_POST['branch_action'] === 'delete_branch') {
            if (!$isAdmin) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit();
            }
            $branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
            if ($branch_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid branch ID']);
                exit();
            }
            $getBranchQuery = "SELECT branch_name FROM branches WHERE id = ?";
            $getBranchStmt = mysqli_prepare($db, $getBranchQuery);
            mysqli_stmt_bind_param($getBranchStmt, 'i', $branch_id);
            mysqli_stmt_execute($getBranchStmt);
            $branchResult = mysqli_stmt_get_result($getBranchStmt);
            if ($branchResult->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'Branch not found']);
                exit();
            }
            $branchRow = mysqli_fetch_assoc($branchResult);
            $branch_name = $branchRow['branch_name'];
            $checkEmployeesQuery = "SELECT COUNT(*) as count FROM employees WHERE branch_name = ? AND status = 'Active'";
            $checkEmployeesStmt = mysqli_prepare($db, $checkEmployeesQuery);
            mysqli_stmt_bind_param($checkEmployeesStmt, 's', $branch_name);
            mysqli_stmt_execute($checkEmployeesStmt);
            $employeeCount = mysqli_fetch_assoc(mysqli_stmt_get_result($checkEmployeesStmt));
            if ($employeeCount['count'] > 0) {
                echo json_encode(['success' => false, 'message' => "Cannot delete branch with active employees. ({$employeeCount['count']} assigned)"]);
                exit();
            }
            $deleteQuery = "DELETE FROM branches WHERE id = ?";
            $deleteStmt = mysqli_prepare($db, $deleteQuery);
            mysqli_stmt_bind_param($deleteStmt, 'i', $branch_id);
            if (mysqli_stmt_execute($deleteStmt)) {
                echo json_encode(['success' => true, 'message' => "Branch '{$branch_name}' deleted successfully"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error deleting branch: ' . mysqli_error($db)]);
            }
            exit();
        }
    }
}

// Get available branches from branches table
$branchesQuery = "SELECT id, branch_name FROM branches WHERE is_active = 1 ORDER BY branch_name ASC";
$branchesResult = mysqli_query($db, $branchesQuery);
$branches = [];
while ($row = mysqli_fetch_assoc($branchesResult)) {
    $branches[] = ['id' => $row['id'], 'branch_name' => $row['branch_name']];
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // TEMPORARY: COMMENT OUT RATE LIMITER FOR TESTING
    // if (!checkRateLimit()) {
    //     echo json_encode(['success' => false, 'message' => 'Rate limit exceeded. Please wait a minute.']);
    //     exit();
    // }

    if ($_POST['action'] === 'load_employees') {
        $branch = $_POST['branch'] ?? '';
        $showMarked = $_POST['show_marked'] ?? 'false';
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $perPage = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;
        
        // Validate pagination parameters
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage)); // Limit to 100 per page max
        
        $offset = ($page - 1) * $perPage;

        if (empty($branch)) {
            echo json_encode(['success' => false, 'message' => 'Branch is required']);
            exit();
        }

        // Check if cutoff time has passed for today (Philippine time)
        $today = date('Y-m-d'); // Philippine date
        $checkCutoffQuery = "SELECT 1 FROM attendance WHERE attendance_date = ? AND auto_absent_applied = 1 LIMIT 1";
        $checkStmt = mysqli_prepare($db, $checkCutoffQuery);
        mysqli_stmt_bind_param($checkStmt, 's', $today);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        $cutoffApplied = mysqli_num_rows($checkResult) > 0;

        if (!$cutoffApplied && !$isBeforeCutoff) {
            // Apply automatic absent for unmarked employees
            applyAutoAbsent($db, $today);
        }

        // DEBUG: Log parameters
        error_log("DEBUG: Loading employees - branch: $branch, page: $page, perPage: $perPage, offset: $offset");
        
        try {
            // Build base query for counting - SIMPLIFIED VERSION
            if ($showMarked === 'true') {
                // Show ALL employees with their attendance status
                $countQuery = "SELECT COUNT(*) as total
                              FROM employees e
                              WHERE e.status = 'Active'";
            } else {
                // Show ONLY employees WITHOUT attendance (before cutoff) or with specific status
                if ($isBeforeCutoff) {
                    // Before cutoff: show only unmarked employees (walang attendance OR present)
                    $countQuery = "SELECT COUNT(*) as total
                                  FROM employees e
                                  LEFT JOIN attendance a ON e.id = a.employee_id AND a.attendance_date = CURDATE()
                                  WHERE e.status = 'Active' AND (a.id IS NULL)";
                } else {
                    // After cutoff: show only employees not marked as Present
                    $countQuery = "SELECT COUNT(*) as total
                                  FROM employees e
                                  LEFT JOIN attendance a ON e.id = a.employee_id AND a.attendance_date = CURDATE()
                                  WHERE e.status = 'Active' AND (a.id IS NULL OR a.status != 'Present')";
                }
            }
            
            // Execute count query
            error_log("DEBUG: Count Query: $countQuery");
            $countResult = mysqli_query($db, $countQuery);
            
            if (!$countResult) {
                error_log("DEBUG: Count query failed: " . mysqli_error($db));
                echo json_encode(['success' => false, 'message' => 'Count query failed: ' . mysqli_error($db)]);
                exit();
            }
            
            $countRow = mysqli_fetch_assoc($countResult);
            $totalCount = $countRow['total'];
            
            // Calculate total pages
            $totalPages = ceil($totalCount / $perPage);
            error_log("DEBUG: Total count: $totalCount, Total pages: $totalPages");
            
            // SIMPLIFIED MAIN QUERY - Remove complex CASE statements temporarily
            if ($showMarked === 'true') {
                // Show ALL employees with their attendance status
                $query = "SELECT
                            e.id,
                            e.employee_code,
                            e.first_name,
                            e.middle_name,
                            e.last_name,
                            e.position,
                            e.branch_name as original_branch,
                            a.branch_name as logged_branch,
                            a.status as attendance_status,
                            a.is_auto_absent,
                            CASE 
                                WHEN a.id IS NOT NULL THEN 1 
                                ELSE 0 
                            END as has_attendance_today
                          FROM employees e
                          LEFT JOIN attendance a ON e.id = a.employee_id AND a.attendance_date = CURDATE()
                          WHERE e.status = 'Active'
                          ORDER BY e.last_name, e.first_name
                          LIMIT $perPage OFFSET $offset";
            } else {
                // Show ONLY employees WITHOUT attendance (before cutoff) or with specific status
                if ($isBeforeCutoff) {
                    // Before cutoff: show only unmarked employees (walang attendance OR present)
                    $query = "SELECT
                                e.id,
                                e.employee_code,
                                e.first_name,
                                e.middle_name,
                                e.last_name,
                                e.position,
                                e.branch_name as original_branch,
                                a.branch_name as logged_branch,
                                a.status as attendance_status,
                                a.is_auto_absent,
                                CASE 
                                    WHEN a.id IS NOT NULL THEN 1 
                                    ELSE 0 
                                END as has_attendance_today
                              FROM employees e
                              LEFT JOIN attendance a ON e.id = a.employee_id AND a.attendance_date = CURDATE()
                              WHERE e.status = 'Active' AND (a.id IS NULL)
                              ORDER BY e.last_name, e.first_name
                              LIMIT $perPage OFFSET $offset";
                } else {
                    // After cutoff: show only employees not marked as Present
                    $query = "SELECT
                                e.id,
                                e.employee_code,
                                e.first_name,
                                e.middle_name,
                                e.last_name,
                                e.position,
                                e.branch_name as original_branch,
                                a.branch_name as logged_branch,
                                a.status as attendance_status,
                                a.is_auto_absent,
                                CASE 
                                    WHEN a.id IS NOT NULL THEN 1 
                                    ELSE 0 
                                END as has_attendance_today
                              FROM employees e
                              LEFT JOIN attendance a ON e.id = a.employee_id AND a.attendance_date = CURDATE()
                              WHERE e.status = 'Active' AND (a.id IS NULL OR a.status != 'Present')
                              ORDER BY e.last_name, e.first_name
                              LIMIT $perPage OFFSET $offset";
                }
            }
            
            error_log("DEBUG: Main Query: $query");
            $result = mysqli_query($db, $query);
            
            if (!$result) {
                error_log("DEBUG: Main query failed: " . mysqli_error($db));
                echo json_encode(['success' => false, 'message' => 'Query failed: ' . mysqli_error($db)]);
                exit();
            }

            $employees = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $employees[] = [
                    'id' => $row['id'],
                    'employee_code' => $row['employee_code'],
                    'name' => trim($row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'] . ' ' : '') . $row['last_name']),
                    'position' => $row['position'],
                    'original_branch' => $row['original_branch'],
                    'logged_branch' => $row['logged_branch'] ?? $branch, // Use selected branch if null
                    'attendance_status' => $row['attendance_status'] ?? null,
                    'is_auto_absent' => (bool)($row['is_auto_absent'] ?? false),
                    'has_attendance_today' => (bool)($row['has_attendance_today'] ?? false)
                ];
            }

            error_log("DEBUG: Found " . count($employees) . " employees");
            
            echo json_encode([
                'success' => true, 
                'employees' => $employees,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $totalCount,
                    'total_pages' => $totalPages
                ],
                'cutoff_time' => $cutoffTime,
                'current_time' => $currentTime,
                'is_before_cutoff' => $isBeforeCutoff
            ]);
            exit();
            
        } catch (Exception $e) {
            error_log("DEBUG: Exception: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
            exit();
        }
    }

    if ($_POST['action'] === 'mark_present') {
        $employeeId = $_POST['employee_id'] ?? 0;
        $branch = $_POST['branch'] ?? '';
        $status = 'Present'; // Can only mark as present (absent is automatic)

        if (!$employeeId || empty($branch)) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit();
        }

        // Check if employee already has attendance today (Philippine date)
        $checkQuery = "SELECT id, status, is_auto_absent FROM attendance WHERE employee_id = ? AND attendance_date = CURDATE()";
        $checkStmt = mysqli_prepare($db, $checkQuery);
        mysqli_stmt_bind_param($checkStmt, 'i', $employeeId);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);

        if (mysqli_num_rows($checkResult) > 0) {
            $attendance = mysqli_fetch_assoc($checkResult);
            
            // Update to Present (even if was auto-absent)
            $updateQuery = "UPDATE attendance SET status = ?, branch_name = ?, is_auto_absent = 0, updated_at = NOW() WHERE id = ?";
            $updateStmt = mysqli_prepare($db, $updateQuery);
            mysqli_stmt_bind_param($updateStmt, 'ssi', $status, $branch, $attendance['id']);

            if (mysqli_stmt_execute($updateStmt)) {
                echo json_encode([
                    'success' => true, 
                    'message' => "Attendance marked as Present" . ($attendance['is_auto_absent'] ? " (overriding auto-absent)" : ""),
                    'is_update' => true
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update attendance: ' . mysqli_error($db)]);
            }
            exit();
        }

        // Insert new attendance record as Present
        $insertQuery = "INSERT INTO attendance (employee_id, status, attendance_date, branch_name, is_auto_absent, created_at) VALUES (?, ?, CURDATE(), ?, 0, NOW())";
        $insertStmt = mysqli_prepare($db, $insertQuery);
        mysqli_stmt_bind_param($insertStmt, 'iss', $employeeId, $status, $branch);

        if (mysqli_stmt_execute($insertStmt)) {
            echo json_encode([
                'success' => true, 
                'message' => "Employee marked as Present successfully",
                'is_update' => false
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to mark attendance: ' . mysqli_error($db)]);
        }
        exit();
    }
}

// Function to apply automatic absent for unmarked employees after cutoff
function applyAutoAbsent($db, $date) {
    // First, mark that we've applied auto absent for today (Philippine date)
    $checkQuery = "SELECT 1 FROM attendance WHERE attendance_date = ? AND auto_absent_applied = 1 LIMIT 1";
    $checkStmt = mysqli_prepare($db, $checkQuery);
    mysqli_stmt_bind_param($checkStmt, 's', $date);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);
    
    if (mysqli_num_rows($checkResult) == 0) {
        // Get all active employees without attendance today (Philippine date)
        $query = "SELECT e.id, e.branch_name 
                  FROM employees e
                  WHERE e.status = 'Active' 
                  AND NOT EXISTS (
                      SELECT 1 FROM attendance a 
                      WHERE a.employee_id = e.id 
                      AND a.attendance_date = ?
                  )";
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, 's', $date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $absentCount = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            // Insert auto-absent record
            $insertQuery = "INSERT INTO attendance (employee_id, status, attendance_date, branch_name, is_auto_absent, created_at) 
                           VALUES (?, 'Absent', ?, ?, 1, NOW())";
            $insertStmt = mysqli_prepare($db, $insertQuery);
            mysqli_stmt_bind_param($insertStmt, 'iss', $row['id'], $date, $row['branch_name']);
            mysqli_stmt_execute($insertStmt);
            $absentCount++;
        }
        
        // Mark that auto absent has been applied for today (Philippine date)
        $markQuery = "INSERT INTO attendance (employee_id, status, attendance_date, branch_name, auto_absent_applied, created_at) 
                     VALUES (0, 'System', ?, 'System', 1, NOW())";
        $markStmt = mysqli_prepare($db, $markQuery);
        mysqli_stmt_bind_param($markStmt, 's', $date);
        mysqli_stmt_execute($markStmt);
        
        return $absentCount;
    }
    
    return 0;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Select Employee — JAJR Attendance</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    /* CSS STYLES REMAIN THE SAME - NO CHANGES HERE */
    /* All your existing CSS remains exactly as is */
    
    /* Dark Engineering Theme */
    body {
        font-family: 'Inter', sans-serif;
        background: #000000;
        color: #ffffff;
        min-height: 100vh;
        margin: 0;
    }

    .app-shell {
        display: flex;
        min-height: 100vh;
    }

    /* ... ALL YOUR EXISTING CSS STYLES ... */

    /* ===== PAGINATION STYLES ===== */
    .pagination-container {
        background: #1a1a1a;
        border: 1px solid #333;
        border-radius: 8px;
        padding: 14px;
        margin: 14px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }

  <style>
    /* Dark Engineering Theme */
    body {
        font-family: 'Inter', sans-serif;
        background: #000000;
        color: #ffffff;
        min-height: 100vh;
        margin: 0;
    }

    .app-shell {
        display: flex;
        min-height: 100vh;
    }

    .main-content {
        flex: 1;
        padding: 16px;
        overflow-y: auto;
    }

    /* Header */
    .header-card {
        background: #1a1a1a;
        border: 1px solid #333;
        border-radius: 8px;
        padding: 14px 16px;
        margin-bottom: 14px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }

    .header-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .menu-toggle {
        background: none;
        border: none;
        font-size: 20px;
        cursor: pointer;
        padding: 4px;
        color: #FFD700;
    }

    .welcome {
        font-size: 18px;
        font-weight: 700;
        color: #FFD700;
        margin-bottom: 4px;
    }

    .text-sm {
        font-size: 12px;
    }

    .text-gray {
        color: #888;
    }

    /* Time Alert */
    .time-alert {
        background: #1a1a1a;
        border: 1px solid;
        border-radius: 8px;
        padding: 12px 14px;
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .time-alert.before-cutoff {
        border-color: #16a34a;
        background: rgba(22, 163, 74, 0.1);
    }

    .time-alert.after-cutoff {
        border-color: #dc2626;
        background: rgba(220, 38, 38, 0.1);
    }

    .time-alert i {
        font-size: 16px;
    }

    .time-alert.before-cutoff i {
        color: #16a34a;
    }

    .time-alert.after-cutoff i {
        color: #dc2626;
    }

    .time-alert-content {
        flex: 1;
    }

    .time-alert-title {
        font-weight: 600;
        margin-bottom: 4px;
        font-size: 13px;
    }

    .time-alert-message {
        font-size: 12px;
        color: #ccc;
        line-height: 1.4;
    }

    /* Branch Selection */
    .branch-selection {
        background: #1a1a1a;
        border: 1px solid #333;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 14px;
    }

    .branch-title {
        font-size: 16px;
        font-weight: 700;
        color: #FFD700;
        margin-bottom: 12px;
    }

    .branch-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 10px;
    }

    .branch-card {
        background: #2a2a2a;
        border: 2px solid #333;
        border-radius: 6px;
        padding: 12px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        min-height: 70px;
    }

    .branch-card:hover {
        border-color: #FFD700;
        transform: translateY(-2px);
    }

    .branch-card.selected {
        border-color: #FFD700;
        background: #3a3a3a;
    }

    .branch-card.selected::after {
        content: '✓';
        position: absolute;
        top: 4px;
        right: 4px;
        color: #FFD700;
        font-size: 14px;
        font-weight: bold;
    }

    .branch-name {
        font-size: 14px;
        font-weight: 600;
        color: #ffffff;
        margin-bottom: 4px;
    }

    .branch-desc {
        font-size: 11px;
        color: #888;
    }

    /* Filter Options */
    .filter-options-container {
        background: #1a1a1a;
        border: 1px solid #333;
        border-radius: 8px;
        padding: 12px 14px;
        margin-bottom: 14px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .filter-options-title {
        font-size: 13px;
        font-weight: 600;
        color: #ffffff;
    }

    .filter-options {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .toggle-switch {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .toggle-label {
        font-size: 12px;
        color: #888;
    }

    .toggle {
        position: relative;
        display: inline-block;
        width: 40px;
        height: 20px;
    }

    .toggle input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #333;
        transition: .4s;
        border-radius: 34px;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 14px;
        width: 14px;
        left: 3px;
        bottom: 3px;
        background-color: #888;
        transition: .4s;
        border-radius: 50%;
    }

    input:checked + .slider {
        background-color: #FFD700;
    }

    input:checked + .slider:before {
        transform: translateX(20px);
        background-color: #000;
    }

    .view-options {
        display: flex;
        gap: 6px;
    }

    .view-option-btn {
        background: #2a2a2a;
        border: 1px solid #444;
        border-radius: 5px;
        padding: 5px 10px;
        color: #888;
        font-size: 11px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .view-option-btn:hover {
        border-color: #666;
        color: #ccc;
    }

    .view-option-btn.active {
        background: #FFD700;
        border-color: #FFD700;
        color: #000000;
    }

    /* Search Bar */
    .search-container {
        background: #1a1a1a;
        border: 1px solid #333;
        border-radius: 8px;
        padding: 14px;
        margin-bottom: 14px;
    }

    .search-input {
        width: 100%;
        padding: 8px 12px;
        background: #2a2a2a;
        border: 1px solid #444;
        border-radius: 6px;
        color: #ffffff;
        font-size: 13px;
        transition: border-color 0.3s;
    }

    .search-input:focus {
        outline: none;
        border-color: #FFD700;
    }

    .search-input::placeholder {
        color: #888;
    }

    /* View Styles */
    .employee-grid-view {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 12px;
    }

    .employee-list-view {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .employee-details-view {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    /* Employee Card Styles - GRID VIEW */
    .employee-card-grid {
        background: #1a1a1a;
        border: 1px solid #333;
        border-radius: 8px;
        padding: 12px;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        min-height: 140px;
    }

    .employee-card-grid:hover {
        border-color: #444;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }

    .employee-card-grid .employee-info {
        flex: 1;
        margin-bottom: 10px;
    }

    .employee-card-grid .employee-name {
        font-size: 14px;
        font-weight: 600;
        color: #ffffff;
        margin-bottom: 4px;
        line-height: 1.3;
    }

    .employee-card-grid .employee-code {
        font-size: 11px;
        color: #FFD700;
        margin-bottom: 4px;
        font-weight: 500;
    }

    .employee-card-grid .employee-position {
        font-size: 11px;
        color: #888;
        margin-bottom: 6px;
    }

    .employee-card-grid .employee-original-branch {
        font-size: 11px;
        color: #ccc;
        margin-bottom: 8px;
        font-style: italic;
    }

    /* Status and Button Container - GRID VIEW */
    .employee-card-grid .status-button-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-top: auto;
        padding-top: 10px;
        border-top: 1px solid #333;
    }

    /* LIST VIEW */
    .employee-card-list {
        background: #1a1a1a;
        border: 1px solid #333;
        border-radius: 8px;
        padding: 10px 12px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .employee-card-list:hover {
        border-color: #444;
    }

    .employee-card-list .employee-info {
        flex: 1;
        display: grid;
        grid-template-columns: 2fr 1fr 2fr 1fr;
        gap: 12px;
        align-items: center;
    }

    .employee-card-list .employee-name {
        font-size: 13px;
        margin-bottom: 0;
    }

    .employee-card-list .employee-code {
        font-size: 11px;
        margin-bottom: 0;
    }

    .employee-card-list .employee-position {
        font-size: 11px;
        margin-bottom: 0;
    }

    .employee-card-list .status-button-container {
        display: flex;
        align-items: center;
        gap: 10px;
        justify-content: flex-end;
    }

    /* DETAILS VIEW */
    .employee-card-details {
        background: #1a1a1a;
        border: 1px solid #333;
        border-radius: 8px;
        padding: 14px;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
    }

    .employee-card-details:hover {
        border-color: #444;
    }

    .employee-card-details .employee-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 12px;
        padding-bottom: 10px;
        border-bottom: 1px solid #333;
    }

    .employee-card-details .header-left {
        flex: 1;
    }

    .employee-card-details .employee-header .employee-name {
        font-size: 16px;
        font-weight: 700;
        color: #ffffff;
        margin-bottom: 4px;
    }

    .employee-card-details .employee-header .employee-code {
        font-size: 12px;
        color: #FFD700;
        margin-bottom: 0;
    }

    .employee-card-details .employee-body {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 12px;
        margin-bottom: 14px;
    }

    .employee-card-details .detail-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .employee-card-details .detail-label {
        font-size: 10px;
        color: #888;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }

    .employee-card-details .detail-value {
        font-size: 12px;
        color: #ffffff;
        font-weight: 500;
    }

    .employee-card-details .action-buttons {
        display: flex;
        justify-content: flex-end;
        margin-top: 10px;
    }

    .employee-card-details .action-buttons button {
        min-width: 140px;
    }

    /* Status Badge - COMMON */
    .employee-status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        min-width: 80px;
        text-align: center;
        height: 24px;
    }

    .status-available {
        background: #FFD700;
        color: #000;
        border: 1px solid #FFD700;
    }

    .status-present {
        background: #16a34a;
        color: #ffffff;
        border: 1px solid #16a34a;
    }

    .status-absent-auto {
        background: #dc2626;
        color: #ffffff;
        border: 1px dashed #ff6b6b;
    }

    .status-absent-manual {
        background: #b91c1c;
        color: #ffffff;
        border: 1px solid #b91c1c;
    }

    /* Button styles - COMMON */
    .btn-present {
        background: #16a34a;
        color: white;
        border: none;
        padding: 6px 14px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
        height: 26px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        white-space: nowrap;
    }

    .btn-present:hover:not(:disabled) {
        background: #15803d;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(22, 163, 74, 0.4);
    }

    .btn-present-late {
        background: #f59e0b;
        color: white;
        border: none;
        padding: 6px 14px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
        height: 26px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        white-space: nowrap;
    }

    .btn-present-late:hover:not(:disabled) {
        background: #d97706;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(245, 158, 11, 0.4);
    }

    .btn-present:disabled,
    .btn-present-late:disabled {
        background: #444;
        color: #888;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    /* Loading and Messages */
    .loading {
        text-align: center;
        padding: 30px;
        color: #888;
        font-size: 13px;
    }

    .no-employees {
        text-align: center;
        padding: 30px;
        color: #888;
        background: #1a1a1a;
        border: 1px solid #333;
        border-radius: 8px;
        font-size: 13px;
    }

    .all-marked {
        text-align: center;
        padding: 30px;
        background: rgba(22, 163, 74, 0.1);
        border: 1px solid #16a34a;
        border-radius: 8px;
        font-size: 13px;
    }

    .success-message {
        background: #16a34a;
        color: #ffffff;
        padding: 8px 12px;
        border-radius: 6px;
        margin-bottom: 12px;
        display: none;
        font-size: 13px;
    }

    .error-message {
        background: #dc2626;
        color: #ffffff;
        padding: 8px 12px;
        border-radius: 6px;
        margin-bottom: 12px;
        display: none;
        font-size: 13px;
    }

    /* MODAL STYLES */
    .modal-backdrop {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.8);
        z-index: 1000;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .modal-backdrop.show {
        display: flex;
    }

    .modal-panel {
        background: #1a1a1a;
        border: 1px solid #333;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    }

    .form-row {
        margin-bottom: 16px;
    }

    .form-row label {
        display: block;
        margin-bottom: 6px;
        font-size: 12px;
        color: #FFD700;
        font-weight: 600;
    }

    .form-row input {
        width: 100%;
        background: transparent;
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 10px 12px;
        border-radius: 6px;
        color: #ffffff;
        font-size: 13px;
        transition: border-color 0.3s;
    }

    .form-row input:focus {
        outline: none;
        border-color: #FFD700;
    }

    .form-row small {
        color: #888;
        font-size: 11px;
        margin-top: 4px;
        display: block;
    }

    /* ===== BRANCH MANAGEMENT STYLES ===== */
    .branch-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }

    .btn-add-branch {
        background: #FFD700 !important;
        color: #0b0b0b !important;
        border: none !important;
        padding: 8px 14px !important;
        border-radius: 6px !important;
        font-weight: 600 !important;
        font-size: 12px !important;
        cursor: pointer !important;
        transition: all 0.3s ease !important;
        display: flex !important;
        align-items: center !important;
        gap: 6px !important;
        white-space: nowrap !important;
    }

    .btn-add-branch:hover {
        background: #FFC800 !important;
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(255, 215, 0, 0.3);
    }

    .btn-remove-branch {
        position: absolute !important;
        top: 6px !important;
        right: 6px !important;
        background: #dc2626 !important;
        color: white !important;
        border: none !important;
        border-radius: 4px !important;
        width: 24px !important;
        height: 24px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        cursor: pointer !important;
        font-size: 14px !important;
        transition: all 0.2s ease !important;
        opacity: 1 !important;
        padding: 0 !important;
    }

    .branch-card:hover .btn-remove-branch {
        opacity: 1 !important;
    }

    .btn-remove-branch:hover {
        background: #b91c1c !important;
        transform: scale(1.1);
    }

    #branchMessage {
        padding: 10px;
        border-radius: 6px;
        margin-bottom: 12px;
        font-size: 12px;
        display: none;
    }

    #branchMessage.success {
        background: rgba(22, 163, 74, 0.2);
        border: 1px solid #16a34a;
        color: #16a34a;
        display: block;
    }

    #branchMessage.error {
        background: rgba(220, 38, 38, 0.2);
        border: 1px solid #dc2626;
        color: #dc2626;
        display: block;
    }

    /* ===== PAGINATION STYLES ===== */
    .pagination-container {
        background: #1a1a1a;
        border: 1px solid #333;
        border-radius: 8px;
        padding: 14px;
        margin: 14px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }

    .pagination-info {
        font-size: 12px;
        color: #888;
    }

    .pagination-info strong {
        color: #FFD700;
    }

    .pagination-controls {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .page-size-selector {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .page-size-label {
        font-size: 12px;
        color: #888;
    }

    .page-size-select {
        background: #2a2a2a;
        border: 1px solid #444;
        border-radius: 6px;
        padding: 4px 8px;
        color: #ffffff;
        font-size: 12px;
        cursor: pointer;
        min-width: 60px;
    }

    .page-size-select:focus {
        outline: none;
        border-color: #FFD700;
    }

    .pagination-buttons {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .page-btn {
        background: #2a2a2a;
        border: 1px solid #444;
        border-radius: 6px;
        padding: 4px 10px;
        color: #ffffff;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
        min-width: 30px;
        text-align: center;
    }

    .page-btn:hover:not(:disabled):not(.active) {
        border-color: #666;
        background: #3a3a3a;
    }

    .page-btn.active {
        background: #FFD700;
        border-color: #FFD700;
        color: #000000;
        font-weight: 600;
    }

    .page-btn:disabled {
        background: #222;
        border-color: #333;
        color: #555;
        cursor: not-allowed;
    }

    .page-dots {
        color: #888;
        padding: 0 4px;
        font-size: 12px;
    }

    .page-jump {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-left: 8px;
    }

    .page-jump-input {
        background: #2a2a2a;
        border: 1px solid #444;
        border-radius: 6px;
        padding: 4px 8px;
        color: #ffffff;
        font-size: 12px;
        width: 50px;
        text-align: center;
    }

    .page-jump-input:focus {
        outline: none;
        border-color: #FFD700;
    }

    .page-jump-btn {
        background: #2a2a2a;
        border: 1px solid #444;
        border-radius: 6px;
        padding: 4px 8px;
        color: #ffffff;
        font-size: 11px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .page-jump-btn:hover {
        border-color: #FFD700;
        color: #FFD700;
    }

    /* Loading animation for pagination */
    .pagination-loading {
        display: inline-block;
        margin-left: 8px;
        color: #FFD700;
    }

    .pagination-loading i {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Responsive */
    @media (max-width: 768px) {
        .main-content {
            padding: 12px;
        }

        .header-card {
            flex-direction: column;
            gap: 10px;
            align-items: flex-start;
            padding: 12px;
        }

        .branch-grid {
            grid-template-columns: 1fr;
        }

        .filter-options-container {
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
            padding: 10px;
        }

        .filter-options {
            justify-content: space-between;
            width: 100%;
            flex-wrap: wrap;
        }

        .employee-grid-view {
            grid-template-columns: 1fr;
            gap: 10px;
        }

        .employee-card-grid {
            min-height: 130px;
            padding: 10px;
        }

        .employee-card-list .employee-info {
            grid-template-columns: 1fr;
            gap: 8px;
        }

        .employee-card-list {
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
        }

        .employee-card-list .status-button-container {
            width: 100%;
            justify-content: space-between;
        }

        .employee-card-details .employee-body {
            grid-template-columns: 1fr;
        }

        .employee-card-details .employee-header {
            flex-direction: column;
            gap: 10px;
            align-items: flex-start;
        }

        .employee-card-details .action-buttons {
            justify-content: center;
        }

        .employee-card-details .action-buttons button {
            width: 100%;
            max-width: 200px;
        }

        .employee-status {
            min-width: 70px;
            font-size: 9px;
            padding: 3px 8px;
            height: 22px;
        }
        
        .btn-present,
        .btn-present-late {
            min-width: 100px;
            height: 22px;
            font-size: 11px;
            padding: 5px 12px;
        }

        /* Responsive pagination */
        .pagination-container {
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
            padding: 12px;
        }

        .pagination-info {
            text-align: center;
            order: 1;
        }

        .pagination-controls {
            flex-direction: column;
            gap: 10px;
            order: 2;
        }

        .page-size-selector {
            justify-content: center;
            width: 100%;
        }

        .pagination-buttons {
            flex-wrap: wrap;
            justify-content: center;
        }

        .page-jump {
            margin-left: 0;
            margin-top: 5px;
            justify-content: center;
            width: 100%;
        }

        .branch-header {
            flex-direction: column;
            align-items: stretch;
        }

        .btn-add-branch {
            width: 100%;
            justify-content: center;
        }

        .modal-panel {
            width: 90% !important;
        }
    }

    @media (max-width: 480px) {
        .view-options {
            flex-wrap: wrap;
            justify-content: center;
        }

        .view-option-btn {
            flex: 1;
            min-width: 70px;
            padding: 4px 8px;
        }

        .branch-name {
            font-size: 13px;
        }

        .branch-desc {
            font-size: 10px;
        }

        .welcome {
            font-size: 16px;
        }

        .employee-name {
            font-size: 13px;
        }

        .employee-code {
            font-size: 10px;
        }

        .employee-position {
            font-size: 10px;
        }
    }
  </style>
  </style>
</head>
<body>
  <div class="app-shell">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="main-content">
      <!-- Success/Error Messages -->
      <div id="successMessage" class="success-message"></div>
      <div id="errorMessage" class="error-message"></div>

      <!-- DEBUG INFO - Press Ctrl+Shift+D to show -->
      <div id="debugInfo" style="background: red; color: white; padding: 10px; margin-bottom: 10px; display: none;">
          Debug Info:<br>
          User Role: "<?php echo $userRole; ?>"<br>
          Position: <?php echo $position; ?><br>
          Time: <?php echo $currentTime; ?> (PH Time)<br>
          Timezone: <?php echo date_default_timezone_get(); ?>
      </div>

      <!-- Header -->
      <div class="header-card">
        <div class="header-left">
          <div>
            <div class="welcome">Select Employee for Attendance</div>
            <div class="text-sm text-gray">
                Employee Code: <strong><?php echo htmlspecialchars($employeeCode); ?></strong> |
                Position: <?php echo htmlspecialchars($position); ?>
            </div>
          </div>
        </div>
        <div class="text-sm text-gray">
            Today (PH): <?php echo date('F d, Y'); ?><br>
            Current Time (PH): <?php echo $currentTime; ?>
        </div>
      </div>

      <!-- Time Alert -->
      <div class="time-alert <?php echo $isBeforeCutoff ? 'before-cutoff' : 'after-cutoff'; ?>">
        <?php if ($isBeforeCutoff): ?>
          <i class="fas fa-clock"></i>
          <div class="time-alert-content">
            <div class="time-alert-title">Before 9:00 AM Cutoff (Philippine Time)</div>
            <div class="time-alert-message">
              Current Philippine Time: <strong><?php echo $currentTime; ?></strong> | 
              Mark employees as Present before 9:00 AM (PH Time). After cutoff, unmarked employees will be automatically marked as Absent.
            </div>
          </div>
        <?php else: ?>
          <i class="fas fa-exclamation-triangle"></i>
          <div class="time-alert-content">
            <div class="time-alert-title">After 9:00 AM Cutoff (Philippine Time)</div>
            <div class="time-alert-message">
              Current Philippine Time: <strong><?php echo $currentTime; ?></strong> | 
              Unmarked employees have been automatically marked as Absent. You can still override to mark as Present (Late).
            </div>
          </div>
        <?php endif; ?>
      </div>

      <!-- Branch Selection -->
      <div class="branch-selection">
        <div class="branch-header">
          <div class="branch-title">Select Deployment Branch</div>
          <!-- DEBUG: Always show Add Branch button -->
          <button class="btn-add-branch" id="addBranchBtn" title="Add new branch">
            <i class="fas fa-plus"></i> Add Branch
          </button>
        </div>
        <div class="branch-grid" id="branchGrid">
          <?php foreach ($branches as $branch): ?>
          <div class="branch-card" data-branch-id="<?php echo htmlspecialchars($branch['id']); ?>" data-branch="<?php echo htmlspecialchars($branch['branch_name']); ?>">
            <!-- DEBUG: Always show delete button -->
            <button class="btn-remove-branch" onclick="removeBranch(<?php echo htmlspecialchars($branch['id']); ?>, '<?php echo htmlspecialchars($branch['branch_name']); ?>')" title="Delete branch">
              <i class="fas fa-times"></i>
            </button>
            <div class="branch-name"><?php echo htmlspecialchars($branch['branch_name']); ?></div>
            <div class="branch-desc">Deploy employees to this branch</div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Add Branch Modal -->
      <div id="addBranchModal" class="modal-backdrop">
        <div class="modal-panel" style="width: 420px;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="margin: 0; color: #FFD700; font-size: 18px;">Add New Branch</h3>
            <button onclick="closeAddBranchModal()" style="background: none; border: none; color: #888; font-size: 24px; cursor: pointer; padding: 0;">
              <i class="fas fa-times"></i>
            </button>
          </div>
          
          <form id="addBranchForm" onsubmit="submitAddBranch(event)">
            <div class="form-row">
              <label style="font-size: 12px; color: #FFD700; font-weight: 600; margin-bottom: 6px; display: block;">Branch Name</label>
              <input 
                type="text" 
                id="branchNameInput" 
                name="branch_name" 
                placeholder="Enter branch name (e.g., Main Office, Branch A)" 
                required 
                style="background: transparent; border: 1px solid rgba(255,255,255,0.04); padding: 0.6rem 0.75rem; border-radius: 8px; color: #ffffff; width: 100%;"
              />
              <small style="color: #888; font-size: 11px; margin-top: 4px; display: block;">Branch names must be unique and 2-255 characters</small>
            </div>

            <div style="display: flex; gap: 8px; margin-top: 16px; justify-content: flex-end;">
              <button type="button" onclick="closeAddBranchModal()" style="background: transparent; border: 1px solid rgba(255,255,255,0.1); color: #888; padding: 0.6rem 1rem; border-radius: 6px; cursor: pointer; font-weight: 600;">
                Cancel
              </button>
              <button type="submit" style="background: #FFD700; border: none; color: #0b0b0b; padding: 0.6rem 1rem; border-radius: 6px; cursor: pointer; font-weight: 600;">
                <i class="fas fa-plus"></i> Add Branch
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Filter Options -->
      <div class="filter-options-container">
        <div class="filter-options-title">Filters:</div>
        <div class="filter-options">
          <div class="toggle-switch">
            <span class="toggle-label">Show All Employees</span>
            <label class="toggle">
              <input type="checkbox" id="showMarkedToggle">
              <span class="slider"></span>
            </label>
          </div>
          
          <div class="view-options">
            <button class="view-option-btn active" data-view="grid" title="Grid View">
              <i class="fas fa-th"></i>
              <span>Grid</span>
            </button>
            <button class="view-option-btn" data-view="list" title="List View">
              <i class="fas fa-list"></i>
              <span>List</span>
            </button>
            <button class="view-option-btn" data-view="details" title="Details View">
              <i class="fas fa-info-circle"></i>
              <span>Details</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Search Bar -->
      <div class="search-container">
        <input type="text" id="searchInput" class="search-input" placeholder="Search employees by name or ID..." disabled>
      </div>

      <!-- Pagination Top -->
      <div id="paginationTop" class="pagination-container" style="display: none;">
        <div class="pagination-info">
          Showing <strong id="paginationFrom">0</strong> to <strong id="paginationTo">0</strong> of <strong id="paginationTotal">0</strong> employees
        </div>
        <div class="pagination-controls">
          <div class="page-size-selector">
            <span class="page-size-label">Show:</span>
            <select id="pageSizeSelect" class="page-size-select" onchange="changePageSize(this.value)">
              <option value="10">10</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
          </div>
          <div id="paginationButtonsTop" class="pagination-buttons">
            <!-- Pagination buttons will be generated here -->
          </div>
        </div>
      </div>

      <!-- Employee List -->
      <div id="employeeContainer">
        <div class="no-employees">
          <i class="fas fa-users" style="font-size: 36px; color: #444; margin-bottom: 10px;"></i>
          <div>Please select a deployment branch to view all employees</div>
        </div>
      </div>

      <!-- Pagination Bottom -->
      <div id="paginationBottom" class="pagination-container" style="display: none;">
        <div class="pagination-info">
          Page <strong id="currentPage">1</strong> of <strong id="totalPages">1</strong>
        </div>
        <div class="pagination-controls">
          <div class="page-size-selector">
            <span class="page-size-label">Show:</span>
            <select id="pageSizeSelectBottom" class="page-size-select" onchange="changePageSize(this.value)">
              <option value="10">10</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
          </div>
          <div id="paginationButtonsBottom" class="pagination-buttons">
            <!-- Pagination buttons will be generated here -->
          </div>
          <div class="page-jump">
            <input type="number" id="pageJumpInput" class="page-jump-input" min="1" value="1" placeholder="Page">
            <button class="page-jump-btn" onclick="jumpToPage()">Go</button>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script src="../assets/js/sidebar-toggle.js"></script>
  <script>
    let selectedBranch = null;
    let currentView = 'grid';
    let currentEmployees = [];
    let showMarked = false;
    let isBeforeCutoff = <?php echo $isBeforeCutoff ? 'true' : 'false'; ?>;
    let cutoffTime = '<?php echo $cutoffTime; ?>';
    let currentTime = '<?php echo $currentTime; ?>';
    
    // Pagination variables
    let currentPage = 1;
    let perPage = 10;
    let totalEmployees = 0;
    let totalPages = 1;
    let isLoading = false;

    // Initialize view from localStorage
    const savedView = localStorage.getItem('employeeViewPreference');
    if (savedView) {
        currentView = savedView;
        setActiveViewButton(savedView);
    }

    // Initialize page size from localStorage
    const savedPageSize = localStorage.getItem('employeePageSize');
    if (savedPageSize) {
        perPage = parseInt(savedPageSize);
        document.getElementById('pageSizeSelect').value = perPage;
        document.getElementById('pageSizeSelectBottom').value = perPage;
    }

    // Branch selection
    document.querySelectorAll('.branch-card').forEach(card => {
      card.addEventListener('click', function() {
        // Remove selected class from all cards
        document.querySelectorAll('.branch-card').forEach(c => c.classList.remove('selected'));

        // Add selected class to clicked card
        this.classList.add('selected');
        selectedBranch = this.dataset.branch;

        // Enable search
        document.getElementById('searchInput').disabled = false;

        // Reset to page 1 when branch changes
        currentPage = 1;
        
        // Load employees
        loadEmployees(selectedBranch, showMarked, currentPage, perPage);
      });
    });

    // Toggle for showing marked employees
    document.getElementById('showMarkedToggle').addEventListener('change', function() {
        showMarked = this.checked;
        // Reset to page 1 when filter changes
        currentPage = 1;
        if (selectedBranch) {
            loadEmployees(selectedBranch, showMarked, currentPage, perPage);
        }
    });

    // View options selection
    document.querySelectorAll('.view-option-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const view = this.dataset.view;
            if (view !== currentView) {
                currentView = view;
                localStorage.setItem('employeeViewPreference', view);
                setActiveViewButton(view);
                renderEmployees(currentEmployees);
            }
        });
    });

    function setActiveViewButton(view) {
        document.querySelectorAll('.view-option-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.view === view) {
                btn.classList.add('active');
            }
        });
    }

    // Search functionality
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        filterAndRenderEmployees(searchTerm);
    });

    function filterAndRenderEmployees(searchTerm = '') {
        if (searchTerm) {
            const filteredEmployees = currentEmployees.filter(employee => {
                const name = employee.name.toLowerCase();
                const code = employee.employee_code.toLowerCase();
                const position = employee.position.toLowerCase();
                return name.includes(searchTerm) || 
                       code.includes(searchTerm) || 
                       position.includes(searchTerm);
            });
            renderEmployees(filteredEmployees);
        } else {
            renderEmployees(currentEmployees);
        }
    }

    // Load employees function with pagination - FIXED VERSION
    function loadEmployees(branch, showMarked = false, page = 1, perPage = 10) {
      if (isLoading) return;
      
      const container = document.getElementById('employeeContainer');
      container.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin" style="font-size: 18px; margin-bottom: 10px;"></i><div>Loading employees...</div></div>';
      
      // Show loading in pagination
      showPaginationLoading(true);

      isLoading = true;

      const formData = new FormData();
      formData.append('action', 'load_employees');
      formData.append('branch', branch);
      formData.append('show_marked', showMarked.toString());
      formData.append('page', page);
      formData.append('per_page', perPage);

      console.log('DEBUG: Loading employees - Branch:', branch, 'Page:', page, 'Per Page:', perPage);

      fetch('select_employee.php', {
        method: 'POST',
        body: formData
      })
      .then(response => {
        console.log('DEBUG: Response status:', response.status);
        if (!response.ok) {
          throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        console.log('DEBUG: Response data:', data);
        if (data.success) {
          currentEmployees = data.employees;
          isBeforeCutoff = data.is_before_cutoff;
          currentTime = data.current_time;
          cutoffTime = data.cutoff_time;
          
          // Update time display in header
          updateTimeDisplay();
          
          // Update pagination info
          if (data.pagination) {
            currentPage = data.pagination.page;
            perPage = data.pagination.per_page;
            totalEmployees = data.pagination.total;
            totalPages = data.pagination.total_pages;
            
            console.log('DEBUG: Pagination info:', data.pagination);
            // Update pagination controls
            updatePaginationControls();
          }
          
          renderEmployees(currentEmployees);
        } else {
          console.error('DEBUG: Server returned error:', data.message);
          container.innerHTML = '<div class="no-employees"><i class="fas fa-exclamation-triangle" style="font-size: 36px; color: #dc2626; margin-bottom: 10px;"></i><div>Error: ' + data.message + '</div><div style="font-size: 11px; margin-top: 10px; color: #888;">Please check browser console for details</div></div>';
          hidePagination();
        }
      })
      .catch(error => {
        console.error('DEBUG: Fetch error:', error);
        container.innerHTML = '<div class="no-employees"><i class="fas fa-exclamation-triangle" style="font-size: 36px; color: #dc2626; margin-bottom: 10px;"></i><div>Failed to load employees</div><div style="font-size: 11px; margin-top: 10px; color: #888;">Error: ' + error.message + '</div><div style="font-size: 11px; margin-top: 5px; color: #888;">Check browser console (F12) for details</div></div>';
        hidePagination();
      })
      .finally(() => {
        isLoading = false;
        showPaginationLoading(false);
      });
    }

    // Function to update time display
    function updateTimeDisplay() {
      const timeAlert = document.querySelector('.time-alert');
      const timeAlertContent = document.querySelector('.time-alert-content');
      
      if (timeAlert && timeAlertContent) {
        if (isBeforeCutoff) {
          timeAlert.className = 'time-alert before-cutoff';
          timeAlert.querySelector('i').className = 'fas fa-clock';
          document.querySelector('.time-alert-title').textContent = 'Before 9:00 AM Cutoff (Philippine Time)';
          document.querySelector('.time-alert-message').innerHTML = `
            Current Philippine Time: <strong>${currentTime}</strong> | 
            Mark employees as Present before 9:00 AM (PH Time). After cutoff, unmarked employees will be automatically marked as Absent.
          `;
        } else {
          timeAlert.className = 'time-alert after-cutoff';
          timeAlert.querySelector('i').className = 'fas fa-exclamation-triangle';
          document.querySelector('.time-alert-title').textContent = 'After 9:00 AM Cutoff (Philippine Time)';
          document.querySelector('.time-alert-message').innerHTML = `
            Current Philippine Time: <strong>${currentTime}</strong> | 
            Unmarked employees have been automatically marked as Absent. You can still override to mark as Present (Late).
          `;
        }
      }
    }

    // Function to update pagination controls
    function updatePaginationControls() {
      if (totalEmployees === 0 || totalPages === 1) {
        hidePagination();
        return;
      }
      
      showPagination();
      
      // Calculate display range
      const from = Math.min((currentPage - 1) * perPage + 1, totalEmployees);
      const to = Math.min(currentPage * perPage, totalEmployees);
      
      // Update pagination info
      document.getElementById('paginationFrom').textContent = from;
      document.getElementById('paginationTo').textContent = to;
      document.getElementById('paginationTotal').textContent = totalEmployees;
      document.getElementById('currentPage').textContent = currentPage;
      document.getElementById('totalPages').textContent = totalPages;
      document.getElementById('pageJumpInput').value = currentPage;
      
      // Generate pagination buttons
      generatePaginationButtons('paginationButtonsTop');
      generatePaginationButtons('paginationButtonsBottom');
    }
    
    function generatePaginationButtons(containerId) {
      const container = document.getElementById(containerId);
      let html = '';
      
      // Previous button
      html += `<button class="page-btn" onclick="goToPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
                <i class="fas fa-chevron-left"></i>
              </button>`;
      
      // First page
      html += `<button class="page-btn ${currentPage === 1 ? 'active' : ''}" onclick="goToPage(1)">1</button>`;
      
      // Ellipsis if needed
      if (currentPage > 3) {
        html += '<span class="page-dots">...</span>';
      }
      
      // Pages around current page
      for (let i = Math.max(2, currentPage - 1); i <= Math.min(totalPages - 1, currentPage + 1); i++) {
        if (i > 1 && i < totalPages) {
          html += `<button class="page-btn ${currentPage === i ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>`;
        }
      }
      
      // Ellipsis if needed
      if (currentPage < totalPages - 2) {
        html += '<span class="page-dots">...</span>';
      }
      
      // Last page (if not first page)
      if (totalPages > 1) {
        html += `<button class="page-btn ${currentPage === totalPages ? 'active' : ''}" onclick="goToPage(${totalPages})">${totalPages}</button>`;
      }
      
      // Next button
      html += `<button class="page-btn" onclick="goToPage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
                <i class="fas fa-chevron-right"></i>
              </button>`;
      
      container.innerHTML = html;
    }
    
    function goToPage(page) {
      if (page < 1 || page > totalPages || page === currentPage || isLoading) return;
      
      currentPage = page;
      if (selectedBranch) {
        loadEmployees(selectedBranch, showMarked, currentPage, perPage);
      }
      
      // Scroll to top of employee container
      document.getElementById('employeeContainer').scrollIntoView({ behavior: 'smooth' });
    }
    
    function jumpToPage() {
      const pageInput = document.getElementById('pageJumpInput');
      let page = parseInt(pageInput.value);
      
      if (isNaN(page) || page < 1 || page > totalPages) {
        pageInput.value = currentPage;
        return;
      }
      
      goToPage(page);
    }
    
   
    
    function changePageSize(newSize) {
      perPage = parseInt(newSize);
      currentPage = 1; // Reset to first page when changing page size
      
      // Save to localStorage
      localStorage.setItem('employeePageSize', perPage);
      
      // Update both select elements
      document.getElementById('pageSizeSelect').value = perPage;
      document.getElementById('pageSizeSelectBottom').value = perPage;
      
      if (selectedBranch) {
        loadEmployees(selectedBranch, showMarked, currentPage, perPage);
      }
    }
    
    function showPagination() {
      document.getElementById('paginationTop').style.display = 'flex';
      document.getElementById('paginationBottom').style.display = 'flex';
    }
    
    function hidePagination() {
      document.getElementById('paginationTop').style.display = 'none';
      document.getElementById('paginationBottom').style.display = 'none';
    }
    
    function showPaginationLoading(show) {
      const loadingHTML = '<span class="pagination-loading"><i class="fas fa-spinner fa-spin"></i></span>';
      
      if (show) {
        document.getElementById('paginationFrom').innerHTML += loadingHTML;
      } else {
        const fromEl = document.getElementById('paginationFrom');
        const loadingEl = fromEl.querySelector('.pagination-loading');
        if (loadingEl) {
          loadingEl.remove();
        }
      }
    }

    // Render employees based on current view
    function renderEmployees(employees) {
      const container = document.getElementById('employeeContainer');

      // Filter out Present employees when showMarked is false
      let employeesToShow = employees;
      if (!showMarked) {
        employeesToShow = employees.filter(emp => 
          !(emp.attendance_status === 'Present')
        );
      }

      if (employeesToShow.length === 0) {
        if (showMarked) {
          container.innerHTML = '<div class="no-employees"><i class="fas fa-users" style="font-size: 36px; color: #444; margin-bottom: 10px;"></i><div>No employees found for this branch</div></div>';
        } else if (isBeforeCutoff) {
          container.innerHTML = '<div class="all-marked"><i class="fas fa-check-circle" style="font-size: 36px; color: #16a34a; margin-bottom: 10px;"></i><div>All employees have been marked as Present!</div><div class="text-sm text-gray" style="margin-top: 8px;">Unmarked employees will be automatically marked as Absent after 9:00 AM (PH Time)</div></div>';
        } else {
          container.innerHTML = '<div class="all-marked"><i class="fas fa-check-circle" style="font-size: 36px; color: #16a34a; margin-bottom: 10px;"></i><div>All employees have been processed!</div><div class="text-sm text-gray" style="margin-top: 8px;">Unmarked employees have been automatically marked as Absent</div></div>';
        }
        return;
      }

      // Check if mobile and force list view
      const isMobile = window.innerWidth <= 768;
      const viewToUse = isMobile ? 'list' : currentView;

      let html = `<div class="employee-${viewToUse}-view">`;

      employeesToShow.forEach(employee => {
        let statusClass = 'status-available';
        let statusText = 'Available';
        let currentStatus = '';
        let isAutoAbsent = employee.is_auto_absent;
        
        if (employee.has_attendance_today) {
          if (employee.attendance_status === 'Present') {
            statusClass = 'status-present';
            statusText = 'Present';
            currentStatus = 'present';
          } else if (employee.attendance_status === 'Absent') {
            if (employee.is_auto_absent) {
              statusClass = 'status-absent-auto';
              statusText = 'Auto-Absent';
              currentStatus = 'absent-auto';
            } else {
              statusClass = 'status-absent-manual';
              statusText = 'Absent';
              currentStatus = 'absent-manual';
            }
          }
        }

        // Determine button text and class based on time and status
        let buttonText = 'Mark Present';
        let buttonClass = 'btn-present';
        let isDisabled = false;
        let buttonTitle = 'Mark as Present';
        let buttonAction = 'markPresent';

        if (employee.attendance_status === 'Present') {
          buttonText = 'Transfer';
          buttonClass = 'btn-transfer';
          buttonTitle = 'Transfer to different branch';
          buttonAction = 'transferEmployee';
        } else if (!isBeforeCutoff) {
          buttonText = 'Mark as Present (Late)';
          buttonClass = 'btn-present-late';
          buttonTitle = 'Mark as Present (Late override)';
        }

        if (viewToUse === 'grid') {
          // Add branch history indicator
          let branchHistory = '';
          if (employee.attendance_status === 'Present' && employee.logged_branch) {
            branchHistory = `<div class="branch-history" style="font-size: 0.75rem; color: #FFD000; background: #0B0B0B; padding: 2px 6px; border-radius: 4px; margin-top: 4px; display: inline-block;">
              <i class="fas fa-map-marker-alt"></i> Current: ${employee.logged_branch}
            </div>`;
          }
          
          html += `
            <div class="employee-card-grid" id="employee-${employee.id}">
              <div class="employee-info">
                <div class="employee-name">${employee.name}</div>
                <div class="employee-code">ID: ${employee.employee_code}</div>
                <div class="employee-position">${employee.position}</div>
                <div class="employee-original-branch">Original Branch: ${employee.original_branch || 'Not specified'}</div>
                ${branchHistory}
              </div>
              <div class="status-button-container">
                <span class="employee-status ${statusClass}">${statusText}</span>
                <button class="${buttonClass}" 
                        onclick="${buttonAction}(${employee.id}, '${employee.name.replace(/'/g, "\\'")}')"
                        ${isDisabled ? 'disabled' : ''}
                        title="${buttonTitle}">
                  <i class="fas fa-${buttonAction === 'transferEmployee' ? 'exchange-alt' : 'check-circle'}"></i> ${buttonText}
                </button>
              </div>
            </div>
          `;
        } else if (viewToUse === 'list') {
          // Add branch history indicator for list view
          let branchHistory = '';
          if (employee.attendance_status === 'Present' && employee.logged_branch) {
            branchHistory = `<div class="branch-history" style="font-size: 0.75rem; color: #FFD000; background: #0B0B0B; padding: 2px 6px; border-radius: 4px; margin-left: 8px; display: inline-block;">
              <i class="fas fa-map-marker-alt"></i> Current: ${employee.logged_branch}
            </div>`;
          }
          
          html += `
            <div class="employee-card-list" id="employee-${employee.id}">
              <div class="employee-info">
                <div class="employee-name">${employee.name}</div>
                <div class="employee-code">${employee.employee_code}</div>
                <div class="employee-position">${employee.position}</div>
                ${branchHistory}
                <div class="status-button-container">
                  <span class="employee-status ${statusClass}">${statusText}</span>
                  <button class="${buttonClass}" 
                          onclick="${buttonAction}(${employee.id}, '${employee.name.replace(/'/g, "\\'")}')"
                          ${isDisabled ? 'disabled' : ''}
                          title="${buttonTitle}">
                    <i class="fas fa-${buttonAction === 'transferEmployee' ? 'exchange-alt' : 'check-circle'}"></i> ${buttonText}
                  </button>
                </div>
              </div>
            </div>
          `;
        } else if (viewToUse === 'details') {
          html += `
            <div class="employee-card-details" id="employee-${employee.id}">
              <div class="employee-header">
                <div class="header-left">
                  <div class="employee-name">${employee.name}</div>
                  <div class="employee-code">Employee Code: ${employee.employee_code}</div>
                </div>
                <span class="employee-status ${statusClass}">${statusText}</span>
              </div>
              <div class="employee-body">
                <div class="detail-item">
                  <div class="detail-label">Position</div>
                  <div class="detail-value">${employee.position}</div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Original Branch</div>
                  <div class="detail-value">${employee.original_branch || 'Not specified'}</div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Current Branch</div>
                  <div class="detail-value">${selectedBranch}</div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Status</div>
                  <div class="detail-value">${employee.has_attendance_today ? 
                    `${employee.attendance_status} ${employee.is_auto_absent ? '(Auto)' : ''}` : 
                    (isBeforeCutoff ? 'Available for Marking' : 'Will be Auto-Absent')
                  }</div>
                </div>
              </div>
              <div class="action-buttons">
                <button class="${buttonClass}" 
                        onclick="markPresent(${employee.id}, '${employee.name.replace(/'/g, "\\'")}')"
                        ${isDisabled ? 'disabled' : ''}
                        title="${buttonTitle}">
                  <i class="fas fa-check-circle"></i> ${buttonText}
                </button>
              </div>
            </div>
          `;
        }
      });

      html += '</div>';
      container.innerHTML = html;
    }

    // MARK PRESENT FUNCTION
    function markPresent(employeeId, employeeName) {
      if (!selectedBranch) {
        showError('Please select a branch first');
        return;
      }

      // Diretso tanggalin sa UI
      const employeeElement = document.getElementById(`employee-${employeeId}`);
      if (employeeElement) {
        employeeElement.style.transition = 'all 0.3s ease';
        employeeElement.style.opacity = '0';
        employeeElement.style.transform = 'translateY(-10px)';
        
        // Wait for animation then remove
        setTimeout(() => {
          employeeElement.remove();
          
          // Update currentEmployees array
          currentEmployees = currentEmployees.filter(emp => emp.id !== employeeId);
          
          // Update total count locally
          totalEmployees = Math.max(0, totalEmployees - 1);
          
          // Recalculate pagination
          const employeesOnPage = currentEmployees.length;
          if (employeesOnPage === 0 && currentPage > 1) {
            // Go to previous page if current page is empty
            goToPage(currentPage - 1);
          } else {
            // Update pagination display
            updatePaginationControls();
            
            // Check if all employees are marked
            if (totalEmployees === 0 && !showMarked) {
              const container = document.getElementById('employeeContainer');
              if (isBeforeCutoff) {
                container.innerHTML = `
                  <div class="all-marked">
                    <i class="fas fa-check-circle" style="font-size: 36px; color: #16a34a; margin-bottom: 10px;"></i>
                    <div>All employees have been marked as Present!</div>
                    <div class="text-sm text-gray" style="margin-top: 8px;">
                      Unmarked employees will be automatically marked as Absent after 9:00 AM (PH Time)
                    </div>
                  </div>
                `;
                hidePagination();
              } else {
                container.innerHTML = `
                  <div class="all-marked">
                    <i class="fas fa-check-circle" style="font-size: 36px; color: #16a34a; margin-bottom: 10px;"></i>
                    <div>All employees have been processed!</div>
                    <div class="text-sm text-gray" style="margin-top: 8px;">
                      Unmarked employees have been automatically marked as Absent
                    </div>
                </div>
                `;
                hidePagination();
              }
            }
          }
        }, 300);
      }

      // Send to server
      const formData = new FormData();
      formData.append('action', 'mark_present');
      formData.append('employee_id', employeeId);
      formData.append('branch', selectedBranch);

      fetch('select_employee.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const timeStatus = isBeforeCutoff ? '' : ' (Late)';
          showSuccess(`${employeeName} marked as Present${timeStatus} successfully!`);
        } else {
          showError(data.message);
          // If server failed, reload to get correct data
          if (selectedBranch) {
            loadEmployees(selectedBranch, showMarked, currentPage, perPage);
          }
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showError('Failed to mark attendance');
        // Reload on error
        if (selectedBranch) {
          loadEmployees(selectedBranch, showMarked, currentPage, perPage);
        }
      });
    }

    // TRANSFER EMPLOYEE FUNCTION
    function transferEmployee(employeeId, employeeName) {
      if (!selectedBranch) {
        showError('Please select a branch first');
        return;
      }

      // Add glow effect to the card
      const employeeElement = document.getElementById(`employee-${employeeId}`);
      if (employeeElement) {
        employeeElement.style.transition = 'all 0.3s ease';
        employeeElement.style.boxShadow = '0 0 20px rgba(255, 208, 0, 0.6)';
        employeeElement.style.transform = 'scale(1.02)';
      }

      // Send to server
      const formData = new FormData();
      formData.append('employee_id', employeeId);
      formData.append('branch_name', selectedBranch);

      fetch('update_deployment.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Remove glow effect and reload
          if (employeeElement) {
            setTimeout(() => {
              employeeElement.style.boxShadow = '';
              employeeElement.style.transform = '';
            }, 1000);
          }
          
          showSuccess(`${employeeName} transferred to ${selectedBranch} successfully!`);
          
          // Reload employees to update the branch history
          setTimeout(() => {
            loadEmployees(selectedBranch, showMarked, currentPage, perPage);
          }, 1500);
        } else {
          // Remove glow effect on error
          if (employeeElement) {
            employeeElement.style.boxShadow = '';
            employeeElement.style.transform = '';
          }
          showError(data.message);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        // Remove glow effect on error
        if (employeeElement) {
          employeeElement.style.boxShadow = '';
          employeeElement.style.transform = '';
        }
        showError('Failed to transfer employee');
      });
    }

    // Message functions
    function showSuccess(message) {
      const el = document.getElementById('successMessage');
      el.textContent = message;
      el.style.display = 'block';
      document.getElementById('errorMessage').style.display = 'none';
      setTimeout(() => el.style.display = 'none', 5000);
    }

    function showError(message) {
      const el = document.getElementById('errorMessage');
      el.textContent = message;
      el.style.display = 'block';
      document.getElementById('successMessage').style.display = 'none';
      setTimeout(() => el.style.display = 'none', 5000);
    }

    // Handle window resize for responsive view switching
    window.addEventListener('resize', function() {
      if (currentEmployees.length > 0) {
        filterAndRenderEmployees(document.getElementById('searchInput').value);
      }
    });

    // Auto-refresh every minute to check cutoff time (Philippine Time)
    setInterval(() => {
      const now = new Date();
      
      // Convert to Philippine Time in JavaScript (UTC+8)
      const utc = now.getTime() + (now.getTimezoneOffset() * 60000);
      const phTime = new Date(utc + (8 * 3600000)); // UTC+8
      
      const hours = phTime.getHours().toString().padStart(2, '0');
      const minutes = phTime.getMinutes().toString().padStart(2, '0');
      currentTime = `${hours}:${minutes}`;
      
      // Check if we just passed cutoff time
      const wasBeforeCutoff = isBeforeCutoff;
      isBeforeCutoff = currentTime < cutoffTime;
      
      if (wasBeforeCutoff && !isBeforeCutoff && selectedBranch) {
        // We just passed cutoff time, reload employees
        loadEmployees(selectedBranch, showMarked, currentPage, perPage);
        
        // Update time alert
        updateTimeDisplay();
      }
    }, 60000); // Check every minute

    // ===== BRANCH MANAGEMENT FUNCTIONS (INTEGRATED) =====
    
    // DEBUG: Force admin access
    const isAdminUser = true; // Force true for debugging
    
    if (isAdminUser && document.getElementById('addBranchBtn')) {
        console.log('DEBUG: Add Branch button found, attaching click handler');
        document.getElementById('addBranchBtn').addEventListener('click', function() {
            console.log('DEBUG: Add Branch button clicked');
            document.getElementById('addBranchModal').classList.add('show');
            document.getElementById('branchNameInput').focus();
        });
    } else {
        console.log('DEBUG: Add Branch button NOT found or isAdminUser is false');
    }

    function closeAddBranchModal() {
        document.getElementById('addBranchModal').classList.remove('show');
        document.getElementById('addBranchForm').reset();
        clearBranchMessage();
    }

    document.getElementById('addBranchModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeAddBranchModal();
        }
    });

    function submitAddBranch(event) {
        event.preventDefault();
        
        const branchName = document.getElementById('branchNameInput').value.trim();
        
        if (!branchName) {
            showBranchMessage('Branch name is required', 'error');
            return;
        }

        if (branchName.length < 2) {
            showBranchMessage('Branch name must be at least 2 characters', 'error');
            return;
        }

        const submitBtn = document.querySelector('#addBranchForm button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';

        const formData = new FormData();
        formData.append('branch_action', 'add_branch');
        formData.append('branch_name', branchName);

        fetch(window.location.pathname, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showBranchMessage('Branch added successfully!', 'success');
                document.getElementById('addBranchForm').reset();
                addBranchCardToUI(data.branch_id, data.branch_name);
                setTimeout(() => {
                    closeAddBranchModal();
                }, 1500);
            } else {
                showBranchMessage(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showBranchMessage('Failed to add branch', 'error');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    }

    function addBranchCardToUI(branchId, branchName) {
        const branchGrid = document.getElementById('branchGrid');
        
        const branchCard = document.createElement('div');
        branchCard.className = 'branch-card';
        branchCard.setAttribute('data-branch-id', branchId);
        branchCard.setAttribute('data-branch', branchName);
        branchCard.innerHTML = `
            ${isAdminUser ? `<button class="btn-remove-branch" onclick="removeBranch(${branchId}, '${branchName.replace(/'/g, "\\'")}')" title="Delete branch">
                <i class="fas fa-times"></i>
            </button>` : ''}
            <div class="branch-name">${branchName}</div>
            <div class="branch-desc">Deploy employees to this branch</div>
        `;
        
        branchGrid.appendChild(branchCard);
        
        branchCard.addEventListener('click', function() {
            selectBranch(this);
        });
    }

    function removeBranch(branchId, branchName) {
        event.stopPropagation();
        
        if (!confirm(`Are you sure you want to delete the branch "${branchName}"?\n\nThis action cannot be undone.`)) {
            return;
        }

        const formData = new FormData();
        formData.append('branch_action', 'delete_branch');
        formData.append('branch_id', branchId);

        const branchCard = document.querySelector(`[data-branch-id="${branchId}"]`);
        const removeBtn = branchCard.querySelector('.btn-remove-branch');
        const originalContent = removeBtn.innerHTML;
        removeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        removeBtn.disabled = true;

        fetch(window.location.pathname, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                branchCard.style.transition = 'all 0.3s ease';
                branchCard.style.opacity = '0';
                branchCard.style.transform = 'scale(0.9)';
                
                setTimeout(() => {
                    branchCard.remove();
                    showGlobalMessage(data.message, 'success');
                    
                    if (selectedBranch === branchName) {
                        selectedBranch = null;
                        document.getElementById('employeeContainer').innerHTML = `
                            <div class="no-employees">
                                <i class="fas fa-users" style="font-size: 36px; color: #444; margin-bottom: 10px;"></i>
                                <div>Branch deleted. Please select another deployment branch</div>
                            </div>
                        `;
                        hidePagination();
                    }
                }, 300);
            } else {
                removeBtn.innerHTML = originalContent;
                removeBtn.disabled = false;
                showGlobalMessage(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            removeBtn.innerHTML = originalContent;
            removeBtn.disabled = false;
            showGlobalMessage('Failed to delete branch', 'error');
        });
    }

    function showBranchMessage(message, type) {
        let messageEl = document.getElementById('branchMessage');
        if (!messageEl) {
            messageEl = document.createElement('div');
            messageEl.id = 'branchMessage';
            document.getElementById('addBranchForm').insertBefore(messageEl, document.getElementById('addBranchForm').firstChild);
        }
        
        messageEl.textContent = message;
        messageEl.className = type;
    }

    function clearBranchMessage() {
        const messageEl = document.getElementById('branchMessage');
        if (messageEl) {
            messageEl.className = '';
            messageEl.textContent = '';
        }
    }

    function showGlobalMessage(message, type) {
        if (type === 'success') {
            showSuccess(message);
        } else {
            showError(message);
        }
    }

    function selectBranch(cardElement) {
        document.querySelectorAll('.branch-card').forEach(c => c.classList.remove('selected'));
        cardElement.classList.add('selected');
        selectedBranch = cardElement.dataset.branch;
        document.getElementById('searchInput').disabled = false;
        // Reset to page 1 when selecting a branch
        currentPage = 1;
        loadEmployees(selectedBranch, showMarked, currentPage, perPage);
    }

    // Attach click handlers to initial branch cards
    document.querySelectorAll('.branch-card').forEach(card => {
        card.addEventListener('click', function() {
            selectBranch(this);
        });
    });

    // DEBUG: Show debug info with keyboard shortcut
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.shiftKey && e.key === 'D') {
            document.getElementById('debugInfo').style.display = document.getElementById('debugInfo').style.display === 'none' ? 'block' : 'none';
        }
    });
  </script>
</body>
</html>