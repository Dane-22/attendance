<?php
// get_all_employees_with_status.php
require_once __DIR__ . '/conn/db_connection.php';

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

// Get parameters
$branch_name = isset($_REQUEST['branch_name']) ? trim($_REQUEST['branch_name']) : '';
$date_today = date("Y-m-d");

if (empty($branch_name)) {
    echo json_encode(["success" => false, "message" => "Branch name is required"]);
    exit;
}

// Get ALL employees with their today's attendance status
$sql = "SELECT 
            e.id,
            e.employee_code,
            e.first_name,
            e.last_name,
            e.branch_name,
            e.position,
            COALESCE(a.status, 'Not Marked') as today_status,
            COALESCE(a.is_auto_absent, 0) as is_auto_absent,
            CASE 
                WHEN a.status = 'Present' THEN 1
                WHEN a.status = 'Absent' THEN 0
                ELSE -1  # Not marked yet
            END as can_mark_present
        FROM employees e
        LEFT JOIN attendance a ON e.id = a.employee_id 
            AND a.attendance_date = ?
        WHERE e.branch_name = ? 
        AND e.status = 'Active'
        ORDER BY e.employee_code";

$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, "ss", $date_today, $branch_name);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$employees = [];
while ($row = mysqli_fetch_assoc($result)) {
    $employees[] = [
        'id' => (int)$row['id'],
        'employee_code' => $row['employee_code'],
        'first_name' => $row['first_name'],
        'last_name' => $row['last_name'],
        'branch_name' => $row['branch_name'],
        'position' => $row['position'],
        'today_status' => $row['today_status'],
        'is_auto_absent' => (bool)$row['is_auto_absent'],
        'can_mark_present' => (int)$row['can_mark_present'] == -1  // True if not marked yet or is absent
    ];
}

echo json_encode($employees);

mysqli_stmt_close($stmt);
mysqli_close($db);
?>