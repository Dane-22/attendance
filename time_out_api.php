<?php
require_once __DIR__ . '/conn/db_connection.php';
require_once __DIR__ . '/functions.php';
header('Content-Type: application/json');

$employeeId = $_POST['employee_id'] ?? null;
$branchName = $_POST['branch_name'] ?? null;

// Geolocation parameters (optional)
$latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : null;
$longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : null;
$accuracy = isset($_POST['accuracy']) ? floatval($_POST['accuracy']) : null;
$locationVerified = isset($_POST['location_verified']) ? intval($_POST['location_verified']) : 0;

if (!$employeeId || !$branchName) {
    // Log missing parameters
    logApiActivity($db, $employeeId ?? null, 'Time Out Failed', "Missing employee_id or branch_name in time out request");
    echo json_encode(['success' => false, 'message' => 'Missing employee_id or branch_name']);
    exit();
}

function attendanceHasColumn($db, $columnName) {
    $safe = mysqli_real_escape_string($db, $columnName);
    $sql = "SHOW COLUMNS FROM `attendance` LIKE '{$safe}'";
    $result = mysqli_query($db, $sql);
    return $result && mysqli_num_rows($result) > 0;
}

$hasTimeIn = attendanceHasColumn($db, 'time_in');
$hasTimeOut = attendanceHasColumn($db, 'time_out');
$hasIsTimeRunning = attendanceHasColumn($db, 'is_time_running');
$hasClockOutLat = attendanceHasColumn($db, 'clock_out_lat');
$hasClockOutLng = attendanceHasColumn($db, 'clock_out_lng');
$hasLocationAccuracy = attendanceHasColumn($db, 'location_accuracy');
$hasLocationVerified = attendanceHasColumn($db, 'location_verified');

if (!$hasTimeIn) {
    echo json_encode([
        'success' => false,
        'message' => 'Server database is missing attendance.time_in. Please run DB migration on the correct database.'
    ]);
    exit();
}

if (!$hasTimeOut) {
    echo json_encode([
        'success' => false,
        'message' => 'Server database is missing attendance.time_out. Please run DB migration on the correct database.'
    ]);
    exit();
}

$date = date('Y-m-d');

// Find today's latest running attendance row
$sql = $hasIsTimeRunning
    ? "SELECT id, time_in, time_out, is_time_running, branch_name FROM attendance WHERE employee_id = ? AND attendance_date = ? AND is_time_running = 1 ORDER BY id DESC LIMIT 1"
    : "SELECT id, time_in, time_out, 0 as is_time_running, branch_name FROM attendance WHERE employee_id = ? AND attendance_date = ? AND time_in IS NOT NULL AND time_out IS NULL ORDER BY id DESC LIMIT 1";
$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, 'is', $employeeId, $date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

if (!$row) {
    // Log no open record found
    logApiActivity($db, $employeeId, 'Time Out Failed', "No open attendance record for time out - Employee ID: {$employeeId}");
    echo json_encode(['success' => false, 'message' => 'No open attendance record for time out']);
    exit();
}

if (!empty($row['branch_name']) && $row['branch_name'] !== $branchName) {
    // Log branch mismatch
    logApiActivity($db, $employeeId, 'Time Out Failed', "Cannot time out from different branch. Attempted: {$branchName}, Original: {$row['branch_name']}");
    echo json_encode(['success' => false, 'message' => 'Cannot time out from a different branch']);
    exit();
}

$attendanceId = $row['id'];
$shouldIncludeLocation = $hasClockOutLat && $hasClockOutLng;

// Build dynamic UPDATE based on available columns
$updateFields = ["time_out = NOW()", "updated_at = NOW()"];
$updateTypes = '';
$updateParams = [];

if ($hasIsTimeRunning) {
    $updateFields[] = "is_time_running = 0";
}

// Add location columns if available and provided
if ($shouldIncludeLocation && $latitude !== null && $longitude !== null) {
    $updateFields[] = "clock_out_lat = ?";
    $updateFields[] = "clock_out_lng = ?";
    $updateTypes .= 'dd';
    $updateParams[] = $latitude;
    $updateParams[] = $longitude;
    
    if ($hasLocationAccuracy && $accuracy !== null) {
        $updateFields[] = "location_accuracy = ?";
        $updateTypes .= 'd';
        $updateParams[] = $accuracy;
    }
    
    if ($hasLocationVerified) {
        $updateFields[] = "location_verified = ?";
        $updateTypes .= 'i';
        $updateParams[] = $locationVerified;
    }
}

// Add attendance_id to params
$updateTypes .= 'i';
$updateParams[] = $attendanceId;

$updateSql = "UPDATE attendance SET " . implode(', ', $updateFields) . " WHERE id = ?";
$updateStmt = mysqli_prepare($db, $updateSql);
mysqli_stmt_bind_param($updateStmt, $updateTypes, ...$updateParams);
if (mysqli_stmt_execute($updateStmt)) {
    $response = [
        'success' => true,
        'message' => 'Time out recorded',
        'attendance_id' => $attendanceId,
        'time_out' => date('Y-m-d H:i:s'),
        'is_time_running' => false
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
    logApiActivity($db, $employeeId, 'Time Out', "Employee ID {$employeeId} timed out at branch: {$branchName}");
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($db)]);
    
    // Log failed activity to database
    logApiActivity($db, $employeeId, 'Time Out Failed', "Failed to record time out for Employee ID {$employeeId} - Error: " . mysqli_error($db));
}
mysqli_stmt_close($updateStmt);
?>
