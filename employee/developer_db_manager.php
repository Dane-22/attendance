<?php
/**
 * Developer Database Manager - phpMyAdmin Style Interface
 * Virtual database interface for Developers only
 */

require_once __DIR__ . '/../conn/db_connection.php';
require_once __DIR__ . '/../functions.php';
session_start();

// ===== RATE LIMITER CONFIGURATION =====
$rateLimitEnabled = true;
$rateLimitWindow = 60; // 60 seconds window
$rateLimitMaxRequests = 30; // Max 30 requests per window

function checkRateLimit() {
    global $rateLimitEnabled, $rateLimitWindow, $rateLimitMaxRequests;
    
    if (!$rateLimitEnabled) return true;
    
    $currentTime = time();
    $userId = $_SESSION['employee_id'] ?? $_SESSION['user_id'] ?? 'anonymous';
    $rateLimitKey = "dbmanager_ratelimit_$userId";
    
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

// Check rate limit before processing
if (!checkRateLimit()) {
    http_response_code(429);
    die('<div style="padding: 20px; text-align: center; font-family: Arial;">
        <h2>Rate Limit Exceeded</h2>
        <p>Too many requests. Please wait a minute before refreshing.</p>
        <a href="developer_db_manager.php" style="color: #4476b1;">Try again</a>
    </div>');
}

// Developer access only
if (empty($_SESSION['logged_in']) || $_SESSION['position'] !== 'Developer') {
    header('Location: ../login.php');
    exit;
}

// ALLOWED TABLE - Only attendance table is accessible
$ALLOWED_TABLE = 'attendance';

// Get selected table and validate
$selectedTable = $_GET['table'] ?? '';

// BLOCK ACCESS TO ANY TABLE OTHER THAN ATTENDANCE
if ($selectedTable && $selectedTable !== $ALLOWED_TABLE) {
    http_response_code(403);
    die('<div style="padding: 20px; text-align: center; font-family: Arial; background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; margin: 20px;">
        <h2><i class="fas fa-ban"></i> Access Denied</h2>
        <p>You are only allowed to access the <strong>attendance</strong> table.</p>
        <p>Table "' . htmlspecialchars($selectedTable) . '" is not accessible.</p>
        <a href="?table=attendance" style="color: #4476b1;">Go to Attendance Table</a>
    </div>');
}

$tab = $_GET['tab'] ?? 'structure'; // structure, browse, sql, search, insert, export

// Get all tables with info - FILTERED TO ONLY SHOW ATTENDANCE
$tables = [];
$tableResult = mysqli_query($db, "SHOW TABLE STATUS WHERE Name = 'attendance'");
while ($row = mysqli_fetch_assoc($tableResult)) {
    $tables[] = $row;
}

// If no attendance table found, show error
if (empty($tables)) {
    die('<div style="padding: 20px; text-align: center; font-family: Arial;">
        <h2>Error</h2>
        <p>Attendance table not found in database.</p>
    </div>');
}

// Get selected table
$selectedTable = $_GET['table'] ?? '';
$tab = $_GET['tab'] ?? 'structure'; // structure, browse, sql, search, insert, export

// Message handling
$message = $_SESSION['db_message'] ?? '';
$messageType = $_SESSION['db_message_type'] ?? '';
unset($_SESSION['db_message'], $_SESSION['db_message_type']);

// Calculate total size
$totalSize = 0;
$totalRows = 0;
foreach ($tables as $t) {
    $totalSize += $t['Data_length'] + $t['Index_length'];
    $totalRows += $t['Rows'];
}

// Get column info for selected table - MUST BE BEFORE POST HANDLERS
$columns = [];
if ($selectedTable) {
    $colResult = mysqli_query($db, "SHOW COLUMNS FROM `$selectedTable`");
    while ($row = mysqli_fetch_assoc($colResult)) {
        $columns[] = $row;
    }
}

// Handle table actions - ONLY ALLOW ATTENDANCE TABLE ACTIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selectedTable) {
    // Validate table name strictly
    if ($selectedTable !== $ALLOWED_TABLE) {
        http_response_code(403);
        die('Access Denied: Only attendance table can be modified.');
    }
    
    // BLOCK DROP TABLE completely
    if (isset($_POST['action']) && $_POST['action'] === 'drop') {
        $_SESSION['db_message'] = "Drop table operation is disabled for security";
        $_SESSION['db_message_type'] = "error";
        header("Location: developer_db_manager.php");
        exit;
    }
    
    // Handle DELETE record
    if (isset($_POST['delete_record']) && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        $primaryKey = '';
        foreach ($columns as $col) {
            if ($col['Key'] === 'PRI') {
                $primaryKey = $col['Field'];
                break;
            }
        }
        if ($primaryKey && $id) {
            $stmt = mysqli_prepare($db, "DELETE FROM `$selectedTable` WHERE `$primaryKey` = ?");
            mysqli_stmt_bind_param($stmt, 'i', $id);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['db_message'] = "Record deleted successfully";
                $_SESSION['db_message_type'] = "success";
            } else {
                $_SESSION['db_message'] = "Error deleting record: " . mysqli_error($db);
                $_SESSION['db_message_type'] = "error";
            }
            mysqli_stmt_close($stmt);
        }
        header("Location: ?table=$selectedTable&tab=browse&page=$page");
        exit;
    }
    
    // Handle UPDATE record
    if (isset($_POST['update_record']) && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        $primaryKey = '';
        foreach ($columns as $col) {
            if ($col['Key'] === 'PRI') {
                $primaryKey = $col['Field'];
                break;
            }
        }
        
        if ($primaryKey && $id) {
            $updates = [];
            $values = [];
            $types = '';
            
            foreach ($_POST as $key => $value) {
                if ($key !== 'update_record' && $key !== 'id' && $key !== 'page' && $key !== 'table' && $key !== 'primaryKey') {
                    $updates[] = "`$key` = ?";
                    $values[] = $value;
                    $types .= 's';
                }
            }
            
            if (!empty($updates)) {
                $sql = "UPDATE `$selectedTable` SET " . implode(', ', $updates) . " WHERE `$primaryKey` = ?";
                $values[] = $id;
                $types .= 'i';
                
                $stmt = mysqli_prepare($db, $sql);
                mysqli_stmt_bind_param($stmt, $types, ...$values);
                
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['db_message'] = "Record updated successfully";
                    $_SESSION['db_message_type'] = "success";
                } else {
                    $_SESSION['db_message'] = "Error updating record: " . mysqli_error($db);
                    $_SESSION['db_message_type'] = "error";
                }
                mysqli_stmt_close($stmt);
            }
        }
        header("Location: ?table=$selectedTable&tab=browse&page=" . intval($_POST['page'] ?? 1));
        exit;
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'empty' && isset($_POST['confirm'])) {
        mysqli_query($db, "DROP TABLE IF EXISTS `$selectedTable`");
        $_SESSION['db_message'] = "Table '$selectedTable' dropped successfully";
        $_SESSION['db_message_type'] = "success";
        header("Location: developer_db_manager.php");
        exit;
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'empty' && isset($_POST['confirm'])) {
        mysqli_query($db, "TRUNCATE TABLE `$selectedTable`");
        $_SESSION['db_message'] = "Table '$selectedTable' emptied successfully";
        $_SESSION['db_message_type'] = "success";
        header("Location: ?table=$selectedTable&tab=browse");
        exit;
    }
}

// Get indexes for selected table
$indexes = [];
if ($selectedTable) {
    $idxResult = mysqli_query($db, "SHOW INDEX FROM `$selectedTable`");
    while ($row = mysqli_fetch_assoc($idxResult)) {
        $indexes[] = $row;
    }
}

// Format bytes
function formatBytes($bytes) {
    if ($bytes === NULL || $bytes == 0) return '-';
    $units = ['B', 'KB', 'MB', 'GB'];
    $unitIndex = 0;
    while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
        $bytes /= 1024;
        $unitIndex++;
    }
    return round($bytes, 2) . ' ' . $units[$unitIndex];
}

// Get table data for browse tab
$tableData = [];
$totalRecords = 0;
$page = intval($_GET['page'] ?? 1);
$perPage = 25;
$offset = ($page - 1) * $perPage;

if ($selectedTable && $tab === 'browse') {
    $countResult = mysqli_query($db, "SELECT COUNT(*) as total FROM `$selectedTable`");
    if ($countRow = mysqli_fetch_assoc($countResult)) {
        $totalRecords = $countRow['total'];
    }
    
    $primaryKey = '';
    foreach ($columns as $col) {
        if ($col['Key'] === 'PRI') {
            $primaryKey = $col['Field'];
            break;
        }
    }
    
    $orderBy = $primaryKey ? "ORDER BY `$primaryKey` DESC" : '';
    $sql = "SELECT * FROM `$selectedTable` $orderBy LIMIT $offset, $perPage";
    $result = mysqli_query($db, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $tableData[] = $row;
    }
}

$totalPages = ceil($totalRecords / $perPage);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Developer Database Manager - phpMyAdmin Style</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --phpmyadmin-blue: #4476b1;
            --phpmyadmin-light: #f0f0f0;
            --phpmyadmin-border: #d0d0d0;
            --phpmyadmin-hover: #e5e5e5;
            --phpmyadmin-text: #333;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: var(--phpmyadmin-text);
            font-size: 13px;
        }
        .top-bar {
            background: linear-gradient(180deg, #fff 0%, #e5e5e5 100%);
            border-bottom: 1px solid var(--phpmyadmin-border);
            padding: 8px 15px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .logo {
            font-weight: bold;
            color: var(--phpmyadmin-blue);
            font-size: 16px;
        }
        .nav-links {
            display: flex;
            gap: 5px;
        }
        .nav-link {
            padding: 5px 12px;
            text-decoration: none;
            color: #333;
            border: 1px solid transparent;
            border-radius: 3px;
        }
        .nav-link:hover {
            background: #fff;
            border-color: var(--phpmyadmin-border);
        }
        .server-info {
            margin-left: auto;
            font-size: 12px;
            color: #666;
        }
        .main-container {
            display: flex;
            min-height: calc(100vh - 45px);
        }
        .sidebar {
            width: 240px;
            background: #f0f0f0;
            border-right: 1px solid var(--phpmyadmin-border);
            overflow-y: auto;
        }
        .sidebar-header {
            background: #e0e0e0;
            padding: 8px 12px;
            border-bottom: 1px solid var(--phpmyadmin-border);
            font-weight: bold;
            font-size: 12px;
        }
        .db-tree {
            padding: 5px;
        }
        .tree-item {
            display: flex;
            align-items: center;
            padding: 3px 8px;
            cursor: pointer;
            text-decoration: none;
            color: #333;
        }
        .tree-item:hover {
            background: var(--phpmyadmin-hover);
        }
        .tree-item.active {
            background: #d0e0f0;
        }
        .tree-icon {
            width: 16px;
            margin-right: 5px;
            color: var(--phpmyadmin-blue);
        }
        .content {
            flex: 1;
            padding: 15px;
            overflow-x: auto;
        }
        .tabs {
            display: flex;
            border-bottom: 1px solid var(--phpmyadmin-border);
            margin-bottom: 15px;
            background: #f8f8f8;
        }
        .tab {
            padding: 8px 15px;
            text-decoration: none;
            color: #333;
            border: 1px solid transparent;
            border-bottom: none;
            margin-right: 3px;
            border-radius: 3px 3px 0 0;
        }
        .tab:hover {
            background: #e8e8e8;
        }
        .tab.active {
            background: #fff;
            border-color: var(--phpmyadmin-border);
            border-bottom-color: #fff;
            color: var(--phpmyadmin-blue);
            font-weight: bold;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border: 1px solid var(--phpmyadmin-border);
            margin-bottom: 15px;
        }
        .data-table th {
            background: linear-gradient(180deg, #f8f8f8 0%, #e8e8e8 100%);
            padding: 8px 10px;
            text-align: left;
            border-bottom: 1px solid var(--phpmyadmin-border);
            font-weight: bold;
            font-size: 12px;
        }
        .data-table td {
            padding: 6px 10px;
            border-bottom: 1px solid #eee;
        }
        .data-table tr:hover td {
            background: #f5f5f5;
        }
        .data-table tr:nth-child(even) {
            background: #fafafa;
        }
        .action-links {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .action-link {
            color: var(--phpmyadmin-blue);
            text-decoration: none;
            font-size: 11px;
        }
        .action-link:hover {
            text-decoration: underline;
        }
        .action-link i {
            margin-right: 3px;
        }
        .checkbox {
            width: 14px;
            height: 14px;
        }
        .message {
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: 3px;
        }
        .message-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .message-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .footer {
            background: #f0f0f0;
            border-top: 1px solid var(--phpmyadmin-border);
            padding: 10px 15px;
            font-size: 11px;
            color: #666;
        }
        .pagination {
            display: flex;
            gap: 5px;
            margin-top: 15px;
        }
        .page-link {
            padding: 4px 10px;
            border: 1px solid var(--phpmyadmin-border);
            text-decoration: none;
            color: #333;
            background: #fff;
        }
        .page-link:hover {
            background: var(--phpmyadmin-hover);
        }
        .page-link.active {
            background: var(--phpmyadmin-blue);
            color: #fff;
            border-color: var(--phpmyadmin-blue);
        }
        .sql-box {
            background: #fff;
            border: 1px solid var(--phpmyadmin-border);
            padding: 15px;
            margin-bottom: 15px;
        }
        .sql-textarea {
            width: 100%;
            min-height: 150px;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 13px;
            border: 1px solid var(--phpmyadmin-border);
            padding: 10px;
            resize: vertical;
        }
        .btn {
            padding: 6px 15px;
            border: 1px solid var(--phpmyadmin-border);
            background: linear-gradient(180deg, #fff 0%, #e8e8e8 100%);
            cursor: pointer;
            border-radius: 3px;
            font-size: 12px;
        }
        .btn:hover {
            background: linear-gradient(180deg, #f8f8f8 0%, #d8d8d8 100%);
        }
        .btn-primary {
            background: linear-gradient(180deg, #4476b1 0%, #336699 100%);
            color: #fff;
            border-color: #336699;
        }
        .btn-primary:hover {
            background: linear-gradient(180deg, #5586c1 0%, #4476b1 100%);
        }
        .btn-danger {
            background: linear-gradient(180deg, #dc3545 0%, #c82333 100%);
            color: #fff;
            border-color: #bd2130;
        }
        .info-panel {
            background: #fff;
            border: 1px solid var(--phpmyadmin-border);
            padding: 15px;
            margin-bottom: 15px;
        }
        .info-row {
            display: flex;
            padding: 5px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .info-label {
            width: 200px;
            font-weight: bold;
            color: #666;
        }
        .info-value {
            flex: 1;
        }
        .truncate {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="top-bar">
        <div class="logo">
            <i class="fas fa-database mr-2"></i>Developer DB Manager
        </div>
        <div class="nav-links">
            <a href="?" class="nav-link"><i class="fas fa-home mr-1"></i>Home</a>
            <a href="eng_dashboard.php" class="nav-link"><i class="fas fa-arrow-left mr-1"></i>Back to App</a>
        </div>
        <div class="server-info">
            Server: 127.0.0.1:3306 | Database: attendance_db | User: Developer
        </div>
    </div>
    
    <div class="main-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-database mr-1"></i>attendance_db
            </div>
            <div class="db-tree">
                <?php foreach ($tables as $table): ?>
                    <a href="?table=<?php echo $table['Name']; ?>&tab=structure" 
                       class="tree-item <?php echo $selectedTable === $table['Name'] ? 'active' : ''; ?>">
                        <i class="fas fa-table tree-icon"></i>
                        <span><?php echo $table['Name']; ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="sidebar-header" style="margin-top: 20px;">
                <i class="fas fa-info-circle mr-1"></i>Server Info
            </div>
            <div style="padding: 10px; font-size: 11px; color: #666;">
                <div>MySQL: 8.4.7</div>
                <div>PHP: 8.3.28</div>
                <div>Tables: <?php echo count($tables); ?></div>
                <div>Total: <?php echo formatBytes($totalSize); ?></div>
            </div>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
                <div class="message message-<?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check' : 'exclamation-circle'; ?> mr-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$selectedTable): ?>
                <h2 style="font-size: 18px; margin-bottom: 15px;">
                    <i class="fas fa-server mr-2"></i>Database: attendance_db
                </h2>
                
                <div class="info-panel">
                    <div style="margin-bottom: 15px;">
                        <strong>Server:</strong> 127.0.0.1 via TCP/IP<br>
                        <strong>Server type:</strong> MySQL<br>
                        <strong>Server version:</strong> 8.4.7<br>
                        <strong>Protocol version:</strong> 10<br>
                        <strong>User:</strong> Developer<br>
                        <strong>Server charset:</strong> UTF-8 Unicode (utf8mb4)
                    </div>
                </div>
                
                <h3 style="font-size: 14px; margin-bottom: 10px;">Tables</h3>
                
                <form method="POST" id="tableForm">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th width="30"><input type="checkbox" class="checkbox" onclick="toggleAll(this)"></th>
                                <th>Table</th>
                                <th>Action</th>
                                <th>Rows</th>
                                <th>Type</th>
                                <th>Collation</th>
                                <th>Size</th>
                                <th>Overhead</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tables as $table): 
                                $size = $table['Data_length'] + $table['Index_length'];
                                $overhead = $table['Data_free'] ?? 0;
                            ?>
                                <tr>
                                    <td><input type="checkbox" name="tables[]" value="<?php echo $table['Name']; ?>" class="checkbox"></td>
                                    <td>
                                        <i class="fas fa-table mr-1" style="color: var(--phpmyadmin-blue);"></i>
                                        <a href="?table=<?php echo $table['Name']; ?>&tab=structure" style="color: var(--phpmyadmin-blue); text-decoration: none;">
                                            <?php echo $table['Name']; ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="action-links">
                                            <a href="?table=<?php echo $table['Name']; ?>&tab=browse" class="action-link"><i class="fas fa-list"></i>Browse</a>
                                            <a href="?table=<?php echo $table['Name']; ?>&tab=structure" class="action-link"><i class="fas fa-columns"></i>Structure</a>
                                            <a href="?table=<?php echo $table['Name']; ?>&tab=search" class="action-link"><i class="fas fa-search"></i>Search</a>
                                            <a href="?table=<?php echo $table['Name']; ?>&tab=insert" class="action-link"><i class="fas fa-plus"></i>Insert</a>
                                            <a href="?table=<?php echo $table['Name']; ?>&tab=export" class="action-link"><i class="fas fa-download"></i>Export</a>
                                            <a href="?table=<?php echo $table['Name']; ?>&action=empty" class="action-link" style="color: #dc3545;" onclick="return confirm('Empty table <?php echo $table['Name']; ?>?');"><i class="fas fa-trash"></i>Empty</a>
                                            <a href="?table=<?php echo $table['Name']; ?>&action=drop" class="action-link" style="color: #dc3545;" onclick="return confirm('Drop table <?php echo $table['Name']; ?>? This cannot be undone.');"><i class="fas fa-times"></i>Drop</a>
                                        </div>
                                    </td>
                                    <td><?php echo number_format($table['Rows']); ?></td>
                                    <td><?php echo $table['Engine'] ?? 'MyISAM'; ?></td>
                                    <td><?php echo $table['Collation'] ?? 'utf8mb4'; ?></td>
                                    <td><?php echo formatBytes($size); ?></td>
                                    <td><?php echo $overhead > 0 ? formatBytes($overhead) : '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot style="background: #f5f5f5; font-weight: bold;">
                            <tr>
                                <td colspan="3"><?php echo count($tables); ?> tables</td>
                                <td><?php echo number_format($totalRows); ?></td>
                                <td colspan="2">Sum</td>
                                <td><?php echo formatBytes($totalSize); ?></td>
                                <td>-</td>
                            </tr>
                        </tfoot>
                    </table>
                </form>
                
            <?php else: ?>
                <h2 style="font-size: 18px; margin-bottom: 15px;">
                    Table: <span style="color: var(--phpmyadmin-blue);"><?php echo $selectedTable; ?></span>
                    <?php 
                    $currentTable = null;
                    foreach ($tables as $t) {
                        if ($t['Name'] === $selectedTable) {
                            $currentTable = $t;
                            break;
                        }
                    }
                    ?>
                    <span style="font-size: 12px; color: #666; font-weight: normal;">
                        (<?php echo number_format($currentTable['Rows'] ?? 0); ?> rows)
                    </span>
                </h2>
                
                <div class="tabs">
                    <a href="?table=<?php echo $selectedTable; ?>&tab=browse" class="tab <?php echo $tab === 'browse' ? 'active' : ''; ?>">
                        <i class="fas fa-list mr-1"></i>Browse
                    </a>
                    <a href="?table=<?php echo $selectedTable; ?>&tab=structure" class="tab <?php echo $tab === 'structure' ? 'active' : ''; ?>">
                        <i class="fas fa-columns mr-1"></i>Structure
                    </a>
                    <a href="?table=<?php echo $selectedTable; ?>&tab=sql" class="tab <?php echo $tab === 'sql' ? 'active' : ''; ?>">
                        <i class="fas fa-code mr-1"></i>SQL
                    </a>
                    <a href="?table=<?php echo $selectedTable; ?>&tab=search" class="tab <?php echo $tab === 'search' ? 'active' : ''; ?>">
                        <i class="fas fa-search mr-1"></i>Search
                    </a>
                    <a href="?table=<?php echo $selectedTable; ?>&tab=insert" class="tab <?php echo $tab === 'insert' ? 'active' : ''; ?>">
                        <i class="fas fa-plus mr-1"></i>Insert
                    </a>
                    <a href="?table=<?php echo $selectedTable; ?>&tab=export" class="tab <?php echo $tab === 'export' ? 'active' : ''; ?>">
                        <i class="fas fa-download mr-1"></i>Export
                    </a>
                </div>
                
                <?php if ($tab === 'structure'): ?>
                    <h3 style="font-size: 14px; margin-bottom: 10px;">Columns</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Field</th>
                                <th>Type</th>
                                <th>Collation</th>
                                <th>Null</th>
                                <th>Key</th>
                                <th>Default</th>
                                <th>Extra</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($columns as $col): ?>
                                <tr>
                                    <td><strong><?php echo $col['Field']; ?></strong></td>
                                    <td><?php echo $col['Type']; ?></td>
                                    <td><?php echo $col['Collation'] ?? '-'; ?></td>
                                    <td><?php echo $col['Null']; ?></td>
                                    <td><?php echo $col['Key'] ?: '-'; ?></td>
                                    <td><?php echo $col['Default'] ?? 'None'; ?></td>
                                    <td><?php echo $col['Extra'] ?: '-'; ?></td>
                                    <td class="action-links">
                                        <a href="#" class="action-link"><i class="fas fa-edit"></i>Change</a>
                                        <a href="#" class="action-link" style="color: #dc3545;"><i class="fas fa-trash"></i>Drop</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php if (!empty($indexes)): ?>
                        <h3 style="font-size: 14px; margin: 20px 0 10px;">Indexes</h3>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Keyname</th>
                                    <th>Type</th>
                                    <th>Unique</th>
                                    <th>Packed</th>
                                    <th>Column</th>
                                    <th>Cardinality</th>
                                    <th>Collation</th>
                                    <th>Null</th>
                                    <th>Comment</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($indexes as $idx): ?>
                                    <tr>
                                        <td><?php echo $idx['Key_name']; ?></td>
                                        <td><?php echo $idx['Index_type']; ?></td>
                                        <td><?php echo $idx['Non_unique'] ? 'No' : 'Yes'; ?></td>
                                        <td><?php echo $idx['Packed'] ?? '-'; ?></td>
                                        <td><?php echo $idx['Column_name']; ?></td>
                                        <td><?php echo $idx['Cardinality'] ?? '-'; ?></td>
                                        <td><?php echo $idx['Collation'] ?? '-'; ?></td>
                                        <td><?php echo $idx['Null']; ?></td>
                                        <td><?php echo $idx['Comment'] ?: '-'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                    
                <?php elseif ($tab === 'browse'): ?>
                    <?php if (!empty($tableData)): ?>
                        <div style="margin-bottom: 10px; font-size: 12px; color: #666;">
                            Showing rows <?php echo $offset + 1; ?> - <?php echo min($offset + $perPage, $totalRecords); ?> 
                            (<?php echo number_format($totalRecords); ?> total)
                        </div>
                        
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th width="30"><input type="checkbox" class="checkbox"></th>
                                    <?php foreach ($columns as $col): ?>
                                        <th><?php echo $col['Field']; ?></th>
                                    <?php endforeach; ?>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Get primary key field
                                $primaryKeyField = '';
                                foreach ($columns as $col) {
                                    if ($col['Key'] === 'PRI') {
                                        $primaryKeyField = $col['Field'];
                                        break;
                                    }
                                }
                                foreach ($tableData as $row): 
                                    $rowId = $row[$primaryKeyField] ?? '';
                                ?>
                                    <tr>
                                        <td><input type="checkbox" class="checkbox"></td>
                                        <?php foreach ($row as $key => $value): ?>
                                            <td class="truncate" title="<?php echo htmlspecialchars($value ?? ''); ?>">
                                                <?php 
                                                $display = htmlspecialchars($value ?? '');
                                                echo strlen($display) > 50 ? substr($display, 0, 50) . '...' : $display;
                                                ?>
                                            </td>
                                        <?php endforeach; ?>
                                        <td class="action-links">
                                            <a href="?table=<?php echo $selectedTable; ?>&tab=edit&id=<?php echo $rowId; ?>&page=<?php echo $page; ?>" class="action-link"><i class="fas fa-edit"></i>Edit</a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this record? This cannot be undone.');">
                                                <input type="hidden" name="id" value="<?php echo $rowId; ?>">
                                                <input type="hidden" name="page" value="<?php echo $page; ?>">
                                                <button type="submit" name="delete_record" class="action-link" style="background: none; border: none; padding: 0; color: #dc3545; cursor: pointer;">
                                                    <i class="fas fa-trash"></i>Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <?php if ($totalPages > 1): ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?table=<?php echo $selectedTable; ?>&tab=browse&page=1" class="page-link"><<</a>
                                    <a href="?table=<?php echo $selectedTable; ?>&tab=browse&page=<?php echo $page - 1; ?>" class="page-link"><</a>
                                <?php endif; ?>
                                
                                <?php
                                $start = max(1, $page - 2);
                                $end = min($totalPages, $page + 2);
                                for ($i = $start; $i <= $end; $i++):
                                ?>
                                    <a href="?table=<?php echo $selectedTable; ?>&tab=browse&page=<?php echo $i; ?>" 
                                       class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="?table=<?php echo $selectedTable; ?>&tab=browse&page=<?php echo $page + 1; ?>" class="page-link">></a>
                                    <a href="?table=<?php echo $selectedTable; ?>&tab=browse&page=<?php echo $totalPages; ?>" class="page-link">>></a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="info-panel" style="text-align: center; padding: 40px;">
                            <i class="fas fa-inbox fa-3x" style="color: #ccc; margin-bottom: 15px;"></i>
                            <p>No rows found in this table</p>
                        </div>
                    <?php endif; ?>
                    
                <?php elseif ($tab === 'sql'): ?>
                    <?php
                    // Process SQL query submission with validation
                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['execute_sql']) && isset($_POST['sql_query'])) {
                        $sql = trim($_POST['sql_query']);
                        $upperSql = strtoupper($sql);
                        
                        // BLOCK SQL QUERIES that try to access other tables
                        $forbiddenTables = ['employees', 'branches', 'users', 'activity_logs', 'documents', 
                                            'cash_advances', 'overtime_requests', 'notifications', 
                                            'government_deductions', 'performance_adjustments', 
                                            'daily_payroll_reports', 'weekly_payroll_reports', 'procurement_requests'];
                        
                        $blocked = false;
                        foreach ($forbiddenTables as $forbidden) {
                            if (strpos($upperSql, strtoupper($forbidden)) !== false) {
                                $blocked = true;
                                echo '<div class="message message-error">
                                    <i class="fas fa-ban mr-2"></i>
                                    <strong>Query Blocked:</strong> Access to table "' . htmlspecialchars($forbidden) . '" is not allowed. 
                                    You can only query the <strong>attendance</strong> table.
                                </div>';
                                break;
                            }
                        }
                        
                        if (!$blocked) {
                            // Execute the query
                            $result = mysqli_query($db, $sql);
                            if ($result) {
                                $_SESSION['last_sql'] = $sql;
                                echo '<div class="message message-success">
                                    <i class="fas fa-check mr-2"></i>
                                    Query executed successfully.
                                </div>';
                                
                                // Display results if SELECT
                                if (strpos($upperSql, 'SELECT') === 0) {
                                    echo '<h3 style="margin: 15px 0;">Query Results</h3>';
                                    echo '<div style="overflow-x: auto;">';
                                    echo '<table class="data-table">';
                                    echo '<thead><tr>';
                                    $fields = mysqli_fetch_fields($result);
                                    foreach ($fields as $field) {
                                        echo '<th>' . htmlspecialchars($field->name) . '</th>';
                                    }
                                    echo '</tr></thead>';
                                    echo '<tbody>';
                                    $rowCount = 0;
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo '<tr>';
                                        foreach ($row as $value) {
                                            echo '<td>' . htmlspecialchars($value ?? 'NULL') . '</td>';
                                        }
                                        echo '</tr>';
                                        $rowCount++;
                                    }
                                    echo '</tbody></table>';
                                    echo '<p style="margin-top: 10px; color: #666;">' . $rowCount . ' rows returned.</p>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<div class="message message-error">
                                    <i class="fas fa-exclamation-circle mr-2"></i>
                                    Error: ' . htmlspecialchars(mysqli_error($db)) . '
                                </div>';
                            }
                        }
                    }
                    ?>
                    <div class="sql-box">
                        <form method="POST" action="?table=<?php echo $selectedTable; ?>&tab=sql">
                            <label style="font-weight: bold; display: block; margin-bottom: 8px;">
                                Run SQL query/queries on table "<?php echo $selectedTable; ?>" (attendance only):
                            </label>
                            <textarea name="sql_query" class="sql-textarea" placeholder="SELECT * FROM attendance WHERE 1 LIMIT 10;"><?php echo isset($_POST['sql_query']) ? htmlspecialchars($_POST['sql_query']) : (isset($_SESSION['last_sql']) ? htmlspecialchars($_SESSION['last_sql']) : "SELECT * FROM attendance WHERE 1 LIMIT 10;"); ?></textarea>
                            <div style="margin-top: 10px;">
                                <button type="submit" name="execute_sql" class="btn btn-primary">
                                    <i class="fas fa-play mr-1"></i>Go
                                </button>
                            </div>
                            <p style="margin-top: 10px; font-size: 11px; color: #666;">
                                <i class="fas fa-info-circle"></i> Note: Only queries on the attendance table are allowed.
                            </p>
                        </form>
                    </div>
                    
                <?php elseif ($tab === 'search'): ?>
                    <div class="info-panel">
                        <form method="GET" action="?">
                            <input type="hidden" name="table" value="<?php echo $selectedTable; ?>">
                            <input type="hidden" name="tab" value="browse">
                            
                            <table class="data-table" style="border: none;">
                                <tbody>
                                    <?php foreach ($columns as $col): ?>
                                        <tr>
                                            <td style="width: 200px;"><strong><?php echo $col['Field']; ?></strong></td>
                                            <td>
                                                <select name="<?php echo $col['Field']; ?>_operator" style="padding: 4px; margin-right: 10px;">
                                                    <option value="LIKE%..%">LIKE %...%</option>
                                                    <option value="LIKE%">LIKE ...%</option>
                                                    <option value="LIKE%">LIKE %...</option>
                                                    <option value="=">=</option>
                                                    <option value="!=">!=</option>
                                                    <option value=">">></option>
                                                    <option value="<"><</option>
                                                    <option value=">=">>=</option>
                                                    <option value="<="><=</option>
                                                </select>
                                                <input type="text" name="<?php echo $col['Field']; ?>_value" placeholder="Search value" style="padding: 4px; width: 300px;">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                            <div style="margin-top: 15px;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search mr-1"></i>Go
                                </button>
                                <button type="reset" class="btn" style="margin-left: 10px;">Reset</button>
                            </div>
                        </form>
                    </div>
                    
                <?php elseif ($tab === 'insert'): ?>
                    <div class="info-panel">
                        <form method="POST">
                            <table class="data-table" style="border: none;">
                                <thead>
                                    <tr>
                                        <th>Field</th>
                                        <th>Type</th>
                                        <th>Function</th>
                                        <th>Value</th>
                                        <th>Null</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($columns as $col): ?>
                                        <tr>
                                            <td><strong><?php echo $col['Field']; ?></strong></td>
                                            <td><?php echo $col['Type']; ?></td>
                                            <td>
                                                <select name="<?php echo $col['Field']; ?>_func" style="padding: 4px;">
                                                    <option value=""></option>
                                                    <option value="NOW">NOW</option>
                                                    <option value="CURDATE">CURDATE</option>
                                                    <option value="MD5">MD5</option>
                                                </select>
                                            </td>
                                            <td><input type="text" name="<?php echo $col['Field']; ?>" style="padding: 4px; width: 100%;"></td>
                                            <td style="text-align: center;">
                                                <?php if ($col['Null'] === 'YES'): ?>
                                                    <input type="checkbox" name="<?php echo $col['Field']; ?>_null">
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                            <div style="margin-top: 15px;">
                                <button type="submit" name="insert_record" class="btn btn-primary">
                                    <i class="fas fa-plus mr-1"></i>Go
                                </button>
                            </div>
                        </form>
                    </div>
                    
                <?php elseif ($tab === 'export'): ?>
                    <div class="info-panel">
                        <h3 style="margin-bottom: 15px;">Export table "<?php echo $selectedTable; ?>"</h3>
                        
                        <form method="POST" action="api/export_table.php?table=<?php echo $selectedTable; ?>">
                            <div style="margin-bottom: 15px;">
                                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Export method:</label>
                                <label style="display: block; margin: 5px 0;">
                                    <input type="radio" name="export_method" value="quick" checked> Quick - display only the minimal options
                                </label>
                                <label style="display: block; margin: 5px 0;">
                                    <input type="radio" name="export_method" value="custom"> Custom - display all possible options
                                </label>
                            </div>
                            
                            <div style="margin-bottom: 15px;">
                                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Format:</label>
                                <select name="format" style="padding: 5px;">
                                    <option value="sql">SQL</option>
                                    <option value="csv">CSV</option>
                                    <option value="json">JSON</option>
                                    <option value="excel">Excel</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-download mr-1"></i>Go
                            </button>
                        </form>
                    </div>
                    
                <?php elseif ($tab === 'edit' && isset($_GET['id'])): ?>
                    <?php
                    // Fetch record to edit
                    $editId = intval($_GET['id']);
                    $editPage = intval($_GET['page'] ?? 1);
                    $primaryKeyField = '';
                    foreach ($columns as $col) {
                        if ($col['Key'] === 'PRI') {
                            $primaryKeyField = $col['Field'];
                            break;
                        }
                    }
                    
                    $editRecord = null;
                    if ($primaryKeyField && $editId) {
                        $stmt = mysqli_prepare($db, "SELECT * FROM `$selectedTable` WHERE `$primaryKeyField` = ?");
                        mysqli_stmt_bind_param($stmt, 'i', $editId);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        $editRecord = mysqli_fetch_assoc($result);
                        mysqli_stmt_close($stmt);
                    }
                    ?>
                    
                    <?php if ($editRecord): ?>
                        <h3 style="font-size: 14px; margin-bottom: 15px;">
                            Edit Record - ID: <?php echo $editId; ?>
                        </h3>
                        
                        <div class="info-panel">
                            <form method="POST">
                                <input type="hidden" name="id" value="<?php echo $editId; ?>">
                                <input type="hidden" name="page" value="<?php echo $editPage; ?>">
                                
                                <table class="data-table" style="border: none;">
                                    <thead>
                                        <tr>
                                            <th>Field</th>
                                            <th>Type</th>
                                            <th>Value</th>
                                            <th>Null</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($columns as $col): 
                                            $fieldName = $col['Field'];
                                            $fieldValue = $editRecord[$fieldName] ?? '';
                                            $isPrimary = ($col['Key'] === 'PRI');
                                        ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo $fieldName; ?></strong>
                                                    <?php if ($isPrimary): ?>
                                                        <span style="color: #dc3545; font-size: 10px;">(PK)</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $col['Type']; ?></td>
                                                <td>
                                                    <?php if ($isPrimary): ?>
                                                        <input type="text" value="<?php echo htmlspecialchars($fieldValue); ?>" disabled style="padding: 4px; width: 100%; background: #f0f0f0;">
                                                        <input type="hidden" name="<?php echo $fieldName; ?>" value="<?php echo htmlspecialchars($fieldValue); ?>">
                                                    <?php elseif (strpos($col['Type'], 'text') !== false || strlen($fieldValue) > 100): ?>
                                                        <textarea name="<?php echo $fieldName; ?>" rows="3" style="padding: 4px; width: 100%;"><?php echo htmlspecialchars($fieldValue); ?></textarea>
                                                    <?php else: ?>
                                                        <input type="text" name="<?php echo $fieldName; ?>" value="<?php echo htmlspecialchars($fieldValue); ?>" style="padding: 4px; width: 100%;">
                                                    <?php endif; ?>
                                                </td>
                                                <td style="text-align: center;">
                                                    <?php if ($col['Null'] === 'YES' && !$isPrimary): ?>
                                                        <input type="checkbox" name="<?php echo $fieldName; ?>_null" <?php echo $fieldValue === NULL ? 'checked' : ''; ?>>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                
                                <div style="margin-top: 15px;">
                                    <button type="submit" name="update_record" class="btn btn-primary">
                                        <i class="fas fa-save mr-1"></i>Save
                                    </button>
                                    <a href="?table=<?php echo $selectedTable; ?>&tab=browse&page=<?php echo $editPage; ?>" class="btn" style="margin-left: 10px; text-decoration: none;">
                                        <i class="fas fa-times mr-1"></i>Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="message message-error">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            Record not found or invalid ID.
                        </div>
                        <a href="?table=<?php echo $selectedTable; ?>&tab=browse" class="btn" style="margin-top: 15px;">
                            <i class="fas fa-arrow-left mr-1"></i>Back to Browse
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
                
            <?php endif; ?>
        </div>
    </div>
    
    <div class="footer">
        <div style="display: flex; justify-content: space-between;">
            <div>
                <strong>Developer Database Manager</strong> - MySQL / PHP Interface
            </div>
            <div>
                <?php echo count($tables); ?> tables | <?php echo formatBytes($totalSize); ?> | Server version: 8.4.7
            </div>
        </div>
    </div>
    
    <script>
        function toggleAll(source) {
            const checkboxes = document.querySelectorAll('input[name="tables[]"]');
            checkboxes.forEach(cb => cb.checked = source.checked);
        }
    </script>
</body>
</html>
