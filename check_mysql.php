<?php
// check_mysql.php - Test MySQL credentials
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Testing MySQL Connection</h3>";

// Check if mysqli is available
if (!function_exists('mysqli_connect')) {
    die("ERROR: MySQLi extension not available");
}

// Test common credential combinations
$credentials = [
    ['host' => 'localhost', 'user' => 'root', 'pass' => '', 'name' => 'root (no password)'],
    ['host' => 'localhost', 'user' => 'root', 'pass' => 'root', 'name' => 'root / root'],
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => '', 'name' => '127.0.0.1 root (no password)'],
];

foreach ($credentials as $cred) {
    $db = @mysqli_connect($cred['host'], $cred['user'], $cred['pass']);
    if ($db) {
        echo "✅ <b>WORKING:</b> {$cred['name']}<br>";
        echo "Host: {$cred['host']} | User: {$cred['user']}<br><br>";
        mysqli_close($db);
        break;
    } else {
        echo "❌ Failed: {$cred['name']}<br>";
    }
}

if (!isset($db) || !$db) {
    echo "<br><b>All attempts failed. Last error:</b> " . mysqli_connect_error() . "<br>";
    echo "<br>Try one of these fixes:<br>";
    echo "1. Open phpMyAdmin and reset root password<br>";
    echo "2. Run in MySQL: ALTER USER 'root'@'localhost' IDENTIFIED BY ''; FLUSH PRIVILEGES;<br>";
}
?>
