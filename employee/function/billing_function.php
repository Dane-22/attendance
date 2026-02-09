<?php

// Daily rate ang nasa database, hindi monthly salary
$employees = $db->query("SELECT id, employee_code, first_name, middle_name, last_name, daily_rate FROM employees");

function getMonthlySalary($dailyRate) {
    // Daily rate × 26 working days (Monday-Saturday)
    return $dailyRate * 26;
}

function getWeeklySalary($dailyRate) {
    // Daily rate × 6 working days (Monday-Saturday)
    return $dailyRate * 6;
}

function getDateRange($viewType) {
    $today = date('Y-m-d');
    if ($viewType === 'weekly') {
        $startDate = date('Y-m-d', strtotime('-7 days', strtotime($today)));
        $endDate = $today;
    } elseif ($viewType === 'monthly') {
        $startDate = date('Y-m-01', strtotime($today));
        $endDate = date('Y-m-t', strtotime($today));
    } else {
        $startDate = $today;
        $endDate = $today;
    }
    return ['start' => $startDate, 'end' => $endDate];
}
?>