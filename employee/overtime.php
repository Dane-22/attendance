<?php
// employee/overtime.php - Overtime Management Page
require_once __DIR__ . '/../conn/db_connection.php';
require_once __DIR__ . '/../functions.php';
session_start();

// Check if user is logged in and is admin/super admin/developer
if (empty($_SESSION['logged_in']) || !in_array($_SESSION['position'], ['Admin', 'Super Admin', 'Developer'])) {
    header('Location: ../login.php');
    exit;
}

// Get current month and year
$current_month = date('Y-m');
$current_year = date('Y');
$current_month_num = date('m');

// Calculate current week
$current_day = intval(date('d'));
$current_week = ceil($current_day / 7);
if ($current_week > 5) $current_week = 5;

// Handle filters
$selected_month = $_GET['month'] ?? $current_month;
$selected_week = intval($_GET['week'] ?? $current_week);
$view_type = $_GET['view'] ?? 'range';
$selected_branch = $_GET['branch'] ?? 'all';

// Validate week
if ($selected_week < 1 || $selected_week > 5) {
    $selected_week = 1;
}

// Parse selected month
$month_year = explode('-', $selected_month);
$year = $month_year[0];
$month = $month_year[1];
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$has_week_5 = $days_in_month > 28;

// Handle custom date range parameters
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;

// Calculate date ranges
if ($view_type === 'weekly') {
    $week_start_day = 1 + (($selected_week - 1) * 7);
    $week_end_day = min($week_start_day + 6, $days_in_month);
    $start_date = sprintf('%04d-%02d-%02d', $year, $month, $week_start_day);
    $end_date = sprintf('%04d-%02d-%02d', $year, $month, $week_end_day);
    $date_range_label = "Week $selected_week: " . date('M d', strtotime($start_date)) . " - " . date('M d, Y', strtotime($end_date));
} elseif ($view_type === 'range' && $start_date && $end_date) {
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
    $start_date = sprintf('%04d-%02d-01', $year, $month);
    $end_date = sprintf('%04d-%02d-%02d', $year, $month, $days_in_month);
    $date_range_label = "Monthly View: " . date('F Y', strtotime($start_date));
}

// Fetch all branches
$branch_query = "SELECT id, branch_name FROM branches WHERE is_active = 1 ORDER BY branch_name";
$branch_result = mysqli_query($db, $branch_query);
$all_branches_list = [];
while ($branch_row = mysqli_fetch_assoc($branch_result)) {
    $all_branches_list[] = [
        'id' => $branch_row['id'],
        'name' => $branch_row['branch_name']
    ];
}

// Fetch overtime data
$overtime_query = "SELECT a.employee_id, a.attendance_date, a.time_in, a.time_out, a.total_ot_hrs,
                          a.branch_name, e.first_name, e.last_name, e.employee_code, e.daily_rate,
                          r.requested_at, r.approved_at
                   FROM attendance a
                   JOIN employees e ON a.employee_id = e.id
                   LEFT JOIN overtime_requests r ON a.employee_id = r.employee_id 
                       AND a.attendance_date = r.request_date 
                       AND r.status IN ('approved', 'pre-approved')
                   WHERE a.attendance_date BETWEEN ? AND ?
                   AND a.total_ot_hrs > 0
                   AND e.status = 'Active'";

if ($selected_branch !== 'all' && is_numeric($selected_branch)) {
    $overtime_query .= " AND e.branch_id = ?";
}
$overtime_query .= " ORDER BY a.attendance_date DESC, e.last_name, e.first_name";

$stmt = mysqli_prepare($db, $overtime_query);
if ($selected_branch !== 'all' && is_numeric($selected_branch)) {
    mysqli_stmt_bind_param($stmt, 'ssi', $start_date, $end_date, $selected_branch);
} else {
    mysqli_stmt_bind_param($stmt, 'ss', $start_date, $end_date);
}
mysqli_stmt_execute($stmt);
$overtime_result = mysqli_stmt_get_result($stmt);

// Organize overtime data by employee
$employee_overtime = [];
$total_ot_hours = 0;
$total_ot_amount = 0;

while ($row = mysqli_fetch_assoc($overtime_result)) {
    $emp_id = $row['employee_id'];
    $ot_hours = floatval($row['total_ot_hrs']);
    $ot_rate = floatval($row['daily_rate']) / 8;
    $ot_amount = $ot_hours * $ot_rate;
    
    if (!isset($employee_overtime[$emp_id])) {
        $employee_overtime[$emp_id] = [
            'employee' => [
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'employee_code' => $row['employee_code'],
                'daily_rate' => $row['daily_rate']
            ],
            'total_ot_hours' => 0,
            'total_ot_amount' => 0,
            'entries' => []
        ];
    }
    
    $employee_overtime[$emp_id]['total_ot_hours'] += $ot_hours;
    $employee_overtime[$emp_id]['total_ot_amount'] += $ot_amount;
    $employee_overtime[$emp_id]['entries'][] = [
        'date' => $row['attendance_date'],
        'time_in' => $row['time_in'],
        'time_out' => $row['time_out'],
        'ot_hours' => $ot_hours,
        'ot_amount' => $ot_amount,
        'branch' => $row['branch_name'],
        'requested_at' => $row['requested_at'],
        'approved_at' => $row['approved_at']
    ];
    
    $total_ot_hours += $ot_hours;
    $total_ot_amount += $ot_amount;
}

// Sort by last name
uasort($employee_overtime, function($a, $b) {
    return strcmp($a['employee']['last_name'], $b['employee']['last_name']);
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overtime Report - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="css/report.css">
    <link rel="stylesheet" href="css/light-theme.css">
    <script src="js/theme.js"></script>
    <link rel="icon" type="image/x-icon" href="../assets/img/profile/jajr-logo.png">
    <style>
        .ot-card {
            background: transparent;
            border: 1px solid rgba(255, 215, 0, 0.2);
        }
        .ot-header {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
        }
        .employee-row:hover {
            background: rgba(255, 215, 0, 0.1);
        }
        .ot-badge {
            background: linear-gradient(135deg, #FF6B6B 0%, #EE5A5A 100%);
        }
        .summary-card {
            background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
            border-left: 4px solid #FFD700;
        }
        /* Transparent table backgrounds */
        #overtimeTable {
            background: transparent;
        }
        #overtimeTable thead tr {
            background: rgba(31, 41, 55, 0.5) !important;
        }
        #overtimeTable tbody tr {
            background: transparent;
        }
        #overtimeTable tbody tr.employee-row {
            background: transparent;
        }
        #overtimeTable tbody tr.bg-gray-800\/50 {
            background: rgba(31, 41, 55, 0.3) !important;
        }
        #overtimeTable tfoot tr {
            background: rgba(202, 138, 4, 0.5) !important;
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
    <?php 
    if ($view_type === 'range') {
        echo 'Custom Date Range';
    } else {
        echo ($view_type === 'weekly') ? 'Weekly' : 'Monthly';
    }
    ?> Overtime Report
</div>
                        <div class="text-sm text-gray">
                            Admin Panel | <?php echo $date_range_label; ?>
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
<div class="view-toggle mb-4">
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

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="summary-card p-4 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-400">Total Employees with OT</p>
                            <p class="text-2xl font-bold text-white"><?php echo count($employee_overtime); ?></p>
                        </div>
                        <div class="w-12 h-12 rounded-full bg-yellow-500/20 flex items-center justify-center">
                            <i class="fas fa-users text-yellow-500 text-xl"></i>
                        </div>
                    </div>
                </div>
                <div class="summary-card p-4 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-400">Total OT Hours</p>
                            <p class="text-2xl font-bold text-white"><?php echo number_format($total_ot_hours, 1); ?></p>
                        </div>
                        <div class="w-12 h-12 rounded-full bg-red-500/20 flex items-center justify-center">
                            <i class="fas fa-clock text-red-500 text-xl"></i>
                        </div>
                    </div>
                </div>
                <div class="summary-card p-4 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-400">Total OT Amount</p>
                            <p class="text-2xl font-bold text-yellow-400">₱<?php echo number_format($total_ot_amount, 0); ?></p>
                        </div>
                        <div class="w-12 h-12 rounded-full bg-green-500/20 flex items-center justify-center">
                            <i class="fas fa-money-bill-wave text-green-500 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

<!-- Filters -->
<div class="ot-card rounded-lg p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end" id="filterForm">
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
            <label class="block text-sm font-medium text-gray-300 mb-2">&​nbsp;</label>
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

                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Branch</label>
                        <select name="branch" class="input-field" onchange="document.getElementById('filterForm').submit();">
                            <option value="all" <?php echo ($selected_branch === 'all') ? 'selected' : ''; ?>>All Branches</option>
                            <?php foreach ($all_branches_list as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>" <?php echo ($selected_branch === (string)$branch['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($branch['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flex-1 min-w-[220px]">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Search Employee</label>
                        <input type="text" id="employeeSearch" class="input-field" placeholder="Search by name...">
                    </div>
                    
                    <button type="button" onclick="exportToExcel()" class="btn-secondary">
                        <i class="fas fa-file-excel mr-2"></i>Export Excel
                    </button>
                </form>
            </div>

            <!-- Overtime Table -->
            <div class="ot-card rounded-lg overflow-hidden">
                <div class="ot-header p-4">
                    <h2 class="text-lg font-bold text-black">
                        <i class="fas fa-clock mr-2"></i>Overtime Details
                    </h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full" id="overtimeTable">
                        <thead>
                            <tr class="bg-gray-800 text-white">
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase">Employee</th>
                                <th class="px-4 py-3 text-center text-xs font-medium uppercase">Date</th>
                                <th class="px-4 py-3 text-center text-xs font-medium uppercase">Branch</th>
                                <th class="px-4 py-3 text-center text-xs font-medium uppercase">OT Hours</th>
                                <th class="px-4 py-3 text-center text-xs font-medium uppercase">Date Requested</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase">OT Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($employee_overtime)): ?>
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                                    <i class="fas fa-inbox text-4xl mb-2"></i>
                                    <p>No overtime records found for this period</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($employee_overtime as $emp_id => $data): ?>
                                    <?php 
                                    $row_count = count($data['entries']);
                                    $first_entry = true;
                                    ?>
                                    <?php foreach ($data['entries'] as $entry): ?>
                                    <tr class="employee-row border-b border-gray-700" data-employee="<?php echo htmlspecialchars(strtolower($data['employee']['last_name'] . ' ' . $data['employee']['first_name'])); ?>">
                                        <?php if ($first_entry): ?>
                                        <td class="px-4 py-3" rowspan="<?php echo $row_count; ?>">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full bg-yellow-500/20 flex items-center justify-center">
                                                    <span class="text-yellow-500 font-bold">
                                                        <?php echo substr($data['employee']['first_name'], 0, 1) . substr($data['employee']['last_name'], 0, 1); ?>
                                                    </span>
                                                </div>
                                                <div>
                                                    <p class="font-medium text-white">
                                                        <?php echo htmlspecialchars($data['employee']['last_name'] . ', ' . $data['employee']['first_name']); ?>
                                                    </p>
                                                    <p class="text-xs text-gray-400">
                                                        <?php echo htmlspecialchars($data['employee']['employee_code']); ?> | Rate: ₱<?php echo number_format($data['employee']['daily_rate'], 0); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <?php endif; ?>
                                        <td class="px-4 py-3 text-center text-sm text-gray-300">
                                            <?php echo date('M d, Y', strtotime($entry['date'])); ?>
                                        </td>
                                        <td class="px-4 py-3 text-center text-sm text-gray-300">
                                            <span class="px-2 py-1 rounded-full bg-blue-500/20 text-blue-400 text-xs">
                                                <?php echo htmlspecialchars($entry['branch']); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="ot-badge px-3 py-1 rounded-full text-white text-sm font-medium">
                                                <?php echo number_format($entry['ot_hours'], 1); ?> hrs
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center text-sm text-gray-300">
                                            <?php echo $entry['requested_at'] ? date('M d, Y h:i A', strtotime($entry['requested_at'])) : '-'; ?>
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm font-medium text-yellow-400">
                                            ₱<?php echo number_format($entry['ot_amount'], 0); ?>
                                        </td>
                                    </tr>
                                    <?php $first_entry = false; ?>
                                    <?php endforeach; ?>
                                    
                                    <!-- Employee Total Row -->
                                    <tr class="bg-gray-800/50 border-b-2 border-yellow-500/30">
                                        <td colspan="4" class="px-4 py-2 text-right text-sm font-medium text-gray-400">
                                            Total for <?php echo htmlspecialchars($data['employee']['last_name']); ?>
                                        </td>
                                        <td class="px-4 py-2 text-center">
                                            <span class="px-3 py-1 rounded-full bg-yellow-500/20 text-yellow-400 text-sm font-bold">
                                                <?php echo number_format($data['total_ot_hours'], 1); ?> hrs
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-right text-sm font-bold text-yellow-400">
                                            ₱<?php echo number_format($data['total_ot_amount'], 0); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <?php if (!empty($employee_overtime)): ?>
                        <tfoot>
                            <tr class="bg-gradient-to-r from-yellow-600 to-yellow-800 text-white font-bold">
                                <td colspan="4" class="px-4 py-3 text-right">GRAND TOTAL</td>
                                <td class="px-4 py-3 text-center"><?php echo number_format($total_ot_hours, 1); ?> hrs</td>
                                <td class="px-4 py-3 text-right">₱<?php echo number_format($total_ot_amount, 0); ?></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        function changeView(view) {
            document.getElementById('viewInput').value = view;
            document.getElementById('filterForm').submit();
        }

        // Search functionality
        document.getElementById('employeeSearch')?.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('.employee-row');
            
            rows.forEach(row => {
                const employee = row.getAttribute('data-employee');
                if (employee && employee.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Export to Excel
        function exportToExcel() {
            const table = document.getElementById('overtimeTable');
            if (!table) return;
            
            // Create HTML table with enhanced styling for Excel
            let html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            html += '<head><meta charset="utf-8"><style>';
            // Header styling - dark background with white bold text
            html += 'th { font-weight: bold; background-color: #1f2937; color: #ffffff; font-size: 11pt; border: 1px solid #4b5563; padding: 8px; text-align: center; }';
            // Data cell styling
            html += 'td { border: 1px solid #d1d5db; padding: 6px 8px; font-size: 10pt; }';
            // Alternating row colors
            html += 'tr:nth-child(even) { background-color: #f3f4f6; }';
            html += 'tr:nth-child(odd) { background-color: #ffffff; }';
            // Employee name styling
            html += 'td.employee-name { font-weight: 600; color: #111827; }';
            // OT Hours badge styling
            html += 'td.ot-hours { background-color: #fee2e2; color: #dc2626; font-weight: bold; text-align: center; }';
            // OT Amount styling
            html += 'td.ot-amount { color: #059669; font-weight: 600; text-align: right; }';
            // Employee total row styling
            html += 'tr.employee-total { background-color: #fef3c7 !important; font-weight: 600; }';
            html += 'tr.employee-total td { border-top: 2px solid #f59e0b; color: #92400e; }';
            // Grand total row styling
            html += 'tr.grand-total { background-color: #10b981 !important; font-weight: bold; }';
            html += 'tr.grand-total td { color: #ffffff; border: 1px solid #059669; font-size: 11pt; }';
            html += 'tr.grand-total td.ot-hours { background-color: #10b981 !important; color: #ffffff; }';
            html += 'tr.grand-total td.ot-amount { color: #ffffff; }';
            // Table styling
            html += 'table { border-collapse: collapse; width: 100%; font-family: Calibri, Arial, sans-serif; }';
            // Center alignment for date, time, branch
            html += 'td.text-center { text-align: center; }';
            html += 'td.text-right { text-align: right; }';
            html += '</style></head><body><table>';
            
            const rows = table.querySelectorAll('tr');
            let rowIndex = 0;
            
            rows.forEach(row => {
                if (row.style.display === 'none') return;
                
                html += '<tr>';
                let cols = row.querySelectorAll('td, th');
                const isHeader = row.parentElement.tagName.toLowerCase() === 'thead';
                const isTfoot = row.parentElement.tagName.toLowerCase() === 'tfoot';
                const rowClass = row.className || '';
                
                // Reconstruct row with proper classes
                let rowHtml = '<tr';
                if (isTfoot) rowHtml = '<tr class="grand-total"';
                else if (rowClass.includes('bg-gray-800/50')) rowHtml = '<tr class="employee-total"';
                else rowHtml = '<tr';
                rowHtml += '>';
                html = html.replace(/<tr>$/, rowHtml);
                
                cols.forEach((col, index) => {
                    const tag = isHeader ? 'th' : 'td';
                    let cellClass = '';
                    let content = col.innerText.trim().replace(/</g, '&lt;').replace(/>/g, '&gt;');
                    
                    // Add classes based on column type
                    if (!isHeader) {
                        if (index === 0) {
                            cellClass = ' class="employee-name"';
                        } else if (index === 3) {
                            cellClass = ' class="ot-hours text-center"';
                        } else if (index === 5) {
                            cellClass = ' class="ot-amount text-right"';
                        } else if (index >= 1 && index <= 4) {
                            cellClass = ' class="text-center"';
                        }
                    }
                    
                    html += '<' + tag + cellClass + '>' + content + '</' + tag + '>';
                });
                
                html += '</tr>';
                rowIndex++;
            });
            
            html += '</table></body></html>';
            
            const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'Overtime_Report_<?php echo $selected_month; ?>_Week<?php echo $selected_week; ?>.xls';
            link.click();
        }
    </script>
</body>
</html>
