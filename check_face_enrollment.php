<?php
// check_face_enrollment.php - Check if employee has face enrolled
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

try {
    require_once __DIR__ . '/db_connection.php';

    // Get employee_id from query or POST
    $employee_id = null;
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $employee_id = $_GET['employee_id'] ?? null;
    } else {
        $input = json_decode(file_get_contents('php://input'), true);
        $employee_id = $input['employee_id'] ?? $_POST['employee_id'] ?? null;
    }
    
    if (empty($employee_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required field: employee_id']);
        exit;
    }
    
    $employee_id = intval($employee_id);
    
    // Check if face is enrolled in database
    $sql = "SELECT face_embedding, face_enrolled_at FROM employees WHERE id = ?";
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
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        exit;
    }
    
    $is_enrolled = !empty($employee['face_embedding']);
    
    echo json_encode([
        'success' => true,
        'employee_id' => $employee_id,
        'enrolled' => $is_enrolled,
        'enrolled_at' => $employee['face_enrolled_at'] ?? null,
        'message' => $is_enrolled ? 'Face is enrolled' : 'Face not enrolled'
    ]);
    
} catch (Exception $e) {
    error_log("Check face enrollment API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
