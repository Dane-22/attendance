<?php
// employee/employees.php
require_once __DIR__ . '/../conn/db_connection.php';
session_start();

// ===== RATE LIMITER CONFIGURATION =====
$rateLimitEnabled = false; // Set to true pag working na lahat
$rateLimitWindow = 60; // 60 seconds
$rateLimitMaxRequests = 30; // Maximum requests per window

function checkRateLimit() {
    global $rateLimitEnabled, $rateLimitWindow, $rateLimitMaxRequests;
    
    if (!$rateLimitEnabled) return true;
    
    // Huwag gamitin ang session_start() dito, naka-start na sa taas
    $currentTime = time();
    $userId = $_SESSION['employee_code'] ?? 'anonymous';
    $rateLimitKey = "ratelimit_$userId";
    
    if (!isset($_SESSION[$rateLimitKey])) {
        $_SESSION[$rateLimitKey] = [
            'count' => 1,
            'window_start' => $currentTime
        ];
        return true;
    }
    
    $rateData = $_SESSION[$rateLimitKey];
    
    // Reset if window has passed
    if ($currentTime - $rateData['window_start'] > $rateLimitWindow) {
        $_SESSION[$rateLimitKey] = [
            'count' => 1,
            'window_start' => $currentTime
        ];
        return true;
    }
    
    // Check if limit exceeded
    if ($rateData['count'] >= $rateLimitMaxRequests) {
        return false;
    }
    
    // Increment count
    $_SESSION[$rateLimitKey]['count']++;
    return true;
}

// ===== PAGINATION CONFIGURATION =====
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = isset($_GET['per_page']) ? max(1, min(100, intval($_GET['per_page']))) : 10;
$offset = ($page - 1) * $perPage;

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Apply rate limiting for POST requests
    if (!checkRateLimit()) {
        $msg = 'Rate limit exceeded. Please wait a minute.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $employee_code = trim($_POST['employee_code'] ?? '');
            $first_name = trim($_POST['first_name'] ?? '');
            $middle_name = trim($_POST['middle_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $position = trim($_POST['position'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if ($employee_code && $first_name && $last_name) {
                // Check if employee code already exists
                $check = mysqli_prepare($db, "SELECT id FROM employees WHERE employee_code = ?");
                mysqli_stmt_bind_param($check, 's', $employee_code);
                mysqli_stmt_execute($check);
                mysqli_stmt_store_result($check);
                
                if (mysqli_stmt_num_rows($check) > 0) {
                    $msg = 'Error: Employee code already exists.';
                } else {
                    $hash = md5($password ?: 'password');
                    $ins = mysqli_prepare($db, "INSERT INTO employees (employee_code, first_name, middle_name, last_name, email, position, password_hash, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'Active', NOW())");
                    mysqli_stmt_bind_param($ins, 'sssssss', $employee_code, $first_name, $middle_name, $last_name, $email, $position, $hash);
                    if (mysqli_stmt_execute($ins)) {
                        $msg = 'Employee added successfully.';
                    } else {
                        $msg = 'Error adding employee: ' . mysqli_error($db);
                    }
                }
                mysqli_stmt_close($check);
            } else {
                $msg = 'Please provide employee code and name.';
            }
        }

        if ($action === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                $del = mysqli_prepare($db, "DELETE FROM employees WHERE id = ?");
                mysqli_stmt_bind_param($del, 'i', $id);
                if (mysqli_stmt_execute($del)) {
                    $msg = 'Employee removed.';
                } else {
                    $msg = 'Error removing employee: ' . mysqli_error($db);
                }
            }
        }

        if ($action === 'update') {
            $id = intval($_POST['id'] ?? 0);
            $employee_code = trim($_POST['employee_code'] ?? '');
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $position = trim($_POST['position'] ?? '');
            
            if ($id > 0) {
                // Check if the new employee code conflicts with another employee
                $check = mysqli_prepare($db, "SELECT id FROM employees WHERE employee_code = ? AND id != ?");
                mysqli_stmt_bind_param($check, 'si', $employee_code, $id);
                mysqli_stmt_execute($check);
                mysqli_stmt_store_result($check);
                
                if (mysqli_stmt_num_rows($check) > 0) {
                    $msg = 'Error: Employee code already exists for another employee.';
                } else {
                    $up = mysqli_prepare($db, "UPDATE employees SET employee_code = ?, first_name = ?, last_name = ?, email = ?, position = ? WHERE id = ?");
                    mysqli_stmt_bind_param($up, 'sssssi', $employee_code, $first_name, $last_name, $email, $position, $id);
                    if (mysqli_stmt_execute($up)) {
                        $msg = 'Employee updated.';
                        
                        // Handle profile image upload if provided
                        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                            $file = $_FILES['profile_image'];
                            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                            $max_size = 5 * 1024 * 1024; // 5MB
                            
                            if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
                                // Generate unique filename
                                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                                $unique_name = uniqid('profile_', true) . '.' . $extension;
                                $upload_path = __DIR__ . '/uploads/' . $unique_name;
                                
                                // Create uploads directory if it doesn't exist
                                $upload_dir = __DIR__ . '/uploads/';
                                if (!is_dir($upload_dir)) {
                                    mkdir($upload_dir, 0755, true);
                                }
                                
                                // Get current profile image to delete old one
                                $current_image = null;
                                $get_current = mysqli_prepare($db, "SELECT profile_image FROM employees WHERE id = ?");
                                mysqli_stmt_bind_param($get_current, 'i', $id);
                                mysqli_stmt_execute($get_current);
                                
                                // NA-AYOS NA: Bind result muna bago mag-fetch
                                mysqli_stmt_bind_result($get_current, $current_image);
                                if (mysqli_stmt_fetch($get_current)) {
                                    // Successfully fetched the current image
                                }
                                mysqli_stmt_close($get_current);
                                
                                // Delete old profile image if exists
                                if ($current_image && file_exists(__DIR__ . '/uploads/' . $current_image)) {
                                    unlink(__DIR__ . '/uploads/' . $current_image);
                                }
                                
                                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                                    // Update database with new image
                                    $update_img = mysqli_prepare($db, "UPDATE employees SET profile_image = ?, updated_at = NOW() WHERE id = ?");
                                    mysqli_stmt_bind_param($update_img, 'si', $unique_name, $id);
                                    mysqli_stmt_execute($update_img);
                                    mysqli_stmt_close($update_img);
                                    $msg .= ' Profile image updated.';
                                } else {
                                    $msg .= ' Failed to save profile image.';
                                }
                            } else {
                                $msg .= ' Invalid profile image file.';
                            }
                        }
                    } else {
                        $msg = 'Error updating employee: ' . mysqli_error($db);
                    }
                }
                mysqli_stmt_close($check);
            }
        }

        if ($action === 'upload_profile') {
            $id = intval($_POST['employee_id'] ?? 0);
            
            if ($id > 0 && isset($_FILES['profile_image'])) {
                // Include the upload logic here or call the upload script
                $file = $_FILES['profile_image'];
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                if ($file['error'] === UPLOAD_ERR_OK && in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
                    // Generate unique filename
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $unique_name = uniqid('profile_', true) . '.' . $extension;
                    $upload_path = __DIR__ . '/uploads/' . $unique_name;
                    
                    // Create uploads directory if it doesn't exist
                    $upload_dir = __DIR__ . '/uploads/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    // Get current profile image to delete old one
                    $current_image = null;
                    $get_current = mysqli_prepare($db, "SELECT profile_image FROM employees WHERE id = ?");
                    mysqli_stmt_bind_param($get_current, 'i', $id);
                    mysqli_stmt_execute($get_current);
                    
                    // NA-AYOS NA: Bind result muna bago mag-fetch
                    mysqli_stmt_bind_result($get_current, $current_image);
                    if (mysqli_stmt_fetch($get_current)) {
                        // Successfully fetched the current image
                    }
                    mysqli_stmt_close($get_current);
                    
                    // Delete old profile image if exists
                    if ($current_image && file_exists(__DIR__ . '/uploads/' . $current_image)) {
                        unlink(__DIR__ . '/uploads/' . $current_image);
                    }
                    
                    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                        // Update database
                        $update = mysqli_prepare($db, "UPDATE employees SET profile_image = ?, updated_at = NOW() WHERE id = ?");
                        mysqli_stmt_bind_param($update, 'si', $unique_name, $id);
                        if (mysqli_stmt_execute($update)) {
                            $msg = 'Profile image updated successfully.';
                        } else {
                            // If database update fails, delete the uploaded file
                            if (file_exists($upload_path)) {
                                unlink($upload_path);
                            }
                            $msg = 'Failed to update database.';
                        }
                        mysqli_stmt_close($update);
                    } else {
                        $msg = 'Failed to save file.';
                    }
                } else {
                    $msg = 'Invalid file. Only JPG, PNG, GIF, and WebP files up to 5MB are allowed.';
                }
            } else {
                $msg = 'Invalid request.';
            }
        }
    }
}

// Get current view preference from session or default to grid
$currentView = $_SESSION['employee_view'] ?? 'details';

// Handle view change request
if (isset($_GET['view'])) {
    $view = $_GET['view'];
    if (in_array($view, ['list', 'details'])) {
        $_SESSION['employee_view'] = $view;
        $currentView = $view;
    }
}

// Get total count of employees
$countResult = mysqli_query($db, "SELECT COUNT(*) as total FROM employees");
$countRow = mysqli_fetch_assoc($countResult);
$totalEmployees = $countRow['total'];
$totalPages = ceil($totalEmployees / $perPage);

// Get employees with pagination
$emps = mysqli_query($db, "SELECT * FROM employees ORDER BY last_name, first_name LIMIT $perPage OFFSET $offset");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Employee List — JAJR</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="icon" type="image/x-icon" href="../assets/img/profile/jajr-logo.png">
  <style>
    /* ===== ENHANCED EDIT FORM MODAL STYLES ===== */
    .edit-form-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.8);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .edit-form-container {
        background: #1a1a1a;
        border: 2px solid #FFD700;
        border-radius: 16px;
        width: 100%;
        max-width: 700px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 10px 40px rgba(255, 215, 0, 0.2);
    }

    .edit-form-header {
        padding: 24px;
        border-bottom: 1px solid rgba(255, 215, 0, 0.3);
        background: rgba(0, 0, 0, 0.3);
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .edit-form-header h3 {
        margin: 0;
        color: #FFD700;
        font-size: 24px;
        font-weight: 700;
    }

    .employee-id-display {
        background: rgba(255, 215, 0, 0.1);
        color: #FFD700;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
        display: inline-block;
        margin-top: 8px;
    }

    .edit-form-body {
        padding: 24px;
    }

    .form-section {
        margin-bottom: 32px;
        padding-bottom: 24px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .form-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .section-title {
        font-size: 18px;
        color: #FFD700;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 1px solid rgba(255, 215, 0, 0.3);
        font-weight: 600;
    }

    /* .form-row-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 16px;
    } */

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-label {
        color: #ffffff;
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .form-label.required::after {
        content: " *";
        color: #ff4444;
    }

    .form-input {
        width: 100%;
        padding: 12px 16px;
        background: rgba(0, 0, 0, 0.5);
        border: 2px solid rgba(255, 215, 0, 0.3);
        border-radius: 8px;
        color: #ffffff;
        font-size: 14px;
        transition: all 0.3s ease;
    }

    .form-input:focus {
        outline: none;
        border-color: #FFD700;
        box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
    }

    .form-input:disabled {
        background: rgba(0, 0, 0, 0.3);
        border-color: rgba(255, 215, 0, 0.1);
        color: rgba(255, 255, 255, 0.5);
        cursor: not-allowed;
    }

    .form-select {
        width: 100%;
        padding: 12px 16px;
        background: rgba(0, 0, 0, 0.5);
        border: 2px solid rgba(255, 215, 0, 0.3);
        border-radius: 8px;
        color: #ffffff;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .form-select:focus {
        outline: none;
        border-color: #FFD700;
        box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
    }

    .form-select option {
        background: #1a1a1a;
        color: #ffffff;
    }

    .profile-image-upload {
        display: flex;
        align-items: center;
        gap: 20px;
        margin-top: 16px;
    }

    .profile-image-preview {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        border: 3px solid #FFD700;
        overflow: hidden;
        background: linear-gradient(135deg, #FFD700, #FFA500);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #0b0b0b;
        font-size: 24px;
    }

    .profile-image-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .file-upload-area {
        flex: 1;
    }

    .file-input-wrapper {
        position: relative;
        overflow: hidden;
        display: inline-block;
        width: 100%;
    }

    .file-input-wrapper input[type=file] {
        position: absolute;
        left: 0;
        top: 0;
        opacity: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
    }

    .file-input-label {
        display: block;
        padding: 12px 16px;
        background: rgba(255, 215, 0, 0.1);
        border: 2px dashed rgba(255, 215, 0, 0.3);
        border-radius: 8px;
        color: #FFD700;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .file-input-label:hover {
        background: rgba(255, 215, 0, 0.2);
        border-color: #FFD700;
    }

    .file-info {
        font-size: 12px;
        color: rgba(255, 255, 255, 0.6);
        margin-top: 8px;
        text-align: center;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 16px;
        padding-top: 24px;
        margin-top: 24px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .btn-cancel {
        padding: 12px 24px;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 8px;
        color: #ffffff;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-cancel:hover {
        background: rgba(255, 255, 255, 0.2);
        border-color: #ffffff;
    }

    .btn-save {
        padding: 12px 24px;
        background: linear-gradient(135deg, #FFD700, #FFA500);
        border: none;
        border-radius: 8px;
        color: #0b0b0b;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
    }

    .close-btn {
        position: absolute;
        top: 20px;
        right: 20px;
        background: none;
        border: none;
        color: #ffffff;
        font-size: 24px;
        cursor: pointer;
        padding: 8px;
        border-radius: 50%;
        transition: all 0.3s ease;
    }

    .close-btn:hover {
        background: rgba(255, 255, 255, 0.1);
        color: #FFD700;
    }

    /* ===== ENHANCED GRID VIEW ===== */
    /* .employees-grid-view {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 24px;
    } */

    /* .employee-card-grid {
        background: rgba(20, 20, 20, 0.9);
        border: 2px solid rgba(255, 215, 0, 0.2);
        border-radius: 16px;
        padding: 24px;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        cursor: pointer;
    } */

    /* .employee-card-grid:hover {
        transform: translateY(-8px);
        border-color: #FFD700;
        box-shadow: 0 12px 24px rgba(255, 215, 0, 0.2);
    } */

    /* .employee-badge-grid {
        position: absolute;
        top: 20px;
        right: 20px;
        background: rgba(255, 215, 0, 0.1);
        color: #FFD700;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.5px;
    } */

    /* .employee-card-grid .card-header {
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 20px;
    } */

    /* .employee-card-grid .avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        border: 3px solid #FFD700;
        overflow: hidden;
        flex-shrink: 0;
    }

    .employee-card-grid .avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .employee-card-grid .avatar .initials {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #FFD700, #FFA500);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #0b0b0b;
        font-size: 28px;
    }

    .employee-card-grid .employee-info {
        flex: 1;
    }

    .employee-card-grid .employee-name {
        color: #ffffff;
        font-size: 20px;
        font-weight: 700;
        margin: 0 0 8px 0;
        line-height: 1.3;
    }

    .employee-card-grid .employee-position {
        color: #FFD700;
        font-size: 14px;
        font-weight: 600;
        margin: 0 0 6px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .employee-card-grid .employee-email {
        color: rgba(255, 255, 255, 0.7);
        font-size: 14px;
        margin: 0;
        word-break: break-all;
    }

    .employee-status {
        display: inline-block;
        padding: 4px 12px;
        background: rgba(74, 222, 128, 0.1);
        color: #4ade80;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        margin-top: 8px;
    }

    .employee-card-grid .card-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid rgba(255, 215, 0, 0.2);
    } */

    .action-btn {
        padding: 10px 16px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        border: none;
    }

    .action-btn-edit {
        background: rgba(255, 215, 0, 0.1);
        color: #FFD700;
        border: 1px solid rgba(255, 215, 0, 0.3);
    }

    .action-btn-edit:hover {
        background: rgba(255, 215, 0, 0.2);
        border-color: #FFD700;
        transform: translateY(-2px);
    }

    .action-btn-delete {
        background: rgba(255, 68, 68, 0.1);
        color: #ff4444;
        border: 1px solid rgba(255, 68, 68, 0.3);
    }

    .action-btn-delete:hover {
        background: rgba(255, 68, 68, 0.2);
        border-color: #ff4444;
        transform: translateY(-2px);
    }

    /* ===== ENHANCED LIST VIEW ===== */
    .employees-list-view {
        background: rgba(20, 20, 20, 0.8);
        border-radius: 12px;
        border: 1px solid rgba(255, 215, 0, 0.2);
        overflow: hidden;
    }

    .list-header {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr auto;
        padding: 16px 24px;
        background: rgba(0, 0, 0, 0.5);
        border-bottom: 1px solid rgba(255, 215, 0, 0.3);
        color: #FFD700;
        font-weight: 600;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .employee-row {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr auto;
        padding: 16px 24px;
        align-items: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.3s ease;
    }

    .employee-row:hover {
        background: rgba(255, 215, 0, 0.05);
    }

    .employee-row:last-child {
        border-bottom: none;
    }

    .employee-row-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: 2px solid #FFD700;
        overflow: hidden;
        margin-right: 12px;
        display: inline-block;
        vertical-align: middle;
    }

    .employee-row-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .employee-row-avatar .initials {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #FFD700, #FFA500);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #0b0b0b;
        font-size: 16px;
    }

    .employee-row-info {
        display: inline-block;
        vertical-align: middle;
    }

    .employee-row-name {
        color: #ffffff;
        font-weight: 600;
        font-size: 15px;
        margin-bottom: 4px;
    }

    .employee-row-email {
        color: rgba(255, 255, 255, 0.6);
        font-size: 13px;
    }

    .employee-row-position,
    .employee-row-status,
    .employee-row-code {
        color: rgba(255, 255, 255, 0.8);
        font-size: 14px;
    }

    .employee-row-actions {
        display: flex;
        gap: 8px;
    }

    .row-action-btn {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        border: none;
        background: transparent;
        color: rgba(255, 255, 255, 0.7);
        font-size: 16px;
    }

    .row-action-btn:hover {
        transform: translateY(-2px);
    }

    .row-action-edit:hover {
        background: rgba(255, 215, 0, 0.1);
        color: #FFD700;
    }

    .row-action-delete:hover {
        background: rgba(255, 68, 68, 0.1);
        color: #ff4444;
    }

    /* ===== ENHANCED DETAILS VIEW ===== */
    .employees-details-view {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
        gap: 24px;
    }

    .employee-card-details {
        background: rgba(20, 20, 20, 0.9);
        border: 2px solid rgba(255, 215, 0, 0.2);
        border-radius: 16px;
        padding: 32px;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .employee-card-details:hover {
        border-color: #FFD700;
        box-shadow: 0 8px 24px rgba(255, 215, 0, 0.15);
    }

    .employee-badge-details {
        position: absolute;
        top: 24px;
        right: 24px;
        background: rgba(255, 215, 0, 0.1);
        color: #FFD700;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 700;
    }

    .details-header {
        display: flex;
        align-items: center;
        gap: 24px;
        margin-bottom: 32px;
        padding-bottom: 24px;
        border-bottom: 1px solid rgba(255, 215, 0, 0.3);
    }

    .details-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        border: 3px solid #FFD700;
        overflow: hidden;
        flex-shrink: 0;
    }

    .details-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .details-avatar .initials {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #FFD700, #FFA500);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #0b0b0b;
        font-size: 36px;
    }

    .details-header-info {
        flex: 1;
    }

    .details-name {
        color: #ffffff;
        font-size: 28px;
        font-weight: 700;
        margin: 0 0 12px 0;
        line-height: 1.2;
    }

    .details-position {
        color: #FFD700;
        font-size: 18px;
        font-weight: 600;
        margin: 0 0 8px 0;
    }

    .details-email {
        color: rgba(255, 255, 255, 0.7);
        font-size: 16px;
        margin: 0;
    }

    .details-body {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 24px;
        margin-bottom: 32px;
    }

    .detail-item {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .detail-label {
        font-size: 12px;
        color: rgba(255, 215, 0, 0.7);
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 600;
    }

    .detail-value {
        font-size: 16px;
        color: #ffffff;
        font-weight: 500;
    }

    .details-actions {
        display: flex;
        justify-content: flex-end;
        gap: 16px;
        padding-top: 24px;
        margin-top: 24px;
        border-top: 1px solid rgba(255, 215, 0, 0.2);
    }

    /* ===== VIEW OPTIONS ENHANCEMENT ===== */
    .view-options-container {
        background: rgba(20, 20, 20, 0.9);
        border: 2px solid rgba(255, 215, 0, 0.3);
        border-radius: 16px;
        padding: 20px 24px;
        margin-bottom: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }

    .view-options-title {
        font-size: 18px;
        font-weight: 700;
        color: #FFD700;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .view-options {
        display: flex;
        gap: 12px;
    }

    .view-option-btn {
        padding: 12px 24px;
        background: rgba(30, 30, 30, 0.8);
        border: 2px solid rgba(255, 215, 0, 0.3);
        border-radius: 10px;
        color: #888;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }

    .view-option-btn:hover {
        border-color: rgba(255, 215, 0, 0.5);
        color: #FFD700;
        transform: translateY(-2px);
    }

    .view-option-btn.active {
        background: linear-gradient(135deg, #FFD700, #FFA500);
        border-color: #FFD700;
        color: #000;
        box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
    }

    /* ===== PAGINATION ENHANCEMENT ===== */
    .pagination-container {
        background: rgba(20, 20, 20, 0.9);
        border: 2px solid rgba(255, 215, 0, 0.3);
        border-radius: 16px;
        padding: 20px 24px;
        margin: 24px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }

    .pagination-info {
        font-size: 15px;
        color: rgba(255, 255, 255, 0.8);
    }

    .pagination-info strong {
        color: #FFD700;
        font-weight: 700;
    }

    .pagination-controls {
        display: flex;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }

    .page-size-selector {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .page-size-label {
        font-size: 14px;
        color: rgba(255, 255, 255, 0.7);
    }

    .page-size-select {
        background: rgba(30, 30, 30, 0.8);
        border: 2px solid rgba(255, 215, 0, 0.3);
        border-radius: 8px;
        padding: 8px 16px;
        color: #ffffff;
        font-size: 14px;
        cursor: pointer;
        min-width: 80px;
        transition: all 0.3s ease;
    }

    .page-size-select:focus {
        outline: none;
        border-color: #FFD700;
        box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
    }

    .pagination-buttons {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .page-btn {
        min-width: 40px;
        height: 40px;
        padding: 0 8px;
        background: rgba(30, 30, 30, 0.8);
        border: 2px solid rgba(255, 215, 0, 0.3);
        border-radius: 8px;
        color: #ffffff;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }

    .page-btn:hover:not(:disabled):not(.active) {
        border-color: rgba(255, 215, 0, 0.5);
        color: #FFD700;
        transform: translateY(-2px);
    }

    .page-btn.active {
        background: linear-gradient(135deg, #FFD700, #FFA500);
        border-color: #FFD700;
        color: #000000;
        font-weight: 700;
        box-shadow: 0 4px 8px rgba(255, 215, 0, 0.3);
    }

    .page-btn:disabled {
        background: rgba(20, 20, 20, 0.5);
        border-color: rgba(255, 215, 0, 0.1);
        color: rgba(255, 255, 255, 0.3);
        cursor: not-allowed;
        transform: none;
    }

    .page-dots {
        color: rgba(255, 255, 255, 0.5);
        padding: 0 8px;
        font-size: 14px;
    }

    .page-jump {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-left: 12px;
    }

    .page-jump-input {
        background: rgba(30, 30, 30, 0.8);
        border: 2px solid rgba(255, 215, 0, 0.3);
        border-radius: 8px;
        padding: 8px 12px;
        color: #ffffff;
        font-size: 14px;
        width: 70px;
        text-align: center;
        transition: all 0.3s ease;
    }

    .page-jump-input:focus {
        outline: none;
        border-color: #FFD700;
        box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
    }

    .page-jump-btn {
        padding: 8px 16px;
        background: rgba(255, 215, 0, 0.1);
        border: 2px solid rgba(255, 215, 0, 0.3);
        border-radius: 8px;
        color: #FFD700;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .page-jump-btn:hover {
        background: rgba(255, 215, 0, 0.2);
        border-color: #FFD700;
        transform: translateY(-2px);
    }

    /* Responsive Styles */
    @media (max-width: 768px) {
        .employees-grid-view,
        .employees-details-view {
            grid-template-columns: 1fr;
        }

        .list-header,
        .employee-row {
            grid-template-columns: 1fr;
            padding: 16px;
        }

        .employee-row > div {
            margin-bottom: 8px;
        }

        .employee-row-actions {
            justify-content: flex-end;
            margin-top: 12px;
        }

        .details-header {
            flex-direction: column;
            text-align: center;
            gap: 16px;
        }

        .details-body {
            grid-template-columns: 1fr;
        }

        .view-options-container {
            flex-direction: column;
            align-items: stretch;
            padding: 16px;
        }

        .view-options {
            flex-wrap: wrap;
            justify-content: center;
        }

        .view-option-btn {
            padding: 10px 16px;
            font-size: 14px;
            flex: 1;
            min-width: 0;
            justify-content: center;
        }

        .pagination-container {
            flex-direction: column;
            gap: 12px;
            padding: 16px;
        }

        .pagination-controls {
            flex-direction: column;
            width: 100%;
        }

        .page-size-selector {
            width: 100%;
            justify-content: center;
        }

        .pagination-buttons {
            flex-wrap: wrap;
            justify-content: center;
        }

        .page-jump {
            width: 100%;
            justify-content: center;
            margin-left: 0;
        }

        .edit-form-container {
            margin: 0;
            border-radius: 0;
            max-height: 100vh;
        }
    }
  </style>
</head>
<body class="dark-engineering">
  <?php include __DIR__ . '/sidebar.php'; ?>

  <main class="main-content" id="mainContent">
    <div class="container" style="max-width:100%;">
      <div class="header">
        <h1>Employees</h1>
        <div class="text-muted">Manage employee records</div>
      </div>

      <?php if ($msg): ?>
        <div class="card" style="margin-bottom:12px; background: rgba(255,215,0,0.1); border: 1px solid rgba(255,215,0,0.3);">
          <?php echo htmlspecialchars($msg); ?>
        </div>
      <?php endif; ?>

      <div class="top-actions">
        <div class="text-muted">Total Employees: <strong><?php echo $totalEmployees; ?></strong></div>
        <button class="add-btn" id="openAddDesktop" style="background: linear-gradient(135deg, #FFD700, #FFA500); color: #0b0b0b; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600;">
          <i class="fa-solid fa-user-plus"></i>&nbsp;Add Employee
        </button>
      </div>

      <!-- View Options -->
      <div class="view-options-container">
        <div class="view-options-title">
          <i class="fas fa-eye"></i> View Options
        </div>
        <div class="view-options">
          <!-- <a href="?view=grid&page=<?php echo $page; ?>&per_page=<?php echo $perPage; ?>" class="view-option-btn <?php echo $currentView === 'grid' ? 'active' : ''; ?>">
            <i class="fas fa-th"></i>
            <span>Grid View</span>
          </a> -->
          <a href="?view=list&page=<?php echo $page; ?>&per_page=<?php echo $perPage; ?>" class="view-option-btn <?php echo $currentView === 'list' ? 'active' : ''; ?>">
            <i class="fas fa-list"></i>
            <span>List View</span>
          </a>
          <a href="?view=details&page=<?php echo $page; ?>&per_page=<?php echo $perPage; ?>" class="view-option-btn <?php echo $currentView === 'details' ? 'active' : ''; ?>">
            <i class="fas fa-info-circle"></i>
            <span>Details View</span>
          </a>
        </div>
      </div>

      <!-- Pagination Top -->
      <div class="pagination-container">
        <div class="pagination-info">
          Showing <strong><?php echo min(($page - 1) * $perPage + 1, $totalEmployees); ?></strong> to 
          <strong><?php echo min($page * $perPage, $totalEmployees); ?></strong> of 
          <strong><?php echo $totalEmployees; ?></strong> employees
        </div>
        <div class="pagination-controls">
          <div class="page-size-selector">
            <span class="page-size-label">Show:</span>
            <select id="pageSizeSelect" class="page-size-select" onchange="changePageSize(this.value)">
              <option value="10" <?php echo $perPage == 10 ? 'selected' : ''; ?>>10</option>
              <option value="25" <?php echo $perPage == 25 ? 'selected' : ''; ?>>25</option>
              <option value="50" <?php echo $perPage == 50 ? 'selected' : ''; ?>>50</option>
              <option value="100" <?php echo $perPage == 100 ? 'selected' : ''; ?>>100</option>
            </select>
          </div>
          <div class="pagination-buttons">
            <?php echo generatePaginationButtons($page, $totalPages, $perPage, $currentView); ?>
          </div>
        </div>
      </div>

      <section class="mt-6">
        <h2 style="margin-bottom:20px;color:#FFD700;font-size:24px;">Existing Employees</h2>
        
        <?php 
        // Check if mobile - force list view on mobile
        $isMobile = preg_match("/(android|iphone|ipad|mobile)/i", $_SERVER['HTTP_USER_AGENT']);
        $viewToUse = $isMobile ? 'list' : $currentView;
        ?>
        
        <div class="employees-<?php echo $viewToUse; ?>-view">
          <?php mysqli_data_seek($emps, 0); while ($e = mysqli_fetch_assoc($emps)): ?>
            
            <?php if ($viewToUse === 'grid'): ?>
              <!-- Grid View Card -->
              <!-- <article class="employee-card-grid" onclick="viewEmployeeProfile(<?php echo $e['id']; ?>)">
                <div class="employee-badge-grid"><?php echo htmlspecialchars($e['employee_code']); ?></div>
                <div class="card-header">
                  <div class="avatar">
                    <?php if (!empty($e['profile_image']) && file_exists(__DIR__ . '/uploads/' . $e['profile_image'])): ?>
                      <img src="uploads/<?php echo htmlspecialchars($e['profile_image']); ?>" alt="Profile">
                    <?php else: ?>
                      <div class="initials">
                        <?php echo strtoupper(substr($e['first_name'],0,1) . substr($e['last_name'],0,1)); ?>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div class="employee-info">
                    <h3 class="employee-name">
                      <?php echo htmlspecialchars($e['last_name'] . ', ' . $e['first_name']); ?>
                    </h3>
                    <p class="employee-position">
                      <i class="fas fa-briefcase"></i>
                      <?php echo htmlspecialchars($e['position']); ?>
                    </p>
                    <p class="employee-email">
                      <i class="fas fa-envelope"></i>
                      <?php echo htmlspecialchars($e['email']); ?>
                    </p>
                    <span class="employee-status"><?php echo htmlspecialchars($e['status']); ?></span>
                  </div>
                </div>

                <div class="card-actions">
                  <button class="action-btn action-btn-delete" onclick="deleteEmployee(event, <?php echo $e['id']; ?>, '<?php echo htmlspecialchars($e['first_name'] . ' ' . $e['last_name']); ?>')">
                    <i class="fa-solid fa-trash"></i>
                    Delete
                  </button>
                  <button class="action-btn action-btn-edit" onclick="openEditModal(event, <?php echo $e['id']; ?>)">
                    <i class="fa-solid fa-pen-to-square"></i>
                    Edit
                  </button>
                </div>
              </article> -->

            <?php elseif ($viewToUse === 'list'): ?>
              <!-- List View Row -->
              <div class="employee-row">
                <div>
                  <div class="employee-row-avatar">
                    <?php if (!empty($e['profile_image']) && file_exists(__DIR__ . '/uploads/' . $e['profile_image'])): ?>
                      <img src="uploads/<?php echo htmlspecialchars($e['profile_image']); ?>" alt="Profile">
                    <?php else: ?>
                      <div class="initials">
                        <?php echo strtoupper(substr($e['first_name'],0,1) . substr($e['last_name'],0,1)); ?>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div class="employee-row-info">
                    <div class="employee-row-name">
                      <?php echo htmlspecialchars($e['last_name'] . ', ' . $e['first_name']); ?>
                    </div>
                    <div class="employee-row-email">
                      <?php echo htmlspecialchars($e['email']); ?>
                    </div>
                  </div>
                </div>
                <div class="employee-row-code">
                  <?php echo htmlspecialchars($e['employee_code']); ?>
                </div>
                <div class="employee-row-position">
                  <?php echo htmlspecialchars($e['position']); ?>
                </div>
                <div class="employee-row-status">
                  <span style="color: #4ade80;"><?php echo htmlspecialchars($e['status']); ?></span>
                </div>
                <div class="employee-row-actions">
                  <button class="row-action-btn row-action-delete" onclick="deleteEmployee(event, <?php echo $e['id']; ?>, '<?php echo htmlspecialchars($e['first_name'] . ' ' . $e['last_name']); ?>')" title="Delete">
                    <i class="fa-solid fa-trash"></i>
                  </button>
                  <button class="row-action-btn row-action-edit" onclick="openEditModal(event, <?php echo $e['id']; ?>)" title="Edit">
                    <i class="fa-solid fa-pen-to-square"></i>
                  </button>
                </div>
              </div>

            <?php elseif ($viewToUse === 'details'): ?>
              <!-- Details View Card -->
              <article class="employee-card-details">
               
                <div class="details-header">
                  <div class="details-avatar">
                    <?php if (!empty($e['profile_image']) && file_exists(__DIR__ . '/uploads/' . $e['profile_image'])): ?>
                      <img src="uploads/<?php echo htmlspecialchars($e['profile_image']); ?>" alt="Profile">
                    <?php else: ?>
                      <div class="initials">
                        <?php echo strtoupper(substr($e['first_name'],0,1) . substr($e['last_name'],0,1)); ?>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div class="details-header-info">
                    <h2 class="details-name">
                      <?php echo htmlspecialchars($e['last_name'] . ', ' . $e['first_name']); ?>
                    </h2>
                    <p class="details-position">
                      <i class="fas fa-briefcase"></i>
                      <?php echo htmlspecialchars($e['position']); ?>
                    </p>
                    <p class="details-email">
                      <i class="fas fa-envelope"></i>
                      <?php echo htmlspecialchars($e['email']); ?>
                    </p>
                  </div>
                </div>
                
                <div class="details-body">
                  <div class="detail-item">
                    <div class="detail-label">Employee Code</div>
                    <div class="detail-value"><?php echo htmlspecialchars($e['employee_code']); ?></div>
                  </div>


                  <div class="detail-item">
                    <div class="detail-label">Status</div>
                    <div class="detail-value">
                      <span style="color: #4ade80;"><?php echo htmlspecialchars($e['status']); ?></span>
                    </div>
                  </div>
                </div>

                <div class="details-actions">
                  <button class="action-btn action-btn-delete" onclick="deleteEmployee(event, <?php echo $e['id']; ?>, '<?php echo htmlspecialchars($e['first_name'] . ' ' . $e['last_name']); ?>')">
                    <i class="fa-solid fa-trash"></i>
                    Delete
                  </button>
                  <button class="action-btn action-btn-edit" onclick="openEditModal(event, <?php echo $e['id']; ?>)">
                    <i class="fa-solid fa-pen-to-square"></i>
                    Edit Employee
                  </button>
                </div>
              </article>
            <?php endif; ?>
            
          <?php endwhile; ?>
        </div>
      </section>

      <!-- Pagination Bottom -->
      <div class="pagination-container">
        <div class="pagination-info">
          Page <strong><?php echo $page; ?></strong> of <strong><?php echo $totalPages; ?></strong>
        </div>
        <div class="pagination-controls">
          <div class="page-size-selector">
            <span class="page-size-label">Show:</span>
            <select id="pageSizeSelectBottom" class="page-size-select" onchange="changePageSize(this.value)">
              <option value="10" <?php echo $perPage == 10 ? 'selected' : ''; ?>>10</option>
              <option value="25" <?php echo $perPage == 25 ? 'selected' : ''; ?>>25</option>
              <option value="50" <?php echo $perPage == 50 ? 'selected' : ''; ?>>50</option>
              <option value="100" <?php echo $perPage == 100 ? 'selected' : ''; ?>>100</option>
            </select>
          </div>
          <div class="pagination-buttons">
            <?php echo generatePaginationButtons($page, $totalPages, $perPage, $currentView); ?>
          </div>
          <div class="page-jump">
            <input type="number" id="pageJumpInput" class="page-jump-input" min="1" max="<?php echo $totalPages; ?>" value="<?php echo $page; ?>" placeholder="Page">
            <button class="page-jump-btn" onclick="jumpToPage()">Go</button>
          </div>
        </div>
      </div>

      <!-- Floating Add Button for mobile -->
      <button class="fab" id="openAddMobile" title="Add employee" style="position: fixed; bottom: 2rem; right: 2rem; width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, #FFD700, #FFA500); color: #0b0b0b; border: none; font-size: 1.5rem; cursor: pointer; z-index: 100;">
        <i class="fa-solid fa-plus"></i>
      </button>

    </div>
  </main>

  <!-- Enhanced Edit Employee Modal -->
  <div class="edit-form-modal" id="editModal">
    <div class="edit-form-container">
      <div class="edit-form-header">
        <button class="close-btn" onclick="closeEditModal()">&times;</button>
        <h3>Edit Employee</h3>
        <div class="employee-id-display" id="editEmployeeId">Loading...</div>
      </div>
      <form id="editEmployeeForm" method="POST" enctype="multipart/form-data" class="edit-form-body">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" id="editEmployeeIdInput">

        <!-- Profile Information Section -->
        <div class="form-section">
          <h4 class="section-title">Profile Information</h4>
          <div class="form-row-grid">
            <div class="form-group">
              <label class="form-label required">Employee Code</label>
              <input type="text" name="employee_code" id="editEmployeeCode" class="form-input" required>
            </div>
            <div class="form-group">
              <label class="form-label required">First Name</label>
              <input type="text" name="first_name" id="editFirstName" class="form-input" required>
            </div>
            <div class="form-group">
              <label class="form-label">Middle Name</label>
              <input type="text" name="middle_name" id="editMiddleName" class="form-input">
            </div>
            <div class="form-group">
              <label class="form-label required">Last Name</label>
              <input type="text" name="last_name" id="editLastName" class="form-input" required>
            </div>
          </div>
        </div>

        <!-- Contact Information Section -->
        <div class="form-section">
          <h4 class="section-title">Contact Information</h4>
          <div class="form-row-grid">
            <div class="form-group">
              <label class="form-label required">Email Address</label>
              <input type="email" name="email" id="editEmail" class="form-input" required>
            </div>
            <div class="form-group">
              <label class="form-label">Phone Number</label>
              <input type="tel" name="phone" id="editPhone" class="form-input" placeholder="+63 XXX XXX XXXX">
            </div>
          </div>
        </div>

        <!-- Employment Details Section -->
        <div class="form-section">
          <h4 class="section-title">Employment Details</h4>
          <div class="form-row-grid">
            <div class="form-group">
              <label class="form-label required">Position</label>
              <input type="text" name="position" id="editPosition" class="form-input" required>
            </div>
            <div class="form-group">
              <label class="form-label">Department</label>
              <input type="text" name="department" id="editDepartment" class="form-input">
            </div>
            <div class="form-group">
              <label class="form-label">Status</label>
              <select name="status" id="editStatus" class="form-select">
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
                <option value="On Leave">On Leave</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Profile Image Section -->
        <div class="form-section">
          <h4 class="section-title">Profile Image</h4>
          <div class="profile-image-upload">
            <div class="profile-image-preview" id="profileImagePreview">
              <div class="initials" id="profileImageInitials">JD</div>
            </div>
            <div class="file-upload-area">
              <div class="file-input-wrapper">
                <input type="file" id="profileImageInput" name="profile_image" accept="image/*" onchange="previewProfileImage(this)">
                <label for="profileImageInput" class="file-input-label">
                  <i class="fas fa-cloud-upload-alt"></i> Choose New Profile Image
                </label>
              </div>
              <div class="file-info">
                Max file size: 5MB • Formats: JPG, PNG, GIF, WebP
              </div>
            </div>
          </div>
        </div>

        <div class="form-actions">
          <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
          <button type="submit" class="btn-save">
            <i class="fas fa-save"></i> Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Add Employee Modal -->
  <div class="modal-backdrop" id="addModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center;">
    <div class="modal-panel" style="background: #0b0b0b; border: 1px solid rgba(255,215,0,0.3); border-radius: 12px; padding: 2rem; max-width: 500px; width: 90%;">
      <h3 style="margin-top:0; color: #FFD700; margin-bottom: 1.5rem;">Add New Employee</h3>
      <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="form-row" style="margin-bottom: 1rem;">
          <input name="employee_code" required placeholder="Employee code" style="width: 100%; padding: 0.75rem; border: 1px solid rgba(255,215,0,0.3); border-radius: 8px; background: rgba(0,0,0,0.5); color: white;">
        </div>
        <div class="form-row" style="margin-bottom: 1rem;">
          <input name="first_name" required placeholder="First name" style="width: 100%; padding: 0.75rem; border: 1px solid rgba(255,215,0,0.3); border-radius: 8px; background: rgba(0,0,0,0.5); color: white;">
        </div>
        <div class="form-row" style="margin-bottom: 1rem;">
          <input name="middle_name" placeholder="Middle Name" style="width: 100%; padding: 0.75rem; border: 1px solid rgba(255,215,0,0.3); border-radius: 8px; background: rgba(0,0,0,0.5); color: white;">
        </div>
        <div class="form-row" style="margin-bottom: 1rem;">
          <input name="last_name" required placeholder="Last name" style="width: 100%; padding: 0.75rem; border: 1px solid rgba(255,215,0,0.3); border-radius: 8px; background: rgba(0,0,0,0.5); color: white;">
        </div>
        <div class="form-row" style="margin-bottom: 1rem;">
          <input name="email" type="email" placeholder="Email" style="width: 100%; padding: 0.75rem; border: 1px solid rgba(255,215,0,0.3); border-radius: 8px; background: rgba(0,0,0,0.5); color: white;">
        </div>
        <div class="form-row" style="margin-bottom: 1rem;">
          <input name="position" placeholder="Position" style="width: 100%; padding: 0.75rem; border: 1px solid rgba(255,215,0,0.3); border-radius: 8px; background: rgba(0,0,0,0.5); color: white;">
        </div>
        <div class="form-row" style="margin-bottom: 1.5rem;">
          <input name="password" type="password" placeholder="Password (optional)" style="width: 100%; padding: 0.75rem; border: 1px solid rgba(255,215,0,0.3); border-radius: 8px; background: rgba(0,0,0,0.5); color: white;">
        </div>
        <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:8px;">
          <button type="button" class="btn" id="closeAdd" style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.3); padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer;">Cancel</button>
          <button class="add-btn" style="background: linear-gradient(135deg, #FFD700, #FFA500); color: #0b0b0b; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; cursor: pointer;">Add Employee</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // ===== MODAL FUNCTIONALITY =====
    const openAddDesktop = document.getElementById('openAddDesktop');
    const openAddMobile = document.getElementById('openAddMobile');
    const addModal = document.getElementById('addModal');
    const closeAdd = document.getElementById('closeAdd');
    const editModal = document.getElementById('editModal');
    
    function openAddModal() {
      addModal.style.display = 'flex';
    }
    
    function closeAddModal() {
      addModal.style.display = 'none';
    }
    
    openAddDesktop?.addEventListener('click', openAddModal);
    openAddMobile?.addEventListener('click', openAddModal);
    closeAdd?.addEventListener('click', closeAddModal);
    
    addModal?.addEventListener('click', (e) => {
      if(e.target === addModal) {
        closeAddModal();
      }
    });

    // ===== EDIT MODAL FUNCTIONALITY =====
    let currentEditEmployeeId = null;

    function openEditModal(event, employeeId) {
      event.stopPropagation();
      currentEditEmployeeId = employeeId;
      
      // Load employee data via AJAX
      fetch(`get_employee_data.php?id=${employeeId}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const employee = data.employee;
            
            // Populate form fields
            document.getElementById('editEmployeeId').textContent = employee.employee_code;
            document.getElementById('editEmployeeIdInput').value = employee.id;
            document.getElementById('editEmployeeCode').value = employee.employee_code;
            document.getElementById('editFirstName').value = employee.first_name;
            document.getElementById('editMiddleName').value = employee.middle_name || '';
            document.getElementById('editLastName').value = employee.last_name;
            document.getElementById('editEmail').value = employee.email;
            document.getElementById('editPhone').value = employee.phone || '';
            document.getElementById('editPosition').value = employee.position;
            document.getElementById('editDepartment').value = employee.department || '';
            document.getElementById('editStatus').value = employee.status;
            
            // Update profile image preview
            const profileImagePreview = document.getElementById('profileImagePreview');
            const profileImageInitials = document.getElementById('profileImageInitials');
            
            if (employee.profile_image) {
              profileImagePreview.innerHTML = `<img src="uploads/${employee.profile_image}" alt="Profile" onerror="this.style.display='none'; document.getElementById('profileImageInitials').style.display='flex';">`;
              profileImageInitials.style.display = 'none';
              profileImageInitials.textContent = (employee.first_name[0] + employee.last_name[0]).toUpperCase();
            } else {
              profileImagePreview.innerHTML = '';
              profileImageInitials.style.display = 'flex';
              profileImageInitials.textContent = (employee.first_name[0] + employee.last_name[0]).toUpperCase();
              profileImagePreview.appendChild(profileImageInitials);
            }
            
            // Show modal
            editModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
          } else {
            alert('Error loading employee data: ' + (data.message || 'Unknown error'));
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error loading employee data. Please try again.');
        });
    }

    function closeEditModal() {
      editModal.style.display = 'none';
      document.body.style.overflow = 'auto';
      currentEditEmployeeId = null;
    }

    function previewProfileImage(input) {
      const preview = document.getElementById('profileImagePreview');
      const initials = document.getElementById('profileImageInitials');
      
      if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
          preview.innerHTML = `<img src="${e.target.result}" alt="Profile Preview" style="width:100%;height:100%;object-fit:cover;">`;
          initials.style.display = 'none';
        }
        
        reader.readAsDataURL(input.files[0]);
      }
    }

    // Close modal when clicking outside
    editModal?.addEventListener('click', function(e) {
      if (e.target === this) {
        closeEditModal();
      }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeEditModal();
        closeAddModal();
      }
    });

    // ===== EMPLOYEE ACTIONS =====
    function deleteEmployee(event, employeeId, employeeName) {
      event.stopPropagation();
      
      if (confirm(`Are you sure you want to delete employee "${employeeName}"? This action cannot be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="${employeeId}">
        `;
        document.body.appendChild(form);
        form.submit();
      }
    }

    function viewEmployeeProfile(employeeId) {
      // In a real application, this would redirect to a profile page
      // For now, open the edit modal
      openEditModal({stopPropagation: () => {}}, employeeId);
    }

    // ===== PAGINATION FUNCTIONS =====
    function changePageSize(newSize) {
      const url = new URL(window.location.href);
      url.searchParams.set('per_page', newSize);
      url.searchParams.set('page', '1'); // Reset to first page when changing size
      window.location.href = url.toString();
    }

    function jumpToPage() {
      const pageInput = document.getElementById('pageJumpInput');
      let page = parseInt(pageInput.value);
      const totalPages = <?php echo $totalPages; ?>;
      const currentView = '<?php echo $currentView; ?>';
      const perPage = <?php echo $perPage; ?>;
      
      if (isNaN(page) || page < 1 || page > totalPages) {
        pageInput.value = <?php echo $page; ?>;
        alert(`Please enter a page number between 1 and ${totalPages}`);
        return;
      }
      
      const url = new URL(window.location.href);
      url.searchParams.set('page', page);
      window.location.href = url.toString();
    }

    // Handle Enter key on page jump input
    document.getElementById('pageJumpInput')?.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        jumpToPage();
      }
    });

    // Auto-close edit forms when clicking outside on mobile
    document.addEventListener('click', function(e) {
      if (window.innerWidth <= 768) {
        const openDetails = document.querySelector('details[open]');
        if (openDetails && !openDetails.contains(e.target)) {
          openDetails.removeAttribute('open');
        }
      }
    });
  </script>
</body>
</html>

<?php
// Function to generate pagination buttons
function generatePaginationButtons($currentPage, $totalPages, $perPage, $currentView) {
    if ($totalPages <= 1) return '';
    
    $html = '';
    
    // Previous button
    if ($currentPage > 1) {
        $prevPage = $currentPage - 1;
        $html .= '<a href="?page=' . $prevPage . '&per_page=' . $perPage . '&view=' . $currentView . '" class="page-btn">';
        $html .= '<i class="fas fa-chevron-left"></i>';
        $html .= '</a>';
    } else {
        $html .= '<span class="page-btn" disabled><i class="fas fa-chevron-left"></i></span>';
    }
    
    // First page
    $html .= '<a href="?page=1&per_page=' . $perPage . '&view=' . $currentView . '" class="page-btn ' . ($currentPage === 1 ? 'active' : '') . '">1</a>';
    
    // Ellipsis if needed
    if ($currentPage > 3) {
        $html .= '<span class="page-dots">...</span>';
    }
    
    // Pages around current page
    for ($i = max(2, $currentPage - 1); $i <= min($totalPages - 1, $currentPage + 1); $i++) {
        if ($i > 1 && $i < $totalPages) {
            $html .= '<a href="?page=' . $i . '&per_page=' . $perPage . '&view=' . $currentView . '" class="page-btn ' . ($currentPage === $i ? 'active' : '') . '">' . $i . '</a>';
        }
    }
    
    // Ellipsis if needed
    if ($currentPage < $totalPages - 2) {
        $html .= '<span class="page-dots">...</span>';
    }
    
    // Last page (if not first page)
    if ($totalPages > 1) {
        $html .= '<a href="?page=' . $totalPages . '&per_page=' . $perPage . '&view=' . $currentView . '" class="page-btn ' . ($currentPage === $totalPages ? 'active' : '') . '">' . $totalPages . '</a>';
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $nextPage = $currentPage + 1;
        $html .= '<a href="?page=' . $nextPage . '&per_page=' . $perPage . '&view=' . $currentView . '" class="page-btn">';
        $html .= '<i class="fas fa-chevron-right"></i>';
        $html .= '</a>';
    } else {
        $html .= '<span class="page-btn" disabled><i class="fas fa-chevron-right"></i></span>';
    }
    
    return $html;
}
?>