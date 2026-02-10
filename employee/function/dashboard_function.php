<?php
// employee/dashboard.php

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