<?php
/**
 * Check weekly_payroll_reports table structure
 */

date_default_timezone_set('Asia/Manila');

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("CLI only\n");
}

require_once __DIR__ . '/../../conn/db_connection.php';

echo "=== weekly_payroll_reports table structure ===\n";

$result = mysqli_query($db, "SHOW COLUMNS FROM weekly_payroll_reports");
while ($row = mysqli_fetch_assoc($result)) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

exit(0);
