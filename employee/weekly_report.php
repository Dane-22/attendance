<?php
// admin/weekly_report.php - Weekly Deployment & Attendance Report
require_once __DIR__ . '/../conn/db_connection.php';
require_once __DIR__ . '/../functions.php';
session_start();

// Check if user is logged in and is admin/super admin
if (empty($_SESSION['logged_in']) || !in_array($_SESSION['position'], ['Admin', 'Super Admin'])) {
    header('Location: ../login.php');
    exit;
}

// Get current month and year
$current_month = date('Y-m');
$current_year = date('Y');
$current_month_num = date('m');

// Handle filters
$selected_month = $_GET['month'] ?? $current_month;
$selected_week = intval($_GET['week'] ?? 1);
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
        'sss_deduction' => $sss_deduction,
        'philhealth_deduction' => $philhealth_deduction,
        'pagibig_deduction' => $pagibig_deduction,
        'total_deductions' => $total_deductions_amount,
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deployment Report - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
     <link rel="icon" type="image/x-icon" href="../assets/img/profile/jajr-logo.png">
    <style>
        :root {
            --gold: #FFD700;
            --black: #000000;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #000000;
            color: #ffffff;
            min-height: 100vh;
            margin: 0;
        }

        .app-shell {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #0a0a0a;
        }

        /* Header */
        .header-card {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .menu-toggle {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            padding: 4px;
            color: #FFD700;
        }

        .welcome {
            font-size: 24px;
            font-weight: 700;
            color: #FFD700;
            margin-bottom: 4px;
        }

        .text-sm {
            font-size: 14px;
        }

        .text-gray {
            color: #888;
        }

        /* Report Card */
        .report-card {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
        }

        .report-header {
            background: #FFD700;
            border-radius: 12px 12px 0 0;
            padding: 20px;
            margin: -24px -24px 20px -24px;
        }

        .report-table {
            background: #0a0a0a;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #333;
        }

        .date-header {
            background: rgba(255, 215, 0, 0.15);
            font-weight: 600;
            color: #ffffff;
        }

        .branch-header {
            background: #1a1a1a;
            border-right: 1px solid #333;
            color: #FFD700;
        }

        .employee-box {
            background: rgba(255, 215, 0, 0.1);
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 6px;
            padding: 8px;
            margin: 4px 0;
            transition: all 0.2s ease;
        }

        .employee-box:hover {
            background: rgba(255, 215, 0, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 215, 0, 0.2);
        }

        .input-field {
            background: #2a2a2a;
            border: 1px solid #444;
            color: #ffffff;
            padding: 10px 12px;
            border-radius: 8px;
            width: 100%;
        }

        .input-field:focus {
            outline: none;
            border-color: #FFD700;
        }

        .btn-primary {
            background: #FFD700;
            border: none;
            color: #000000;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
        }

        .btn-secondary {
            background: #2a2a2a;
            border: 1px solid #444;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .btn-secondary:hover {
            background: #3a3a3a;
            border-color: #FFD700;
        }

        .btn-print {
            background: #1a1a1a;
            border: 2px solid var(--gold);
            color: var(--gold);
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-print:hover {
            background: var(--gold);
            color: var(--black);
            transform: translateY(-2px);
        }

        .empty-cell {
            min-height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-style: italic;
            background: #1a1a1a;
            border-radius: 6px;
            margin: 4px 0;
        }

        /* View Toggle */
        .view-toggle {
            display: flex;
            background: #2a2a2a;
            border-radius: 8px;
            padding: 4px;
            margin-bottom: 20px;
        }

        .view-option {
            flex: 1;
            padding: 10px 16px;
            text-align: center;
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .view-option.active {
            background: #FFD700;
            color: #000000;
        }

        .view-option:not(.active):hover {
            background: #3a3a3a;
        }

        /* Weekly Breakdown Section */
        .weekly-breakdown {
            margin-top: 20px;
            padding: 20px;
            background: rgba(255, 215, 0, 0.05);
            border-radius: 8px;
            border: 1px solid rgba(255, 215, 0, 0.2);
        }

        .week-card {
            background: rgba(255, 215, 0, 0.1);
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .week-card:hover {
            background: rgba(255, 215, 0, 0.15);
            transform: translateY(-2px);
        }

        /* Summary Cards */
        .summary-card {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }

        .summary-value {
            font-size: 32px;
            font-weight: 700;
            color: #FFD700;
            margin-bottom: 8px;
        }

        .summary-label {
            font-size: 14px;
            color: #888;
        }

        /* Branch Filter Badge */
        .branch-badge {
            display: inline-flex;
            align-items: center;
            background: rgba(255, 215, 0, 0.15);
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 20px;
            padding: 6px 12px;
            margin: 4px;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .branch-badge.all {
            background: rgba(0, 123, 255, 0.15);
            border-color: rgba(0, 123, 255, 0.3);
        }

        .branch-badge:hover {
            background: rgba(255, 215, 0, 0.25);
            border-color: rgba(255, 215, 0, 0.5);
        }

        .branch-badge.all:hover {
            background: rgba(0, 123, 255, 0.25);
            border-color: rgba(0, 123, 255, 0.5);
        }

        .branch-badge.active {
            background: #FFD700;
            border-color: #FFD700;
            color: #000000;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(255, 215, 0, 0.4);
        }

        .branch-badge.all.active {
            background: #FFD700;
            border-color: #FFD700;
            color: #000000;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(255, 215, 0, 0.4);
        }

        /* Responsive Styles */
        @media (max-width: 1024px) {
            .employee-box {
                padding: 6px;
                font-size: 0.9rem;
            }
            
            .report-table {
                font-size: 0.9rem;
            }
        }

        @media (max-width: 768px) {
            .app-shell {
                flex-direction: column;
            }
            
            .main-content {
                padding: 16px;
            }

            .header-card {
                flex-direction: column;
                gap: 16px;
                align-items: flex-start;
            }

            .filters {
                flex-direction: column;
                gap: 10px;
            }

            .btn-primary, .btn-secondary, .btn-print {
                width: 100%;
                text-align: center;
            }

            .view-toggle {
                flex-direction: column;
                gap: 4px;
            }

            .view-option {
                width: 100%;
            }

            .report-table {
                font-size: 0.85rem;
            }

            .employee-box {
                padding: 4px;
                font-size: 0.8rem;
            }
        }

        @media (max-width: 640px) {
            .main-content {
                padding: 12px;
            }

            .report-card {
                padding: 16px;
            }

            .summary-value {
                font-size: 24px;
            }
        }

        @media print {
            @page {
                size: landscape;
                margin: 5mm 3mm;
            }
            
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            body {
                background: white !important;
                color: black !important;
                font-size: 7pt !important;
                line-height: 1 !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            /* Hide web elements */
            .sidebar, .menu-toggle, .view-toggle, form.filters, 
            .btn-print, .btn-primary, .btn-secondary, .weekly-breakdown,
            .mt-8, .summary-card, .branch-badge, .report-header,
            .header-card, h4, .mb-6 {
                display: none !important;
            }
            
            /* Show main content */
            .main-content {
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
            }
            
            .report-card {
                background: white !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
                box-shadow: none !important;
            }
            
            /* Table container */
            .report-table {
                width: 100% !important;
                overflow: visible !important;
            }
            
            /* The table itself */
            table {
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: 6.5pt !important;
            }
            
            /* All cells */
            th, td {
                border: 0.5pt solid black !important;
                padding: 1px 2px !important;
                text-align: center !important;
                vertical-align: middle !important;
                font-size: 6.5pt !important;
                color: black !important;
                background: white !important;
                height: 14px !important;
                white-space: nowrap !important;
            }
            
            /* First column - employee names */
            th:first-child, td:first-child {
                text-align: left !important;
                padding-left: 3px !important;
                font-weight: normal !important;
            }
            
            /* Right align numbers */
            td:nth-child(n+4) {
                text-align: right !important;
                padding-right: 3px !important;
            }
            
            /* Header row */
            thead tr {
                background: #d0d0d0 !important;
            }
            
            th {
                background: #d0d0d0 !important;
                font-weight: bold !important;
                text-transform: uppercase !important;
            }
            
            /* Total row */
            tbody tr:last-child td {
                font-weight: bold !important;
                background: #e8e8e8 !important;
                border-top: 1.5pt solid black !important;
            }
            
            /* Inputs - show as text */
            input.ca-input {
                background: transparent !important;
                border: none !important;
                padding: 0 !important;
                font-size: 6.5pt !important;
                color: black !important;
                width: auto !important;
                text-align: right !important;
            }
            
            /* Remove all colors */
            .bg-gradient-to-r, .bg-gray-800, .bg-red-900\/20, .bg-red-900\/30,
            .from-yellow-600, .to-yellow-800, .from-yellow-700, .to-yellow-900,
            .hover\\:bg-gray-800\\/50:hover {
                background: white !important;
            }
            
            /* Text colors */
            .text-white, .text-gray-400, .text-gray-300, .text-yellow-400,
            .text-blue-400, .text-red-400, .text-green-400, .text-gold-300 {
                color: black !important;
            }
            
            /* Remove overflow restriction */
            .min-w-\\[1200px\\], .min-w-[1200px] {
                min-width: 0 !important;
            }
            
            .overflow-x-auto {
                overflow: visible !important;
            }
            
            /* Fix layout */
            .app-shell, main, .report-card {
                display: block !important;
            }
            
            /* Row styling */
            tr {
                page-break-inside: avoid !important;
            }
            
            /* Hide padding classes */
            .px-3, .px-2, .py-3, .py-2, .py-1, .p-3, .p-2 {
                padding: 1px 2px !important;
            }
        }
    </style>
</head>
<body>
    <div class="app-shell">
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <main class="main-content">
            <!-- Header -->
            <div class="header-card">
                <div class="header-left">
                    <div>
                        <div class="welcome">
                            <?php echo ($view_type === 'weekly') ? 'Weekly' : 'Monthly'; ?> Payroll Report
                        </div>
                        <div class="text-sm text-gray">
                            Admin Panel | <?php echo ($view_type === 'weekly') ? "Week $selected_week Report" : "Monthly Report"; ?>
                            <?php if ($selected_branch !== 'all'): ?>
                            | Branch: <?php echo htmlspecialchars($selected_branch); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="text-sm text-gray">
                    Today: <?php echo date('F d, Y'); ?>
                </div>
            </div>

            <!-- View Type Toggle -->
            <div class="view-toggle">
                <div class="view-option <?php echo ($view_type === 'weekly') ? 'active' : ''; ?>" 
                     onclick="changeView('weekly')">
                    <i class="fas fa-calendar-week mr-2"></i> Weekly View
                </div>
                <div class="view-option <?php echo ($view_type === 'monthly') ? 'active' : ''; ?>" 
                     onclick="changeView('monthly')">
                    <i class="fas fa-calendar-alt mr-2"></i> Monthly View
                </div>
            </div>

            <!-- Main Report Card -->
            <div class="report-card">
                <div class="report-header">
                    <h2 class="text-xl font-bold text-black">
                        <?php echo $date_range_label; ?>
                        <?php if ($selected_branch !== 'all'): ?>
                        <?php endif; ?>
                    </h2>
                </div>

                <!-- Filters -->
                <form method="GET" class="mb-6 flex flex-wrap gap-4 items-end filters" id="filterForm">
                    <input type="hidden" name="view" id="viewInput" value="<?php echo $view_type; ?>">
                    
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Select Month</label>
                        <select name="month" class="input-field" onchange="document.getElementById('filterForm').submit();">
                            <?php
                            for ($i = 0; $i < 12; $i++) {
                                $month_option = date('Y-m', strtotime("-$i months", strtotime($current_month . '-01')));
                                $selected = ($month_option == $selected_month) ? 'selected' : '';
                                echo "<option value=\"$month_option\" $selected>" . date('F Y', strtotime($month_option . '-01')) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <?php if ($view_type === 'weekly'): ?>
                    <div class="flex-1 min-w-[150px]">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Select Week</label>
                        <select name="week" class="input-field" onchange="document.getElementById('filterForm').submit();">
                            <?php for ($w = 1; $w <= ($has_week_5 ? 5 : 4); $w++): ?>
                                <option value="<?php echo $w; ?>" <?php echo ($w == $selected_week) ? 'selected' : ''; ?>>Week <?php echo $w; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="flex-1 min-w-[220px]">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Search</label>
                        <input type="text" id="employeeSearch" class="input-field" placeholder="Search employee...">
                    </div>
                    
                    <button type="button" onclick="exportToExcel()" class="btn-secondary">
                        <i class="fas fa-file-excel mr-2"></i>Export Excel
                    </button>
                </form>

                <!-- Quick Branch Filter Links -->
                <div class="mb-6">
                    <h4 class="text-sm font-medium text-gray-300 mb-2">Quick Branch Filter:</h4>
                    <div class="flex flex-wrap gap-2">
                        <a href="?view=<?php echo $view_type; ?>&month=<?php echo $selected_month; ?>&week=<?php echo $selected_week; ?>&branch=all" 
                           class="branch-badge all <?php echo ($selected_branch === 'all') ? 'active' : ''; ?>">
                            <i class="fas fa-layer-group mr-1"></i>All Branches
                        </a>
                        <?php foreach ($all_branches_list as $branch): ?>
                            <a href="?view=<?php echo $view_type; ?>&month=<?php echo $selected_month; ?>&week=<?php echo $selected_week; ?>&branch=<?php echo urlencode($branch['id']); ?>" 
                               class="branch-badge <?php echo ($selected_branch === (string)$branch['id']) ? 'active' : ''; ?>">
                                <i class="fas fa-building mr-1"></i><?php echo htmlspecialchars($branch['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Payroll Table -->
                <div class="report-table overflow-x-auto mb-6">
                    <table class="w-full border-collapse min-w-[1200px]" id="reportTable">
                        <thead>
                            <tr class="bg-gradient-to-r from-yellow-600 to-yellow-800">
                                <th class="px-3 py-3 text-left text-xs font-medium text-white uppercase tracking-wider border-b border-gray-600" rowspan="2">
                                    Employee
                                </th>
                                <th class="px-2 py-3 text-center text-xs font-medium text-white uppercase tracking-wider border-b border-gray-600" colspan="2">
                                    Days Worked
                                </th>
                                <th class="px-2 py-3 text-right text-xs font-medium text-white uppercase tracking-wider border-b border-gray-600" rowspan="2">
                                    Daily Rate
                                </th>
                                <th class="px-2 py-3 text-right text-xs font-medium text-white uppercase tracking-wider border-b border-gray-600" rowspan="2">
                                    Basic Pay
                                </th>
                                <th class="px-2 py-3 text-center text-xs font-medium text-white uppercase tracking-wider border-b border-gray-600" colspan="2">
                                    Overtime
                                </th>
                                <th class="px-2 py-3 text-right text-xs font-medium text-white uppercase tracking-wider border-b border-gray-600" rowspan="2">
                                    Gross Pay
                                </th>
                                <th class="px-2 py-3 text-right text-xs font-medium text-white uppercase tracking-wider border-b border-gray-600" rowspan="2">
                                    Performance Allowance
                                </th>
                                <th class="px-2 py-3 text-right text-xs font-medium text-white uppercase tracking-wider border-b border-gray-600" rowspan="2">
                                    Gross + Allowance
                                </th>
                                <th class="px-2 py-3 text-center text-xs font-medium text-white uppercase tracking-wider border-b border-gray-600" colspan="6">
                                    Deductions
                                </th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-white uppercase tracking-wider border-b border-gray-600" rowspan="2">
                                    Take Home Pay
                                </th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-white uppercase tracking-wider border-b border-gray-600" rowspan="2">
                                    Signature
                                </th>
                            </tr>
                            <tr class="bg-gradient-to-r from-yellow-700 to-yellow-900">
                                <th class="px-2 py-2 text-center text-xs font-medium text-white uppercase border-b border-gray-600">Days</th>
                                <th class="px-2 py-2 text-center text-xs font-medium text-white uppercase border-b border-gray-600">Hrs</th>
                                <th class="px-2 py-2 text-center text-xs font-medium text-white uppercase border-b border-gray-600">Hrs</th>
                                <th class="px-2 py-2 text-center text-xs font-medium text-white uppercase border-b border-gray-600">Amt</th>
                                <th class="px-2 py-2 text-right text-xs font-medium text-red-300 uppercase border-b border-gray-600 bg-red-900/20">CA</th>
                                <th class="px-2 py-2 text-right text-xs font-medium text-red-300 uppercase border-b border-gray-600 bg-red-900/20">SSS</th>
                                <th class="px-2 py-2 text-right text-xs font-medium text-red-300 uppercase border-b border-gray-600 bg-red-900/20">PHIC</th>
                                <th class="px-2 py-2 text-right text-xs font-medium text-red-300 uppercase border-b border-gray-600 bg-red-900/20">HDMF</th>
                                <th class="px-2 py-2 text-right text-xs font-medium text-red-300 uppercase border-b border-gray-600 bg-red-900/20">SSS Loan</th>
                                <th class="px-2 py-2 text-right text-xs font-medium text-red-300 uppercase border-b border-gray-600 bg-red-900/20">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employee_payroll as $emp_id => $payroll): ?>
                            <?php
                                $ot_hours = $payroll['total_ot_hrs'];
                                $ot_rate = $payroll['daily_rate'] / 8;
                                $ot_amount = $ot_hours * $ot_rate;
                                $allowance = 0; // Placeholder for performance allowance - will be filled by user input
                                $gross_plus_allowance = $payroll['gross_pay'] + $allowance;
                                $ca_deduction = 0; // Placeholder for cash advance
                                $sss_loan = 0; // Placeholder for SSS loan
                                $total_deductions = $payroll['sss_deduction'] + $payroll['philhealth_deduction'] + $payroll['pagibig_deduction'] + $ca_deduction + $sss_loan;
                                $take_home = $gross_plus_allowance - $total_deductions;
                            ?>
                            <tr class="border-b border-gray-700 hover:bg-gray-800/50">
                                <td class="px-3 py-2">
                                    <div class="font-medium text-white text-sm">
                                        <?php echo htmlspecialchars(strtoupper($payroll['employee']['last_name'] . ', ' . $payroll['employee']['first_name'])); ?>
                                    </div>
                                </td>
                                <td class="px-2 py-2 text-center text-sm text-white">
                                    <?php echo $payroll['days_worked']; ?>
                                </td>
                                <td class="px-2 py-2 text-center text-sm text-gray-400">
                                    <?php echo number_format($payroll['total_hours'], 0); ?>
                                </td>
                                <td class="px-2 py-2 text-right text-sm text-gray-300">
                                    <?php echo number_format($payroll['daily_rate'], 0); ?>
                                </td>
                                <td class="px-2 py-2 text-right text-sm font-medium text-white">
                                    <?php echo number_format($payroll['gross_pay'], 0); ?>
                                </td>
                                <td class="px-2 py-2 text-center text-sm text-gray-400">
                                    <?php echo $ot_hours; ?>
                                </td>
                                <td class="px-2 py-2 text-right text-sm text-gray-400">
                                    <?php echo number_format($ot_amount, 0); ?>
                                </td>
                                <td class="px-2 py-2 text-right text-sm font-medium text-yellow-400">
                                    <?php echo number_format($payroll['gross_pay'] + $ot_amount, 0); ?>
                                </td>
                                <td class="px-2 py-2 text-right text-sm">
                                    <input type="number" 
                                           name="allowance_<?php echo $emp_id; ?>" 
                                           id="allowance_<?php echo $emp_id; ?>"
                                           value="0" 
                                           min="0"
                                           step="0.01"
                                           class="w-20 bg-gray-800 border border-gray-600 rounded px-2 py-1 text-right text-blue-400 focus:border-yellow-500 focus:outline-none allowance-input"
                                           data-emp-id="<?php echo $emp_id; ?>"
                                           onchange="updateCalculations(<?php echo $emp_id; ?>)">
                                </td>
                                <td class="px-2 py-2 text-right text-sm font-medium text-white">
                                    <?php echo number_format($gross_plus_allowance + $ot_amount, 0); ?>
                                </td>
                                <td class="px-2 py-2 text-right text-sm">
                                    <input type="number" 
                                           name="ca_<?php echo $emp_id; ?>" 
                                           id="ca_<?php echo $emp_id; ?>"
                                           value="0" 
                                           min="0"
                                           step="0.01"
                                           class="w-20 bg-gray-800 border border-gray-600 rounded px-2 py-1 text-right text-red-400 focus:border-yellow-500 focus:outline-none ca-input"
                                           data-emp-id="<?php echo $emp_id; ?>"
                                           onchange="updateCalculations(<?php echo $emp_id; ?>)">
                                </td>
                                <td class="px-2 py-2 text-right text-sm text-red-400">
                                    <?php echo ($payroll['sss_deduction'] > 0) ? number_format($payroll['sss_deduction'], 0) : '-'; ?>
                                </td>
                                <td class="px-2 py-2 text-right text-sm text-red-400">
                                    <?php echo ($payroll['philhealth_deduction'] > 0) ? number_format($payroll['philhealth_deduction'], 0) : '-'; ?>
                                </td>
                                <td class="px-2 py-2 text-right text-sm text-red-400">
                                    <?php echo ($payroll['pagibig_deduction'] > 0) ? number_format($payroll['pagibig_deduction'], 0) : '-'; ?>
                                </td>
                                <td class="px-2 py-2 text-right text-sm text-red-400">
                                    <?php echo ($sss_loan > 0) ? number_format($sss_loan, 0) : '-'; ?>
                                </td>
                                <td class="px-2 py-2 text-right text-sm font-medium text-red-400">
                                    <?php echo number_format($total_deductions, 0); ?>
                                </td>
                                <td class="px-3 py-2 text-right text-sm font-bold text-green-400">
                                    <?php echo number_format($take_home, 0); ?>
                                </td>
                                <td class="px-3 py-2 text-center text-sm text-gray-400">
                                    <!-- Signature -->
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <!-- Total Row -->
                            <?php
                            $total_ot_hours = 0;
                            $total_ot = 0;
                            $total_allowance = 0;
                            $total_ca = 0;
                            $total_sss_loan = 0;
                            foreach ($employee_payroll as $payroll) {
                                $emp_ot_hours = $payroll['total_ot_hrs'];
                                $emp_ot_rate = $payroll['daily_rate'] / 8;
                                $total_ot_hours += $emp_ot_hours;
                                $total_ot += $emp_ot_hours * $emp_ot_rate;
                            }
                            $grand_total_deductions = $payroll_totals['total_deductions'] + $total_ca + $total_sss_loan;
                            $grand_take_home = $payroll_totals['total_gross'] + $total_allowance + $total_ot - $grand_total_deductions;
                            ?>
                            <tr class="bg-gray-800 font-bold border-t-2 border-yellow-500" id="totalRow">
                                <td class="px-3 py-3 text-white">TOTAL</td>
                                <td class="px-2 py-3 text-center text-white" id="totalDays"><?php echo $payroll_totals['total_days']; ?></td>
                                <td class="px-2 py-3 text-center text-gray-400" id="totalHours"><?php echo number_format($payroll_totals['total_hours'], 0); ?></td>
                                <td class="px-2 py-3 text-right text-gray-400">-</td>
                                <td class="px-2 py-3 text-right text-yellow-400" id="totalGross"><?php echo number_format($payroll_totals['total_gross'], 0); ?></td>
                                <td class="px-2 py-3 text-center text-gray-400" id="totalOTHours"><?php echo number_format($total_ot_hours, 0); ?></td>
                                <td class="px-2 py-3 text-right text-gray-400" id="totalOTAmount"><?php echo number_format($total_ot, 0); ?></td>
                                <td class="px-2 py-3 text-right text-yellow-400" id="totalGrossPlusOT"><?php echo number_format($payroll_totals['total_gross'] + $total_ot, 0); ?></td>
                                <td class="px-2 py-3 text-right text-blue-400" id="totalAllowance"><?php echo number_format($total_allowance, 0); ?></td>
                                <td class="px-2 py-3 text-right text-white" id="totalGrossPlusAllowance"><?php echo number_format($payroll_totals['total_gross'] + $total_allowance + $total_ot, 0); ?></td>
                                <td class="px-2 py-3 text-right text-red-400" id="totalCA"><?php echo ($total_ca > 0) ? number_format($total_ca, 0) : '-'; ?></td>
                                <td class="px-2 py-3 text-right text-red-400"><?php echo number_format($payroll_totals['total_deductions'], 0); ?></td>
                                <td class="px-2 py-3 text-right text-red-400">-</td>
                                <td class="px-2 py-3 text-right text-red-400">-</td>
                                <td class="px-2 py-3 text-right text-red-400">-</td>
                                <td class="px-2 py-3 text-right text-red-400">-</td>
                                <td class="px-2 py-3 text-right text-red-400" id="grandTotalDeductions"><?php echo number_format($grand_total_deductions, 0); ?></td>
                                <td class="px-3 py-3 text-right text-green-400" id="grandTakeHome"><?php echo number_format($grand_take_home, 0); ?></td>
                                <td class="px-3 py-3 text-center text-gray-400">-</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        const menuToggle = document.querySelector('.menu-toggle');
        if (menuToggle) {
            menuToggle.addEventListener('click', function() {
                const sidebar = document.querySelector('.sidebar');
                if (sidebar) {
                    sidebar.classList.toggle('active');
                }
            });
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (event) => {
            const sidebar = document.querySelector('.sidebar');
            const menuToggle = document.querySelector('.menu-toggle');
            
            if (window.innerWidth <= 768 && sidebar && menuToggle) {
                const isClickInsideSidebar = sidebar.contains(event.target);
                const isClickOnToggle = menuToggle.contains(event.target);
                
                if (!isClickInsideSidebar && !isClickOnToggle && sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                }
            }
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            const sidebar = document.querySelector('.sidebar');
            if (window.innerWidth > 768 && sidebar) {
                sidebar.classList.remove('active');
            }
        });

        // Print functionality
        function printReport() {
            window.print();
        }

        // Change view type (weekly/monthly)
        function changeView(viewType) {
            const url = new URL(window.location.href);
            url.searchParams.set('view', viewType);
            
            // Reset week to 1 when switching to monthly view
            if (viewType === 'monthly') {
                url.searchParams.delete('week');
            }
            
            window.location.href = url.toString();
        }

        // Export to Excel functionality with borders
        function exportToExcel() {
            const table = document.getElementById('reportTable');
            const tbody = table.querySelector('tbody');
            const dataRows = tbody.querySelectorAll('tr:not(:last-child)');
            const totalRow = tbody.querySelector('tr:last-child');
            
            // Build worksheet data
            let wsData = [];
            
            // Title rows
            wsData.push(['JAJR SECURITY SERVICES, INC.']);
            wsData.push(['PAYROLL PERIOD: <?php echo ($view_type === "weekly") ? "WEEK $selected_week - " : ""; ?><?php echo strtoupper(date('F Y', strtotime($year . "-" . $month . "-01"))); ?>']);
            wsData.push([]);
            
            // Headers - must match data structure exactly (21 columns)
            // Data structure: [EMPLOYEE, DAYS, HRS, RATE, BASIC, OT_HRS, OT_AMT, GROSS, PERF_ALLOW, '', GROSS_ALLOW, '', CA, SSS, PHIC, HDMF, SSS_LOAN, TOTAL, TAKE_HOME, '', SIGNATURE]
            wsData.push(['EMPLOYEE', 'DAYS WORKED', '', 'DAILY RATE', 'BASIC PAY', 'OVERTIME', '', 'GROSS PAY', 'PERFORMANCE ALLOWANCE', '', 'GROSS + ALLOWANCE', '', 'CA', 'SSS', 'PHIC', 'HDMF', 'SSS LOAN', 'TOTAL DEDUCTIONS', 'TAKE HOME PAY', '', 'SIGNATURE']);
            wsData.push(['', 'days', 'hrs', '', '', 'hrs', 'amt', '', '', '', '', '', '', '', '', '', '', '', '', '', '']);
            wsData.push([]);
            
            // Data rows
            dataRows.forEach((row, rowIdx) => {
                const cells = row.querySelectorAll('td');
                if (cells.length < 17) {
                    console.log('Row', rowIdx, 'skipped - only', cells.length, 'cells');
                    return;
                }
                
                // Debug: Log cell values
                console.log('Row', rowIdx, 'Cell 7 (Gross Pay):', cells[7]?.textContent?.trim());
                console.log('Row', rowIdx, 'Cell 9 (Gross+Allowance):', cells[9]?.textContent?.trim());
                console.log('Row', rowIdx, 'Cell 16 (Take Home):', cells[16]?.textContent?.trim());
                
                // Get allowance from input or text (Performance Allowance)
                const allowanceVal = cells[8].querySelector('input') ? cells[8].querySelector('input').value : cells[8].textContent.replace(/,/g, '').trim();
                
                // Get CA from input or text
                const caVal = cells[10].querySelector('input') ? cells[10].querySelector('input').value : cells[10].textContent.replace(/,/g, '').trim();
                
                // Map data to match header structure
                // Header: [EMPLOYEE, DAYS WORKED, '', DAILY RATE, BASIC PAY, OVERTIME, '', GROSS PAY, PERFORMANCE, '', GROSS +, '', CA, SSS, PHIC, HDMF, SSS LOAN, TOTAL, TAKE HOME, '', SIGNATURE]
                const rowData = [
                    cells[0].textContent.trim(),           // 0: EMPLOYEE
                    cells[1].textContent.trim(),           // 1: DAYS WORKED (days)
                    cells[2].textContent.trim(),           // 2: DAYS WORKED (hrs)
                    cells[3].textContent.replace(/,/g, '').trim(), // 3: DAILY RATE
                    cells[4].textContent.replace(/,/g, '').trim(), // 4: BASIC PAY
                    cells[5].textContent.trim(),           // 5: OVERTIME (hrs)
                    cells[6].textContent.replace(/,/g, '').trim(), // 6: OVERTIME (amt)
                    cells[7].textContent.replace(/,/g, '').trim(), // 7: GROSS PAY
                    allowanceVal,                          // 8: PERFORMANCE ALLOWANCE
                    '',                                    // 9: empty spacer
                    cells[9].textContent.replace(/,/g, '').trim(), // 10: GROSS + ALLOWANCE
                    '',                                    // 11: empty spacer
                    caVal,                                 // 12: CA
                    cells[11].textContent.replace(/,/g, '').replace('-', '0').trim(), // 13: SSS
                    cells[12].textContent.replace(/,/g, '').replace('-', '0').trim(), // 14: PHIC
                    cells[13].textContent.replace(/,/g, '').replace('-', '0').trim(), // 15: HDMF
                    cells[14].textContent.replace(/,/g, '').replace('-', '0').trim(), // 16: SSS LOAN
                    cells[15].textContent.replace(/,/g, '').trim(), // 17: TOTAL
                    cells[16].textContent.replace(/,/g, '').trim(), // 18: TAKE HOME PAY
                    '',                                    // 19: empty spacer
                    ''                                     // 20: SIGNATURE
                ];
                
                // Debug: Log the rowData being pushed
                console.log('Row', rowIdx, 'rowData[7] (Gross Pay):', rowData[7]);
                console.log('Row', rowIdx, 'rowData[10] (Gross+Allowance):', rowData[10]);
                console.log('Row', rowIdx, 'rowData[18] (Take Home):', rowData[18]);
                
                wsData.push(rowData);
            });
            
            // Total row
            if (totalRow) {
                const t = totalRow.querySelectorAll('td');
                if (t.length >= 18) {
                    wsData.push([
                        'TOTAL',                                // 0
                        t[1].textContent.trim(),                // 1: total days
                        t[2].textContent.trim(),                // 2: total hours
                        '',                                     // 3
                        t[4].textContent.replace(/,/g, ''),    // 4: total gross
                        t[5].textContent.trim(),                // 5: total OT hrs
                        t[6].textContent.replace(/,/g, ''),    // 6: total OT amt
                        t[7].textContent.replace(/,/g, ''),    // 7: gross + OT
                        t[8].textContent.replace(/,/g, ''),    // 8: total allowance
                        '',                                     // 9
                        t[9].textContent.replace(/,/g, ''),    // 10: gross + allowance
                        '',                                     // 11
                        t[10].textContent.replace(/,/g, '').replace('-', '0').trim(), // 12: CA
                        t[11].textContent.replace(/,/g, '').replace('-', '0').trim(), // 13: SSS
                        t[12].textContent.replace(/,/g, '').replace('-', '0').trim(), // 14: PHIC
                        t[13].textContent.replace(/,/g, '').replace('-', '0').trim(), // 15: HDMF
                        t[14].textContent.replace(/,/g, '').replace('-', '0').trim(), // 16: SSS Loan
                        t[15].textContent.replace(/,/g, '').trim(),                   // 17: Total Deductions
                        t[16].textContent.replace(/,/g, '').trim(),                   // 18: Take Home
                        '',                                     // 19
                        ''                                      // 20
                    ]);
                }
            }
            
            // Create worksheet
            const ws = XLSX.utils.aoa_to_sheet(wsData);
            
            // Define border style
            const borderStyle = {
                top: { style: 'thin', color: { rgb: '000000' } },
                bottom: { style: 'thin', color: { rgb: '000000' } },
                left: { style: 'thin', color: { rgb: '000000' } },
                right: { style: 'thin', color: { rgb: '000000' } }
            };
            
            const boldBorderStyle = {
                top: { style: 'medium', color: { rgb: '000000' } },
                bottom: { style: 'medium', color: { rgb: '000000' } },
                left: { style: 'medium', color: { rgb: '000000' } },
                right: { style: 'medium', color: { rgb: '000000' } }
            };
            
            // Apply borders to all cells
            const range = XLSX.utils.decode_range(ws['!ref']);
            for (let R = 3; R <= range.e.r; R++) {
                for (let C = 0; C <= range.e.c; C++) {
                    const cellRef = XLSX.utils.encode_cell({ r: R, c: C });
                    if (!ws[cellRef]) ws[cellRef] = { v: '' };
                    if (!ws[cellRef].s) ws[cellRef].s = {};
                    
                    // Bold header rows
                    if (R === 3 || R === 4 || R === 5) {
                        ws[cellRef].s.font = { bold: true };
                        ws[cellRef].s.border = boldBorderStyle;
                    } else if (R === range.e.r) {
                        // Bold total row
                        ws[cellRef].s.font = { bold: true };
                        ws[cellRef].s.border = borderStyle;
                    } else {
                        ws[cellRef].s.border = borderStyle;
                    }
                }
            }
            
            // Set column widths
            ws['!cols'] = [
                { wch: 25 }, // Employee
                { wch: 8 }, { wch: 6 }, // Days worked
                { wch: 10 }, // Daily rate
                { wch: 10 }, // Basic pay
                { wch: 6 }, { wch: 8 }, // Overtime
                { wch: 10 }, // Gross pay
                { wch: 10 }, { wch: 2 }, // Performance allowance
                { wch: 10 }, { wch: 2 }, // Gross + allowance
                { wch: 8 }, { wch: 8 }, { wch: 8 }, { wch: 8 }, { wch: 10 }, { wch: 10 }, // Deductions
                { wch: 12 }, { wch: 2 }, // Take home pay
                { wch: 12 } // Signature
            ];
            
            // Create workbook and download
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Payroll Report');
            XLSX.writeFile(wb, 'payroll_report_<?php echo $selected_month; ?>_week<?php echo $selected_week; ?><?php echo ($selected_branch !== "all") ? "_" . $selected_branch : ""; ?>.xlsx');
        }

        // Auto-refresh on view change
        document.addEventListener('DOMContentLoaded', function() {
            const viewSelect = document.querySelector('select[name="view"]');
            if (viewSelect) {
                viewSelect.addEventListener('change', function() {
                    const form = this.closest('form');
                    form.submit();
                });
            }

            const employeeSearch = document.getElementById('employeeSearch');
            if (employeeSearch) {
                employeeSearch.addEventListener('input', function() {
                    const q = (this.value || '').trim().toLowerCase();
                    const tbody = document.querySelector('#reportTable tbody');
                    if (!tbody) return;

                    const rows = tbody.querySelectorAll('tr');
                    rows.forEach((row, idx) => {
                        if (idx === rows.length - 1) return;
                        const text = (row.textContent || '').toLowerCase();
                        row.style.display = !q || text.includes(q) ? '' : 'none';
                    });
                });
            }
        });

        // Update calculations when CA input changes
        function updateCalculations(empId) {
            const caInput = document.getElementById('ca_' + empId);
            const caValue = parseFloat(caInput.value) || 0;
            
            // Get base values from the row (stored as data attributes)
            const row = caInput.closest('tr');
            
            // Find all cells in the row
            const cells = row.querySelectorAll('td');
            
            // Get values from cells (indices based on table structure)
            const grossPayText = cells[4].textContent.replace(/,/g, '');
            const grossPay = parseFloat(grossPayText) || 0;
            
            const otAmountText = cells[6].textContent.replace(/,/g, '');
            const otAmount = parseFloat(otAmountText) || 0;
            
            const allowanceInput = document.getElementById('allowance_' + empId);
            const allowance = parseFloat(allowanceInput.value) || 0;
            
            // Get deduction values
            const sssText = cells[11].textContent.replace(/,/g, '').replace('-', '0');
            const sss = parseFloat(sssText) || 0;
            
            const phicText = cells[12].textContent.replace(/,/g, '').replace('-', '0');
            const phic = parseFloat(phicText) || 0;
            
            const hdmfText = cells[13].textContent.replace(/,/g, '').replace('-', '0');
            const hdmf = parseFloat(hdmfText) || 0;
            
            const sssLoanText = cells[14].textContent.replace(/,/g, '').replace('-', '0');
            const sssLoan = parseFloat(sssLoanText) || 0;
            
            // Calculate totals
            const grossPlusAllowance = grossPay + allowance + otAmount;
            const totalDeductions = sss + phic + hdmf + caValue + sssLoan;
            const takeHome = grossPlusAllowance - totalDeductions;
            
            // Update Total Deductions cell (index 15)
            cells[15].textContent = numberFormat(totalDeductions);
            
            // Update Take Home cell (index 16)
            cells[16].textContent = numberFormat(takeHome);
            
            // Update the grand totals
            updateGrandTotals();
        }
        
        // Format number helper
        function numberFormat(num) {
            return Math.round(num).toLocaleString();
        }
        
        // Update grand total row
        function updateGrandTotals() {
            const allCAInputs = document.querySelectorAll('.ca-input');
            const allAllowanceInputs = document.querySelectorAll('.allowance-input');
            let totalCA = 0;
            let totalAllowance = 0;
            
            allCAInputs.forEach(input => {
                totalCA += parseFloat(input.value) || 0;
            });
            
            allAllowanceInputs.forEach(input => {
                totalAllowance += parseFloat(input.value) || 0;
            });
            
            // Get base totals from the total row
            const totalGross = parseFloat(document.getElementById('totalGross')?.textContent.replace(/,/g, '')) || 0;
            const totalOT = parseFloat(document.getElementById('totalOTAmount')?.textContent.replace(/,/g, '')) || 0;
            const baseDeductions = parseFloat(document.getElementById('grandTotalDeductions')?.textContent.replace(/,/g, '')) || 0;
            
            // Calculate grand totals
            const grandTotalDeductions = baseDeductions + totalCA;
            const grandTakeHome = totalGross + totalAllowance + totalOT - grandTotalDeductions;
            
            // Update total row cells
            const totalCAElement = document.getElementById('totalCA');
            if (totalCAElement) {
                totalCAElement.textContent = totalCA > 0 ? numberFormat(totalCA) : '-';
            }
            
            const totalAllowanceElement = document.getElementById('totalAllowance');
            if (totalAllowanceElement) {
                totalAllowanceElement.textContent = numberFormat(totalAllowance);
            }
            
            const totalGrossPlusAllowanceElement = document.getElementById('totalGrossPlusAllowance');
            if (totalGrossPlusAllowanceElement) {
                totalGrossPlusAllowanceElement.textContent = numberFormat(totalGross + totalAllowance + totalOT);
            }
            
            const grandTotalDeductionsElement = document.getElementById('grandTotalDeductions');
            if (grandTotalDeductionsElement) {
                grandTotalDeductionsElement.textContent = numberFormat(grandTotalDeductions);
            }
            
            const grandTakeHomeElement = document.getElementById('grandTakeHome');
            if (grandTakeHomeElement) {
                grandTakeHomeElement.textContent = numberFormat(grandTakeHome);
            }
        }
    </script>
</body>
</html>