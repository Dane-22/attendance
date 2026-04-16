<?php
// fix_db_user.php - Update DB_USER in .env file

$envPath = __DIR__ . '/.env';

if (!file_exists($envPath)) {
    die(".env file not found!");
}

// Read current .env
$lines = file($envPath, FILE_IGNORE_NEW_LINES);
$newLines = [];
$found = false;

foreach ($lines as $line) {
    if (strpos($line, 'DB_USER=') === 0) {
        $newLines[] = 'DB_USER=root';
        $found = true;
        echo "Updated: DB_USER=root\n";
    } else {
        $newLines[] = $line;
    }
}

if (!$found) {
    $newLines[] = 'DB_USER=root';
    echo "Added: DB_USER=root\n";
}

// Write back
file_put_contents($envPath, implode("\n", $newLines) . "\n");
echo "Done! Refresh the page to test.";
?>
