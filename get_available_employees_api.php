<?php
// get_available_employees.php
require_once __DIR__ . '/conn/db_connection.php';

// --- 1. SOLUSYON SA 403 / CORS ERROR ---
// Pinapayagan nito ang Android app mo na mag-request sa server
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header('Content-Type: application/json');

// Handle OPTIONS request (pre-flight check ng Android)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

// --- 2. ERROR LOGGER ---
$log_file = "api_debug.log";
$current_time = date("Y-m-d H:i:s");
$date_today = date("Y-m-d");

// --- 3. GET BRANCH NAME FROM REQUEST ---
// Ang Android ay magpapadala ng 'branch_name' sa POST or GET
$branch_name = isset($_REQUEST['branch_name']) ? $_REQUEST['branch_name'] : '';

$log_entry = "[$current_time] Branch: $branch_name | Date: $date_today" . PHP_EOL;
file_put_contents($log_file, $log_entry, FILE_APPEND);

// --- 4. DATABASE QUERY (Filtered by Branch) ---
if (empty($branch_name)) {
    echo json_encode(["success" => false, "message" => "Branch name is required"]);
    exit;
}

// SQL: ACTIVE Employees sa specific Branch na wala pang attendance today
$sql = "SELECT id, employee_code, first_name, last_name, branch_name, position 
        FROM employees 
        WHERE branch_name = ? 
        AND id NOT IN (SELECT employee_id FROM attendance WHERE attendance_date = ?)
        AND status = 'Active'";

$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, "ss", $branch_name, $date_today);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$employees = [];
while ($row = mysqli_fetch_assoc($result)) {
    $employees[] = $row;
}

// --- 5. RESPONSE ---
echo json_encode($employees); // Diretsong list para madali basahin ng Retrofit List<Employee>

mysqli_stmt_close($stmt);
?>