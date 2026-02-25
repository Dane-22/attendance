<?php
/**
 * CSRF Token Management
 * 
 * Usage:
 * - Include this file in pages with forms
 * - Call generateCsrfToken() to create/retrieve token
 * - Use getCsrfField() to output the hidden input
 * - Call validateCsrfToken($_POST['csrf_token']) on form processing
 */

/**
 * Generate or retrieve CSRF token
 * @return string The CSRF token
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token from request
 * @param string $token The token from the request
 * @return bool True if valid, false otherwise
 */
function validateCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    // Token expires after 1 hour
    if (time() - ($_SESSION['csrf_token_time'] ?? 0) > 3600) {
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF hidden form field HTML
 * @return string HTML input element
 */
function getCsrfField() {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Get CSRF token for AJAX requests
 * @return string The CSRF token
 */
function getCsrfToken() {
    return generateCsrfToken();
}

/**
 * Regenerate CSRF token (call after successful form submission)
 */
function regenerateCsrfToken() {
    unset($_SESSION['csrf_token']);
    unset($_SESSION['csrf_token_time']);
    generateCsrfToken();
}
