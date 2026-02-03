<?php
// login_api.php - FIXED VERSION WITH DUAL PASSWORD SUPPORT
require_once __DIR__ . "/conn/db_connection.php";
header('Content-Type: application/json');

// --- 1. START NG ERROR LOGGER (Kusa itong gagawa ng api_debug.log) ---
$log_file = "api_debug.log";
$current_time = date("Y-m-d H:i:s");

// Basahin ang JSON input (kung galing sa Kotlin)
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// I-log natin kung ano ang pumasok na data para sa debugging
if (!empty($json)) {
    $log_entry = "[$current_time] Received JSON: " . $json . PHP_EOL;
} else {
    $log_entry = "[$current_time] Received POST: " . json_encode($_POST) . PHP_EOL;
}
file_put_contents($log_file, $log_entry, FILE_APPEND);
// --- END NG LOGGER ---

// --- 2. PAG-ASSIGN NG MGA VALUES ---
// Prioritize $_POST for URL-encoded data (from React Native)
$identifier   = $_POST['identifier']   ?? $data['identifier']   ?? null;
$password     = $_POST['password']     ?? $data['password']     ?? null;
$daily_branch = $_POST['branch_name'] ?? $data['branch_name'] ?? null;

// --- 3. VALIDATION ---
// Debug: Log what we actually received
error_log("DEBUG: \$_POST = " . print_r($_POST, true));
error_log("DEBUG: \$data = " . print_r($data, true));
error_log("DEBUG: identifier = " . var_export($identifier, true));
error_log("DEBUG: password = " . var_export($password, true));
error_log("DEBUG: branch_name = " . var_export($daily_branch, true));

if (!$identifier || !$password || !$daily_branch) {
    echo json_encode([
        "success" => false, 
        "message" => "Please fill in all fields (Identifier, Password, and Branch).",
        "debug" => [
            "received_id" => $identifier,
            "received_branch" => $daily_branch,
            "post_data" => $_POST,
            "json_data" => $data
        ]
    ]);
    exit;
}

// --- 4. DATABASE QUERY (Base sa employees table mo) ---
if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
    $sql = "SELECT * FROM employees WHERE email = ? AND status = 'Active' LIMIT 1";
} else {
    $sql = "SELECT * FROM employees WHERE employee_code = ? AND status = 'Active' LIMIT 1";
}

$stmt = mysqli_prepare($db, $sql);

/** * FIX: Isang "s" at isang variable lang ang dapat i-bind 
 * dahil isa lang ang "?" sa SQL query natin sa itaas.
 */
mysqli_stmt_bind_param($stmt, "s", $identifier); 

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// --- 5. DUAL PASSWORD VERIFICATION (MD5 at password_hash) ---
if ($user) {
    $stored_hash = $user['password_hash'];
    $password_valid = false;
    
    // Unang check: password_hash() format (starts with $2y$)
    if (strpos($stored_hash, '$2y$') === 0) {
        // It's a password_hash() format - use password_verify()
        if (password_verify($password, $stored_hash)) {
            $password_valid = true;
        }
    } else {
        // It's NOT a password_hash() format - try MD5
        if (md5($password) === $stored_hash) {
            $password_valid = true;
            
            // AUTO-UPGRADE: Convert MD5 hash to password_hash() for next login
            $new_hash = password_hash($password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE employees SET password_hash = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($db, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "si", $new_hash, $user['id']);
            mysqli_stmt_execute($update_stmt);
            mysqli_stmt_close($update_stmt);
            
            // Log the upgrade
            file_put_contents($log_file, "[$current_time] Upgraded password for user ID: {$user['id']} from MD5 to password_hash()\n", FILE_APPEND);
        }
    }
    
    if ($password_valid) {
        // Log attendance for web interface compatibility
        $check_sql = "SELECT id FROM attendance 
                     WHERE employee_id = ? AND attendance_date = CURDATE()";
        $check_stmt = mysqli_prepare($db, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "i", $user['id']);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        // INSERT OR UPDATE ATTENDANCE WITH DAILY BRANCH
        if (mysqli_stmt_num_rows($check_stmt) == 0) {
            // FIRST TIME LOGIN TODAY - INSERT NEW
            $att_sql = "INSERT INTO attendance 
                       (employee_id, branch_name, attendance_date, status, created_at) 
                       VALUES (?, ?, CURDATE(), 'Present', NOW())";
            $att_stmt = mysqli_prepare($db, $att_sql);
            mysqli_stmt_bind_param($att_stmt, "is", $user['id'], $daily_branch);
        } else {
            // MAY ATTENDANCE NA - UPDATE BRANCH
            $att_sql = "UPDATE attendance 
                       SET branch_name = ?, updated_at = NOW() 
                       WHERE employee_id = ? AND attendance_date = CURDATE()";
            $att_stmt = mysqli_prepare($db, $att_sql);
            mysqli_stmt_bind_param($att_stmt, "si", $daily_branch, $user['id']);
        }
        
        mysqli_stmt_execute($att_stmt);
        mysqli_stmt_close($check_stmt);
        
        // Response para sa Mobile App
        echo json_encode([
            "success" => true,
            "message" => "Login successful",
            "user_data" => [
                "id"            => $user['id'],
                "employee_code" => $user['employee_code'],
                "first_name"    => $user['first_name'],
                "last_name"     => $user['last_name'],
                "position"      => $user['position'],
                "assigned_branch" => $user['branch_name'],
                "daily_branch"  => $daily_branch
            ]
        ]);
        
        // Log successful login
        file_put_contents($log_file, "[$current_time] Successful login: {$user['employee_code']} from branch: {$daily_branch}\n", FILE_APPEND);
    } else {
        echo json_encode([
            "success" => false, 
            "message" => "Invalid password.",
            "debug_hash" => substr($stored_hash, 0, 20) . "..." // Show first 20 chars for debugging
        ]);
        file_put_contents($log_file, "[$current_time] Failed login attempt for: {$identifier}\n", FILE_APPEND);
    }
} else {
    echo json_encode(["success" => false, "message" => "Account not found or is currently Inactive."]);
    file_put_contents($log_file, "[$current_time] Account not found: {$identifier}\n", FILE_APPEND);
}

// Close statement
if (isset($stmt)) {
    mysqli_stmt_close($stmt);
}
?>