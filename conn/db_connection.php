<?php
// conn/db_connection.php

date_default_timezone_set('Asia/Manila');

// Load environment variables from .env file
$envPath = dirname(__DIR__) . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Split by the first equals sign
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            if (preg_match('/^(["\'])(.*)\1$/m', $value, $matches)) {
                $value = $matches[2];
            }
            
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// Get values with defaults
$host = getenv('DB_HOST');
$user_name = getenv('DB_USER');
$passwd = getenv('DB_PASS') ;
$schema = getenv('DB_SCHEMA');

// Debug: Uncomment to check values (remove in production)
// echo "Host: $host<br>";
// echo "User: $user_name<br>";
// echo "Schema: $schema<br>";

// Create connection
try {
    $db = mysqli_connect($host, $user_name, $passwd, $schema);
    
    // Check connection
    if (!$db) {
        throw new Exception("Connection failed: " . mysqli_connect_error());
    }

    // Ensure DB session uses PHT (UTC+08:00) for NOW()/CURDATE()
    @mysqli_query($db, "SET time_zone = '+08:00'");
    
    // Set charset to ensure proper JSON encoding
    mysqli_set_charset($db, 'utf8mb4');
    
    // Optional: Verify connection
    // echo "Connected successfully to database: $schema";
    
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}
?>