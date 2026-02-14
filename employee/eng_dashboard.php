<?php
/**
 * Engineer Dashboard - Attendance Monitoring System
 * Features: Engineer-specific tools, site overview, project tracking
 */

// Start session and include database connection
session_start();
require_once __DIR__ . '/../conn/db_connection.php';

// Check if user is Engineer
$userRole = isset($_SESSION['position']) ? $_SESSION['position'] : '';
if ($userRole !== 'Engineer') {
    header('Location: select_employee.php');
    exit();
}

// Get current user info
$currentUserId = isset($_SESSION['employee_id']) ? $_SESSION['employee_id'] : 0;
$currentUserName = isset($_SESSION['username']) ? $_SESSION['username'] : 'Engineer';
$currentUserAvatar = isset($_SESSION['profile_image']) ? $_SESSION['profile_image'] : '';

// Initialize employee data variables
$employeeId = 0;
$employeeCode = '';

// Initialize variables
$totalSites = 0;
$activeProjects = 0;
$pendingRequests = 0;
$pendingOvertimeRequests = 0;
$recentTransfers = [];
$dbError = null;

// Get today's attendance status for the engineer
$todayAttendance = null;
$hasOpenShift = false;
$shiftId = null;
$currentBranch = '';

try {
    // 0. Fetch employee data for the logged-in user
    $empQuery = "SELECT id, employee_code, first_name, last_name, branch_id FROM employees WHERE id = ? AND status = 'Active' LIMIT 1";
    $empStmt = mysqli_prepare($db, $empQuery);
    mysqli_stmt_bind_param($empStmt, 'i', $currentUserId);
    mysqli_stmt_execute($empStmt);
    $empResult = mysqli_stmt_get_result($empStmt);
    if ($row = mysqli_fetch_assoc($empResult)) {
        $employeeId = (int)$row['id'];
        $employeeCode = $row['employee_code'] ?? '';
        $currentUserName = ($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '');
    }
    mysqli_stmt_close($empStmt);
    
    // 1. Total Sites/Branches Count
    $result = mysqli_query($db, "SELECT COUNT(*) as count FROM branches WHERE is_active = 1");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $totalSites = $row['count'];
        mysqli_free_result($result);
    }

    // 1b. Get all active branches for branch selection modal
    $branchesList = [];
    $branchesQuery = "SELECT id, branch_name, branch_address FROM branches WHERE is_active = 1 ORDER BY branch_name ASC";
    $branchesResult = mysqli_query($db, $branchesQuery);
    if ($branchesResult) {
        while ($row = mysqli_fetch_assoc($branchesResult)) {
            $branchesList[] = $row;
        }
        mysqli_free_result($branchesResult);
    }

    // 2. Get current employee branch_id
    $branchQuery = "SELECT branch_id FROM employees WHERE id = ? LIMIT 1";
    $branchStmt = mysqli_prepare($db, $branchQuery);
    mysqli_stmt_bind_param($branchStmt, 'i', $employeeId);
    mysqli_stmt_execute($branchStmt);
    $branchResult = mysqli_stmt_get_result($branchStmt);
    $currentBranchId = null;
    if ($row = mysqli_fetch_assoc($branchResult)) {
        $currentBranchId = $row['branch_id'] ?? null;
    }
    mysqli_stmt_close($branchStmt);
    
    // Get branch name for display
    $currentBranch = '';
    if ($currentBranchId) {
        $branchNameQuery = "SELECT branch_name FROM branches WHERE id = ? LIMIT 1";
        $branchNameStmt = mysqli_prepare($db, $branchNameQuery);
        mysqli_stmt_bind_param($branchNameStmt, 'i', $currentBranchId);
        mysqli_stmt_execute($branchNameStmt);
        $branchNameResult = mysqli_stmt_get_result($branchNameStmt);
        if ($row = mysqli_fetch_assoc($branchNameResult)) {
            $currentBranch = $row['branch_name'] ?? '';
        }
        mysqli_stmt_close($branchNameStmt);
    }

    // 3. Get today's attendance status
    $attQuery = "SELECT id, time_in, time_out FROM attendance 
                 WHERE employee_id = ? AND attendance_date = CURDATE() 
                 ORDER BY id DESC LIMIT 1";
    $attStmt = mysqli_prepare($db, $attQuery);
    mysqli_stmt_bind_param($attStmt, 'i', $employeeId);
    mysqli_stmt_execute($attStmt);
    $attResult = mysqli_stmt_get_result($attStmt);
    if ($row = mysqli_fetch_assoc($attResult)) {
        $todayAttendance = $row;
        $hasOpenShift = !empty($row['time_in']) && empty($row['time_out']);
        $shiftId = $row['id'];
    }
    mysqli_stmt_close($attStmt);

    // 4. Get pending cash advance requests count
    $caQuery = "SELECT COUNT(*) as count FROM cash_advances 
                WHERE employee_id = ? AND status = 'Pending'";
    $caStmt = mysqli_prepare($db, $caQuery);
    mysqli_stmt_bind_param($caStmt, 'i', $employeeId);
    mysqli_stmt_execute($caStmt);
    $caResult = mysqli_stmt_get_result($caStmt);
    if ($row = mysqli_fetch_assoc($caResult)) {
        $pendingRequests = $row['count'];
    }
    mysqli_stmt_close($caStmt);
    
    // 4b. Get pending overtime requests count
    $otQuery = "SELECT COUNT(*) as count FROM overtime_requests 
                WHERE employee_id = ? AND status = 'pending'";
    $otStmt = mysqli_prepare($db, $otQuery);
    mysqli_stmt_bind_param($otStmt, 'i', $employeeId);
    mysqli_stmt_execute($otStmt);
    $otResult = mysqli_stmt_get_result($otStmt);
    if ($row = mysqli_fetch_assoc($otResult)) {
        $pendingOvertimeRequests = $row['count'];
    }
    mysqli_stmt_close($otStmt);

    // 5. Recent Employee Transfers
    $query = "SELECT 
                e.id,
                e.first_name,
                e.middle_name,
                e.last_name,
                et.from_branch,
                et.to_branch,
                et.transfer_date
              FROM employee_transfers et
              LEFT JOIN employees e ON et.employee_id = e.id
              ORDER BY et.transfer_date DESC, et.id DESC
              LIMIT 5";
    $result = mysqli_query($db, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $recentTransfers[] = $row;
        }
        mysqli_free_result($result);
    }

} catch (Exception $e) {
    $dbError = "Database error: " . $e->getMessage();
}

// Handle Cash Advance Request AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_cash_advance'])) {
    header('Content-Type: application/json');
    
    $amount = floatval($_POST['amount'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid amount']);
        exit();
    }
    
    if (empty($reason)) {
        echo json_encode(['success' => false, 'message' => 'Please provide a reason for the cash advance']);
        exit();
    }
    
    // Insert cash advance request with 'Pending' status
    $query = "INSERT INTO cash_advances (employee_id, amount, particular, reason, request_date, status) 
              VALUES (?, ?, 'Cash Advance', ?, NOW(), 'Pending')";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, 'ids', $employeeId, $amount, $reason);
    
    if (mysqli_stmt_execute($stmt)) {
        $newId = mysqli_insert_id($db);
        
        // Create notification for the employee
        $notifTitle = "Cash Advance Submitted";
        $notifMessage = "Your cash advance request for ₱" . number_format($amount, 2) . " has been submitted and is pending approval.";
        $notifType = 'cash_advance_pending';
        
        $notifSql = "INSERT INTO employee_notifications (employee_id, cash_advance_id, notification_type, title, message, is_read, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())";
        $notifStmt = mysqli_prepare($db, $notifSql);
        if ($notifStmt) {
            mysqli_stmt_bind_param($notifStmt, 'iisss', $employeeId, $newId, $notifType, $notifTitle, $notifMessage);
            mysqli_stmt_execute($notifStmt);
            mysqli_stmt_close($notifStmt);
        }
        
        echo json_encode(['success' => true, 'id' => $newId, 'message' => 'Cash advance request submitted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit request']);
    }
    mysqli_stmt_close($stmt);
    exit();
}

// Handle Overtime Request AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_overtime'])) {
    header('Content-Type: application/json');
    
    $branchName = trim($_POST['branch_name'] ?? '');
    $requestDate = $_POST['request_date'] ?? '';
    $requestedHours = floatval($_POST['requested_hours'] ?? 0);
    $overtimeReason = trim($_POST['overtime_reason'] ?? '');
    
    if (empty($branchName)) {
        echo json_encode(['success' => false, 'message' => 'Please select a branch']);
        exit();
    }
    
    if (empty($requestDate)) {
        echo json_encode(['success' => false, 'message' => 'Please select a date']);
        exit();
    }
    
    if ($requestedHours <= 0) {
        echo json_encode(['success' => false, 'message' => 'Please enter valid overtime hours']);
        exit();
    }
    
    if (empty($overtimeReason)) {
        echo json_encode(['success' => false, 'message' => 'Please provide a reason for overtime']);
        exit();
    }
    
    // Insert into overtime_requests table
    $query = "INSERT INTO overtime_requests (employee_id, branch_name, request_date, requested_hours, overtime_reason, status, requested_by, requested_by_user_id, requested_at) 
              VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, NOW())";
    $stmt = mysqli_prepare($db, $query);
    $requestedBy = $currentUserName;
    $requestedByUserId = $currentUserId;
    mysqli_stmt_bind_param($stmt, 'issdssi', $employeeId, $branchName, $requestDate, $requestedHours, $overtimeReason, $requestedBy, $requestedByUserId);
    
    if (mysqli_stmt_execute($stmt)) {
        $overtimeRequestId = mysqli_insert_id($db);
        mysqli_stmt_close($stmt);
        
        // Insert notification for the employee
        $notificationTitle = 'Overtime Request Submitted';
        $notificationMessage = "Your overtime request for {$requestedHours} hours on {$requestDate} at {$branchName} has been submitted and is pending approval.";
        $notifQuery = "INSERT INTO employee_notifications (employee_id, overtime_request_id, notification_type, title, message, is_read, created_at) 
                       VALUES (?, ?, 'overtime_approved', ?, ?, 0, NOW())";
        $notifStmt = mysqli_prepare($db, $notifQuery);
        mysqli_stmt_bind_param($notifStmt, 'iiss', $employeeId, $overtimeRequestId, $notificationTitle, $notificationMessage);
        mysqli_stmt_execute($notifStmt);
        mysqli_stmt_close($notifStmt);
        
        echo json_encode(['success' => true, 'id' => $overtimeRequestId, 'message' => 'Overtime request submitted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit overtime request']);
    }
    exit();
}
function formatDateShort($date) {
    return date('M d, Y', strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Engineer Dashboard - Attendance Monitoring</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="dashboard.css">
    <style>
        /* Time In/Out Section Styles */
        .time-tracking-section {
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.1) 0%, rgba(0, 0, 0, 0.2) 100%);
            border: 1px solid rgba(255, 215, 0, 0.2);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .time-tracking-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .time-tracking-header i {
            font-size: 24px;
            color: var(--gold-2);
        }
        
        .time-tracking-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: #fff;
        }
        
        .time-tracking-status {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            padding: 12px 16px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 12px;
        }
        
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #4CAF50;
        }
        
        .status-indicator.inactive {
            background: #F44336;
        }
        
        .time-tracking-status span {
            color: #e5e5e5;
            font-size: 14px;
        }
        
        .time-tracking-buttons {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }
        
        .btn-time {
            flex: 1;
            min-width: 140px;
            padding: 16px 24px;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-time-in {
            background: linear-gradient(180deg, #4CAF50 0%, #45a049 100%);
            color: #fff;
            box-shadow: 0 8px 20px rgba(76, 175, 80, 0.3);
        }
        
        .btn-time-in:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(76, 175, 80, 0.4);
        }
        
        .btn-time-in:disabled {
            background: linear-gradient(180deg, #666 0%, #555 100%);
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .btn-time-out {
            background: linear-gradient(180deg, #F44336 0%, #d32f2f 100%);
            color: #fff;
            box-shadow: 0 8px 20px rgba(244, 67, 54, 0.3);
        }
        
        .btn-time-out:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(244, 67, 54, 0.4);
        }
        
        .btn-time-out:disabled {
            background: linear-gradient(180deg, #666 0%, #555 100%);
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        /* Cash Advance Section Styles */
        .cash-advance-section {
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.05) 0%, rgba(0, 0, 0, 0.2) 100%);
            border: 1px solid rgba(255, 215, 0, 0.15);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .cash-advance-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .cash-advance-header i {
            font-size: 24px;
            color: var(--gold-2);
        }
        
        .cash-advance-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: #fff;
        }
        
        .ca-form {
            display: grid;
            gap: 16px;
        }
        
        .ca-field {
            display: grid;
            gap: 8px;
        }
        
        .ca-field label {
            color: #b5b5b5;
            font-size: 13px;
            font-weight: 600;
        }
        
        .ca-field input,
        .ca-field textarea {
            width: 100%;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 12px 14px;
            color: #fff;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .ca-field input:focus,
        .ca-field textarea:focus {
            outline: none;
            border-color: rgba(255, 215, 0, 0.5);
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }
        
        .ca-field textarea {
            min-height: 80px;
            resize: vertical;
        }
        
        .btn-submit-ca {
            background: linear-gradient(180deg, #FFE680 0%, #FFD700 100%);
            color: #0b0b0b;
            border: none;
            border-radius: 10px;
            padding: 14px 24px;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 8px;
        }
        
        .btn-submit-ca:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 215, 0, 0.3);
        }
        
        .ca-alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 16px;
            display: none;
        }
        
        .ca-alert.show {
            display: block;
        }
        
        .ca-alert.success {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid #4CAF50;
            color: #4CAF50;
        }
        
        .ca-alert.error {
            background: rgba(244, 67, 54, 0.2);
            border: 1px solid #F44336;
            color: #F44336;
        }
        
        /* Overtime Request Section Styles */
        .overtime-request-section {
            background: linear-gradient(135deg, rgba(255, 152, 0, 0.05) 0%, rgba(0, 0, 0, 0.2) 100%);
            border: 1px solid rgba(255, 152, 0, 0.15);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .overtime-request-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .overtime-request-header i {
            font-size: 24px;
            color: var(--gold-2);
        }
        
        .overtime-request-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: #fff;
        }
        
        .ot-form {
            display: grid;
            gap: 16px;
        }
        
        .ot-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        
        .ot-field {
            display: grid;
            gap: 8px;
        }
        
        .ot-field label {
            color: #b5b5b5;
            font-size: 13px;
            font-weight: 600;
        }
        
        .ot-field input,
        .ot-field select,
        .ot-field textarea {
            width: 100%;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 12px 14px;
            color: #fff;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .ot-field input:focus,
        .ot-field select:focus,
        .ot-field textarea:focus {
            outline: none;
            border-color: rgba(255, 215, 0, 0.5);
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }
        
        .ot-field select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23fff' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            padding-right: 40px;
        }
        
        .ot-field select option {
            background: #1a1a2e;
            color: #fff;
        }
        
        .ot-field textarea {
            min-height: 80px;
            resize: vertical;
        }
        
        .btn-submit-ot {
            background: linear-gradient(180deg, #FF9800 0%, #F57C00 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 14px 24px;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 8px;
        }
        
        .btn-submit-ot:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 152, 0, 0.3);
        }
        
        .ot-alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 16px;
            display: none;
        }
        
        .ot-alert.show {
            display: block;
        }
        
        .ot-alert.success {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid #4CAF50;
            color: #4CAF50;
        }
        
        .ot-alert.error {
            background: rgba(244, 67, 54, 0.2);
            border: 1px solid #F44336;
            color: #F44336;
        }
        
        .current-time {
            font-size: 32px;
            font-weight: 700;
            color: var(--gold-2);
            text-align: center;
            margin-bottom: 8px;
            font-family: 'Inter', monospace;
        }
        
        .current-date {
            text-align: center;
            color: #888;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        /* Branch Selection Modal Styles */
        .branch-selection-modal .modal-content {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 16px;
            color: #fff;
        }
        
        .branch-selection-modal .modal-header {
            border-bottom: 1px solid rgba(255, 215, 0, 0.2);
            padding: 20px 24px;
        }
        
        .branch-selection-modal .modal-title {
            color: var(--gold-2);
            font-weight: 700;
            font-size: 1.25rem;
        }
        
        .branch-selection-modal .modal-header .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
        
        .branch-selection-modal .modal-body {
            padding: 24px;
        }
        
        .branch-selection-modal .modal-footer {
            border-top: 1px solid rgba(255, 215, 0, 0.2);
            padding: 20px 24px;
        }
        
        .branch-option {
            display: flex;
            align-items: center;
            padding: 16px 20px;
            margin-bottom: 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .branch-option:hover {
            background: rgba(255, 215, 0, 0.1);
            border-color: rgba(255, 215, 0, 0.3);
        }
        
        .branch-option.selected {
            background: rgba(255, 215, 0, 0.15);
            border-color: var(--gold-2);
        }
        
        .branch-option input[type="radio"] {
            display: none;
        }
        
        .branch-option .branch-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.2) 0%, rgba(255, 215, 0, 0.05) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
        }
        
        .branch-option .branch-icon i {
            font-size: 20px;
            color: var(--gold-2);
        }
        
        .branch-option .branch-info {
            flex: 1;
        }
        
        .branch-option .branch-info h6 {
            margin: 0 0 4px 0;
            font-weight: 600;
            color: #fff;
            font-size: 1rem;
        }
        
        .branch-option .branch-info p {
            margin: 0;
            color: #888;
            font-size: 0.875rem;
        }
        
        .branch-option .check-icon {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: transparent;
            transition: all 0.3s ease;
        }
        
        .branch-option.selected .check-icon {
            background: var(--gold-2);
            color: #1a1a2e;
        }
        
        .btn-confirm-branch {
            background: linear-gradient(180deg, #4CAF50 0%, #45a049 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-confirm-branch:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(76, 175, 80, 0.4);
            color: #fff;
        }
        
        .btn-confirm-branch:disabled {
            background: linear-gradient(180deg, #666 0%, #555 100%);
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .btn-cancel-branch {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-cancel-branch:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
        }
        
        .no-branches-message {
            text-align: center;
            padding: 40px 20px;
            color: #888;
        }
        
        .no-branches-message i {
            font-size: 48px;
            color: var(--gold-2);
            margin-bottom: 16px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="app-shell">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <!-- Top Navbar -->
            <div class="top-navbar">
                <div class="navbar-brand">
                    <i class="fas fa-hard-hat" style="color: var(--gold-2); font-size: 1.75rem;"></i>
                    <h1>Engineer Dashboard</h1>
                </div>
                <div class="navbar-user">
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($currentUserName); ?></div>
                        <div class="user-role"><?php echo htmlspecialchars($userRole); ?></div>
                    </div>
                    <div class="user-avatar">
                        <?php if ($currentUserAvatar && file_exists(__DIR__ . '/../' . $currentUserAvatar)): ?>
                            <img src="../<?php echo htmlspecialchars($currentUserAvatar); ?>" alt="Profile">
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Dashboard Title -->
            <div class="dashboard-title">
                <i class="fas fa-tachometer-alt"></i>
                Engineer Overview
            </div>

            <?php if ($dbError): ?>
                <div class="db-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($dbError); ?>
                </div>
            <?php endif; ?>

            <!-- Time Tracking Section -->
            <div class="time-tracking-section">
                <div class="time-tracking-header">
                    <i class="fas fa-clock"></i>
                    <h3>Time Tracking</h3>
                </div>
                
                <div class="current-time" id="currentTime">--:--:--</div>
                <div class="current-date" id="currentDate">--</div>
                
                <div class="time-tracking-status">
                    <div class="status-indicator <?php echo $hasOpenShift ? '' : 'inactive'; ?>"></div>
                    <span>
                        <?php if ($hasOpenShift): ?>
                            Currently Clocked In (<?php echo !empty($todayAttendance['time_in']) ? date('h:i A', strtotime($todayAttendance['time_in'])) : ''; ?>)
                        <?php elseif ($todayAttendance && $todayAttendance['time_out']): ?>
                            Clocked Out at <?php echo !empty($todayAttendance['time_out']) ? date('h:i A', strtotime($todayAttendance['time_out'])) : ''; ?>
                        <?php else: ?>
                            Not Clocked In Today
                        <?php endif; ?>
                    </span>
                </div>
                
                <div class="time-tracking-buttons">
                    <button type="button" class="btn-time btn-time-in" id="btnTimeIn" 
                            data-employee-id="<?php echo $employeeId; ?>"
                            data-employee-code="<?php echo htmlspecialchars($employeeCode); ?>"
                            data-branch="<?php echo htmlspecialchars($currentBranch); ?>"
                            <?php echo $hasOpenShift ? 'disabled' : ''; ?>>
                        <i class="fas fa-play"></i>
                        Time In
                    </button>
                    <button type="button" class="btn-time btn-time-out" id="btnTimeOut"
                            data-employee-id="<?php echo $employeeId; ?>"
                            data-shift-id="<?php echo $shiftId ?? ''; ?>"
                            <?php echo !$hasOpenShift ? 'disabled' : ''; ?>>
                        <i class="fas fa-stop"></i>
                        Time Out
                    </button>
                </div>
            </div>

            <!-- Cash Advance Request Section -->
            <div class="cash-advance-section">
                <div class="cash-advance-header">
                    <i class="fas fa-money-bill-wave"></i>
                    <h3>Request Cash Advance</h3>
                    <?php if ($pendingRequests > 0): ?>
                        <span class="notification-badge" style="margin-left: auto;"><?php echo $pendingRequests; ?> Pending</span>
                    <?php endif; ?>
                </div>
                
                <div id="caAlert" class="ca-alert"></div>
                
                <form class="ca-form" id="cashAdvanceForm">
                    <div class="ca-field">
                        <label for="caAmount">Amount (₱)</label>
                        <input type="number" id="caAmount" name="amount" min="1" step="0.01" placeholder="Enter amount" required>
                    </div>
                    <div class="ca-field">
                        <label for="caReason">Reason / Purpose</label>
                        <textarea id="caReason" name="reason" placeholder="Explain why you need this cash advance..." required></textarea>
                    </div>
                    <button type="submit" class="btn-submit-ca">
                        <i class="fas fa-paper-plane"></i>
                        Submit Request to Admin
                    </button>
                </form>
            </div>

            <!-- Overtime Request Section -->
            <div class="overtime-request-section">
                <div class="overtime-request-header">
                    <i class="fas fa-clock"></i>
                    <h3>Request Overtime</h3>
                    <?php if ($pendingOvertimeRequests > 0): ?>
                        <span class="notification-badge" style="margin-left: auto;"><?php echo $pendingOvertimeRequests; ?> Pending</span>
                    <?php endif; ?>
                </div>
                
                <div id="otAlert" class="ot-alert"></div>
                
                <form class="ot-form" id="overtimeForm">
                    <div class="ot-field">
                        <label for="otBranch">Branch / Site</label>
                        <select id="otBranch" name="branch_name" required>
                            <option value="">Select a branch...</option>
                            <?php foreach ($branchesList as $branch): ?>
                                <option value="<?php echo htmlspecialchars($branch['branch_name']); ?>">
                                    <?php echo htmlspecialchars($branch['branch_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="ot-row">
                        <div class="ot-field">
                            <label for="otDate">Date</label>
                            <input type="date" id="otDate" name="request_date" required>
                        </div>
                        <div class="ot-field">
                            <label for="otHours">Hours</label>
                            <input type="number" id="otHours" name="requested_hours" min="0.5" max="24" step="0.5" placeholder="e.g. 2.5" required>
                        </div>
                    </div>
                    <div class="ot-field">
                        <label for="otReason">Reason / Justification</label>
                        <textarea id="otReason" name="overtime_reason" placeholder="Explain why overtime is needed..." required></textarea>
                    </div>
                    <button type="submit" class="btn-submit-ot">
                        <i class="fas fa-paper-plane"></i>
                        Submit Overtime Request
                    </button>
                </form>
            </div>

            <!-- Summary Cards -->
            <div class="summary-grid">
                <div class="summary-card">
                    <div class="summary-icon branches">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="summary-number"><?php echo number_format($totalSites); ?></div>
                    <div class="summary-label">Active Sites</div>
                    <div class="summary-change">
                        <i class="fas fa-map-marker-alt"></i>
                        Operational locations
                    </div>
                </div>

                <div class="summary-card">
                    <div class="summary-icon employees">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="summary-number">-</div>
                    <div class="summary-label">Site Personnel</div>
                    <div class="summary-change">
                        <i class="fas fa-hard-hat"></i>
                        Field workers
                    </div>
                </div>

                <div class="summary-card">
                    <div class="summary-icon transfers">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="summary-number"><?php echo count($recentTransfers); ?></div>
                    <div class="summary-label">Recent Transfers</div>
                    <div class="summary-change">
                        <i class="fas fa-calendar-day"></i>
                        Staff movements
                    </div>
                </div>

                <div class="summary-card">
                    <div class="summary-icon payroll">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div class="summary-number">-</div>
                    <div class="summary-label">Site Attendance</div>
                    <div class="summary-change">
                        <i class="fas fa-check-circle"></i>
                        Daily tracking
                    </div>
                </div>
            </div>

            <!-- Data Monitoring Section -->
            <div class="monitoring-section">
                <!-- Recent Transfers -->
                <div class="monitoring-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-exchange-alt"></i>
                            Recent Staff Transfers
                        </h5>
                        <a href="transfer_module.php" class="view-all-btn">
                            View All <i class="fas fa-arrow-right" style="font-size: 0.75rem;"></i>
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($recentTransfers)): ?>
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Transfer</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentTransfers as $transfer): 
                                        $firstName = $transfer['first_name'] ?? '';
                                        $lastName = $transfer['last_name'] ?? '';
                                        $employeeName = trim($firstName . ' ' . $lastName) ?: 'Unknown';
                                    ?>
                                        <tr>
                                            <td class="emp-name"><?php echo htmlspecialchars($employeeName); ?></td>
                                            <td>
                                                <span class="branch-from"><?php echo htmlspecialchars($transfer['from_branch'] ?? 'N/A'); ?></span>
                                                <i class="fas fa-arrow-right arrow-icon"></i>
                                                <span class="branch-to"><?php echo htmlspecialchars($transfer['to_branch'] ?? 'N/A'); ?></span>
                                            </td>
                                            <td><?php echo formatDateShort($transfer['transfer_date']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p>No recent transfers found</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Site Overview -->
                <div class="monitoring-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-map-marked-alt"></i>
                            Site Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="empty-state" style="padding: 2rem 1rem;">
                            <i class="fas fa-hard-hat" style="font-size: 2.5rem; color: var(--gold-2); margin-bottom: 1rem;"></i>
                            <p>Welcome to the Engineer Dashboard</p>
                            <p style="font-size: 0.9rem; color: #9CA3AF; margin-top: 0.5rem;">
                                Access site attendance, manage staff transfers, and procurement requests from the quick actions above.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Branch Selection Modal -->
    <div class="modal fade branch-selection-modal" id="branchSelectionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        Select Branch for Time In
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-4">Please select the branch where you will be working today:</p>
                    <div id="branchesListContainer">
                        <?php if (!empty($branchesList)): ?>
                            <?php foreach ($branchesList as $index => $branch): ?>
                                <label class="branch-option" data-branch-name="<?php echo htmlspecialchars($branch['branch_name']); ?>">
                                    <input type="radio" name="selected_branch" value="<?php echo htmlspecialchars($branch['branch_name']); ?>">
                                    <div class="branch-icon">
                                        <i class="fas fa-building"></i>
                                    </div>
                                    <div class="branch-info">
                                        <h6><?php echo htmlspecialchars($branch['branch_name']); ?></h6>
                                        <?php if (!empty($branch['branch_address'])): ?>
                                            <p><?php echo htmlspecialchars($branch['branch_address']); ?></p>
                                        <?php else: ?>
                                            <p>Active branch location</p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="check-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-branches-message">
                                <i class="fas fa-exclamation-circle"></i>
                                <h5>No Active Branches</h5>
                                <p>There are no active branches available. Please contact the administrator.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel-branch" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-confirm-branch" id="btnConfirmBranch" disabled>
                        <i class="fas fa-check me-2"></i>
                        Confirm & Time In
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/dashboard.js"></script>
    <script>
        // Update current time display
        function updateTime() {
            const now = new Date();
            document.getElementById('currentTime').textContent = now.toLocaleTimeString('en-US', { 
                hour12: true,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('currentDate').textContent = now.toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }
        updateTime();
        setInterval(updateTime, 1000);
        
        // Time In functionality
        document.getElementById('btnTimeIn').addEventListener('click', function() {
            const btn = this;
            const employeeId = btn.dataset.employeeId;
            const employeeCode = btn.dataset.employeeCode;
            const currentBranch = btn.dataset.branch;
            
            // Always show branch selection modal first
            const modal = document.getElementById('branchSelectionModal');
            
            // Pre-select current branch if assigned
            if (currentBranch) {
                modal.querySelectorAll('.branch-option').forEach(option => {
                    if (option.dataset.branchName === currentBranch) {
                        option.classList.add('selected');
                        option.querySelector('input[type="radio"]').checked = true;
                        document.getElementById('btnConfirmBranch').disabled = false;
                    } else {
                        option.classList.remove('selected');
                        option.querySelector('input[type="radio"]').checked = false;
                    }
                });
            }
            
            const branchModal = new bootstrap.Modal(modal);
            branchModal.show();
        });
        
        // Branch Selection Modal functionality
        (function() {
            const modal = document.getElementById('branchSelectionModal');
            const confirmBtn = document.getElementById('btnConfirmBranch');
            const timeInBtn = document.getElementById('btnTimeIn');
            let selectedBranchName = '';
            
            // Handle branch option selection
            modal.querySelectorAll('.branch-option').forEach(option => {
                option.addEventListener('click', function() {
                    // Remove selected class from all options
                    modal.querySelectorAll('.branch-option').forEach(opt => opt.classList.remove('selected'));
                    // Add selected class to clicked option
                    this.classList.add('selected');
                    // Check the radio input
                    this.querySelector('input[type="radio"]').checked = true;
                    // Store selected branch
                    selectedBranchName = this.dataset.branchName;
                    // Enable confirm button
                    confirmBtn.disabled = false;
                });
            });
            
            // Handle confirm button click
            confirmBtn.addEventListener('click', function() {
                if (!selectedBranchName) return;
                
                const employeeId = timeInBtn.dataset.employeeId;
                const employeeCode = timeInBtn.dataset.employeeCode;
                
                // Close modal
                bootstrap.Modal.getInstance(modal).hide();
                
                // Disable time in button
                timeInBtn.disabled = true;
                timeInBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                
                const formData = new FormData();
                formData.append('employee_id', employeeId);
                formData.append('employee_code', employeeCode);
                formData.append('branch_name', selectedBranchName);
                
                fetch('api/clock_in.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('Time In recorded successfully at ' + (data.time_in || 'now'));
                        location.reload();
                    } else {
                        alert(data.message || 'Failed to Time In');
                        timeInBtn.disabled = false;
                        timeInBtn.innerHTML = '<i class="fas fa-play"></i> Time In';
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Error recording Time In');
                    timeInBtn.disabled = false;
                    timeInBtn.innerHTML = '<i class="fas fa-play"></i> Time In';
                });
            });
        })();
        
        // Time Out functionality
        document.getElementById('btnTimeOut').addEventListener('click', function() {
            const btn = this;
            const employeeId = btn.dataset.employeeId;
            const shiftId = btn.dataset.shiftId;
            
            if (!confirm('Confirm Time Out?')) return;
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            const formData = new FormData();
            formData.append('employee_id', employeeId);
            if (shiftId) formData.append('shift_id', shiftId);
            
            fetch('api/clock_out.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Time Out recorded successfully at ' + (data.time_out || 'now'));
                    location.reload();
                } else {
                    alert(data.message || 'Failed to Time Out');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-stop"></i> Time Out';
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error recording Time Out');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-stop"></i> Time Out';
            });
        });
        
        // Cash Advance Form submission
        document.getElementById('cashAdvanceForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const alertDiv = document.getElementById('caAlert');
            const submitBtn = form.querySelector('.btn-submit-ca');
            
            const formData = new FormData(form);
            formData.append('request_cash_advance', '1');
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(async r => {
                const text = await r.text();
                console.log('Raw server response:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    throw new Error('Server returned non-JSON: ' + text.substring(0, 200));
                }
            })
            .then(data => {
                alertDiv.className = 'ca-alert ' + (data.success ? 'success show' : 'error show');
                alertDiv.textContent = data.message;
                
                if (data.success) {
                    form.reset();
                    setTimeout(() => location.reload(), 1500);
                }
            })
            .catch(err => {
                console.error(err);
                alertDiv.className = 'ca-alert error show';
                alertDiv.textContent = 'Error submitting request. Please try again.';
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Request to Admin';
            });
        });
        
        // Overtime Request Form submission
        document.getElementById('overtimeForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const alertDiv = document.getElementById('otAlert');
            const submitBtn = form.querySelector('.btn-submit-ot');
            
            const formData = new FormData(form);
            formData.append('request_overtime', '1');
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(async r => {
                const text = await r.text();
                console.log('Raw server response:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    throw new Error('Server returned non-JSON: ' + text.substring(0, 200));
                }
            })
            .then(data => {
                alertDiv.className = 'ot-alert ' + (data.success ? 'success show' : 'error show');
                alertDiv.textContent = data.message;
                
                if (data.success) {
                    form.reset();
                    setTimeout(() => location.reload(), 1500);
                }
            })
            .catch(err => {
                console.error(err);
                alertDiv.className = 'ot-alert error show';
                alertDiv.textContent = 'Error submitting request. Please try again.';
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Overtime Request';
            });
        });
    </script>
</body>
</html>
