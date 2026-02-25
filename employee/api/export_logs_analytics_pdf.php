<?php
// api/export_logs_analytics_pdf.php - Export activity logs analytics to PDF
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

// Get analytics data
$analytics = getLogsAnalytics($db, $search_user, $search_action);

// Generate PDF using simple HTML-to-PDF approach
$pdf_content = generatePDFContent($analytics, $search_user, $search_action);

// Set headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="activity_logs_analytics_' . date('Y-m-d_H-i-s') . '.pdf"');
header('Cache-Control: max-age=0');

// Check if mPDF is available, otherwise use HTML output
if (class_exists('mPDF')) {
    $mpdf = new mPDF();
    $mpdf->WriteHTML($pdf_content);
    $mpdf->Output('activity_logs_analytics.pdf', 'D');
} else {
    // Use DomPDF if available
    if (class_exists('DomPDF\Dompdf')) {
        $dompdf = new DomPDF\Dompdf();
        $dompdf->loadHtml($pdf_content);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('activity_logs_analytics.pdf', ['Attachment' => true]);
    } else {
        // Fallback: Generate simple PDF-like output (printable HTML)
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: inline; filename="activity_logs_analytics.html"');
        echo $pdf_content;
    }
}

/**
 * Get analytics data from database
 */
function getLogsAnalytics($db, $search_user, $search_action) {
    $analytics = [
        'total_logs' => 0,
        'total_users' => 0,
        'action_breakdown' => [],
        'daily_stats' => [],
        'top_users' => [],
        'ip_addresses' => []
    ];
    
    // Build where clause
    $where = "WHERE 1=1";
    $params = [];
    $param_types = '';
    
    if ($search_user) {
        $where .= " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_code LIKE ? OR CONCAT(e.first_name, ' ', e.last_name) LIKE ?)";
        $search_term = "%{$search_user}%";
        $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
        $param_types .= 'ssss';
    }
    
    if ($search_action) {
        $where .= " AND al.action LIKE ?";
        $params[] = "%{$search_action}%";
        $param_types .= 's';
    }
    
    // Total logs count
    $query = "SELECT COUNT(*) as total FROM activity_logs al LEFT JOIN employees e ON al.user_id = e.id {$where}";
    $stmt = mysqli_prepare($db, $query);
    if (!empty($param_types)) {
        mysqli_stmt_bind_param($stmt, $param_types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $analytics['total_logs'] = mysqli_fetch_assoc($result)['total'];
    
    // Total unique users
    $query = "SELECT COUNT(DISTINCT al.user_id) as total FROM activity_logs al LEFT JOIN employees e ON al.user_id = e.id {$where}";
    $stmt = mysqli_prepare($db, $query);
    if (!empty($param_types)) {
        mysqli_stmt_bind_param($stmt, $param_types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $analytics['total_users'] = mysqli_fetch_assoc($result)['total'];
    
    // Action breakdown
    $query = "SELECT al.action, COUNT(*) as count 
              FROM activity_logs al 
              LEFT JOIN employees e ON al.user_id = e.id 
              {$where}
              GROUP BY al.action 
              ORDER BY count DESC 
              LIMIT 10";
    $stmt = mysqli_prepare($db, $query);
    if (!empty($param_types)) {
        mysqli_stmt_bind_param($stmt, $param_types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $analytics['action_breakdown'][] = $row;
    }
    
    // Daily stats (last 30 days)
    $query = "SELECT DATE(al.created_at) as date, COUNT(*) as count 
              FROM activity_logs al 
              LEFT JOIN employees e ON al.user_id = e.id 
              {$where} AND al.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
              GROUP BY DATE(al.created_at) 
              ORDER BY date DESC";
    $stmt = mysqli_prepare($db, $query);
    if (!empty($param_types)) {
        mysqli_stmt_bind_param($stmt, $param_types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $analytics['daily_stats'][] = $row;
    }
    
    // Top users by activity - ALL USERS
    $query = "SELECT CONCAT(e.first_name, ' ', e.last_name) as user_name, e.employee_code, COUNT(*) as count 
              FROM activity_logs al 
              LEFT JOIN employees e ON al.user_id = e.id 
              {$where} AND al.user_id IS NOT NULL
              GROUP BY al.user_id 
              ORDER BY count DESC";
    $stmt = mysqli_prepare($db, $query);
    if (!empty($param_types)) {
        mysqli_stmt_bind_param($stmt, $param_types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $analytics['top_users'][] = $row;
    }
    
    // Unique IP addresses
    $query = "SELECT al.ip_address, COUNT(*) as count 
              FROM activity_logs al 
              LEFT JOIN employees e ON al.user_id = e.id 
              {$where}
              GROUP BY al.ip_address 
              ORDER BY count DESC 
              LIMIT 10";
    $stmt = mysqli_prepare($db, $query);
    if (!empty($param_types)) {
        mysqli_stmt_bind_param($stmt, $param_types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $analytics['ip_addresses'][] = $row;
    }
    
    return $analytics;
}

/**
 * Generate PDF HTML content
 */
function generatePDFContent($analytics, $search_user, $search_action) {
    $date = date('F d, Y H:i:s');
    $filters = [];
    if ($search_user) $filters[] = "User: {$search_user}";
    if ($search_action) $filters[] = "Action: {$search_action}";
    $filter_text = empty($filters) ? 'None' : implode(' | ', $filters);
    
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Activity Logs Analytics</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; border-bottom: 3px solid #FFA500; padding-bottom: 20px; margin-bottom: 30px; }
        .header h1 { color: #FFA500; font-size: 24px; margin: 0; }
        .header p { color: #666; margin: 5px 0; }
        .stats-grid { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .stat-card { background: #f8f9fa; border: 1px solid #ddd; border-radius: 8px; padding: 20px; text-align: center; width: 30%; }
        .stat-value { font-size: 32px; font-weight: bold; color: #FFA500; }
        .stat-label { font-size: 14px; color: #666; margin-top: 5px; }
        .section { margin-bottom: 30px; }
        .section-title { background: #FFA500; color: white; padding: 10px 15px; font-size: 14px; font-weight: bold; border-radius: 5px 5px 0 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
        .bar-container { background: #f0f0f0; height: 20px; border-radius: 10px; overflow: hidden; }
        .bar { background: #FFA500; height: 100%; border-radius: 10px; }
        .filters { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .footer { text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; color: #999; font-size: 11px; }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-chart-line"></i> Activity Logs Analytics Report</h1>
        <p>Generated: ' . $date . '</p>
        <p>Filters Applied: ' . htmlspecialchars($filter_text) . '</p>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value">' . number_format($analytics['total_logs']) . '</div>
            <div class="stat-label">Total Activity Logs</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">' . number_format($analytics['total_users']) . '</div>
            <div class="stat-label">Active Users</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">' . count($analytics['action_breakdown']) . '</div>
            <div class="stat-label">Unique Actions</div>
        </div>
    </div>
    
    <!-- Text Summary -->
    <div class="section">
        <div class="section-title">📝 Executive Summary</div>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; line-height: 1.6;">
            ' . generateTextSummary($analytics, $search_user, $search_action) . '
        </div>
    </div>';
    
    // Action Breakdown
    if (!empty($analytics['action_breakdown'])) {
        $max_count = max(array_column($analytics['action_breakdown'], 'count'));
        $html .= '
    <div class="section">
        <div class="section-title">📊 Action Breakdown (Top 10)</div>
        <table>
            <tr>
                <th style="width: 40%;">Action</th>
                <th style="width: 15%;">Count</th>
                <th style="width: 45%;">Distribution</th>
            </tr>';
        foreach ($analytics['action_breakdown'] as $action) {
            $percentage = ($action['count'] / $max_count) * 100;
            $html .= '
            <tr>
                <td>' . htmlspecialchars($action['action']) . '</td>
                <td>' . number_format($action['count']) . '</td>
                <td>
                    <div class="bar-container">
                        <div class="bar" style="width: ' . $percentage . '%;"></div>
                    </div>
                </td>
            </tr>';
        }
        $html .= '
        </table>
    </div>';
    }
    
    // Top Users
    if (!empty($analytics['top_users'])) {
        $html .= '
    <div class="section">
        <div class="section-title">👥 All Active Users</div>
        <table>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 35%;">User</th>
                <th style="width: 25%;">Employee Code</th>
                <th style="width: 35%;">Activities</th>
            </tr>';
        $rank = 1;
        foreach ($analytics['top_users'] as $user) {
            $html .= '
            <tr>
                <td>' . $rank . '</td>
                <td>' . htmlspecialchars($user['user_name']) . '</td>
                <td>' . htmlspecialchars($user['employee_code']) . '</td>
                <td>' . number_format($user['count']) . '</td>
            </tr>';
            $rank++;
        }
        $html .= '
        </table>
    </div>';
    }
    
    // Daily Stats
    if (!empty($analytics['daily_stats'])) {
        $html .= '
    <div class="section">
        <div class="section-title">📅 Daily Activity (Last 30 Days)</div>
        <table>
            <tr>
                <th>Date</th>
                <th>Activity Count</th>
            </tr>';
        foreach (array_slice($analytics['daily_stats'], 0, 15) as $stat) {
            $html .= '
            <tr>
                <td>' . date('M d, Y', strtotime($stat['date'])) . '</td>
                <td>' . number_format($stat['count']) . '</td>
            </tr>';
        }
        $html .= '
        </table>
    </div>';
    }
    
    // IP Addresses
    if (!empty($analytics['ip_addresses'])) {
        $html .= '
    <div class="section">
        <div class="section-title">🌐 Top IP Addresses</div>
        <table>
            <tr>
                <th>IP Address</th>
                <th>Request Count</th>
            </tr>';
        foreach ($analytics['ip_addresses'] as $ip) {
            $html .= '
            <tr>
                <td>' . htmlspecialchars($ip['ip_address']) . '</td>
                <td>' . number_format($ip['count']) . '</td>
            </tr>';
        }
        $html .= '
        </table>
    </div>';
    }
    
    $html .= '
    <div class="footer">
        <p>Activity Logs Analytics Report | Generated by System</p>
        <p>Page 1 of 1</p>
    </div>
</body>
</html>';
    
    return $html;
}

/**
 * Generate text summary of analytics data
 */
function generateTextSummary($analytics, $search_user, $search_action) {
    $summary = [];
    
    // Overview
    $summary[] = "This report shows activity analytics for " . number_format($analytics['total_logs']) . " total log entries from " . number_format($analytics['total_users']) . " active users.";
    
    // Top action
    if (!empty($analytics['action_breakdown'])) {
        $topAction = $analytics['action_breakdown'][0];
        $percentage = round(($topAction['count'] / $analytics['total_logs']) * 100, 1);
        $summary[] = "The most common activity is \"" . $topAction['action'] . "\" accounting for " . number_format($topAction['count']) . " entries (" . $percentage . "% of all activities).";
    }
    
    // Top user
    if (!empty($analytics['top_users'])) {
        $topUser = $analytics['top_users'][0];
        $summary[] = "The most active user is " . $topUser['user_name'] . " (" . $topUser['employee_code'] . ") with " . number_format($topUser['count']) . " activities.";
    }
    
    // Activity trend
    if (!empty($analytics['daily_stats'])) {
        $totalDays = count($analytics['daily_stats']);
        $avgPerDay = round($analytics['total_logs'] / max($totalDays, 1), 1);
        $summary[] = "Activity occurred over " . $totalDays . " days with an average of " . $avgPerDay . " activities per day.";
        
        // Recent activity
        $recentCount = array_sum(array_column(array_slice($analytics['daily_stats'], 0, 7), 'count'));
        $summary[] = "In the most recent 7 days, there were " . number_format($recentCount) . " activities recorded.";
    }
    
    // IP diversity
    if (!empty($analytics['ip_addresses'])) {
        $uniqueIps = count($analytics['ip_addresses']);
        $summary[] = "Activity originated from " . $uniqueIps . " unique IP address(es).";
    }
    
    // Filters applied
    if ($search_user || $search_action) {
        $filters = [];
        if ($search_user) $filters[] = "user matching \"" . $search_user . "\"";
        if ($search_action) $filters[] = "action matching \"" . $search_action . "\"";
        $summary[] = "Results are filtered by " . implode(" and ", $filters) . ".";
    }
    
    return "<p>" . implode("</p><p>", $summary) . "</p>";
}
