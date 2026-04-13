<?php
/**
 * Sync Attendance to Payroll Reports
 * 
 * Browser interface to manually sync attendance data to daily_payroll_reports
 * Use this after adding/deleting attendance records to update weekly_report.php
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
$sync_results = [];

// Government deduction constants (monthly)
$MONTHLY_PHILHEALTH = 250.00;
$MONTHLY_SSS = 450.00;
$MONTHLY_PAGIBIG = 200.00;

// Handle sync request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = $_POST['start_date'] ?? date('Y-m-d');
    $end_date = $_POST['end_date'] ?? date('Y-m-d');
    $sync_mode = $_POST['sync_mode'] ?? 'missing'; // 'missing' or 'all'
    
    // Validate dates
    if (!strtotime($start_date) || !strtotime($end_date)) {
        $error = 'Invalid date format.';
    } elseif (strtotime($end_date) < strtotime($start_date)) {
        $error = 'End date must be after start date.';
    } else {
        // Get all attendance records in date range
        $att_query = "SELECT 
                        a.id as attendance_id,
                        a.employee_id,
                        a.attendance_date,
                        a.time_in,
                        a.time_out,
                        a.total_ot_hrs,
                        a.branch_name,
                        e.daily_rate,
                        e.first_name,
                        e.last_name,
                        e.position,
                        b.id as branch_id
                     FROM attendance a
                     JOIN employees e ON a.employee_id = e.id
                     LEFT JOIN branches b ON a.branch_name = b.branch_name
                     WHERE a.attendance_date BETWEEN ? AND ?
                     AND a.time_out IS NOT NULL
                     AND e.status = 'Active'
                     AND LOWER(e.position) IN ('worker', 'admin', 'engineer', 'developer')
                     ORDER BY a.attendance_date, a.employee_id";
        
        $stmt = mysqli_prepare($db, $att_query);
        mysqli_stmt_bind_param($stmt, 'ss', $start_date, $end_date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $processed = 0;
        $skipped = 0;
        $errors = 0;
        
        while ($row = mysqli_fetch_assoc($result)) {
            $employee_id = $row['employee_id'];
            $attendance_date = $row['attendance_date'];
            $branch_id = $row['branch_id'] ?? 0;
            
            // Check if payroll record already exists
            $check_sql = "SELECT id FROM daily_payroll_reports 
                          WHERE employee_id = ? AND report_date = ? AND branch_id = ?";
            $check_stmt = mysqli_prepare($db, $check_sql);
            mysqli_stmt_bind_param($check_stmt, 'isi', $employee_id, $attendance_date, $branch_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            $exists = mysqli_fetch_assoc($check_result);
            mysqli_stmt_close($check_stmt);
            
            if ($exists && $sync_mode === 'missing') {
                $skipped++;
                continue;
            }
            
            // Calculate worked hours
            $start_ts = strtotime($row['time_in']);
            $end_ts = strtotime($row['time_out']);
            $worked_hours = 0;
            if ($start_ts !== false && $end_ts !== false && $end_ts > $start_ts) {
                $worked_hours = ($end_ts - $start_ts) / 3600;
            }
            
            $daily_rate = floatval($row['daily_rate'] ?? 0);
            $total_ot_hrs = floatval($row['total_ot_hrs'] ?? 0);
            
            // Calculate using the same logic as manual_attendance_entry.php
            $calc_result = calculateDaysAndPay($worked_hours, $daily_rate);
            $days_worked = $calc_result['days_worked'];
            $basic_pay = $calc_result['gross_pay'];
            
            $ot_rate = $daily_rate / 8;
            $ot_amount = $total_ot_hrs * $ot_rate;
            $gross_pay = $basic_pay + $ot_amount;
            $performance_allowance = 0.00;
            $gross_plus_allowance = $gross_pay;
            
            // Calculate pro-rated deductions
            $days_in_month = cal_days_in_month(CAL_GREGORIAN, date('m', strtotime($attendance_date)), date('Y', strtotime($attendance_date)));
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
            $payroll_status = 'Pending';
            
            mysqli_stmt_bind_param($payroll_stmt, 'isiiiiiddddddddddddddddds', 
                $employee_id, $attendance_date, $report_year, $report_month, $report_day, $week_number,
                $branch_id, $days_worked, $worked_hours, $daily_rate, $basic_pay, $total_ot_hrs, $ot_rate, $ot_amount,
                $performance_allowance, $gross_pay, $gross_plus_allowance, $ca_deduction,
                $sss_deduction, $philhealth_deduction, $pagibig_deduction, $sss_loan,
                $total_deductions, $take_home_pay, $payroll_status
            );
            
            if (mysqli_stmt_execute($payroll_stmt)) {
                $processed++;
                $sync_results[] = [
                    'employee' => $row['last_name'] . ', ' . $row['first_name'],
                    'date' => $attendance_date,
                    'status' => $exists ? 'Updated' : 'Inserted',
                    'gross_pay' => $gross_pay
                ];
            } else {
                $errors++;
                $sync_results[] = [
                    'employee' => $row['last_name'] . ', ' . $row['first_name'],
                    'date' => $attendance_date,
                    'status' => 'Error: ' . mysqli_error($db),
                    'gross_pay' => 0
                ];
            }
            mysqli_stmt_close($payroll_stmt);
        }
        
        $message = "Sync completed! Processed: $processed, Skipped: $skipped, Errors: $errors";
    }
}

// Get stats
$stats_query = "SELECT 
                    (SELECT COUNT(*) FROM attendance a 
                     JOIN employees e ON a.employee_id = e.id 
                     WHERE a.time_out IS NOT NULL 
                     AND LOWER(e.position) IN ('worker', 'admin', 'engineer', 'developer')) as total_attendance,
                    (SELECT COUNT(*) FROM daily_payroll_reports) as total_payroll_records,
                    (SELECT COUNT(DISTINCT report_date) FROM daily_payroll_reports) as payroll_dates";
$stats_result = mysqli_query($db, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Get recent payroll dates
$recent_dates_query = "SELECT report_date, COUNT(*) as count 
                       FROM daily_payroll_reports 
                       GROUP BY report_date 
                       ORDER BY report_date DESC 
                       LIMIT 10";
$recent_dates_result = mysqli_query($db, $recent_dates_query);
$recent_dates = [];
while ($row = mysqli_fetch_assoc($recent_dates_result)) {
    $recent_dates[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sync Attendance to Payroll - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
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
        .btn-danger {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: #fff;
            font-weight: 600;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-danger:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(220, 38, 38, 0.3); }
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
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 165, 0, 0.2);
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
        }
        .sync-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .sync-table th {
            background: rgba(255, 165, 0, 0.2);
            padding: 12px;
            text-align: left;
            color: var(--orange);
            font-weight: 600;
        }
        .sync-table td {
            padding: 10px 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/sidebar.php'; ?>
    
    <main class="main-content">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-white mb-2">
                <i class="fas fa-sync-alt mr-2" style="color: var(--orange);"></i>
                Sync Attendance to Payroll
            </h1>
            <p class="text-gray-400 text-sm">
                Sync attendance records to daily_payroll_reports table. 
                This updates the data shown in <code>weekly_report.php</code>.
            </p>
        </div>
        
        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="stat-card">
                <div class="text-2xl font-bold" style="color: var(--orange);"><?php echo number_format($stats['total_attendance']); ?></div>
                <div class="text-sm text-gray-400">Total Attendance Records</div>
            </div>
            <div class="stat-card">
                <div class="text-2xl font-bold" style="color: var(--orange);"><?php echo number_format($stats['total_payroll_records']); ?></div>
                <div class="text-sm text-gray-400">Payroll Records</div>
            </div>
            <div class="stat-card">
                <div class="text-2xl font-bold" style="color: var(--orange);"><?php echo number_format($stats['payroll_dates']); ?></div>
                <div class="text-sm text-gray-400">Dates with Payroll Data</div>
            </div>
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
        
        <!-- Sync Form -->
        <div class="form-card mb-6">
            <h2 class="text-lg font-semibold text-white mb-4">
                <i class="fas fa-calendar-alt mr-2" style="color: var(--orange);"></i>
                Select Date Range to Sync
            </h2>
            
            <form method="POST" action="" onsubmit="return confirm('This will sync attendance to payroll reports. Continue?');">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm text-gray-400 mb-2">Start Date *</label>
                        <input type="date" name="start_date" class="input-field" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-2">End Date *</label>
                        <input type="date" name="end_date" class="input-field" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm text-gray-400 mb-2">Sync Mode</label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="sync_mode" value="missing" checked class="accent-orange-500">
                            <span class="text-sm">Only Missing Records (Skip existing)</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="sync_mode" value="all" class="accent-orange-500">
                            <span class="text-sm">Update All (Overwrite existing)</span>
                        </label>
                    </div>
                </div>
                
                <div class="flex gap-3">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-sync-alt mr-2"></i>Start Sync
                    </button>
                    <a href="weekly_report.php" class="btn-secondary" style="padding: 0.75rem 1.5rem; background: rgba(255,255,255,0.1); color: #fff; border-radius: 8px; text-decoration: none;">
                        <i class="fas fa-chart-bar mr-2"></i>View Report
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Sync Results -->
        <?php if (!empty($sync_results)): ?>
        <div class="form-card mb-6">
            <h2 class="text-lg font-semibold text-white mb-4">
                <i class="fas fa-list mr-2" style="color: var(--orange);"></i>
                Sync Results (Last 50)
            </h2>
            <div class="overflow-x-auto max-h-96 overflow-y-auto">
                <table class="sync-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Gross Pay</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $limited_results = array_slice($sync_results, 0, 50);
                        foreach ($limited_results as $result): 
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($result['employee']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($result['date'])); ?></td>
                                <td>
                                    <?php if ($result['status'] === 'Inserted'): ?>
                                        <span class="text-green-400"><?php echo $result['status']; ?></span>
                                    <?php elseif ($result['status'] === 'Updated'): ?>
                                        <span class="text-yellow-400"><?php echo $result['status']; ?></span>
                                    <?php else: ?>
                                        <span class="text-red-400"><?php echo $result['status']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>₱<?php echo number_format($result['gross_pay'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Recent Payroll Dates -->
        <div class="form-card">
            <h2 class="text-lg font-semibold text-white mb-4">
                <i class="fas fa-history mr-2" style="color: var(--orange);"></i>
                Recent Payroll Dates
            </h2>
            <div class="overflow-x-auto">
                <table class="sync-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Records</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_dates as $date_row): ?>
                            <tr>
                                <td><?php echo date('M d, Y (l)', strtotime($date_row['report_date'])); ?></td>
                                <td><?php echo number_format($date_row['count']); ?> records</td>
                                <td>
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="start_date" value="<?php echo $date_row['report_date']; ?>">
                                        <input type="hidden" name="end_date" value="<?php echo $date_row['report_date']; ?>">
                                        <input type="hidden" name="sync_mode" value="all">
                                        <button type="submit" class="text-sm px-3 py-1 rounded" style="background: rgba(255,165,0,0.2); color: var(--orange); border: none; cursor: pointer;">
                                            <i class="fas fa-sync-alt mr-1"></i>Re-sync
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recent_dates)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-gray-500 py-4">No payroll records found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Quick Tips -->
        <div class="mt-6 form-card">
            <h3 class="text-md font-semibold text-white mb-3">
                <i class="fas fa-lightbulb mr-2" style="color: var(--orange);"></i>
                Quick Tips
            </h3>
            <ul class="text-sm text-gray-400 space-y-2">
                <li><i class="fas fa-check text-green-400 mr-2"></i><strong>Only Missing Records</strong> - Skips dates that already have payroll data (faster)</li>
                <li><i class="fas fa-check text-green-400 mr-2"></i><strong>Update All</strong> - Overwrites existing payroll data with fresh calculations</li>
                <li><i class="fas fa-check text-green-400 mr-2"></i>Use after manually adding/deleting attendance records</li>
                <li><i class="fas fa-check text-green-400 mr-2"></i>Only completed shifts (with time_out) are synced</li>
            </ul>
        </div>
    </main>
</body>
</html>
