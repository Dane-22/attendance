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
$currentView = $_SESSION['employee_view'] ?? 'grid';

// Handle view change request
if (isset($_GET['view'])) {
    $view = $_GET['view'];
    if (in_array($view, ['grid', 'list', 'details'])) {
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
  <style>
    /* View Options Styles */
    .view-options-container {
        background: #1a1a1a;
        border: 1px solid rgba(255,215,0,0.2);
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }

    .view-options-title {
        font-size: 16px;
        font-weight: 600;
        color: #FFD700;
    }

    .view-options {
        display: flex;
        gap: 8px;
    }

    .view-option-btn {
        background: rgba(30,30,30,0.8);
        border: 1px solid rgba(255,215,0,0.3);
        border-radius: 6px;
        padding: 8px 16px;
        color: #888;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
    }

    .view-option-btn:hover {
        border-color: rgba(255,215,0,0.5);
        color: #FFD700;
    }

    .view-option-btn.active {
        background: linear-gradient(135deg, #FFD700, #FFA500);
        border-color: #FFD700;
        color: #000;
    }

    /* Grid View Styles */
    .employees-grid-view {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }

    /* List View Styles */
    .employees-list-view {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .employee-card-list {
        background: rgba(20,20,20,0.8);
        border: 1px solid rgba(255,215,0,0.2);
        border-radius: 12px;
        padding: 1rem;
        display: grid;
        grid-template-columns: auto 1fr auto auto;
        align-items: center;
        gap: 20px;
    }

    .employee-card-list .avatar {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #FFD700, #FFA500);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #0b0b0b;
        font-size: 1rem;
    }

    .employee-card-list .info {
        display: flex;
        flex-direction: column;
    }

    .employee-card-list .card-actions {
        display: flex;
        gap: 8px;
    }

    /* Details View Styles */
    .employees-details-view {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 20px;
    }

    .employee-card-details {
        background: rgba(20,20,20,0.8);
        border: 1px solid rgba(255,215,0,0.2);
        border-radius: 12px;
        padding: 1.5rem;
    }

    .employee-card-details .details-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid rgba(255,215,0,0.1);
    }

    .employee-card-details .avatar {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #FFD700, #FFA500);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #0b0b0b;
        font-size: 1.2rem;
    }

    .employee-card-details .details-body {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 16px;
        margin-bottom: 1.5rem;
    }

    .detail-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .detail-label {
        font-size: 12px;
        color: rgba(255,255,255,0.6);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .detail-value {
        font-size: 14px;
        color: #ffffff;
        font-weight: 500;
    }

    .employee-card-details .card-actions {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
    }

    /* ===== PAGINATION STYLES ===== */
    .pagination-container {
        background: #1a1a1a;
        border: 1px solid rgba(255,215,0,0.2);
        border-radius: 12px;
        padding: 16px 20px;
        margin: 20px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }

    .pagination-info {
        font-size: 14px;
        color: #888;
    }

    .pagination-info strong {
        color: #FFD700;
    }

    .pagination-controls {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .page-size-selector {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .page-size-label {
        font-size: 14px;
        color: #888;
    }

    .page-size-select {
        background: rgba(30,30,30,0.8);
        border: 1px solid rgba(255,215,0,0.3);
        border-radius: 6px;
        padding: 6px 12px;
        color: #ffffff;
        font-size: 14px;
        cursor: pointer;
        min-width: 70px;
    }

    .page-size-select:focus {
        outline: none;
        border-color: #FFD700;
    }

    .pagination-buttons {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .page-btn {
        background: rgba(30,30,30,0.8);
        border: 1px solid rgba(255,215,0,0.3);
        border-radius: 6px;
        padding: 6px 12px;
        color: #ffffff;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s ease;
        min-width: 36px;
        text-align: center;
        text-decoration: none;
        display: inline-block;
    }

    .page-btn:hover:not(:disabled):not(.active) {
        border-color: rgba(255,215,0,0.5);
        color: #FFD700;
    }

    .page-btn.active {
        background: linear-gradient(135deg, #FFD700, #FFA500);
        border-color: #FFD700;
        color: #000000;
        font-weight: 600;
    }

    .page-btn:disabled {
        background: rgba(20,20,20,0.5);
        border-color: rgba(255,215,0,0.1);
        color: #555;
        cursor: not-allowed;
    }

    .page-dots {
        color: #888;
        padding: 0 6px;
        font-size: 14px;
    }

    .page-jump {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-left: 12px;
    }

    .page-jump-input {
        background: rgba(30,30,30,0.8);
        border: 1px solid rgba(255,215,0,0.3);
        border-radius: 6px;
        padding: 6px 10px;
        color: #ffffff;
        font-size: 14px;
        width: 60px;
        text-align: center;
    }

    .page-jump-input:focus {
        outline: none;
        border-color: #FFD700;
    }

    .page-jump-btn {
        background: rgba(30,30,30,0.8);
        border: 1px solid rgba(255,215,0,0.3);
        border-radius: 6px;
        padding: 6px 12px;
        color: #ffffff;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .page-jump-btn:hover {
        border-color: #FFD700;
        color: #FFD700;
    }

    /* Loading animation for pagination */
    .pagination-loading {
        display: inline-block;
        margin-left: 8px;
        color: #FFD700;
    }

    .pagination-loading i {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Responsive Styles */
    @media (max-width: 768px) {
        /* Force list view on mobile */
        .employees-grid-view,
        .employees-details-view {
            grid-template-columns: 1fr;
        }

        .employee-card-list {
            grid-template-columns: auto 1fr;
            gap: 12px;
        }

        .employee-card-list .card-actions {
            grid-column: 1 / span 2;
            justify-content: flex-end;
            margin-top: 12px;
        }

        .employee-card-details .details-body {
            grid-template-columns: 1fr;
        }

        /* Responsive pagination */
        .pagination-container {
            flex-direction: column;
            align-items: stretch;
            gap: 12px;
            padding: 14px;
        }

        .pagination-info {
            text-align: center;
            order: 1;
        }

        .pagination-controls {
            flex-direction: column;
            gap: 12px;
            order: 2;
        }

        .page-size-selector {
            justify-content: center;
            width: 100%;
        }

        .pagination-buttons {
            flex-wrap: wrap;
            justify-content: center;
        }

        .page-jump {
            margin-left: 0;
            margin-top: 8px;
            justify-content: center;
            width: 100%;
        }

        /* Hide view options on mobile (force list view) */
        .view-options-container {
            display: none;
        }
    }

    @media (max-width: 480px) {
        .employee-card-list {
            padding: 12px;
            gap: 10px;
        }

        .employee-card-details {
            padding: 1rem;
        }

        .employee-card-details .details-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }

        .page-btn {
            min-width: 32px;
            padding: 4px 8px;
            font-size: 12px;
        }

        .page-size-select {
            padding: 4px 8px;
            font-size: 12px;
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
        <div class="view-options-title">View Options:</div>
        <div class="view-options">
          <a href="?view=grid&page=<?php echo $page; ?>&per_page=<?php echo $perPage; ?>" class="view-option-btn <?php echo $currentView === 'grid' ? 'active' : ''; ?>">
            <i class="fas fa-th"></i>
            <span>Grid</span>
          </a>
          <a href="?view=list&page=<?php echo $page; ?>&per_page=<?php echo $perPage; ?>" class="view-option-btn <?php echo $currentView === 'list' ? 'active' : ''; ?>">
            <i class="fas fa-list"></i>
            <span>List</span>
          </a>
          <a href="?view=details&page=<?php echo $page; ?>&per_page=<?php echo $perPage; ?>" class="view-option-btn <?php echo $currentView === 'details' ? 'active' : ''; ?>">
            <i class="fas fa-info-circle"></i>
            <span>Details</span>
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
        <h2 style="margin-bottom:12px;color:#FFD700;">Existing Employees</h2>
        
        <?php 
        // Check if mobile - force list view on mobile
        $isMobile = preg_match("/(android|iphone|ipad|mobile)/i", $_SERVER['HTTP_USER_AGENT']);
        $viewToUse = $isMobile ? 'list' : $currentView;
        ?>
        
        <div class="employees-<?php echo $viewToUse; ?>-view">
          <?php mysqli_data_seek($emps, 0); while ($e = mysqli_fetch_assoc($emps)): ?>
            
            <?php if ($viewToUse === 'grid'): ?>
              <!-- Grid View Card -->
              <article class="employee-card record" style="background: rgba(20,20,20,0.8); border: 1px solid rgba(255,215,0,0.2); border-radius: 12px; padding: 1rem; margin-bottom: 1rem;">
                <div class="meta" style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                  <div class="avatar" style="width: 50px; height: 50px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #0b0b0b; font-size: 1.2rem; border: 2px solid #FFD000; overflow: hidden;">
                    <?php if (!empty($e['profile_image']) && file_exists(__DIR__ . '/uploads/' . $e['profile_image'])): ?>
                      <img src="uploads/<?php echo htmlspecialchars($e['profile_image']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                      <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #FFD700, #FFA500); display: flex; align-items: center; justify-content: center;">
                        <?php echo strtoupper(substr($e['first_name'],0,1) . substr($e['last_name'],0,1)); ?>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div class="info">
                    <div class="name" style="font-weight: 600; color: white; font-size: 1.1rem;">
                      <?php echo htmlspecialchars($e['last_name'] . ', ' . $e['first_name']); ?>
                    </div>
                    <div class="sub" style="color: rgba(255,255,255,0.7); font-size: 0.9rem;">
                      <?php echo htmlspecialchars($e['employee_code']); ?> • <?php echo htmlspecialchars($e['position']); ?>
                    </div>
                    <div class="sub" style="margin-top:6px; color:rgba(255,255,255,0.6); font-size:.85rem;">
                      <?php echo htmlspecialchars($e['email']); ?>
                    </div>
                  </div>
                </div>

                <div class="card-actions" style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                  <form method="POST" style="display:inline-block;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo $e['id']; ?>">
                    <button class="action-icon" onclick="return confirm('Remove employee?')" title="Delete" style="background: rgba(255,68,68,0.1); color: #ff4444; border: 1px solid rgba(255,68,68,0.3); padding: 0.5rem; border-radius: 6px; cursor: pointer;">
                      <i class="fa-solid fa-trash"></i>
                    </button>
                  </form>

                  <details>
                    <summary class="action-icon" title="Edit" style="background: rgba(255,215,0,0.1); color: #FFD700; border: 1px solid rgba(255,215,0,0.3); padding: 0.5rem; border-radius: 6px; cursor: pointer; list-style: none;">
                      <i class="fa-solid fa-pen-to-square"></i>
                    </summary>
                    <form method="POST" class="edit-form card" style="margin-top:8px;padding:12px; background: rgba(30,30,30,0.9); border: 1px solid rgba(255,215,0,0.3); border-radius: 8px;">
                      <input type="hidden" name="action" value="update">
                      <input type="hidden" name="id" value="<?php echo $e['id']; ?>">
                      <div class="form-row" style="margin-bottom: 0.75rem;">
                        <input name="employee_code" value="<?php echo htmlspecialchars($e['employee_code']); ?>" placeholder="Code" style="width: 100%; padding: 0.5rem; border: 1px solid rgba(255,215,0,0.3); border-radius: 6px; background: rgba(0,0,0,0.5); color: white;">
                      </div>
                      <div class="form-row" style="margin-bottom: 0.75rem;">
                        <input name="first_name" value="<?php echo htmlspecialchars($e['first_name']); ?>" placeholder="First name" style="width: 100%; padding: 0.5rem; border: 1px solid rgba(255,215,0,0.3); border-radius: 6px; background: rgba(0,0,0,0.5); color: white;">
                      </div>
                      <div class="form-row" style="margin-bottom: 0.75rem;">
                        <input name="last_name" value="<?php echo htmlspecialchars($e['last_name']); ?>" placeholder="Last name" style="width: 100%; padding: 0.5rem; border: 1px solid rgba(255,215,0,0.3); border-radius: 6px; background: rgba(0,0,0,0.5); color: white;">
                      </div>
                      <div class="form-row" style="margin-bottom: 0.75rem;">
                        <input name="email" value="<?php echo htmlspecialchars($e['email']); ?>" placeholder="Email" type="email" style="width: 100%; padding: 0.5rem; border: 1px solid rgba(255,215,0,0.3); border-radius: 6px; background: rgba(0,0,0,0.5); color: white;">
                      </div>
                      <div class="form-row" style="margin-bottom: 0.75rem;">
                        <input name="position" value="<?php echo htmlspecialchars($e['position']); ?>" placeholder="Position" style="width: 100%; padding: 0.5rem; border: 1px solid rgba(255,215,0,0.3); border-radius: 6px; background: rgba(0,0,0,0.5); color: white;">
                      </div>
                      <div style="text-align:right;margin-top:8px;">
                        <button class="btn add-btn" style="background: linear-gradient(135deg, #FFD700, #FFA500); color: #0b0b0b; border: none; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 600;">Save</button>
                      </div>
                    </form>
                  </details>
                </div>
              </article>

            <?php elseif ($viewToUse === 'list'): ?>
              <!-- List View Card -->
              <article class="employee-card-list">
                <div class="avatar" style="border: 2px solid #FFD000; border-radius: 8px; overflow: hidden;">
                  <?php if (!empty($e['profile_image']) && file_exists(__DIR__ . '/uploads/' . $e['profile_image'])): ?>
                    <img src="uploads/<?php echo htmlspecialchars($e['profile_image']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                  <?php else: ?>
                    <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #FFD700, #FFA500); display: flex; align-items: center; justify-content: center; font-weight: bold; color: #0b0b0b; font-size: 1.2rem;">
                      <?php echo strtoupper(substr($e['first_name'],0,1) . substr($e['last_name'],0,1)); ?>
                    </div>
                  <?php endif; ?>
                </div>
                <div class="info">
                  <div class="name" style="font-weight: 600; color: white; font-size: 1rem;">
                    <?php echo htmlspecialchars($e['last_name'] . ', ' . $e['first_name']); ?>
                  </div>
                  <div class="sub" style="color: rgba(255,255,255,0.7); font-size: 0.9rem;">
                    <?php echo htmlspecialchars($e['employee_code']); ?> • <?php echo htmlspecialchars($e['position']); ?>
                  </div>
                  <div class="sub" style="color:rgba(255,255,255,0.6); font-size:.85rem;">
                    <?php echo htmlspecialchars($e['email']); ?>
                  </div>
                </div>
                <div class="card-actions">
                  <form method="POST" style="display:inline-block;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo $e['id']; ?>">
                    <button class="action-icon" onclick="return confirm('Remove employee?')" title="Delete" style="background: rgba(255,68,68,0.1); color: #ff4444; border: 1px solid rgba(255,68,68,0.3); padding: 0.5rem; border-radius: 6px; cursor: pointer;">
                      <i class="fa-solid fa-trash"></i>
                    </button>
                  </form>

                  <details>
                    <summary class="action-icon" title="Edit" style="background: rgba(255,215,0,0.1); color: #FFD700; border: 1px solid rgba(255,215,0,0.3); padding: 0.5rem; border-radius: 6px; cursor: pointer; list-style: none;">
                      <i class="fa-solid fa-pen-to-square"></i>
                    </summary>
                    <form method="POST" enctype="multipart/form-data" class="edit-form card" style="margin-top:8px;padding:12px; background: rgba(30,30,30,0.9); border: 1px solid rgba(255,215,0,0.3); border-radius: 8px; position: absolute; z-index: 10;">
                      <input type="hidden" name="action" value="update">
                      <input type="hidden" name="id" value="<?php echo $e['id']; ?>">
                      <div class="form-row" style="margin-bottom: 0.75rem;">
                        <input name="employee_code" value="<?php echo htmlspecialchars($e['employee_code']); ?>" placeholder="Code" style="width: 100%; padding: 0.5rem; border: 1px solid rgba(255,215,0,0.3); border-radius: 6px; background: rgba(0,0,0,0.5); color: white;">
                      </div>
                      <div class="form-row" style="margin-bottom: 0.75rem;">
                        <input name="first_name" value="<?php echo htmlspecialchars($e['first_name']); ?>" placeholder="First name" style="width: 100%; padding: 0.5rem; border: 1px solid rgba(255,215,0,0.3); border-radius: 6px; background: rgba(0,0,0,0.5); color: white;">
                      </div>
                      <div class="form-row" style="margin-bottom: 0.75rem;">
                        <input name="last_name" value="<?php echo htmlspecialchars($e['last_name']); ?>" placeholder="Last name" style="width: 100%; padding: 0.5rem; border: 1px solid rgba(255,215,0,0.3); border-radius: 6px; background: rgba(0,0,0,0.5); color: white;">
                      </div>
                      <div class="form-row" style="margin-bottom: 0.75rem;">
                        <input name="email" value="<?php echo htmlspecialchars($e['email']); ?>" placeholder="Email" type="email" style="width: 100%; padding: 0.5rem; border: 1px solid rgba(255,215,0,0.3); border-radius: 6px; background: rgba(0,0,0,0.5); color: white;">
                      </div>
                      <div class="form-row" style="margin-bottom: 0.75rem;">
                        <input name="position" value="<?php echo htmlspecialchars($e['position']); ?>" placeholder="Position" style="width: 100%; padding: 0.5rem; border: 1px solid rgba(255,215,0,0.3); border-radius: 6px; background: rgba(0,0,0,0.5); color: white;">
                      </div>
                      <div class="form-row" style="margin-bottom: 0.75rem;">
                        <label for="profile_image_list_<?php echo $e['id']; ?>" style="color: white; display: block; margin-bottom: 0.5rem;">Profile Image:</label>
                        <input type="file" id="profile_image_list_<?php echo $e['id']; ?>" name="profile_image" accept="image/*" style="width: 100%; padding: 0.5rem; border: 1px solid rgba(255,215,0,0.3); border-radius: 6px; background: rgba(0,0,0,0.5); color: white;">
                        <small style="color: rgba(255,255,255,0.6); font-size: 0.8rem;">Accepted formats: JPG, PNG, GIF, WebP (max 5MB)</small>
                      </div>
                      <div style="text-align:right;margin-top:8px;">
                        <button class="btn add-btn" style="background: linear-gradient(135deg, #FFD700, #FFA500); color: #0b0b0b; border: none; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 600;">Save</button>
                      </div>
                    </form>
                  </details>
                </div>
              </article>

            <?php elseif ($viewToUse === 'details'): ?>
              <!-- Details View Card -->
              <article class="employee-card-details">
                <div class="details-header">
                  <div class="avatar" style="border: 2px solid #FFD000; border-radius: 8px; overflow: hidden;">
                    <?php if (!empty($e['profile_image']) && file_exists(__DIR__ . '/uploads/' . $e['profile_image'])): ?>
                      <img src="uploads/<?php echo htmlspecialchars($e['profile_image']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                      <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #FFD700, #FFA500); display: flex; align-items: center; justify-content: center; font-weight: bold; color: #0b0b0b; font-size: 1.2rem;">
                        <?php echo strtoupper(substr($e['first_name'],0,1) . substr($e['last_name'],0,1)); ?>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div>
                    <div class="name" style="font-weight: 600; color: white; font-size: 1.1rem;">
                      <?php echo htmlspecialchars($e['last_name'] . ', ' . $e['first_name']); ?>
                    </div>
                    <div class="sub" style="color: #FFD700; font-size: 0.9rem;">
                      <?php echo htmlspecialchars($e['employee_code']); ?>
                    </div>
                  </div>
                </div>
                
                <div class="details-body">
                  <div class="detail-item">
                    <div class="detail-label">Position</div>
                    <div class="detail-value"><?php echo htmlspecialchars($e['position']); ?></div>
                  </div>

                  <div class="detail-item">
                    <div class="detail-label">Status</div>
                    <div class="detail-value">
                      <span style="color: #4ade80;"><?php echo htmlspecialchars($e['status']); ?></span>
                    </div>
                  </div>
                  <div class="detail-item">
                    <div class="detail-label">Email</div>
                    <div class="detail-value"><?php echo htmlspecialchars($e['email']); ?></div>
                  </div>
                </div>

                <div class="card-actions">
                  <form method="POST" style="display:inline-block;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo $e['id']; ?>">
                    <button class="action-icon" onclick="return confirm('Remove employee?')" title="Delete" style="background: rgba(255,68,68,0.1); color: #ff4444; border: 1px solid rgba(255,68,68,0.3); padding: 0.5rem; border-radius: 6px; cursor: pointer;">
                      <i class="fa-solid fa-trash"></i>
                    </button>
                  </form>

                  <details>
                    <summary class="action-icon" title="Edit" style="background: rgba(255,215,0,0.1); color: #FFD700; border: 1px solid rgba(255,215,0,0.3); padding: 0.5rem; border-radius: 6px; cursor: pointer; list-style: none;">
                      <i class="fa-solid fa-pen-to-square"></i>
                    </summary>
                    <form method="POST" class="edit-form card" style="margin-top:8px;padding:12px; background: rgba(30,30,30,0.9); border: 1px solid rgba(255,215,0,0.3); border-radius: 8px;">
                      <input type="hidden" name="action" value="update">
                      <input type="hidden" name="id" value="<?php echo $e['id']; ?>">
                      <div class="form-row" style="margin-bottom: 0.75rem;">
                        <input name="employee_code" value="<?php echo htmlspecialchars($e['employee_code']); ?>" placeholder="Code" style="width: 100%; padding: 0.5rem; border: 1px solid rgba(255,215,0,0.3); border-radius: 6px; background: rgba(0,0,0,0.5); color: white;">
                      </div>
                      <div class="form-row" style="margin-bottom: 0.75rem;">
                        <input name="first_name" value="<?php echo htmlspecialchars($e['first_name']); ?>" placeholder="First name" style="width: 100%; padding: 0.5rem; border: 1px solid rgba(255,215,0,0.3); border-radius: 6px; background: rgba(0,0,0,0.5); color: white;">
                      </div>
                      <div class="form-row" style="margin-bottom: 0.75rem;">
                        <input name="last_name" value="<?php echo htmlspecialchars($e['last_name']); ?>" placeholder="Last name" style="width: 100%; padding: 0.5rem; border: 1px solid rgba(255,215,0,0.3); border-radius: 6px; background: rgba(0,0,0,0.5); color: white;">
                      </div>
                      <div class="form-row" style="margin-bottom: 0.75rem;">
                        <input name="email" value="<?php echo htmlspecialchars($e['email']); ?>" placeholder="Email" type="email" style="width: 100%; padding: 0.5rem; border: 1px solid rgba(255,215,0,0.3); border-radius: 6px; background: rgba(0,0,0,0.5); color: white;">
                      </div>
                      <div class="form-row" style="margin-bottom: 0.75rem;">
                        <input name="position" value="<?php echo htmlspecialchars($e['position']); ?>" placeholder="Position" style="width: 100%; padding: 0.5rem; border: 1px solid rgba(255,215,0,0.3); border-radius: 6px; background: rgba(0,0,0,0.5); color: white;">
                      </div>
                      <div class="form-row" style="margin-bottom: 0.75rem;">
                        <label for="profile_image_details_<?php echo $e['id']; ?>" style="color: white; display: block; margin-bottom: 0.5rem;">Profile Image:</label>
                        <input type="file" id="profile_image_details_<?php echo $e['id']; ?>" name="profile_image" accept="image/*" style="width: 100%; padding: 0.5rem; border: 1px solid rgba(255,215,0,0.3); border-radius: 6px; background: rgba(0,0,0,0.5); color: white;">
                        <small style="color: rgba(255,255,255,0.6); font-size: 0.8rem;">Accepted formats: JPG, PNG, GIF, WebP (max 5MB)</small>
                      </div>
                      <div style="text-align:right;margin-top:8px;">
                        <button class="btn add-btn" style="background: linear-gradient(135deg, #FFD700, #FFA500); color: #0b0b0b; border: none; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 600;">Save</button>
                      </div>
                    </form>
                  </details>
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
    // Modal functionality
    const openAddDesktop = document.getElementById('openAddDesktop');
    const openAddMobile = document.getElementById('openAddMobile');
    const addModal = document.getElementById('addModal');
    const closeAdd = document.getElementById('closeAdd');
    
    function openModal() {
      addModal.style.display = 'flex';
    }
    
    function closeModal() {
      addModal.style.display = 'none';
    }
    
    openAddDesktop?.addEventListener('click', openModal);
    openAddMobile?.addEventListener('click', openModal);
    closeAdd?.addEventListener('click', closeModal);
    
    addModal?.addEventListener('click', (e) => {
      if(e.target === addModal) {
        closeModal();
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

    // Pagination functions
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