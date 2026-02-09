<?php
// employees_today_status_api.php
if (file_exists(__DIR__ . '/conn/db_connection.php')) {
    require_once __DIR__ . '/conn/db_connection.php';
} else {
    require_once __DIR__ . '/db_connection.php';
}

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

// Get parameters (branch_name kept for backward compatibility; endpoint returns all employees)
$branch_name = isset($_REQUEST['branch_name']) ? trim($_REQUEST['branch_name']) : '';
$date_today = date("Y-m-d");

function attendanceHasColumn($db, $columnName) {
    $safe = mysqli_real_escape_string($db, $columnName);
    $sql = "SHOW COLUMNS FROM `attendance` LIKE '{$safe}'";
    $result = mysqli_query($db, $sql);
    return $result && mysqli_num_rows($result) > 0;
}

$hasTimeIn = attendanceHasColumn($db, 'time_in');
$hasTimeOut = attendanceHasColumn($db, 'time_out');
$hasIsTimeRunning = attendanceHasColumn($db, 'is_time_running');
$hasTotalOtHrs = attendanceHasColumn($db, 'total_ot_hrs');
$hasIsOvertimeRunning = attendanceHasColumn($db, 'is_overtime_running');

$timeInSelect = $hasTimeIn ? "a.time_in" : "NULL";
$timeOutSelect = $hasTimeOut ? "a.time_out" : "NULL";
$isTimeRunningSelect = $hasIsTimeRunning ? "COALESCE(a.is_time_running, 0)" : "0";
$totalOtHrsSelect = $hasTotalOtHrs ? "COALESCE(a.total_ot_hrs, '')" : "''";
$isOvertimeRunningSelect = $hasIsOvertimeRunning ? "COALESCE(a.is_overtime_running, 0)" : "0";

// Midnight auto-close logic: auto-close any open sessions from yesterday at 23:59:59
function autoCloseYesterdayOpenSessions($db, $today) {
    // Check if we already ran reset today
    $resetCheckSql = "SELECT 1 FROM branch_reset_log WHERE reset_date = ? LIMIT 1";
    $resetCheckStmt = mysqli_prepare($db, $resetCheckSql);
    mysqli_stmt_bind_param($resetCheckStmt, 's', $today);
    mysqli_stmt_execute($resetCheckStmt);
    mysqli_stmt_store_result($resetCheckStmt);
    $alreadyReset = mysqli_stmt_num_rows($resetCheckStmt) > 0;
    mysqli_stmt_close($resetCheckStmt);
    
    if ($alreadyReset) {
        return; // Already ran today
    }
    
    // Calculate yesterday's date
    $yesterday = date('Y-m-d', strtotime($today . ' -1 day'));
    $yesterdayEnd = $yesterday . ' 23:59:59';
    
    // Check if attendance table has the required columns
    $hasIsTimeRunning = attendanceHasColumn($db, 'is_time_running');
    $hasIsOvertimeRunning = attendanceHasColumn($db, 'is_overtime_running');
    $hasUpdatedAt = attendanceHasColumn($db, 'updated_at');
    
    // Build update SQL based on available columns
    $setParts = ["time_out = ?"];
    $types = 's';
    $params = [$yesterdayEnd];
    
    if ($hasIsTimeRunning) {
        $setParts[] = "is_time_running = 0";
    }
    if ($hasIsOvertimeRunning) {
        $setParts[] = "is_overtime_running = 0";
    }
    if ($hasUpdatedAt) {
        $setParts[] = "updated_at = NOW()";
    }
    
    $setClause = implode(', ', $setParts);
    
    // Find and close open sessions from yesterday
    // Open session = time_in is not NULL AND time_out IS NULL (or is_time_running = 1 if column exists)
    if ($hasIsTimeRunning) {
        $updateSql = "UPDATE attendance SET {$setClause} 
                      WHERE attendance_date = ? 
                        AND (is_time_running = 1 OR (time_in IS NOT NULL AND time_out IS NULL))";
    } else {
        $updateSql = "UPDATE attendance SET {$setClause} 
                      WHERE attendance_date = ? 
                        AND time_in IS NOT NULL 
                        AND time_out IS NULL";
    }
    
    $updateStmt = mysqli_prepare($db, $updateSql);
    if ($updateStmt) {
        // Bind yesterday date as additional param
        $types .= 's';
        $params[] = $yesterday;
        mysqli_stmt_bind_param($updateStmt, $types, ...$params);
        mysqli_stmt_execute($updateStmt);
        $affectedRows = mysqli_stmt_affected_rows($updateStmt);
        mysqli_stmt_close($updateStmt);
        
        // Log the reset
        $logSql = "INSERT INTO branch_reset_log (reset_date, employees_affected, created_at) VALUES (?, ?, NOW()) 
                   ON DUPLICATE KEY UPDATE employees_affected = VALUES(employees_affected), created_at = VALUES(created_at)";
        $logStmt = mysqli_prepare($db, $logSql);
        mysqli_stmt_bind_param($logStmt, 'si', $today, $affectedRows);
        mysqli_stmt_execute($logStmt);
        mysqli_stmt_close($logStmt);
    }
}

// Run the midnight auto-close check
autoCloseYesterdayOpenSessions($db, $date_today);

// Get ALL employees with their assigned branch (employees.branch_id -> branches) and latest attendance log for today
$sql = "SELECT 
            e.id,
            e.employee_code,
            e.first_name,
            e.last_name,
            e.position,
            e.branch_id,
            b.branch_name AS assigned_branch_name,
            a.branch_name,
            a.status as today_status,
            {$timeInSelect} as time_in,
            {$timeOutSelect} as time_out,
            COALESCE(a.is_auto_absent, 0) as is_auto_absent,
            {$isTimeRunningSelect} as is_time_running,
            {$isOvertimeRunningSelect} as is_overtime_running,
            {$totalOtHrsSelect} as total_ot_hrs,
            CASE
              WHEN {$isTimeRunningSelect} = 1 THEN 1
              ELSE 0
            END as is_timed_in
        FROM employees e
        LEFT JOIN branches b ON e.branch_id = b.id
        LEFT JOIN (
            SELECT a1.*
            FROM attendance a1
            INNER JOIN (
                SELECT employee_id, MAX(id) AS max_id
                FROM attendance
                WHERE attendance_date = ?
                GROUP BY employee_id
            ) t ON a1.id = t.max_id
        ) a ON e.id = a.employee_id
        WHERE e.status = 'Active'
          AND e.position = 'Worker'
        ORDER BY e.employee_code";

$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, "s", $date_today);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$employees = [];
while ($row = mysqli_fetch_assoc($result)) {
    $employees[] = [
        'id' => (int)$row['id'],
        'employee_code' => $row['employee_code'],
        'first_name' => $row['first_name'],
        'last_name' => $row['last_name'],
        'position' => $row['position'],
        'branch_id' => isset($row['branch_id']) ? (is_null($row['branch_id']) ? null : (int)$row['branch_id']) : null,
        'assigned_branch_name' => $row['assigned_branch_name'],
        'branch_name' => $row['branch_name'],
        'today_status' => $row['today_status'],
        'time_in' => $row['time_in'],
        'time_out' => $row['time_out'],
        'is_auto_absent' => (bool)$row['is_auto_absent'],
        'is_time_running' => (int)($row['is_time_running'] ?? 0) === 1,
        'is_overtime_running' => (int)($row['is_overtime_running'] ?? 0) === 1,
        'is_timed_in' => (int)$row['is_timed_in'] === 1,
        'total_ot_hrs' => isset($row['total_ot_hrs']) ? (string)$row['total_ot_hrs'] : ''
    ];
}

echo json_encode($employees);

mysqli_stmt_close($stmt);
mysqli_close($db);
?>
