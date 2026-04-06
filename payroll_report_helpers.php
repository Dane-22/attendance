<?php

function payroll_parse_month($monthParam) {
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

function payroll_last_day_of_month($year, $month) {
    return (int)date('t', strtotime(sprintf('%04d-%02d-01', $year, $month)));
}

function payroll_period_context($year, $month, $viewType, $weekNumber) {
    $lastDay = payroll_last_day_of_month($year, $month);
    $startDay = 1;
    $endDay = $lastDay;

    if ($viewType === 'weekly') {
        $startDay = (($weekNumber - 1) * 7) + 1;
        $endDay = min($startDay + 6, $lastDay);
    }

    return [
        'start_day' => $startDay,
        'end_day' => $endDay,
        'start_date' => sprintf('%04d-%02d-%02d', $year, $month, $startDay),
        'end_date' => sprintf('%04d-%02d-%02d', $year, $month, $endDay),
    ];
}

function payroll_weekly_deductions($weekNumber) {
    switch ((int)$weekNumber) {
        case 1:
            return ['sss' => 250.0, 'philhealth' => 100.0, 'pagibig' => 50.0];
        case 2:
            return ['sss' => 100.0, 'philhealth' => 100.0, 'pagibig' => 50.0];
        case 3:
            return ['sss' => 100.0, 'philhealth' => 50.0, 'pagibig' => 100.0];
        case 4:
        case 5:
        default:
            return ['sss' => 0.0, 'philhealth' => 0.0, 'pagibig' => 0.0];
    }
}

function payroll_monthly_deductions() {
    return ['sss' => 450.0, 'philhealth' => 250.0, 'pagibig' => 200.0];
}

function payroll_to_float($value, $fallback = 0.0) {
    if ($value === null || $value === '') {
        return (float)$fallback;
    }
    return is_numeric($value) ? (float)$value : (float)$fallback;
}

function payroll_empty_report_summary() {
    return [
        'total_employees' => 0,
        'total_days_worked' => 0.0,
        'total_gross_pay' => 0.0,
        'total_allowances' => 0.0,
        'total_ca_deductions' => 0.0,
        'total_deductions' => 0.0,
        'total_take_home_pay' => 0.0,
    ];
}

function payroll_fetch_worker_employees($db, $branchId, $employeeId) {
    $where = ["LOWER(COALESCE(e.position, '')) = 'worker'"];
    $types = '';
    $params = [];

    if ($branchId !== null && $branchId > 0) {
        $where[] = 'e.branch_id = ?';
        $types .= 'i';
        $params[] = $branchId;
    }

    if ($employeeId > 0) {
        $where[] = 'e.id = ?';
        $types .= 'i';
        $params[] = $employeeId;
    }

    $sql = "
        SELECT
            e.id,
            CONCAT_WS(' ', e.first_name, e.last_name) AS employee_name,
            e.daily_rate,
            e.performance_allowance,
            e.branch_id,
            COALESCE(b.branch_name, '') AS branch_name
        FROM employees e
        LEFT JOIN branches b ON b.id = e.branch_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY employee_name ASC, e.id ASC
    ";

    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare worker employee query: ' . mysqli_error($db));
    }

    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $employees = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $employees[(int)$row['id']] = [
            'employee_id' => (int)$row['id'],
            'employee_name' => $row['employee_name'] ?: ('Employee #' . (int)$row['id']),
            'daily_rate' => payroll_to_float($row['daily_rate']),
            'performance_allowance' => payroll_to_float($row['performance_allowance']),
            'branch_id' => isset($row['branch_id']) ? ($row['branch_id'] === null ? null : (int)$row['branch_id']) : null,
            'branch_name' => $row['branch_name'],
        ];
    }

    mysqli_stmt_close($stmt);

    return $employees;
}

function payroll_fetch_daily_aggregates($db, $year, $month, $viewType, $weekNumber, $branchId, $employeeId) {
    $where = [
        'd.report_year = ?',
        'd.report_month = ?',
        "LOWER(COALESCE(e.position, '')) = 'worker'",
    ];
    $types = 'ii';
    $params = [$year, $month];

    if ($viewType === 'weekly') {
        $where[] = 'd.week_number = ?';
        $types .= 'i';
        $params[] = $weekNumber;
    }

    if ($branchId !== null && $branchId > 0) {
        $where[] = 'COALESCE(d.branch_id, e.branch_id) = ?';
        $types .= 'i';
        $params[] = $branchId;
    }

    if ($employeeId > 0) {
        $where[] = 'd.employee_id = ?';
        $types .= 'i';
        $params[] = $employeeId;
    }

    $sql = "
        SELECT
            d.employee_id,
            COALESCE(d.branch_id, e.branch_id) AS branch_id,
            COALESCE(b.branch_name, eb.branch_name, '') AS branch_name,
            COALESCE(MAX(NULLIF(d.daily_rate, 0)), MAX(e.daily_rate), 0) AS daily_rate,
            SUM(COALESCE(d.days_worked, 0)) AS days_worked,
            SUM(COALESCE(d.total_hours, 0)) AS total_hours,
            SUM(COALESCE(d.basic_pay, 0)) AS basic_pay,
            SUM(COALESCE(d.ot_hours, 0)) AS ot_hours,
            SUM(COALESCE(d.ot_amount, 0)) AS ot_amount,
            SUM(COALESCE(d.gross_pay, COALESCE(d.basic_pay, 0) + COALESCE(d.ot_amount, 0))) AS gross_pay,
            SUM(COALESCE(d.ca_deduction, 0)) AS ca_deduction,
            SUM(COALESCE(d.sss_loan, 0)) AS sss_loan
        FROM daily_payroll_reports d
        INNER JOIN employees e ON e.id = d.employee_id
        LEFT JOIN branches b ON b.id = d.branch_id
        LEFT JOIN branches eb ON eb.id = e.branch_id
        WHERE " . implode(' AND ', $where) . "
        GROUP BY d.employee_id, COALESCE(d.branch_id, e.branch_id)
        ORDER BY d.employee_id ASC
    ";

    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare daily payroll aggregate query: ' . mysqli_error($db));
    }

    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[(int)$row['employee_id']] = [
            'employee_id' => (int)$row['employee_id'],
            'branch_id' => isset($row['branch_id']) ? ($row['branch_id'] === null ? null : (int)$row['branch_id']) : null,
            'branch_name' => $row['branch_name'],
            'daily_rate' => payroll_to_float($row['daily_rate']),
            'days_worked' => payroll_to_float($row['days_worked']),
            'total_hours' => payroll_to_float($row['total_hours']),
            'basic_pay' => payroll_to_float($row['basic_pay']),
            'ot_hours' => payroll_to_float($row['ot_hours']),
            'ot_amount' => payroll_to_float($row['ot_amount']),
            'gross_pay' => payroll_to_float($row['gross_pay']),
            'ca_deduction' => payroll_to_float($row['ca_deduction']),
            'sss_loan' => payroll_to_float($row['sss_loan']),
        ];
    }

    mysqli_stmt_close($stmt);

    return $rows;
}

function payroll_fetch_attendance_aggregates($db, $startDate, $endDate, $branchId, $employeeId) {
    $where = [
        'a.attendance_date BETWEEN ? AND ?',
        "LOWER(COALESCE(e.position, '')) = 'worker'",
    ];
    $types = 'ss';
    $params = [$startDate, $endDate];

    if ($branchId !== null && $branchId > 0) {
        $where[] = 'COALESCE(ab.id, eb.id) = ?';
        $types .= 'i';
        $params[] = $branchId;
    }

    if ($employeeId > 0) {
        $where[] = 'a.employee_id = ?';
        $types .= 'i';
        $params[] = $employeeId;
    }

    $sql = "
        SELECT
            a.employee_id,
            COALESCE(ab.id, eb.id) AS branch_id,
            COALESCE(ab.branch_name, eb.branch_name, a.branch_name, '') AS branch_name,
            COUNT(DISTINCT CASE
                WHEN a.time_in IS NOT NULL OR LOWER(COALESCE(a.status, '')) IN ('present', 'late')
                THEN a.attendance_date
                ELSE NULL
            END) AS days_worked,
            SUM(CASE
                WHEN a.time_in IS NOT NULL AND a.time_out IS NOT NULL
                THEN GREATEST(TIMESTAMPDIFF(MINUTE, a.time_in, a.time_out), 0) / 60
                ELSE 0
            END) AS total_hours,
            SUM(CASE
                WHEN TRIM(COALESCE(a.total_ot_hrs, '')) = ''
                THEN 0
                ELSE CAST(a.total_ot_hrs AS DECIMAL(10,2))
            END) AS ot_hours,
            MAX(COALESCE(e.daily_rate, 0)) AS daily_rate
        FROM attendance a
        INNER JOIN employees e ON e.id = a.employee_id
        LEFT JOIN branches ab ON ab.branch_name = a.branch_name
        LEFT JOIN branches eb ON eb.id = e.branch_id
        WHERE " . implode(' AND ', $where) . "
        GROUP BY a.employee_id, COALESCE(ab.id, eb.id)
        ORDER BY a.employee_id ASC
    ";

    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare attendance aggregate query: ' . mysqli_error($db));
    }

    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $dailyRate = payroll_to_float($row['daily_rate']);
        $daysWorked = payroll_to_float($row['days_worked']);
        $otHours = payroll_to_float($row['ot_hours']);
        $otRate = $dailyRate > 0 ? $dailyRate / 8 : 0.0;
        $otAmount = $otHours * $otRate;
        $basicPay = $daysWorked * $dailyRate;
        $grossPay = $basicPay + $otAmount;

        $rows[(int)$row['employee_id']] = [
            'employee_id' => (int)$row['employee_id'],
            'branch_id' => isset($row['branch_id']) ? ($row['branch_id'] === null ? null : (int)$row['branch_id']) : null,
            'branch_name' => $row['branch_name'],
            'daily_rate' => $dailyRate,
            'days_worked' => $daysWorked,
            'total_hours' => payroll_to_float($row['total_hours']),
            'basic_pay' => $basicPay,
            'ot_hours' => $otHours,
            'ot_amount' => $otAmount,
            'gross_pay' => $grossPay,
            'ca_deduction' => 0.0,
            'sss_loan' => 0.0,
        ];
    }

    mysqli_stmt_close($stmt);

    return $rows;
}

function payroll_fetch_weekly_overlays($db, $year, $month, $viewType, $weekNumber, $branchId, $employeeId) {
    $where = [
        'w.report_year = ?',
        'w.report_month = ?',
        'w.view_type = ?',
        "LOWER(COALESCE(e.position, '')) = 'worker'",
    ];
    $types = 'iis';
    $params = [$year, $month, $viewType];

    if ($viewType === 'weekly') {
        $where[] = 'w.week_number = ?';
        $types .= 'i';
        $params[] = $weekNumber;
    }

    if ($branchId !== null && $branchId > 0) {
        $where[] = 'COALESCE(w.branch_id, e.branch_id) = ?';
        $types .= 'i';
        $params[] = $branchId;
    }

    if ($employeeId > 0) {
        $where[] = 'w.employee_id = ?';
        $types .= 'i';
        $params[] = $employeeId;
    }

    $sql = "
        SELECT
            w.*,
            CONCAT_WS(' ', e.first_name, e.last_name) AS employee_name,
            COALESCE(w.branch_id, e.branch_id) AS resolved_branch_id,
            COALESCE(b.branch_name, eb.branch_name, '') AS resolved_branch_name,
            COALESCE(e.daily_rate, 0) AS employee_daily_rate,
            COALESCE(e.performance_allowance, 0) AS employee_default_allowance
        FROM weekly_payroll_reports w
        INNER JOIN employees e ON e.id = w.employee_id
        LEFT JOIN branches b ON b.id = w.branch_id
        LEFT JOIN branches eb ON eb.id = e.branch_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY w.updated_at DESC, w.id DESC
    ";

    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare weekly overlay query: ' . mysqli_error($db));
    }

    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $employeeKey = (int)$row['employee_id'];
        if (!isset($rows[$employeeKey])) {
            $rows[$employeeKey] = $row;
        }
    }

    mysqli_stmt_close($stmt);

    return $rows;
}

function payroll_build_report_rows($db, $year, $month, $viewType, $weekNumber, $branchId = null, $employeeId = 0) {
    $period = payroll_period_context($year, $month, $viewType, $weekNumber);
    $employees = payroll_fetch_worker_employees($db, $branchId, $employeeId);
    $dailyRows = payroll_fetch_daily_aggregates($db, $year, $month, $viewType, $weekNumber, $branchId, $employeeId);
    $attendanceRows = payroll_fetch_attendance_aggregates($db, $period['start_date'], $period['end_date'], $branchId, $employeeId);
    $weeklyRows = payroll_fetch_weekly_overlays($db, $year, $month, $viewType, $weekNumber, $branchId, $employeeId);

    $employeeIds = array_unique(array_merge(
        array_keys($employees),
        array_keys($dailyRows),
        array_keys($attendanceRows),
        array_keys($weeklyRows)
    ));
    sort($employeeIds);

    $rows = [];

    foreach ($employeeIds as $currentEmployeeId) {
        $employee = isset($employees[$currentEmployeeId]) ? $employees[$currentEmployeeId] : null;
        $daily = isset($dailyRows[$currentEmployeeId]) ? $dailyRows[$currentEmployeeId] : null;
        $attendance = isset($attendanceRows[$currentEmployeeId]) ? $attendanceRows[$currentEmployeeId] : null;
        $weekly = isset($weeklyRows[$currentEmployeeId]) ? $weeklyRows[$currentEmployeeId] : null;

        if (!$employee && !$weekly) {
            continue;
        }

        $computed = $daily ?: $attendance;
        if (!$computed && !$weekly) {
            continue;
        }

        $employeeName = $employee ? $employee['employee_name'] : ($weekly['employee_name'] ?: ('Employee #' . $currentEmployeeId));
        $resolvedBranchId = null;
        if ($computed && array_key_exists('branch_id', $computed)) {
            $resolvedBranchId = $computed['branch_id'];
        } elseif ($weekly) {
            $resolvedBranchId = $weekly['resolved_branch_id'] === null ? null : (int)$weekly['resolved_branch_id'];
        } elseif ($employee) {
            $resolvedBranchId = $employee['branch_id'];
        }

        $resolvedBranchName = '';
        if ($computed && !empty($computed['branch_name'])) {
            $resolvedBranchName = $computed['branch_name'];
        } elseif ($weekly && !empty($weekly['resolved_branch_name'])) {
            $resolvedBranchName = $weekly['resolved_branch_name'];
        } elseif ($employee) {
            $resolvedBranchName = $employee['branch_name'];
        }

        $dailyRate = $computed ? payroll_to_float($computed['daily_rate']) : payroll_to_float($weekly ? $weekly['daily_rate'] : ($employee ? $employee['daily_rate'] : 0));
        if ($dailyRate <= 0 && $employee) {
            $dailyRate = payroll_to_float($employee['daily_rate']);
        }

        $daysWorked = $computed ? payroll_to_float($computed['days_worked']) : payroll_to_float($weekly ? $weekly['days_worked'] : 0);
        $totalHours = $computed ? payroll_to_float($computed['total_hours']) : payroll_to_float($weekly ? $weekly['total_hours'] : 0);
        $basicPay = $computed ? payroll_to_float($computed['basic_pay']) : payroll_to_float($weekly ? $weekly['basic_pay'] : 0);
        $otHours = $computed ? payroll_to_float($computed['ot_hours']) : payroll_to_float($weekly ? $weekly['ot_hours'] : 0);
        $otRate = $dailyRate > 0 ? ($dailyRate / 8) : payroll_to_float($weekly ? $weekly['ot_rate'] : 0);
        $otAmount = $computed ? payroll_to_float($computed['ot_amount']) : payroll_to_float($weekly ? $weekly['ot_amount'] : 0);
        $grossPay = $computed ? payroll_to_float($computed['gross_pay']) : payroll_to_float($weekly ? $weekly['gross_pay'] : ($basicPay + $otAmount));

        $governmentDeductions = $computed
            ? ($viewType === 'monthly' ? payroll_monthly_deductions() : payroll_weekly_deductions($weekNumber))
            : [
                'sss' => payroll_to_float($weekly ? $weekly['sss_deduction'] : 0),
                'philhealth' => payroll_to_float($weekly ? $weekly['philhealth_deduction'] : 0),
                'pagibig' => payroll_to_float($weekly ? $weekly['pagibig_deduction'] : 0),
            ];

        $performanceAllowance = $weekly
            ? payroll_to_float($weekly['performance_allowance'])
            : payroll_to_float($employee ? $employee['performance_allowance'] : 0);
        $caDeduction = $weekly
            ? payroll_to_float($weekly['ca_deduction'])
            : payroll_to_float($computed ? $computed['ca_deduction'] : 0);
        $sssLoan = $weekly
            ? payroll_to_float($weekly['sss_loan'])
            : payroll_to_float($computed ? $computed['sss_loan'] : 0);

        $totalDeductions = $governmentDeductions['sss'] + $governmentDeductions['philhealth'] + $governmentDeductions['pagibig'] + $sssLoan;
        $grossPlusAllowance = $grossPay + $performanceAllowance;
        $takeHomePay = $grossPay + $performanceAllowance + $otAmount - $totalDeductions - $caDeduction;

        $rows[] = [
            'id' => $weekly ? (int)$weekly['id'] : sprintf('computed-%d-%d-%d-%s-%d', $currentEmployeeId, $year, $month, $viewType, $weekNumber),
            'employee_id' => $currentEmployeeId,
            'employee_name' => $employeeName,
            'branch_id' => $resolvedBranchId,
            'branch_name' => $resolvedBranchName,
            'report_year' => (int)$year,
            'report_month' => (int)$month,
            'week_number' => (int)$weekNumber,
            'view_type' => $viewType,
            'days_worked' => $daysWorked,
            'total_hours' => $totalHours,
            'daily_rate' => $dailyRate,
            'basic_pay' => $basicPay,
            'ot_hours' => $otHours,
            'ot_rate' => $otRate,
            'ot_amount' => $otAmount,
            'performance_allowance' => $performanceAllowance,
            'gross_pay' => $grossPay,
            'gross_plus_allowance' => $grossPlusAllowance,
            'ca_deduction' => $caDeduction,
            'sss_deduction' => $governmentDeductions['sss'],
            'philhealth_deduction' => $governmentDeductions['philhealth'],
            'pagibig_deduction' => $governmentDeductions['pagibig'],
            'sss_loan' => $sssLoan,
            'total_deductions' => $totalDeductions,
            'take_home_pay' => $takeHomePay,
            'payment_status' => $weekly ? $weekly['payment_status'] : 'Not Paid',
            'status' => $weekly && !empty($weekly['status']) ? $weekly['status'] : 'Draft',
            'created_at' => $weekly ? $weekly['created_at'] : null,
            'updated_at' => $weekly ? $weekly['updated_at'] : null,
        ];
    }

    usort($rows, function ($a, $b) {
        return strcasecmp($a['employee_name'], $b['employee_name']);
    });

    return $rows;
}

function payroll_build_summary($rows) {
    $summary = payroll_empty_report_summary();
    $employees = [];

    foreach ($rows as $row) {
        $employees[$row['employee_id']] = true;
        $summary['total_days_worked'] += payroll_to_float($row['days_worked']);
        $summary['total_gross_pay'] += payroll_to_float($row['gross_pay']);
        $summary['total_allowances'] += payroll_to_float($row['performance_allowance']);
        $summary['total_ca_deductions'] += payroll_to_float($row['ca_deduction']);
        $summary['total_deductions'] += payroll_to_float($row['total_deductions']);
        $summary['total_take_home_pay'] += payroll_to_float($row['take_home_pay']);
    }

    $summary['total_employees'] = count($employees);

    return $summary;
}

function payroll_find_report_row($db, $year, $month, $viewType, $weekNumber, $employeeId, $branchId = null) {
    $rows = payroll_build_report_rows($db, $year, $month, $viewType, $weekNumber, $branchId, $employeeId);
    if (count($rows) === 0) {
        return null;
    }

    foreach ($rows as $row) {
        if ((int)$row['employee_id'] === (int)$employeeId) {
            return $row;
        }
    }

    return null;
}

