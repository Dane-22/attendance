<?php
// Siguraduhin na tama ang path papuntang db_connection.php
require_once 'conn/db_connection.php'; 

// 1. I-check kung ano ang variable name sa config mo ($conn o $con)
// Gagamit tayo ng mysqli fetch_all para makuha lahat ng rows
$sql = "SELECT id, branch_name, branch_address, created_at, is_active FROM branches";
$result = mysqli_query($db, $sql); // Palitan ang $conn kung iba ang nasa config mo

if ($result) {
    // Kunin lahat ng branches bilang Associative Array
    $branches = mysqli_fetch_all($result, MYSQLI_ASSOC);

    // I-output as JSON para makita mo sa Postman
    header('Content-Type: application/json');
    echo json_encode($branches);
} else {
    echo "Error sa query: " . mysqli_error($db);
}
?>