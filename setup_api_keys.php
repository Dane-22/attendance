<?php
/**
 * API Key Setup Script
 * 
 * Run this script to auto-generate API keys for all API endpoints
 * Usage: Run via browser or CLI
 */

require_once __DIR__ . '/conn/db_connection.php';
require_once __DIR__ . '/include/api_key_manager.php';

// Security: Only allow from localhost or admin users
$isCli = php_sapi_name() === 'cli';
if (!$isCli) {
    session_start();
    $allowed = false;
    
    // Check if localhost
    if ($_SERVER['REMOTE_ADDR'] === '127.0.0.1' || $_SERVER['REMOTE_ADDR'] === '::1') {
        $allowed = true;
    }
    
    // Check if admin user
    if (isset($_SESSION['position']) && in_array($_SESSION['position'], ['Admin', 'Super Admin'])) {
        $allowed = true;
    }
    
    if (!$allowed) {
        http_response_code(403);
        die('Access denied. This script can only be run from localhost or by admin users.');
    }
}

header('Content-Type: text/plain');

echo "========================================\n";
echo "   API Key Auto-Generation System\n";
echo "========================================\n\n";

// Create table
echo "1. Creating API keys table if not exists...\n";
createApiKeysTable($db);
echo "   Table ready.\n\n";

// Generate keys
$userId = $_SESSION['employee_id'] ?? null;
echo "2. Generating API keys for all endpoints...\n";
$generated = autoGenerateSystemApiKeys($db, $userId);

if (empty($generated)) {
    echo "   No new keys generated (keys may already exist).\n";
} else {
    echo "   Generated " . count($generated) . " API keys:\n\n";
    
    foreach ($generated as $key) {
        echo "   API: {$key['api_name']}\n";
        echo "   File: {$key['file']}\n";
        echo "   Key: {$key['api_key']}\n";
        echo "   ----------------------------------------\n";
    }
}

// Show all existing keys
echo "\n3. All Active API Keys:\n";
$allKeys = getAllApiKeys($db, true);

if (empty($allKeys)) {
    echo "   No active API keys found.\n";
} else {
    foreach ($allKeys as $key) {
        $permissions = is_array($key['permissions']) ? implode(', ', $key['permissions']) : $key['permissions'];
        echo "\n   Name: {$key['api_name']}\n";
        echo "   Key: {$key['api_key']}\n";
        echo "   Permissions: {$permissions}\n";
        echo "   Created: {$key['created_at']}\n";
        if ($key['expires_at']) {
            echo "   Expires: {$key['expires_at']}\n";
        }
    }
}

echo "\n========================================\n";
echo "Setup complete!\n";
echo "========================================\n\n";

if (!$isCli) {
    echo "IMPORTANT: Save these API keys securely. They are shown only once.\n";
    echo "Copy them to your .env file or secure storage.\n\n";
    echo "Example .env format:\n";
    echo "API_KEY_LOGIN=jajr_live_xxxxxxxx...\n";
    echo "API_KEY_CLOCK_IN=jajr_live_xxxxxxxx...\n";
}
