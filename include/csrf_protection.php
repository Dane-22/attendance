<?php
/**
 * CSRF Protection Middleware
 * 
 * Provides functions for generating and validating CSRF tokens
 * to prevent Cross-Site Request Forgery attacks
 */

/**
 * Initialize CSRF protection - must be called after session_start()
 * Initializes CSRF token storage in session
 */
function initCsrfProtection() {
    // Initialize CSRF token array in session if not exists
    if (!isset($_SESSION['csrf_tokens'])) {
        $_SESSION['csrf_tokens'] = [];
    }
    
    // Clean up expired tokens (older than 24 hours)
    $now = time();
    $_SESSION['csrf_tokens'] = array_filter($_SESSION['csrf_tokens'], function($token) use ($now) {
        return ($now - $token['created']) < 86400; // 24 hours
    });
}

/**
 * Generate a new CSRF token
 * @param string $form_name Optional form identifier for multiple forms
 * @return string The generated CSRF token
 */
function generateCsrfToken($form_name = 'default') {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new Exception('Session must be started before generating CSRF token');
    }
    
    $token = bin2hex(random_bytes(32)); // 64 character hex string
    
    $_SESSION['csrf_tokens'][$token] = [
        'form' => $form_name,
        'created' => time()
    ];
    
    return $token;
}

/**
 * Get CSRF token for a form (generates if not exists)
 * @param string $form_name Optional form identifier
 * @return string The CSRF token
 */
function getCsrfToken($form_name = 'default') {
    // Check if we already have a valid token for this form
    if (isset($_SESSION['csrf_tokens'])) {
        foreach ($_SESSION['csrf_tokens'] as $token => $data) {
            if ($data['form'] === $form_name && (time() - $data['created']) < 3600) {
                return $token; // Return existing valid token (1 hour validity)
            }
        }
    }
    
    // Generate new token
    return generateCsrfToken($form_name);
}

/**
 * Validate a CSRF token
 * @param string $token The token to validate
 * @param string $form_name Optional form identifier
 * @return bool True if valid, false otherwise
 */
function validateCsrfToken($token, $form_name = 'default') {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return false;
    }
    
    // Check if token exists and is valid
    if (!isset($_SESSION['csrf_tokens'][$token])) {
        return false;
    }
    
    $token_data = $_SESSION['csrf_tokens'][$token];
    
    // Check if token is for the correct form
    if ($token_data['form'] !== $form_name) {
        return false;
    }
    
    // Check if token is expired (24 hours)
    if ((time() - $token_data['created']) > 86400) {
        // Remove expired token
        unset($_SESSION['csrf_tokens'][$token]);
        return false;
    }
    
    // Token is valid - remove it (one-time use for security)
    unset($_SESSION['csrf_tokens'][$token]);
    
    return true;
}

/**
 * Verify CSRF token from request - main validation function for POST requests
 * @param string $form_name Optional form identifier
 * @param bool $exit_on_failure Whether to exit script on validation failure
 * @return bool True if valid
 */
function verifyCsrfToken($form_name = 'default', $exit_on_failure = true) {
    // Get token from POST data or headers
    $token = $_POST['csrf_token'] ?? 
             $_SERVER['HTTP_X_CSRF_TOKEN'] ?? 
             $_SERVER['HTTP_X_XSRF_TOKEN'] ?? 
             null;
    
    if (empty($token)) {
        if ($exit_on_failure) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'CSRF token missing. Please refresh the page and try again.'
            ]);
            exit;
        }
        return false;
    }
    
    if (!validateCsrfToken($token, $form_name)) {
        if ($exit_on_failure) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Invalid or expired CSRF token. Please refresh the page and try again.'
            ]);
            exit;
        }
        return false;
    }
    
    return true;
}

/**
 * Output CSRF token as a hidden input field
 * @param string $form_name Optional form identifier
 * @return string HTML input field
 */
function csrfField($form_name = 'default') {
    $token = getCsrfToken($form_name);
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Output CSRF token as meta tag for JavaScript/AJAX use
 * @param string $form_name Optional form identifier
 * @return string HTML meta tag
 */
function csrfMetaTag($form_name = 'default') {
    $token = getCsrfToken($form_name);
    return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
}

/**
 * Initialize secure session with all security settings
 * Call this at the start of each page that uses sessions
 */
function initSecureSession() {
    // Set session cookie parameters before starting session
    session_set_cookie_params([
        'lifetime' => 7200, // 2 hours
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    
    // Session security settings
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_trans_sid', 0);
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Regenerate session ID periodically for security
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 1800) { // 30 minutes
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
    
    // Initialize CSRF protection
    initCsrfProtection();
}

/**
 * Regenerate session ID on privilege escalation
 * Call this after successful login
 */
function regenerateSession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        // Save session data
        $session_data = $_SESSION;
        
        // Regenerate ID and delete old session
        session_regenerate_id(true);
        
        // Restore session data
        $_SESSION = $session_data;
        $_SESSION['created'] = time();
    }
}
