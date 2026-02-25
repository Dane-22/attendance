<?php
/**
 * API Key Validation Middleware
 * 
 * Include this at the top of all API files to enforce API key authentication
 * Supports API key via: Header (X-API-Key), POST parameter (api_key), or GET parameter (api_key)
 */

require_once __DIR__ . '/api_key_manager.php';

/**
 * Validate API key for the current request
 * @param mysqli $db Database connection
 * @param string $endpoint Optional endpoint name for permission checking
 * @return array|null Returns null if valid, or error array if invalid
 */
function requireApiKey($db, $endpoint = null) {
    // Get API key from various sources
    $apiKey = null;
    
    // 1. Check header
    $headers = getallheaders();
    if (isset($headers['X-API-Key'])) {
        $apiKey = $headers['X-API-Key'];
    } elseif (isset($headers['x-api-key'])) {
        $apiKey = $headers['x-api-key'];
    }
    
    // 2. Check POST
    if (!$apiKey && isset($_POST['api_key'])) {
        $apiKey = $_POST['api_key'];
    }
    
    // 3. Check GET
    if (!$apiKey && isset($_GET['api_key'])) {
        $apiKey = $_GET['api_key'];
    }
    
    // 4. Check Authorization header (Bearer token format)
    if (!$apiKey && isset($headers['Authorization'])) {
        $auth = $headers['Authorization'];
        if (strpos($auth, 'Bearer ') === 0) {
            $apiKey = substr($auth, 7);
        }
    }
    
    // No API key provided
    if (!$apiKey) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'API key required. Provide via X-API-Key header, api_key parameter, or Authorization: Bearer header.'
        ]);
        exit;
    }
    
    // Validate the API key
    $validation = validateApiKey($db, $apiKey, $endpoint);
    
    if (!$validation['valid']) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $validation['message']
        ]);
        exit;
    }
    
    // Store validated key data for potential use in the API
    $GLOBALS['__validated_api_key'] = $validation['data'];
    
    return null;
}

/**
 * Get the currently validated API key data
 * @return array|null API key data if validated, null otherwise
 */
function getValidatedApiKey() {
    return $GLOBALS['__validated_api_key'] ?? null;
}

/**
 * Check if request has valid API key without enforcing it
 * @param mysqli $db Database connection
 * @param string $endpoint Optional endpoint name
 * @return array Validation result
 */
function checkApiKey($db, $endpoint = null) {
    // Get API key from various sources
    $apiKey = null;
    
    $headers = getallheaders();
    if (isset($headers['X-API-Key'])) {
        $apiKey = $headers['X-API-Key'];
    } elseif (isset($headers['x-api-key'])) {
        $apiKey = $headers['x-api-key'];
    } elseif (isset($_POST['api_key'])) {
        $apiKey = $_POST['api_key'];
    } elseif (isset($_GET['api_key'])) {
        $apiKey = $_GET['api_key'];
    } elseif (isset($headers['Authorization'])) {
        $auth = $headers['Authorization'];
        if (strpos($auth, 'Bearer ') === 0) {
            $apiKey = substr($auth, 7);
        }
    }
    
    if (!$apiKey) {
        return ['valid' => false, 'message' => 'No API key provided'];
    }
    
    return validateApiKey($db, $apiKey, $endpoint);
}
