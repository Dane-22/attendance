<?php
// Enable error reporting for debugging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../update_allowance_errors.log');
error_log("[report.php] File loaded - starting execution");

// Include utility functions for payroll calculation
require_once __DIR__ . '/../../functions.php';

// Catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log("[report.php] FATAL ERROR: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line']);
    }
});

// Get current month and year
$current_month = date('Y-m');
$current_year = date('Y');
$current_month_num = date('m');

// Calculate current week based on today's date (1-7=week1, 8-14=week2, 15-21=week3, 22-28=week4, 29+=week5)
$current_day = intval(date('d'));
$current_week = ceil($current_day / 7);
if ($current_week > 5) $current_week = 5;

// Handle filters
$selected_month = $_GET['month'] ?? $current_month;
$selected_week = intval($_GET['week'] ?? $current_week);
$view_type = $_GET['view'] ?? 'range'; // 'weekly', 'monthly', or 'range'
$selected_branch = $_GET['branch'] ?? 'all'; // 'all' or specific branch

// Handle custom date range parameters
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;

error_log("[report.php] Received dates - start_date: $start_date, end_date: $end_date, view_type: $view_type");

// Validate week (1-5)
if ($selected_week < 1 || $selected_week > 5) {
    $selected_week = 1;
}

// Parse selected month
$month_year = explode('-', $selected_month);
$year = $month_year[0];
$month = $month_year[1];

// Calculate number of days in the month
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);

// Determine if Week 5 exists (if month has more than 28 days)
$has_week_5 = $days_in_month > 28;

// If Week 5 selected but not available, default to Week 4
if ($selected_week == 5 && !$has_week_5) {
    $selected_week = 4;
}

// Calculate date ranges based on view type
if ($view_type === 'weekly') {
    // Weekly view logic - Work days only (exclude Sundays)
    // Generate all work days in the month (exclude Sundays)
    $work_days = [];
    for ($day = 1; $day <= $days_in_month; $day++) {
        $date_str = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $day_of_week = date('w', strtotime($date_str)); // 0 = Sunday, 1 = Monday, etc.
        if ($day_of_week != 0) { // Exclude Sundays
            $work_days[] = $day;
        }
    }
    
    // Determine number of weeks based on work days
    // Week 1: From 1st work day to first Sunday (or up to 5 days)
    // Subsequent weeks: Monday to Friday (5 days)
    $total_work_days = count($work_days);
    
    // Calculate week boundaries
    $week_boundaries = [];
    $current_week = 1;
    $day_index = 0;
    
    while ($day_index < $total_work_days) {
        $week_start_day = $work_days[$day_index];
        
        // For Week 1, go until we hit a Sunday or max 5 days
        // For other weeks, take up to 5 days
        $days_in_this_week = 0;
        $week_end_index = $day_index;
        
        while ($week_end_index < $total_work_days && $days_in_this_week < 5) {
            // Check if next day would cross a Sunday
            if ($days_in_this_week > 0) {
                $current_date = sprintf('%04d-%02d-%02d', $year, $month, $work_days[$week_end_index]);
                $prev_date = sprintf('%04d-%02d-%02d', $year, $month, $work_days[$week_end_index - 1]);
                $current_dow = date('w', strtotime($current_date));
                $prev_dow = date('w', strtotime($prev_date));
                
                // If we went from Saturday (6) to Monday (1), that's a new week
                if ($prev_dow == 6 && $current_dow == 1) {
                    break;
                }
            }
            $days_in_this_week++;
            $week_end_index++;
        }
        
        $week_end_day = $work_days[$week_end_index - 1];
        $week_boundaries[$current_week] = ['start' => $week_start_day, 'end' => $week_end_day];
        
        $day_index = $week_end_index;
        $current_week++;
    }
    
    // Update has_week_5 based on actual calculated weeks
    $has_week_5 = count($week_boundaries) >= 5;
    
    // Get the selected week boundaries
    if (isset($week_boundaries[$selected_week])) {
        $week_start_day = $week_boundaries[$selected_week]['start'];
        $week_end_day = $week_boundaries[$selected_week]['end'];
    } else {
        // Fallback to last available week if selected week doesn't exist
        $last_week = count($week_boundaries);
        $week_start_day = $week_boundaries[$last_week]['start'] ?? 1;
        $week_end_day = $week_boundaries[$last_week]['end'] ?? min(5, $days_in_month);
        $selected_week = $last_week;
    }
    
    $start_date = sprintf('%04d-%02d-%02d', $year, $month, $week_start_day);
    $end_date = sprintf('%04d-%02d-%02d', $year, $month, $week_end_day);
    $date_range_label = "Week $selected_week: " . date('M d', strtotime($start_date)) . " - " . date('M d, Y', strtotime($end_date));
} elseif ($view_type === 'range' && $start_date && $end_date) {
    // Custom date range view logic
    // Validate and sanitize dates
    $start_ts = strtotime($start_date);
    $end_ts = strtotime($end_date);
    
    if ($start_ts === false || $end_ts === false || $end_ts < $start_ts) {
        // Invalid dates, fallback to current week
        $start_date = date('Y-m-d', strtotime('monday this week'));
        $end_date = date('Y-m-d', strtotime('sunday this week'));
    }
    
    $date_range_label = "Custom Range: " . date('M d, Y', strtotime($start_date)) . " - " . date('M d, Y', strtotime($end_date));
} else {
    // Monthly view logic - whole month
    $start_date = sprintf('%04d-%02d-01', $year, $month);
    $end_date = sprintf('%04d-%02d-%02d', $year, $month, $days_in_month);
    $date_range_label = "Monthly View: " . date('F Y', strtotime($start_date));
}

// Fetch all branches for dropdown using branches table
$branch_query = "SELECT id, branch_name FROM branches WHERE is_active = 1 ORDER BY branch_name";
$branch_result = mysqli_query($db, $branch_query);
$all_branches_list = [];
while ($branch_row = mysqli_fetch_assoc($branch_result)) {
    $all_branches_list[] = [
        'id' => $branch_row['id'],
        'name' => $branch_row['branch_name']
    ];
}

// Quick Branch Filter Pagination (Rate Limiter)
$branches_per_page = 10; // Show 10 branches per page
$branch_page = isset($_GET['branch_page']) ? intval($_GET['branch_page']) : 1;
$branch_page = max(1, $branch_page);
$total_branches = count($all_branches_list);
$total_branch_pages = ceil($total_branches / $branches_per_page);

// Slice the branches array for pagination
$branch_offset = ($branch_page - 1) * $branches_per_page;
$paginated_branches = array_slice($all_branches_list, $branch_offset, $branches_per_page);

// Fetch payroll data from daily_payroll_reports for the date range (primary source)
error_log("[report.php] About to fetch payroll data");
$payroll_query = "SELECT 
                    dpr.employee_id,
                    dpr.report_date,
                    dpr.days_worked,
                    dpr.total_hours,
                    dpr.daily_rate,
                    dpr.basic_pay,
                    dpr.ot_hours,
                    dpr.ot_rate,
                    dpr.ot_amount,
                    dpr.performance_allowance,
                    dpr.gross_pay,
                    dpr.gross_plus_allowance,
                    dpr.ca_deduction,
                    dpr.sss_deduction,
                    dpr.philhealth_deduction,
                    dpr.pagibig_deduction,
                    dpr.sss_loan,
                    dpr.total_deductions,
                    dpr.take_home_pay,
                    dpr.status,
                    dpr.branch_id,
                    IFNULL(dpr.is_manual_adjustment, 0) as is_manual_adjustment,
                    b.branch_name,
                    e.first_name,
                    e.last_name,
                    e.employee_code,
                    e.position
                 FROM daily_payroll_reports dpr
                 JOIN employees e ON dpr.employee_id = e.id
                 LEFT JOIN branches b ON dpr.branch_id = b.id
                 WHERE dpr.report_date BETWEEN ? AND ?
                 AND LOWER(e.position) IN ('worker', 'admin', 'engineer', 'developer')";

// Add branch filter if not 'all'
if ($selected_branch !== 'all' && is_numeric($selected_branch)) {
    $payroll_query .= " AND dpr.branch_id = ?";
}
$payroll_query .= " ORDER BY dpr.report_date, b.branch_name";

$stmt = mysqli_prepare($db, $payroll_query);
if ($selected_branch !== 'all' && is_numeric($selected_branch)) {
    mysqli_stmt_bind_param($stmt, 'ssi', $start_date, $end_date, $selected_branch);
} else {
    mysqli_stmt_bind_param($stmt, 'ss', $start_date, $end_date);
}

mysqli_stmt_execute($stmt);
$payroll_result = mysqli_stmt_get_result($stmt);
$payroll_row_count = mysqli_num_rows($payroll_result);
error_log("[report.php] Payroll query executed. Date range: $start_date to $end_date. Rows fetched: $payroll_row_count");

// Fetch attendance data as fallback/supplement (for dates not in daily_payroll_reports)
// Exclude voided records - they should not count toward days worked
$attendance_query = "SELECT a.employee_id, a.attendance_date, a.status, a.branch_name, a.time_in, a.time_out, a.total_ot_hrs,
                            e.first_name, e.last_name, e.employee_code, e.daily_rate, e.position
                     FROM attendance a
                     JOIN employees e ON a.employee_id = e.id
                     WHERE a.attendance_date BETWEEN ? AND ?
                     AND a.is_voided = 0
                     AND e.status = 'Active'
                     AND LOWER(e.position) IN ('worker', 'admin', 'engineer', 'developer')";

// Add branch filter if not 'all' - filter by branch_id
if ($selected_branch !== 'all' && is_numeric($selected_branch)) {
    $attendance_query .= " AND e.branch_id = ?";
}
$attendance_query .= " ORDER BY a.attendance_date, a.branch_name";

$stmt2 = mysqli_prepare($db, $attendance_query);
if ($selected_branch !== 'all' && is_numeric($selected_branch)) {
    mysqli_stmt_bind_param($stmt2, 'ssi', $start_date, $end_date, $selected_branch);
} else {
    mysqli_stmt_bind_param($stmt2, 'ss', $start_date, $end_date);
}

mysqli_stmt_execute($stmt2);
$attendance_result = mysqli_stmt_get_result($stmt2);
$attendance_row_count = mysqli_num_rows($attendance_result);
error_log("[report.php] Attendance query executed. Date range: $start_date to $end_date. Rows fetched: $attendance_row_count");

// Fetch approved overtime from overtime_requests table (authoritative source)
$approved_ot_query = "SELECT r.employee_id, r.request_date, r.requested_hours as ot_hours, r.requested_at as requested_at, r.approved_at as approved_at, r.overtime_reason as reason
                      FROM overtime_requests r
                      WHERE r.request_date BETWEEN ? AND ?
                      AND r.status IN ('approved', 'pre-approved')";

if ($selected_branch !== 'all' && is_numeric($selected_branch)) {
    $approved_ot_query .= " AND r.employee_id IN (SELECT id FROM employees WHERE branch_id = ? AND status = 'Active')";
}
$approved_ot_query .= " ORDER BY r.request_date, r.employee_id";

$ot_stmt = mysqli_prepare($db, $approved_ot_query);
if ($selected_branch !== 'all' && is_numeric($selected_branch)) {
    mysqli_stmt_bind_param($ot_stmt, 'ssi', $start_date, $end_date, $selected_branch);
} else {
    mysqli_stmt_bind_param($ot_stmt, 'ss', $start_date, $end_date);
}
mysqli_stmt_execute($ot_stmt);
$approved_ot_result = mysqli_stmt_get_result($ot_stmt);
$approved_ot_row_count = mysqli_num_rows($approved_ot_result);
error_log("[report.php] Approved overtime query executed. Date range: $start_date to $end_date. Approved OT rows: $approved_ot_row_count");

// Organize approved overtime by employee
$approved_overtime = [];
while ($ot_row = mysqli_fetch_assoc($approved_ot_result)) {
    $emp_id = $ot_row['employee_id'];
    if (!isset($approved_overtime[$emp_id])) {
        $approved_overtime[$emp_id] = [
            'total_hours' => 0,
            'entries' => []
        ];
    }
    $approved_overtime[$emp_id]['total_hours'] += floatval($ot_row['ot_hours']);
    $approved_overtime[$emp_id]['entries'][] = [
        'date' => $ot_row['request_date'],
        'hours' => floatval($ot_row['ot_hours']),
        'start_time' => $ot_row['ot_start_time'],
        'end_time' => $ot_row['ot_end_time'],
        'reason' => $ot_row['reason']
    ];
}
error_log("[report.php] Approved overtime organized. Employees with approved OT: " . count($approved_overtime));

// Government deduction constants (monthly)
$MONTHLY_PHILHEALTH = 250.00;
$MONTHLY_SSS = 450.00;
$MONTHLY_PAGIBIG = 200.00;

// Calculate deductions based on view type
if ($view_type === 'monthly') {
    // Monthly view: Use full monthly deduction amounts
    $sss_deduction = $MONTHLY_SSS;
    $philhealth_deduction = $MONTHLY_PHILHEALTH;
    $pagibig_deduction = $MONTHLY_PAGIBIG;
} else {
    // Weekly view: Custom prorated deduction amounts
    switch ($selected_week) {
        case 1:
            $sss_deduction = 250.00;
            $philhealth_deduction = 100.00;
            $pagibig_deduction = 50.00;
            break;
        case 2:
            $sss_deduction = 100.00;
            $philhealth_deduction = 100.00;
            $pagibig_deduction = 50.00;
            break;
        case 3:
            $sss_deduction = 100.00;
            $philhealth_deduction = 50.00;
            $pagibig_deduction = 100.00;
            break;
        case 4:
        case 5:
        default:
            $sss_deduction = 0.00;
            $philhealth_deduction = 0.00;
            $pagibig_deduction = 0.00;
            break;
    }
}
$total_deductions_amount = $sss_deduction + $philhealth_deduction + $pagibig_deduction;

// Organize data by employee for payroll calculation
$employee_payroll = [];

// Also fetch employees with no attendance (for complete payroll)
// Check if performance_allowance column exists
$column_check = mysqli_query($db, "SHOW COLUMNS FROM employees LIKE 'performance_allowance'");
$has_allowance_column = mysqli_num_rows($column_check) > 0;

// Check if sss_loan column exists in employees table
$loan_column_check = mysqli_query($db, "SHOW COLUMNS FROM employees LIKE 'sss_loan'");
$has_loan_column = mysqli_num_rows($loan_column_check) > 0;

// Check if has_deduction column exists
$deduction_column_check = mysqli_query($db, "SHOW COLUMNS FROM employees LIKE 'has_deduction'");
$has_deduction_column = mysqli_num_rows($deduction_column_check) > 0;

// Build query based on available columns
$select_columns = "e.id, e.employee_code, e.first_name, e.last_name, e.daily_rate, e.position, e.status, e.branch_id, b.branch_name";
if ($has_allowance_column) {
    $select_columns .= ", e.performance_allowance";
}
if ($has_loan_column) {
    $select_columns .= ", e.sss_loan";
}
if ($has_deduction_column) {
    $select_columns .= ", e.has_deduction";
}

$all_employees_query = "SELECT $select_columns
                        FROM employees e
                        LEFT JOIN branches b ON e.branch_id = b.id
                        WHERE e.status = 'Active'
                        AND LOWER(e.position) IN ('worker', 'admin', 'engineer', 'developer')";

// Add branch filter if not 'all'
$has_branch_filter = ($selected_branch !== 'all' && $selected_branch !== '' && is_numeric($selected_branch));
if ($has_branch_filter) {
    $all_employees_query .= " AND e.branch_id = ?";
}

$all_employees_query .= " ORDER BY e.last_name, e.first_name";

$emp_stmt = mysqli_prepare($db, $all_employees_query);
if ($has_branch_filter) {
    mysqli_stmt_bind_param($emp_stmt, 'i', $selected_branch);
}
mysqli_stmt_execute($emp_stmt);
$all_employees_result = mysqli_stmt_get_result($emp_stmt);

while ($emp = mysqli_fetch_assoc($all_employees_result)) {
    $emp_id = $emp['id'];
    $employee_payroll[$emp_id] = [
        'employee' => $emp,
        'days_worked' => 0,
        'total_hours' => 0,
        'total_ot_hrs' => 0,
        'daily_rate' => floatval($emp['daily_rate']),
        'gross_pay' => 0,
        'sss_deduction' => 0,
        'philhealth_deduction' => 0,
        'pagibig_deduction' => 0,
        'total_deductions' => 0,
        'net_pay' => 0,
        'performance_allowance' => floatval($emp['performance_allowance'] ?? 0),
        'sss_loan' => floatval($emp['sss_loan'] ?? 0),
        '_daily' => [],
        '_branches' => [],  // Track per-branch totals: [branch_name => ['days'=>x, 'hours'=>y, 'ot_hours'=>z]]
        '_has_payroll_record' => []  // Track dates covered by daily_payroll_reports
    ];
}

// Process daily_payroll_reports data first (primary source)
error_log("[report.php] About to process daily_payroll_reports data");
while ($row = mysqli_fetch_assoc($payroll_result)) {
    $emp_id = $row['employee_id'];
    
    if (isset($employee_payroll[$emp_id])) {
        $report_date = $row['report_date'];
        $branch_name = trim($row['branch_name'] ?? 'Unassigned');
        
        // Mark this date as having payroll data
        $employee_payroll[$emp_id]['_has_payroll_record'][$report_date] = true;
        
        // Accumulate totals from daily records
        $employee_payroll[$emp_id]['days_worked'] += floatval($row['days_worked'] ?? 0);
        $employee_payroll[$emp_id]['total_hours'] += floatval($row['total_hours'] ?? 0);
        // Note: total_ot_hrs will be set from approved_overtime array (authoritative source)
        $employee_payroll[$emp_id]['gross_pay'] += floatval($row['gross_pay'] ?? 0);
        
        // Accumulate performance allowance from daily records (take the max or latest)
        $employee_payroll[$emp_id]['performance_allowance'] = floatval($row['performance_allowance'] ?? 0);
        
        // Accumulate SSS loan from daily records (take the max/latest - same as allowance)
        $employee_payroll[$emp_id]['sss_loan'] = floatval($row['sss_loan'] ?? 0);
        
        // Track per-branch totals
        if (!isset($employee_payroll[$emp_id]['_branches'][$branch_name])) {
            $employee_payroll[$emp_id]['_branches'][$branch_name] = ['days' => 0, 'hours' => 0, 'ot_hours' => 0];
        }
        $employee_payroll[$emp_id]['_branches'][$branch_name]['days'] += floatval($row['days_worked'] ?? 0);
        $employee_payroll[$emp_id]['_branches'][$branch_name]['hours'] += floatval($row['total_hours'] ?? 0);
        $employee_payroll[$emp_id]['_branches'][$branch_name]['ot_hours'] += floatval($row['ot_hours'] ?? 0);
        
        // Track daily breakdown
        if (!isset($employee_payroll[$emp_id]['_daily'][$report_date])) {
            $employee_payroll[$emp_id]['_daily'][$report_date] = [];
        }
        $employee_payroll[$emp_id]['_daily'][$report_date][$branch_name] = [
            'status' => $row['status'] ?? 'Present',
            'hours' => floatval($row['total_hours'] ?? 0),
            'ot_hours' => floatval($row['ot_hours'] ?? 0)
        ];
        
        // Track if this employee has manual adjustments
        if (!empty($row['is_manual_adjustment'])) {
            $employee_payroll[$emp_id]['_has_manual_adjustment'] = true;
        }
    }
}

// Process attendance data as fallback (for dates not in daily_payroll_reports)
// Skip employees with manual adjustments entirely to prevent overwriting manual changes
error_log("[report.php] About to process attendance data");
while ($row = mysqli_fetch_assoc($attendance_result)) {
    $emp_id = $row['employee_id'];
    
    // Skip this employee entirely if they have manual adjustments
    if (isset($employee_payroll[$emp_id]['_has_manual_adjustment']) && $employee_payroll[$emp_id]['_has_manual_adjustment']) {
        continue;
    }
    
    if (isset($employee_payroll[$emp_id])) {
        $attendance_date = $row['attendance_date'] ?? null;
        $status = strtolower(trim($row['status'] ?? ''));
        $branch_name = trim($row['branch_name'] ?? '');

        if (!$attendance_date) {
            continue;
        }
        
        // Skip if this date is already covered by daily_payroll_reports
        if (isset($employee_payroll[$emp_id]['_has_payroll_record'][$attendance_date])) {
            continue;
        }

        if (!isset($employee_payroll[$emp_id]['_daily'][$attendance_date])) {
            $employee_payroll[$emp_id]['_daily'][$attendance_date] = [];
        }

        // Only include attendance in report totals AFTER employee has timed out
        $time_in = $row['time_in'] ?? null;
        $time_out = $row['time_out'] ?? null;
        if (empty($time_in) || empty($time_out)) {
            continue;
        }

        // Calculate worked hours from time_in/time_out
        $start_ts = strtotime($time_in);
        $end_ts = strtotime($time_out);
        $worked_hours = 0;
        if ($start_ts !== false && $end_ts !== false && $end_ts > $start_ts) {
            $worked_hours = ($end_ts - $start_ts) / 3600;
        }

        if (!isset($employee_payroll[$emp_id]['_daily'][$attendance_date][$branch_name])) {
            $employee_payroll[$emp_id]['_daily'][$attendance_date][$branch_name] = [
                'status' => $status,
                'hours' => 0,
                'ot_hours' => 0,
                'time_in' => $time_in,
                'time_out' => $time_out,
                'records' => []
            ];
        }

        $employee_payroll[$emp_id]['_daily'][$attendance_date][$branch_name]['status'] = $status;
        $employee_payroll[$emp_id]['_daily'][$attendance_date][$branch_name]['hours'] += $worked_hours;
        $employee_payroll[$emp_id]['_daily'][$attendance_date][$branch_name]['ot_hours'] += floatval($row['total_ot_hrs'] ?? 0);
        // Store individual record for merging logic
        $employee_payroll[$emp_id]['_daily'][$attendance_date][$branch_name]['records'][] = [
            'time_in' => $time_in,
            'time_out' => $time_out,
            'hours' => $worked_hours,
            'ot_hours' => floatval($row['total_ot_hrs'] ?? 0)
        ];
    }
}

// Merge threshold in minutes - records within this gap will be merged
$MERGE_THRESHOLD_MINUTES = 15;

// Finalize day/hour totals from per-day/per-branch attendance
foreach ($employee_payroll as $emp_id => &$payroll) {
    if (empty($payroll['_daily']) || !is_array($payroll['_daily'])) {
        continue;
    }

    foreach ($payroll['_daily'] as $attendance_date => $branches) {
        if (!is_array($branches) || empty($branches)) {
            continue;
        }

        // Skip if this date was already covered by daily_payroll_reports
        if (isset($payroll['_has_payroll_record'][$attendance_date])) {
            continue;
        }

        // Collect all records for this date across all branches for merging analysis
        $all_records = [];
        foreach ($branches as $bName => $bData) {
            if (isset($bData['records']) && is_array($bData['records'])) {
                foreach ($bData['records'] as $record) {
                    $all_records[] = array_merge($record, ['branch' => $bName]);
                }
            } else {
                // Fallback for legacy data without records array
                $all_records[] = [
                    'time_in' => $bData['time_in'] ?? null,
                    'time_out' => $bData['time_out'] ?? null,
                    'hours' => $bData['hours'] ?? 0,
                    'ot_hours' => $bData['ot_hours'] ?? 0,
                    'branch' => $bName
                ];
            }
        }

        // Filter out records without valid times and short records
        $all_records = array_filter($all_records, function($r) {
            // Must have both time_in and time_out
            if (empty($r['time_in']) || empty($r['time_out'])) {
                return false;
            }
            // Must be at least 30 minutes (0.5 hours)
            $start_ts = strtotime($r['time_in']);
            $end_ts = strtotime($r['time_out']);
            if ($start_ts === false || $end_ts === false) {
                return false;
            }
            $hours = ($end_ts - $start_ts) / 3600;
            return $hours >= 0.5;
        });

        if (empty($all_records)) {
            continue;
        }

        // Sort by time_in
        usort($all_records, function($a, $b) {
            return strtotime($a['time_in']) - strtotime($b['time_in']);
        });

        // Merge records within threshold
        $merged_records = [];
        $current_merge = null;
        $merge_count = 0;

        foreach ($all_records as $record) {
            if ($current_merge === null) {
                $current_merge = $record;
            } else {
                $gap = strtotime($record['time_in']) - strtotime($current_merge['time_out']);
                if ($gap < ($MERGE_THRESHOLD_MINUTES * 60)) {
                    // Merge - extend time_out and accumulate hours
                    $current_merge['time_out'] = $record['time_out'];
                    $current_merge['hours'] += $record['hours'];
                    $current_merge['ot_hours'] += $record['ot_hours'];
                    // Track that we merged across branches if different
                    if ($current_merge['branch'] !== $record['branch']) {
                        $current_merge['branch'] = $current_merge['branch'] . '/' . $record['branch'];
                    }
                    $merge_count++;
                } else {
                    // Save current merge and start new one
                    $merged_records[] = $current_merge;
                    $current_merge = $record;
                }
            }
        }
        if ($current_merge !== null) {
            $merged_records[] = $current_merge;
        }

        // Log if records were merged
        if ($merge_count > 0) {
            error_log("[report.php] Merged {$merge_count} attendance records for employee {$emp_id} on {$attendance_date}");
        }

        // If employee worked at exactly 2 branches on the same date (transfer scenario),
        // and we didn't merge them (different branches with large gap)
        $unique_branches = array_unique(array_column($all_records, 'branch'));
        $is_transfer_scenario = count($unique_branches) === 2 && count($merged_records) === 2;

        if ($is_transfer_scenario) {
            // Calculate hours per branch from original records (not merged)
            $branch_hours = [];
            foreach ($all_records as $record) {
                $bName = $record['branch'];
                if (!isset($branch_hours[$bName])) {
                    $branch_hours[$bName] = ['hours' => 0, 'ot_hours' => 0];
                }
                $branch_hours[$bName]['hours'] += $record['hours'];
                $branch_hours[$bName]['ot_hours'] += $record['ot_hours'];
            }

            foreach ($branch_hours as $bName => $bData) {
                if (!isset($payroll['_branches'][$bName])) {
                    $payroll['_branches'][$bName] = ['days' => 0, 'hours' => 0, 'ot_hours' => 0];
                }
                $payroll['_branches'][$bName]['days'] += 0.5;
                $payroll['_branches'][$bName]['hours'] += floatval($bData['hours'] ?? 0);
                $payroll['_branches'][$bName]['ot_hours'] += floatval($bData['ot_hours'] ?? 0);
            }
            $payroll['days_worked'] += 1.0;
            foreach ($all_records as $record) {
                $payroll['total_hours'] += floatval($record['hours'] ?? 0);
                $payroll['total_ot_hrs'] += floatval($record['ot_hours'] ?? 0);
            }
            continue;
        }

        // Default: calculate days based on actual hours worked (Option E - Hybrid)
        $day_hours = 0;
        foreach ($merged_records as $mRecord) {
            $day_hours += floatval($mRecord['hours'] ?? 0);
            $payroll['total_hours'] += floatval($mRecord['hours'] ?? 0);
            $payroll['total_ot_hrs'] += floatval($mRecord['ot_hours'] ?? 0);
        }
        // Calculate days and pay based on hours (full day if >= 8h, else hourly)
        $calc_result = calculateDaysAndPay($day_hours, $payroll['daily_rate']);
        $payroll['days_worked'] += $calc_result['days_worked'];

        // Assign to primary branch (the one with most hours on this day)
        $branch_hour_totals = [];
        foreach ($merged_records as $mRecord) {
            $branches_in_record = explode('/', $mRecord['branch']);
            foreach ($branches_in_record as $bName) {
                if (!isset($branch_hour_totals[$bName])) {
                    $branch_hour_totals[$bName] = ['hours' => 0, 'ot_hours' => 0];
                }
                $branch_hour_totals[$bName]['hours'] += floatval($mRecord['hours'] ?? 0) / count($branches_in_record);
                $branch_hour_totals[$bName]['ot_hours'] += floatval($mRecord['ot_hours'] ?? 0) / count($branches_in_record);
            }
        }

        // Find primary branch (most hours)
        $primary_branch = '';
        $max_hours = 0;
        foreach ($branch_hour_totals as $bName => $bData) {
            if ($bData['hours'] > $max_hours) {
                $max_hours = $bData['hours'];
                $primary_branch = $bName;
            }
        }

        // Assign proportional days to primary branch based on hours
        if ($primary_branch && isset($branch_hour_totals[$primary_branch])) {
            if (!isset($payroll['_branches'][$primary_branch])) {
                $payroll['_branches'][$primary_branch] = ['days' => 0, 'hours' => 0, 'ot_hours' => 0];
            }
            // Calculate days proportionally based on hours (same calculation as above)
            $branch_calc = calculateDaysAndPay($branch_hour_totals[$primary_branch]['hours'], $payroll['daily_rate']);
            $payroll['_branches'][$primary_branch]['days'] += $branch_calc['days_worked'];
            $payroll['_branches'][$primary_branch]['hours'] += $branch_hour_totals[$primary_branch]['hours'];
            $payroll['_branches'][$primary_branch]['ot_hours'] += $branch_hour_totals[$primary_branch]['ot_hours'];
        }
    }
}
unset($payroll);

// Apply approved overtime hours to each employee (authoritative source from overtime_requests)
foreach ($employee_payroll as $emp_id => &$payroll) {
    if (isset($approved_overtime[$emp_id])) {
        $payroll['total_ot_hrs'] = $approved_overtime[$emp_id]['total_hours'];
        error_log("[report.php] Applied approved OT for emp_id=$emp_id: " . $approved_overtime[$emp_id]['total_hours'] . " hours");
    } else {
        $payroll['total_ot_hrs'] = 0; // No approved overtime for this employee
    }
}
unset($payroll);

// Calculate payroll for each employee
error_log("[report.php] About to calculate payroll");
$payroll_totals = [
    'total_employees' => 0,
    'total_days' => 0,
    'total_hours' => 0,
    'total_gross' => 0,
    'total_deductions' => 0,
    'total_net' => 0
];

foreach ($employee_payroll as $emp_id => &$payroll) {
    $daily_rate = $payroll['daily_rate'];
    $days_worked = $payroll['days_worked'];
    
    // Calculate gross pay
    $gross_pay = $daily_rate * $days_worked;
    $payroll['gross_pay'] = $gross_pay;
    
    // Apply deductions only if employee has attendance records AND has_deduction flag is set
    if ($days_worked > 0 && !empty($payroll['employee']['has_deduction'])) {
        $payroll['sss_deduction'] = $sss_deduction;
        $payroll['philhealth_deduction'] = $philhealth_deduction;
        $payroll['pagibig_deduction'] = $pagibig_deduction;
        $payroll['total_deductions'] = $total_deductions_amount;
    }
    
    // Calculate net pay
    $net_pay = $gross_pay - $payroll['total_deductions'];
    $payroll['net_pay'] = max(0, $net_pay); // Ensure no negative net pay
    
    // Update totals
    if ($days_worked > 0) {
        $payroll_totals['total_employees']++;
    }
    $payroll_totals['total_days'] += $days_worked;
    $payroll_totals['total_hours'] += $payroll['total_hours'];
    $payroll_totals['total_gross'] += $gross_pay;
    $payroll_totals['total_deductions'] += $payroll['total_deductions'];
    $payroll_totals['total_net'] += $payroll['net_pay'];
}
unset($payroll); // Break reference

// Filter to show only Active employees (removed days_worked filter to show all)
$employee_payroll = array_filter($employee_payroll, function($p) {
    return $p['employee']['status'] === 'Active';
});

error_log("[report.php] Payroll calculated, about to load allowances");

// Save report data to database
function saveWeeklyReportData($db, $employee_payroll, $payroll_totals, $year, $month, $selected_week, $view_type, $selected_branch) {
    // Check if weekly_payroll_reports table exists
    $table_check = mysqli_query($db, "SHOW TABLES LIKE 'weekly_payroll_reports'");
    if (mysqli_num_rows($table_check) == 0) {
        // Table doesn't exist, skip saving
        return;
    }
    
    $created_by = $_SESSION['user_id'] ?? null;
    $filter_branch_id = ($selected_branch !== 'all' && is_numeric($selected_branch)) ? $selected_branch : null;
    
    foreach ($employee_payroll as $emp_id => $payroll) {
        $daily_rate = $payroll['daily_rate'];
        
        // If no per-branch data (no attendance), optionally save a single empty row
        if (empty($payroll['_branches'])) {
            // Skip saving empty records - don't clutter DB with zero rows
            continue;
        }
        
        // Save a row for EACH branch the employee worked at
        foreach ($payroll['_branches'] as $branch_name => $branch_data) {
            // Get branch_id from branch_name
            $branch_id = null;
            $branch_lookup = mysqli_query($db, "SELECT id FROM branches WHERE branch_name = '" . mysqli_real_escape_string($db, $branch_name) . "' LIMIT 1");
            if ($branch_lookup && $row = mysqli_fetch_assoc($branch_lookup)) {
                $branch_id = $row['id'];
            }
            
            // Skip if a specific branch filter is active and this isn't that branch
            if ($filter_branch_id !== null && $branch_id !== $filter_branch_id) {
                continue;
            }
            
            $days_worked = $branch_data['days'];
            $total_hours = floatval($branch_data['hours']);
            $ot_hours = floatval($branch_data['ot_hours']);
            $ot_rate = $daily_rate / 8;
            $ot_amount = $ot_hours * $ot_rate;
            $gross_pay = $daily_rate * $days_worked;
            $allowance = 0;
            $ca_deduction = 0;
            $sss_loan = 0;
            $gross_plus_allowance = $gross_pay + $allowance + $ot_amount;
            
            // Deductions apply per employee, not per branch - assign full deductions to primary branch only
            // or split proportionally. Here we assign to each branch row (may over-count if not careful)
            // Better: only apply deductions to the branch with most days, or split by days ratio
            $sss_deduction = 0;
            $philhealth_deduction = 0;
            $pagibig_deduction = 0;
            $total_deductions = 0;
            
            // Apply deductions only to the first/main branch to avoid double-counting in summaries
            // Check if this is the primary branch (most days worked)
            $max_days = 0;
            $primary_branch = null;
            foreach ($payroll['_branches'] as $bn => $bd) {
                if ($bd['days'] > $max_days) {
                    $max_days = $bd['days'];
                    $primary_branch = $bn;
                }
            }
            if ($branch_name === $primary_branch && $days_worked > 0) {
                $sss_deduction = $payroll['sss_deduction'];
                $philhealth_deduction = $payroll['philhealth_deduction'];
                $pagibig_deduction = $payroll['pagibig_deduction'];
            }
            
            // Calculate net pay
            $net_pay = $gross_pay - $payroll['total_deductions'];
            $payroll['net_pay'] = max(0, $net_pay); // Ensure no negative net pay
            
            // Update totals
            if ($days_worked > 0) {
                $payroll_totals['total_employees']++;
            }
            $payroll_totals['total_days'] += $days_worked;
            $payroll_totals['total_hours'] += $payroll['total_hours'];
            $payroll_totals['total_gross'] += $gross_pay;
            $payroll_totals['total_deductions'] += $payroll['total_deductions'];
            $payroll_totals['total_net'] += $payroll['net_pay'];
        }
    }
    unset($payroll); // Break reference
}

// NOTE: On-demand saving disabled - data is now only saved by cron jobs
// weekly_aggregate_non_branch33.php (Friday midnight)
// weekly_aggregate_branch33.php (Saturday midnight)
// Uncomment below to re-enable on-demand saving when viewing reports:
// if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($employee_payroll)) {
//     saveWeeklyReportData($db, $employee_payroll, $payroll_totals, $year, $month, $selected_week, $view_type, $selected_branch);
// }

// Load payment status and performance allowance from database for each employee
$week_num_for_db = ($view_type === 'monthly') ? 0 : $selected_week;
ini_set('error_log', __DIR__ . '/../update_allowance_errors.log');
error_log("[report.php] Loading allowances for year=$year, month=$month, week=$week_num_for_db, view=$view_type");
$payment_status_query = "SELECT employee_id, payment_status, performance_allowance, sss_loan FROM weekly_payroll_reports AS wpr1 
                         WHERE report_year = ? AND report_month = ? AND week_number = ? AND view_type = ?
                         AND id = (SELECT MAX(id) FROM weekly_payroll_reports AS wpr2 
                                   WHERE wpr2.employee_id = wpr1.employee_id 
                                   AND wpr2.report_year = wpr1.report_year 
                                   AND wpr2.report_month = wpr1.report_month 
                                   AND wpr2.week_number = wpr1.week_number 
                                   AND wpr2.view_type = wpr1.view_type)";
$payment_stmt = mysqli_prepare($db, $payment_status_query);
mysqli_stmt_bind_param($payment_stmt, 'iiis', $year, $month, $week_num_for_db, $view_type);
mysqli_stmt_execute($payment_stmt);
$payment_result = mysqli_stmt_get_result($payment_stmt);

$payment_statuses = [];
$weekly_allowances = [];
$weekly_loans = [];
while ($row = mysqli_fetch_assoc($payment_result)) {
    $payment_statuses[$row['employee_id']] = $row['payment_status'];
    $weekly_allowances[$row['employee_id']] = floatval($row['performance_allowance'] ?? 0);
    $weekly_loans[$row['employee_id']] = floatval($row['sss_loan'] ?? 0);
    error_log("[report.php] Found in DB: emp_id=" . $row['employee_id'] . ", allowance=" . $row['performance_allowance'] . ", loan=" . $row['sss_loan']);
}
mysqli_stmt_close($payment_stmt);

// If no records found and view_type is not 'weekly', also try loading 'weekly' records
// (since update_loan.php saves everything as 'weekly' for compatibility)
if (empty($weekly_loans) && $view_type !== 'weekly') {
    error_log("[report.php] No records found with view_type='$view_type', trying 'weekly'");
    $payment_stmt2 = mysqli_prepare($db, $payment_status_query);
    $weekly_view_type = 'weekly';
    mysqli_stmt_bind_param($payment_stmt2, 'iiis', $year, $month, $week_num_for_db, $weekly_view_type);
    mysqli_stmt_execute($payment_stmt2);
    $payment_result2 = mysqli_stmt_get_result($payment_stmt2);
    while ($row = mysqli_fetch_assoc($payment_result2)) {
        $payment_statuses[$row['employee_id']] = $row['payment_status'];
        $weekly_allowances[$row['employee_id']] = floatval($row['performance_allowance'] ?? 0);
        $weekly_loans[$row['employee_id']] = floatval($row['sss_loan'] ?? 0);
        error_log("[report.php] Found with 'weekly' fallback: emp_id=" . $row['employee_id'] . ", loan=" . $row['sss_loan']);
    }
    mysqli_stmt_close($payment_stmt2);
}

error_log("[report.php] Total employees with allowances: " . count($weekly_allowances));
error_log("[report.php] Employee IDs with allowances: " . implode(', ', array_keys($weekly_allowances)));

// Merge payment status and performance allowance into employee payroll data
error_log("[report.php] About to merge allowances into employee data. Employee count: " . count($employee_payroll));
error_log("[report.php] Weekly loans loaded: " . print_r($weekly_loans, true));
foreach ($employee_payroll as $emp_id => &$payroll) {
    $payroll['payment_status'] = $payment_statuses[$emp_id] ?? 'Not Paid';
    // Load employee's default performance allowance
    $default_allowance = floatval($payroll['employee']['performance_allowance'] ?? 0);
    $payroll['performance_allowance'] = $default_allowance;

    // Override with weekly-specific value if exists
    if (isset($weekly_allowances[$emp_id])) {
        $payroll['performance_allowance'] = $weekly_allowances[$emp_id];
    }
    
    // Override sss_loan with weekly-specific value if exists
    if (isset($weekly_loans[$emp_id])) {
        $payroll['sss_loan'] = $weekly_loans[$emp_id];
        error_log("[report.php] Merged loan for emp_id=$emp_id: " . $weekly_loans[$emp_id]);
    } else {
        error_log("[report.php] No weekly loan found for emp_id=$emp_id, using daily value: " . ($payroll['sss_loan'] ?? 0));
    }
}
unset($payroll);

// Generate date array for the selected range
$dates = [];
$all_dates = []; // Initialize to prevent undefined variable error
if ($view_type === 'weekly') {
    // For weekly view
    $current_date = strtotime($start_date);
    while ($current_date <= strtotime($end_date)) {
        $date_str = date('Y-m-d', $current_date);
        $dates[] = $date_str;
        $current_date = strtotime('+1 day', $current_date);
    }
} else {
    // For monthly view - all days of the month
    for ($day = 1; $day <= $days_in_month; $day++) {
        $date_str = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $dates[] = $date_str;
    }
}

// Add missing dates to all_dates
foreach ($dates as $date) {
    if (!in_array($date, $all_dates)) {
        $all_dates[] = $date;
    }
}
sort($all_dates);

// Calculate weekly breakdown for monthly view
$weekly_breakdown = [];
if ($view_type === 'monthly') {
    $week_num = 1;
    $current_week_dates = [];
    
    foreach ($dates as $date) {
        $day = date('d', strtotime($date));
        $current_week_dates[] = $date;
        
        // End of week or end of month
        if (count($current_week_dates) == 7 || $day == $days_in_month) {
            $weekly_breakdown[$week_num] = $current_week_dates;
            $week_num++;
            $current_week_dates = [];
        }
    }
}