<?php
// Siguraduhin na ang path ay tama para sa connection file
require_once __DIR__ . '/conn/db_connection.php';

// I-set ang header para laging JSON ang output
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
    exit;
}

// 1. BASAHIN ANG INPUT (Para gumana sa Kotlin/JSON at Postman)
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// --- DITO MO ILALAGAY YUNG LOGGING PARA MA-CHECK ANG PHONE DATA ---
$log_file = "api_debug.log";
$current_time = date("Y-m-d H:i:s");

// Isulat kung ano ang pumasok na RAW data (kahit empty o may typo)
$log_entry = "[$current_time] Received: " . ($json ?: "No JSON found, checking POST") . PHP_EOL;
file_put_contents($log_file, $log_entry, FILE_APPEND);
// --- END NG LOGGING ---

// 2. I-ASSIGN ANG MGA VALUES
// Gagamit tayo ng null coalescing (??) para i-check kung nasa JSON o sa $_POST ang data
$employee_id     = $data['employee_id']     ?? $_POST['employee_id']     ?? null;
$status          = $data['status']          ?? $_POST['status']          ?? null;
$branch_name     = $data['branch_name']     ?? $_POST['branch_name']     ?? null;
$attendance_date = $data['attendance_date'] ?? $_POST['attendance_date'] ?? null;

// 3. VALIDATION: Siguraduhin na hindi empty ang mga kailangan
if (!$employee_id || !$status || !$branch_name || !$attendance_date) {
    echo json_encode([
        "success" => false, 
        "message" => "Missing required fields",
        "debug" => [
            "emp_id" => $employee_id,
            "status" => $status,
            "branch" => $branch_name,
            "date"   => $attendance_date
        ]
    ]);
    exit;
}

// 4. DATABASE INSERT
// Tandaan: Gamitin ang variable name na nasa db_connection.php (hal. $conn o $db)
$sql = "INSERT INTO attendance (employee_id, status, branch_name, attendance_date) VALUES (?,?,?,?)";

// Dito tayo nagka-error kanina, siguraduhin na '$db' ang variable name sa connection file mo
$stmt = $db->prepare($sql); 

if ($stmt) {
    $stmt->bind_param("isss", $employee_id, $status, $branch_name, $attendance_date);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Attendance recorded"]);
    } else {
        echo json_encode(["success" => false, "message" => "SQL Execution Error: " . $stmt->error]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Database Prepare Error: " . $db->error]);
}