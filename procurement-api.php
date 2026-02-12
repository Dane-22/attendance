<?php
// procurement-api.php
// Handles API communication with the procurement system

/**
 * Syncs password to the procurement system
 * 
 * @param string $employee_no The employee number (e.g., ENG-2026-0001)
 * @param string $password The plain text new password
 * @return array ['success' => bool, 'message' => string]
 */
function syncPasswordToProcurement($employee_no, $password) {
    $api_url = 'https://procurement-api.xandree.com/api/auth/sync-password/';
    $api_key = 'qwertyuiopasdfghjklzxcvbnm';
    
    // Prepare the request payload
    $payload = json_encode([
        'employee_no' => $employee_no,
        'password' => $password
    ]);
    
    // Initialize cURL
    $ch = curl_init($api_url);
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification for now
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'x-api-key: ' . $api_key,
        'Content-Type: application/json'
    ]);
    
    // Execute request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    
    curl_close($ch);
    
    // Handle cURL errors
    if ($curl_error) {
        return [
            'success' => false,
            'message' => 'Connection failed: ' . $curl_error
        ];
    }
    
    // Parse response
    $response_data = json_decode($response, true);
    
    // Check HTTP status
    if ($http_code >= 200 && $http_code < 300) {
        return [
            'success' => true,
            'message' => $response_data['message'] ?? 'Password synced successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => $response_data['message'] ?? 'Sync failed with HTTP ' . $http_code
        ];
    }
}

/**
 * Logs procurement sync errors for debugging
 * 
 * @param string $message Error message
 * @param array $context Additional context
 */
function logProcurementError($message, $context = []) {
    $log_file = __DIR__ . '/logs/procurement_sync.log';
    
    // Create logs directory if it doesn't exist
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $context_json = json_encode($context);
    $log_entry = "[$timestamp] $message | Context: $context_json\n";
    
    error_log($log_entry, 3, $log_file);
}
