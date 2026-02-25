<?php
/**
 * API Key Management Dashboard
 * 
 * Admin interface for managing API keys
 * Access: Admin and Super Admin only
 */

session_start();
require_once __DIR__ . '/../conn/db_connection.php';
require_once __DIR__ . '/../include/api_key_manager.php';
require_once __DIR__ . '/../include/security_headers.php';

// Check admin access
$userRole = $_SESSION['position'] ?? '';
if (!in_array($userRole, ['Admin', 'Super Admin'])) {
    header('Location: ../login.php');
    exit;
}

$currentUserId = $_SESSION['employee_id'] ?? null;
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'generate':
                $apiName = trim($_POST['api_name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $endpoints = $_POST['endpoints'] ?? [];
                $expiresInDays = !empty($_POST['expires_in_days']) ? (int)$_POST['expires_in_days'] : null;
                
                if (empty($apiName)) {
                    $error = 'API name is required';
                } else {
                    $result = storeApiKey($db, $apiName, $description, $endpoints, $currentUserId, $expiresInDays);
                    if ($result['success']) {
                        $message = "API key generated successfully: {$result['api_key']}";
                    } else {
                        $error = $result['message'];
                    }
                }
                break;
                
            case 'revoke':
                $keyId = (int)($_POST['key_id'] ?? 0);
                if ($keyId > 0 && revokeApiKey($db, $keyId)) {
                    $message = 'API key revoked successfully';
                } else {
                    $error = 'Failed to revoke API key';
                }
                break;
                
            case 'delete':
                $keyId = (int)($_POST['key_id'] ?? 0);
                if ($keyId > 0 && deleteApiKey($db, $keyId)) {
                    $message = 'API key deleted permanently';
                } else {
                    $error = 'Failed to delete API key';
                }
                break;
                
            case 'auto_generate':
                $generated = autoGenerateSystemApiKeys($db, $currentUserId);
                if (!empty($generated)) {
                    $message = 'Auto-generated ' . count($generated) . ' API keys for system endpoints';
                } else {
                    $message = 'No new keys generated (keys may already exist)';
                }
                break;
        }
    }
}

// Get all API keys
$apiKeys = getAllApiKeys($db, false);

// Get available endpoints for the form
$availableEndpoints = [
    'login_api' => 'Login API',
    'time_in_api' => 'Clock In API',
    'time_out_api' => 'Clock Out API',
    'clock_out_api' => 'Clock Out API 2',
    'qr_clock_api' => 'QR Clock API',
    'submit_attendance_api' => 'Submit Attendance API',
    'get_branches_api' => 'Get Branches API',
    'get_branch_api' => 'Get Branch API',
    'employees_today_status_api' => 'Employees Status API',
    'get_available_employees_api' => 'Available Employees API',
    'get_shift_logs_api' => 'Shift Logs API',
    'mark_attendance_absent_api' => 'Mark Absent API',
    'get_attendance_absent_notes_api' => 'Absent Notes API',
    'set_attendance_ot_hrs_api' => 'Set OT Hours API',
    'transfer_branch_api' => 'Transfer Branch API',
    'set_employee_branch_api' => 'Set Employee Branch API',
    'update_profile_api' => 'Update Profile API',
    'change-password-api' => 'Change Password API',
    '*' => 'All Endpoints (Master Key)'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Key Management - JAJR Attendance System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #e0e0e0;
            min-height: 100vh;
            padding: 2rem;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        h1 {
            color: #FFD700;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .subtitle { color: #888; margin-bottom: 2rem; }
        .grid { display: grid; grid-template-columns: 400px 1fr; gap: 2rem; }
        
        /* Card styles */
        .card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 215, 0, 0.2);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .card h2 {
            color: #FFD700;
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }
        
        /* Form styles */
        .form-group { margin-bottom: 1rem; }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #aaa;
            font-size: 0.875rem;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 8px;
            color: #fff;
            font-size: 0.875rem;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #FFD700;
        }
        .checkbox-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
        }
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
        }
        .checkbox-item input[type="checkbox"] {
            width: auto;
        }
        
        /* Button styles */
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #0b0b0b;
        }
        .btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: #fff;
        }
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.3); }
        
        /* Alert styles */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .alert-success { background: rgba(40, 167, 69, 0.2); border: 1px solid #28a745; color: #28a745; }
        .alert-error { background: rgba(220, 53, 69, 0.2); border: 1px solid #dc3545; color: #dc3545; }
        
        /* Table styles */
        .table-container { overflow-x: auto; }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid rgba(255, 215, 0, 0.1);
        }
        th {
            color: #FFD700;
            font-weight: 600;
            background: rgba(0, 0, 0, 0.2);
        }
        .key-preview {
            font-family: 'Courier New', monospace;
            background: rgba(0, 0, 0, 0.3);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
        }
        .status-active { color: #28a745; }
        .status-inactive { color: #dc3545; }
        .actions { display: flex; gap: 0.5rem; }
        .actions form { display: inline; }
        .btn-sm { padding: 0.5rem 0.75rem; font-size: 0.75rem; }
        
        /* API key display */
        .api-key-display {
            background: rgba(0, 0, 0, 0.4);
            border: 2px dashed #FFD700;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
            font-family: 'Courier New', monospace;
            word-break: break-all;
            color: #FFD700;
        }
        .copy-btn {
            background: rgba(255, 215, 0, 0.2);
            border: 1px solid #FFD700;
            color: #FFD700;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 0.5rem;
        }
        
        @media (max-width: 1024px) {
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 API Key Management</h1>
        <p class="subtitle">Manage API keys for external integrations and mobile apps</p>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="grid">
            <!-- Left Column: Generate New Key -->
            <div>
                <div class="card">
                    <h2>Generate New API Key</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="generate">
                        
                        <div class="form-group">
                            <label>API Name *</label>
                            <input type="text" name="api_name" required placeholder="e.g., Mobile App Production">
                        </div>
                        
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" rows="2" placeholder="Optional description"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Allowed Endpoints</label>
                            <div class="checkbox-grid">
                                <?php foreach ($availableEndpoints as $value => $label): ?>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="endpoints[]" value="<?php echo htmlspecialchars($value); ?>" id="ep_<?php echo htmlspecialchars($value); ?>">
                                    <label for="ep_<?php echo htmlspecialchars($value); ?>"><?php echo htmlspecialchars($label); ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Expires In (days, leave empty for no expiry)</label>
                            <input type="number" name="expires_in_days" min="1" placeholder="e.g., 365">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Generate API Key</button>
                    </form>
                </div>
                
                <div class="card">
                    <h2>Quick Setup</h2>
                    <p style="margin-bottom: 1rem; color: #888;">Auto-generate API keys for all system endpoints</p>
                    <form method="POST">
                        <input type="hidden" name="action" value="auto_generate">
                        <button type="submit" class="btn btn-secondary">Auto-Generate System Keys</button>
                    </form>
                </div>
            </div>
            
            <!-- Right Column: List All Keys -->
            <div>
                <div class="card">
                    <h2>All API Keys (<?php echo count($apiKeys); ?>)</h2>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>API Name</th>
                                    <th>API Key</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Last Used</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($apiKeys as $key): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($key['api_name']); ?></strong>
                                        <?php if ($key['description']): ?>
                                            <br><small style="color: #888;"><?php echo htmlspecialchars($key['description']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="key-preview"><?php echo substr(htmlspecialchars($key['api_key']), 0, 20); ?>...</span>
                                    </td>
                                    <td class="<?php echo $key['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $key['is_active'] ? 'Active' : 'Revoked'; ?>
                                        <?php if ($key['expires_at'] && strtotime($key['expires_at']) < time()): ?>
                                            <br><small style="color: #dc3545;">Expired</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('Y-m-d', strtotime($key['created_at'])); ?></td>
                                    <td><?php echo $key['last_used_at'] ? date('Y-m-d H:i', strtotime($key['last_used_at'])) : 'Never'; ?></td>
                                    <td>
                                        <div class="actions">
                                            <?php if ($key['is_active']): ?>
                                            <form method="POST" onsubmit="return confirm('Revoke this API key?');">
                                                <input type="hidden" name="action" value="revoke">
                                                <input type="hidden" name="key_id" value="<?php echo $key['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Revoke</button>
                                            </form>
                                            <?php else: ?>
                                            <form method="POST" onsubmit="return confirm('Permanently delete this API key?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="key_id" value="<?php echo $key['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="card">
                    <h2>📖 API Usage Instructions</h2>
                    <p style="margin-bottom: 1rem;">Include your API key in requests using one of these methods:</p>
                    
                    <h3 style="color: #FFD700; margin: 1rem 0 0.5rem;">1. Header (Recommended)</h3>
                    <pre style="background: rgba(0,0,0,0.3); padding: 1rem; border-radius: 8px; overflow-x: auto;">X-API-Key: your_api_key_here</pre>
                    
                    <h3 style="color: #FFD700; margin: 1rem 0 0.5rem;">2. Bearer Token</h3>
                    <pre style="background: rgba(0,0,0,0.3); padding: 1rem; border-radius: 8px; overflow-x: auto;">Authorization: Bearer your_api_key_here</pre>
                    
                    <h3 style="color: #FFD700; margin: 1rem 0 0.5rem;">3. POST/GET Parameter</h3>
                    <pre style="background: rgba(0,0,0,0.3); padding: 1rem; border-radius: 8px; overflow-x: auto;">api_key=your_api_key_here</pre>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('API key copied to clipboard!');
            });
        }
    </script>
</body>
</html>
