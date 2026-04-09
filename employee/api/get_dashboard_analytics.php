<?php
/**
 * Dashboard Analytics API
 * Returns comprehensive analytics data for the admin dashboard
 */

require_once __DIR__ . '/../../conn/db_connection.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Initialize response array
$response = [
    'success' => false,
    'data' => []
];

try {
    // Get date range parameters (default to last 7 days)
    $days = isset($_GET['days']) ? intval($_GET['days']) : 7;
    if ($days < 1 || $days > 90) $days = 7;
    
    $startDate = date('Y-m-d', strtotime("-{$days} days"));
    $endDate = date('Y-m-d');
    
    $analyticsData = [];
    
    // 1. Today's Attendance Summary
    $todayQuery = "SELECT 
        COUNT(*) as total_records,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_count,
        SUM(CASE WHEN status = 'Absent' OR status IS NULL THEN 1 ELSE 0 END) as absent_count,
        SUM(CASE WHEN time_in IS NOT NULL THEN 1 ELSE 0 END) as clocked_in_count
    FROM attendance 
    WHERE attendance_date = CURDATE()";
    
    $todayResult = mysqli_query($db, $todayQuery);
    $todayData = mysqli_fetch_assoc($todayResult);
    
    // Calculate total active employees for percentage
    $activeEmployeesQuery = "SELECT COUNT(*) as count FROM employees WHERE status = 'Active'";
    $activeResult = mysqli_query($db, $activeEmployeesQuery);
    $activeEmployees = mysqli_fetch_assoc($activeResult)['count'];
    
    $analyticsData['today_attendance'] = [
        'present' => intval($todayData['present_count']),
        'late' => intval($todayData['late_count']),
        'absent' => intval($todayData['absent_count']),
        'clocked_in' => intval($todayData['clocked_in_count']),
        'total_employees' => intval($activeEmployees),
        'attendance_rate' => $activeEmployees > 0 ? round((($todayData['present_count'] + $todayData['late_count']) / $activeEmployees) * 100, 1) : 0
    ];
    
    // 2. 7-Day Attendance Trend
    $trendQuery = "SELECT 
        DATE(attendance_date) as date,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late,
        SUM(CASE WHEN status = 'Absent' OR status IS NULL THEN 1 ELSE 0 END) as absent,
        COUNT(DISTINCT employee_id) as total_employees
    FROM attendance 
    WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(attendance_date)
    ORDER BY attendance_date ASC";
    
    $trendResult = mysqli_query($db, $trendQuery);
    $trendData = [];
    while ($row = mysqli_fetch_assoc($trendResult)) {
        $trendData[] = [
            'date' => date('M d', strtotime($row['date'])),
            'full_date' => $row['date'],
            'present' => intval($row['present']),
            'late' => intval($row['late']),
            'absent' => intval($row['absent']),
            'attendance_rate' => $row['total_employees'] > 0 ? round((($row['present'] + $row['late']) / $row['total_employees']) * 100, 1) : 0
        ];
    }
    $analyticsData['attendance_trend'] = $trendData;
    
    // 3. Branch-wise Attendance (Today)
    $branchQuery = "SELECT 
        b.branch_name,
        COUNT(DISTINCT e.id) as total_employees,
        SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN a.status = 'Late' THEN 1 ELSE 0 END) as late,
        SUM(CASE WHEN a.status = 'Absent' OR a.status IS NULL THEN 1 ELSE 0 END) as absent
    FROM branches b
    LEFT JOIN employees e ON b.id = e.branch_id AND e.status = 'Active'
    LEFT JOIN attendance a ON e.id = a.employee_id AND a.attendance_date = CURDATE()
    WHERE b.is_active = 1
    GROUP BY b.id, b.branch_name
    ORDER BY b.branch_name";
    
    $branchResult = mysqli_query($db, $branchQuery);
    $branchData = [];
    while ($row = mysqli_fetch_assoc($branchResult)) {
        $total = $row['present'] + $row['late'] + $row['absent'];
        $attendanceRate = $row['total_employees'] > 0 ? round((($row['present'] + $row['late']) / $row['total_employees']) * 100, 1) : 0;
        $branchData[] = [
            'branch' => $row['branch_name'],
            'total_employees' => intval($row['total_employees']),
            'present' => intval($row['present']),
            'late' => intval($row['late']),
            'absent' => intval($row['absent']),
            'attendance_rate' => $attendanceRate
        ];
    }
    $analyticsData['branch_attendance'] = $branchData;
    
    // 4. Overtime Summary
    $currentMonth = date('Y-m');
    $overtimeQuery = "SELECT 
        SUM(CASE WHEN status = 'pending' THEN requested_hours ELSE 0 END) as pending_hours,
        SUM(CASE WHEN status = 'approved' THEN requested_hours ELSE 0 END) as approved_hours,
        SUM(CASE WHEN status = 'rejected' THEN requested_hours ELSE 0 END) as rejected_hours,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
        COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_count,
        COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_count
    FROM overtime_requests 
    WHERE DATE_FORMAT(requested_at, '%Y-%m') = '{$currentMonth}'";
    
    $overtimeResult = mysqli_query($db, $overtimeQuery);
    $overtimeData = mysqli_fetch_assoc($overtimeResult);
    
    $analyticsData['overtime_summary'] = [
        'pending_hours' => floatval($overtimeData['pending_hours'] ?? 0),
        'approved_hours' => floatval($overtimeData['approved_hours'] ?? 0),
        'rejected_hours' => floatval($overtimeData['rejected_hours'] ?? 0),
        'pending_count' => intval($overtimeData['pending_count'] ?? 0),
        'approved_count' => intval($overtimeData['approved_count'] ?? 0),
        'rejected_count' => intval($overtimeData['rejected_count'] ?? 0)
    ];
    
    // 5. Monthly Overtime Trend (Last 6 months)
    $otTrendQuery = "SELECT 
        DATE_FORMAT(requested_at, '%Y-%m') as month,
        DATE_FORMAT(requested_at, '%b %Y') as month_label,
        SUM(requested_hours) as total_hours,
        SUM(CASE WHEN status = 'approved' THEN requested_hours ELSE 0 END) as approved_hours
    FROM overtime_requests 
    WHERE requested_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(requested_at, '%Y-%m')
    ORDER BY month ASC";
    
    $otTrendResult = mysqli_query($db, $otTrendQuery);
    $otTrendData = [];
    while ($row = mysqli_fetch_assoc($otTrendResult)) {
        $otTrendData[] = [
            'month' => $row['month_label'],
            'total_hours' => floatval($row['total_hours']),
            'approved_hours' => floatval($row['approved_hours'])
        ];
    }
    $analyticsData['overtime_trend'] = $otTrendData;
    
    // 6. Top Overtime Employees (Current Month)
    $topOtQuery = "SELECT 
        e.first_name,
        e.last_name,
        e.employee_code,
        SUM(o.requested_hours) as total_hours,
        COUNT(o.id) as request_count
    FROM overtime_requests o
    JOIN employees e ON o.employee_id = e.id
    WHERE o.status = 'approved' 
    AND DATE_FORMAT(o.requested_at, '%Y-%m') = '{$currentMonth}'
    GROUP BY o.employee_id
    ORDER BY total_hours DESC
    LIMIT 5";
    
    $topOtResult = mysqli_query($db, $topOtQuery);
    $topOtData = [];
    while ($row = mysqli_fetch_assoc($topOtResult)) {
        $topOtData[] = [
            'name' => $row['first_name'] . ' ' . $row['last_name'],
            'code' => $row['employee_code'],
            'hours' => floatval($row['total_hours']),
            'requests' => intval($row['request_count'])
        ];
    }
    $analyticsData['top_overtime_employees'] = $topOtData;
    
    // 7. Cash Advance Summary
    $caQuery = "SELECT 
        SUM(CASE WHEN status = 'Pending' THEN amount ELSE 0 END) as pending_amount,
        SUM(CASE WHEN status = 'Approved' THEN amount ELSE 0 END) as approved_amount,
        SUM(CASE WHEN status = 'Paid' THEN amount ELSE 0 END) as paid_amount,
        COUNT(CASE WHEN status = 'Pending' THEN 1 END) as pending_count,
        COUNT(CASE WHEN status = 'Approved' THEN 1 END) as approved_count,
        COUNT(CASE WHEN status = 'Paid' THEN 1 END) as paid_count
    FROM cash_advances 
    WHERE request_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    
    $caResult = mysqli_query($db, $caQuery);
    $caData = mysqli_fetch_assoc($caResult);
    
    $analyticsData['cash_advance_summary'] = [
        'pending_amount' => floatval($caData['pending_amount'] ?? 0),
        'approved_amount' => floatval($caData['approved_amount'] ?? 0),
        'paid_amount' => floatval($caData['paid_amount'] ?? 0),
        'pending_count' => intval($caData['pending_count'] ?? 0),
        'approved_count' => intval($caData['approved_count'] ?? 0),
        'paid_count' => intval($caData['paid_count'] ?? 0)
    ];
    
    // 8. Employee Distribution by Position
    $positionQuery = "SELECT 
        position,
        COUNT(*) as count,
        ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM employees WHERE status = 'Active'), 1) as percentage
    FROM employees 
    WHERE status = 'Active'
    GROUP BY position
    ORDER BY count DESC";
    
    $positionResult = mysqli_query($db, $positionQuery);
    $positionData = [];
    while ($row = mysqli_fetch_assoc($positionResult)) {
        $positionData[] = [
            'position' => $row['position'],
            'count' => intval($row['count']),
            'percentage' => floatval($row['percentage'])
        ];
    }
    $analyticsData['employee_by_position'] = $positionData;
    
    // 9. Employee Distribution by Branch
    $branchDistQuery = "SELECT 
        b.branch_name,
        COUNT(e.id) as count
    FROM branches b
    LEFT JOIN employees e ON b.id = e.branch_id AND e.status = 'Active'
    WHERE b.is_active = 1
    GROUP BY b.id, b.branch_name
    ORDER BY count DESC";
    
    $branchDistResult = mysqli_query($db, $branchDistQuery);
    $branchDistData = [];
    while ($row = mysqli_fetch_assoc($branchDistResult)) {
        $branchDistData[] = [
            'branch' => $row['branch_name'],
            'count' => intval($row['count'])
        ];
    }
    $analyticsData['employee_by_branch'] = $branchDistData;
    
    // 10. Recent Activity Stats (Last 7 days)
    $activityQuery = "SELECT 
        DATE(created_at) as date,
        COUNT(*) as count
    FROM activity_logs 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC";
    
    $activityResult = mysqli_query($db, $activityQuery);
    $activityData = [];
    while ($row = mysqli_fetch_assoc($activityResult)) {
        $activityData[] = [
            'date' => date('M d', strtotime($row['date'])),
            'count' => intval($row['count'])
        ];
    }
    $analyticsData['activity_trend'] = $activityData;
    
    // Set success response
    $response['success'] = true;
    $response['data'] = $analyticsData;
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

// Output JSON response
echo json_encode($response);
?>
