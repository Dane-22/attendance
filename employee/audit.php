<?php
// employee/audit.php - Attendance Audit with Calendar View, Pagination, Rate Limiting, and Search
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../conn/db_connection.php';
require_once __DIR__ . '/../functions.php';
session_start();

// Rate Limiting Configuration
$RATE_LIMIT_MAX_REQUESTS = 60; // Max requests per minute
$RATE_LIMIT_WINDOW = 60; // Window in seconds

// Initialize rate limiting in session
if (!isset($_SESSION['audit_rate_limit'])) {
    $_SESSION['audit_rate_limit'] = [
        'requests' => [],
        'blocked_until' => null
    ];
}

$now = time();

// Check if user is currently blocked
if ($_SESSION['audit_rate_limit']['blocked_until'] && $now < $_SESSION['audit_rate_limit']['blocked_until']) {
    $retryAfter = $_SESSION['audit_rate_limit']['blocked_until'] - $now;
    http_response_code(429);
    die(json_encode(['error' => 'Too many requests. Please try again in ' . $retryAfter . ' seconds.']));
}

// Clean old requests outside the window
$_SESSION['audit_rate_limit']['requests'] = array_filter(
    $_SESSION['audit_rate_limit']['requests'],
    function($timestamp) use ($now, $RATE_LIMIT_WINDOW) {
        return ($now - $timestamp) < $RATE_LIMIT_WINDOW;
    }
);

// Check if limit exceeded
if (count($_SESSION['audit_rate_limit']['requests']) >= $RATE_LIMIT_MAX_REQUESTS) {
    $_SESSION['audit_rate_limit']['blocked_until'] = $now + $RATE_LIMIT_WINDOW;
    http_response_code(429);
    die(json_encode(['error' => 'Rate limit exceeded. Please try again in ' . $RATE_LIMIT_WINDOW . ' seconds.']));
}

// Record this request
$_SESSION['audit_rate_limit']['requests'][] = $now;

// Check if user is logged in and is admin/super admin/developer
if (empty($_SESSION['logged_in']) || !in_array($_SESSION['position'], ['Admin', 'Super Admin', 'Developer'])) {
    header('Location: ../login.php');
    exit;
}

// Define admin role variables for later use
$isAdmin = in_array($_SESSION['position'], ['Admin', 'Super Admin', 'Developer']);
$isSuperAdmin = in_array($_SESSION['position'], ['Super Admin', 'Developer']);

// Pagination Configuration
$RECORDS_PER_PAGE = 25;
$currentPage = max(1, intval($_GET['page'] ?? 1));
$offset = ($currentPage - 1) * $RECORDS_PER_PAGE;

// Search Parameters
$searchQuery = trim($_GET['search'] ?? '');
$searchType = $_GET['search_type'] ?? 'all'; // all, name, code, branch

// Get selected date (default to today)
$selectedDate = $_GET['date'] ?? date('Y-m-d');
$currentMonth = (int)($_GET['month'] ?? date('m'));
$currentYear = (int)($_GET['year'] ?? date('Y'));

// Get filter parameter
$filter = $_GET['filter'] ?? 'day'; // day, week, or month

// Determine date range based on filter
if ($filter === 'week') {
    // Get current week (Monday to Sunday)
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $weekEnd = date('Y-m-d', strtotime('sunday this week'));
    $dateRangeLabel = date('M d', strtotime($weekStart)) . ' - ' . date('M d, Y', strtotime($weekEnd));
} elseif ($filter === 'month') {
    // Get current month
    $monthStart = date('Y-m-01');
    $monthEnd = date('Y-m-t');
    $dateRangeLabel = date('F Y');
} else {
    // Single day (default)
    $dateRangeLabel = date('F d, Y (l)', strtotime($selectedDate));
}

// Build search condition for SQL
$searchCondition = '';
$searchParams = [];
$searchTypes = '';

if (!empty($searchQuery)) {
    $searchPattern = '%' . $searchQuery . '%';
    switch ($searchType) {
        case 'name':
            $searchCondition = " AND (e.first_name LIKE ? OR e.last_name LIKE ?)";
            $searchParams = [$searchPattern, $searchPattern];
            $searchTypes = 'ss';
            break;
        case 'code':
            $searchCondition = " AND e.employee_code LIKE ?";
            $searchParams = [$searchPattern];
            $searchTypes = 's';
            break;
        case 'branch':
            $searchCondition = " AND a.branch_name LIKE ?";
            $searchParams = [$searchPattern];
            $searchTypes = 's';
            break;
        case 'all':
        default:
            $searchCondition = " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_code LIKE ? OR a.branch_name LIKE ?)";
            $searchParams = [$searchPattern, $searchPattern, $searchPattern, $searchPattern];
            $searchTypes = 'ssss';
            break;
    }
}

// Get total count for pagination
$totalRecords = 0;
if ($filter === 'week') {
    $countSql = "SELECT COUNT(*) as total FROM attendance a LEFT JOIN employees e ON a.employee_id = e.id WHERE a.attendance_date BETWEEN ? AND ?" . $searchCondition;
    $countStmt = mysqli_prepare($db, $countSql);
    if ($countStmt) {
        $params = array_merge([$weekStart, $weekEnd], $searchParams);
        $types = 'ss' . $searchTypes;
        mysqli_stmt_bind_param($countStmt, $types, ...$params);
        mysqli_stmt_execute($countStmt);
        $countResult = mysqli_stmt_get_result($countStmt);
        $countRow = mysqli_fetch_assoc($countResult);
        $totalRecords = $countRow['total'] ?? 0;
        mysqli_stmt_close($countStmt);
    }
} elseif ($filter === 'month') {
    $countSql = "SELECT COUNT(*) as total FROM attendance a LEFT JOIN employees e ON a.employee_id = e.id WHERE a.attendance_date BETWEEN ? AND ?" . $searchCondition;
    $countStmt = mysqli_prepare($db, $countSql);
    if ($countStmt) {
        $params = array_merge([$monthStart, $monthEnd], $searchParams);
        $types = 'ss' . $searchTypes;
        mysqli_stmt_bind_param($countStmt, $types, ...$params);
        mysqli_stmt_execute($countStmt);
        $countResult = mysqli_stmt_get_result($countStmt);
        $countRow = mysqli_fetch_assoc($countResult);
        $totalRecords = $countRow['total'] ?? 0;
        mysqli_stmt_close($countStmt);
    }
} else {
    $countSql = "SELECT COUNT(*) as total FROM attendance a LEFT JOIN employees e ON a.employee_id = e.id WHERE a.attendance_date = ?" . $searchCondition;
    $countStmt = mysqli_prepare($db, $countSql);
    if ($countStmt) {
        $params = array_merge([$selectedDate], $searchParams);
        $types = 's' . $searchTypes;
        mysqli_stmt_bind_param($countStmt, $types, ...$params);
        mysqli_stmt_execute($countStmt);
        $countResult = mysqli_stmt_get_result($countStmt);
        $countRow = mysqli_fetch_assoc($countResult);
        $totalRecords = $countRow['total'] ?? 0;
        mysqli_stmt_close($countStmt);
    }
}

// Calculate pagination values
$totalPages = max(1, ceil($totalRecords / $RECORDS_PER_PAGE));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $RECORDS_PER_PAGE;

// Get attendance summary based on filter (unchanged summary query)
$attendanceSummary = [];
if ($filter === 'week') {
    $summarySql = "SELECT 
        COUNT(*) as total_employees,
        SUM(CASE WHEN time_in IS NOT NULL AND time_out IS NULL THEN 1 ELSE 0 END) as currently_present,
        SUM(CASE WHEN time_in IS NOT NULL AND time_out IS NOT NULL THEN 1 ELSE 0 END) as completed_shifts,
        SUM(CASE WHEN time_in IS NULL AND status = 'Absent' THEN 1 ELSE 0 END) as absent_count
    FROM attendance 
    WHERE attendance_date BETWEEN ? AND ?";
    $summaryStmt = mysqli_prepare($db, $summarySql);
    if ($summaryStmt) {
        mysqli_stmt_bind_param($summaryStmt, 'ss', $weekStart, $weekEnd);
    }
} elseif ($filter === 'month') {
    $summarySql = "SELECT 
        COUNT(*) as total_employees,
        SUM(CASE WHEN time_in IS NOT NULL AND time_out IS NULL THEN 1 ELSE 0 END) as currently_present,
        SUM(CASE WHEN time_in IS NOT NULL AND time_out IS NOT NULL THEN 1 ELSE 0 END) as completed_shifts,
        SUM(CASE WHEN time_in IS NULL AND status = 'Absent' THEN 1 ELSE 0 END) as absent_count
    FROM attendance 
    WHERE attendance_date BETWEEN ? AND ?";
    $summaryStmt = mysqli_prepare($db, $summarySql);
    if ($summaryStmt) {
        mysqli_stmt_bind_param($summaryStmt, 'ss', $monthStart, $monthEnd);
    }
} else {
    $summarySql = "SELECT 
        COUNT(*) as total_employees,
        SUM(CASE WHEN time_in IS NOT NULL AND time_out IS NULL THEN 1 ELSE 0 END) as currently_present,
        SUM(CASE WHEN time_in IS NOT NULL AND time_out IS NOT NULL THEN 1 ELSE 0 END) as completed_shifts,
        SUM(CASE WHEN time_in IS NULL AND status = 'Absent' THEN 1 ELSE 0 END) as absent_count
    FROM attendance 
    WHERE attendance_date = ?";
    $summaryStmt = mysqli_prepare($db, $summarySql);
    if ($summaryStmt) {
        mysqli_stmt_bind_param($summaryStmt, 's', $selectedDate);
    }
}
if ($summaryStmt) {
    mysqli_stmt_execute($summaryStmt);
    $summaryResult = mysqli_stmt_get_result($summaryStmt);
    $attendanceSummary = mysqli_fetch_assoc($summaryResult);
    mysqli_stmt_close($summaryStmt);
}

// Get detailed attendance based on filter with search and pagination
$attendanceData = [];
if ($filter === 'week') {
    $detailSql = "SELECT 
        a.id,
        a.employee_id,
        a.attendance_date,
        a.time_in,
        a.time_out,
        a.branch_name,
        a.status,
        TIMESTAMPDIFF(MINUTE, a.time_in, COALESCE(a.time_out, NOW())) as minutes_worked,
        e.first_name,
        e.last_name,
        e.employee_code,
        e.position
    FROM attendance a
    LEFT JOIN employees e ON a.employee_id = e.id
    WHERE a.attendance_date BETWEEN ? AND ?" . $searchCondition . "
    ORDER BY a.attendance_date DESC, a.time_in DESC
    LIMIT ? OFFSET ?";
    $detailStmt = mysqli_prepare($db, $detailSql);
    if ($detailStmt) {
        $params = array_merge([$weekStart, $weekEnd], $searchParams, [$RECORDS_PER_PAGE, $offset]);
        $types = 'ss' . $searchTypes . 'ii';
        mysqli_stmt_bind_param($detailStmt, $types, ...$params);
    }
} elseif ($filter === 'month') {
    $detailSql = "SELECT 
        a.id,
        a.employee_id,
        a.attendance_date,
        a.time_in,
        a.time_out,
        a.branch_name,
        a.status,
        TIMESTAMPDIFF(MINUTE, a.time_in, COALESCE(a.time_out, NOW())) as minutes_worked,
        e.first_name,
        e.last_name,
        e.employee_code,
        e.position
    FROM attendance a
    LEFT JOIN employees e ON a.employee_id = e.id
    WHERE a.attendance_date BETWEEN ? AND ?" . $searchCondition . "
    ORDER BY a.attendance_date DESC, a.time_in DESC
    LIMIT ? OFFSET ?";
    $detailStmt = mysqli_prepare($db, $detailSql);
    if ($detailStmt) {
        $params = array_merge([$monthStart, $monthEnd], $searchParams, [$RECORDS_PER_PAGE, $offset]);
        $types = 'ss' . $searchTypes . 'ii';
        mysqli_stmt_bind_param($detailStmt, $types, ...$params);
    }
} else {
    $detailSql = "SELECT 
        a.id,
        a.employee_id,
        a.attendance_date,
        a.time_in,
        a.time_out,
        a.branch_name,
        a.status,
        TIMESTAMPDIFF(MINUTE, a.time_in, COALESCE(a.time_out, NOW())) as minutes_worked,
        e.first_name,
        e.last_name,
        e.employee_code,
        e.position
    FROM attendance a
    LEFT JOIN employees e ON a.employee_id = e.id
    WHERE a.attendance_date = ?" . $searchCondition . "
    ORDER BY a.time_in DESC
    LIMIT ? OFFSET ?";
    $detailStmt = mysqli_prepare($db, $detailSql);
    if ($detailStmt) {
        $params = array_merge([$selectedDate], $searchParams, [$RECORDS_PER_PAGE, $offset]);
        $types = 's' . $searchTypes . 'ii';
        mysqli_stmt_bind_param($detailStmt, $types, ...$params);
    }
}
if ($detailStmt) {
    mysqli_stmt_execute($detailStmt);
    $detailResult = mysqli_stmt_get_result($detailStmt);
    while ($row = mysqli_fetch_assoc($detailResult)) {
        $attendanceData[] = $row;
    }
    mysqli_stmt_close($detailStmt);
}

// Get days with attendance for calendar highlighting
$calendarMonth = sprintf('%04d-%02d', $currentYear, $currentMonth);
$daysWithAttendance = [];
$calendarSql = "SELECT DISTINCT attendance_date, 
    COUNT(*) as count,
    SUM(CASE WHEN time_in IS NOT NULL AND time_out IS NULL THEN 1 ELSE 0 END) as open_shifts
FROM attendance 
WHERE attendance_date LIKE ?
GROUP BY attendance_date";
$calendarStmt = mysqli_prepare($db, $calendarSql);
$monthPattern = $calendarMonth . '%';
if ($calendarStmt) {
    mysqli_stmt_bind_param($calendarStmt, 's', $monthPattern);
    mysqli_stmt_execute($calendarStmt);
    $calendarResult = mysqli_stmt_get_result($calendarStmt);
    while ($row = mysqli_fetch_assoc($calendarResult)) {
        $daysWithAttendance[$row['attendance_date']] = $row;
    }
    mysqli_stmt_close($calendarStmt);
}

// Calculate calendar data
$firstDayOfMonth = date('w', strtotime("$currentYear-$currentMonth-01"));
$daysInMonth = date('t', strtotime("$currentYear-$currentMonth-01"));
$prevMonth = $currentMonth == 1 ? 12 : $currentMonth - 1;
$prevYear = $currentMonth == 1 ? $currentYear - 1 : $currentYear;
$nextMonth = $currentMonth == 12 ? 1 : $currentMonth + 1;
$nextYear = $currentMonth == 12 ? $currentYear + 1 : $currentYear;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Audit - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="css/light-theme.css">
    <script src="js/theme.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --orange: #FFA500;
            --black: #000000;
            --gold-2: #FFD700;
        }
        body {
            background: linear-gradient(135deg, var(--black) 0%, #1a1a1a 100%);
            color: #ffffff;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
        }
        .main-content {
            margin-left: 16rem;
            padding: 2rem;
            min-height: 100vh;
        }
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
        }
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
        }
        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 165, 0, 0.2);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        .calendar-day:hover {
            background: rgba(255, 165, 0, 0.2);
            transform: translateY(-2px);
        }
        .calendar-day.selected {
            background: var(--orange);
            color: var(--black);
            font-weight: bold;
        }
        .calendar-day.has-data {
            border-color: var(--gold-2);
        }
        .calendar-day.has-data::after {
            content: '';
            position: absolute;
            bottom: 4px;
            width: 6px;
            height: 6px;
            background: var(--gold-2);
            border-radius: 50%;
        }
        .calendar-day.other-month {
            opacity: 0.4;
        }
        .calendar-day .day-number {
            font-size: 14px;
            font-weight: 600;
        }
        .calendar-day .day-count {
            font-size: 10px;
            margin-top: 2px;
        }
        .weekday-header {
            text-align: center;
            padding: 10px;
            font-weight: 600;
            color: var(--orange);
            font-size: 12px;
            text-transform: uppercase;
        }
        .audit-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 165, 0, 0.1);
            border-radius: 12px;
            padding: 1.5rem;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 165, 0, 0.1);
            border-radius: 10px;
            padding: 1rem;
        }
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
        }
        .attendance-table th {
            background: rgba(255, 165, 0, 0.2);
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: var(--orange);
            font-size: 12px;
            text-transform: uppercase;
        }
        .attendance-table td {
            padding: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .attendance-table tr:hover {
            background: rgba(255, 165, 0, 0.05);
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-present {
            background: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
        }
        .status-completed {
            background: rgba(33, 150, 243, 0.2);
            color: #2196F3;
        }
        .status-absent {
            background: rgba(244, 67, 54, 0.2);
            color: #F44336;
        }
        .status-late {
            background: rgba(255, 152, 0, 0.2);
            color: #FF9800;
        }
        .btn-nav {
            background: rgba(255, 165, 0, 0.2);
            border: 1px solid rgba(255, 165, 0, 0.3);
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-nav:hover {
            background: var(--orange);
            color: var(--black);
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-white mb-2">Attendance Audit</h1>
            <p class="text-gray-300 mb-4">Review daily attendance records by selecting a date</p>
            
            <!-- Search Form -->
            <div class="mb-4 p-4 bg-white/5 rounded-lg border border-orange-500/20">
                <form method="GET" class="flex flex-wrap gap-3 items-end">
                    <input type="hidden" name="date" value="<?php echo htmlspecialchars($selectedDate); ?>">
                    <input type="hidden" name="month" value="<?php echo $currentMonth; ?>">
                    <input type="hidden" name="year" value="<?php echo $currentYear; ?>">
                    <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                    
                    <div class="flex-1 min-w-[200px]">
                        <label class="text-sm text-gray-400 block mb-1">Search</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" 
                               placeholder="Search employees, codes, or branches..."
                               class="w-full px-3 py-2 bg-black/30 border border-orange-500/30 rounded text-white placeholder-gray-500 focus:outline-none focus:border-orange-500">
                    </div>
                    
                    <div class="min-w-[150px]">
                        <label class="text-sm text-gray-400 block mb-1">Search By</label>
                        <select name="search_type" class="w-full px-3 py-2 bg-black/30 border border-orange-500/30 rounded text-white focus:outline-none focus:border-orange-500">
                            <option value="all" <?php echo $searchType === 'all' ? 'selected' : ''; ?>>All Fields</option>
                            <option value="name" <?php echo $searchType === 'name' ? 'selected' : ''; ?>>Employee Name</option>
                            <option value="code" <?php echo $searchType === 'code' ? 'selected' : ''; ?>>Employee Code</option>
                            <option value="branch" <?php echo $searchType === 'branch' ? 'selected' : ''; ?>>Branch</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn-nav h-[38px]">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                    
                    <?php if (!empty($searchQuery)): ?>
                    <a href="?date=<?php echo $selectedDate; ?>&month=<?php echo $currentMonth; ?>&year=<?php echo $currentYear; ?>&filter=<?php echo $filter; ?>" 
                       class="btn-nav h-[38px] bg-red-500/20 border-red-500/30 hover:bg-red-500">
                        <i class="fas fa-times mr-2"></i>Clear
                    </a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="flex gap-3 flex-wrap">
                <a href="?filter=week<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) . '&search_type=' . urlencode($searchType) : ''; ?>" class="btn-nav <?= $filter === 'week' ? 'bg-orange-500 text-black' : '' ?>">
                    <i class="fas fa-calendar-week mr-2"></i>This Week
                </a>
                <a href="?filter=month<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) . '&search_type=' . urlencode($searchType) : ''; ?>" class="btn-nav <?= $filter === 'month' ? 'bg-orange-500 text-black' : '' ?>">
                    <i class="fas fa-calendar-alt mr-2"></i>This Month
                </a>
                <a href="?date=<?= date('Y-m-d') ?>" class="btn-nav <?= $filter === 'day' ? 'bg-orange-500 text-black' : '' ?>">
                    <i class="fas fa-calendar-day mr-2"></i>Today
                </a>
                <?php if (in_array(strtolower($_SESSION['position'] ?? ''), ['super admin', 'Developer', 'admin'])): ?>
                <a href="audit_report_selector.php" class="btn-nav bg-gradient-to-r from-yellow-600 to-orange-600 hover:from-yellow-500 hover:to-orange-500 border-yellow-500">
                    <i class="fas fa-file-pdf mr-2"></i>Generate Report
                </a>
                <?php endif; ?>
                
                <!-- Export Excel with Date Range & Branch Selection -->
                <button onclick="toggleExportForm()" class="btn-nav bg-gradient-to-r from-green-600 to-green-700 hover:from-green-500 hover:to-green-600 border-green-500">
                    <i class="fas fa-file-excel mr-2"></i>Export Excel
                </button>
                
                <!-- Individual Report - Links to separate selector page -->
                <a href="individual_report_selector.php" class="btn-nav bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 border-blue-500">
                    <i class="fas fa-user mr-2"></i>Individual Report
                </a>
            </div>
            
            <!-- Export Excel with Date Range, Branch & Employee Selection -->
            <div id="exportForm" class="hidden mt-4 p-4 bg-white/5 rounded-lg border border-green-500/30">
                <form id="exportFormElement" class="flex flex-wrap gap-3 items-end">
                    <div class="min-w-[150px]">
                        <label class="text-sm text-gray-400 block mb-1">Start Date</label>
                        <input type="date" name="start_date" id="exportStartDate" value="<?php echo date('Y-m-d', strtotime('-7 days')); ?>" 
                               class="w-full px-3 py-2 bg-black/30 border border-green-500/30 rounded text-white focus:outline-none focus:border-green-500" required>
                    </div>
                    <div class="min-w-[150px]">
                        <label class="text-sm text-gray-400 block mb-1">End Date</label>
                        <input type="date" name="end_date" id="exportEndDate" value="<?php echo date('Y-m-d'); ?>" 
                               class="w-full px-3 py-2 bg-black/30 border border-green-500/30 rounded text-white focus:outline-none focus:border-green-500" required>
                    </div>
                    <div class="min-w-[200px]">
                        <label class="text-sm text-gray-400 block mb-1">Branch</label>
                        <select name="branch" id="exportBranch" class="w-full px-3 py-2 bg-black/30 border border-green-500/30 rounded text-white focus:outline-none focus:border-green-500">
                            <option value="">All Branches</option>
                            <?php
                            // Fetch all branches
                            $branchesQuery = "SELECT branch_name FROM branches ORDER BY branch_name";
                            $branchesResult = mysqli_query($db, $branchesQuery);
                            while ($branch = mysqli_fetch_assoc($branchesResult)):
                            ?>
                            <option value="<?php echo htmlspecialchars($branch['branch_name']); ?>"><?php echo htmlspecialchars($branch['branch_name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="min-w-[220px]">
                        <label class="text-sm text-gray-400 block mb-1">Employee</label>
                        <select name="employee_id" id="exportEmployee" class="w-full px-3 py-2 bg-black/30 border border-green-500/30 rounded text-white focus:outline-none focus:border-green-500">
                            <option value="">All Employees</option>
                            <?php
                            // Fetch all active employees
                            $employeesQuery = "SELECT id, first_name, last_name, employee_code FROM employees ORDER BY last_name, first_name";
                            $employeesResult = mysqli_query($db, $employeesQuery);
                            while ($emp = mysqli_fetch_assoc($employeesResult)):
                            ?>
                            <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['last_name'] . ', ' . $emp['first_name'] . ' (' . $emp['employee_code'] . ')'); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="flex gap-2 w-full sm:w-auto">
                        <button type="button" onclick="exportAttendance('all')" class="btn-nav bg-gradient-to-r from-green-600 to-green-700 hover:from-green-500 hover:to-green-600 border-green-500 h-[38px]">
                            <i class="fas fa-file-excel mr-2"></i>Export All
                        </button>
                        <button type="button" onclick="exportAttendance('individual')" class="btn-nav bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 border-blue-500 h-[38px]">
                            <i class="fas fa-user mr-2"></i>Individual Report
                        </button>
                        <button type="button" onclick="toggleExportForm()" class="btn-nav bg-gray-600 hover:bg-gray-500 border-gray-500 h-[38px]">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </button>
                    </div>
                </form>
            </div>
            
            <script>
            function toggleExportForm() {
                const form = document.getElementById('exportForm');
                form.classList.toggle('hidden');
            }
            
            function exportAttendance(type) {
                const startDate = document.getElementById('exportStartDate').value;
                const endDate = document.getElementById('exportEndDate').value;
                const branch = document.getElementById('exportBranch').value;
                const employeeId = document.getElementById('exportEmployee').value;
                
                if (!startDate || !endDate) {
                    alert('Please select both start and end dates.');
                    return;
                }
                
                let url;
                if (type === 'individual' && employeeId) {
                    url = 'export_employee_individual.php?start_date=' + encodeURIComponent(startDate) + 
                          '&end_date=' + encodeURIComponent(endDate) + 
                          '&employee_id=' + encodeURIComponent(employeeId);
                } else if (type === 'individual' && !employeeId) {
                    alert('Please select an employee for individual report.');
                    return;
                } else {
                    url = 'export_attendance_excel.php?start_date=' + encodeURIComponent(startDate) + 
                          '&end_date=' + encodeURIComponent(endDate);
                    if (branch) {
                        url += '&branch=' + encodeURIComponent(branch);
                    }
                }
                
                window.location.href = url;
            }
            </script>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Calendar Section -->
            <div class="lg:col-span-1">
                <div class="audit-card">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-white">
                            <?php echo date('F Y', strtotime("$currentYear-$currentMonth-01")); ?>
                        </h2>
                        <div class="flex gap-2">
                            <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>&date=<?php echo $selectedDate; ?>" 
                               class="btn-nav">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <a href="?date=<?php echo date('Y-m-d'); ?>" class="btn-nav">
                                Today
                            </a>
                            <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>&date=<?php echo $selectedDate; ?>" 
                               class="btn-nav">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Weekday Headers -->
                    <div class="calendar-grid mb-2">
                        <div class="weekday-header">Sun</div>
                        <div class="weekday-header">Mon</div>
                        <div class="weekday-header">Tue</div>
                        <div class="weekday-header">Wed</div>
                        <div class="weekday-header">Thu</div>
                        <div class="weekday-header">Fri</div>
                        <div class="weekday-header">Sat</div>
                    </div>

                    <!-- Calendar Days -->
                    <div class="calendar-grid">
                        <?php
                        // Previous month days
                        $prevMonthDays = date('t', strtotime("$prevYear-$prevMonth-01"));
                        for ($i = $firstDayOfMonth - 1; $i >= 0; $i--) {
                            $dayNum = $prevMonthDays - $i;
                            echo '<div class="calendar-day other-month">
                                <span class="day-number">' . $dayNum . '</span>
                            </div>';
                        }

                        // Current month days
                        for ($day = 1; $day <= $daysInMonth; $day++) {
                            $dateStr = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
                            $hasData = isset($daysWithAttendance[$dateStr]);
                            $isSelected = ($dateStr === $selectedDate);
                            $dataCount = $hasData ? $daysWithAttendance[$dateStr]['count'] : 0;
                            
                            $classes = ['calendar-day'];
                            if ($isSelected) $classes[] = 'selected';
                            if ($hasData) $classes[] = 'has-data';
                            if ($dateStr === date('Y-m-d')) $classes[] = 'border-2 border-yellow-400';
                            
                            echo '<a href="?date=' . $dateStr . '&month=' . $currentMonth . '&year=' . $currentYear . '" 
                                  class="' . implode(' ', $classes) . '">';
                            echo '<span class="day-number">' . $day . '</span>';
                            if ($hasData) {
                                echo '<span class="day-count">' . $dataCount . ' rec</span>';
                            }
                            echo '</a>';
                        }

                        // Next month days
                        $totalCells = $firstDayOfMonth + $daysInMonth;
                        $remainingCells = (7 - ($totalCells % 7)) % 7;
                        for ($day = 1; $day <= $remainingCells; $day++) {
                            echo '<div class="calendar-day other-month">
                                <span class="day-number">' . $day . '</span>
                            </div>';
                        }
                        ?>
                    </div>

                    <!-- Legend -->
                    <div class="mt-4 flex flex-wrap gap-4 text-sm">
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 rounded bg-orange-500"></div>
                            <span class="text-gray-300">Selected</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 rounded border border-yellow-400"></div>
                            <span class="text-gray-300">Has Records</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 rounded border-2 border-yellow-400"></div>
                            <span class="text-gray-300">Today</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance Details Section -->
            <div class="lg:col-span-2">
                <!-- Stats Cards -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="stat-card">
                        <div class="text-gray-400 text-sm">Total Records</div>
                        <div class="text-2xl font-bold text-white">
                            <?php echo $attendanceSummary['total_employees'] ?? 0; ?>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="text-gray-400 text-sm">Currently Present</div>
                        <div class="text-2xl font-bold text-green-400">
                            <?php echo $attendanceSummary['currently_present'] ?? 0; ?>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="text-gray-400 text-sm">Completed Shifts</div>
                        <div class="text-2xl font-bold text-blue-400">
                            <?php echo $attendanceSummary['completed_shifts'] ?? 0; ?>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="text-gray-400 text-sm">Absent</div>
                        <div class="text-2xl font-bold text-red-400">
                            <?php echo $attendanceSummary['absent_count'] ?? 0; ?>
                        </div>
                    </div>
                </div>

                <!-- Selected Date/Range Header -->
                <div class="audit-card mb-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-bold text-white">
                            <i class="fas fa-calendar-day mr-2 text-orange-500"></i>
                            <?php echo $dateRangeLabel; ?>
                            <?php if ($filter !== 'day'): ?>
                                <span class="text-sm font-normal text-gray-400 ml-2">
                                    (<?php echo count($attendanceData); ?> records)
                                </span>
                            <?php endif; ?>
                        </h2>
                    </div>
                </div>

                <!-- Attendance Table -->
                <div class="audit-card">
                    <?php if (count($attendanceData) > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="attendance-table">
                                <thead>
                                    <tr>
                                        <?php if ($filter !== 'day'): ?>
                                        <th>Date</th>
                                        <?php endif; ?>
                                        <th>Employee</th>
                                        <th>Code</th>
                                        <th>Branch</th>
                                        <th>Time In</th>
                                        <th>Time Out</th>
                                        <th>Hours</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attendanceData as $record): 
                                        $hoursWorked = $record['minutes_worked'] ? round($record['minutes_worked'] / 60, 2) : 0;
                                        
                                        // Determine status
                                        $isLate = false;
                                        if ($record['time_in'] && strtolower($record['position']) === 'worker') {
                                            $timeIn = strtotime($record['time_in']);
                                            $lateThreshold = strtotime(date('Y-m-d', $timeIn) . ' 07:15:00');
                                            if ($timeIn >= $lateThreshold) {
                                                $isLate = true;
                                            }
                                        }
                                        
                                        if ($record['time_in'] && $record['time_out']) {
                                            if ($isLate) {
                                                $statusClass = 'status-late';
                                                $statusText = 'Late';
                                            } else {
                                                $statusClass = 'status-completed';
                                                $statusText = 'Completed';
                                            }
                                        } elseif ($record['time_in']) {
                                            if ($isLate) {
                                                $statusClass = 'status-late';
                                                $statusText = 'Late';
                                            } else {
                                                $statusClass = 'status-present';
                                                $statusText = 'Present';
                                            }
                                        } else {
                                            $statusClass = 'status-absent';
                                            $statusText = $record['status'] ?? 'Absent';
                                        }
                                    ?>
                                        <tr>
                                            <?php if ($filter !== 'day'): ?>
                                            <td class="text-gray-300">
                                                <?php echo date('M d', strtotime($record['attendance_date'])); ?>
                                            </td>
                                            <?php endif; ?>
                                            <td>
                                                <div class="font-medium text-white">
                                                    <?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?>
                                                </div>
                                                <div class="text-xs text-gray-400">
                                                    <?php echo htmlspecialchars($record['position']); ?>
                                                </div>
                                            </td>
                                            <td class="text-gray-300">
                                                <?php echo htmlspecialchars($record['employee_code']); ?>
                                            </td>
                                            <td class="text-gray-300">
                                                <?php echo htmlspecialchars($record['branch_name'] ?? 'N/A'); ?>
                                            </td>
                                            <td class="text-gray-300">
                                                <?php echo $record['time_in'] ? date('h:i A', strtotime($record['time_in'])) : '-'; ?>
                                            </td>
                                            <td class="text-gray-300">
                                                <?php echo $record['time_out'] ? date('h:i A', strtotime($record['time_out'])) : '-'; ?>
                                            </td>
                                            <td class="text-gray-300">
                                                <?php echo $hoursWorked > 0 ? number_format($hoursWorked, 2) . ' hrs' : '-'; ?>
                                            </td>
                                            <td>
                                                <span class="status-badge <?php echo $statusClass; ?>">
                                                    <?php echo $statusText; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination Controls -->
                        <?php if ($totalPages > 1): ?>
                        <div class="mt-6 flex flex-wrap items-center justify-between gap-4">
                            <div class="text-sm text-gray-400">
                                Showing <?php echo (($currentPage - 1) * $RECORDS_PER_PAGE) + 1; ?> - 
                                <?php echo min($currentPage * $RECORDS_PER_PAGE, $totalRecords); ?> of 
                                <?php echo $totalRecords; ?> records
                                <?php if (!empty($searchQuery)): ?>
                                    <span class="text-orange-400">(filtered by "<?php echo htmlspecialchars($searchQuery); ?>")</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <!-- Previous Page -->
                                <?php if ($currentPage > 1): ?>
                                    <a href="?page=<?php echo $currentPage - 1; ?>&date=<?php echo $selectedDate; ?>&month=<?php echo $currentMonth; ?>&year=<?php echo $currentYear; ?>&filter=<?php echo $filter; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) . '&search_type=' . urlencode($searchType) : ''; ?>" 
                                       class="btn-nav px-3 py-2">
                                        <i class="fas fa-chevron-left mr-1"></i> Prev
                                    </a>
                                <?php else: ?>
                                    <span class="px-3 py-2 bg-gray-700/50 text-gray-500 rounded cursor-not-allowed">
                                        <i class="fas fa-chevron-left mr-1"></i> Prev
                                    </span>
                                <?php endif; ?>
                                
                                <!-- Page Numbers -->
                                <?php
                                $startPage = max(1, $currentPage - 2);
                                $endPage = min($totalPages, $currentPage + 2);
                                
                                if ($startPage > 1): ?>
                                    <a href="?page=1&date=<?php echo $selectedDate; ?>&month=<?php echo $currentMonth; ?>&year=<?php echo $currentYear; ?>&filter=<?php echo $filter; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) . '&search_type=' . urlencode($searchType) : ''; ?>" 
                                       class="btn-nav px-3 py-2 min-w-[40px] text-center">1</a>
                                    <?php if ($startPage > 2): ?>
                                        <span class="text-gray-500 px-2">...</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                    <?php if ($i == $currentPage): ?>
                                        <span class="px-3 py-2 bg-orange-500 text-black rounded min-w-[40px] text-center font-bold"><?php echo $i; ?></span>
                                    <?php else: ?>
                                        <a href="?page=<?php echo $i; ?>&date=<?php echo $selectedDate; ?>&month=<?php echo $currentMonth; ?>&year=<?php echo $currentYear; ?>&filter=<?php echo $filter; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) . '&search_type=' . urlencode($searchType) : ''; ?>" 
                                           class="btn-nav px-3 py-2 min-w-[40px] text-center"><?php echo $i; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <?php if ($endPage < $totalPages): ?>
                                    <?php if ($endPage < $totalPages - 1): ?>
                                        <span class="text-gray-500 px-2">...</span>
                                    <?php endif; ?>
                                    <a href="?page=<?php echo $totalPages; ?>&date=<?php echo $selectedDate; ?>&month=<?php echo $currentMonth; ?>&year=<?php echo $currentYear; ?>&filter=<?php echo $filter; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) . '&search_type=' . urlencode($searchType) : ''; ?>" 
                                       class="btn-nav px-3 py-2 min-w-[40px] text-center"><?php echo $totalPages; ?></a>
                                <?php endif; ?>
                                
                                <!-- Next Page -->
                                <?php if ($currentPage < $totalPages): ?>
                                    <a href="?page=<?php echo $currentPage + 1; ?>&date=<?php echo $selectedDate; ?>&month=<?php echo $currentMonth; ?>&year=<?php echo $currentYear; ?>&filter=<?php echo $filter; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) . '&search_type=' . urlencode($searchType) : ''; ?>" 
                                       class="btn-nav px-3 py-2">
                                        Next <i class="fas fa-chevron-right ml-1"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="px-3 py-2 bg-gray-700/50 text-gray-500 rounded cursor-not-allowed">
                                        Next <i class="fas fa-chevron-right ml-1"></i>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php elseif ($totalRecords > 0): ?>
                        <div class="mt-4 text-sm text-gray-400">
                            Showing <?php echo $totalRecords; ?> record<?php echo $totalRecords > 1 ? 's' : ''; ?>
                            <?php if (!empty($searchQuery)): ?>
                                <span class="text-orange-400">(filtered by "<?php echo htmlspecialchars($searchQuery); ?>")</span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-12">
                            <i class="fas fa-inbox text-4xl text-gray-500 mb-4"></i>
                            <p class="text-lg text-gray-400">
                                No attendance records for 
                                <?php echo $filter === 'day' ? 'this date' : ($filter === 'week' ? 'this week' : 'this month'); ?>
                            </p>
                            <p class="text-sm text-gray-500 mt-2">
                                <?php echo $filter === 'day' ? 'Select a highlighted date on the calendar to view records' : 'Try selecting a different time period'; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($isAdmin || $isSuperAdmin): ?>
    <!-- Push Notification Component - Super Admin Only -->
    <div id="pushNotificationWidget" class="push-notification-widget">
        <div id="pushNotificationStatus" class="push-notification-status">
            <span id="pushNotificationIcon">🔔</span>
            <span id="pushNotificationText">Checking notifications...</span>
            <button id="pushNotificationBtn" class="push-notification-btn" style="display: none;">
                Enable Notifications
            </button>
        </div>
    </div>

    <style>
        /* Push Notification Widget - Dark/Gold Theme */
        .push-notification-widget {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
            background: rgba(22, 22, 22, 0.95);
            border: 1px solid rgba(255, 165, 0, 0.3);
            border-radius: 12px;
            padding: 16px 20px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            min-width: 280px;
            max-width: 360px;
            transition: all 0.3s ease;
        }

        .push-notification-widget:hover {
            border-color: rgba(255, 215, 0, 0.5);
            box-shadow: 0 12px 40px rgba(255, 165, 0, 0.15);
        }

        .push-notification-status {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #ffffff;
            font-size: 14px;
            font-family: 'Inter', system-ui, sans-serif;
        }

        .push-notification-status #pushNotificationIcon {
            font-size: 20px;
            filter: drop-shadow(0 0 8px rgba(255, 215, 0, 0.5));
        }

        .push-notification-status #pushNotificationText {
            flex: 1;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
        }

        .push-notification-btn {
            background: linear-gradient(135deg, #D4AF37 0%, #FFD700 100%);
            border: none;
            border-radius: 8px;
            color: #0b0b0b;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
        }

        .push-notification-btn:hover {
            background: linear-gradient(135deg, #FFD700 0%, #FFD66B 100%);
            box-shadow: 0 6px 20px rgba(212, 175, 55, 0.5);
            transform: translateY(-2px);
        }

        .push-notification-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 10px rgba(212, 175, 55, 0.3);
        }

        .push-notification-status.enabled #pushNotificationIcon {
            filter: drop-shadow(0 0 12px rgba(76, 175, 80, 0.8));
        }

        .push-notification-status.enabled #pushNotificationText {
            color: #4CAF50;
        }

        .push-notification-status.denied #pushNotificationIcon {
            filter: drop-shadow(0 0 8px rgba(244, 67, 54, 0.6));
        }

        .push-notification-status.denied #pushNotificationText {
            color: #F44336;
        }

        .push-notification-status.unsupported #pushNotificationIcon {
            filter: grayscale(100%);
            opacity: 0.5;
        }

        .push-notification-status.unsupported #pushNotificationText {
            color: #9CA3AF;
        }

        @media (max-width: 768px) {
            .push-notification-widget {
                bottom: 10px;
                right: 10px;
                left: 10px;
                min-width: auto;
                max-width: none;
            }
        }
    </style>

    <script>
        (function() {
            'use strict';

            const widget = document.getElementById('pushNotificationWidget');
            const statusEl = document.getElementById('pushNotificationStatus');
            const iconEl = document.getElementById('pushNotificationIcon');
            const textEl = document.getElementById('pushNotificationText');
            const btnEl = document.getElementById('pushNotificationBtn');

            // VAPID public key (should match server configuration)
            // This will be loaded from the server
            let vapidPublicKey = null;

            // Check browser support
            if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                statusEl.classList.add('unsupported');
                iconEl.textContent = '🔕';
                textEl.textContent = 'Notifications not supported';
                console.log('[Push] Browser does not support service workers or push notifications');
                return;
            }

            // Initialize push notifications
            async function initPushNotifications() {
                try {
                    // Register service worker - use relative path for compatibility
                    const registration = await navigator.serviceWorker.register('../sw.js');
                    console.log('[Push] Service Worker registered:', registration);

                    // Check notification permission
                    const permission = Notification.permission;
                    updateUI(permission);

                    if (permission === 'granted') {
                        // Already granted, subscribe
                        await subscribeToPush(registration);
                    } else if (permission === 'default') {
                        // Show enable button
                        btnEl.style.display = 'block';
                        btnEl.addEventListener('click', () => requestPermission(registration));
                    }
                    // If denied, show disabled state (no button)

                } catch (error) {
                    console.error('[Push] Initialization error:', error);
                    statusEl.classList.add('unsupported');
                    iconEl.textContent = '⚠️';
                    textEl.textContent = 'Notification error';
                }
            }

            // Update UI based on permission status
            function updateUI(permission) {
                statusEl.classList.remove('enabled', 'denied', 'unsupported');

                switch (permission) {
                    case 'granted':
                        statusEl.classList.add('enabled');
                        iconEl.textContent = '🔔';
                        textEl.textContent = 'Notifications enabled';
                        btnEl.style.display = 'none';
                        break;
                    case 'denied':
                        statusEl.classList.add('denied');
                        iconEl.textContent = '🔕';
                        textEl.textContent = 'Notifications blocked';
                        btnEl.style.display = 'none';
                        break;
                    default:
                        iconEl.textContent = '🔔';
                        textEl.textContent = 'Enable notifications?';
                        btnEl.style.display = 'block';
                }
            }

            // Request notification permission
            async function requestPermission(registration) {
                try {
                    const permission = await Notification.requestPermission();
                    console.log('[Push] Permission result:', permission);
                    updateUI(permission);

                    if (permission === 'granted') {
                        await subscribeToPush(registration);
                    }
                } catch (error) {
                    console.error('[Push] Permission request error:', error);
                    textEl.textContent = 'Permission error';
                }
            }

            // Subscribe to push notifications
            async function subscribeToPush(registration) {
                try {
                    // Fetch VAPID public key from server
                    if (!vapidPublicKey) {
                        // For now, we'll need to get this from the server
                        // In production, this should be fetched from an API endpoint
                        vapidPublicKey = await fetchVapidPublicKey();
                    }

                    if (!vapidPublicKey) {
                        throw new Error('VAPID public key not available');
                    }

                    // Convert VAPID key to Uint8Array
                    const applicationServerKey = urlBase64ToUint8Array(vapidPublicKey);

                    // Subscribe
                    const subscription = await registration.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: applicationServerKey
                    });

                    console.log('[Push] Subscribed:', subscription);

                    // Send subscription to server
                    await saveSubscription(subscription);

                    statusEl.classList.add('enabled');
                    iconEl.textContent = '🔔';
                    textEl.textContent = 'Notifications active';

                } catch (error) {
                    console.error('[Push] Subscription error:', error);
                    textEl.textContent = 'Subscription failed';
                }
            }

            // Fetch VAPID public key from server
            async function fetchVapidPublicKey() {
                try {
                    const response = await fetch('api/get_vapid_key.php');
                    const result = await response.json();
                    
                    if (result.success && result.publicKey) {
                        return result.publicKey;
                    } else {
                        console.error('[Push] Failed to fetch VAPID key:', result.message);
                        return null;
                    }
                } catch (error) {
                    console.error('[Push] Error fetching VAPID key:', error);
                    return null;
                }
            }

            // Save subscription to server
            async function saveSubscription(subscription) {
                try {
                    const response = await fetch('api/save_push_subscription.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        credentials: 'include', // Include session cookies
                        body: JSON.stringify({
                            endpoint: subscription.endpoint,
                            keys: {
                                p256dh: arrayBufferToBase64(subscription.getKey('p256dh')),
                                auth: arrayBufferToBase64(subscription.getKey('auth'))
                            }
                        })
                    });

                    const result = await response.json();
                    console.log('[Push] Subscription saved:', result);

                    if (!result.success) {
                        throw new Error(result.message || 'Failed to save subscription');
                    }

                } catch (error) {
                    console.error('[Push] Save subscription error:', error);
                    throw error;
                }
            }

            // Convert ArrayBuffer to Base64
            function arrayBufferToBase64(buffer) {
                const bytes = new Uint8Array(buffer);
                let binary = '';
                for (let i = 0; i < bytes.byteLength; i++) {
                    binary += String.fromCharCode(bytes[i]);
                }
                return btoa(binary);
            }

            // Convert URL-safe Base64 to Uint8Array
            function urlBase64ToUint8Array(base64String) {
                const padding = '='.repeat((4 - base64String.length % 4) % 4);
                const base64 = (base64String + padding)
                    .replace(/\-/g, '+')
                    .replace(/_/g, '/');

                const rawData = atob(base64);
                const outputArray = new Uint8Array(rawData.length);

                for (let i = 0; i < rawData.length; ++i) {
                    outputArray[i] = rawData.charCodeAt(i);
                }
                return outputArray;
            }

            // Initialize when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initPushNotifications);
            } else {
                initPushNotifications();
            }

        })();
    </script>
    <?php endif; ?>
</body>
</html>
