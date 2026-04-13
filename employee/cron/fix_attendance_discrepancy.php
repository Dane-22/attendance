<?php
/**
 * Fix Attendance/Payroll Discrepancy
 * Creates backup, investigates orphaned records, and cleans them up
 */

// Set timezone
date_default_timezone_set('Asia/Manila');

// Database connection
require_once __DIR__ . '/../../conn/db_connection.php';

// Include utility functions for payroll calculation
require_once __DIR__ . '/../../functions.php';

// Log file
$log_file = __DIR__ . '/fix_attendance_discrepancy.log';
$log_message = function($msg) use ($log_file) {
    $timestamp = date('Y-m-d H:i:s');
    $line = "[$timestamp] $msg\n";
    file_put_contents($log_file, $line, FILE_APPEND);
    echo $line;
};

$log_message("=== Starting Attendance/Payroll Discrepancy Fix ===");

// Step 1: Create backup
$backup_table = 'daily_payroll_reports_backup_' . date('Y_m_d_His');
$log_message("Step 1: Creating backup table: $backup_table");

$backup_sql = "CREATE TABLE IF NOT EXISTS $backup_table AS SELECT * FROM daily_payroll_reports";
if (mysqli_query($db, $backup_sql)) {
    $log_message("✓ Backup created successfully: $backup_table");
} else {
    $log_message("✗ ERROR creating backup: " . mysqli_error($db));
    exit(1);
}

// Step 2: Investigate orphaned records
$log_message("\nStep 2: Investigating orphaned records...");

$investigate_sql = "SELECT 
    COUNT(*) as orphaned_records,
    COUNT(DISTINCT dpr.employee_id) as affected_employees,
    SUM(dpr.days_worked) as total_incorrect_days,
    MIN(dpr.report_date) as earliest_date,
    MAX(dpr.report_date) as latest_date
FROM daily_payroll_reports dpr
LEFT JOIN attendance a ON dpr.employee_id = a.employee_id 
    AND dpr.report_date = a.attendance_date
WHERE a.id IS NULL 
   OR a.time_in IS NULL 
   OR a.time_out IS NULL";

$result = mysqli_query($db, $investigate_sql);
if ($result && $row = mysqli_fetch_assoc($result)) {
    $log_message("Found orphaned records:");
    $log_message("  - Total orphaned records: " . $row['orphaned_records']);
    $log_message("  - Affected employees: " . $row['affected_employees']);
    $log_message("  - Total incorrect days: " . $row['total_incorrect_days']);
    $log_message("  - Date range: " . $row['earliest_date'] . " to " . $row['latest_date']);
    
    $orphaned_count = (int)$row['orphaned_records'];
} else {
    $log_message("✗ ERROR investigating records: " . mysqli_error($db));
    exit(1);
}

// Step 3: Show detailed orphaned records
if ($orphaned_count > 0) {
    $log_message("\nStep 3: Detailed orphaned records:");
    
    $detail_sql = "SELECT 
        dpr.employee_id,
        e.first_name,
        e.last_name,
        dpr.report_date,
        dpr.days_worked,
        dpr.branch_id,
        a.id as attendance_id,
        a.time_in,
        a.time_out
    FROM daily_payroll_reports dpr
    JOIN employees e ON dpr.employee_id = e.id
    LEFT JOIN attendance a ON dpr.employee_id = a.employee_id 
        AND dpr.report_date = a.attendance_date
    WHERE a.id IS NULL
       OR a.time_in IS NULL 
       OR a.time_out IS NULL
    ORDER BY dpr.report_date DESC, e.last_name
    LIMIT 20";
    
    $detail_result = mysqli_query($db, $detail_sql);
    while ($row = mysqli_fetch_assoc($detail_result)) {
        $log_message(sprintf("  - Emp %d (%s %s): %s, days=%s, attendance=%s",
            $row['employee_id'],
            $row['first_name'],
            $row['last_name'],
            $row['report_date'],
            $row['days_worked'],
            $row['attendance_id'] ? 'incomplete' : 'none'
        ));
    }
}

// Step 4: Delete orphaned records
$log_message("\nStep 4: Deleting orphaned records...");

$delete_sql = "DELETE dpr FROM daily_payroll_reports dpr
LEFT JOIN attendance a ON dpr.employee_id = a.employee_id 
    AND dpr.report_date = a.attendance_date
WHERE a.id IS NULL 
   OR a.time_in IS NULL 
   OR a.time_out IS NULL";

if (mysqli_query($db, $delete_sql)) {
    $deleted = mysqli_affected_rows($db);
    $log_message("✓ Deleted $deleted orphaned records");
} else {
    $log_message("✗ ERROR deleting records: " . mysqli_error($db));
    exit(1);
}

// Step 5: Regenerate clean data for April 2026
$log_message("\nStep 5: Regenerating clean payroll data for April 2026...");

// We'll regenerate by calling the generate_daily_payroll.php logic inline
$start_date = '2026-04-01';
$end_date = '2026-04-30';

// Government deduction constants
$MONTHLY_PHILHEALTH = 250.00;
$MONTHLY_SSS = 450.00;
$MONTHLY_PAGIBIG = 200.00;

// Fetch attendance records with time_out for April 2026
$attendance_query = "SELECT 
                        a.employee_id,
                        a.attendance_date,
                        a.time_in,
                        a.time_out,
                        a.total_ot_hrs,
                        a.branch_name,
                        e.daily_rate,
                        e.first_name,
                        e.last_name,
                        b.id as branch_id
                     FROM attendance a
                     JOIN employees e ON a.employee_id = e.id
                     LEFT JOIN branches b ON a.branch_name = b.branch_name
                     WHERE a.attendance_date BETWEEN '$start_date' AND '$end_date'
                     AND a.time_out IS NOT NULL
                     AND e.status = 'Active'
                     AND LOWER(e.position) = 'worker'
                     ORDER BY a.attendance_date, a.employee_id";

$attendance_result = mysqli_query($db, $attendance_query);
if (!$attendance_result) {
    $log_message("✗ ERROR fetching attendance: " . mysqli_error($db));
    exit(1);
}

$processed = 0;
$inserted = 0;
$skipped = 0;

while ($row = mysqli_fetch_assoc($attendance_result)) {
    $employee_id = $row['employee_id'];
    $attendance_date = $row['attendance_date'];
    $branch_id = $row['branch_id'];
    
    // Check if record already exists in daily_payroll_reports
    $check_sql = "SELECT id FROM daily_payroll_reports 
                  WHERE employee_id = $employee_id AND report_date = '$attendance_date' AND branch_id = " . ($branch_id ? $branch_id : 'NULL');
    $check_result = mysqli_query($db, $check_sql);
    
    if (mysqli_num_rows($check_result) > 0) {
        $skipped++;
        continue;
    }
    
    // Calculate worked hours
    $time_in = $row['time_in'];
    $time_out = $row['time_out'];
    $worked_hours = 0;
    
    if ($time_in && $time_out) {
        $start_ts = strtotime($time_in);
        $end_ts = strtotime($time_out);
        if ($start_ts !== false && $end_ts !== false && $end_ts > $start_ts) {
            $worked_hours = ($end_ts - $start_ts) / 3600;
        }
    }
    
    $daily_rate = floatval($row['daily_rate'] ?? 0);
    $ot_hours = floatval($row['total_ot_hrs'] ?? 0);
    
    // Calculate payroll values using hours-based calculation (Option E - Hybrid)
    $calc_result = calculateDaysAndPay($worked_hours, $daily_rate);
    $days_worked = $calc_result['days_worked'];
    $basic_pay = $calc_result['gross_pay']; // Use calculated pay based on hours
    $ot_rate = $daily_rate / 8;
    $ot_amount = $ot_hours * $ot_rate;
    $gross_pay = $basic_pay + $ot_amount;
    $performance_allowance = 0.00;
    $gross_plus_allowance = $gross_pay + $performance_allowance;
    
    // Calculate deductions (pro-rated daily)
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, date('m', strtotime($attendance_date)), date('Y', strtotime($attendance_date)));
    $sss_deduction = $MONTHLY_SSS / $days_in_month;
    $philhealth_deduction = $MONTHLY_PHILHEALTH / $days_in_month;
    $pagibig_deduction = $MONTHLY_PAGIBIG / $days_in_month;
    $ca_deduction = 0.00;
    $sss_loan = 0.00;
    $total_deductions = $sss_deduction + $philhealth_deduction + $pagibig_deduction + $ca_deduction + $sss_loan;
    
    $take_home_pay = $gross_plus_allowance - $total_deductions;
    
    // Extract date components
    $report_year = date('Y', strtotime($attendance_date));
    $report_month = date('n', strtotime($attendance_date));
    $report_day = date('j', strtotime($attendance_date));
    $week_number = ceil($report_day / 7);
    if ($week_number > 5) $week_number = 5;
    
    // Insert into daily_payroll_reports
    $insert_sql = "INSERT INTO daily_payroll_reports 
        (employee_id, report_date, report_year, report_month, report_day, week_number, 
         branch_id, days_worked, total_hours, daily_rate, basic_pay, ot_hours, ot_rate, ot_amount,
         performance_allowance, gross_pay, gross_plus_allowance, ca_deduction, 
         sss_deduction, philhealth_deduction, pagibig_deduction, sss_loan, 
         total_deductions, take_home_pay, status, created_at)
        VALUES 
        ($employee_id, '$attendance_date', $report_year, $report_month, $report_day, $week_number,
         " . ($branch_id ? $branch_id : 'NULL') . ", $days_worked, $worked_hours, $daily_rate, $basic_pay, $ot_hours, $ot_rate, $ot_amount,
         $performance_allowance, $gross_pay, $gross_plus_allowance, $ca_deduction, 
         $sss_deduction, $philhealth_deduction, $pagibig_deduction, $sss_loan, 
         $total_deductions, $take_home_pay, 'Pending', NOW())";
    
    if (mysqli_query($db, $insert_sql)) {
        $inserted++;
    } else {
        $log_message("  ERROR inserting emp $employee_id on $attendance_date: " . mysqli_error($db));
    }
    
    $processed++;
}

$log_message("✓ Regenerated $inserted new records (processed: $processed, skipped existing: $skipped)");

// Step 6: Create database trigger to prevent future orphaned records
$log_message("\nStep 6: Creating database trigger to prevent orphaned records...");

$trigger_sql = "
DELIMITER //
CREATE TRIGGER IF NOT EXISTS trg_validate_attendance_before_dpr_insert 
BEFORE INSERT ON daily_payroll_reports
FOR EACH ROW
BEGIN
    DECLARE v_attendance_count INT DEFAULT 0;
    
    SELECT COUNT(*) INTO v_attendance_count
    FROM attendance a
    WHERE a.employee_id = NEW.employee_id
      AND a.attendance_date = NEW.report_date
      AND a.time_in IS NOT NULL
      AND a.time_out IS NOT NULL;
    
    IF v_attendance_count = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot insert daily_payroll_reports record without valid attendance record with time_in and time_out';
    END IF;
END//
DELIMITER ;
";

// Try to create trigger (may fail if already exists or insufficient privileges)
if (mysqli_multi_query($db, $trigger_sql)) {
    while (mysqli_next_result($db)) {} // Clear results
    $log_message("✓ Trigger created successfully");
} else {
    $log_message("⚠ Could not create trigger (may already exist or insufficient privileges): " . mysqli_error($db));
}

$log_message("\n=== Fix Complete ===");
$log_message("Backup table: $backup_table");
$log_message("Review log at: $log_file");

echo "\nDONE. Check the log file for details.\n";
exit(0);
?>
