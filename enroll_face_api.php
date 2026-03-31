<?php
// enroll_face_api.php - Enroll employee face for recognition
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    require_once __DIR__ . '/db_connection.php';

    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    $employee_id = $input['employee_id'] ?? $_POST['employee_id'] ?? null;
    $image_base64 = $input['image'] ?? $_POST['image'] ?? null;
    
    if (empty($employee_id) || empty($image_base64)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields: employee_id and image']);
        exit;
    }
    
    $employee_id = intval($employee_id);
    
    // Call Python face service to enroll
    $face_service_url = getenv('FACE_SERVICE_URL') ?: 'http://localhost:5000/enroll';
    
    $ch = curl_init($face_service_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'employee_id' => $employee_id,
        'image' => $image_base64
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        error_log("Face service connection error: " . $curl_error);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Face recognition service unavailable']);
        exit;
    }
    
    if ($http_code !== 200) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Face enrollment failed on service']);
        exit;
    }
    
    $face_result = json_decode($response, true);
    
    if (!$face_result || !$face_result['success']) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => $face_result['message'] ?? 'Face enrollment failed'
        ]);
        exit;
    }
    
    // Store face embedding in database
    $face_embedding = json_encode($face_result['face_embedding']);
    $face_enrolled_at = date('Y-m-d H:i:s');
    
    $update_sql = "UPDATE employees SET face_embedding = ?, face_enrolled_at = ? WHERE id = ?";
    $stmt = mysqli_prepare($db, $update_sql);
    
    if (!$stmt) {
        throw new Exception("Database prepare failed: " . mysqli_error($db));
    }
    
    mysqli_stmt_bind_param($stmt, 'ssi', $face_embedding, $face_enrolled_at, $employee_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Database update failed: " . mysqli_stmt_error($stmt));
    }
    
    mysqli_stmt_close($stmt);
    
    echo json_encode([
        'success' => true,
        'message' => 'Face enrolled successfully',
        'employee_id' => $employee_id,
        'enrolled_at' => $face_enrolled_at
    ]);
    
} catch (Exception $e) {
    error_log("Enroll face API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
