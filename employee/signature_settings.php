<?php
// signature_settings.php - E-Signature Management
session_start();

// Include database connection
require_once __DIR__ . '/../conn/db_connection.php';

// Initialize variables
$message = '';
$messageType = '';
$signatures = [];
$employees = [];
$selectedEmployeeId = $_POST['employee_id'] ?? $_SESSION['user_id'] ?? 0;

// Fetch all employees for dropdown
$empQuery = "SELECT id, first_name, last_name FROM employees WHERE status = 'Active' ORDER BY last_name, first_name";
$empResult = mysqli_query($db, $empQuery);
if ($empResult) {
    while ($row = mysqli_fetch_assoc($empResult)) {
        $employees[] = $row;
    }
    mysqli_free_result($empResult);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Upload new signature
        if ($_POST['action'] === 'upload_signature') {
            $uploadEmployeeId = intval($_POST['employee_id'] ?? 0);
            
            if ($uploadEmployeeId <= 0) {
                $message = 'Please select an employee.';
                $messageType = 'warning';
            } elseif (isset($_FILES['signature_image']) && $_FILES['signature_image']['error'] === 0) {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $fileType = $_FILES['signature_image']['type'];
                
                if (in_array($fileType, $allowedTypes)) {
                    $uploadDir = __DIR__ . '/uploads/signatures/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $fileName = 'sig_' . $uploadEmployeeId . '_' . time() . '.' . pathinfo($_FILES['signature_image']['name'], PATHINFO_EXTENSION);
                    $uploadPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['signature_image']['tmp_name'], $uploadPath)) {
                        // Save to database
                        $signatureName = trim($_POST['signature_name'] ?? 'My Signature');
                        $filePath = 'uploads/signatures/' . $fileName;
                        
                        $stmt = mysqli_prepare($db, "INSERT INTO e_signatures (employee_id, signature_type, signature_image, created_at) VALUES (?, ?, ?, NOW())");
                        mysqli_stmt_bind_param($stmt, "iss", $uploadEmployeeId, $signatureName, $filePath);
                        
                        if (mysqli_stmt_execute($stmt)) {
                            $message = 'Signature uploaded successfully!';
                            $messageType = 'success';
                        } else {
                            $message = 'Error saving signature to database.';
                            $messageType = 'danger';
                        }
                        mysqli_stmt_close($stmt);
                    } else {
                        $message = 'Error uploading file.';
                        $messageType = 'danger';
                    }
                } else {
                    $message = 'Invalid file type. Please upload JPG, PNG, or GIF.';
                    $messageType = 'warning';
                }
            }
        }
        
        // Delete signature
        if ($_POST['action'] === 'delete_signature') {
            $signatureId = intval($_POST['signature_id'] ?? 0);
            
            // Get file path first (no restriction to current user - admin can delete any)
            $stmt = mysqli_prepare($db, "SELECT signature_image FROM e_signatures WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $signatureId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $filePath);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);
            
            if ($filePath) {
                // Delete file
                $fullPath = __DIR__ . '/' . $filePath;
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
                
                // Delete from database
                $stmt = mysqli_prepare($db, "DELETE FROM e_signatures WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "i", $signatureId);
                
                if (mysqli_stmt_execute($stmt)) {
                    $message = 'Signature deleted successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Error deleting signature.';
                    $messageType = 'danger';
                }
                mysqli_stmt_close($stmt);
            }
        }
        
        // Save canvas signature
        if ($_POST['action'] === 'save_canvas') {
            $signatureData = $_POST['signature_data'] ?? '';
            $signatureName = trim($_POST['signature_name'] ?? 'Drawn Signature');
            $canvasEmployeeId = intval($_POST['employee_id'] ?? 0);
            
            if ($canvasEmployeeId <= 0) {
                $message = 'Please select an employee.';
                $messageType = 'warning';
            } elseif ($signatureData) {
                // Extract base64 data
                $signatureData = str_replace('data:image/png;base64,', '', $signatureData);
                $signatureData = str_replace(' ', '+', $signatureData);
                $data = base64_decode($signatureData);
                
                $uploadDir = __DIR__ . '/uploads/signatures/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileName = 'sig_canvas_' . $canvasEmployeeId . '_' . time() . '.png';
                $filePath = $uploadDir . $fileName;
                
                if (file_put_contents($filePath, $data)) {
                    $dbPath = 'uploads/signatures/' . $fileName;
                    
                    $stmt = mysqli_prepare($db, "INSERT INTO e_signatures (employee_id, signature_type, signature_image, created_at) VALUES (?, ?, ?, NOW())");
                    mysqli_stmt_bind_param($stmt, "iss", $canvasEmployeeId, $signatureName, $dbPath);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $message = 'Signature saved successfully!';
                        $messageType = 'success';
                    } else {
                        $message = 'Error saving signature.';
                        $messageType = 'danger';
                    }
                    mysqli_stmt_close($stmt);
                }
            }
        }
    }
}

// Fetch all signatures with employee names
$sigQuery = "SELECT es.id, es.signature_type, es.signature_image, es.created_at, 
                    e.first_name, e.last_name 
             FROM e_signatures es 
             LEFT JOIN employees e ON es.employee_id = e.id 
             ORDER BY es.created_at DESC";
$sigResult = mysqli_query($db, $sigQuery);
if ($sigResult) {
    while ($row = mysqli_fetch_assoc($sigResult)) {
        $signatures[] = [
            'id' => $row['id'],
            'signature_name' => $row['signature_type'],
            'file_path' => $row['signature_image'],
            'created_at' => $row['created_at'],
            'employee_name' => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))
        ];
    }
    mysqli_free_result($sigResult);
}

mysqli_close($db);

// Helper function to format date
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Signature Settings</title>
    
    <!-- FontAwesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <style>
        /* E-Signature - Dark Engineering Theme */
        body {
            background: var(--bg-page);
            color: var(--soft-white);
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial;
            margin: 0;
        }
        
        /* App Shell */
        .app-shell {
            display: flex;
            min-height: 100vh;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            padding: 1rem;
            background: var(--bg-page);
            overflow-x: hidden;
            margin-left: 0;
        }
        
        @media (min-width: 1024px) {
            .main-content {
                margin-left: 260px;
            }
        }
        
        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, rgba(255,214,107,0.15) 0%, rgba(212,175,55,0.08) 100%);
            border: 1px solid rgba(212,175,55,0.15);
            border-left: 4px solid var(--gold-2);
            color: var(--soft-white);
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.25rem;
            box-shadow: 0 8px 28px rgba(0,0,0,0.6);
        }
        
        .page-header h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--soft-white);
        }
        
        .page-header h1 i {
            color: var(--gold-1);
            margin-right: 0.75rem;
        }
        
        .page-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.75;
            color: var(--muted-white);
        }
        
        /* Action Buttons */
        .btn-gold {
            background: var(--accent);
            color: #111;
            padding: 0.6rem 1rem;
            border-radius: 10px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-gold:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(212,175,55,0.3);
        }
        
        .btn-secondary {
            background: rgba(255,255,255,0.08);
            color: var(--soft-white);
            padding: 0.6rem 1rem;
            border-radius: 10px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-secondary:hover {
            background: rgba(255,255,255,0.12);
        }
        
        .btn-danger {
            background: linear-gradient(180deg, #e06a6a, #c95555);
            color: white;
            padding: 0.6rem 1rem;
            border-radius: 10px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(224,106,106,0.3);
        }
        
        /* Cards */
        .dashboard-card {
            background: var(--card-bg);
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.03);
            box-shadow: 0 6px 20px rgba(0,0,0,0.6);
            margin-bottom: 1.25rem;
            overflow: hidden;
        }
        
        .dashboard-card:hover {
            box-shadow: 0 26px 60px rgba(212,175,55,0.06);
        }
        
        .card-header {
            background: transparent;
            border-bottom: 1px solid rgba(255,255,255,0.03);
            padding: 1.1rem;
            font-weight: 600;
            color: var(--gold-2);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .card-header i {
            color: var(--gold-1);
        }
        
        .card-body {
            padding: 1.1rem;
        }
        
        /* Form Styling */
        .form-label {
            font-weight: 500;
            color: var(--muted-white);
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .form-control {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 8px;
            padding: 0.75rem;
            color: var(--soft-white);
            font-size: 0.95rem;
            width: 100%;
            box-sizing: border-box;
        }
        
        .form-control:focus {
            background: rgba(255,255,255,0.05);
            border-color: var(--gold-2);
            box-shadow: 0 0 0 0.2rem rgba(212,175,55,0.15);
            color: var(--soft-white);
            outline: none;
        }
        
        .form-control::placeholder {
            color: rgba(255,255,255,0.4);
        }
        
        /* Canvas Styling */
        .signature-pad-container {
            background: rgba(255,255,255,0.02);
            border: 2px dashed rgba(212,175,55,0.3);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        #signatureCanvas {
            background: white;
            border-radius: 8px;
            cursor: crosshair;
            display: block;
            margin: 0 auto;
            touch-action: none;
        }
        
        .canvas-controls {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            margin-top: 1rem;
        }
        
        .canvas-hint {
            text-align: center;
            color: var(--muted-white);
            font-size: 0.875rem;
            margin-bottom: 0.75rem;
        }
        
        /* Upload Zone */
        .upload-zone {
            background: rgba(255,255,255,0.02);
            border: 2px dashed rgba(212,175,55,0.3);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .upload-zone:hover {
            background: rgba(255,214,107,0.03);
            border-color: var(--gold-2);
        }
        
        .upload-zone i {
            font-size: 3rem;
            color: var(--gold-1);
            margin-bottom: 1rem;
        }
        
        .upload-zone-text {
            color: var(--muted-white);
            font-size: 0.9rem;
        }
        
        .upload-zone input[type="file"] {
            display: none;
        }
        
        /* Signature List */
        .signature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        .signature-item {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.03);
            border-radius: 10px;
            padding: 1rem;
            position: relative;
        }
        
        .signature-item:hover {
            border-color: rgba(212,175,55,0.2);
        }
        
        .signature-image {
            width: 100%;
            height: 120px;
            object-fit: contain;
            background: white;
            border-radius: 6px;
            margin-bottom: 0.75rem;
        }
        
        .signature-name {
            font-weight: 600;
            color: var(--gold-1);
            margin-bottom: 0.25rem;
        }
        
        .signature-date {
            font-size: 0.8rem;
            color: var(--muted-white);
        }
        
        .signature-actions {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .btn-icon.delete {
            background: rgba(224,106,106,0.1);
            color: var(--absent-red);
        }
        
        .btn-icon.delete:hover {
            background: rgba(224,106,106,0.2);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 2.5rem;
            color: var(--muted-white);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: rgba(255,255,255,0.15);
        }
        
        /* Alert Messages */
        .alert {
            border-radius: 10px;
            border: none;
            padding: 1rem 1.25rem;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: rgba(57,255,20,0.1);
            color: var(--present-green);
            border: 1px solid rgba(57,255,20,0.2);
        }
        
        .alert-danger {
            background: rgba(224,106,106,0.1);
            color: var(--absent-red);
            border: 1px solid rgba(224,106,106,0.2);
        }
        
        .alert-warning {
            background: rgba(255,214,107,0.1);
            color: var(--gold-1);
            border: 1px solid rgba(255,214,107,0.2);
        }
        
        /* Tab Navigation */
        .tab-nav {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.25rem;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            padding-bottom: 0.75rem;
        }
        
        .tab-btn {
            background: rgba(255,255,255,0.03);
            border: none;
            color: var(--muted-white);
            padding: 0.6rem 1.25rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .tab-btn:hover {
            background: rgba(255,255,255,0.06);
            color: var(--soft-white);
        }
        
        .tab-btn.active {
            background: rgba(212,175,55,0.1);
            color: var(--gold-1);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Stats Cards */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.25rem;
        }
        
        .stat-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 1.1rem;
            border: 1px solid rgba(255,255,255,0.03);
            box-shadow: 0 6px 20px rgba(0,0,0,0.6);
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }
        
        .stat-icon.signatures {
            background: rgba(255,214,107,0.1);
            color: var(--gold-1);
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--soft-white);
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: var(--muted-white);
        }
        
        /* Utilities */
        .d-flex {
            display: flex !important;
        }
        
        .justify-content-between {
            justify-content: space-between !important;
        }
        
        .align-items-center {
            align-items: center !important;
        }
        
        .gap-2 {
            gap: 0.5rem !important;
        }
        
        .mb-3 {
            margin-bottom: 1rem !important;
        }
        
        .me-2 {
            margin-right: 0.5rem !important;
        }
        
        .text-muted {
            color: var(--muted-white) !important;
        }
    </style>
</head>
<body>
    <div class="app-shell">
        <!-- Include Sidebar -->
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1><i class="fas fa-signature"></i>E-Signature Settings</h1>
                        <p>Create and manage your electronic signatures</p>
                    </div>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
            
            <!-- Alert Messages -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Statistics -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-icon signatures">
                        <i class="fas fa-file-signature"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?php echo count($signatures); ?></div>
                        <div class="stat-label">Saved Signatures</div>
                    </div>
                </div>
            </div>
            
            <!-- Create New Signature Card -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-plus-circle"></i>
                    Create New Signature
                </div>
                <div class="card-body">
                    <!-- Tab Navigation -->
                    <div class="tab-nav">
                        <button class="tab-btn active" onclick="switchTab('draw')">
                            <i class="fas fa-pen me-2"></i>Draw
                        </button>
                        <button class="tab-btn" onclick="switchTab('upload')">
                            <i class="fas fa-upload me-2"></i>Upload
                        </button>
                    </div>
                    
                    <!-- Draw Signature Tab -->
                    <div id="drawTab" class="tab-content active">
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-user me-2"></i>Select Employee</label>
                            <select name="employee_id" class="form-control" id="drawEmployeeId" required>
                                <option value="">Choose an employee...</option>
                                <?php foreach ($employees as $emp): 
                                    $name = htmlspecialchars(trim($emp['first_name'] . ' ' . $emp['last_name']));
                                    $selected = ($emp['id'] == $selectedEmployeeId) ? 'selected' : '';
                                ?>
                                    <option value="<?php echo $emp['id']; ?>" <?php echo $selected; ?>><?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="canvas-hint">
                            <i class="fas fa-hand-pointer me-2"></i>Use your mouse or touch to draw your signature
                        </div>
                        <div class="signature-pad-container">
                            <canvas id="signatureCanvas" width="600" height="200"></canvas>
                        </div>
                        <div class="canvas-controls">
                            <button type="button" class="btn btn-secondary" onclick="clearCanvas()">
                                <i class="fas fa-eraser me-2"></i>Clear
                            </button>
                            <button type="button" class="btn btn-gold" onclick="saveCanvas()">
                                <i class="fas fa-save me-2"></i>Save Signature
                            </button>
                        </div>
                    </div>
                    
                    <!-- Upload Signature Tab -->
                    <div id="uploadTab" class="tab-content">
                        <form method="POST" enctype="multipart/form-data" id="uploadForm">
                            <input type="hidden" name="action" value="upload_signature">
                            
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-user me-2"></i>Select Employee</label>
                                <select name="employee_id" class="form-control" required>
                                    <option value="">Choose an employee...</option>
                                    <?php foreach ($employees as $emp): 
                                        $name = htmlspecialchars(trim($emp['first_name'] . ' ' . $emp['last_name']));
                                        $selected = ($emp['id'] == $selectedEmployeeId) ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo $emp['id']; ?>" <?php echo $selected; ?>><?php echo $name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Signature Name</label>
                                <input type="text" name="signature_name" class="form-control" placeholder="e.g., My Official Signature" required>
                            </div>
                            
                            <div class="upload-zone" onclick="document.getElementById('signatureFile').click()">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <div class="upload-zone-text">
                                    <strong>Click to upload</strong> or drag and drop<br>
                                    PNG, JPG, or GIF (max 5MB)
                                </div>
                                <input type="file" id="signatureFile" name="signature_image" accept="image/png,image/jpeg,image/gif" required onchange="handleFileSelect(this)">
                            </div>
                            
                            <div id="filePreview" class="mt-3" style="display:none;">
                                <p class="text-muted"><i class="fas fa-file-image me-2"></i><span id="fileName"></span></p>
                                <button type="submit" class="btn btn-gold w-100">
                                    <i class="fas fa-upload me-2"></i>Upload Signature
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Saved Signatures Card -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-folder-open"></i>
                    Saved Signatures
                </div>
                <div class="card-body">
                    <?php if (!empty($signatures)): ?>
                        <div class="signature-grid">
                        <?php foreach ($signatures as $sig): 
                            $empName = $sig['employee_name'] ?: 'Unknown';
                        ?>
                            <div class="signature-item">
                                <div class="signature-actions">
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this signature?');">
                                        <input type="hidden" name="action" value="delete_signature">
                                        <input type="hidden" name="signature_id" value="<?php echo $sig['id']; ?>">
                                        <button type="submit" class="btn-icon delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                                <img src="<?php echo htmlspecialchars($sig['file_path']); ?>" alt="Signature" class="signature-image">
                                <div class="signature-name"><?php echo htmlspecialchars($sig['signature_name']); ?></div>
                                <div class="employee-name" style="font-size: 0.85rem; color: var(--gold-2); margin-bottom: 0.25rem;">
                                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($empName); ?>
                                </div>
                                <div class="signature-date">
                                    <i class="far fa-calendar me-1"></i><?php echo formatDate($sig['created_at']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-signature"></i>
                            <h5>No Signatures Yet</h5>
                            <p>Create your first signature using the draw or upload option above.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Hidden form for canvas save -->
    <form method="POST" id="canvasForm" style="display:none;">
        <input type="hidden" name="action" value="save_canvas">
        <input type="hidden" name="signature_data" id="signatureData">
        <input type="hidden" name="signature_name" id="canvasSignatureName" value="Drawn Signature">
        <input type="hidden" name="employee_id" id="canvasEmployeeId">
    </form>
    
    <script>
        // Canvas setup
        const canvas = document.getElementById('signatureCanvas');
        const ctx = canvas.getContext('2d');
        let isDrawing = false;
        let lastX = 0;
        let lastY = 0;
        
        // Set canvas background
        ctx.fillStyle = 'white';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        // Set drawing style
        ctx.strokeStyle = 'black';
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        
        // Drawing functions
        function startDrawing(e) {
            isDrawing = true;
            [lastX, lastY] = getCoordinates(e);
        }
        
        function draw(e) {
            if (!isDrawing) return;
            e.preventDefault();
            
            const [x, y] = getCoordinates(e);
            
            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(x, y);
            ctx.stroke();
            
            [lastX, lastY] = [x, y];
        }
        
        function stopDrawing() {
            isDrawing = false;
        }
        
        function getCoordinates(e) {
            const rect = canvas.getBoundingClientRect();
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;
            
            if (e.touches) {
                return [
                    (e.touches[0].clientX - rect.left) * scaleX,
                    (e.touches[0].clientY - rect.top) * scaleY
                ];
            }
            return [
                (e.offsetX || e.clientX - rect.left) * scaleX,
                (e.offsetY || e.clientY - rect.top) * scaleY
            ];
        }
        
        // Event listeners
        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDrawing);
        canvas.addEventListener('mouseout', stopDrawing);
        
        canvas.addEventListener('touchstart', startDrawing);
        canvas.addEventListener('touchmove', draw);
        canvas.addEventListener('touchend', stopDrawing);
        
        // Clear canvas
        function clearCanvas() {
            ctx.fillStyle = 'white';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.strokeStyle = 'black';
        }
        
        // Save canvas
        function saveCanvas() {
            const employeeId = document.getElementById('drawEmployeeId').value;
            if (!employeeId) {
                alert('Please select an employee first.');
                return;
            }
            const dataURL = canvas.toDataURL('image/png');
            document.getElementById('signatureData').value = dataURL;
            document.getElementById('canvasEmployeeId').value = employeeId;
            document.getElementById('canvasForm').submit();
        }
        
        // Tab switching
        function switchTab(tab) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            if (tab === 'draw') {
                document.querySelectorAll('.tab-btn')[0].classList.add('active');
                document.getElementById('drawTab').classList.add('active');
            } else {
                document.querySelectorAll('.tab-btn')[1].classList.add('active');
                document.getElementById('uploadTab').classList.add('active');
            }
        }
        
        // File upload handling
        function handleFileSelect(input) {
            if (input.files && input.files[0]) {
                document.getElementById('fileName').textContent = input.files[0].name;
                document.getElementById('filePreview').style.display = 'block';
            }
        }
        
        // Auto-hide alerts
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
        
    </script>
</body>
</html>
