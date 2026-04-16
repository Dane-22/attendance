<?php
// diagnose_db.php - Check available databases and tables
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Database Diagnosis</h3>";

// Load env
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

$host = $_ENV['DB_HOST'] ?? 'localhost';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';
$schema = $_ENV['DB_SCHEMA'] ?? 'attendance_db';

echo "Current .env settings:<br>";
echo "DB_HOST: $host<br>";
echo "DB_USER: $user<br>";
echo "DB_SCHEMA: $schema<br><br>";

// Connect without database to list all databases
$conn = @mysqli_connect($host, $user, $pass);
if (!$conn) {
    die("Cannot connect to MySQL: " . mysqli_connect_error());
}

echo "✅ Connected to MySQL<br><br>";

// List databases
$result = mysqli_query($conn, "SHOW DATABASES");
echo "<b>Available Databases:</b><br>";
$databases = [];
while ($row = mysqli_fetch_assoc($result)) {
    $db = $row['Database'];
    if (!in_array($db, ['information_schema', 'mysql', 'performance_schema', 'sys'])) {
        $databases[] = $db;
        echo "- $db<br>";
    }
}
echo "<br>";

// Check each database for employees/users table
foreach ($databases as $db) {
    mysqli_select_db($conn, $db);
    $result = mysqli_query($conn, "SHOW TABLES LIKE '%employee%'");
    $tables = [];
    while ($row = mysqli_fetch_array($result)) {
        $tables[] = $row[0];
    }
    
    if (!empty($tables)) {
        echo "<b>Database '$db' has employee tables:</b> " . implode(', ', $tables) . "<br>";
        
        // Check if employees table has data
        if (in_array('employees', $tables)) {
            $count = mysqli_query($conn, "SELECT COUNT(*) FROM employees");
            $num = mysqli_fetch_array($count)[0];
            echo "   -> $num employees found<br>";
        }
    }
}

mysqli_close($conn);

echo "<br><hr><b>Recommendation:</b><br>";
echo "If the database with your data is different from '$schema', update .env:<br>";
echo "<a href='update_env.php'>Click to update DB_SCHEMA</a>";
?>
