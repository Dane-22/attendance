<?php
/**
 * MONITORING DASHBOARD - VERIFICATION & TEST SCRIPT
 * ==================================================
 * 
 * This script verifies that all required components are in place
 * and the monitoring dashboard will work correctly.
 * 
 * Access this file at: /employee/test_monitoring_dashboard.php
 */

session_start();

// Check if user is logged in
$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

// Try to establish database connection
$db_connected = false;
$db = null;
try {
    require_once __DIR__ . '/../conn/db_connection.php';
    $db_connected = is_object($db);
} catch (Exception $e) {
    $db = null;
}

// Check for required files
$files_check = [
    'monitoring_dashboard.php' => file_exists(__DIR__ . '/monitoring_dashboard.php'),
    'monitoring_dashboard_component.php' => file_exists(__DIR__ . '/monitoring_dashboard_component.php'),
    'MONITORING_DASHBOARD_GUIDE.php' => file_exists(__DIR__ . '/MONITORING_DASHBOARD_GUIDE.php'),
    'db_connection.php' => file_exists(__DIR__ . '/../conn/db_connection.php'),
];

// Check database tables if connected
$tables_check = [];
if ($db_connected && $db) {
    $tables = ['employees', 'attendance'];
    foreach ($tables as $table) {
        $result = mysqli_query($db, "SHOW TABLES LIKE '$table'");
        $tables_check[$table] = mysqli_num_rows($result) > 0;
    }
    
    // Check columns if tables exist
    $columns_check = [];
    if ($tables_check['employees'] ?? false) {
        $result = mysqli_query($db, "DESCRIBE employees");
        $columns = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $columns[] = $row['Field'];
        }
        $columns_check['employees'] = [
            'id' => in_array('id', $columns),
            'first_name' => in_array('first_name', $columns),
            'last_name' => in_array('last_name', $columns),
            'status' => in_array('status', $columns),
            'branch_name' => in_array('branch_name', $columns),
        ];
    }
    
    if ($tables_check['attendance'] ?? false) {
        $result = mysqli_query($db, "DESCRIBE attendance");
        $columns = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $columns[] = $row['Field'];
        }
        $columns_check['attendance'] = [
            'id' => in_array('id', $columns),
            'employee_id' => in_array('employee_id', $columns),
            'status' => in_array('status', $columns),
            'branch_name' => in_array('branch_name', $columns),
            'attendance_date' => in_array('attendance_date', $columns),
            'created_at' => in_array('created_at', $columns),
        ];
    }
}

// Get data counts if everything is working
$data_check = [];
if ($db_connected && $db && ($tables_check['employees'] ?? false) && ($tables_check['attendance'] ?? false)) {
    $result = mysqli_query($db, "SELECT COUNT(*) as count FROM employees WHERE status = 'Active'");
    $data_check['total_active_employees'] = mysqli_fetch_assoc($result)['count'] ?? 0;
    
    $result = mysqli_query($db, "SELECT COUNT(*) as count FROM attendance WHERE attendance_date = CURDATE() AND status = 'Present'");
    $data_check['present_today'] = mysqli_fetch_assoc($result)['count'] ?? 0;
    
    $result = mysqli_query($db, "SELECT COUNT(*) as count FROM attendance WHERE attendance_date = CURDATE() AND status = 'Absent'");
    $data_check['absent_today'] = mysqli_fetch_assoc($result)['count'] ?? 0;
    
    $result = mysqli_query($db, "SELECT COUNT(*) as count FROM branches WHERE is_active = 1");
    $data_check['total_branches'] = mysqli_fetch_assoc($result)['count'] ?? 0;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Dashboard - Verification Test</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #0a0a0a 0%, #0f0f0f 100%);
            color: #ffffff;
            font-family: 'Inter', system-ui, sans-serif;
            padding: 2rem;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .header {
            margin-bottom: 2rem;
            border-bottom: 2px solid #d4af37;
            padding-bottom: 1rem;
        }
        
        .header h1 {
            color: #d4af37;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            color: #a0a0a0;
        }
        
        .section {
            background: rgba(26, 26, 26, 0.8);
            border: 1px solid rgba(212, 175, 55, 0.15);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .section-title {
            color: #d4af37;
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .check-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            border-left: 3px solid transparent;
            border-radius: 4px;
        }
        
        .check-item.pass {
            background: rgba(16, 185, 129, 0.1);
            border-left-color: #10b981;
        }
        
        .check-item.fail {
            background: rgba(239, 68, 68, 0.1);
            border-left-color: #ef4444;
        }
        
        .check-item.warning {
            background: rgba(245, 158, 11, 0.1);
            border-left-color: #f59e0b;
        }
        
        .status-icon {
            font-size: 1.5rem;
            min-width: 2rem;
            text-align: center;
        }
        
        .check-item.pass .status-icon {
            color: #10b981;
        }
        
        .check-item.fail .status-icon {
            color: #ef4444;
        }
        
        .check-item.warning .status-icon {
            color: #f59e0b;
        }
        
        .check-content {
            flex: 1;
        }
        
        .check-label {
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 0.25rem;
        }
        
        .check-details {
            font-size: 0.85rem;
            color: #a0a0a0;
        }
        
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .summary-item {
            background: rgba(212, 175, 55, 0.1);
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
        }
        
        .summary-label {
            color: #a0a0a0;
            font-size: 0.85rem;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }
        
        .summary-value {
            color: #d4af37;
            font-size: 2rem;
            font-weight: 700;
        }
        
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #d4af37, #FFD700);
            color: #0a0a0a;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            border: none;
            cursor: pointer;
            margin-right: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(212, 175, 55, 0.2);
        }
        
        .status-pass {
            color: #10b981;
        }
        
        .status-fail {
            color: #ef4444;
        }
        
        .status-warning {
            color: #f59e0b;
        }
    </style>
</head>
<body>
    <div class="container">
        
        <div class="header">
            <h1>🔍 Monitoring Dashboard Verification</h1>
            <p>This page verifies that all components are correctly installed and configured.</p>
        </div>
        
        <!-- ===== SESSION CHECK ===== -->
        <div class="section">
            <div class="section-title">👤 Session Status</div>
            <div class="check-item <?php echo $is_logged_in ? 'pass' : 'warning'; ?>">
                <div class="status-icon">
                    <?php echo $is_logged_in ? '✓' : '⚠'; ?>
                </div>
                <div class="check-content">
                    <div class="check-label">User Session</div>
                    <div class="check-details">
                        <?php echo $is_logged_in ? 'Session active' : 'Not logged in (test access only - this is expected for testing)'; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ===== DATABASE CONNECTION ===== -->
        <div class="section">
            <div class="section-title">🗄️ Database Connection</div>
            <div class="check-item <?php echo $db_connected ? 'pass' : 'fail'; ?>">
                <div class="status-icon">
                    <?php echo $db_connected ? '✓' : '✗'; ?>
                </div>
                <div class="check-content">
                    <div class="check-label">Database Connected</div>
                    <div class="check-details">
                        <?php echo $db_connected ? 'Connected to attendance_db' : 'Failed to connect to database'; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ===== FILES CHECK ===== -->
        <div class="section">
            <div class="section-title">📁 Required Files</div>
            <?php foreach ($files_check as $file => $exists): ?>
                <div class="check-item <?php echo $exists ? 'pass' : 'fail'; ?>">
                    <div class="status-icon">
                        <?php echo $exists ? '✓' : '✗'; ?>
                    </div>
                    <div class="check-content">
                        <div class="check-label"><?php echo $file; ?></div>
                        <div class="check-details">
                            <?php echo $exists ? 'Found in /employee/ directory' : 'Missing from /employee/ directory'; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- ===== TABLES CHECK ===== -->
        <?php if ($db_connected && $db): ?>
        <div class="section">
            <div class="section-title">📊 Database Tables</div>
            <?php foreach ($tables_check as $table => $exists): ?>
                <div class="check-item <?php echo $exists ? 'pass' : 'fail'; ?>">
                    <div class="status-icon">
                        <?php echo $exists ? '✓' : '✗'; ?>
                    </div>
                    <div class="check-content">
                        <div class="check-label">Table: <?php echo $table; ?></div>
                        <div class="check-details">
                            <?php echo $exists ? 'Table exists in database' : 'Table not found in database'; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- ===== COLUMNS CHECK ===== -->
        <?php if (isset($columns_check)): ?>
        <div class="section">
            <div class="section-title">🔗 Database Columns</div>
            
            <?php if (isset($columns_check['employees'])): ?>
                <div style="margin-bottom: 1.5rem;">
                    <h3 style="color: #d4af37; margin-bottom: 0.5rem;">✓ Employees Table</h3>
                    <?php foreach ($columns_check['employees'] as $col => $exists): ?>
                        <div class="check-item <?php echo $exists ? 'pass' : 'fail'; ?>" style="margin-bottom: 0.25rem;">
                            <div class="status-icon" style="font-size: 1rem;">
                                <?php echo $exists ? '✓' : '✗'; ?>
                            </div>
                            <div class="check-content">
                                <div class="check-label" style="margin-bottom: 0;">employees.<?php echo $col; ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($columns_check['attendance'])): ?>
                <div>
                    <h3 style="color: #d4af37; margin-bottom: 0.5rem;">✓ Attendance Table</h3>
                    <?php foreach ($columns_check['attendance'] as $col => $exists): ?>
                        <div class="check-item <?php echo $exists ? 'pass' : 'fail'; ?>" style="margin-bottom: 0.25rem;">
                            <div class="status-icon" style="font-size: 1rem;">
                                <?php echo $exists ? '✓' : '✗'; ?>
                            </div>
                            <div class="check-content">
                                <div class="check-label" style="margin-bottom: 0;">attendance.<?php echo $col; ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- ===== DATA SAMPLE ===== -->
        <?php if (isset($data_check) && !empty($data_check)): ?>
        <div class="section">
            <div class="section-title">📈 Current Data</div>
            <div class="summary">
                <div class="summary-item">
                    <div class="summary-label">Active Employees</div>
                    <div class="summary-value"><?php echo $data_check['total_active_employees'] ?? 0; ?></div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Present Today</div>
                    <div class="summary-value"><?php echo $data_check['present_today'] ?? 0; ?></div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Absent Today</div>
                    <div class="summary-value"><?php echo $data_check['absent_today'] ?? 0; ?></div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Total Branches</div>
                    <div class="summary-value"><?php echo $data_check['total_branches'] ?? 0; ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php endif; ?>
        
        <!-- ===== SUMMARY & NEXT STEPS ===== -->
        <div class="section">
            <div class="section-title">📋 Summary & Next Steps</div>
            
            <?php 
            $all_files_exist = array_reduce($files_check, fn($carry, $item) => $carry && $item, true);
            $db_ready = $db_connected && ($tables_check['employees'] ?? false) && ($tables_check['attendance'] ?? false);
            $everything_good = $all_files_exist && $db_ready;
            ?>
            
            <?php if ($everything_good): ?>
                <div class="check-item pass" style="margin-bottom: 1rem;">
                    <div class="status-icon">✓</div>
                    <div class="check-content">
                        <div class="check-label">Everything is Ready!</div>
                        <div class="check-details">All components are installed and configured correctly.</div>
                    </div>
                </div>
                
                <h3 style="color: #d4af37; margin: 1rem 0 0.5rem 0;">Next Steps:</h3>
                <ol style="margin-left: 2rem; color: #a0a0a0;">
                    <li>Open your <code style="color: #FFD700;">dashboard.php</code> file</li>
                    <li>Add this include statement in the main content area:
                        <pre style="background: rgba(212, 175, 55, 0.1); padding: 0.75rem; border-radius: 4px; margin: 0.5rem 0; color: #FFD700; overflow-x: auto;">
&lt;?php include __DIR__ . '/monitoring_dashboard_component.php'; ?&gt;</pre>
                    </li>
                    <li>Access the standalone version at: <a href="monitoring_dashboard.php" style="color: #FFD700; text-decoration: underline;">monitoring_dashboard.php</a></li>
                    <li>Or see detailed instructions in <a href="MONITORING_DASHBOARD_GUIDE.php" style="color: #FFD700; text-decoration: underline;">MONITORING_DASHBOARD_GUIDE.php</a></li>
                </ol>
            <?php else: ?>
                <div class="check-item fail" style="margin-bottom: 1rem;">
                    <div class="status-icon">✗</div>
                    <div class="check-content">
                        <div class="check-label">Setup Issues Detected</div>
                        <div class="check-details">Please address the issues marked with ✗ above.</div>
                    </div>
                </div>
                
                <h3 style="color: #f59e0b; margin: 1rem 0 0.5rem 0;">Issues to Fix:</h3>
                <ul style="margin-left: 2rem; color: #a0a0a0;">
                    <?php if (!$all_files_exist): ?>
                        <li>Some required files are missing from /employee/ directory</li>
                    <?php endif; ?>
                    <?php if (!$db_connected): ?>
                        <li>Database connection failed - check db_connection.php</li>
                    <?php endif; ?>
                    <?php if ($db_connected && !($tables_check['employees'] ?? false)): ?>
                        <li>employees table not found in database</li>
                    <?php endif; ?>
                    <?php if ($db_connected && !($tables_check['attendance'] ?? false)): ?>
                        <li>attendance table not found in database</li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        </div>
        
        <!-- ===== ACTION BUTTONS ===== -->
        <div class="section">
            <div class="section-title">🚀 Quick Actions</div>
            <a href="monitoring_dashboard.php" class="button">View Standalone Dashboard</a>
            <a href="MONITORING_DASHBOARD_GUIDE.php" class="button">Read Full Guide</a>
            <a href="QUICK_SETUP.php" class="button">Quick Setup</a>
            <a href="test_monitoring_dashboard.php" class="button" style="background: rgba(212, 175, 55, 0.2); color: #d4af37;">Refresh Test</a>
        </div>
        
    </div>
</body>
</html>
