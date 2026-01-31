<?php
// employee/upload_profile.php
require_once __DIR__ . '/../conn/db_connection.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['employee_code'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$employee_id = intval($_POST['employee_id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$employee_id || $action !== 'upload_profile') {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Check if employee exists
$check = mysqli_prepare($db, "SELECT id FROM employees WHERE id = ?");
mysqli_stmt_bind_param($check, 'i', $employee_id);
mysqli_stmt_execute($check);
mysqli_stmt_store_result($check);

if (mysqli_stmt_num_rows($check) === 0) {
    echo json_encode(['success' => false, 'message' => 'Employee not found']);
    exit;
}
mysqli_stmt_close($check);

// Handle file upload
if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['profile_image'];
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$max_size = 5 * 1024 * 1024; // 5MB

// Validate file type
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.']);
    exit;
}

// Validate file size
if ($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5MB.']);
    exit;
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$unique_name = uniqid('profile_', true) . '.' . $extension;
$upload_path = __DIR__ . '/uploads/' . $unique_name;

// Create uploads directory if it doesn't exist
$upload_dir = __DIR__ . '/uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
    exit;
}

// Get current profile image to delete old one
$current_image = null;
$get_current = mysqli_prepare($db, "SELECT profile_image FROM employees WHERE id = ?");
mysqli_stmt_bind_param($get_current, 'i', $employee_id);
mysqli_stmt_execute($get_current);
mysqli_stmt_bind_result($get_current, $current_image);
mysqli_stmt_fetch();
mysqli_stmt_close($get_current);

// Delete old profile image if exists
if ($current_image && file_exists(__DIR__ . '/uploads/' . $current_image)) {
    unlink(__DIR__ . '/uploads/' . $current_image);
}

// Update database
$update = mysqli_prepare($db, "UPDATE employees SET profile_image = ?, updated_at = NOW() WHERE id = ?");
mysqli_stmt_bind_param($update, 'si', $unique_name, $employee_id);

if (mysqli_stmt_execute($update)) {
    echo json_encode(['success' => true, 'message' => 'Profile image updated successfully', 'filename' => $unique_name]);
} else {
    // If database update fails, delete the uploaded file
    if (file_exists($upload_path)) {
        unlink($upload_path);
    }
    echo json_encode(['success' => false, 'message' => 'Failed to update database']);
}

mysqli_stmt_close($update);
?></content>
<parameter name="filePath">c:\wamp64\www\attendance_web_Copy\employee\upload_profile.php