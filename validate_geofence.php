<?php
// validate_geofence.php
require_once __DIR__ . '/conn/db_connection.php';



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

/**
 * Check for location spoofing by validating timestamp
 * @param int $gpsTimestamp GPS timestamp from client
 * @param int $maxDiffSeconds Maximum allowed difference (default: 300 seconds = 5 minutes)
 * @return array Validation result
 */
function validateLocationTimestamp($gpsTimestamp, $maxDiffSeconds = 300) {
    $serverTime = time();
    $timeDiff = abs($serverTime - $gpsTimestamp);
    
    return [
        'is_valid' => $timeDiff <= $maxDiffSeconds,
        'time_diff_seconds' => $timeDiff,
        'server_timestamp' => $serverTime,
        'gps_timestamp' => $gpsTimestamp,
        'max_allowed_diff' => $maxDiffSeconds
    ];
}

/**
 * Log geofence violation to database
 * @param mysqli $db Database connection
 * @param array $violationData Violation data
 */
function logGeofenceViolation($db, $violationData) {
    $sql = "INSERT INTO geofence_violations (
        employee_id, branch_id, attendance_id, location_log_id,
        violation_date, violation_time, latitude, longitude,
        distance_from_branch, geofence_radius, accuracy_meters,
        device_info, ip_address, is_flagged_accuracy
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($db, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'iiiiisddidissi',
            $violationData['employee_id'],
            $violationData['branch_id'],
            $violationData['attendance_id'],
            $violationData['location_log_id'],
            $violationData['violation_date'],
            $violationData['violation_time'],
            $violationData['latitude'],
            $violationData['longitude'],
            $violationData['distance'],
            $violationData['radius'],
            $violationData['accuracy'],
            $violationData['device_info'],
            $violationData['ip_address'],
            $violationData['is_flagged_accuracy']
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

/**
 * Check if employee has exceeded violation threshold
 * @param mysqli $db Database connection
 * @param int $employeeId Employee ID
 * @return array Violation status
 */
function checkViolationThreshold($db, $employeeId) {
    $today = date('Y-m-d');
    
    $sql = "SELECT COUNT(*) as violation_count, 
                   MAX(created_at) as last_violation
            FROM geofence_violations 
            WHERE employee_id = ? AND violation_date = ? AND status = 'active'";
    
    $stmt = mysqli_prepare($db, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'is', $employeeId, $today);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $data = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        $violationCount = $data['violation_count'] ?? 0;
        $exceededThreshold = $violationCount >= 3;
        
        // Trigger admin notification if threshold exceeded
        if ($exceededThreshold && $violationCount == 3) {
            triggerAdminNotification($db, $employeeId, $violationCount);
        }
        
        return [
            'violation_count' => $violationCount,
            'exceeded_threshold' => $exceededThreshold,
            'last_violation' => $data['last_violation']
        ];
    }
    
    return ['violation_count' => 0, 'exceeded_threshold' => false];
}

/**
 * Trigger admin notification for geofence violations
 * @param mysqli $db Database connection
 * @param int $employeeId Employee ID
 * @param int $violationCount Number of violations
 */
function triggerAdminNotification($db, $employeeId, $violationCount) {
    // Get employee details
    $empSql = "SELECT first_name, last_name, employee_code FROM employees WHERE id = ?";
    $empStmt = mysqli_prepare($db, $empSql);
    if ($empStmt) {
        mysqli_stmt_bind_param($empStmt, 'i', $employeeId);
        mysqli_stmt_execute($empStmt);
        $empResult = mysqli_stmt_get_result($empStmt);
        $employee = mysqli_fetch_assoc($empResult);
        mysqli_stmt_close($empStmt);
        
        if ($employee) {
            $message = sprintf(
                'Geofence Violation Alert: %s %s (%s) has %d violations today',
                $employee['first_name'],
                $employee['last_name'],
                $employee['employee_code'],
                $violationCount
            );
            
            $notifSql = "INSERT INTO admin_notifications (type, message, employee_id, created_at, status) 
                        VALUES ('Geofence Violation', ?, ?, NOW(), 'unread')";
            $notifStmt = mysqli_prepare($db, $notifSql);
            if ($notifStmt) {
                mysqli_stmt_bind_param($notifStmt, 'si', $message, $employeeId);
                mysqli_stmt_execute($notifStmt);
                mysqli_stmt_close($notifStmt);
            }
        }
    }
}

$branchId = isset($_POST['branch_id']) ? (int)$_POST['branch_id'] : (isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : 0);
$branchName = trim((string)($_POST['branch_name'] ?? $_GET['branch_name'] ?? ''));
$lat = $_POST['lat'] ?? $_GET['lat'] ?? null;
$lng = $_POST['lng'] ?? $_GET['lng'] ?? null;
$employeeId = isset($_POST['employee_id']) ? (int)$_POST['employee_id'] : (isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0);
$attendanceId = isset($_POST['attendance_id']) ? (int)$_POST['attendance_id'] : (isset($_GET['attendance_id']) ? (int)$_GET['attendance_id'] : 0);
$accuracy = isset($_POST['accuracy']) ? (float)$_POST['accuracy'] : (isset($_GET['accuracy']) ? (float)$_GET['accuracy'] : null);
$gpsTimestamp = isset($_POST['gps_timestamp']) ? (int)$_POST['gps_timestamp'] : (isset($_GET['gps_timestamp']) ? (int)$_GET['gps_timestamp'] : null);
$deviceInfo = trim((string)($_POST['device_info'] ?? $_GET['device_info'] ?? ''));
$actionType = trim((string)($_POST['action_type'] ?? $_GET['action_type'] ?? 'validation'));

if ($lat === null || $lng === null) {
    echo json_encode(['success' => false, 'message' => 'Missing lat or lng']);
    exit;
}

$lat = (float)$lat;
$lng = (float)$lng;

if ($branchId <= 0 && $branchName === '') {
    echo json_encode(['success' => false, 'message' => 'Missing branch_id or branch_name']);
    exit;
}

if ($branchId > 0) {
    $sql = "SELECT id, branch_name, lat, `long`, geofence_radius_meters
            FROM branches
            WHERE id = ?
            LIMIT 1";
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $branchId);
} else {
    $sql = "SELECT id, branch_name, lat, `long`, geofence_radius_meters
            FROM branches
            WHERE branch_name = ?
            LIMIT 1";
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 's', $branchName);
}

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed', 'error' => mysqli_error($db)]);
    exit;
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$branch = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

if (!$branch) {
    echo json_encode(['success' => false, 'message' => 'Branch not found']);
    exit;
}

$branchLat = isset($branch['lat']) ? (float)$branch['lat'] : 0.0;
$branchLng = isset($branch['long']) ? (float)$branch['long'] : 0.0;
$radius = (int)($branch['geofence_radius_meters'] ?? 1000);

if (!$branchLat || !$branchLng) {
    echo json_encode([
        'success' => true,
        'is_valid' => false,
        'distance_meters' => null,
        'radius_meters' => 1000,
        'remaining_meters' => 0,
        'outside_by_meters' => null,
        'enforcement' => 'soft',
        'action' => 'warn',
        'can_override' => true,
        'message' => 'Branch location not set',
        'branch' => [
            'id' => (int)$branch['id'],
            'name' => $branch['branch_name'],
            'latitude' => $branch['lat'],
            'longitude' => $branch['long'],
        ]
    ]);
    exit;
}

// Phase 2: Accuracy and timestamp validation
$isFlaggedAccuracy = ($accuracy !== null && $accuracy > 100);
$timestampValidation = ['is_valid' => true]; // Default to valid if not provided
if ($gpsTimestamp !== null) {
    $timestampValidation = validateLocationTimestamp($gpsTimestamp);
}

// Check for location spoofing
$isLocationSpoofed = !$timestampValidation['is_valid'];

$hardRoles = ['Admin', 'Super Admin', 'Manager', 'Supervisor'];
$enforcement = 'soft';
$employeeRole = '';
$canOverride = false;

if ($employeeId > 0) {
    $roleStmt = mysqli_prepare($db, "SELECT position FROM employees WHERE id = ? LIMIT 1");
    if ($roleStmt) {
        mysqli_stmt_bind_param($roleStmt, 'i', $employeeId);
        mysqli_stmt_execute($roleStmt);
        $roleRes = mysqli_stmt_get_result($roleStmt);
        $empRow = $roleRes ? mysqli_fetch_assoc($roleRes) : null;
        mysqli_stmt_close($roleStmt);

        $employeeRole = $empRow ? (string)($empRow['position'] ?? '') : '';
        if (in_array($employeeRole, $hardRoles, true)) {
            $enforcement = 'hard';
            $canOverride = true;
        }
    }
}

$distance = haversineMeters($lat, $lng, $branchLat, $branchLng);
$isValid = ($distance <= $radius);

$remaining = $isValid ? ($radius - $distance) : 0;
$outsideBy = $isValid ? 0 : ($distance - $radius);

$action = $isValid ? 'allow' : 'block';
$requiresOverride = false;
$overrideReason = '';

// Phase 2: Hard enforcement logic
if (!$isValid) {
    if ($enforcement === 'hard') {
        $action = 'block';
        $requiresOverride = true;
        $overrideReason = sprintf('Outside geofence by %d meters', $outsideBy);
    } else {
        $action = 'warn';
        $requiresOverride = false;
    }
}

// Additional blocking conditions
if ($isLocationSpoofed) {
    $action = 'block';
    $overrideReason = 'Location timestamp validation failed';
    $requiresOverride = true;
}

if ($isFlaggedAccuracy) {
    $action = $action === 'allow' ? 'warn' : 'block';
    $overrideReason = ($overrideReason ? $overrideReason . '; ' : '') . 'Poor GPS accuracy';
}

// Log violation if applicable
if (!$isValid && $employeeId > 0) {
    $violationData = [
        'employee_id' => $employeeId,
        'branch_id' => $branch['id'],
        'attendance_id' => $attendanceId,
        'location_log_id' => null,
        'violation_date' => date('Y-m-d'),
        'violation_time' => date('H:i:s'),
        'latitude' => $lat,
        'longitude' => $lng,
        'distance' => (int)round($distance),
        'radius' => $radius,
        'accuracy' => $accuracy,
        'device_info' => $deviceInfo,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'is_flagged_accuracy' => $isFlaggedAccuracy ? 1 : 0
    ];
    
    logGeofenceViolation($db, $violationData);
    
    // Check violation threshold
    $violationStatus = checkViolationThreshold($db, $employeeId);
    if ($violationStatus['exceeded_threshold']) {
        $overrideReason = ($overrideReason ? $overrideReason . '; ' : '') . 'Violation threshold exceeded';
    }
}

echo json_encode([
    'success' => true,
    'is_valid' => $isValid,
    'distance_meters' => (int)round($distance),
    'radius_meters' => $radius,
    'remaining_meters' => (int)round($remaining),
    'outside_by_meters' => (int)round($outsideBy),
    'enforcement' => $enforcement,
    'action' => $action,
    'can_override' => $canOverride,
    'requires_override' => $requiresOverride,
    'override_reason' => $overrideReason,
    'employee_role' => $employeeRole,
    'accuracy' => $accuracy,
    'flagged_accuracy' => $isFlaggedAccuracy,
    'timestamp_validation' => $timestampValidation,
    'location_spoofed' => $isLocationSpoofed,
    'branch' => [
        'id' => (int)$branch['id'],
        'name' => $branch['branch_name'],
        'latitude' => $branch['lat'],
        'longitude' => $branch['long'],
    ],
]);

mysqli_close($db);
