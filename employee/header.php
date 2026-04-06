<?php
/**
 * Unified Header Component
 * Usage: Set $pageTitle and $pageIcon, then include this file
 * Example:
 *   $pageTitle = "Dashboard";
 *   $pageIcon = "fa-chart-line";
 *   include __DIR__ . '/header.php';
 */

// Ensure required variables exist
if (!isset($pageTitle)) {
    $pageTitle = ucfirst(str_replace(['_', '-'], ' ', basename($_SERVER['PHP_SELF'], '.php')));
}
if (!isset($pageIcon)) {
    $pageIcon = 'fa-file';
}

// Get user data from session
$currentUserName = $_SESSION['username'] ?? 'User';
$userRole = $_SESSION['position'] ?? 'Employee';
$userAvatar = $_SESSION['profile_image'] ?? '';
$employeeId = $_SESSION['employee_id'] ?? 0;

// Determine notification destination based on role
$isSuperAdmin = in_array($userRole, ['Super Admin', 'Developer']);
$isAdmin = ($userRole === 'Admin');
$isEngineer = ($userRole === 'Engineer');

if ($isSuperAdmin || $isDeveloper) {
    $notifPage = 'notification.php';
} elseif ($isAdmin) {
    $notifPage = 'admin_notification.php';
} else {
    $notifPage = 'my_notifications.php';
}

// Get notification count and recent notifications
$notifCount = 0;
$recentNotifications = [];
$consecutiveIssues = [];

if (isset($db) && $employeeId) {
    // Try to use existing function from sidebar
    if (function_exists('getUnreadNotificationCount')) {
        $notifCount = getUnreadNotificationCount($db, $employeeId);
    } else {
        // Fallback query
        $checkTable = @mysqli_query($db, "SHOW TABLES LIKE 'employee_notifications'");
        if ($checkTable && mysqli_num_rows($checkTable) > 0) {
            $sql = "SELECT COUNT(*) as cnt FROM employee_notifications WHERE employee_id = ? AND is_read = 0";
            $stmt = mysqli_prepare($db, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'i', $employeeId);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($result);
                $notifCount = intval($row['cnt'] ?? 0);
                mysqli_stmt_close($stmt);
            }
        }
    }
    
    // For admin/engineer roles, also check approval notifications
    if (($isAdmin || $isSuperAdmin || $isEngineer) && function_exists('getPendingApprovalCount')) {
        $notifCount += getPendingApprovalCount($db);
    }
    
    // For admin/engineer roles, check consecutive late/absent issues
    if (($isAdmin || $isSuperAdmin || $isEngineer)) {
        $consecutiveCount = 0;
        
        // Get all active workers
        $workerQuery = "SELECT e.id, e.first_name, e.last_name, e.employee_code, b.branch_name
                       FROM employees e
                       LEFT JOIN branches b ON e.branch_id = b.id
                       WHERE e.status = 'Active' AND e.position = 'Worker'";
        $workerResult = mysqli_query($db, $workerQuery);
        
        if ($workerResult) {
            while ($worker = mysqli_fetch_assoc($workerResult)) {
                // Get last 14 days attendance excluding Sundays
                $attendanceQuery = "SELECT attendance_date, status, time_in
                                   FROM attendance 
                                   WHERE employee_id = {$worker['id']}
                                     AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
                                     AND DAYOFWEEK(attendance_date) != 1
                                   ORDER BY attendance_date DESC";
                
                $attResult = mysqli_query($db, $attendanceQuery);
                $attendanceRecords = [];
                
                if ($attResult) {
                    while ($att = mysqli_fetch_assoc($attResult)) {
                        $attendanceRecords[] = $att;
                    }
                    mysqli_free_result($attResult);
                }
                
                // Check for 3+ consecutive issues
                if (count($attendanceRecords) >= 3) {
                    $consecutiveCount = 0;
                    $issueDates = [];
                    $issueStatuses = [];
                    
                    foreach ($attendanceRecords as $record) {
                        $status = $record['status'] ?? 'Absent';
                        if (empty($record['time_in']) && in_array($status, ['Present', 'Late'])) {
                            $status = 'Absent';
                        }
                        
                        if (in_array($status, ['Late', 'Absent'])) {
                            $consecutiveCount++;
                            $issueDates[] = $record['attendance_date'];
                            $issueStatuses[] = $status;
                        } else {
                            break;
                        }
                    }
                    
                    if ($consecutiveCount >= 3) {
                        $consecutiveIssues[] = [
                            'worker' => $worker,
                            'consecutive_count' => $consecutiveCount,
                            'dates' => array_slice($issueDates, 0, 5),
                            'statuses' => array_slice($issueStatuses, 0, 5),
                            'branch' => $worker['branch_name'] ?? 'Unknown'
                        ];
                    }
                }
            }
            mysqli_free_result($workerResult);
        }
        
        // Add consecutive issues to notification count
        $notifCount += count($consecutiveIssues);
    }
    
    // Fetch recent notifications for dropdown
    $checkTable = @mysqli_query($db, "SHOW TABLES LIKE 'employee_notifications'");
    if ($checkTable && mysqli_num_rows($checkTable) > 0) {
        $notifQuery = "SELECT n.*
                      FROM employee_notifications n
                      WHERE n.employee_id = ?
                      ORDER BY n.created_at DESC
                      LIMIT 10";
        $stmt = mysqli_prepare($db, $notifQuery);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $employeeId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $recentNotifications[] = $row;
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Helper function for pending approval count if not exists
if (!function_exists('getPendingApprovalCount')) {
    function getPendingApprovalCount($db) {
        if (!$db) return 0;
        $count = 0;
        
        // Check overtime_requests
        $checkTable = @mysqli_query($db, "SHOW TABLES LIKE 'overtime_requests'");
        if ($checkTable && mysqli_num_rows($checkTable) > 0) {
            $result = @mysqli_query($db, "SELECT COUNT(*) as cnt FROM overtime_requests WHERE status IN ('pending', 'pre_approved')");
            if ($result) {
                $row = mysqli_fetch_assoc($result);
                $count += intval($row['cnt'] ?? 0);
            }
        }
        
        return $count;
    }
}

// Helper function to format time ago
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M d', $time);
}

// Helper function to get notification icon
function getNotificationIcon($type) {
    $icons = [
        'cash_advance_pending' => 'fa-money-bill-wave',
        'cash_advance_approved' => 'fa-check-circle',
        'cash_advance_rejected' => 'fa-times-circle',
        'overtime_submitted' => 'fa-clock',
        'overtime_approved' => 'fa-check-circle',
        'overtime_rejected' => 'fa-times-circle',
        'leave_submitted' => 'fa-umbrella-beach',
        'leave_approved' => 'fa-check-circle',
        'leave_rejected' => 'fa-times-circle',
        'transfer_request' => 'fa-exchange-alt',
        'attendance_alert' => 'fa-exclamation-triangle',
        'system' => 'fa-cog',
        'default' => 'fa-bell'
    ];
    return $icons[$type] ?? $icons['default'];
}
?>
<header class="unified-header">
    <div class="header-left">
        <h1 class="page-title">
            <i class="fas <?php echo htmlspecialchars($pageIcon); ?>"></i>
            <?php echo htmlspecialchars($pageTitle); ?>
        </h1>
    </div>
    <div class="header-right">
        <!-- Notification Bell with Dropdown -->
        <div class="notification-dropdown-wrapper">
            <button class="header-notification-btn" id="notificationBell" title="Notifications">
                <i class="fas fa-bell"></i>
                <?php if ($notifCount > 0): ?>
                    <span class="notification-badge-header"><?php echo $notifCount > 99 ? '99+' : $notifCount; ?></span>
                <?php endif; ?>
            </button>
            
            <!-- Notification Dropdown Card -->
            <div class="notification-dropdown-card" id="notificationDropdown">
                <div class="notification-card-header">
                    <h3>Notifications</h3>
                    <div class="notification-tabs">
                        <button class="notif-tab active" data-tab="all">All</button>
                        <button class="notif-tab" data-tab="unread">Unread</button>
                    </div>
                </div>
                
                <div class="notification-list">
                    <?php if (empty($recentNotifications)): ?>
                        <div class="notification-empty">
                            <i class="fas fa-bell-slash"></i>
                            <p>No notifications yet</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentNotifications as $notif): 
                            $isUnread = !$notif['is_read'];
                            $notifIcon = getNotificationIcon($notif['notification_type'] ?? 'default');
                            $timeAgo = timeAgo($notif['created_at']);
                        ?>
                            <a href="<?php echo htmlspecialchars($notifPage); ?>?read=<?php echo $notif['id']; ?>" 
                               class="notification-item <?php echo $isUnread ? 'unread' : ''; ?>">
                                <div class="notification-avatar">
                                    <div class="avatar-placeholder">
                                        <i class="fas <?php echo $notifIcon; ?>"></i>
                                    </div>
                                    <?php if ($isUnread): ?>
                                        <span class="unread-dot"></span>
                                    <?php endif; ?>
                                </div>
                                <div class="notification-content">
                                    <div class="notification-title">
                                        <?php echo htmlspecialchars($notif['title'] ?? 'Notification'); ?>
                                    </div>
                                    <div class="notification-message">
                                        <?php echo htmlspecialchars($notif['message'] ?? ''); ?>
                                    </div>
                                    <div class="notification-time">
                                        <?php echo $timeAgo; ?>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="notification-card-footer">
                    <a href="<?php echo htmlspecialchars($notifPage); ?>" class="view-all-link">
                        View All Notifications <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Profile Section (Clickable) -->
        <a href="settings.php" class="header-profile-link" title="Go to Settings">
            <div class="header-user-info">
                <span class="header-user-name"><?php echo htmlspecialchars($currentUserName); ?></span>
                <span class="header-user-role"><?php echo htmlspecialchars($userRole); ?></span>
            </div>
            <div class="header-avatar">
                <?php if ($userAvatar && file_exists(__DIR__ . '/../' . $userAvatar)): ?>
                    <img src="../<?php echo htmlspecialchars($userAvatar); ?>" alt="Profile">
                <?php else: ?>
                    <i class="fas fa-user"></i>
                <?php endif; ?>
            </div>
        </a>
    </div>
</header>

<script>
    // Notification dropdown toggle
    document.addEventListener('DOMContentLoaded', function() {
        const bell = document.getElementById('notificationBell');
        const dropdown = document.getElementById('notificationDropdown');
        
        if (bell && dropdown) {
            bell.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdown.classList.toggle('show');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!dropdown.contains(e.target) && !bell.contains(e.target)) {
                    dropdown.classList.remove('show');
                }
            });
            
            // Tab switching
            const tabs = dropdown.querySelectorAll('.notif-tab');
            tabs.forEach(tab => {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    tabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    
                    const tabType = this.dataset.tab;
                    const items = dropdown.querySelectorAll('.notification-item');
                    
                    items.forEach(item => {
                        if (tabType === 'unread') {
                            item.style.display = item.classList.contains('unread') ? 'flex' : 'none';
                        } else {
                            item.style.display = 'flex';
                        }
                    });
                });
            });
        }
    });
</script>
