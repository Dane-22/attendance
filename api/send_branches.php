<?php
// api/send_branches.php - Procurement Sync Interface
session_start();

// Include database connection
require_once __DIR__ . '/../conn/db_connection.php';

// Fetch all branches
$sql = "SELECT id, branch_name, branch_address, created_at, is_active FROM branches ORDER BY branch_name";
$result = mysqli_query($db, $sql);
$branches = [];

if ($result) {
    $branches = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_free_result($result);
}

mysqli_close($db);

// Helper function to format date
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

// Helper function to get status badge
function getStatusBadge($isActive) {
    if ($isActive == 1) {
        return '<span class="badge bg-success">Active</span>';
    }
    return '<span class="badge bg-secondary">Inactive</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sync to Procurement - Branch Data</title>
    
    <!-- FontAwesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <style>
        /* Sync to Procurement - Dark Engineering Theme */
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
        .badge {
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .bg-success {
            background: rgba(57,255,20,0.1) !important;
            color: var(--present-green) !important;
        }
        
        .bg-secondary {
            background: rgba(255,255,255,0.08) !important;
            color: var(--muted-white) !important;
        }
        
        /* Branch Name */
        .branch-name {
            font-weight: 600;
            color: var(--gold-1);
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
        
        .stat-icon.branches {
            background: rgba(255,214,107,0.1);
            color: var(--gold-1);
        }
        
        .stat-icon.active {
            background: rgba(57,255,20,0.1);
            color: var(--present-green);
        }
        
        .stat-icon.sync {
            background: rgba(54,185,204,0.1);
            color: #36b9cc;
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
        
        .alert-info {
            background: rgba(54,185,204,0.1);
            color: #36b9cc;
            border: 1px solid rgba(54,185,204,0.2);
        }
        
        /* JSON Output Section */
        .json-section {
            background: rgba(0,0,0,0.3);
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            border: 1px solid rgba(255,255,255,0.05);
        }
        
        .json-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        
        .json-title {
            font-weight: 600;
            color: var(--gold-2);
            font-size: 0.9rem;
        }
        
        .btn-copy {
            background: rgba(255,255,255,0.08);
            color: var(--soft-white);
            border: none;
            padding: 0.4rem 0.75rem;
            border-radius: 6px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-copy:hover {
            background: rgba(255,255,255,0.12);
        }
        
        .json-content {
            background: rgba(0,0,0,0.4);
            border-radius: 6px;
            padding: 1rem;
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
            font-size: 0.85rem;
            color: var(--soft-white);
            max-height: 300px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-break: break-word;
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
        <?php include __DIR__ . '/../employee/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1><i class="fas fa-sync-alt"></i>Sync to Procurement</h1>
                        <p>View and sync branch data for procurement system</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="../employee/dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                        <button class="btn btn-gold" onclick="refreshData()">
                            <i class="fas fa-sync-alt me-2"></i>Refresh
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Info Alert -->
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>API Endpoint:</strong> This data is available as JSON at <code>send_branches.php</code> for system integration.
            </div>
            
            <!-- Statistics Row -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-icon branches">
                        <i class="fas fa-building"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?php echo count($branches); ?></div>
                        <div class="stat-label">Total Branches</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon active">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?php echo count(array_filter($branches, fn($b) => $b['is_active'] == 1)); ?></div>
                        <div class="stat-label">Active Branches</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon sync">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div>
                        <div class="stat-value">Ready</div>
                        <div class="stat-label">Sync Status</div>
                    </div>
                </div>
            </div>
            
            <!-- Branches Table Card -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-list"></i>
                    Branch Data for Procurement
                </div>
                <div class="card-body">
                    <?php if (!empty($branches)): ?>
                        <div class="table-container">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Branch Name</th>
                                        <th>Address</th>
                                        <th>Created</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($branches as $branch): ?>
                                        <tr>
                                            <td>#<?php echo $branch['id']; ?></td>
                                            <td class="branch-name"><?php echo htmlspecialchars($branch['branch_name']); ?></td>
                                            <td>
                                                <?php if ($branch['branch_address']): ?>
                                                    <i class="fas fa-map-marker-alt text-muted me-2"></i><?php echo htmlspecialchars($branch['branch_address']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted"><i class="fas fa-minus me-2"></i>No address</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo formatDate($branch['created_at']); ?></td>
                                            <td><?php echo getStatusBadge($branch['is_active']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- JSON Output Section -->
                        <div class="json-section">
                            <div class="json-header">
                                <span class="json-title"><i class="fas fa-code me-2"></i>JSON Output (for API Integration)</span>
                                <button class="btn-copy" onclick="copyJSON()">
                                    <i class="fas fa-copy me-1"></i>Copy JSON
                                </button>
                            </div>
                            <div class="json-content" id="jsonOutput"><?php echo json_encode($branches, JSON_PRETTY_PRINT); ?></div>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-building"></i>
                            <h5>No Branches Found</h5>
                            <p>No branch data available in the database.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function refreshData() {
            location.reload();
        }
        
        function copyJSON() {
            const jsonText = document.getElementById('jsonOutput').textContent;
            navigator.clipboard.writeText(jsonText).then(() => {
                const btn = document.querySelector('.btn-copy');
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check me-1"></i>Copied!';
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                }, 2000);
            });
        }
    </script>
</body>
</html>
