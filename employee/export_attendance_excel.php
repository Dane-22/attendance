<?php
/**
 * Attendance Excel Export by Branch
 * Generates branch-grouped attendance reports with color-coded formatting
 */

require_once __DIR__ . '/../conn/db_connection.php';
require_once __DIR__ . '/../functions.php';
session_start();

// Check authentication - Admin, Super Admin, or Developer
if (empty($_SESSION['logged_in']) || !in_array($_SESSION['position'], ['Admin', 'Super Admin', 'Developer'])) {
    header('Location: ../login.php');
    exit;
}

// Get parameters
$selectedDate = $_GET['date'] ?? date('Y-m-d');
$filter = $_GET['filter'] ?? 'day';
$searchQuery = trim($_GET['search'] ?? '');
$searchType = $_GET['search_type'] ?? 'all';

// Validate date
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $selectedDate = date('Y-m-d');
}

// Determine date range based on filter
if ($filter === 'week') {
    $weekStart = date('Y-m-d', strtotime('monday this week', strtotime($selectedDate)));
    $weekEnd = date('Y-m-d', strtotime('sunday this week', strtotime($selectedDate)));
    $dateRangeLabel = date('M d', strtotime($weekStart)) . ' - ' . date('M d, Y', strtotime($weekEnd));
} elseif ($filter === 'month') {
    $monthStart = date('Y-m-01', strtotime($selectedDate));
    $monthEnd = date('Y-m-t', strtotime($selectedDate));
    $dateRangeLabel = date('F Y', strtotime($selectedDate));
} else {
    $dateRangeLabel = date('F d, Y', strtotime($selectedDate));
}

// Build search condition
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
        default:
            $searchCondition = " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_code LIKE ? OR a.branch_name LIKE ?)";
            $searchParams = [$searchPattern, $searchPattern, $searchPattern, $searchPattern];
            $searchTypes = 'ssss';
            break;
    }
}

// Get attendance data
$attendanceData = [];
if ($filter === 'week') {
    $sql = "SELECT 
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
    ORDER BY a.branch_name, e.last_name, e.first_name, a.attendance_date";
    
    $stmt = mysqli_prepare($db, $sql);
    $params = array_merge([$weekStart, $weekEnd], $searchParams);
    $types = 'ss' . $searchTypes;
    mysqli_stmt_bind_param($stmt, $types, ...$params);
} elseif ($filter === 'month') {
    $sql = "SELECT 
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
    ORDER BY a.branch_name, e.last_name, e.first_name, a.attendance_date";
    
    $stmt = mysqli_prepare($db, $sql);
    $params = array_merge([$monthStart, $monthEnd], $searchParams);
    $types = 'ss' . $searchTypes;
    mysqli_stmt_bind_param($stmt, $types, ...$params);
} else {
    $sql = "SELECT 
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
    ORDER BY a.branch_name, e.last_name, e.first_name";
    
    $stmt = mysqli_prepare($db, $sql);
    $params = array_merge([$selectedDate], $searchParams);
    $types = 's' . $searchTypes;
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Group data by branch
$branchData = [];
while ($row = mysqli_fetch_assoc($result)) {
    $branchName = $row['branch_name'] ?? 'Unassigned';
    
    // Calculate hours worked
    $hoursWorked = 0;
    if ($row['minutes_worked']) {
        $hoursWorked = round($row['minutes_worked'] / 60, 2);
    }
    
    // Determine status
    if ($row['time_in'] && $row['time_out']) {
        $status = 'COMPLETED';
    } elseif ($row['time_in']) {
        $status = 'PRESENT';
    } else {
        $status = $row['status'] ?? 'ABSENT';
    }
    
    // Format times
    $timeIn = $row['time_in'] ? date('h:i:s A', strtotime($row['time_in'])) : '-';
    $timeOut = $row['time_out'] ? date('h:i:s A', strtotime($row['time_out'])) : '-';
    $hoursDisplay = $hoursWorked > 0 ? number_format($hoursWorked, 2) : '-';
    
    $branchData[$branchName][] = [
        'name' => $row['first_name'] . ' ' . $row['last_name'],
        'employee_code' => $row['employee_code'],
        'time_in' => $timeIn,
        'time_out' => $timeOut,
        'hours' => $hoursDisplay,
        'status' => strtoupper($status),
        'position' => $row['position'],
        'date' => $row['attendance_date']
    ];
}
mysqli_stmt_close($stmt);

// Generate filename
$filename = 'attendance_' . str_replace(['-', ' '], ['', '_'], $dateRangeLabel) . '_By_Branch.xls';

// Output headers
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Color definitions matching reference image
$headerBg = '#FFC000';      // Gold/Yellow for branch and column headers
$nameBg = '#C6E0B4';        // Light Green for name column
$timeBg = '#B4C7E7';        // Light Blue for time/hours columns
$statusBg = '#F4B084';       // Light Salmon/Red for status column
$borderColor = '#000000';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { 
            border-collapse: collapse; 
            font-family: Calibri, Arial, sans-serif;
            font-size: 11pt;
        }
        th, td { 
            border: 1px solid <?php echo $borderColor; ?>; 
            padding: 6px 8px; 
            text-align: left;
            vertical-align: middle;
        }
        .branch-header {
            background-color: <?php echo $headerBg; ?>;
            font-weight: bold;
            font-size: 12pt;
            text-align: center;
        }
        .col-header {
            background-color: <?php echo $headerBg; ?>;
            font-weight: bold;
            text-align: center;
        }
        .name-cell {
            background-color: <?php echo $nameBg; ?>;
        }
        .time-cell {
            background-color: <?php echo $timeBg; ?>;
            text-align: center;
        }
        .status-cell {
            background-color: <?php echo $statusBg; ?>;
            text-align: center;
            font-weight: 600;
        }
        .title { 
            font-size: 14pt; 
            font-weight: bold; 
        }
        .info { 
            font-size: 10pt; 
        }
        .spacer {
            height: 8px;
        }
    </style>
</head>
<body>
    <table>
        <!-- Report Title -->
        <tr>
            <td colspan="5" class="title">Attendance Report by Branch</td>
        </tr>
        <tr>
            <td colspan="5" class="info">Period: <?php echo htmlspecialchars($dateRangeLabel); ?></td>
        </tr>
        <tr>
            <td colspan="5" class="info">Generated: <?php echo date('F d, Y h:i A'); ?></td>
        </tr>
        <tr>
            <td colspan="5" class="info">Generated by: <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></td>
        </tr>
        <?php if (!empty($searchQuery)): ?>
        <tr>
            <td colspan="5" class="info">Search Filter: <?php echo htmlspecialchars($searchQuery); ?> (<?php echo htmlspecialchars($searchType); ?>)</td>
        </tr>
        <?php endif; ?>
        <tr><td colspan="5" class="spacer">&nbsp;</td></tr>
        
        <?php if (empty($branchData)): ?>
            <tr>
                <td colspan="5" style="text-align: center; padding: 20px;">
                    No attendance records found for the selected period.
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($branchData as $branchName => $employees): ?>
                <!-- Branch Header -->
                <tr>
                    <td colspan="5" class="branch-header">
                        <?php echo htmlspecialchars(strtoupper($branchName)); ?>
                    </td>
                </tr>
                
                <!-- Column Headers -->
                <tr>
                    <th class="col-header">Name</th>
                    <th class="col-header">Time in</th>
                    <th class="col-header">Time out</th>
                    <th class="col-header">Total hours</th>
                    <th class="col-header">Status</th>
                </tr>
                
                <!-- Employee Data -->
                <?php foreach ($employees as $emp): ?>
                <tr>
                    <td class="name-cell"><?php echo htmlspecialchars(strtoupper($emp['name'])); ?></td>
                    <td class="time-cell"><?php echo htmlspecialchars($emp['time_in']); ?></td>
                    <td class="time-cell"><?php echo htmlspecialchars($emp['time_out']); ?></td>
                    <td class="time-cell"><?php echo htmlspecialchars($emp['hours']); ?></td>
                    <td class="status-cell"><?php echo htmlspecialchars($emp['status']); ?></td>
                </tr>
                <?php endforeach; ?>
                
                <!-- Spacer between branches -->
                <tr><td colspan="5" class="spacer">&nbsp;</td></tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>
</body>
</html>
