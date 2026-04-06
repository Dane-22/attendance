<?php
if (file_exists(__DIR__ . '/conn/db_connection.php')) {
    require_once __DIR__ . '/conn/db_connection.php';
} else {
    require_once __DIR__ . '/db_connection.php';
}
require_once __DIR__ . '/payroll_report_helpers.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

function respond($statusCode, $payload) {
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

$viewType = isset($_REQUEST['view_type']) ? trim($_REQUEST['view_type']) : 'weekly';
$viewType = $viewType === 'monthly' ? 'monthly' : 'weekly';

$monthParam = isset($_REQUEST['month']) ? trim($_REQUEST['month']) : date('Y-m');
$parsedMonth = payroll_parse_month($monthParam);
if ($parsedMonth === null) {
    respond(400, [
        'success' => false,
        'message' => 'Invalid month. Expected YYYY-MM.',
    ]);
}

list($reportYear, $reportMonth) = $parsedMonth;
$weekNumber = isset($_REQUEST['week_number']) ? (int)$_REQUEST['week_number'] : (isset($_REQUEST['week']) ? (int)$_REQUEST['week'] : 1);
if ($weekNumber < 1 || $weekNumber > 5) {
    $weekNumber = 1;
}

$employeeId = isset($_REQUEST['employee_id']) ? (int)$_REQUEST['employee_id'] : 0;
$branchFilter = isset($_REQUEST['branch_id']) ? $_REQUEST['branch_id'] : (isset($_REQUEST['branch']) ? $_REQUEST['branch'] : 'all');
$branchId = null;
if ($branchFilter !== null && $branchFilter !== '' && strtolower((string)$branchFilter) !== 'all') {
    $branchId = (int)$branchFilter;
}

try {
    $rows = payroll_build_report_rows($db, $reportYear, $reportMonth, $viewType, $weekNumber, $branchId, $employeeId);
    $summary = payroll_build_summary($rows);

    mysqli_close($db);

    respond(200, [
        'success' => true,
        'rows' => $rows,
        'summary' => $summary,
        'filters' => [
            'view_type' => $viewType,
            'month' => $monthParam,
            'week_number' => $viewType === 'weekly' ? $weekNumber : null,
            'branch_id' => $branchId,
            'employee_id' => $employeeId > 0 ? $employeeId : null,
        ],
    ]);
} catch (Exception $error) {
    mysqli_close($db);
    respond(500, [
        'success' => false,
        'message' => 'Failed to load payroll report.',
        'error' => $error->getMessage(),
    ]);
}
?>
