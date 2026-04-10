<?php
/**
 * Week Calculator Helper
 * Calculates work-week boundaries based on work days (Mon-Sat, excluding Sundays)
 * Used by weekly_report.php and billing.php for consistent week calculations
 */

/**
 * Calculate week boundaries for a given month/year
 * Week 1: From 1st work day to first Sunday or 5 days max
 * Weeks 2-5: Monday to Saturday (5-6 work days)
 * 
 * @param int $year Year (e.g., 2026)
 * @param int $month Month (1-12)
 * @return array Week boundaries with start/end days for each week
 */
function calculateWorkWeekBoundaries($year, $month) {
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    
    // Generate all work days in the month (exclude Sundays)
    $work_days = [];
    for ($day = 1; $day <= $days_in_month; $day++) {
        $date_str = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $day_of_week = date('w', strtotime($date_str)); // 0 = Sunday, 1 = Monday, etc.
        if ($day_of_week != 0) { // Exclude Sundays
            $work_days[] = $day;
        }
    }
    
    // Calculate week boundaries
    $week_boundaries = [];
    $current_week = 1;
    $day_index = 0;
    $total_work_days = count($work_days);
    
    while ($day_index < $total_work_days) {
        $week_start_day = $work_days[$day_index];
        
        // For Week 1, go until we hit a Sunday or max 5 days
        // For other weeks, take up to 5 days (Mon-Fri) or 6 days (Mon-Sat)
        $days_in_this_week = 0;
        $week_end_index = $day_index;
        
        while ($week_end_index < $total_work_days && $days_in_this_week < 6) {
            // Check if next day would cross a Sunday (Sat -> Mon transition)
            if ($days_in_this_week > 0) {
                $current_date = sprintf('%04d-%02d-%02d', $year, $month, $work_days[$week_end_index]);
                $prev_date = sprintf('%04d-%02d-%02d', $year, $month, $work_days[$week_end_index - 1]);
                $current_dow = date('w', strtotime($current_date));
                $prev_dow = date('w', strtotime($prev_date));
                
                // If we went from Saturday (6) to Monday (1), that's a new week
                if ($prev_dow == 6 && $current_dow == 1) {
                    break;
                }
            }
            $days_in_this_week++;
            $week_end_index++;
            
            // Cap at 5 days for weeks after week 1 (Mon-Fri work week)
            if ($current_week > 1 && $days_in_this_week >= 5) {
                break;
            }
        }
        
        $week_end_day = $work_days[$week_end_index - 1];
        $week_boundaries[$current_week] = [
            'start' => $week_start_day,
            'end' => $week_end_day,
            'start_date' => sprintf('%04d-%02d-%02d', $year, $month, $week_start_day),
            'end_date' => sprintf('%04d-%02d-%02d', $year, $month, $week_end_day)
        ];
        
        $day_index = $week_end_index;
        $current_week++;
    }
    
    return $week_boundaries;
}

/**
 * Get prorated government deductions for a specific week
 * Based on weekly_report.php logic
 * 
 * @param int $week_number Week number (1-5)
 * @return array Deduction amounts for SSS, PhilHealth, Pag-IBIG
 */
function getWeeklyGovernmentDeductions($week_number) {
    switch ($week_number) {
        case 1:
            return [
                'sss' => 250.00,
                'philhealth' => 100.00,
                'pagibig' => 50.00,
                'total' => 400.00
            ];
        case 2:
            return [
                'sss' => 100.00,
                'philhealth' => 100.00,
                'pagibig' => 50.00,
                'total' => 250.00
            ];
        case 3:
            return [
                'sss' => 100.00,
                'philhealth' => 50.00,
                'pagibig' => 100.00,
                'total' => 250.00
            ];
        case 4:
        case 5:
        default:
            return [
                'sss' => 0.00,
                'philhealth' => 0.00,
                'pagibig' => 0.00,
                'total' => 0.00
            ];
    }
}

/**
 * Calculate cumulative deductions for weeks 1 through N
 * 
 * @param int $max_week Maximum week to include (e.g., 3 for weeks 1-3)
 * @return array Cumulative deduction amounts
 */
function calculateCumulativeDeductions($max_week) {
    $cumulative = [
        'sss' => 0.00,
        'philhealth' => 0.00,
        'pagibig' => 0.00,
        'total' => 0.00
    ];
    
    for ($week = 1; $week <= $max_week; $week++) {
        $weekly = getWeeklyGovernmentDeductions($week);
        $cumulative['sss'] += $weekly['sss'];
        $cumulative['philhealth'] += $weekly['philhealth'];
        $cumulative['pagibig'] += $weekly['pagibig'];
        $cumulative['total'] += $weekly['total'];
    }
    
    return $cumulative;
}

/**
 * Determine which week a specific date falls into
 * 
 * @param int $year Year
 * @param int $month Month
 * @param int $day Day of month
 * @return int Week number (1-5) or 0 if Sunday/not in any week
 */
function getWeekNumberForDate($year, $month, $day) {
    $week_boundaries = calculateWorkWeekBoundaries($year, $month);
    
    foreach ($week_boundaries as $week_num => $boundaries) {
        if ($day >= $boundaries['start'] && $day <= $boundaries['end']) {
            return $week_num;
        }
    }
    
    return 0; // Sunday or not in any work week
}

/**
 * Get current week based on today's date
 * 
 * @return int Current week number (1-5)
 */
function getCurrentWorkWeek() {
    $year = date('Y');
    $month = date('m');
    $today = date('j');
    return getWeekNumberForDate($year, $month, $today);
}
