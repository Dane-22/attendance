<?php
// check_mysql.php - Test MySQL credentials
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Testing MySQL Connection</h3>";

if (!function_exists('mysqli_connect')) {
    die("ERROR: MySQLi extension not available");
}

// More password options
$credentials = [
    ['host' => 'localhost', 'user' => 'root', 'pass' => '', 'name' => 'root (no password)'],
    ['host' => 'localhost', 'user' => 'root', 'pass' => 'root', 'name' => 'root / root'],
    ['host' => 'localhost', 'user' => 'root', 'pass' => 'toor', 'name' => 'root / toor'],
    ['host' => 'localhost', 'user' => 'root', 'pass' => 'admin', 'name' => 'root / admin'],
    ['host' => 'localhost', 'user' => 'root', 'pass' => 'password', 'name' => 'root / password'],
    ['host' => 'localhost', 'user' => 'root', 'pass' => 'mysql', 'name' => 'root / mysql'],
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => '', 'name' => '127.0.0.1 root (no password)'],
];

$working = null;
foreach ($credentials as $cred) {
    try {
        $db = @mysqli_connect($cred['host'], $cred['user'], $cred['pass']);
        if ($db) {
            echo "✅ <b>WORKING:</b> {$cred['name']}<br>";
            $working = $cred;
            mysqli_close($db);
            break;
        } else {
            echo "❌ Failed: {$cred['name']}<br>";
        }
    } catch (Exception $e) {
        echo "❌ Failed: {$cred['name']} - " . $e->getMessage() . "<br>";
    }
}

if (!$working) {
    echo "<br><b>All attempts failed.</b><br><br>";
    echo "<b>Quick Fix:</b> Open MySQL console or phpMyAdmin and run:<br>";
    echo "<code>ALTER USER 'root'@'localhost' IDENTIFIED BY ''; FLUSH PRIVILEGES;</code><br><br>";
    echo "Or create a simple fix script:<br>";
    echo "<a href='reset_root_pass.php'>Click here to reset root password to empty</a>";
} else {
    echo "<br><hr><h4>Update your .env file:</h4>";
    echo "DB_HOST=localhost<br>";
    echo "DB_USER=root<br>";
    echo "DB_PASS=" . ($working['pass'] ? $working['pass'] : "") . "<br>";
    echo "DB_SCHEMA=attendance_db<br>";
}
?>
