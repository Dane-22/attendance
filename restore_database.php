<?php
// restore_database.php - Restore attendance_db from SQL dump
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$user = 'root';
$pass = '';
$database = 'attendance_db';
$sqlFile = __DIR__ . '/attendance_db (2).sql';

echo "<h3>Restoring Database...</h3>";

// Check if SQL file exists
if (!file_exists($sqlFile)) {
    die("❌ SQL file not found: $sqlFile");
}

echo "✅ Found SQL dump file<br>";

// Connect to MySQL (without database)
$conn = mysqli_connect($host, $user, $pass);
if (!$conn) {
    die("❌ Cannot connect to MySQL: " . mysqli_connect_error());
}
echo "✅ Connected to MySQL<br>";

// Drop database if exists and recreate
mysqli_query($conn, "DROP DATABASE IF EXISTS $database");
if (!mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $database")) {
    die("❌ Failed to create database: " . mysqli_error($conn));
}
echo "✅ Created database: $database<br>";

// Use mysql command line for better SQL import (handles DELIMITER/triggers properly)
$command = sprintf(
    'mysql -h %s -u %s %s %s < "%s" 2>&1',
    escapeshellarg($host),
    escapeshellarg($user),
    $pass ? '-p' . escapeshellarg($pass) : '',
    escapeshellarg($database),
    $sqlFile
);

echo "⏳ Importing SQL dump...<br>";
$output = shell_exec($command);
$returnCode = 0;

// Alternative: Try using multi_query if shell_exec fails
if (empty($output) || strpos($output, 'error') !== false) {
    echo "⚠️ Shell import had issues, trying PHP method...<br>";
    
    // Select the database
    mysqli_select_db($conn, $database);
    
    // Read SQL file
    $sql = file_get_contents($sqlFile);
    
    // Remove DELIMITER commands and normalize trigger syntax
    $sql = preg_replace('/DELIMITER\s+\$\$/i', '', $sql);
    $sql = preg_replace('/DELIMITER\s+;/i', '', $sql);
    $sql = str_replace('$$', ';', $sql);
    
    // Remove SQL comments that might cause issues
    $sql = preg_replace('/--[^\n]*\n/', "\n", $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    
    // Execute using multi_query
    if (mysqli_multi_query($conn, $sql)) {
        do {
            if ($result = mysqli_store_result($conn)) {
                mysqli_free_result($result);
            }
        } while (mysqli_more_results($conn) && mysqli_next_result($conn));
        
        if (mysqli_errno($conn)) {
            echo "⚠️ Some statements failed: " . mysqli_error($conn) . "<br>";
        }
    }
}

echo "<br>✅ Database restore completed!<br><br>";

// Verify tables
mysqli_select_db($conn, $database);
$result = mysqli_query($conn, "SHOW TABLES");
if ($result) {
    $tableCount = mysqli_num_rows($result);
    echo "📊 Tables created: $tableCount<br><ul>";
    while ($row = mysqli_fetch_array($result)) {
        echo "<li>{$row[0]}</li>";
    }
    echo "</ul>";
    
    // Check employees count
    $empResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM employees");
    if ($empResult) {
        $empCount = mysqli_fetch_assoc($empResult)['count'];
        echo "👥 Employees in database: $empCount<br><br>";
    }
} else {
    echo "⚠️ Could not retrieve tables<br>";
}

mysqli_close($conn);

echo "<a href='/main/' style='padding:10px 20px;background:#28a745;color:white;text-decoration:none;border-radius:5px;'>Go to App</a>";
?>
