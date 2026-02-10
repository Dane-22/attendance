<?php
session_start();

require_once __DIR__ . '/../conn/db_connection.php';
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json');

if (empty($_SESSION['logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';

function respond($success, $payload = []) {
    echo json_encode(array_merge(['success' => $success], $payload));
    exit;
}

$today = date('Y-m-d');

switch ($action) {
    case 'instant_export':
        // Determine current payroll week of the month (1-4/5)
        $dayOfMonth = (int)date('j');
        $currentWeek = (int)ceil($dayOfMonth / 7);
        if ($currentWeek > 5) {
            $currentWeek = 5;
        }
        $monthParam = date('Y-m');

        logActivity($db, 'Instant Payroll Export', 'Triggered from dashboard for ' . $monthParam . ' week ' . $currentWeek);

        $url = 'weekly_report.php?view=weekly&month=' . urlencode($monthParam) . '&week=' . $currentWeek . '&auto_export=1';
        respond(true, ['url' => $url]);

    case 'search_employees':
        $q = trim($_POST['q'] ?? '');
        if ($q === '') {
            respond(true, ['employees' => []]);
        }

        $like = '%' . $q . '%';
        $sql = "SELECT id, employee_code, CONCAT(first_name, ' ', last_name) AS name
                FROM employees
                WHERE status = 'Active'
                  AND (first_name LIKE ? OR last_name LIKE ? OR employee_code LIKE ? OR CONCAT(first_name, ' ', last_name) LIKE ?)
                ORDER BY last_name, first_name
                LIMIT 10";

        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, 'ssss', $like, $like, $like, $like);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $employees = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $employees[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'code' => $row['employee_code'],
            ];
        }
        mysqli_stmt_close($stmt);

        logActivity($db, 'Quick Attendance Search', 'Searched for employees with query: ' . $q);

        respond(true, ['employees' => $employees]);

    case 'missing_logs':
        // Employees who have no attendance row for today
        $sql = "SELECT e.id, e.employee_code, CONCAT(e.first_name, ' ', e.last_name) AS name
                FROM employees e
                LEFT JOIN attendance a
                  ON a.employee_id = e.id
                 AND a.attendance_date = ?
                WHERE e.status = 'Active'
                  AND a.id IS NULL
                ORDER BY e.last_name, e.first_name";

        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, 's', $today);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $employees = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $employees[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'code' => $row['employee_code'],
            ];
        }
        mysqli_stmt_close($stmt);

        logActivity($db, 'View Missing Logs', 'Viewed employees without attendance for ' . $today);

        respond(true, ['employees' => $employees]);

    case 'recent_activity':
        $sql = "SELECT a.action, a.details, a.created_at,
                       e.first_name, e.last_name
                FROM activity_logs a
                LEFT JOIN employees e ON a.user_id = e.id
                ORDER BY a.created_at DESC
                LIMIT 5";

        $result = mysqli_query($db, $sql);
        $logs = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $logs[] = [
                'action' => $row['action'],
                'details' => $row['details'],
                'created_at' => $row['created_at'],
                'user' => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
            ];
        }

        logActivity($db, 'View Recent Activity Logs', 'Viewed top 5 recent activity logs from dashboard');

        respond(true, ['logs' => $logs]);

    default:
        respond(false, ['message' => 'Invalid action']);
}
