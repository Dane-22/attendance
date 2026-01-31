<?php
// employee/documents.php
require('../conn/db_connection.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}
// Check if super_admin (redirect to admin dashboard if super_admin)
if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin') {
    header('Location: ../admin/dashboard.php');
    exit();
}

// ==================== RATE LIMITER ====================
class RateLimiter {
    private $db;
    private $limit = 10; // Default limit per page
    private $maxRequestsPerMinute = 60; // Max requests per minute
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function checkRateLimit($userId) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $currentTime = time();
        $minuteAgo = $currentTime - 60;
        
        // Clean old records
        $cleanStmt = $this->db->prepare("DELETE FROM rate_limit WHERE timestamp < ?");
        $cleanStmt->bind_param("i", $minuteAgo);
        $cleanStmt->execute();
        $cleanStmt->close();
        
        // Count requests in last minute
        $countStmt = $this->db->prepare("SELECT COUNT(*) as count FROM rate_limit WHERE ip = ? AND timestamp > ?");
        $countStmt->bind_param("si", $ip, $minuteAgo);
        $countStmt->execute();
        $result = $countStmt->get_result()->fetch_assoc();
        $countStmt->close();
        
        if ($result['count'] >= $this->maxRequestsPerMinute) {
            http_response_code(429);
            die("Rate limit exceeded. Please wait a minute before trying again.");
        }
        
        // Log this request
        $logStmt = $this->db->prepare("INSERT INTO rate_limit (ip, user_id, timestamp) VALUES (?, ?, ?)");
        $logStmt->bind_param("sii", $ip, $userId, $currentTime);
        $logStmt->execute();
        $logStmt->close();
        
        return true;
    }
    
    public function setLimit($limit) {
        $this->limit = max(1, min(100, $limit)); // Limit between 1-100
        return $this->limit;
    }
    
    public function getLimit() {
        return $this->limit;
    }
}

// Create rate limit table if not exists
$db->query("CREATE TABLE IF NOT EXISTS rate_limit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    user_id INT NOT NULL,
    timestamp INT NOT NULL,
    INDEX idx_ip_timestamp (ip, timestamp)
)");

// Initialize rate limiter
$rateLimiter = new RateLimiter($db);
$userId = $_SESSION['user_id'] ?? 0;
$rateLimiter->checkRateLimit($userId);

// ==================== PAGINATION CONFIGURATION ====================
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10; // Default to 10 per page
$limit = $rateLimiter->setLimit($limit); // Apply rate limiter limit

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Get current view preference from session or default to grid
$currentView = $_SESSION['document_view'] ?? 'grid';

// Handle view change request
if (isset($_GET['view'])) {
    $view = $_GET['view'];
    if (in_array($view, ['grid', 'list', 'details'])) {
        $_SESSION['document_view'] = $view;
        $currentView = $view;
    }
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    $employeeId = $_POST['employee_id'];
    $documentType = $_POST['document_type'] ?? 'other';
    $file = $_FILES['document'];
    
    $allowedTypes = [
        'application/pdf' => 'pdf', 
        'image/png' => 'image', 
        'image/jpeg' => 'image', 
        'image/jpg' => 'image',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'doc'
    ];
    
    if (in_array($file['type'], array_keys($allowedTypes))) {
        $category = $allowedTypes[$file['type']];
        $timestamp = date('YmdHis');
        $newName = $employeeId . '_' . $timestamp . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($file['name']));
        $uploadPath = '../uploads/' . $newName;
        
        if (!file_exists('../uploads')) {
            mkdir('../uploads', 0777, true);
        }
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $checkStmt = $db->prepare("SELECT id FROM documents WHERE employee_id = ? AND document_type = ?");
            $checkStmt->bind_param("is", $employeeId, $documentType);
            $checkStmt->execute();
            $existingDoc = $checkStmt->get_result()->fetch_assoc();
            $checkStmt->close();
            
            if ($existingDoc) {
                $stmt = $db->prepare("UPDATE documents SET document_name = ?, category = ?, file_path = ?, upload_date = NOW() WHERE id = ?");
                $stmt->bind_param("sssi", $file['name'], $category, $uploadPath, $existingDoc['id']);
            } else {
                $stmt = $db->prepare("INSERT INTO documents (employee_id, document_name, document_type, category, file_path) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("issss", $employeeId, $file['name'], $documentType, $category, $uploadPath);
            }
            
            if ($stmt->execute()) {
                $msg = 'Document uploaded successfully!';
            }
            $stmt->close();
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $employeeId = isset($_GET['emp']) ? intval($_GET['emp']) : null;
    
    $stmt = $db->prepare("SELECT file_path FROM documents WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($result) {
        if (file_exists($result['file_path'])) {
            unlink($result['file_path']);
        }
        $deleteStmt = $db->prepare("DELETE FROM documents WHERE id = ?");
        $deleteStmt->bind_param("i", $id);
        $deleteStmt->execute();
        $deleteStmt->close();
        
        $msg = 'Document deleted successfully!';
    }
}

// ==================== FETCH DATA WITH PAGINATION ====================
// Fetch employees with pagination
$employeesQuery = "SELECT id, employee_code, first_name, middle_name, last_name, position, profile_image FROM employees ORDER BY last_name, first_name LIMIT ? OFFSET ?";
$employeesStmt = $db->prepare($employeesQuery);
$employeesStmt->bind_param("ii", $limit, $offset);
$employeesStmt->execute();
$employees = $employeesStmt->get_result();

// Get total count for pagination
$totalCountStmt = $db->query("SELECT COUNT(*) as total FROM employees");
$totalCount = $totalCountStmt->fetch_assoc()['total'];
$totalCountStmt->close();

// Calculate total pages
$totalPages = ceil($totalCount / $limit);

// Fetch documents for selected employee with pagination
$selectedEmp = isset($_GET['emp']) ? intval($_GET['emp']) : null;
$documents = null;
$empDetails = null;

if ($selectedEmp) {
    $stmt = $db->prepare("SELECT id, employee_code, first_name, middle_name, last_name, email, position, status, daily_rate, profile_image FROM employees WHERE id = ?");
    $stmt->bind_param("i", $selectedEmp);
    $stmt->execute();
    $empDetails = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // Get documents count for this employee
    $docCountStmt = $db->prepare("SELECT COUNT(*) as total FROM documents WHERE employee_id = ?");
    $docCountStmt->bind_param("i", $selectedEmp);
    $docCountStmt->execute();
    $docTotal = $docCountStmt->get_result()->fetch_assoc()['total'];
    $docCountStmt->close();
    $docTotalPages = ceil($docTotal / $limit);
    
    // Fetch documents with pagination
    $docQuery = "SELECT * FROM documents WHERE employee_id = ? ORDER BY 
        CASE document_type 
            WHEN 'sss' THEN 1
            WHEN 'tin' THEN 2
            WHEN 'philhealth' THEN 3
            WHEN 'pagibig' THEN 4
            WHEN 'birth_certificate' THEN 5
            WHEN 'diploma' THEN 6
            WHEN 'resume' THEN 7
            ELSE 8
        END, upload_date DESC LIMIT ? OFFSET ?";
    
    $docStmt = $db->prepare($docQuery);
    $docStmt->bind_param("iii", $selectedEmp, $limit, $offset);
    $docStmt->execute();
    $documents = $docStmt->get_result();
    
    $requiredDocs = ['sss', 'tin', 'philhealth', 'pagibig', 'birth_certificate', 'diploma'];
    $docStatus = [];
    foreach ($requiredDocs as $docType) {
        $checkStmt = $db->prepare("SELECT id FROM documents WHERE employee_id = ? AND document_type = ?");
        $checkStmt->bind_param("is", $selectedEmp, $docType);
        $checkStmt->execute();
        $docStatus[$docType] = $checkStmt->get_result()->fetch_assoc() ? true : false;
        $checkStmt->close();
    }
}

// Function to build query string with pagination
function buildQueryString($params = []) {
    $currentParams = $_GET;
    unset($currentParams['page']); // Remove page from current params
    $mergedParams = array_merge($currentParams, $params);
    return http_build_query($mergedParams);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Digital 201 File Manager — JAJR</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .dark-engineering { background: #0b0b0b; color: white; min-height: 100vh; }
    .main-content { margin-left: 280px; padding: 2rem; min-height: 100vh; }
    @media (max-width: 768px) { .main-content { margin-left: 0; padding: 1rem; } }
    
    .glass-card { background: rgba(20,20,20,0.8); border: 1px solid rgba(255,215,0,0.2); border-radius: 12px; }
    .gold-gradient { background: linear-gradient(135deg, #FFD700, #FFA500); color: #0b0b0b; }
    .gold-border { border: 1px solid rgba(255,215,0,0.3); }
    .gold-text { color: #FFD700; }
    
    /* View Options Styles */
    .view-options-container {
        background: rgba(20,20,20,0.8);
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
    
    /* Pagination Styles */
    .pagination-container {
        background: rgba(20,20,20,0.8);
        border: 1px solid rgba(255,215,0,0.2);
        border-radius: 12px;
        padding: 16px 20px;
        margin-top: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }

    .pagination-info {
        color: rgba(255,255,255,0.7);
        font-size: 14px;
    }

    .pagination-controls {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .pagination-btn {
        background: rgba(30,30,30,0.8);
        border: 1px solid rgba(255,215,0,0.3);
        border-radius: 6px;
        padding: 8px 16px;
        color: #FFD700;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .pagination-btn:hover {
        background: rgba(255,215,0,0.1);
        border-color: rgba(255,215,0,0.5);
    }

    .pagination-btn.disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .page-numbers {
        display: flex;
        gap: 4px;
    }

    .page-number {
        background: rgba(30,30,30,0.8);
        border: 1px solid rgba(255,215,0,0.3);
        border-radius: 6px;
        padding: 8px 12px;
        color: #FFD700;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        min-width: 40px;
        text-align: center;
    }

    .page-number:hover {
        background: rgba(255,215,0,0.1);
    }

    .page-number.active {
        background: linear-gradient(135deg, #FFD700, #FFA500);
        border-color: #FFD700;
        color: #000;
    }

    .limit-selector {
        background: rgba(30,30,30,0.8);
        border: 1px solid rgba(255,215,0,0.3);
        border-radius: 6px;
        padding: 8px 12px;
        color: #FFD700;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
    }

    .limit-selector:focus {
        outline: none;
        border-color: rgba(255,215,0,0.5);
    }

    .rate-limit-info {
        font-size: 12px;
        color: rgba(255,215,0,0.7);
        text-align: center;
        margin-top: 5px;
    }
    
    /* Employee View Styles */
    .employees-grid-view { 
        display: grid; 
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); 
        gap: 1.5rem; 
    }
    
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
        grid-template-columns: auto 1fr auto;
        align-items: center;
        gap: 20px;
        transition: transform 0.3s ease;
    }
    
    .employee-card-list:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255,215,0,0.1);
    }
    
    .employees-details-view {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 1.5rem;
    }
    
    .employee-card-details {
        background: rgba(20,20,20,0.8);
        border: 1px solid rgba(255,215,0,0.2);
        border-radius: 12px;
        padding: 1.5rem;
        transition: transform 0.3s ease;
    }
    
    .employee-card-details:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(255,215,0,0.1);
    }
    
    .employee-card { transition: transform 0.3s ease, box-shadow 0.3s ease; }
    .employee-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(255,215,0,0.1); }
    
    /* Documents View Styles */
    .documents-grid-view { 
        display: grid; 
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); 
        gap: 1.5rem; 
    }
    
    .documents-list-view {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    
    .document-card-list {
        background: rgba(20,20,20,0.8);
        border: 1px solid rgba(255,215,0,0.2);
        border-radius: 12px;
        padding: 1rem;
        display: grid;
        grid-template-columns: auto 1fr auto auto;
        align-items: center;
        gap: 20px;
        transition: all 0.3s ease;
    }
    
    .document-card-list:hover {
        box-shadow: 0 5px 15px rgba(255,215,0,0.2);
    }
    
    .documents-details-view {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 1.5rem;
    }
    
    .document-card { transition: all 0.3s ease; }
    .document-card:hover { box-shadow: 0 5px 15px rgba(255,215,0,0.2); }
    
    .status-present { color: #10B981; }
    .status-missing { color: #EF4444; }
    
    .avatar-initials { 
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
    
    /* Document Details View Styles */
    .document-card-details .details-body {
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
    
    .fab { 
        position: fixed; 
        bottom: 2rem; 
        right: 2rem; 
        width: 60px; 
        height: 60px; 
        border-radius: 50%; 
        background: linear-gradient(135deg, #FFD700, #FFA500); 
        color: #0b0b0b; 
        border: none; 
        font-size: 1.5rem; 
        cursor: pointer; 
        z-index: 100; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
    }
    
    /* Responsive Styles */
    @media (max-width: 768px) {
        /* Force list view on mobile for employees */
        .employees-grid-view,
        .employees-details-view {
            grid-template-columns: 1fr;
        }
        
        .employee-card-list {
            grid-template-columns: 1fr;
            gap: 12px;
        }
        
        .employee-card-list .avatar-initials {
            width: 40px;
            height: 40px;
            font-size: 1rem;
        }
        
        /* Force list view on mobile for documents */
        .documents-grid-view,
        .documents-details-view {
            grid-template-columns: 1fr;
        }
        
        .document-card-list {
            grid-template-columns: 1fr;
            gap: 12px;
        }
        
        .document-card-list .icon-container {
            justify-self: center;
        }
        
        .document-card-list .actions {
            justify-self: center;
            display: flex;
            gap: 10px;
        }
        
        /* Adjust pagination for mobile */
        .pagination-container {
            flex-direction: column;
            gap: 12px;
        }
        
        .pagination-controls {
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .page-numbers {
            flex-wrap: wrap;
            justify-content: center;
        }
        
        /* Hide view options on mobile (force list view) */
        .view-options-container {
            display: none;
        }
        
        /* Adjust document details view for mobile */
        .document-card-details .details-body {
            grid-template-columns: 1fr;
        }
    }
    
    @media (max-width: 480px) {
        .employee-card-list {
            padding: 12px;
        }
        
        .employee-card-details {
            padding: 1rem;
        }
        
        .document-card-list {
            padding: 12px;
        }
        
        .document-card-details {
            padding: 1rem;
        }
        
        .pagination-btn {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .page-number {
            padding: 6px 10px;
            min-width: 30px;
            font-size: 12px;
        }
        
        .limit-selector {
            padding: 6px 10px;
            font-size: 12px;
        }
    }
  </style>
</head>
<body class="dark-engineering">
  <?php include 'sidebar.php'; ?>

  <main class="main-content" id="mainContent">
    <div class="container">
      <div class="header" style="margin-bottom: 2rem;">
        <h1 class="gold-text">Digital 201 File Manager</h1>
        <div class="text-muted">Manage employee documents and records</div>
        <!-- <div class="rate-limit-info">
          <i class="fas fa-shield-alt"></i> Rate limiter active | Max 60 requests/minute | Current limit: <?php echo $limit; ?> items/page
        </div> -->
      </div>

      <?php if ($msg): ?>
        <div class="glass-card" style="margin-bottom: 1rem; padding: 1rem; background: rgba(255,215,0,0.1); border-color: rgba(255,215,0,0.3);">
          <?php echo htmlspecialchars($msg); ?>
        </div>
      <?php endif; ?>

      <?php if (!$selectedEmp): ?>
        <!-- Phase 1: Employee Selection -->
        <div class="top-actions" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
          <div class="text-muted">Total Employees: <?php echo $totalCount; ?></div>
          <div style="display: flex; gap: 1rem;">
            <!-- Limit selector -->
            <form method="GET" action="" style="display: inline;">
              <select name="limit" class="limit-selector" onchange="this.form.submit()">
                <option value="5" <?php echo $limit == 5 ? 'selected' : ''; ?>>5 per page</option>
                <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10 per page</option>
                <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25 per page</option>
                <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50 per page</option>
                <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100 per page</option>
              </select>
              <?php if(isset($_GET['view'])): ?>
                <input type="hidden" name="view" value="<?php echo htmlspecialchars($_GET['view']); ?>">
              <?php endif; ?>
            </form>
            
            <button class="btn gold-gradient" style="border: none; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600;">
              <i class="fa-solid fa-filter"></i>&nbsp;Filter
            </button>
          </div>
        </div>

        <!-- View Options for Employee Selection -->
        <div class="view-options-container">
          <div class="view-options-title">View Options:</div>
          <div class="view-options">
            <a href="?<?php echo buildQueryString(['view' => 'grid', 'page' => 1]); ?>" class="view-option-btn <?php echo $currentView === 'grid' ? 'active' : ''; ?>">
              <i class="fas fa-th"></i>
              <span>Grid</span>
            </a>
            <a href="?<?php echo buildQueryString(['view' => 'list', 'page' => 1]); ?>" class="view-option-btn <?php echo $currentView === 'list' ? 'active' : ''; ?>">
              <i class="fas fa-list"></i>
              <span>List</span>
            </a>
            <a href="?<?php echo buildQueryString(['view' => 'details', 'page' => 1]); ?>" class="view-option-btn <?php echo $currentView === 'details' ? 'active' : ''; ?>">
              <i class="fas fa-info-circle"></i>
              <span>Details</span>
            </a>
          </div>
        </div>

        <section>
          <h2 style="margin-bottom: 1.5rem; color: #FFD700;">Select Employee</h2>
          
          <?php 
          // Check if mobile - force list view on mobile
          $isMobile = preg_match("/(android|iphone|ipad|mobile)/i", $_SERVER['HTTP_USER_AGENT']);
          $viewToUse = $isMobile ? 'list' : $currentView;
          ?>
          
          <div class="employees-<?php echo $viewToUse; ?>-view">
            <?php if ($employees && $employees->num_rows > 0): ?>
              <?php mysqli_data_seek($employees, 0); while ($emp = $employees->fetch_assoc()): ?>
            
            <?php if ($viewToUse === 'grid'): ?>
              <!-- Grid View Card -->
              <article class="employee-card glass-card" style="padding: 1.5rem;">
                <div class="meta" style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                  <?php if (!empty($emp['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($emp['profile_image']); ?>" 
                         alt="Profile" 
                         style="width: 50px; height: 50px; border-radius: 8px; object-fit: cover; border: 2px solid #FFD700;">
                  <?php else: ?>
                    <div class="avatar-initials">
                      <?php echo strtoupper(substr($emp['first_name'],0,1) . substr($emp['last_name'],0,1)); ?>
                    </div>
                  <?php endif; ?>
                  
                  <div class="info">
                    <div class="name" style="font-weight: 600; color: white; font-size: 1.1rem;">
                      <?php echo htmlspecialchars($emp['last_name'] . ', ' . $emp['first_name']); ?>
                    </div>
                    <div class="sub" style="color: rgba(255,255,255,0.7); font-size: 0.9rem;">
                      <?php echo htmlspecialchars($emp['employee_code']); ?> • <?php echo htmlspecialchars($emp['position']); ?>
                    </div>
                  </div>
                </div>

                <div style="text-align: center; margin-top: 1.5rem;">
                  <a href="?<?php echo buildQueryString(['emp' => $emp['id'], 'page' => 1]); ?>" 
                     class="btn gold-gradient" 
                     style="display: inline-block; width: 100%; text-align: center; text-decoration: none; border: none; padding: 0.75rem; border-radius: 8px; font-weight: 600;">
                    <i class="fa-solid fa-folder-open"></i>&nbsp;View Documents
                  </a>
                </div>
              </article>
            
            <?php elseif ($viewToUse === 'list'): ?>
              <!-- List View Card -->
              <article class="employee-card-list">
                <div class="icon-container">
                  <?php if (!empty($emp['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($emp['profile_image']); ?>" 
                         alt="Profile" 
                         style="width: 40px; height: 40px; border-radius: 8px; object-fit: cover; border: 2px solid #FFD700;">
                  <?php else: ?>
                    <div class="avatar-initials" style="width: 40px; height: 40px; font-size: 1rem;">
                      <?php echo strtoupper(substr($emp['first_name'],0,1) . substr($emp['last_name'],0,1)); ?>
                    </div>
                  <?php endif; ?>
                </div>
                
                <div class="info">
                  <div class="name" style="font-weight: 600; color: white; font-size: 1rem;">
                    <?php echo htmlspecialchars($emp['last_name'] . ', ' . $emp['first_name']); ?>
                  </div>
                  <div class="sub" style="color: rgba(255,255,255,0.7); font-size: 0.9rem;">
                    <?php echo htmlspecialchars($emp['employee_code']); ?> • <?php echo htmlspecialchars($emp['position']); ?>
                  </div>
                </div>
                
                <div class="actions">
                  <a href="?<?php echo buildQueryString(['emp' => $emp['id'], 'page' => 1]); ?>" 
                     class="btn gold-gradient" 
                     style="display: inline-block; text-align: center; text-decoration: none; border: none; padding: 0.5rem 1rem; border-radius: 8px; font-weight: 600; font-size: 0.875rem;">
                    <i class="fa-solid fa-folder-open"></i>&nbsp;View
                  </a>
                </div>
              </article>
            
            <?php elseif ($viewToUse === 'details'): ?>
              <!-- Details View Card -->
              <article class="employee-card-details">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                  <?php if (!empty($emp['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($emp['profile_image']); ?>" 
                         alt="Profile" 
                         style="width: 60px; height: 60px; border-radius: 8px; object-fit: cover; border: 2px solid #FFD700;">
                  <?php else: ?>
                    <div class="avatar-initials" style="width: 60px; height: 60px; font-size: 1.4rem;">
                      <?php echo strtoupper(substr($emp['first_name'],0,1) . substr($emp['last_name'],0,1)); ?>
                    </div>
                  <?php endif; ?>
                  
                  <div>
                    <div class="name" style="font-weight: 600; color: white; font-size: 1.2rem;">
                      <?php echo htmlspecialchars($emp['last_name'] . ', ' . $emp['first_name']); ?>
                    </div>
                    <div class="sub" style="color: #FFD700; font-size: 0.9rem;">
                      <?php echo htmlspecialchars($emp['employee_code']); ?>
                    </div>
                  </div>
                </div>
                
                <div class="details-body">
                  <div class="detail-item">
                    <div class="detail-label">Position</div>
                    <div class="detail-value"><?php echo htmlspecialchars($emp['position']); ?></div>
                  </div>
                  
                  <div class="detail-item">
                    <div class="detail-label">Name</div>
                    <div class="detail-value">
                      <?php 
                        $fullName = htmlspecialchars($emp['first_name']);
                        if (!empty($emp['middle_name'])) {
                          $fullName .= ' ' . htmlspecialchars($emp['middle_name']);
                        }
                        $fullName .= ' ' . htmlspecialchars($emp['last_name']);
                        echo $fullName;
                      ?>
                    </div>
                  </div>
                </div>

                <div style="text-align: center; margin-top: 1.5rem;">
                  <a href="?<?php echo buildQueryString(['emp' => $emp['id'], 'page' => 1]); ?>" 
                     class="btn gold-gradient" 
                     style="display: inline-block; width: 100%; text-align: center; text-decoration: none; border: none; padding: 0.75rem; border-radius: 8px; font-weight: 600;">
                    <i class="fa-solid fa-folder-open"></i>&nbsp;View Documents
                  </a>
                </div>
              </article>
            <?php endif; ?>
            
            <?php endwhile; ?>
            <?php else: ?>
              <div class="glass-card text-center" style="padding: 3rem; grid-column: 1 / -1;">
                <i class="fa-solid fa-users" style="font-size: 3rem; color: rgba(255,255,255,0.3); margin-bottom: 1rem;"></i>
                <h3 style="color: rgba(255,255,255,0.8); margin-bottom: 0.5rem;">No employees found</h3>
                <p style="color: rgba(255,255,255,0.6);">No employees match your criteria.</p>
              </div>
            <?php endif; ?>
          </div>

          <!-- Pagination for Employees -->
          <?php if ($totalPages > 1): ?>
          <div class="pagination-container">
            <div class="pagination-info">
              Showing <?php echo min($limit, $employees->num_rows); ?> of <?php echo $totalCount; ?> employees
              (Page <?php echo $page; ?> of <?php echo $totalPages; ?>)
            </div>
            
            <div class="pagination-controls">
              <!-- First Page -->
              <a href="?<?php echo buildQueryString(['page' => 1]); ?>" 
                 class="pagination-btn <?php echo $page == 1 ? 'disabled' : ''; ?>">
                <i class="fas fa-angle-double-left"></i> First
              </a>
              
              <!-- Previous Page -->
              <a href="?<?php echo buildQueryString(['page' => max(1, $page - 1)]); ?>" 
                 class="pagination-btn <?php echo $page == 1 ? 'disabled' : ''; ?>">
                <i class="fas fa-angle-left"></i> Prev
              </a>
              
              <!-- Page Numbers -->
              <div class="page-numbers">
                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                for ($i = $startPage; $i <= $endPage; $i++):
                ?>
                  <a href="?<?php echo buildQueryString(['page' => $i]); ?>" 
                     class="page-number <?php echo $i == $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                  </a>
                <?php endfor; ?>
              </div>
              
              <!-- Next Page -->
              <a href="?<?php echo buildQueryString(['page' => min($totalPages, $page + 1)]); ?>" 
                 class="pagination-btn <?php echo $page == $totalPages ? 'disabled' : ''; ?>">
                Next <i class="fas fa-angle-right"></i>
              </a>
              
              <!-- Last Page -->
              <a href="?<?php echo buildQueryString(['page' => $totalPages]); ?>" 
                 class="pagination-btn <?php echo $page == $totalPages ? 'disabled' : ''; ?>">
                Last <i class="fas fa-angle-double-right"></i>
              </a>
            </div>
          </div>
          <?php endif; ?>
        </section>

      <?php else: ?>
        <!-- Phase 2: Document Repository -->
        <div style="margin-bottom: 2rem;">
          <a href="?<?php echo buildQueryString(['emp' => null, 'page' => $page]); ?>" class="btn gold-border" style="text-decoration: none; padding: 0.5rem 1rem; border-radius: 6px; color: #FFD700;">
            <i class="fa-solid fa-arrow-left"></i>&nbsp;Back to Employees
          </a>
        </div>

        <!-- Employee Profile Header -->
        <div class="glass-card" style="padding: 2rem; margin-bottom: 2rem;">
          <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
            <!-- Profile Image -->
            <div class="flex-shrink-0">
              <?php if (!empty($empDetails['profile_image'])): ?>
                <img src="<?php echo htmlspecialchars($empDetails['profile_image']); ?>" 
                     alt="Profile" 
                     style="width: 80px; height: 80px; border-radius: 12px; object-fit: cover; border: 3px solid #FFD700;">
              <?php else: ?>
                <div class="avatar-initials" style="width: 80px; height: 80px; font-size: 1.5rem;">
                  <?php echo strtoupper(substr($empDetails['first_name'],0,1) . substr($empDetails['last_name'],0,1)); ?>
                </div>
              <?php endif; ?>
            </div>
            
            <!-- Employee Details -->
            <div class="flex-grow">
              <h2 class="gold-text" style="font-size: 1.75rem; margin-bottom: 0.5rem;">
                <i class="fa-solid fa-user"></i>&nbsp;
                <?php 
                  $fullName = htmlspecialchars($empDetails['first_name']);
                  if (!empty($empDetails['middle_name'])) {
                    $fullName .= ' ' . htmlspecialchars($empDetails['middle_name']);
                  }
                  $fullName .= ' ' . htmlspecialchars($empDetails['last_name']);
                  echo $fullName;
                ?>
              </h2>
              
              <!-- Quick Info -->
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                <div class="glass-card" style="padding: 1rem;">
                  <p class="text-sm" style="color: rgba(255,255,255,0.6); margin-bottom: 0.25rem;">
                    <i class="fa-solid fa-id-badge"></i>&nbsp;Employee Code
                  </p>
                  <p style="font-size: 1.1rem; font-weight: 600;"><?php echo htmlspecialchars($empDetails['employee_code']); ?></p>
                </div>
                
                <?php if (!empty($empDetails['position'])): ?>
                <div class="glass-card" style="padding: 1rem;">
                  <p class="text-sm" style="color: rgba(255,255,255,0.6); margin-bottom: 0.25rem;">
                    <i class="fa-solid fa-briefcase"></i>&nbsp;Position
                  </p>
                  <p style="font-size: 1.1rem; font-weight: 600;"><?php echo htmlspecialchars($empDetails['position']); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($empDetails['email'])): ?>
                <div class="glass-card" style="padding: 1rem;">
                  <p class="text-sm" style="color: rgba(255,255,255,0.6); margin-bottom: 0.25rem;">
                    <i class="fa-solid fa-envelope"></i>&nbsp;Email
                  </p>
                  <p style="font-size: 1.1rem; font-weight: 600;"><?php echo htmlspecialchars($empDetails['email']); ?></p>
                </div>
                <?php endif; ?>
              </div>
              
              <!-- Required Documents Status -->
              <div class="mt-6">
                <h3 class="gold-text" style="font-size: 1.25rem; margin-bottom: 1rem;">
                  <i class="fa-solid fa-file-alt"></i>&nbsp;Required Documents Status
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
                  <?php
                  $docLabels = [
                      'sss' => 'SSS',
                      'tin' => 'TIN',
                      'philhealth' => 'PhilHealth',
                      'pagibig' => 'Pag-IBIG',
                      'birth_certificate' => 'Birth Cert',
                      'diploma' => 'Diploma'
                  ];
                  
                  foreach ($docLabels as $docType => $label):
                  ?>
                  <div class="glass-card text-center" style="padding: 0.75rem;">
                    <p class="text-sm" style="color: rgba(255,255,255,0.6); margin-bottom: 0.5rem;"><?php echo $label; ?></p>
                    <p class="text-lg <?php echo $docStatus[$docType] ? 'status-present' : 'status-missing'; ?>">
                      <i class="fa-solid fa-<?php echo $docStatus[$docType] ? 'check-circle' : 'times-circle'; ?>"></i>
                    </p>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- View Options for Documents -->
        <div class="view-options-container">
          <div class="view-options-title">Document View Options:</div>
          <div class="view-options">
            <a href="?<?php echo buildQueryString(['view' => 'grid', 'page' => 1]); ?>" class="view-option-btn <?php echo $currentView === 'grid' ? 'active' : ''; ?>">
              <i class="fas fa-th"></i>
              <span>Grid</span>
            </a>
            <a href="?<?php echo buildQueryString(['view' => 'list', 'page' => 1]); ?>" class="view-option-btn <?php echo $currentView === 'list' ? 'active' : ''; ?>">
              <i class="fas fa-list"></i>
              <span>List</span>
            </a>
            <a href="?<?php echo buildQueryString(['view' => 'details', 'page' => 1]); ?>" class="view-option-btn <?php echo $currentView === 'details' ? 'active' : ''; ?>">
              <i class="fas fa-info-circle"></i>
              <span>Details</span>
            </a>
          </div>
          
          <div>
            <!-- Limit selector for documents -->
            <form method="GET" action="" style="display: inline;">
              <select name="limit" class="limit-selector" onchange="this.form.submit()">
                <option value="5" <?php echo $limit == 5 ? 'selected' : ''; ?>>5 per page</option>
                <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10 per page</option>
                <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25 per page</option>
                <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50 per page</option>
                <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100 per page</option>
              </select>
              <input type="hidden" name="emp" value="<?php echo $selectedEmp; ?>">
              <?php if(isset($_GET['view'])): ?>
                <input type="hidden" name="view" value="<?php echo htmlspecialchars($_GET['view']); ?>">
              <?php endif; ?>
            </form>
          </div>
        </div>

        <!-- Documents Section -->
        <section>
          <div class="top-actions" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 class="gold-text" style="margin: 0;">
              <i class="fa-solid fa-folder"></i>&nbsp;Documents
              <?php if (isset($docTotal)): ?>
                <span style="font-size: 0.875rem; color: rgba(255,255,255,0.6); margin-left: 0.5rem;">
                  (<?php echo $docTotal; ?> total files | Showing <?php echo min($limit, $documents ? $documents->num_rows : 0); ?> per page)
                </span>
              <?php endif; ?>
            </h2>
            
            <button class="btn gold-gradient" id="openAddDesktop" style="border: none; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600;">
              <i class="fa-solid fa-upload"></i>&nbsp;Add Document
            </button>
          </div>

          <?php 
          // Check if mobile - force list view on mobile
          $isMobile = preg_match("/(android|iphone|ipad|mobile)/i", $_SERVER['HTTP_USER_AGENT']);
          $docViewToUse = $isMobile ? 'list' : $currentView;
          ?>
          
          <div class="documents-<?php echo $docViewToUse; ?>-view">
            <?php if ($documents && $documents->num_rows > 0): ?>
              <?php mysqli_data_seek($documents, 0); while ($doc = $documents->fetch_assoc()): ?>
              
              <?php if ($docViewToUse === 'grid'): ?>
                <!-- Grid View Document Card -->
                <div class="document-card glass-card" style="padding: 1.5rem;">
                  <div class="flex justify-between items-start mb-3">
                    <div>
                      <?php
                      $iconClass = '';
                      $iconColor = '';
                      switch($doc['category']) {
                          case 'pdf':
                              $iconClass = 'fa-solid fa-file-pdf';
                              $iconColor = '#EF4444';
                              break;
                          case 'image':
                              $iconClass = 'fa-solid fa-image';
                              $iconColor = '#3B82F6';
                              break;
                          case 'doc':
                              $iconClass = 'fa-solid fa-file-word';
                              $iconColor = '#10B981';
                              break;
                          default:
                              $iconClass = 'fa-solid fa-file';
                              $iconColor = 'rgba(255,255,255,0.6)';
                      }
                      ?>
                      <i class="<?php echo $iconClass; ?> text-3xl" style="color: <?php echo $iconColor; ?>;"></i>
                    </div>
                    
                    <?php if (!empty($doc['document_type'])): ?>
                      <?php
                      $typeColors = [
                          'sss' => 'rgba(239, 68, 68, 0.2)',
                          'tin' => 'rgba(59, 130, 246, 0.2)',
                          'philhealth' => 'rgba(16, 185, 129, 0.2)',
                          'pagibig' => 'rgba(168, 85, 247, 0.2)',
                          'birth_certificate' => 'rgba(245, 158, 11, 0.2)',
                          'diploma' => 'rgba(99, 102, 241, 0.2)',
                          'resume' => 'rgba(236, 72, 153, 0.2)'
                      ];
                      $typeColor = $typeColors[$doc['document_type']] ?? 'rgba(255,255,255,0.1)';
                      $typeLabels = [
                          'sss' => 'SSS',
                          'tin' => 'TIN',
                          'philhealth' => 'PhilHealth',
                          'pagibig' => 'Pag-IBIG',
                          'birth_certificate' => 'Birth Cert',
                          'diploma' => 'Diploma',
                          'resume' => 'Resume',
                          'other' => 'Other'
                      ];
                      $typeLabel = $typeLabels[$doc['document_type']] ?? ucfirst($doc['document_type']);
                      ?>
                      <span style="background: <?php echo $typeColor; ?>; color: #FFD700; border-radius: 4px; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 600;">
                        <?php echo $typeLabel; ?>
                      </span>
                    <?php endif; ?>
                  </div>
                  
                  <h4 class="gold-text" style="margin: 0.75rem 0; font-size: 1.1rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    <?php echo htmlspecialchars($doc['document_name']); ?>
                  </h4>
                  
                  <p class="text-sm" style="color: rgba(255,255,255,0.6); margin-bottom: 1rem;">
                    <i class="fa-regular fa-clock"></i>&nbsp;
                    <?php echo date('M d, Y', strtotime($doc['upload_date'])); ?>
                  </p>
                  
                  <div class="flex justify-between pt-3" style="border-top: 1px solid rgba(255,255,255,0.1);">
                    <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" 
                       class="btn gold-border" style="padding: 0.5rem; text-decoration: none; color: #FFD700;" title="View">
                      <i class="fa-solid fa-eye"></i>
                    </a>
                    <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" download
                       class="btn gold-border" style="padding: 0.5rem; text-decoration: none; color: #3B82F6;" title="Download">
                      <i class="fa-solid fa-download"></i>
                    </a>
                    <a href="?<?php echo buildQueryString(['delete' => $doc['id']]); ?>" 
                       onclick="return confirm('Are you sure you want to delete this document?')" 
                       class="btn gold-border" style="padding: 0.5rem; text-decoration: none; color: #EF4444;" title="Delete">
                      <i class="fa-solid fa-trash"></i>
                    </a>
                  </div>
                </div>
              
              <?php elseif ($docViewToUse === 'list'): ?>
                <!-- List View Document Card -->
                <div class="document-card-list">
                  <div class="icon-container">
                    <?php
                    $iconClass = '';
                    $iconColor = '';
                    switch($doc['category']) {
                        case 'pdf':
                            $iconClass = 'fa-solid fa-file-pdf';
                            $iconColor = '#EF4444';
                            break;
                        case 'image':
                            $iconClass = 'fa-solid fa-image';
                            $iconColor = '#3B82F6';
                            break;
                        case 'doc':
                            $iconClass = 'fa-solid fa-file-word';
                            $iconColor = '#10B981';
                            break;
                        default:
                            $iconClass = 'fa-solid fa-file';
                            $iconColor = 'rgba(255,255,255,0.6)';
                    }
                    ?>
                    <i class="<?php echo $iconClass; ?>" style="color: <?php echo $iconColor; ?>; font-size: 1.5rem;"></i>
                  </div>
                  
                  <div class="info">
                    <div class="name" style="font-weight: 600; color: white; font-size: 1rem;">
                      <?php echo htmlspecialchars($doc['document_name']); ?>
                    </div>
                    <div class="sub" style="color: rgba(255,255,255,0.7); font-size: 0.9rem;">
                      <?php echo date('M d, Y', strtotime($doc['upload_date'])); ?>
                    </div>
                  </div>
                  
                  <div class="type">
                    <?php if (!empty($doc['document_type'])): ?>
                      <?php
                      $typeLabels = [
                          'sss' => 'SSS',
                          'tin' => 'TIN',
                          'philhealth' => 'PhilHealth',
                          'pagibig' => 'Pag-IBIG',
                          'birth_certificate' => 'Birth Cert',
                          'diploma' => 'Diploma',
                          'resume' => 'Resume',
                          'other' => 'Other'
                      ];
                      $typeLabel = $typeLabels[$doc['document_type']] ?? ucfirst($doc['document_type']);
                      ?>
                      <span style="color: #FFD700; font-size: 0.875rem; font-weight: 500;">
                        <?php echo $typeLabel; ?>
                      </span>
                    <?php endif; ?>
                  </div>
                  
                  <div class="actions">
                    <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" 
                       class="btn gold-border" style="padding: 0.5rem; text-decoration: none; color: #FFD700;" title="View">
                      <i class="fa-solid fa-eye"></i>
                    </a>
                    <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" download
                       class="btn gold-border" style="padding: 0.5rem; text-decoration: none; color: #3B82F6;" title="Download">
                      <i class="fa-solid fa-download"></i>
                    </a>
                    <a href="?<?php echo buildQueryString(['delete' => $doc['id']]); ?>" 
                       onclick="return confirm('Are you sure you want to delete this document?')" 
                       class="btn gold-border" style="padding: 0.5rem; text-decoration: none; color: #EF4444;" title="Delete">
                      <i class="fa-solid fa-trash"></i>
                    </a>
                  </div>
                </div>
              
              <?php elseif ($docViewToUse === 'details'): ?>
                <!-- Details View Document Card -->
                <div class="document-card-details glass-card" style="padding: 1.5rem;">
                  <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                      <?php
                      $iconClass = '';
                      $iconColor = '';
                      switch($doc['category']) {
                          case 'pdf':
                              $iconClass = 'fa-solid fa-file-pdf';
                              $iconColor = '#EF4444';
                              break;
                          case 'image':
                              $iconClass = 'fa-solid fa-image';
                              $iconColor = '#3B82F6';
                              break;
                          case 'doc':
                              $iconClass = 'fa-solid fa-file-word';
                              $iconColor = '#10B981';
                              break;
                          default:
                              $iconClass = 'fa-solid fa-file';
                              $iconColor = 'rgba(255,255,255,0.6)';
                      }
                      ?>
                      <i class="<?php echo $iconClass; ?>" style="color: <?php echo $iconColor; ?>; font-size: 2rem;"></i>
                      
                      <div>
                        <h4 class="gold-text" style="margin: 0; font-size: 1.2rem;">
                          <?php echo htmlspecialchars($doc['document_name']); ?>
                        </h4>
                        <p class="text-sm" style="color: rgba(255,255,255,0.6); margin-top: 0.25rem;">
                          <i class="fa-regular fa-clock"></i>&nbsp;
                          <?php echo date('M d, Y', strtotime($doc['upload_date'])); ?>
                        </p>
                      </div>
                    </div>
                    
                    <?php if (!empty($doc['document_type'])): ?>
                      <?php
                      $typeColors = [
                          'sss' => 'rgba(239, 68, 68, 0.2)',
                          'tin' => 'rgba(59, 130, 246, 0.2)',
                          'philhealth' => 'rgba(16, 185, 129, 0.2)',
                          'pagibig' => 'rgba(168, 85, 247, 0.2)',
                          'birth_certificate' => 'rgba(245, 158, 11, 0.2)',
                          'diploma' => 'rgba(99, 102, 241, 0.2)',
                          'resume' => 'rgba(236, 72, 153, 0.2)'
                      ];
                      $typeColor = $typeColors[$doc['document_type']] ?? 'rgba(255,255,255,0.1)';
                      $typeLabels = [
                          'sss' => 'SSS',
                          'tin' => 'TIN',
                          'philhealth' => 'PhilHealth',
                          'pagibig' => 'Pag-IBIG',
                          'birth_certificate' => 'Birth Certificate',
                          'diploma' => 'Diploma/TOR',
                          'resume' => 'Resume/CV',
                          'other' => 'Other Document'
                      ];
                      $typeLabel = $typeLabels[$doc['document_type']] ?? ucfirst($doc['document_type']);
                      ?>
                      <span style="background: <?php echo $typeColor; ?>; color: #FFD700; border-radius: 6px; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600;">
                        <?php echo $typeLabel; ?>
                      </span>
                    <?php endif; ?>
                  </div>
                  
                  <div class="details-body">
                    <div class="detail-item">
                      <div class="detail-label">File Type</div>
                      <div class="detail-value"><?php echo strtoupper($doc['category']); ?></div>
                    </div>
                    
                    <div class="detail-item">
                      <div class="detail-label">Uploaded</div>
                      <div class="detail-value"><?php echo date('F d, Y', strtotime($doc['upload_date'])); ?></div>
                    </div>
                    
                    <div class="detail-item">
                      <div class="detail-label">File Name</div>
                      <div class="detail-value" style="word-break: break-all;"><?php echo htmlspecialchars($doc['document_name']); ?></div>
                    </div>
                    
                    <div class="detail-item">
                      <div class="detail-label">Document ID</div>
                      <div class="detail-value">#<?php echo $doc['id']; ?></div>
                    </div>
                  </div>
                  
                  <div style="display: flex; justify-content: space-between; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1);">
                    <div style="display: flex; gap: 0.5rem;">
                      <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" 
                         class="btn gold-gradient" 
                         style="text-decoration: none; border: none; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 600; font-size: 0.875rem;">
                        <i class="fa-solid fa-eye"></i>&nbsp;View
                      </a>
                      <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" download
                         class="btn gold-border" 
                         style="text-decoration: none; padding: 0.5rem 1rem; border-radius: 6px; color: #3B82F6; font-weight: 600; font-size: 0.875rem;">
                        <i class="fa-solid fa-download"></i>&nbsp;Download
                      </a>
                    </div>
                    
                    <a href="?<?php echo buildQueryString(['delete' => $doc['id']]); ?>" 
                       onclick="return confirm('Are you sure you want to delete this document?')" 
                       class="btn gold-border" 
                       style="text-decoration: none; padding: 0.5rem 1rem; border-radius: 6px; color: #EF4444; font-weight: 600; font-size: 0.875rem;">
                      <i class="fa-solid fa-trash"></i>&nbsp;Delete
                    </a>
                  </div>
                </div>
              <?php endif; ?>
              
              <?php endwhile; ?>
            <?php else: ?>
              <div class="glass-card text-center" style="padding: 3rem; grid-column: 1 / -1;">
                <i class="fa-solid fa-folder-open" style="font-size: 3rem; color: rgba(255,255,255,0.3); margin-bottom: 1rem;"></i>
                <h3 style="color: rgba(255,255,255,0.8); margin-bottom: 0.5rem;">No documents found</h3>
                <p style="color: rgba(255,255,255,0.6);">Click the "Add Document" button to upload files.</p>
              </div>
            <?php endif; ?>
          </div>

          <!-- Pagination for Documents -->
          <?php if (isset($docTotalPages) && $docTotalPages > 1): ?>
          <div class="pagination-container">
            <div class="pagination-info">
              Showing <?php echo min($limit, $documents ? $documents->num_rows : 0); ?> of <?php echo $docTotal; ?> documents
              (Page <?php echo $page; ?> of <?php echo $docTotalPages; ?>)
            </div>
            
            <div class="pagination-controls">
              <!-- First Page -->
              <a href="?<?php echo buildQueryString(['page' => 1]); ?>" 
                 class="pagination-btn <?php echo $page == 1 ? 'disabled' : ''; ?>">
                <i class="fas fa-angle-double-left"></i> First
              </a>
              
              <!-- Previous Page -->
              <a href="?<?php echo buildQueryString(['page' => max(1, $page - 1)]); ?>" 
                 class="pagination-btn <?php echo $page == 1 ? 'disabled' : ''; ?>">
                <i class="fas fa-angle-left"></i> Prev
              </a>
              
              <!-- Page Numbers -->
              <div class="page-numbers">
                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($docTotalPages, $page + 2);
                
                for ($i = $startPage; $i <= $endPage; $i++):
                ?>
                  <a href="?<?php echo buildQueryString(['page' => $i]); ?>" 
                     class="page-number <?php echo $i == $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                  </a>
                <?php endfor; ?>
              </div>
              
              <!-- Next Page -->
              <a href="?<?php echo buildQueryString(['page' => min($docTotalPages, $page + 1)]); ?>" 
                 class="pagination-btn <?php echo $page == $docTotalPages ? 'disabled' : ''; ?>">
                Next <i class="fas fa-angle-right"></i>
              </a>
              
              <!-- Last Page -->
              <a href="?<?php echo buildQueryString(['page' => $docTotalPages]); ?>" 
                 class="pagination-btn <?php echo $page == $docTotalPages ? 'disabled' : ''; ?>">
                Last <i class="fas fa-angle-double-right"></i>
              </a>
            </div>
          </div>
          <?php endif; ?>
        </section>

        <!-- Floating Add Button for mobile -->
        <button class="fab" id="openAddMobile" title="Add document" style="display: none;">
          <i class="fa-solid fa-plus"></i>
        </button>

        <!-- Add Document Modal -->
        <div class="modal-backdrop" id="addModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center;">
          <div class="modal-panel" style="background: #0b0b0b; border: 1px solid rgba(255,215,0,0.3); border-radius: 12px; padding: 2rem; max-width: 500px; width: 90%;">
            <h3 style="margin-top:0; color: #FFD700; margin-bottom: 1.5rem;">
              <i class="fa-solid fa-upload"></i>&nbsp;Upload Document
            </h3>
            <form method="POST" enctype="multipart/form-data">
              <input type="hidden" name="employee_id" value="<?php echo $selectedEmp; ?>">
              
              <div class="form-row" style="margin-bottom: 1rem;">
                <label style="display: block; color: rgba(255,255,255,0.8); margin-bottom: 0.5rem;">Document Type</label>
                <select name="document_type" required 
                        style="width: 100%; padding: 0.75rem; border: 1px solid rgba(255,215,0,0.3); border-radius: 8px; background: rgba(0,0,0,0.5); color: white;">
                  <option value="">Select Document Type</option>
                  <option value="sss">SSS Document</option>
                  <option value="tin">TIN Document</option>
                  <option value="philhealth">PhilHealth Document</option>
                  <option value="pagibig">Pag-IBIG Document</option>
                  <option value="birth_certificate">Birth Certificate</option>
                  <option value="diploma">Diploma/TOR</option>
                  <option value="resume">Resume/CV</option>
                  <option value="employment_certificate">Employment Certificate</option>
                  <option value="nbi_clearance">NBI Clearance</option>
                  <option value="police_clearance">Police Clearance</option>
                  <option value="medical_certificate">Medical Certificate</option>
                  <option value="other">Other Document</option>
                </select>
              </div>
              
              <div class="form-row" style="margin-bottom: 1.5rem;">
                <label style="display: block; color: rgba(255,255,255,0.8); margin-bottom: 0.5rem;">Select File</label>
                <input type="file" name="document" accept=".pdf,.png,.jpg,.jpeg,.docx" required 
                       style="width: 100%; padding: 0.75rem; border: 1px solid rgba(255,215,0,0.3); border-radius: 8px; background: rgba(0,0,0,0.5); color: white;">
                <p style="color: rgba(255,255,255,0.5); font-size: 0.875rem; margin-top: 0.5rem;">
                  Allowed: PDF, PNG, JPG, JPEG, DOCX
                </p>
              </div>
              
              <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:8px;">
                <button type="button" class="btn" id="closeAdd" 
                        style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.3); padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer;">
                  Cancel
                </button>
                <button type="submit" class="btn gold-gradient" 
                        style="border: none; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; cursor: pointer;">
                  <i class="fa-solid fa-upload"></i>&nbsp;Upload
                </button>
              </div>
            </form>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </main>

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
    
    if (openAddDesktop) openAddDesktop.addEventListener('click', openModal);
    if (openAddMobile) openAddMobile.addEventListener('click', openModal);
    if (closeAdd) closeAdd.addEventListener('click', closeModal);
    
    if (addModal) addModal.addEventListener('click', (e) => {
      if(e.target === addModal) {
        closeModal();
      }
    });

    // Show mobile button on small screens
    if (window.innerWidth < 768 && openAddMobile) {
      openAddMobile.style.display = 'flex';
    }
    
    window.addEventListener('resize', () => {
      if (window.innerWidth < 768 && openAddMobile) {
        openAddMobile.style.display = 'flex';
      } else if (openAddMobile) {
        openAddMobile.style.display = 'none';
      }
    });
  </script>
</body>
</html>