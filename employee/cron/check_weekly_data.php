<?php
/**
 * Check where weekly_payroll_reports data is coming from
 */

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../../conn/db_connection.php';

echo "=== weekly_payroll_reports Diagnostic ===\n\n";

// 1. Count total records
$result = mysqli_query($db, "SELECT COUNT(*) as total FROM weekly_payroll_reports");
$row = mysqli_fetch_assoc($result);
$total = $row['total'];
echo "Total records: $total\n";

if ($total > 0) {
    // 2. Check latest records
    echo "\n--- Latest 5 Records ---\n";
    $result = mysqli_query($db, "SELECT id, employee_id, report_year, report_month, week_number, branch_id, days_worked, take_home_pay, created_at, updated_at FROM weekly_payroll_reports ORDER BY updated_at DESC LIMIT 5");
    while ($row = mysqli_fetch_assoc($result)) {
        echo "ID {$row['id']}: Emp {$row['employee_id']} - {$row['report_year']}-{$row['report_month']} Week {$row['week_number']} - Branch {$row['branch_id']} - Days: {$row['days_worked']} - Net: {$row['take_home_pay']}\n";
        echo "   Created: {$row['created_at']} | Updated: {$row['updated_at']}\n\n";
    }
    
    // 3. Check if records are from today
    echo "--- Records Created Today (" . date('Y-m-d') . ") ---\n";
    $today = date('Y-m-d');
    $result = mysqli_query($db, "SELECT COUNT(*) as today_count FROM weekly_payroll_reports WHERE DATE(updated_at) = '$today'");
    $row = mysqli_fetch_assoc($result);
    echo "Records updated today: {$row['today_count']}\n";
    
    // 4. Check unique periods
    echo "\n--- Unique Year/Month/Week Combinations ---\n";
    $result = mysqli_query($db, "SELECT report_year, report_month, week_number, COUNT(*) as count FROM weekly_payroll_reports GROUP BY report_year, report_month, week_number ORDER BY report_year DESC, report_month DESC, week_number DESC");
    while ($row = mysqli_fetch_assoc($result)) {
        echo "Year {$row['report_year']}, Month {$row['report_month']}, Week {$row['week_number']}: {$row['count']} records\n";
    }
}

echo "\n=== Source Analysis ===\n";
echo "Records are created/updated by:\n";
echo "1. Viewing weekly_report.php (on-demand calculation)\n";
echo "2. The automated cron scripts (daily and weekly aggregation)\n";
echo "\nTo prevent on-demand saving, modify saveWeeklyReportData() in function/report.php\n";

exit(0);
