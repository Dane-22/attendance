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

$branchId = isset($_POST['branch_id']) ? (int)$_POST['branch_id'] : (isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : 0);
$branchName = trim((string)($_POST['branch_name'] ?? $_GET['branch_name'] ?? ''));
$lat = $_POST['lat'] ?? $_GET['lat'] ?? null;
$lng = $_POST['lng'] ?? $_GET['lng'] ?? null;
$employeeId = isset($_POST['employee_id']) ? (int)$_POST['employee_id'] : (isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0);

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
$radius = (int)($branch['geofence_radius_meters'] ?? 200);

if (!$branchLat || !$branchLng) {
    echo json_encode([
        'success' => true,
        'is_valid' => false,
        'distance_meters' => null,
        'radius_meters' => $radius,
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

$hardRoles = ['Admin', 'Super Admin', 'Manager', 'Supervisor'];
$enforcement = 'soft';

if ($employeeId > 0) {
    $roleStmt = mysqli_prepare($db, "SELECT position FROM employees WHERE id = ? LIMIT 1");
    if ($roleStmt) {
        mysqli_stmt_bind_param($roleStmt, 'i', $employeeId);
        mysqli_stmt_execute($roleStmt);
        $roleRes = mysqli_stmt_get_result($roleStmt);
        $empRow = $roleRes ? mysqli_fetch_assoc($roleRes) : null;
        mysqli_stmt_close($roleStmt);

        $pos = $empRow ? (string)($empRow['position'] ?? '') : '';
        if (in_array($pos, $hardRoles, true)) {
            $enforcement = 'hard';
        }
    }
}

$distance = haversineMeters($lat, $lng, $branchLat, $branchLng);
$isValid = ($distance <= $radius);

$remaining = $isValid ? ($radius - $distance) : 0;
$outsideBy = $isValid ? 0 : ($distance - $radius);

$action = $isValid ? 'allow' : ($enforcement === 'hard' ? 'block' : 'warn');

echo json_encode([
    'success' => true,
    'is_valid' => $isValid,
    'distance_meters' => (int)round($distance),
    'radius_meters' => $radius,
    'remaining_meters' => (int)round($remaining),
    'outside_by_meters' => (int)round($outsideBy),
    'enforcement' => $enforcement,
    'action' => $action,
    'can_override' => ($enforcement === 'soft'),
    'branch' => [
        'id' => (int)$branch['id'],
        'name' => $branch['branch_name'],
        'latitude' => $branch['lat'],
        'longitude' => $branch['long'],
    ],
]);

mysqli_close($db);
