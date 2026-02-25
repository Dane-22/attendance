<?php
/**
 * Secure Session Configuration
 * 
 * Include this file after session_start() to apply secure settings
 */

/**
 * Configure secure session settings
 */
function configureSecureSession() {
    // Only configure if session is active
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return false;
    }
    
    // Session cookie settings
    ini_set('session.cookie_httponly', 1);
    
    // Only set secure flag if HTTPS is available
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.gc_maxlifetime', 3600); // 1 hour
    ini_set('session.cookie_lifetime', 0); // Expire on browser close
    
    // Regenerate session ID periodically to prevent session fixation
    if (isset($_SESSION['last_regeneration'])) {
        if (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    } else {
        $_SESSION['last_regeneration'] = time();
    }
    
    return true;
}

/**
 * Destroy session securely
 */
function destroySecureSession() {
    // Clear session data
    $_SESSION = [];
    
    // Destroy session cookie
    if (isset($_COOKIE[session_name()])) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            [
                'expires' => time() - 3600,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => 'Strict'
            ]
        );
    }
    
    // Destroy session
    session_destroy();
}

// Auto-execute when included
configureSecureSession();
