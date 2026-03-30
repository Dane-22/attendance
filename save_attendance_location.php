<?php
// employee/api/save_attendance_location.php
    require_once __DIR__ . '/../../conn/db_connection.php';


header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

function haversineMeters($lat1, $lon1, $lat2, $lon2) {
    $R = 6371000.0;
    $phi1 = deg2rad($lat1);
    $phi2 = deg2rad($lat2);
    $dPhi = deg2rad($lat2 - $lat1);
    $dLambda = deg2rad($lon2 - $lon1);

    $a = sin($dPhi / 2) * sin($dPhi / 2) +
         cos($phi1) * cos($phi2) *
         sin($dLambda / 2) * sin($dLambda / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $R * $c;
}

$attendanceId = isset($_POST['attendance_id']) ? (int)$_POST['attendance_id'] : 0;
$employeeId = isset($_POST['employee_id']) ? (int)$_POST['employee_id'] : 0;
$branchId = isset($_POST['branch_id']) ? (int)$_POST['branch_id'] : 0;

$action = trim((string)($_POST['action'] ?? ''));
$latitude = $_POST['latitude'] ?? null;
$longitude = $_POST['longitude'] ?? null;
$accuracy = $_POST['accuracy'] ?? null;

$isValidated = isset($_POST['is_validated']) ? (int)$_POST['is_validated'] : null;
$failureReason = trim((string)($_POST['validation_failure_reason'] ?? ''));

$deviceInfo = trim((string)($_POST['device_info'] ?? ''));
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

if ($attendanceId <= 0 || $employeeId <= 0 || $branchId <= 0 || $latitude === null || $longitude === null || $action === '') {
    echo json_encode(['success' => false, 'message' => 'Missing attendance_id, employee_id, branch_id, latitude, longitude, or action']);
    exit;
}

$latitude = (float)$latitude;
$longitude = (float)$longitude;
$accuracy = $accuracy !== null ? (float)$accuracy : null;

$allowedActions = ['clock_in', 'clock_out', 'qr_scan', 'manual_override'];
if (!in_array($action, $allowedActions, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

$branchLat = null;
$branchLng = null;
$radius = 200;

$bStmt = mysqli_prepare($db, "SELECT lat, `long`, geofence_radius_meters FROM branches WHERE id = ? LIMIT 1");
if (!$bStmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed (branch lookup)', 'error' => mysqli_error($db)]);
    exit;
}

mysqli_stmt_bind_param($bStmt, 'i', $branchId);
mysqli_stmt_execute($bStmt);
$bRes = mysqli_stmt_get_result($bStmt);
$bRow = $bRes ? mysqli_fetch_assoc($bRes) : null;
mysqli_stmt_close($bStmt);

if (!$bRow) {
    echo json_encode(['success' => false, 'message' => 'Branch not found']);
    exit;
}

if (!empty($bRow['lat']) && !empty($bRow['long'])) {
    $branchLat = (float)$bRow['lat'];
    $branchLng = (float)$bRow['long'];
}
$radius = (int)($bRow['geofence_radius_meters'] ?? 200);

$distanceMeters = null;
if ($branchLat !== null && $branchLng !== null) {
    $distanceMeters = (int)round(haversineMeters($latitude, $longitude, $branchLat, $branchLng));
}

if ($isValidated === null) {
    if ($distanceMeters !== null) {
        $isValidated = ($distanceMeters <= $radius) ? 1 : 0;
        if ($isValidated === 0 && $failureReason === '') {
            $failureReason = 'Outside geofence';
        }
    } else {
        $isValidated = 0;
        if ($failureReason === '') {
            $failureReason = 'Branch location not set';
        }
    }
}

$insertSql = "INSERT INTO location_logs
    (employee_id, attendance_id, action_type, latitude, longitude, accuracy_meters, branch_id, distance_from_branch_meters,
     device_info, ip_address, is_validated, validation_failure_reason, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$ins = mysqli_prepare($db, $insertSql);
if (!$ins) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed (insert)', 'error' => mysqli_error($db)]);
    exit;
}

$distBind = ($distanceMeters !== null) ? (int)$distanceMeters : 0;
$accBind = ($accuracy !== null) ? (float)$accuracy : null;

mysqli_stmt_bind_param(
    $ins,
    'iisdddiissis',
    $employeeId,
    $attendanceId,
    $action,
    $latitude,
    $longitude,
    $accBind,
    $branchId,
    $distBind,
    $deviceInfo,
    $ipAddress,
    $isValidated,
    $failureReason
);

$ok = mysqli_stmt_execute($ins);
$logId = $ok ? mysqli_insert_id($db) : null;
$err = mysqli_stmt_error($ins);
mysqli_stmt_close($ins);

if (!$ok) {
    echo json_encode(['success' => false, 'message' => 'Insert failed', 'error' => $err]);
    exit;
}

if ($action === 'clock_in' || $action === 'qr_scan') {
    $uSql = "UPDATE attendance
             SET clock_in_lat = ?, clock_in_lng = ?, location_accuracy = ?, location_verified = ?, updated_at = NOW()
             WHERE id = ? LIMIT 1";
} elseif ($action === 'clock_out') {
    $uSql = "UPDATE attendance
             SET clock_out_lat = ?, clock_out_lng = ?, location_accuracy = ?, location_verified = ?, updated_at = NOW()
             WHERE id = ? LIMIT 1";
} else {
    $uSql = null;
}

if ($uSql) {
    $u = mysqli_prepare($db, $uSql);
    if ($u) {
        mysqli_stmt_bind_param($u, 'dddii', $latitude, $longitude, $accBind, $isValidated, $attendanceId);
        mysqli_stmt_execute($u);
        mysqli_stmt_close($u);
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Location saved successfully',
    'data' => [
        'location_log_id' => (int)$logId,
        'attendance_id' => (int)$attendanceId,
        'employee_id' => (int)$employeeId,
        'branch_id' => (int)$branchId,
        'action' => $action,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'accuracy' => $accuracy,
        'distance_from_branch_meters' => $distanceMeters,
        'radius_meters' => $radius,
        'is_validated' => (int)$isValidated,
        'validation_failure_reason' => $failureReason !== '' ? $failureReason : null
    ]
]);

mysqli_close($db);
