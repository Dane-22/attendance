<?php
require_once __DIR__ . '/../../conn/db_connection.php';

echo "=== Checking weekly_payroll_reports ===\n";

// Count records
$r = mysqli_query($db, 'SELECT COUNT(*) as c FROM weekly_payroll_reports');
$row = mysqli_fetch_assoc($r);
echo "Total records: " . $row['c'] . "\n";

if ($row['c'] > 0) {
    echo "\nLatest records:\n";
    $r = mysqli_query($db, 'SELECT id, employee_id, report_year, report_month, week_number, days_worked, take_home_pay, updated_at FROM weekly_payroll_reports ORDER BY updated_at DESC LIMIT 3');
    while ($row = mysqli_fetch_assoc($r)) {
        echo "ID " . $row['id'] . ": Emp " . $row['employee_id'] . " - " . $row['report_year'] . "-" . $row['report_month'] . " Week " . $row['week_number'] . " - Updated: " . $row['updated_at'] . "\n";
    }
}

echo "\n=== Source ===\n";
echo "Records are created when:\n";
echo "1. Someone views weekly_report.php\n";
echo "2. Cron scripts run (daily/weekly aggregation)\n";
