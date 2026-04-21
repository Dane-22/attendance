<?php
// restore_database_v2.php - Pure PHP database restore
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300); // 5 minutes

$host = 'localhost';
$user = 'root';
$pass = '';
$database = 'attendance_db';
$sqlFile = __DIR__ . '/attendance_db (2).sql';

echo "<h3>Restoring Database (PHP Method)...</h3>";

// Check if SQL file exists
if (!file_exists($sqlFile)) {
    die("❌ SQL file not found: $sqlFile");
}

echo "✅ Found SQL dump file: " . number_format(filesize($sqlFile) / 1024, 2) . " KB<br>";

// Connect to MySQL
$conn = mysqli_connect($host, $user, $pass);
if (!$conn) {
    die("❌ Cannot connect to MySQL: " . mysqli_connect_error());
}
echo "✅ Connected to MySQL<br>";

// Drop and recreate database
mysqli_query($conn, "DROP DATABASE IF EXISTS $database");
if (!mysqli_query($conn, "CREATE DATABASE $database")) {
    die("❌ Failed to create database: " . mysqli_error($conn));
}
echo "✅ Created database: $database<br>";
mysqli_select_db($conn, $database);

// Read SQL file
$sql = file_get_contents($sqlFile);

// Split into statements (handle DELIMITER changes for triggers)
$statements = [];
$currentStatement = '';
$inTrigger = false;
$delimiter = ';';

$lines = explode("\n", $sql);
$totalLines = count($lines);

foreach ($lines as $i => $line) {
    $trimmed = trim($line);
    
    // Skip empty lines and comments
    if (empty($trimmed) || strpos($trimmed, '--') === 0 || strpos($trimmed, '/*') === 0) {
        continue;
    }
    
    // Handle DELIMITER changes
    if (stripos($trimmed, 'DELIMITER') === 0) {
        $parts = preg_split('/\s+/', $trimmed);
        $delimiter = trim($parts[1] ?? ';');
        continue;
    }
    
    $currentStatement .= $line . "\n";
    
    // Check if statement is complete
    if (substr($trimmed, -strlen($delimiter)) === $delimiter) {
        // Remove the delimiter from the end
        $currentStatement = substr($currentStatement, 0, -strlen($delimiter));
        $statements[] = trim($currentStatement);
        $currentStatement = '';
    }
}

echo "⏳ Executing " . count($statements) . " SQL statements...<br>";

$success = 0;
$failed = 0;
$errors = [];

foreach ($statements as $i => $stmt) {
    if (empty($stmt)) continue;
    
    // Skip DROP/CREATE DATABASE statements (we already handled that)
    if (stripos($stmt, 'DROP DATABASE') === 0 || stripos($stmt, 'CREATE DATABASE') === 0) {
        continue;
    }
    
    if (mysqli_query($conn, $stmt)) {
        $success++;
    } else {
        $failed++;
        if ($failed <= 5) { // Only show first 5 errors
            $errors[] = mysqli_error($conn);
        }
    }
    
    // Show progress every 100 statements
    if ($i % 100 === 0 && $i > 0) {
        echo "  Processed $i statements...<br>";
        flush();
    }
}

echo "<br>✅ Execution complete!<br>";
echo "- Successful: $success<br>";
echo "- Failed: $failed<br>";

if (!empty($errors)) {
    echo "<details><summary>Sample errors (showing first 5)</summary>";
    foreach ($errors as $err) {
        echo "<pre>$err</pre>";
    }
    echo "</details>";
}

// Verify tables
$result = mysqli_query($conn, "SHOW TABLES");
if ($result) {
    $tableCount = mysqli_num_rows($result);
    echo "<br>📊 Tables created: $tableCount<br><ul>";
    while ($row = mysqli_fetch_array($result)) {
        echo "<li>{$row[0]}</li>";
    }
    echo "</ul>";
    
    // Check employees count
    $empResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM employees");
    if ($empResult) {
        $empCount = mysqli_fetch_assoc($empResult)['count'];
        echo "👥 Employees: $empCount | ";
    }
    
    // Check activity logs
    $logResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM activity_logs");
    if ($logResult) {
        $logCount = mysqli_fetch_assoc($logResult)['count'];
        echo "Activity logs: $logCount<br><br>";
    }
} else {
    echo "⚠️ Could not retrieve tables: " . mysqli_error($conn) . "<br>";
}

mysqli_close($conn);

echo "<a href='/main/' style='padding:10px 20px;background:#28a745;color:white;text-decoration:none;border-radius:5px;'>Go to App</a>";
?>
