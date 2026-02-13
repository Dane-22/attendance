<?php
session_start();
require_once '../conn/db_connection.php';

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

$data = [];
$filterTitle = '';

switch ($filter) {
    case 'site_salary':
        $filterTitle = 'Site Salary (Total Salary per Branch)';
        $sql = "SELECT 
                    COALESCE(a.branch_name, 'Unassigned') as branch_name,
                    COUNT(DISTINCT e.id) as employee_count,
                    SUM(pr.basic_pay) as total_basic_pay,
                    SUM(pr.ot_pay) as total_ot_pay,
                    SUM(pr.gross_pay) as total_gross_pay,
                    SUM(pr.total_deductions) as total_deductions,
                    SUM(pr.net_pay) as total_net_pay
                FROM employees e
                LEFT JOIN (
                    SELECT DISTINCT employee_id, branch_name
                    FROM attendance
                    WHERE attendance_date BETWEEN ? AND ?
                ) a ON e.id = a.employee_id
                LEFT JOIN payroll_records pr ON e.id = pr.employee_id 
                    AND pr.pay_period_start >= ? AND pr.pay_period_end <= ?
                WHERE COALESCE(a.branch_name, '') != 'Main Branch'
                GROUP BY a.branch_name
                ORDER BY a.branch_name";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ssss", $startDate, $endDate, $startDate, $endDate);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        break;

    case 'office_salary':
        $filterTitle = 'Office Salary (Main Branch Total)';
        $sql = "SELECT 
                    COALESCE(a.branch_name, 'Unassigned') as branch_name,
                    COUNT(DISTINCT e.id) as employee_count,
                    SUM(pr.basic_pay) as total_basic_pay,
                    SUM(pr.ot_pay) as total_ot_pay,
                    SUM(pr.gross_pay) as total_gross_pay,
                    SUM(pr.total_deductions) as total_deductions,
                    SUM(pr.net_pay) as total_net_pay
                FROM employees e
                LEFT JOIN (
                    SELECT DISTINCT employee_id, branch_name
                    FROM attendance
                    WHERE attendance_date BETWEEN ? AND ?
                ) a ON e.id = a.employee_id
                LEFT JOIN payroll_records pr ON e.id = pr.employee_id 
                    AND pr.pay_period_start >= ? AND pr.pay_period_end <= ?
                WHERE COALESCE(a.branch_name, '') = 'Main Branch'
                GROUP BY a.branch_name
                ORDER BY a.branch_name";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ssss", $startDate, $endDate, $startDate, $endDate);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
                    SUM(pr.sss_deduction) as total_employee_share,
                    SUM(pr.sss_deduction) * 0.0733 as estimated_employer_share,
                    SUM(pr.sss_deduction) * 1.0733 as total_contribution,
                    COUNT(DISTINCT pr.employee_id) as employee_count
                FROM payroll_records pr
                WHERE pr.pay_period_start >= ? AND pr.pay_period_end <= ? AND pr.sss_deduction > 0
                
                UNION ALL
                
                SELECT 
                    'PhilHealth' as contribution_type,
                    SUM(pr.philhealth_deduction) as total_employee_share,
                    SUM(pr.philhealth_deduction) as estimated_employer_share,
                    SUM(pr.philhealth_deduction) * 2 as total_contribution,
                    COUNT(DISTINCT pr.employee_id) as employee_count
                FROM payroll_records pr
                WHERE pr.pay_period_start >= ? AND pr.pay_period_end <= ? AND pr.philhealth_deduction > 0
                
                UNION ALL
                
                SELECT 
                    'Pag-IBIG' as contribution_type,
                    SUM(pr.pagibig_deduction) as total_employee_share,
                    SUM(pr.pagibig_deduction) as estimated_employer_share,
                    SUM(pr.pagibig_deduction) * 2 as total_contribution,
                    COUNT(DISTINCT pr.employee_id) as employee_count
                FROM payroll_records pr
                WHERE pr.pay_period_start >= ? AND pr.pay_period_end <= ? AND pr.pagibig_deduction > 0";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ssssss", $startDate, $endDate, $startDate, $endDate, $startDate, $endDate);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
    <link rel="stylesheet" href="css/billing.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
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

                <button type="submit" class="filter-btn">Generate Report</button>
                <button type="button" class="filter-btn print-btn" onclick="openPrintPreview()">
                    <i class="fas fa-print"></i> Print Preview
                </button>
            </form>
        </div>

        <div class="report-section">
            <h2><?php echo $filterTitle; ?></h2>
            <p class="date-range">Period: <?php echo date('F d, Y', strtotime($startDate)); ?> - <?php echo date('F d, Y', strtotime($endDate)); ?></p>

            <?php if (empty($data)): ?>
                <div class="no-data">
                    <p>No data found for the selected period.</p>
                </div>
            <?php else: ?>
                <table class="billing-table">
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
                        <?php endif; ?>
                    </thead>
                    <tbody>
                        <?php 
                        $grandTotal = 0;
                        foreach ($data as $row): 
                        ?>
                            <?php if ($filter === 'site_salary' || $filter === 'office_salary'): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['branch_name'] ?? 'N/A'); ?></td>
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
        }
    </script>
</body>
</html>
