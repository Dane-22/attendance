<?php
/**
 * Check daily_payroll_reports table data
 */

date_default_timezone_set('Asia/Manila');

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("CLI only\n");
}

require_once __DIR__ . '/../../conn/db_connection.php';

echo "=== Checking daily_payroll_reports table ===\n";

// Check if table exists
$table_check = mysqli_query($db, "SHOW TABLES LIKE 'daily_payroll_reports'");
if (mysqli_num_rows($table_check) == 0) {
    echo "ERROR: Table daily_payroll_reports does not exist!\n";
    exit(1);
}

// Count total records
$count_result = mysqli_query($db, "SELECT COUNT(*) as total FROM daily_payroll_reports");
$count_row = mysqli_fetch_assoc($count_result);
$total = $count_row['total'];
echo "Total records: $total\n";

if ($total > 0) {
    // Get date range
    $date_result = mysqli_query($db, "SELECT MIN(report_date) as earliest, MAX(report_date) as latest FROM daily_payroll_reports");
    $date_row = mysqli_fetch_assoc($date_result);
    echo "Date range: {$date_row['earliest']} to {$date_row['latest']}\n";
    
    // Get unique periods
    $periods_result = mysqli_query($db, "SELECT DISTINCT report_year, report_month, week_number FROM daily_payroll_reports ORDER BY report_year, report_month, week_number");
    echo "\nPeriods with data:\n";
    while ($p = mysqli_fetch_assoc($periods_result)) {
        echo "  Year {$p['report_year']}, Month {$p['report_month']}, Week {$p['week_number']}\n";
    }
    
    // Sample records
    echo "\nSample records (last 5):\n";
    $sample_result = mysqli_query($db, "SELECT employee_id, report_date, branch_id, days_worked, take_home_pay FROM daily_payroll_reports ORDER BY id DESC LIMIT 5");
    while ($s = mysqli_fetch_assoc($sample_result)) {
        echo "  Emp {$s['employee_id']} - {$s['report_date']} - Branch {$s['branch_id']} - Days: {$s['days_worked']} - Net: {$s['take_home_pay']}\n";
    }
} else {
    echo "Table is empty - no daily payroll data recorded yet.\n";
    echo "The daily script needs to run to populate this table.\n";
}

echo "\n=== Check Complete ===\n";
exit(0);
