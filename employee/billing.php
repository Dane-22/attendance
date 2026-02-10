<?php
// employee/select_employee.php
session_start();

// ===== SET PHILIPPINE TIME ZONE =====
date_default_timezone_set('Asia/Manila'); // Philippine Time (UTC+8)

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Check if this is an AJAX request
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Session expired. Please refresh the page and login again.']);
        exit();
    } else {
        header('Location: ../login.php');
        exit();
    }
}

require('../conn/db_connection.php');
require_once __DIR__ . '/../functions.php';
require('function/billing_function.php');

// Government deduction constants (monthly)
$MONTHLY_PHILHEALTH = 250.00;
$MONTHLY_SSS = 450.00;
$MONTHLY_PAGIBIG = 200.00;

// Determine current payroll week of the month (Week 1 to Week 4)
$dayOfMonth = (int)date('j');
$currentPayrollWeek = (int)ceil($dayOfMonth / 7);
if ($currentPayrollWeek > 4) {
    $currentPayrollWeek = 4;
}

// Get current week date range (within the current month)
$monthStart = date('Y-m-01');
$weekStart = date('Y-m-d', strtotime($monthStart . ' +' . (($currentPayrollWeek - 1) * 7) . ' days'));
$weekEndCandidate = date('Y-m-d', strtotime($weekStart . ' +6 days'));
$monthEnd = date('Y-m-t');
$weekEnd = (strtotime($weekEndCandidate) > strtotime($monthEnd)) ? $monthEnd : $weekEndCandidate;

// Weekly deductions logic
if ($currentPayrollWeek === 4) {
    $weeklySss = 0.00;
    $weeklyPhilhealth = 0.00;
    $weeklyPagibig = 0.00;
} else {
    $weeklySss = $MONTHLY_SSS / 3;
    $weeklyPhilhealth = $MONTHLY_PHILHEALTH / 3;
    $weeklyPagibig = $MONTHLY_PAGIBIG / 3;
}

// Activity logging: payroll viewed
@logActivity(
    $db,
    'Viewed Payroll',
    'Viewed payroll for Week ' . $currentPayrollWeek . ' (' . $weekStart . ' to ' . $weekEnd . ')'
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Billing System | Payroll Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="../assets/img/profile/jajr-logo.png">
    <link rel="stylesheet" href="css/billing.css">
    <link rel="stylesheet" href="css/light-theme.css">
    <script src="js/theme.js"></script>
</head>
<body>
    <div class="app-shell">
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <main class="main-content">
            <!-- Header -->
            <div class="header-card">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                    <div class="header-left">
                        <div class="header-icon">
                            <i class="fas fa-calculator"></i>
                        </div>
                        <div class="header-text">
                            <div class="welcome">Employee Billing System</div>
                            <div class="header-subtitle">
                                <i class="fas fa-user-shield me-2"></i>
                                <?php echo isset($_SESSION['position']) ? $_SESSION['position'] . ' Panel' : 'Employee Panel'; ?>
                            </div>
                        </div>
                    </div>
                    <div class="date-display">
                        <i class="fas fa-calendar-alt"></i>
                        <?php echo date('F d, Y'); ?>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="badge" style="background: linear-gradient(135deg, var(--gold), var(--gold-dark)); color: var(--black); font-weight: 700; padding: 10px 14px; border-radius: 10px;">
                        Current Payroll Week: <?php echo (int)$currentPayrollWeek; ?>
                    </span>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value">
                        <?php 
                            $totalEmployees = $employees->num_rows;
                            echo $totalEmployees;
                        ?>
                    </div>
                    <div class="stat-label">Total Employees</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-value">
                        <?php
                            $totalMonthly = 0;
                            $employees->data_seek(0);
                            while ($emp = $employees->fetch_assoc()) {
                                $totalMonthly += getMonthlySalary($emp['daily_rate']);
                            }
                            echo "₱" . number_format($totalMonthly, 2);
                        ?>
                    </div>
                    <div class="stat-label">Total Monthly Salary</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-week"></i>
                    </div>
                    <div class="stat-value">
                        <?php
                            $employees->data_seek(0);
                            $totalWeekly = 0;
                            while ($emp = $employees->fetch_assoc()) {
                                $totalWeekly += getWeeklySalary($emp['daily_rate']);
                            }
                            echo "₱" . number_format($totalWeekly, 2);
                        ?>
                    </div>
                    <div class="stat-label">Total Weekly Salary</div>
                </div>
            </div>

            <!-- Payroll Table -->
            <div class="employee-table-container">
                <div class="table-header">
                    <div class="table-title">
                        <i class="fas fa-file-invoice-dollar me-2"></i>
                        Weekly Payroll (<?php echo htmlspecialchars($weekStart); ?> to <?php echo htmlspecialchars($weekEnd); ?>)
                    </div>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="employeeSearch" placeholder="Search employees..." onkeyup="searchEmployees()">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Employee Name</th>
                                <th>Gross Pay</th>
                                <th>SSS Deduction</th>
                                <th>PhilHealth Deduction</th>
                                <th>Pag-IBIG Deduction</th>
                                <th>Net Pay</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="employeeTableBody">
                            <?php
                                $payrollSql = "
                                    SELECT
                                        e.id,
                                        e.first_name,
                                        e.last_name,
                                        e.employee_code,
                                        e.daily_rate,
                                        (
                                            IFNULL(
                                                SUM(
                                                    CASE
                                                        WHEN a.time_in IS NOT NULL AND a.time_out IS NOT NULL
                                                            THEN TIME_TO_SEC(TIMEDIFF(a.time_out, a.time_in))
                                                        ELSE 0
                                                    END
                                                ),
                                                0
                                            ) / 3600
                                        ) + IFNULL(SUM(CAST(NULLIF(a.total_ot_hrs, '') AS DECIMAL(10,2))), 0) AS total_hours
                                    FROM employees e
                                    LEFT JOIN attendance a
                                        ON a.employee_id = e.id
                                        AND a.attendance_date BETWEEN '{$weekStart}' AND '{$weekEnd}'
                                        AND DAYOFWEEK(a.attendance_date) BETWEEN 2 AND 7
                                    WHERE e.status = 'Active'
                                    GROUP BY e.id
                                    ORDER BY e.last_name ASC, e.first_name ASC
                                ";

                                $payrollResult = mysqli_query($db, $payrollSql);
                                if ($payrollResult) {
                                    while ($row = mysqli_fetch_assoc($payrollResult)) {
                                        $empName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                                        $empNameJs = json_encode($empName);
                                        $grossPay = (float)$row['daily_rate'] * (float)$row['total_hours'];

                                        $sssDeduction = (float)$weeklySss;
                                        $philhealthDeduction = (float)$weeklyPhilhealth;
                                        $pagibigDeduction = (float)$weeklyPagibig;

                                        $netPay = $grossPay - $sssDeduction - $philhealthDeduction - $pagibigDeduction;
                            ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-placeholder me-3">
                                                        <div style="width: 36px; height: 36px; background: linear-gradient(135deg, var(--gold), var(--gold-dark)); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--black); font-weight: bold;">
                                                            <?php echo strtoupper(substr($row['first_name'] ?? '', 0, 1)); ?>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <div class="fw-medium"><?php echo htmlspecialchars($empName); ?></div>
                                                        <div class="text-muted small"><?php echo htmlspecialchars($row['employee_code'] ?? ''); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-dark p-2">
                                                    ₱<?php echo number_format($grossPay, 2); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-danger p-2">
                                                    -₱<?php echo number_format($sssDeduction, 2); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-danger p-2">
                                                    -₱<?php echo number_format($philhealthDeduction, 2); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-danger p-2">
                                                    -₱<?php echo number_format($pagibigDeduction, 2); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success p-2">
                                                    ₱<?php echo number_format($netPay, 2); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn-gold" onclick='openBillingModal(<?php echo (int)$row['id']; ?>, <?php echo htmlspecialchars($empNameJs, ENT_QUOTES, 'UTF-8'); ?>, <?php echo (float)$row['daily_rate']; ?>, 0, 0)'>
                                                    <i class="fas fa-receipt me-2"></i>
                                                    View Receipt
                                                </button>
                                            </td>
                                        </tr>
                            <?php
                                    }
                                }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Billing Modal -->
    <div class="modal fade" id="billingModal" tabindex="-1" aria-labelledby="billingModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-calculator me-2"></i>
                        Billing Details for <span id="empName" class="text-warning"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Loading Spinner -->
                    <div id="loadingSpinner" class="loading-spinner">
                        <div class="spinner"></div>
                        <p>Loading billing information...</p>
                    </div>
                    
                    <!-- Content -->
                    <div id="billingContent" style="display: none;">
                        <!-- Performance Editor Section -->
                        <div class="performance-editor mb-4">
                            <h6 class="mb-3">
                                <i class="fas fa-edit me-2"></i>
                                Performance Adjustment (Editable by Supervisor)
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label small text-white">Performance Score (%)</label>
                                        <input type="number" id="performanceScore" class="form-control bg-dark border-light text-white" 
                                               min="0" max="100" step="1" value="85">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label small text-white">Performance Bonus/Deduction</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-dark border-light">₱</span>
                                            <input type="number" id="performanceBonus" class="form-control bg-dark border-light text-white" 
                                                   step="0.01" value="0">
                                        </div>
                                        <div class="form-text text-muted small">Positive for bonus, negative for deduction</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label small text-white">Remarks/Notes</label>
                                        <textarea id="performanceRemarks" class="form-control bg-dark border-light text-white" 
                                                  rows="2" placeholder="Optional notes..."></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-gold" onclick="applyPerformance()">
                                    <i class="fas fa-check me-1"></i> Apply Changes
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" onclick="resetPerformance()">
                                    <i class="fas fa-undo me-1"></i> Reset
                                </button>
                            </div>
                        </div>
                        
                        <!-- View Type Selector -->
                        <div class="view-type-selector">
                            <button class="view-type-btn active" onclick="changeViewType('weekly')">
                                <i class="fas fa-calendar-week me-2"></i>
                                Weekly
                            </button>
                            <button class="view-type-btn" onclick="changeViewType('monthly')">
                                <i class="fas fa-calendar-alt me-2"></i>
                                Monthly
                            </button>
                        </div>
                        
                        <!-- Digital Receipt -->
                        <div id="digitalReceipt" class="receipt-container">
                            <!-- Receipt content will be loaded here -->
                        </div>
                        
                        <!-- Additional Details -->
                        <div id="additionalDetails" style="display: none;">
                            <h6 class="mb-3">
                                <i class="fas fa-list-ul me-2"></i>
                                Detailed Attendance Breakdown
                            </h6>
                            <div id="attendanceDetails" class="table-responsive"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn-gold" onclick="printReceipt()">
                        <i class="fas fa-print me-2"></i>
                        Print Receipt
                    </button>
                    <button class="btn-outline-gold" onclick="toggleDetails()" id="detailsBtn">
                        <i class="fas fa-eye me-2"></i>
                        View Details
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
   <script src="js/billing.js"></script>
</body>
</html>