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
        }

        .log-row:hover {
            background: rgba(255, 165, 0, 0.05);
        }

        .input-field {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 165, 0, 0.2);
            color: #ffffff;
        }

        .input-field::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .btn-primary {
            background: linear-gradient(90deg, var(--orange), var(--black));
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 165, 0, 0.3);
        }

        .pagination-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 165, 0, 0.2);
            color: #ffffff;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .pagination-btn:hover,
        .pagination-btn.active {
            background: var(--orange);
            color: var(--black);
        }

        .back-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            background: rgba(0, 0, 0, 0.8);
            border: 1px solid var(--orange);
            color: var(--orange);
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: var(--orange);
            color: var(--black);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <!-- <a href="../employee/dashboard.php" class="back-btn">← Back to Dashboard</a> -->

    <div class="container mx-auto px-4 py-8">
        <div class="log-card p-6 mb-6">
            <div class="log-header p-4 mb-6">
                <h1 class="text-2xl font-bold text-white">Activity Logs</h1>
                <p class="text-gray-300 mt-2">Monitor all system activities and user actions</p>
            </div>

            <!-- Search Filters -->
            <form method="GET" class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Search by User</label>
                    <input type="text" name="search_user" value="<?php echo htmlspecialchars($search_user); ?>"
                           placeholder="Name, code, or ID" class="input-field w-full px-3 py-2 rounded">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Search by Action</label>
                    <input type="text" name="search_action" value="<?php echo htmlspecialchars($search_action); ?>"
                           placeholder="Action type" class="input-field w-full px-3 py-2 rounded">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="btn-primary w-full">Search</button>
                </div>
            </form>

            <!-- Results Info -->
            <div class="mb-4 text-gray-300">
                Showing <?php echo ($offset + 1) . ' - ' . min($offset + $per_page, $total_records); ?> of <?php echo $total_records; ?> records
            </div>

            <!-- Logs Table -->
            <div class="log-table overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-600">
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Time</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">User</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Action</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Details</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">IP Address</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-600">
                        <?php while ($log = mysqli_fetch_assoc($result)): ?>
                        <tr class="log-row">
                            <td class="px-4 py-3 text-sm text-gray-300">
                                <?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <?php if ($log['user_id']): ?>
                                    <div class="text-white font-medium">
                                        <?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?>
                                    </div>
                                    <div class="text-gray-400 text-xs">
                                        <?php echo htmlspecialchars($log['employee_code']); ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-gray-500">System</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-orange-900 text-orange-200">
                                    <?php echo htmlspecialchars($log['action']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-300 max-w-xs truncate" title="<?php echo htmlspecialchars($log['details']); ?>">
                                <?php echo htmlspecialchars($log['details']); ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-400 font-mono">
                                <?php echo htmlspecialchars($log['ip_address']); ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="mt-6 flex justify-center space-x-2">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo ($page - 1); ?>&search_user=<?php echo urlencode($search_user); ?>&search_action=<?php echo urlencode($search_action); ?>"
                       class="pagination-btn">Previous</a>
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
                       class="pagination-btn">Next</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>