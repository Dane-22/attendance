<?php
// check_mysql.php - Test MySQL credentials

echo "<h3>Testing MySQL Connection</h3>";

// Test common credential combinations
$credentials = [
    ['host' => 'localhost', 'user' => 'root', 'pass' => '', 'name' => 'root (no password)'],
    ['host' => 'localhost', 'user' => 'root', 'pass' => 'root', 'name' => 'root / root'],
    ['host' => 'localhost', 'user' => 'root', 'pass' => 'password', 'name' => 'root / password'],
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => '', 'name' => '127.0.0.1 root (no password)'],
];

foreach ($credentials as $cred) {
    $db = @mysqli_connect($cred['host'], $cred['user'], $cred['pass']);
    if ($db) {
        echo "✅ <b>WORKING:</b> {$cred['name']}<br>";
        echo "Host: {$cred['host']} | User: {$cred['user']} | Pass: '{$cred['pass']}'<br><br>";
        mysqli_close($db);
    } else {
        echo "❌ Failed: {$cred['name']} - " . mysqli_connect_error() . "<br>";
    }
}

echo "<br><hr><h4>Update .env with working credentials:</h4>";
echo "DB_HOST=localhost<br>";
echo "DB_USER=root<br>";
echo "DB_PASS=<i>your_password</i><br>";
echo "DB_SCHEMA=attendance_db<br>";
?>
