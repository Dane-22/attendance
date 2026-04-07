<?php
/**
 * Individual Employee Attendance Excel Export
 * Generates a focused attendance report for a single employee across a date range
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

// Get parameters (support both GET and POST)
$startDate = $_GET['start_date'] ?? $_POST['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? $_POST['end_date'] ?? null;
$employeeId = $_GET['employee_id'] ?? $_POST['employee_id'] ?? null;

// Validate required parameters
if (!$startDate || !$endDate || !$employeeId) {
    die('Missing required parameters: start_date, end_date, and employee_id are required.');
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
    die('Invalid date format. Use YYYY-MM-DD.');
}

// Validate employee_id is numeric
if (!is_numeric($employeeId)) {
    die('Invalid employee ID.');
}

// Get employee details
$employeeSql = "SELECT id, first_name, last_name, employee_code, position, 
                (SELECT branch_name FROM employees e2 WHERE e2.id = e.id AND e2.branch_id IS NOT NULL LIMIT 1) as branch_name
              FROM employees e WHERE id = ?";
$employeeStmt = mysqli_prepare($db, $employeeSql);
mysqli_stmt_bind_param($employeeStmt, 'i', $employeeId);
mysqli_stmt_execute($employeeStmt);
$employeeResult = mysqli_stmt_get_result($employeeStmt);
$employee = mysqli_fetch_assoc($employeeResult);
mysqli_stmt_close($employeeStmt);

if (!$employee) {
    die('Employee not found.');
}

$employeeName = $employee['first_name'] . ' ' . $employee['last_name'];
$employeeCode = $employee['employee_code'];
$employeePosition = $employee['position'];
$employeeBranch = $employee['branch_name'] ?? 'N/A';

// Date range label
$dateRangeLabel = date('M d', strtotime($startDate)) . ' - ' . date('M d, Y', strtotime($endDate));

// Get attendance data for the employee
$sql = "SELECT 
    a.id,
    a.attendance_date,
    a.time_in,
    a.time_out,
    a.branch_name,
    a.status,
    TIMESTAMPDIFF(MINUTE, a.time_in, COALESCE(a.time_out, NOW())) as minutes_worked
FROM attendance a
WHERE a.employee_id = ? 
    AND a.attendance_date BETWEEN ? AND ?
ORDER BY a.attendance_date ASC";

$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, 'iss', $employeeId, $startDate, $endDate);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$attendanceData = [];
$totalHours = 0;
$daysPresent = 0;
$daysAbsent = 0;
$daysCompleted = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $hoursWorked = 0;
    if ($row['minutes_worked']) {
        $hoursWorked = round($row['minutes_worked'] / 60, 2);
    }
    
    // Determine status
    if ($row['time_in'] && $row['time_out']) {
        $status = 'COMPLETED';
        $daysCompleted++;
        $daysPresent++;
    } elseif ($row['time_in']) {
        $status = 'PRESENT';
        $daysPresent++;
    } else {
        $status = $row['status'] ?? 'ABSENT';
        $daysAbsent++;
    }
    
    $totalHours += $hoursWorked;
    
    $attendanceData[] = [
        'date' => $row['attendance_date'],
        'day_of_week' => date('D', strtotime($row['attendance_date'])),
        'time_in' => $row['time_in'] ? date('h:i:s A', strtotime($row['time_in'])) : '-',
        'time_out' => $row['time_out'] ? date('h:i:s A', strtotime($row['time_out'])) : '-',
        'hours' => $hoursWorked > 0 ? number_format($hoursWorked, 2) : '-',
        'status' => strtoupper($status),
        'branch' => $row['branch_name'] ?? 'N/A'
    ];
}
mysqli_stmt_close($stmt);

// Calculate date range statistics
$dateStart = new DateTime($startDate);
$dateEnd = new DateTime($endDate);
$interval = $dateStart->diff($dateEnd);
$totalDaysInRange = $interval->days + 1;
$averageHours = $daysPresent > 0 ? round($totalHours / $daysPresent, 2) : 0;

// Create new Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Define colors
$headerBg = 'FFC000';      // Gold/Yellow for headers
$employeeBg = 'D9E1F2';    // Light blue for employee info
$labelBg = 'F4F4F4';       // Light gray for labels
$dataGreen = 'C6E0B4';     // Light green
$dataBlue = 'B4C7E7';      // Light blue
$dataOrange = 'F4B084';    // Light salmon/orange
$borderColor = '000000';

// Set column widths
$sheet->getColumnDimension('A')->setWidth(15);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(15);
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getColumnDimension('E')->setWidth(12);
$sheet->getColumnDimension('F')->setWidth(15);
$sheet->getColumnDimension('G')->setWidth(25);

// Title
$sheet->setCellValue('A1', 'INDIVIDUAL EMPLOYEE ATTENDANCE REPORT');
$sheet->mergeCells('A1:G1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($headerBg);

// Employee Info Section
$currentRow = 3;

$sheet->setCellValue('A' . $currentRow, 'EMPLOYEE INFORMATION');
$sheet->mergeCells('A' . $currentRow . ':G' . $currentRow);
$sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(11);
$sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($employeeBg);
$currentRow++;

// Employee details
$sheet->setCellValue('A' . $currentRow, 'Name:');
$sheet->setCellValue('B' . $currentRow, strtoupper($employeeName));
$sheet->setCellValue('D' . $currentRow, 'Employee Code:');
$sheet->setCellValue('E' . $currentRow, $employeeCode);
$sheet->getStyle('A' . $currentRow)->getFont()->setBold(true);
$sheet->getStyle('D' . $currentRow)->getFont()->setBold(true);
$currentRow++;

$sheet->setCellValue('A' . $currentRow, 'Position:');
$sheet->setCellValue('B' . $currentRow, $employeePosition);
$sheet->setCellValue('D' . $currentRow, 'Default Branch:');
$sheet->setCellValue('E' . $currentRow, $employeeBranch);
$sheet->getStyle('A' . $currentRow)->getFont()->setBold(true);
$sheet->getStyle('D' . $currentRow)->getFont()->setBold(true);
$currentRow++;

// Empty row
$currentRow++;

// Report Period
$sheet->setCellValue('A' . $currentRow, 'REPORT PERIOD');
$sheet->mergeCells('A' . $currentRow . ':G' . $currentRow);
$sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(11);
$sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($employeeBg);
$currentRow++;

$sheet->setCellValue('A' . $currentRow, 'Start Date:');
$sheet->setCellValue('B' . $currentRow, date('F d, Y', strtotime($startDate)));
$sheet->setCellValue('D' . $currentRow, 'End Date:');
$sheet->setCellValue('E' . $currentRow, date('F d, Y', strtotime($endDate)));
$sheet->getStyle('A' . $currentRow)->getFont()->setBold(true);
$sheet->getStyle('D' . $currentRow)->getFont()->setBold(true);
$currentRow++;

$sheet->setCellValue('A' . $currentRow, 'Total Days:');
$sheet->setCellValue('B' . $currentRow, $totalDaysInRange);
$sheet->setCellValue('D' . $currentRow, 'Records Found:');
$sheet->setCellValue('E' . $currentRow, count($attendanceData));
$sheet->getStyle('A' . $currentRow)->getFont()->setBold(true);
$sheet->getStyle('D' . $currentRow)->getFont()->setBold(true);
$currentRow++;

// Empty row
$currentRow++;

// Summary Statistics
$sheet->setCellValue('A' . $currentRow, 'ATTENDANCE SUMMARY');
$sheet->mergeCells('A' . $currentRow . ':G' . $currentRow);
$sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(11);
$sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($employeeBg);
$currentRow++;

$sheet->setCellValue('A' . $currentRow, 'Days Present:');
$sheet->setCellValue('B' . $currentRow, $daysPresent);
$sheet->setCellValue('C' . $currentRow, 'Days Absent:');
$sheet->setCellValue('D' . $currentRow, $daysAbsent);
$sheet->setCellValue('E' . $currentRow, 'Shifts Completed:');
$sheet->setCellValue('F' . $currentRow, $daysCompleted);
$sheet->getStyle('A' . $currentRow)->getFont()->setBold(true);
$sheet->getStyle('C' . $currentRow)->getFont()->setBold(true);
$sheet->getStyle('E' . $currentRow)->getFont()->setBold(true);
$currentRow++;

$sheet->setCellValue('A' . $currentRow, 'Total Hours:');
$sheet->setCellValue('B' . $currentRow, number_format($totalHours, 2));
$sheet->setCellValue('C' . $currentRow, 'Avg Hours/Day:');
$sheet->setCellValue('D' . $currentRow, $averageHours);
$sheet->getStyle('A' . $currentRow)->getFont()->setBold(true);
$sheet->getStyle('C' . $currentRow)->getFont()->setBold(true);
$currentRow++;

// Empty row
$currentRow++;

// Attendance Records Header
$sheet->setCellValue('A' . $currentRow, 'DAILY ATTENDANCE RECORDS');
$sheet->mergeCells('A' . $currentRow . ':G' . $currentRow);
$sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(11);
$sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($headerBg);
$currentRow++;

// Column Headers
$headers = ['Date', 'Day', 'Time In', 'Time Out', 'Hours', 'Status', 'Branch'];
$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . $currentRow, $header);
    $col++;
}
$colHeaderStyle = $sheet->getStyle('A' . $currentRow . ':G' . $currentRow);
$colHeaderStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($headerBg);
$colHeaderStyle->getFont()->setBold(true);
$colHeaderStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$colHeaderStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
$currentRow++;

// Attendance Data Rows
if (empty($attendanceData)) {
    $sheet->setCellValue('A' . $currentRow, 'No attendance records found for the selected period.');
    $sheet->mergeCells('A' . $currentRow . ':G' . $currentRow);
    $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A' . $currentRow)->getFont()->setItalic(true);
} else {
    foreach ($attendanceData as $record) {
        $sheet->setCellValue('A' . $currentRow, date('M d, Y', strtotime($record['date'])));
        $sheet->setCellValue('B' . $currentRow, $record['day_of_week']);
        $sheet->setCellValue('C' . $currentRow, $record['time_in']);
        $sheet->setCellValue('D' . $currentRow, $record['time_out']);
        $sheet->setCellValue('E' . $currentRow, $record['hours']);
        $sheet->setCellValue('F' . $currentRow, $record['status']);
        $sheet->setCellValue('G' . $currentRow, $record['branch']);
        
        // Apply status-based coloring
        $statusColor = $dataOrange; // Default for ABSENT
        if ($record['status'] === 'COMPLETED') {
            $statusColor = $dataGreen;
        } elseif ($record['status'] === 'PRESENT') {
            $statusColor = $dataBlue;
        }
        
        // Date and Day columns - light yellow
        $sheet->getStyle('A' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF2CC');
        $sheet->getStyle('B' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF2CC');
        // Time columns - light blue
        $sheet->getStyle('C' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($dataBlue);
        $sheet->getStyle('D' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($dataBlue);
        // Hours column - light green
        $sheet->getStyle('E' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($dataGreen);
        // Status column - based on status
        $sheet->getStyle('F' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($statusColor);
        $sheet->getStyle('F' . $currentRow)->getFont()->setBold(true);
        // Branch column - white/default
        
        // Center align most columns
        $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C' . $currentRow . ':F' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Add borders
        $sheet->getStyle('A' . $currentRow . ':G' . $currentRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        $currentRow++;
    }
}

// Footer with generation info
$currentRow += 2;
$sheet->setCellValue('A' . $currentRow, 'Report generated on: ' . date('F d, Y h:i A') . ' by ' . ($_SESSION['full_name'] ?? 'Admin'));
$sheet->mergeCells('A' . $currentRow . ':G' . $currentRow);
$sheet->getStyle('A' . $currentRow)->getFont()->setSize(9)->setItalic(true);
$sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

// Generate filename
$filename = 'individual_attendance_' . preg_replace('/[^a-zA-Z0-9]/', '_', $employeeName) . '_' . 
            str_replace(['-', ' '], ['', '_'], $dateRangeLabel) . '.xlsx';

// Set headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: public');

// Create writer and output
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
