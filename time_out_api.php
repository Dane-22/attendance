Dan
dan3gt4yh235
Online



Dan — 10:55 AM
yow
CJ
 joined the party. — 10:57 AM

Wave to say hi!
Jhunell Acas
 just slid into the server. — 11:01 AM

Wave to say hi!
Welcome 
Prince Christiane Tolentino
. Say hi! — 11:02 AM

Wave to say hi!
Dan — 11:40 AM
-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 03, 2026 at 03:40 AM

rate_limit.sql
2 KB
-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 03, 2026 at 03:39 AM

performance_adjustments (1).sql
2 KB
-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 03, 2026 at 03:39 AM

login_attempts.sql
2 KB
-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 03, 2026 at 03:39 AM

employee_transfers.sql
2 KB
-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 03, 2026 at 03:39 AM

employees (2).sql
15 KB
-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 03, 2026 at 03:39 AM

documents.sql
3 KB
-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 03, 2026 at 03:39 AM

branch_reset_log.sql
2 KB
-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 03, 2026 at 03:39 AM

branches (1).sql
2 KB
-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 03, 2026 at 03:39 AM

activity_logs.sql
2 KB
-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 03, 2026 at 03:39 AM

attendance_db (9).sql
25 KB
-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 03, 2026 at 03:39 AM

activity_logs.sql
2 KB
-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 03, 2026 at 03:39 AM

attendance_db (9).sql
25 KB
-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 03, 2026 at 03:39 AM

attendance.sql
4 KB
-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 03, 2026 at 12:43 AM

employees (1).sql
16 KB
Dan — 1:09 PM
-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 03, 2026 at 05:08 AM

attendance_db (10).sql
25 KB
Dan — 1:40 PM
<?php
// employee/select_employee.php
session_start();

// ===== SET PHILIPPINE TIME ZONE =====
date_default_timezone_set('Asia/Manila'); // Philippine Time (UTC+8)

select_employee.php
13 KB
<?php
$employeeName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$employeeCode = $_SESSION['employee_code'];
$position = $_SESSION['position'] ?? 'Employee';
$userRole = $_SESSION['role'] ?? 'Employee';

attendance.php
31 KB
<?php
// api/clock_in.php
session_start();
require_once __DIR__ . '/../../conn/db_connection.php';

header('Content-Type: application/json');

clock_in.php
6 KB
<?php
// api/clock_out.php
session_start();
require_once __DIR__ . '/../../conn/db_connection.php';

header('Content-Type: application/json');

clock_out.php
2 KB
// ===== EMPLOYEE ATTENDANCE MANAGEMENT SCRIPT =====

    // Global variables
    let selectedBranch = null;
    let currentStatusFilter = 'available'; // 'all', 'present', 'absent', or 'available'
    let currentView = 'list';

attendance.js
41 KB
Prince Christiane Tolentino — 1:52 PM
<?php
// clock_out_api.php
require_once __DIR__ . '/conn/db_connection.php';
header('Content-Type: application/json');

// Input
$employeeId = $_POST['employee_id'] ?? null;
$branchName = $_POST['branch_name'] ?? null;

if (!$employeeId) {
    echo json_encode(['success' => false, 'message' => 'Missing employee_id']);
    exit();
}

// Find today's open attendance row (time_in set, time_out NULL)
$sql = "SELECT id FROM attendance WHERE employee_id = ? AND attendance_date = CURDATE() AND time_in IS NOT NULL AND time_out IS NULL ORDER BY id DESC LIMIT 1";
$stmt = mysqli_prepare($db, $sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error (prepare select)']);
    exit();
}
mysqli_stmt_bind_param($stmt, "i", $employeeId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'No open attendance (clock in) found for today']);
    exit();
}
$attendanceId = intval($row['id']);

// Update time_out
if ($branchName !== null && $branchName !== '') {
    $updateSql = "UPDATE attendance SET time_out = NOW(), branch_name = ? WHERE id = ?";
    $updateStmt = mysqli_prepare($db, $updateSql);
    if (!$updateStmt) {
        echo json_encode(['success' => false, 'message' => 'Database error (prepare update)']);
        exit();
    }
    mysqli_stmt_bind_param($updateStmt, "si", $branchName, $attendanceId);
} else {
    $updateSql = "UPDATE attendance SET time_out = NOW() WHERE id = ?";
    $updateStmt = mysqli_prepare($db, $updateSql);
    if (!$updateStmt) {
        echo json_encode(['success' => false, 'message' => 'Database error (prepare update)']);
        exit();
    }
    mysqli_stmt_bind_param($updateStmt, "i", $attendanceId);
}

if (mysqli_stmt_execute($updateStmt)) {
    $timeOut = date('H:i:s');
    echo json_encode([
        'success' => true,
        'message' => 'Clocked out successfully',
        'time_out' => $timeOut,
        'attendance_id' => $attendanceId
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($db)]);
}
mysqli_stmt_close($updateStmt);
?>

clock_out_api.php
3 KB
Prince Christiane Tolentino — 2:04 PM
<?php
require_once __DIR__ . '/conn/db_connection.php';
header('Content-Type: application/json');

$employeeId = $_POST['employee_id'] ?? null;
$branchName = $_POST['branch_name'] ?? null;

time_out_api.php
2 KB
<?php
require_once __DIR__ . '/conn/db_connection.php';
header('Content-Type: application/json');

$employeeId = $_POST['employee_id'] ?? null;
$branchName = $_POST['branch_name'] ?? null;

time_in_api.php
2 KB
Dan — 2:42 PM
-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 03, 2026 at 06:42 AM

attendance (1).sql
8 KB
Prince Christiane Tolentino — 2:53 PM
<?php
require_once __DIR__ . '/conn/db_connection.php';
header('Content-Type: application/json');

$employeeId = $_POST['employee_id'] ?? null;
$branchName = $_POST['branch_name'] ?? null;

time_in_api.php
2 KB
<?php
require_once __DIR__ . '/conn/db_connection.php';
header('Content-Type: application/json');

$employeeId = $_POST['employee_id'] ?? null;
$branchName = $_POST['branch_name'] ?? null;

time_out_api.php
2 KB
<?php
// employees_today_status_api.php
if (file_exists(__DIR__ . '/conn/db_connection.php')) {
    require_once __DIR__ . '/conn/db_connection.php';
} else {
    require_once __DIR__ . '/db_connection.php';

employees_today_status_api.php
3 KB
Dan — 3:29 PM
-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 03, 2026 at 07:28 AM

attendance_db (11).sql
30 KB
Prince Christiane Tolentino — 3:32 PM
Image
Dan — 3:36 PM
-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 03, 2026 at 07:36 AM

attendance_db (12).sql
31 KB
Prince Christiane Tolentino — 3:45 PM
<?php
// employees_today_status_api.php
if (file_exists(__DIR__ . '/conn/db_connection.php')) {
    require_once __DIR__ . '/conn/db_connection.php';
} else {
    require_once __DIR__ . '/db_connection.php';

employees_today_status_api.php
4 KB
<?php
// employees_today_status_api.php
if (file_exists(__DIR__ . '/conn/db_connection.php')) {
    require_once __DIR__ . '/conn/db_connection.php';
} else {
    require_once __DIR__ . '/db_connection.php';

employees_today_status_api.php
4 KB
Dan — 3:53 PM
SUPER001

12345678
-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 03, 2026 at 07:54 AM

attendance_db (7).sql
31 KB
Prince Christiane Tolentino — 3:59 PM
ds
<?php
require_once __DIR__ . '/conn/db_connection.php';
header('Content-Type: application/json');

$employeeId = $_POST['employee_id'] ?? null;
$branchName = $_POST['branch_name'] ?? null;

time_out_api.php
3 KB
<?php
require_once __DIR__ . '/conn/db_connection.php';
header('Content-Type: application/json');

$employeeId = $_POST['employee_id'] ?? null;
$branchName = $_POST['branch_name'] ?? null;

time_in_api.php
3 KB
﻿
<?php
require_once __DIR__ . '/conn/db_connection.php';
header('Content-Type: application/json');

$employeeId = $_POST['employee_id'] ?? null;
$branchName = $_POST['branch_name'] ?? null;

if (!$employeeId || !$branchName) {
    echo json_encode(['success' => false, 'message' => 'Missing employee_id or branch_name']);
    exit();
}

function attendanceHasColumn($db, $columnName) {
    $safe = mysqli_real_escape_string($db, $columnName);
    $sql = "SHOW COLUMNS FROM `attendance` LIKE '{$safe}'";
    $result = mysqli_query($db, $sql);
    return $result && mysqli_num_rows($result) > 0;
}

$hasTimeIn = attendanceHasColumn($db, 'time_in');
$hasTimeOut = attendanceHasColumn($db, 'time_out');
$hasIsTimeRunning = attendanceHasColumn($db, 'is_time_running');

if (!$hasTimeIn) {
    echo json_encode([
        'success' => false,
        'message' => 'Server database is missing attendance.time_in. Please run DB migration on the correct database.'
    ]);
    exit();
}

if (!$hasTimeOut) {
    echo json_encode([
        'success' => false,
        'message' => 'Server database is missing attendance.time_out. Please run DB migration on the correct database.'
    ]);
    exit();
}

$date = date('Y-m-d');

// Find today's latest running attendance row
$sql = $hasIsTimeRunning
    ? "SELECT id, time_in, time_out, is_time_running, branch_name FROM attendance WHERE employee_id = ? AND attendance_date = ? AND is_time_running = 1 ORDER BY id DESC LIMIT 1"
    : "SELECT id, time_in, time_out, 0 as is_time_running, branch_name FROM attendance WHERE employee_id = ? AND attendance_date = ? AND time_in IS NOT NULL AND time_out IS NULL ORDER BY id DESC LIMIT 1";
$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, 'is', $employeeId, $date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'No open attendance record for time out']);
    exit();
}

if (!empty($row['branch_name']) && $row['branch_name'] !== $branchName) {
    echo json_encode(['success' => false, 'message' => 'Cannot time out from a different branch']);
    exit();
}

$attendanceId = $row['id'];
$updateSql = "UPDATE attendance SET time_out = NOW(), is_time_running = 0, updated_at = NOW() WHERE id = ?";
$updateStmt = mysqli_prepare($db, $updateSql);
mysqli_stmt_bind_param($updateStmt, 'i', $attendanceId);
if (mysqli_stmt_execute($updateStmt)) {
    echo json_encode([
        'success' => true,
        'message' => 'Time out recorded',
        'attendance_id' => $attendanceId,
        'time_out' => date('Y-m-d H:i:s'),
        'is_time_running' => false
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($db)]);
}
mysqli_stmt_close($updateStmt);
?>
