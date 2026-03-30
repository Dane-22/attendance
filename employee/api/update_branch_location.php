<?php
/**
 * Update Branch Location (Admin Only)
 * API Endpoint: employee/api/update_branch_location.php
 * 
 * Allows admins to set/update branch GPS coordinates and geofence radius
 */

session_start();
require_once __DIR__ . '/../../conn/db_connection.php';
require_once __DIR__ . '/../../functions.php';

header('Content-Type: application/json');

// Verify admin permissions
$allowed_positions = ['Admin', 'Super Admin'];
$user_position = $_SESSION['position'] ?? '';
$user_id = $_SESSION['employee_id'] ?? null;

if (!in_array($user_position, $allowed_positions)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized: Admin access required'
    ]);
    exit;
}

// Get POST parameters
$branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : null;
$lat = isset($_POST['latitude']) ? floatval($_POST['latitude']) : null;
$lng = isset($_POST['longitude']) ? floatval($_POST['longitude']) : null;
$radius = isset($_POST['radius']) ? intval($_POST['radius']) : 200;
$address = isset($_POST['branch_address']) ? $_POST['branch_address'] : null;

// Validate required parameters
if (!$branch_id || is_null($lat) || is_null($lng)) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters: branch_id, latitude, longitude'
    ]);
    exit;
}

// Validate coordinates are in valid ranges
if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid coordinates: Latitude must be -90 to 90, Longitude -180 to 180'
    ]);
    exit;
}

// Validate radius
if ($radius < 50 || $radius > 1000) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid radius: Must be between 50 and 1000 meters'
    ]);
    exit;
}

try {
    // Check if branch exists
    $check_sql = "SELECT id, branch_name FROM branches WHERE id = ?";
    $check_stmt = mysqli_prepare($db, $check_sql);
    mysqli_stmt_bind_param($check_stmt, 'i', $branch_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $branch = mysqli_fetch_assoc($check_stmt);
    mysqli_stmt_close($check_stmt);

    if (!$branch) {
        echo json_encode([
            'success' => false,
            'message' => 'Branch not found'
        ]);
        exit;
    }

    // Update branch location
    // Note: lat/long are stored as VARCHAR in your schema
    $update_sql = "UPDATE branches 
                   SET lat = ?, 
                       `long` = ?, 
                       geofence_radius_meters = ?, 
                       location_verified = 1";
    
    $params = [strval($lat), strval($lng), $radius];
    $types = 'ssi';
    
    // Optionally update address if provided
    if ($address !== null) {
        $update_sql .= ", branch_address = ?";
        $params[] = $address;
        $types .= 's';
    }
    
    $update_sql .= " WHERE id = ?";
    $params[] = $branch_id;
    $types .= 'i';

    $stmt = mysqli_prepare($db, $update_sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$success) {
        throw new Exception('Failed to update branch location: ' . mysqli_error($db));
    }

    // Log admin activity
    $log_details = sprintf(
        "Updated branch #%d (%s) location to (%f, %f) with %dm radius",
        $branch_id,
        $branch['branch_name'],
        $lat,
        $lng,
        $radius
    );
    
    logActivity($db, $user_id, 'Branch Location Updated', $log_details);

    // Notify admins if this was the first time location was set
    if ($branch && empty($branch['lat'])) {
        // Create notification for branch location now being set
        $notify_sql = "INSERT INTO notifications 
                       (recipient_id, title, message, type, related_id, related_type) 
                       SELECT id, 
                              'Branch Location Updated',
                              ?,
                              'System',
                              ?,
                              'branch'
                       FROM employees 
                       WHERE position IN ('Admin', 'Super Admin')";
        
        $notify_msg = "Branch '{$branch['branch_name']}' location has been set.";
        $notify_stmt = mysqli_prepare($db, $notify_sql);
        mysqli_stmt_bind_param($notify_stmt, 'si', $notify_msg, $branch_id);
        mysqli_stmt_execute($notify_stmt);
        mysqli_stmt_close($notify_stmt);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Branch location updated successfully',
        'data' => [
            'branch_id' => $branch_id,
            'branch_name' => $branch['branch_name'],
            'latitude' => $lat,
            'longitude' => $lng,
            'radius_meters' => $radius,
            'location_verified' => true,
            'updated_by' => $user_id
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

mysqli_close($db);
?>
