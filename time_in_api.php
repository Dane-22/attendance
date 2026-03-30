<?php
require_once __DIR__ . '/conn/db_connection.php';
require_once __DIR__ . '/functions.php';
header('Content-Type: application/json');

$employeeId = $_POST['employee_id'] ?? null;
$branchName = $_POST['branch_name'] ?? null;
$debug = isset($_POST['debug']) ? (int)$_POST['debug'] : 0;

// Geolocation parameters (optional)
$latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : null;
$longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : null;
$accuracy = isset($_POST['accuracy']) ? floatval($_POST['accuracy']) : null;
$locationVerified = isset($_POST['location_verified']) ? intval($_POST['location_verified']) : 0;

if (!$employeeId || !$branchName) {
    echo json_encode(['success' => false, 'message' => 'Missing employee_id or branch_name']);
    exit();
}

function attendanceHasColumn($db, $columnName) {
    $safe = mysqli_real_escape_string($db, $columnName);
    $sql = "SHOW COLUMNS FROM `attendance` LIKE '{$safe}'";
    $result = mysqli_query($db, $sql);
    return $result && mysqli_num_rows($result) > 0;
}

function getCurrentDatabaseName($db) {
    $result = mysqli_query($db, "SELECT DATABASE() AS db_name");
    if (!$result) return null;
    $row = mysqli_fetch_assoc($result);
    return $row ? ($row['db_name'] ?? null) : null;
}

function hasAttendanceTable($db) {
    $result = mysqli_query($db, "SHOW TABLES LIKE 'attendance'");
    return $result && mysqli_num_rows($result) > 0;
}

$hasTimeIn = attendanceHasColumn($db, 'time_in');
$hasTimeOut = attendanceHasColumn($db, 'time_out');
$hasIsTimeRunning = attendanceHasColumn($db, 'is_time_running');
$hasIsOvertimeRunning = attendanceHasColumn($db, 'is_overtime_running');
$hasTotalOtHrs = attendanceHasColumn($db, 'total_ot_hrs');
$hasClockInLat = attendanceHasColumn($db, 'clock_in_lat');
$hasClockInLng = attendanceHasColumn($db, 'clock_in_lng');
$hasLocationAccuracy = attendanceHasColumn($db, 'location_accuracy');
$hasLocationVerified = attendanceHasColumn($db, 'location_verified');

if (!$hasTimeIn) {
    $payload = [
        'success' => false,
        'message' => 'Server database is missing attendance.time_in. Please run DB migration on the correct database.'
    ];
    if ($debug === 1) {
        $payload['debug'] = [
            'db_name' => getCurrentDatabaseName($db),
            'has_attendance_table' => hasAttendanceTable($db),
            'mysqli_error' => mysqli_error($db),
        ];
    }
    echo json_encode($payload);
    exit();
}

if (!$hasTimeOut) {
    $payload = [
        'success' => false,
        'message' => 'Server database is missing attendance.time_out. Please run DB migration on the correct database.'
    ];
    if ($debug === 1) {
        $payload['debug'] = [
            'db_name' => getCurrentDatabaseName($db),
            'has_attendance_table' => hasAttendanceTable($db),
            'mysqli_error' => mysqli_error($db),
        ];
    }
    echo json_encode($payload);
    exit();
}

$date = date('Y-m-d');

// Check if already clocked in for today
// Check if employee has any open session at a different branch today
$sql = $hasIsTimeRunning
    ? "SELECT id, branch_name, time_in, time_out, is_time_running FROM attendance WHERE employee_id = ? AND attendance_date = ? AND (is_time_running = 1 OR (time_in IS NOT NULL AND time_out IS NULL))"
    : "SELECT id, branch_name, time_in, time_out FROM attendance WHERE employee_id = ? AND attendance_date = ? AND (time_in IS NOT NULL AND time_out IS NULL)";
$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, 'is', $employeeId, $date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$openRows = [];
while ($r = $result ? mysqli_fetch_assoc($result) : null) {
    if ($r) $openRows[] = $r;
}
mysqli_stmt_close($stmt);

// If any open session exists at a different branch, block
foreach ($openRows as $row) {
    if (strcasecmp($row['branch_name'], $branchName) !== 0) {
        echo json_encode(['success' => false, 'message' => 'Already timed in at another branch today', 'branch_name' => $row['branch_name'], 'time_in' => $row['time_in']]);
        exit();
    }
}
// Otherwise, allow time-in (multiple sessions allowed at the same branch in a day)

$shouldIncludeOtDefaults = $hasIsOvertimeRunning && $hasTotalOtHrs;
$shouldIncludeLocation = $hasClockInLat && $hasClockInLng;

$insertSql = null;
$insertTypes = 'iss';
$insertParams = [$employeeId, $branchName, $date];

// Build dynamic INSERT based on available columns
$columns = ['employee_id', 'branch_name', 'attendance_date', 'time_in', 'status', 'created_at'];
$values = ['?', '?', '?', 'NOW()', "'Present'", 'NOW()'];

if ($hasIsTimeRunning) {
    $columns[] = 'is_time_running';
    $values[] = '1';
}

if ($hasIsOvertimeRunning) {
    $columns[] = 'is_overtime_running';
    $values[] = '0';
}

if ($hasTotalOtHrs) {
    $columns[] = 'total_ot_hrs';
    $values[] = "''";
}

// Add location columns if available and provided
if ($shouldIncludeLocation && $latitude !== null && $longitude !== null) {
    $columns[] = 'clock_in_lat';
    $columns[] = 'clock_in_lng';
    $values[] = '?';
    $values[] = '?';
    $insertTypes .= 'dd';
    $insertParams[] = $latitude;
    $insertParams[] = $longitude;
    
    if ($hasLocationAccuracy && $accuracy !== null) {
        $columns[] = 'location_accuracy';
        $values[] = '?';
        $insertTypes .= 'd';
        $insertParams[] = $accuracy;
    }
    
    if ($hasLocationVerified) {
        $columns[] = 'location_verified';
        $values[] = '?';
        $insertTypes .= 'i';
        $insertParams[] = $locationVerified;
    }
}

$insertSql = "INSERT INTO attendance (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ")";

$insertStmt = mysqli_prepare($db, $insertSql);
mysqli_stmt_bind_param($insertStmt, $insertTypes, ...$insertParams);
if (mysqli_stmt_execute($insertStmt)) {
    $attendanceId = mysqli_insert_id($db);
    $response = [
        'success' => true,
        'message' => 'Time in recorded',
        'attendance_id' => $attendanceId,
        'time_in' => date('Y-m-d H:i:s'),
        'is_time_running' => true
    ];
    
    // Include location data in response if saved
    if ($shouldIncludeLocation && $latitude !== null && $longitude !== null) {
        $response['location'] = [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy,
            'verified' => $locationVerified
        ];
    }
    
    echo json_encode($response);
    
    // Log activity to database
    logApiActivity($db, $employeeId, 'Time In', "Employee ID {$employeeId} timed in at branch: {$branchName}");
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($db)]);
    
    // Log failed activity to database
    logApiActivity($db, $employeeId, 'Time In Failed', "Failed to record time in for Employee ID {$employeeId} at branch: {$branchName} - Error: " . mysqli_error($db));
}
mysqli_stmt_close($insertStmt);
?>
