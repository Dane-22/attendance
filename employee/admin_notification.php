<?php
// employee/admin_notification.php
// Admin dashboard for viewing overtime requests and cash advance (read-only or limited actions)

// Suppress PHP errors for clean JSON output
@error_reporting(0);
@ini_set('display_errors', 0);

session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Check if user is Admin (not Super Admin - they use notification.php)
$isAdmin = ($_SESSION['position'] ?? '') === 'Admin';
if (!$isAdmin) {
    header('Location: dashboard.php');
    exit();
}

require_once __DIR__ . '/../conn/db_connection.php';
require_once __DIR__ . '/../functions.php';

// Helper function to get pending count
function getPendingOvertimeCount($db) {
    if (!$db) return 0;
    $checkTable = @mysqli_query($db, "SHOW TABLES LIKE 'overtime_requests'");
    if (!$checkTable || mysqli_num_rows($checkTable) === 0) {
        return 0;
    }
    $sql = "SELECT COUNT(*) as cnt FROM overtime_requests WHERE status = 'pending'";
    $result = @mysqli_query($db, $sql);
    if (!$result) return 0;
    $row = mysqli_fetch_assoc($result);
    return intval($row['cnt'] ?? 0);
}

// Helper function to get pending cash advance count
function getPendingCashAdvanceCount($db) {
    if (!$db) return 0;
    $checkTable = @mysqli_query($db, "SHOW TABLES LIKE 'cash_advances'");
    if (!$checkTable || mysqli_num_rows($checkTable) === 0) {
        return 0;
    }
    $sql = "SELECT COUNT(*) as cnt FROM cash_advances WHERE status = 'pending' AND particular = 'Cash Advance'";
    $result = @mysqli_query($db, $sql);
    if (!$result) return 0;
    $row = mysqli_fetch_assoc($result);
    return intval($row['cnt'] ?? 0);
}

// Helper function to get pending leave request count
function getPendingLeaveCount($db) {
    if (!$db) return 0;
    $checkTable = @mysqli_query($db, "SHOW TABLES LIKE 'leave_requests'");
    if (!$checkTable || mysqli_num_rows($checkTable) === 0) {
        return 0;
    }
    $sql = "SELECT COUNT(*) as cnt FROM leave_requests WHERE status = 'pending'";
    $result = @mysqli_query($db, $sql);
    if (!$result) return 0;
    $row = mysqli_fetch_assoc($result);
    return intval($row['cnt'] ?? 0);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // Load cash advance requests (view only for admin)
    if ($_POST['action'] === 'load_cash_advance_requests') {
        $checkTable = @mysqli_query($db, "SHOW TABLES LIKE 'cash_advances'");
        if (!$checkTable || mysqli_num_rows($checkTable) === 0) {
            echo json_encode([
                'success' => true,
                'requests' => [],
                'counts' => ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'all' => 0]
            ]);
            exit();
        }
        
        $status = isset($_POST['status']) ? $_POST['status'] : 'pending';
        
        $whereClause = "WHERE particular = 'Cash Advance'";
        if ($status !== 'all') {
            $whereClause .= " AND c.status = '" . mysqli_real_escape_string($db, $status) . "'";
        }
        
        $sql = "SELECT c.*, e.first_name, e.last_name 
                FROM cash_advances c 
                LEFT JOIN employees e ON c.employee_id = e.id 
                $whereClause 
                ORDER BY c.request_date DESC";
        
        $result = @mysqli_query($db, $sql);
        $requests = [];
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $initials = strtoupper(substr($row['first_name'] ?? '', 0, 1) . substr($row['last_name'] ?? '', 0, 1));
                
                $requests[] = [
                    'id' => $row['id'],
                    'employee_id' => $row['employee_id'],
                    'employee_name' => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
                    'employee_initials' => $initials,
                    'amount' => $row['amount'],
                    'reason' => $row['reason'],
                    'status' => $row['status'] ?? 'pending',
                    'request_date' => $row['request_date'],
                    'approved_by' => $row['approved_by'] ?? null,
                    'approved_at' => $row['approved_at'] ?? null,
                    'rejection_reason' => $row['rejection_reason'] ?? null
                ];
            }
        }
        
        // Get counts for tabs - include pre_approved in counts
        $countsSql = "SELECT status, COUNT(*) as cnt FROM cash_advances WHERE particular = 'Cash Advance' GROUP BY status";
        $countsResult = mysqli_query($db, $countsSql);
        $counts = ['pending' => 0, 'pre_approved' => 0, 'approved' => 0, 'rejected' => 0, 'all' => 0];
        
        if ($countsResult) {
            while ($row = mysqli_fetch_assoc($countsResult)) {
                $counts[$row['status']] = intval($row['cnt']);
                $counts['all'] += intval($row['cnt']);
            }
        }
        
        echo json_encode([
            'success' => true,
            'requests' => $requests,
            'counts' => $counts
        ]);
        exit();
    }
    
    // Check if table exists for overtime requests
    $checkTable = @mysqli_query($db, "SHOW TABLES LIKE 'overtime_requests'");
    if (!$checkTable || mysqli_num_rows($checkTable) === 0) {
        echo json_encode(['success' => false, 'message' => 'Overtime requests table does not exist.']);
        exit();
    }
    
    // Ensure status column exists
    $checkColumn = @mysqli_query($db, "SHOW COLUMNS FROM overtime_requests LIKE 'status'");
    if (!$checkColumn || mysqli_num_rows($checkColumn) === 0) {
        @mysqli_query($db, "ALTER TABLE overtime_requests ADD COLUMN status VARCHAR(20) DEFAULT 'pending'");
        @mysqli_query($db, "ALTER TABLE overtime_requests ADD COLUMN approved_by VARCHAR(100) DEFAULT NULL");
        @mysqli_query($db, "ALTER TABLE overtime_requests ADD COLUMN approved_at DATETIME DEFAULT NULL");
        @mysqli_query($db, "ALTER TABLE overtime_requests ADD COLUMN rejection_reason TEXT DEFAULT NULL");
    }
    
    // Load overtime requests (view only for admin)
    if ($_POST['action'] === 'load_requests') {
        $status = isset($_POST['status']) ? $_POST['status'] : 'pending';
        
        $whereClause = "WHERE 1=1";
        if ($status !== 'all') {
            $whereClause = "WHERE r.status = '" . mysqli_real_escape_string($db, $status) . "'";
        }
        
        $sql = "SELECT r.*, e.first_name, e.last_name 
                FROM overtime_requests r 
                LEFT JOIN employees e ON r.employee_id = e.id 
                $whereClause 
                ORDER BY r.requested_at DESC";
        
        $result = @mysqli_query($db, $sql);
        $requests = [];
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $initials = strtoupper(substr($row['first_name'] ?? '', 0, 1) . substr($row['last_name'] ?? '', 0, 1));
                
                $requests[] = [
                    'id' => $row['id'],
                    'employee_id' => $row['employee_id'],
                    'employee_name' => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
                    'employee_avatar' => '',
                    'employee_initials' => $initials,
                    'branch_name' => $row['branch_name'],
                    'request_date' => $row['request_date'],
                    'requested_hours' => $row['requested_hours'],
                    'overtime_reason' => $row['overtime_reason'],
                    'status' => $row['status'],
                    'requested_by' => $row['requested_by'],
                    'requested_at' => $row['requested_at'],
                    'approved_by' => $row['approved_by'],
                    'approved_at' => $row['approved_at'],
                    'rejection_reason' => $row['rejection_reason']
                ];
            }
        }
        
        // Get counts for tabs - include pre-approved in counts
        $countsSql = "SELECT status, COUNT(*) as cnt FROM overtime_requests GROUP BY status";
        $countsResult = mysqli_query($db, $countsSql);
        $counts = ['pending' => 0, 'pre-approved' => 0, 'approved' => 0, 'rejected' => 0, 'all' => 0];
        
        if ($countsResult) {
            while ($row = mysqli_fetch_assoc($countsResult)) {
                $counts[$row['status']] = intval($row['cnt']);
                $counts['all'] += intval($row['cnt']);
            }
        }
        
        echo json_encode([
            'success' => true,
            'requests' => $requests,
            'counts' => $counts
        ]);
        exit();
    }
    // Pre-approve overtime request (Admin can pre-approve, Super Admin will do final approval)
    if ($_POST['action'] === 'pre_approve_request') {
        try {
            $requestId = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
            $adminName = $_SESSION['username'] ?? 'Admin';
            
            if ($requestId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
                exit();
            }
            
            // Ensure status column can accept 'pre-approved' value
            @mysqli_query($db, "ALTER TABLE overtime_requests MODIFY COLUMN status ENUM('pending','approved','rejected','pre-approved') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending'");
            
            // Get request details first for notification
            $getSql = "SELECT * FROM overtime_requests WHERE id = ? AND status = 'pending' LIMIT 1";
            $getStmt = mysqli_prepare($db, $getSql);
            mysqli_stmt_bind_param($getStmt, 'i', $requestId);
            mysqli_stmt_execute($getStmt);
            $getResult = mysqli_stmt_get_result($getStmt);
            $requestDetails = mysqli_fetch_assoc($getResult);
            mysqli_stmt_close($getStmt);
            
            if (!$requestDetails) {
                echo json_encode(['success' => false, 'message' => 'Request not found or already processed']);
                exit();
            }
            
            // Update request status to pre-approved (hyphen to match DB schema)
            $updateSql = "UPDATE overtime_requests SET status = 'pre-approved', approved_by = ?, approved_at = NOW() WHERE id = ? AND status = 'pending'";
            $updateStmt = mysqli_prepare($db, $updateSql);
            mysqli_stmt_bind_param($updateStmt, 'si', $adminName, $requestId);
            
            if (mysqli_stmt_execute($updateStmt) && mysqli_stmt_affected_rows($updateStmt) > 0) {
                mysqli_stmt_close($updateStmt);
                
                // Create notification for Super Admin
                $superAdminNotifTitle = "Overtime Pre-Approved by Admin";
                $superAdminNotifMessage = "Admin {$adminName} pre-approved overtime request for {$requestDetails['requested_by']} - {$requestDetails['requested_hours']} hours on {$requestDetails['request_date']}. Awaiting final approval.";
                $superAdminNotifType = 'overtime_pre_approved';
                
                // Get all Super Admin and Developer users
                $superAdminSql = "SELECT id FROM employees WHERE position IN ('Super Admin', 'Developer') AND status = 'Active'";
                $superAdminResult = mysqli_query($db, $superAdminSql);
                if ($superAdminResult) {
                    while ($superAdminRow = mysqli_fetch_assoc($superAdminResult)) {
                        $superAdminId = $superAdminRow['id'];
                        $notifInsertSql = "INSERT INTO employee_notifications (employee_id, overtime_request_id, notification_type, title, message, is_read, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())";
                        $notifStmt = mysqli_prepare($db, $notifInsertSql);
                        if ($notifStmt) {
                            mysqli_stmt_bind_param($notifStmt, 'iisss', $superAdminId, $requestId, $superAdminNotifType, $superAdminNotifTitle, $superAdminNotifMessage);
                            mysqli_stmt_execute($notifStmt);
                            mysqli_stmt_close($notifStmt);
                            
                            // Send push notification
                            sendPushNotification($db, $superAdminId, $superAdminNotifTitle, $superAdminNotifMessage, '/employee/notification.php');
                        }
                    }
                }
                
                // Create notification for the requester
                $requesterId = isset($requestDetails['requested_by_user_id']) ? intval($requestDetails['requested_by_user_id']) : 0;
                if ($requesterId > 0) {
                    $notifTitle = "Overtime Pre-Approved";
                    $notifMessage = "Your overtime request for {$requestDetails['requested_hours']} hours on {$requestDetails['request_date']} has been pre-approved by Admin {$adminName} and is now awaiting final approval from Super Admin.";
                    $notifType = 'overtime_pre_approved';
                    
                    $notifSql = "INSERT INTO employee_notifications (employee_id, overtime_request_id, notification_type, title, message, is_read, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())";
                    $notifStmt = mysqli_prepare($db, $notifSql);
                    if ($notifStmt) {
                        mysqli_stmt_bind_param($notifStmt, 'iisss', $requesterId, $requestId, $notifType, $notifTitle, $notifMessage);
                        mysqli_stmt_execute($notifStmt);
                        mysqli_stmt_close($notifStmt);
                    }
                }
                
                echo json_encode(['success' => true, 'message' => 'Overtime request pre-approved. Awaiting final approval from Super Admin.']);
                logActivity($db, 'Overtime Pre-Approved', "Admin {$adminName} pre-approved overtime #{$requestId} for {$requestDetails['requested_hours']} hours on {$requestDetails['request_date']}");
            } else {
                mysqli_stmt_close($updateStmt);
                echo json_encode(['success' => false, 'message' => 'Request not found or already processed']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
        }
        exit();
    }
    
    // Pre-approve cash advance request
    if ($_POST['action'] === 'pre_approve_cash_advance') {
        try {
            $requestId = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
            $adminName = $_SESSION['username'] ?? 'Admin';
            
            if ($requestId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
                exit();
            }
            
            // Ensure status column can accept 'pre_approved' value - modify to ENUM if it's VARCHAR
            @mysqli_query($db, "ALTER TABLE cash_advances MODIFY COLUMN status ENUM('Pending','Approved','Rejected','Pre-Approved') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Pending'");
            
            // Get request details first
            $getSql = "SELECT * FROM cash_advances WHERE id = ? AND status = 'Pending' LIMIT 1";
            $getStmt = mysqli_prepare($db, $getSql);
            mysqli_stmt_bind_param($getStmt, 'i', $requestId);
            mysqli_stmt_execute($getStmt);
            $getResult = mysqli_stmt_get_result($getStmt);
            $requestDetails = mysqli_fetch_assoc($getResult);
            mysqli_stmt_close($getStmt);
            
            if (!$requestDetails) {
                echo json_encode(['success' => false, 'message' => 'Request not found or already processed']);
                exit();
            }
            
            // Update request status to Pre-Approved (matching ENUM case)
            $updateSql = "UPDATE cash_advances SET status = 'Pre-Approved', approved_by = ?, approved_at = NOW() WHERE id = ? AND status = 'Pending'";
            $updateStmt = mysqli_prepare($db, $updateSql);
            mysqli_stmt_bind_param($updateStmt, 'si', $adminName, $requestId);
            
            if (mysqli_stmt_execute($updateStmt) && mysqli_stmt_affected_rows($updateStmt) > 0) {
                mysqli_stmt_close($updateStmt);
                
                // Create notification for Super Admin
                $superAdminNotifTitle = "Cash Advance Pre-Approved by Admin";
                $superAdminNotifMessage = "Admin {$adminName} pre-approved cash advance request for ₱" . number_format($requestDetails['amount'], 2) . " - Awaiting final approval.";
                $superAdminNotifType = 'cash_advance_pre_approved';
                
                $superAdminSql = "SELECT id FROM employees WHERE position IN ('Super Admin', 'Developer') AND status = 'Active'";
                $superAdminResult = mysqli_query($db, $superAdminSql);
                if ($superAdminResult) {
                    while ($superAdminRow = mysqli_fetch_assoc($superAdminResult)) {
                        $superAdminId = $superAdminRow['id'];
                        $notifInsertSql = "INSERT INTO employee_notifications (employee_id, cash_advance_id, notification_type, title, message, is_read, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())";
                        $notifStmt = mysqli_prepare($db, $notifInsertSql);
                        if ($notifStmt) {
                            mysqli_stmt_bind_param($notifStmt, 'iisss', $superAdminId, $requestId, $superAdminNotifType, $superAdminNotifTitle, $superAdminNotifMessage);
                            mysqli_stmt_execute($notifStmt);
                            mysqli_stmt_close($notifStmt);
                            
                            // Send push notification
                            sendPushNotification($db, $superAdminId, $superAdminNotifTitle, $superAdminNotifMessage, '/employee/notification.php');
                        }
                    }
                }
                
                echo json_encode(['success' => true, 'message' => 'Cash advance request pre-approved. Awaiting final approval from Super Admin.']);
                logActivity($db, 'Cash Advance Pre-Approved', "Admin {$adminName} pre-approved cash advance #{$requestId}");
            } else {
                mysqli_stmt_close($updateStmt);
                echo json_encode(['success' => false, 'message' => 'Request not found or already processed']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
        }
        exit();
    }
    
    // Load leave requests
    if ($_POST['action'] === 'load_leave_requests') {
        $checkTable = @mysqli_query($db, "SHOW TABLES LIKE 'leave_requests'");
        if (!$checkTable || mysqli_num_rows($checkTable) === 0) {
            echo json_encode([
                'success' => true,
                'requests' => [],
                'counts' => ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'all' => 0]
            ]);
            exit();
        }
        
        $status = isset($_POST['status']) ? $_POST['status'] : 'pending';
        
        $whereClause = "WHERE 1=1";
        if ($status !== 'all') {
            $whereClause = "WHERE l.status = '" . mysqli_real_escape_string($db, $status) . "'";
        }
        
        $sql = "SELECT l.*, e.first_name, e.last_name 
                FROM leave_requests l 
                LEFT JOIN employees e ON l.employee_id = e.id 
                $whereClause 
                ORDER BY l.requested_at DESC";
        
        $result = @mysqli_query($db, $sql);
        $requests = [];
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $initials = strtoupper(substr($row['first_name'] ?? '', 0, 1) . substr($row['last_name'] ?? '', 0, 1));
                
                $requests[] = [
                    'id' => $row['id'],
                    'employee_id' => $row['employee_id'],
                    'employee_name' => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
                    'employee_initials' => $initials,
                    'leave_date' => $row['leave_date'],
                    'leave_type' => $row['leave_type'],
                    'days_requested' => $row['days_requested'],
                    'reason' => $row['reason'],
                    'status' => $row['status'],
                    'requested_at' => $row['requested_at'],
                    'approved_by' => $row['approved_by'],
                    'approved_at' => $row['approved_at'],
                    'rejection_reason' => $row['rejection_reason']
                ];
            }
        }
        
        // Get counts for tabs
        $countsSql = "SELECT status, COUNT(*) as cnt FROM leave_requests GROUP BY status";
        $countsResult = mysqli_query($db, $countsSql);
        $counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'all' => 0];
        
        if ($countsResult) {
            while ($row = mysqli_fetch_assoc($countsResult)) {
                $counts[$row['status']] = intval($row['cnt']);
                $counts['all'] += intval($row['cnt']);
            }
        }
        
        echo json_encode([
            'success' => true,
            'requests' => $requests,
            'counts' => $counts
        ]);
        exit();
    }
    
    // Approve leave request
    if ($_POST['action'] === 'approve_leave') {
        try {
            $requestId = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
            $adminName = $_SESSION['username'] ?? 'Admin';
            $adminId = $_SESSION['employee_id'] ?? 0;
            
            if ($requestId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
                exit();
            }
            
            // Get request details first for notification
            $getSql = "SELECT * FROM leave_requests WHERE id = ? AND status = 'pending' LIMIT 1";
            $getStmt = mysqli_prepare($db, $getSql);
            mysqli_stmt_bind_param($getStmt, 'i', $requestId);
            mysqli_stmt_execute($getStmt);
            $getResult = mysqli_stmt_get_result($getStmt);
            $requestDetails = mysqli_fetch_assoc($getResult);
            mysqli_stmt_close($getStmt);
            
            if (!$requestDetails) {
                echo json_encode(['success' => false, 'message' => 'Request not found or already processed']);
                exit();
            }
            
            // Update request status
            $updateSql = "UPDATE leave_requests SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ? AND status = 'pending'";
            $updateStmt = mysqli_prepare($db, $updateSql);
            mysqli_stmt_bind_param($updateStmt, 'si', $adminName, $requestId);
            
            if (mysqli_stmt_execute($updateStmt) && mysqli_stmt_affected_rows($updateStmt) > 0) {
                mysqli_stmt_close($updateStmt);
                
                // Create notification for the employee
                $employeeId = intval($requestDetails['employee_id']);
                $notifTitle = "Leave Request Approved";
                $notifMessage = "Your leave request for {$requestDetails['days_requested']} day(s) on {$requestDetails['leave_date']} ({$requestDetails['leave_type']}) has been approved.";
                $notifType = 'leave_approved';
                
                $notifSql = "INSERT INTO employee_notifications (employee_id, leave_request_id, notification_type, title, message, is_read, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())";
                $notifStmt = mysqli_prepare($db, $notifSql);
                if ($notifStmt) {
                    mysqli_stmt_bind_param($notifStmt, 'iisss', $employeeId, $requestId, $notifType, $notifTitle, $notifMessage);
                    mysqli_stmt_execute($notifStmt);
                    mysqli_stmt_close($notifStmt);
                    
                    // Send push notification
                    sendPushNotification($db, $employeeId, $notifTitle, $notifMessage, '/employee/my_notifications.php');
                }
                
                // Deduct leave days from employee balance
                $deductSql = "UPDATE employee_leaves SET remaining_leaves = remaining_leaves - ?, used_leaves = used_leaves + ? 
                             WHERE employee_id = ? AND remaining_leaves >= ?";
                $deductStmt = mysqli_prepare($db, $deductSql);
                $days = floatval($requestDetails['days_requested']);
                mysqli_stmt_bind_param($deductStmt, 'ddid', $days, $days, $employeeId, $days);
                mysqli_stmt_execute($deductStmt);
                mysqli_stmt_close($deductStmt);
                
                // Log the transaction
                $transSql = "INSERT INTO leave_transactions (employee_id, transaction_type, days, balance_after, reason, created_by, created_at) 
                            VALUES (?, 'used', ?, (SELECT remaining_leaves FROM employee_leaves WHERE employee_id = ?), ?, ?, NOW())";
                $transStmt = mysqli_prepare($db, $transSql);
                $reason = "Leave approved: " . $requestDetails['leave_date'];
                mysqli_stmt_bind_param($transStmt, 'idisi', $employeeId, $days, $employeeId, $reason, $adminId);
                mysqli_stmt_execute($transStmt);
                mysqli_stmt_close($transStmt);
                
                echo json_encode(['success' => true, 'message' => 'Leave request approved successfully']);
                logActivity($db, 'Leave Approved', "Admin {$adminName} approved leave #{$requestId} for {$requestDetails['days_requested']} day(s)");
            } else {
                mysqli_stmt_close($updateStmt);
                echo json_encode(['success' => false, 'message' => 'Request not found or already processed']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
        }
        exit();
    }
    
    // Reject leave request
    if ($_POST['action'] === 'reject_leave') {
        try {
            $requestId = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
            $rejectionReason = isset($_POST['rejection_reason']) ? trim($_POST['rejection_reason']) : '';
            $adminName = $_SESSION['username'] ?? 'Admin';
            
            if ($requestId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
                exit();
            }
            
            // Get request details first for notification
            $getSql = "SELECT * FROM leave_requests WHERE id = ? AND status = 'pending' LIMIT 1";
            $getStmt = mysqli_prepare($db, $getSql);
            mysqli_stmt_bind_param($getStmt, 'i', $requestId);
            mysqli_stmt_execute($getStmt);
            $getResult = mysqli_stmt_get_result($getStmt);
            $requestDetails = mysqli_fetch_assoc($getResult);
            mysqli_stmt_close($getStmt);
            
            if (!$requestDetails) {
                echo json_encode(['success' => false, 'message' => 'Request not found or already processed']);
                exit();
            }
            
            // Update request status
            $updateSql = "UPDATE leave_requests SET status = 'rejected', approved_by = ?, approved_at = NOW(), rejection_reason = ? WHERE id = ? AND status = 'pending'";
            $updateStmt = mysqli_prepare($db, $updateSql);
            mysqli_stmt_bind_param($updateStmt, 'ssi', $adminName, $rejectionReason, $requestId);
            
            if (mysqli_stmt_execute($updateStmt) && mysqli_stmt_affected_rows($updateStmt) > 0) {
                mysqli_stmt_close($updateStmt);
                
                // Create notification for the employee
                $employeeId = intval($requestDetails['employee_id']);
                $reasonText = $rejectionReason ? " Reason: {$rejectionReason}" : "";
                $notifTitle = "Leave Request Rejected";
                $notifMessage = "Your leave request for {$requestDetails['days_requested']} day(s) on {$requestDetails['leave_date']} ({$requestDetails['leave_type']}) was rejected." . $reasonText;
                $notifType = 'leave_rejected';
                
                $notifSql = "INSERT INTO employee_notifications (employee_id, leave_request_id, notification_type, title, message, is_read, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())";
                $notifStmt = mysqli_prepare($db, $notifSql);
                if ($notifStmt) {
                    mysqli_stmt_bind_param($notifStmt, 'iisss', $employeeId, $requestId, $notifType, $notifTitle, $notifMessage);
                    mysqli_stmt_execute($notifStmt);
                    mysqli_stmt_close($notifStmt);
                    
                    // Send push notification
                    sendPushNotification($db, $employeeId, $notifTitle, $notifMessage, '/employee/my_notifications.php');
                }
                
                echo json_encode(['success' => true, 'message' => 'Leave request rejected']);
                logActivity($db, 'Leave Rejected', "Admin {$adminName} rejected leave #{$requestId}. Reason: {$rejectionReason}");
            } else {
                mysqli_stmt_close($updateStmt);
                echo json_encode(['success' => false, 'message' => 'Request not found or already processed']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
        }
        exit();
    }
}

// Get initial pending count
$pendingCount = getPendingOvertimeCount($db);
$pendingCashAdvanceCount = getPendingCashAdvanceCount($db);
$pendingLeaveCount = getPendingLeaveCount($db);
$totalPendingCount = $pendingCount + $pendingCashAdvanceCount + $pendingLeaveCount;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Notifications — JAJR Attendance</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="css/notification.css">
    <link rel="stylesheet" href="css/light-theme.css">
    <style>
        .request-type-tabs {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 12px;
        }
        
        .type-tab {
            background: #2a2a2a;
            border: 1px solid #444;
            color: #888;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .type-tab:hover {
            background: #333;
            color: #fff;
            border-color: #666;
        }
        
        .type-tab.active {
            background: #FFD700;
            color: #000;
            border-color: #FFD700;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        }
        
        .type-count {
            background: rgba(255, 255, 255, 0.2);
            color: inherit;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
        }
        
        .amount-highlight {
            color: #FFD700;
            font-weight: 700;
            font-size: 16px;
        }
        
        .request-type {
            color: #888;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        /* View-only badge */
        .view-only-badge {
            background: rgba(100, 100, 100, 0.3);
            border: 1px solid #666;
            color: #aaa;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        /* Light theme adjustments */
        body.light-theme .request-type-tabs {
            border-bottom-color: #ddd;
        }
        
        body.light-theme .type-tab {
            background: #f5f5f5;
            border-color: #ddd;
            color: #666;
        }
        
        body.light-theme .type-tab:hover {
            background: #eee;
            color: #333;
        }
        
        body.light-theme .type-tab.active {
            background: #FFD700;
            color: #000;
            border-color: #FFD700;
        }
    </style>
    <script src="js/theme.js"></script>
</head>
<body>
    <div class="app-shell">
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-container">
                <div class="notification-header">
                    <h1><i class="fas fa-bell"></i> Admin Notifications</h1>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span class="view-only-badge" style="background: linear-gradient(135deg, rgba(255, 152, 0, 0.2) 0%, rgba(255, 152, 0, 0.05) 100%); border-color: #FF9800; color: #FF9800;">
                            <i class="fas fa-check-double"></i> Can Pre-Approve
                        </span>
                        <div class="pending-badge">
                            <span class="badge-count" id="pendingBadge"><?php echo $totalPendingCount; ?></span>
                            <span class="badge-label">Pending</span>
                        </div>
                    </div>
                </div>
                
                <div style="background: rgba(255, 215, 0, 0.1); border: 1px solid rgba(255, 215, 0, 0.3); border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; color: #ccc; font-size: 13px;">
                    <i class="fas fa-info-circle" style="color: #FFD700; margin-right: 8px;"></i>
                    As an Admin, you can <strong>pre-approve</strong> requests. Super Admin will then do the final approval.
                </div>
                
                <div class="request-type-tabs">
                    <button class="type-tab active" data-type="overtime" onclick="switchRequestType('overtime')">
                        <i class="fas fa-clock"></i> Overtime
                        <span class="type-count" id="type-count-overtime"><?php echo $pendingCount; ?></span>
                    </button>
                    <button class="type-tab" data-type="cash_advance" onclick="switchRequestType('cash_advance')">
                        <i class="fas fa-money-bill-wave"></i> Cash Advance
                        <span class="type-count" id="type-count-cash-advance"><?php echo $pendingCashAdvanceCount; ?></span>
                    </button>
                    <button class="type-tab" data-type="leave" onclick="switchRequestType('leave')">
                        <i class="fas fa-umbrella-beach"></i> Leave
                        <span class="type-count" id="type-count-leave"><?php echo $pendingLeaveCount; ?></span>
                    </button>
                </div>
                
                <div class="notification-tabs">
                    <button class="tab-btn active" data-status="pending" onclick="switchTab('pending')">
                        Pending (<span id="count-pending">0</span>)
                    </button>
                    <button class="tab-btn" data-status="pre_approved" onclick="switchTab('pre_approved')">
                        Pre-Approved (<span id="count-pre-approved">0</span>)
                    </button>
                    <button class="tab-btn" data-status="approved" onclick="switchTab('approved')">
                        Approved (<span id="count-approved">0</span>)
                    </button>
                    <button class="tab-btn" data-status="rejected" onclick="switchTab('rejected')">
                        Rejected (<span id="count-rejected">0</span>)
                    </button>
                    <button class="tab-btn" data-status="all" onclick="switchTab('all')">
                        All (<span id="count-all">0</span>)
                    </button>
                </div>
                
                <div class="notification-container" id="requestsContainer">
                    <div class="loading-state">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Loading requests...</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        let currentTab = 'pending';
        let currentRequestType = 'overtime';
        let currentRequests = [];
        
        function switchRequestType(type) {
            currentRequestType = type;
            document.querySelectorAll('.type-tab').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.type === type);
            });
            loadRequests(currentTab);
        }
        
        function switchTab(status) {
            currentTab = status;
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.status === status);
            });
            loadRequests(status);
        }
        
        async function loadRequests(status) {
            const container = document.getElementById('requestsContainer');
            container.innerHTML = `
                <div class="loading-state">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading requests...</p>
                </div>
            `;
            
            try {
                const formData = new FormData();
                if (currentRequestType === 'cash_advance') {
                    formData.append('action', 'load_cash_advance_requests');
                } else if (currentRequestType === 'leave') {
                    formData.append('action', 'load_leave_requests');
                } else {
                    formData.append('action', 'load_requests');
                }
                formData.append('status', status);
                
                const response = await fetch('admin_notification.php', {
                    method: 'POST',
                    body: formData
                });
                
                const text = await response.text();
                console.log('DEBUG: Raw response:', text.substring(0, 500));
                
                let data = null;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('DEBUG: JSON parse error:', e);
                    console.error('DEBUG: Full response:', text);
                    throw new Error('Invalid JSON response from server');
                }
                
                if (data.success) {
                    currentRequests = data.requests;
                    updateCounts(data.counts);
                    if (currentRequestType === 'cash_advance') {
                        renderCashAdvanceRequests(data.requests);
                    } else if (currentRequestType === 'leave') {
                        renderLeaveRequests(data.requests);
                    } else {
                        renderRequests(data.requests);
                    }
                } else {
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-exclamation-circle"></i>
                            <p>${data.message || 'Failed to load requests'}</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading requests:', error);
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>Error loading requests. Check console for details.</p>
                    </div>
                `;
            }
        }
        
        function renderCashAdvanceRequests(requests) {
            const container = document.getElementById('requestsContainer');
            
            if (requests.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <p>No ${currentTab} cash advance requests</p>
                    </div>
                `;
                return;
            }
            
            let html = '<div class="requests-grid">';
            
            requests.forEach(request => {
                const statusClass = request.status;
                const statusIcon = request.status.toLowerCase() === 'pending' ? 'fa-clock' : 
                                  request.status.toLowerCase() === 'pre_approved' ? 'fa-check-double' :
                                  request.status.toLowerCase() === 'approved' ? 'fa-check' : 'fa-times';
                const amountFormatted = new Intl.NumberFormat('en-PH', {
                    style: 'currency',
                    currency: 'PHP'
                }).format(request.amount);
                
                html += `
                    <div class="request-card ${statusClass}" data-request-id="${request.id}">
                        <div class="request-header">
                            <div class="employee-info">
                                <div class="employee-avatar">
                                    ${request.employee_avatar ? 
                                        `<img src="${request.employee_avatar}" alt="">` : 
                                        `<span class="initials">${request.employee_initials}</span>`
                                    }
                                </div>
                                <div class="employee-details">
                                    <h4>${escapeHtml(request.employee_name)}</h4>
                                    <span class="request-type"><i class="fas fa-money-bill-wave"></i> Cash Advance</span>
                                </div>
                            </div>
                            <div class="status-badge ${statusClass}">
                                <i class="fas ${statusIcon}"></i>
                                ${request.status === 'pre_approved' ? 'Pre-Approved' : request.status.charAt(0).toUpperCase() + request.status.slice(1)}
                            </div>
                        </div>
                        
                        <div class="request-body">
                            <div class="info-row">
                                <span class="label">Amount:</span>
                                <span class="value amount-highlight">${amountFormatted}</span>
                            </div>
                            <div class="info-row reason">
                                <span class="label">Reason:</span>
                                <span class="value">${escapeHtml(request.reason)}</span>
                            </div>
                            <div class="info-row">
                                <span class="label">Request Date:</span>
                                <span class="value">${formatDate(request.request_date)}</span>
                            </div>
                            ${request.rejection_reason ? `
                                <div class="info-row rejection">
                                    <span class="label">Rejection Reason:</span>
                                    <span class="value">${escapeHtml(request.rejection_reason)}</span>
                                </div>
                            ` : ''}
                            ${request.approved_by ? `
                                <div class="info-row meta">
                                    <span class="label">${request.status.toLowerCase() === 'approved' ? 'Approved' : 'Pre-Approved'} by:</span>
                                    <span class="value">${escapeHtml(request.approved_by)} on ${formatDateTime(request.approved_at)}</span>
                                </div>
                            ` : ''}
                        </div>
                        
                        ${request.status.toLowerCase() === 'pending' ? `
                            <div class="request-actions">
                                <button class="btn-approve" onclick="preApproveCashAdvance(${request.id})" style="background: linear-gradient(180deg, #FF9800 0%, #F57C00 100%);">
                                    <i class="fas fa-check-double"></i> Pre-Approve
                                </button>
                            </div>
                        ` : ''}
                    </div>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
        }
        
        function updateCounts(counts) {
            document.getElementById('count-pending').textContent = counts.pending || 0;
            document.getElementById('count-pre-approved').textContent = counts.pre_approved || 0;
            document.getElementById('count-approved').textContent = counts.approved || 0;
            document.getElementById('count-rejected').textContent = counts.rejected || 0;
            document.getElementById('count-all').textContent = counts.all || 0;
            const pendingAndPreApproved = (counts.pending || 0) + (counts.pre_approved || 0);
            document.getElementById('pendingBadge').textContent = pendingAndPreApproved;
        }
        
        function renderRequests(requests) {
            const container = document.getElementById('requestsContainer');
            
            if (requests.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <p>No ${currentTab} overtime requests</p>
                    </div>
                `;
                return;
            }
            
            let html = '<div class="requests-grid">';
            
            requests.forEach(request => {
                const statusClass = request.status;
                const statusIcon = request.status.toLowerCase() === 'pending' ? 'fa-clock' : 
                                  request.status.toLowerCase() === 'pre_approved' ? 'fa-check-double' :
                                  request.status.toLowerCase() === 'approved' ? 'fa-check' : 'fa-times';
                
                html += `
                    <div class="request-card ${statusClass}" data-request-id="${request.id}">
                        <div class="request-header">
                            <div class="employee-info">
                                <div class="employee-avatar">
                                    ${request.employee_avatar ? 
                                        `<img src="${request.employee_avatar}" alt="">` : 
                                        `<span class="initials">${request.employee_initials}</span>`
                                    }
                                </div>
                                <div class="employee-details">
                                    <h4>${escapeHtml(request.employee_name)}</h4>
                                    <span class="branch-name"><i class="fas fa-building"></i> ${escapeHtml(request.branch_name)}</span>
                                </div>
                            </div>
                            <div class="status-badge ${statusClass}">
                                <i class="fas ${statusIcon}"></i>
                                ${request.status === 'pre_approved' ? 'Pre-Approved' : request.status.charAt(0).toUpperCase() + request.status.slice(1)}
                            </div>
                        </div>
                        
                        <div class="request-body">
                            <div class="info-row">
                                <span class="label">Date:</span>
                                <span class="value">${formatDate(request.request_date)}</span>
                            </div>
                            <div class="info-row">
                                <span class="label">Hours Requested:</span>
                                <span class="value hours">${request.requested_hours} hrs</span>
                            </div>
                            <div class="info-row reason">
                                <span class="label">Reason:</span>
                                <span class="value">${escapeHtml(request.overtime_reason)}</span>
                            </div>
                            ${request.rejection_reason ? `
                                <div class="info-row rejection">
                                    <span class="label">Rejection Reason:</span>
                                    <span class="value">${escapeHtml(request.rejection_reason)}</span>
                                </div>
                            ` : ''}
                            <div class="info-row meta">
                                <span class="label">Requested:</span>
                                <span class="value">${formatDateTime(request.requested_at)}</span>
                            </div>
                            ${request.approved_by ? `
                                <div class="info-row meta">
                                    <span class="label">${request.status.toLowerCase() === 'approved' ? 'Approved' : 'Pre-Approved'} by:</span>
                                    <span class="value">${escapeHtml(request.approved_by)} on ${formatDateTime(request.approved_at)}</span>
                                </div>
                            ` : ''}
                        </div>
                        
                        ${request.status.toLowerCase() === 'pending' ? `
                            <div class="request-actions">
                                <button class="btn-approve" onclick="preApproveRequest(${request.id})" style="background: linear-gradient(180deg, #FF9800 0%, #F57C00 100%);">
                                    <i class="fas fa-check-double"></i> Pre-Approve
                                </button>
                            </div>
                        ` : ''}
                    </div>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            return String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
        
        function formatDate(dateStr) {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }
        
        function formatDateTime(dateTimeStr) {
            if (!dateTimeStr) return '';
            const date = new Date(dateTimeStr);
            return date.toLocaleString('en-US', { 
                month: 'short', 
                day: 'numeric', 
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        async function preApproveRequest(requestId) {
            if (!confirm('Pre-approve this overtime request?\n\nThis will mark it as "Pre-Approved" and notify Super Admin for final approval.')) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'pre_approve_request');
                formData.append('request_id', requestId);
                
                const response = await fetch('admin_notification.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast(data.message, 'success');
                    loadRequests(currentTab);
                    updatePendingBadge();
                } else {
                    showToast(data.message || 'Failed to pre-approve request', 'error');
                }
            } catch (error) {
                console.error('Error pre-approving request:', error);
                showToast('Error pre-approving request', 'error');
            }
        }
        
        async function preApproveCashAdvance(requestId) {
            if (!confirm('Pre-approve this cash advance request?\n\nThis will mark it as "Pre-Approved" and notify Super Admin for final approval.')) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'pre_approve_cash_advance');
                formData.append('request_id', requestId);
                
                const response = await fetch('admin_notification.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast(data.message, 'success');
                    loadRequests(currentTab);
                    updatePendingBadge();
                } else {
                    showToast(data.message || 'Failed to pre-approve request', 'error');
                }
            } catch (error) {
                console.error('Error pre-approving cash advance:', error);
                showToast('Error pre-approving request', 'error');
            }
        }
        
        // Render Leave Requests
        function renderLeaveRequests(requests) {
            const container = document.getElementById('requestsContainer');
            
            if (requests.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <p>No ${currentTab} leave requests</p>
                    </div>
                `;
                return;
            }
            
            let html = '<div class="requests-grid">';
            
            requests.forEach(request => {
                const statusClass = request.status;
                const statusIcon = request.status.toLowerCase() === 'pending' ? 'fa-clock' : 
                                  request.status.toLowerCase() === 'approved' ? 'fa-check' : 'fa-times';
                
                html += `
                    <div class="request-card ${statusClass}" data-request-id="${request.id}">
                        <div class="request-header">
                            <div class="employee-info">
                                <div class="employee-avatar">
                                    ${request.employee_avatar ? 
                                        `<img src="${request.employee_avatar}" alt="">` : 
                                        `<span class="initials">${request.employee_initials}</span>`
                                    }
                                </div>
                                <div class="employee-details">
                                    <h4>${escapeHtml(request.employee_name)}</h4>
                                    <span class="request-type"><i class="fas fa-umbrella-beach"></i> Leave Request</span>
                                </div>
                            </div>
                            <div class="status-badge ${statusClass}">
                                <i class="fas ${statusIcon}"></i>
                                ${request.status.charAt(0).toUpperCase() + request.status.slice(1)}
                            </div>
                        </div>
                        
                        <div class="request-body">
                            <div class="info-row">
                                <span class="label">Leave Date:</span>
                                <span class="value">${formatDate(request.leave_date)}</span>
                            </div>
                            <div class="info-row">
                                <span class="label">Leave Type:</span>
                                <span class="value">${escapeHtml(request.leave_type)}</span>
                            </div>
                            <div class="info-row">
                                <span class="label">Days Requested:</span>
                                <span class="value hours">${request.days_requested} day(s)</span>
                            </div>
                            <div class="info-row reason">
                                <span class="label">Reason:</span>
                                <span class="value">${escapeHtml(request.reason)}</span>
                            </div>
                            ${request.rejection_reason ? `
                                <div class="info-row rejection">
                                    <span class="label">Rejection Reason:</span>
                                    <span class="value">${escapeHtml(request.rejection_reason)}</span>
                                </div>
                            ` : ''}
                            <div class="info-row meta">
                                <span class="label">Requested:</span>
                                <span class="value">${formatDateTime(request.requested_at)}</span>
                            </div>
                            ${request.approved_by ? `
                                <div class="info-row meta">
                                    <span class="label">${request.status.toLowerCase() === 'approved' ? 'Approved' : 'Rejected'} by:</span>
                                    <span class="value">${escapeHtml(request.approved_by)} on ${formatDateTime(request.approved_at)}</span>
                                </div>
                            ` : ''}
                        </div>
                        
                        ${request.status.toLowerCase() === 'pending' ? `
                            <div class="request-actions">
                                <button class="btn-approve" onclick="approveLeave(${request.id})">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                                <button class="btn-reject" onclick="showRejectLeaveModal(${request.id})">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </div>
                        ` : ''}
                    </div>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
        }
        
        async function approveLeave(requestId) {
            if (!confirm('Approve this leave request?')) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'approve_leave');
                formData.append('request_id', requestId);
                
                const response = await fetch('admin_notification.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast(data.message, 'success');
                    loadRequests(currentTab);
                    updatePendingBadge();
                } else {
                    showToast(data.message || 'Failed to approve leave request', 'error');
                }
            } catch (error) {
                console.error('Error approving leave:', error);
                showToast('Error approving leave request', 'error');
            }
        }
        
        async function showRejectLeaveModal(requestId) {
            const reason = prompt('Enter rejection reason (optional):');
            if (reason === null) return; // User cancelled
            
            try {
                const formData = new FormData();
                formData.append('action', 'reject_leave');
                formData.append('request_id', requestId);
                formData.append('rejection_reason', reason);
                
                const response = await fetch('admin_notification.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast(data.message, 'success');
                    loadRequests(currentTab);
                    updatePendingBadge();
                } else {
                    showToast(data.message || 'Failed to reject leave request', 'error');
                }
            } catch (error) {
                console.error('Error rejecting leave:', error);
                showToast('Error rejecting leave request', 'error');
            }
        }
        
        function showToast(message, type) {
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#4CAF50' : '#F44336'};
                color: white;
                padding: 16px 24px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                z-index: 10000;
                display: flex;
                align-items: center;
                gap: 10px;
                font-weight: 500;
                animation: slideIn 0.3s ease;
            `;
            toast.innerHTML = `
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                <span>${message}</span>
            `;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }
        
        async function updatePendingBadge() {
            try {
                // Get overtime count
                const formData = new FormData();
                formData.append('action', 'load_requests');
                formData.append('status', 'pending');
                const response = await fetch('admin_notification.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                // Get cash advance count
                const caFormData = new FormData();
                caFormData.append('action', 'load_cash_advance_requests');
                caFormData.append('status', 'pending');
                const caResponse = await fetch('admin_notification.php', {
                    method: 'POST',
                    body: caFormData
                });
                const caData = await caResponse.json();
                
                // Get leave count
                const leaveFormData = new FormData();
                leaveFormData.append('action', 'load_leave_requests');
                leaveFormData.append('status', 'pending');
                const leaveResponse = await fetch('admin_notification.php', {
                    method: 'POST',
                    body: leaveFormData
                });
                const leaveData = await leaveResponse.json();
                
                const otPending = data.success ? ((data.counts?.pending || 0) + (data.counts?.pre_approved || 0)) : 0;
                const caPending = caData.success ? ((caData.counts?.pending || 0) + (caData.counts?.pre_approved || 0)) : 0;
                const leavePending = leaveData.success ? (leaveData.counts?.pending || 0) : 0;
                
                document.getElementById('pendingBadge').textContent = otPending + caPending + leavePending;
                document.getElementById('type-count-overtime').textContent = otPending;
                document.getElementById('type-count-cash-advance').textContent = caPending;
                document.getElementById('type-count-leave').textContent = leavePending;
            } catch (e) {
                console.error('Error updating badge:', e);
            }
        }
        
        // Load initial data
        document.addEventListener('DOMContentLoaded', () => {
            loadRequests('pending');
        });
        
        // Auto-refresh every 30 seconds
        setInterval(() => {
            loadRequests(currentTab);
        }, 30000);
    </script>
</body>
</html>
