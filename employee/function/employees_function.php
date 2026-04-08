<?php
// Check if user is Super Admin
$isSuperAdmin = false;
$sessionPosition = $_SESSION['position'] ?? '';
$sessionRole = $_SESSION['role'] ?? '';
$sessionUserRole = $_SESSION['user_role'] ?? '';
if ($sessionPosition === 'Super Admin' || $sessionRole === 'Super Admin' || $sessionUserRole === 'Super Admin') {
    $isSuperAdmin = true;
}

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
    // Check if user is Super Admin for employee modifications
    if (!$isSuperAdmin) {
        $msg = 'Error: Only Super Admin can modify employee records.';
    } else {
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
                $has_deduction = isset($_POST['has_deduction']) ? 1 : 0;
                
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
                        // Only include has_deduction in INSERT if column exists
                        if ($hasDeductionColumn) {
                            $ins = mysqli_prepare($db, "INSERT INTO employees (employee_code, first_name, middle_name, last_name, email, position, password_hash, status, has_deduction, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'Active', ?, NOW())");
                            mysqli_stmt_bind_param($ins, 'sssssssi', $employee_code, $first_name, $middle_name, $last_name, $email, $position, $hash, $has_deduction);
                        } else {
                            $ins = mysqli_prepare($db, "INSERT INTO employees (employee_code, first_name, middle_name, last_name, email, position, password_hash, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'Active', NOW())");
                            mysqli_stmt_bind_param($ins, 'sssssss', $employee_code, $first_name, $middle_name, $last_name, $email, $position, $hash);
                        }
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
                    // Soft delete: Set status to 'Inactive' instead of deleting
                    $del = mysqli_prepare($db, "UPDATE employees SET status = 'Inactive', updated_at = NOW() WHERE id = ?");
                    mysqli_stmt_bind_param($del, 'i', $id);
                        if (mysqli_stmt_execute($del)) {
                        $msg = 'Employee deactivated successfully.';
                    } else {
                        $msg = 'Error deactivating employee: ' . mysqli_error($db);
                    }
                }
            }

            if ($action === 'update') {
                $id = intval($_POST['id'] ?? 0);
                $employee_code = trim($_POST['employee_code'] ?? '');
                $first_name = trim($_POST['first_name'] ?? '');
                $middle_name = trim($_POST['middle_name'] ?? '');
                $last_name = trim($_POST['last_name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $position = trim($_POST['position'] ?? '');
                $status = trim($_POST['status'] ?? 'Active');
                $daily_rate = floatval($_POST['daily_rate'] ?? 600.00);
                $has_deduction = isset($_POST['has_deduction']) ? 1 : 0;
                
                if ($id > 0) {
                    // Check if the new employee code conflicts with another employee
                    $check = mysqli_prepare($db, "SELECT id FROM employees WHERE employee_code = ? AND id != ?");
                    mysqli_stmt_bind_param($check, 'si', $employee_code, $id);
                    mysqli_stmt_execute($check);
                    mysqli_stmt_store_result($check);
                    
                    if (mysqli_stmt_num_rows($check) > 0) {
                        $msg = 'Error: Employee code already exists for another employee.';
                    } else {
                        // Only include has_deduction in UPDATE if column exists
                        if ($hasDeductionColumn) {
                            $up = mysqli_prepare($db, "UPDATE employees SET employee_code = ?, first_name = ?, middle_name = ?, last_name = ?, email = ?, position = ?, status = ?, daily_rate = ?, has_deduction = ? WHERE id = ?");
                            mysqli_stmt_bind_param($up, 'ssssssssii', $employee_code, $first_name, $middle_name, $last_name, $email, $position, $status, $daily_rate, $has_deduction, $id);
                        } else {
                            $up = mysqli_prepare($db, "UPDATE employees SET employee_code = ?, first_name = ?, middle_name = ?, last_name = ?, email = ?, position = ?, status = ?, daily_rate = ? WHERE id = ?");
                            mysqli_stmt_bind_param($up, 'ssssssssi', $employee_code, $first_name, $middle_name, $last_name, $email, $position, $status, $daily_rate, $id);
                        }
                        if (mysqli_stmt_execute($up)) {
                            $msg = 'Employee updated.';
                            

                            // Handle profile image upload if provided
                            if (isset($_FILES['profile_image'])) {
                                $file = $_FILES['profile_image'];
                                
                                // Check for upload errors
                                if ($file['error'] !== UPLOAD_ERR_OK) {
                                    $error_messages = [
                                        UPLOAD_ERR_INI_SIZE => 'File exceeds server upload limit (upload_max_filesize)',
                                        UPLOAD_ERR_FORM_SIZE => 'File exceeds form max size',
                                        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                                        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                                        UPLOAD_ERR_NO_TMP_DIR => 'Missing temp folder',
                                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                                        UPLOAD_ERR_EXTENSION => 'PHP extension stopped upload'
                                    ];
                                    $error_msg = $error_messages[$file['error']] ?? 'Unknown upload error (code: ' . $file['error'] . ')';
                                    $msg .= ' Profile image error: ' . $error_msg;
                                } else {
                                    // Upload successful, process the file
                                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                                    $max_size = 5 * 1024 * 1024; // 5MB
                                    
                                    if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
                                        // Generate unique filename
                                        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                                        $unique_name = uniqid('profile_', true) . '.' . $extension;
                                        $upload_path = __DIR__ . '/../uploads/' . $unique_name;
                                        
                                        // Create uploads directory if it doesn't exist
                                        $upload_dir = __DIR__ . '/../uploads/';
                                        if (!is_dir($upload_dir)) {
                                            mkdir($upload_dir, 0755, true);
                                        }
                                        
                                        // Get current profile image to delete old one
                                        $current_image = null;
                                        $get_current = mysqli_prepare($db, "SELECT profile_image FROM employees WHERE id = ?");
                                        mysqli_stmt_bind_param($get_current, 'i', $id);
                                        mysqli_stmt_execute($get_current);
                                        mysqli_stmt_bind_result($get_current, $current_image);
                                        mysqli_stmt_fetch($get_current);
                                        mysqli_stmt_close($get_current);
                                        
                                        // Delete old profile image if exists
                                        if ($current_image && file_exists(__DIR__ . '/../uploads/' . $current_image)) {
                                            unlink(__DIR__ . '/../uploads/' . $current_image);
                                        }
                                        
                                        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                                            // Update database with new image
                                            $update_img = mysqli_prepare($db, "UPDATE employees SET profile_image = ?, updated_at = NOW() WHERE id = ?");
                                            mysqli_stmt_bind_param($update_img, 'si', $unique_name, $id);
                                            mysqli_stmt_execute($update_img);
                                            mysqli_stmt_close($update_img);
                                            $msg .= ' Profile image updated. [Debug: saved to ' . $upload_path . ']';
                                        } else {
                                            $msg .= ' Failed to save profile image. [Debug: tmp=' . $file['tmp_name'] . ', dest=' . $upload_path . ', exists=' . (file_exists($file['tmp_name']) ? 'yes' : 'no') . ', dir_writable=' . (is_writable($upload_dir) ? 'yes' : 'no') . ']';
                                        }
                                    } else {
                                        $msg .= ' Invalid profile image file. Only JPG, PNG, GIF, and WebP files up to 5MB are allowed.';
                                    }
                                }
                            }
                        } else {
                            $msg = 'Error updating employee: ' . mysqli_error($db);
                        }
                    }
                    mysqli_stmt_close($check);
                }
            }

            // ===== RESET PASSWORD ACTION =====
            if ($action === 'reset_password') {
                $id = intval($_POST['id'] ?? 0);
                
                if ($id > 0) {
                    // Hash the default password using MD5 (consistent with existing system)
                    $default_password = 'jajrconstruction';
                    $password_hash = md5($default_password);
                    
                    $stmt = mysqli_prepare($db, "UPDATE employees SET password_hash = ? WHERE id = ?");
                    mysqli_stmt_bind_param($stmt, 'si', $password_hash, $id);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        // Return JSON response for AJAX requests
                        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                            header('Content-Type: application/json');
                            echo json_encode(['success' => true, 'message' => 'Password reset successfully to default.']);
                            exit;
                        }
                        $msg = 'Password reset successfully to default.';
                    } else {
                        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                            header('Content-Type: application/json');
                            echo json_encode(['success' => false, 'message' => 'Error resetting password: ' . mysqli_error($db)]);
                            exit;
                        }
                        $msg = 'Error resetting password: ' . mysqli_error($db);
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => 'Invalid employee ID.']);
                        exit;
                    }
                    $msg = 'Invalid employee ID.';
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
}

// Get current view preference from session or default to list (removed details)
$currentView = $_SESSION['employee_view'] ?? 'list';

// Handle view change request
if (isset($_GET['view'])) {
    $view = $_GET['view'];
    if (in_array($view, ['list'])) { // Removed 'details' from allowed views
        $_SESSION['employee_view'] = $view;
        $currentView = $view;
    }
}

// Get search parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchTerm = mysqli_real_escape_string($db, $search);

$fromClause = "FROM employees e LEFT JOIN branches b ON b.id = e.branch_id";

// Check if has_deduction column exists
function columnExists($db, $table, $column) {
    $result = mysqli_query($db, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && mysqli_num_rows($result) > 0;
}
$hasDeductionColumn = columnExists($db, 'employees', 'has_deduction');

// Build search condition
$searchCondition = "WHERE e.status = 'Active'";
if (!empty($search)) {
    $searchCondition = "WHERE e.status = 'Active' AND (
        CONCAT(e.first_name, ' ', e.last_name) LIKE '%$searchTerm%' OR
        CONCAT(e.last_name, ', ', e.first_name) LIKE '%$searchTerm%' OR
        e.employee_code LIKE '%$searchTerm%' OR
        e.email LIKE '%$searchTerm%' OR
        e.position LIKE '%$searchTerm%' OR
        b.branch_name LIKE '%$searchTerm%'
    )";
}

// Get total count of employees (with search filter)
$countQuery = "SELECT COUNT(*) as total $fromClause $searchCondition";
$countResult = mysqli_query($db, $countQuery);
$countRow = mysqli_fetch_assoc($countResult);
$totalEmployees = $countRow['total'];
$totalPages = ceil($totalEmployees / $perPage);

// Get employees with pagination and search
// Only include has_deduction if column exists
$deductionSelect = $hasDeductionColumn ? ", COALESCE(e.has_deduction, 1) as has_deduction" : ", 1 as has_deduction";
$query = "SELECT e.*, b.branch_name AS branch_name$deductionSelect $fromClause $searchCondition ORDER BY e.last_name, e.first_name LIMIT $perPage OFFSET $offset";
$emps = mysqli_query($db, $query);

// Helper function to build URLs with all parameters
function buildEmployeeUrl($params = []) {
    global $search, $perPage;
    $urlParams = [
        'page' => '1',
        'per_page' => $perPage,
        'view' => $_GET['view'] ?? 'list'
    ];
    
    if (!empty($search)) {
        $urlParams['search'] = $search;
    }
    
    // Merge with provided params
    $urlParams = array_merge($urlParams, $params);
    
    return '?' . http_build_query($urlParams);
}
?>