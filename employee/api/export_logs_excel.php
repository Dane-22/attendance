<?php
// api/export_logs_excel.php - Export activity logs to Excel using CSV approach
require_once __DIR__ . '/../../conn/db_connection.php';
require_once __DIR__ . '/../../functions.php';

// Check if user is logged in and is admin/super admin
session_start();
if (empty($_SESSION['logged_in']) || !in_array($_SESSION['position'], ['Admin', 'Super Admin'])) {
    http_response_code(403);
    die(json_encode(['error' => 'Unauthorized access']));
}

// Get filter parameters
$search_user = trim($_GET['search_user'] ?? '');
$search_action = trim($_GET['search_action'] ?? '');
$per_page = 100; // 100 rows per file/page

// Build query for all matching records
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

$query .= " ORDER BY al.created_at DESC";

// Execute query
$stmt = mysqli_prepare($db, $query);
if (!empty($param_types)) {
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Fetch all records
$logs = [];
while ($log = mysqli_fetch_assoc($result)) {
    $logs[] = $log;
}

$total_records = count($logs);
$total_pages = ceil($total_records / $per_page);

// If only one page, export as HTML which Excel handles with formatting
if ($total_pages <= 1) {
    exportHtml($logs, $total_records, $search_user, $search_action);
} else {
    // Multiple pages - create multiple HTMLs in a ZIP
    exportMultiPageHtml($logs, $per_page, $total_pages, $total_records, $search_user, $search_action);
}

function exportHtml($logs, $total_records, $search_user, $search_action) {
    $filename = 'activity_logs_' . date('Y-m-d_H-i-s') . '.xls';
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; font-family: Calibri, sans-serif; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; }
        th { background-color: #FFA500; font-weight: bold; }
        .title { font-size: 14pt; font-weight: bold; }
        .info { font-size: 10pt; }
    </style>
</head>
<body>
    <table>
        <tr><td colspan="6" class="title">Activity Logs Export</td></tr>
        <tr><td colspan="6" class="info">Generated: ' . date('Y-m-d H:i:s') . '</td></tr>
        <tr><td colspan="6" class="info">Total Records: ' . $total_records . '</td></tr>';
    
    if ($search_user) {
        echo '<tr><td colspan="6" class="info">User Filter: ' . htmlspecialchars($search_user) . '</td></tr>';
    }
    if ($search_action) {
        echo '<tr><td colspan="6" class="info">Action Filter: ' . htmlspecialchars($search_action) . '</td></tr>';
    }
    
    echo '<tr><td colspan="6">&nbsp;</td></tr>
        <tr>
            <th>Timestamp</th>
            <th>User</th>
            <th>Employee Code</th>
            <th>Action</th>
            <th>Details</th>
            <th>IP Address</th>
        </tr>';
    
    foreach ($logs as $log) {
        $user = $log['user_id'] ? ($log['first_name'] . ' ' . $log['last_name']) : 'System';
        $empCode = $log['user_id'] ? $log['employee_code'] : 'N/A';
        
        echo '<tr>
            <td>' . date('Y-m-d H:i:s', strtotime($log['created_at'])) . '</td>
            <td>' . htmlspecialchars($user) . '</td>
            <td>' . htmlspecialchars($empCode) . '</td>
            <td>' . htmlspecialchars($log['action']) . '</td>
            <td>' . htmlspecialchars($log['details'] ?? '') . '</td>
            <td>' . htmlspecialchars($log['ip_address']) . '</td>
        </tr>';
    }
    
    echo '</table></body></html>';
    exit;
}

function exportMultiPageHtml($logs, $per_page, $total_pages, $total_records, $search_user, $search_action) {
    // Create temp directory
    $tempDir = sys_get_temp_dir() . '/excel_export_' . uniqid();
    mkdir($tempDir);
    
    // Generate HTML files
    for ($page = 1; $page <= $total_pages; $page++) {
        $start = ($page - 1) * $per_page;
        $end = min($start + $per_page, $total_records);
        
        $filename = $tempDir . sprintf('/activity_logs_page_%03d.xls', $page);
        $fp = fopen($filename, 'w');
        
        fwrite($fp, '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; font-family: Calibri, sans-serif; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; }
        th { background-color: #FFA500; font-weight: bold; }
        .title { font-size: 14pt; font-weight: bold; }
        .info { font-size: 10pt; }
    </style>
</head>
<body>
    <table>
        <tr><td colspan="6" class="title">Activity Logs Export - Page ' . $page . ' of ' . $total_pages . '</td></tr>');
        
        if ($page === 1) {
            fwrite($fp, '<tr><td colspan="6" class="info">Generated: ' . date('Y-m-d H:i:s') . '</td></tr>');
            fwrite($fp, '<tr><td colspan="6" class="info">Total Records: ' . $total_records . '</td></tr>');
            if ($search_user) fwrite($fp, '<tr><td colspan="6" class="info">User Filter: ' . htmlspecialchars($search_user) . '</td></tr>');
            if ($search_action) fwrite($fp, '<tr><td colspan="6" class="info">Action Filter: ' . htmlspecialchars($search_action) . '</td></tr>');
        }
        
        fwrite($fp, '<tr><td colspan="6">&nbsp;</td></tr>
        <tr>
            <th>Timestamp</th>
            <th>User</th>
            <th>Employee Code</th>
            <th>Action</th>
            <th>Details</th>
            <th>IP Address</th>
        </tr>');
        
        // Data rows for this page
        for ($i = $start; $i < $end; $i++) {
            $log = $logs[$i];
            $user = $log['user_id'] ? ($log['first_name'] . ' ' . $log['last_name']) : 'System';
            $empCode = $log['user_id'] ? $log['employee_code'] : 'N/A';
            
            fwrite($fp, '<tr>
            <td>' . date('Y-m-d H:i:s', strtotime($log['created_at'])) . '</td>
            <td>' . htmlspecialchars($user) . '</td>
            <td>' . htmlspecialchars($empCode) . '</td>
            <td>' . htmlspecialchars($log['action']) . '</td>
            <td>' . htmlspecialchars($log['details'] ?? '') . '</td>
            <td>' . htmlspecialchars($log['ip_address']) . '</td>
        </tr>');
        }
        
        fwrite($fp, '</table></body></html>');
        fclose($fp);
    }
    
    // Create ZIP
    $zipFile = tempnam(sys_get_temp_dir(), 'logs') . '.zip';
    $zip = new ZipArchive();
    $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    
    for ($page = 1; $page <= $total_pages; $page++) {
        $filename = sprintf('activity_logs_page_%03d.xls', $page);
        $zip->addFile($tempDir . '/' . $filename, $filename);
    }
    $zip->close();
    
    // Clean up temp HTML files
    foreach (glob($tempDir . '/*') as $file) {
        unlink($file);
    }
    rmdir($tempDir);
    
    // Send ZIP
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="activity_logs_' . date('Y-m-d_H-i-s') . '.zip"');
    header('Content-Length: ' . filesize($zipFile));
    header('Cache-Control: max-age=0');
    
    readfile($zipFile);
    unlink($zipFile);
    exit;
}
