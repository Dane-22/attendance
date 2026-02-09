<?php
// employee/payroll.php - Payroll Processing System
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

// Handle filters
$selected_month = $_GET['month'] ?? $current_month;
$selected_week = intval($_GET['week'] ?? 1);
$view_type = $_GET['view'] ?? 'weekly'; // 'weekly' or 'monthly'
$selected_branch = $_GET['branch'] ?? 'all';
$action = $_GET['action'] ?? 'view'; // 'view', 'calculate', 'process'

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
$has_week_5 = $days_in_month > 28;

// If Week 5 selected but not available, default to Week 4
if ($selected_week == 5 && !$has_week_5) {
    $selected_week = 4;
}

// Calculate date ranges
if ($view_type === 'weekly') {
    $week_start_day = 1 + (($selected_week - 1) * 7);
    $week_end_day = min($week_start_day + 6, $days_in_month);
    $start_date = sprintf('%04d-%02d-%02d', $year, $month, $week_start_day);
    $end_date = sprintf('%04d-%02d-%02d', $year, $month, $week_end_day);
    $date_range_label = "Week $selected_week: " . date('M d', strtotime($start_date)) . " - " . date('M d, Y', strtotime($end_date));
} else {
    $start_date = sprintf('%04d-%02d-01', $year, $month);
    $end_date = sprintf('%04d-%02d-%02d', $year, $month, $days_in_month);
    $date_range_label = "Monthly Payroll: " . date('F Y', strtotime($start_date));
}

// Fetch all branches
$branch_query = "SELECT DISTINCT branch_name FROM attendance WHERE branch_name IS NOT NULL AND branch_name != '' ORDER BY branch_name";
$branch_result = mysqli_query($db, $branch_query);
$all_branches_list = [];
while ($branch_row = mysqli_fetch_assoc($branch_result)) {
    $all_branches_list[] = $branch_row['branch_name'];
}

// Fetch employees with their daily rates
$employee_query = "SELECT id, employee_code, first_name, last_name, daily_rate, position, status 
                   FROM employees 
                   WHERE status = 'Active'
                   ORDER BY last_name, first_name";
$employee_result = mysqli_query($db, $employee_query);
$employees = [];
while ($row = mysqli_fetch_assoc($employee_result)) {
    $employees[$row['id']] = $row;
}

// Fetch attendance data for the date range
$attendance_query = "SELECT a.employee_id, a.attendance_date, a.status, a.branch_name,
                            e.daily_rate
                     FROM attendance a
                     JOIN employees e ON a.employee_id = e.id
                     WHERE a.attendance_date BETWEEN ? AND ?";

if ($selected_branch !== 'all') {
    $attendance_query .= " AND a.branch_name = ?";
    $attendance_query .= " ORDER BY a.employee_id, a.attendance_date";
    $stmt = mysqli_prepare($db, $attendance_query);
    mysqli_stmt_bind_param($stmt, 'sss', $start_date, $end_date, $selected_branch);
} else {
    $attendance_query .= " ORDER BY a.employee_id, a.attendance_date";
    $stmt = mysqli_prepare($db, $attendance_query);
    mysqli_stmt_bind_param($stmt, 'ss', $start_date, $end_date);
}

mysqli_stmt_execute($stmt);
$attendance_result = mysqli_stmt_get_result($stmt);

// Organize attendance data by employee
$attendance_by_employee = [];
$branch_by_employee = [];

while ($row = mysqli_fetch_assoc($attendance_result)) {
    $emp_id = $row['employee_id'];
    $status = $row['status'];
    
    if (!isset($attendance_by_employee[$emp_id])) {
        $attendance_by_employee[$emp_id] = [
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'total_days' => 0
        ];
    }
    
    $attendance_by_employee[$emp_id]['total_days']++;
    
    if ($status === 'Present') {
        $attendance_by_employee[$emp_id]['present']++;
    } elseif ($status === 'Absent') {
        $attendance_by_employee[$emp_id]['absent']++;
    } elseif ($status === 'Late') {
        $attendance_by_employee[$emp_id]['late']++;
        $attendance_by_employee[$emp_id]['present']++; // Late counts as present for pay
    }
    
    // Track primary branch (most frequent)
    $branch = $row['branch_name'];
    if (!isset($branch_by_employee[$emp_id])) {
        $branch_by_employee[$emp_id] = [];
    }
    if (!isset($branch_by_employee[$emp_id][$branch])) {
        $branch_by_employee[$emp_id][$branch] = 0;
    }
    $branch_by_employee[$emp_id][$branch]++;
}

// Get primary branch for each employee
$primary_branch = [];
foreach ($branch_by_employee as $emp_id => $branches) {
    arsort($branches);
    $primary_branch[$emp_id] = array_key_first($branches);
}

// Fetch performance adjustments/bonuses for the period
$bonus_query = "SELECT employee_id, SUM(bonus_amount) as total_bonus
                FROM performance_adjustments
                WHERE adjustment_date BETWEEN ? AND ?
                GROUP BY employee_id";
$bonus_stmt = mysqli_prepare($db, $bonus_query);
mysqli_stmt_bind_param($bonus_stmt, 'ss', $start_date, $end_date);
mysqli_stmt_execute($bonus_stmt);
$bonus_result = mysqli_stmt_get_result($bonus_stmt);
$bonuses = [];
while ($row = mysqli_fetch_assoc($bonus_result)) {
    $bonuses[$row['employee_id']] = $row['total_bonus'];
}

// Calculate payroll for each employee
$payroll_data = [];
$totals = [
    'basic_pay' => 0,
    'ot_pay' => 0,
    'performance_bonus' => 0,
    'gross_pay' => 0,
    'total_deductions' => 0,
    'net_pay' => 0
];

foreach ($employees as $emp_id => $employee) {
    $attendance = $attendance_by_employee[$emp_id] ?? ['present' => 0, 'absent' => 0, 'late' => 0, 'total_days' => 0];
    $daily_rate = floatval($employee['daily_rate']);
    $days_present = $attendance['present'];
    $days_absent = $attendance['absent'];
    $days_late = $attendance['late'];
    
    // Basic pay calculation
    $basic_pay = $days_present * $daily_rate;
    
    // OT calculation (placeholder - would need OT hours from attendance)
    $ot_hours = 0;
    $ot_rate = $daily_rate / 8 * 1.25; // 25% overtime premium
    $ot_pay = $ot_hours * $ot_rate;
    
    // Performance bonus
    $performance_bonus = $bonuses[$emp_id] ?? 0;
    
    // Gross pay
    $gross_pay = $basic_pay + $ot_pay + $performance_bonus;
    
    // Deductions (Philippine standard deductions - simplified)
    $sss_deduction = min($gross_pay * 0.045, 1125); // SSS capped
    $philhealth_deduction = min($gross_pay * 0.035, 2450); // PhilHealth capped
    $pagibig_deduction = 100; // Fixed Pag-IBIG
    
    // Tax calculation (simplified)
    $taxable_income = $gross_pay - ($sss_deduction + $philhealth_deduction + $pagibig_deduction);
    $tax_deduction = 0;
    if ($taxable_income > 20833) {
        $tax_deduction = ($taxable_income - 20833) * 0.20;
    }
    
    $total_deductions = $sss_deduction + $philhealth_deduction + $pagibig_deduction + $tax_deduction;
    $net_pay = $gross_pay - $total_deductions;
    
    $payroll_data[$emp_id] = [
        'employee' => $employee,
        'branch' => $primary_branch[$emp_id] ?? 'N/A',
        'attendance' => $attendance,
        'daily_rate' => $daily_rate,
        'days_present' => $days_present,
        'days_absent' => $days_absent,
        'days_late' => $days_late,
        'basic_pay' => $basic_pay,
        'ot_hours' => $ot_hours,
        'ot_rate' => $ot_rate,
        'ot_pay' => $ot_pay,
        'performance_bonus' => $performance_bonus,
        'gross_pay' => $gross_pay,
        'sss_deduction' => $sss_deduction,
        'philhealth_deduction' => $philhealth_deduction,
        'pagibig_deduction' => $pagibig_deduction,
        'tax_deduction' => $tax_deduction,
        'total_deductions' => $total_deductions,
        'net_pay' => $net_pay
    ];
    
    // Update totals
    $totals['basic_pay'] += $basic_pay;
    $totals['ot_pay'] += $ot_pay;
    $totals['performance_bonus'] += $performance_bonus;
    $totals['gross_pay'] += $gross_pay;
    $totals['total_deductions'] += $total_deductions;
    $totals['net_pay'] += $net_pay;
}

// Handle form submission for processing payroll
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payroll'])) {
    $processed_count = 0;
    
    foreach ($payroll_data as $emp_id => $data) {
        // Check if payroll record already exists
        $check_query = "SELECT id FROM payroll_records 
                       WHERE employee_id = ? AND pay_period_start = ? AND pay_period_end = ?";
        $check_stmt = mysqli_prepare($db, $check_query);
        mysqli_stmt_bind_param($check_stmt, 'iss', $emp_id, $start_date, $end_date);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            // Update existing record
            $existing = mysqli_fetch_assoc($check_result);
            $payroll_id = $existing['id'];
            
            $update_query = "UPDATE payroll_records SET
                days_present = ?, days_absent = ?, days_late = ?,
                daily_rate = ?, basic_pay = ?, ot_hours = ?, ot_rate = ?, ot_pay = ?,
                performance_bonus = ?, gross_pay = ?,
                sss_deduction = ?, philhealth_deduction = ?, pagibig_deduction = ?,
                tax_deduction = ?, total_deductions = ?, net_pay = ?,
                status = 'Processed', processed_by = ?, processed_at = NOW()
                WHERE id = ?";
            
            $update_stmt = mysqli_prepare($db, $update_query);
            mysqli_stmt_bind_param($update_stmt, 'iiidddddddddddddiii',
                $data['days_present'], $data['days_absent'], $data['days_late'],
                $data['daily_rate'], $data['basic_pay'], $data['ot_hours'], $data['ot_rate'], $data['ot_pay'],
                $data['performance_bonus'], $data['gross_pay'],
                $data['sss_deduction'], $data['philhealth_deduction'], $data['pagibig_deduction'],
                $data['tax_deduction'], $data['total_deductions'], $data['net_pay'],
                $_SESSION['user_id'], $payroll_id
            );
            
            if (mysqli_stmt_execute($update_stmt)) {
                $processed_count++;
            }
        } else {
            // Insert new record
            $insert_query = "INSERT INTO payroll_records
                (employee_id, pay_period_start, pay_period_end, days_present, days_absent, days_late,
                daily_rate, basic_pay, ot_hours, ot_rate, ot_pay, performance_bonus, gross_pay,
                sss_deduction, philhealth_deduction, pagibig_deduction, tax_deduction, total_deductions, net_pay,
                status, processed_by, processed_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Processed', ?, NOW())";
            
            $insert_stmt = mysqli_prepare($db, $insert_query);
            mysqli_stmt_bind_param($insert_stmt, 'issiiidddddddddddddi',
                $emp_id, $start_date, $end_date,
                $data['days_present'], $data['days_absent'], $data['days_late'],
                $data['daily_rate'], $data['basic_pay'], $data['ot_hours'], $data['ot_rate'], $data['ot_pay'],
                $data['performance_bonus'], $data['gross_pay'],
                $data['sss_deduction'], $data['philhealth_deduction'], $data['pagibig_deduction'],
                $data['tax_deduction'], $data['total_deductions'], $data['net_pay'],
                $_SESSION['user_id']
            );
            
            if (mysqli_stmt_execute($insert_stmt)) {
                $processed_count++;
            }
        }
    }
    
    if ($processed_count > 0) {
        $message = "Payroll processed successfully for $processed_count employees.";
    } else {
        $error = "No payroll records were processed.";
    }
}

// Check for existing payroll records
$existing_payroll = [];
$check_existing_query = "SELECT employee_id, status FROM payroll_records 
                        WHERE pay_period_start = ? AND pay_period_end = ?";
$check_stmt = mysqli_prepare($db, $check_existing_query);
mysqli_stmt_bind_param($check_stmt, 'ss', $start_date, $end_date);
mysqli_stmt_execute($check_stmt);
$existing_result = mysqli_stmt_get_result($check_stmt);
while ($row = mysqli_fetch_assoc($existing_result)) {
    $existing_payroll[$row['employee_id']] = $row['status'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Processing - Admin Panel</title>
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

        .welcome {
            font-size: 24px;
            font-weight: 700;
            color: #FFD700;
            margin-bottom: 4px;
        }

        .text-sm { font-size: 14px; }
        .text-gray { color: #888; }

        .payroll-card {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
        }

        .payroll-header {
            background: linear-gradient(90deg, var(--gold), var(--black));
            border-radius: 12px 12px 0 0;
            padding: 20px;
            margin: -24px -24px 20px -24px;
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
            background: linear-gradient(90deg, var(--gold), var(--black));
            border: none;
            color: white;
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

        .btn-success {
            background: linear-gradient(90deg, #28a745, #1e7e34);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
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
        }

        .payroll-table {
            background: #0a0a0a;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #333;
            width: 100%;
        }

        .payroll-table th {
            background: rgba(255, 215, 0, 0.15);
            color: #FFD700;
            font-weight: 600;
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #333;
        }

        .payroll-table td {
            padding: 12px;
            border-bottom: 1px solid #222;
        }

        .payroll-table tr:hover {
            background: rgba(255, 215, 0, 0.05);
        }

        .amount-positive {
            color: #28a745;
            font-weight: 600;
        }

        .amount-negative {
            color: #dc3545;
            font-weight: 600;
        }

        .amount-neutral {
            color: #FFD700;
            font-weight: 600;
        }

        .summary-card {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }

        .summary-value {
            font-size: 28px;
            font-weight: 700;
            color: #FFD700;
            margin-bottom: 8px;
        }

        .summary-label {
            font-size: 14px;
            color: #888;
        }

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
            background: linear-gradient(90deg, var(--gold), var(--black));
            color: white;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .status-draft { background: rgba(108, 117, 125, 0.2); color: #6c757d; border: 1px solid #6c757d; }
        .status-processed { background: rgba(255, 193, 7, 0.2); color: #ffc107; border: 1px solid #ffc107; }
        .status-paid { background: rgba(40, 167, 69, 0.2); color: #28a745; border: 1px solid #28a745; }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.15);
            border: 1px solid #28a745;
            color: #28a745;
        }

        .alert-error {
            background: rgba(220, 53, 69, 0.15);
            border: 1px solid #dc3545;
            color: #dc3545;
        }

        @media (max-width: 768px) {
            .payroll-table {
                font-size: 0.85rem;
            }
            .payroll-table th, .payroll-table td {
                padding: 8px;
            }
        }

        @media print {
            body * { visibility: hidden; }
            .payroll-card, .payroll-card * { visibility: visible; }
            .payroll-card {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                border: none;
                background: white;
                color: black;
            }
            .btn-print, .btn-primary, .btn-success, .sidebar, .filters {
                display: none;
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
                <div>
                    <div class="welcome">
                        <i class="fas fa-money-bill-wave mr-2"></i>Payroll Processing
                    </div>
                    <div class="text-sm text-gray">
                        Admin Panel | <?php echo ($view_type === 'weekly') ? "Week $selected_week Payroll" : "Monthly Payroll"; ?>
                        <?php if ($selected_branch !== 'all'): ?> | Branch: <?php echo htmlspecialchars($selected_branch); ?><?php endif; ?>
                    </div>
                </div>
                <div class="text-sm text-gray">
                    Today: <?php echo date('F d, Y'); ?>
                </div>
            </div>

            <!-- View Toggle -->
            <div class="view-toggle">
                <div class="view-option <?php echo ($view_type === 'weekly') ? 'active' : ''; ?>" 
                     onclick="changeView('weekly')">
                    <i class="fas fa-calendar-week mr-2"></i> Weekly Payroll
                </div>
                <div class="view-option <?php echo ($view_type === 'monthly') ? 'active' : ''; ?>" 
                     onclick="changeView('monthly')">
                    <i class="fas fa-calendar-alt mr-2"></i> Monthly Payroll
                </div>
            </div>

            <!-- Messages -->
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Main Payroll Card -->
            <div class="payroll-card">
                <div class="payroll-header">
                    <h2 class="text-xl font-bold text-white">
                        <?php echo $date_range_label; ?>
                        <?php if ($selected_branch !== 'all'): ?>
                        <span class="block text-sm mt-1 text-gray-200">
                            <i class="fas fa-building mr-1"></i>Branch: <?php echo htmlspecialchars($selected_branch); ?>
                        </span>
                        <?php endif; ?>
                    </h2>
                </div>

                <!-- Filters -->
                <form method="GET" class="mb-6 flex flex-wrap gap-4 items-end filters">
                    <input type="hidden" name="view" value="<?php echo $view_type; ?>">
                    
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Select Month</label>
                        <select name="month" class="input-field">
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
                        <select name="week" class="input-field">
                            <?php for ($w = 1; $w <= ($has_week_5 ? 5 : 4); $w++): ?>
                                <option value="<?php echo $w; ?>" <?php echo ($w == $selected_week) ? 'selected' : ''; ?>>Week <?php echo $w; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Select Branch</label>
                        <select name="branch" class="input-field">
                            <option value="all" <?php echo ($selected_branch === 'all') ? 'selected' : ''; ?>>All Branches</option>
                            <?php foreach ($all_branches_list as $branch_name): ?>
                                <option value="<?php echo htmlspecialchars($branch_name); ?>" 
                                    <?php echo ($selected_branch === $branch_name) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($branch_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="flex gap-2">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-filter mr-2"></i>Calculate
                        </button>
                        <button type="button" onclick="window.print()" class="btn-print">
                            <i class="fas fa-print mr-2"></i>Print
                        </button>
                    </div>
                </form>

                <!-- Summary Cards -->
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-6">
                    <div class="summary-card">
                        <div class="summary-value"><?php echo count($payroll_data); ?></div>
                        <div class="summary-label">Employees</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-value">₱<?php echo number_format($totals['basic_pay'], 2); ?></div>
                        <div class="summary-label">Basic Pay</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-value">₱<?php echo number_format($totals['ot_pay'], 2); ?></div>
                        <div class="summary-label">OT Pay</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-value">₱<?php echo number_format($totals['performance_bonus'], 2); ?></div>
                        <div class="summary-label">Bonuses</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-value">₱<?php echo number_format($totals['gross_pay'], 2); ?></div>
                        <div class="summary-label">Gross Pay</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-value" style="color: #28a745;">₱<?php echo number_format($totals['net_pay'], 2); ?></div>
                        <div class="summary-label">Net Pay</div>
                    </div>
                </div>

                <!-- Payroll Table -->
                <div class="overflow-x-auto">
                    <table class="payroll-table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Branch</th>
                                <th class="text-center">Days</th>
                                <th class="text-right">Daily Rate</th>
                                <th class="text-right">Basic Pay</th>
                                <th class="text-right">OT Pay</th>
                                <th class="text-right">Bonus</th>
                                <th class="text-right">Gross</th>
                                <th class="text-right">Deductions</th>
                                <th class="text-right">Net Pay</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payroll_data as $emp_id => $data): ?>
                            <?php 
                            $status = $existing_payroll[$emp_id] ?? 'Draft';
                            $status_class = 'status-' . strtolower($status);
                            ?>
                            <tr>
                                <td>
                                    <div class="font-medium"><?php echo htmlspecialchars($data['employee']['last_name'] . ', ' . $data['employee']['first_name']); ?></div>
                                    <div class="text-xs text-gray-400"><?php echo htmlspecialchars($data['employee']['employee_code']); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($data['branch']); ?></td>
                                <td class="text-center">
                                    <span class="text-green-400" title="Present"><?php echo $data['days_present']; ?></span>
                                    <?php if ($data['days_late'] > 0): ?>
                                    / <span class="text-yellow-400" title="Late"><?php echo $data['days_late']; ?></span>
                                    <?php endif; ?>
                                    <?php if ($data['days_absent'] > 0): ?>
                                    / <span class="text-red-400" title="Absent"><?php echo $data['days_absent']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right">₱<?php echo number_format($data['daily_rate'], 2); ?></td>
                                <td class="text-right amount-neutral">₱<?php echo number_format($data['basic_pay'], 2); ?></td>
                                <td class="text-right amount-positive">₱<?php echo number_format($data['ot_pay'], 2); ?></td>
                                <td class="text-right amount-positive">₱<?php echo number_format($data['performance_bonus'], 2); ?></td>
                                <td class="text-right amount-neutral">₱<?php echo number_format($data['gross_pay'], 2); ?></td>
                                <td class="text-right amount-negative">-₱<?php echo number_format($data['total_deductions'], 2); ?></td>
                                <td class="text-right" style="color: #28a745; font-weight: 700;">₱<?php echo number_format($data['net_pay'], 2); ?></td>
                                <td class="text-center">
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo $status; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background: rgba(255, 215, 0, 0.1); font-weight: 700;">
                                <td colspan="4" class="text-right">TOTALS:</td>
                                <td class="text-right amount-neutral">₱<?php echo number_format($totals['basic_pay'], 2); ?></td>
                                <td class="text-right amount-positive">₱<?php echo number_format($totals['ot_pay'], 2); ?></td>
                                <td class="text-right amount-positive">₱<?php echo number_format($totals['performance_bonus'], 2); ?></td>
                                <td class="text-right amount-neutral">₱<?php echo number_format($totals['gross_pay'], 2); ?></td>
                                <td class="text-right amount-negative">-₱<?php echo number_format($totals['total_deductions'], 2); ?></td>
                                <td class="text-right" style="color: #28a745;">₱<?php echo number_format($totals['net_pay'], 2); ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Process Payroll Button -->
                <form method="POST" class="mt-6 flex justify-end">
                    <button type="submit" name="process_payroll" class="btn-success" <?php echo empty($payroll_data) ? 'disabled' : ''; ?>>
                        <i class="fas fa-check-circle mr-2"></i>Process Payroll
                    </button>
                </form>
            </div>
        </main>
    </div>

    <script>
        function changeView(view) {
            const url = new URL(window.location.href);
            url.searchParams.set('view', view);
            if (view === 'monthly') {
                url.searchParams.delete('week');
            }
            window.location.href = url.toString();
        }
    </script>
</body>
</html>
