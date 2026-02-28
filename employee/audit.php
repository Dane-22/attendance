<?php
// employee/audit.php - Attendance Audit with Calendar View
require_once __DIR__ . '/../conn/db_connection.php';
require_once __DIR__ . '/../functions.php';
session_start();

// Check if user is logged in and is admin/super admin
if (empty($_SESSION['logged_in']) || !in_array($_SESSION['position'], ['Admin', 'Super Admin'])) {
    header('Location: ../login.php');
    exit;
}

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

// Get attendance summary based on filter
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

// Get detailed attendance based on filter
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
    WHERE a.attendance_date BETWEEN ? AND ?
    ORDER BY a.attendance_date DESC, a.time_in DESC";
    $detailStmt = mysqli_prepare($db, $detailSql);
    if ($detailStmt) {
        mysqli_stmt_bind_param($detailStmt, 'ss', $weekStart, $weekEnd);
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
    WHERE a.attendance_date BETWEEN ? AND ?
    ORDER BY a.attendance_date DESC, a.time_in DESC";
    $detailStmt = mysqli_prepare($db, $detailSql);
    if ($detailStmt) {
        mysqli_stmt_bind_param($detailStmt, 'ss', $monthStart, $monthEnd);
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
    WHERE a.attendance_date = ?
    ORDER BY a.time_in DESC";
    $detailStmt = mysqli_prepare($db, $detailSql);
    if ($detailStmt) {
        mysqli_stmt_bind_param($detailStmt, 's', $selectedDate);
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
            <div class="flex gap-3">
                <a href="?filter=week" class="btn-nav <?= $filter === 'week' ? 'bg-orange-500 text-black' : '' ?>">
                    <i class="fas fa-calendar-week mr-2"></i>This Week
                </a>
                <a href="?filter=month" class="btn-nav <?= $filter === 'month' ? 'bg-orange-500 text-black' : '' ?>">
                    <i class="fas fa-calendar-alt mr-2"></i>This Month
                </a>
                <a href="?date=<?= date('Y-m-d') ?>" class="btn-nav <?= $filter === 'day' ? 'bg-orange-500 text-black' : '' ?>">
                    <i class="fas fa-calendar-day mr-2"></i>Today
                </a>
            </div>
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
                                        if ($record['time_in'] && $record['time_out']) {
                                            $statusClass = 'status-completed';
                                            $statusText = 'Completed';
                                        } elseif ($record['time_in']) {
                                            $statusClass = 'status-present';
                                            $statusText = 'Present';
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
</body>
</html>
