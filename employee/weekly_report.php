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

// Fetch all branches for dropdown
$branch_query = "SELECT DISTINCT branch_name FROM attendance WHERE branch_name IS NOT NULL AND branch_name != '' ORDER BY branch_name";
$branch_result = mysqli_query($db, $branch_query);
$all_branches_list = [];
while ($branch_row = mysqli_fetch_assoc($branch_result)) {
    $all_branches_list[] = $branch_row['branch_name'];
}

// Fetch attendance data for the date range - Get all present employees
$attendance_query = "SELECT a.employee_id, a.attendance_date, a.status, a.branch_name,
                            e.first_name, e.last_name, e.employee_code
                     FROM attendance a
                     JOIN employees e ON a.employee_id = e.id
                     WHERE a.attendance_date BETWEEN ? AND ? 
                     AND a.status = 'Present'";
                    
// Add branch filter if not 'all'
if ($selected_branch !== 'all') {
    $attendance_query .= " AND a.branch_name = ?";
    $attendance_query .= " ORDER BY a.attendance_date, a.branch_name";
    
    $stmt = mysqli_prepare($db, $attendance_query);
    mysqli_stmt_bind_param($stmt, 'sss', $start_date, $end_date, $selected_branch);
} else {
    $attendance_query .= " ORDER BY a.attendance_date, a.branch_name";
    $stmt = mysqli_prepare($db, $attendance_query);
    mysqli_stmt_bind_param($stmt, 'ss', $start_date, $end_date);
}

mysqli_stmt_execute($stmt);
$attendance_result = mysqli_stmt_get_result($stmt);

// Organize data by date and branch
$attendance_by_date_branch = [];
$all_branches = [];
$all_dates = [];

while ($row = mysqli_fetch_assoc($attendance_result)) {
    $date = $row['attendance_date'];
    $branch = $row['branch_name'];
    $employee_name = $row['first_name'] . ' ' . $row['last_name'];
    $employee_code = $row['employee_code'];
    
    // Store data
    $attendance_by_date_branch[$date][$branch][] = [
        'name' => $employee_name,
        'code' => $employee_code
    ];
    
    // Collect unique branches
    if (!in_array($branch, $all_branches)) {
        $all_branches[] = $branch;
    }
    
    // Collect unique dates
    if (!in_array($date, $all_dates)) {
        $all_dates[] = $date;
    }
}

// If branch filter is selected, only show that branch
if ($selected_branch !== 'all') {
    $all_branches = [$selected_branch];
} else {
    // Sort branches alphabetically
    sort($all_branches);
}

// Generate date array for the selected range
$dates = [];
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
            background: linear-gradient(90deg, var(--gold), var(--black));
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
            background: linear-gradient(90deg, var(--gold), var(--black));
            color: white;
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
        }

        .branch-badge.all {
            background: rgba(0, 123, 255, 0.15);
            border-color: rgba(0, 123, 255, 0.3);
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
            body * {
                visibility: hidden;
            }
            .report-card, .report-card * {
                visibility: visible;
            }
            .report-card {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                border: none;
                background: white;
                color: black;
            }
            .btn-print, .input-field, .btn-primary, .sidebar, .top-nav,
            .view-toggle, .filters, .btn-secondary {
                display: none;
            }
            .main-content {
                margin-left: 0;
                background: white;
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
                            <?php echo ($view_type === 'weekly') ? 'Weekly' : 'Monthly'; ?> Deployment Report
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
                    <h2 class="text-xl font-bold text-white">
                        <?php echo $date_range_label; ?>
                        <?php if ($selected_branch !== 'all'): ?>
                        <span class="text-gold-300 block text-sm mt-1">
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
                            <i class="fas fa-filter mr-2"></i>Apply Filter
                        </button>
                        <button type="button" onclick="window.print()" class="btn-print">
                            <i class="fas fa-print mr-2"></i>Print Report
                        </button>
                        <button type="button" onclick="exportToExcel()" class="btn-secondary">
                            <i class="fas fa-file-excel mr-2"></i>Export Excel
                        </button>
                    </div>
                </form>

                <!-- Quick Branch Filter Links -->
                <div class="mb-6">
                    <h4 class="text-sm font-medium text-gray-300 mb-2">Quick Branch Filter:</h4>
                    <div class="flex flex-wrap gap-2">
                        <a href="?view=<?php echo $view_type; ?>&month=<?php echo $selected_month; ?>&week=<?php echo $selected_week; ?>&branch=all" 
                           class="branch-badge all <?php echo ($selected_branch === 'all') ? 'active' : ''; ?>">
                            <i class="fas fa-layer-group mr-1"></i>All Branches
                        </a>
                        <?php foreach ($all_branches_list as $branch_name): ?>
                            <?php if ($branch_name): ?>
                            <a href="?view=<?php echo $view_type; ?>&month=<?php echo $selected_month; ?>&week=<?php echo $selected_week; ?>&branch=<?php echo urlencode($branch_name); ?>" 
                               class="branch-badge <?php echo ($selected_branch === $branch_name) ? 'active' : ''; ?>">
                                <i class="fas fa-building mr-1"></i><?php echo htmlspecialchars($branch_name); ?>
                            </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Weekly Breakdown for Monthly View -->
                <?php if ($view_type === 'monthly' && !empty($weekly_breakdown)): ?>
                <div class="weekly-breakdown mb-6">
                    <h3 class="text-lg font-semibold text-gold-300 mb-3">Weekly Breakdown</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <?php foreach ($weekly_breakdown as $week_num => $week_dates): ?>
                        <?php
                        $week_start = reset($week_dates);
                        $week_end = end($week_dates);
                        ?>
                        <div class="week-card">
                            <div class="font-bold text-gold-300 mb-1">Week <?php echo $week_num; ?></div>
                            <div class="text-sm">
                                <?php echo date('M d', strtotime($week_start)); ?> - <?php echo date('M d', strtotime($week_end)); ?>
                            </div>
                            <div class="text-xs text-gray-400 mt-2">
                                <?php echo count($week_dates); ?> days
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Report Table -->
                <div class="report-table overflow-x-auto mb-6">
                    <?php if ($view_type === 'monthly' && count($dates) > 20): ?>
                    <div class="text-center py-4 text-sm text-gray-400">
                        <i class="fas fa-info-circle mr-2"></i>
                        Showing <?php echo count($dates); ?> days. Scroll horizontally to view all data.
                    </div>
                    <?php endif; ?>
                    
                    <table class="w-full border-collapse min-w-[600px]" id="reportTable">
                        <thead>
                            <tr>
                                <!-- Date row header -->
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider border-b border-gray-600">
                                    Date / Branch
                                </th>
                                <!-- Branch names as column headers -->
                                <?php if ($selected_branch === 'all'): ?>
                                    <?php foreach ($all_branches as $branch): ?>
                                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-300 uppercase tracking-wider border-b border-gray-600 branch-header">
                                            <?php echo htmlspecialchars($branch); ?>
                                        </th>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <!-- Single branch view -->
                                    <th class="px-3 py-3 text-center text-xs font-medium text-gray-300 uppercase tracking-wider border-b border-gray-600 branch-header">
                                        <?php echo htmlspecialchars($selected_branch); ?>
                                    </th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Each date as a row -->
                            <?php foreach ($dates as $date): ?>
                            <?php 
                            // Highlight weekends
                            $day_of_week = date('w', strtotime($date));
                            $is_weekend = ($day_of_week == 0 || $day_of_week == 6);
                            ?>
                            <tr class="border-b border-gray-700 <?php echo $is_weekend ? 'bg-gray-800/30' : ''; ?>">
                                <!-- Date column -->
                                <td class="px-4 py-3 date-header whitespace-nowrap">
                                    <div class="font-semibold"><?php echo date('D', strtotime($date)); ?></div>
                                    <div class="text-sm"><?php echo date('M d, Y', strtotime($date)); ?></div>
                                    <?php if ($is_weekend): ?>
                                    <div class="text-xs text-gold-300 mt-1">
                                        <i class="fas fa-flag mr-1"></i>Weekend
                                    </div>
                                    <?php endif; ?>
                                </td>
                                
                                <!-- Employee boxes for each branch -->
                                <?php if ($selected_branch === 'all'): ?>
                                    <?php foreach ($all_branches as $branch): ?>
                                        <td class="px-3 py-3 align-top min-w-[180px]">
                                            <div class="employee-container">
                                                <?php if (isset($attendance_by_date_branch[$date][$branch]) && !empty($attendance_by_date_branch[$date][$branch])): ?>
                                                    <?php foreach ($attendance_by_date_branch[$date][$branch] as $employee): ?>
                                                        <div class="employee-box">
                                                            <div class="font-medium text-sm text-white">
                                                                <?php echo htmlspecialchars($employee['name']); ?>
                                                            </div>
                                                            <div class="text-xs text-gray-300 mt-1">
                                                                ID: <?php echo htmlspecialchars($employee['code']); ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <div class="empty-cell text-sm">
                                                        No Deployment
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <!-- Single branch view -->
                                    <td class="px-3 py-3 align-top min-w-[180px]">
                                        <div class="employee-container">
                                            <?php if (isset($attendance_by_date_branch[$date][$selected_branch]) && !empty($attendance_by_date_branch[$date][$selected_branch])): ?>
                                                <?php foreach ($attendance_by_date_branch[$date][$selected_branch] as $employee): ?>
                                                    <div class="employee-box">
                                                        <div class="font-medium text-sm text-white">
                                                            <?php echo htmlspecialchars($employee['name']); ?>
                                                        </div>
                                                        <div class="text-xs text-gray-300 mt-1">
                                                            ID: <?php echo htmlspecialchars($employee['code']); ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="empty-cell text-sm">
                                                    No Deployment
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Summary Section -->
                <div class="mt-8">
                    <h3 class="text-lg font-semibold text-gold-300 mb-4">Report Summary</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="summary-card">
                            <div class="summary-value">
                                <?php echo count($all_branches); ?>
                            </div>
                            <div class="summary-label">
                                <?php echo ($selected_branch === 'all') ? 'Total Branches' : 'Selected Branch'; ?>
                            </div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-value">
                                <?php echo count($dates); ?>
                            </div>
                            <div class="summary-label">
                                <?php echo ($view_type === 'weekly') ? 'Days in Week' : 'Days in Month'; ?>
                            </div>
                        </div>
                        <div class="summary-card">
                            <?php
                            // Count total deployments
                            $total_deployments = 0;
                            foreach ($attendance_by_date_branch as $date => $branches) {
                                foreach ($branches as $branch => $employees) {
                                    $total_deployments += count($employees);
                                }
                            }
                            ?>
                            <div class="summary-value">
                                <?php echo $total_deployments; ?>
                            </div>
                            <div class="summary-label">Total Deployments</div>
                        </div>
                        <?php if ($view_type === 'monthly'): ?>
                        <div class="summary-card">
                            <div class="summary-value">
                                <?php echo count($weekly_breakdown); ?>
                            </div>
                            <div class="summary-label">Weeks in Month</div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

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

        // Export to Excel functionality
        function exportToExcel() {
            // Create a simple Excel export by converting table to CSV
            const table = document.getElementById('reportTable');
            const rows = table.querySelectorAll('tr');
            let csv = [];
            
            for (let i = 0; i < rows.length; i++) {
                const row = [], cols = rows[i].querySelectorAll('td, th');
                
                for (let j = 0; j < cols.length; j++) {
                    // Clean text content
                    let text = cols[j].innerText;
                    // Remove newlines and extra spaces
                    text = text.replace(/\n/g, ' ').replace(/\s+/g, ' ').trim();
                    // Handle quotes for CSV
                    text = text.replace(/"/g, '""');
                    // Wrap in quotes if contains comma
                    if (text.includes(',')) {
                        text = '"' + text + '"';
                    }
                    row.push(text);
                }
                
                csv.push(row.join(','));
            }
            
            // Download CSV file
            const csvContent = "data:text/csv;charset=utf-8," + csv.join('\n');
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "deployment_report_<?php echo $selected_month; ?><?php echo ($selected_branch !== 'all') ? '_' . $selected_branch : ''; ?>.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
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
        });
    </script>
</body>
</html>