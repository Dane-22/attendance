<?php
// employee/cash_advance.php - Cash Advance Ledger
require_once __DIR__ . '/../conn/db_connection.php';
require_once __DIR__ . '/../functions.php';
session_start();

// Check if user is logged in
if (empty($_SESSION['logged_in'])) {
    header('Location: ../login.php');
    exit;
}

$employeeId = $_SESSION['employee_id'] ?? null;
$employeeName = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
$position = $_SESSION['position'] ?? 'Employee';
$isAdmin = in_array($position, ['Admin', 'Super Admin']);

// Handle AJAX add transaction request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_transaction_ajax'])) {
    header('Content-Type: application/json');
    
    $empId = intval($_POST['employee_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $particular = $_POST['particular'] ?? 'Cash Advance';
    $reason = trim($_POST['reason'] ?? '');
    
    if ($empId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid employee']);
        exit;
    }
    
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid amount']);
        exit;
    }
    
    $query = "INSERT INTO cash_advances (employee_id, amount, particular, reason, request_date, status) VALUES (?, ?, ?, ?, NOW(), 'pending')";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, 'idss', $empId, $amount, $particular, $reason);
    
    if (mysqli_stmt_execute($stmt)) {
        $newId = mysqli_insert_id($db);
        echo json_encode(['success' => true, 'id' => $newId]);
        logActivity($db, 'Cash Advance Added', "Added {$particular} of ₱{$amount} for employee #{$empId}");
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add transaction']);
    }
    mysqli_stmt_close($stmt);
    exit;
}

// Handle signature upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_signature'])) {
    header('Content-Type: application/json');
    
    $empId = intval($_POST['employee_id'] ?? 0);
    $signatureData = $_POST['signature_data'] ?? '';
    $signatureType = $_POST['signature_type'] ?? 'employee';
    
    if ($empId <= 0 || empty($signatureData)) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit;
    }
    
    // Extract base64 image data
    if (preg_match('/^data:image\/(\w+);base64,/', $signatureData, $matches)) {
        $imageType = $matches[1];
        $base64Data = substr($signatureData, strlen($matches[0]));
        $imageData = base64_decode($base64Data);
        
        if ($imageData === false) {
            echo json_encode(['success' => false, 'message' => 'Invalid image data']);
            exit;
        }
        
        // Create upload directory
        $uploadDir = __DIR__ . '/../uploads/signatures/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate filename
        $filename = 'sig_' . $empId . '_' . $signatureType . '_' . time() . '.' . $imageType;
        $filepath = $uploadDir . $filename;
        
        if (file_put_contents($filepath, $imageData)) {
            // Save to database
            $dbPath = 'uploads/signatures/' . $filename;
            $query = "INSERT INTO e_signatures (employee_id, signature_type, signature_image, signature_data) 
                      VALUES (?, ?, ?, ?) 
                      ON DUPLICATE KEY UPDATE 
                      signature_image = VALUES(signature_image), 
                      signature_data = VALUES(signature_data),
                      updated_at = CURRENT_TIMESTAMP";
            $stmt = mysqli_prepare($db, $query);
            mysqli_stmt_bind_param($stmt, 'isss', $empId, $signatureType, $dbPath, $signatureData);
            
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => true, 'path' => $dbPath]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
            mysqli_stmt_close($stmt);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save file']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid image format']);
    }
    exit;
}

// Handle signature file upload (from file input)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_signature_file'])) {
    header('Content-Type: application/json');
    
    $empId = intval($_POST['employee_id'] ?? 0);
    $signatureType = $_POST['signature_type'] ?? 'employee';
    
    if ($empId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid employee']);
        exit;
    }
    
    if (!isset($_FILES['signature_file']) || $_FILES['signature_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'File upload failed']);
        exit;
    }
    
    $file = $_FILES['signature_file'];
    
    // Validate file type
    $validTypes = ['image/png', 'image/jpeg', 'image/jpg'];
    if (!in_array($file['type'], $validTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only PNG and JPG allowed.']);
        exit;
    }
    
    // Validate file size (max 2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 2MB.']);
        exit;
    }
    
    // Create upload directory
    $uploadDir = __DIR__ . '/../uploads/signatures/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Get file extension
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'sig_' . $empId . '_' . $signatureType . '_' . time() . '.' . $ext;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Convert to base64 for storage
        $imageData = file_get_contents($filepath);
        $base64Data = base64_encode($imageData);
        $signatureDataUrl = 'data:image/' . ($ext === 'png' ? 'png' : 'jpeg') . ';base64,' . $base64Data;
        
        // Save to database
        $dbPath = 'uploads/signatures/' . $filename;
        $query = "INSERT INTO e_signatures (employee_id, signature_type, signature_image, signature_data) 
                  VALUES (?, ?, ?, ?) 
                  ON DUPLICATE KEY UPDATE 
                  signature_image = VALUES(signature_image), 
                  signature_data = VALUES(signature_data),
                  updated_at = CURRENT_TIMESTAMP";
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, 'isss', $empId, $signatureType, $dbPath, $signatureDataUrl);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true, 'path' => $dbPath]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
    }
    exit;
}

// Get signature API
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['get_signature'])) {
    header('Content-Type: application/json');
    
    $empId = intval($_GET['employee_id'] ?? 0);
    $signatureType = $_GET['signature_type'] ?? 'employee';
    
    $query = "SELECT signature_image, signature_data FROM e_signatures 
              WHERE employee_id = ? AND signature_type = ? AND is_active = 1 
              ORDER BY updated_at DESC LIMIT 1";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, 'is', $empId, $signatureType);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        echo json_encode(['success' => true, 'signature' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No signature found']);
    }
    mysqli_stmt_close($stmt);
    exit;
}

// Handle AJAX update requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_update'])) {
    header('Content-Type: application/json');
    
    $transId = intval($_POST['transaction_id'] ?? 0);
    $field = $_POST['field'] ?? '';
    $value = $_POST['value'] ?? '';
    
    if ($transId <= 0 || !in_array($field, ['particular', 'amount'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }
    
    // Sanitize and validate
    if ($field === 'amount') {
        $value = floatval($value);
        if ($value <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid amount']);
            exit;
        }
    } else {
        $value = in_array($value, ['Cash Advance', 'Payment']) ? $value : 'Cash Advance';
    }
    
    $query = "UPDATE cash_advances SET {$field} = ? WHERE id = ?";
    $stmt = mysqli_prepare($db, $query);
    
    if ($field === 'amount') {
        mysqli_stmt_bind_param($stmt, 'di', $value, $transId);
    } else {
        mysqli_stmt_bind_param($stmt, 'si', $value, $transId);
    }
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
        logActivity($db, 'Cash Advance Updated', "Updated {$field} for transaction #{$transId}");
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed']);
    }
    mysqli_stmt_close($stmt);
    exit;
}

// Pagination settings
$perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 20;
$perPage = max(10, min(100, $perPage));
$currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1;
$currentPage = max(1, $currentPage);
$offset = ($currentPage - 1) * $perPage;

// Search functionality
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchFilter = '';
$searchParams = [];
if ($searchTerm !== '') {
    $searchFilter = " AND (first_name LIKE ? OR last_name LIKE ? OR employee_code LIKE ?)";
    $like = '%' . $searchTerm . '%';
    $searchParams = [$like, $like, $like];
}

// Fetch cash advance records with running balance calculation
$transactions = [];
$employeeList = [];

// First, get all employees and calculate their balances
if ($isAdmin) {
    // Count total for pagination
    $countQuery = "SELECT COUNT(*) as total FROM employees WHERE status = 'active'" . $searchFilter;
    $countStmt = mysqli_prepare($db, $countQuery);
    if ($searchTerm !== '') {
        mysqli_stmt_bind_param($countStmt, 'sss', ...$searchParams);
    }
    mysqli_stmt_execute($countStmt);
    $countResult = mysqli_stmt_get_result($countStmt);
    $totalEmployeesAll = mysqli_fetch_assoc($countResult)['total'] ?? 0;
    mysqli_stmt_close($countStmt);
    $totalPages = ceil($totalEmployeesAll / $perPage);
    
    // Adjust current page if beyond total
    if ($currentPage > $totalPages && $totalPages > 0) {
        $currentPage = $totalPages;
        $offset = ($currentPage - 1) * $perPage;
    }
    
    $empQuery = "SELECT id, first_name, last_name, employee_code, daily_rate, position 
                 FROM employees 
                 WHERE status = 'active' " . $searchFilter . "
                 ORDER BY last_name, first_name
                 LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($db, $empQuery);
    if ($searchTerm !== '') {
        mysqli_stmt_bind_param($stmt, 'sssii', ...[...$searchParams, $perPage, $offset]);
    } else {
        mysqli_stmt_bind_param($stmt, 'ii', $perPage, $offset);
    }
    mysqli_stmt_execute($stmt);
    $empResult = mysqli_stmt_get_result($stmt);
} else {
    $totalEmployeesAll = 1;
    $totalPages = 1;
    $empQuery = "SELECT id, first_name, last_name, employee_code, daily_rate, position 
                 FROM employees 
                 WHERE id = ? AND status = 'active'";
    $stmt = mysqli_prepare($db, $empQuery);
    mysqli_stmt_bind_param($stmt, 'i', $employeeId);
    mysqli_stmt_execute($stmt);
    $empResult = mysqli_stmt_get_result($stmt);
}

// Calculate balances for each employee
while ($emp = mysqli_fetch_assoc($empResult)) {
    $empId = $emp['id'];
    
    // Get all transactions for this employee (removed status filter for debugging)
    $caQuery = "SELECT * FROM cash_advances 
                WHERE employee_id = ?
                ORDER BY request_date ASC";
    $caStmt = mysqli_prepare($db, $caQuery);
    mysqli_stmt_bind_param($caStmt, 'i', $empId);
    mysqli_stmt_execute($caStmt);
    $caResult = mysqli_stmt_get_result($caStmt);
    
    $balance = 0;
    $totalCA = 0;
    $totalPaid = 0;
    $lastTransaction = null;
    $transactionCount = 0;
    
    while ($ca = mysqli_fetch_assoc($caResult)) {
        if ($ca['particular'] === 'Payment') {
            $balance -= $ca['amount'];
            $totalPaid += $ca['amount'];
        } else {
            $balance += $ca['amount'];
            $totalCA += $ca['amount'];
        }
        $lastTransaction = $ca;
        $transactionCount++;
    }
    
    $emp['balance'] = $balance;
    $emp['total_ca'] = $totalCA;
    $emp['total_paid'] = $totalPaid;
    $emp['transaction_count'] = $transactionCount;
    $emp['last_transaction'] = $lastTransaction;
    $emp['last_date'] = $lastTransaction ? $lastTransaction['request_date'] : null;
    
    $employeeList[] = $emp;
    mysqli_stmt_close($caStmt);
}

// Calculate totals
$totalEmployees = count($employeeList);
$totalWithBalance = 0;
$totalOutstanding = 0;
$totalCA = 0;
$totalPaid = 0;

foreach ($employeeList as $emp) {
    if ($emp['balance'] > 0) {
        $totalWithBalance++;
    }
    $totalOutstanding += $emp['balance'];
    $totalCA += $emp['total_ca'];
    $totalPaid += $emp['total_paid'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash Advance - JAJR Company</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="css/select_employee.css">
    <link rel="stylesheet" href="css/light-theme.css">
    <script src="js/theme.js"></script>
    <link rel="icon" type="image/x-icon" href="../assets/img/profile/jajr-logo.png">
    <style>
        .cash-advance-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stat-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.1);
        }
        
        .stat-box h4 {
            color: #888;
            font-size: 11px;
            text-transform: uppercase;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }
        
        .stat-box .amount {
            color: #FFD700;
            font-size: 28px;
            font-weight: 800;
            line-height: 1;
        }
        
        .request-form {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-group label {
            display: block;
            color: #888;
            font-size: 13px;
            margin-bottom: 6px;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            background: #2a2a2a;
            border: 1px solid #444;
            border-radius: 6px;
            padding: 10px 12px;
            color: #fff;
            font-size: 14px;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #FFD700;
        }
        
        .btn-submit {
            background: #FFD700;
            color: #000;
            border: none;
            padding: 10px 24px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            background: linear-gradient(to right, #FFD700, #FFA500);
            color: #000;
            padding: 14px 16px;
            text-align: left;
            font-weight: 700;
            font-size: 13px;
            white-space: nowrap;
        }
        
        .data-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #333;
            color: #e8e8e8;
            font-size: 14px;
        }
        
        .data-table tr:hover {
            background: rgba(255, 215, 0, 0.05);
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .status-pending {
            background: rgba(255, 193, 7, 0.2);
            color: #FFC107;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }
        
        .status-approved {
            background: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
            border: 1px solid rgba(76, 175, 80, 0.3);
        }
        
        .status-paid {
            background: rgba(33, 150, 243, 0.2);
            color: #2196F3;
            border: 1px solid rgba(33, 150, 243, 0.3);
        }
        
        .status-rejected {
            background: rgba(244, 67, 54, 0.2);
            color: #F44336;
            border: 1px solid rgba(244, 67, 54, 0.3);
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 16px;
        }
        
        .alert-success {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid #4CAF50;
            color: #4CAF50;
        }
        
        .alert-error {
            background: rgba(244, 67, 54, 0.2);
            border: 1px solid #F44336;
            color: #F44336;
        }
        
        .view-tabs { display: flex; gap: 12px; margin-bottom: 24px; border-bottom: 1px solid #333; padding-bottom: 12px; }
        .view-tab { background: #2a2a2a; border: 1px solid #444; color: #888; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s ease; }
        .view-tab:hover { background: #333; color: #fff; border-color: #666; }
        .view-tab.active { background: #FFD700; color: #000; border-color: #FFD700; box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3); }
        .view-content { display: none; }
        .view-content.active { display: block; }

        .btn-action {
            background: linear-gradient(180deg, #FFE680 0%, #FFD700 100%);
            color: #0b0b0b;
            border: 1px solid rgba(0, 0, 0, 0.25);
            border-radius: 10px;
            padding: 10px 14px;
            font-weight: 800;
            font-size: 13px;
            letter-spacing: 0.2px;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.2s ease, filter 0.2s ease;
            box-shadow: 0 8px 20px rgba(255, 215, 0, 0.18);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .btn-action:hover {
            transform: translateY(-1px);
            filter: brightness(1.02);
            box-shadow: 0 12px 26px rgba(255, 215, 0, 0.26);
        }

        .btn-action:active {
            transform: translateY(0);
            filter: brightness(0.98);
            box-shadow: 0 8px 18px rgba(255, 215, 0, 0.18);
        }

        .btn-action:focus-visible {
            outline: 2px solid rgba(255, 215, 0, 0.65);
            outline-offset: 2px;
        }

        #transactionModal.modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.72);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 18px;
        }

        #transactionModal .modal-panel {
            background: radial-gradient(1200px 500px at 20% 0%, rgba(255, 215, 0, 0.10), rgba(0, 0, 0, 0)) ,
                        linear-gradient(180deg, #171717 0%, #101010 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            box-shadow: 0 18px 60px rgba(0, 0, 0, 0.55);
            overflow: hidden;
        }

        #transactionModal .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 18px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(0, 0, 0, 0.20);
        }

        #transactionModal .modal-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 900;
            letter-spacing: 0.3px;
            color: #FFD700;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        #transactionModal .modal-close {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.10);
            color: #e5e5e5;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s ease, transform 0.15s ease;
        }

        #transactionModal .modal-close:hover {
            background: rgba(255, 255, 255, 0.10);
            transform: translateY(-1px);
        }

        #transactionModal .modal-body {
            padding: 18px;
        }

        #transactionModal .print-header {
            padding: 14px 14px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.03);
            margin-bottom: 14px;
        }

        #transactionModal .print-header h4 {
            margin: 0 0 6px 0;
            font-size: 15px;
            font-weight: 900;
            color: #FFD700;
        }

        #transactionModal .print-header p {
            margin: 0;
            color: #a3a3a3;
        }

        #transactionModal .printable-table {
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            overflow: hidden;
            background: rgba(0, 0, 0, 0.18);
        }

        #transactionModal .printable-table table {
            width: 100%;
            border-collapse: collapse;
        }

        #transactionModal .printable-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: linear-gradient(180deg, rgba(255, 215, 0, 0.18) 0%, rgba(0, 0, 0, 0.22) 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            color: #FFD700;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 0.35px;
        }

        #transactionModal .printable-table th,
        #transactionModal .printable-table td {
            padding: 11px 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        #transactionModal .printable-table th:nth-child(3),
        #transactionModal .printable-table td:nth-child(3) {
            text-align: right;
        }

        #transactionModal .printable-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }

        #transactionModal .printable-table td {
            color: #e5e5e5;
            font-size: 13px;
        }

        #transactionModal .editable-particular,
        #transactionModal .editable-amount {
            width: 100%;
            max-width: 220px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.10);
            border-radius: 10px;
            color: #fff;
            padding: 9px 10px;
            font-size: 13px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        #transactionModal .editable-amount {
            text-align: right;
            margin-left: auto;
            display: block;
        }

        #transactionModal .editable-particular:focus,
        #transactionModal .editable-amount:focus {
            outline: none;
            border-color: rgba(255, 215, 0, 0.55);
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.12);
            background: rgba(255, 255, 255, 0.08);
        }

        #transactionModal .modal-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            padding: 14px 18px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(0, 0, 0, 0.20);
        }

        #transactionModal .modal-footer .btn-primary,
        #transactionModal .modal-footer .btn-secondary {
            border-radius: 10px;
            padding: 10px 14px;
            font-weight: 900;
            font-size: 13px;
            letter-spacing: 0.2px;
            cursor: pointer;
            border: 1px solid rgba(255, 255, 255, 0.10);
            transition: transform 0.15s ease, filter 0.2s ease, box-shadow 0.2s ease;
        }

        #transactionModal .modal-footer .btn-primary {
            background: linear-gradient(180deg, #FFE680 0%, #FFD700 100%);
            color: #0b0b0b;
            border-color: rgba(0, 0, 0, 0.25);
            box-shadow: 0 10px 24px rgba(255, 215, 0, 0.16);
        }

        #transactionModal .modal-footer .btn-secondary {
            background: rgba(255, 255, 255, 0.06);
            color: #e5e5e5;
        }

        #transactionModal .modal-footer .btn-primary:hover,
        #transactionModal .modal-footer .btn-secondary:hover {
            transform: translateY(-1px);
            filter: brightness(1.02);
        }

        #transactionModal .modal-footer .btn-primary:active,
        #transactionModal .modal-footer .btn-secondary:active {
            transform: translateY(0);
            filter: brightness(0.98);
        }

        #transactionModal .ca-form-header {
            margin-bottom: 16px;
            padding: 14px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.03);
        }

        #transactionModal .ca-form-header h4 {
            margin: 0 0 6px 0;
            color: #FFD700;
            font-size: 15px;
            font-weight: 900;
        }

        #transactionModal .ca-form-header p {
            margin: 0;
            color: #a3a3a3;
            font-size: 13px;
        }

        #transactionModal .ca-form {
            display: grid;
            gap: 14px;
            padding: 14px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(0, 0, 0, 0.16);
        }

        #transactionModal .ca-field {
            display: grid;
            gap: 8px;
        }

        #transactionModal .ca-label {
            color: #b5b5b5;
            font-size: 12.5px;
            font-weight: 800;
            letter-spacing: 0.25px;
        }

        #transactionModal .ca-input,
        #transactionModal .ca-textarea,
        #transactionModal .ca-select {
            width: 100%;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.10);
            border-radius: 12px;
            padding: 11px 12px;
            color: #fff;
            font-size: 14px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        #transactionModal .ca-textarea {
            min-height: 84px;
            resize: vertical;
        }

        #transactionModal .ca-input:focus,
        #transactionModal .ca-textarea:focus,
        #transactionModal .ca-select:focus {
            outline: none;
            border-color: rgba(255, 215, 0, 0.55);
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.12);
            background: rgba(255, 255, 255, 0.08);
        }

        #transactionModal .ca-form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            padding-top: 6px;
        }

        #transactionModal .btn-ca-primary,
        #transactionModal .btn-ca-secondary {
            border-radius: 12px;
            padding: 11px 14px;
            font-weight: 900;
            font-size: 13px;
            letter-spacing: 0.2px;
            cursor: pointer;
            border: 1px solid rgba(255, 255, 255, 0.10);
            transition: transform 0.15s ease, filter 0.2s ease, box-shadow 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        #transactionModal .btn-ca-primary {
            background: linear-gradient(180deg, #FFE680 0%, #FFD700 100%);
            color: #0b0b0b;
            border-color: rgba(0, 0, 0, 0.25);
            box-shadow: 0 10px 22px rgba(255, 215, 0, 0.14);
            flex: 1;
            justify-content: center;
        }

        #transactionModal .btn-ca-secondary {
            background: rgba(255, 255, 255, 0.06);
            color: #e5e5e5;
        }

        #transactionModal .btn-ca-primary:hover,
        #transactionModal .btn-ca-secondary:hover {
            transform: translateY(-1px);
            filter: brightness(1.02);
        }

        #transactionModal .btn-ca-primary:active,
        #transactionModal .btn-ca-secondary:active {
            transform: translateY(0);
            filter: brightness(0.98);
        }

        /* Search Bar Styles */
        .ca-search-container {
            margin-bottom: 20px;
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .ca-search-box {
            position: relative;
            flex: 1;
            min-width: 280px;
            max-width: 400px;
        }
        
        .ca-search-box input {
            width: 100%;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.10);
            border-radius: 12px;
            padding: 12px 16px 12px 44px;
            color: #fff;
            font-size: 14px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }
        
        .ca-search-box input::placeholder {
            color: #888;
        }
        
        .ca-search-box input:focus {
            outline: none;
            border-color: rgba(255, 215, 0, 0.55);
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.12);
            background: rgba(255, 255, 255, 0.08);
        }
        
        .ca-search-box i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
            font-size: 16px;
        }
        
        .ca-search-btn {
            background: linear-gradient(180deg, #FFE680 0%, #FFD700 100%);
            color: #0b0b0b;
            border: 1px solid rgba(0, 0, 0, 0.25);
            border-radius: 12px;
            padding: 12px 20px;
            font-weight: 800;
            font-size: 13px;
            cursor: pointer;
            transition: transform 0.15s ease, filter 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 8px 20px rgba(255, 215, 0, 0.18);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .ca-search-btn:hover {
            transform: translateY(-1px);
            filter: brightness(1.02);
            box-shadow: 0 12px 26px rgba(255, 215, 0, 0.26);
        }
        
        .ca-clear-btn {
            background: rgba(255, 255, 255, 0.06);
            color: #e5e5e5;
            border: 1px solid rgba(255, 255, 255, 0.10);
            border-radius: 12px;
            padding: 12px 20px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .ca-clear-btn:hover {
            background: rgba(255, 255, 255, 0.10);
            transform: translateY(-1px);
        }
        
        /* Pagination Styles */
        .ca-pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding: 14px 18px;
            background: rgba(0, 0, 0, 0.20);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            flex-wrap: wrap;
            gap: 12px;
        }
        
        .ca-pagination-info {
            color: #a3a3a3;
            font-size: 13px;
        }
        
        .ca-pagination-info strong {
            color: #FFD700;
        }
        
        .ca-pagination-controls {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .ca-page-btn {
            background: rgba(255, 255, 255, 0.06);
            color: #e5e5e5;
            border: 1px solid rgba(255, 255, 255, 0.10);
            border-radius: 10px;
            padding: 8px 14px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-width: 36px;
            justify-content: center;
        }
        
        .ca-page-btn:hover:not(.disabled) {
            background: rgba(255, 255, 255, 0.10);
            transform: translateY(-1px);
        }
        
        .ca-page-btn.active {
            background: linear-gradient(180deg, #FFE680 0%, #FFD700 100%);
            color: #0b0b0b;
            border-color: rgba(0, 0, 0, 0.25);
            box-shadow: 0 4px 12px rgba(255, 215, 0, 0.2);
        }
        
        .ca-page-btn.disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
        
        .ca-per-page-select {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.10);
            border-radius: 10px;
            padding: 8px 12px;
            color: #fff;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .ca-per-page-select:focus {
            outline: none;
            border-color: rgba(255, 215, 0, 0.55);
        }
        
        /* Enhanced Report Card */
        .report-card {
            background: radial-gradient(1200px 500px at 20% 0%, rgba(255, 215, 0, 0.08), rgba(0, 0, 0, 0)) ,
                        linear-gradient(180deg, #1a1a1a 0%, #141414 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }
        
        .report-card h3 {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 900;
            letter-spacing: 0.3px;
        }
        
        /* Enhanced Data Table */
        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .data-table th {
            background: linear-gradient(180deg, rgba(255, 215, 0, 0.22) 0%, rgba(255, 165, 0, 0.15) 100%);
            color: #FFD700;
            padding: 14px 16px;
            text-align: left;
            font-weight: 900;
            font-size: 12px;
            letter-spacing: 0.35px;
            text-transform: uppercase;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        
        .data-table th:first-child {
            border-radius: 10px 0 0 0;
        }
        
        .data-table th:last-child {
            border-radius: 0 10px 0 0;
        }
        
        .data-table td {
            padding: 14px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            color: #e5e5e5;
            font-size: 14px;
        }
        
        .data-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.04);
        }
        
        .data-table tbody tr:last-child td:first-child {
            border-radius: 0 0 0 10px;
        }
        
        .data-table tbody tr:last-child td:last-child {
            border-radius: 0 0 10px 0;
        }
        
        /* ============================================
           MOBILE GRID VIEW - Cash Advance Cards
           ============================================ */
        @media (max-width: 767px) {
            /* Stats grid - 2 columns on mobile */
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
                margin-bottom: 20px;
            }
            
            .stat-box {
                padding: 16px 12px;
            }
            
            .stat-box .amount {
                font-size: 22px;
            }
            
            .stat-box h4 {
                font-size: 10px;
            }
            
            /* Hide table header on mobile */
            .data-table thead {
                display: none !important;
            }
            
            /* Convert table to block */
            .data-table tbody {
                display: block;
            }
            
            .data-table tbody tr {
                display: block;
                background: #1a1a1a;
                border: 1px solid #333;
                border-radius: 12px;
                padding: 16px;
                margin-bottom: 12px;
                box-sizing: border-box;
            }
            
            /* Style each cell as a row with label */
            .data-table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px 0;
                border-bottom: 1px solid #333;
                font-size: 14px;
            }
            
            .data-table td:last-child {
                border-bottom: none;
                padding-bottom: 0;
            }
            
            /* Add labels before values */
            .data-table td:nth-child(1)::before { content: "Last Transaction: "; color: #888; font-size: 12px; }
            .data-table td:nth-child(2)::before { content: "Employee: "; color: #888; font-size: 12px; }
            .data-table td:nth-child(3)::before { content: "Total CA: "; color: #888; font-size: 12px; }
            .data-table td:nth-child(4)::before { content: "Total Paid: "; color: #888; font-size: 12px; }
            .data-table td:nth-child(5)::before { content: "Balance: "; color: #888; font-size: 12px; }
            
            /* Actions cell - stack buttons vertically */
            .data-table td:last-child {
                flex-direction: column;
                gap: 8px;
                align-items: stretch;
                margin-top: 8px;
                padding-top: 12px;
                border-top: 1px solid #444;
            }
            
            .data-table td:last-child button {
                width: 100%;
                justify-content: center;
                padding: 12px;
                font-size: 13px;
            }
            
            /* Employee name styling */
            .data-table td:nth-child(2) strong {
                font-size: 16px;
                color: #FFD700;
            }
            
            /* Amount styling */
            .data-table td:nth-child(3),
            .data-table td:nth-child(4),
            .data-table td:nth-child(5) {
                font-weight: 600;
            }
            
            /* Search container adjustments */
            .ca-search-container {
                flex-direction: column;
                gap: 10px;
            }
            
            .ca-search-box {
                min-width: 100%;
                max-width: 100%;
            }
            
            /* Pagination adjustments */
            .ca-pagination {
                flex-direction: column;
                gap: 12px;
                align-items: stretch;
            }
            
            .ca-pagination-controls {
                justify-content: center;
                flex-wrap: wrap;
            }
        }
        
        /* Extra small screens */
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .cash-advance-container {
                padding: 0 10px;
            }
            
            .data-table tbody tr {
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="app-shell">
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="header-card">
                <div class="header-left">
                    <div>
                        <div class="welcome">Cash Advance</div>
                        <div class="text-sm text-gray">Request and track cash advances</div>
                    </div>
                </div>
                <div class="text-sm text-gray">
                    Today: <?php echo date('F d, Y'); ?>
                </div>
            </div>
            
            <div class="cash-advance-container">
                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-box">
                        <h4>Total Employees</h4>
                        <div class="amount"><?php echo $totalEmployees; ?></div>
                    </div>
                    <div class="stat-box">
                        <h4>With Outstanding Balance</h4>
                        <div class="amount"><?php echo $totalWithBalance; ?></div>
                    </div>
                    <div class="stat-box">
                        <h4>Total Cash Advance</h4>
                        <div class="amount">₱<?php echo number_format($totalCA, 2); ?></div>
                    </div>
                    <div class="stat-box">
                        <h4>Total Paid</h4>
                        <div class="amount" style="color: #4CAF50;">₱<?php echo number_format($totalPaid, 2); ?></div>
                    </div>
                    <div class="stat-box">
                        <h4>Outstanding Balance</h4>
                        <div class="amount">₱<?php echo number_format($totalOutstanding, 2); ?></div>
                    </div>
                </div>
                
                <!-- Employee View -->
                <div id="view-employee" class="view-content active">
                    <div class="report-card">
                        <h3 style="color: #FFD700; margin-bottom: 16px; font-size: 16px;">
                            <i class="fas fa-users mr-2"></i>Employee Cash Advance Summary
                        </h3>
                        
                        <!-- Search Bar -->
                        <form method="GET" class="ca-search-container">
                            <div class="ca-search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" name="search" placeholder="Search by name or employee code..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                            </div>
                            <button type="submit" class="ca-search-btn">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <?php if ($searchTerm !== ''): ?>
                            <a href="?" class="ca-clear-btn">
                                <i class="fas fa-times"></i> Clear
                            </a>
                            <?php endif; ?>
                        </form>
                        
                        <div class="report-table" style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Last Transaction</th>
                                        <th>Employee</th>
                                        <th>Total CA</th>
                                        <th>Total Paid</th>
                                        <th>Balance</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($employeeList as $emp): ?>
                                    <tr data-emp-id="<?php echo $emp['id']; ?>">
                                        <td>
                                            <?php if ($emp['last_date']): ?>
                                                <?php echo date('M d, Y', strtotime($emp['last_date'])); ?>
                                            <?php else: ?>
                                                <span style="color: #666;">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($emp['last_name'] . ', ' . $emp['first_name']); ?></strong>
                                        </td>
                                        <td>₱<?php echo number_format($emp['total_ca'], 2); ?></td>
                                        <td style="color: #4CAF50;">₱<?php echo number_format($emp['total_paid'], 2); ?></td>
                                        <td class="balance-cell" style="color: <?php echo $emp['balance'] > 0 ? '#FFD700' : '#888'; ?>">
                                            ₱<?php echo number_format($emp['balance'], 2); ?>
                                        </td>
                                        <td>
                                            <button class="btn-action" onclick="viewEmployeeHistory(<?php echo $emp['id']; ?>, '<?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>')">
                                                <i class="fas fa-money-bill-wave mr-1"></i> Cash Advance Record
                                            </button>
                                            <button class="btn-print-action" onclick="quickPrintEmployee(<?php echo $emp['id']; ?>, '<?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>')">
                                                <i class="fas fa-print mr-1"></i> Print
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($employeeList)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 40px; color: #888;">
                                            <i class="fas fa-search" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>
                                            No employees found matching your search.
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($isAdmin && $totalPages > 1): ?>
                        <div class="ca-pagination">
                            <div class="ca-pagination-info">
                                Showing <strong><?php echo (($currentPage - 1) * $perPage) + 1; ?> - <?php echo min($currentPage * $perPage, $totalEmployeesAll); ?></strong> of <strong><?php echo $totalEmployeesAll; ?></strong> employees
                            </div>
                            <div class="ca-pagination-controls">
                                <!-- Per Page Selector -->
                                <select class="ca-per-page-select" onchange="window.location.href='?page=1&per_page='+this.value<?php echo $searchTerm !== '' ? "+'&search='+'" . urlencode($searchTerm) . "'" : ""; ?>">
                                    <option value="10" <?php echo $perPage == 10 ? 'selected' : ''; ?>>10 per page</option>
                                    <option value="20" <?php echo $perPage == 20 ? 'selected' : ''; ?>>20 per page</option>
                                    <option value="50" <?php echo $perPage == 50 ? 'selected' : ''; ?>>50 per page</option>
                                    <option value="100" <?php echo $perPage == 100 ? 'selected' : ''; ?>>100 per page</option>
                                </select>
                                
                                <!-- Previous Button -->
                                <?php if ($currentPage > 1): ?>
                                <a href="?page=<?php echo $currentPage - 1; ?>&per_page=<?php echo $perPage; ?><?php echo $searchTerm !== '' ? '&search=' . urlencode($searchTerm) : ''; ?>" class="ca-page-btn">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                                <?php else: ?>
                                <span class="ca-page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                                <?php endif; ?>
                                
                                <!-- Page Numbers -->
                                <?php
                                $startPage = max(1, $currentPage - 2);
                                $endPage = min($totalPages, $currentPage + 2);
                                
                                if ($startPage > 1) {
                                    echo '<a href="?page=1&per_page=' . $perPage . ($searchTerm !== '' ? '&search=' . urlencode($searchTerm) : '') . '" class="ca-page-btn">1</a>';
                                    if ($startPage > 2) {
                                        echo '<span class="ca-page-btn disabled">...</span>';
                                    }
                                }
                                
                                for ($i = $startPage; $i <= $endPage; $i++) {
                                    if ($i == $currentPage) {
                                        echo '<span class="ca-page-btn active">' . $i . '</span>';
                                    } else {
                                        echo '<a href="?page=' . $i . '&per_page=' . $perPage . ($searchTerm !== '' ? '&search=' . urlencode($searchTerm) : '') . '" class="ca-page-btn">' . $i . '</a>';
                                    }
                                }
                                
                                if ($endPage < $totalPages) {
                                    if ($endPage < $totalPages - 1) {
                                        echo '<span class="ca-page-btn disabled">...</span>';
                                    }
                                    echo '<a href="?page=' . $totalPages . '&per_page=' . $perPage . ($searchTerm !== '' ? '&search=' . urlencode($searchTerm) : '') . '" class="ca-page-btn">' . $totalPages . '</a>';
                                }
                                ?>
                                
                                <!-- Next Button -->
                                <?php if ($currentPage < $totalPages): ?>
                                <a href="?page=<?php echo $currentPage + 1; ?>&per_page=<?php echo $perPage; ?><?php echo $searchTerm !== '' ? '&search=' . urlencode($searchTerm) : ''; ?>" class="ca-page-btn">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                                <?php else: ?>
                                <span class="ca-page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php elseif ($isAdmin && $totalEmployeesAll > 0): ?>
                        <div class="ca-pagination" style="justify-content: center;">
                            <div class="ca-pagination-info">
                                Showing <strong><?php echo $totalEmployeesAll; ?></strong> employee<?php echo $totalEmployeesAll > 1 ? 's' : ''; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
            </div>
        </main>
    </div>
    
    <script>
        // Tab switching (only Employee View now)
        function switchView(view) {
            // Only employee view exists now
            document.getElementById('tab-employee').classList.add('active');
            document.getElementById('view-employee').classList.add('active');
        }
        
        // Store current employee ID for adding transactions
        let currentEmployeeIdForAdd = null;
        let currentEmployeeNameForAdd = '';
        
        // Show add transaction form in modal
        function showAddTransactionForm() {
            const content = document.getElementById('modalContent');
            
            content.innerHTML = `
                <div class="ca-form-header">
                    <h4>Add New Transaction</h4>
                    <p>Employee: ${currentEmployeeNameForAdd}</p>
                </div>
                <form id="addTransactionForm" class="ca-form">
                    <div class="ca-field">
                        <label class="ca-label">Particular</label>
                        <select id="newParticular" class="ca-select">
                            <option value="Cash Advance">Cash Advance</option>
                            <option value="Payment">Payment</option>
                        </select>
                    </div>
                    <div class="ca-field">
                        <label class="ca-label">Amount (₱)</label>
                        <input type="number" id="newAmount" min="0.01" step="0.01" required placeholder="Enter amount" class="ca-input">
                    </div>
                    <div class="ca-field">
                        <label class="ca-label">Notes / Reason (Optional)</label>
                        <textarea id="newReason" rows="2" placeholder="Enter notes or reason" class="ca-textarea"></textarea>
                    </div>
                    <div class="ca-form-actions">
                        <button type="button" onclick="saveNewTransaction()" class="btn-ca-primary">
                            <i class="fas fa-save"></i>Save Transaction
                        </button>
                        <button type="button" onclick="reloadEmployeeHistory()" class="btn-ca-secondary">
                            <i class="fas fa-times"></i>Cancel
                        </button>
                    </div>
                </form>
            `;
        }
        
        // Save new transaction
        function saveNewTransaction() {
            const particular = document.getElementById('newParticular').value;
            const amount = parseFloat(document.getElementById('newAmount').value);
            const reason = document.getElementById('newReason').value;
            
            if (!amount || amount <= 0) {
                alert('Please enter a valid amount');
                return;
            }
            
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'add_transaction_ajax=1&employee_id=' + currentEmployeeIdForAdd + '&particular=' + encodeURIComponent(particular) + '&amount=' + amount + '&reason=' + encodeURIComponent(reason)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Transaction added successfully!');
                    // Reload entire page to update all totals
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to add transaction'));
                }
            })
            .catch(error => {
                alert('Error adding transaction');
            });
        }
        
        // Print cash advance history
        function printCashAdvanceHistory() {
            // Check if signature exists first
            if (!currentEmployeeSignature) {
                if (confirm('No e-signature uploaded yet. Would you like to upload your signature first?\n\nClick OK to upload signature, or Cancel to print without signature.')) {
                    openSignatureModal();
                    return;
                }
            }
            
            const modalContent = document.getElementById('modalContent');
            const signatureSection = modalContent.querySelector('.signature-section');
            
            // Show signature section for printing
            if (signatureSection) {
                signatureSection.style.display = 'block';
            }
            
            // Create a new window for printing
            const printWindow = window.open('', '_blank');
            
            // Build signature HTML - show e-signature if available, otherwise show line
            let employeeSignatureHtml = '';
            if (currentEmployeeSignature) {
                employeeSignatureHtml = `<img src="${currentEmployeeSignature}" style="max-width: 150px; max-height: 60px;" alt="Employee Signature">`;
            } else {
                employeeSignatureHtml = `<div class="line"></div>`;
            }
            
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Cash Advance History - ${currentEmployeeNameForAdd}</title>
                    <style>
                        * { box-sizing: border-box; margin: 0; padding: 0; }
                        @page { size: auto; margin: 10mm; }
                        body { font-family: Arial, sans-serif; padding: 10px; background: #fff; color: #333; font-size: 10px; }
                        .print-header { margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px solid #333; }
                        .print-header h2 { color: #000; margin: 0 0 3px 0; font-size: 14px; }
                        .print-header h4 { color: #000; margin: 0 0 2px 0; font-size: 12px; }
                        .print-header p { color: #666; margin: 1px 0; font-size: 9px; }
                        table { width: 100%; border-collapse: collapse; margin: 8px 0; }
                        th, td { padding: 4px 6px; text-align: left; border-bottom: 1px solid #ddd; font-size: 9px; }
                        th { background: #f5f5f5; font-weight: bold; color: #000; }
                        td { color: #333; }
                        td:last-child, th:last-child { text-align: right; }
                        .balance-row { font-weight: bold; background: #f9f9f9; }
                        .print-balance { margin-top: 10px; padding: 8px 10px; background: #f5f5f5; border: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; }
                        .print-balance .label { font-size: 10px; color: #666; }
                        .print-balance .amount { font-size: 14px; font-weight: bold; color: #000; }
                        .signature-section { margin-top: 15px; padding-top: 10px; }
                        .signature-row { display: flex; justify-content: space-between; margin-top: 15px; }
                        .signature-box { text-align: center; width: 45%; }
                        .signature-box .line { border-bottom: 1px solid #333; padding-bottom: 3px; margin-bottom: 3px; min-height: 30px; }
                        .signature-box .label { color: #666; font-size: 9px; margin: 0; }
                        .signature-box .name { color: #888; font-size: 8px; margin: 3px 0 0 0; }
                        .signature-image { max-width: 120px; max-height: 40px; margin-bottom: 3px; }
                        .footer-note { text-align: center; margin-top: 15px; padding-top: 10px; border-top: 1px solid #eee; }
                        .footer-note p { color: #999; font-size: 8px; margin: 0; }
                        .logo-img { max-width: 50px; max-height: 50px; margin-bottom: 5px; }
                        @media print {
                            body { padding: 0; margin: 0; }
                            .no-print { display: none !important; }
                        }
                    </style>
                </head>
                <body>
                    <div style="text-align: center; margin-bottom: 8px;">
                        <img src="http://localhost/main/assets/img/profile/jajr-logo.png" class="logo-img" alt="JAJR Company Logo">
                    </div>
                    <div class="print-header">
                        <h2>JAJR Company - Cash Advance History</h2>
                        ${modalContent.querySelector('.print-header').innerHTML}
                    </div>
                    <div class="printable-table">
                        ${modalContent.querySelector('.printable-table table').outerHTML}
                    </div>
                    <div class="print-balance">
                        <span class="label">Current Balance:</span>
                        <span class="amount">${modalContent.querySelector('.print-balance span:last-child').textContent}</span>
                    </div>
                    <div class="signature-section">
                        <div class="signature-row">
                            <div class="signature-box">
                                ${employeeSignatureHtml}
                                <p class="label">Employee Signature</p>
                                <p class="name">${currentEmployeeNameForAdd}</p>
                            </div>
                            <div class="signature-box">
                                <div class="line"></div>
                                <p class="label">Authorized By</p>
                                <p class="name">HR / Admin</p>
                            </div>
                        </div>
                        <div class="footer-note">
                            <p>This document is computer generated and valid without signature.</p>
                        </div>
                    </div>
                </body>
                </html>
            `);
            
            printWindow.document.close();
            printWindow.focus();
            
            // Delay print to allow styles to load
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 250);
            
            // Hide signature section after printing
            if (signatureSection) {
                signatureSection.style.display = 'none';
            }
        }
        
        // Reload employee history after adding
        function reloadEmployeeHistory() {
            viewEmployeeHistory(currentEmployeeIdForAdd, currentEmployeeNameForAdd);
        }
        
        // Quick print employee cash advance (direct from list)
        function quickPrintEmployee(empId, empName) {
            // Load signature first
            loadEmployeeSignature(empId).then(() => {
                // Check if signature exists
                if (!currentEmployeeSignature) {
                    // Set current employee for signature upload
                    currentEmployeeIdForAdd = empId;
                    currentEmployeeNameForAdd = empName;
                    
                    if (confirm('No e-signature uploaded yet for this employee. Would you like to upload the signature first?\n\nClick OK to upload signature, or Cancel to print without signature.')) {
                        openSignatureModal();
                        return;
                    }
                }
                
                // Fetch employee data
                fetch('api/get_employee_ca.php?emp_id=' + empId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            printEmployeeDataDirectly(data.employee, data.transactions, empName);
                        } else {
                            alert('Error loading employee data');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error loading employee data');
                    });
            });
        }
        
        // Print employee data directly without opening modal
        function printEmployeeDataDirectly(employee, transactions, empName) {
            let transactionsHtml = '';
            let runningBalance = 0;
            
            // Calculate from oldest to newest
            const sorted = [...transactions].reverse();
            sorted.forEach(t => {
                if (t.particular === 'Payment') {
                    runningBalance -= parseFloat(t.amount);
                } else {
                    runningBalance += parseFloat(t.amount);
                }
                
                transactionsHtml += `
                    <tr>
                        <td>${new Date(t.request_date).toLocaleDateString()}</td>
                        <td>${t.particular}</td>
                        <td style="text-align: right;">₱${parseFloat(t.amount).toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                        <td style="text-align: right; font-weight: bold;">₱${runningBalance.toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                    </tr>
                `;
            });
            
            // Build signature HTML
            let employeeSignatureHtml = '';
            if (currentEmployeeSignature) {
                employeeSignatureHtml = `<img src="${currentEmployeeSignature}" style="max-width: 150px; max-height: 60px;" alt="Employee Signature">`;
            } else {
                employeeSignatureHtml = `<div class="line"></div>`;
            }
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Cash Advance History - ${empName}</title>
                    <style>
                        * { box-sizing: border-box; margin: 0; padding: 0; }
                        @page { size: auto; margin: 10mm; }
                        body { font-family: Arial, sans-serif; padding: 10px; background: #fff; color: #333; font-size: 10px; }
                        .print-header { margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px solid #333; }
                        .print-header h2 { color: #000; margin: 0 0 3px 0; font-size: 14px; }
                        .print-header h4 { color: #333; margin: 0 0 2px 0; font-size: 12px; }
                        .print-header p { color: #666; margin: 1px 0; font-size: 9px; }
                        table { width: 100%; border-collapse: collapse; margin: 8px 0; }
                        th, td { padding: 4px 6px; text-align: left; border-bottom: 1px solid #ddd; font-size: 9px; }
                        th { background: #f5f5f5; font-weight: bold; color: #000; }
                        td { color: #333; }
                        td:last-child, th:last-child { text-align: right; }
                        .print-balance { margin-top: 10px; padding: 8px 10px; background: #f5f5f5; border: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; }
                        .print-balance .label { font-size: 10px; color: #666; }
                        .print-balance .amount { font-size: 14px; font-weight: bold; color: #000; }
                        .signature-section { margin-top: 15px; padding-top: 10px; }
                        .signature-row { display: flex; justify-content: space-between; margin-top: 15px; }
                        .signature-box { text-align: center; width: 45%; }
                        .signature-box .line { border-bottom: 1px solid #333; padding-bottom: 3px; margin-bottom: 3px; min-height: 30px; }
                        .signature-box .label { color: #666; font-size: 9px; margin: 0; }
                        .signature-box .name { color: #888; font-size: 8px; margin: 3px 0 0 0; }
                        .logo-img { max-width: 50px; max-height: 50px; margin-bottom: 5px; }
                        .footer-note { text-align: center; margin-top: 15px; padding-top: 10px; border-top: 1px solid #eee; }
                        .footer-note p { color: #999; font-size: 8px; margin: 0; }
                        @media print {
                            body { padding: 0; margin: 0; }
                        }
                    </style>
                </head>
                <body>
                    <div style="text-align: center; margin-bottom: 8px;">
                        <img src="http://localhost/main/assets/img/profile/jajr-logo.png" class="logo-img" alt="JAJR Company Logo">
                    </div>
                    <div class="print-header">
                        <h2>JAJR Company - Cash Advance History</h2>
                        <h4>${employee.last_name}, ${employee.first_name}</h4>
                        <p>Code: ${employee.employee_code}</p>
                        <p>Date Printed: ${new Date().toLocaleDateString()}</p>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Particular</th>
                                <th style="text-align: right;">Amount</th>
                                <th style="text-align: right;">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${transactionsHtml || '<tr><td colspan="4" style="text-align: center; padding: 10px;">No transactions found</td></tr>'}
                        </tbody>
                    </table>
                    <div class="print-balance">
                        <span class="label">Current Balance:</span>
                        <span class="amount">₱${parseFloat(employee.balance).toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                    </div>
                    <div class="signature-section">
                        <div class="signature-row">
                            <div class="signature-box">
                                ${employeeSignatureHtml}
                                <p class="label">Employee Signature</p>
                                <p class="name">${empName}</p>
                            </div>
                            <div class="signature-box">
                                <div class="line"></div>
                                <p class="label">Authorized By</p>
                                <p class="name">HR / Admin</p>
                            </div>
                        </div>
                        <div class="footer-note">
                            <p>This document is computer generated and valid without signature.</p>
                        </div>
                    </div>
                </body>
                </html>
            `);
            
            printWindow.document.close();
            printWindow.focus();
            
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 250);
        }
        
        // View employee history
        function viewEmployeeHistory(empId, empName) {
            currentEmployeeIdForAdd = empId;
            currentEmployeeNameForAdd = empName;
            
            fetch('api/get_employee_ca.php?emp_id=' + empId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showEmployeeHistoryModal(data.employee, data.transactions);
                    } else {
                        alert('Error loading history');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading history');
                });
        }
        
        // Show employee history modal with 4 columns
        function showEmployeeHistoryModal(employee, transactions) {
            const modal = document.getElementById('transactionModal');
            const content = document.getElementById('modalContent');
            
            let transactionsHtml = '';
            let runningBalance = 0;
            
            // Calculate from oldest to newest
            const sorted = [...transactions].reverse();
            sorted.forEach(t => {
                if (t.particular === 'Payment') {
                    runningBalance -= parseFloat(t.amount);
                } else {
                    runningBalance += parseFloat(t.amount);
                }
                
                const particularClass = t.particular === 'Cash Advance' ? 'particular-ca' : 'particular-payment';
                
                transactionsHtml += `
                    <tr data-trans-id="${t.id}">
                        <td>${new Date(t.request_date).toLocaleDateString()}</td>
                        <td>
                            <select class="editable-particular" onchange="updateModalTransaction(${t.id}, 'particular', this.value)">
                                <option value="Cash Advance" ${t.particular === 'Cash Advance' ? 'selected' : ''}>Cash Advance</option>
                                <option value="Payment" ${t.particular === 'Payment' ? 'selected' : ''}>Payment</option>
                            </select>
                        </td>
                        <td>
                            <input type="number" class="editable-amount" value="${t.amount}" min="0.01" step="0.01" onchange="updateModalTransaction(${t.id}, 'amount', this.value)">
                        </td>
                        <td style="color: #FFD700; font-weight: 700; text-align: right;">₱${runningBalance.toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                    </tr>
                `;
            });
            
            content.innerHTML = `
                <div class="print-header">
                    <h4 style="color: #FFD700; margin: 0 0 10px 0;">${employee.last_name}, ${employee.first_name}</h4>
                    <p style="color: #888; margin: 0; font-size: 13px;">Code: ${employee.employee_code}</p>
                    <p style="color: #888; margin: 5px 0 0 0; font-size: 12px;">Date Printed: ${new Date().toLocaleDateString()}</p>
                </div>
                <div class="printable-table" style="max-height: 400px; overflow-y: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #2a2a2a;">
                                <th style="padding: 10px; text-align: left; font-size: 12px; color: #FFD700;">Date</th>
                                <th style="padding: 10px; text-align: left; font-size: 12px; color: #FFD700;">Particular</th>
                                <th style="padding: 10px; text-align: right; font-size: 12px; color: #FFD700;">Amount</th>
                                <th style="padding: 10px; text-align: right; font-size: 12px; color: #FFD700;">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${transactionsHtml || '<tr><td colspan="4" style="text-align: center; padding: 20px; color: #666;">No transactions found</td></tr>'}
                        </tbody>
                    </table>
                </div>
                <div class="print-balance" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #333; display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: #888;">Current Balance:</span>
                    <span style="color: #FFD700; font-size: 24px; font-weight: 700;">₱${parseFloat(employee.balance).toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                </div>
                <div class="signature-section" style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #444; display: none;">
                    <div style="display: flex; justify-content: space-between; margin-top: 30px;">
                        <div style="text-align: center; width: 45%;">
                            <div style="border-bottom: 1px solid #333; padding-bottom: 5px; margin-bottom: 5px; min-height: 30px;"></div>
                            <p style="color: #666; font-size: 12px; margin: 0;">Employee Signature</p>
                            <p style="color: #888; font-size: 11px; margin: 5px 0 0 0;">${employee.first_name} ${employee.last_name}</p>
                        </div>
                        <div style="text-align: center; width: 45%;">
                            <div style="border-bottom: 1px solid #333; padding-bottom: 5px; margin-bottom: 5px; min-height: 30px;"></div>
                            <p style="color: #666; font-size: 12px; margin: 0;">Authorized By</p>
                            <p style="color: #888; font-size: 11px; margin: 5px 0 0 0;">HR / Admin</p>
                        </div>
                    </div>
                    <div style="text-align: center; margin-top: 40px;">
                        <p style="color: #666; font-size: 11px; margin: 0;">This document is computer generated and valid without signature.</p>
                    </div>
                </div>
            `;
            
            modal.style.display = 'flex';
        }
        
        // Update transaction from modal
        function updateModalTransaction(id, field, value) {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'ajax_update=1&transaction_id=' + id + '&field=' + field + '&value=' + encodeURIComponent(value)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the page to update all balances
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to update'));
                }
            })
            .catch(error => {
                alert('Error updating value');
            });
        }
        
        function closeModal() {
            document.getElementById('transactionModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('transactionModal');
            if (e.target === modal) {
                closeModal();
            }
        });
    </script>
    
    <!-- Employee History Modal -->
    <div id="transactionModal" class="modal-backdrop" style="display: none;">
        <div class="modal-panel" style="max-width: 900px; width: 90%;">
            <div class="modal-header">
                <h3 class="text-yellow-400">
                    <i class="fas fa-history mr-2"></i>Cash Advance History
                </h3>
                <button type="button" onclick="closeModal()" class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="modalContent">
                <!-- Content will be dynamically inserted -->
            </div>
            <div class="modal-footer">
                <button type="button" onclick="showAddTransactionForm()" class="btn-primary no-print">
                    <i class="fas fa-plus mr-2"></i>Add Transaction
                </button>
                <button type="button" onclick="openSignatureModal()" class="btn-primary no-print" style="background: #2196F3;" id="signatureBtn">
                    <i class="fas fa-signature mr-2"></i>Upload Signature
                </button>
                <button type="button" onclick="printCashAdvanceHistory()" class="btn-primary no-print" style="background: #4CAF50;">
                    <i class="fas fa-print mr-2"></i>Print
                </button>
                <button type="button" onclick="closeModal()" class="btn-secondary no-print">Close</button>
            </div>
        </div>
    </div>

<!-- Signature Modal -->
<div id="signatureModal" class="modal-backdrop" style="display: none;">
    <div class="modal-panel" style="max-width: 650px; width: 90%;">
        <div class="modal-header">
            <h3 class="text-yellow-400">
                <i class="fas fa-signature mr-2"></i>E-Signature
            </h3>
            <button type="button" onclick="closeSignatureModal()" class="modal-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <!-- Signature Method Tabs -->
            <div style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid #444; padding-bottom: 10px;">
                <button type="button" id="tabDrawSig" onclick="switchSignatureTab('draw')" class="sig-tab active" style="flex: 1; padding: 10px; background: #FFD700; color: #000; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-pen mr-2"></i>Draw Signature
                </button>
                <button type="button" id="tabUploadSig" onclick="switchSignatureTab('upload')" class="sig-tab" style="flex: 1; padding: 10px; background: #333; color: #fff; border: 1px solid #444; border-radius: 8px; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-upload mr-2"></i>Upload Image
                </button>
            </div>
            
            <!-- Draw Signature Section -->
            <div id="drawSigSection" style="display: block;">
                <div style="margin-bottom: 15px;">
                    <p style="color: #888; font-size: 13px; margin: 0;">
                        Please draw your signature in the box below using your mouse or touch device.
                    </p>
                </div>
                <div style="border: 2px solid #444; border-radius: 8px; background: #fff; overflow: hidden;">
                    <canvas id="signatureCanvas" width="600" height="200" style="display: block; cursor: crosshair;"></canvas>
                </div>
                <div style="margin-top: 15px; display: flex; gap: 10px; justify-content: center;">
                    <button type="button" onclick="clearSignature()" class="btn-secondary">
                        <i class="fas fa-eraser mr-1"></i>Clear
                    </button>
                    <button type="button" onclick="uploadSignature()" class="btn-primary" style="background: #4CAF50;">
                        <i class="fas fa-save mr-1"></i>Save Signature
                    </button>
                </div>
            </div>
            
            <!-- Upload Image Section -->
            <div id="uploadSigSection" style="display: none;">
                <div style="margin-bottom: 15px;">
                    <p style="color: #888; font-size: 13px; margin: 0;">
                        Upload an existing signature image file (PNG, JPG, or JPEG).
                    </p>
                </div>
                <div style="border: 2px dashed #555; border-radius: 8px; padding: 30px; text-align: center; background: rgba(255,255,255,0.03);">
                    <i class="fas fa-cloud-upload-alt" style="font-size: 48px; color: #FFD700; margin-bottom: 15px;"></i>
                    <p style="color: #888; margin-bottom: 15px;">Drag and drop your signature image here, or click to browse</p>
                    <input type="file" id="signatureFileInput" accept="image/png,image/jpeg,image/jpg" style="display: none;" onchange="handleSignatureFileUpload(this)">
                    <button type="button" onclick="document.getElementById('signatureFileInput').click()" class="btn-primary" style="background: #2196F3;">
                        <i class="fas fa-folder-open mr-1"></i>Choose File
                    </button>
                    <div id="filePreviewContainer" style="margin-top: 15px; display: none;">
                        <p style="color: #4CAF50; font-size: 12px; margin-bottom: 5px;"><i class="fas fa-check mr-1"></i>File selected</p>
                        <img id="filePreview" style="max-width: 200px; max-height: 80px; border: 1px solid #ddd; border-radius: 4px; background: #fff; padding: 5px;">
                    </div>
                </div>
                <div style="margin-top: 15px; display: flex; gap: 10px; justify-content: center;">
                    <button type="button" onclick="clearFileSelection()" class="btn-secondary">
                        <i class="fas fa-times mr-1"></i>Clear
                    </button>
                    <button type="button" onclick="uploadSignatureFile()" class="btn-primary" style="background: #4CAF50;">
                        <i class="fas fa-upload mr-1"></i>Upload Signature
                    </button>
                </div>
            </div>
            
            <div id="signatureStatus" style="margin-top: 15px; text-align: center; font-size: 13px;"></div>
        </div>
    </div>
</div>

<style>
    /* Signature Modal Styles */
    #signatureModal .modal-panel {
        background: radial-gradient(1200px 500px at 20% 0%, rgba(255, 215, 0, 0.10), rgba(0, 0, 0, 0)),
                    linear-gradient(180deg, #171717 0%, #101010 100%);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 14px;
        box-shadow: 0 18px 60px rgba(0, 0, 0, 0.55);
    }
    
    #signatureCanvas {
        touch-action: none;
        -webkit-touch-callout: none;
        -webkit-user-select: none;
        user-select: none;
    }
    
    .btn-print-action {
        background: linear-gradient(180deg, #4CAF50 0%, #45a049 100%);
        color: #fff;
        border: 1px solid rgba(0, 0, 0, 0.25);
        border-radius: 8px;
        padding: 6px 12px;
        font-weight: 700;
        font-size: 12px;
        cursor: pointer;
        transition: transform 0.15s ease, filter 0.2s ease, box-shadow 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    
    .btn-print-action:hover {
        transform: translateY(-1px);
        filter: brightness(1.02);
    }
    
    .signature-preview {
        max-width: 150px;
        max-height: 60px;
        border: 1px solid #ddd;
        border-radius: 4px;
        background: #fff;
    }
</style>

<script>
    // Signature canvas variables
    let signatureCanvas, signatureCtx;
    let isDrawing = false;
    let currentSignatureType = 'employee';
    
    // Initialize signature canvas when modal opens
    function initSignatureCanvas() {
        signatureCanvas = document.getElementById('signatureCanvas');
        if (!signatureCanvas) return;
        
        signatureCtx = signatureCanvas.getContext('2d');
        signatureCtx.strokeStyle = '#000';
        signatureCtx.lineWidth = 2;
        signatureCtx.lineCap = 'round';
        signatureCtx.lineJoin = 'round';
        
        // Mouse events
        signatureCanvas.addEventListener('mousedown', startDrawing);
        signatureCanvas.addEventListener('mousemove', draw);
        signatureCanvas.addEventListener('mouseup', stopDrawing);
        signatureCanvas.addEventListener('mouseout', stopDrawing);
        
        // Touch events
        signatureCanvas.addEventListener('touchstart', handleTouch);
        signatureCanvas.addEventListener('touchmove', handleTouch);
        signatureCanvas.addEventListener('touchend', stopDrawing);
    }
    
    function startDrawing(e) {
        isDrawing = true;
        const rect = signatureCanvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        signatureCtx.beginPath();
        signatureCtx.moveTo(x, y);
    }
    
    function draw(e) {
        if (!isDrawing) return;
        const rect = signatureCanvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        signatureCtx.lineTo(x, y);
        signatureCtx.stroke();
    }
    
    function stopDrawing() {
        isDrawing = false;
    }
    
    function handleTouch(e) {
        e.preventDefault();
        const touch = e.touches[0];
        const rect = signatureCanvas.getBoundingClientRect();
        const x = touch.clientX - rect.left;
        const y = touch.clientY - rect.top;
        
        if (e.type === 'touchstart') {
            isDrawing = true;
            signatureCtx.beginPath();
            signatureCtx.moveTo(x, y);
        } else if (isDrawing) {
            signatureCtx.lineTo(x, y);
            signatureCtx.stroke();
        }
    }
    
    function clearSignature() {
        if (signatureCtx) {
            signatureCtx.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
        }
        document.getElementById('signatureStatus').innerHTML = '';
    }
    
    function openSignatureModal(type = 'employee') {
        currentSignatureType = type;
        const modal = document.getElementById('signatureModal');
        modal.style.display = 'flex';
        
        // Initialize canvas after modal is visible
        setTimeout(() => {
            initSignatureCanvas();
            clearSignature();
        }, 100);
    }
    
    function closeSignatureModal() {
        document.getElementById('signatureModal').style.display = 'none';
        // Reset to draw tab when closing
        switchSignatureTab('draw');
        clearFileSelection();
    }
    
    // Tab switching for signature methods
    function switchSignatureTab(tab) {
        const drawSection = document.getElementById('drawSigSection');
        const uploadSection = document.getElementById('uploadSigSection');
        const tabDraw = document.getElementById('tabDrawSig');
        const tabUpload = document.getElementById('tabUploadSig');
        
        if (tab === 'draw') {
            drawSection.style.display = 'block';
            uploadSection.style.display = 'none';
            tabDraw.style.background = '#FFD700';
            tabDraw.style.color = '#000';
            tabDraw.style.border = 'none';
            tabUpload.style.background = '#333';
            tabUpload.style.color = '#fff';
            tabUpload.style.border = '1px solid #444';
        } else {
            drawSection.style.display = 'none';
            uploadSection.style.display = 'block';
            tabDraw.style.background = '#333';
            tabDraw.style.color = '#fff';
            tabDraw.style.border = '1px solid #444';
            tabUpload.style.background = '#FFD700';
            tabUpload.style.color = '#000';
            tabUpload.style.border = 'none';
        }
        document.getElementById('signatureStatus').innerHTML = '';
    }
    
    // File upload handling
    let selectedSignatureFile = null;
    
    function handleSignatureFileUpload(input) {
        const file = input.files[0];
        if (!file) return;
        
        // Validate file type
        const validTypes = ['image/png', 'image/jpeg', 'image/jpg'];
        if (!validTypes.includes(file.type)) {
            document.getElementById('signatureStatus').innerHTML = '<span style="color: #F44336;">Invalid file type. Please upload PNG or JPG image.</span>';
            return;
        }
        
        // Validate file size (max 2MB)
        if (file.size > 2 * 1024 * 1024) {
            document.getElementById('signatureStatus').innerHTML = '<span style="color: #F44336;">File too large. Maximum size is 2MB.</span>';
            return;
        }
        
        selectedSignatureFile = file;
        
        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('filePreview').src = e.target.result;
            document.getElementById('filePreviewContainer').style.display = 'block';
        };
        reader.readAsDataURL(file);
        
        document.getElementById('signatureStatus').innerHTML = '';
    }
    
    function clearFileSelection() {
        selectedSignatureFile = null;
        document.getElementById('signatureFileInput').value = '';
        document.getElementById('filePreviewContainer').style.display = 'none';
        document.getElementById('filePreview').src = '';
        document.getElementById('signatureStatus').innerHTML = '';
    }
    
    function uploadSignatureFile() {
        if (!selectedSignatureFile) {
            document.getElementById('signatureStatus').innerHTML = '<span style="color: #F44336;">Please select a file first</span>';
            return;
        }
        
        const formData = new FormData();
        formData.append('upload_signature_file', '1');
        formData.append('employee_id', currentEmployeeIdForAdd);
        formData.append('signature_type', currentSignatureType);
        formData.append('signature_file', selectedSignatureFile);
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('signatureStatus').innerHTML = '<span style="color: #4CAF50;">Signature uploaded successfully!</span>';
                setTimeout(() => {
                    closeSignatureModal();
                    reloadEmployeeHistory();
                }, 1000);
            } else {
                document.getElementById('signatureStatus').innerHTML = '<span style="color: #F44336;">Error: ' + (data.message || 'Failed to upload') + '</span>';
            }
        })
        .catch(error => {
            document.getElementById('signatureStatus').innerHTML = '<span style="color: #F44336;">Error uploading signature</span>';
        });
    }
    
    function uploadSignature() {
        if (!signatureCanvas) return;
        
        // Check if canvas is empty
        const imageData = signatureCtx.getImageData(0, 0, signatureCanvas.width, signatureCanvas.height);
        const isEmpty = !imageData.data.some(channel => channel !== 0);
        
        if (isEmpty) {
            document.getElementById('signatureStatus').innerHTML = '<span style="color: #F44336;">Please draw your signature first</span>';
            return;
        }
        
        const signatureData = signatureCanvas.toDataURL('image/png');
        
        fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'upload_signature=1&employee_id=' + currentEmployeeIdForAdd + '&signature_type=' + currentSignatureType + '&signature_data=' + encodeURIComponent(signatureData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('signatureStatus').innerHTML = '<span style="color: #4CAF50;">Signature saved successfully!</span>';
                setTimeout(() => {
                    closeSignatureModal();
                    // Reload to show signature in print view
                    reloadEmployeeHistory();
                }, 1000);
            } else {
                document.getElementById('signatureStatus').innerHTML = '<span style="color: #F44336;">Error: ' + (data.message || 'Failed to save') + '</span>';
            }
        })
        .catch(error => {
            document.getElementById('signatureStatus').innerHTML = '<span style="color: #F44336;">Error saving signature</span>';
        });
    }
    
    // Store current employee signature for printing
    let currentEmployeeSignature = null;
    
    // Load employee signature before showing history
    function loadEmployeeSignature(empId) {
        return fetch('?get_signature=1&employee_id=' + empId + '&signature_type=employee')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentEmployeeSignature = data.signature.signature_data;
                } else {
                    currentEmployeeSignature = null;
                }
                return data;
            })
            .catch(() => {
                currentEmployeeSignature = null;
            });
    }
    
    // Override viewEmployeeHistory to load signature first
    const originalViewEmployeeHistory = viewEmployeeHistory;
    viewEmployeeHistory = function(empId, empName) {
        currentEmployeeIdForAdd = empId;
        currentEmployeeNameForAdd = empName;
        
        // Load signature first, then load history
        loadEmployeeSignature(empId).then(() => {
            // Update signature button label
            updateSignatureButtonLabel();
            
            fetch('api/get_employee_ca.php?emp_id=' + empId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showEmployeeHistoryModal(data.employee, data.transactions);
                    } else {
                        alert('Error loading history');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading history');
                });
        });
    };
    
    // Update signature button label based on signature status
    function updateSignatureButtonLabel() {
        const btn = document.getElementById('signatureBtn');
        if (btn) {
            if (currentEmployeeSignature) {
                btn.innerHTML = '<i class="fas fa-signature mr-2"></i>Update Signature';
            } else {
                btn.innerHTML = '<i class="fas fa-signature mr-2"></i>Upload Signature';
            }
        }
    }
</script>
</body>
</html>
<?php
// Create cash_advances table if it doesn't exist
$tableCheck = mysqli_query($db, "SHOW TABLES LIKE 'cash_advances'");
if (mysqli_num_rows($tableCheck) == 0) {
    $createTable = "CREATE TABLE cash_advances (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        particular VARCHAR(50) DEFAULT 'Cash Advance',
        reason TEXT,
        status ENUM('Pending', 'Approved', 'Paid', 'Rejected') DEFAULT 'Pending',
        request_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        approved_date DATETIME NULL,
        paid_date DATETIME NULL,
        approved_by INT NULL,
        FOREIGN KEY (employee_id) REFERENCES employees(id)
    )";
    mysqli_query($db, $createTable);
}
?>
