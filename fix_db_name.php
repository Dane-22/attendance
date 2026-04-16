<?php
// fix_db_name.php - Switch to attendance_db

$envPath = __DIR__ . '/.env';

if (!file_exists($envPath)) {
    die(".env file not found!");
}

$lines = file($envPath, FILE_IGNORE_NEW_LINES);
$newLines = [];

foreach ($lines as $line) {
    if (strpos($line, 'DB_SCHEMA=') === 0) {
        $newLines[] = 'DB_SCHEMA=attendance_db';
        echo "Updated: DB_SCHEMA=attendance_db<br>";
    } else {
        $newLines[] = $line;
    }
}

file_put_contents($envPath, implode("\n", $newLines) . "\n");
echo "<br>Done! Now using attendance_db (105 employees)<br><br>";
echo "<a href='/'>Click to go to app</a>";
?>
