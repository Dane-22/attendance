<?php
// login_api_simple.php - Based on your working login.php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

try {
    if (file_exists(_DIR_ . '/conn/db_connection.php')) {
        require_once _DIR_ . '/conn/db_connection.php';
    } else {
        require_once _DIR_ . '/db_connection.php';
    }

    $is_md5_hash = function($value) {
        return is_string($value) && preg_match('/^[a-f0-9]{32}$/i', $value);
    };
    
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
        $storedHash = (string)($user['password_hash'] ?? '');

        $okPassword = false;
        if ($is_md5_hash($storedHash)) {
            $okPassword = strtolower(md5($password)) === strtolower($storedHash);
        } else {
            $okPassword = password_verify($password, $storedHash);
        }

        if ($okPassword) {

            if ($is_md5_hash($storedHash)) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                if ($newHash) {
                    $upgradeStmt = mysqli_prepare($db, 'UPDATE employees SET password_hash = ? WHERE id = ? LIMIT 1');
                    if ($upgradeStmt) {
                        mysqli_stmt_bind_param($upgradeStmt, 'si', $newHash, $user['id']);
                        mysqli_stmt_execute($upgradeStmt);
                        mysqli_stmt_close($upgradeStmt);
                    }
                }
            }
            
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