<?php
// login_api.php - FIXED VERSION
require_once __DIR__ . '/conn/db_connection.php';
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
$identifier   = $data['identifier']   ?? $_POST['identifier']   ?? null;
$password     = $data['password']     ?? $_POST['password']     ?? null;
$daily_branch = $data['branch_name'] ?? $_POST['branch_name'] ?? null;

// --- 3. VALIDATION ---
if (!$identifier || !$password || !$daily_branch) {
    echo json_encode([
        "success" => false, 
        "message" => "Please fill in all fields (Identifier, Password, and Branch).",
        "debug" => [
            "received_id" => $identifier,
            "received_branch" => $daily_branch
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

// --- 5. PASSWORD VERIFICATION ---
if ($user) {
    // MD5 ang gamit mo sa web code base sa database screenshot mo
    if (md5($password) === $user['password_hash']) {
        
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
    } else {
        echo json_encode(["success" => false, "message" => "Invalid password."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Account not found or is currently Inactive."]);
}
?>