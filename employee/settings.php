<?php
// employee/settings.php
session_start();

// Debug session
error_log("Session Data: " . print_r($_SESSION, true));

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

require('../conn/db_connection.php');

$employeeName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$employeeCode = $_SESSION['employee_code'];
$position = $_SESSION['position'] ?? 'Employee';
$employeeId = $_SESSION['id'] ?? 0;

// Check user role/type for system tools access
$user_role = $_SESSION['user_type'] ?? $_SESSION['role'] ?? $_SESSION['position'] ?? 'Employee';

// Define which roles can access system tools
$allowed_roles_for_system_tools = ['Super Admin', 'Admin', 'Administrator'];
$can_access_system_tools = in_array($user_role, $allowed_roles_for_system_tools);

// DEBUG: Check session variables
error_log("Session ID: " . $employeeId);
error_log("Session First Name: " . $_SESSION['first_name']);
error_log("Session Last Name: " . $_SESSION['last_name']);
error_log("User Role: " . $user_role);
error_log("Can access system tools: " . ($can_access_system_tools ? 'Yes' : 'No'));

// Initialize messages
$success_message = '';
$error_message = '';

// ============ GET CURRENT USER DATA ============
$userData = [];

// First, let's verify the employee ID exists
$checkIdQuery = "SELECT COUNT(*) as count FROM employees WHERE id = ?";
$stmt = mysqli_prepare($db, $checkIdQuery);
mysqli_stmt_bind_param($stmt, "i", $employeeId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
$employeeExists = $row['count'] > 0;
mysqli_stmt_close($stmt);

if ($employeeExists) {
    $userQuery = "SELECT first_name, middle_name, last_name, email, position, profile_image FROM employees WHERE id = ?";
    $stmt = mysqli_prepare($db, $userQuery);
    mysqli_stmt_bind_param($stmt, "i", $employeeId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $userData = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
} else {
    // If employee ID doesn't exist, use session data
    $userData = [
        'first_name' => $_SESSION['first_name'] ?? '',
        'middle_name' => $_SESSION['middle_name'] ?? '',
        'last_name' => $_SESSION['last_name'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'position' => $_SESSION['position'] ?? 'Employee'
    ];
}

// Set default values if they're empty
$userData['first_name'] = $userData['first_name'] ?? '';
$userData['middle_name'] = $userData['middle_name'] ?? '';
$userData['last_name'] = $userData['last_name'] ?? '';
$userData['email'] = $userData['email'] ?? '';
$userData['position'] = $userData['position'] ?? 'Employee';

// DEBUG: Check what data we fetched
error_log("User Data Fetched: " . print_r($userData, true));

// Set default profile image if none exists
$profile_image = !empty($userData['profile_image']) ? '../' . $userData['profile_image'] : '../assets/images/default-avatar.png';

// ============ PROFILE UPDATE HANDLING ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = mysqli_real_escape_string($db, $_POST['first_name'] ?? '');
    $middle_name = mysqli_real_escape_string($db, $_POST['middle_name'] ?? '');
    $last_name = mysqli_real_escape_string($db, $_POST['last_name'] ?? '');
    
    // Validate required fields
    if (empty($first_name) || empty($last_name)) {
        $error_message = "First name and last name are required!";
    } else {
        // Update profile in database
        $updateQuery = "UPDATE employees SET 
                        first_name = ?, 
                        middle_name = ?, 
                        last_name = ?, 
                        updated_at = NOW() 
                        WHERE id = ?";
        
        $stmt = mysqli_prepare($db, $updateQuery);
        mysqli_stmt_bind_param($stmt, "sssi", $first_name, $middle_name, $last_name, $employeeId);
        
        if (mysqli_stmt_execute($stmt)) {
            // Update session variables
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['middle_name'] = $middle_name;
            
            // Also update the $userData array
            $userData['first_name'] = $first_name;
            $userData['middle_name'] = $middle_name;
            $userData['last_name'] = $last_name;
            
            $success_message = "Profile updated successfully!";
        } else {
            $error_message = "Failed to update profile. Please try again.";
        }
        
        mysqli_stmt_close($stmt);
    }
}

// ============ PASSWORD UPDATE HANDLING ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate passwords
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "All password fields are required!";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "New passwords do not match!";
    } elseif (strlen($new_password) < 6) {
        $error_message = "New password must be at least 6 characters long!";
    } else {
        // Get current password hash from database
        $passwordQuery = "SELECT password_hash FROM employees WHERE id = ?";
        $stmt = mysqli_prepare($db, $passwordQuery);
        mysqli_stmt_bind_param($stmt, "i", $employeeId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        
        if ($user) {
            // Verify current password (MD5)
            $current_password_md5 = md5($current_password);
            
            if ($current_password_md5 === $user['password_hash']) {
                // Update with new MD5 password
                $new_password_md5 = md5($new_password);
                $updatePasswordQuery = "UPDATE employees SET password_hash = ?, updated_at = NOW() WHERE id = ?";
                $updateStmt = mysqli_prepare($db, $updatePasswordQuery);
                mysqli_stmt_bind_param($updateStmt, "si", $new_password_md5, $employeeId);
                
                if (mysqli_stmt_execute($updateStmt)) {
                    $success_message = "Password updated successfully!";
                } else {
                    $error_message = "Failed to update password. Please try again.";
                }
                
                mysqli_stmt_close($updateStmt);
            } else {
                $error_message = "Current password is incorrect!";
            }
        }
        
        mysqli_stmt_close($stmt);
    }
}

// ============ PROFILE IMAGE UPLOAD HANDLING ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    $upload_dir = '../uploads/profile_images/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_name = basename($_FILES['profile_image']['name']);
    $file_tmp = $_FILES['profile_image']['tmp_name'];
    $file_size = $_FILES['profile_image']['size'];
    $file_error = $_FILES['profile_image']['error'];
    
    // Get file extension
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Allowed extensions
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
    
    // Check if file is an image
    if (!in_array($file_ext, $allowed_ext)) {
        $error_message = "Only JPG, JPEG, PNG & GIF files are allowed!";
    } elseif ($file_size > 2097152) { // 2MB limit
        $error_message = "File size must be less than 2MB!";
    } elseif ($file_error === 0) {
        // Generate unique filename
        $new_file_name = "profile_" . $employeeId . "_" . time() . "." . $file_ext;
        $file_destination = $upload_dir . $new_file_name;
        
        // Move uploaded file
        if (move_uploaded_file($file_tmp, $file_destination)) {
            // Update database with image path
            $image_path = 'uploads/profile_images/' . $new_file_name;
            $updateImageQuery = "UPDATE employees SET profile_image = ?, updated_at = NOW() WHERE id = ?";
            $stmt = mysqli_prepare($db, $updateImageQuery);
            mysqli_stmt_bind_param($stmt, "si", $image_path, $employeeId);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['profile_image'] = $image_path;
                $userData['profile_image'] = $image_path;
                $profile_image = '../' . $image_path;
                $success_message = "Profile image uploaded successfully!";
            } else {
                $error_message = "Failed to update profile image in database.";
            }
            
            mysqli_stmt_close($stmt);
        } else {
            $error_message = "Failed to upload file. Please try again.";
        }
    }
}

// ============ DATABASE BACKUP FUNCTION (CORRECTED) ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['backup_database'])) {
    // Check if user has permission to backup database
    if (!$can_access_system_tools) {
        $error_message = "You don't have permission to perform this action!";
        error_log("Unauthorized backup attempt by user ID: $employeeId, Role: $user_role");
    } else {
        // Create backup directory if it doesn't exist
        $backup_dir = '../backups/';
        if (!file_exists($backup_dir)) {
            if (!mkdir($backup_dir, 0755, true)) {
                $error_message = "Failed to create backup directory. Please check permissions.";
                error_log("Backup Error: Cannot create directory $backup_dir");
            }
        }
        
        // Check if directory is writable
        if (!is_writable($backup_dir)) {
            $error_message = "Backup directory is not writable. Please check permissions.";
            error_log("Backup Error: Directory $backup_dir is not writable");
        } else {
            // Generate filename with timestamp
            $backup_file = $backup_dir . 'attendance_db_backup_' . date('Y-m-d_H-i-s') . '.sql';
            $handle = fopen($backup_file, 'w');
            
            if ($handle) {
                // Write SQL header
                fwrite($handle, "-- Database Backup\n");
                fwrite($handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
                fwrite($handle, "-- Database: attendance_db\n\n");
                fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");
                
                // Get all tables
                $tables_query = "SHOW TABLES";
                $tables_result = mysqli_query($db, $tables_query);
                
                if ($tables_result) {
                    while ($table_row = mysqli_fetch_array($tables_result)) {
                        $table = $table_row[0];
                        
                        // Get create table statement
                        $create_result = mysqli_query($db, "SHOW CREATE TABLE `$table`");
                        if ($create_result) {
                            $create_row = mysqli_fetch_array($create_result);
                            fwrite($handle, "DROP TABLE IF EXISTS `$table`;\n");
                            fwrite($handle, $create_row[1] . ";\n\n");
                            
                            // Get table data
                            $data_result = mysqli_query($db, "SELECT * FROM `$table`");
                            if ($data_result && mysqli_num_rows($data_result) > 0) {
                                fwrite($handle, "-- Data for table `$table`\n");
                                
                                while ($data_row = mysqli_fetch_assoc($data_result)) {
                                    $columns = implode("`, `", array_keys($data_row));
                                    $values = array_map(function($value) use ($db) {
                                        if (is_null($value)) return 'NULL';
                                        return "'" . mysqli_real_escape_string($db, $value) . "'";
                                    }, array_values($data_row));
                                    
                                    fwrite($handle, "INSERT INTO `$table` (`$columns`) VALUES (" . implode(', ', $values) . ");\n");
                                }
                                fwrite($handle, "\n");
                            }
                        }
                    }
                    
                    fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
                    fclose($handle);
                    
                    // Check if file was created successfully
                    if (file_exists($backup_file) && filesize($backup_file) > 0) {
                        // Set headers for download
                        header('Content-Type: application/octet-stream');
                        header('Content-Disposition: attachment; filename="' . basename($backup_file) . '"');
                        header('Content-Length: ' . filesize($backup_file));
                        header('Content-Transfer-Encoding: binary');
                        header('Cache-Control: must-revalidate');
                        header('Pragma: public');
                        
                        // Clear any previous output
                        ob_clean();
                        flush();
                        
                        readfile($backup_file);
                        
                        // Clean up
                        unlink($backup_file);
                        exit();
                    } else {
                        $error_message = "Backup file was created but appears to be empty.";
                    }
                } else {
                    $error_message = "Failed to retrieve database tables.";
                }
            } else {
                $error_message = "Cannot create backup file. Check directory permissions.";
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Settings — JAJR Company</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
   <link rel="icon" type="image/x-icon" href="../assets/img/profile/jajr-logo.png">

  <style>
    /* DARK ENGINEERING THEME - SETTINGS PAGE */
    
    /* Base Theme Variables */
    :root {
        --charcoal: #0b0b0b;
        --card-gray: #161616;
        --accent-gold: #d4af37;
        --accent-orange: #ff8c42;
        --input-dark: #1f1f1f;
        --border-dark: #333;
        --text-primary: #ffffff;
        --text-secondary: #b0b0b0;
        --text-muted: #888;
        --danger: #dc3545;
        --success: #28a745;
        --radius: 8px;
    }
    
    body.settings-page {
        font-family: 'Inter', sans-serif;
        background: var(--charcoal);
        color: var(--text-primary);
        min-height: 100vh;
        margin: 0;
    }
    
    .app-shell {
        display: flex;
        min-height: 100vh;
    }
    
    /* Main Content */
    .main-content {
        flex: 1;
        padding: 24px;
        overflow-y: auto;
        background: var(--charcoal);
    }
    
    /* Header Card */
    .settings-header {
        background: var(--card-gray);
        border-radius: var(--radius);
        padding: 24px;
        margin-bottom: 24px;
        border: 1px solid var(--border-dark);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }
    
    .header-left {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    
    .menu-toggle {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        padding: 8px;
        color: var(--accent-gold);
        border-radius: 6px;
        transition: all 0.2s;
    }
    
    .menu-toggle:hover {
        background: rgba(212, 175, 55, 0.1);
    }
    
    .welcome {
        font-size: 24px;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 4px;
    }
    
    .text-sm {
        font-size: 14px;
    }
    
    .text-muted {
        color: var(--text-muted);
    }
    
    /* Messages */
    .message-alert {
        padding: 16px;
        border-radius: var(--radius);
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        animation: slideDown 0.3s ease-out;
    }
    
    @keyframes slideDown {
        from {
            transform: translateY(-10px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    .alert-success {
        background: rgba(40, 167, 69, 0.1);
        border: 1px solid rgba(40, 167, 69, 0.3);
        color: var(--success);
    }
    
    .alert-danger {
        background: rgba(220, 53, 69, 0.1);
        border: 1px solid rgba(220, 53, 69, 0.3);
        color: var(--danger);
    }
    
    /* Settings Container */
    .settings-container {
        display: flex;
        gap: 24px;
        background: var(--card-gray);
        border-radius: var(--radius);
        border: 1px solid var(--border-dark);
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }
    
    /* Vertical Tabs */
    .settings-tabs {
        width: 240px;
        background: var(--input-dark);
        border-right: 1px solid var(--border-dark);
        padding: 24px 0;
    }
    
    .tab-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 24px;
        color: var(--text-secondary);
        text-decoration: none;
        border-left: 3px solid transparent;
        transition: all 0.2s;
        cursor: pointer;
    }
    
    .tab-link:hover {
        background: rgba(212, 175, 55, 0.05);
        color: var(--text-primary);
    }
    
    .tab-link.active {
        background: rgba(212, 175, 55, 0.1);
        color: var(--accent-gold);
        border-left-color: var(--accent-gold);
    }
    
    .tab-link.admin-only {
        background: linear-gradient(135deg, rgba(212, 175, 55, 0.1), rgba(255, 140, 66, 0.1));
        border-left: 3px solid var(--accent-orange);
    }
    
    .tab-link.admin-only .tab-icon {
        color: var(--accent-orange);
    }
    
    .tab-icon {
        font-size: 18px;
        width: 24px;
        text-align: center;
    }
    
    /* Settings Content */
    .settings-content {
        flex: 1;
        padding: 32px;
    }
    
    .tab-pane {
        display: none;
    }
    
    .tab-pane.active {
        display: block;
        animation: fadeIn 0.3s ease-out;
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .section-title {
        font-size: 20px;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 8px;
    }
    
    .section-subtitle {
        font-size: 14px;
        color: var(--text-muted);
        margin-bottom: 24px;
    }
    
    /* Profile Image Upload */
    .profile-image-section {
        text-align: center;
        margin-bottom: 32px;
    }
    
    .avatar-container {
        position: relative;
        display: inline-block;
        margin-bottom: 16px;
    }
    
    .avatar-preview {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--accent-gold);
        box-shadow: 0 0 20px rgba(212, 175, 55, 0.3);
        transition: all 0.3s;
    }
    
    .avatar-preview:hover {
        box-shadow: 0 0 30px rgba(212, 175, 55, 0.5);
    }
    
    .avatar-upload-btn {
        position: absolute;
        bottom: 10px;
        right: 10px;
        background: linear-gradient(135deg, var(--accent-gold), #ff8c42);
        color: var(--charcoal);
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        transition: all 0.3s;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
    }
    
    .avatar-upload-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
    }
    
    .upload-instructions {
        font-size: 12px;
        color: var(--text-muted);
        margin-top: 8px;
    }
    
    /* Form Styles */
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: var(--text-primary);
        font-size: 14px;
    }
    
    .form-input, .form-select, .form-textarea {
        width: 100%;
        padding: 12px 16px;
        background: var(--input-dark);
        border: 1px solid var(--border-dark);
        border-radius: var(--radius);
        color: var(--text-primary);
        font-size: 14px;
        font-family: 'Inter', sans-serif;
        transition: all 0.2s;
    }
    
    .form-input:focus, .form-select:focus, .form-textarea:focus {
        outline: none;
        border-color: var(--accent-gold);
        box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
    }
    
    .form-input::placeholder {
        color: var(--text-muted);
    }
    
    .form-textarea {
        min-height: 100px;
        resize: vertical;
    }
    
    .password-strength {
        height: 4px;
        background: var(--border-dark);
        border-radius: 2px;
        margin-top: 8px;
        overflow: hidden;
    }
    
    .strength-bar {
        height: 100%;
        width: 0%;
        border-radius: 2px;
        transition: width 0.3s;
    }
    
    .strength-weak { background: var(--danger); width: 33%; }
    .strength-medium { background: #ffc107; width: 66%; }
    .strength-strong { background: var(--success); width: 100%; }
    
    .password-hint {
        font-size: 12px;
        color: var(--text-muted);
        margin-top: 4px;
    }
    
    /* System Tools */
    .system-tools {
        background: var(--input-dark);
        border-radius: var(--radius);
        padding: 24px;
        border: 1px solid var(--border-dark);
    }
    
    .tool-card {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 20px;
        background: var(--card-gray);
        border-radius: var(--radius);
        border: 1px solid var(--border-dark);
        margin-bottom: 16px;
        transition: all 0.2s;
    }
    
    .tool-card:hover {
        border-color: var(--accent-gold);
        transform: translateY(-2px);
    }
    
    .tool-icon {
        font-size: 24px;
        color: var(--accent-gold);
        width: 40px;
        text-align: center;
    }
    
    .tool-content {
        flex: 1;
    }
    
    .tool-title {
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 4px;
    }
    
    .tool-description {
        font-size: 13px;
        color: var(--text-muted);
    }
    
    /* Buttons */
    .btn {
        padding: 12px 24px;
        border-radius: var(--radius);
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s;
        border: none;
        font-family: 'Inter', sans-serif;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, var(--accent-gold), #ff8c42);
        color: var(--charcoal);
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(212, 175, 55, 0.3);
    }
    
    .btn-danger {
        background: linear-gradient(135deg, var(--danger), #c82333);
        color: white;
    }
    
    .btn-danger:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(220, 53, 69, 0.3);
    }
    
    .btn-block {
        width: 100%;
    }
    
    .btn-sm {
        padding: 8px 16px;
        font-size: 13px;
    }
    
    /* File Upload */
    .file-upload-wrapper {
        position: relative;
        overflow: hidden;
        display: inline-block;
    }
    
    .file-upload-wrapper input[type=file] {
        position: absolute;
        left: 0;
        top: 0;
        opacity: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        .settings-container {
            flex-direction: column;
        }
        
        .settings-tabs {
            width: 100%;
            border-right: none;
            border-bottom: 1px solid var(--border-dark);
        }
        
        .tab-link {
            justify-content: center;
        }
        
        .form-grid {
            grid-template-columns: 1fr;
        }
        
        .main-content {
            padding: 16px;
        }
    }
    
    @media (max-width: 480px) {
        .avatar-preview {
            width: 120px;
            height: 120px;
        }
        
        .settings-content {
            padding: 20px;
        }
    }
    
    /* Password Visibility Toggle */
    .password-toggle {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: var(--text-muted);
        cursor: pointer;
        font-size: 16px;
    }
    
    .password-wrapper {
        position: relative;
    }
    
    /* Danger Zone */
    .danger-zone {
        background: rgba(220, 53, 69, 0.05);
        border: 1px solid rgba(220, 53, 69, 0.3);
        border-radius: var(--radius);
        padding: 24px;
        margin-top: 32px;
    }
    
    .danger-zone-title {
        color: var(--danger);
        font-weight: 700;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
  </style>
</head>
<body class="settings-page">
  <div class="app-shell">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="main-content">
      <!-- Debug Info (Remove after testing) -->
      <div style="background: #1f1f1f; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #d4af37; color: #fff; font-size: 12px; display: none;">
        <strong>Debug Information:</strong><br>
        Employee ID: <?php echo $employeeId; ?><br>
        First Name: <?php echo htmlspecialchars($userData['first_name'] ?? 'Not found'); ?><br>
        Middle Name: <?php echo htmlspecialchars($userData['middle_name'] ?? 'Not found'); ?><br>
        Last Name: <?php echo htmlspecialchars($userData['last_name'] ?? 'Not found'); ?><br>
        Email: <?php echo htmlspecialchars($userData['email'] ?? 'Not found'); ?><br>
        Position: <?php echo htmlspecialchars($userData['position'] ?? 'Not found'); ?><br>
        User Role: <?php echo htmlspecialchars($user_role); ?><br>
        Can Access System Tools: <?php echo $can_access_system_tools ? 'Yes' : 'No'; ?>
      </div>

      <!-- Messages -->
      <?php if ($success_message): ?>
      <div class="message-alert alert-success">
        <i class="fas fa-check-circle"></i>
        <span><?php echo htmlspecialchars($success_message); ?></span>
      </div>
      <?php endif; ?>
      
      <?php if ($error_message): ?>
      <div class="message-alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <span><?php echo htmlspecialchars($error_message); ?></span>
      </div>
      <?php endif; ?>

      <!-- Header -->
      <div class="settings-header">
        <div class="header-left">
          <button id="sidebarToggle" class="menu-toggle" aria-label="Toggle sidebar">
            <i class="fas fa-bars"></i>
          </button>
          <div>
            <div class="welcome">Settings & Account</div>
            <div class="text-sm text-muted">
                Manage your profile, security, and system preferences
            </div>
          </div>
        </div>
      </div>

      <!-- Settings Container -->
      <div class="settings-container">
        <!-- Vertical Tabs -->
        <div class="settings-tabs">
          <a href="#profile" class="tab-link active" onclick="switchTab('profile', event)">
            <i class="fas fa-user-circle tab-icon"></i>
            <span>Profile</span>
          </a>
          <a href="#security" class="tab-link" onclick="switchTab('security', event)">
            <i class="fas fa-shield-alt tab-icon"></i>
            <span>Security</span>
          </a>
          
          <?php if ($can_access_system_tools): ?>
          <a href="#system" class="tab-link admin-only" onclick="switchTab('system', event)">
            <i class="fas fa-cogs tab-icon"></i>
            <span>System Tools</span>
            <span style="font-size: 10px; margin-left: 8px; color: var(--accent-orange);">(Admin)</span>
          </a>
          <?php endif; ?>
        </div>

        <!-- Settings Content -->
        <div class="settings-content">
          <!-- Profile Tab -->
          <div id="profile-tab" class="tab-pane active">
            <div class="section-title">Profile Information</div>
            <div class="section-subtitle">Update your personal details and profile picture</div>
            
            <!-- Profile Image Upload -->
            <div class="profile-image-section">
              <div class="avatar-container">
                <img src="<?php echo $profile_image; ?>" alt="Profile" class="avatar-preview" id="avatarPreview">
                <label class="avatar-upload-btn">
                  <i class="fas fa-camera"></i>
                </label>
              </div>
              <form method="POST" enctype="multipart/form-data" id="imageUploadForm">
                <div class="file-upload-wrapper">
                  <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('profileImageInput').click()">
                    <i class="fas fa-upload"></i> Upload New Photo
                  </button>
                  <input type="file" name="profile_image" id="profileImageInput" accept="image/*" style="display: none;" onchange="uploadImage()">
                </div>
              </form>
              <div class="upload-instructions">
                Supported formats: JPG, PNG, GIF | Max size: 2MB
              </div>
            </div>

            <!-- Profile Form -->
            <form method="POST" action="">
              <input type="hidden" name="update_profile" value="1">
              
              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label">First Name *</label>
                  <input type="text" name="first_name" class="form-input" 
                         value="<?php echo isset($userData['first_name']) ? htmlspecialchars($userData['first_name']) : ''; ?>" 
                         required
                         placeholder="Enter your first name">
                </div>
                
                <div class="form-group">
                  <label class="form-label">Middle Name</label>
                  <input type="text" name="middle_name" class="form-input" 
                         value="<?php echo isset($userData['middle_name']) ? htmlspecialchars($userData['middle_name']) : ''; ?>"
                         placeholder="Enter your middle name (optional)">
                </div>
                
                <div class="form-group">
                  <label class="form-label">Last Name *</label>
                  <input type="text" name="last_name" class="form-input" 
                         value="<?php echo isset($userData['last_name']) ? htmlspecialchars($userData['last_name']) : ''; ?>" 
                         required
                         placeholder="Enter your last name">
                </div>
              </div>
              
              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label">Employee Code</label>
                  <input type="text" class="form-input" 
                         value="<?php echo htmlspecialchars($employeeCode); ?>" 
                         disabled style="background: #2a2a2a;">
                </div>
                
                <div class="form-group">
                  <label class="form-label">Email Address</label>
                  <input type="email" class="form-input" 
                         value="<?php echo isset($userData['email']) ? htmlspecialchars($userData['email']) : ''; ?>" 
                         disabled style="background: #2a2a2a;">
                </div>
                
                <div class="form-group">
                  <label class="form-label">Position</label>
                  <input type="text" class="form-input" 
                         value="<?php echo isset($userData['position']) ? htmlspecialchars($userData['position']) : ''; ?>" 
                         disabled style="background: #2a2a2a;">
                </div>
              </div>
              
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Changes
              </button>
            </form>
          </div>

          <!-- Security Tab -->
          <div id="security-tab" class="tab-pane">
            <div class="section-title">Security Settings</div>
            <div class="section-subtitle">Update your password and manage account security</div>
            
            <!-- Password Update Form -->
            <form method="POST" action="" id="passwordForm">
              <input type="hidden" name="update_password" value="1">
              
              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label">Current Password *</label>
                  <div class="password-wrapper">
                    <input type="password" name="current_password" id="currentPassword" 
                           class="form-input" required>
                    <button type="button" class="password-toggle" 
                            onclick="togglePassword('currentPassword')">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                </div>
              </div>
              
              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label">New Password *</label>
                  <div class="password-wrapper">
                    <input type="password" name="new_password" id="newPassword" 
                           class="form-input" required onkeyup="checkPasswordStrength()">
                    <button type="button" class="password-toggle" 
                            onclick="togglePassword('newPassword')">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                  <div class="password-strength">
                    <div class="strength-bar" id="passwordStrength"></div>
                  </div>
                  <div class="password-hint" id="passwordHint">
                    Password must be at least 6 characters long
                  </div>
                </div>
                
                <div class="form-group">
                  <label class="form-label">Confirm New Password *</label>
                  <div class="password-wrapper">
                    <input type="password" name="confirm_password" id="confirmPassword" 
                           class="form-input" required>
                    <button type="button" class="password-toggle" 
                            onclick="togglePassword('confirmPassword')">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                  <div class="password-hint" id="passwordMatch"></div>
                </div>
              </div>
              
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-key"></i> Update Password
              </button>
            </form>
            
            <!-- Danger Zone -->
            <div class="danger-zone">
              <div class="danger-zone-title">
                <i class="fas fa-exclamation-triangle"></i>
                Danger Zone
              </div>
              <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 16px;">
                Once you delete your account, there is no going back. Please be certain.
              </p>
              <button class="btn btn-danger" onclick="showDeleteConfirmation()">
                <i class="fas fa-trash-alt"></i> Delete Account
              </button>
            </div>
          </div>

          <!-- System Tools Tab (Admin Only) -->
          <?php if ($can_access_system_tools): ?>
          <div id="system-tab" class="tab-pane">
            <div class="section-title">System Tools (Admin Only)</div>
            <div class="section-subtitle">Database maintenance and system utilities</div>
            
            <div class="system-tools">
              <!-- Database Backup -->
              <div class="tool-card">
                <div class="tool-icon">
                  <i class="fas fa-database"></i>
                </div>
                <div class="tool-content">
                  <div class="tool-title">Database Backup</div>
                  <div class="tool-description">
                    Generate and download a complete backup of the attendance database.
                    This includes all employee records and attendance data.
                  </div>
                </div>
                <form method="POST" action="">
                  <input type="hidden" name="backup_database" value="1">
                  <button type="submit" class="btn btn-primary" onclick="return confirm('Are you sure you want to generate a database backup?')">
                    <i class="fas fa-download"></i> Generate Backup
                  </button>
                </form>
              </div>
              
              <!-- System Info -->
              <div class="tool-card">
                <div class="tool-icon">
                  <i class="fas fa-info-circle"></i>
                </div>
                <div class="tool-content">
                  <div class="tool-title">System Information</div>
                  <div class="tool-description">
                    <div style="margin-bottom: 4px;">
                      <strong>PHP Version:</strong> <?php echo phpversion(); ?>
                    </div>
                    <div style="margin-bottom: 4px;">
                      <strong>MySQL Version:</strong> 
                      <?php   
                      $version = mysqli_get_server_info($db);
                      echo $version ?: 'Unknown';
                      ?>
                    </div>
                    <div>
                      <strong>Server Time:</strong> <?php echo date('Y-m-d H:i:s'); ?>
                    </div>
                    <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid var(--border-dark);">
                      <strong>Current User Role:</strong> <?php echo htmlspecialchars($user_role); ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </main>
  </div>

  <script src="../assets/employee.js"></script>
  <script>
    // Tab switching
    function switchTab(tabName, event) {
      // Hide all tabs
      document.querySelectorAll('.tab-pane').forEach(tab => {
        tab.classList.remove('active');
      });
      
      document.querySelectorAll('.tab-link').forEach(tab => {
        tab.classList.remove('active');
      });
      
      // Show selected tab
      const targetTab = document.getElementById(tabName + '-tab');
      if (targetTab) {
        targetTab.classList.add('active');
      }
      
      // Activate tab button
      event.currentTarget.classList.add('active');
    }

    // Image upload
    function uploadImage() {
      const input = document.getElementById('profileImageInput');
      const preview = document.getElementById('avatarPreview');
      
      if (input.files && input.files[0]) {
        // Preview image
        const reader = new FileReader();
        reader.onload = function(e) {
          preview.src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
        
        // Submit the form
        document.getElementById('imageUploadForm').submit();
      }
    }

    // Password visibility toggle
    function togglePassword(inputId) {
      const input = document.getElementById(inputId);
      const toggle = input.parentElement.querySelector('.password-toggle i');
      
      if (input.type === 'password') {
        input.type = 'text';
        toggle.classList.remove('fa-eye');
        toggle.classList.add('fa-eye-slash');
      } else {
        input.type = 'password';
        toggle.classList.remove('fa-eye-slash');
        toggle.classList.add('fa-eye');
      }
    }

    // Password strength checker
    function checkPasswordStrength() {
      const password = document.getElementById('newPassword').value;
      const strengthBar = document.getElementById('passwordStrength');
      const hint = document.getElementById('passwordHint');
      const confirm = document.getElementById('confirmPassword');
      const matchHint = document.getElementById('passwordMatch');
      
      // Reset
      strengthBar.className = 'strength-bar';
      hint.style.color = '';
      
      if (password.length === 0) {
        strengthBar.style.width = '0%';
        hint.textContent = 'Password must be at least 6 characters long';
        return;
      }
      
      if (password.length < 6) {
        strengthBar.className = 'strength-bar strength-weak';
        hint.textContent = 'Password is too short';
        hint.style.color = '#dc3545';
      } else if (password.length < 10) {
        strengthBar.className = 'strength-bar strength-medium';
        hint.textContent = 'Password is okay, but could be stronger';
        hint.style.color = '#ffc107';
      } else {
        strengthBar.className = 'strength-bar strength-strong';
        hint.textContent = 'Strong password!';
        hint.style.color = '#28a745';
      }
      
      // Check if passwords match
      if (confirm.value && password !== confirm.value) {
        matchHint.textContent = 'Passwords do not match';
        matchHint.style.color = '#dc3545';
      } else if (confirm.value) {
        matchHint.textContent = 'Passwords match';
        matchHint.style.color = '#28a745';
      } else {
        matchHint.textContent = '';
      }
    }

    // Confirm password match
    document.getElementById('confirmPassword').addEventListener('keyup', function() {
      const newPassword = document.getElementById('newPassword').value;
      const confirmPassword = this.value;
      const matchHint = document.getElementById('passwordMatch');
      
      if (confirmPassword.length === 0) {
        matchHint.textContent = '';
      } else if (newPassword === confirmPassword) {
        matchHint.textContent = 'Passwords match';
        matchHint.style.color = '#28a745';
      } else {
        matchHint.textContent = 'Passwords do not match';
        matchHint.style.color = '#dc3545';
      }
    });

    // Delete account confirmation
    function showDeleteConfirmation() {
      if (confirm('⚠️ WARNING: This action cannot be undone!\n\nAre you sure you want to delete your account? All your data will be permanently removed.')) {
        alert('Account deletion requested. Please contact your administrator for this action.');
      }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize password strength
      checkPasswordStrength();
      
      // Set first tab as active
      const firstTabLink = document.querySelector('.tab-link');
      if (firstTabLink) {
        const firstTabId = firstTabLink.getAttribute('href').substring(1);
        const firstTab = document.getElementById(firstTabId + '-tab');
        if (firstTab) {
          firstTab.classList.add('active');
        }
      }
      
      // Auto-close messages after 5 seconds
      setTimeout(() => {
        const messages = document.querySelectorAll('.message-alert');
        messages.forEach(msg => {
          msg.style.opacity = '0';
          msg.style.transition = 'opacity 0.3s';
          setTimeout(() => msg.remove(), 300);
        });
      }, 5000);
      
      // Add form validation
      const passwordForm = document.getElementById('passwordForm');
      if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
          const newPass = document.getElementById('newPassword').value;
          const confirmPass = document.getElementById('confirmPassword').value;
          
          if (newPass !== confirmPass) {
            e.preventDefault();
            alert('Passwords do not match!');
            return false;
          }
          
          if (newPass.length < 6) {
            e.preventDefault();
            alert('Password must be at least 6 characters long!');
            return false;
          }
        });
      }
    });
  </script>
</body>
</html>