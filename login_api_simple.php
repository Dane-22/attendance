<?php
// login_api_simple.php - Based on your working login.php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

try {
    // Include database connection (adjust path as needed)
    require_once __DIR__ . '/conn/db_connection.php';
    
    // Get POST data exactly like your login.php
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    $daily_branch = $_POST['branch_name'] ?? '';
    
    // Debug log
    error_log("API: identifier=$identifier, branch=$daily_branch");
    
    // Validation
    if (empty($identifier) || empty($password) || empty($daily_branch)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please fill in all fields (Identifier, Password, and Branch).',
            'debug' => [
                'received_id' => $identifier,
                'received_branch' => $daily_branch,
                'post_data' => $_POST
            ]
        ]);
        exit;
    }
    
    // Check user (exactly like your login.php)
    if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
        $sql = "SELECT * FROM employees WHERE email = ? AND status = 'Active'";
    } else {
        $sql = "SELECT * FROM employees WHERE employee_code = ? AND status = 'Active'";
    }
    
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, "s", $identifier);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    
    if ($user) {
        // Verify password (exactly like your login.php)
        if (md5($password) === $user['password_hash']) {
            
            // Success response for mobile app
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'user_data' => [
                    'id' => $user['id'],
                    'employee_code' => $user['employee_code'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'position' => $user['position'],
                    'assigned_branch' => $user['branch_name'],
                    'daily_branch' => $daily_branch
                ]
            ]);
            
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid password.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Account not found or is inactive.']);
    }
    
    mysqli_stmt_close($stmt);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?>
