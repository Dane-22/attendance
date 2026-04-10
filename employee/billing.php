<?php
session_start();
require_once '../conn/db_connection.php';
require_once 'function/week_calculator.php';

// Check if user is logged in
if (!isset($_SESSION['employee_id'])) {
    header('Location: ../login.php');
    exit;
}

// Get selected filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'site_salary';

// Get date range
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Trigger daily payroll generation when Generate Report is clicked
if (isset($_GET['generate_report']) && $_GET['generate_report'] === '1') {
    // Use HTTP request to generate daily payroll reports with date range
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    
    // Detect base path (works on both localhost /main/ and production root)
    $scriptPath = dirname($_SERVER['SCRIPT_NAME']); // /main/employee or /employee
    $basePath = str_replace('/employee', '', $scriptPath); // /main or ''
    
    // Call generate_daily_payroll.php to populate daily_payroll_reports with deductions
    $cronUrl = "$protocol://$host$basePath/employee/cron/generate_daily_payroll.php?start_date=$startDate&end_date=$endDate";
    
    // Run via HTTP GET request
    $ch = curl_init($cronUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120); // Increased timeout for date range processing
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Store result in session to display message
    $_SESSION['aggregation_result'] = [
        'success' => $httpCode === 200,
        'output' => $response ?: "HTTP Error: $httpCode"
    ];
    
    // Redirect to remove the generate parameter from URL
    header("Location: billing.php?filter=$filter&start_date=$startDate&end_date=$endDate&aggregated=1");
    exit;
}

$data = [];
$filterTitle = '';

switch ($filter) {
    case 'site_salary':
        $filterTitle = 'Site Salary (Total Salary per Branch)';
        
        // First try daily_payroll_reports
        $sql = "SELECT 
                    COALESCE(b.branch_name, 'Unassigned') as branch_name,
                    COUNT(DISTINCT dpr.employee_id) as employee_count,
                    SUM(dpr.basic_pay) as total_basic_pay,
                    SUM(dpr.ot_amount) as total_ot_pay,
                    SUM(dpr.gross_pay) as total_gross_pay,
                    SUM(dpr.total_deductions) as total_deductions,
                    SUM(dpr.take_home_pay) as total_net_pay
                FROM daily_payroll_reports dpr
                LEFT JOIN employees e ON dpr.employee_id = e.id
                LEFT JOIN branches b ON dpr.branch_id = b.id
                WHERE dpr.report_date BETWEEN ? AND ?
                  AND (b.branch_name IS NULL OR UPPER(b.branch_name) != 'MAIN OFFICE')
                GROUP BY b.branch_name
                ORDER BY b.branch_name";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // If no data, fallback to calculating from attendance
        if (empty($data)) {
            $sql = "SELECT 
                        COALESCE(b.branch_name, 'Unassigned') as branch_name,
                        COUNT(DISTINCT a.employee_id) as employee_count,
                        SUM(e.daily_rate * (CASE WHEN a.time_out IS NOT NULL THEN 1 ELSE 0 END)) as total_basic_pay,
                        SUM((e.daily_rate / 8) * COALESCE(a.total_ot_hrs, 0)) as total_ot_pay,
                        SUM(e.daily_rate * (CASE WHEN a.time_out IS NOT NULL THEN 1 ELSE 0 END)) + SUM((e.daily_rate / 8) * COALESCE(a.total_ot_hrs, 0)) as total_gross_pay,
                        0 as total_deductions,
                        SUM(e.daily_rate * (CASE WHEN a.time_out IS NOT NULL THEN 1 ELSE 0 END)) + SUM((e.daily_rate / 8) * COALESCE(a.total_ot_hrs, 0)) as total_net_pay
                    FROM attendance a
                    LEFT JOIN employees e ON a.employee_id = e.id
                    LEFT JOIN branches b ON a.branch_name = b.branch_name
                    WHERE a.attendance_date BETWEEN ? AND ?
                      AND a.time_out IS NOT NULL
                      AND (b.branch_name IS NULL OR UPPER(b.branch_name) != 'MAIN OFFICE')
                    GROUP BY b.branch_name
                    ORDER BY b.branch_name";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        break;

    case 'office_salary':
        $filterTitle = 'Office Salary (Main Branch Total)';
        
        // First try daily_payroll_reports
        $sql = "SELECT 
                    COALESCE(b.branch_name, 'Unassigned') as branch_name,
                    COUNT(DISTINCT dpr.employee_id) as employee_count,
                    SUM(dpr.basic_pay) as total_basic_pay,
                    SUM(dpr.ot_amount) as total_ot_pay,
                    SUM(dpr.gross_pay) as total_gross_pay,
                    SUM(dpr.total_deductions) as total_deductions,
                    SUM(dpr.take_home_pay) as total_net_pay
                FROM daily_payroll_reports dpr
                LEFT JOIN employees e ON dpr.employee_id = e.id
                LEFT JOIN branches b ON dpr.branch_id = b.id
                WHERE dpr.report_date BETWEEN ? AND ?
                  AND UPPER(b.branch_name) = 'MAIN OFFICE'
                GROUP BY b.branch_name
                ORDER BY b.branch_name";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // If no data, fallback to calculating from attendance
        if (empty($data)) {
            $sql = "SELECT 
                        COALESCE(b.branch_name, 'Unassigned') as branch_name,
                        COUNT(DISTINCT a.employee_id) as employee_count,
                        SUM(e.daily_rate * (CASE WHEN a.time_out IS NOT NULL THEN 1 ELSE 0 END)) as total_basic_pay,
                        SUM((e.daily_rate / 8) * COALESCE(a.total_ot_hrs, 0)) as total_ot_pay,
                        SUM(e.daily_rate * (CASE WHEN a.time_out IS NOT NULL THEN 1 ELSE 0 END)) + SUM((e.daily_rate / 8) * COALESCE(a.total_ot_hrs, 0)) as total_gross_pay,
                        0 as total_deductions,
                        SUM(e.daily_rate * (CASE WHEN a.time_out IS NOT NULL THEN 1 ELSE 0 END)) + SUM((e.daily_rate / 8) * COALESCE(a.total_ot_hrs, 0)) as total_net_pay
                    FROM attendance a
                    LEFT JOIN employees e ON a.employee_id = e.id
                    LEFT JOIN branches b ON a.branch_name = b.branch_name
                    WHERE a.attendance_date BETWEEN ? AND ?
                      AND a.time_out IS NOT NULL
                      AND UPPER(b.branch_name) = 'MAIN OFFICE'
                    GROUP BY b.branch_name
                    ORDER BY b.branch_name";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        break;

    case 'cash_advance':
        $filterTitle = 'Cash Advance (Total per Employee)';
        $sql = "SELECT e.id, 
                       e.employee_code,
                       CONCAT(e.first_name, ' ', COALESCE(e.middle_name, ''), ' ', e.last_name) as full_name,
                       COALESCE(a.branch_name, 'Unassigned') as branch_name,
                       SUM(ca.amount) as total_cash_advance,
                       COUNT(ca.id) as request_count,
                       ca2.status as latest_status
                FROM employees e
                LEFT JOIN (
                    SELECT DISTINCT employee_id, branch_name
                    FROM attendance
                    WHERE attendance_date BETWEEN ? AND ?
                ) a ON e.id = a.employee_id
                LEFT JOIN cash_advances ca ON e.id = ca.employee_id 
                    AND ca.request_date >= ? AND ca.request_date <= ?
                LEFT JOIN (
                    SELECT employee_id, status
                    FROM cash_advances ca1
                    WHERE request_date = (
                        SELECT MAX(request_date) 
                        FROM cash_advances 
                        WHERE employee_id = ca1.employee_id
                    )
                ) ca2 ON e.id = ca2.employee_id
                GROUP BY e.id, e.employee_code, e.first_name, e.middle_name, e.last_name, a.branch_name, ca2.status
                HAVING total_cash_advance > 0
                ORDER BY total_cash_advance DESC";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ssss", $startDate, $endDate, $startDate, $endDate);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        break;

    case 'employer_share':
        $filterTitle = 'Employer Share Contribution (SSS, PhilHealth, Pag-IBIG)';
        $sql = "SELECT 
                    'SSS' as contribution_type,
                    SUM(dpr.sss_deduction) as total_employee_share,
                    SUM(dpr.sss_deduction) * 0.0733 as estimated_employer_share,
                    SUM(dpr.sss_deduction) * 1.0733 as total_contribution,
                    COUNT(DISTINCT dpr.employee_id) as employee_count
                FROM daily_payroll_reports dpr
                JOIN employees e ON dpr.employee_id = e.id
                WHERE dpr.report_date BETWEEN ? AND ? AND dpr.sss_deduction > 0
                  AND e.has_deduction = 1

                UNION ALL

                SELECT 
                    'PhilHealth' as contribution_type,
                    SUM(dpr.philhealth_deduction) as total_employee_share,
                    SUM(dpr.philhealth_deduction) as estimated_employer_share,
                    SUM(dpr.philhealth_deduction) * 2 as total_contribution,
                    COUNT(DISTINCT dpr.employee_id) as employee_count
                FROM daily_payroll_reports dpr
                JOIN employees e ON dpr.employee_id = e.id
                WHERE dpr.report_date BETWEEN ? AND ? AND dpr.philhealth_deduction > 0
                  AND e.has_deduction = 1

                UNION ALL

                SELECT 
                    'Pag-IBIG' as contribution_type,
                    SUM(dpr.pagibig_deduction) as total_employee_share,
                    SUM(dpr.pagibig_deduction) as estimated_employer_share,
                    SUM(dpr.pagibig_deduction) * 2 as total_contribution,
                    COUNT(DISTINCT dpr.employee_id) as employee_count
                FROM daily_payroll_reports dpr
                JOIN employees e ON dpr.employee_id = e.id
                WHERE dpr.report_date BETWEEN ? AND ? AND dpr.pagibig_deduction > 0
                  AND e.has_deduction = 1";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ssssss", $startDate, $endDate, $startDate, $endDate, $startDate, $endDate);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        break;

    case 'employees_with_deductions':
        // Use shared week calculator for consistent week calculations with weekly_report.php
        $year = date('Y');
        $month = date('m');
        
        // Parse end date to determine which week the report period falls into
        $endDateParts = explode('-', $endDate);
        $reportYear = intval($endDateParts[0]);
        $reportMonth = intval($endDateParts[1]);
        $reportEndDay = intval($endDateParts[2]);
        
        // Get week boundaries using shared helper for the report's month
        $weekBoundaries = calculateWorkWeekBoundaries($reportYear, $reportMonth);
        
        // Determine which week the end date falls into (single week like weekly_report)
        $reportWeek = getWeekNumberForDate($reportYear, $reportMonth, $reportEndDay);
        if ($reportWeek == 0) {
            $reportWeek = 1; // Default to week 1 if end date is Sunday
        }
        
        // Cap at week 3 for deductions (matching weekly_report logic)
        $deductionWeek = min($reportWeek, 3);
        
        // Get the single week deduction amount (not cumulative) - matching weekly_report.php
        $weeklyDeductions = getWeeklyGovernmentDeductions($deductionWeek);
        
        // Get the end date of the deduction week for filtering
        $lastDeductionDay = isset($weekBoundaries[$deductionWeek]) ? $weekBoundaries[$deductionWeek]['end'] : 21;
        
        $filterTitle = 'Employees with Government Deductions (Week ' . $deductionWeek . ')';
        
        // Query sums payroll data up to the last deduction day
        $sql = "SELECT 
                    e.id,
                    e.employee_code,
                    CONCAT(e.first_name, ' ', COALESCE(e.middle_name, ''), ' ', e.last_name) as full_name,
                    COALESCE(b.branch_name, 'Unassigned') as branch_name,
                    e.daily_rate,
                    e.position,
                    COALESCE(SUM(CASE WHEN DAY(dpr.report_date) <= ? THEN dpr.days_worked ELSE 0 END), 0) as total_days_worked,
                    COALESCE(SUM(CASE WHEN DAY(dpr.report_date) <= ? THEN dpr.basic_pay ELSE 0 END), 0) as total_basic_pay,
                    COALESCE(SUM(CASE WHEN DAY(dpr.report_date) <= ? THEN dpr.total_deductions ELSE 0 END), 0) as db_deductions,
                    COALESCE(SUM(CASE WHEN DAY(dpr.report_date) <= ? THEN dpr.take_home_pay ELSE 0 END), 0) as total_net_pay
                FROM employees e
                LEFT JOIN branches b ON e.branch_id = b.id
                LEFT JOIN daily_payroll_reports dpr ON e.id = dpr.employee_id 
                    AND dpr.report_date BETWEEN ? AND ?
                WHERE e.has_deduction = 1
                  AND e.status = 'Active'
                GROUP BY e.id, e.employee_code, e.first_name, e.middle_name, e.last_name, 
                         b.branch_name, e.daily_rate, e.position
                ORDER BY b.branch_name, e.last_name, e.first_name";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("iiiiss", $lastDeductionDay, $lastDeductionDay, $lastDeductionDay, $lastDeductionDay, $startDate, $endDate);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Override deductions with weekly calculated values (matching weekly_report.php)
        foreach ($data as &$row) {
            // Use the single week deduction amount from week calculator
            $row['total_deductions'] = $weeklyDeductions['total'];
            // Recalculate net pay with the new deduction amount
            $row['total_net_pay'] = $row['total_basic_pay'] - $weeklyDeductions['total'];
        }
        unset($row);
        break;
}

// Format currency helper
function formatCurrency($amount) {
    return '₱' . number_format($amount ?? 0, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing - JAJR Construction</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/billing.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/theme-variables.css">
    <link rel="stylesheet" href="css/light-theme.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <script src="js/theme.js"></script>
</head>
<body>
    <div class="app-shell">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <div class="billing-container">
        <header class="billing-header">
            <h1>Billing & Payroll Reports</h1>
        </header>

        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <label for="filter">Report Type:</label>
                    <select name="filter" id="filter" onchange="this.form.submit()">
                        <option value="site_salary" <?php echo $filter === 'site_salary' ? 'selected' : ''; ?>>
                            Site Salary (Per Branch)
                        </option>
                        <option value="office_salary" <?php echo $filter === 'office_salary' ? 'selected' : ''; ?>>
                            Office Salary (Main Branch)
                        </option>
                        <option value="cash_advance" <?php echo $filter === 'cash_advance' ? 'selected' : ''; ?>>
                            Cash Advance (Per Employee)
                        </option>
                        <option value="employer_share" <?php echo $filter === 'employer_share' ? 'selected' : ''; ?>>
                            Employer Share Contribution
                        </option>
                        <option value="employees_with_deductions" <?php echo $filter === 'employees_with_deductions' ? 'selected' : ''; ?>>
                            Employees with Deductions
                        </option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="start_date">Start Date:</label>
                    <input type="date" name="start_date" id="start_date" value="<?php echo $startDate; ?>">
                </div>

                <div class="filter-group">
                    <label for="end_date">End Date:</label>
                    <input type="date" name="end_date" id="end_date" value="<?php echo $endDate; ?>">
                </div>

                <button type="submit" name="generate_report" value="1" class="filter-btn">Generate Report</button>
                <button type="button" class="filter-btn print-btn" onclick="openPrintPreview()">
                    <i class="fas fa-print"></i> Print Preview
                </button>
            </form>
        </div>

        <?php if (isset($_SESSION['aggregation_result'])): ?>
            <div class="alert <?php echo $_SESSION['aggregation_result']['success'] ? 'alert-success' : 'alert-danger'; ?>">
                <strong><?php echo $_SESSION['aggregation_result']['success'] ? 'Success!' : 'Error:'; ?></strong>
                Daily payroll generation completed.
                <?php if (!$_SESSION['aggregation_result']['success']): ?>
                    <pre><?php echo htmlspecialchars($_SESSION['aggregation_result']['output']); ?></pre>
                <?php endif; ?>
            </div>
            <?php unset($_SESSION['aggregation_result']); ?>
        <?php endif; ?>

        <div class="report-section">
            <h2><?php echo $filterTitle; ?></h2>
            <p class="date-range">Period: <?php echo date('F d, Y', strtotime($startDate)); ?> - <?php echo date('F d, Y', strtotime($endDate)); ?></p>

            <?php if (empty($data)): ?>
                <div class="no-data">
                    <p>No data found for the selected period.</p>
                </div>
            <?php else: ?>
                <table class="billing-table" data-table-type="<?php echo $filter; ?>">
                    <thead>
                        <?php if ($filter === 'site_salary' || $filter === 'office_salary'): ?>
                            <tr>
                                <th>Branch Name</th>
                                <th>Employee Count</th>
                                <th>Basic Pay</th>
                                <th>OT Pay</th>
                                <th>Gross Pay</th>
                                <th>Total Deductions</th>
                                <th>Net Pay</th>
                            </tr>
                        <?php elseif ($filter === 'cash_advance'): ?>
                            <tr>
                                <th>Employee Code</th>
                                <th>Employee Name</th>
                                <th>Branch</th>
                                <th>Total Cash Advance</th>
                                <th>Request Count</th>
                                <th>Latest Status</th>
                            </tr>
                        <?php elseif ($filter === 'employer_share'): ?>
                            <tr>
                                <th>Contribution Type</th>
                                <th>Employee Count</th>
                                <th>Employee Share</th>
                                <th>Employer Share</th>
                                <th>Total Contribution</th>
                            </tr>
                        <?php elseif ($filter === 'employees_with_deductions'): ?>
                            <tr>
                                <th>Employee Code</th>
                                <th>Employee Name</th>
                                <th>Branch</th>
                                <th>Position</th>
                                <th>Daily Rate</th>
                                <th>Days Worked</th>
                                <th>Basic Pay</th>
                                <th>Total Deductions</th>
                                <th>Net Pay</th>
                            </tr>
                        <?php endif; ?>
                    </thead>
                    <tbody>
                        <?php 
                        $grandTotal = 0;
                        foreach ($data as $row): 
                        ?>
                            <?php if ($filter === 'site_salary' || $filter === 'office_salary'): ?>
                                <tr>
                                    <td>
                                        <span class="branch-link" onclick="openBranchModal('<?php echo htmlspecialchars(addslashes($row['branch_name'] ?? 'N/A'), ENT_QUOTES); ?>')">
                                            <?php echo htmlspecialchars($row['branch_name'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $row['employee_count']; ?></td>
                                    <td class="amount"><?php echo formatCurrency($row['total_basic_pay']); ?></td>
                                    <td class="amount"><?php echo formatCurrency($row['total_ot_pay']); ?></td>
                                    <td class="amount"><?php echo formatCurrency($row['total_gross_pay']); ?></td>
                                    <td class="amount deduction"><?php echo formatCurrency($row['total_deductions']); ?></td>
                                    <td class="amount net"><?php echo formatCurrency($row['total_net_pay']); ?></td>
                                </tr>
                                <?php $grandTotal += ($row['total_net_pay'] ?? 0); ?>
                            <?php elseif ($filter === 'cash_advance'): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['employee_code']); ?></td>
                                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['branch_name'] ?? 'N/A'); ?></td>
                                    <td class="amount"><?php echo formatCurrency($row['total_cash_advance']); ?></td>
                                    <td><?php echo $row['request_count']; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower($row['latest_status'] ?? 'pending'); ?>">
                                            <?php echo $row['latest_status'] ?? 'No Status'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php $grandTotal += ($row['total_cash_advance'] ?? 0); ?>
                            <?php elseif ($filter === 'employer_share'): ?>
                                <tr>
                                    <td><?php echo $row['contribution_type']; ?></td>
                                    <td><?php echo $row['employee_count']; ?></td>
                                    <td class="amount"><?php echo formatCurrency($row['total_employee_share']); ?></td>
                                    <td class="amount"><?php echo formatCurrency($row['estimated_employer_share']); ?></td>
                                    <td class="amount net"><?php echo formatCurrency($row['total_contribution']); ?></td>
                                </tr>
                                <?php $grandTotal += ($row['total_contribution'] ?? 0); ?>
                            <?php elseif ($filter === 'employees_with_deductions'): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['employee_code']); ?></td>
                                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['branch_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['position']); ?></td>
                                    <td class="amount"><?php echo formatCurrency($row['daily_rate']); ?></td>
                                    <td><?php echo $row['total_days_worked']; ?></td>
                                    <td class="amount"><?php echo formatCurrency($row['total_basic_pay']); ?></td>
                                    <td class="amount deduction"><?php echo formatCurrency($row['total_deductions']); ?></td>
                                    <td class="amount net"><?php echo formatCurrency($row['total_net_pay']); ?></td>
                                </tr>
                                <?php $grandTotal += ($row['total_net_pay'] ?? 0); ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                    <?php if ($filter !== 'employer_share'): ?>
                    <tfoot>
                        <tr class="total-row">
                            <?php if ($filter === 'site_salary' || $filter === 'office_salary'): ?>
                                <td colspan="6"><strong>Grand Total Net Pay:</strong></td>
                                <td class="amount net"><strong><?php echo formatCurrency($grandTotal); ?></strong></td>
                            <?php elseif ($filter === 'cash_advance'): ?>
                                <td colspan="3"><strong>Grand Total Cash Advance:</strong></td>
                                <td class="amount"><strong><?php echo formatCurrency($grandTotal); ?></strong></td>
                                <td colspan="2"></td>
                            <?php elseif ($filter === 'employees_with_deductions'): ?>
                                <td colspan="6"><strong>Grand Total Net Pay:</strong></td>
                                <td class="amount deduction"><strong><?php echo formatCurrency(array_sum(array_column($data, 'total_deductions'))); ?></strong></td>
                                <td class="amount net"><strong><?php echo formatCurrency($grandTotal); ?></strong></td>
                            <?php endif; ?>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>
        </div>
    </div>

    <!-- Branch Detail Modal -->
    <div id="branchDetailModal" class="branch-modal">
        <div class="branch-modal-content">
            <div class="branch-modal-header">
                <h2 id="branchModalTitle">Branch Details</h2>
                <button class="close-btn" onclick="closeBranchModal()">&times;</button>
            </div>
            <div class="branch-modal-body">
                <p class="branch-modal-period" id="branchModalPeriod"></p>
                <div id="branchModalLoading" class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
                <div id="branchModalError" class="branch-modal-error" style="display: none;"></div>
                <div id="branchModalContent" style="display: none;">
                    <table class="branch-detail-table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Position</th>
                                <th>Days</th>
                                <th>Basic Pay</th>
                                <th>OT</th>
                                <th>Deductions</th>
                                <th>Net Pay</th>
                            </tr>
                        </thead>
                        <tbody id="branchModalTableBody">
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="2"><strong>Total (<span id="branchModalEmployeeCount">0</span> employees)</strong></td>
                                <td id="branchModalTotalDays">0</td>
                                <td id="branchModalTotalBasic" class="amount"></td>
                                <td id="branchModalTotalOT" class="amount"></td>
                                <td id="branchModalTotalDeductions" class="amount deduction"></td>
                                <td id="branchModalTotalNet" class="amount net"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="branch-modal-footer">
                <button class="filter-btn close-modal-btn" onclick="closeBranchModal()">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>

    <!-- Print Preview Modal -->
    <div id="printModal" class="print-modal">
        <div class="print-modal-content">
            <div class="print-modal-header">
                <h2>Payment Request Form - Print Preview</h2>
                <button class="close-btn" onclick="closePrintPreview()">&times;</button>
            </div>
            <div class="print-modal-body">
                <div class="payment-form" id="paymentForm">
                    <div class="form-header">
                        <div class="company-info">
                            <h1>JAJR CONSTRUCTION</h1>
                            <p>#55 P. Zamora St. Barangay II, San Fernando City, La Union</p>
                            <p>Telephone # (072) 607-1150</p>
                            <p>E-mail Address: jajrconstruction@yahoo.com</p>
                        </div>
                        <div class="form-info">
                            <table class="form-info-table">
                                <tr><td>Ref. PRF:</td><td>2017-01-0111</td></tr>
                                <tr><td>PRF/Year-Month-Seq. No.:</td><td>2026-02-0001</td></tr>
                                <tr><td>Date:</td><td><?php echo date('F d, Y'); ?></td></tr>
                                <tr><td>PO No.:</td><td>_____________</td></tr>
                            </table>
                        </div>
                    </div>
                    
                    <h2 class="form-title">PAYMENT REQUEST FORM</h2>
                    
                    <div class="payee-section">
                        <table class="payee-table">
                            <tr>
                                <td class="label">Payee:</td>
                                <td colspan="3" class="value"><strong>ELAINE MARICRIS T. AGUILAR</strong></td>
                            </tr>
                            <tr>
                                <td class="label">TIN:</td>
                                <td class="value">_____________</td>
                                <td class="label">Address:</td>
                                <td class="value">_____________</td>
                            </tr>
                            <tr>
                                <td class="label">Form of Payment:</td>
                                <td colspan="3">
                                    <span class="checkbox">☐ Check</span>
                                    <span class="checkbox">☐ Bank Transfer</span>
                                    <span class="checkbox">☐ Others</span>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <table class="payment-table">
                        <thead>
                            <tr>
                                <th class="col-particulars">PARTICULARS</th>
                                <th class="col-amount">AMOUNT</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Site Salary Section -->
                            <tr class="section-header">
                                <td colspan="2"><strong>SALARY (SITE)</strong></td>
                            </tr>
                            <?php 
                            $siteTotal = 0;
                            if ($filter === 'site_salary' && !empty($data)): 
                                foreach ($data as $row): 
                                    $siteTotal += ($row['total_net_pay'] ?? 0);
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['branch_name'] ?? 'N/A'); ?></td>
                                <td class="amount-right"><?php echo formatCurrency($row['total_net_pay']); ?></td>
                            </tr>
                            <?php endforeach; endif; ?>
                            <?php if ($filter !== 'site_salary'): ?>
                            <tr><td colspan="2" class="no-data-cell">-- Select 'Site Salary' filter to view data --</td></tr>
                            <?php endif; ?>

                            <!-- Office Salary Section -->
                            <tr class="section-header">
                                <td colspan="2"><strong>OFFICE SALARY</strong></td>
                            </tr>
                            <?php 
                            $officeTotal = 0;
                            if ($filter === 'office_salary' && !empty($data)): 
                                foreach ($data as $row): 
                                    $officeTotal += ($row['total_net_pay'] ?? 0);
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['branch_name'] ?? 'N/A'); ?></td>
                                <td class="amount-right"><?php echo formatCurrency($row['total_net_pay']); ?></td>
                            </tr>
                            <?php endforeach; endif; ?>
                            <?php if ($filter !== 'office_salary'): ?>
                            <tr><td colspan="2" class="no-data-cell">-- Select 'Office Salary' filter to view data --</td></tr>
                            <?php endif; ?>

                            <!-- Cash Advance Section -->
                            <tr class="section-header">
                                <td colspan="2"><strong>CASH ADVANCE</strong></td>
                            </tr>
                            <?php 
                            $cashAdvanceTotal = 0;
                            if ($filter === 'cash_advance' && !empty($data)): 
                                foreach ($data as $row): 
                                    $cashAdvanceTotal += ($row['total_cash_advance'] ?? 0);
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td class="amount-right"><?php echo formatCurrency($row['total_cash_advance']); ?></td>
                            </tr>
                            <?php endforeach; endif; ?>
                            <?php if ($filter !== 'cash_advance'): ?>
                            <tr><td colspan="2" class="no-data-cell">-- Select 'Cash Advance' filter to view data --</td></tr>
                            <?php endif; ?>

                            <!-- Employer Share Contribution Section -->
                            <tr class="section-header">
                                <td colspan="2"><strong>EMPLOYER SHARE CONTRIBUTION</strong></td>
                            </tr>
                            <?php 
                            $employerShareTotal = 0;
                            if ($filter === 'employer_share' && !empty($data)): 
                                foreach ($data as $row): 
                                    $employerShareTotal += ($row['total_contribution'] ?? 0);
                            ?>
                            <tr>
                                <td><?php echo $row['contribution_type']; ?> EMPLOYER CONTRIBUTION 1st week</td>
                                <td class="amount-right"><?php echo formatCurrency($row['total_contribution']); ?></td>
                            </tr>
                            <?php endforeach; endif; ?>
                            <?php if ($filter !== 'employer_share'): ?>
                            <tr><td colspan="2" class="no-data-cell">-- Select 'Employer Share' filter to view data --</td></tr>
                            <?php endif; ?>

                            <!-- Total Row -->
                            <tr class="total-row">
                                <td><strong>Total</strong></td>
                                <td class="amount-right"><strong>
                                    <?php 
                                    $grandTotal = $siteTotal + $officeTotal + $cashAdvanceTotal + $employerShareTotal;
                                    echo formatCurrency($grandTotal); 
                                    ?>
                                </strong></td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="signature-section">
                        <table class="signature-table">
                            <tr>
                                <td class="signature-box">
                                    <div class="signature-label">Prepared by:</div>
                                    <div class="signature-line">_________________________</div>
                                    <div class="signature-name">Accounting Staff</div>
                                </td>
                                <td class="signature-box">
                                    <div class="signature-label">Reviewed by:</div>
                                    <div class="signature-line">_________________________</div>
                                    <div class="signature-name">Accountant</div>
                                </td>
                                <td class="signature-box">
                                    <div class="signature-label">Approved by:</div>
                                    <div class="signature-line">_________________________</div>
                                    <div class="signature-name">President</div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="print-modal-footer">
                <button class="filter-btn" onclick="printPaymentForm()">
                    <i class="fas fa-print"></i> Print
                </button>
                <button class="filter-btn close-modal-btn" onclick="closePrintPreview()">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>

    <script>
        // Auto-submit form when filter changes
        document.getElementById('filter').addEventListener('change', function() {
            this.form.submit();
        });

        // Print Preview Functions
        function openPrintPreview() {
            document.getElementById('printModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closePrintPreview() {
            document.getElementById('printModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function printPaymentForm() {
            var printContent = document.getElementById('paymentForm').innerHTML;
            var originalContent = document.body.innerHTML;
            
            document.body.innerHTML = '<div class="payment-form">' + printContent + '</div>';
            window.print();
            document.body.innerHTML = originalContent;
            
            // Re-attach event listeners after restoring content
            document.getElementById('filter').addEventListener('change', function() {
                this.form.submit();
            });
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById('printModal');
            if (event.target == modal) {
                closePrintPreview();
            }
            var branchModal = document.getElementById('branchDetailModal');
            if (event.target == branchModal) {
                closeBranchModal();
            }
        }

        // Branch Modal Functions
        const currentDateRange = {
            start: '<?php echo $startDate; ?>',
            end: '<?php echo $endDate; ?>'
        };

        function formatCurrency(amount) {
            return '₱' + parseFloat(amount || 0).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        }

        function openBranchModal(branchName) {
            const modal = document.getElementById('branchDetailModal');
            const loading = document.getElementById('branchModalLoading');
            const content = document.getElementById('branchModalContent');
            const error = document.getElementById('branchModalError');
            const title = document.getElementById('branchModalTitle');
            const period = document.getElementById('branchModalPeriod');

            // Show modal
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';

            // Reset state
            loading.style.display = 'block';
            content.style.display = 'none';
            error.style.display = 'none';

            // Set title and period
            title.textContent = branchName + ' - Employee Details';
            const startDate = new Date(currentDateRange.start);
            const endDate = new Date(currentDateRange.end);
            period.textContent = 'Period: ' + startDate.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) + 
                                 ' - ' + endDate.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });

            // Fetch data
            fetch('api/get_branch_employees.php?branch_name=' + encodeURIComponent(branchName) + 
                  '&start_date=' + encodeURIComponent(currentDateRange.start) + 
                  '&end_date=' + encodeURIComponent(currentDateRange.end))
                .then(response => response.json())
                .then(data => {
                    loading.style.display = 'none';
                    if (data.error) {
                        error.textContent = data.error;
                        error.style.display = 'block';
                    } else {
                        renderBranchEmployees(data);
                        content.style.display = 'block';
                    }
                })
                .catch(err => {
                    loading.style.display = 'none';
                    error.textContent = 'Failed to load data. Please try again.';
                    error.style.display = 'block';
                    console.error('Error fetching branch data:', err);
                });
        }

        function renderBranchEmployees(data) {
            const tbody = document.getElementById('branchModalTableBody');
            const employees = data.employees || [];
            const totals = data.totals || {};

            // Clear existing content
            tbody.innerHTML = '';

            // Render employee rows
            employees.forEach(emp => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><strong>${escapeHtml(emp.full_name)}</strong><br><small>${escapeHtml(emp.employee_code)}</small></td>
                    <td>${escapeHtml(emp.position || 'N/A')}</td>
                    <td>${emp.days_worked || 0}</td>
                    <td class="amount">${formatCurrency(emp.basic_pay)}</td>
                    <td class="amount">${formatCurrency(emp.ot_amount)}</td>
                    <td class="amount deduction">${formatCurrency(emp.total_deductions)}</td>
                    <td class="amount net">${formatCurrency(emp.take_home_pay)}</td>
                `;
                tbody.appendChild(row);
            });

            // Update totals
            document.getElementById('branchModalEmployeeCount').textContent = totals.employee_count || 0;
            document.getElementById('branchModalTotalDays').textContent = totals.total_days_worked || 0;
            document.getElementById('branchModalTotalBasic').textContent = formatCurrency(totals.total_basic_pay);
            document.getElementById('branchModalTotalOT').textContent = formatCurrency(totals.total_ot_amount);
            document.getElementById('branchModalTotalDeductions').textContent = formatCurrency(totals.total_deductions);
            document.getElementById('branchModalTotalNet').textContent = formatCurrency(totals.total_take_home);
        }

        function closeBranchModal() {
            document.getElementById('branchDetailModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Close modal on ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeBranchModal();
            }
        });
    </script>
</body>
</html>
