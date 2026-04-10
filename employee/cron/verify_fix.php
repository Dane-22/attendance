<?php
/**
 * Verify the attendance/payroll discrepancy fix
 * Compares audit view (attendance table) with weekly report view (daily_payroll_reports)
 */

// Set timezone
date_default_timezone_set('Asia/Manila');

// Database connection
require_once __DIR__ . '/../../conn/db_connection.php';

header('Content-Type: text/plain');
echo "=== Verifying Attendance/Payroll Discrepancy Fix ===\n\n";

// Test for Week 2 of April 2026 (the reported issue period)
$year = 2026;
$month = 4;
$week = 2;

// Calculate week boundaries for April 2026 Week 2
// April 1, 2026 is Wednesday (day 3 of week)
// Week 2 would be April 6-10 (Mon-Fri)
$start_date = '2026-04-06';
$end_date = '2026-04-10';

echo "Testing Period: $start_date to $end_date (Week 2, April 2026)\n\n";

// Query 1: Count from attendance table (what audit.php shows)
$attendance_sql = "SELECT 
    a.employee_id,
    e.first_name,
    e.last_name,
    COUNT(DISTINCT a.attendance_date) as attendance_days,
    GROUP_CONCAT(DISTINCT a.attendance_date ORDER BY a.attendance_date) as dates
FROM attendance a
JOIN employees e ON a.employee_id = e.id
WHERE a.attendance_date BETWEEN '$start_date' AND '$end_date'
AND a.time_in IS NOT NULL
AND a.time_out IS NOT NULL
AND LOWER(e.position) = 'worker'
GROUP BY a.employee_id
ORDER BY e.last_name, e.first_name";

$attendance_result = mysqli_query($db, $attendance_sql);
$attendance_data = [];

while ($row = mysqli_fetch_assoc($attendance_result)) {
    $attendance_data[$row['employee_id']] = $row;
}

// Query 2: Count from daily_payroll_reports (what weekly_report.php shows)
$payroll_sql = "SELECT 
    dpr.employee_id,
    e.first_name,
    e.last_name,
    SUM(dpr.days_worked) as payroll_days,
    GROUP_CONCAT(DISTINCT dpr.report_date ORDER BY dpr.report_date) as dates
FROM daily_payroll_reports dpr
JOIN employees e ON dpr.employee_id = e.id
WHERE dpr.report_date BETWEEN '$start_date' AND '$end_date'
GROUP BY dpr.employee_id
ORDER BY e.last_name, e.first_name";

$payroll_result = mysqli_query($db, $payroll_sql);
$payroll_data = [];

while ($row = mysqli_fetch_assoc($payroll_result)) {
    $payroll_data[$row['employee_id']] = $row;
}

// Compare the data
echo "Comparison Results:\n";
echo str_repeat("-", 80) . "\n";
echo sprintf("%-30s | %-12s | %-12s | %-10s\n", "Employee", "Attendance", "Payroll", "Match?");
echo str_repeat("-", 80) . "\n";

$all_employees = array_unique(array_merge(array_keys($attendance_data), array_keys($payroll_data)));
$matches = 0;
$mismatches = 0;
$menuel_data = null;

foreach ($all_employees as $emp_id) {
    $att_days = isset($attendance_data[$emp_id]) ? floatval($attendance_data[$emp_id]['attendance_days']) : 0;
    $pay_days = isset($payroll_data[$emp_id]) ? floatval($payroll_data[$emp_id]['payroll_days']) : 0;
    
    $name = isset($attendance_data[$emp_id]) 
        ? $attendance_data[$emp_id]['last_name'] . ', ' . $attendance_data[$emp_id]['first_name']
        : (isset($payroll_data[$emp_id]) 
            ? $payroll_data[$emp_id]['last_name'] . ', ' . $payroll_data[$emp_id]['first_name']
            : 'Unknown');
    
    $match = ($att_days == $pay_days) ? "✓ YES" : "✗ NO";
    
    if ($att_days == $pay_days) {
        $matches++;
    } else {
        $mismatches++;
    }
    
    // Check if this is Menuel Benitez
    if (stripos($name, 'benitez') !== false && stripos($name, 'menuel') !== false) {
        $menuel_data = [
            'name' => $name,
            'attendance_days' => $att_days,
            'payroll_days' => $pay_days,
            'attendance_dates' => isset($attendance_data[$emp_id]) ? $attendance_data[$emp_id]['dates'] : 'none',
            'payroll_dates' => isset($payroll_data[$emp_id]) ? $payroll_data[$emp_id]['dates'] : 'none'
        ];
    }
    
    echo sprintf("%-30s | %-12s | %-12s | %-10s\n", 
        substr($name, 0, 30), 
        $att_days, 
        $pay_days, 
        $match
    );
}

echo str_repeat("-", 80) . "\n";
echo "\nSummary:\n";
echo "  Total employees checked: " . count($all_employees) . "\n";
echo "  ✓ Matches: $matches\n";
echo "  ✗ Mismatches: $mismatches\n";

// Detailed check for Menuel Benitez
echo "\n" . str_repeat("=", 80) . "\n";
echo "DETAILED CHECK: Menuel Benitez\n";
echo str_repeat("=", 80) . "\n";

if ($menuel_data) {
    echo "Employee: {$menuel_data['name']}\n";
    echo "Attendance days: {$menuel_data['attendance_days']}\n";
    echo "Payroll days: {$menuel_data['payroll_days']}\n";
    echo "Attendance dates: {$menuel_data['attendance_dates']}\n";
    echo "Payroll dates: {$menuel_data['payroll_dates']}\n";
    
    if ($menuel_data['attendance_days'] == $menuel_data['payroll_days']) {
        echo "\n✓ FIX VERIFIED: Menuel Benitez now shows matching data!\n";
    } else {
        echo "\n✗ ISSUE PERSISTS: Menuel Benitez still has a mismatch.\n";
        echo "  Expected (from attendance): {$menuel_data['attendance_days']} days\n";
        echo "  Actual (from payroll): {$menuel_data['payroll_days']} days\n";
    }
} else {
    echo "Menuel Benitez not found in the data for this period.\n";
    echo "This could mean:\n";
    echo "  1. No attendance records for this period\n";
    echo "  2. Employee name spelled differently\n";
    echo "  3. Different week being tested\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "Overall Status: ";
if ($mismatches == 0) {
    echo "✓ PASS - All employees have matching attendance and payroll data\n";
    exit(0);
} else {
    echo "✗ FAIL - $mismatches employee(s) have mismatched data\n";
    exit(1);
}
?>
