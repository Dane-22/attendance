<?php
// employee/function/clock_functions.php
// Core clock-in/clock-out logic for direct PHP calls (no HTTP/cURL needed)

// Note: Helper functions (attendanceHasTimeColumns, etc.) are already defined in attendance.php
// which is loaded before this file in select_employee.php

/**
 * Perform clock-in for an employee
 * Returns array with success, message, time_in, etc.
 */
function performClockIn($db, $employeeId, $employeeCode, $branchName = null) {
    if (!attendanceHasTimeColumns($db)) {
        return ['success' => false, 'message' => 'Time In/Out is not available: attendance table has no time_in/time_out columns'];
    }

    if ($branchName === null || $branchName === '') {
        $bSql = "SELECT branch_name FROM employees WHERE id = ? LIMIT 1";
        $bStmt = mysqli_prepare($db, $bSql);
        if ($bStmt) {
            mysqli_stmt_bind_param($bStmt, 'i', $employeeId);
            mysqli_stmt_execute($bStmt);
            $bResult = mysqli_stmt_get_result($bStmt);
            if ($bResult && ($bRow = mysqli_fetch_assoc($bResult))) {
                $branchName = $bRow['branch_name'] ?? '';
            }
            mysqli_stmt_close($bStmt);
        }
        if ($branchName === null || $branchName === '') {
            $branchName = 'System';
        }
    }

    // Check if already clocked in
    $checkSql = "SELECT id FROM attendance WHERE employee_id = ? AND attendance_date = CURDATE() AND time_in IS NOT NULL AND time_out IS NULL";
    $stmt = mysqli_prepare($db, $checkSql);
    if (!$stmt) {
        return ['success' => false, 'message' => 'Database error (prepare check)'];
    }
    mysqli_stmt_bind_param($stmt, "i", $employeeId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => 'Already clocked in'];
    }
    mysqli_stmt_close($stmt);

    // Validate selected branch and auto-transfer assignment
    $targetBranchId = null;
    $targetBranchName = null;
    $branchResolveStmt = mysqli_prepare($db, "SELECT id, branch_name FROM branches WHERE branch_name = ? AND is_active = 1 LIMIT 1");
    if ($branchResolveStmt) {
        mysqli_stmt_bind_param($branchResolveStmt, 's', $branchName);
        mysqli_stmt_execute($branchResolveStmt);
        $branchResolveRes = mysqli_stmt_get_result($branchResolveStmt);
        $branchResolveRow = $branchResolveRes ? mysqli_fetch_assoc($branchResolveRes) : null;
        mysqli_stmt_close($branchResolveStmt);
        if ($branchResolveRow) {
            $targetBranchId = (int)($branchResolveRow['id'] ?? 0);
            $targetBranchName = $branchResolveRow['branch_name'] ?? null;
        }
    }

    if (!$targetBranchId || !$targetBranchName) {
        return ['success' => false, 'message' => 'Invalid branch selected'];
    }

    $currentAssignedBranchId = null;
    $currentAssignedBranchName = null;
    $assignedStmt = mysqli_prepare($db, "SELECT e.branch_id, b.branch_name FROM employees e LEFT JOIN branches b ON b.id = e.branch_id WHERE e.id = ? LIMIT 1");
    if ($assignedStmt) {
        mysqli_stmt_bind_param($assignedStmt, 'i', $employeeId);
        mysqli_stmt_execute($assignedStmt);
        $assignedRes = mysqli_stmt_get_result($assignedStmt);
        $assignedRow = $assignedRes ? mysqli_fetch_assoc($assignedRes) : null;
        mysqli_stmt_close($assignedStmt);
        if ($assignedRow) {
            $currentAssignedBranchId = isset($assignedRow['branch_id']) ? (int)$assignedRow['branch_id'] : null;
            $currentAssignedBranchName = $assignedRow['branch_name'] ?? null;
        }
    }

    $didAutoTransfer = false;
    if ($currentAssignedBranchId === null || (int)$currentAssignedBranchId !== (int)$targetBranchId) {
        $hasUpdatedAt = employeesHasColumn($db, 'updated_at');
        $updateEmpSql = $hasUpdatedAt
            ? "UPDATE employees SET branch_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? LIMIT 1"
            : "UPDATE employees SET branch_id = ? WHERE id = ? LIMIT 1";
        $updateEmpStmt = mysqli_prepare($db, $updateEmpSql);
        if ($updateEmpStmt) {
            mysqli_stmt_bind_param($updateEmpStmt, 'ii', $targetBranchId, $employeeId);
            if (mysqli_stmt_execute($updateEmpStmt)) {
                $didAutoTransfer = (mysqli_stmt_affected_rows($updateEmpStmt) > 0);
            }
            mysqli_stmt_close($updateEmpStmt);
        }
    }

    // If there's an existing attendance row for today with no time_in yet, update it instead of inserting
    $existingSql = "SELECT id FROM attendance WHERE employee_id = ? AND attendance_date = CURDATE() AND time_in IS NULL ORDER BY id DESC LIMIT 1";
    $existingStmt = mysqli_prepare($db, $existingSql);
    if ($existingStmt) {
        mysqli_stmt_bind_param($existingStmt, "i", $employeeId);
        mysqli_stmt_execute($existingStmt);
        $existingResult = mysqli_stmt_get_result($existingStmt);
        if ($existingResult && ($existingRow = mysqli_fetch_assoc($existingResult))) {
            $existingId = intval($existingRow['id']);

            $hasRunningCol = attendanceHasIsTimeRunningColumn($db);
            $hasOvertimeRunningCol = attendanceHasIsOvertimeRunningColumn($db);
            $hasTotalOtHrsCol = attendanceHasTotalOtHrsColumn($db);
            $hasStatusCol = attendanceHasStatusColumn($db);

            if ($branchName !== null && $branchName !== '') {
                if ($hasRunningCol) {
                    if ($hasOvertimeRunningCol) {
                        $updateSql = $hasStatusCol
                            ? "UPDATE attendance SET time_in = NOW(), branch_name = ?, status = 'Present', is_time_running = 1, is_overtime_running = 0 WHERE id = ?"
                            : "UPDATE attendance SET time_in = NOW(), branch_name = ?, is_time_running = 1, is_overtime_running = 0 WHERE id = ?";
                    } else {
                        $updateSql = $hasStatusCol
                            ? "UPDATE attendance SET time_in = NOW(), branch_name = ?, status = 'Present', is_time_running = 1 WHERE id = ?"
                            : "UPDATE attendance SET time_in = NOW(), branch_name = ?, is_time_running = 1 WHERE id = ?";
                    }
                } else {
                    if ($hasOvertimeRunningCol) {
                        $updateSql = $hasStatusCol
                            ? "UPDATE attendance SET time_in = NOW(), branch_name = ?, status = 'Present', is_overtime_running = 0 WHERE id = ?"
                            : "UPDATE attendance SET time_in = NOW(), branch_name = ?, is_overtime_running = 0 WHERE id = ?";
                    } else {
                        $updateSql = $hasStatusCol
                            ? "UPDATE attendance SET time_in = NOW(), branch_name = ?, status = 'Present' WHERE id = ?"
                            : "UPDATE attendance SET time_in = NOW(), branch_name = ? WHERE id = ?";
                    }
                }
                $updateStmt = mysqli_prepare($db, $updateSql);
                if (!$updateStmt) {
                    mysqli_stmt_close($existingStmt);
                    return ['success' => false, 'message' => 'Database error (prepare update)'];
                }
                mysqli_stmt_bind_param($updateStmt, "si", $branchName, $existingId);
            } else {
                if ($hasRunningCol) {
                    if ($hasOvertimeRunningCol) {
                        $updateSql = $hasStatusCol
                            ? "UPDATE attendance SET time_in = NOW(), status = 'Present', is_time_running = 1, is_overtime_running = 0 WHERE id = ?"
                            : "UPDATE attendance SET time_in = NOW(), is_time_running = 1, is_overtime_running = 0 WHERE id = ?";
                    } else {
                        $updateSql = $hasStatusCol
                            ? "UPDATE attendance SET time_in = NOW(), status = 'Present', is_time_running = 1 WHERE id = ?"
                            : "UPDATE attendance SET time_in = NOW(), is_time_running = 1 WHERE id = ?";
                    }
                } else {
                    if ($hasOvertimeRunningCol) {
                        $updateSql = $hasStatusCol
                            ? "UPDATE attendance SET time_in = NOW(), status = 'Present', is_overtime_running = 0 WHERE id = ?"
                            : "UPDATE attendance SET time_in = NOW(), is_overtime_running = 0 WHERE id = ?";
                    } else {
                        $updateSql = $hasStatusCol
                            ? "UPDATE attendance SET time_in = NOW(), status = 'Present' WHERE id = ?"
                            : "UPDATE attendance SET time_in = NOW() WHERE id = ?";
                    }
                }
                $updateStmt = mysqli_prepare($db, $updateSql);
                if (!$updateStmt) {
                    mysqli_stmt_close($existingStmt);
                    return ['success' => false, 'message' => 'Database error (prepare update)'];
                }
                mysqli_stmt_bind_param($updateStmt, "i", $existingId);
            }

            if (mysqli_stmt_execute($updateStmt)) {
                $timeIn = null;
                $timeStmt = mysqli_prepare($db, "SELECT time_in FROM attendance WHERE id = ? AND employee_id = ? LIMIT 1");
                if ($timeStmt) {
                    mysqli_stmt_bind_param($timeStmt, 'ii', $existingId, $employeeId);
                    mysqli_stmt_execute($timeStmt);
                    $timeRes = mysqli_stmt_get_result($timeStmt);
                    if ($timeRes && ($timeRow = mysqli_fetch_assoc($timeRes))) {
                        $timeIn = $timeRow['time_in'] ?? null;
                    }
                    mysqli_stmt_close($timeStmt);
                }

                mysqli_stmt_close($updateStmt);
                mysqli_stmt_close($existingStmt);
                return [
                    'success' => true,
                    'message' => 'Clocked in successfully',
                    'time_in' => $timeIn,
                    'shift_id' => $existingId,
                    'auto_transferred' => $didAutoTransfer,
                    'from_branch' => $currentAssignedBranchName,
                    'to_branch' => $targetBranchName
                ];
            }

            mysqli_stmt_close($updateStmt);
            mysqli_stmt_close($existingStmt);
            return ['success' => false, 'message' => 'Database error: ' . mysqli_error($db)];
        }
        mysqli_stmt_close($existingStmt);
    }

    // Clock in - insert new record
    $hasRunningCol = attendanceHasIsTimeRunningColumn($db);
    $hasOvertimeRunningCol = attendanceHasIsOvertimeRunningColumn($db);
    $hasTotalOtHrsCol = attendanceHasTotalOtHrsColumn($db);
    $hasStatusCol = attendanceHasStatusColumn($db);

    if ($branchName !== null && $branchName !== '') {
        if ($hasRunningCol) {
            if ($hasOvertimeRunningCol) {
                if ($hasStatusCol) {
                    $sql = $hasTotalOtHrsCol
                        ? "INSERT INTO attendance (employee_id, branch_name, attendance_date, time_in, status, is_time_running, is_overtime_running, total_ot_hrs) VALUES (?, ?, CURDATE(), NOW(), 'Present', 1, 0, 0)"
                        : "INSERT INTO attendance (employee_id, branch_name, attendance_date, time_in, status, is_time_running, is_overtime_running) VALUES (?, ?, CURDATE(), NOW(), 'Present', 1, 0)";
                } else {
                    $sql = $hasTotalOtHrsCol
                        ? "INSERT INTO attendance (employee_id, branch_name, attendance_date, time_in, is_time_running, is_overtime_running, total_ot_hrs) VALUES (?, ?, CURDATE(), NOW(), 1, 0, 0)"
                        : "INSERT INTO attendance (employee_id, branch_name, attendance_date, time_in, is_time_running, is_overtime_running) VALUES (?, ?, CURDATE(), NOW(), 1, 0)";
                }
            } else {
                if ($hasStatusCol) {
                    $sql = $hasTotalOtHrsCol
                        ? "INSERT INTO attendance (employee_id, branch_name, attendance_date, time_in, status, is_time_running, total_ot_hrs) VALUES (?, ?, CURDATE(), NOW(), 'Present', 1, 0)"
                        : "INSERT INTO attendance (employee_id, branch_name, attendance_date, time_in, status, is_time_running) VALUES (?, ?, CURDATE(), NOW(), 'Present', 1)";
                } else {
                    $sql = $hasTotalOtHrsCol
                        ? "INSERT INTO attendance (employee_id, branch_name, attendance_date, time_in, is_time_running, total_ot_hrs) VALUES (?, ?, CURDATE(), NOW(), 1, 0)"
                        : "INSERT INTO attendance (employee_id, branch_name, attendance_date, time_in, is_time_running) VALUES (?, ?, CURDATE(), NOW(), 1)";
                }
            }
        } else {
            if ($hasOvertimeRunningCol) {
                if ($hasStatusCol) {
                    $sql = $hasTotalOtHrsCol
                        ? "INSERT INTO attendance (employee_id, branch_name, attendance_date, time_in, status, is_overtime_running, total_ot_hrs) VALUES (?, ?, CURDATE(), NOW(), 'Present', 0, 0)"
                        : "INSERT INTO attendance (employee_id, branch_name, attendance_date, time_in, status, is_overtime_running) VALUES (?, ?, CURDATE(), NOW(), 'Present', 0)";
                } else {
                    $sql = $hasTotalOtHrsCol
                        ? "INSERT INTO attendance (employee_id, branch_name, attendance_date, time_in, is_overtime_running, total_ot_hrs) VALUES (?, ?, CURDATE(), NOW(), 0, 0)"
                        : "INSERT INTO attendance (employee_id, branch_name, attendance_date, time_in, is_overtime_running) VALUES (?, ?, CURDATE(), NOW(), 0)";
                }
            } else {
                if ($hasStatusCol) {
                    $sql = $hasTotalOtHrsCol
                        ? "INSERT INTO attendance (employee_id, branch_name, attendance_date, time_in, status, total_ot_hrs) VALUES (?, ?, CURDATE(), NOW(), 'Present', 0)"
                        : "INSERT INTO attendance (employee_id, branch_name, attendance_date, time_in, status) VALUES (?, ?, CURDATE(), NOW(), 'Present')";
                } else {
                    $sql = $hasTotalOtHrsCol
                        ? "INSERT INTO attendance (employee_id, branch_name, attendance_date, time_in, total_ot_hrs) VALUES (?, ?, CURDATE(), NOW(), 0)"
                        : "INSERT INTO attendance (employee_id, branch_name, attendance_date, time_in) VALUES (?, ?, CURDATE(), NOW())";
                }
            }
        }
        $stmt = mysqli_prepare($db, $sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Database error (prepare insert)'];
        }
        mysqli_stmt_bind_param($stmt, "is", $employeeId, $branchName);
    } else {
        if ($hasRunningCol) {
            if ($hasOvertimeRunningCol) {
                if ($hasStatusCol) {
                    $sql = $hasTotalOtHrsCol
                        ? "INSERT INTO attendance (employee_id, time_in, status, is_time_running, is_overtime_running, total_ot_hrs) VALUES (?, NOW(), 'Present', 1, 0, 0)"
                        : "INSERT INTO attendance (employee_id, time_in, status, is_time_running, is_overtime_running) VALUES (?, NOW(), 'Present', 1, 0)";
                } else {
                    $sql = $hasTotalOtHrsCol
                        ? "INSERT INTO attendance (employee_id, time_in, is_time_running, is_overtime_running, total_ot_hrs) VALUES (?, NOW(), 1, 0, 0)"
                        : "INSERT INTO attendance (employee_id, time_in, is_time_running, is_overtime_running) VALUES (?, NOW(), 1, 0)";
                }
            } else {
                if ($hasStatusCol) {
                    $sql = $hasTotalOtHrsCol
                        ? "INSERT INTO attendance (employee_id, time_in, status, is_time_running, total_ot_hrs) VALUES (?, NOW(), 'Present', 1, 0)"
                        : "INSERT INTO attendance (employee_id, time_in, status, is_time_running) VALUES (?, NOW(), 'Present', 1)";
                } else {
                    $sql = $hasTotalOtHrsCol
                        ? "INSERT INTO attendance (employee_id, time_in, is_time_running, total_ot_hrs) VALUES (?, NOW(), 1, 0)"
                        : "INSERT INTO attendance (employee_id, time_in, is_time_running) VALUES (?, NOW(), 1)";
                }
            }
        } else {
            if ($hasOvertimeRunningCol) {
                if ($hasStatusCol) {
                    $sql = $hasTotalOtHrsCol
                        ? "INSERT INTO attendance (employee_id, time_in, status, is_overtime_running, total_ot_hrs) VALUES (?, NOW(), 'Present', 0, 0)"
                        : "INSERT INTO attendance (employee_id, time_in, status, is_overtime_running) VALUES (?, NOW(), 'Present', 0)";
                } else {
                    $sql = $hasTotalOtHrsCol
                        ? "INSERT INTO attendance (employee_id, time_in, is_overtime_running, total_ot_hrs) VALUES (?, NOW(), 0, 0)"
                        : "INSERT INTO attendance (employee_id, time_in, is_overtime_running) VALUES (?, NOW(), 0)";
                }
            } else {
                if ($hasStatusCol) {
                    $sql = $hasTotalOtHrsCol
                        ? "INSERT INTO attendance (employee_id, time_in, status, total_ot_hrs) VALUES (?, NOW(), 'Present', 0)"
                        : "INSERT INTO attendance (employee_id, time_in, status) VALUES (?, NOW(), 'Present')";
                } else {
                    $sql = $hasTotalOtHrsCol
                        ? "INSERT INTO attendance (employee_id, time_in, total_ot_hrs) VALUES (?, NOW(), 0)"
                        : "INSERT INTO attendance (employee_id, time_in) VALUES (?, NOW())";
                }
            }
        }
        $stmt = mysqli_prepare($db, $sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Database error (prepare insert)'];
        }
        mysqli_stmt_bind_param($stmt, "i", $employeeId);
    }

    if (mysqli_stmt_execute($stmt)) {
        $shiftId = mysqli_insert_id($db);
        $timeIn = null;
        $timeStmt = mysqli_prepare($db, "SELECT time_in FROM attendance WHERE id = ? AND employee_id = ? LIMIT 1");
        if ($timeStmt) {
            mysqli_stmt_bind_param($timeStmt, 'ii', $shiftId, $employeeId);
            mysqli_stmt_execute($timeStmt);
            $timeRes = mysqli_stmt_get_result($timeStmt);
            if ($timeRes && ($timeRow = mysqli_fetch_assoc($timeRes))) {
                $timeIn = $timeRow['time_in'] ?? null;
            }
            mysqli_stmt_close($timeStmt);
        }

        mysqli_stmt_close($stmt);
        return [
            'success' => true,
            'message' => 'Clocked in successfully',
            'time_in' => $timeIn,
            'shift_id' => $shiftId
        ];
    } else {
        $error = mysqli_error($db);
        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => 'Database error: ' . $error];
    }
}

/**
 * Perform clock-out for an employee
 * Returns array with success, message, time_out, etc.
 */
function performClockOut($db, $employeeId, $employeeCode, $branchName = null) {
    // Find the open attendance record for today
    $sql = "SELECT id, time_in, branch_name FROM attendance 
            WHERE employee_id = ? 
            AND attendance_date = CURDATE() 
            AND time_in IS NOT NULL 
            AND time_out IS NULL 
            ORDER BY time_in DESC LIMIT 1";
    
    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) {
        return ['success' => false, 'message' => 'Database error'];
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $employeeId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$row) {
        return ['success' => false, 'message' => 'No active time-in found for today'];
    }
    
    $attendanceId = $row['id'];
    $timeIn = $row['time_in'];
    $timeInBranch = $row['branch_name'];
    
    // If branch name provided, check if different from time-in branch
    if ($branchName && $branchName !== $timeInBranch) {
        // Allow clock out from different branch but log it
        error_log("Clock out from different branch: Employee $employeeId time-in at $timeInBranch, clock-out from $branchName");
    }
    
    // Calculate hours worked
    $hoursWorked = 0;
    if ($timeIn && $timeIn !== '0000-00-00 00:00:00') {
        try {
            $timeInObj = new DateTime($timeIn);
            $timeOutObj = new DateTime();
            $interval = $timeInObj->diff($timeOutObj);
            $hoursWorked = $interval->h + ($interval->i / 60) + ($interval->days * 24);
        } catch (Exception $e) {
            // If DateTime fails, default to 0
            $hoursWorked = 0;
        }
    }
    
    // Update attendance record
    $hasRunningCol = attendanceHasIsTimeRunningColumn($db);
    $hasOvertimeRunningCol = attendanceHasIsOvertimeRunningColumn($db);
    
    if ($hasRunningCol) {
        if ($hasOvertimeRunningCol) {
            $updateSql = "UPDATE attendance 
                          SET time_out = NOW(), 
                              is_time_running = 0, 
                              is_overtime_running = 0,
                              total_hours = ?
                          WHERE id = ? AND employee_id = ?";
        } else {
            $updateSql = "UPDATE attendance 
                          SET time_out = NOW(), 
                              is_time_running = 0,
                              total_hours = ?
                          WHERE id = ? AND employee_id = ?";
        }
    } else {
        $updateSql = "UPDATE attendance 
                      SET time_out = NOW(),
                          total_hours = ?
                      WHERE id = ? AND employee_id = ?";
    }
    
    $updateStmt = mysqli_prepare($db, $updateSql);
    if (!$updateStmt) {
        return ['success' => false, 'message' => 'Database error (prepare update)'];
    }
    
    mysqli_stmt_bind_param($updateStmt, 'dii', $hoursWorked, $attendanceId, $employeeId);
    
    if (mysqli_stmt_execute($updateStmt)) {
        $timeOut = null;
        $timeStmt = mysqli_prepare($db, "SELECT time_out FROM attendance WHERE id = ? LIMIT 1");
        if ($timeStmt) {
            mysqli_stmt_bind_param($timeStmt, 'i', $attendanceId);
            mysqli_stmt_execute($timeStmt);
            $timeRes = mysqli_stmt_get_result($timeStmt);
            if ($timeRes && ($timeRow = mysqli_fetch_assoc($timeRes))) {
                $timeOut = $timeRow['time_out'] ?? null;
            }
            mysqli_stmt_close($timeStmt);
        }
        
        mysqli_stmt_close($updateStmt);
        return [
            'success' => true,
            'message' => 'Clocked out successfully',
            'time_out' => $timeOut,
            'hours_worked' => round($hoursWorked, 2)
        ];
    } else {
        $error = mysqli_error($db);
        mysqli_stmt_close($updateStmt);
        return ['success' => false, 'message' => 'Failed to record time-out: ' . $error];
    }
}
