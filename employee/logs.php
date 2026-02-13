<?php
// admin/logs.php - Activity Logs Viewer
require_once __DIR__ . '/../conn/db_connection.php';
require_once __DIR__ . '/../functions.php';
session_start();

// Check if user is logged in and is admin/super admin
if (empty($_SESSION['logged_in']) || !in_array($_SESSION['position'], ['Admin', 'Super Admin'])) {
    header('Location: ../login.php');
    exit;
}

// Handle search and filters
$search_user = trim($_GET['search_user'] ?? '');
$search_action = trim($_GET['search_action'] ?? '');
$page = intval($_GET['page'] ?? 1);
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Build query
$query = "SELECT al.*, e.first_name, e.last_name, e.employee_code
          FROM activity_logs al
          LEFT JOIN employees e ON al.user_id = e.id
          WHERE 1=1";

$params = [];
$param_types = '';

if ($search_user) {
    $query .= " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_code LIKE ? OR CONCAT(e.first_name, ' ', e.last_name) LIKE ?)";
    $search_term = "%{$search_user}%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
    $param_types .= 'ssss';
}

if ($search_action) {
    $query .= " AND al.action LIKE ?";
    $params[] = "%{$search_action}%";
    $param_types .= 's';
}

$query .= " ORDER BY al.created_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$param_types .= 'ii';

// Execute query
$stmt = mysqli_prepare($db, $query);
if (!empty($param_types)) {
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM activity_logs al LEFT JOIN employees e ON al.user_id = e.id WHERE 1=1";
$count_params = [];
$count_types = '';

if ($search_user) {
    $count_query .= " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_code LIKE ? OR CONCAT(e.first_name, ' ', e.last_name) LIKE ?)";
    $count_params = array_merge($count_params, [$search_term, $search_term, $search_term, $search_term]);
    $count_types .= 'ssss';
}

if ($search_action) {
    $count_query .= " AND al.action LIKE ?";
    $count_params[] = "%{$search_action}%";
    $count_types .= 's';
}

$count_stmt = mysqli_prepare($db, $count_query);
if (!empty($count_types)) {
    mysqli_stmt_bind_param($count_stmt, $count_types, ...$count_params);
}
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="css/light-theme.css">
    <script src="js/theme.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --orange: #FFA500;
            --black: #000000;
        }

        body {
            background: linear-gradient(135deg, var(--black) 0%, #1a1a1a 100%);
            color: #ffffff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .main-content {
            margin-left: 16rem;
            padding: 2rem;
            min-height: 100vh;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
        }

        .log-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 165, 0, 0.1);
            border-radius: 12px;
        }

        .log-header {
            background: linear-gradient(90deg, var(--orange), var(--black));
            border-radius: 12px 12px 0 0;
        }

        .log-table {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 8px;
            overflow: hidden;
        }

        .log-row:hover {
            background: rgba(255, 165, 0, 0.05);
        }

        .input-field {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 165, 0, 0.2);
            color: #ffffff;
            transition: all 0.3s ease;
        }

        .input-field:focus {
            outline: none;
            border-color: var(--orange);
            box-shadow: 0 0 0 2px rgba(255, 165, 0, 0.2);
        }

        .input-field::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .btn-primary {
            background: linear-gradient(90deg, var(--orange), var(--black));
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 165, 0, 0.4);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 165, 0, 0.3);
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: rgba(255, 165, 0, 0.2);
        }

        .pagination-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 165, 0, 0.2);
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
        }

        .pagination-btn:hover {
            background: rgba(255, 165, 0, 0.3);
            transform: translateY(-1px);
        }

        .pagination-btn.active {
            background: var(--orange);
            color: var(--black);
            font-weight: bold;
        }

        .action-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .action-login { background: rgba(76, 175, 80, 0.2); color: #4CAF50; }
        .action-logout { background: rgba(244, 67, 54, 0.2); color: #F44336; }
        .action-create { background: rgba(33, 150, 243, 0.2); color: #2196F3; }
        .action-update { background: rgba(255, 152, 0, 0.2); color: #FF9800; }
        .action-delete { background: rgba(244, 67, 54, 0.2); color: #F44336; }
        .action-system { background: rgba(156, 39, 176, 0.2); color: #9C27B0; }

        .table-container {
            max-height: 60vh;
            overflow-y: auto;
        }

        .table-container::-webkit-scrollbar {
            width: 8px;
        }

        .table-container::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 4px;
        }

        .table-container::-webkit-scrollbar-thumb {
            background: var(--orange);
            border-radius: 4px;
        }

        .stats-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 165, 0, 0.1);
            border-radius: 10px;
            padding: 1.5rem;
        }

        .export-btn {
            background: linear-gradient(90deg, #10B981, #059669);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }

        /* ============================================
           MOBILE GRID VIEW - Activity Log Cards
           ============================================ */
        @media (max-width: 767px) {
            /* Hide table header on mobile */
            .table-container table thead {
                display: none !important;
            }
            
            /* Convert table to block */
            .table-container table tbody {
                display: block;
            }
            
            .table-container table {
                display: block;
                width: 100%;
            }
            
            /* Card styling for each row */
            .table-container tbody tr.log-row {
                display: block;
                background: rgba(255, 255, 255, 0.05);
                border: 1px solid rgba(255, 165, 0, 0.2);
                border-radius: 12px;
                padding: 16px;
                margin-bottom: 12px;
            }
            
            /* Alternating background not needed on mobile */
            .table-container tbody tr.log-row.bg-gray-800\/50 {
                background: rgba(255, 255, 255, 0.05);
            }
            
            /* All cells as block */
            .table-container tbody tr td {
                display: block;
                width: 100%;
                padding: 8px 0;
                border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            }
            
            /* Remove borders from last cell */
            .table-container tbody tr td:last-child {
                border-bottom: none;
            }
            
            /* Timestamp styling (first cell) */
            .table-container tbody tr td:first-child {
                font-size: 14px;
                font-weight: 600;
                color: var(--orange);
                border-bottom: 2px solid rgba(255, 165, 0, 0.3);
                margin-bottom: 8px;
                padding-bottom: 12px;
            }
            
            .table-container tbody tr td:first-child .text-xs {
                color: #9ca3af;
                font-weight: 400;
            }
            
            /* Add labels to cells */
            .table-container tbody tr td:nth-child(2)::before { content: "User: "; color: #9ca3af; font-weight: 500; }
            .table-container tbody tr td:nth-child(3)::before { content: "Action: "; color: #9ca3af; font-weight: 500; }
            .table-container tbody tr td:nth-child(4)::before { content: "Details: "; color: #9ca3af; font-weight: 500; }
            .table-container tbody tr td:nth-child(5)::before { content: "IP: "; color: #9ca3af; font-weight: 500; }
            
            /* User cell styling */
            .table-container tbody tr td:nth-child(2) {
                font-weight: 500;
            }
            
            .table-container tbody tr td:nth-child(2) .text-xs {
                color: #9ca3af;
            }
            
            /* Action badge full width */
            .table-container tbody tr td:nth-child(3) .action-badge {
                display: inline-flex;
                width: auto;
                min-width: 120px;
                justify-content: center;
            }
            
            /* Details cell - remove truncation on mobile */
            .table-container tbody tr td:nth-child(4) .truncate {
                white-space: normal;
                overflow: visible;
                text-overflow: clip;
            }
            
            /* IP address styling */
            .table-container tbody tr td:nth-child(5) .bg-gray-900 {
                display: inline-block;
            }
            
            /* No data message */
            .table-container tbody tr td[colspan="5"] {
                text-align: center;
                padding: 40px 20px;
            }
            
            /* Table container - remove max-height on mobile */
            .table-container {
                max-height: none;
                overflow-y: visible;
            }
            
            /* Stats cards - 1 column on mobile */
            .grid.grid-cols-1.md\:grid-cols-3 {
                grid-template-columns: 1fr;
            }
            
            /* Search form adjustments */
            form.grid.grid-cols-1.md\:grid-cols-3 {
                gap: 12px;
            }
            
            /* Pagination adjustments */
            .pagination-btn {
                padding: 8px 12px;
                min-width: 36px;
                font-size: 14px;
            }
            
            /* Page jump input */
            .input-field.w-16 {
                width: 50px;
            }
        }

        /* Extra small screens */
        @media (max-width: 480px) {
            .table-container tbody tr.log-row {
                padding: 12px;
            }
            
            .table-container tbody tr td:first-child {
                font-size: 13px;
            }
            
            .main-content {
                padding: 0.75rem;
            }
            
            .log-card {
                padding: 12px !important;
            }
            
            .stats-card {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../employee/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-white mb-2">Activity Logs</h1>
            <p class="text-gray-300">Monitor and track all system activities and user actions</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="stats-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">Total Records</p>
                        <p class="text-2xl font-bold text-white"><?php echo number_format($total_records); ?></p>
                    </div>
                    <div class="text-orange-500">
                        <i class="fas fa-database text-2xl"></i>
                    </div>
                </div>
            </div>
            <div class="stats-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">This Page</p>
                        <p class="text-2xl font-bold text-white">
                            <?php echo number_format(min($per_page, $total_records - $offset)); ?>
                        </p>
                    </div>
                    <div class="text-orange-500">
                        <i class="fas fa-list text-2xl"></i>
                    </div>
                </div>
            </div>
            <div class="stats-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">Total Pages</p>
                        <p class="text-2xl font-bold text-white"><?php echo $total_pages; ?></p>
                    </div>
                    <div class="text-orange-500">
                        <i class="fas fa-file-alt text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="log-card p-6 mb-6">
            <!-- Search and Filters -->
            <div class="mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            <i class="fas fa-user mr-2"></i>Search by User
                        </label>
                        <input type="text" name="search_user" value="<?php echo htmlspecialchars($search_user); ?>"
                               placeholder="Name, employee code, or ID" 
                               class="input-field w-full px-4 py-3 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            <i class="fas fa-bolt mr-2"></i>Search by Action
                        </label>
                        <input type="text" name="search_action" value="<?php echo htmlspecialchars($search_action); ?>"
                               placeholder="Action type or keyword" 
                               class="input-field w-full px-4 py-3 rounded-lg">
                    </div>
                    <div class="flex items-end space-x-2">
                        <button type="submit" class="btn-primary flex-1">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <a href="?page=1" class="btn-secondary px-4 py-3 rounded-lg">
                            <i class="fas fa-redo"></i>
                        </a>
                    </div>
                </form>
                
                <!-- Export Button (if needed) -->
                <!--
                <div class="flex justify-end">
                    <button class="export-btn">
                        <i class="fas fa-download"></i> Export Logs
                    </button>
                </div>
                -->
            </div>

            <!-- Results Info -->
            <div class="flex justify-between items-center mb-4">
                <div class="text-gray-300">
                    <i class="fas fa-info-circle mr-2"></i>
                    Showing <?php echo ($offset + 1) . ' - ' . min($offset + $per_page, $total_records); ?> 
                    of <?php echo number_format($total_records); ?> records
                </div>
                <div class="text-sm text-gray-400">
                    Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                </div>
            </div>

            <!-- Logs Table -->
            <div class="table-container">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-800">
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                <i class="fas fa-clock mr-2"></i>Timestamp
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                <i class="fas fa-user mr-2"></i>User
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                <i class="fas fa-bolt mr-2"></i>Action
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                <i class="fas fa-info-circle mr-2"></i>Details
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                <i class="fas fa-network-wired mr-2"></i>IP Address
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php 
                        $row_count = 0;
                        while ($log = mysqli_fetch_assoc($result)): 
                            $row_count++;
                        ?>
                        <tr class="log-row <?php echo $row_count % 2 === 0 ? 'bg-gray-800/50' : ''; ?>">
                            <td class="px-4 py-3 text-sm text-gray-300">
                                <div class="font-medium">
                                    <?php echo date('M d, Y', strtotime($log['created_at'])); ?>
                                </div>
                                <div class="text-xs text-gray-400">
                                    <?php echo date('H:i:s', strtotime($log['created_at'])); ?>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <?php if ($log['user_id']): ?>
                                    <div class="text-white font-medium">
                                        <?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?>
                                    </div>
                                    <div class="text-gray-400 text-xs">
                                        <i class="fas fa-id-badge mr-1"></i>
                                        <?php echo htmlspecialchars($log['employee_code']); ?>
                                    </div>
                                <?php else: ?>
                                    <div class="flex items-center text-gray-400">
                                        <i class="fas fa-server mr-2"></i>
                                        <span>System</span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <?php
                                $action_class = 'action-system';
                                $action_lower = strtolower($log['action']);
                                if (strpos($action_lower, 'login') !== false) $action_class = 'action-login';
                                elseif (strpos($action_lower, 'logout') !== false) $action_class = 'action-logout';
                                elseif (strpos($action_lower, 'create') !== false) $action_class = 'action-create';
                                elseif (strpos($action_lower, 'update') !== false) $action_class = 'action-update';
                                elseif (strpos($action_lower, 'delete') !== false) $action_class = 'action-delete';
                                ?>
                                <span class="action-badge <?php echo $action_class; ?>">
                                    <i class="fas fa-<?php 
                                        if ($action_class === 'action-login') echo 'sign-in-alt';
                                        elseif ($action_class === 'action-logout') echo 'sign-out-alt';
                                        elseif ($action_class === 'action-create') echo 'plus';
                                        elseif ($action_class === 'action-update') echo 'edit';
                                        elseif ($action_class === 'action-delete') echo 'trash';
                                        else echo 'cog';
                                    ?> mr-1"></i>
                                    <?php echo htmlspecialchars($log['action']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-300 max-w-xs">
                                <div class="truncate" title="<?php echo htmlspecialchars($log['details']); ?>">
                                    <?php echo htmlspecialchars($log['details']); ?>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-400 font-mono">
                                <div class="flex items-center">
                                    <i class="fas fa-globe mr-2 text-xs"></i>
                                    <span class="bg-gray-900 px-2 py-1 rounded">
                                        <?php echo htmlspecialchars($log['ip_address']); ?>
                                    </span>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        
                        <?php if ($row_count === 0): ?>
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-400">
                                <i class="fas fa-inbox text-3xl mb-2"></i>
                                <p class="text-lg">No activity logs found</p>
                                <p class="text-sm mt-1">Try adjusting your search criteria</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="mt-8 flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="text-gray-300 text-sm">
                    Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                </div>
                <div class="flex flex-wrap justify-center gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=1&search_user=<?php echo urlencode($search_user); ?>&search_action=<?php echo urlencode($search_action); ?>"
                           class="pagination-btn" title="First Page">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                        <a href="?page=<?php echo ($page - 1); ?>&search_user=<?php echo urlencode($search_user); ?>&search_action=<?php echo urlencode($search_action); ?>"
                           class="pagination-btn" title="Previous">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);

                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <a href="?page=<?php echo $i; ?>&search_user=<?php echo urlencode($search_user); ?>&search_action=<?php echo urlencode($search_action); ?>"
                           class="pagination-btn <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo ($page + 1); ?>&search_user=<?php echo urlencode($search_user); ?>&search_action=<?php echo urlencode($search_action); ?>"
                           class="pagination-btn" title="Next">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <a href="?page=<?php echo $total_pages; ?>&search_user=<?php echo urlencode($search_user); ?>&search_action=<?php echo urlencode($search_action); ?>"
                           class="pagination-btn" title="Last Page">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                
                <!-- Page Jump (Optional) -->
                <div class="flex items-center gap-2">
                    <span class="text-gray-300 text-sm">Go to:</span>
                    <input type="number" 
                           min="1" 
                           max="<?php echo $total_pages; ?>" 
                           value="<?php echo $page; ?>"
                           class="input-field w-16 px-2 py-1 rounded text-center"
                           onchange="if(this.value) window.location.href = '?page=' + this.value + '&search_user=<?php echo urlencode($search_user); ?>&search_action=<?php echo urlencode($search_action); ?>'">
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Add hover effect to table rows
        document.querySelectorAll('.log-row').forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(4px)';
                this.style.transition = 'transform 0.2s ease';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0)';
            });
        });

        // Auto-refresh logs every 30 seconds (optional)
        // setTimeout(() => {
        //     window.location.reload();
        // }, 30000);
    </script>
</body>
</html>