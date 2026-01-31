<?php
// submit_attendance.php
require_once __DIR__ . '/conn/db_connection.php';
header('Content-Type: application/json');

// --- 1. START NG ERROR LOGGER ---
$log_file = "api_debug.log";
$current_time = date("Y-m-d H:i:s");

// I-log natin ang data na pumasok mula sa POST
$log_entry = "[$current_time] Attendance Request: " . json_encode($_POST) . PHP_EOL;
file_put_contents($log_file, $log_entry, FILE_APPEND);
// --- END NG LOGGER ---

// --- 2. PAG-ASSIGN NG MGA VALUES ---
$employee_id     = $_POST['employee_id']   ?? null;
$branch_selected = $_POST['branch_name']   ?? null; 
$status          = "Present"; // Default status base sa log mo
$date            = date("Y-m-d");

// --- 3. VALIDATION ---
if (!$employee_id || !$branch_selected) {
    echo json_encode(["success" => false, "message" => "Missing data: employee_id or branch_name."]);
    exit;
}

// --- 4. ANTI-DUPLICATE CHECK ---
// Chine-check kung may record na ang employee sa date na ito
$check_sql = "SELECT id FROM attendance WHERE employee_id = ? AND attendance_date = ?";
$check_stmt = mysqli_prepare($db, $check_sql);
mysqli_stmt_bind_param($check_stmt, "is", $employee_id, $date);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

if (mysqli_num_rows($check_result) > 0) {
    echo json_encode(["success" => false, "message" => "Employee already timed-in today."]);
    mysqli_stmt_close($check_stmt);
    exit;
}
mysqli_stmt_close($check_stmt);

// --- 5. INSERT ATTENDANCE ---
$insert_sql = "INSERT INTO attendance (employee_id, branch_name, attendance_date, status) VALUES (?, ?, ?, ?)";
$insert_stmt = mysqli_prepare($db, $insert_sql);
mysqli_stmt_bind_param($insert_stmt, "isss", $employee_id, $branch_selected, $date, $status);

if (mysqli_stmt_execute($insert_stmt)) {
    echo json_encode([
        "success" => true, 
        "message" => "Attendance saved!",
        "debug_info" => [
            "id" => $employee_id,
            "branch" => $branch_selected,
            "date" => $date
        ]
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Database error during insert."]);
}

mysqli_stmt_close($insert_stmt);
?>