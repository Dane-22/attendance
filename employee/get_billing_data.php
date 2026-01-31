<?php
// filepath: c:\wamp64\www\attendance_web\employee\get_billing_data.php
require_once __DIR__ . '/../conn/db_connection.php';
require_once __DIR__ . '/../functions.php';
session_start();

if (empty($_SESSION['logged_in'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit;
}

if (!isset($_GET['emp_id']) || !isset($_GET['view_type'])) {
    header('HTTP/1.1 400 Bad Request');
    exit;
}

$empId = intval($_GET['emp_id']);
$viewType = $_GET['view_type'];

// Get employee details - daily_rate ang column name
$stmt = $conn->prepare("SELECT daily_rate FROM employees WHERE id = ?");
$stmt->bind_param("i", $empId);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();

if (!$employee) {
    header('HTTP/1.1 404 Not Found');
    echo json_encode(['error' => 'Employee not found']);
    exit;
}

// Daily rate is directly from database
$dailyRate = $employee['daily_rate'];

// Also calculate monthly and weekly for reference
$monthlySalary = $dailyRate * 26; // 26 working days (Monday-Saturday)
$weeklySalary = $dailyRate * 6;   // 6 working days (Monday-Saturday)

// Get date range based on view type
function getDateRange($viewType) {
    $today = date('Y-m-d');
    if ($viewType === 'weekly') {
        $startDate = date('Y-m-d', strtotime('-7 days', strtotime($today)));
        $endDate = $today;
    } elseif ($viewType === 'monthly') {
        $startDate = date('Y-m-01', strtotime($today));
        $endDate = date('Y-m-t', strtotime($today));
    } else {
        $startDate = $today;
        $endDate = $today;
    }
    return ['start' => $startDate, 'end' => $endDate];
}

$dateRange = getDateRange($viewType);

// Get attendance records - exclude Sundays (Monday to Saturday only)
$stmt = $conn->prepare("
    SELECT attendance_date, status 
    FROM attendance 
    WHERE employee_id = ? 
    AND attendance_date BETWEEN ? AND ?
    AND DAYOFWEEK(attendance_date) BETWEEN 2 AND 7 -- Monday (2) to Saturday (7)
");
$stmt->bind_param("iss", $empId, $dateRange['start'], $dateRange['end']);
$stmt->execute();
$attendance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Compute salary
function computeSalary($attendance, $dailyRate) {
    $presentDays = 0;
    $lateCount = 0;
    $earlyOutCount = 0;
    $absentCount = 0;
    
    foreach ($attendance as $record) {
        if ($record['status'] === 'Present') {
            $presentDays++;
        } elseif ($record['status'] === 'Late') {
            $presentDays++;
            $lateCount++;
        } elseif ($record['status'] === 'Early Out') {
            $presentDays++;
            $earlyOutCount++;
        } elseif ($record['status'] === 'Absent') {
            $absentCount++;
        }
    }
    
    $gross = $presentDays * $dailyRate;
    return [
        'totalDays' => $presentDays, 
        'gross' => $gross,
        'dailyRate' => $dailyRate,
        'lateCount' => $lateCount,
        'earlyOutCount' => $earlyOutCount,
        'absentCount' => $absentCount,
        'attendanceRecords' => $attendance
    ];
}

// Compute deductions with realistic amounts
function computeDeductions($grossSalary) {
    // Realistic deductions based on Philippine rates
    $sss = min($grossSalary * 0.045, 1350); // 4.5% max 1350
    $philhealth = $grossSalary * 0.025; // 2.5%
    $pagibig = min($grossSalary * 0.02, 100); // 2% max 100
    
    // Tax computation (Based on Philippines Tax Table 2024)
    $taxableIncome = $grossSalary;
    $tax = 0;
    
    if ($taxableIncome <= 20833) {
        $tax = 0;
    } elseif ($taxableIncome <= 33333) {
        $tax = ($taxableIncome - 20833) * 0.20;
    } elseif ($taxableIncome <= 66667) {
        $tax = 2500 + ($taxableIncome - 33333) * 0.25;
    } elseif ($taxableIncome <= 166667) {
        $tax = 10833.33 + ($taxableIncome - 66667) * 0.30;
    } elseif ($taxableIncome <= 666667) {
        $tax = 40833.33 + ($taxableIncome - 166667) * 0.32;
    } else {
        $tax = 200833.33 + ($taxableIncome - 666667) * 0.35;
    }
    
    return [
        'sss' => round($sss, 2),
        'philhealth' => round($philhealth, 2),
        'pagibig' => round($pagibig, 2),
        'tax' => round($tax, 2),
        'totalDeductions' => round($sss + $philhealth + $pagibig + $tax, 2)
    ];
}

// Check for saved performance adjustments
function getSavedPerformance($conn, $empId, $viewType, $dateRange) {
    $stmt = $conn->prepare("
        SELECT performance_score, bonus_amount, remarks 
        FROM performance_adjustments 
        WHERE employee_id = ? 
        AND view_type = ?
        AND adjustment_date BETWEEN ? AND ?
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->bind_param("isss", $empId, $viewType, $dateRange['start'], $dateRange['end']);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Compute performance
function computePerformanceBonus($attendanceRecords, $grossSalary) {
    $performanceScore = 100; // Base score
    
    foreach ($attendanceRecords as $record) {
        if ($record['status'] === 'Late') {
            $performanceScore -= 5; // -5 points per late
        } elseif ($record['status'] === 'Early Out') {
            $performanceScore -= 3; // -3 points per early out
        } elseif ($record['status'] === 'Absent') {
            $performanceScore -= 10; // -10 points per absent
        }
    }
    
    // Cap score between 0 and 100
    $performanceScore = max(0, min(100, $performanceScore));
    
    // Calculate bonus/deduction based on performance score
    $bonus = 0;
    if ($performanceScore >= 95) {
        $bonus = $grossSalary * 0.10; // 10% bonus for excellent performance
    } elseif ($performanceScore >= 90) {
        $bonus = $grossSalary * 0.05; // 5% bonus for good performance
    } elseif ($performanceScore >= 85) {
        $bonus = $grossSalary * 0.02; // 2% bonus for satisfactory performance
    } elseif ($performanceScore < 70) {
        $bonus = -($grossSalary * 0.05); // 5% deduction for poor performance
    } elseif ($performanceScore < 75) {
        $bonus = -($grossSalary * 0.03); // 3% deduction for below average
    } elseif ($performanceScore < 80) {
        $bonus = -($grossSalary * 0.01); // 1% deduction for needs improvement
    }
    
    // Get performance rating
    function getPerformanceRating($score) {
        if ($score >= 95) return 'Excellent';
        if ($score >= 90) return 'Very Good';
        if ($score >= 85) return 'Good';
        if ($score >= 80) return 'Satisfactory';
        if ($score >= 75) return 'Needs Improvement';
        return 'Poor';
    }
    
    return [
        'performanceScore' => $performanceScore,
        'performanceBonus' => round($bonus, 2),
        'performanceRating' => getPerformanceRating($performanceScore),
        'remarks' => ''
    ];
}

// Compute all data
$computation = computeSalary($attendance, $dailyRate);
$deductions = computeDeductions($computation['gross']);

// Check for saved performance adjustments
$savedPerformance = getSavedPerformance($conn, $empId, $viewType, $dateRange);

if ($savedPerformance) {
    // Use saved performance instead of computed
    $performance = [
        'performanceScore' => $savedPerformance['performance_score'],
        'performanceBonus' => $savedPerformance['bonus_amount'],
        'performanceRating' => getPerformanceRating($savedPerformance['performance_score']),
        'remarks' => $savedPerformance['remarks'] ?? ''
    ];
} else {
    // Compute performance normally
    $performance = computePerformanceBonus($attendance, $computation['gross']);
}

// Calculate net pay
$netPay = $computation['gross'] - $deductions['totalDeductions'] + $performance['performanceBonus'];

// Prepare response
$response = [
    'attendance' => $attendance,
    'computation' => array_merge($computation, [
        'monthlySalary' => $monthlySalary,
        'weeklySalary' => $weeklySalary
    ]),
    'deductions' => $deductions,
    'performance' => $performance,
    'dateRange' => $dateRange,
    'viewType' => $viewType,
    'netPay' => round($netPay, 2)
];

header('Content-Type: application/json');
echo json_encode($response);
?>