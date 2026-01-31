<?php
// employee/analytics.php
require_once __DIR__ . '/../conn/db_connection.php';
session_start();

// Sanitize and set date range
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Validate and sanitize dates
if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
    $start_date = mysqli_real_escape_string($db, $_GET['start_date']);
    $end_date = mysqli_real_escape_string($db, $_GET['end_date']);
    
    if (strtotime($end_date) < strtotime($start_date)) {
        $end_date = $start_date;
    }
}

// Get attendance data with date filter
$sql = "SELECT e.id, e.employee_code, e.first_name, e.last_name,
           COALESCE(SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END), 0) AS present_count,
           COALESCE(SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END), 0) AS absent_count
         FROM employees e
         LEFT JOIN attendance a ON e.id = a.employee_id 
            AND a.created_at BETWEEN ? AND ?
         GROUP BY e.id
         ORDER BY e.last_name, e.first_name";

$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

// Get overall statistics
$stats_sql = "SELECT 
    COUNT(DISTINCT e.id) as total_employees,
    COALESCE(SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END), 0) as total_present,
    COALESCE(SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END), 0) as total_absent,
    COALESCE(AVG(CASE WHEN a.status = 'present' THEN 1.0 ELSE 0 END) * 100, 0) as avg_attendance
    FROM employees e
    LEFT JOIN attendance a ON e.id = a.employee_id 
        AND a.created_at BETWEEN ? AND ?";
$stats_stmt = mysqli_prepare($db, $stats_sql);
mysqli_stmt_bind_param($stats_stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($stats_stmt);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stats_stmt));

// Get daily presence data for chart
$daily_sql = "SELECT DATE(a.created_at) as date,
                     SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as daily_present,
                     SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as daily_absent
              FROM attendance a
              WHERE a.created_at BETWEEN ? AND ?
              GROUP BY DATE(a.created_at)
              ORDER BY date";
$daily_stmt = mysqli_prepare($db, $daily_sql);
mysqli_stmt_bind_param($daily_stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($daily_stmt);
$daily_data = mysqli_stmt_get_result($daily_stmt);

// Prepare daily data arrays for chart
$dates = [];
$daily_present = [];
$daily_absent = [];

while ($day = mysqli_fetch_assoc($daily_data)) {
    $dates[] = date('M d', strtotime($day['date']));
    $daily_present[] = $day['daily_present'];
    $daily_absent[] = $day['daily_absent'];
}

// Re-run query for table display
mysqli_data_seek($res, 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Analytics Dashboard — JAJR Engineering</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    :root {
      --primary-dark: #0b0b0b;
      --primary-gold: #FFD700;
      --gold-gradient: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
      --dark-card: rgba(20, 20, 20, 0.8);
    }

    * {
      box-sizing: border-box;
    }

    body {
      background-color: var(--primary-dark);
      color: #ffffff;
      font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
      overflow-x: hidden;
    }

    .glass-card {
      background: rgba(20, 20, 20, 0.8);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 215, 0, 0.2);
      border-radius: 12px;
    }

    .gold-gradient {
      background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
      color: #0b0b0b;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .gold-gradient:hover {
      background: linear-gradient(135deg, #FFA500 0%, #FF8C00 100%);
      transform: translateY(-2px);
    }

    .gold-text {
      color: var(--primary-gold);
    }

    .gold-border {
      border-color: rgba(255, 215, 0, 0.3);
    }

    .stat-card {
      background: linear-gradient(145deg, rgba(30, 30, 30, 0.9), rgba(15, 15, 15, 0.9));
      border-left: 4px solid var(--primary-gold);
      transition: transform 0.3s ease;
    }

    .stat-card:hover {
      transform: translateY(-4px);
    }

    /* Mobile First Adjustments */
    .sidebar {
      position: fixed;
      top: 0;
      left: -100%;
      width: 280px;
      height: 100vh;
      z-index: 1000;
      transition: left 0.3s ease;
      overflow-y: auto;
    }

    .sidebar.active {
      left: 0;
    }

    .main-content {
      margin-left: 0;
      width: 100%;
      padding: 1rem;
      transition: margin-left 0.3s ease;
    }

    @media (min-width: 768px) {
      .sidebar {
        left: 0;
        width: 260px;
      }
      
      .main-content {
        margin-left: 260px;
        width: calc(100% - 260px);
        padding: 1.5rem;
      }
    }

    @media (min-width: 1024px) {
      .main-content {
        padding: 2rem;
      }
    }

    .hamburger {
      position: fixed;
      top: 1rem;
      left: 1rem;
      z-index: 1100;
      background: #0D0D0D;
      /* border: 1px solid rgba(255, 215, 0, 0.3); */
    }

    @media (min-width: 768px) {
      .hamburger {
        display: none;
      }
    }

    .overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.7);
      z-index: 999;
      backdrop-filter: blur(3px);
    }

    .overlay.active {
      display: block;
    }

    /* Responsive Table */
    .responsive-table {
      width: 100%;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }

    .table-wrapper {
      min-width: 800px;
    }

    @media (max-width: 640px) {
      .table-wrapper {
        min-width: 600px;
      }
    }

    .table-header {
      background: rgba(255, 215, 0, 0.1);
      white-space: nowrap;
    }

    .table-row:hover {
      background: rgba(255, 215, 0, 0.05);
    }

    .progress-bar {
      height: 8px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 4px;
      overflow: hidden;
      min-width: 80px;
    }

    .progress-fill {
      height: 100%;
      background: linear-gradient(90deg, #FFD700, #FFA500);
      border-radius: 4px;
      transition: width 0.5s ease;
    }

    .status-badge {
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
      white-space: nowrap;
    }

    .excellent { background: rgba(0, 255, 0, 0.1); color: #00FF00; }
    .good { background: rgba(255, 215, 0, 0.1); color: #FFD700; }
    .fair { background: rgba(255, 165, 0, 0.1); color: #FFA500; }
    .poor { background: rgba(255, 0, 0, 0.1); color: #FF4444; }

    input[type="date"] {
      background: rgba(30, 30, 30, 0.8);
      border: 1px solid rgba(255, 215, 0, 0.3);
      color: white;
      width: 100%;
      height: 45px;
      padding: 0 1rem;
      border-radius: 8px;
      font-size: 0.95rem;
    }

    input[type="date"]:focus {
      outline: none;
      border-color: var(--primary-gold);
      box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.2);
    }

    /* Responsive Chart Containers */
    .chart-container {
      height: 300px;
      width: 100%;
    }

    @media (min-width: 768px) {
      .chart-container {
        height: 320px;
      }
    }

    @media (min-width: 1024px) {
      .chart-container {
        height: 350px;
      }
    }

    /* Responsive Grid */
    .responsive-grid {
      display: grid;
      grid-template-columns: repeat(1, 1fr);
      gap: 1rem;
    }

    @media (min-width: 640px) {
      .responsive-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (min-width: 1024px) {
      .responsive-grid {
        grid-template-columns: repeat(4, 1fr);
      }
    }

    /* Responsive Flex */
    .responsive-flex {
      flex-direction: column;
    }

    @media (min-width: 768px) {
      .responsive-flex {
        flex-direction: row;
      }
    }

    /* Touch-friendly buttons */
    .btn {
      padding: 0.75rem 1.5rem;
      font-size: 0.95rem;
      min-height: 45px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    @media (max-width: 640px) {
      .btn {
        padding: 0.625rem 1.25rem;
        font-size: 0.875rem;
        min-height: 42px;
      }
    }

    /* Typography scaling */
    .text-scale-lg {
      font-size: 1.875rem;
    }

    @media (max-width: 640px) {
      .text-scale-lg {
        font-size: 1.5rem;
      }
    }

    .text-scale-md {
      font-size: 1.125rem;
    }

    @media (max-width: 640px) {
      .text-scale-md {
        font-size: 1rem;
      }
    }

    /* Card spacing */
    .card-spacing {
      padding: 1.25rem;
    }

    @media (min-width: 768px) {
      .card-spacing {
        padding: 1.5rem;
      }
    }

    @media (min-width: 1024px) {
      .card-spacing {
        padding: 2rem;
      }
    }

    /* Hide/show elements based on screen size */
    .mobile-only {
      display: block;
    }

    .desktop-only {
      display: none;
    }

    @media (min-width: 768px) {
      .mobile-only {
        display: none;
      }
      
      .desktop-only {
        display: block;
      }
    }

    /* Smooth transitions */
    .smooth-transition {
      transition: all 0.3s ease;
    }
  </style>
</head>
<body class="bg-gray-900 text-white">
  <!-- Mobile Overlay -->
  <div class="overlay" id="sidebarOverlay"></div>
  
  <!-- Mobile Hamburger Menu -->
  <button class="hamburger p-3 rounded-lg shadow-lg z-1100 mobile-only">
    <i class="fas fa-bars text-yellow-500 text-xl"></i>
  </button>

  <!-- Sidebar -->
  <div class="sidebar glass-card p-5 z-1000">
    <div class="flex items-center mb-8">
      <div class="w-10 h-10 bg-gradient-to-r from-yellow-500 to-orange-500 rounded-lg flex items-center justify-center mr-3">
        <i class="fas fa-cogs text-gray-900"></i>
      </div>
      <div>
        <h2 class="text-xl font-bold gold-text">JAJR Company</h2>
        <p class="text-gray-400 text-sm">Engineering</p>
      </div>
    </div>
    
    <nav class="space-y-2">
      <a href="dashboard.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-800 text-gray-300 hover:text-white smooth-transition">
        <i class="fas fa-tachometer-alt w-5"></i>
        <span>Dashboard</span>
      </a>
      <a href="attendance.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-800 text-gray-300 hover:text-white smooth-transition">
        <i class="fas fa-calendar-check w-5"></i>
        <span>Daily Attendance</span>
      </a>
      <a href="employees.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-800 text-gray-300 hover:text-white smooth-transition">
        <i class="fas fa-users w-5"></i>
        <span>Employee List</span>
      </a>
      <a href="analytics.php" class="flex items-center space-x-3 p-3 rounded-lg bg-gray-800 text-white smooth-transition">
        <i class="fas fa-chart-line w-5"></i>
        <span>Analytics</span>
      </a>
      <a href="tasks.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-800 text-gray-300 hover:text-white smooth-transition">
        <i class="fas fa-tasks w-5"></i>
        <span>My Tasks</span>
      </a>
      <a href="settings.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-800 text-gray-300 hover:text-white smooth-transition">
        <i class="fas fa-cog w-5"></i>
        <span>Settings</span>
      </a>
      <a href="../logout.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-red-900/20 text-red-400 hover:text-red-300 smooth-transition">
        <i class="fas fa-sign-out-alt w-5"></i>
        <span>Log Out</span>
      </a>
    </nav>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Header -->
    <div class="flex justify-between items-start mb-6 responsive-flex">
      <div class="mb-4 md:mb-0">
        <h1 class="text-2xl md:text-3xl font-bold gold-text text-scale-lg">Attendance Analytics</h1>
        <p class="text-gray-400 text-sm md:text-base">Monitor and analyze employee attendance patterns</p>
      </div>
      <div class="flex flex-wrap gap-2 md:space-x-3">
        <button onclick="window.print()" class="gold-gradient px-4 md:px-6 py-2.5 rounded-lg font-semibold flex items-center space-x-2 btn">
          <i class="fas fa-print"></i>
          <span class="hidden sm:inline">Print Report</span>
          <span class="sm:hidden">Print</span>
        </button>
      </div>
    </div>

    <!-- Date Filter Bar -->
    <div class="glass-card p-4 md:p-6 mb-6 card-spacing">
      <h2 class="text-base md:text-lg font-semibold gold-text mb-3 md:mb-4">Filter by Date Range</h2>
      <form method="GET" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-1 md:mb-2">Start Date</label>
            <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" 
                   class="w-full">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-1 md:mb-2">End Date</label>
            <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" 
                   class="w-full">
          </div>
        </div>
        <div class="flex flex-wrap gap-2 md:gap-3 pt-2">
          <button type="submit" class="gold-gradient px-4 md:px-8 py-2.5 rounded-lg font-semibold flex items-center space-x-2 btn flex-1 md:flex-none">
            <i class="fas fa-filter"></i>
            <span>Apply Filter</span>
          </button>
          <a href="analytics.php" class="bg-gray-800 hover:bg-gray-700 px-4 md:px-8 py-2.5 rounded-lg font-semibold flex items-center space-x-2 btn flex-1 md:flex-none">
            <i class="fas fa-redo"></i>
            <span>Reset</span>
          </a>
        </div>
      </form>
    </div>

    <!-- Stats Cards -->
    <div class="responsive-grid mb-6">
      <div class="stat-card p-4 md:p-6 rounded-xl">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs md:text-sm text-gray-400">Total Employees</p>
            <p class="text-2xl md:text-3xl font-bold text-white mt-1 md:mt-2"><?php echo $stats['total_employees']; ?></p>
          </div>
          <div class="p-2 md:p-3 rounded-lg bg-yellow-500/10">
            <i class="fas fa-users text-yellow-500 text-lg md:text-xl"></i>
          </div>
        </div>
      </div>
      
      <div class="stat-card p-4 md:p-6 rounded-xl">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs md:text-sm text-gray-400">Present Days</p>
            <p class="text-2xl md:text-3xl font-bold text-green-400 mt-1 md:mt-2"><?php echo $stats['total_present']; ?></p>
          </div>
          <div class="p-2 md:p-3 rounded-lg bg-green-500/10">
            <i class="fas fa-check-circle text-green-500 text-lg md:text-xl"></i>
          </div>
        </div>
      </div>
      
      <div class="stat-card p-4 md:p-6 rounded-xl">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs md:text-sm text-gray-400">Absent Days</p>
            <p class="text-2xl md:text-3xl font-bold text-red-400 mt-1 md:mt-2"><?php echo $stats['total_absent']; ?></p>
          </div>
          <div class="p-2 md:p-3 rounded-lg bg-red-500/10">
            <i class="fas fa-times-circle text-red-500 text-lg md:text-xl"></i>
          </div>
        </div>
      </div>
      
      <div class="stat-card p-4 md:p-6 rounded-xl">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs md:text-sm text-gray-400">Avg Attendance</p>
            <p class="text-2xl md:text-3xl font-bold text-yellow-400 mt-1 md:mt-2"><?php echo round($stats['avg_attendance'], 1); ?>%</p>
          </div>
          <div class="p-2 md:p-3 rounded-lg bg-yellow-500/10">
            <i class="fas fa-chart-line text-yellow-500 text-lg md:text-xl"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6 lg:gap-8 mb-6">
      <!-- Daily Presence Chart -->
      <div class="glass-card p-4 md:p-6">
        <h2 class="text-base md:text-lg font-semibold gold-text mb-4 md:mb-6">Daily Presence Overview</h2>
        <div class="chart-container">
          <canvas id="dailyChart"></canvas>
        </div>
      </div>
      
      <!-- Attendance Distribution Chart -->
      <div class="glass-card p-4 md:p-6">
        <h2 class="text-base md:text-lg font-semibold gold-text mb-4 md:mb-6">Attendance Distribution</h2>
        <div class="chart-container">
          <canvas id="distributionChart"></canvas>
        </div>
      </div>
    </div>

    <!-- Detailed Table -->
    <div class="glass-card overflow-hidden">
      <div class="p-4 md:p-6 border-b border-gold-border">
        <h2 class="text-base md:text-lg font-semibold gold-text">Detailed Attendance Report</h2>
        <p class="text-gray-400 text-xs md:text-sm">
          <?php echo date('F j, Y', strtotime($start_date)); ?> to <?php echo date('F j, Y', strtotime($end_date)); ?>
        </p>
      </div>
      
      <div class="responsive-table p-2 md:p-4">
        <div class="table-wrapper">
          <table class="w-full">
            <thead class="table-header">
              <tr>
                <th class="p-3 md:p-4 text-left text-xs md:text-sm font-semibold text-gray-300">Employee</th>
                <th class="p-3 md:p-4 text-left text-xs md:text-sm font-semibold text-gray-300">Code</th>
                <th class="p-3 md:p-4 text-left text-xs md:text-sm font-semibold text-gray-300">Present</th>
                <th class="p-3 md:p-4 text-left text-xs md:text-sm font-semibold text-gray-300">Absent</th>
                <th class="p-3 md:p-4 text-left text-xs md:text-sm font-semibold text-gray-300">Total</th>
                <th class="p-3 md:p-4 text-left text-xs md:text-sm font-semibold text-gray-300">Attendance Rate</th>
                <th class="p-3 md:p-4 text-left text-xs md:text-sm font-semibold text-gray-300">Status</th>
              </tr>
            </thead>
            <tbody>
            <?php while ($row = mysqli_fetch_assoc($res)): ?>
              <?php
                $present = intval($row['present_count']);
                $absent = intval($row['absent_count']);
                $total = $present + $absent;
                $percentage = $total > 0 ? round(($present / $total) * 100, 1) : 0;
                
                if ($percentage >= 95) {
                  $status_class = 'excellent';
                  $status_text = 'Excellent';
                } elseif ($percentage >= 85) {
                  $status_class = 'good';
                  $status_text = 'Good';
                } elseif ($percentage >= 70) {
                  $status_class = 'fair';
                  $status_text = 'Fair';
                } else {
                  $status_class = 'poor';
                  $status_text = 'Review';
                }
              ?>
              <tr class="table-row border-b border-gray-800 hover:bg-gray-800/50 transition-colors">
                <td class="p-3 md:p-4">
                  <div class="font-medium text-white text-sm md:text-base truncate max-w-[120px] md:max-w-none">
                    <?php echo htmlspecialchars($row['last_name'] . ', ' . $row['first_name']); ?>
                  </div>
                </td>
                <td class="p-3 md:p-4 text-gray-400 text-sm"><?php echo htmlspecialchars($row['employee_code']); ?></td>
                <td class="p-3 md:p-4 text-green-400 font-semibold text-sm md:text-base"><?php echo $present; ?></td>
                <td class="p-3 md:p-4 text-red-400 font-semibold text-sm md:text-base"><?php echo $absent; ?></td>
                <td class="p-3 md:p-4 text-gray-400 text-sm md:text-base"><?php echo $total; ?></td>
                <td class="p-3 md:p-4">
                  <div class="flex items-center space-x-2">
                    <div class="progress-bar">
                      <div class="progress-fill" style="width: <?php echo min($percentage, 100); ?>%"></div>
                    </div>
                    <span class="font-semibold text-xs md:text-sm <?php echo $percentage >= 85 ? 'text-green-400' : ($percentage >= 70 ? 'text-yellow-400' : 'text-red-400'); ?>">
                      <?php echo $percentage; ?>%
                    </span>
                  </div>
                </td>
                <td class="p-3 md:p-4">
                  <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                </td>
              </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
        </div>
        
        <?php if (mysqli_num_rows($res) == 0): ?>
        <div class="text-center py-8 md:py-12">
          <i class="fas fa-database text-gray-600 text-4xl md:text-5xl mb-3 md:mb-4"></i>
          <h3 class="text-base md:text-lg font-medium text-gray-400 mb-1 md:mb-2">No attendance data found</h3>
          <p class="text-gray-500 text-sm md:text-base">No records available for the selected date range.</p>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script>
    // Mobile sidebar functionality
    const hamburger = document.querySelector('.hamburger');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const mainContent = document.querySelector('.main-content');

    function toggleSidebar() {
      sidebar.classList.toggle('active');
      overlay.classList.toggle('active');
      document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
    }

    hamburger.addEventListener('click', toggleSidebar);
    overlay.addEventListener('click', toggleSidebar);

    // Close sidebar on resize if desktop
    function handleResize() {
      if (window.innerWidth >= 768) {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
      }
    }

    window.addEventListener('resize', handleResize);

    // Initialize charts after DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
      // Chart configuration
      const createGoldGradient = (ctx) => {
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(255, 215, 0, 0.8)');
        gradient.addColorStop(0.7, 'rgba(255, 165, 0, 0.4)');
        gradient.addColorStop(1, 'rgba(255, 165, 0, 0.1)');
        return gradient;
      };

      // Daily Presence Chart
      const dailyCtx = document.getElementById('dailyChart').getContext('2d');
      if (dailyCtx) {
        const dailyGradient = createGoldGradient(dailyCtx);
        
        new Chart(dailyCtx, {
          type: 'bar',
          data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [{
              label: 'Present',
              data: <?php echo json_encode($daily_present); ?>,
              backgroundColor: dailyGradient,
              borderColor: '#FFD700',
              borderWidth: 1,
              borderRadius: 4,
              barPercentage: 0.7
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { display: false },
              tooltip: {
                backgroundColor: 'rgba(20, 20, 20, 0.9)',
                titleColor: '#FFD700',
                bodyColor: '#ffffff',
                borderColor: '#FFD700',
                borderWidth: 1
              }
            },
            scales: {
              x: {
                grid: { color: 'rgba(255, 255, 255, 0.1)' },
                ticks: { 
                  color: '#999',
                  maxRotation: 45,
                  minRotation: 45
                }
              },
              y: {
                beginAtZero: true,
                grid: { color: 'rgba(255, 255, 255, 0.1)' },
                ticks: { 
                  color: '#999',
                  callback: function(value) {
                    return value + (value === 0 ? '' : '');
                  }
                }
              }
            }
          }
        });
      }

      // Attendance Distribution Chart
      const distCtx = document.getElementById('distributionChart').getContext('2d');
      if (distCtx) {
        new Chart(distCtx, {
          type: 'doughnut',
          data: {
            labels: ['Present', 'Absent'],
            datasets: [{
              data: [<?php echo $stats['total_present']; ?>, <?php echo $stats['total_absent']; ?>],
              backgroundColor: [
                'rgba(0, 255, 0, 0.3)',
                'rgba(255, 0, 0, 0.3)'
              ],
              borderColor: ['#00FF00', '#FF4444'],
              borderWidth: 2,
              hoverOffset: 10
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: window.innerWidth < 768 ? '60%' : '70%',
            plugins: {
              legend: {
                position: 'bottom',
                labels: {
                  color: '#ffffff',
                  padding: 15,
                  font: {
                    size: window.innerWidth < 768 ? 12 : 14
                  }
                }
              },
              tooltip: {
                backgroundColor: 'rgba(20, 20, 20, 0.9)',
                titleColor: '#FFD700',
                bodyColor: '#ffffff',
                callbacks: {
                  label: function(context) {
                    const total = <?php echo $stats['total_present'] + $stats['total_absent']; ?>;
                    const percentage = total > 0 ? ((context.raw / total) * 100).toFixed(1) : 0;
                    return `${context.label}: ${context.raw} (${percentage}%)`;
                  }
                }
              }
            }
          }
        });
      }

      // Add responsive behavior to charts on window resize
      let resizeTimer;
      window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
          // Charts will automatically resize due to Chart.js responsive options
        }, 250);
      });
    });

    // Touch and swipe support for mobile
    let touchStartX = 0;
    let touchEndX = 0;

    document.addEventListener('touchstart', function(e) {
      touchStartX = e.changedTouches[0].screenX;
    });

    document.addEventListener('touchend', function(e) {
      touchEndX = e.changedTouches[0].screenX;
      handleSwipe();
    });

    function handleSwipe() {
      const swipeThreshold = 50;
      const swipeDistance = touchEndX - touchStartX;

      // Swipe left to close sidebar
      if (sidebar.classList.contains('active') && swipeDistance < -swipeThreshold) {
        toggleSidebar();
      }
      // Swipe right to open sidebar
      else if (!sidebar.classList.contains('active') && swipeDistance > swipeThreshold && window.innerWidth < 768) {
        toggleSidebar();
      }
    }
  </script>
</body>
</html>