<?php
/**
 * Admin Dashboard - Attendance Monitoring System
 * Features: Summary Cards, Quick Actions, Data Monitoring
 */

// Start session and include database connection
session_start();
require_once __DIR__ . '/../conn/db_connection.php';

// Check if user is admin
$userRole = isset($_SESSION['position']) ? $_SESSION['position'] : '';
if (!in_array($userRole, ['Admin', 'Super Admin'])) {
    header('Location: select_employee.php');
    exit();
}

// Get current user info
$currentUserId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$currentUserName = isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin';
$currentUserAvatar = isset($_SESSION['profile_image']) ? $_SESSION['profile_image'] : '';

// Initialize variables
$totalEmployees = 0;
$activeBranches = 0;
$transfersToday = 0;
$pendingPayroll = 0;
$recentTransfers = [];
$recentActivity = [];
$dbError = null;

try {
    // 1. Total Employees Count
    $result = mysqli_query($db, "SELECT COUNT(*) as count FROM employees WHERE status = 'Active'");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $totalEmployees = $row['count'];
        mysqli_free_result($result);
    }

    // 2. Active Branches Count
    $result = mysqli_query($db, "SELECT COUNT(*) as count FROM branches WHERE is_active = 1");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $activeBranches = $row['count'];
        mysqli_free_result($result);
    }

    // 3. Transfers Today Count
    $today = date('Y-m-d');
    $result = mysqli_query($db, "SELECT COUNT(*) as count FROM employee_transfers WHERE DATE(transfer_date) = CURDATE()");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $transfersToday = $row['count'];
        mysqli_free_result($result);
    }

    // 4. Pending Payroll Count - check if table exists first
    $tableCheck = mysqli_query($db, "SHOW TABLES LIKE 'payroll_records'");
    if ($tableCheck && mysqli_num_rows($tableCheck) > 0) {
        $result = mysqli_query($db, "SELECT COUNT(*) as count FROM payroll_records WHERE status = 'Draft'");
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $pendingPayroll = $row['count'];
            mysqli_free_result($result);
        }
    }
    if ($tableCheck) {
        mysqli_free_result($tableCheck);
    }

    // 5. Recent Transfers (Last 5)
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

    // 6. Recent Activity Logs (Last 5)
    $query = "SELECT 
                id,
                user_id,
                action,
                details,
                created_at
              FROM activity_logs
              ORDER BY created_at DESC
              LIMIT 5";
    $result = mysqli_query($db, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $recentActivity[] = $row;
        }
        mysqli_free_result($result);
    }

} catch (Exception $e) {
    $dbError = "Database error: " . $e->getMessage();
}

// Helper function to format date
function formatDate($date) {
    return date('M d, Y h:i A', strtotime($date));
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
    <title>Admin Dashboard - Attendance Monitoring</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="dashboard.css">


    
   
</head>
<body>
    <div class="app-shell">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <!-- Top Navbar -->
            <div class="top-navbar">
                <div class="navbar-brand">
                    <i class="fas fa-chart-line" style="color: var(--gold-2); font-size: 1.75rem;"></i>
                    <h1>Admin Dashboard</h1>
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
                Dashboard Overview
            </div>

            <?php if ($dbError): ?>
                <div class="db-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($dbError); ?>
                </div>
            <?php endif; ?>

            <!-- Summary Cards -->
            <div class="summary-grid">
                <div class="summary-card">
                    <div class="summary-icon employees">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="summary-number"><?php echo number_format($totalEmployees); ?></div>
                    <div class="summary-label">Total Employees</div>
                    <div class="summary-change">
                        <i class="fas fa-arrow-up"></i>
                        Active workforce
                    </div>
                </div>

                <div class="summary-card">
                    <div class="summary-icon branches">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="summary-number"><?php echo number_format($activeBranches); ?></div>
                    <div class="summary-label">Active Branches</div>
                    <div class="summary-change">
                        <i class="fas fa-check-circle"></i>
                        Operational sites
                    </div>
                </div>

                <div class="summary-card">
                    <div class="summary-icon transfers">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="summary-number"><?php echo number_format($transfersToday); ?></div>
                    <div class="summary-label">Transfers Today</div>
                    <div class="summary-change">
                        <i class="fas fa-calendar-day"></i>
                        <?php echo date('M d, Y'); ?>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="summary-icon payroll">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div class="summary-number"><?php echo number_format($pendingPayroll); ?></div>
                    <div class="summary-label">Pending Payroll</div>
                    <div class="summary-change">
                        <i class="fas fa-clock"></i>
                        Awaiting approval
                    </div>
                </div>
            </div>

            <!-- Quick Actions Section -->
            <div class="quick-actions-section">
                <div class="section-header">
                    <i class="fas fa-bolt"></i>
                    <h2>Quick Action Command Center</h2>
                </div>
                <div class="quick-actions-grid">
                    <a href="../send_branches.php" class="quick-action-btn">
                        <div class="action-icon">
                            <i class="fas fa-sync-alt"></i>
                        </div>
                        <span class="action-label">Sync to Procurement</span>
                        <span class="action-desc">Update branch data</span>
                    </a>

                    <a href="transfer_module.php" class="quick-action-btn">
                        <div class="action-icon">
                            <i class="fas fa-people-arrows"></i>
                        </div>
                        <span class="action-label">Staff Transfer</span>
                        <span class="action-desc">Move employees</span>
                    </a>

                    <a href="cash_advance.php" class="quick-action-btn">
                        <div class="action-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <span class="action-label">Cash Advance</span>
                        <span class="action-desc">Print receipts <i class="fas fa-print" style="font-size: 0.7rem;"></i></span>
                    </a>

                    <a href="signature_settings.php" class="quick-action-btn">
                        <div class="action-icon">
                            <i class="fas fa-signature"></i>
                        </div>
                        <span class="action-label">E-Signature</span>
                        <span class="action-desc">Manage signatures</span>
                    </a>

                    <a href="settings.php" class="quick-action-btn">
                        <div class="action-icon">
                            <i class="fas fa-user-cog"></i>
                        </div>
                        <span class="action-label">Profile Settings</span>
                        <span class="action-desc">Avatar & profile</span>
                    </a>

                    <a href="view_logs.php" class="quick-action-btn">
                        <div class="action-icon">
                            <i class="fas fa-terminal"></i>
                        </div>
                        <span class="action-label">System Logs</span>
                        <span class="action-desc">View api_debug.log</span>
                    </a>
                </div>
            </div>

            <!-- Data Monitoring Section -->
            <div class="monitoring-section">
                <!-- Recent Transfers -->
                <div class="monitoring-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-exchange-alt"></i>
                            Recent Transfers
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

                <!-- System Activity -->
                <div class="monitoring-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-clipboard-list"></i>
                            System Activity
                        </h5>
                        <a href="logs.php" class="view-all-btn">
                            View All <i class="fas fa-arrow-right" style="font-size: 0.75rem;"></i>
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($recentActivity)): ?>
                            <ul class="activity-list">
                                <?php foreach ($recentActivity as $activity): 
                                    // Determine icon based on action
                                    $action = strtolower($activity['action']);
                                    $iconClass = 'default';
                                    $icon = 'fa-circle';
                                    
                                    if (strpos($action, 'login') !== false) {
                                        $iconClass = 'login';
                                        $icon = 'fa-sign-in-alt';
                                    } elseif (strpos($action, 'transfer') !== false) {
                                        $iconClass = 'transfer';
                                        $icon = 'fa-exchange-alt';
                                    } elseif (strpos($action, 'attendance') !== false || strpos($action, 'present') !== false || strpos($action, 'absent') !== false) {
                                        $iconClass = 'attendance';
                                        $icon = 'fa-calendar-check';
                                    } elseif (strpos($action, 'payroll') !== false || strpos($action, 'payment') !== false) {
                                        $iconClass = 'payroll';
                                        $icon = 'fa-dollar-sign';
                                    }
                                ?>
                                    <li class="activity-item">
                                        <div class="activity-icon <?php echo $iconClass; ?>">
                                            <i class="fas <?php echo $icon; ?>"></i>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-text">
                                                <?php 
                                                // Extract username from details (e.g., "User Super Admin logged in...")
                                                $details = $activity['details'] ?? '';
                                                if (preg_match('/User\s+(.+?)\s+logged/', $details, $matches)) {
                                                    $userDisplay = $matches[1];
                                                } else {
                                                    $userDisplay = 'User #' . ($activity['user_id'] ?? 'Unknown');
                                                }
                                                ?>
                                                <strong><?php echo htmlspecialchars($userDisplay); ?></strong>
                                                <?php echo htmlspecialchars($activity['action']); ?>
                                            </div>
                                            <div class="activity-time">
                                                <i class="far fa-clock" style="margin-right: 4px;"></i>
                                                <?php echo formatDate($activity['created_at']); ?>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-clipboard"></i>
                                <p>No recent activity found</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/dashboard.js"></script>
    
 
</body>
</html>
