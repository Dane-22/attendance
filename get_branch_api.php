<?php
require_once __DIR__ . '/conn/db_connection.php';

header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

// Kunin lang ang mga active na branches
$sql = "SELECT branch_name FROM branches WHERE is_active = 1";
$result = mysqli_query($db, $sql);

$branches = [];
while ($row = mysqli_fetch_assoc($result)) {
    $branches[] = $row['branch_name'];
}

echo json_encode($branches);
?>