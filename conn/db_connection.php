<?php
// conn/db_connection.php
$host = 'localhost';
$user_name = 'root';
$passwd = '';
$schema = 'attendance_db';

// Create connection
$db = mysqli_connect($host, $user_name, $passwd, $schema);

// Check connection
if (!$db) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to ensure proper JSON encoding
mysqli_set_charset($db, 'utf8mb4');
?>