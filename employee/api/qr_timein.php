<?php
// api/qr_timein.php - QR Code Time In (No Authentication Required)
date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../../conn/db_connection.php';

// Get employee data from URL parameters
$employeeId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$employeeCode = isset($_GET['code']) ? htmlspecialchars($_GET['code']) : '';

if (!$employeeId || !$employeeCode) {
    showResult('Error', 'Invalid QR code. Missing employee information.', false);
    exit();
}

// Verify employee exists and get their branch
$empStmt = mysqli_prepare($db, "SELECT e.id, e.first_name, e.last_name, e.employee_code, e.branch_id 
                                FROM employees e 
                                WHERE e.id = ? AND e.employee_code = ? LIMIT 1");
mysqli_stmt_bind_param($empStmt, 'is', $employeeId, $employeeCode);
mysqli_stmt_execute($empStmt);
$empResult = mysqli_stmt_get_result($empStmt);
$employee = mysqli_fetch_assoc($empResult);
mysqli_stmt_close($empStmt);

if (!$employee) {
    showResult('Error', 'Employee not found. Invalid QR code.', false);
    exit();
}

// Check if already clocked in today
$checkSql = "SELECT id, time_in FROM attendance 
             WHERE employee_id = ? AND attendance_date = CURDATE() 
             AND time_in IS NOT NULL AND time_out IS NULL";
$checkStmt = mysqli_prepare($db, $checkSql);
mysqli_stmt_bind_param($checkStmt, "i", $employeeId);
mysqli_stmt_execute($checkStmt);
$checkResult = mysqli_stmt_get_result($checkStmt);
$existing = mysqli_fetch_assoc($checkResult);
mysqli_stmt_close($checkStmt);

if ($existing) {
    showResult('Already Clocked In', 
               $employee['first_name'] . ' ' . $employee['last_name'] . ' already clocked in at ' . 
               date('h:i A', strtotime($existing['time_in'])), 
               true, $employee);
    exit();
}

// Record time-in
$branchId = $employee['branch_id'] ?? null;
$sql = "INSERT INTO attendance (employee_id, branch_id, attendance_date, time_in, status) 
        VALUES (?, ?, CURDATE(), NOW(), 'Present')";
$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, "ii", $employeeId, $branchId);

if (mysqli_stmt_execute($stmt)) {
    $timeIn = date('h:i A');
    showResult('Success!', 
               $employee['first_name'] . ' ' . $employee['last_name'] . ' clocked in at ' . $timeIn, 
               true, $employee);
} else {
    showResult('Error', 'Failed to record time-in. Please try again.', false);
}

mysqli_stmt_close($stmt);

function showResult($title, $message, $success, $employee = null) {
    $bgColor = $success ? '#10b981' : '#ef4444';
    $icon = $success ? '✓' : '✗';
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>JAJR Attendance - <?php echo $title; ?></title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: #0b0b0b;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .card {
                background: #1a1a1a;
                border: 2px solid <?php echo $bgColor; ?>;
                border-radius: 16px;
                padding: 40px;
                text-align: center;
                max-width: 400px;
                width: 100%;
                box-shadow: 0 10px 40px rgba(0,0,0,0.5);
            }
            .icon {
                width: 80px;
                height: 80px;
                background: <?php echo $bgColor; ?>;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 40px;
                color: white;
                margin: 0 auto 20px;
            }
            h1 {
                color: #ffffff;
                font-size: 24px;
                margin-bottom: 12px;
            }
            p {
                color: rgba(255,255,255,0.8);
                font-size: 16px;
                line-height: 1.5;
            }
            .time {
                color: #FFD700;
                font-size: 32px;
                font-weight: bold;
                margin: 20px 0;
            }
            .employee-code {
                color: rgba(255,255,255,0.5);
                font-size: 14px;
                margin-top: 16px;
            }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="icon"><?php echo $icon; ?></div>
            <h1><?php echo $title; ?></h1>
            <p><?php echo $message; ?></p>
            <?php if ($employee): ?>
            <div class="employee-code">Code: <?php echo $employee['employee_code']; ?></div>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
}
?>
