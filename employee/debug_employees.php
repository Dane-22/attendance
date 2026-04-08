<?php
/**
 * Diagnostic script for employees.php
 * Run this on production to see the exact error
 */

// Enable all error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>Employee Page Diagnostic</h1>";
echo "<pre>";

try {
    echo "Step 1: Starting session...\n";
    session_start();
    echo "✓ Session started\n";
    echo "  Session data: " . print_r($_SESSION, true) . "\n";
    
    echo "\nStep 2: Loading db_connection.php...\n";
    $dbPath = __DIR__ . '/../conn/db_connection.php';
    echo "  Path: $dbPath\n";
    echo "  Exists: " . (file_exists($dbPath) ? 'YES' : 'NO') . "\n";
    require_once $dbPath;
    echo "✓ Database connection loaded\n";
    echo "  DB variable exists: " . (isset($db) ? 'YES' : 'NO') . "\n";
    
    echo "\nStep 3: Loading employees_function.php...\n";
    $funcPath = __DIR__ . '/function/employees_function.php';
    echo "  Path: $funcPath\n";
    echo "  Exists: " . (file_exists($funcPath) ? 'YES' : 'NO') . "\n";
    require_once $funcPath;
    echo "✓ Functions loaded\n";
    
    echo "\nStep 4: Checking key variables...\n";
    echo "  isSuperAdmin: " . (isset($isSuperAdmin) ? ($isSuperAdmin ? 'true' : 'false') : 'NOT SET') . "\n";
    echo "  msg: " . (isset($msg) ? $msg : 'NOT SET') . "\n";
    echo "  totalEmployees: " . (isset($totalEmployees) ? $totalEmployees : 'NOT SET') . "\n";
    echo "  page: " . (isset($page) ? $page : 'NOT SET') . "\n";
    echo "  perPage: " . (isset($perPage) ? $perPage : 'NOT SET') . "\n";
    echo "  currentView: " . (isset($currentView) ? $currentView : 'NOT SET') . "\n";
    echo "  search: " . (isset($search) ? $search : 'NOT SET') . "\n";
    echo "  emps result: " . (isset($emps) ? (is_object($emps) ? 'MySQLi Result' : gettype($emps)) : 'NOT SET') . "\n";
    echo "  totalPages: " . (isset($totalPages) ? $totalPages : 'NOT SET') . "\n";
    
    echo "\nStep 5: Testing sidebar.php load...\n";
    $sidebarPath = __DIR__ . '/sidebar.php';
    echo "  Path: $sidebarPath\n";
    echo "  Exists: " . (file_exists($sidebarPath) ? 'YES' : 'NO') . "\n";
    
    // Try to capture sidebar output
    ob_start();
    include $sidebarPath;
    $sidebarOutput = ob_get_clean();
    echo "✓ Sidebar loaded successfully\n";
    echo "  Sidebar output length: " . strlen($sidebarOutput) . " chars\n";
    
    echo "\n✅ ALL CHECKS PASSED - employees.php should work!\n";
    
} catch (Throwable $e) {
    echo "\n❌ ERROR OCCURRED:\n";
    echo "  Type: " . get_class($e) . "\n";
    echo "  Message: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . "\n";
    echo "  Line: " . $e->getLine() . "\n";
    echo "  Stack Trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "</pre>";

// Also create a minimal test
echo "<hr><h2>Minimal Include Test</h2><pre>";

// Test 1: Just session and DB
echo "Test 1: Session + DB only...\n";
try {
    require_once __DIR__ . '/../conn/db_connection.php';
    echo "  DB loaded, \$db exists: " . (isset($db) ? 'YES' : 'NO') . "\n";
} catch (Throwable $e) {
    echo "  ERROR: " . $e->getMessage() . "\n";
}

// Test 2: Include sidebar with suppressed errors
echo "\nTest 2: Include sidebar with error capture...\n";
$prevErrorHandler = set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo "  WARNING [$errno]: $errstr in $errfile:$errline\n";
    return true; // Suppress standard error handling
});

try {
    include __DIR__ . '/sidebar.php';
    echo "  Sidebar included\n";
    echo "  isAdmin: " . (isset($isAdmin) ? ($isAdmin ? 'true' : 'false') : 'NOT SET') . "\n";
    echo "  isSuperAdmin: " . (isset($isSuperAdmin) ? ($isSuperAdmin ? 'true' : 'false') : 'NOT SET') . "\n";
    echo "  isDeveloper: " . (isset($isDeveloper) ? ($isDeveloper ? 'true' : 'false') : 'NOT SET') . "\n";
} catch (Throwable $e) {
    echo "  ERROR: " . $e->getMessage() . "\n";
}

restore_error_handler();
echo "</pre>";

// Quick link to actual employees page
echo "<hr><p><a href='employees.php' style='font-size: 18px;'>Go to employees.php</a></p>";
