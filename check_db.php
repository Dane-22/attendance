<?php
require_once __DIR__ . '/conn/db_connection.php';

$result = mysqli_query($db, "SHOW COLUMNS FROM weekly_payroll_reports LIKE 'view_type'");
$row = mysqli_fetch_assoc($result);
echo "Current view_type column definition:\n";
echo "Type: " . $row['Type'] . "\n";
echo "Default: " . $row['Default'] . "\n";

mysqli_close($db);
