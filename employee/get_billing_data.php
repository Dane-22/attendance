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

// Government deduction constants (monthly)
$MONTHLY_PHILHEALTH = 250.00;
$MONTHLY_SSS = 450.00;
$MONTHLY_PAGIBIG = 200.00;

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
        $dayOfMonth = (int)date('j', strtotime($today));
        $payrollWeek = (int)ceil($dayOfMonth / 7);
        if ($payrollWeek > 4) {
            $payrollWeek = 4;
        }
        $monthStart = date('Y-m-01', strtotime($today));
        $startDate = date('Y-m-d', strtotime($monthStart . ' +' . (($payrollWeek - 1) * 7) . ' days'));
        $endCandidate = date('Y-m-d', strtotime($startDate . ' +6 days'));
        $monthEnd = date('Y-m-t', strtotime($today));
        $endDate = (strtotime($endCandidate) > strtotime($monthEnd)) ? $monthEnd : $endCandidate;
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
    SELECT attendance_date, status, time_in, time_out, total_ot_hrs
    FROM attendance 
    WHERE employee_id = ? 
    AND attendance_date BETWEEN ? AND ?
    AND DAYOFWEEK(attendance_date) BETWEEN 2 AND 7 -- Monday (2) to Saturday (7)
");
$stmt->bind_param("iss", $empId, $dateRange['start'], $dateRange['end']);
$stmt->execute();
$attendance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Compute payroll hours and gross pay (daily_rate × total hours)
function computeSalary($attendance, $dailyRate) {
    $presentDays = 0;
    $lateCount = 0;
    $earlyOutCount = 0;
    $absentCount = 0;
    $totalHours = 0.0;

    foreach ($attendance as $record) {
        if (($record['status'] ?? '') === 'Present') {
            $presentDays++;
        } elseif (($record['status'] ?? '') === 'Late') {
            $presentDays++;
            $lateCount++;
        } elseif (($record['status'] ?? '') === 'Early Out') {
            $presentDays++;
            $earlyOutCount++;
        } elseif (($record['status'] ?? '') === 'Absent') {
            $absentCount++;
        }

        $timeIn = $record['time_in'] ?? null;
        $timeOut = $record['time_out'] ?? null;
        if (!empty($timeIn) && !empty($timeOut)) {
            $diff = strtotime($timeOut) - strtotime($timeIn);
            if ($diff > 0) {
                $totalHours += ($diff / 3600);
            }
        }

        $ot = $record['total_ot_hrs'] ?? '';
        if ($ot !== '' && is_numeric($ot)) {
            $totalHours += (float)$ot;
        }
    }

    $gross = $totalHours * (float)$dailyRate;

    return [
        'totalDays' => $presentDays,
        'gross' => $gross,
        'dailyRate' => $dailyRate,
        'lateCount' => $lateCount,
        'earlyOutCount' => $earlyOutCount,
        'absentCount' => $absentCount,
        'totalHours' => $totalHours,
        'attendanceRecords' => $attendance
    ];
}

// Compute deductions based on weekly deduction cycle
function computeDeductions($grossSalary, $monthlySss, $monthlyPhilhealth, $monthlyPagibig) {
    $today = date('Y-m-d');
    $dayOfMonth = (int)date('j', strtotime($today));
    $payrollWeek = (int)ceil($dayOfMonth / 7);
    if ($payrollWeek > 4) {
        $payrollWeek = 4;
    }

    if ($payrollWeek === 4) {
        $sss = 0.00;
        $philhealth = 0.00;
        $pagibig = 0.00;
    } else {
        $sss = $monthlySss / 3;
        $philhealth = $monthlyPhilhealth / 3;
        $pagibig = $monthlyPagibig / 3;
    }

    $total = $sss + $philhealth + $pagibig;

    return [
        'sss' => round($sss, 2),
        'philhealth' => round($philhealth, 2),
        'pagibig' => round($pagibig, 2),
        'tax' => 0.00,
        'totalDeductions' => round($total, 2)
    ];
}

// Compute all data
$computation = computeSalary($attendance, $dailyRate);
$deductions = computeDeductions($computation['gross'], $MONTHLY_SSS, $MONTHLY_PHILHEALTH, $MONTHLY_PAGIBIG);

// Activity logging: payslip viewed
@logActivity(
    $db,
    'Viewed Payslip',
    'Viewed payslip for employee_id=' . $empId . ' (' . $dateRange['start'] . ' to ' . $dateRange['end'] . ')'
);

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

// Determine current payroll week for response
$todayResp = date('Y-m-d');
$dayOfMonthResp = (int)date('j', strtotime($todayResp));
$payrollWeekResp = (int)ceil($dayOfMonthResp / 7);
if ($payrollWeekResp > 4) {
    $payrollWeekResp = 4;
}

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
    'netPay' => round($netPay, 2),
    'payrollWeek' => $payrollWeekResp
];

header('Content-Type: application/json');
echo json_encode($response);
?>