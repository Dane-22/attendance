<?php
// admin/weekly_report.php - Weekly Deployment & Attendance Report
require_once __DIR__ . '/../conn/db_connection.php';
require_once __DIR__ . '/../functions.php';
session_start();

// Rate Limiting Configuration
$RATE_LIMIT_MAX_REQUESTS = 60; // Max requests per minute
$RATE_LIMIT_WINDOW = 60; // Window in seconds

// Initialize rate limiting in session
if (!isset($_SESSION['weekly_report_rate_limit'])) {
    $_SESSION['weekly_report_rate_limit'] = [
        'requests' => [],
        'blocked_until' => null
    ];
}

$now = time();

// Check if user is currently blocked
if ($_SESSION['weekly_report_rate_limit']['blocked_until'] && $now < $_SESSION['weekly_report_rate_limit']['blocked_until']) {
    $retryAfter = $_SESSION['weekly_report_rate_limit']['blocked_until'] - $now;
    http_response_code(429);
    die(json_encode(['error' => 'Too many requests. Please try again in ' . $retryAfter . ' seconds.']));
}

// Clean old requests outside the window
$_SESSION['weekly_report_rate_limit']['requests'] = array_filter(
    $_SESSION['weekly_report_rate_limit']['requests'],
    function($timestamp) use ($now, $RATE_LIMIT_WINDOW) {
        return ($now - $timestamp) < $RATE_LIMIT_WINDOW;
    }
);

// Check if limit exceeded
if (count($_SESSION['weekly_report_rate_limit']['requests']) >= $RATE_LIMIT_MAX_REQUESTS) {
    $_SESSION['weekly_report_rate_limit']['blocked_until'] = $now + $RATE_LIMIT_WINDOW;
    http_response_code(429);
    die(json_encode(['error' => 'Rate limit exceeded. Please try again in ' . $RATE_LIMIT_WINDOW . ' seconds.']));
}

// Record this request
$_SESSION['weekly_report_rate_limit']['requests'][] = $now;

// Check if user is logged in and is admin/super admin/developer
if (empty($_SESSION['logged_in']) || !in_array($_SESSION['position'], ['Admin', 'Super Admin', 'Developer'])) {
    header('Location: ../login.php');
    exit;
}

include __DIR__ . '/function/report.php';
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
    <link rel="stylesheet" href="css/report.css">
    <link rel="stylesheet" href="css/light-theme.css">
    <script src="js/theme.js"></script>
     <link rel="icon" type="image/x-icon" href="../assets/img/profile/jajr-logo.png">
 
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
                            <?php 
                            if ($view_type === 'range') {
                                echo 'Custom Date Range';
                            } else {
                                echo ($view_type === 'weekly') ? 'Weekly' : 'Monthly';
                            }
                            ?> Payroll Report
                        </div>
                        <div class="text-sm text-gray">
                            Admin Panel | 
                            <?php 
                            if ($view_type === 'weekly') {
                                echo "Week $selected_week Report";
                            } elseif ($view_type === 'range') {
                                echo $date_range_label;
                            } else {
                                echo "Monthly Report";
                            }
                            ?>
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
                <div class="view-option <?php echo ($view_type === 'range') ? 'active' : ''; ?>" 
                     onclick="changeView('range')">
                    <i class="fas fa-calendar mr-2"></i> Date Range
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
                    
                    <?php if ($view_type === 'range'): ?>
                    <!-- Date Range Inputs with Search Button -->
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Start Date</label>
                        <input type="date" name="start_date" class="input-field" 
                               value="<?php echo $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days')); ?>">
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-300 mb-2">End Date</label>
                        <input type="date" name="end_date" class="input-field" 
                               value="<?php echo $_GET['end_date'] ?? date('Y-m-d'); ?>">
                    </div>
                    <div class="flex-initial">
                        <label class="block text-sm font-medium text-gray-300 mb-2">&nbsp;</label>
                        <button type="submit" class="btn-secondary h-[38px]">
                            <i class="fas fa-search mr-2"></i>Filter
                        </button>
                    </div>
                    <?php else: ?>
                    <!-- Month/Week Selectors for Weekly/Monthly views -->
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
                    <?php endif; ?>
                    
                    <div class="flex-1 min-w-[220px]">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Search</label>
                        <input type="text" id="employeeSearch" class="input-field" placeholder="Search employee...">
                    </div>
                    
                    <button type="button" onclick="exportToExcel()" class="btn-secondary">
                        <i class="fas fa-file-excel mr-2"></i>Export Excel
                    </button>
                    <button type="button" onclick="openBundlePrintModal()" class="btn-primary" id="bundlePrintBtn" disabled>
                        <i class="fas fa-print mr-2"></i>Bundle Print <span id="bundlePrintCount">(0 pages)</span>
                    </button>
                </form>
                <div class="mb-4 flex items-center gap-2">
                    <span id="selectionCounter" class="text-sm text-gray-400">0 employees selected</span>
                </div>

                <!-- Quick Branch Filter Links -->
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-2">
                        <h4 class="text-sm font-medium text-gray-300">Quick Branch Filter:</h4>
                        <?php if ($total_branch_pages > 1): ?>
                        <span class="text-xs text-gray-400">Page <?php echo $branch_page; ?> of <?php echo $total_branch_pages; ?> (<?php echo $total_branches; ?> branches)</span>
                        <?php endif; ?>
                    </div>
                    <div class="flex flex-wrap gap-2 mb-3">
                        <a href="?view=<?php echo $view_type; ?>&month=<?php echo $selected_month; ?>&week=<?php echo $selected_week; ?>&branch=all&branch_page=1<?php echo ($view_type === 'range' && isset($_GET['start_date'])) ? '&start_date=' . urlencode($_GET['start_date']) . '&end_date=' . urlencode($_GET['end_date']) : ''; ?>" 
                           class="branch-badge all <?php echo ($selected_branch === 'all') ? 'active' : ''; ?>">
                            <i class="fas fa-layer-group mr-1"></i>All Branches
                        </a>
                        <?php foreach ($paginated_branches as $branch): ?>
                            <a href="?view=<?php echo $view_type; ?>&month=<?php echo $selected_month; ?>&week=<?php echo $selected_week; ?>&branch=<?php echo urlencode($branch['id']); ?>&branch_page=<?php echo $branch_page; ?><?php echo ($view_type === 'range' && isset($_GET['start_date'])) ? '&start_date=' . urlencode($_GET['start_date']) . '&end_date=' . urlencode($_GET['end_date']) : ''; ?>" 
                               class="branch-badge <?php echo ($selected_branch === (string)$branch['id']) ? 'active' : ''; ?>">
                                <i class="fas fa-building mr-1"></i><?php echo htmlspecialchars($branch['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Branch Filter Pagination -->
                    <?php if ($total_branch_pages > 1): ?>
                    <div class="flex justify-center items-center gap-2 mt-3">
                        <!-- Previous Page -->
                        <?php if ($branch_page > 1): ?>
                        <a href="?view=<?php echo $view_type; ?>&month=<?php echo $selected_month; ?>&week=<?php echo $selected_week; ?>&branch=<?php echo urlencode($selected_branch); ?>&branch_page=<?php echo $branch_page - 1; ?><?php echo ($view_type === 'range' && isset($_GET['start_date'])) ? '&start_date=' . urlencode($_GET['start_date']) . '&end_date=' . urlencode($_GET['end_date']) : ''; ?>" 
                           class="px-3 py-1 text-xs font-medium text-gray-300 bg-gray-800 border border-gray-600 rounded hover:bg-gray-700 transition-colors">
                            <i class="fas fa-chevron-left mr-1"></i>Prev
                        </a>
                        <?php else: ?>
                        <span class="px-3 py-1 text-xs font-medium text-gray-500 bg-gray-900 border border-gray-700 rounded cursor-not-allowed">
                            <i class="fas fa-chevron-left mr-1"></i>Prev
                        </span>
                        <?php endif; ?>
                        
                        <!-- Page Numbers -->
                        <div class="flex gap-1">
                            <?php
                            $start_page = max(1, $branch_page - 2);
                            $end_page = min($total_branch_pages, $branch_page + 2);
                            
                            if ($start_page > 1) {
                                echo '<a href="?view=' . $view_type . '&month=' . $selected_month . '&week=' . $selected_week . '&branch=' . urlencode($selected_branch) . '&branch_page=1' . (($view_type === 'range' && isset($_GET['start_date'])) ? '&start_date=' . urlencode($_GET['start_date']) . '&end_date=' . urlencode($_GET['end_date']) : '') . '" class="px-2 py-1 text-xs font-medium text-gray-300 bg-gray-800 border border-gray-600 rounded hover:bg-gray-700 transition-colors">1</a>';
                                if ($start_page > 2) {
                                    echo '<span class="px-2 py-1 text-xs text-gray-500">...</span>';
                                }
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++) {
                                if ($i == $branch_page) {
                                    echo '<span class="px-2 py-1 text-xs font-medium text-black bg-yellow-500 border border-yellow-500 rounded">' . $i . '</span>';
                                } else {
                                    echo '<a href="?view=' . $view_type . '&month=' . $selected_month . '&week=' . $selected_week . '&branch=' . urlencode($selected_branch) . '&branch_page=' . $i . (($view_type === 'range' && isset($_GET['start_date'])) ? '&start_date=' . urlencode($_GET['start_date']) . '&end_date=' . urlencode($_GET['end_date']) : '') . '" class="px-2 py-1 text-xs font-medium text-gray-300 bg-gray-800 border border-gray-600 rounded hover:bg-gray-700 transition-colors">' . $i . '</a>';
                                }
                            }
                            
                            if ($end_page < $total_branch_pages) {
                                if ($end_page < $total_branch_pages - 1) {
                                    echo '<span class="px-2 py-1 text-xs text-gray-500">...</span>';
                                }
                                echo '<a href="?view=' . $view_type . '&month=' . $selected_month . '&week=' . $selected_week . '&branch=' . urlencode($selected_branch) . '&branch_page=' . $total_branch_pages . (($view_type === 'range' && isset($_GET['start_date'])) ? '&start_date=' . urlencode($_GET['start_date']) . '&end_date=' . urlencode($_GET['end_date']) : '') . '" class="px-2 py-1 text-xs font-medium text-gray-300 bg-gray-800 border border-gray-600 rounded hover:bg-gray-700 transition-colors">' . $total_branch_pages . '</a>';
                            }
                            ?>
                        </div>
                        
                        <!-- Next Page -->
                        <?php if ($branch_page < $total_branch_pages): ?>
                        <a href="?view=<?php echo $view_type; ?>&month=<?php echo $selected_month; ?>&week=<?php echo $selected_week; ?>&branch=<?php echo urlencode($selected_branch); ?>&branch_page=<?php echo $branch_page + 1; ?><?php echo ($view_type === 'range' && isset($_GET['start_date'])) ? '&start_date=' . urlencode($_GET['start_date']) . '&end_date=' . urlencode($_GET['end_date']) : ''; ?>" 
                           class="px-3 py-1 text-xs font-medium text-gray-300 bg-gray-800 border border-gray-600 rounded hover:bg-gray-700 transition-colors">
                            Next<i class="fas fa-chevron-right ml-1"></i>
                        </a>
                        <?php else: ?>
                        <span class="px-3 py-1 text-xs font-medium text-gray-500 bg-gray-900 border border-gray-700 rounded cursor-not-allowed">
                            Next<i class="fas fa-chevron-right ml-1"></i>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Payroll Table -->
                <div class="report-table overflow-x-auto mb-6">
                    <table class="w-full border-collapse min-w-[1200px]" id="reportTable">
                        <thead>
                            <tr class="bg-gradient-to-r from-yellow-600 to-yellow-800">
                                <th class="px-2 py-3 text-center text-xs font-medium text-white uppercase tracking-wider border-b border-gray-600" rowspan="2" style="width: 40px;">
                                    <input type="checkbox" id="selectAllCheckbox" class="bundle-checkbox" onclick="toggleSelectAll()" title="Select All">
                                </th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-white uppercase tracking-wider border-b border-gray-600" rowspan="2">
                                    Employee
                                </th>
                                <th class="px-2 py-3 text-center text-xs font-medium text-white uppercase tracking-wider border-b border-gray-600 bg-blue-900/20" rowspan="2">
                                    Date
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
                                    Actions
                                </th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-white uppercase tracking-wider border-b border-gray-600" rowspan="2">
                                    Remarks
                                </th>
                            </tr>
                            <tr class="bg-gradient-to-r from-yellow-700 to-yellow-900">
                                <th class="px-2 py-2 text-center text-xs font-medium text-white uppercase border-b border-gray-600">Days</th>
                                <th class="px-2 py-2 text-center text-xs font-medium text-white uppercase border-b border-gray-600">Hrs</th>
                                <th class="px-2 py-2 text-center text-xs font-medium text-white uppercase border-b border-gray-600">OT Hrs</th>
                                <th class="px-2 py-2 text-right text-xs font-medium text-white uppercase border-b border-gray-600">OT Amt</th>
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
                                $allowance = floatval($payroll['performance_allowance'] ?? 0);
                                $gross_plus_allowance = $payroll['gross_pay'] + $allowance;
                                $ca_deduction = 0; // Placeholder for cash advance
                                $sss_loan = floatval($payroll['sss_loan'] ?? 0);
                                $total_deductions = $payroll['sss_deduction'] + $payroll['philhealth_deduction'] + $payroll['pagibig_deduction'] + $ca_deduction + $sss_loan;
                                $take_home = $gross_plus_allowance + $ot_amount - $total_deductions;
                            ?>
                            <tr class="border-b border-gray-700 hover:bg-gray-800/50" data-emp-id="<?php echo $emp_id; ?>">
                                <td class="px-2 py-2 text-center">
                                    <input type="checkbox" class="bundle-checkbox emp-checkbox" value="<?php echo $emp_id; ?>" onchange="updateSelection()" data-emp-name="<?php echo htmlspecialchars($payroll['employee']['last_name'] . ', ' . $payroll['employee']['first_name']); ?>">
                                </td>
                                <td class="px-3 py-2">
                                    <div class="font-medium text-white text-sm">
                                        <?php echo htmlspecialchars(strtoupper($payroll['employee']['last_name'] . ', ' . $payroll['employee']['first_name'])); ?>
                                    </div>
                                </td>
                                <td class="px-2 py-2 text-center text-gray-400">
                                    <?php echo $date_range_label; ?>
                                </td>
                                <td class="px-2 py-2 text-center text-sm">
                                    <input type="number"
                                           name="days_worked_<?php echo $emp_id; ?>"
                                           id="days_worked_<?php echo $emp_id; ?>"
                                           value="<?php echo number_format($payroll['days_worked'], 1, '.', ''); ?>"
                                           min="0"
                                           max="31"
                                           step="0.5"
                                           class="w-16 bg-gray-800 border border-gray-600 rounded px-2 py-1 text-center text-gray-400 focus:border-yellow-500 focus:outline-none days-worked-input"
                                           data-emp-id="<?php echo $emp_id; ?>"
                                           data-daily-rate="<?php echo $payroll['daily_rate']; ?>"
                                           onchange="saveDaysWorked(<?php echo $emp_id; ?>, this.value)">
                                </td>
                                <td class="px-2 py-2 text-center text-sm text-white">
                                    <?php echo number_format($payroll['total_hours'], 0); ?>
                                </td>
                                <td class="px-2 py-2 text-right text-sm text-gray-300">
                                    <?php echo number_format($payroll['daily_rate'], 0); ?>
                                </td>
                                <td class="px-2 py-2 text-right text-sm font-medium text-white">
                                    <?php echo number_format($payroll['gross_pay'], 0); ?>
                                </td>
                                <td class="px-2 py-2 text-center text-sm text-yellow-400">
                                    <?php echo ($ot_hours > 0) ? number_format($ot_hours, 1) : '-'; ?>
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
                                           value="<?php echo number_format($allowance, 2, '.', ''); ?>" 
                                           min="0"
                                           step="0.01"
                                           class="w-20 bg-gray-800 border border-gray-600 rounded px-2 py-1 text-right text-blue-400 focus:border-yellow-500 focus:outline-none allowance-input"
                                           data-emp-id="<?php echo $emp_id; ?>"
                                           onchange="updateCalculations(<?php echo $emp_id; ?>); saveAllowance(<?php echo $emp_id; ?>, this.value);">
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
                                <td class="px-2 py-2 text-right text-sm">
                                    <input type="number"
                                            name="loan_<?php echo $emp_id; ?>"
                                            id="loan_<?php echo $emp_id; ?>"
                                            value="<?php echo number_format($sss_loan, 2, '.', ''); ?>"
                                            min="0"
                                            step="0.01"
                                            class="w-20 bg-gray-800 border border-gray-600 rounded px-2 py-1 text-right text-red-400 focus:border-yellow-500 focus:outline-none loan-input"
                                            data-emp-id="<?php echo $emp_id; ?>"
                                            onchange="updateCalculations(<?php echo $emp_id; ?>); saveLoan(<?php echo $emp_id; ?>, this.value);">
                                </td>
                                <td class="px-2 py-2 text-right text-sm font-medium text-red-400">
                                    <?php echo number_format($total_deductions, 0); ?>
                                </td>
                                <td class="px-3 py-2 text-right text-sm font-bold text-green-400">
                                    <?php echo number_format($take_home, 0); ?>
                                </td>
                                <td class="px-3 py-2 text-center text-sm">
                                    <button type="button" 
                                            onclick="openPayslipModal(<?php echo $emp_id; ?>, '<?php echo htmlspecialchars($payroll['employee']['last_name'] . ', ' . $payroll['employee']['first_name']); ?>', <?php echo $payroll['days_worked']; ?>, <?php echo $payroll['daily_rate']; ?>, <?php echo $payroll['gross_pay']; ?>, <?php echo $ot_hours; ?>, <?php echo $ot_amount; ?>, <?php echo $payroll['sss_deduction']; ?>, <?php echo $payroll['philhealth_deduction']; ?>, <?php echo $payroll['pagibig_deduction']; ?>, <?php echo $total_deductions; ?>, <?php echo $take_home; ?>)"
                                            class="btn-payslip">
                                        <i class="fas fa-file-invoice mr-1"></i>Payslip
                                    </button>
                                </td>
                                <td class="px-3 py-2 text-center text-sm">
                                    <select id="remarks_<?php echo $emp_id; ?>" 
                                            class="remarks-select <?php echo ($payroll['payment_status'] ?? 'Not Paid') === 'Paid' ? 'paid' : 'not-paid'; ?>"
                                            onchange="updatePaymentStatus(<?php echo $emp_id; ?>, this.value)">
                                        <option value="Not Paid" <?php echo ($payroll['payment_status'] ?? 'Not Paid') === 'Not Paid' ? 'selected' : ''; ?>>Not Paid</option>
                                        <option value="Paid" <?php echo ($payroll['payment_status'] ?? 'Not Paid') === 'Paid' ? 'selected' : ''; ?>>Paid</option>
                                    </select>
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
                            $sum_total_deductions = 0; // Sum of all Total columns
                            foreach ($employee_payroll as $payroll) {
                                $emp_ot_hours = $payroll['total_ot_hrs'];
                                $emp_ot_rate = $payroll['daily_rate'] / 8;
                                $total_ot_hours += $emp_ot_hours;
                                $total_ot += $emp_ot_hours * $emp_ot_rate;
                                
                                // Accumulate performance allowance from each employee
                                $total_allowance += floatval($payroll['performance_allowance'] ?? 0);
                                
                                // Use the pre-calculated total_deductions from the payroll array
                                $sum_total_deductions += $payroll['total_deductions'];
                            }
                            $grand_total_deductions = $payroll_totals['total_deductions'] + $total_ca + $total_sss_loan;
                            $grand_take_home = $payroll_totals['total_gross'] + $total_allowance + $total_ot - $grand_total_deductions;
                            ?>
                            <tr class="bg-gray-800 font-bold border-t-2 border-yellow-500" id="totalRow">
                                <td class="px-2 py-3 text-center text-gray-400">-</td>
                                <td class="px-3 py-3 text-white">TOTAL</td>
                                <td class="px-2 py-3 text-center text-gray-400">-</td>
                                <td class="px-2 py-3 text-center text-white" id="totalDays"><?php echo $payroll_totals['total_days']; ?></td>
                                <td class="px-2 py-3 text-center text-gray-400" id="totalHours"><?php echo number_format($payroll_totals['total_hours'], 0); ?></td>
                                <td class="px-2 py-3 text-right text-gray-400">-</td>
                                <td class="px-2 py-3 text-right text-yellow-400" id="totalGross"><?php echo number_format($payroll_totals['total_gross'], 0); ?></td>
                                <td class="px-2 py-3 text-center text-yellow-400" id="totalOTHours"><?php echo number_format($total_ot_hours, 1); ?></td>
                                <td class="px-2 py-3 text-right text-gray-400" id="totalOTAmount"><?php echo number_format($total_ot, 0); ?></td>
                                <td class="px-2 py-3 text-right text-yellow-400" id="totalGrossPlusOT"><?php echo number_format($payroll_totals['total_gross'] + $total_ot, 0); ?></td>
                                <td class="px-2 py-3 text-right text-blue-400" id="totalAllowance"><?php echo number_format($total_allowance, 0); ?></td>
                                <td class="px-2 py-3 text-right text-white" id="totalGrossPlusAllowance"><?php echo number_format($payroll_totals['total_gross'] + $total_allowance + $total_ot, 0); ?></td>
                                <td class="px-2 py-3 text-right text-red-400" id="totalCA"><?php echo ($total_ca > 0) ? number_format($total_ca, 0) : '-'; ?></td>
                                <td class="px-2 py-3 text-right text-red-400">-</td>
                                <td class="px-2 py-3 text-right text-red-400">-</td>
                                <td class="px-2 py-3 text-right text-red-400">-</td>
                                <td class="px-2 py-3 text-right text-red-400">-</td>
                                <td class="px-2 py-3 text-right text-red-400" id="grandTotalDeductions"><?php echo number_format($sum_total_deductions, 0); ?></td>
                                <td class="px-3 py-3 text-right text-green-400" id="grandTakeHome"><?php echo number_format($grand_take_home, 0); ?></td>
                                <td class="px-3 py-3 text-center text-gray-400">-</td>
                                <td class="px-3 py-3 text-center text-gray-400">-</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Payslip Modal -->
    <div id="payslipModal" class="modal-backdrop" style="display: none;">
        <div class="modal-panel payslip-modal">
            <div class="modal-header">
                <h3 class="text-lg font-bold text-yellow-400">
                    <i class="fas fa-file-invoice mr-2"></i>Employee Payslip
                </h3>
                <button type="button" onclick="closePayslipModal()" class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="payslipContent">
                <!-- Payslip content will be dynamically inserted here -->
            </div>
            <div class="modal-footer">
                <button type="button" onclick="printPayslip()" class="btn-primary">
                    <i class="fas fa-print mr-2"></i>Print
                </button>
                <button type="button" onclick="closePayslipModal()" class="btn-secondary">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Bundle Print Modal -->
    <div id="bundlePrintModal" class="modal-backdrop" style="display: none;">
        <div class="modal-panel bundle-modal">
            <div class="modal-header">
                <h3 class="text-lg font-bold text-yellow-400">
                    <i class="fas fa-print mr-2"></i>Bundle Print Preview
                </h3>
                <button type="button" onclick="closeBundlePrintModal()" class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="bundlePrintContent">
                <!-- Bundle print content will be dynamically inserted here -->
            </div>
            <div class="modal-footer">
                <span id="bundlePageInfo" class="text-sm text-gray-400 mr-auto"></span>
                <button type="button" onclick="printBundlePayslips()" class="btn-primary">
                    <i class="fas fa-print mr-2"></i>Print All Pages
                </button>
                <button type="button" onclick="closeBundlePrintModal()" class="btn-secondary">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
    <script src="js/report.js"></script>
    <script>
        // Payslip Modal Functions
        let currentPayslipData = null;
        
        function openPayslipModal(empId, empName, daysWorked, dailyRate, grossPay, otHours, otAmount, sss, philhealth, pagibig, totalDeductions, takeHome) {
            currentPayslipData = {
                empId, empName, daysWorked, dailyRate, grossPay, otHours, otAmount, sss, philhealth, pagibig, totalDeductions, takeHome
            };
            
            const allowance = parseFloat(document.getElementById('allowance_' + empId)?.value || 0);
            const ca = parseFloat(document.getElementById('ca_' + empId)?.value || 0);
            const grossPlusAllowance = grossPay + allowance + otAmount;
            const finalDeductions = totalDeductions + ca;
            const finalTakeHome = grossPlusAllowance - finalDeductions;
            
            const content = document.getElementById('payslipContent');
            content.innerHTML = `
                <div class="payslip-container">
                    <div class="payslip-header">
                        <h4 class="text-white font-bold text-lg">${empName}</h4>
                        <p class="text-gray-400 text-sm">Employee ID: ${empId}</p>
                        <p class="text-gray-400 text-sm">Period: <?php echo $date_range_label; ?></p>
                    </div>
                    <div class="payslip-section">
                        <h5 class="text-yellow-400 font-semibold mb-2">Earnings</h5>
                        <div class="payslip-row">
                            <span class="text-gray-300">Days Worked</span>
                            <span class="text-white">${daysWorked} days</span>
                        </div>
                        <div class="payslip-row">
                            <span class="text-gray-300">Daily Rate</span>
                            <span class="text-white">₱${numberFormat(dailyRate)}</span>
                        </div>
                        <div class="payslip-row">
                            <span class="text-gray-300">Basic Pay</span>
                            <span class="text-white">₱${numberFormat(grossPay)}</span>
                        </div>
                        <div class="payslip-row">
                            <span class="text-gray-300">Overtime (${otHours} hrs)</span>
                            <span class="text-white">₱${numberFormat(otAmount)}</span>
                        </div>
                        ${allowance > 0 ? `<div class="payslip-row"><span class="text-gray-300">Performance Allowance</span><span class="text-white">₱${numberFormat(allowance)}</span></div>` : ''}
                        <div class="payslip-row total">
                            <span class="text-yellow-400 font-bold">Gross Pay</span>
                            <span class="text-yellow-400 font-bold">₱${numberFormat(grossPlusAllowance)}</span>
                        </div>
                    </div>
                    <div class="payslip-section">
                        <h5 class="text-red-400 font-semibold mb-2">Deductions</h5>
                        ${ca > 0 ? `<div class="payslip-row"><span class="text-gray-300">Cash Advance</span><span class="text-red-400">-₱${numberFormat(ca)}</span></div>` : ''}
                        ${sss > 0 ? `<div class="payslip-row"><span class="text-gray-300">SSS</span><span class="text-red-400">-₱${numberFormat(sss)}</span></div>` : ''}
                        ${philhealth > 0 ? `<div class="payslip-row"><span class="text-gray-300">PhilHealth</span><span class="text-red-400">-₱${numberFormat(philhealth)}</span></div>` : ''}
                        ${pagibig > 0 ? `<div class="payslip-row"><span class="text-gray-300">Pag-IBIG</span><span class="text-red-400">-₱${numberFormat(pagibig)}</span></div>` : ''}
                        <div class="payslip-row total">
                            <span class="text-red-400 font-bold">Total Deductions</span>
                            <span class="text-red-400 font-bold">-₱${numberFormat(finalDeductions)}</span>
                        </div>
                    </div>
                    <div class="payslip-footer">
                        <div class="payslip-row grand-total">
                            <span class="text-green-400 font-bold text-lg">NET PAY</span>
                            <span class="text-green-400 font-bold text-lg">₱${numberFormat(finalTakeHome)}</span>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('payslipModal').style.display = 'flex';
        }
        
        function closePayslipModal() {
            document.getElementById('payslipModal').style.display = 'none';
            currentPayslipData = null;
        }
        
        function printPayslip() {
            if (!currentPayslipData) return;
            const printWindow = window.open('', '_blank');
            const content = document.getElementById('payslipContent').innerHTML;
            printWindow.document.write(`
                <html>
                <head>
                    <title>Payslip - ${currentPayslipData.empName}</title>
                    <style>
                        @page {
                            size: 4in 7in;
                            margin: 0.15in;
                        }
                        body { 
                            font-family: Arial, sans-serif; 
                            padding: 0; 
                            margin: 0;
                            background: white;
                            font-size: 9pt;
                            width: 3.7in;
                        }
                        .payslip-container { 
                            width: 3.7in; 
                            background: white; 
                            padding: 0.1in;
                            box-sizing: border-box;
                        }
                        .payslip-header { 
                            text-align: center; 
                            margin-bottom: 8px; 
                            padding-bottom: 6px; 
                            border-bottom: 1px solid #FFD700; 
                        }
                        .payslip-header h4 { font-size: 11pt; margin: 0; }
                        .payslip-header p { font-size: 7pt; margin: 2px 0; color: #666; }
                        .payslip-section { margin-bottom: 8px; }
                        .payslip-row { 
                            display: flex; 
                            justify-content: space-between; 
                            padding: 2px 0;
                            font-size: 8pt;
                        }
                        .payslip-row.total { 
                            border-top: 1px solid #ddd; 
                            margin-top: 4px; 
                            padding-top: 4px;
                            font-weight: bold;
                        }
                        .payslip-row.grand-total { 
                            border-top: 1px solid #4CAF50; 
                            margin-top: 6px; 
                            padding-top: 6px;
                            font-weight: bold;
                            font-size: 9pt;
                        }
                        h5 { margin: 0 0 4px 0; font-size: 9pt; }
                        .signature-section { margin-top: 12px; padding-top: 8px; font-size: 7pt; }
                        .signature-line { border-top: 1px solid #333; width: 1.2in; margin-top: 20px; margin-bottom: 2px; }
                        .signature-label { font-size: 6pt; color: #666; }
                        .signature-row { display: flex; justify-content: space-between; margin-top: 10px; }
                        .signature-box { text-align: center; }
                        .acknowledgment {
                            margin-top: 8px; 
                            font-size: 6pt; 
                            color: #999; 
                            text-align: center;
                            line-height: 1.2;
                        }
                    </style>
                </head>
                <body>
                    ${content}
                    <div class="signature-section">
                        <div class="signature-row">
                            <div class="signature-box">
                                <div class="signature-line"></div>
                                <div class="signature-label">Employee Signature</div>
                            </div>
                            <div class="signature-box">
                                <div class="signature-line"></div>
                                <div class="signature-label">Authorized Signature</div>
                            </div>
                        </div>
                        <div class="acknowledgment">
                            I hereby acknowledge receipt of the above amount and that all deductions are correct.
                        </div>
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }
        
        function numberFormat(num) {
            return num.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        
        // Update Payment Status Function
        function updatePaymentStatus(empId, status) {
            const select = document.getElementById('remarks_' + empId);

            // Get employee name from the row (2nd td contains the name)
            const row = select.closest('tr');
            const empName = row.querySelector('td:nth-child(2) .font-medium').textContent.trim();
            
            // Update visual styling
            select.classList.remove('paid', 'not-paid');
            select.classList.add(status === 'Paid' ? 'paid' : 'not-paid');
            
            // Send AJAX request to update database
            const formData = new FormData();
            formData.append('employee_id', empId);
            formData.append('payment_status', status);
            formData.append('year', <?php echo $year; ?>);
            formData.append('month', <?php echo $month; ?>);
            formData.append('week', <?php echo $selected_week; ?>);
            formData.append('view_type', '<?php echo $view_type; ?>');
            
            fetch('update_payment_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success toast notification
                    showToast(`${empName} has been set to ${status}`, 'success');
                } else {
                    console.error('Failed to update payment status:', data.error);
                    showToast('Failed to update payment status. Please try again.', 'error');
                    // Revert the select if failed
                    select.value = status === 'Paid' ? 'Not Paid' : 'Paid';
                    select.classList.remove('paid', 'not-paid');
                    select.classList.add(select.value === 'Paid' ? 'paid' : 'not-paid');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error updating payment status. Please check your connection.', 'error');
            });
        }
        
        // Toast Notification Function
        function showToast(message, type = 'success') {
            // Remove existing toast if any
            const existingToast = document.querySelector('.toast-notification');
            if (existingToast) {
                existingToast.remove();
            }
            
            // Create toast element
            const toast = document.createElement('div');
            toast.className = `toast-notification ${type}`;
            toast.innerHTML = `
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                <span>${message}</span>
            `;
            
            // Add to body
            document.body.appendChild(toast);
            
            // Trigger animation
            setTimeout(() => toast.classList.add('show'), 10);
            
            // Remove after 3 seconds
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        
        // Save Performance Allowance Function
        function saveAllowance(empId, allowanceValue) {
            const input = document.getElementById('allowance_' + empId);

            // Get employee name from the row (2nd td contains the name)
            const row = input.closest('tr');
            const empName = row.querySelector('td:nth-child(2) .font-medium').textContent.trim();

            const formData = new FormData();
            formData.append('employee_id', empId);
            formData.append('performance_allowance', allowanceValue);
            formData.append('year', <?php echo $year; ?>);
            formData.append('month', <?php echo $month; ?>);
            formData.append('week', <?php echo $selected_week; ?>);
            formData.append('view_type', '<?php echo $view_type; ?>');

            fetch('update_allowance.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(`Performance allowance saved for ${empName}`, 'success');
                } else {
                    console.error('Failed to save allowance:', data.error);
                    showToast('Failed to save allowance. Please try again.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error saving allowance. Please check your connection.', 'error');
            });
        }

        // Update Calculations Function
        function updateCalculations(empId) {
            // This function updates the displayed calculations when inputs change
            // Implementation can be expanded based on needs
            console.log('Calculations updated for employee', empId);
        }

        // Save SSS Loan Function
        function saveLoan(empId, loanValue) {
            const input = document.getElementById('loan_' + empId);
            const row = input.closest('tr');
            const empName = row.querySelector('td:nth-child(2) .font-medium').textContent.trim();

            const formData = new FormData();
            formData.append('employee_id', empId);
            formData.append('sss_loan', loanValue);
            formData.append('year', <?php echo $year; ?>);
            formData.append('month', <?php echo $month; ?>);
            formData.append('week', <?php echo $selected_week; ?>);
            formData.append('view_type', '<?php echo $view_type; ?>');

            fetch('update_loan.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(text => {
                console.log('Raw response:', text);
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        showToast(`SSS Loan saved for ${empName}`, 'success');
                    } else {
                        console.error('Failed to save loan:', data.error);
                        showToast('Failed to save loan: ' + (data.error || 'Unknown error'), 'error');
                    }
                } catch (e) {
                    console.error('Invalid JSON response:', text);
                    showToast('Server error - check console for details', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error saving loan. Please check your connection.', 'error');
            });
        }

        // Save Days Worked Function
        function saveDaysWorked(empId, daysValue) {
            console.log('[saveDaysWorked] START - empId:', empId, 'daysValue:', daysValue);

            const input = document.getElementById('days_worked_' + empId);
            console.log('[saveDaysWorked] Input element:', input);

            const row = input.closest('tr');
            const empName = row.querySelector('td:nth-child(2) .font-medium').textContent.trim();
            const dailyRate = parseFloat(input.getAttribute('data-daily-rate')) || 0;
            const days = parseFloat(daysValue) || 0;

            console.log('[saveDaysWorked] Employee:', empName, 'Daily Rate:', dailyRate, 'Days:', days);

            // Update UI calculations immediately
            const basicPay = dailyRate * days;
            console.log('[saveDaysWorked] Calculated basicPay:', basicPay);

            const grossPayCell = row.querySelector('td:nth-child(7)');
            if (grossPayCell) {
                grossPayCell.textContent = numberFormat(basicPay);
                console.log('[saveDaysWorked] Updated grossPayCell with:', numberFormat(basicPay));
            }

            const formData = new FormData();
            formData.append('employee_id', empId);
            formData.append('days_worked', days);
            formData.append('year', <?php echo $year; ?>);
            formData.append('month', <?php echo $month; ?>);
            formData.append('week', <?php echo $selected_week; ?>);
            formData.append('view_type', '<?php echo $view_type; ?>');
            formData.append('start_date', '<?php echo $start_date; ?>');
            formData.append('end_date', '<?php echo $end_date; ?>');

            console.log('[saveDaysWorked] FormData prepared:', {
                employee_id: empId,
                days_worked: days,
                year: <?php echo $year; ?>,
                month: <?php echo $month; ?>,
                week: <?php echo $selected_week; ?>,
                view_type: '<?php echo $view_type; ?>',
                start_date: '<?php echo $start_date; ?>',
                end_date: '<?php echo $end_date; ?>'
            });

            console.log('[saveDaysWorked] Sending fetch request to update_days_worked.php...');

            fetch('update_days_worked.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('[saveDaysWorked] Response received:', response);
                console.log('[saveDaysWorked] Response status:', response.status);
                console.log('[saveDaysWorked] Response ok:', response.ok);
                return response.json();
            })
            .then(data => {
                console.log('[saveDaysWorked] Response data:', data);
                if (data.success) {
                    console.log('[saveDaysWorked] SUCCESS - Days worked saved, no page reload');
                    showToast(`Days worked saved for ${empName}`, 'success');
                } else {
                    console.error('[saveDaysWorked] FAILED - Server returned error:', data.error, 'Message:', data.message);
                    showToast('Failed to save days worked: ' + (data.message || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                console.error('[saveDaysWorked] CATCH ERROR:', error);
                showToast('Error saving days worked. Please check your connection.', 'error');
            });

            console.log('[saveDaysWorked] END - fetch initiated');
        }

        // Bundle Print Functions
        let selectedEmployees = new Set();

        function toggleSelectAll() {
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            const empCheckboxes = document.querySelectorAll('.emp-checkbox');
            
            empCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
                const empId = checkbox.value;
                if (selectAllCheckbox.checked) {
                    selectedEmployees.add(empId);
                } else {
                    selectedEmployees.delete(empId);
                }
            });
            
            updateSelectionUI();
        }

        function updateSelection() {
            selectedEmployees.clear();
            const empCheckboxes = document.querySelectorAll('.emp-checkbox');

            empCheckboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    selectedEmployees.add(checkbox.value);
                }
            });

            // Update select all checkbox state
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            const allChecked = empCheckboxes.length > 0 && empCheckboxes.length === selectedEmployees.size;
            selectAllCheckbox.checked = allChecked;

            updateSelectionUI();
        }
        window.updateSelection = updateSelection;

        function updateSelectionUI() {
            const count = selectedEmployees.size;
            const pages = Math.ceil(count / 6);
            
            document.getElementById('selectionCounter').textContent = `${count} employees selected`;
            document.getElementById('bundlePrintCount').textContent = `(${pages} page${pages !== 1 ? 's' : ''})`;
            document.getElementById('bundlePrintBtn').disabled = count === 0;
        }

        function openBundlePrintModal() {
            if (selectedEmployees.size === 0) {
                showToast('Please select at least one employee', 'error');
                return;
            }

            const bundleData = collectBundleData();
            const content = document.getElementById('bundlePrintContent');
            const totalPages = Math.ceil(bundleData.length / 6);

            let html = '';

            for (let page = 0; page < totalPages; page++) {
                const pageEmployees = bundleData.slice(page * 6, (page + 1) * 6);
                html += generatePageHTML(pageEmployees, page + 1, totalPages);
            }

            content.innerHTML = html;
            document.getElementById('bundlePageInfo').textContent = `${totalPages} page${totalPages !== 1 ? 's' : ''} (${bundleData.length} employees)`;
            document.getElementById('bundlePrintModal').style.display = 'flex';
        }

        function closeBundlePrintModal() {
            document.getElementById('bundlePrintModal').style.display = 'none';
        }

        function collectBundleData() {
            const data = [];
            const empCheckboxes = document.querySelectorAll('.emp-checkbox:checked');
            
            empCheckboxes.forEach(checkbox => {
                const empId = checkbox.value;
                const empName = checkbox.getAttribute('data-emp-name');
                const row = checkbox.closest('tr');
                
                // Get data from the row cells and inputs
                const cells = row.querySelectorAll('td');
                const allowance = parseFloat(document.getElementById('allowance_' + empId)?.value || 0);
                const ca = parseFloat(document.getElementById('ca_' + empId)?.value || 0);
                const loan = parseFloat(document.getElementById('loan_' + empId)?.value || 0);
                
                // Extract values from cells (skip checkbox and name columns)
                const daysWorked = cells[2]?.textContent?.trim() || '0';
                const dailyRate = cells[5]?.textContent?.trim().replace(/[^0-9.]/g, '') || '0';
                const grossPay = cells[6]?.textContent?.trim().replace(/[^0-9.]/g, '') || '0';
                const otHours = cells[7]?.textContent?.trim() || '0';
                const otAmount = cells[8]?.textContent?.trim().replace(/[^0-9.]/g, '') || '0';
                const sss = cells[13]?.textContent?.trim().replace(/[^0-9.]/g, '') || '0';
                const philhealth = cells[14]?.textContent?.trim().replace(/[^0-9.]/g, '') || '0';
                const pagibig = cells[15]?.textContent?.trim().replace(/[^0-9.]/g, '') || '0';
                const totalDeductions = cells[17]?.textContent?.trim().replace(/[^0-9.]/g, '') || '0';
                const takeHome = cells[18]?.textContent?.trim().replace(/[^0-9.]/g, '') || '0';
                
                data.push({
                    empId,
                    empName,
                    daysWorked: parseFloat(daysWorked) || 0,
                    dailyRate: parseFloat(dailyRate) || 0,
                    grossPay: parseFloat(grossPay) || 0,
                    otHours: parseFloat(otHours) || 0,
                    otAmount: parseFloat(otAmount) || 0,
                    allowance,
                    sss: parseFloat(sss) || 0,
                    philhealth: parseFloat(philhealth) || 0,
                    pagibig: parseFloat(pagibig) || 0,
                    ca,
                    loan,
                    totalDeductions: parseFloat(totalDeductions) || 0,
                    takeHome: parseFloat(takeHome) || 0,
                    period: '<?php echo $date_range_label; ?>'
                });
            });
            
            return data;
        }

        function generatePageHTML(employees, pageNum, totalPages) {
            let payslipsHTML = '';
            
            employees.forEach(emp => {
                const grossWithAllowance = emp.grossPay + emp.allowance + emp.otAmount;
                const finalDeductions = emp.totalDeductions + emp.ca;
                const finalTakeHome = grossWithAllowance - finalDeductions;
                
                payslipsHTML += `
                    <div class="bundle-payslip">
                        <div class="bp-header">
                            <h4 class="bp-name">${emp.empName}</h4>
                            <p class="bp-info">ID: ${emp.empId} | ${emp.period}</p>
                        </div>
                        <div class="bp-section">
                            <h5 class="bp-section-title earnings">EARNINGS</h5>
                            <div class="bp-row">
                                <span>Days:</span>
                                <span>${emp.daysWorked}</span>
                            </div>
                            <div class="bp-row">
                                <span>Rate:</span>
                                <span>₱${numberFormat(emp.dailyRate)}</span>
                            </div>
                            <div class="bp-row">
                                <span>Basic:</span>
                                <span>₱${numberFormat(emp.grossPay)}</span>
                            </div>
                            ${emp.otAmount > 0 ? `
                            <div class="bp-row">
                                <span>OT (${emp.otHours}h):</span>
                                <span>₱${numberFormat(emp.otAmount)}</span>
                            </div>` : ''}
                            ${emp.allowance > 0 ? `
                            <div class="bp-row">
                                <span>Allowance:</span>
                                <span>₱${numberFormat(emp.allowance)}</span>
                            </div>` : ''}
                            <div class="bp-row total">
                                <span>Gross:</span>
                                <span>₱${numberFormat(grossWithAllowance)}</span>
                            </div>
                        </div>
                        <div class="bp-section">
                            <h5 class="bp-section-title deductions">DEDUCTIONS</h5>
                            ${emp.sss > 0 ? `
                            <div class="bp-row">
                                <span>SSS:</span>
                                <span>₱${numberFormat(emp.sss)}</span>
                            </div>` : ''}
                            ${emp.philhealth > 0 ? `
                            <div class="bp-row">
                                <span>PhilHealth:</span>
                                <span>₱${numberFormat(emp.philhealth)}</span>
                            </div>` : ''}
                            ${emp.pagibig > 0 ? `
                            <div class="bp-row">
                                <span>Pag-IBIG:</span>
                                <span>₱${numberFormat(emp.pagibig)}</span>
                            </div>` : ''}
                            ${emp.ca > 0 ? `
                            <div class="bp-row">
                                <span>CA:</span>
                                <span>₱${numberFormat(emp.ca)}</span>
                            </div>` : ''}
                            ${emp.loan > 0 ? `
                            <div class="bp-row">
                                <span>SSS Loan:</span>
                                <span>₱${numberFormat(emp.loan)}</span>
                            </div>` : ''}
                            <div class="bp-row total">
                                <span>Total:</span>
                                <span>₱${numberFormat(finalDeductions)}</span>
                            </div>
                        </div>
                        <div class="bp-footer">
                            <div class="bp-row net">
                                <span>NET PAY:</span>
                                <span>₱${numberFormat(finalTakeHome)}</span>
                            </div>
                        </div>
                        <div class="bp-signatures">
                            <div class="bp-sig-line"></div>
                            <div class="bp-sig-line"></div>
                        </div>
                    </div>
                `;
            });
            
            // Fill empty slots if less than 6 employees
            const emptySlots = 6 - employees.length;
            for (let i = 0; i <emptySlots; i++) {
                payslipsHTML += `<div class="bundle-payslip empty"></div>`;
            }
            
            return `
                <div class="bundle-page">
                    <div class="bundle-page-header">Page ${pageNum} of ${totalPages}</div>
                    <div class="bundle-page-grid">
                        ${payslipsHTML}
                    </div>
                </div>
            `;
        }

        function printBundlePayslips() {
            const content = document.getElementById('bundlePrintContent').innerHTML;
            const printWindow = window.open('', '_blank');
            
            printWindow.document.write(`
                <html>
                <head>
                    <title>Bundle Payslips</title>
                    <style>
                        @page {
                            margin: 5mm;
                            size: auto;
                        }
                        body {
                            font-family: Arial, sans-serif;
                            margin: 0;
                            padding: 0;
                            background: white;
                        }
                        .bundle-page {
                            width: 100%;
                            height: 100vh;
                            page-break-after: always;
                            box-sizing: border-box;
                            padding: 5mm;
                        }
                        .bundle-page:last-child {
                            page-break-after: auto;
                        }
                        .bundle-page-header {
                            text-align: center;
                            font-size: 10pt;
                            color: #666;
                            margin-bottom: 3mm;
                        }
                        .bundle-page-grid {
                            display: grid;
                            grid-template-columns: 1fr 1fr 1fr;
                            grid-template-rows: 1fr 1fr;
                            gap: 2mm;
                            height: calc(100% - 10mm);
                            width: 100%;
                        }
                        .bundle-payslip {
                            border: 1px solid #444;
                            border-radius: 2mm;
                            padding: 2mm;
                            display: flex;
                            flex-direction: column;
                            font-size: 7pt;
                            background: #1a1a1a;
                            color: #fff;
                            box-sizing: border-box;
                            overflow: hidden;
                        }
                        .bundle-payslip.empty {
                            border: 1px dashed #666;
                            background: #f5f5f5;
                        }
                        .bp-header {
                            text-align: center;
                            border-bottom: 1px solid #FFD700;
                            padding-bottom: 1mm;
                            margin-bottom: 1mm;
                        }
                        .bp-name {
                            font-size: 9pt;
                            font-weight: bold;
                            margin: 0;
                            line-height: 1.2;
                            color: #FFD700;
                        }
                        .bp-info {
                            font-size: 6pt;
                            color: #999;
                            margin: 0;
                        }
                        .bp-section {
                            flex: 1;
                        }
                        .bp-section-title {
                            font-size: 7pt;
                            font-weight: bold;
                            margin: 1mm 0 0.5mm 0;
                            text-transform: uppercase;
                        }
                        .bp-section-title.earnings {
                            color: #FFD700;
                        }
                        .bp-section-title.deductions {
                            color: #ef4444;
                        }
                        .bp-row {
                            display: flex;
                            justify-content: space-between;
                            padding: 0.3mm 0;
                            font-size: 6.5pt;
                            line-height: 1.1;
                            color: #ddd;
                        }
                        .bp-row.total {
                            border-top: 1px solid #555;
                            margin-top: 0.5mm;
                            padding-top: 0.5mm;
                            font-weight: bold;
                            color: #fff;
                        }
                        .bp-row.net {
                            font-size: 9pt;
                            font-weight: bold;
                            color: #10b981;
                        }
                        .bp-footer {
                            border-top: 1px solid #10b981;
                            margin-top: 1mm;
                            padding-top: 1mm;
                        }
                        .bp-signatures {
                            display: flex;
                            justify-content: space-between;
                            margin-top: 2mm;
                        }
                        .bp-sig-line {
                            width: 40%;
                            border-top: 1px solid #666;
                            height: 3mm;
                        }
                    </style>
                </head>
                <body>
                    ${content}
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }
    </script>
</body>
</html>