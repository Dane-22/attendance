<?php
/**
 * Monthly Leave Credit Script
 * 
 * This script adds 1 leave credit to all active employees on a monthly basis.
 * It is idempotent - safe to run multiple times, will only credit if not already credited for the month.
 * 
 * Usage:
 * - Manual: php monthly_leave_credit.php
 * - Scheduled: Run via Windows Task Scheduler on the 1st of each month at 12:00 AM
 * 
 * Windows Task Scheduler Setup:
 * 1. Open Task Scheduler (taskschd.msc)
 * 2. Create Basic Task
 * 3. Name: "Monthly Leave Credit"
 * 4. Trigger: Monthly on day 1 at 12:00:00 AM
 * 5. Action: Start a program
 * 6. Program: C:\wamp64\bin\php\php8.x.x\php.exe
 * 7. Arguments: c:\wamp64\www\main\employee\cron\monthly_leave_credit.php
 */

date_default_timezone_set('Asia/Manila');

// Prevent browser access (CLI only)
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("This script can only be run from command line.\n");
}

require_once __DIR__ . '/../../conn/db_connection.php';
require_once __DIR__ . '/../../functions.php';

// Log file
$log_file = __DIR__ . '/monthly_leave_credit.log';
$log_message = function($msg) use ($log_file) {
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $msg\n", FILE_APPEND);
    echo "$msg\n";
};

$log_message("=== Starting Monthly Leave Credit Process ===");

// Get current month/year for comparison
$current_year = date('Y');
$current_month = date('n');
$current_date = date('Y-m-d');
$first_day_of_month = date('Y-m-01');

$log_message("Processing credits for: {$current_year}-{$current_month} (Date: {$current_date})");

// Check if employee_leaves table exists
$table_check = mysqli_query($db, "SHOW TABLES LIKE 'employee_leaves'");
if (mysqli_num_rows($table_check) == 0) {
    $log_message("ERROR: employee_leaves table does not exist! Run setup_leave_system.php first.");
    exit(1);
}

// Check if leave_transactions table exists
$trans_check = mysqli_query($db, "SHOW TABLES LIKE 'leave_transactions'");
if (mysqli_num_rows($trans_check) == 0) {
    $log_message("WARNING: leave_transactions table does not exist. Audit trail will be skipped.");
    $has_transactions_table = false;
} else {
    $has_transactions_table = true;
}

// Get all active employees who need credit for this month
// An employee needs credit if:
// 1. They have no record in employee_leaves (last_credited_month IS NULL), OR
// 2. Their last_credited_month is before the first day of current month
$sql = "SELECT 
            e.id,
            e.first_name,
            e.last_name,
            e.employee_code,
            el.id as leave_record_id,
            el.total_leaves,
            el.remaining_leaves,
            el.last_credited_month
        FROM employees e
        LEFT JOIN employee_leaves el ON e.id = el.employee_id
        WHERE e.status = 'Active'
          AND (
              el.last_credited_month IS NULL 
              OR el.last_credited_month < ?
          )";

$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, 's', $first_day_of_month);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$employees_to_credit = [];
while ($row = mysqli_fetch_assoc($result)) {
    $employees_to_credit[] = $row;
}
mysqli_stmt_close($stmt);

$total_employees = count($employees_to_credit);
$log_message("Found {$total_employees} active employees needing credit for {$current_year}-{$current_month}");

if ($total_employees == 0) {
    $log_message("All employees already credited for this month. Nothing to do.");
    $log_message("=== Monthly Leave Credit Process Complete ===\n");
    exit(0);
}

// Process each employee
$credited_count = 0;
$failed_count = 0;
$transaction_errors = [];

foreach ($employees_to_credit as $employee) {
    $emp_id = $employee['id'];
    $emp_name = $employee['first_name'] . ' ' . $employee['last_name'];
    $emp_code = $employee['employee_code'];
    $leave_record_id = $employee['leave_record_id'];
    
    mysqli_begin_transaction($db);
    
    try {
        if ($leave_record_id) {
            // Update existing leave record
            $update_sql = "UPDATE employee_leaves 
                          SET total_leaves = total_leaves + 1.00,
                              remaining_leaves = remaining_leaves + 1.00,
                              last_credited_month = ?,
                              updated_at = CURRENT_TIMESTAMP
                          WHERE employee_id = ?";
            $update_stmt = mysqli_prepare($db, $update_sql);
            mysqli_stmt_bind_param($update_stmt, 'si', $first_day_of_month, $emp_id);
            $update_result = mysqli_stmt_execute($update_stmt);
            mysqli_stmt_close($update_stmt);
            
            if (!$update_result) {
                throw new Exception("Failed to update leave record: " . mysqli_error($db));
            }
        } else {
            // Create new leave record for employee
            $insert_sql = "INSERT INTO employee_leaves 
                          (employee_id, total_leaves, used_leaves, remaining_leaves, last_credited_month, created_at, updated_at)
                          VALUES (?, 1.00, 0.00, 1.00, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
            $insert_stmt = mysqli_prepare($db, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, 'is', $emp_id, $first_day_of_month);
            $insert_result = mysqli_stmt_execute($insert_stmt);
            mysqli_stmt_close($insert_stmt);
            
            if (!$insert_result) {
                throw new Exception("Failed to create leave record: " . mysqli_error($db));
            }
        }
        
        // Log transaction if table exists
        if ($has_transactions_table) {
            $trans_sql = "INSERT INTO leave_transactions 
                         (employee_id, transaction_type, amount, description, reference_date, created_by, created_at)
                         VALUES (?, 'credit', 1.00, 'Monthly leave credit', ?, 0, CURRENT_TIMESTAMP)";
            $trans_stmt = mysqli_prepare($db, $trans_sql);
            mysqli_stmt_bind_param($trans_stmt, 'is', $emp_id, $first_day_of_month);
            $trans_result = mysqli_stmt_execute($trans_stmt);
            mysqli_stmt_close($trans_stmt);
            
            if (!$trans_result) {
                $transaction_errors[] = "Failed to log transaction for employee {$emp_id}: " . mysqli_error($db);
            }
        }
        
        // Log activity
        logActivity($db, 'Leave Credit', "Monthly leave credit of 1.00 day added for employee {$emp_name} ({$emp_code})");
        
        mysqli_commit($db);
        $credited_count++;
        $log_message("✓ Credited: {$emp_name} ({$emp_code}) - ID: {$emp_id}");
        
    } catch (Exception $e) {
        mysqli_rollback($db);
        $failed_count++;
        $log_message("✗ Failed: {$emp_name} ({$emp_code}) - Error: " . $e->getMessage());
    }
}

// Summary
$log_message("=== Monthly Leave Credit Summary ===");
$log_message("Total employees processed: {$total_employees}");
$log_message("Successfully credited: {$credited_count}");
$log_message("Failed: {$failed_count}");

if (!empty($transaction_errors)) {
    $log_message("Transaction logging errors: " . count($transaction_errors));
    foreach ($transaction_errors as $err) {
        $log_message("  - {$err}");
    }
}

$log_message("=== Monthly Leave Credit Process Complete ===\n");

exit($failed_count > 0 ? 1 : 0);
