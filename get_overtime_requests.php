<?php
/**
 * Get Overtime Requests API
 * 
 * Endpoint: POST /get_overtime_requests.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

try {
    // Include database connection
    require_once __DIR__ . '/conn/db_connection.php';

    // Get input data (support both form-data, JSON, and query params)
    $input = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = $_POST;
        if (empty($input)) {
            $json = file_get_contents('php://input');
            $input = json_decode($json, true) ?? [];
        }
    } else {
        $input = $_GET;
    }

    // Build query
    $where = ["1=1"];
    $params = [];
    $types = "";

    // Filter by employee_id
    if (!empty($input['employee_id'])) {
        $where[] = "r.employee_id = ?";
        $params[] = intval($input['employee_id']);
        $types .= "i";
    }

    // Filter by status
    if (!empty($input['status'])) {
        $status = mysqli_real_escape_string($db, $input['status']);
        $where[] = "r.status = ?";
        $params[] = $status;
        $types .= "s";
    }

    // Filter by date range
    if (!empty($input['start_date'])) {
        $where[] = "r.request_date >= ?";
        $params[] = $input['start_date'];
        $types .= "s";
    }
    if (!empty($input['end_date'])) {
        $where[] = "r.request_date <= ?";
        $params[] = $input['end_date'];
        $types .= "s";
    }

    $where_clause = implode(" AND ", $where);

    // Get overtime requests with employee info
    $sql = "SELECT 
                r.id,
                r.employee_id,
                e.employee_code,
                CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                r.branch_name,
                r.request_date,
                r.requested_hours,
                r.overtime_reason,
                r.status,
                r.requested_by,
                r.requested_by_user_id,
                r.requested_at,
                r.approved_by,
                r.approved_at,
                r.rejection_reason,
                r.attendance_id
            FROM overtime_requests r
            LEFT JOIN employees e ON r.employee_id = e.id
            WHERE $where_clause
            ORDER BY r.requested_at DESC";

    $stmt = mysqli_prepare($db, $sql);
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $requests = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Format dates
        $row['id'] = intval($row['id']);
        $row['employee_id'] = intval($row['employee_id']);
        $row['requested_hours'] = floatval($row['requested_hours']);
        $row['requested_by_user_id'] = $row['requested_by_user_id'] ? intval($row['requested_by_user_id']) : null;
        $row['attendance_id'] = $row['attendance_id'] ? intval($row['attendance_id']) : null;
        $requests[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => $requests,
        'count' => count($requests)
    ]);

    mysqli_close($db);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
