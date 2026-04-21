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

// Create database
if (!mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $database")) {
    die("❌ Failed to create database: " . mysqli_error($conn));
}
echo "✅ Created database: $database<br>";

// Select the database
mysqli_select_db($conn, $database);

// Read and execute SQL file
$sql = file_get_contents($sqlFile);

// Split by semicolons to execute statements individually
$statements = array_filter(array_map('trim', explode(';', $sql)));

$success = 0;
$failed = 0;

foreach ($statements as $statement) {
    if (empty($statement)) continue;
    
    // Skip comments and empty lines
    $cleanStatement = trim(preg_replace('/--.*\n/', '', $statement));
    if (empty($cleanStatement)) continue;
    
    if (mysqli_query($conn, $statement . ';')) {
        $success++;
    } else {
        $failed++;
        // Only show errors for important statements
        if (!strpos($statement, 'DROP TABLE') && !strpos($statement, 'INSERT INTO `activity_logs`')) {
            echo "⚠️ Warning: " . mysqli_error($conn) . "<br>";
        }
    }
}

echo "<br>✅ Database restored!<br>";
echo "- Successful statements: $success<br>";
echo "- Failed statements: $failed<br><br>";

// Verify tables
$result = mysqli_query($conn, "SHOW TABLES");
$tableCount = mysqli_num_rows($result);
echo "📊 Tables created: $tableCount<br><ul>";
while ($row = mysqli_fetch_array($result)) {
    echo "<li>{$row[0]}</li>";
}
echo "</ul>";

// Check employees count
$empResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM employees");
$empCount = mysqli_fetch_assoc($empResult)['count'];
echo "👥 Employees in database: $empCount<br><br>";

mysqli_close($conn);

echo "<a href='/main/' style='padding:10px 20px;background:#28a745;color:white;text-decoration:none;border-radius:5px;'>Go to App</a>";
?>
