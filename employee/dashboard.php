<?php
require_once('../conn/db_connection.php');
session_start();
require_once('function/dashboard_function.php');

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Employee Dashboard — JAJR</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="css/dashboard.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    // PHP data exposed to JavaScript
    window.dashboardData = {
      overviewData: <?php echo json_encode($overviewData); ?>,
      weeklyPattern: <?php echo json_encode($weeklyPattern); ?>,
      monthlyTrend: <?php echo json_encode($monthlyTrend); ?>,
      isAdmin: <?php echo (isset($_SESSION['position']) && in_array($_SESSION['position'], ['Admin', 'Super Admin'])) ? 'true' : 'false'; ?>
    };
  </script>
  <link rel="icon" type="image/x-icon" href="../assets/img/profile/jajr-logo.png">
 
</head>
<body class="employee-bg">
  <div class="app-shell">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="main-content">
      <!-- Attendance Notification -->
      <?php if (isset($attendance_message)): ?>
      <div class="attendance-notification">
        <div class="notification-content">
          <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
          </svg>
          <span><?php echo htmlspecialchars($attendance_message); ?></span>
        </div>
      </div>
      <?php endif; ?>

      <!-- MONITORING DASHBOARD COMPONENT -->
      <?php include __DIR__ . '/monitoring_dashboard_component.php'; ?>

      <!-- Debug Info (Remove this after testing) -->
      <!-- <div style="background: #f3f4f6; padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 12px; color: #6b7280; border: 1px solid #d1d5db;">
        <strong>Summary:</strong><br>
        Today: <?php echo $today; ?><br> -->
        <!-- Monthly Rate:%<br> -->
         <!-- <?php echo $employeeAttendanceStats['attendance_rate']; ?>
        <br>
        Present Today: <?php echo $presentCount; ?><br>
        Total Employees: <?php echo $totalEmployees; ?>
      </div>  -->

      <div class="header-card">
        <div class="header-left">
          <button id="sidebarToggle" class="menu-toggle" aria-label="Toggle sidebar">☰</button>
          <div>
            <div class="welcome">Welcome back, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</div>
            <div class="text-sm text-gray-500">
                Employee Code: <strong><?php echo htmlspecialchars($employeeCode); ?></strong> | 
                Position: <?php echo htmlspecialchars($position); ?>
            </div>
          </div>
        </div>
        <div class="text-sm text-gray-500">
            Today: <?php echo date('F d, Y'); ?>
        </div>
      </div>


      <!-- Personal Attendance Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-title">Your Monthly Attendance Rate</div>
          <div class="stat-value"><?php echo $employeeAttendanceStats['attendance_rate']; ?>%</div>
          <div class="progress-bar">
            <div class="progress-fill" style="width: <?php echo $employeeAttendanceStats['attendance_rate']; ?>%"></div>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-title">Days Present (This Month)</div>
          <div class="stat-value"><?php echo $employeeAttendanceStats['total_present']; ?></div>
          <div class="stat-change positive">
            <?php echo $employeeAttendanceStats['consecutive_days']; ?> consecutive days present
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-title">Days Absent (This Month)</div>
          <div class="stat-value"><?php echo $employeeAttendanceStats['total_absent']; ?></div>
          <div class="stat-change <?php echo $employeeAttendanceStats['total_absent'] > 0 ? 'negative' : 'positive'; ?>">
            <?php echo $employeeAttendanceStats['total_absent'] > 0 ? 'Needs improvement' : 'Perfect!'; ?>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-title">Today's Company Rate</div>
          <div class="stat-value"><?php echo $attendanceRate; ?>%</div>
          <div class="stat-change positive">
            <?php echo $presentCount; ?> of <?php echo $totalEmployees; ?> employees present
          </div>
        </div>
      </div>

      <?php if (isset($_SESSION['position']) && in_array($_SESSION['position'], ['Admin', 'Super Admin'])): ?>
      <!-- Admin Quick Actions -->
      <div class="quick-actions-section">
        <div class="quick-actions-title">
          <i class="fas fa-bolt"></i>
          Admin Quick Actions
        </div>
        <div class="quick-actions-grid">
          <button class="quick-action-btn" onclick="quickActionInstantExport()">
            <span class="action-number">1</span>
            <div class="action-icon"><i class="fas fa-file-excel"></i></div>
            <span class="action-label">Instant Payroll Export</span>
            <span class="action-desc">Export current week payroll</span>
          </button>
          
          <button class="quick-action-btn" onclick="quickActionSearchAttendance()">
            <span class="action-number">2</span>
            <div class="action-icon"><i class="fas fa-user-clock"></i></div>
            <span class="action-label">Search & Log Attendance</span>
            <span class="action-desc">Find employee & time in/out</span>
          </button>
          
          <button class="quick-action-btn" onclick="quickActionMissingLogs()">
            <span class="action-number">3</span>
            <div class="action-icon"><i class="fas fa-user-times"></i></div>
            <span class="action-label">View Missing Logs</span>
            <span class="action-desc">Employees not timed in today</span>
          </button>
          
          <button class="quick-action-btn" onclick="quickActionRecentActivity()">
            <span class="action-number">4</span>
            <div class="action-icon"><i class="fas fa-history"></i></div>
            <span class="action-label">Recent Activity Logs</span>
            <span class="action-desc">Top 5 recent system logs</span>
          </button>
        </div>
      </div>

      <!-- Quick Action Modals -->
      <div id="modal-search-attendance" class="quick-action-modal">
        <div class="quick-action-modal-content">
          <div class="quick-action-modal-header">
            <h3><i class="fas fa-user-clock"></i> Search & Log Attendance</h3>
            <button class="quick-action-modal-close" onclick="closeQuickActionModal('modal-search-attendance')">&times;</button>
          </div>
          <div class="quick-action-modal-body">
            <input type="text" class="quick-action-search" id="search-attendance-input" placeholder="Search employee by name or code..." oninput="searchEmployees(this.value)">
            <div class="quick-action-list" id="search-attendance-results">
              <!-- Results will be populated here -->
            </div>
          </div>
        </div>
      </div>

      <div id="modal-missing-logs" class="quick-action-modal">
        <div class="quick-action-modal-content">
          <div class="quick-action-modal-header">
            <h3><i class="fas fa-user-times"></i> Missing Logs - <?php echo date('F d, Y'); ?></h3>
            <button class="quick-action-modal-close" onclick="closeQuickActionModal('modal-missing-logs')">&times;</button>
          </div>
          <div class="quick-action-modal-body">
            <div class="quick-action-list" id="missing-logs-results">
              <div style="text-align: center; color: #808080; padding: 20px;">Loading...</div>
            </div>
          </div>
        </div>
      </div>

      <div id="modal-recent-activity" class="quick-action-modal">
        <div class="quick-action-modal-content">
          <div class="quick-action-modal-header">
            <h3><i class="fas fa-history"></i> Recent Activity Logs</h3>
            <button class="quick-action-modal-close" onclick="closeQuickActionModal('modal-recent-activity')">&times;</button>
          </div>
          <div class="quick-action-modal-body">
            <div id="recent-activity-results">
              <div style="text-align: center; color: #808080; padding: 20px;">Loading...</div>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Analytics Section -->
      <div class="analytics-section">
        <div class="section-header">
          <div>
            <div class="section-title">Attendance Analytics</div>
            <div class="section-subtitle">Detailed reports and insights about your attendance</div>
          </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
          <div class="tab active" onclick="switchTab('overview')">Overview</div>
          <div class="tab" onclick="switchTab('detailed')">Detailed Report</div>
          <div class="tab" onclick="switchTab('ranking')">Ranking</div>
          <div class="tab" onclick="switchTab('trends')">Trends</div>
        </div>

        <!-- Overview Tab -->
        <div id="overview-tab" class="tab-content active">
          <div class="insight-card">
            <div class="insight-title">📊 Attendance Insight</div>
            <div class="insight-text">
              <?php
              $rate = $employeeAttendanceStats['attendance_rate'];
              if ($rate >= 95) {
                  echo "Excellent attendance! You're setting a great example with " . $rate . "% attendance rate.";
              } elseif ($rate >= 85) {
                  echo "Good attendance at " . $rate . "%. Keep up the consistency!";
              } else {
                  echo "Your attendance rate is " . $rate . "%. Consider improving consistency.";
              }
              ?>
            </div>
          </div>

          <!-- Charts Row -->
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div class="chart-container">
              <canvas id="attendanceChart"></canvas>
            </div>
            <div class="chart-container">
              <canvas id="weeklyPatternChart"></canvas>
            </div>
          </div>

          <!-- Quick Stats -->
          <?php if (!empty($weeklyPattern)): ?>
          <div class="data-table">
            <table>
              <thead>
                <tr>
                  <th>Day of Week</th>
                  <th>Attendance Rate</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($weeklyPattern as $day): ?>
                <tr>
                  <td><?php echo $day['day']; ?></td>
                  <td>
                    <div style="display: flex; align-items: center; gap: 8px;">
                      <span><?php echo $day['rate']; ?>%</span>
                      <div class="progress-bar" style="flex: 1;">
                        <div class="progress-fill" style="width: <?php echo $day['rate']; ?>%"></div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <?php if ($day['rate'] >= 90): ?>
                      <span class="badge badge-present">Consistent</span>
                    <?php elseif ($day['rate'] >= 70): ?>
                      <span class="badge badge-warning">Average</span>
                    <?php else: ?>
                      <span class="badge badge-absent">Low</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
          <div style="text-align: center; padding: 40px; color: #6b7280; background: #f9fafb; border-radius: 8px;">
            No weekly pattern data available yet. Mark more attendance to see patterns.
          </div>
          <?php endif; ?>
        </div>

        <!-- Detailed Report Tab -->
        <div id="detailed-tab" class="tab-content">
          <?php if (!empty($detailedReport)): ?>
          <div class="data-table">
            <table>
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Day</th>
                  <th>Status</th>
                  <th>Marked At</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($detailedReport as $record): ?>
                <tr>
                  <td><?php echo date('M d, Y', strtotime($record['attendance_date'])); ?></td>
                  <td><?php echo date('D', strtotime($record['attendance_date'])); ?></td>
                  <td>
                    <?php if ($record['status'] == 'Present'): ?>
                      <span class="badge badge-present">Present</span>
                    <?php else: ?>
                      <span class="badge badge-absent">Absent</span>
                    <?php endif; ?>
                  </td>
                  <td><?php echo date('h:i A', strtotime($record['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
          <div style="text-align: center; padding: 40px; color: #6b7280; background: #f9fafb; border-radius: 8px;">
            No attendance records found for this month. Mark your attendance to see detailed reports.
          </div>
          <?php endif; ?>
        </div>

        <!-- Ranking Tab -->
        <div id="ranking-tab" class="tab-content">
          <?php if (!empty($attendanceRanking)): ?>
          <div class="data-table">
            <table>
              <thead>
                <tr>
                  <th>Rank</th>
                  <th>Employee</th>
                  <th>Position</th>
                  <th>Present Days</th>
                  <th>Absent Days</th>
                  <th>Attendance Rate</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                $userRank = 0;
                foreach ($attendanceRanking as $index => $employee):
                $rank = $index + 1;
                $isCurrentUser = $employee['id'] == $employeeId;
                if ($isCurrentUser) $userRank = $rank;
                ?>
                <tr style="<?php echo $isCurrentUser ? 'background: #f0f9ff; font-weight: 600;' : ''; ?>">
                  <td>
                    <span class="rank-badge <?php echo 'rank-' . min($rank, 3); ?>">
                      <?php echo $rank; ?>
                    </span>
                    <?php if ($isCurrentUser): ?>
                      <span style="font-size: 12px; color: #3b82f6; margin-left: 4px;">(You)</span>
                    <?php endif; ?>
                  </td>
                  <td><?php echo htmlspecialchars($employee['name']); ?></td>
                  <td><?php echo htmlspecialchars($employee['position']); ?></td>
                  <td><?php echo $employee['present_days']; ?></td>
                  <td><?php echo $employee['absent_days']; ?></td>
                  <td>
                    <div style="display: flex; align-items: center; gap: 8px;">
                      <span><?php echo $employee['attendance_rate']; ?>%</span>
                      <div class="progress-bar" style="flex: 1;">
                        <div class="progress-fill" style="width: <?php echo $employee['attendance_rate']; ?>%"></div>
                      </div>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <?php if ($userRank > 0): ?>
            <div style="padding: 16px; background: #f8fafc; border-top: 1px solid #e5e7eb; text-align: center;">
              <strong>Your Rank:</strong> #<?php echo $userRank; ?> out of <?php echo count($attendanceRanking); ?> employees
            </div>
            <?php endif; ?>
          </div>
          <?php else: ?>
          <div style="text-align: center; padding: 40px; color: #6b7280; background: #f9fafb; border-radius: 8px;">
            No ranking data available for this month. Mark attendance to see rankings.
          </div>
          <?php endif; ?>
        </div>

        <!-- Trends Tab -->
        <div id="trends-tab" class="tab-content">
          <?php if (!empty($monthlyTrend)): ?>
          <div class="chart-container" style="height: 400px;">
            <canvas id="trendChart"></canvas>
          </div>
          
          <div class="data-table">
            <table>
              <thead>
                <tr>
                  <th>Month</th>
                  <th>Present Days</th>
                  <th>Absent Days</th>
                  <th>Attendance Rate</th>
                  <th>Trend</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($monthlyTrend as $month): ?>
                <?php 
                $trendIcon = '';
                $trendClass = '';
                $rate = $month['rate'];
                
                if ($rate >= 95) {
                    $trendIcon = '📈';
                    $trendClass = 'positive';
                } elseif ($rate >= 85) {
                    $trendIcon = '➡️';
                    $trendClass = 'positive';
                } else {
                    $trendIcon = '📉';
                    $trendClass = 'negative';
                }
                ?>
                <tr>
                  <td><?php echo $month['month']; ?></td>
                  <td><?php echo $month['present']; ?></td>
                  <td><?php echo $month['absent']; ?></td>
                  <td><?php echo $rate; ?>%</td>
                  <td class="<?php echo $trendClass; ?>">
                    <?php echo $trendIcon; ?> 
                    <?php echo $rate >= 95 ? 'Excellent' : ($rate >= 85 ? 'Good' : 'Needs Improvement'); ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
          <div style="text-align: center; padding: 40px; color: #6b7280; background: #f9fafb; border-radius: 8px;">
            No trend data available yet. Mark more attendance to see trends.
          </div>
          <?php endif; ?>
        </div>
      </div>
    </main>
  </div>

  <script src="../assets/js/employee.js"></script>
  <script src="js/dashboard.js"></script>
</body>
</html>