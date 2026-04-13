<?php
/**
 * Daily Payroll Report Generator
 * 
 * This script populates daily_payroll_reports from attendance data
 * Run this to backfill or update daily payroll records from existing attendance
 * 
 * Usage: 
 * 1. Browser: http://your-server/employee/cron/generate_daily_payroll.php?start_date=2026-02-23&end_date=2026-02-28
 * 2. CLI: php generate_daily_payroll.php
 */

// Set timezone
date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../../conn/db_connection.php';

// Include utility functions for payroll calculation
require_once __DIR__ . '/../../functions.php';

// Get date range from parameters or use defaults
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Government deduction constants (monthly)
$MONTHLY_PHILHEALTH = 250.00;
$MONTHLY_SSS = 450.00;
$MONTHLY_PAGIBIG = 200.00;

header('Content-Type: text/plain');
echo "=== Daily Payroll Report Generator ===\n";
echo "Period: $start_date to $end_date\n";
echo "Started: " . date('Y-m-d H:i:s') . "\n\n";

// Fetch attendance records with time_out (completed shifts)
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
                     WHERE a.attendance_date BETWEEN ? AND ?
                     AND a.time_out IS NOT NULL
                     AND e.status = 'Active'
                     AND LOWER(e.position) IN ('worker', 'admin', 'engineer', 'developer')
                     ORDER BY a.attendance_date, a.employee_id";

$stmt = mysqli_prepare($db, $attendance_query);
mysqli_stmt_bind_param($stmt, 'ss', $start_date, $end_date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Merge threshold in minutes - records within this gap will be merged
$MERGE_THRESHOLD_MINUTES = 15;

// Group all attendance records by employee_id and attendance_date for merging
$attendance_groups = [];
while ($row = mysqli_fetch_assoc($result)) {
    $key = $row['employee_id'] . '_' . $row['attendance_date'];
    if (!isset($attendance_groups[$key])) {
        $attendance_groups[$key] = [
            'employee_id' => $row['employee_id'],
            'attendance_date' => $row['attendance_date'],
            'daily_rate' => $row['daily_rate'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'branch_id' => $row['branch_id'],
            'branch_name' => $row['branch_name'],
            'records' => []
        ];
    }
    $attendance_groups[$key]['records'][] = [
        'time_in' => $row['time_in'],
        'time_out' => $row['time_out'],
        'total_ot_hrs' => $row['total_ot_hrs'],
        'branch_name' => $row['branch_name']
    ];
}

$processed = 0;
$errors = 0;
$skipped = 0;

// Process each grouped attendance (one per employee per day)
foreach ($attendance_groups as $group) {
    $employee_id = $group['employee_id'];
    $attendance_date = $group['attendance_date'];
    $branch_id = $group['branch_id'];
    $branch_name = $group['branch_name'];

    // Validate attendance record exists with time_in and time_out
    $validate_sql = "SELECT id, time_in, time_out FROM attendance 
                     WHERE employee_id = ? AND attendance_date = ? 
                     AND time_in IS NOT NULL AND time_out IS NOT NULL";
    $validate_stmt = mysqli_prepare($db, $validate_sql);
    mysqli_stmt_bind_param($validate_stmt, 'is', $employee_id, $attendance_date);
    mysqli_stmt_execute($validate_stmt);
    $validate_result = mysqli_stmt_get_result($validate_stmt);
    
    if (mysqli_num_rows($validate_result) == 0) {
        echo "SKIP: No valid attendance record for Employee $employee_id on $attendance_date\n";
        $skipped++;
        mysqli_stmt_close($validate_stmt);
        continue;
    }
    mysqli_stmt_close($validate_stmt);
    
    // Check if record already exists in daily_payroll_reports
    $check_sql = "SELECT id FROM daily_payroll_reports 
                  WHERE employee_id = ? AND report_date = ? AND branch_id = ?";
    $check_stmt = mysqli_prepare($db, $check_sql);
    mysqli_stmt_bind_param($check_stmt, 'isi', $employee_id, $attendance_date, $branch_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        echo "SKIP: Record already exists for Employee $employee_id on $attendance_date\n";
        $skipped++;
        continue;
    }
    
    // Merge multiple attendance records for this employee on this date
    $records = $group['records'];
    
    // Filter out records without valid times and short records (less than 30 minutes)
    $valid_records = array_filter($records, function($r) {
        if (empty($r['time_in']) || empty($r['time_out'])) {
            return false;
        }
        $start_ts = strtotime($r['time_in']);
        $end_ts = strtotime($r['time_out']);
        if ($start_ts === false || $end_ts === false) {
            return false;
        }
        $hours = ($end_ts - $start_ts) / 3600;
        return $hours >= 0.5; // At least 30 minutes
    });
    
    if (empty($valid_records)) {
        echo "SKIP: No valid attendance records for Employee $employee_id on $attendance_date\n";
        $skipped++;
        continue;
    }
    
    // Sort by time_in
    usort($valid_records, function($a, $b) {
        return strtotime($a['time_in']) - strtotime($b['time_in']);
    });
    
    // Merge records within threshold
    $merged_records = [];
    $current_merge = null;
    $merge_count = 0;
    
    foreach ($valid_records as $record) {
        if ($current_merge === null) {
            $current_merge = $record;
        } else {
            $gap = strtotime($record['time_in']) - strtotime($current_merge['time_out']);
            if ($gap < ($MERGE_THRESHOLD_MINUTES * 60)) {
                // Merge - extend time_out and accumulate hours/OT
                $current_merge['time_out'] = $record['time_out'];
                $start_ts = strtotime($record['time_in']);
                $end_ts = strtotime($record['time_out']);
                if ($start_ts !== false && $end_ts !== false && $end_ts > $start_ts) {
                    $current_merge['hours'] = ($end_ts - strtotime($current_merge['time_in'])) / 3600;
                }
                $current_merge['total_ot_hrs'] += floatval($record['total_ot_hrs'] ?? 0);
                $merge_count++;
            } else {
                // Gap too large, save current and start new merge
                $merged_records[] = $current_merge;
                $current_merge = $record;
            }
        }
    }
    // Don't forget the last merge
    if ($current_merge !== null) {
        $merged_records[] = $current_merge;
    }
    
    // Calculate total worked hours and OT from merged records
    $worked_hours = 0;
    $ot_hours = 0;
    $first_time_in = null;
    $last_time_out = null;
    $total_ot_hrs = 0;
    
    foreach ($merged_records as $record) {
        $start_ts = strtotime($record['time_in']);
        $end_ts = strtotime($record['time_out']);
        if ($start_ts !== false && $end_ts !== false && $end_ts > $start_ts) {
            $worked_hours += ($end_ts - $start_ts) / 3600;
        }
        $total_ot_hrs += floatval($record['total_ot_hrs'] ?? 0);
        
        if ($first_time_in === null || $start_ts < strtotime($first_time_in)) {
            $first_time_in = $record['time_in'];
        }
        if ($last_time_out === null || $end_ts > strtotime($last_time_out)) {
            $last_time_out = $record['time_out'];
        }
    }
    
    $daily_rate = floatval($group['daily_rate'] ?? 0);
    $ot_hours = $total_ot_hrs;
    
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
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
        days_worked = VALUES(days_worked),
        total_hours = VALUES(total_hours),
        basic_pay = VALUES(basic_pay),
        ot_hours = VALUES(ot_hours),
        ot_amount = VALUES(ot_amount),
        gross_pay = VALUES(gross_pay),
        gross_plus_allowance = VALUES(gross_plus_allowance),
        sss_deduction = VALUES(sss_deduction),
        philhealth_deduction = VALUES(philhealth_deduction),
        pagibig_deduction = VALUES(pagibig_deduction),
        total_deductions = VALUES(total_deductions),
        take_home_pay = VALUES(take_home_pay),
        updated_at = NOW()";
    
    // Use the primary branch from the first record's branch_id
    // If multiple branches were involved, use the last one (most recent)
    
    $insert_stmt = mysqli_prepare($db, $insert_sql);
    mysqli_stmt_bind_param($insert_stmt, 'isiiiiiddddddddddddddddds', 
        $employee_id, $attendance_date, $report_year, $report_month, $report_day, $week_number,
        $branch_id, $days_worked, $worked_hours, $daily_rate, $basic_pay, $ot_hours, $ot_rate, $ot_amount,
        $performance_allowance, $gross_pay, $gross_plus_allowance, $ca_deduction,
        $sss_deduction, $philhealth_deduction, $pagibig_deduction, $sss_loan,
        $total_deductions, $take_home_pay, $status
    );
    
    $status = 'Pending';
    
    if (mysqli_stmt_execute($insert_stmt)) {
        echo "INSERTED: Employee $employee_id ($row[first_name] $row[last_name]) - $attendance_date - Gross: ₱" . number_format($gross_pay, 2) . "\n";
        $processed++;
    } else {
        echo "ERROR: Employee $employee_id on $attendance_date - " . mysqli_error($db) . "\n";
        $errors++;
    }
}

echo "\n=== Summary ===\n";
echo "Processed: $processed records\n";
echo "Skipped: $skipped records (already exist)\n";
echo "Errors: $errors records\n";
echo "Completed: " . date('Y-m-d H:i:s') . "\n";

// Also update the log file
$log_file = __DIR__ . '/daily_payroll_generator.log';
$log_message = "[" . date('Y-m-d H:i:s') . "] Generated $processed records, skipped $skipped, errors $errors for period $start_date to $end_date\n";
file_put_contents($log_file, $log_message, FILE_APPEND);

mysqli_close($db);
?>
