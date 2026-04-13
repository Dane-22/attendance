<?php
/**
 * Manual Attendance Entry
 * 
 * Browser interface to manually input attendance records
 * and sync them to the daily_payroll_reports table.
 * This allows manual entry for audit.php and weekly_report.php synchronization.
 */

require_once __DIR__ . '/../conn/db_connection.php';
require_once __DIR__ . '/../functions.php';
session_start();

// Check if user is logged in and is admin/super admin/developer
if (empty($_SESSION['logged_in']) || !in_array($_SESSION['position'], ['Admin', 'Super Admin', 'Developer'])) {
    header('Location: ../login.php');
    exit;
}

$message = '';
$error = '';

// Get all active employees with supported positions
$employees_query = "SELECT id, employee_code, first_name, last_name, position, daily_rate, branch_id 
                    FROM employees 
                    WHERE status = 'Active' 
                    AND LOWER(position) IN ('worker', 'admin', 'engineer', 'developer')
                    ORDER BY last_name, first_name";
$employees_result = mysqli_query($db, $employees_query);
$employees = [];
while ($row = mysqli_fetch_assoc($employees_result)) {
    $employees[] = $row;
}

// Get all branches
$branches_query = "SELECT id, branch_name FROM branches WHERE is_active = 1 ORDER BY branch_name";
$branches_result = mysqli_query($db, $branches_query);
$branches = [];
while ($row = mysqli_fetch_assoc($branches_result)) {
    $branches[] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = intval($_POST['employee_id'] ?? 0);
    $attendance_date = $_POST['attendance_date'] ?? '';
    $time_in = $_POST['time_in'] ?? '';
    $time_out = $_POST['time_out'] ?? '';
    $branch_id = intval($_POST['branch_id'] ?? 0);
    $total_ot_hrs = floatval($_POST['total_ot_hrs'] ?? 0);
    $status = $_POST['status'] ?? 'Present';
    
    // Validation
    if (!$employee_id || !$attendance_date || !$time_in || !$time_out || !$branch_id) {
        $error = 'Please fill in all required fields.';
    } else {
        // Get employee details
        $emp_query = "SELECT e.*, b.branch_name 
                      FROM employees e 
                      LEFT JOIN branches b ON e.branch_id = b.id 
                      WHERE e.id = ?";
        $emp_stmt = mysqli_prepare($db, $emp_query);
        mysqli_stmt_bind_param($emp_stmt, 'i', $employee_id);
        mysqli_stmt_execute($emp_stmt);
        $emp_result = mysqli_stmt_get_result($emp_stmt);
        $employee = mysqli_fetch_assoc($emp_result);
        mysqli_stmt_close($emp_stmt);
        
        if (!$employee) {
            $error = 'Employee not found.';
        } else {
            // Get branch name
            $branch_name = '';
            foreach ($branches as $b) {
                if ($b['id'] == $branch_id) {
                    $branch_name = $b['branch_name'];
                    break;
                }
            }
            
            // Combine date and time
            $time_in_full = $attendance_date . ' ' . $time_in;
            $time_out_full = $attendance_date . ' ' . $time_out;
            
            // Calculate worked hours
            $start_ts = strtotime($time_in_full);
            $end_ts = strtotime($time_out_full);
            $worked_hours = 0;
            if ($start_ts !== false && $end_ts !== false && $end_ts > $start_ts) {
                $worked_hours = ($end_ts - $start_ts) / 3600;
            }
            
            // Check if attendance record already exists
            $check_sql = "SELECT id FROM attendance 
                          WHERE employee_id = ? AND attendance_date = ?";
            $check_stmt = mysqli_prepare($db, $check_sql);
            mysqli_stmt_bind_param($check_stmt, 'is', $employee_id, $attendance_date);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            $existing = mysqli_fetch_assoc($check_result);
            mysqli_stmt_close($check_stmt);
            
            if ($existing) {
                // Update existing attendance record
                $att_sql = "UPDATE attendance SET 
                            time_in = ?, 
                            time_out = ?, 
                            total_ot_hrs = ?, 
                            branch_name = ?, 
                            status = ?,
                            updated_at = NOW()
                            WHERE id = ?";
                $att_stmt = mysqli_prepare($db, $att_sql);
                mysqli_stmt_bind_param($att_stmt, 'ssdssi', 
                    $time_in_full, $time_out_full, $total_ot_hrs, $branch_name, $status, $existing['id']);
            } else {
                // Insert new attendance record
                $att_sql = "INSERT INTO attendance 
                            (employee_id, attendance_date, time_in, time_out, total_ot_hrs, branch_name, status, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                $att_stmt = mysqli_prepare($db, $att_sql);
                mysqli_stmt_bind_param($att_stmt, 'isssdss', 
                    $employee_id, $attendance_date, $time_in_full, $time_out_full, $total_ot_hrs, $branch_name, $status);
            }
            
            if (mysqli_stmt_execute($att_stmt)) {
                mysqli_stmt_close($att_stmt);
                
                // Now sync to daily_payroll_reports
                $daily_rate = floatval($employee['daily_rate'] ?? 0);
                $ot_rate = $daily_rate / 8;
                $ot_amount = $total_ot_hrs * $ot_rate;
                $basic_pay = $daily_rate;
                $gross_pay = $basic_pay + $ot_amount;
                $performance_allowance = 0.00;
                $gross_plus_allowance = $gross_pay;
                
                // Calculate pro-rated deductions
                $days_in_month = cal_days_in_month(CAL_GREGORIAN, date('m', strtotime($attendance_date)), date('Y', strtotime($attendance_date)));
                $MONTHLY_SSS = 450.00;
                $MONTHLY_PHILHEALTH = 250.00;
                $MONTHLY_PAGIBIG = 200.00;
                
                $sss_deduction = $MONTHLY_SSS / $days_in_month;
                $philhealth_deduction = $MONTHLY_PHILHEALTH / $days_in_month;
                $pagibig_deduction = $MONTHLY_PAGIBIG / $days_in_month;
                $ca_deduction = 0.00;
                $sss_loan = 0.00;
                $total_deductions = $sss_deduction + $philhealth_deduction + $pagibig_deduction + $ca_deduction + $sss_loan;
                $take_home_pay = $gross_plus_allowance - $total_deductions;
                
                // Extract date components
                $report_year = date('Y', strtotime($attendance_date));
                $report_month = date('n', strtotime($attendance_date));
                $report_day = date('j', strtotime($attendance_date));
                $week_number = ceil($report_day / 7);
                if ($week_number > 5) $week_number = 5;
                
                // Insert or update daily_payroll_reports
                $payroll_sql = "INSERT INTO daily_payroll_reports 
                    (employee_id, report_date, report_year, report_month, report_day, week_number, 
                     branch_id, days_worked, total_hours, daily_rate, basic_pay, ot_hours, ot_rate, ot_amount,
                     performance_allowance, gross_pay, gross_plus_allowance, ca_deduction, 
                     sss_deduction, philhealth_deduction, pagibig_deduction, sss_loan, 
                     total_deductions, take_home_pay, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                    days_worked = VALUES(days_worked),
                    total_hours = VALUES(total_hours),
                    daily_rate = VALUES(daily_rate),
                    basic_pay = VALUES(basic_pay),
                    ot_hours = VALUES(ot_hours),
                    ot_rate = VALUES(ot_rate),
                    ot_amount = VALUES(ot_amount),
                    gross_pay = VALUES(gross_pay),
                    gross_plus_allowance = VALUES(gross_plus_allowance),
                    sss_deduction = VALUES(sss_deduction),
                    philhealth_deduction = VALUES(philhealth_deduction),
                    pagibig_deduction = VALUES(pagibig_deduction),
                    total_deductions = VALUES(total_deductions),
                    take_home_pay = VALUES(take_home_pay),
                    updated_at = NOW()";
                
                $payroll_stmt = mysqli_prepare($db, $payroll_sql);
                // Calculate days and pay based on actual hours (Option E - Hybrid)
                $calc_result = calculateDaysAndPay($worked_hours, $daily_rate);
                $days_worked = $calc_result['days_worked'];
                $basic_pay = $calc_result['gross_pay']; // Use calculated pay based on hours
                $payroll_status = 'Pending';
                
                mysqli_stmt_bind_param($payroll_stmt, 'isiiiiiddddddddddddddddds', 
                    $employee_id, $attendance_date, $report_year, $report_month, $report_day, $week_number,
                    $branch_id, $days_worked, $worked_hours, $daily_rate, $basic_pay, $total_ot_hrs, $ot_rate, $ot_amount,
                    $performance_allowance, $gross_pay, $gross_plus_allowance, $ca_deduction,
                    $sss_deduction, $philhealth_deduction, $pagibig_deduction, $sss_loan,
                    $total_deductions, $take_home_pay, $payroll_status
                );
                
                if (mysqli_stmt_execute($payroll_stmt)) {
                    mysqli_stmt_close($payroll_stmt);
                    $message = 'Attendance record saved and synced to daily payroll successfully!';
                } else {
                    $error = 'Attendance saved but failed to sync to payroll: ' . mysqli_error($db);
                }
            } else {
                $error = 'Failed to save attendance: ' . mysqli_error($db);
            }
        }
    }
}

// Get recent entries for display
$recent_query = "SELECT a.*, e.first_name, e.last_name, e.employee_code, b.branch_name as emp_branch
                 FROM attendance a
                 JOIN employees e ON a.employee_id = e.id
                 LEFT JOIN branches b ON e.branch_id = b.id
                 WHERE LOWER(e.position) IN ('worker', 'admin', 'engineer', 'developer')
                 ORDER BY a.created_at DESC
                 LIMIT 20";
$recent_result = mysqli_query($db, $recent_query);
$recent_entries = [];
while ($row = mysqli_fetch_assoc($recent_result)) {
    $recent_entries[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual Attendance Entry - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="css/light-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --orange: #FFA500; --black: #000000; --gold-2: #FFD700; }
        body {
            background: linear-gradient(135deg, var(--black) 0%, #1a1a1a 100%);
            color: #ffffff;
            font-family: 'Inter', system-ui, sans-serif;
            min-height: 100vh;
        }
        .main-content { margin-left: 16rem; padding: 2rem; min-height: 100vh; }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding: 1rem; } }
        .form-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 165, 0, 0.2);
            border-radius: 12px;
            padding: 2rem;
        }
        .input-field {
            width: 100%;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 165, 0, 0.3);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            color: #fff;
            font-size: 14px;
        }
        .input-field:focus {
            outline: none;
            border-color: var(--orange);
            box-shadow: 0 0 0 2px rgba(255, 165, 0, 0.2);
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--orange) 0%, var(--gold-2) 100%);
            color: var(--black);
            font-weight: 600;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(255, 165, 0, 0.3); }
        .alert-success {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid #4CAF50;
            color: #4CAF50;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .alert-error {
            background: rgba(244, 67, 54, 0.2);
            border: 1px solid #F44336;
            color: #F44336;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .recent-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .recent-table th {
            background: rgba(255, 165, 0, 0.2);
            padding: 12px;
            text-align: left;
            color: var(--orange);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
        }
        .recent-table td {
            padding: 10px 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .recent-table tr:hover { background: rgba(255, 165, 0, 0.05); }
    </style>
</head>
<body>
    <?php include __DIR__ . '/sidebar.php'; ?>
    
    <main class="main-content">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-white mb-2">
                <i class="fas fa-user-clock mr-2" style="color: var(--orange);"></i>
                Manual Attendance Entry
            </h1>
            <p class="text-gray-400 text-sm">
                Manually input attendance records and sync to daily payroll reports. 
                This updates both <code>audit.php</code> and <code>weekly_report.php</code>.
            </p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert-success">
                <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert-error">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- Entry Form -->
        <div class="form-card mb-6">
            <h2 class="text-lg font-semibold text-white mb-4">
                <i class="fas fa-plus-circle mr-2" style="color: var(--orange);"></i>
                Add/Update Attendance Record
            </h2>
            
            <form method="POST" action="">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                    <!-- Employee -->
                    <div>
                        <label class="block text-sm text-gray-400 mb-2">Employee *</label>
                        <select name="employee_id" class="input-field" required>
                            <option value="">Select Employee</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?php echo $emp['id']; ?>">
                                    <?php echo htmlspecialchars($emp['last_name'] . ', ' . $emp['first_name'] . ' (' . $emp['position'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Date -->
                    <div>
                        <label class="block text-sm text-gray-400 mb-2">Date *</label>
                        <input type="date" name="attendance_date" class="input-field" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <!-- Branch -->
                    <div>
                        <label class="block text-sm text-gray-400 mb-2">Branch *</label>
                        <select name="branch_id" class="input-field" required>
                            <option value="">Select Branch</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>">
                                    <?php echo htmlspecialchars($branch['branch_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Time In -->
                    <div>
                        <label class="block text-sm text-gray-400 mb-2">Time In *</label>
                        <input type="time" name="time_in" class="input-field" required>
                    </div>
                    
                    <!-- Time Out -->
                    <div>
                        <label class="block text-sm text-gray-400 mb-2">Time Out *</label>
                        <input type="time" name="time_out" class="input-field" required>
                    </div>
                    
                    <!-- OT Hours -->
                    <div>
                        <label class="block text-sm text-gray-400 mb-2">OT Hours</label>
                        <input type="number" name="total_ot_hrs" class="input-field" 
                               value="0" min="0" step="0.5">
                    </div>
                    
                    <!-- Status -->
                    <div>
                        <label class="block text-sm text-gray-400 mb-2">Status</label>
                        <select name="status" class="input-field">
                            <option value="Present">Present</option>
                            <option value="Late">Late</option>
                            <option value="Absent">Absent</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex gap-3">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save mr-2"></i>Save & Sync to Payroll
                    </button>
                    <a href="audit.php" class="btn-secondary" style="padding: 0.75rem 1.5rem; background: rgba(255,255,255,0.1); color: #fff; border-radius: 8px; text-decoration: none;">
                        <i class="fas fa-list mr-2"></i>View Audit
                    </a>
                    <a href="weekly_report.php" class="btn-secondary" style="padding: 0.75rem 1.5rem; background: rgba(255,255,255,0.1); color: #fff; border-radius: 8px; text-decoration: none;">
                        <i class="fas fa-chart-bar mr-2"></i>View Report
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Recent Entries -->
        <div class="form-card">
            <h2 class="text-lg font-semibold text-white mb-4">
                <i class="fas fa-history mr-2" style="color: var(--orange);"></i>
                Recent Entries (Synced to Payroll)
            </h2>
            
            <div class="overflow-x-auto">
                <table class="recent-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Date</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Branch</th>
                            <th>OT Hrs</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_entries as $entry): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($entry['last_name'] . ', ' . $entry['first_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($entry['attendance_date'])); ?></td>
                                <td><?php echo $entry['time_in'] ? date('h:i A', strtotime($entry['time_in'])) : '-'; ?></td>
                                <td><?php echo $entry['time_out'] ? date('h:i A', strtotime($entry['time_out'])) : '-'; ?></td>
                                <td><?php echo htmlspecialchars($entry['branch_name'] ?? $entry['emp_branch'] ?? 'N/A'); ?></td>
                                <td><?php echo number_format($entry['total_ot_hrs'], 1); ?></td>
                                <td>
                                    <span class="px-2 py-1 rounded text-xs font-semibold <?php 
                                        echo match(strtolower($entry['status'])) {
                                            'present' => 'bg-green-500/20 text-green-400',
                                            'late' => 'bg-yellow-500/20 text-yellow-400',
                                            'absent' => 'bg-red-500/20 text-red-400',
                                            default => 'bg-gray-500/20 text-gray-400'
                                        };
                                    ?>">
                                        <?php echo htmlspecialchars($entry['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recent_entries)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-gray-500 py-4">No recent entries found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Quick Links -->
        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="cron/generate_daily_payroll.php" target="_blank" class="form-card text-center hover:border-orange-500 transition-colors" style="text-decoration: none; display: block;">
                <i class="fas fa-sync-alt text-2xl mb-2" style="color: var(--orange);"></i>
                <h3 class="text-white font-semibold">Bulk Sync</h3>
                <p class="text-sm text-gray-400">Generate payroll for date ranges</p>
            </a>
            <a href="audit.php" class="form-card text-center hover:border-orange-500 transition-colors" style="text-decoration: none; display: block;">
                <i class="fas fa-clipboard-check text-2xl mb-2" style="color: var(--orange);"></i>
                <h3 class="text-white font-semibold">Audit View</h3>
                <p class="text-sm text-gray-400">Review all attendance records</p>
            </a>
            <a href="weekly_report.php" class="form-card text-center hover:border-orange-500 transition-colors" style="text-decoration: none; display: block;">
                <i class="fas fa-file-invoice-dollar text-2xl mb-2" style="color: var(--orange);"></i>
                <h3 class="text-white font-semibold">Payroll Report</h3>
                <p class="text-sm text-gray-400">View weekly/monthly reports</p>
            </a>
        </div>
    </main>
</body>
</html>
