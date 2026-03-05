<?php
/**
 * Operational Audit Report Generator - Super Admin Only
 * Generates comprehensive operational report with 5 sections
 */

require_once __DIR__ . '/../conn/db_connection.php';
require_once __DIR__ . '/../functions.php';
session_start();

// Super Admin access only
if (empty($_SESSION['logged_in']) || $_SESSION['position'] !== 'Super Admin') {
    header('Location: ../login.php');
    exit;
}

// Get parameters from date selector
$startDate = $_GET['start_date'] ?? date('Y-m-d');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Validate dates
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
    header('Location: audit_report_selector.php');
    exit;
}

// Format period label
if ($startDate === $endDate) {
    $periodLabel = date('F d, Y (l)', strtotime($startDate));
} else {
    $periodLabel = date('M d', strtotime($startDate)) . ' - ' . date('M d, Y', strtotime($endDate));
}

// ============================================
// I. EXECUTIVE SUMMARY DATA
// ============================================
$execSummary = [];

// Overall attendance health
$healthSql = "SELECT 
    COUNT(*) as total_records,
    SUM(CASE WHEN time_in IS NOT NULL THEN 1 ELSE 0 END) as clocked_in,
    SUM(CASE WHEN time_out IS NOT NULL THEN 1 ELSE 0 END) as clocked_out,
    SUM(CASE WHEN time_in IS NOT NULL AND time_out IS NULL THEN 1 ELSE 0 END) as open_sessions,
    COUNT(DISTINCT employee_id) as unique_employees
FROM attendance 
WHERE attendance_date BETWEEN ? AND ?";
$healthStmt = mysqli_prepare($db, $healthSql);
mysqli_stmt_bind_param($healthStmt, 'ss', $startDate, $endDate);
mysqli_stmt_execute($healthStmt);
$healthData = mysqli_fetch_assoc(mysqli_stmt_get_result($healthStmt));

$execSummary['operational_health'] = $healthData['open_sessions'] > 0 
    ? 'Active Operations' 
    : ($healthData['total_records'] > 0 ? 'Completed Cycle' : 'No Activity');
$execSummary['staff_coverage'] = $healthData['unique_employees'];
$execSummary['session_status'] = $healthData['clocked_out'] . ' of ' . $healthData['clocked_in'] . ' shifts completed';

// ============================================
// II. WORKFORCE ANALYTICS DATA
// ============================================
$workforceData = [];

// Attendance percentage
$totalEmployeesQuery = "SELECT COUNT(*) as total FROM employees WHERE status = 'Active' AND LOWER(position) = 'worker'";
$totalResult = mysqli_query($db, $totalEmployeesQuery);
$totalActiveEmployees = mysqli_fetch_assoc($totalResult)['total'];

$attendanceRate = $totalActiveEmployees > 0 
    ? round(($healthData['unique_employees'] / $totalActiveEmployees) * 100, 1) 
    : 0;

$workforceData['attendance_percentage'] = $attendanceRate;

// Punctuality analysis (arrivals 7:00-8:00 AM)
$punctualSql = "SELECT COUNT(*) as punctual_count
FROM attendance 
WHERE attendance_date BETWEEN ? AND ?
AND time_in IS NOT NULL
AND TIME(time_in) BETWEEN '07:00:00' AND '08:00:00'";
$punctualStmt = mysqli_prepare($db, $punctualSql);
mysqli_stmt_bind_param($punctualStmt, 'ss', $startDate, $endDate);
mysqli_stmt_execute($punctualStmt);
$punctualCount = mysqli_fetch_assoc(mysqli_stmt_get_result($punctualStmt))['punctual_count'];

$workforceData['punctual_arrivals'] = $punctualCount;
$workforceData['punctual_percentage'] = $healthData['clocked_in'] > 0 
    ? round(($punctualCount / $healthData['clocked_in']) * 100, 1) 
    : 0;

// Total man-hours
$hoursSql = "SELECT 
    SUM(TIMESTAMPDIFF(MINUTE, time_in, COALESCE(time_out, NOW()))) as total_minutes
FROM attendance 
WHERE attendance_date BETWEEN ? AND ?
AND time_in IS NOT NULL";
$hoursStmt = mysqli_prepare($db, $hoursSql);
mysqli_stmt_bind_param($hoursStmt, 'ss', $startDate, $endDate);
mysqli_stmt_execute($hoursStmt);
$totalMinutes = mysqli_fetch_assoc(mysqli_stmt_get_result($hoursStmt))['total_minutes'] ?? 0;
$workforceData['total_man_hours'] = round($totalMinutes / 60, 2);

// Early arrivals (before 7 AM) and late arrivals (after 8 AM)
$earlySql = "SELECT COUNT(*) as count FROM attendance 
WHERE attendance_date BETWEEN ? AND ?
AND time_in IS NOT NULL AND TIME(time_in) < '07:00:00'";
$earlyStmt = mysqli_prepare($db, $earlySql);
mysqli_stmt_bind_param($earlyStmt, 'ss', $startDate, $endDate);
mysqli_stmt_execute($earlyStmt);
$earlyCount = mysqli_fetch_assoc(mysqli_stmt_get_result($earlyStmt))['count'];

$lateSql = "SELECT COUNT(*) as count FROM attendance 
WHERE attendance_date BETWEEN ? AND ?
AND time_in IS NOT NULL AND TIME(time_in) > '08:00:00'";
$lateStmt = mysqli_prepare($db, $lateSql);
mysqli_stmt_bind_param($lateStmt, 'ss', $startDate, $endDate);
mysqli_stmt_execute($lateStmt);
$lateCount = mysqli_fetch_assoc(mysqli_stmt_get_result($lateStmt))['count'];

$workforceData['early_arrivals'] = $earlyCount;
$workforceData['late_arrivals'] = $lateCount;

// ============================================
// III. OPERATIONAL EFFICIENCY DATA
// ============================================
$efficiencyData = [];

// Main Office vs Field Branches comparison
$branchSql = "SELECT 
    CASE 
        WHEN b.branch_name = 'Main Branch' THEN 'Main Office'
        WHEN b.branch_name IS NOT NULL THEN 'Field Branches'
        ELSE 'Unassigned'
    END as location_type,
    COUNT(*) as record_count,
    SUM(CASE WHEN time_out IS NOT NULL THEN 1 ELSE 0 END) as completed,
    COUNT(DISTINCT a.employee_id) as staff_count
FROM attendance a
LEFT JOIN branches b ON a.branch_name = b.branch_name
WHERE a.attendance_date BETWEEN ? AND ?
AND a.time_in IS NOT NULL
GROUP BY location_type";
$branchStmt = mysqli_prepare($db, $branchSql);
mysqli_stmt_bind_param($branchStmt, 'ss', $startDate, $endDate);
mysqli_stmt_execute($branchStmt);
$branchResult = mysqli_stmt_get_result($branchStmt);

$efficiencyData['locations'] = [];
while ($row = mysqli_fetch_assoc($branchResult)) {
    $efficiencyData['locations'][$row['location_type']] = [
        'records' => $row['record_count'],
        'completed' => $row['completed'],
        'completion_rate' => $row['record_count'] > 0 
            ? round(($row['completed'] / $row['record_count']) * 100, 1) 
            : 0,
        'staff' => $row['staff_count']
    ];
}

// Average shift duration by location
$durationSql = "SELECT 
    CASE 
        WHEN b.branch_name = 'Main Branch' THEN 'Main Office'
        WHEN b.branch_name IS NOT NULL THEN 'Field Branches'
        ELSE 'Unassigned'
    END as location_type,
    AVG(TIMESTAMPDIFF(MINUTE, time_in, time_out)) as avg_duration
FROM attendance a
LEFT JOIN branches b ON a.branch_name = b.branch_name
WHERE a.attendance_date BETWEEN ? AND ?
AND a.time_in IS NOT NULL AND a.time_out IS NOT NULL
GROUP BY location_type";
$durationStmt = mysqli_prepare($db, $durationSql);
mysqli_stmt_bind_param($durationStmt, 'ss', $startDate, $endDate);
mysqli_stmt_execute($durationStmt);
$durationResult = mysqli_stmt_get_result($durationStmt);

while ($row = mysqli_fetch_assoc($durationResult)) {
    if (isset($efficiencyData['locations'][$row['location_type']])) {
        $efficiencyData['locations'][$row['location_type']]['avg_shift_hours'] = 
            round(($row['avg_duration'] ?? 0) / 60, 2);
    }
}

// ============================================
// IV. ANOMALY DETECTION
// ============================================
$anomalies = [];

// Forgotten clock-outs (sessions open > 12 hours)
$forgottenSql = "SELECT 
    a.employee_id,
    e.first_name,
    e.last_name,
    a.attendance_date,
    a.time_in,
    TIMESTAMPDIFF(HOUR, a.time_in, NOW()) as hours_open
FROM attendance a
JOIN employees e ON a.employee_id = e.id
WHERE a.attendance_date BETWEEN ? AND ?
AND a.time_in IS NOT NULL AND a.time_out IS NULL
AND TIMESTAMPDIFF(HOUR, a.time_in, NOW()) > 12";
$forgottenStmt = mysqli_prepare($db, $forgottenSql);
mysqli_stmt_bind_param($forgottenStmt, 'ss', $startDate, $endDate);
mysqli_stmt_execute($forgottenStmt);
$forgottenResult = mysqli_stmt_get_result($forgottenStmt);

while ($row = mysqli_fetch_assoc($forgottenResult)) {
    $anomalies['forgotten_clockouts'][] = $row;
}

// Excessive open sessions count
$anomalies['open_sessions_count'] = $healthData['open_sessions'] ?? 0;

// Short shifts (< 4 hours) - potential incomplete data
$shortSql = "SELECT 
    a.employee_id,
    e.first_name,
    e.last_name,
    a.attendance_date,
    TIMESTAMPDIFF(MINUTE, a.time_in, a.time_out) as minutes
FROM attendance a
JOIN employees e ON a.employee_id = e.id
WHERE a.attendance_date BETWEEN ? AND ?
AND a.time_in IS NOT NULL AND a.time_out IS NOT NULL
AND TIMESTAMPDIFF(MINUTE, a.time_in, a.time_out) < 240";
$shortStmt = mysqli_prepare($db, $shortSql);
mysqli_stmt_bind_param($shortStmt, 'ss', $startDate, $endDate);
mysqli_stmt_execute($shortStmt);
$shortResult = mysqli_stmt_get_result($shortStmt);

while ($row = mysqli_fetch_assoc($shortResult)) {
    $anomalies['short_shifts'][] = $row;
}

// Missing attendance (Active employees with no records)
$missingSql = "SELECT e.id, e.first_name, e.last_name, e.branch_id
FROM employees e
WHERE e.status = 'Active'
AND LOWER(e.position) = 'worker'
AND e.id NOT IN (
    SELECT DISTINCT employee_id 
    FROM attendance 
    WHERE attendance_date BETWEEN ? AND ?
)";
$missingStmt = mysqli_prepare($db, $missingSql);
mysqli_stmt_bind_param($missingStmt, 'ss', $startDate, $endDate);
mysqli_stmt_execute($missingStmt);
$missingResult = mysqli_stmt_get_result($missingStmt);

while ($row = mysqli_fetch_assoc($missingResult)) {
    $anomalies['missing_attendance'][] = $row;
}

// ============================================
// V. STRATEGIC RECOMMENDATIONS
// ============================================
$recommendations = [];

// Rec 1: Based on punctuality
if ($workforceData['punctual_percentage'] < 80) {
    $recommendations[] = [
        'priority' => 'High',
        'category' => 'Workforce Discipline',
        'recommendation' => 'Implement stricter clock-in enforcement between 07:00-08:00 AM. ' . 
            'Current punctuality rate of ' . $workforceData['punctual_percentage'] . '% falls below optimal threshold.',
        'action' => 'Configure automated reminders 15 minutes before shift start.'
    ];
}

// Rec 2: Based on forgotten clock-outs
if ($anomalies['open_sessions_count'] > 5 || !empty($anomalies['forgotten_clockouts'])) {
    $recommendations[] = [
        'priority' => 'High',
        'category' => 'Data Integrity',
        'recommendation' => $anomalies['open_sessions_count'] . ' forgotten clock-outs detected. ' .
            'This impacts payroll accuracy and compliance reporting.',
        'action' => 'Enable automatic clock-out after 14 hours and enforce end-of-shift reminders.'
    ];
}

// Rec 3: Based on missing attendance
$missingCount = count($anomalies['missing_attendance'] ?? []);
if ($missingCount > 0) {
    $recommendations[] = [
        'priority' => 'Medium',
        'category' => 'Attendance Coverage',
        'recommendation' => $missingCount . ' active employee(s) without attendance records for the period.',
        'action' => 'Verify leave status or investigate potential system access issues.'
    ];
}

// Rec 4: Based on short shifts
$shortCount = count($anomalies['short_shifts'] ?? []);
if ($shortCount > 3) {
    $recommendations[] = [
        'priority' => 'Low',
        'category' => 'Operational Efficiency',
        'recommendation' => $shortCount . ' shifts recorded under 4 hours, indicating potential incomplete data capture.',
        'action' => 'Review time capture procedures at field branches.'
    ];
}

// Default recommendation if none triggered
if (empty($recommendations)) {
    $recommendations[] = [
        'priority' => 'Standard',
        'category' => 'Continuous Improvement',
        'recommendation' => 'Operations are functioning within normal parameters. Maintain current monitoring protocols.',
        'action' => 'Schedule weekly review to ensure sustained performance.'
    ];
}

// Generate report timestamp
$reportGenerated = date('F d, Y \a\t h:i A');
$reportDate = date('F d, Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operational Audit Report - <?= $periodLabel ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #f5f5f5;
        }
        .report-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .section-header {
            background: linear-gradient(135deg, #1a365d 0%, #2c5282 100%);
            color: white;
            padding: 1rem 1.5rem;
            margin: 0;
        }
        .section-content {
            padding: 1.5rem;
        }
        .metric-card {
            background: #f7fafc;
            border-left: 4px solid #2c5282;
            padding: 1rem;
            margin-bottom: 0.75rem;
        }
        .anomaly-critical {
            border-left: 4px solid #c53030;
            background: #fff5f5;
        }
        .anomaly-warning {
            border-left: 4px solid #d69e2e;
            background: #fffff0;
        }
        .recommendation-high {
            border-left: 4px solid #c53030;
        }
        .recommendation-medium {
            border-left: 4px solid #d69e2e;
        }
        .recommendation-low {
            border-left: 4px solid #38a169;
        }
        @media print {
            body { background: white; }
            .no-print { display: none !important; }
            .report-container { box-shadow: none; }
        }
    </style>
</head>
<body class="p-4">
    <!-- Print Controls -->
    <div class="no-print max-w-6xl mx-auto mb-4 flex gap-3">
        <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            <i class="fas fa-print mr-2"></i>Print Report
        </button>
        <a href="audit.php?filter=<?= $filter ?>&date=<?= $selectedDate ?>" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
            <i class="fas fa-arrow-left mr-2"></i>Back to Audit
        </a>
    </div>

    <div class="report-container">
        <!-- Report Header -->
        <div class="bg-gradient-to-r from-gray-900 to-gray-800 text-white p-8">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-3xl font-bold mb-2">JAJR CONSTRUCTION</h1>
                    <p class="text-gray-300 text-lg">Daily Operational Audit Report</p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-400">Report Period</p>
                    <p class="text-xl font-semibold"><?= htmlspecialchars($periodLabel) ?></p>
                    <p class="text-sm text-gray-400 mt-2">Generated: <?= $reportGenerated ?></p>
                </div>
            </div>
            <div class="mt-6 flex gap-6 text-sm">
                <div>
                    <span class="text-gray-400">Operational Date:</span>
                    <span class="ml-2 font-semibold"><?= $reportDate ?></span>
                </div>
                <div>
                    <span class="text-gray-400">Location:</span>
                    <span class="ml-2 font-semibold">Main Office & Field Branches</span>
                </div>
                <div>
                    <span class="text-gray-400">Classification:</span>
                    <span class="ml-2 font-semibold text-yellow-400">Board Level</span>
                </div>
            </div>
        </div>

        <!-- I. EXECUTIVE SUMMARY -->
        <section>
            <div class="section-header">
                <h2 class="text-xl font-bold"><i class="fas fa-chart-line mr-3"></i>I. EXECUTIVE SUMMARY</h2>
            </div>
            <div class="section-content">
                <div class="prose max-w-none text-gray-700 leading-relaxed">
                    <p class="text-lg mb-4">
                        This operational audit assesses workforce deployment and attendance compliance for 
                        <strong><?= htmlspecialchars($periodLabel) ?></strong>. The analysis reveals 
                        <strong><?= $execSummary['operational_health'] ?></strong> with 
                        <strong><?= $execSummary['staff_coverage'] ?></strong> personnel actively engaged.
                    </p>
                    
                    <div class="grid grid-cols-3 gap-4 mt-6">
                        <div class="metric-card">
                            <div class="text-sm text-gray-500 uppercase tracking-wide">Operational Status</div>
                            <div class="text-2xl font-bold text-gray-800"><?= $execSummary['operational_health'] ?></div>
                        </div>
                        <div class="metric-card">
                            <div class="text-sm text-gray-500 uppercase tracking-wide">Staff Coverage</div>
                            <div class="text-2xl font-bold text-blue-700"><?= $execSummary['staff_coverage'] ?> Personnel</div>
                        </div>
                        <div class="metric-card">
                            <div class="text-sm text-gray-500 uppercase tracking-wide">Session Completion</div>
                            <div class="text-2xl font-bold text-green-700"><?= $execSummary['session_status'] ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- II. WORKFORCE ANALYTICS -->
        <section>
            <div class="section-header">
                <h2 class="text-xl font-bold"><i class="fas fa-users mr-3"></i>II. WORKFORCE ANALYTICS</h2>
            </div>
            <div class="section-content">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="metric-card text-center">
                        <div class="text-3xl font-bold text-blue-700"><?= $workforceData['attendance_percentage'] ?>%</div>
                        <div class="text-sm text-gray-600 mt-1">Attendance Rate</div>
                    </div>
                    <div class="metric-card text-center">
                        <div class="text-3xl font-bold text-green-700"><?= $workforceData['punctual_arrivals'] ?></div>
                        <div class="text-sm text-gray-600 mt-1">Punctual Arrivals<br><span class="text-xs text-gray-400">(07:00-08:00 AM)</span></div>
                    </div>
                    <div class="metric-card text-center">
                        <div class="text-3xl font-bold text-orange-700"><?= number_format($workforceData['total_man_hours'], 1) ?></div>
                        <div class="text-sm text-gray-600 mt-1">Total Man-Hours</div>
                    </div>
                    <div class="metric-card text-center">
                        <div class="text-3xl font-bold text-purple-700"><?= $workforceData['punctual_percentage'] ?>%</div>
                        <div class="text-sm text-gray-600 mt-1">Punctuality Rate</div>
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-800 mb-3">Punctuality Distribution</h4>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Early Arrivals (Before 07:00 AM)</span>
                            <span class="font-semibold text-green-700"><?= $workforceData['early_arrivals'] ?> staff</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Standard Hours (07:00-08:00 AM)</span>
                            <span class="font-semibold text-blue-700"><?= $workforceData['punctual_arrivals'] ?> staff</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Late Arrivals (After 08:00 AM)</span>
                            <span class="font-semibold text-red-700"><?= $workforceData['late_arrivals'] ?> staff</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- III. OPERATIONAL EFFICIENCY -->
        <section>
            <div class="section-header">
                <h2 class="text-xl font-bold"><i class="fas fa-cogs mr-3"></i>III. OPERATIONAL EFFICIENCY</h2>
            </div>
            <div class="section-content">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="p-3 text-left font-semibold text-gray-700 border">Location</th>
                                <th class="p-3 text-center font-semibold text-gray-700 border">Staff Deployed</th>
                                <th class="p-3 text-center font-semibold text-gray-700 border">Shifts Completed</th>
                                <th class="p-3 text-center font-semibold text-gray-700 border">Completion Rate</th>
                                <th class="p-3 text-center font-semibold text-gray-700 border">Avg Shift Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($efficiencyData['locations'] as $location => $data): ?>
                            <tr>
                                <td class="p-3 border font-medium"><?= $location ?></td>
                                <td class="p-3 border text-center"><?= $data['staff'] ?></td>
                                <td class="p-3 border text-center"><?= $data['completed'] ?> / <?= $data['records'] ?></td>
                                <td class="p-3 border text-center">
                                    <span class="px-2 py-1 rounded <?= $data['completion_rate'] >= 90 ? 'bg-green-100 text-green-800' : ($data['completion_rate'] >= 70 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                                        <?= $data['completion_rate'] ?>%
                                    </span>
                                </td>
                                <td class="p-3 border text-center"><?= $data['avg_shift_hours'] ?? 'N/A' ?> hrs</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($efficiencyData['locations'])): ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-info-circle mr-2"></i>No operational data available for the selected period.
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- IV. ANOMALY DETECTION -->
        <section>
            <div class="section-header bg-gradient-to-r from-red-900 to-red-700">
                <h2 class="text-xl font-bold"><i class="fas fa-exclamation-triangle mr-3"></i>IV. ANOMALY DETECTION</h2>
            </div>
            <div class="section-content">
                <?php 
                $hasAnomalies = !empty($anomalies['forgotten_clockouts']) || 
                                !empty($anomalies['short_shifts']) || 
                                !empty($anomalies['missing_attendance']) ||
                                $anomalies['open_sessions_count'] > 0;
                
                if (!$hasAnomalies): 
                ?>
                <div class="metric-card border-green-500 bg-green-50">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-600 text-2xl mr-4"></i>
                        <div>
                            <div class="font-semibold text-green-800">No Anomalies Detected</div>
                            <div class="text-green-700 text-sm">All attendance records are within normal parameters.</div>
                        </div>
                    </div>
                </div>
                <?php else: ?>

                    <?php if ($anomalies['open_sessions_count'] > 0): ?>
                    <div class="metric-card anomaly-warning mb-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="font-semibold text-red-800">
                                    <i class="fas fa-door-open mr-2"></i>Forgotten Clock-Outs Detected
                                </div>
                                <div class="text-red-700 mt-1">
                                    <?= $anomalies['open_sessions_count'] ?> session(s) remain open beyond normal shift duration.
                                    This may impact payroll processing and compliance reporting.
                                </div>
                            </div>
                            <span class="px-3 py-1 bg-red-200 text-red-800 rounded text-sm font-semibold">ACTION REQUIRED</span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($anomalies['forgotten_clockouts'])): ?>
                    <div class="bg-white border rounded-lg overflow-hidden mb-4">
                        <div class="bg-red-50 p-3 border-b">
                            <span class="font-semibold text-red-800">Extended Open Sessions (>12 hours)</span>
                        </div>
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="p-2 text-left">Employee</th>
                                    <th class="p-2 text-left">Date</th>
                                    <th class="p-2 text-left">Clock-In</th>
                                    <th class="p-2 text-left">Hours Open</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($anomalies['forgotten_clockouts'] as $record): ?>
                                <tr class="border-b">
                                    <td class="p-2"><?= htmlspecialchars($record['first_name'] . ' ' . $record['last_name']) ?></td>
                                    <td class="p-2"><?= $record['attendance_date'] ?></td>
                                    <td class="p-2"><?= date('h:i A', strtotime($record['time_in'])) ?></td>
                                    <td class="p-2 text-red-600 font-semibold"><?= $record['hours_open'] ?> hrs</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($anomalies['missing_attendance'])): ?>
                    <div class="metric-card anomaly-warning mb-4">
                        <div class="font-semibold text-yellow-800 mb-2">
                            <i class="fas fa-user-slash mr-2"></i>Missing Attendance Records
                        </div>
                        <div class="text-yellow-700">
                            <?= count($anomalies['missing_attendance']) ?> active employee(s) without attendance records 
                            for the reporting period. This may indicate leave without approval, system access issues, or non-compliance.
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($anomalies['short_shifts'])): ?>
                    <div class="metric-card anomaly-warning">
                        <div class="font-semibold text-yellow-800 mb-2">
                            <i class="fas fa-clock mr-2"></i>Short Shift Anomalies (< 4 hours)
                        </div>
                        <div class="text-yellow-700">
                            <?= count($anomalies['short_shifts']) ?> shift(s) recorded under 4 hours duration. 
                            These instances may indicate partial attendance capture or operational irregularities requiring review.
                        </div>
                    </div>
                    <?php endif; ?>

                <?php endif; ?>
            </div>
        </section>

        <!-- V. STRATEGIC RECOMMENDATIONS -->
        <section>
            <div class="section-header bg-gradient-to-r from-green-900 to-green-700">
                <h2 class="text-xl font-bold"><i class="fas fa-lightbulb mr-3"></i>V. STRATEGIC RECOMMENDATIONS</h2>
            </div>
            <div class="section-content">
                <div class="space-y-4">
                    <?php foreach ($recommendations as $index => $rec): ?>
                    <div class="metric-card recommendation-<?= strtolower($rec['priority']) ?>">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex items-center">
                                <span class="text-2xl font-bold text-gray-400 mr-3"><?= $index + 1 ?></span>
                                <span class="font-semibold text-gray-800"><?= $rec['category'] ?></span>
                            </div>
                            <span class="px-3 py-1 rounded text-xs font-semibold
                                <?= $rec['priority'] === 'High' ? 'bg-red-100 text-red-800' : 
                                    ($rec['priority'] === 'Medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') ?>">
                                <?= $rec['priority'] ?> PRIORITY
                            </span>
                        </div>
                        <p class="text-gray-700 mb-2 ml-10"><?= $rec['recommendation'] ?></p>
                        <div class="ml-10 bg-gray-50 p-3 rounded">
                            <span class="text-sm font-semibold text-gray-600">Recommended Action:</span>
                            <p class="text-sm text-gray-700 mt-1"><?= $rec['action'] ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Report Footer -->
        <div class="bg-gray-100 p-6 border-t">
            <div class="flex justify-between items-center text-sm text-gray-600">
                <div>
                    <span class="font-semibold">Report Generated By:</span> 
                    <?= htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) ?> 
                    (<?= htmlspecialchars($_SESSION['position']) ?>)
                </div>
                <div>
                    <i class="fas fa-shield-alt mr-1"></i>
                    Confidential - Board Level Distribution Only
                </div>
            </div>
            <div class="mt-4 text-xs text-gray-500 text-center">
                This report contains operational data subject to audit and compliance review. 
                Data accuracy is dependent on real-time attendance system integrity.
            </div>
        </div>
    </div>

    <script>
        // Auto-print option
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('autoprint') === '1') {
            window.print();
        }
    </script>
</body>
</html>
