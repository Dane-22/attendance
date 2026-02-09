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
                            $sum_total_deductions = 0; // Sum of all Total columns
                            foreach ($employee_payroll as $payroll) {
                                $emp_ot_hours = $payroll['total_ot_hrs'];
                                $emp_ot_rate = $payroll['daily_rate'] / 8;
                                $total_ot_hours += $emp_ot_hours;
                                $total_ot += $emp_ot_hours * $emp_ot_rate;
                                
                                // Use the pre-calculated total_deductions from the payroll array
                                $sum_total_deductions += $payroll['total_deductions'];
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
                                <td class="px-2 py-3 text-right text-red-400">-</td>
                                <td class="px-2 py-3 text-right text-red-400">-</td>
                                <td class="px-2 py-3 text-right text-red-400">-</td>
                                <td class="px-2 py-3 text-right text-red-400">-</td>
                                <td class="px-2 py-3 text-right text-red-400" id="grandTotalDeductions"><?php echo number_format($sum_total_deductions, 0); ?></td>
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
    <script src="js/report.js"></script>
</body>
</html>