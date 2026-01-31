<?php
// filepath: c:\wamp64\www\attendance_web\employee\billing.php
require_once __DIR__ . '/../conn/db_connection.php';
require_once __DIR__ . '/../functions.php';
session_start();

if (empty($_SESSION['logged_in'])) {
    header('Location: ../login.php');
    exit;
}
// Check if super_admin (redirect to admin dashboard if super_admin)
if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin') {
    header('Location: ../admin/dashboard.php');
    exit();
}

global $db;

// Daily rate ang nasa database, hindi monthly salary
$employees = $db->query("SELECT id, employee_code, first_name, middle_name, last_name, daily_rate FROM employees");

function getMonthlySalary($dailyRate) {
    // Daily rate × 26 working days (Monday-Saturday)
    return $dailyRate * 26;
}

function getWeeklySalary($dailyRate) {
    // Daily rate × 6 working days (Monday-Saturday)
    return $dailyRate * 6;
}

function getDateRange($viewType) {
    $today = date('Y-m-d');
    if ($viewType === 'weekly') {
        $startDate = date('Y-m-d', strtotime('-7 days', strtotime($today)));
        $endDate = $today;
    } elseif ($viewType === 'monthly') {
        $startDate = date('Y-m-01', strtotime($today));
        $endDate = date('Y-m-t', strtotime($today));
    } else {
        $startDate = $today;
        $endDate = $today;
    }
    return ['start' => $startDate, 'end' => $endDate];
}
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

    <style>
        :root {
            --gold: #FFD700;
            --gold-light: #FFF8DC;
            --gold-dark: #D4AF37;
            --black: #0F0F0F;
            --dark-gray: #1A1A1A;
            --medium-gray: #2D2D2D;
            --light-gray: #4A4A4A;
            --text-light: #E0E0E0;
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
            --info: #3B82F6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--black);
            color: var(--text-light);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .app-shell {
            display: flex;
            min-height: 100vh;
            background: linear-gradient(135deg, #0F0F0F 0%, #1A1A1A 100%);
        }

        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
            background: var(--black);
        }

        /* Header Styles */
        .header-card {
            background: linear-gradient(135deg, var(--dark-gray) 0%, var(--medium-gray) 100%);
            border: 1px solid var(--light-gray);
            border-radius: 16px;
            padding: 24px 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            position: relative;
            overflow: hidden;
        }

        .header-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--gold), var(--gold-dark));
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--black);
            font-size: 24px;
            box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
        }

        .header-text {
            flex: 1;
        }

        .welcome {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(90deg, var(--gold), #FFED4E);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .header-subtitle {
            font-size: 14px;
            color: #888;
            font-weight: 500;
        }

        .date-display {
            background: var(--dark-gray);
            border: 1px solid var(--light-gray);
            border-radius: 10px;
            padding: 12px 20px;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .date-display i {
            color: var(--gold);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--dark-gray);
            border: 1px solid var(--light-gray);
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
            border-color: var(--gold);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            background: rgba(255, 215, 0, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            color: var(--gold);
            font-size: 20px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--gold);
            line-height: 1;
        }

        .stat-label {
            color: #888;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 8px;
        }

        /* Table Styling */
        .employee-table-container {
            background: var(--dark-gray);
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border: 1px solid var(--light-gray);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .table-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-light);
        }

        .search-box {
            position: relative;
            width: 300px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 20px 12px 45px;
            background: var(--black);
            border: 1px solid var(--light-gray);
            border-radius: 10px;
            color: var(--text-light);
            font-size: 14px;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gold);
        }

        .table {
            background: transparent;
            color: var(--text-light);
            margin-bottom: 0;
        }

        .table thead th {
            background: var(--medium-gray);
            border-bottom: 2px solid var(--gold);
            padding: 18px 15px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 13px;
            color: var(--gold);
            vertical-align: middle;
        }

        .table tbody tr {
            border-bottom: 1px solid var(--light-gray);
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background: rgba(255, 215, 0, 0.05);
            transform: scale(1.01);
        }

        .table tbody td {
            padding: 20px 15px;
            vertical-align: middle;
            border-color: var(--light-gray);
        }

        /* Button Styles */
        .btn-gold {
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            color: var(--black);
            border: none;
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 10px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            letter-spacing: 0.5px;
        }

        .btn-gold:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(255, 215, 0, 0.4);
            color: var(--black);
        }

        .btn-outline-gold {
            background: transparent;
            color: var(--gold);
            border: 2px solid var(--gold);
            font-weight: 600;
            padding: 10px 22px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-outline-gold:hover {
            background: var(--gold);
            color: var(--black);
            transform: translateY(-3px);
        }

        /* Modal Styling */
        .modal-content {
            background: var(--dark-gray);
            border: 1px solid var(--light-gray);
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        .modal-header {
            border-bottom: 1px solid var(--light-gray);
            padding: 25px 30px;
            background: var(--medium-gray);
            border-radius: 16px 16px 0 0;
        }

        .modal-title {
            font-weight: 700;
            font-size: 22px;
            color: var(--gold);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-title i {
            color: var(--gold);
        }

        .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
            opacity: 0.8;
        }

        .modal-body {
            padding: 30px;
        }

        .modal-footer {
            border-top: 1px solid var(--light-gray);
            padding: 20px 30px;
            gap: 10px;
        }

        /* Receipt Styles */
        .receipt-container {
            background: linear-gradient(135deg, #1A1A1A 0%, #2D2D2D 100%);
            border: 2px solid var(--gold);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 10px 40px rgba(255, 215, 0, 0.2);
            position: relative;
            overflow: hidden;
        }

        .receipt-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at top right, rgba(255, 215, 0, 0.1), transparent 70%);
            pointer-events: none;
        }

        .receipt-header {
            text-align: center;
            border-bottom: 2px solid var(--gold);
            padding-bottom: 20px;
            margin-bottom: 25px;
            position: relative;
        }

        .receipt-header h4 {
            color: var(--gold);
            font-weight: 800;
            font-size: 28px;
            letter-spacing: 2px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .receipt-badge {
            display: inline-block;
            background: var(--gold);
            color: var(--black);
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }

        .receipt-item {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px dashed var(--light-gray);
            transition: all 0.3s ease;
        }

        .receipt-item:hover {
            background: rgba(255, 255, 255, 0.02);
            padding-left: 10px;
            padding-right: 10px;
            margin: 0 -10px;
            border-radius: 8px;
        }

        .receipt-item.total {
            border-top: 2px solid var(--gold);
            border-bottom: none;
            font-weight: 700;
            margin-top: 20px;
            padding-top: 20px;
            background: rgba(255, 215, 0, 0.05);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .receipt-left {
            color: #BBB;
            font-weight: 500;
        }

        .receipt-right {
            color: var(--text-light);
            font-weight: 600;
            font-size: 16px;
        }

        .receipt-right.total-amount {
            color: var(--gold);
            font-size: 28px;
            font-weight: 800;
        }

        .receipt-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--light-gray);
            color: #888;
            font-size: 13px;
        }

        .receipt-footer p {
            margin-bottom: 5px;
        }

        /* View Type Selector */
        .view-type-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            background: var(--black);
            padding: 8px;
            border-radius: 12px;
            width: fit-content;
            border: 1px solid var(--light-gray);
        }

        .view-type-btn {
            padding: 10px 20px;
            border: none;
            background: transparent;
            color: var(--text-light);
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .view-type-btn.active {
            background: var(--gold);
            color: var(--black);
        }

        /* Performance Editor Styles */
        .performance-editor {
            background: linear-gradient(135deg, #1A1A1A 0%, #2D2D2D 100%);
            border: 1px solid var(--gold);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .performance-editor h6 {
            color: var(--gold);
            font-weight: 600;
            border-bottom: 1px solid var(--light-gray);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .form-control.bg-dark {
            background: var(--black) !important;
            border-color: var(--light-gray);
            color: var(--text-light) !important;
        }

        .form-control.bg-dark:focus {
            background: var(--black) !important;
            border-color: var(--gold);
            box-shadow: 0 0 0 0.25rem rgba(255, 215, 0, 0.25);
            color: var(--text-light) !important;
        }

        /* Notification Styles */
        .notification {
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Loading Animation */
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 40px;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 3px solid var(--light-gray);
            border-top: 3px solid var(--gold);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                padding: 20px 15px;
            }
            
            .header-card {
                padding: 20px;
            }
            
            .welcome {
                font-size: 24px;                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        
            }                                                                                                                                                        
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .table-header {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
            
            .search-box {
                width: 100%;
            }
            
            .modal-dialog {
                margin: 10px;
            }
            
            .modal-body {
                padding: 20px;
            }
            
            .receipt-container {
                padding: 20px;
            }
            
            .                                                                                                                                                                                                                                                                                           ce-editor {
                padding: 15px;
            }
        }

        /* Print Styles */
        @media print {
            .no-print {
                display: none;
            }
            
            body {
                background: white;
                color: black;
            }
            
            .receipt-container {
                border: 2px solid black;
                background: white;
                color: black;
                box-shadow: none;
            }
            
            .receipt-header h4 {
                color: black;
            }
            
            .receipt-item.total {
                border-top: 2px solid black;
            }
            
            .performance-editor {
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

            <!-- Employee Table -->
            <div class="employee-table-container">
                <div class="table-header">
                    <div class="table-title">
                        <i class="fas fa-list-alt me-2"></i>
                        Employee List
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
                                <th>ID</th>
                                <th>Employee Name</th>
                                <th>Monthly Salary</th>
                                <th>Weekly Salary*</th>
                                <th>Daily Rate*</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="employeeTableBody">
                            <?php 
                                $employees->data_seek(0);
                                while ($emp = $employees->fetch_assoc()): 
                                    $dailyRate = $emp['daily_rate'];
                                    $monthlySalary = getMonthlySalary($dailyRate);
                                    $weeklySalary = getWeeklySalary($dailyRate);
                            ?>
                                <tr>
                                    <td><span class="badge bg-dark">#<?php echo str_pad($emp['id'], 4, '0', STR_PAD_LEFT); ?></span></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-placeholder me-3">
                                                <div style="width: 36px; height: 36px; background: linear-gradient(135deg, var(--gold), var(--gold-dark)); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--black); font-weight: bold;">
                                                    <?php echo strtoupper(substr($emp['first_name'], 0, 1)); ?>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="fw-medium"><?php echo $emp['first_name'] . ' ' . $emp['last_name']; ?></div>
                                                <div class="text-muted small"><?php echo $emp['employee_code']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-dark p-2">
                                            <i class="fas fa-peso-sign me-1"></i>
                                            <?php echo number_format($monthlySalary, 2); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary p-2">
                                            <i class="fas fa-peso-sign me-1"></i>
                                            <?php echo number_format($weeklySalary, 2); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success p-2">
                                            <i class="fas fa-peso-sign me-1"></i>
                                            <?php echo number_format($dailyRate, 2); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn-gold" onclick="openBillingModal(<?php echo $emp['id']; ?>, '<?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>', <?php echo $dailyRate; ?>, <?php echo $monthlySalary; ?>, <?php echo $weeklySalary; ?>)">
                                            <i class="fas fa-file-invoice-dollar me-2"></i>
                                            View Billing
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <div class="text-muted small mt-2">
                        *Monthly salary computed as daily rate × 26 working days (Monday-Saturday)<br>
                        *Weekly salary computed as daily rate × 6 working days (Monday-Saturday)
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Billing Modal -->
    <div class="modal fade" id="billingModal" tabindex="-1" aria-labelledby="billingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
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
    <script>
        let currentEmpId, currentEmpName, currentDailyRate, currentMonthlySalary, currentWeeklySalary, currentBillingData;
        let currentViewType = 'weekly';
        let originalPerformanceData = null;

        function openBillingModal(empId, empName, dailyRate, monthlySalary, weeklySalary) {
            currentEmpId = empId;
            currentEmpName = empName;
            currentDailyRate = dailyRate;
            currentMonthlySalary = monthlySalary;
            currentWeeklySalary = weeklySalary;
            document.getElementById('empName').textContent = empName;
            
            document.getElementById('loadingSpinner').style.display = 'block';
            document.getElementById('billingContent').style.display = 'none';
            
            updateBilling();
            
            const modal = new bootstrap.Modal(document.getElementById('billingModal'));
            modal.show();
        }

        function changeViewType(type) {
            currentViewType = type;
            
            document.querySelectorAll('.view-type-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.textContent.includes(type.charAt(0).toUpperCase() + type.slice(1))) {
                    btn.classList.add('active');
                }
            });
            
            updateBilling();
        }

        async function updateBilling() {
            if (!currentEmpId) return;
            
            try {
                const response = await fetch(`get_billing_data.php?emp_id=${currentEmpId}&view_type=${currentViewType}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });
                
                const data = await response.json();
                currentBillingData = data;
                
                // Store original performance data for reset functionality
                originalPerformanceData = { ...data.performance };
                
                // Update form fields with current performance data
                document.getElementById('performanceScore').value = data.performance.performanceScore || 85;
                document.getElementById('performanceBonus').value = data.performance.performanceBonus || 0;
                document.getElementById('performanceRemarks').value = data.performance.remarks || '';
                
                document.getElementById('loadingSpinner').style.display = 'none';
                document.getElementById('billingContent').style.display = 'block';
                
                renderDigitalReceipt(data);
                renderDetailedBreakdown(data);
                
            } catch (error) {
                console.error('Error:', error);
                simulateBillingData();
            }
        }

        function simulateBillingData() {
            // Generate dates excluding Sundays
            const attendance = [];
            const today = new Date('2026-01-26');
            
            // Generate last 7 working days (Monday-Saturday)
            let daysAdded = 0;
            let daysBack = 0;
            
            while (daysAdded < 7) {
                const date = new Date(today);
                date.setDate(date.getDate() - daysBack);
                daysBack++;
                
                // Skip Sundays (day 0 = Sunday)
                if (date.getDay() === 0) {
                    continue;
                }
                
                // Determine status
                let status;
                if (daysAdded === 3) {
                    status = 'Late';
                } else if (daysAdded === 5) {
                    status = 'Absent';
                } else {
                    status = 'Present';
                }
                
                attendance.push({
                    attendance_date: date.toISOString().split('T')[0],
                    status: status
                });
                
                daysAdded++;
            }
            
            const presentDays = attendance.filter(r => r.status !== 'Absent').length;
            const computation = {
                totalDays: presentDays,
                gross: currentDailyRate * presentDays,
                dailyRate: currentDailyRate,
                lateCount: attendance.filter(r => r.status === 'Late').length,
                earlyOutCount: 0,
                absentCount: attendance.filter(r => r.status === 'Absent').length
            };
            
            const deductions = {
                sss: 450,
                philhealth: 250,
                pagibig: 200,
                tax: 150,
                totalDeductions: 1050
            };
            
            const performance = {
                performanceScore: 85,
                performanceBonus: currentDailyRate * presentDays * 0.02,
                performanceRating: 'Good',
                remarks: ''
            };
            
            const dateRange = currentViewType === 'weekly' 
                ? { start: attendance[attendance.length-1].attendance_date, end: attendance[0].attendance_date }
                : { start: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0], end: new Date().toISOString().split('T')[0] };
            
            currentBillingData = {
                attendance,
                computation,
                deductions,
                performance,
                dateRange,
                viewType: currentViewType,
                netPay: computation.gross - deductions.totalDeductions + performance.performanceBonus
            };
            
            // Store original performance data
            originalPerformanceData = { ...performance };
            
            // Update form fields
            document.getElementById('performanceScore').value = performance.performanceScore;
            document.getElementById('performanceBonus').value = performance.performanceBonus;
            document.getElementById('performanceRemarks').value = performance.remarks || '';
            
            document.getElementById('loadingSpinner').style.display = 'none';
            document.getElementById('billingContent').style.display = 'block';
            
            renderDigitalReceipt(currentBillingData);
            renderDetailedBreakdown(currentBillingData);
        }

        function applyPerformance() {
            const score = parseInt(document.getElementById('performanceScore').value) || 85;
            const bonus = parseFloat(document.getElementById('performanceBonus').value) || 0;
            const remarks = document.getElementById('performanceRemarks').value;
            
            // Update current billing data
            if (currentBillingData && currentBillingData.performance) {
                // Update performance data
                currentBillingData.performance.performanceScore = score;
                currentBillingData.performance.performanceBonus = bonus;
                currentBillingData.performance.remarks = remarks;
                
                // Calculate performance rating based on score
                currentBillingData.performance.performanceRating = getPerformanceRating(score);
                
                // Update net pay with new bonus
                const newNetPay = currentBillingData.computation.gross - 
                                 currentBillingData.deductions.totalDeductions + 
                                 bonus;
                currentBillingData.netPay = newNetPay;
                
                // Save to database (optional)
                savePerformanceToDatabase(currentEmpId, {
                    score: score,
                    bonus: bonus,
                    remarks: remarks,
                    viewType: currentViewType
                });
                
                // Re-render receipt
                renderDigitalReceipt(currentBillingData);
                
                // Show success message
                showNotification('Performance updated successfully!', 'success');
            }
        }

        function resetPerformance() {
            if (originalPerformanceData) {
                // Restore original performance data
                currentBillingData.performance = { ...originalPerformanceData };
                currentBillingData.netPay = currentBillingData.computation.gross - 
                                           currentBillingData.deductions.totalDeductions + 
                                           originalPerformanceData.performanceBonus;
                
                // Update form fields
                document.getElementById('performanceScore').value = originalPerformanceData.performanceScore;
                document.getElementById('performanceBonus').value = originalPerformanceData.performanceBonus;
                document.getElementById('performanceRemarks').value = originalPerformanceData.remarks || '';
                
                // Re-render receipt
                renderDigitalReceipt(currentBillingData);
                
                showNotification('Performance reset to original values', 'info');
            }
        }

        function getPerformanceRating(score) {
            if (score >= 95) return 'Excellent';
            if (score >= 90) return 'Very Good';
            if (score >= 85) return 'Good';
            if (score >= 80) return 'Satisfactory';
            if (score >= 75) return 'Needs Improvement';
            return 'Poor';
        }

        async function savePerformanceToDatabase(empId, performanceData) {
            try {
                const response = await fetch('save_performance.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        empId: empId,
                        ...performanceData,
                        date: new Date().toISOString().split('T')[0]
                    })
                });
                
                return await response.json();
            } catch (error) {
                console.error('Error saving performance:', error);
                // Still allow editing even if save fails
            }
        }

        function showNotification(message, type) {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification alert alert-${type === 'success' ? 'success' : 'info'}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close ms-auto" onclick="this.parentElement.remove()"></button>
            `;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
                animation: slideIn 0.3s ease;
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }

        function renderDigitalReceipt(data) {
            const { computation, deductions, performance, dateRange } = data;
            const formattedGross = parseFloat(computation.gross).toFixed(2);
            const formattedNet = parseFloat(data.netPay || computation.gross).toFixed(2);
            const currentDate = new Date().toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            const currentTime = new Date().toLocaleTimeString('en-US', { 
                hour12: true, 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            
            // Check if performance has remarks
            const remarksText = performance.remarks ? `<br><small class="text-muted">Remarks: ${performance.remarks}</small>` : '';
            
            let receiptHTML = `
                <div class="receipt-header">
                    <div class="receipt-badge">${currentViewType.toUpperCase()} PAYSLIP</div>
                    <h4>PAYROLL SYSTEM</h4>
                    <p class="mb-1"><i class="fas fa-calendar me-2"></i>${currentDate}</p>
                    <p class="mb-1"><i class="fas fa-clock me-2"></i>${currentTime}</p>
                    <p class="mb-0"><i class="fas fa-user me-2"></i><strong>${currentEmpName}</strong></p>
                    <p class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Period: ${dateRange.start} to ${dateRange.end}</p>
                    <p class="mb-0"><i class="fas fa-hashtag me-2"></i>Ref: INV-${Math.floor(Math.random() * 1000000).toString().padStart(6, '0')}</p>
                </div>
                
                <div class="receipt-item">
                    <div class="receipt-left"><i class="fas fa-calendar-check me-2"></i>Total Present Days</div>
                    <div class="receipt-right">${computation.totalDays} days</div>
                </div>
                
                <div class="receipt-item">
                    <div class="receipt-left"><i class="fas fa-money-bill-wave me-2"></i>Daily Rate</div>
                    <div class="receipt-right">₱${parseFloat(computation.dailyRate || currentDailyRate).toFixed(2)}</div>
                </div>
                
                <div class="receipt-item">
                    <div class="receipt-left"><i class="fas fa-clock me-2"></i>Absent Days</div>
                    <div class="receipt-right">${computation.absentCount || 0} days</div>
                </div>`;
            
            // Add salary rate based on view type
            if (currentViewType === 'weekly') {
                receiptHTML += `
                    <div class="receipt-item">
                        <div class="receipt-left"><i class="fas fa-calendar-week me-2"></i>Weekly Salary Rate</div>
                        <div class="receipt-right">₱${currentWeeklySalary.toFixed(2)}</div>
                    </div>`;
            } else {
                receiptHTML += `
                    <div class="receipt-item">
                        <div class="receipt-left"><i class="fas fa-calendar-alt me-2"></i>Monthly Salary Rate</div>
                        <div class="receipt-right">₱${currentMonthlySalary.toFixed(2)}</div>
                    </div>`;
            }
            
            receiptHTML += `
                <div class="receipt-item" style="border-bottom: 2px solid var(--gold);">
                    <div class="receipt-left"><i class="fas fa-coins me-2"></i>Gross Salary</div>
                    <div class="receipt-right" style="font-weight: bold;">₱${formattedGross}</div>
                </div>
                
                <div class="receipt-item">
                    <div class="receipt-left"><i class="fas fa-file-invoice me-2"></i><strong>DEDUCTIONS</strong></div>
                    <div class="receipt-right"></div>
                </div>`;
            
            if (deductions) {
                receiptHTML += `
                    <div class="receipt-item" style="padding-left: 20px;">
                        <div class="receipt-left">SSS Contribution</div>
                        <div class="receipt-right">-₱${(deductions.sss || 0).toFixed(2)}</div>
                    </div>
                    <div class="receipt-item" style="padding-left: 20px;">
                        <div class="receipt-left">PhilHealth</div>
                        <div class="receipt-right">-₱${(deductions.philhealth || 0).toFixed(2)}</div>
                    </div>
                    <div class="receipt-item" style="padding-left: 20px;">
                        <div class="receipt-left">Pag-IBIG</div>
                        <div class="receipt-right">-₱${(deductions.pagibig || 0).toFixed(2)}</div>
                    </div>
                    <div class="receipt-item" style="padding-left: 20px;">
                        <div class="receipt-left">Withholding Tax</div>
                        <div class="receipt-right">-₱${(deductions.tax || 0).toFixed(2)}</div>
                    </div>
                    <div class="receipt-item" style="padding-left: 20px; border-bottom: 1px dashed var(--light-gray);">
                        <div class="receipt-left"><strong>Total Deductions</strong></div>
                        <div class="receipt-right"><strong>-₱${(deductions.totalDeductions || 0).toFixed(2)}</strong></div>
                    </div>`;
            }
            
            if (performance) {
                const bonusClass = performance.performanceBonus >= 0 ? 'text-success' : 'text-danger';
                const bonusSign = performance.performanceBonus >= 0 ? '+' : '';
                
                receiptHTML += `
                    <div class="receipt-item">
                        <div class="receipt-left">
                            <i class="fas fa-chart-line me-2"></i>
                            Performance Bonus (${performance.performanceScore}% - ${performance.performanceRating})
                            ${remarksText}
                        </div>
                        <div class="receipt-right ${bonusClass}">
                            ${bonusSign}₱${Math.abs(performance.performanceBonus || 0).toFixed(2)}
                        </div>
                    </div>`;
            }
            
            receiptHTML += `
                <div class="receipt-item total">
                    <div class="receipt-left"><i class="fas fa-hand-holding-usd me-2"></i>NET PAY</div>
                    <div class="receipt-right total-amount">₱${formattedNet}</div>
                </div>
                
                <div class="receipt-footer">
                    <p class="mb-1"><i class="fas fa-shield-alt me-2"></i>This is an official digital receipt</p>
                    <p class="mb-0">*** ${currentViewType === 'weekly' ? 'Weekly' : 'Monthly'} payment processed electronically ***</p>
                    <p class="mb-0"><i class="fas fa-print me-2"></i>Print for your records</p>
                    <p class="print-only">Printed on: ${new Date().toLocaleString()}</p>
                </div>`;
            
            document.getElementById('digitalReceipt').innerHTML = receiptHTML;
        }

        function renderDetailedBreakdown(data) {
            const { attendance } = data;
            let detailsHTML = `
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th><i class="fas fa-calendar-day me-2"></i>Date</th>
                            <th><i class="fas fa-user-check me-2"></i>Status</th>
                            <th><i class="fas fa-money-bill me-2"></i>Amount</th>
                        </tr>
                    </thead>
                    <tbody>`;
            
            attendance.forEach(record => {
                const statusClass = record.status === 'Present' ? 'success' : 
                                  record.status === 'Late' ? 'warning' : 
                                  record.status === 'Early Out' ? 'warning' : 'danger';
                const amount = (record.status === 'Present' || record.status === 'Late' || record.status === 'Early Out') 
                    ? currentDailyRate : 0;
                
                const date = record.attendance_date || record.date;
                
                detailsHTML += `
                    <tr>
                        <td>${new Date(date).toLocaleDateString()}</td>
                        <td>
                            <span class="badge bg-${statusClass}">
                                <i class="fas fa-${record.status === 'Present' ? 'check-circle' : 
                                                record.status === 'Late' ? 'clock' : 
                                                record.status === 'Early Out' ? 'sign-out-alt' : 'times-circle'} me-1"></i>
                                ${record.status}
                            </span>
                        </td>
                        <td class="${amount > 0 ? 'text-success fw-bold' : 'text-muted'}">
                            ${amount > 0 ? '₱' + parseFloat(amount).toFixed(2) : '₱0.00'}
                        </td>
                    </tr>`;
            });
            
            detailsHTML += '</tbody></table>';
            document.getElementById('attendanceDetails').innerHTML = detailsHTML;
        }

        function toggleDetails() {
            const detailsSection = document.getElementById('additionalDetails');
            const detailsButton = document.getElementById('detailsBtn');
            
            if (detailsSection.style.display === 'none') {
                detailsSection.style.display = 'block';
                detailsButton.innerHTML = '<i class="fas fa-eye-slash me-2"></i>Hide Details';
                detailsButton.classList.add('active');
            } else {
                detailsSection.style.display = 'none';
                detailsButton.innerHTML = '<i class="fas fa-eye me-2"></i>View Details';
                detailsButton.classList.remove('active');
            }
        }

        function printReceipt() {
            const printWindow = window.open('', '_blank', 'width=800,height=600');
            const receiptContent = document.getElementById('digitalReceipt').innerHTML;
            const detailsContent = document.getElementById('attendanceDetails').innerHTML;
            
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>${currentViewType === 'weekly' ? 'Weekly' : 'Monthly'} Payslip for ${currentEmpName}</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 30px; background: white; color: black; }
                        .receipt-container { border: 2px solid #000; padding: 30px; margin-bottom: 30px; }
                        .receipt-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 25px; }
                        .receipt-item { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px dashed #ccc; }
                        .receipt-item.total { border-top: 2px solid #000; font-weight: bold; margin-top: 20px; padding-top: 20px; }
                        .total-amount { font-size: 24px; color: #000; font-weight: bold; }
                        table { width: 100%; border-collapse: collapse; margin-top: 30px; }
                        th, td { border: 1px solid #000; padding: 10px; text-align: left; }
                        th { background-color: #f2f2f2; font-weight: bold; }
                        @media print { 
                            body { margin: 0; padding: 20px; }
                            .no-print { display: none; }
                        }
                        .text-center { text-align: center; }
                        .mt-4 { margin-top: 30px; }
                    </style>
                </head>
                <body>
                    <div class="receipt-container">
                        ${receiptContent}
                    </div>
                    
                    <div class="mt-4">
                        <h4 class="text-center">Detailed Attendance Record</h4>
                        ${detailsContent}
                    </div>
                    
                    <div class="mt-4 text-center">
                        <hr>
                        <p><strong>Authorized Signature:</strong></p>
                        <p>_________________________</p>
                        <p>Date: ${new Date().toLocaleDateString()}</p>
                    </div>
                    
                    <script>
                        window.onload = function() {
                            window.print();
                            setTimeout(function() {
                                window.close();
                            }, 1000);
                        }
                    <\/script>
                </body>
                </html>
            `);
            
            printWindow.document.close();
        }

        function searchEmployees() {
            const input = document.getElementById('employeeSearch');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('employeeTableBody');
            const tr = table.getElementsByTagName('tr');
            
            for (let i = 0; i < tr.length; i++) {
                const td = tr[i].getElementsByTagName('td')[1];
                if (td) {
                    const txtValue = td.textContent || td.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = '';
                    } else {
                        tr[i].style.display = 'none';
                    }
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 200);
            });
            
            const tableRows = document.querySelectorAll('.table tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transition = 'all 0.3s ease';
                });
            });
        });
    </script>
</body>
</html>