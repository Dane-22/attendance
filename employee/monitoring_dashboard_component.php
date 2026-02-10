<?php
/**
 * MONITORING DASHBOARD COMPONENT
 * File: employee/monitoring_dashboard_component.php
 * 
 * This is a modular component meant to be included within dashboard.php
 * It requires:
 * - Active database connection ($db)
 * - Session started
 * - CSS & JS already loaded in parent document
 * 
 * USAGE IN dashboard.php:
 * <?php include __DIR__ . '/monitoring_dashboard_component.php'; ?>
 * 
 * Place this include where you want the monitoring dashboard to appear
 */

// Verify prerequisites
if (!isset($db)) {
    die('Error: Database connection not found. Please ensure $db is initialized.');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$today = date('Y-m-d');

// ============================================================================
// REAL-TIME SQL LOGIC - All Data Queries
// ============================================================================

// 1. Total Active Employees
$totalEmployeesQuery = "SELECT COUNT(*) as count FROM employees WHERE status = 'Active'";
$totalEmployeesResult = mysqli_query($db, $totalEmployeesQuery);
$totalEmployees = mysqli_fetch_assoc($totalEmployeesResult)['count'] ?? 0;

// 2. Present Today
$presentTodayQuery = "SELECT COUNT(*) as count FROM attendance WHERE attendance_date = CURDATE() AND status = 'Present'";
$presentTodayResult = mysqli_query($db, $presentTodayQuery);
$presentToday = mysqli_fetch_assoc($presentTodayResult)['count'] ?? 0;

// 3. Absent Today (Computed)
$absentTodayQuery = "SELECT COUNT(*) as count FROM attendance WHERE attendance_date = CURDATE() AND status = 'Absent'";
$absentTodayResult = mysqli_query($db, $absentTodayQuery);
$absentToday = mysqli_fetch_assoc($absentTodayResult)['count'] ?? 0;

// 4. Today's Deployment by Branch
$deploymentQuery = "SELECT branch_name, COUNT(*) as total FROM attendance 
                    WHERE attendance_date = CURDATE() AND status = 'Present' 
                    GROUP BY branch_name ORDER BY total DESC";
$deploymentResult = mysqli_query($db, $deploymentQuery);
$deploymentData = [];
$activeBranches = 0;
while ($row = mysqli_fetch_assoc($deploymentResult)) {
    $deploymentData[] = $row;
    $activeBranches++;
}

// 5. All Branches with Today's Headcount
$allBranchesQuery = "SELECT branch_name FROM branches WHERE is_active = 1 ORDER BY branch_name ASC";
$allBranchesResult = mysqli_query($db, $allBranchesQuery);
$branchHeadcount = [];

while ($branch = mysqli_fetch_assoc($allBranchesResult)) {
    $branchName = $branch['branch_name'];
    
    // Get present count for this branch today
    $headcountQuery = "SELECT COUNT(*) as count FROM attendance 
                      WHERE attendance_date = CURDATE() AND status = 'Present' AND branch_name = ?";
    $headcountStmt = mysqli_prepare($db, $headcountQuery);
    mysqli_stmt_bind_param($headcountStmt, 's', $branchName);
    mysqli_stmt_execute($headcountStmt);
    $headcountRes = mysqli_stmt_get_result($headcountStmt);
    $headcountRow = mysqli_fetch_assoc($headcountRes);
    
    // Get total employees in this branch
    $totalBranchQuery = "SELECT COUNT(*) as count
                        FROM employees e
                        JOIN branches b ON e.branch_id = b.id
                        WHERE e.status = 'Active' AND b.branch_name = ?";
    $totalBranchStmt = mysqli_prepare($db, $totalBranchQuery);
    mysqli_stmt_bind_param($totalBranchStmt, 's', $branchName);
    mysqli_stmt_execute($totalBranchStmt);
    $totalBranchRes = mysqli_stmt_get_result($totalBranchStmt);
    $totalBranchRow = mysqli_fetch_assoc($totalBranchRes);
    
    $presentCount = $headcountRow['count'] ?? 0;
    $totalCount = $totalBranchRow['count'] ?? 0;
    $percentage = $totalCount > 0 ? round(($presentCount / $totalCount) * 100) : 0;
    
    $branchHeadcount[] = [
        'branch_name' => $branchName,
        'present_today' => $presentCount,
        'total_employees' => $totalCount,
        'percentage' => $percentage
    ];
}

// 6. Recent Activity - Last 5 Attendance Records with Employee Names
$recentActivityQuery = "SELECT a.id, a.created_at, a.status, a.branch_name, 
                               e.first_name, e.last_name 
                        FROM attendance a
                        JOIN employees e ON a.employee_id = e.id
                        ORDER BY a.created_at DESC
                        LIMIT 5";
$recentActivityResult = mysqli_query($db, $recentActivityQuery);
$recentActivity = [];
while ($row = mysqli_fetch_assoc($recentActivityResult)) {
    $recentActivity[] = $row;
}

?>

<!-- ====================================================================== -->
<!-- MONITORING DASHBOARD HTML & CSS -->
<!-- ====================================================================== -->

<style>
    /* ===== CSS VARIABLES & BASE STYLES ===== */
    .monitoring-dashboard {
        --gold: #d4af37;
        --gold-light: #FFD700;
        --black: #0a0a0a;
        --dark-gray: #1a1a1a;
        --medium-gray: #2d2d2d;
        --light-gray: #3a3a3a;
    }

    .monitoring-dashboard * {
        box-sizing: border-box;
    }

    .monitoring-dashboard {
        width: 100%;
    }

    /* ===== SUMMARY CARDS WITH GLASSMORPHISM ===== */
    .md-summary-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2.5rem;
    }

    .md-glass-card {
        background: rgba(26, 26, 26, 0.7);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(212, 175, 55, 0.15);
        border-radius: 16px;
        padding: 1.5rem;
        transition: all 0.3s cubic-bezier(0.23, 1, 0.320, 1);
        position: relative;
        overflow: hidden;
    }

    .md-glass-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(212, 175, 55, 0.05), transparent);
        transition: left 0.5s ease;
        pointer-events: none;
    }

    .md-glass-card:hover {
        border-color: rgba(212, 175, 55, 0.35);
        background: rgba(26, 26, 26, 0.85);
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(212, 175, 55, 0.1);
    }

    .md-glass-card:hover::before {
        left: 100%;
    }

    .md-card-icon {
        font-size: 2.5rem;
        color: var(--gold);
        margin-bottom: 0.75rem;
        display: inline-block;
    }

    .md-card-label {
        font-size: 0.875rem;
        color: #a0a0a0;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .md-card-value {
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--gold);
        line-height: 1;
        margin-bottom: 0.5rem;
    }

    .md-card-subtitle {
        font-size: 0.75rem;
        color: #7a7a7a;
    }

    /* ===== SECTION TITLES ===== */
    .md-section-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #ffffff;
        margin-bottom: 1rem;
        margin-top: 2rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .md-section-title i {
        color: var(--gold);
        font-size: 1.75rem;
    }

    /* ===== HEADCOUNT TABLE ===== */
    .md-branch-table-container {
        background: rgba(26, 26, 26, 0.8);
        border: 1px solid rgba(212, 175, 55, 0.15);
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 2.5rem;
    }

    .md-branch-table {
        width: 100%;
        border-collapse: collapse;
    }

    .md-branch-table thead {
        background: linear-gradient(90deg, rgba(212, 175, 55, 0.1), rgba(212, 175, 55, 0.05));
        border-bottom: 2px solid rgba(212, 175, 55, 0.2);
    }

    .md-branch-table th {
        padding: 1rem;
        text-align: left;
        font-weight: 700;
        color: var(--gold);
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .md-branch-table tbody tr {
        border-bottom: 1px solid rgba(212, 175, 55, 0.08);
        transition: background-color 0.2s ease;
    }

    .md-branch-table tbody tr:hover {
        background: rgba(212, 175, 55, 0.05);
    }

    .md-branch-table td {
        padding: 1rem;
        color: #e0e0e0;
        font-size: 0.95rem;
    }

    .md-branch-name {
        font-weight: 600;
        color: var(--gold-light);
    }

    /* Progress Bar */
    .md-progress-container {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .md-progress-bar {
        flex: 1;
        height: 8px;
        background: rgba(212, 175, 55, 0.1);
        border-radius: 10px;
        overflow: hidden;
        position: relative;
    }

    .md-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--gold), var(--gold-light));
        border-radius: 10px;
        transition: width 0.4s ease;
        box-shadow: 0 0 10px rgba(212, 175, 55, 0.3);
    }

    .md-progress-text {
        font-weight: 600;
        color: var(--gold);
        min-width: 45px;
        text-align: right;
    }

    .md-headcount-info {
        color: #a0a0a0;
        font-size: 0.9rem;
    }

    /* ===== ACTIVITY TICKER ===== */
    .md-activity-ticker-container {
        background: rgba(26, 26, 26, 0.8);
        border: 1px solid rgba(212, 175, 55, 0.15);
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    .md-activity-item {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        padding: 1rem;
        border-left: 3px solid transparent;
        border-radius: 4px;
        transition: all 0.3s ease;
        margin-bottom: 0.75rem;
    }

    .md-activity-item:last-child {
        margin-bottom: 0;
    }

    .md-activity-item:hover {
        background: rgba(212, 175, 55, 0.05);
        border-left-color: var(--gold);
    }

    .md-activity-status-icon {
        font-size: 1.5rem;
        min-width: 2rem;
        text-align: center;
        margin-top: 0.25rem;
    }

    .md-activity-status-icon.present {
        color: #10b981;
    }

    .md-activity-status-icon.absent {
        color: #ef4444;
    }

    .md-activity-content {
        flex: 1;
    }

    .md-activity-employee-name {
        font-weight: 700;
        color: var(--gold);
        font-size: 1rem;
        margin-bottom: 0.25rem;
    }

    .md-activity-meta {
        font-size: 0.85rem;
        color: #a0a0a0;
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .md-activity-meta-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .md-activity-meta-item i {
        color: var(--gold);
        font-size: 0.8rem;
    }

    .md-activity-time {
        font-size: 0.8rem;
        color: #7a7a7a;
        margin-top: 0.5rem;
    }

    /* Empty State */
    .md-empty-state {
        text-align: center;
        padding: 3rem 2rem;
        color: #7a7a7a;
    }

    .md-empty-state i {
        font-size: 3rem;
        color: rgba(212, 175, 55, 0.3);
        margin-bottom: 1rem;
        display: block;
    }

    /* Animations */
    @keyframes mdFadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .md-summary-cards .md-glass-card {
        animation: mdFadeInUp 0.6s ease-out;
    }

    .md-summary-cards .md-glass-card:nth-child(1) { animation-delay: 0.1s; }
    .md-summary-cards .md-glass-card:nth-child(2) { animation-delay: 0.2s; }
    .md-summary-cards .md-glass-card:nth-child(3) { animation-delay: 0.3s; }
    .md-summary-cards .md-glass-card:nth-child(4) { animation-delay: 0.4s; }

    /* Status Badge */
    .md-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.75rem;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .md-status-optimal {
        color: #10b981;
    }

    .md-status-normal {
        color: #f59e0b;
    }

    .md-status-low {
        color: #ef4444;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .md-summary-cards {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .md-card-value {
            font-size: 1.75rem;
        }

        .md-card-icon {
            font-size: 1.75rem;
        }

        .md-section-title {
            font-size: 1.25rem;
        }

        .md-branch-table th,
        .md-branch-table td {
            padding: 0.75rem;
            font-size: 0.85rem;
        }

        .md-progress-container {
            flex-direction: column;
            align-items: stretch;
            gap: 0.5rem;
        }

        .md-progress-text {
            text-align: right;
        }
    }
</style>

<!-- Monitoring Dashboard Container -->
<div class="monitoring-dashboard">

    <!-- ================================================================ -->
    <!-- SECTION 1: TOP ROW SUMMARY CARDS (4 Cards) -->
    <!-- ================================================================ -->
    <div class="md-summary-cards">
        
        <!-- Card 1: Total Manpower -->
        <div class="md-glass-card">
            <div class="md-card-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="md-card-label">Total Manpower</div>
            <div class="md-card-value"><?php echo $totalEmployees; ?></div>
            <div class="md-card-subtitle">Active Employees</div>
        </div>

        <!-- Card 2: On-Site (Present Today) -->
        <div class="md-glass-card">
            <div class="md-card-icon">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="md-card-label">On-Site Today</div>
            <div class="md-card-value"><?php echo $presentToday; ?></div>
            <div class="md-card-subtitle">
                <?php 
                $onSitePercent = $totalEmployees > 0 ? round(($presentToday / $totalEmployees) * 100) : 0;
                echo $onSitePercent . '% Attendance';
                ?>
            </div>
        </div>

        <!-- Card 3: Absent Today -->
        <div class="md-glass-card">
            <div class="md-card-icon">
                <i class="fas fa-user-slash"></i>
            </div>
            <div class="md-card-label">Absent Today</div>
            <div class="md-card-value"><?php echo $absentToday; ?></div>
            <div class="md-card-subtitle">
                <?php 
                $absentPercent = $totalEmployees > 0 ? round(($absentToday / $totalEmployees) * 100) : 0;
                echo $absentPercent . '% Absence';
                ?>
            </div>
        </div>

        <!-- Card 4: Active Branches -->
        <div class="md-glass-card">
            <div class="md-card-icon">
                <i class="fas fa-sitemap"></i>
            </div>
            <div class="md-card-label">Active Branches</div>
            <div class="md-card-value"><?php echo $activeBranches; ?></div>
            <div class="md-card-subtitle">Deployed Today</div>
        </div>

    </div>

    <!-- ================================================================ -->
    <!-- SECTION 2: HEADCOUNT PER BRANCH TABLE -->
    <!-- ================================================================ -->
    <div class="md-section-title">
        <i class="fas fa-building"></i>
        Headcount Per Branch
    </div>

    <div class="md-branch-table-container">
        <?php if (!empty($branchHeadcount)): ?>
            <table class="md-branch-table">
                <thead>
                    <tr>
                        <th>Branch Name</th>
                        <th>Present Today</th>
                        <th>Total Employees</th>
                        <th>Capacity</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($branchHeadcount as $branch): ?>
                        <tr>
                            <td class="md-branch-name"><?php echo htmlspecialchars($branch['branch_name']); ?></td>
                            <td class="md-headcount-info">
                                <strong><?php echo $branch['present_today']; ?></strong> / <?php echo $branch['total_employees']; ?>
                            </td>
                            <td><?php echo $branch['total_employees']; ?> employees</td>
                            <td>
                                <div class="md-progress-container">
                                    <div class="md-progress-bar">
                                        <div class="md-progress-fill" style="width: <?php echo $branch['percentage']; ?>%;"></div>
                                    </div>
                                    <div class="md-progress-text"><?php echo $branch['percentage']; ?>%</div>
                                </div>
                            </td>
                            <td>
                                <?php 
                                $statusClass = '';
                                $statusIcon = '';
                                $statusText = '';
                                
                                if ($branch['percentage'] >= 80) {
                                    $statusClass = 'md-status-optimal';
                                    $statusIcon = 'fa-check-circle';
                                    $statusText = 'Optimal';
                                } elseif ($branch['percentage'] >= 60) {
                                    $statusClass = 'md-status-normal';
                                    $statusIcon = 'fa-exclamation-circle';
                                    $statusText = 'Normal';
                                } else {
                                    $statusClass = 'md-status-low';
                                    $statusIcon = 'fa-times-circle';
                                    $statusText = 'Low';
                                }
                                ?>
                                <span class="md-status-badge <?php echo $statusClass; ?>">
                                    <i class="fas <?php echo $statusIcon; ?>"></i> 
                                    <?php echo $statusText; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="md-empty-state">
                <i class="fas fa-inbox"></i>
                <p>No branch data available</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- ================================================================ -->
    <!-- SECTION 3: RECENT ACTIVITY TICKER -->
    <!-- ================================================================ -->
    <div class="md-section-title">
        <i class="fas fa-clock"></i>
        Recent Activity (Last 5 Records)
    </div>

    <div class="md-activity-ticker-container">
        <?php if (!empty($recentActivity)): ?>
            <?php foreach ($recentActivity as $activity): ?>
                <?php
                $activityStatus = $activity['status'] ?? '';
                $activityBranchName = $activity['branch_name'] ?? '';
                $activityFirstName = $activity['first_name'] ?? '';
                $activityLastName = $activity['last_name'] ?? '';
                $activityCreatedAt = $activity['created_at'] ?? '';
                ?>
                <div class="md-activity-item">
                    <div class="md-activity-status-icon <?php echo strtolower($activityStatus); ?>">
                        <?php 
                        if ($activityStatus === 'Present') {
                            echo '<i class="fas fa-circle-check"></i>';
                        } else {
                            echo '<i class="fas fa-circle-xmark"></i>';
                        }
                        ?>
                    </div>
                    <div class="md-activity-content">
                        <div class="md-activity-employee-name">
                            <?php echo htmlspecialchars(trim($activityFirstName . ' ' . $activityLastName)); ?>
                        </div>
                        <div class="md-activity-meta">
                            <div class="md-activity-meta-item">
                                <i class="fas fa-building"></i>
                                <span><?php echo htmlspecialchars($activityBranchName); ?></span>
                            </div>
                            <div class="md-activity-meta-item">
                                <i class="fas fa-check"></i>
                                <span><?php echo htmlspecialchars($activityStatus); ?></span>
                            </div>
                        </div>
                        <div class="md-activity-time">
                            <i class="fas fa-clock"></i>
                            <?php 
                            $createdTime = $activityCreatedAt !== '' ? strtotime($activityCreatedAt) : false;
                            $now = time();
                            $diff = ($createdTime !== false) ? ($now - $createdTime) : null;
                            
                            if ($diff !== null && $diff < 60) {
                                echo "Just now";
                            } elseif ($diff !== null && $diff < 3600) {
                                echo round($diff / 60) . " minutes ago";
                            } elseif ($diff !== null && $diff < 86400) {
                                echo round($diff / 3600) . " hours ago";
                            } else {
                                echo $createdTime !== false ? date('M d, Y H:i', $createdTime) : '';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="md-empty-state">
                <i class="fas fa-inbox"></i>
                <p>No recent activity</p>
            </div>
        <?php endif; ?>
    </div>

</div>
