<?php
// employee/select_employee.php
session_start();

// ===== SET PHILIPPINE TIME ZONE =====
date_default_timezone_set('Asia/Manila'); // Philippine Time (UTC+8)

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

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

// Create payroll_payments table if not exists
$createPaymentsTable = "CREATE TABLE IF NOT EXISTS payroll_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    payroll_week INT NOT NULL,
    payroll_year INT NOT NULL,
    payroll_start_date DATE NOT NULL,
    payroll_end_date DATE NOT NULL,
    gross_pay DECIMAL(10,2) NOT NULL,
    net_pay DECIMAL(10,2) NOT NULL,
    status ENUM('Pending', 'Paid') DEFAULT 'Pending',
    paid_at DATETIME NULL,
    paid_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_payroll (employee_id, payroll_week, payroll_year, payroll_start_date)
)";

if (!mysqli_query($db, $createPaymentsTable)) {
    error_log('Failed to create payroll_payments table: ' . mysqli_error($db));
}

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

// Check if user is admin
$isAdmin = isset($_SESSION['position']) && in_array(strtolower($_SESSION['position']), ['admin', 'hr', 'supervisor', 'super admin']);

// Handle mark as paid API
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_as_paid'])) {
    header('Content-Type: application/json');
    
    if (!$isAdmin) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $empId = intval($_POST['employee_id'] ?? 0);
    $payrollWeek = intval($_POST['payroll_week'] ?? $currentPayrollWeek);
    $payrollYear = intval($_POST['payroll_year'] ?? date('Y'));
    $weekStart = $_POST['week_start'] ?? $weekStart;
    $weekEnd = $_POST['week_end'] ?? $weekEnd;
    $grossPay = floatval($_POST['gross_pay'] ?? 0);
    $netPay = floatval($_POST['net_pay'] ?? 0);
    $paidBy = intval($_SESSION['employee_id'] ?? 0);
    
    if ($empId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid employee']);
        exit;
    }
    
    // Insert or update payment record
    $query = "INSERT INTO payroll_payments 
              (employee_id, payroll_week, payroll_year, payroll_start_date, payroll_end_date, gross_pay, net_pay, status, paid_at, paid_by)
              VALUES (?, ?, ?, ?, ?, ?, ?, 'Paid', NOW(), ?)
              ON DUPLICATE KEY UPDATE 
              status = 'Paid', paid_at = NOW(), paid_by = VALUES(paid_by),
              gross_pay = VALUES(gross_pay), net_pay = VALUES(net_pay)";
    
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, 'iiissddi', $empId, $payrollWeek, $payrollYear, $weekStart, $weekEnd, $grossPay, $netPay, $paidBy);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Payment marked as paid']);
    } else {
        $error = mysqli_stmt_error($stmt);
        error_log('payroll_payments error: ' . $error);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $error]);
    }
    mysqli_stmt_close($stmt);
    exit;
}

// Function to check if payment has been made
function getPaymentStatus($db, $empId, $weekStart, $weekEnd) {
    $query = "SELECT status, paid_at FROM payroll_payments 
              WHERE employee_id = ? AND payroll_start_date = ? AND payroll_end_date = ?
              ORDER BY id DESC LIMIT 1";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, 'iss', $empId, $weekStart, $weekEnd);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row ?: ['status' => 'Pending', 'paid_at' => null];
}

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
                            // Get employees marked as paid for current week
                            $paidQuery = "SELECT employee_id, gross_pay FROM payroll_payments 
                                         WHERE status = 'Paid' 
                                         AND payroll_start_date = ? AND payroll_end_date = ?";
                            $stmtPaid = mysqli_prepare($db, $paidQuery);
                            mysqli_stmt_bind_param($stmtPaid, 'ss', $weekStart, $weekEnd);
                            mysqli_stmt_execute($stmtPaid);
                            $paidResult = mysqli_stmt_get_result($stmtPaid);
                            
                            $totalMonthlyPaid = 0;
                            while ($paid = mysqli_fetch_assoc($paidResult)) {
                                $totalMonthlyPaid += $paid['gross_pay'];
                            }
                            mysqli_stmt_close($stmtPaid);
                            
                            echo "₱" . number_format($totalMonthlyPaid, 2);
                        ?>
                    </div>
                    <div class="stat-label">Total Paid (Current Week)</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-week"></i>
                    </div>
                    <div class="stat-value">
                        <?php
                            // Get paid employee IDs for current week
                            $paidIdsQuery = "SELECT employee_id FROM payroll_payments 
                                            WHERE status = 'Paid' 
                                            AND payroll_start_date = ? AND payroll_end_date = ?";
                            $stmtPaidIds = mysqli_prepare($db, $paidIdsQuery);
                            mysqli_stmt_bind_param($stmtPaidIds, 'ss', $weekStart, $weekEnd);
                            mysqli_stmt_execute($stmtPaidIds);
                            $paidIdsResult = mysqli_stmt_get_result($stmtPaidIds);
                            $paidEmployeeIds = [];
                            while ($row = mysqli_fetch_assoc($paidIdsResult)) {
                                $paidEmployeeIds[] = $row['employee_id'];
                            }
                            mysqli_stmt_close($stmtPaidIds);
                            
                            // Get all active employees and calculate pending total
                            $totalPending = 0;
                            $empQuery = "SELECT id, daily_rate FROM employees WHERE status = 'Active'";
                            $empResult = mysqli_query($db, $empQuery);
                            
                            if ($empResult) {
                                while ($emp = mysqli_fetch_assoc($empResult)) {
                                    if (!in_array($emp['id'], $paidEmployeeIds)) {
                                        // Get present days for this employee in current week
                                        $presentDaysQuery = "SELECT COUNT(*) as present_count 
                                                            FROM attendance 
                                                            WHERE employee_id = ? 
                                                            AND attendance_date BETWEEN ? AND ?
                                                            AND status IN ('Present', 'Late', 'Early Out')
                                                            AND DAYOFWEEK(attendance_date) BETWEEN 2 AND 7";
                                        $stmtPresent = mysqli_prepare($db, $presentDaysQuery);
                                        mysqli_stmt_bind_param($stmtPresent, 'iss', $emp['id'], $weekStart, $weekEnd);
                                        mysqli_stmt_execute($stmtPresent);
                                        $presentResult = mysqli_stmt_get_result($stmtPresent);
                                        $presentData = mysqli_fetch_assoc($presentResult);
                                        $presentDays = intval($presentData['present_count'] ?? 0);
                                        mysqli_stmt_close($stmtPresent);
                                        
                                        $empGross = floatval($emp['daily_rate']) * $presentDays;
                                        $totalPending += $empGross;
                                    }
                                }
                            }
                            
                            echo "₱" . number_format($totalPending, 2);
                        ?>
                    </div>
                    <div class="stat-label">Total Pending (Current Week)</div>
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
                                        
                                        // Check payment status first
                                        $paymentStatus = getPaymentStatus($db, $row['id'], $weekStart, $weekEnd);
                                        $isPaid = ($paymentStatus['status'] === 'Paid');
                                        
                                        // Get stored payment data if paid
                                        $storedGross = 0;
                                        $storedNet = 0;
                                        if ($isPaid) {
                                            $storedQuery = "SELECT gross_pay, net_pay FROM payroll_payments 
                                                           WHERE employee_id = ? AND payroll_start_date = ? AND payroll_end_date = ? AND status = 'Paid'
                                                           LIMIT 1";
                                            $stmtStored = mysqli_prepare($db, $storedQuery);
                                            mysqli_stmt_bind_param($stmtStored, 'iss', $row['id'], $weekStart, $weekEnd);
                                            mysqli_stmt_execute($stmtStored);
                                            $storedResult = mysqli_stmt_get_result($stmtStored);
                                            $storedData = mysqli_fetch_assoc($storedResult);
                                            if ($storedData) {
                                                $storedGross = floatval($storedData['gross_pay']);
                                                $storedNet = floatval($storedData['net_pay']);
                                            }
                                            mysqli_stmt_close($stmtStored);
                                        }
                                        
                                        // Use stored gross if paid, otherwise calculate from attendance
                                        if ($isPaid && $storedGross > 0) {
                                            $grossPay = $storedGross;
                                            $netPay = $storedNet;
                                        } else {
                                            // Calculate from attendance (pending employees)
                                            $grossPay = (float)$row['daily_rate'] * (float)$row['total_hours'];
                                            $sssDeduction = (float)$weeklySss;
                                            $philhealthDeduction = (float)$weeklyPhilhealth;
                                            $pagibigDeduction = (float)$weeklyPagibig;
                                            $netPay = $grossPay - $sssDeduction - $philhealthDeduction - $pagibigDeduction;
                                        }

                                        $sssDeduction = (float)$weeklySss;
                                        $philhealthDeduction = (float)$weeklyPhilhealth;
                                        $pagibigDeduction = (float)$weeklyPagibig;
                                        
                                        // Format values based on payment status
                                        if ($isPaid) {
                                            $grossDisplay = '₱' . number_format($grossPay, 2);
                                            $sssDisplay = '-₱' . number_format($sssDeduction, 2);
                                            $philDisplay = '-₱' . number_format($philhealthDeduction, 2);
                                            $pagDisplay = '-₱' . number_format($pagibigDeduction, 2);
                                            $netDisplay = '₱' . number_format($netPay, 2);
                                            $statusBadge = '<span class="badge" style="background: #28a745; color: white; font-size: 10px; padding: 4px 8px; border-radius: 4px; margin-top: 4px; display: inline-block;">PAID</span>';
                                            $btnText = 'View Receipt';
                                            $btnClass = 'btn-gold';
                                        } else {
                                            $grossDisplay = '<span class="text-muted">***</span>';
                                            $sssDisplay = '<span class="text-muted">***</span>';
                                            $philDisplay = '<span class="text-muted">***</span>';
                                            $pagDisplay = '<span class="text-muted">***</span>';
                                            $netDisplay = '<span class="text-muted">***</span>';
                                            $statusBadge = '<span class="badge" style="background: #ffc107; color: #000; font-size: 10px; padding: 4px 8px; border-radius: 4px; margin-top: 4px; display: inline-block;">PENDING</span>';
                                            $btnText = 'View Details';
                                            $btnClass = 'btn-outline-light';
                                        }
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
                                                        <div class="mt-1"><?php echo $statusBadge; ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo $grossDisplay; ?></td>
                                            <td><?php echo $sssDisplay; ?></td>
                                            <td><?php echo $philDisplay; ?></td>
                                            <td><?php echo $pagDisplay; ?></td>
                                            <td><?php echo $netDisplay; ?></td>
                                            <td>
                                                <?php if ($isAdmin && !$isPaid): ?>
                                                    <button class="btn-mark-paid" onclick='markAsPaid(<?php echo (int)$row['id']; ?>, <?php echo $grossPay; ?>, <?php echo $netPay; ?>)'>
                                                        <i class="fas fa-check-circle"></i>
                                                        <span>Mark as Paid</span>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="<?php echo $btnClass; ?> btn-sm" onclick='openBillingModal(<?php echo (int)$row['id']; ?>, <?php echo htmlspecialchars($empNameJs, ENT_QUOTES, 'UTF-8'); ?>, <?php echo (float)$row['daily_rate']; ?>)'>
                                                        <i class="fas fa-receipt me-1"></i>
                                                        <?php echo $btnText; ?>
                                                    </button>
                                                <?php endif; ?>
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