<?php
// 1. Connection settings
$host = "localhost";
$user = "root";
$pass = ""; // Default sa WAMP ay empty
$dbname = "attendance_db";

$conn = new mysqli($host, $user, $pass, $dbname);

// 2. Tanggapin ang JSON data mula sa Mobile
$json = file_get_contents('php://input');
$data = json_decode($json);

if ($data) {
    $sid = $data->student_id;
    $stat = $data->status;

    // 3. I-save sa Database
    $sql = "INSERT INTO attendance (student_id, status) VALUES ('$sid', '$stat')";
    
    if ($conn->query($sql)) {
        echo json_encode(["res" => "Success"]);
    } else {
        echo json_encode(["res" => "Error: " . $conn->error]);
    }
}
?>