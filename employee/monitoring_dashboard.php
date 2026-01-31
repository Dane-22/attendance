<?php
// employee/monitoring_dashboard.php
// Professional Monitoring Dashboard with Real-Time SQL Logic
// Dark Engineering Theme with Gold/Black Colors

// IMPORTANT: This file should be included in dashboard.php or called as a standalone
// It requires an active database connection ($db) and session to be started

if (!isset($db)) {
    require_once __DIR__ . '/../conn/db_connection.php';
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$today = date('Y-m-d');

// ============================================================================
// SECTION 1: REAL-TIME SQL LOGIC & DATA FETCHING
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

// 4. Today's Deployment (By Branch)
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
$allBranchesQuery = "SELECT DISTINCT branch_name FROM employees WHERE status = 'Active' ORDER BY branch_name ASC";
$allBranchesResult = mysqli_query($db, $allBranchesQuery);
$branchHeadcount = [];

while ($branch = mysqli_fetch_assoc($allBranchesResult)) {
    $branchName = $branch['branch_name'];
    $headcountQuery = "SELECT COUNT(*) as count FROM attendance 
                      WHERE attendance_date = CURDATE() AND status = 'Present' AND branch_name = ?";
    $headcountStmt = mysqli_prepare($db, $headcountQuery);
    mysqli_stmt_bind_param($headcountStmt, 's', $branchName);
    mysqli_stmt_execute($headcountStmt);
    $headcountRes = mysqli_stmt_get_result($headcountStmt);
    $headcountRow = mysqli_fetch_assoc($headcountRes);
    
    // Get total employees in branch
    $totalBranchQuery = "SELECT COUNT(*) as count FROM employees WHERE status = 'Active' AND branch_name = ?";
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

// 6. Recent Activity - Last 5 Attendance Records (with employee names)
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

// ============================================================================
// SECTION 2: DASHBOARD UI LAYOUT STARTS HERE
// ============================================================================
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Dashboard - JAJR</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --gold: #d4af37;
            --gold-light: #FFD700;
            --black: #0a0a0a;
            --dark-gray: #1a1a1a;
            --medium-gray: #2d2d2d;
            --light-gray: #3a3a3a;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, var(--black) 0%, #0f0f0f 100%);
            color: #ffffff;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
        }

        .monitoring-container {
            padding: 2rem;
            max-width: 100%;
        }

        /* ===== SUMMARY CARDS WITH GLASSMORPHISM ===== */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .glass-card {
            background: rgba(26, 26, 26, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(212, 175, 55, 0.15);
            border-radius: 16px;
            padding: 1.5rem;
            transition: all 0.3s cubic-bezier(0.23, 1, 0.320, 1);
            position: relative;
            overflow: hidden;
        }

        .glass-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(212, 175, 55, 0.05), transparent);
            transition: left 0.5s ease;
        }

        .glass-card:hover {
            border-color: rgba(212, 175, 55, 0.35);
            background: rgba(26, 26, 26, 0.85);
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(212, 175, 55, 0.1);
        }

        .glass-card:hover::before {
            left: 100%;
        }

        .card-icon {
            font-size: 2.5rem;
            color: var(--gold);
            margin-bottom: 0.75rem;
            display: inline-block;
        }

        .card-label {
            font-size: 0.875rem;
            color: #a0a0a0;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .card-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--gold);
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .card-subtitle {
            font-size: 0.75rem;
            color: #7a7a7a;
        }

        /* ===== HEADCOUNT PER BRANCH TABLE ===== */
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 1rem;
            margin-top: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            color: var(--gold);
            font-size: 1.75rem;
        }

        .branch-table-container {
            background: rgba(26, 26, 26, 0.8);
            border: 1px solid rgba(212, 175, 55, 0.15);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 2.5rem;
        }

        .branch-table {
            width: 100%;
            border-collapse: collapse;
        }

        .branch-table thead {
            background: linear-gradient(90deg, rgba(212, 175, 55, 0.1), rgba(212, 175, 55, 0.05));
            border-bottom: 2px solid rgba(212, 175, 55, 0.2);
        }

        .branch-table th {
            padding: 1rem;
            text-align: left;
            font-weight: 700;
            color: var(--gold);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .branch-table tbody tr {
            border-bottom: 1px solid rgba(212, 175, 55, 0.08);
            transition: background-color 0.2s ease;
        }

        .branch-table tbody tr:hover {
            background: rgba(212, 175, 55, 0.05);
        }

        .branch-table td {
            padding: 1rem;
            color: #e0e0e0;
            font-size: 0.95rem;
        }

        .branch-name {
            font-weight: 600;
            color: var(--gold-light);
        }

        /* Progress Bar */
        .progress-container {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .progress-bar {
            flex: 1;
            height: 8px;
            background: rgba(212, 175, 55, 0.1);
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--gold), var(--gold-light));
            border-radius: 10px;
            transition: width 0.4s ease;
            box-shadow: 0 0 10px rgba(212, 175, 55, 0.3);
        }

        .progress-text {
            font-weight: 600;
            color: var(--gold);
            min-width: 45px;
            text-align: right;
        }

        .headcount-info {
            color: #a0a0a0;
            font-size: 0.9rem;
        }

        /* ===== RECENT ACTIVITY TICKER ===== */
        .activity-ticker-container {
            background: rgba(26, 26, 26, 0.8);
            border: 1px solid rgba(212, 175, 55, 0.15);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem;
            border-left: 3px solid transparent;
            border-radius: 4px;
            transition: all 0.3s ease;
            margin-bottom: 0.75rem;
        }

        .activity-item:last-child {
            margin-bottom: 0;
        }

        .activity-item:hover {
            background: rgba(212, 175, 55, 0.05);
            border-left-color: var(--gold);
        }

        .activity-status-icon {
            font-size: 1.5rem;
            min-width: 2rem;
            text-align: center;
            margin-top: 0.25rem;
        }

        .activity-status-icon.present {
            color: #10b981;
        }

        .activity-status-icon.absent {
            color: #ef4444;
        }

        .activity-content {
            flex: 1;
        }

        .activity-employee-name {
            font-weight: 700;
            color: var(--gold);
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }

        .activity-meta {
            font-size: 0.85rem;
            color: #a0a0a0;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .activity-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .activity-meta-item i {
            color: var(--gold);
            font-size: 0.8rem;
        }

        .activity-time {
            font-size: 0.8rem;
            color: #7a7a7a;
            margin-top: 0.5rem;
        }

        /* ===== RESPONSIVE ADJUSTMENTS ===== */
        @media (max-width: 768px) {
            .monitoring-container {
                padding: 1rem;
            }

            .summary-cards {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 1rem;
            }

            .card-value {
                font-size: 1.75rem;
            }

            .card-icon {
                font-size: 1.75rem;
            }

            .section-title {
                font-size: 1.25rem;
            }

            .branch-table th,
            .branch-table td {
                padding: 0.75rem;
                font-size: 0.85rem;
            }

            .progress-container {
                flex-direction: column;
                align-items: stretch;
                gap: 0.5rem;
            }

            .progress-text {
                text-align: right;
            }
        }

        /* ===== ANIMATIONS ===== */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .summary-cards .glass-card {
            animation: fadeInUp 0.6s ease-out;
        }

        .summary-cards .glass-card:nth-child(1) { animation-delay: 0.1s; }
        .summary-cards .glass-card:nth-child(2) { animation-delay: 0.2s; }
        .summary-cards .glass-card:nth-child(3) { animation-delay: 0.3s; }
        .summary-cards .glass-card:nth-child(4) { animation-delay: 0.4s; }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #7a7a7a;
        }

        .empty-state i {
            font-size: 3rem;
            color: rgba(212, 175, 55, 0.3);
            margin-bottom: 1rem;
            display: block;
        }
    </style>
</head>
<body>
    <div class="monitoring-container">
        
        <!-- ================================================================ -->
        <!-- SECTION 1: TOP ROW SUMMARY CARDS (4 Cards) -->
        <!-- ================================================================ -->
        <div class="summary-cards">
            
            <!-- Card 1: Total Manpower -->
            <div class="glass-card">
                <div class="card-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="card-label">Total Manpower</div>
                <div class="card-value"><?php echo $totalEmployees; ?></div>
                <div class="card-subtitle">Active Employees</div>
            </div>

            <!-- Card 2: On-Site (Present Today) -->
            <div class="glass-card">
                <div class="card-icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="card-label">On-Site Today</div>
                <div class="card-value"><?php echo $presentToday; ?></div>
                <div class="card-subtitle">
                    <?php 
                    $onSitePercent = $totalEmployees > 0 ? round(($presentToday / $totalEmployees) * 100) : 0;
                    echo $onSitePercent . '% Attendance Rate';
                    ?>
                </div>
            </div>

            <!-- Card 3: Absent Today -->
            <div class="glass-card">
                <div class="card-icon">
                    <i class="fas fa-user-slash"></i>
                </div>
                <div class="card-label">Absent Today</div>
                <div class="card-value"><?php echo $absentToday; ?></div>
                <div class="card-subtitle">
                    <?php 
                    $absentPercent = $totalEmployees > 0 ? round(($absentToday / $totalEmployees) * 100) : 0;
                    echo $absentPercent . '% Absence Rate';
                    ?>
                </div>
            </div>

            <!-- Card 4: Active Branches -->
            <div class="glass-card">
                <div class="card-icon">
                    <i class="fas fa-sitemap"></i>
                </div>
                <div class="card-label">Active Branches</div>
                <div class="card-value"><?php echo $activeBranches; ?></div>
                <div class="card-subtitle">Deployed Today</div>
            </div>

        </div>

        <!-- ================================================================ -->
        <!-- SECTION 2: HEADCOUNT PER BRANCH TABLE -->
        <!-- ================================================================ -->
        <div class="section-title">
            <i class="fas fa-building"></i>
            Headcount Per Branch
        </div>

        <div class="branch-table-container">
            <?php if (!empty($branchHeadcount)): ?>
                <table class="branch-table">
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
                                <td class="branch-name"><?php echo htmlspecialchars($branch['branch_name']); ?></td>
                                <td class="headcount-info">
                                    <strong><?php echo $branch['present_today']; ?></strong> / <?php echo $branch['total_employees']; ?>
                                </td>
                                <td><?php echo $branch['total_employees']; ?> employees</td>
                                <td>
                                    <div class="progress-container">
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $branch['percentage']; ?>%;"></div>
                                        </div>
                                        <div class="progress-text"><?php echo $branch['percentage']; ?>%</div>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    if ($branch['percentage'] >= 80) {
                                        echo '<span style="color: #10b981;"><i class="fas fa-check-circle"></i> Optimal</span>';
                                    } elseif ($branch['percentage'] >= 60) {
                                        echo '<span style="color: #f59e0b;"><i class="fas fa-exclamation-circle"></i> Normal</span>';
                                    } else {
                                        echo '<span style="color: #ef4444;"><i class="fas fa-times-circle"></i> Low</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No branch data available</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- ================================================================ -->
        <!-- SECTION 3: RECENT ACTIVITY TICKER -->
        <!-- ================================================================ -->
        <div class="section-title">
            <i class="fas fa-clock"></i>
            Recent Activity (Last 5 Records)
        </div>

        <div class="activity-ticker-container">
            <?php if (!empty($recentActivity)): ?>
                <?php foreach ($recentActivity as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-status-icon <?php echo strtolower($activity['status']); ?>">
                            <?php 
                            if ($activity['status'] === 'Present') {
                                echo '<i class="fas fa-circle-check"></i>';
                            } else {
                                echo '<i class="fas fa-circle-xmark"></i>';
                            }
                            ?>
                        </div>
                        <div class="activity-content">
                            <div class="activity-employee-name">
                                <?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?>
                            </div>
                            <div class="activity-meta">
                                <div class="activity-meta-item">
                                    <i class="fas fa-building"></i>
                                    <span><?php echo htmlspecialchars($activity['branch_name']); ?></span>
                                </div>
                                <div class="activity-meta-item">
                                    <i class="fas fa-check"></i>
                                    <span><?php echo htmlspecialchars($activity['status']); ?></span>
                                </div>
                            </div>
                            <div class="activity-time">
                                <i class="fas fa-clock"></i>
                                <?php 
                                $createdTime = strtotime($activity['created_at']);
                                $now = time();
                                $diff = $now - $createdTime;
                                
                                if ($diff < 60) {
                                    echo "Just now";
                                } elseif ($diff < 3600) {
                                    echo round($diff / 60) . " minutes ago";
                                } elseif ($diff < 86400) {
                                    echo round($diff / 3600) . " hours ago";
                                } else {
                                    echo date('M d, Y H:i', $createdTime);
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No recent activity</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>
