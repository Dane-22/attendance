<?php
// fix_db_pass.php - Reset DB password to empty (common for WAMP)

$envPath = __DIR__ . '/.env';

if (!file_exists($envPath)) {
    die(".env file not found!");
}

$lines = file($envPath, FILE_IGNORE_NEW_LINES);
$newLines = [];

foreach ($lines as $line) {
    if (strpos($line, 'DB_PASS=') === 0) {
        $newLines[] = 'DB_PASS=';  // Empty password
        echo "Updated: DB_PASS=(empty)<br>";
    } else {
        $newLines[] = $line;
    }
}

file_put_contents($envPath, implode("\n", $newLines) . "\n");
echo "Done! <a href='/'>Click to test</a>";
?>
