<?php
// transfer_module.php - Staff Transfer Management Module
session_start();

// Include database connection
require_once __DIR__ . '/../conn/db_connection.php';

// Initialize variables
$message = '';
$messageType = '';
$employees = [];
$branches = [];
$transfers = [];
$editMode = false;
$editTransfer = null;

// Handle form submission for creating/updating transfer
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Add new transfer
        if ($_POST['action'] === 'add_transfer') {
            $employeeId = intval($_POST['employee_id'] ?? 0);
            $fromBranch = trim($_POST['from_branch'] ?? '');
            $toBranch = trim($_POST['to_branch'] ?? '');
            $transferDate = $_POST['transfer_date'] ?? date('Y-m-d');
            $status = $_POST['status'] ?? 'pending';
            
            if ($employeeId && $fromBranch && $toBranch) {
                $stmt = mysqli_prepare($db, "INSERT INTO employee_transfers (employee_id, from_branch, to_branch, transfer_date, status) VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "issss", $employeeId, $fromBranch, $toBranch, $transferDate, $status);
                
                if (mysqli_stmt_execute($stmt)) {
                    $message = 'Transfer created successfully!';
                    $messageType = 'success';
                    
                    // Log the activity
                    $userId = $_SESSION['user_id'] ?? 0;
                    $action = 'transfer_created';
                    $details = "Transfer created for employee ID $employeeId from $fromBranch to $toBranch";
                    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
                    
                    $logStmt = mysqli_prepare($db, "INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
                    mysqli_stmt_bind_param($logStmt, "isss", $userId, $action, $details, $ipAddress);
                    mysqli_stmt_execute($logStmt);
                    mysqli_stmt_close($logStmt);
                } else {
                    $message = 'Error creating transfer: ' . mysqli_error($db);
                    $messageType = 'danger';
                }
                mysqli_stmt_close($stmt);
            } else {
                $message = 'Please fill in all required fields.';
                $messageType = 'warning';
            }
        }
        
        // Update transfer status
        if ($_POST['action'] === 'update_status') {
            $transferId = intval($_POST['transfer_id'] ?? 0);
            $newStatus = $_POST['new_status'] ?? '';
            
            if ($transferId && $newStatus) {
                $stmt = mysqli_prepare($db, "UPDATE employee_transfers SET status = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "si", $newStatus, $transferId);
                
                if (mysqli_stmt_execute($stmt)) {
                    $message = 'Transfer status updated successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Error updating transfer: ' . mysqli_error($db);
                    $messageType = 'danger';
                }
                mysqli_stmt_close($stmt);
            }
        }
        
        // Delete transfer
        if ($_POST['action'] === 'delete_transfer') {
            $transferId = intval($_POST['transfer_id'] ?? 0);
            
            if ($transferId) {
                $stmt = mysqli_prepare($db, "DELETE FROM employee_transfers WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "i", $transferId);
                
                if (mysqli_stmt_execute($stmt)) {
                    $message = 'Transfer deleted successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Error deleting transfer: ' . mysqli_error($db);
                    $messageType = 'danger';
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// Fetch all employees for dropdown
$empQuery = "SELECT id, first_name, last_name FROM employees WHERE status = 'Active' ORDER BY last_name, first_name";
$empResult = mysqli_query($db, $empQuery);
if ($empResult) {
    while ($row = mysqli_fetch_assoc($empResult)) {
        $employees[] = $row;
    }
    mysqli_free_result($empResult);
}

// Fetch branches from branches table
$branchQuery = "SELECT id, branch_name FROM branches ORDER BY branch_name";
$branchResult = mysqli_query($db, $branchQuery);
if ($branchResult) {
    while ($row = mysqli_fetch_assoc($branchResult)) {
        $branches[] = $row['branch_name'];
    }
    mysqli_free_result($branchResult);
}

// Fetch all transfers with employee details
$transferQuery = "SELECT 
    et.id,
    et.employee_id,
    et.from_branch,
    et.to_branch,
    et.transfer_date,
    et.status,
    et.created_at,
    e.first_name,
    e.last_name
FROM employee_transfers et
LEFT JOIN employees e ON et.employee_id = e.id
ORDER BY et.created_at DESC";
$transferResult = mysqli_query($db, $transferQuery);
if ($transferResult) {
    while ($row = mysqli_fetch_assoc($transferResult)) {
        $transfers[] = $row;
    }
    mysqli_free_result($transferResult);
}

// Helper function to format date
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

// Helper function to get status badge class
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'completed':
            return 'bg-success';
        case 'pending':
            return 'bg-warning text-dark';
        case 'cancelled':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

mysqli_close($db);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Transfer Module</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/theme-variables.css">
    <link rel="stylesheet" href="css/light-theme.css">
    <script src="js/theme.js"></script>
    
    <style>
        /* Transfer Module - Dark Engineering Theme */
        body {
            background: var(--bg-page);
            color: var(--soft-white);
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial;
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
        
        .btn-gold {
            background: var(--accent);
            color: #111;
            padding: 0.6rem 1rem;
            border-radius: 10px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-gold:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(212,175,55,0.3);
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
        }
        
        .form-control, .form-select {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 8px;
            padding: 0.75rem;
            color: var(--soft-white);
            font-size: 0.95rem;
        }
        
        .form-control:focus, .form-select:focus {
            background: rgba(255,255,255,0.05);
            border-color: var(--gold-2);
            box-shadow: 0 0 0 0.2rem rgba(212,175,55,0.15);
            color: var(--soft-white);
        }
        
        .form-control::placeholder {
            color: rgba(255,255,255,0.4);
        }
        
        /* Buttons */
        .btn {
            border-radius: 8px;
            padding: 0.6rem 0.85rem;
            font-weight: 600;
            transition: all 0.2s;
            border: none;
            min-height: 44px;
        }
        
        .btn-primary {
            background: var(--accent);
            color: #111;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(212,175,55,0.3);
        }
        
        .btn-secondary {
            background: rgba(255,255,255,0.08);
            color: var(--soft-white);
        }
        
        .btn-secondary:hover {
            background: rgba(255,255,255,0.12);
        }
        
        .btn-danger {
            background: linear-gradient(180deg, #e06a6a, #c95555);
            color: white;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(224,106,106,0.3);
        }
        
        .btn-sm {
            padding: 0.4rem 0.6rem;
            font-size: 0.875rem;
        }
        
        /* Table Styling */
        .table-container {
            overflow-x: auto;
        }
        
        .custom-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .custom-table th {
            background: rgba(255,255,255,0.02);
            color: var(--gold-2);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 1rem;
            border-bottom: 2px solid rgba(212,175,55,0.1);
            text-align: left;
        }
        
        .custom-table td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid rgba(255,255,255,0.03);
            color: var(--soft-white);
        }
        
        .custom-table tr:hover {
            background: rgba(255,255,255,0.01);
        }
        
        /* Status Badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .bg-success {
            background: rgba(57,255,20,0.1) !important;
            color: var(--present-green) !important;
        }
        
        .bg-warning {
            background: rgba(255,214,107,0.15) !important;
            color: var(--gold-1) !important;
        }
        
        .bg-danger {
            background: rgba(224,106,106,0.15) !important;
            color: var(--absent-red) !important;
        }
        
        .bg-secondary {
            background: rgba(255,255,255,0.08) !important;
            color: var(--muted-white) !important;
        }
        
        /* Employee Name */
        .emp-name {
            font-weight: 600;
            color: var(--gold-1);
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
        
        .empty-state h5 {
            color: var(--soft-white);
            margin-bottom: 0.5rem;
        }
        
        /* Alert */
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
        
        .alert-info {
            background: rgba(54,185,204,0.1);
            color: #36b9cc;
            border: 1px solid rgba(54,185,204,0.2);
        }
        
        /* Modal */
        .modal-content {
            background: var(--card-bg);
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.05);
            box-shadow: 0 20px 60px rgba(0,0,0,0.8);
        }
        
        .modal-header {
            background: linear-gradient(135deg, rgba(255,214,107,0.1) 0%, rgba(212,175,55,0.05) 100%);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            border-radius: 12px 12px 0 0;
            padding: 1.25rem;
        }
        
        .modal-title {
            color: var(--soft-white);
            font-weight: 600;
        }
        
        .modal-title i {
            color: var(--gold-1);
        }
        
        .modal-header .btn-close {
            filter: invert(1);
            opacity: 0.6;
        }
        
        .modal-body {
            padding: 1.25rem;
            color: var(--soft-white);
        }
        
        .modal-footer {
            border-top: 1px solid rgba(255,255,255,0.05);
            padding: 1rem 1.25rem;
        }
        
        /* Stats Cards */
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
        
        .stat-card:hover {
            box-shadow: 0 20px 50px rgba(212,175,55,0.08);
            transform: translateY(-3px);
            transition: all 0.2s;
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
        
        .stat-icon.pending {
            background: rgba(255,214,107,0.1);
            color: var(--gold-1);
        }
        
        .stat-icon.completed {
            background: rgba(57,255,20,0.1);
            color: var(--present-green);
        }
        
        .stat-icon.total {
            background: rgba(255,255,255,0.05);
            color: var(--gold-2);
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
        
        /* Text colors */
        .text-muted {
            color: var(--muted-white) !important;
        }
        
        .text-primary {
            color: var(--gold-1) !important;
        }
        
        /* Row grid */
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: -0.5rem;
        }
        
        .col-md-4, .col-md-6, .col-md-12 {
            padding: 0.5rem;
        }
        
        .col-md-4 {
            flex: 0 0 33.333%;
            max-width: 33.333%;
        }
        
        .col-md-6 {
            flex: 0 0 50%;
            max-width: 50%;
        }
        
        @media (max-width: 768px) {
            .col-md-4, .col-md-6 {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }
        
        /* Utilities */
        .mb-3 {
            margin-bottom: 1rem !important;
        }
        
        .mb-4 {
            margin-bottom: 1.25rem !important;
        }
        
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
        
        .d-inline {
            display: inline !important;
        }
        
        .me-2 {
            margin-right: 0.5rem !important;
        }
        
        .me-3 {
            margin-right: 1rem !important;
        }
        
        .fade {
            transition: opacity 0.15s linear;
        }
    </style>
</head>
<body>
    <div class="app-shell">
        <!-- Include Sidebar -->
        <?php include 'sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1><i class="fas fa-exchange-alt me-3"></i>Staff Transfer Module</h1>
                        <p>Manage employee transfers between branches efficiently</p>
                    </div>
                    <button class="btn btn-gold" data-bs-toggle="modal" data-bs-target="#addTransferModal">
                        <i class="fas fa-plus me-2"></i>New Transfer
                    </button>
                </div>
            </div>
            
            <!-- Alert Messages -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Statistics Row -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon total">
                            <i class="fas fa-list"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?php echo count($transfers); ?></div>
                            <div class="stat-label">Total Transfers</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon pending">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?php echo count(array_filter($transfers, fn($t) => $t['status'] === 'pending')); ?></div>
                            <div class="stat-label">Pending Transfers</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon completed">
                            <i class="fas fa-check"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?php echo count(array_filter($transfers, fn($t) => $t['status'] === 'completed')); ?></div>
                            <div class="stat-label">Completed Transfers</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Transfers Table Card -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-history"></i>
                    Transfer History
                </div>
                <div class="card-body">
                    <?php if (!empty($transfers)): ?>
                        <div class="table-container">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Employee</th>
                                        <th>From Branch</th>
                                        <th>To Branch</th>
                                        <th>Transfer Date</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transfers as $transfer): 
                                        $employeeName = trim(($transfer['first_name'] ?? '') . ' ' . ($transfer['last_name'] ?? '')) ?: 'Unknown';
                                    ?>
                                        <tr>
                                            <td>#<?php echo $transfer['id']; ?></td>
                                            <td class="emp-name"><?php echo htmlspecialchars($employeeName); ?></td>
                                            <td><i class="fas fa-map-marker-alt text-muted me-1"></i><?php echo htmlspecialchars($transfer['from_branch']); ?></td>
                                            <td><i class="fas fa-arrow-right text-primary me-1"></i><?php echo htmlspecialchars($transfer['to_branch']); ?></td>
                                            <td><?php echo formatDate($transfer['transfer_date']); ?></td>
                                            <td>
                                                <span class="badge status-badge <?php echo getStatusBadgeClass($transfer['status']); ?>">
                                                    <?php echo ucfirst($transfer['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatDate($transfer['created_at']); ?></td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <!-- Status Update Dropdown -->
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="transfer_id" value="<?php echo $transfer['id']; ?>">
                                                        <select name="new_status" class="form-select form-select-sm" style="width: auto; display: inline-block;" onchange="this.form.submit()">
                                                            <option value="pending" <?php echo $transfer['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                            <option value="completed" <?php echo $transfer['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                            <option value="cancelled" <?php echo $transfer['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                        </select>
                                                    </form>
                                                    
                                                    <!-- Delete Button -->
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this transfer?');">
                                                        <input type="hidden" name="action" value="delete_transfer">
                                                        <input type="hidden" name="transfer_id" value="<?php echo $transfer['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-exchange-alt"></i>
                            <h5>No Transfers Found</h5>
                            <p>Start by creating a new staff transfer using the button above.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Transfer Modal -->
    <div class="modal fade" id="addTransferModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Create New Staff Transfer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_transfer">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-user me-2"></i>Select Employee</label>
                                <select name="employee_id" class="form-select" required>
                                    <option value="">Choose an employee...</option>
                                    <?php foreach ($employees as $emp): 
                                        $name = htmlspecialchars(trim($emp['first_name'] . ' ' . $emp['last_name']));
                                    ?>
                                        <option value="<?php echo $emp['id']; ?>">
                                            <?php echo $name; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-calendar me-2"></i>Transfer Date</label>
                                <input type="date" name="transfer_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-map-marker-alt me-2"></i>From Branch</label>
                                <select name="from_branch" class="form-select" required>
                                    <option value="">Select source branch...</option>
                                    <?php foreach ($branches as $branch): ?>
                                        <option value="<?php echo htmlspecialchars($branch); ?>">
                                            <?php echo htmlspecialchars($branch); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-arrow-right me-2"></i>To Branch</label>
                                <select name="to_branch" class="form-select" required>
                                    <option value="">Select destination branch...</option>
                                    <?php foreach ($branches as $branch): ?>
                                        <option value="<?php echo htmlspecialchars($branch); ?>">
                                            <?php echo htmlspecialchars($branch); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-tag me-2"></i>Status</label>
                            <select name="status" class="form-select">
                                <option value="pending" selected>Pending</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Note:</strong> The transfer will be recorded and can be updated later. Make sure to verify the branch assignments before completing.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Create Transfer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>
