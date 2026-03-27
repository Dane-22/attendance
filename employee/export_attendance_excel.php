<?php
/**
 * Attendance Excel Export by Branch
 * Generates branch-grouped attendance reports with color-coded formatting using PhpSpreadsheet
 */

require_once __DIR__ . '/../conn/db_connection.php';
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

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
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

// Validate dates
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $selectedDate = date('Y-m-d');
}

// Validate custom date range if provided
$isCustomRange = false;
if ($startDate && $endDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
    $isCustomRange = true;
    $dateRangeLabel = date('M d', strtotime($startDate)) . ' - ' . date('M d, Y', strtotime($endDate));
} else {
    // Determine date range based on filter
    if ($filter === 'week') {
        $weekStart = date('Y-m-d', strtotime('monday this week', strtotime($selectedDate)));
        $weekEnd = date('Y-m-d', strtotime('sunday this week', strtotime($selectedDate)));
        $startDate = $weekStart;
        $endDate = $weekEnd;
        $dateRangeLabel = date('M d', strtotime($weekStart)) . ' - ' . date('M d, Y', strtotime($weekEnd));
    } elseif ($filter === 'month') {
        $monthStart = date('Y-m-01', strtotime($selectedDate));
        $monthEnd = date('Y-m-t', strtotime($selectedDate));
        $startDate = $monthStart;
        $endDate = $monthEnd;
        $dateRangeLabel = date('F Y', strtotime($selectedDate));
    } else {
        $startDate = $selectedDate;
        $endDate = $selectedDate;
        $dateRangeLabel = date('F d, Y', strtotime($selectedDate));
    }
}

// Build search condition
$searchCondition = '';
$searchParams = [];
$searchTypes = '';

// Exclude main branch and main office from export
$excludeBranchesCondition = " AND (a.branch_name IS NULL OR (LOWER(a.branch_name) NOT LIKE '%main branch%' AND LOWER(a.branch_name) NOT LIKE '%main office%'))";

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

// Get attendance data using unified date range
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
WHERE a.attendance_date BETWEEN ? AND ?" . $excludeBranchesCondition . $searchCondition . "
ORDER BY a.branch_name, e.last_name, e.first_name, a.attendance_date";

$stmt = mysqli_prepare($db, $sql);
$params = array_merge([$startDate, $endDate], $searchParams);
$types = 'ss' . $searchTypes;
mysqli_stmt_bind_param($stmt, $types, ...$params);

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

// Create new Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Define colors
$headerBg = 'FFC000';      // Gold/Yellow for branch and column headers
$nameBg = 'C6E0B4';        // Light Green for name column
$timeBg = 'B4C7E7';        // Light Blue for time/hours columns
$statusBg = 'F4B084';      // Light Salmon/Orange for status column
$borderColor = '000000';

// Set column widths
$sheet->getColumnDimension('A')->setWidth(35);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(15);
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getColumnDimension('E')->setWidth(15);

// Title
$sheet->setCellValue('A1', 'Attendance Report by Branch');
$sheet->mergeCells('A1:E1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

// Period
$sheet->setCellValue('A2', 'Period: ' . $dateRangeLabel);
$sheet->mergeCells('A2:E2');
$sheet->getStyle('A2')->getFont()->setSize(10);

// Generated date
$sheet->setCellValue('A3', 'Generated: ' . date('F d, Y h:i A'));
$sheet->mergeCells('A3:E3');
$sheet->getStyle('A3')->getFont()->setSize(10);

// Generated by
$sheet->setCellValue('A4', 'Generated by: ' . ($_SESSION['full_name'] ?? 'Admin'));
$sheet->mergeCells('A4:E4');
$sheet->getStyle('A4')->getFont()->setSize(10);

// Search filter if applicable
$currentRow = 5;
if (!empty($searchQuery)) {
    $sheet->setCellValue('A' . $currentRow, 'Search Filter: ' . $searchQuery . ' (' . $searchType . ')');
    $sheet->mergeCells('A' . $currentRow . ':E' . $currentRow);
    $sheet->getStyle('A' . $currentRow)->getFont()->setSize(10);
    $currentRow++;
}

// Empty row
$currentRow++;

// Check if there's data
if (empty($branchData)) {
    $sheet->setCellValue('A' . $currentRow, 'No attendance records found for the selected period.');
    $sheet->mergeCells('A' . $currentRow . ':E' . $currentRow);
    $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
} else {
    foreach ($branchData as $branchName => $employees) {
        // Skip main branch and main office (case-insensitive check as safety measure)
        $branchNameLower = strtolower($branchName);
        if (strpos($branchNameLower, 'main branch') !== false || strpos($branchNameLower, 'main office') !== false) {
            continue;
        }
        
        // Branch Header
        $sheet->setCellValue('A' . $currentRow, strtoupper($branchName));
        $sheet->mergeCells('A' . $currentRow . ':E' . $currentRow);
        $branchHeaderStyle = $sheet->getStyle('A' . $currentRow . ':E' . $currentRow);
        $branchHeaderStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($headerBg);
        $branchHeaderStyle->getFont()->setBold(true)->setSize(12);
        $branchHeaderStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $branchHeaderStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $currentRow++;
        
        // Column Headers
        $headers = ['Name', 'Time in', 'Time out', 'Total hours', 'Status'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $currentRow, $header);
            $col++;
        }
        $colHeaderStyle = $sheet->getStyle('A' . $currentRow . ':E' . $currentRow);
        $colHeaderStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($headerBg);
        $colHeaderStyle->getFont()->setBold(true);
        $colHeaderStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $colHeaderStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $currentRow++;
        
        // Employee Data
        foreach ($employees as $emp) {
            $sheet->setCellValue('A' . $currentRow, strtoupper($emp['name']));
            $sheet->setCellValue('B' . $currentRow, $emp['time_in']);
            $sheet->setCellValue('C' . $currentRow, $emp['time_out']);
            $sheet->setCellValue('D' . $currentRow, $emp['hours']);
            $sheet->setCellValue('E' . $currentRow, $emp['status']);
            
            // Apply cell colors
            // Name column - light green
            $sheet->getStyle('A' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($nameBg);
            // Time columns - light blue
            $sheet->getStyle('B' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($timeBg);
            $sheet->getStyle('C' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($timeBg);
            $sheet->getStyle('D' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($timeBg);
            // Status column - light salmon/orange
            $sheet->getStyle('E' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($statusBg);
            $sheet->getStyle('E' . $currentRow)->getFont()->setBold(true);
            
            // Center align time and status columns
            $sheet->getStyle('B' . $currentRow . ':E' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Add borders
            $sheet->getStyle('A' . $currentRow . ':E' . $currentRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            
            $currentRow++;
        }
        
        // Empty row between branches
        $currentRow++;
    }
}

// Generate filename
$filename = 'attendance_' . str_replace(['-', ' '], ['', '_'], $dateRangeLabel) . '_By_Branch.xlsx';

// Set headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: public');

// Create writer and output
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
