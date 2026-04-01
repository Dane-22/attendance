<?php
require_once __DIR__ . '/conn/db_connection.php';

header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Check if requesting all branches for selection
$getAll = isset($_GET['all']) && $_GET['all'] === '1';

if ($getAll) {
    // Return all branches with full details for branch selection
    // Note: using 'lat' and 'long' column names as they exist in DB
    $sql = "SELECT id, branch_name, branch_address, lat as latitude, `long` as longitude, 
            COALESCE(geofence_radius_meters, 200) as geofence_radius 
            FROM branches 
            WHERE is_active = 1 
            ORDER BY branch_name ASC";
    
    $result = mysqli_query($db, $sql);
    
    if (!$result) {
        // Query failed - return error
        echo json_encode([
            'success' => false, 
            'message' => 'Database query failed: ' . mysqli_error($db),
            'branches' => []
        ]);
        exit;
    }
    
    $branches = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $branches[] = [
            'id' => $row['id'],
            'branch_name' => $row['branch_name'],
            'branch_address' => $row['branch_address'],
            'latitude' => $row['latitude'],
            'longitude' => $row['longitude'],
            'geofence_radius' => intval($row['geofence_radius']) ?: 200
        ];
    }
    
    echo json_encode(['success' => true, 'branches' => $branches]);
    exit;
}

// Get specific employee's branch (original functionality)
$employeeId = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;

if ($employeeId > 0) {
    $sql = "SELECT b.id, b.branch_name, b.branch_address, b.lat as latitude, b.`long` as longitude, 
            COALESCE(b.geofence_radius_meters, 200) as geofence_radius 
            FROM employees e 
            LEFT JOIN branches b ON b.id = e.branch_id 
            WHERE e.id = ? AND b.is_active = 1 
            LIMIT 1";
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $employeeId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        echo json_encode([
            'success' => true,
            'branch' => [
                'id' => $row['id'],
                'branch_name' => $row['branch_name'],
                'branch_address' => $row['branch_address'],
                'latitude' => $row['latitude'],
                'longitude' => $row['longitude'],
                'geofence_radius' => intval($row['geofence_radius']) ?: 200
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Branch not found']);
    }
    mysqli_stmt_close($stmt);
    exit;
}

// Default: return simple array of branch names (backward compatibility)
$sql = "SELECT branch_name FROM branches WHERE is_active = 1 ORDER BY branch_name ASC";
$result = mysqli_query($db, $sql);

$branches = [];
while ($row = mysqli_fetch_assoc($result)) {
    $branches[] = $row['branch_name'];
}

echo json_encode($branches);
?>