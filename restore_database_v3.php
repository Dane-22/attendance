<?php
// restore_database_v3.php - Robust SQL restore with proper trigger handling
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);

$host = 'localhost';
$user = 'root';
$pass = '';
$database = 'attendance_db';
$sqlFile = __DIR__ . '/attendance_db (2).sql';

echo "=== Database Restore v3 ===\n\n";

if (!file_exists($sqlFile)) {
    die("❌ SQL file not found: $sqlFile\n");
}

$fileSize = filesize($sqlFile);
echo "✅ Found SQL file: " . number_format($fileSize / 1024, 2) . " KB\n";

// Connect to MySQL
$conn = @mysqli_connect($host, $user, $pass);
if (!$conn) {
    die("❌ Cannot connect to MySQL: " . mysqli_connect_error() . "\n");
}
echo "✅ Connected to MySQL\n";

// Get MySQL version
$versionResult = mysqli_query($conn, "SELECT VERSION()");
$versionRow = mysqli_fetch_array($versionResult);
echo "ℹ️  MySQL Version: " . $versionRow[0] . "\n";

// Drop and recreate database
echo "\n📦 Setting up database...\n";
mysqli_query($conn, "DROP DATABASE IF EXISTS `$database`");
if (!mysqli_query($conn, "CREATE DATABASE `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
    die("❌ Failed to create database: " . mysqli_error($conn) . "\n");
}
echo "✅ Database created: $database\n";

// Select database
mysqli_select_db($conn, $database);

// Read the entire SQL file
$sql = file_get_contents($sqlFile);

// Remove BOM if present
$sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);

// Normalize line endings
$sql = str_replace(["\r\n", "\r"], "\n", $sql);

// Parse SQL statements respecting DELIMITER changes
$statements = [];
$currentDelimiter = ';';
$currentStatement = '';
$inDelimiterBlock = false;

$lines = explode("\n", $sql);
$lineCount = count($lines);

echo "📄 Parsing $lineCount lines...\n";

for ($i = 0; $i < $lineCount; $i++) {
    $line = $lines[$i];
    $trimmed = trim($line);
    
    // Skip empty lines and pure comment lines
    if ($trimmed === '' || preg_match('/^--\s/', $trimmed) || preg_match('/^\/\*/', $trimmed)) {
        continue;
    }
    
    // Handle DELIMITER command
    if (preg_match('/^DELIMITER\s+(.+)$/i', $trimmed, $matches)) {
        $currentDelimiter = trim($matches[1]);
        continue; // Don't add DELIMITER command as a statement
    }
    
    // Add line to current statement
    $currentStatement .= $line . "\n";
    
    // Check if statement is complete (ends with current delimiter)
    if (substr($trimmed, -strlen($currentDelimiter)) === $currentDelimiter) {
        // Remove the delimiter from the end
        $cleanStmt = substr($currentStatement, 0, -strlen($currentDelimiter));
        $cleanStmt = trim($cleanStmt);
        
        if (!empty($cleanStmt)) {
            $statements[] = $cleanStmt;
        }
        
        $currentStatement = '';
    }
}

$totalStatements = count($statements);
echo "📋 Found $totalStatements statements to execute\n\n";

// Execute statements
$success = 0;
$failed = 0;
$errors = [];

// Skip CREATE DATABASE and USE statements (we already handled that)
$skipPatterns = [
    '/^CREATE\s+DATABASE/i',
    '/^USE\s+`/i',
    '/^USE\s+\w/i',
];

echo "⏳ Executing statements...\n";

foreach ($statements as $idx => $stmt) {
    // Check if we should skip this statement
    $shouldSkip = false;
    foreach ($skipPatterns as $pattern) {
        if (preg_match($pattern, $stmt)) {
            $shouldSkip = true;
            break;
        }
    }
    if ($shouldSkip) {
        continue;
    }
    
    // Execute the statement
    if (@mysqli_query($conn, $stmt)) {
        $success++;
    } else {
        $failed++;
        $error = mysqli_error($conn);
        if (count($errors) < 10) {
            $errors[] = [
                'stmt' => substr($stmt, 0, 100) . (strlen($stmt) > 100 ? '...' : ''),
                'error' => $error
            ];
        }
    }
    
    // Progress indicator every 50 statements
    if (($idx + 1) % 50 === 0) {
        echo "  Progress: " . ($idx + 1) . "/$totalStatements\n";
    }
}

echo "\n" . str_repeat("=", 40) . "\n";
echo "✅ Execution complete!\n";
echo "   Successful: $success\n";
echo "   Failed: $failed\n";

if (!empty($errors)) {
    echo "\n⚠️  First " . count($errors) . " errors:\n";
    foreach ($errors as $e) {
        echo "   SQL: " . $e['stmt'] . "\n";
        echo "   Error: " . $e['error'] . "\n\n";
    }
}

// Verify results
echo "\n" . str_repeat("=", 40) . "\n";
echo "📊 Verification:\n";

$result = mysqli_query($conn, "SHOW TABLES");
if ($result) {
    $tables = [];
    while ($row = mysqli_fetch_array($result)) {
        $tables[] = $row[0];
    }
    echo "   Tables created: " . count($tables) . "\n";
    
    // Check key tables
    $keyTables = ['employees', 'attendance', 'branches', 'activity_logs'];
    foreach ($keyTables as $table) {
        if (in_array($table, $tables)) {
            $countRes = mysqli_query($conn, "SELECT COUNT(*) FROM `$table`");
            $count = mysqli_fetch_array($countRes)[0];
            echo "   ✓ $table: $count rows\n";
        } else {
            echo "   ✗ $table: MISSING\n";
        }
    }
} else {
    echo "   ❌ Could not retrieve tables\n";
}

mysqli_close($conn);

echo "\n" . str_repeat("=", 40) . "\n";
echo "Done!\n";
?>
