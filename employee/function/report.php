
<?php
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
$view_type = $_GET['view'] ?? 'weekly'; // 'weekly' or 'monthly'
$selected_branch = $_GET['branch'] ?? 'all'; // 'all' or specific branch

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
    // Weekly view logic
    $week_start_day = 1 + (($selected_week - 1) * 7);
    $week_end_day = min($week_start_day + 6, $days_in_month);
    $start_date = sprintf('%04d-%02d-%02d', $year, $month, $week_start_day);
    $end_date = sprintf('%04d-%02d-%02d', $year, $month, $week_end_day);
    $date_range_label = "Week $selected_week: " . date('M d', strtotime($start_date)) . " - " . date('M d, Y', strtotime($end_date));
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

// Fetch attendance data for the date range - Get all employees and their attendance
$attendance_query = "SELECT a.employee_id, a.attendance_date, a.status, a.branch_name, a.time_in, a.time_out, a.total_ot_hrs,
                            e.first_name, e.last_name, e.employee_code, e.daily_rate, e.position
                     FROM attendance a
                     JOIN employees e ON a.employee_id = e.id
                     WHERE a.attendance_date BETWEEN ? AND ?
                     AND e.status = 'Active'
                     AND LOWER(e.position) = 'worker'";

// Add branch filter if not 'all' - filter by branch_id
if ($selected_branch !== 'all' && is_numeric($selected_branch)) {
    $attendance_query .= " AND e.branch_id = ?";
}
$attendance_query .= " ORDER BY a.attendance_date, a.branch_name";

$stmt = mysqli_prepare($db, $attendance_query);
if ($selected_branch !== 'all' && is_numeric($selected_branch)) {
    mysqli_stmt_bind_param($stmt, 'ssi', $start_date, $end_date, $selected_branch);
} else {
    mysqli_stmt_bind_param($stmt, 'ss', $start_date, $end_date);
}

mysqli_stmt_execute($stmt);
$attendance_result = mysqli_stmt_get_result($stmt);

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
    // Weekly view: Divide monthly deductions by 3 (for weeks 1-3), zero for week 4
    if ($selected_week === 4) {
        $sss_deduction = 0.00;
        $philhealth_deduction = 0.00;
        $pagibig_deduction = 0.00;
    } else {
        $sss_deduction = $MONTHLY_SSS / 3;
        $philhealth_deduction = $MONTHLY_PHILHEALTH / 3;
        $pagibig_deduction = $MONTHLY_PAGIBIG / 3;
    }
}
$total_deductions_amount = $sss_deduction + $philhealth_deduction + $pagibig_deduction;

// Organize data by employee for payroll calculation
$employee_payroll = [];

// Also fetch employees with no attendance (for complete payroll)
$all_employees_query = "SELECT e.id, e.employee_code, e.first_name, e.last_name, e.daily_rate, e.position, e.status, e.branch_id, b.branch_name
                        FROM employees e
                        LEFT JOIN branches b ON e.branch_id = b.id
                        WHERE e.status = 'Active'
                        AND LOWER(e.position) = 'worker'";

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
        'net_pay' => 0
    ];
}

while ($row = mysqli_fetch_assoc($attendance_result)) {
    $emp_id = $row['employee_id'];
    
    if (isset($employee_payroll[$emp_id])) {
        $attendance_date = $row['attendance_date'] ?? null;
        if (!isset($employee_payroll[$emp_id]['_days_seen'])) {
            $employee_payroll[$emp_id]['_days_seen'] = [];
        }

        // Count unique attendance_date as 1 day worked
        if ($attendance_date && !isset($employee_payroll[$emp_id]['_days_seen'][$attendance_date])) {
            $employee_payroll[$emp_id]['days_worked']++;
            $employee_payroll[$emp_id]['_days_seen'][$attendance_date] = true;
        }

        // Sum realtime worked hours using time_in/time_out (if no time_out yet, use current time)
        $time_in = $row['time_in'] ?? null;
        if (!empty($time_in)) {
            $time_out = $row['time_out'] ?? null;
            $start_ts = strtotime($time_in);
            $end_ts = !empty($time_out) ? strtotime($time_out) : time();
            if ($start_ts !== false && $end_ts !== false && $end_ts > $start_ts) {
                $employee_payroll[$emp_id]['total_hours'] += ($end_ts - $start_ts) / 3600;
            }
        }

        // Sum up overtime hours
        $ot_hours = floatval($row['total_ot_hrs'] ?? 0);
        $employee_payroll[$emp_id]['total_ot_hrs'] += $ot_hours;
    }
}

// Calculate payroll for each employee
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
    
    // Apply deductions only if employee has attendance records
    if ($days_worked > 0) {
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

// Save report data to database
function saveWeeklyReportData($db, $employee_payroll, $payroll_totals, $year, $month, $selected_week, $view_type, $selected_branch) {
    // Check if weekly_payroll_reports table exists
    $table_check = mysqli_query($db, "SHOW TABLES LIKE 'weekly_payroll_reports'");
    if (mysqli_num_rows($table_check) == 0) {
        // Table doesn't exist, skip saving
        return;
    }
    
    $created_by = $_SESSION['user_id'] ?? null;
    $branch_id = ($selected_branch !== 'all' && is_numeric($selected_branch)) ? $selected_branch : null;
    
    foreach ($employee_payroll as $emp_id => $payroll) {
        $ot_hours = $payroll['total_ot_hrs'];
        $ot_rate = $payroll['daily_rate'] / 8;
        $ot_amount = $ot_hours * $ot_rate;
        $allowance = 0;
        $ca_deduction = 0;
        $sss_loan = 0;
        $gross_plus_allowance = $payroll['gross_pay'] + $allowance + $ot_amount;
        $total_deductions = $payroll['sss_deduction'] + $payroll['philhealth_deduction'] + $payroll['pagibig_deduction'] + $ca_deduction + $sss_loan;
        $take_home = $gross_plus_allowance - $total_deductions;
        
        $query = "INSERT INTO weekly_payroll_reports (
            employee_id, report_year, report_month, week_number, view_type, branch_id,
            days_worked, total_hours, daily_rate, basic_pay,
            ot_hours, ot_rate, ot_amount,
            performance_allowance, gross_pay, gross_plus_allowance,
            ca_deduction, sss_deduction, philhealth_deduction, pagibig_deduction, sss_loan, total_deductions,
            take_home_pay, status, created_by
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        ) ON DUPLICATE KEY UPDATE
            days_worked = VALUES(days_worked),
            total_hours = VALUES(total_hours),
            daily_rate = VALUES(daily_rate),
            basic_pay = VALUES(basic_pay),
            ot_hours = VALUES(ot_hours),
            ot_rate = VALUES(ot_rate),
            ot_amount = VALUES(ot_amount),
            performance_allowance = VALUES(performance_allowance),
            gross_pay = VALUES(gross_pay),
            gross_plus_allowance = VALUES(gross_plus_allowance),
            ca_deduction = VALUES(ca_deduction),
            sss_deduction = VALUES(sss_deduction),
            philhealth_deduction = VALUES(philhealth_deduction),
            pagibig_deduction = VALUES(pagibig_deduction),
            sss_loan = VALUES(sss_loan),
            total_deductions = VALUES(total_deductions),
            take_home_pay = VALUES(take_home_pay),
            updated_at = CURRENT_TIMESTAMP";
        
        $week_num = ($view_type === 'monthly') ? 0 : $selected_week;
        $view_str = $view_type;
        $total_hours = $payroll['total_hours'];
        $status = 'Draft';
        
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, 'iiisssiddddddddddddddddss',
            $emp_id, $year, $month, $week_num, $view_str, $branch_id,
            $payroll['days_worked'], $total_hours, $payroll['daily_rate'], $payroll['gross_pay'],
            $ot_hours, $ot_rate, $ot_amount,
            $allowance, $payroll['gross_pay'], $gross_plus_allowance,
            $ca_deduction, $payroll['sss_deduction'], $payroll['philhealth_deduction'], $payroll['pagibig_deduction'], $sss_loan, $total_deductions,
            $take_home, $status, $created_by
        );
        
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Save the report data (only if report is being viewed, not on every page load)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($employee_payroll)) {
    saveWeeklyReportData($db, $employee_payroll, $payroll_totals, $year, $month, $selected_week, $view_type, $selected_branch);
}

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
?>