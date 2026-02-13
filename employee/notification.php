<?php
// employee/notification.php
// Super Admin dashboard for overtime request approvals

session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Check if user is Super Admin
$isAdmin = ($_SESSION['position'] ?? '') === 'Super Admin';
if (!$isAdmin) {
    header('Location: dashboard.php');
    exit();
}

require_once __DIR__ . '/../conn/db_connection.php';

// Helper function to get pending count
function getPendingOvertimeCount($db) {
    if (!$db) return 0;
    // Suppress errors and check if table exists first
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

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Clear any previous output and set JSON header
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    
    // Check if table exists for all AJAX operations
    $checkTable = @mysqli_query($db, "SHOW TABLES LIKE 'overtime_requests'");
    if (!$checkTable || mysqli_num_rows($checkTable) === 0) {
        echo json_encode(['success' => false, 'message' => 'Overtime requests table does not exist. Please run the database migration.']);
        exit();
    }
    
    // Load overtime requests
    if ($_POST['action'] === 'load_requests') {
        // Check if table exists
        $checkTable = @mysqli_query($db, "SHOW TABLES LIKE 'overtime_requests'");
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
        
        // Get counts for tabs
        $countsSql = "SELECT status, COUNT(*) as cnt FROM overtime_requests GROUP BY status";
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
    
    // Approve overtime request
    if ($_POST['action'] === 'approve_request') {
        $requestId = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
        $adminName = $_SESSION['username'] ?? 'Admin';
        
        if ($requestId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
            exit();
        }
        
        // Get request details
        $checkSql = "SELECT * FROM overtime_requests WHERE id = ? AND status = 'pending' LIMIT 1";
        $checkStmt = mysqli_prepare($db, $checkSql);
        mysqli_stmt_bind_param($checkStmt, 'i', $requestId);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        $request = mysqli_fetch_assoc($checkResult);
        mysqli_stmt_close($checkStmt);
        
        if (!$request) {
            echo json_encode(['success' => false, 'message' => 'Request not found or already processed']);
            exit();
        }
        
        // Find or create attendance record for today
        $attendanceSql = "SELECT id FROM attendance WHERE employee_id = ? AND attendance_date = CURDATE() ORDER BY id DESC LIMIT 1";
        $attendanceStmt = mysqli_prepare($db, $attendanceSql);
        mysqli_stmt_bind_param($attendanceStmt, 'i', $request['employee_id']);
        mysqli_stmt_execute($attendanceStmt);
        $attendanceResult = mysqli_stmt_get_result($attendanceStmt);
        $attendance = mysqli_fetch_assoc($attendanceResult);
        mysqli_stmt_close($attendanceStmt);
        
        $attendanceId = null;
        
        if ($attendance) {
            $attendanceId = $attendance['id'];
            // Update existing attendance record with overtime hours
            $updateSql = "UPDATE attendance SET total_ot_hrs = ? WHERE id = ?";
            $updateStmt = mysqli_prepare($db, $updateSql);
            mysqli_stmt_bind_param($updateStmt, 'si', $request['requested_hours'], $attendanceId);
            mysqli_stmt_execute($updateStmt);
            mysqli_stmt_close($updateStmt);
        } else {
            // Create new attendance record with overtime
            $insertSql = "INSERT INTO attendance (employee_id, attendance_date, branch_name, status, total_ot_hrs, created_at) 
                          VALUES (?, CURDATE(), ?, 'Present', ?, NOW())";
            $insertStmt = mysqli_prepare($db, $insertSql);
            mysqli_stmt_bind_param($insertStmt, 'iss', $request['employee_id'], $request['branch_name'], $request['requested_hours']);
            mysqli_stmt_execute($insertStmt);
            $attendanceId = mysqli_insert_id($db);
            mysqli_stmt_close($insertStmt);
        }
        
        // Update request status
        $updateRequestSql = "UPDATE overtime_requests SET status = 'approved', approved_by = ?, approved_at = NOW(), attendance_id = ? WHERE id = ?";
        $updateRequestStmt = mysqli_prepare($db, $updateRequestSql);
        mysqli_stmt_bind_param($updateRequestStmt, 'sii', $adminName, $attendanceId, $requestId);
        
        if (mysqli_stmt_execute($updateRequestStmt)) {
            mysqli_stmt_close($updateRequestStmt);
            
            // Insert notification for requester (the person who submitted the request)
            $requesterId = isset($request['requested_by_user_id']) ? intval($request['requested_by_user_id']) : 0;
            if ($requesterId > 0) {
                $notifTitle = "Overtime Approved";
                $notifMessage = "Your overtime request for {$request['requested_by']} on {$request['request_date']} has been approved. Hours: {$request['requested_hours']}, Project: {$request['branch_name']}";
                $notifType = 'overtime_approved';
                
                $notifSql = "INSERT INTO employee_notifications (employee_id, overtime_request_id, notification_type, title, message, is_read, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())";
                $notifStmt = mysqli_prepare($db, $notifSql);
                if ($notifStmt) {
                    mysqli_stmt_bind_param($notifStmt, 'iisss', $requesterId, $requestId, $notifType, $notifTitle, $notifMessage);
                    mysqli_stmt_execute($notifStmt);
                    mysqli_stmt_close($notifStmt);
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Overtime request approved']);
        } else {
            mysqli_stmt_close($updateRequestStmt);
            echo json_encode(['success' => false, 'message' => 'Failed to approve request']);
        }
        exit();
    }
    
    // Reject overtime request
    if ($_POST['action'] === 'reject_request') {
        $requestId = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
        $rejectionReason = isset($_POST['rejection_reason']) ? trim($_POST['rejection_reason']) : '';
        $adminName = $_SESSION['username'] ?? 'Admin';
        
        if ($requestId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
            exit();
        }
        
        // Get request details first for notification
        $getSql = "SELECT * FROM overtime_requests WHERE id = ? AND status = 'pending' LIMIT 1";
        $getStmt = mysqli_prepare($db, $getSql);
        mysqli_stmt_bind_param($getStmt, 'i', $requestId);
        mysqli_stmt_execute($getStmt);
        $getResult = mysqli_stmt_get_result($getStmt);
        $requestDetails = mysqli_fetch_assoc($getResult);
        mysqli_stmt_close($getStmt);
        
        // Update request status
        $updateSql = "UPDATE overtime_requests SET status = 'rejected', approved_by = ?, approved_at = NOW(), rejection_reason = ? WHERE id = ? AND status = 'pending'";
        $updateStmt = mysqli_prepare($db, $updateSql);
        mysqli_stmt_bind_param($updateStmt, 'ssi', $adminName, $rejectionReason, $requestId);
        
        if (mysqli_stmt_execute($updateStmt) && mysqli_stmt_affected_rows($updateStmt) > 0) {
            mysqli_stmt_close($updateStmt);
            
            // Insert notification for requester (the person who submitted the request)
            if ($requestDetails) {
                $requesterId = isset($requestDetails['requested_by_user_id']) ? intval($requestDetails['requested_by_user_id']) : 0;
                if ($requesterId > 0) {
                    $notifTitle = "Overtime Rejected";
                    $reasonText = $rejectionReason ? " Reason: {$rejectionReason}" : "";
                    $notifMessage = "Your overtime request for {$requestDetails['requested_by']} on {$requestDetails['request_date']} was rejected. Hours: {$requestDetails['requested_hours']}, Project: {$requestDetails['branch_name']}.{$reasonText}";
                    $notifType = 'overtime_rejected';
                    
                    $notifSql = "INSERT INTO employee_notifications (employee_id, overtime_request_id, notification_type, title, message, is_read, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())";
                    $notifStmt = mysqli_prepare($db, $notifSql);
                    if ($notifStmt) {
                        mysqli_stmt_bind_param($notifStmt, 'iisss', $requesterId, $requestId, $notifType, $notifTitle, $notifMessage);
                        mysqli_stmt_execute($notifStmt);
                        mysqli_stmt_close($notifStmt);
                    }
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Overtime request rejected']);
        } else {
            mysqli_stmt_close($updateStmt);
            echo json_encode(['success' => false, 'message' => 'Request not found or already processed']);
        }
        exit();
    }
}

// Get initial pending count
$pendingCount = getPendingOvertimeCount($db);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications — JAJR Attendance</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="css/notification.css">
    <link rel="stylesheet" href="css/light-theme.css">
    <script src="js/theme.js"></script>
</head>
<body>
    <div class="app-shell">
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="notification-header">
                <h1><i class="fas fa-bell"></i> Overtime Requests</h1>
                <div class="pending-badge">
                    <span class="badge-count" id="pendingBadge"><?php echo $pendingCount; ?></span>
                    <span class="badge-label">Pending</span>
                </div>
            </div>
            
            <div class="notification-tabs">
                <button class="tab-btn active" data-status="pending" onclick="switchTab('pending')">
                    Pending (<span id="count-pending">0</span>)
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
        </main>
    </div>
    
    <!-- Rejection Modal -->
    <div id="rejectionModal" class="modal-backdrop" style="display: none;">
        <div class="modal-panel">
            <h3>Reject Overtime Request</h3>
            <p>Please provide a reason for rejection:</p>
            <textarea id="rejectionReason" placeholder="Enter rejection reason..."></textarea>
            <div class="modal-actions">
                <button class="btn-cancel" onclick="closeRejectionModal()">Cancel</button>
                <button class="btn-reject" onclick="confirmRejection()">Reject Request</button>
            </div>
        </div>
    </div>
    
    <script>
        let currentTab = 'pending';
        let currentRequests = [];
        let rejectionRequestId = null;
        
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
                formData.append('action', 'load_requests');
                formData.append('status', status);
                
                const response = await fetch('notification.php', {
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
                    renderRequests(data.requests);
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
        
        function updateCounts(counts) {
            document.getElementById('count-pending').textContent = counts.pending || 0;
            document.getElementById('count-approved').textContent = counts.approved || 0;
            document.getElementById('count-rejected').textContent = counts.rejected || 0;
            document.getElementById('count-all').textContent = counts.all || 0;
            document.getElementById('pendingBadge').textContent = counts.pending || 0;
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
                const statusIcon = request.status === 'pending' ? 'fa-clock' : 
                                  request.status === 'approved' ? 'fa-check' : 'fa-times';
                
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
                                ${request.status.charAt(0).toUpperCase() + request.status.slice(1)}
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
                                    <span class="label">${request.status === 'approved' ? 'Approved' : 'Processed'} by:</span>
                                    <span class="value">${escapeHtml(request.approved_by)} on ${formatDateTime(request.approved_at)}</span>
                                </div>
                            ` : ''}
                        </div>
                        
                        ${request.status === 'pending' ? `
                            <div class="request-actions">
                                <button class="btn-approve" onclick="approveRequest(${request.id})">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                                <button class="btn-reject" onclick="showRejectionModal(${request.id})">
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
        
        async function approveRequest(requestId) {
            if (!confirm('Are you sure you want to approve this overtime request?')) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'approve_request');
                formData.append('request_id', requestId);
                
                const response = await fetch('notification.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast(data.message, 'success');
                    loadRequests(currentTab);
                } else {
                    showToast(data.message || 'Failed to approve request', 'error');
                }
            } catch (error) {
                console.error('Error approving request:', error);
                showToast('Error approving request', 'error');
            }
        }
        
        function showRejectionModal(requestId) {
            rejectionRequestId = requestId;
            document.getElementById('rejectionModal').style.display = 'flex';
            document.getElementById('rejectionReason').value = '';
            document.getElementById('rejectionReason').focus();
        }
        
        function closeRejectionModal() {
            document.getElementById('rejectionModal').style.display = 'none';
            rejectionRequestId = null;
        }
        
        async function confirmRejection() {
            if (!rejectionRequestId) return;
            
            const reason = document.getElementById('rejectionReason').value.trim();
            
            try {
                const formData = new FormData();
                formData.append('action', 'reject_request');
                formData.append('request_id', rejectionRequestId);
                formData.append('rejection_reason', reason);
                
                const response = await fetch('notification.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast(data.message, 'success');
                    closeRejectionModal();
                    loadRequests(currentTab);
                } else {
                    showToast(data.message || 'Failed to reject request', 'error');
                }
            } catch (error) {
                console.error('Error rejecting request:', error);
                showToast('Error rejecting request', 'error');
            }
        }
        
        function showToast(message, type) {
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                <span>${message}</span>
            `;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.add('show');
            }, 10);
            
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        
        // Close modal on backdrop click
        document.getElementById('rejectionModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRejectionModal();
            }
        });
        
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
