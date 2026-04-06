<?php
if (file_exists(__DIR__ . '/conn/db_connection.php')) {
    require_once __DIR__ . '/conn/db_connection.php';
} else {
    require_once __DIR__ . '/db_connection.php';
}

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

function parseMonth($monthParam) {
    if (!preg_match('/^\d{4}\-\d{2}$/', $monthParam)) {
        return null;
    }

    $year = (int)substr($monthParam, 0, 4);
    $month = (int)substr($monthParam, 5, 2);
    if ($month < 1 || $month > 12) {
        return null;
    }

    return [$year, $month];
}

$viewType = isset($_REQUEST['view_type']) ? trim($_REQUEST['view_type']) : 'weekly';
$viewType = $viewType === 'monthly' ? 'monthly' : 'weekly';

$monthParam = isset($_REQUEST['month']) ? trim($_REQUEST['month']) : date('Y-m');
$parsedMonth = parseMonth($monthParam);
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

$where = [
    "w.report_year = ?",
    "w.report_month = ?",
    "w.view_type = ?"
];
$types = 'iis';
$params = [$reportYear, $reportMonth, $viewType];

if ($viewType === 'weekly') {
    $where[] = "w.week_number = ?";
    $types .= 'i';
    $params[] = $weekNumber;
}

if ($branchId !== null && $branchId > 0) {
    $where[] = "COALESCE(w.branch_id, e.branch_id) = ?";
    $types .= 'i';
    $params[] = $branchId;
}

if ($employeeId > 0) {
    $where[] = "w.employee_id = ?";
    $types .= 'i';
    $params[] = $employeeId;
}

$whereSql = implode(' AND ', $where);

$sql = "
    SELECT
        w.id,
        w.employee_id,
        CONCAT_WS(' ', e.first_name, e.last_name) AS employee_name,
        COALESCE(w.branch_id, e.branch_id) AS branch_id,
        COALESCE(b.branch_name, '') AS branch_name,
        w.report_year,
        w.report_month,
        w.week_number,
        w.view_type,
        COALESCE(w.days_worked, 0) AS days_worked,
        COALESCE(w.total_hours, 0) AS total_hours,
        COALESCE(w.daily_rate, 0) AS daily_rate,
        COALESCE(w.basic_pay, 0) AS basic_pay,
        COALESCE(w.ot_hours, 0) AS ot_hours,
        COALESCE(w.ot_rate, 0) AS ot_rate,
        COALESCE(w.ot_amount, 0) AS ot_amount,
        COALESCE(w.performance_allowance, 0) AS performance_allowance,
        COALESCE(w.gross_pay, 0) AS gross_pay,
        COALESCE(w.gross_plus_allowance, 0) AS gross_plus_allowance,
        COALESCE(w.ca_deduction, 0) AS ca_deduction,
        COALESCE(w.sss_deduction, 0) AS sss_deduction,
        COALESCE(w.philhealth_deduction, 0) AS philhealth_deduction,
        COALESCE(w.pagibig_deduction, 0) AS pagibig_deduction,
        COALESCE(w.sss_loan, 0) AS sss_loan,
        COALESCE(w.total_deductions, 0) AS total_deductions,
        COALESCE(w.take_home_pay, 0) AS take_home_pay,
        COALESCE(w.payment_status, 'Not Paid') AS payment_status,
        COALESCE(NULLIF(w.status, ''), 'Draft') AS status,
        w.created_at,
        w.updated_at
    FROM weekly_payroll_reports w
    LEFT JOIN employees e ON e.id = w.employee_id
    LEFT JOIN branches b ON b.id = COALESCE(w.branch_id, e.branch_id)
    WHERE {$whereSql}
    ORDER BY employee_name ASC, w.week_number ASC, w.id ASC
";

$stmt = mysqli_prepare($db, $sql);
if (!$stmt) {
    respond(500, [
        'success' => false,
        'message' => 'Failed to prepare payroll query.',
        'error' => mysqli_error($db),
    ]);
}

mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$rows = [];
$summary = [
    'total_employees' => 0,
    'total_days_worked' => 0,
    'total_gross_pay' => 0.0,
    'total_allowances' => 0.0,
    'total_ca_deductions' => 0.0,
    'total_deductions' => 0.0,
    'total_take_home_pay' => 0.0,
];

while ($row = mysqli_fetch_assoc($result)) {
    $normalized = [
        'id' => (int)$row['id'],
        'employee_id' => (int)$row['employee_id'],
        'employee_name' => $row['employee_name'] ?: ('Employee #' . (int)$row['employee_id']),
        'branch_id' => isset($row['branch_id']) ? (is_null($row['branch_id']) ? null : (int)$row['branch_id']) : null,
        'branch_name' => $row['branch_name'],
        'report_year' => (int)$row['report_year'],
        'report_month' => (int)$row['report_month'],
        'week_number' => (int)$row['week_number'],
        'view_type' => $row['view_type'],
        'days_worked' => (float)$row['days_worked'],
        'total_hours' => (float)$row['total_hours'],
        'daily_rate' => (float)$row['daily_rate'],
        'basic_pay' => (float)$row['basic_pay'],
        'ot_hours' => (float)$row['ot_hours'],
        'ot_rate' => (float)$row['ot_rate'],
        'ot_amount' => (float)$row['ot_amount'],
        'performance_allowance' => (float)$row['performance_allowance'],
        'gross_pay' => (float)$row['gross_pay'],
        'gross_plus_allowance' => (float)$row['gross_plus_allowance'],
        'ca_deduction' => (float)$row['ca_deduction'],
        'sss_deduction' => (float)$row['sss_deduction'],
        'philhealth_deduction' => (float)$row['philhealth_deduction'],
        'pagibig_deduction' => (float)$row['pagibig_deduction'],
        'sss_loan' => (float)$row['sss_loan'],
        'total_deductions' => (float)$row['total_deductions'],
        'take_home_pay' => (float)$row['take_home_pay'],
        'payment_status' => $row['payment_status'],
        'status' => $row['status'],
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at'],
    ];

    $rows[] = $normalized;
    $summary['total_days_worked'] += $normalized['days_worked'];
    $summary['total_gross_pay'] += $normalized['gross_pay'];
    $summary['total_allowances'] += $normalized['performance_allowance'];
    $summary['total_ca_deductions'] += $normalized['ca_deduction'];
    $summary['total_deductions'] += $normalized['total_deductions'];
    $summary['total_take_home_pay'] += $normalized['take_home_pay'];
}

$uniqueEmployees = [];
foreach ($rows as $row) {
    $uniqueEmployees[$row['employee_id']] = true;
}
$summary['total_employees'] = count($uniqueEmployees);

mysqli_stmt_close($stmt);
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
?>
