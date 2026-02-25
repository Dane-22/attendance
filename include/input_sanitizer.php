<?php
/**
 * Input Sanitization Utilities
 * 
 * Provides secure input validation and sanitization functions
 */

/**
 * Sanitize input based on type
 * @param mixed $input The input to sanitize
 * @param string $type The type of sanitization to apply
 * @return mixed Sanitized input
 */
function sanitizeInput($input, $type = 'string') {
    if ($input === null) {
        return null;
    }
    
    switch ($type) {
        case 'email':
            $sanitized = filter_var(trim($input), FILTER_SANITIZE_EMAIL);
            return filter_var($sanitized, FILTER_VALIDATE_EMAIL) ? $sanitized : false;
            
        case 'int':
            return filter_var($input, FILTER_VALIDATE_INT);
            
        case 'float':
            return filter_var($input, FILTER_VALIDATE_FLOAT);
            
        case 'url':
            $sanitized = filter_var($input, FILTER_SANITIZE_URL);
            return filter_var($sanitized, FILTER_VALIDATE_URL) ? $sanitized : false;
            
        case 'bool':
            return filter_var($input, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            
        case 'html':
            // Allow basic HTML tags, escape the rest
            $allowedTags = '<p><br><strong><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><a>';
            return strip_tags($input, $allowedTags);
            
        case 'string':
        default:
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Sanitize an array of inputs based on rules
 * @param array $array The input array
 * @param array $rules Key => type mapping
 * @return array Sanitized array
 */
function sanitizeArray($array, $rules = []) {
    $clean = [];
    foreach ($array as $key => $value) {
        $type = $rules[$key] ?? 'string';
        $clean[$key] = sanitizeInput($value, $type);
    }
    return $clean;
}

/**
 * Validate and sanitize file upload
 * @param array $file $_FILES array element
 * @param array $allowedTypes Array of allowed MIME types
 * @param int $maxSize Maximum file size in bytes
 * @return array|bool Array with file info or false on failure
 */
function validateFileUpload($file, $allowedTypes = [], $maxSize = 2097152) {
    // Check upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        return false;
    }
    
    // Verify MIME type if finfo is available
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!empty($allowedTypes) && !in_array($mimeType, $allowedTypes)) {
            return false;
        }
    }
    
    return [
        'name' => sanitizeInput($file['name']),
        'type' => $mimeType ?? $file['type'],
        'size' => $file['size'],
        'tmp_name' => $file['tmp_name']
    ];
}

/**
 * Generate a safe filename for uploads
 * @param string $originalName Original filename
 * @param string $prefix Optional prefix
 * @return string Safe filename
 */
function generateSafeFilename($originalName, $prefix = '') {
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $safeExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
    
    if (!in_array($extension, $safeExtensions)) {
        $extension = 'txt'; // Default to safe extension
    }
    
    $filename = $prefix . bin2hex(random_bytes(16)) . '.' . $extension;
    return $filename;
}

/**
 * Validate password strength
 * @param string $password Password to validate
 * @return array ['valid' => bool, 'errors' => array]
 */
function validatePasswordStrength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $errors[] = 'Password must contain at least one special character';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}
