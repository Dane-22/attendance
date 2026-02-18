<?php
// employee/my_notifications.php
// Employee notification center - shows overtime request status updates

// Start output buffering to prevent any accidental output
ob_start();

// Suppress PHP errors for clean JSON output
error_reporting(0);
ini_set('display_errors', 0);

session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Get current user info
$userId = $_SESSION['employee_id'] ?? 0;
$firstName = $_SESSION['first_name'] ?? '';
$lastName = $_SESSION['last_name'] ?? '';

require_once __DIR__ . '/../conn/db_connection.php';
require_once __DIR__ . '/../functions.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Clean output buffer to prevent any previous output
    if (ob_get_level()) {
        ob_clean();
    }
    header('Content-Type: application/json');
    
    // Load notifications
    if ($_POST['action'] === 'load_notifications') {
        $filter = isset($_POST['filter']) ? $_POST['filter'] : 'all';
        
        $whereClause = "WHERE n.employee_id = " . intval($userId);
        if ($filter === 'unread') {
            $whereClause .= " AND n.is_read = 0";
        }
        
        $sql = "SELECT n.*, r.requested_hours, r.request_date, r.branch_name, r.rejection_reason, r.status as request_status,
                       c.amount as ca_amount, c.reason as ca_reason, c.status as ca_status, c.rejection_reason as ca_rejection_reason
                FROM employee_notifications n
                LEFT JOIN overtime_requests r ON n.overtime_request_id = r.id
                LEFT JOIN cash_advances c ON n.cash_advance_id = c.id
                $whereClause
                ORDER BY n.created_at DESC";
        
        $result = @mysqli_query($db, $sql);
        
        // If query failed (e.g., cash_advance_id column doesn't exist), fallback to simpler query
        if (!$result) {
            $sql = "SELECT n.*, r.requested_hours, r.request_date, r.branch_name, r.rejection_reason, r.status as request_status
                    FROM employee_notifications n
                    LEFT JOIN overtime_requests r ON n.overtime_request_id = r.id
                    $whereClause
                    ORDER BY n.created_at DESC";
            $result = @mysqli_query($db, $sql);
        }
        
        $notifications = [];
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $notifications[] = [
                    'id' => $row['id'],
                    'type' => $row['notification_type'],
                    'title' => $row['title'],
                    'message' => $row['message'],
                    'is_read' => (bool)$row['is_read'],
                    'created_at' => $row['created_at'],
                    'request_date' => $row['request_date'],
                    'requested_hours' => $row['requested_hours'],
                    'branch_name' => $row['branch_name'],
                    'rejection_reason' => $row['rejection_reason'],
                    'request_status' => $row['request_status'],
                    'ca_amount' => $row['ca_amount'],
                    'ca_reason' => $row['ca_reason'],
                    'ca_status' => $row['ca_status'],
                    'ca_rejection_reason' => $row['ca_rejection_reason']
                ];
            }
        }
        
        // Get counts
        $unreadCount = 0;
        $totalCount = 0;
        $countSql = "SELECT is_read, COUNT(*) as cnt FROM employee_notifications WHERE employee_id = ? GROUP BY is_read";
        $countStmt = mysqli_prepare($db, $countSql);
        if ($countStmt) {
            mysqli_stmt_bind_param($countStmt, 'i', $userId);
            mysqli_stmt_execute($countStmt);
            $countResult = mysqli_stmt_get_result($countStmt);
            while ($row = mysqli_fetch_assoc($countResult)) {
                if ($row['is_read'] == 0) {
                    $unreadCount = intval($row['cnt']);
                }
                $totalCount += intval($row['cnt']);
            }
            mysqli_stmt_close($countStmt);
        }
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
            'total_count' => $totalCount
        ]);
        exit();
    }
    
    // Mark as read
    if ($_POST['action'] === 'mark_read') {
        $notifId = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
        
        if ($notifId > 0) {
            $sql = "UPDATE employee_notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND employee_id = ?";
            $stmt = mysqli_prepare($db, $sql);
            mysqli_stmt_bind_param($stmt, 'ii', $notifId, $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            logActivity($db, 'Notification Marked Read', "User marked notification #{$notifId} as read");
        }
        
        echo json_encode(['success' => true]);
        exit();
    }
    
    // Mark all as read
    if ($_POST['action'] === 'mark_all_read') {
        $sql = "UPDATE employee_notifications SET is_read = 1, read_at = NOW() WHERE employee_id = ? AND is_read = 0";
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        logActivity($db, 'All Notifications Marked Read', "User marked all notifications as read");
        
        echo json_encode(['success' => true]);
        exit();
    }
    
    // Delete notification
    if ($_POST['action'] === 'delete_notification') {
        $notifId = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
        
        if ($notifId > 0) {
            $sql = "DELETE FROM employee_notifications WHERE id = ? AND employee_id = ?";
            $stmt = mysqli_prepare($db, $sql);
            mysqli_stmt_bind_param($stmt, 'ii', $notifId, $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            logActivity($db, 'Notification Deleted', "User deleted notification #{$notifId}");
        }
        
        echo json_encode(['success' => true]);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Notifications — JAJR Attendance</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="css/my_notifications.css">
    <link rel="stylesheet" href="css/light-theme.css">
    <script src="js/theme.js"></script>
</head>
<body>
    <div class="app-shell">
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-container">
                <div class="notification-header">
                    <h1><i class="fas fa-envelope"></i> My Notifications</h1>
                    <div class="header-actions">
                        <button class="btn-mark-all" onclick="markAllRead()">
                            <i class="fas fa-check-double"></i> Mark All Read
                        </button>
                    </div>
                </div>
                
                <div class="notification-tabs">
                    <button class="tab-btn active" data-filter="all" onclick="switchTab('all')">
                        All (<span id="count-all">0</span>)
                    </button>
                    <button class="tab-btn" data-filter="unread" onclick="switchTab('unread')">
                        Unread (<span id="count-unread">0</span>)
                    </button>
                </div>
                
                <div class="notification-container" id="notificationsContainer">
                    <div class="loading-state">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Loading notifications...</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        let currentFilter = 'all';
        let currentNotifications = [];
        
        function switchTab(filter) {
            currentFilter = filter;
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.filter === filter);
            });
            loadNotifications(filter);
        }
        
        async function loadNotifications(filter = 'all') {
            const container = document.getElementById('notificationsContainer');
            container.innerHTML = `
                <div class="loading-state">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading notifications...</p>
                </div>
            `;
            
            try {
                const formData = new FormData();
                formData.append('action', 'load_notifications');
                formData.append('filter', filter);
                
                const response = await fetch('my_notifications.php', {
                    method: 'POST',
                    body: formData
                });
                
                const text = await response.text();
                let data = null;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Raw server response:', text);
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-exclamation-circle"></i>
                            <p>Server Error (check console for details)</p>
                            <pre style="font-size: 10px; max-width: 100%; overflow: auto; text-align: left; background: #222; padding: 10px; margin-top: 10px;">${escapeHtml(text.substring(0, 500))}</pre>
                        </div>
                    `;
                    return;
                }
                
                if (data.success) {
                    currentNotifications = data.notifications;
                    updateCounts(data.unread_count, data.total_count);
                    renderNotifications(data.notifications);
                } else {
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-exclamation-circle"></i>
                            <p>Failed to load notifications</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading notifications:', error);
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>Error loading notifications</p>
                    </div>
                `;
            }
        }
        
        function updateCounts(unread, total) {
            document.getElementById('count-unread').textContent = unread || 0;
            document.getElementById('count-all').textContent = total || 0;
            // Also update sidebar badge
            const sidebarBadge = document.querySelector('.sidebar .notification-badge');
            if (sidebarBadge) {
                sidebarBadge.textContent = unread || 0;
                sidebarBadge.style.display = unread > 0 ? 'inline-flex' : 'none';
            }
        }
        
        function renderNotifications(notifications) {
            const container = document.getElementById('notificationsContainer');
            
            if (notifications.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No ${currentFilter === 'unread' ? 'unread ' : ''}notifications</p>
                    </div>
                `;
                return;
            }
            
            let html = '<div class="notifications-grid">';
            
            notifications.forEach(notif => {
                // Determine notification type and styling
                let isApproved = false;
                let isPending = false;
                let isCashAdvance = false;
                let statusIcon, statusClass, statusText, metaInfo;
                
                if (notif.type === 'overtime_approved') {
                    isApproved = true;
                    statusIcon = 'fa-check-circle';
                    statusClass = 'approved';
                    statusText = 'APPROVED';
                    metaInfo = `${formatDate(notif.request_date)} • ${notif.requested_hours} hrs`;
                } else if (notif.type === 'overtime_rejected') {
                    statusIcon = 'fa-times-circle';
                    statusClass = 'rejected';
                    statusText = 'REJECTED';
                    metaInfo = `${formatDate(notif.request_date)} • ${notif.requested_hours} hrs`;
                } else if (notif.type === 'cash_advance_pending') {
                    isPending = true;
                    isCashAdvance = true;
                    statusIcon = 'fa-clock';
                    statusClass = 'pending';
                    statusText = 'PENDING';
                    metaInfo = notif.ca_amount ? `Amount: ₱${parseFloat(notif.ca_amount).toLocaleString('en-PH', {minimumFractionDigits: 2})}` : '';
                } else if (notif.type === 'cash_advance_approved') {
                    isApproved = true;
                    isCashAdvance = true;
                    statusIcon = 'fa-check-circle';
                    statusClass = 'approved';
                    statusText = 'APPROVED';
                    metaInfo = notif.ca_amount ? `Amount: ₱${parseFloat(notif.ca_amount).toLocaleString('en-PH', {minimumFractionDigits: 2})}` : '';
                } else if (notif.type === 'cash_advance_rejected') {
                    isCashAdvance = true;
                    statusIcon = 'fa-times-circle';
                    statusClass = 'rejected';
                    statusText = 'REJECTED';
                    metaInfo = notif.ca_amount ? `Amount: ₱${parseFloat(notif.ca_amount).toLocaleString('en-PH', {minimumFractionDigits: 2})}` : '';
                } else {
                    // Default fallback
                    statusIcon = 'fa-info-circle';
                    statusClass = 'info';
                    statusText = 'INFO';
                    metaInfo = '';
                }
                
                const unreadClass = notif.is_read ? '' : 'unread';
                const dotIndicator = notif.is_read ? '' : '<span class="unread-dot"></span>';
                
                html += `
                    <div class="notification-card ${statusClass} ${unreadClass}" data-notification-id="${notif.id}">
                        ${dotIndicator}
                        <div class="notification-icon">
                            <i class="fas ${statusIcon}"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-header-row">
                                <h4>${escapeHtml(notif.title)}</h4>
                                <span class="notification-time">${formatDateTime(notif.created_at)}</span>
                            </div>
                            <p class="notification-message">${escapeHtml(notif.message)}</p>
                            <div class="notification-meta">
                                <span class="status-badge ${statusClass}">${statusText}</span>
                                ${metaInfo ? `<span class="date-info">${metaInfo}</span>` : ''}
                            </div>
                        </div>
                        <div class="notification-actions">
                            ${!notif.is_read ? `
                                <button class="btn-action btn-read" onclick="markRead(${notif.id})" title="Mark as read">
                                    <i class="fas fa-check"></i>
                                </button>
                            ` : ''}
                            <button class="btn-action btn-delete" onclick="deleteNotification(${notif.id})" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
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
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);
            
            if (diffMins < 1) return 'Just now';
            if (diffMins < 60) return `${diffMins}m ago`;
            if (diffHours < 24) return `${diffHours}h ago`;
            if (diffDays < 7) return `${diffDays}d ago`;
            
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }
        
        async function markRead(notificationId) {
            try {
                const formData = new FormData();
                formData.append('action', 'mark_read');
                formData.append('notification_id', notificationId);
                
                const response = await fetch('my_notifications.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    loadNotifications(currentFilter);
                }
            } catch (error) {
                console.error('Error marking as read:', error);
            }
        }
        
        async function markAllRead() {
            if (!confirm('Mark all notifications as read?')) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'mark_all_read');
                
                const response = await fetch('my_notifications.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    loadNotifications(currentFilter);
                }
            } catch (error) {
                console.error('Error marking all as read:', error);
            }
        }
        
        async function deleteNotification(notificationId) {
            if (!confirm('Delete this notification?')) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'delete_notification');
                formData.append('notification_id', notificationId);
                
                const response = await fetch('my_notifications.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    loadNotifications(currentFilter);
                }
            } catch (error) {
                console.error('Error deleting notification:', error);
            }
        }
        
        // Load initial data
        document.addEventListener('DOMContentLoaded', () => {
            loadNotifications('all');
        });
        
        // Auto-refresh every 60 seconds
        setInterval(() => {
            loadNotifications(currentFilter);
        }, 60000);
    </script>
</body>
</html>
