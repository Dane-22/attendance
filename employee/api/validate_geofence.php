<?php
/**
 * Validate Geofence - Check if coordinates are within branch radius
 * API Endpoint: employee/api/validate_geofence.php
 * 
 * Validates employee location against branch geofence
 * Returns distance, validation status, and remaining distance
 */

require_once __DIR__ . '/../../conn/db_connection.php';

header('Content-Type: application/json');

// Get parameters (support both GET and POST)
$branch_id = isset($_REQUEST['branch_id']) ? intval($_REQUEST['branch_id']) : null;
$lat = isset($_REQUEST['lat']) ? floatval($_REQUEST['lat']) : null;
$lng = isset($_REQUEST['lng']) ? floatval($_REQUEST['lng']) : null;
$employee_id = isset($_REQUEST['employee_id']) ? intval($_REQUEST['employee_id']) : null;

// Validate required parameters
if (!$branch_id || !$lat || !$lng) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters: branch_id, lat, lng'
    ]);
    exit;
}

try {
    // Get branch location and geofence radius
    // Note: lat/long are stored as VARCHAR in your schema, so we need to cast them
    $sql = "SELECT 
                id, 
                branch_name, 
                CAST(lat AS DECIMAL(10,8)) as latitude, 
                CAST(`long` AS DECIMAL(11,8)) as longitude,
                geofence_radius_meters,
                location_verified
            FROM branches 
            WHERE id = ? 
            AND is_active = 1";
    
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $branch_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $branch = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$branch) {
        echo json_encode([
            'success' => false,
            'message' => 'Branch not found or inactive'
        ]);
        exit;
    }

    // Check if branch has coordinates set
    if (is_null($branch['latitude']) || is_null($branch['longitude'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Branch location not set',
            'branch_name' => $branch['branch_name'],
            'needs_location_setup' => true
        ]);
        exit;
    }

    // Calculate distance using Haversine formula
    $distance = haversineDistance(
        $lat, 
        $lng, 
        floatval($branch['latitude']), 
        floatval($branch['longitude'])
    );

    // Use default radius if not set
    $radius = $branch['geofence_radius_meters'] ?? 1000;
    $is_valid = $distance <= $radius;

    // Determine enforcement type based on employee role (if employee_id provided)
    $enforcement = 'soft'; // default
    if ($employee_id) {
        $emp_sql = "SELECT position FROM employees WHERE id = ?";
        $emp_stmt = mysqli_prepare($db, $emp_sql);
        mysqli_stmt_bind_param($emp_stmt, 'i', $employee_id);
        mysqli_stmt_execute($emp_stmt);
        $emp_result = mysqli_stmt_get_result($emp_stmt);
        $employee = mysqli_fetch_assoc($emp_result);
        mysqli_stmt_close($emp_stmt);

        if ($employee) {
            $high_compliance_roles = ['Admin', 'Super Admin', 'Manager', 'Supervisor'];
            if (in_array($employee['position'], $high_compliance_roles)) {
                $enforcement = 'hard';
            }
        }
    }

    // Calculate remaining distance
    $remaining = max(0, $radius - $distance);

    echo json_encode([
        'success' => true,
        'is_valid' => $is_valid,
        'distance_meters' => round($distance),
        'radius_meters' => $radius,
        'remaining_meters' => round($remaining),
        'outside_by_meters' => $is_valid ? 0 : round($distance - $radius),
        'enforcement' => $enforcement,
        'action' => $is_valid ? 'allow' : ($enforcement === 'hard' ? 'block' : 'warn'),
        'can_override' => $enforcement === 'soft' && !$is_valid,
        'branch' => [
            'id' => $branch['id'],
            'name' => $branch['branch_name'],
            'latitude' => floatval($branch['latitude']),
            'longitude' => floatval($branch['longitude']),
            'location_verified' => $branch['location_verified']
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

mysqli_close($db);

/**
 * Calculate distance between two coordinates using Haversine formula
 * @param float $lat1 Latitude of point 1
 * @param float $lon1 Longitude of point 1
 * @param float $lat2 Latitude of point 2
 * @param float $lon2 Longitude of point 2
 * @return float Distance in meters
 */
function haversineDistance($lat1, $lon1, $lat2, $lon2) {
    $R = 6371000; // Earth's radius in meters
    
    $lat1Rad = deg2rad($lat1);
    $lat2Rad = deg2rad($lat2);
    $deltaLat = deg2rad($lat2 - $lat1);
    $deltaLon = deg2rad($lon2 - $lon1);
    
    $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
         cos($lat1Rad) * cos($lat2Rad) *
         sin($deltaLon / 2) * sin($deltaLon / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $R * $c;
}
?>
