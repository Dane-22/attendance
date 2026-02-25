<?php
/**
 * API Key Management System
 * 
 * Provides functions for generating, validating, and managing API keys
 * Auto-generates API keys for API endpoints
 */

/**
 * Create API keys table if not exists
 * @param mysqli $db Database connection
 */
function createApiKeysTable($db) {
    $sql = "CREATE TABLE IF NOT EXISTS api_keys (
        id INT AUTO_INCREMENT PRIMARY KEY,
        api_key VARCHAR(64) NOT NULL UNIQUE,
        api_name VARCHAR(100) NOT NULL,
        description TEXT,
        permissions JSON,
        is_active TINYINT(1) DEFAULT 1,
        rate_limit INT DEFAULT 1000,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_used_at TIMESTAMP NULL,
        expires_at TIMESTAMP NULL,
        INDEX idx_api_key (api_key),
        INDEX idx_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    mysqli_query($db, $sql);
}

/**
 * Generate a cryptographically secure API key
 * @param string $prefix Optional prefix (e.g., 'jajr_live_', 'jajr_test_')
 * @return string The generated API key
 */
function generateApiKey($prefix = 'jajr_live_') {
    $bytes = random_bytes(32);
    $key = bin2hex($bytes);
    return $prefix . $key;
}

/**
 * Store a new API key in the database
 * @param mysqli $db Database connection
 * @param string $apiName Name of the API/service
 * @param string $description Optional description
 * @param array $permissions Array of allowed endpoints
 * @param int $createdBy User ID who created the key
 * @param int|null $expiresInDays Days until expiration (null for no expiry)
 * @return array ['success' => bool, 'api_key' => string|false, 'message' => string]
 */
function storeApiKey($db, $apiName, $description = '', $permissions = [], $createdBy = null, $expiresInDays = null) {
    // Generate new key
    $apiKey = generateApiKey();
    
    // Create table if not exists
    createApiKeysTable($db);
    
    // Prepare permissions JSON
    $permissionsJson = json_encode($permissions);
    
    // Calculate expiration
    $expiresAt = null;
    if ($expiresInDays !== null) {
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiresInDays} days"));
    }
    
    // Insert into database
    $stmt = mysqli_prepare($db, "INSERT INTO api_keys (api_key, api_name, description, permissions, created_by, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'ssssis', $apiKey, $apiName, $description, $permissionsJson, $createdBy, $expiresAt);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return [
            'success' => true,
            'api_key' => $apiKey,
            'message' => 'API key generated successfully'
        ];
    } else {
        mysqli_stmt_close($stmt);
        return [
            'success' => false,
            'api_key' => false,
            'message' => 'Failed to store API key: ' . mysqli_error($db)
        ];
    }
}

/**
 * Validate an API key
 * @param mysqli $db Database connection
 * @param string $apiKey The API key to validate
 * @param string $endpoint Optional endpoint to check permissions for
 * @return array ['valid' => bool, 'message' => string, 'data' => array|null]
 */
function validateApiKey($db, $apiKey, $endpoint = null) {
    // Check if table exists
    $tableCheck = mysqli_query($db, "SHOW TABLES LIKE 'api_keys'");
    if (mysqli_num_rows($tableCheck) === 0) {
        return ['valid' => false, 'message' => 'API key system not initialized', 'data' => null];
    }
    
    // Query for the API key
    $stmt = mysqli_prepare($db, "SELECT * FROM api_keys WHERE api_key = ? AND is_active = 1");
    mysqli_stmt_bind_param($stmt, 's', $apiKey);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $keyData = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$keyData) {
        return ['valid' => false, 'message' => 'Invalid or inactive API key', 'data' => null];
    }
    
    // Check expiration
    if ($keyData['expires_at'] && strtotime($keyData['expires_at']) < time()) {
        return ['valid' => false, 'message' => 'API key has expired', 'data' => null];
    }
    
    // Check endpoint permissions if specified
    if ($endpoint !== null) {
        $permissions = json_decode($keyData['permissions'], true) ?: [];
        
        // If permissions is empty array, allow all endpoints
        if (!empty($permissions) && !in_array($endpoint, $permissions) && !in_array('*', $permissions)) {
            return ['valid' => false, 'message' => 'API key not authorized for this endpoint', 'data' => null];
        }
    }
    
    // Update last used timestamp
    $updateStmt = mysqli_prepare($db, "UPDATE api_keys SET last_used_at = NOW() WHERE id = ?");
    mysqli_stmt_bind_param($updateStmt, 'i', $keyData['id']);
    mysqli_stmt_execute($updateStmt);
    mysqli_stmt_close($updateStmt);
    
    return [
        'valid' => true,
        'message' => 'API key valid',
        'data' => $keyData
    ];
}

/**
 * Auto-generate API keys for all system APIs
 * @param mysqli $db Database connection
 * @param int $createdBy User ID creating the keys
 * @return array Generated keys information
 */
function autoGenerateSystemApiKeys($db, $createdBy = null) {
    $apiDefinitions = [
        ['name' => 'Login API', 'endpoint' => 'login_api', 'file' => 'login_api.php'],
        ['name' => 'Clock In API', 'endpoint' => 'time_in_api', 'file' => 'time_in_api.php'],
        ['name' => 'Clock Out API', 'endpoint' => 'time_out_api', 'file' => 'time_out_api.php'],
        ['name' => 'Clock Out API 2', 'endpoint' => 'clock_out_api', 'file' => 'clock_out_api.php'],
        ['name' => 'QR Clock API', 'endpoint' => 'qr_clock_api', 'file' => 'qr_clock_api.php'],
        ['name' => 'Attendance Submit API', 'endpoint' => 'submit_attendance_api', 'file' => 'submit_attendance_api.php'],
        ['name' => 'Get Branches API', 'endpoint' => 'get_branches_api', 'file' => 'get_branches_api.php'],
        ['name' => 'Get Branch API', 'endpoint' => 'get_branch_api', 'file' => 'get_branch_api.php'],
        ['name' => 'Employees Status API', 'endpoint' => 'employees_today_status_api', 'file' => 'employees_today_status_api.php'],
        ['name' => 'Available Employees API', 'endpoint' => 'get_available_employees_api', 'file' => 'get_available_employees_api.php'],
        ['name' => 'Shift Logs API', 'endpoint' => 'get_shift_logs_api', 'file' => 'get_shift_logs_api.php'],
        ['name' => 'Mark Absent API', 'endpoint' => 'mark_attendance_absent_api', 'file' => 'mark_attendance_absent_api.php'],
        ['name' => 'Absent Notes API', 'endpoint' => 'get_attendance_absent_notes_api', 'file' => 'get_attendance_absent_notes_api.php'],
        ['name' => 'Set OT Hours API', 'endpoint' => 'set_attendance_ot_hrs_api', 'file' => 'set_attendance_ot_hrs_api.php'],
        ['name' => 'Transfer Branch API', 'endpoint' => 'transfer_branch_api', 'file' => 'transfer_branch_api.php'],
        ['name' => 'Set Employee Branch API', 'endpoint' => 'set_employee_branch_api', 'file' => 'set_employee_branch_api.php'],
        ['name' => 'Update Profile API', 'endpoint' => 'update_profile_api', 'file' => 'update_profile_api.php'],
        ['name' => 'Change Password API', 'endpoint' => 'change-password-api', 'file' => 'change-password-api.php'],
        ['name' => 'Cash Advance Summary API', 'endpoint' => 'cash_advance_summary', 'file' => 'cash_advance_summary.php'],
        ['name' => 'Overtime Requests API', 'endpoint' => 'get_overtime_requests', 'file' => 'get_overtime_requests.php'],
        ['name' => 'Procurement API', 'endpoint' => 'procurement-api', 'file' => 'procurement-api.php'],
        ['name' => 'Send Branches API', 'endpoint' => 'send_branches', 'file' => 'send_branches.php'],
        ['name' => 'Master API Key', 'endpoint' => '*', 'file' => 'all_apis'],
    ];
    
    $generatedKeys = [];
    
    foreach ($apiDefinitions as $api) {
        // Check if key already exists for this API
        $checkStmt = mysqli_prepare($db, "SELECT id FROM api_keys WHERE api_name = ? AND is_active = 1");
        mysqli_stmt_bind_param($checkStmt, 's', $api['name']);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_store_result($checkStmt);
        
        if (mysqli_stmt_num_rows($checkStmt) === 0) {
            // Generate new key
            $result = storeApiKey(
                $db,
                $api['name'],
                'Auto-generated API key for ' . $api['file'],
                [$api['endpoint']],
                $createdBy,
                null // No expiration
            );
            
            if ($result['success']) {
                $generatedKeys[] = [
                    'api_name' => $api['name'],
                    'api_key' => $result['api_key'],
                    'endpoint' => $api['endpoint'],
                    'file' => $api['file']
                ];
            }
        }
        mysqli_stmt_close($checkStmt);
    }
    
    return $generatedKeys;
}

/**
 * Get all API keys
 * @param mysqli $db Database connection
 * @param bool $activeOnly Only return active keys
 * @return array Array of API key records
 */
function getAllApiKeys($db, $activeOnly = true) {
    $sql = "SELECT * FROM api_keys";
    if ($activeOnly) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY created_at DESC";
    
    $result = mysqli_query($db, $sql);
    $keys = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $row['permissions'] = json_decode($row['permissions'], true);
        $keys[] = $row;
    }
    
    return $keys;
}

/**
 * Revoke an API key
 * @param mysqli $db Database connection
 * @param int $keyId The API key ID to revoke
 * @return bool True on success, false on failure
 */
function revokeApiKey($db, $keyId) {
    $stmt = mysqli_prepare($db, "UPDATE api_keys SET is_active = 0 WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $keyId);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

/**
 * Delete an API key permanently
 * @param mysqli $db Database connection
 * @param int $keyId The API key ID to delete
 * @return bool True on success, false on failure
 */
function deleteApiKey($db, $keyId) {
    $stmt = mysqli_prepare($db, "DELETE FROM api_keys WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $keyId);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}
