<?php
// verify_face_api.php - Verify face and return employee ID for time-in
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
    require_once __DIR__ . '/conn/db_connection.php';

    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    $image_base64 = $input['image'] ?? $_POST['image'] ?? null;
    $threshold = $input['threshold'] ?? $_POST['threshold'] ?? 0.6;
    
    if (empty($image_base64)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required field: image']);
        exit;
    }
    
    error_log("Verify face API request: image_present=" . (!empty($image_base64) ? 'yes' : 'no') . " image_length=" . strlen((string)$image_base64) . " threshold=" . floatval($threshold));

    // Call Python face service to verify
    $face_service_url = 'http://face-recog.xandree.com/verify';
    
    $ch = curl_init($face_service_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'image' => $image_base64,
        'threshold' => floatval($threshold)
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

    error_log("Verify face API upstream response: http_code=$http_code response_length=" . strlen((string)$response));
    
    if ($http_code !== 200) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Face verification failed on service']);
        exit;
    }
    
    $face_result = json_decode($response, true);
    
    if (!$face_result || !$face_result['success']) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => $face_result['message'] ?? 'Face verification failed'
        ]);
        exit;
    }
    
    if (!$face_result['matched']) {
        error_log("Verify face API no match returned by service");
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'matched' => false,
            'message' => $face_result['message'] ?? 'Face not recognized'
        ]);
        exit;
    }
    
    // Get employee details from database
    $employee_id = intval($face_result['employee_id']);
    error_log("Verify face API matched employee: employee_id=$employee_id confidence=" . ($face_result['confidence'] ?? 'n/a'));
    
    $sql = "SELECT id, first_name, last_name, email, position FROM employees WHERE id = ?";
    $stmt = mysqli_prepare($db, $sql);
    
    if (!$stmt) {
        throw new Exception("Database prepare failed: " . mysqli_error($db));
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $employee_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $employee = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$employee) {
        error_log("Verify face API employee not found after match: employee_id=$employee_id");
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'matched' => true,
        'employee_id' => $employee_id,
        'confidence' => $face_result['confidence'],
        'employee' => [
            'id' => $employee['id'],
                'firstname' => $employee['first_name'],
            'lastname' => $employee['last_name'],
            'email' => $employee['email'],
            'position' => $employee['position']
        ],
        'message' => 'Face verified successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Verify face API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
