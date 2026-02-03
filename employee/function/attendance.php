<?php
$employeeName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$employeeCode = $_SESSION['employee_code'];
$position = $_SESSION['position'] ?? 'Employee';
$userRole = $_SESSION['role'] ?? 'Employee';

// DEBUG: Log role for debugging
error_log("DEBUG select_employee.php: User Role = '$userRole'");

// Get current PHILIPPINE time
$currentTime = date('H:i'); // Philippine time
$cutoffTime = '09:00'; // 9 AM cutoff (Philippine time)

// Determine if we're before or after cutoff time
$isBeforeCutoff = $currentTime < $cutoffTime;

// ===== RATE LIMITER CONFIGURATION =====
// COMMENT OUT MUNA ANG RATE LIMITER PARA MA-TEST
$rateLimitEnabled = false; // CHANGE TO false PARA I-DISABLE MUNA
$rateLimitWindow = 60; // 60 seconds
$rateLimitMaxRequests = 30; // Maximum requests per window

function attendanceHasTimeColumns($db) {
    static $cached = null;
    if ($cached !== null) return $cached;

    $sql = "SELECT COUNT(*) as cnt
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'attendance'
              AND COLUMN_NAME IN ('time_in','time_out')";
    $result = mysqli_query($db, $sql);
    if (!$result) {
        $cached = false;
        return $cached;
    }
    $row = mysqli_fetch_assoc($result);
    $cached = intval($row['cnt'] ?? 0) === 2;
    return $cached;
}

function checkRateLimit() {
    global $rateLimitEnabled, $rateLimitWindow, $rateLimitMaxRequests;
    
    if (!$rateLimitEnabled) return true;
    
    // Huwag gamitin ang session_start() dito, naka-start na sa taas
    $currentTime = time();
    $userId = $_SESSION['employee_code'] ?? 'anonymous';
    $rateLimitKey = "ratelimit_$userId";
    
    if (!isset($_SESSION[$rateLimitKey])) {
        $_SESSION[$rateLimitKey] = [
            'count' => 1,
            'window_start' => $currentTime
        ];
        return true;
    }
    
    $rateData = $_SESSION[$rateLimitKey];
    
    // Reset if window has passed
    if ($currentTime - $rateData['window_start'] > $rateLimitWindow) {
        $_SESSION[$rateLimitKey] = [
            'count' => 1,
            'window_start' => $currentTime
        ];
        return true;
    }
    
    // Check if limit exceeded
    if ($rateData['count'] >= $rateLimitMaxRequests) {
        return false;
    }
    
    // Increment count
    $_SESSION[$rateLimitKey]['count']++;
    return true;
}

// ===== BRANCH MANAGEMENT ACTIONS (INTEGRATED) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['branch_action'])) {
        header('Content-Type: application/json');
        
        // DEBUG version - force admin access
        $isAdmin = true; // Force true for debugging
        
        if ($_POST['branch_action'] === 'add_branch') {
            if (!$isAdmin) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit();
            }
            $branch_name = isset($_POST['branch_name']) ? trim($_POST['branch_name']) : '';
            if (empty($branch_name) || strlen($branch_name) < 2 || strlen($branch_name) > 255) {
                echo json_encode(['success' => false, 'message' => 'Branch name must be 2-255 characters']);
                exit();
            }
            $checkQuery = "SELECT id FROM branches WHERE branch_name = ?";
            $checkStmt = mysqli_prepare($db, $checkQuery);
            mysqli_stmt_bind_param($checkStmt, 's', $branch_name);
            mysqli_stmt_execute($checkStmt);
            if (mysqli_stmt_get_result($checkStmt)->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Branch already exists']);
                exit();
            }
            $insertQuery = "INSERT INTO branches (branch_name, created_at) VALUES (?, NOW())";
            $insertStmt = mysqli_prepare($db, $insertQuery);
            mysqli_stmt_bind_param($insertStmt, 's', $branch_name);
            if (mysqli_stmt_execute($insertStmt)) {
                echo json_encode(['success' => true, 'message' => 'Branch added successfully', 'branch_id' => mysqli_insert_id($db), 'branch_name' => $branch_name]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error adding branch: ' . mysqli_error($db)]);
            }
            exit();
        }
        
        if ($_POST['branch_action'] === 'delete_branch') {
            if (!$isAdmin) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit();
            }
            $branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
            if ($branch_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid branch ID']);
                exit();
            }
            $getBranchQuery = "SELECT branch_name FROM branches WHERE id = ?";
            $getBranchStmt = mysqli_prepare($db, $getBranchQuery);
            mysqli_stmt_bind_param($getBranchStmt, 'i', $branch_id);
            mysqli_stmt_execute($getBranchStmt);
            $branchResult = mysqli_stmt_get_result($getBranchStmt);
            if ($branchResult->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'Branch not found']);
                exit();
            }
            $branchRow = mysqli_fetch_assoc($branchResult);
            $branch_name = $branchRow['branch_name'];
            $checkEmployeesQuery = "SELECT COUNT(*) as count FROM employees WHERE branch_name = ? AND status = 'Active'";
            $checkEmployeesStmt = mysqli_prepare($db, $checkEmployeesQuery);
            mysqli_stmt_bind_param($checkEmployeesStmt, 's', $branch_name);
            mysqli_stmt_execute($checkEmployeesStmt);
            $employeeCount = mysqli_fetch_assoc(mysqli_stmt_get_result($checkEmployeesStmt));
            if ($employeeCount['count'] > 0) {
                echo json_encode(['success' => false, 'message' => "Cannot delete branch with active employees. ({$employeeCount['count']} assigned)"]);
                exit();
            }
            $deleteQuery = "DELETE FROM branches WHERE id = ?";
            $deleteStmt = mysqli_prepare($db, $deleteQuery);
            mysqli_stmt_bind_param($deleteStmt, 'i', $branch_id);
            if (mysqli_stmt_execute($deleteStmt)) {
                echo json_encode(['success' => true, 'message' => "Branch '{$branch_name}' deleted successfully"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error deleting branch: ' . mysqli_error($db)]);
            }
            exit();
        }
    }
}

// Get available branches from branches table
$branchesQuery = "SELECT id, branch_name FROM branches WHERE is_active = 1 ORDER BY branch_name ASC";
$branchesResult = mysqli_query($db, $branchesQuery);
$branches = [];
while ($row = mysqli_fetch_assoc($branchesResult)) {
    $branches[] = ['id' => $row['id'], 'branch_name' => $row['branch_name']];
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // TEMPORARY: COMMENT OUT RATE LIMITER FOR TESTING
    // if (!checkRateLimit()) {
    //     echo json_encode(['success' => false, 'message' => 'Rate limit exceeded. Please wait a minute.']);
    //     exit();
    // }

    if ($_POST['action'] === 'load_employees') {
        $branch = $_POST['branch'] ?? '';
        $statusFilter = $_POST['status_filter'] ?? 'all';
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $perPage = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;
        
        // Validate pagination parameters
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage)); // Limit to 100 per page max
        
        $offset = ($page - 1) * $perPage;

        if (empty($branch)) {
            echo json_encode(['success' => false, 'message' => 'Deployment branch is required']);
            exit();
        }

        // Validate that the branch exists
        $branchCheckQuery = "SELECT id FROM branches WHERE branch_name = ? AND is_active = 1";
        $branchCheckStmt = mysqli_prepare($db, $branchCheckQuery);
        mysqli_stmt_bind_param($branchCheckStmt, 's', $branch);
        mysqli_stmt_execute($branchCheckStmt);
        $branchCheckResult = mysqli_stmt_get_result($branchCheckStmt);
        if (mysqli_num_rows($branchCheckResult) === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid branch selected']);
            exit();
        }

        // DEBUG: Log parameters
        error_log("DEBUG: Loading employees - branch: $branch, status_filter: $statusFilter, page: $page, perPage: $perPage, offset: $offset");
        
        try {
            // Build base query for counting - Based on status filter
            $countQuery = "";
            $countParams = [];
            
            if ($statusFilter === 'present') {
                // Show only employees who timed-in to the selected branch today
                $countQuery = "SELECT COUNT(*) as total
                              FROM employees e
                              INNER JOIN (
                                  SELECT a1.*
                                  FROM attendance a1
                                  INNER JOIN (
                                      SELECT employee_id, MAX(id) AS max_id
                                      FROM attendance
                                      WHERE attendance_date = CURDATE() AND branch_name = ? AND time_in IS NOT NULL
                                      GROUP BY employee_id
                                  ) t ON a1.id = t.max_id
                              ) a ON e.id = a.employee_id
                              WHERE e.status = 'Active'";
                $countParams = [$branch];
            } elseif ($statusFilter === 'absent') {
                // Show only ABSENT employees (both auto and manual)
                $countQuery = "SELECT COUNT(*) as total
                              FROM employees e
                              INNER JOIN (
                                  SELECT a1.*
                                  FROM attendance a1
                                  INNER JOIN (
                                      SELECT employee_id, MAX(id) AS max_id
                                      FROM attendance
                                      WHERE attendance_date = CURDATE()
                                      GROUP BY employee_id
                                  ) t ON a1.id = t.max_id
                              ) a ON e.id = a.employee_id
                              WHERE e.status = 'Active' AND a.status = 'Absent' AND a.branch_name = ?";
                $countParams = [$branch];
            } elseif ($statusFilter === 'available') {
                // Show AVAILABLE employees (not yet marked today)
                $countQuery = "SELECT COUNT(*) as total
                              FROM employees e
                              LEFT JOIN (
                                  SELECT a1.*
                                  FROM attendance a1
                                  INNER JOIN (
                                      SELECT employee_id, MAX(id) AS max_id
                                      FROM attendance
                                      WHERE attendance_date = CURDATE()
                                      GROUP BY employee_id
                                  ) t ON a1.id = t.max_id
                              ) a ON e.id = a.employee_id
                              WHERE e.status = 'Active' AND a.id IS NULL";
                $countParams = [];
            } else {
                // Show ALL employees with their attendance status - for pull method, show all
                $countQuery = "SELECT COUNT(*) as total
                              FROM employees e
                              WHERE e.status = 'Active'";
                $countParams = [];
            }
            
            // Execute count query
            error_log("DEBUG: Count Query: $countQuery");
            $countStmt = mysqli_prepare($db, $countQuery);
            if (!empty($countParams)) {
                mysqli_stmt_bind_param($countStmt, str_repeat('s', count($countParams)), ...$countParams);
            }
            mysqli_stmt_execute($countStmt);
            $countResult = mysqli_stmt_get_result($countStmt);
            
            if (!$countResult) {
                error_log("DEBUG: Count query failed: " . mysqli_error($db));
                echo json_encode(['success' => false, 'message' => 'Count query failed: ' . mysqli_error($db)]);
                exit();
            }
            
            $countRow = mysqli_fetch_assoc($countResult);
            $totalCount = $countRow['total'];
            
            // Calculate total pages
            $totalPages = ceil($totalCount / $perPage);
            error_log("DEBUG: Total count: $totalCount, Total pages: $totalPages");
            
            // MAIN QUERY - Based on status filter
            $query = "";
            $mainParams = [];
            
            if ($statusFilter === 'present') {
                // Show only employees who timed-in to the selected branch today
                $query = "SELECT
                            e.id,
                            e.employee_code,
                            e.first_name,
                            e.middle_name,
                            e.last_name,
                            e.position,
                            'Not Assigned' as original_branch,
                            a.branch_name as logged_branch,
                            'Present' as attendance_status,
                            a.is_auto_absent,
                            1 as has_attendance_today
                          FROM employees e
                          INNER JOIN (
                              SELECT a1.*
                              FROM attendance a1
                              INNER JOIN (
                                  SELECT employee_id, MAX(id) AS max_id
                                  FROM attendance
                                  WHERE attendance_date = CURDATE() AND branch_name = ? AND time_in IS NOT NULL
                                  GROUP BY employee_id
                              ) t ON a1.id = t.max_id
                          ) a ON e.id = a.employee_id
                          WHERE e.status = 'Active'
                          ORDER BY e.last_name, e.first_name
                          LIMIT $perPage OFFSET $offset";
                $mainParams = [$branch];
            } elseif ($statusFilter === 'absent') {
                // Show only ABSENT employees
                $query = "SELECT
                            e.id,
                            e.employee_code,
                            e.first_name,
                            e.middle_name,
                            e.last_name,
                            e.position,
                            'Not Assigned' as original_branch,
                            a.branch_name as logged_branch,
                            a.status as attendance_status,
                            a.is_auto_absent,
                            1 as has_attendance_today
                          FROM employees e
                          INNER JOIN (
                              SELECT a1.*
                              FROM attendance a1
                              INNER JOIN (
                                  SELECT employee_id, MAX(id) AS max_id
                                  FROM attendance
                                  WHERE attendance_date = CURDATE()
                                  GROUP BY employee_id
                              ) t ON a1.id = t.max_id
                          ) a ON e.id = a.employee_id
                          WHERE e.status = 'Active' AND a.status = 'Absent' AND a.branch_name = ?
                          ORDER BY e.last_name, e.first_name
                          LIMIT $perPage OFFSET $offset";
                $mainParams = [$branch];
            } elseif ($statusFilter === 'available') {
                // Show AVAILABLE employees (not yet marked today)
                $query = "SELECT
                            e.id,
                            e.employee_code,
                            e.first_name,
                            e.middle_name,
                            e.last_name,
                            e.position,
                            'Not Assigned' as original_branch,
                            a.branch_name as logged_branch,
                            a.status as attendance_status,
                            a.is_auto_absent,
                            CASE 
                                WHEN a.id IS NOT NULL THEN 1 
                                ELSE 0 
                            END as has_attendance_today
                          FROM employees e
                          LEFT JOIN (
                              SELECT a1.*
                              FROM attendance a1
                              INNER JOIN (
                                  SELECT employee_id, MAX(id) AS max_id
                                  FROM attendance
                                  WHERE attendance_date = CURDATE()
                                  GROUP BY employee_id
                              ) t ON a1.id = t.max_id
                          ) a ON e.id = a.employee_id
                          WHERE e.status = 'Active' AND a.id IS NULL
                          ORDER BY e.last_name, e.first_name
                          LIMIT $perPage OFFSET $offset";
                $mainParams = [];
            } else {
                // Show ALL employees with their attendance status - for pull method, show all
                $query = "SELECT
                            e.id,
                            e.employee_code,
                            e.first_name,
                            e.middle_name,
                            e.last_name,
                            e.position,
                            'Not Assigned' as original_branch,
                            a.branch_name as logged_branch,
                            a.status as attendance_status,
                            a.is_auto_absent,
                            CASE 
                                WHEN a.id IS NOT NULL THEN 1 
                                ELSE 0 
                            END as has_attendance_today
                          FROM employees e
                          LEFT JOIN (
                              SELECT a1.*
                              FROM attendance a1
                              INNER JOIN (
                                  SELECT employee_id, MAX(id) AS max_id
                                  FROM attendance
                                  WHERE attendance_date = CURDATE()
                                  GROUP BY employee_id
                              ) t ON a1.id = t.max_id
                          ) a ON e.id = a.employee_id
                          WHERE e.status = 'Active'
                          ORDER BY e.last_name, e.first_name
                          LIMIT $perPage OFFSET $offset";
                $mainParams = [];
            }
            
            error_log("DEBUG: Main Query: $query");
            $stmt = mysqli_prepare($db, $query);
            if (!empty($mainParams)) {
                mysqli_stmt_bind_param($stmt, str_repeat('s', count($mainParams)), ...$mainParams);
            }
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (!$result) {
                error_log("DEBUG: Main query failed: " . mysqli_error($db));
                echo json_encode(['success' => false, 'message' => 'Query failed: ' . mysqli_error($db)]);
                exit();
            }

            $hasTimeCols = attendanceHasTimeColumns($db);
            $employees = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $shiftId = null;
                $timeIn = null;
                $timeOut = null;
                $totalHours = 0;

                if ($hasTimeCols) {
                    // Latest shift (for displaying current session and clock-out targeting)
                    $shiftSql = "SELECT id, time_in, time_out
                                FROM attendance
                                WHERE employee_id = ? AND attendance_date = CURDATE() AND branch_name = ? AND time_in IS NOT NULL
                                ORDER BY id DESC
                                LIMIT 1";
                    $shiftStmt = mysqli_prepare($db, $shiftSql);
                    if ($shiftStmt) {
                        mysqli_stmt_bind_param($shiftStmt, 'is', $row['id'], $branch);
                        mysqli_stmt_execute($shiftStmt);
                        $shiftResult = mysqli_stmt_get_result($shiftStmt);
                        if ($shiftResult && ($shiftRow = mysqli_fetch_assoc($shiftResult))) {
                            $shiftId = $shiftRow['id'];
                            $timeIn = $shiftRow['time_in'];
                            $timeOut = $shiftRow['time_out'];
                        }
                        mysqli_stmt_close($shiftStmt);
                    }

                    // Total hours for today (sum of all sessions)
                    $totalSql = "SELECT
                                    SUM(
                                        GREATEST(
                                            0,
                                            TIME_TO_SEC(
                                                TIMEDIFF(
                                                    COALESCE(time_out, NOW()),
                                                    time_in
                                                )
                                            )
                                        )
                                    ) AS total_seconds
                                  FROM attendance
                                  WHERE employee_id = ?
                                    AND attendance_date = CURDATE()
                                    AND branch_name = ?
                                    AND time_in IS NOT NULL";
                    $totalStmt = mysqli_prepare($db, $totalSql);
                    if ($totalStmt) {
                        mysqli_stmt_bind_param($totalStmt, 'is', $row['id'], $branch);
                        mysqli_stmt_execute($totalStmt);
                        $totalResult = mysqli_stmt_get_result($totalStmt);
                        if ($totalResult && ($totalRow = mysqli_fetch_assoc($totalResult))) {
                            $totalSeconds = intval($totalRow['total_seconds'] ?? 0);
                            $totalHours = round($totalSeconds / 3600, 2);
                        }
                        mysqli_stmt_close($totalStmt);
                    }
                }

                $employees[] = [
                    'id' => $row['id'],
                    'employee_code' => $row['employee_code'],
                    'name' => trim($row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'] . ' ' : '') . $row['last_name']),
                    'position' => $row['position'],
                    'original_branch' => $row['original_branch'],
                    'logged_branch' => $row['logged_branch'] ?? $branch, // Use selected branch if null
                    'attendance_status' => $row['attendance_status'] ?? null,
                    'is_auto_absent' => (bool)($row['is_auto_absent'] ?? false),
                    'has_attendance_today' => (bool)($row['has_attendance_today'] ?? false),
                    'shift_id' => $shiftId,
                    'time_in' => $timeIn,
                    'time_out' => $timeOut,
                    'total_hours' => $totalHours
                ];
            }

            error_log("DEBUG: Found " . count($employees) . " employees");
            
            echo json_encode([
                'success' => true, 
                'employees' => $employees,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $totalCount,
                    'total_pages' => $totalPages
                ],
                'cutoff_time' => $cutoffTime,
                'current_time' => $currentTime,
                'is_before_cutoff' => $isBeforeCutoff
            ]);
            exit();
            
        } catch (Exception $e) {
            error_log("DEBUG: Exception: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
            exit();
        }
    }

    if ($_POST['action'] === 'mark_present') {
        $employeeId = $_POST['employee_id'] ?? 0;
        $branch = $_POST['branch'] ?? '';
        $status = 'Present'; // Can only mark as present (absent is automatic)

        if (!$employeeId || empty($branch)) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit();
        }

        // Check if employee already has attendance today (Philippine date)
        $checkQuery = "SELECT id, status, is_auto_absent FROM attendance WHERE employee_id = ? AND attendance_date = CURDATE()";
        $checkStmt = mysqli_prepare($db, $checkQuery);
        mysqli_stmt_bind_param($checkStmt, 'i', $employeeId);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);

        if (mysqli_num_rows($checkResult) > 0) {
            $attendance = mysqli_fetch_assoc($checkResult);

            // Update to Present (even if was auto-absent)
            $updateQuery = "UPDATE attendance SET status = ?, branch_name = ?, is_auto_absent = 0, updated_at = NOW() WHERE id = ?";
            $updateStmt = mysqli_prepare($db, $updateQuery);
            mysqli_stmt_bind_param($updateStmt, 'ssi', $status, $branch, $attendance['id']);

            if (mysqli_stmt_execute($updateStmt)) {
                echo json_encode([
                    'success' => true,
                    'message' => "Attendance marked as Present" . ($attendance['is_auto_absent'] ? " (overriding auto-absent)" : ""),
                    'is_update' => true
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update attendance: ' . mysqli_error($db)]);
            }
            exit();
        }

        // Insert new attendance record as Present
        $insertQuery = "INSERT INTO attendance (employee_id, status, attendance_date, branch_name, is_auto_absent, created_at) VALUES (?, ?, CURDATE(), ?, 0, NOW())";
        $insertStmt = mysqli_prepare($db, $insertQuery);
        mysqli_stmt_bind_param($insertStmt, 'iss', $employeeId, $status, $branch);

        if (mysqli_stmt_execute($insertStmt)) {
            echo json_encode([
                'success' => true,
                'message' => "Employee marked as Present successfully",
                'is_update' => false
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to mark attendance: ' . mysqli_error($db)]);
        }
        exit();
    }

    if ($_POST['action'] === 'get_shift_logs') {
        $employeeId = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
        $date = isset($_POST['date']) && $_POST['date'] !== '' ? $_POST['date'] : date('Y-m-d');
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        $limit = max(1, min(50, $limit));

        if ($employeeId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid employee']);
            exit();
        }

        if (!attendanceHasTimeColumns($db)) {
            echo json_encode(['success' => false, 'message' => 'Time logs are not available on this database schema']);
            exit();
        }

        $sql = "SELECT id, branch_name, attendance_date, time_in, time_out
                FROM attendance
                WHERE employee_id = ? AND attendance_date = ? AND time_in IS NOT NULL
                ORDER BY id DESC
                LIMIT $limit";
        $stmt = mysqli_prepare($db, $sql);
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Database error']);
            exit();
        }
        mysqli_stmt_bind_param($stmt, 'is', $employeeId, $date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $logs = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $logs[] = [
                    'id' => $row['id'],
                    'branch_name' => $row['branch_name'] ?? null,
                    'attendance_date' => $row['attendance_date'] ?? null,
                    'time_in' => $row['time_in'] ?? null,
                    'time_out' => $row['time_out'] ?? null
                ];
            }
        }
        mysqli_stmt_close($stmt);

        echo json_encode([
            'success' => true,
            'logs' => $logs
        ]);
        exit();
    }
}
?>