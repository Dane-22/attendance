<?php
// employee/dashboard.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}
// Check if super_admin (redirect to admin dashboard if super_admin)
if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin') {
    header('Location: ../admin/dashboard.php');
    exit();
}

require('../conn/db_connection.php');

$employeeName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$employeeCode = $_SESSION['employee_code'];
$position = $_SESSION['position'] ?? 'Employee';
$employeeId = $_SESSION['id'] ?? 0;

// Check if there's attendance message from login
if (isset($_SESSION['attendance_message'])) {
    $attendance_message = $_SESSION['attendance_message'];
    unset($_SESSION['attendance_message']);
}

// Get dates for queries
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$weekStart = date('Y-m-d', strtotime('monday this week'));
$monthStart = date('Y-m-01');
$currentYear = date('Y');

// ============ TODAY'S OVERVIEW ============
$totalEmployees = 0;
$presentCount = 0;
$absentCount = 0;
$attendanceRate = 0;

// Total employees
$resTotal = mysqli_query($db, "SELECT COUNT(*) as c FROM employees WHERE status = 'Active'");
if ($resTotal) {
    $rowT = mysqli_fetch_assoc($resTotal);
    $totalEmployees = intval($rowT['c']);
}

// Present today - use attendance_date column
$resPresent = mysqli_query($db, "SELECT COUNT(*) as c FROM attendance WHERE attendance_date = '" . $today . "' AND status = 'Present'");
if ($resPresent) { 
    $presentCount = intval(mysqli_fetch_assoc($resPresent)['c']); 
}

// Absent today - use attendance_date column
$resAbsent = mysqli_query($db, "SELECT COUNT(*) as c FROM attendance WHERE attendance_date = '" . $today . "' AND status = 'Absent'");
if ($resAbsent) { 
    $absentCount = intval(mysqli_fetch_assoc($resAbsent)['c']); 
}

// Calculate attendance rate
if ($totalEmployees > 0) {
    $attendanceRate = round(($presentCount / $totalEmployees) * 100, 1);
}

// ============ EMPLOYEE'S PERSONAL STATISTICS ============
$employeeAttendanceStats = [
    'today_status' => 'Not Marked',
    'total_present' => 0,
    'total_absent' => 0,
    'attendance_rate' => 0,
    'consecutive_days' => 0
];

// Check today's status - use attendance_date column
$todayStatusQuery = "SELECT status FROM attendance WHERE employee_id = ? AND attendance_date = ?";
$stmt = mysqli_prepare($db, $todayStatusQuery);
mysqli_stmt_bind_param($stmt, "is", $employeeId, $today);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if ($row = mysqli_fetch_assoc($result)) {
    $employeeAttendanceStats['today_status'] = $row['status'];
}

// Monthly statistics - use attendance_date column
$monthStatsQuery = "SELECT 
    COUNT(CASE WHEN status = 'Present' THEN 1 END) as total_present,
    COUNT(CASE WHEN status = 'Absent' THEN 1 END) as total_absent
    FROM attendance 
    WHERE employee_id = ? 
    AND MONTH(attendance_date) = MONTH(CURRENT_DATE())
    AND YEAR(attendance_date) = YEAR(CURRENT_DATE())";

$stmt = mysqli_prepare($db, $monthStatsQuery);
mysqli_stmt_bind_param($stmt, "i", $employeeId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    $employeeAttendanceStats['total_present'] = $row['total_present'] ?? 0;
    $employeeAttendanceStats['total_absent'] = $row['total_absent'] ?? 0;
    
    $totalDays = $row['total_present'] + $row['total_absent'];
    if ($totalDays > 0) {
        $employeeAttendanceStats['attendance_rate'] = round(($row['total_present'] / $totalDays) * 100, 1);
    }
}

// Calculate consecutive present days - use attendance_date column
$consecutiveQuery = "SELECT attendance_date, status 
                     FROM attendance 
                     WHERE employee_id = ? 
                     ORDER BY attendance_date DESC";
$stmt = mysqli_prepare($db, $consecutiveQuery);
mysqli_stmt_bind_param($stmt, "i", $employeeId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$consecutiveCount = 0;
$currentDate = $today;
while ($row = mysqli_fetch_assoc($result)) {
    if ($row['status'] == 'Present' && $row['attendance_date'] == $currentDate) {
        $consecutiveCount++;
        $currentDate = date('Y-m-d', strtotime($currentDate . ' -1 day'));
    } else {
        break;
    }
}
$employeeAttendanceStats['consecutive_days'] = $consecutiveCount;

// ============ MONTHLY TREND (Last 6 Months) ============
$monthlyTrend = [];
$trendQuery = "SELECT 
    DATE_FORMAT(attendance_date, '%Y-%m') as month,
    COUNT(CASE WHEN status = 'Present' THEN 1 END) as present,
    COUNT(CASE WHEN status = 'Absent' THEN 1 END) as absent,
    COUNT(*) as total
    FROM attendance 
    WHERE employee_id = ?
    AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(attendance_date, '%Y-%m')
    ORDER BY month DESC";

$stmt = mysqli_prepare($db, $trendQuery);
mysqli_stmt_bind_param($stmt, "i", $employeeId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $rate = $row['total'] > 0 ? round(($row['present'] / $row['total']) * 100, 1) : 0;
    $monthlyTrend[] = [
        'month' => date('M Y', strtotime($row['month'] . '-01')),
        'present' => $row['present'],
        'absent' => $row['absent'],
        'rate' => $rate
    ];
}

// ============ ATTENDANCE RANKING ============
$attendanceRanking = [];
$rankingQuery = "SELECT 
    e.id,
    e.employee_code,
    CONCAT(e.first_name, ' ', e.last_name) as name,
    e.position,
    COUNT(CASE WHEN a.status = 'Present' THEN 1 END) as present_days,
    COUNT(CASE WHEN a.status = 'Absent' THEN 1 END) as absent_days,
    COUNT(*) as total_days,
    ROUND((COUNT(CASE WHEN a.status = 'Present' THEN 1 END) / COUNT(*)) * 100, 1) as attendance_rate
    FROM employees e
    LEFT JOIN attendance a ON e.id = a.employee_id 
        AND MONTH(a.attendance_date) = MONTH(CURRENT_DATE())
        AND YEAR(a.attendance_date) = YEAR(CURRENT_DATE())
    WHERE e.status = 'Active'
    GROUP BY e.id, e.employee_code, e.first_name, e.last_name, e.position
    HAVING total_days > 0
    ORDER BY attendance_rate DESC, present_days DESC
    LIMIT 10";

$result = mysqli_query($db, $rankingQuery);
while ($row = mysqli_fetch_assoc($result)) {
    $attendanceRanking[] = $row;
}

// ============ WEEKLY PATTERN ============
$weeklyPattern = [];
$weeklyQuery = "SELECT 
    DAYNAME(attendance_date) as day_name,
    DAYOFWEEK(attendance_date) as day_num,
    COUNT(CASE WHEN status = 'Present' THEN 1 END) as present,
    COUNT(CASE WHEN status = 'Absent' THEN 1 END) as absent,
    COUNT(*) as total
    FROM attendance 
    WHERE employee_id = ?
    AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL 60 DAY)
    GROUP BY DAYNAME(attendance_date), DAYOFWEEK(attendance_date)
    ORDER BY day_num";

$stmt = mysqli_prepare($db, $weeklyQuery);
mysqli_stmt_bind_param($stmt, "i", $employeeId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $rate = $row['total'] > 0 ? round(($row['present'] / $row['total']) * 100, 1) : 0;
    $weeklyPattern[] = [
        'day' => $row['day_name'],
        'present' => $row['present'],
        'rate' => $rate
    ];
}

// ============ DETAILED ATTENDANCE REPORT ============
$detailedReport = [];
// FIXED QUERY: Removed duplicate created_at and fixed syntax
$reportQuery = "SELECT 
    attendance_date,
    status,
    created_at
    FROM attendance 
    WHERE employee_id = ?
    AND MONTH(attendance_date) = MONTH(CURRENT_DATE())
    AND YEAR(attendance_date) = YEAR(CURRENT_DATE())
    ORDER BY attendance_date DESC";

$stmt = mysqli_prepare($db, $reportQuery);
mysqli_stmt_bind_param($stmt, "i", $employeeId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $detailedReport[] = $row;
}

// ============ ATTENDANCE OVERVIEW FOR CHART ============
$overviewData = [
    'labels' => [],
    'present' => [],
    'absent' => []
];

// Last 7 days overview
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dayLabel = date('D', strtotime($date));
    
    $dayQuery = "SELECT 
        COUNT(CASE WHEN status = 'Present' THEN 1 END) as present,
        COUNT(CASE WHEN status = 'Absent' THEN 1 END) as absent
        FROM attendance 
        WHERE attendance_date = ?";
    
    $stmt = mysqli_prepare($db, $dayQuery);
    mysqli_stmt_bind_param($stmt, "s", $date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $dayData = mysqli_fetch_assoc($result);
    
    $overviewData['labels'][] = $dayLabel;
    $overviewData['present'][] = $dayData['present'] ?? 0;
    $overviewData['absent'][] = $dayData['absent'] ?? 0;
}

// ============ MARK ATTENDANCE FUNCTIONALITY ============
// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     if (isset($_POST['mark_attendance'])) {
//         $status = $_POST['status'];
        
//         // Check if already marked for today
//         $checkQuery = "SELECT id FROM attendance WHERE employee_id = ? AND attendance_date = ?";
//         $stmt = mysqli_prepare($db, $checkQuery);
//         mysqli_stmt_bind_param($stmt, "is", $employeeId, $today);
//         mysqli_stmt_execute($stmt);
//         $result = mysqli_stmt_get_result($stmt);
        
//         if (mysqli_num_rows($result) > 0) {
//             // Update existing
//             $updateQuery = "UPDATE attendance SET status = ?, updated_at = NOW() 
//                            WHERE employee_id = ? AND attendance_date = ?";
//             $stmt = mysqli_prepare($db, $updateQuery);
//             mysqli_stmt_bind_param($stmt, "sis", $status, $employeeId, $today);
//         } else {
//             // Insert new
//             $insertQuery = "INSERT INTO attendance (employee_id, status, attendance_date) 
//                            VALUES (?, ?, ?)";
//             $stmt = mysqli_prepare($db, $insertQuery);
//             mysqli_stmt_bind_param($stmt, "iss", $employeeId, $status, $today);
//         }
        
//         if (mysqli_stmt_execute($stmt)) {
//             $_SESSION['attendance_message'] = "Attendance marked as " . $status . " for today!";
//             header('Location: dashboard.php');
//             exit();
//         }
//     }
// }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Employee Dashboard — JAJR</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="icon" type="image/x-icon" href="../assets/img/profile/jajr-logo.png">
  <style>
    /* Reset and Base Styles */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body.employee-bg {
        font-family: 'Inter', 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        background: #0B0B0B;
        color: #E8E8E8;
        min-height: 100vh;
    }
    
    .app-shell {
        display: flex;
        min-height: 100vh;
    }
    
    /* Main Content Area */
    .main-content {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
        background: #0B0B0B;
    }
    
    /* Header Card */
    .header-card {
        background: #161616;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        border: 0.5px solid rgba(212, 175, 55, 0.2);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
        transition: all 0.3s ease;
    }
    
    .header-card:hover {
        border-color: rgba(212, 175, 55, 0.4);
        box-shadow: 0 6px 16px rgba(212, 175, 55, 0.1);
    }
    
    .header-left {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    
    .menu-toggle {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        padding: 4px;
        color: #d4af37;
        transition: color 0.2s;
    }
    
    .menu-toggle:hover {
        color: #FFD700;
    }
    
    .welcome {
        font-size: 24px;
        font-weight: 700;
        color: #E8E8E8;
        margin-bottom: 4px;
    }
    
    .text-sm {
        font-size: 14px;
        color: #A0A0A0;
    }
    
    .text-gray-500 {
        color: #A0A0A0;
    }
    
    /* Attendance Notification */
    .attendance-notification {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 20px;
        animation: slideIn 0.3s ease-out;
    }
    
    @keyframes slideIn {
        from {
            transform: translateY(-10px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    .notification-content {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    /* Today Status */
    /* .today-status {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 20px;
        padding: 16px;
        border-radius: 8px;
        text-align: center;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .status-present {
        background: #d1fae5;
        color: #065f46;
        border: 2px solid #10b981;
    }
    
    .status-absent {
        background: #fee2e2;
        color: #991b1b;
        border: 2px solid #ef4444;
    }
    
    .status-not-marked {
        background: #fef3c7;
        color: #92400e;
        border: 2px solid #f59e0b;
    } */
    
    /* Mark Attendance Form */
    /* .attendance-mark-form {
        background: white;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .attendance-mark-form h3 {
        font-size: 18px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 16px;
    }
    
    .form-group {
        margin-bottom: 16px;
    }
    
    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #374151;
        font-size: 14px;
    }
    
    .form-select, .form-textarea {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        font-family: 'Inter', sans-serif;
        transition: border-color 0.2s;
    }
    
    .form-select:focus, .form-textarea:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .form-textarea {
        min-height: 100px;
        resize: vertical;
    }
    
    .btn-submit {
        background: #3b82f6;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
        font-size: 14px;
        width: 100%;
    }
    
    .btn-submit:hover {
        background: #2563eb;
    }
     */
    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }
    
    .stat-card {
        background: #161616;
        border-radius: 12px;
        padding: 20px;
        border: 0.5px solid rgba(212, 175, 55, 0.15);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        transition: all 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-4px);
        border-color: rgba(212, 175, 55, 0.35);
        box-shadow: 0 8px 20px rgba(212, 175, 55, 0.15);
    }
    
    .stat-title {
        font-size: 13px;
        color: #808080;
        margin-bottom: 8px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .stat-value {
        font-size: 32px;
        font-weight: 800;
        color: #d4af37;
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .stat-value i {
        color: #d4af37;
        font-size: 28px;
    }
    
    .stat-change {
        font-size: 12px;
        font-weight: 500;
        color: #A0A0A0;
    }
    
    .positive {
        color: #10b981;
    }
    
    .negative {
        color: #ef4444;
    }
    
    /* Analytics Section */
    .analytics-section {
        margin-top: 24px;
        background: #161616;
        border-radius: 12px;
        padding: 24px;
        border: 0.5px solid rgba(212, 175, 55, 0.15);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }
    
    .section-header {
        margin-bottom: 24px;
        padding-bottom: 12px;
        border-bottom: 1px solid rgba(212, 175, 55, 0.15);
    }
    
    .section-title {
        font-size: 20px;
        font-weight: 700;
        color: #d4af37;
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .section-title i {
        color: #d4af37;
        font-size: 22px;
    }
    
    .section-subtitle {
        font-size: 14px;
        color: #808080;
    }
    
    /* Tabs */
    .tabs {
        display: flex;
        border-bottom: 1px solid rgba(212, 175, 55, 0.15);
        margin-bottom: 24px;
        overflow-x: auto;
        scrollbar-width: none;
    }
    
    .tabs::-webkit-scrollbar {
        display: none;
    }
    
    .tab {
        padding: 12px 20px;
        cursor: pointer;
        border-bottom: 2px solid transparent;
        margin-bottom: -1px;
        color: #808080;
        font-weight: 600;
        white-space: nowrap;
        font-size: 14px;
        transition: all 0.2s;
    }
    
    .tab:hover {
        color: #d4af37;
    }
    
    .tab.active {
        color: #d4af37;
        border-bottom-color: #d4af37;
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
        animation: fadeIn 0.3s ease-out;
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Insight Card */
    .insight-card {
        background: linear-gradient(135deg, rgba(212, 175, 55, 0.15), rgba(212, 175, 55, 0.05));
        border: 1px solid rgba(212, 175, 55, 0.25);
        color: #E8E8E8;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }
    
    .insight-title {
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
        color: #d4af37;
    }
    
    .insight-title i {
        color: #d4af37;
        font-size: 18px;
    }
    
    .insight-text {
        font-size: 14px;
        opacity: 0.85;
        line-height: 1.5;
        color: #A0A0A0;
    }
    
    /* Chart Container */
    .chart-container {
        background: #161616;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        border: 0.5px solid rgba(212, 175, 55, 0.15);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        position: relative;
        height: 300px;
    }
    
    /* Data Table */
    .data-table {
        background: #161616;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        margin-bottom: 20px;
        border: 0.5px solid rgba(212, 175, 55, 0.15);
    }
    
    .data-table table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .data-table th {
        background: rgba(212, 175, 55, 0.08);
        padding: 16px;
        text-align: left;
        font-weight: 700;
        color: #d4af37;
        border-bottom: 1px solid rgba(212, 175, 55, 0.15);
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .data-table td {
        padding: 16px;
        border-bottom: 1px solid rgba(212, 175, 55, 0.08);
        color: #E8E8E8;
        font-size: 14px;
    }
    
    .data-table tr {
        background: #161616;
        transition: all 0.2s ease;
    }
    
    .data-table tbody tr:hover {
        background: rgba(212, 175, 55, 0.05);
        border-color: rgba(212, 175, 55, 0.2);
    }
    
    /* Badges */
    .badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .badge-present {
        background: rgba(34, 197, 94, 0.15);
        color: #86efac;
        border: 0.5px solid rgba(34, 197, 94, 0.3);
    }
    
    .badge-absent {
        background: rgba(239, 68, 68, 0.15);
        color: #fca5a5;
        border: 0.5px solid rgba(239, 68, 68, 0.3);
    }
    
    .badge-warning {
        background: rgba(202, 138, 4, 0.15);
        color: #fcd34d;
        border: 0.5px solid rgba(202, 138, 4, 0.3);
    }
    
    /* Rank Badge */
    .rank-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        font-weight: 700;
        font-size: 14px;
        margin-right: 8px;
    }
    
    .rank-1 {
        background: linear-gradient(135deg, #FFD700, #d4af37);
        color: #0B0B0B;
        box-shadow: 0 0 12px rgba(212, 175, 55, 0.4);
    }
    
    .rank-2 {
        background: linear-gradient(135deg, #A0A0A0, #707070);
        color: white;
    }
    
    .rank-3 {
        background: linear-gradient(135deg, #8B7355, #5C4033);
        color: white;
    }
    
    /* Progress Bar */
    .progress-bar {
        height: 8px;
        background: #2D2D2D;
        border-radius: 10px;
        overflow: hidden;
        margin: 12px 0;
        border: 0.5px solid rgba(212, 175, 55, 0.1);
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #FFD700 0%, #d4af37 100%);
        border-radius: 10px;
        transition: width 0.3s ease;
        box-shadow: 0 0 8px rgba(212, 175, 55, 0.5);
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        .main-content {
            padding: 16px;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .header-card {
            flex-direction: column;
            gap: 16px;
            align-items: flex-start;
        }
        
        .chart-container {
            height: 250px;
        }
        
        .tabs {
            flex-wrap: nowrap;
        }
        
        .insight-card {
            padding: 16px;
        }
    }
    
    @media (max-width: 480px) {
        .welcome {
            font-size: 20px;
        }
        
        .stat-value {
            font-size: 24px;
        }
        
        .chart-container {
            height: 200px;
            padding: 16px;
        }
        
        .tab {
            padding: 10px 12px;
            font-size: 13px;
        }
    }
</style>
</head>
<body class="employee-bg">
  <div class="app-shell">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="main-content">
      <!-- Attendance Notification -->
      <?php if (isset($attendance_message)): ?>
      <div class="attendance-notification">
        <div class="notification-content">
          <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
          </svg>
          <span><?php echo htmlspecialchars($attendance_message); ?></span>
        </div>
      </div>
      <?php endif; ?>

      <!-- MONITORING DASHBOARD COMPONENT -->
      <?php include __DIR__ . '/monitoring_dashboard_component.php'; ?>

      <!-- Debug Info (Remove this after testing) -->
      <!-- <div style="background: #f3f4f6; padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 12px; color: #6b7280; border: 1px solid #d1d5db;">
        <strong>Summary:</strong><br>
        Today: <?php echo $today; ?><br> -->
        <!-- Monthly Rate:%<br> -->
         <!-- <?php echo $employeeAttendanceStats['attendance_rate']; ?>
        <br>
        Present Today: <?php echo $presentCount; ?><br>
        Total Employees: <?php echo $totalEmployees; ?>
      </div>  -->

      <div class="header-card">
        <div class="header-left">
          <button id="sidebarToggle" class="menu-toggle" aria-label="Toggle sidebar">☰</button>
          <div>
            <div class="welcome">Welcome back, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</div>
            <div class="text-sm text-gray-500">
                Employee Code: <strong><?php echo htmlspecialchars($employeeCode); ?></strong> | 
                Position: <?php echo htmlspecialchars($position); ?>
            </div>
          </div>
        </div>
        <div class="text-sm text-gray-500">
            Today: <?php echo date('F d, Y'); ?>
        </div>
      </div>


      <!-- Personal Attendance Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-title">Your Monthly Attendance Rate</div>
          <div class="stat-value"><?php echo $employeeAttendanceStats['attendance_rate']; ?>%</div>
          <div class="progress-bar">
            <div class="progress-fill" style="width: <?php echo $employeeAttendanceStats['attendance_rate']; ?>%"></div>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-title">Days Present (This Month)</div>
          <div class="stat-value"><?php echo $employeeAttendanceStats['total_present']; ?></div>
          <div class="stat-change positive">
            <?php echo $employeeAttendanceStats['consecutive_days']; ?> consecutive days present
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-title">Days Absent (This Month)</div>
          <div class="stat-value"><?php echo $employeeAttendanceStats['total_absent']; ?></div>
          <div class="stat-change <?php echo $employeeAttendanceStats['total_absent'] > 0 ? 'negative' : 'positive'; ?>">
            <?php echo $employeeAttendanceStats['total_absent'] > 0 ? 'Needs improvement' : 'Perfect!'; ?>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-title">Today's Company Rate</div>
          <div class="stat-value"><?php echo $attendanceRate; ?>%</div>
          <div class="stat-change positive">
            <?php echo $presentCount; ?> of <?php echo $totalEmployees; ?> employees present
          </div>
        </div>
      </div>

      <!-- Analytics Section -->
      <div class="analytics-section">
        <div class="section-header">
          <div>
            <div class="section-title">Attendance Analytics</div>
            <div class="section-subtitle">Detailed reports and insights about your attendance</div>
          </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
          <div class="tab active" onclick="switchTab('overview')">Overview</div>
          <div class="tab" onclick="switchTab('detailed')">Detailed Report</div>
          <div class="tab" onclick="switchTab('ranking')">Ranking</div>
          <div class="tab" onclick="switchTab('trends')">Trends</div>
        </div>

        <!-- Overview Tab -->
        <div id="overview-tab" class="tab-content active">
          <div class="insight-card">
            <div class="insight-title">📊 Attendance Insight</div>
            <div class="insight-text">
              <?php
              $rate = $employeeAttendanceStats['attendance_rate'];
              if ($rate >= 95) {
                  echo "Excellent attendance! You're setting a great example with " . $rate . "% attendance rate.";
              } elseif ($rate >= 85) {
                  echo "Good attendance at " . $rate . "%. Keep up the consistency!";
              } else {
                  echo "Your attendance rate is " . $rate . "%. Consider improving consistency.";
              }
              ?>
            </div>
          </div>

          <!-- Charts Row -->
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div class="chart-container">
              <canvas id="attendanceChart"></canvas>
            </div>
            <div class="chart-container">
              <canvas id="weeklyPatternChart"></canvas>
            </div>
          </div>

          <!-- Quick Stats -->
          <?php if (!empty($weeklyPattern)): ?>
          <div class="data-table">
            <table>
              <thead>
                <tr>
                  <th>Day of Week</th>
                  <th>Attendance Rate</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($weeklyPattern as $day): ?>
                <tr>
                  <td><?php echo $day['day']; ?></td>
                  <td>
                    <div style="display: flex; align-items: center; gap: 8px;">
                      <span><?php echo $day['rate']; ?>%</span>
                      <div class="progress-bar" style="flex: 1;">
                        <div class="progress-fill" style="width: <?php echo $day['rate']; ?>%"></div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <?php if ($day['rate'] >= 90): ?>
                      <span class="badge badge-present">Consistent</span>
                    <?php elseif ($day['rate'] >= 70): ?>
                      <span class="badge badge-warning">Average</span>
                    <?php else: ?>
                      <span class="badge badge-absent">Low</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
          <div style="text-align: center; padding: 40px; color: #6b7280; background: #f9fafb; border-radius: 8px;">
            No weekly pattern data available yet. Mark more attendance to see patterns.
          </div>
          <?php endif; ?>
        </div>

        <!-- Detailed Report Tab -->
        <div id="detailed-tab" class="tab-content">
          <?php if (!empty($detailedReport)): ?>
          <div class="data-table">
            <table>
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Day</th>
                  <th>Status</th>
                  <th>Marked At</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($detailedReport as $record): ?>
                <tr>
                  <td><?php echo date('M d, Y', strtotime($record['attendance_date'])); ?></td>
                  <td><?php echo date('D', strtotime($record['attendance_date'])); ?></td>
                  <td>
                    <?php if ($record['status'] == 'Present'): ?>
                      <span class="badge badge-present">Present</span>
                    <?php else: ?>
                      <span class="badge badge-absent">Absent</span>
                    <?php endif; ?>
                  </td>
                  <td><?php echo date('h:i A', strtotime($record['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
          <div style="text-align: center; padding: 40px; color: #6b7280; background: #f9fafb; border-radius: 8px;">
            No attendance records found for this month. Mark your attendance to see detailed reports.
          </div>
          <?php endif; ?>
        </div>

        <!-- Ranking Tab -->
        <div id="ranking-tab" class="tab-content">
          <?php if (!empty($attendanceRanking)): ?>
          <div class="data-table">
            <table>
              <thead>
                <tr>
                  <th>Rank</th>
                  <th>Employee</th>
                  <th>Position</th>
                  <th>Present Days</th>
                  <th>Absent Days</th>
                  <th>Attendance Rate</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                $userRank = 0;
                foreach ($attendanceRanking as $index => $employee):
                $rank = $index + 1;
                $isCurrentUser = $employee['id'] == $employeeId;
                if ($isCurrentUser) $userRank = $rank;
                ?>
                <tr style="<?php echo $isCurrentUser ? 'background: #f0f9ff; font-weight: 600;' : ''; ?>">
                  <td>
                    <span class="rank-badge <?php echo 'rank-' . min($rank, 3); ?>">
                      <?php echo $rank; ?>
                    </span>
                    <?php if ($isCurrentUser): ?>
                      <span style="font-size: 12px; color: #3b82f6; margin-left: 4px;">(You)</span>
                    <?php endif; ?>
                  </td>
                  <td><?php echo htmlspecialchars($employee['name']); ?></td>
                  <td><?php echo htmlspecialchars($employee['position']); ?></td>
                  <td><?php echo $employee['present_days']; ?></td>
                  <td><?php echo $employee['absent_days']; ?></td>
                  <td>
                    <div style="display: flex; align-items: center; gap: 8px;">
                      <span><?php echo $employee['attendance_rate']; ?>%</span>
                      <div class="progress-bar" style="flex: 1;">
                        <div class="progress-fill" style="width: <?php echo $employee['attendance_rate']; ?>%"></div>
                      </div>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <?php if ($userRank > 0): ?>
            <div style="padding: 16px; background: #f8fafc; border-top: 1px solid #e5e7eb; text-align: center;">
              <strong>Your Rank:</strong> #<?php echo $userRank; ?> out of <?php echo count($attendanceRanking); ?> employees
            </div>
            <?php endif; ?>
          </div>
          <?php else: ?>
          <div style="text-align: center; padding: 40px; color: #6b7280; background: #f9fafb; border-radius: 8px;">
            No ranking data available for this month. Mark attendance to see rankings.
          </div>
          <?php endif; ?>
        </div>

        <!-- Trends Tab -->
        <div id="trends-tab" class="tab-content">
          <?php if (!empty($monthlyTrend)): ?>
          <div class="chart-container" style="height: 400px;">
            <canvas id="trendChart"></canvas>
          </div>
          
          <div class="data-table">
            <table>
              <thead>
                <tr>
                  <th>Month</th>
                  <th>Present Days</th>
                  <th>Absent Days</th>
                  <th>Attendance Rate</th>
                  <th>Trend</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($monthlyTrend as $month): ?>
                <?php 
                $trendIcon = '';
                $trendClass = '';
                $rate = $month['rate'];
                
                if ($rate >= 95) {
                    $trendIcon = '📈';
                    $trendClass = 'positive';
                } elseif ($rate >= 85) {
                    $trendIcon = '➡️';
                    $trendClass = 'positive';
                } else {
                    $trendIcon = '📉';
                    $trendClass = 'negative';
                }
                ?>
                <tr>
                  <td><?php echo $month['month']; ?></td>
                  <td><?php echo $month['present']; ?></td>
                  <td><?php echo $month['absent']; ?></td>
                  <td><?php echo $rate; ?>%</td>
                  <td class="<?php echo $trendClass; ?>">
                    <?php echo $trendIcon; ?> 
                    <?php echo $rate >= 95 ? 'Excellent' : ($rate >= 85 ? 'Good' : 'Needs Improvement'); ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
          <div style="text-align: center; padding: 40px; color: #6b7280; background: #f9fafb; border-radius: 8px;">
            No trend data available yet. Mark more attendance to see trends.
          </div>
          <?php endif; ?>
        </div>
      </div>
    </main>
  </div>

  <script src="../assets/js/employee.js"></script>
  <script>
    // Tab switching
    function switchTab(tabName) {
      // Hide all tabs
      document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
      });
      
      document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
      });
      
      // Show selected tab
      document.getElementById(tabName + '-tab').classList.add('active');
      
      // Activate tab button
      event.target.classList.add('active');
      
      // Re-render charts if needed
      if (tabName === 'trends' && typeof renderTrendChart === 'function') {
        setTimeout(() => {
          renderTrendChart();
        }, 100);
      }
    }

    // Chart rendering functions
    function renderAttendanceChart() {
      const ctx = document.getElementById('attendanceChart');
      if (!ctx) return;
      
      new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
          labels: <?php echo json_encode($overviewData['labels']); ?>,
          datasets: [
            {
              label: 'Present',
              data: <?php echo json_encode($overviewData['present']); ?>,
              backgroundColor: '#10b981',
              borderColor: '#10b981',
              borderWidth: 1
            },
            {
              label: 'Absent',
              data: <?php echo json_encode($overviewData['absent']); ?>,
              backgroundColor: '#ef4444',
              borderColor: '#ef4444',
              borderWidth: 1
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              title: {
                display: true,
                text: 'Number of Employees'
              }
            }
          },
          plugins: {
            legend: {
              position: 'top',
            },
            title: {
              display: true,
              text: 'Company Attendance (Last 7 Days)'
            }
          }
        }
      });
    }

    function renderWeeklyPatternChart() {
      const ctx = document.getElementById('weeklyPatternChart');
      if (!ctx) return;
      
      const labels = <?php echo json_encode(array_column($weeklyPattern, 'day')); ?>;
      const data = <?php echo json_encode(array_column($weeklyPattern, 'rate')); ?>;
      
      new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
          labels: labels,
          datasets: [{
            label: 'Your Attendance Rate (%)',
            data: data,
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            borderColor: '#3b82f6',
            borderWidth: 2,
            fill: true,
            tension: 0.4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              max: 100,
              title: {
                display: true,
                text: 'Attendance Rate %'
              }
            }
          },
          plugins: {
            legend: {
              position: 'top',
            },
            title: {
              display: true,
              text: 'Your Weekly Attendance Pattern'
            }
          }
        }
      });
    }

    function renderTrendChart() {
      const ctx = document.getElementById('trendChart');
      if (!ctx) return;
      
      const months = <?php echo json_encode(array_column($monthlyTrend, 'month')); ?>;
      const rates = <?php echo json_encode(array_column($monthlyTrend, 'rate')); ?>;
      
      // Reverse for chronological order
      const sortedMonths = [...months].reverse();
      const sortedRates = [...rates].reverse();
      
      new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
          labels: sortedMonths,
          datasets: [{
            label: 'Your Attendance Rate Trend',
            data: sortedRates,
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            borderColor: '#10b981',
            borderWidth: 3,
            fill: true,
            tension: 0.3
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              max: 100,
              title: {
                display: true,
                text: 'Attendance Rate %'
              }
            },
            x: {
              title: {
                display: true,
                text: 'Month'
              }
            }
          },
          plugins: {
            legend: {
              position: 'top',
            },
            title: {
              display: true,
              text: 'Your Attendance Trend (Last 6 Months)'
            }
          }
        }
      });
    }

    // Initialize charts when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
      console.log('Dashboard loaded');
      
      try {
        if (typeof renderAttendanceChart === 'function') {
          renderAttendanceChart();
        }
        if (typeof renderWeeklyPatternChart === 'function') {
          renderWeeklyPatternChart();
        }
        
        // Check if trend tab is active on load
        if (document.getElementById('trends-tab') && document.getElementById('trends-tab').classList.contains('active')) {
          setTimeout(() => {
            if (typeof renderTrendChart === 'function') {
              renderTrendChart();
            }
          }, 100);
        }
      } catch (error) {
        console.error('Error loading charts:', error);
      }
    });

    // Force show analytics section
    document.addEventListener('DOMContentLoaded', function() {
      const analyticsSection = document.querySelector('.analytics-section');
      if (analyticsSection) {
        analyticsSection.style.display = 'block';
      }
    });
  </script>
</body>
</html>