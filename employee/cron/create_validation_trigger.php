<?php
/**
 * Create database trigger to validate attendance before inserting to daily_payroll_reports
 * Run this once to set up the trigger
 */

// Set timezone
date_default_timezone_set('Asia/Manila');

// Database connection
require_once __DIR__ . '/../../conn/db_connection.php';

header('Content-Type: text/plain');
echo "=== Creating Attendance Validation Trigger ===\n\n";

// Drop existing trigger if exists
$drop_sql = "DROP TRIGGER IF EXISTS trg_validate_attendance_before_dpr_insert";
if (mysqli_query($db, $drop_sql)) {
    echo "✓ Dropped existing trigger (if any)\n";
} else {
    echo "✗ Error dropping trigger: " . mysqli_error($db) . "\n";
}

// Create trigger using mysqli without DELIMITER
$trigger_sql = "
CREATE TRIGGER trg_validate_attendance_before_dpr_insert 
BEFORE INSERT ON daily_payroll_reports
FOR EACH ROW
BEGIN
    DECLARE v_attendance_count INT DEFAULT 0;
    
    SELECT COUNT(*) INTO v_attendance_count
    FROM attendance a
    WHERE a.employee_id = NEW.employee_id
      AND a.attendance_date = NEW.report_date
      AND a.time_in IS NOT NULL
      AND a.time_out IS NOT NULL;
    
    IF v_attendance_count = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot insert daily_payroll_reports record without valid attendance record with time_in and time_out';
    END IF;
END
";

if (mysqli_query($db, $trigger_sql)) {
    echo "✓ Trigger created successfully\n";
    echo "\nTrigger details:\n";
    echo "  - Name: trg_validate_attendance_before_dpr_insert\n";
    echo "  - Event: BEFORE INSERT on daily_payroll_reports\n";
    echo "  - Validation: Checks for attendance record with time_in AND time_out\n";
    echo "  - Error: Throws SQLSTATE 45000 if no valid attendance found\n";
} else {
    echo "✗ Error creating trigger: " . mysqli_error($db) . "\n";
    exit(1);
}

// Also create trigger for UPDATE operations
$drop_update_sql = "DROP TRIGGER IF EXISTS trg_validate_attendance_before_dpr_update";
mysqli_query($db, $drop_update_sql);

$update_trigger_sql = "
CREATE TRIGGER trg_validate_attendance_before_dpr_update 
BEFORE UPDATE ON daily_payroll_reports
FOR EACH ROW
BEGIN
    DECLARE v_attendance_count INT DEFAULT 0;
    
    -- Only validate if key fields changed (employee_id or report_date)
    IF OLD.employee_id != NEW.employee_id OR OLD.report_date != NEW.report_date THEN
        SELECT COUNT(*) INTO v_attendance_count
        FROM attendance a
        WHERE a.employee_id = NEW.employee_id
          AND a.attendance_date = NEW.report_date
          AND a.time_in IS NOT NULL
          AND a.time_out IS NOT NULL;
        
        IF v_attendance_count = 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot update daily_payroll_reports record without valid attendance record with time_in and time_out';
        END IF;
    END IF;
END
";

if (mysqli_query($db, $update_trigger_sql)) {
    echo "✓ Update trigger created successfully\n";
} else {
    echo "✗ Error creating update trigger: " . mysqli_error($db) . "\n";
}

echo "\n=== Complete ===\n";
echo "Both triggers are now active and will prevent orphaned records.\n";
exit(0);
?>
