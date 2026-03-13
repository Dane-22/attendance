<?php
// employee/api/get_next_employee_code.php
// API endpoint to get the next auto-generated employee code based on position

session_start();
require_once __DIR__ . '/../../conn/db_connection.php';

header('Content-Type: application/json');

// Check if user is logged in and is Super Admin
if (!isset($_SESSION['employee_code'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$sessionPosition = $_SESSION['position'] ?? '';
$sessionRole = $_SESSION['role'] ?? '';
$sessionUserRole = $_SESSION['user_role'] ?? '';

if ($sessionPosition !== 'Super Admin' && $sessionRole !== 'Super Admin' && $sessionUserRole !== 'Super Admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden: Only Super Admin can access']);
    exit();
}

// Get position from request
$position = isset($_GET['position']) ? trim($_GET['position']) : '';

if (empty($position)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Position is required']);
    exit();
}

$position = strtolower($position);
$nextCode = '';
$currentYear = date('Y');

try {
    switch ($position) {
        case 'worker':
            // Get the highest E#### code
            $query = "SELECT employee_code FROM employees WHERE employee_code REGEXP '^E[0-9]+$' ORDER BY CAST(SUBSTRING(employee_code, 2) AS UNSIGNED) DESC LIMIT 1";
            $result = mysqli_query($db, $query);
            
            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                $lastCode = $row['employee_code'];
                preg_match('/E(\d+)/', $lastCode, $matches);
                $lastNumber = isset($matches[1]) ? intval($matches[1]) : 0;
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }
            $nextCode = 'E' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            break;
            
        case 'admin':
            // Get the highest ADMIN-YYYY-#### code for current year
            $query = "SELECT employee_code FROM employees 
                      WHERE employee_code REGEXP '^ADMIN-[0-9]{4}-[0-9]+$' 
                      AND employee_code LIKE 'ADMIN-{$currentYear}-%' 
                      ORDER BY CAST(SUBSTRING_INDEX(employee_code, '-', -1) AS UNSIGNED) DESC 
                      LIMIT 1";
            $result = mysqli_query($db, $query);
            
            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                $lastCode = $row['employee_code'];
                preg_match('/ADMIN-\d{4}-(\d+)/', $lastCode, $matches);
                $lastNumber = isset($matches[1]) ? intval($matches[1]) : 0;
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }
            $nextCode = 'ADMIN-' . $currentYear . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            break;
            
        case 'engineer':
            // Get the highest ENG-YYYY-#### code for current year
            $query = "SELECT employee_code FROM employees 
                      WHERE employee_code REGEXP '^ENG-[0-9]{4}-[0-9]+$' 
                      AND employee_code LIKE 'ENG-{$currentYear}-%' 
                      ORDER BY CAST(SUBSTRING_INDEX(employee_code, '-', -1) AS UNSIGNED) DESC 
                      LIMIT 1";
            $result = mysqli_query($db, $query);
            
            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                $lastCode = $row['employee_code'];
                preg_match('/ENG-\d{4}-(\d+)/', $lastCode, $matches);
                $lastNumber = isset($matches[1]) ? intval($matches[1]) : 0;
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }
            $nextCode = 'ENG-' . $currentYear . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            break;
            
        case 'developer':
            // Get the highest DEV-YYYY-#### code for current year
            $query = "SELECT employee_code FROM employees 
                      WHERE employee_code REGEXP '^DEV-[0-9]{4}-[0-9]+$' 
                      AND employee_code LIKE 'DEV-{$currentYear}-%' 
                      ORDER BY CAST(SUBSTRING_INDEX(employee_code, '-', -1) AS UNSIGNED) DESC 
                      LIMIT 1";
            $result = mysqli_query($db, $query);
            
            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                $lastCode = $row['employee_code'];
                preg_match('/DEV-\d{4}-(\d+)/', $lastCode, $matches);
                $lastNumber = isset($matches[1]) ? intval($matches[1]) : 0;
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }
            $nextCode = 'DEV-' . $currentYear . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unsupported position for auto-generation']);
            exit();
    }
    
    echo json_encode([
        'success' => true,
        'position' => $position,
        'employee_code' => $nextCode
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
