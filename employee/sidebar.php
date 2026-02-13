<?php
// employee/sidebar.php
// Determine active page
$current = basename($_SERVER['PHP_SELF']);

// Get user role from session
$userRole = isset($_SESSION['position']) ? $_SESSION['position'] : 'Employee';

// Check if user is Admin or Super Admin
$isAdmin = in_array($userRole, ['Admin', 'Super Admin']);

// Get pending count for badge (function defined in notification.php if included)
$pendingOvertimeCount = 0;
if ($isAdmin && function_exists('getPendingOvertimeCount') && isset($db)) {
    $pendingOvertimeCount = getPendingOvertimeCount($db);
}

// Helper function to get unread notification count for employees
function getUnreadNotificationCount($db, $employeeId) {
    if (!$db || !$employeeId) return 0;
    // Check if table exists first
    $checkTable = @mysqli_query($db, "SHOW TABLES LIKE 'employee_notifications'");
    if (!$checkTable || mysqli_num_rows($checkTable) === 0) {
        return 0;
    }
    $sql = "SELECT COUNT(*) as cnt FROM employee_notifications WHERE employee_id = ? AND is_read = 0";
    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) return 0;
    mysqli_stmt_bind_param($stmt, 'i', $employeeId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return intval($row['cnt'] ?? 0);
}

// Get unread notification count for current user
$unreadNotifCount = 0;
if (isset($db) && isset($_SESSION['employee_id'])) {
    $unreadNotifCount = getUnreadNotificationCount($db, $_SESSION['employee_id']);
}

// Detect if we're being included from outside the employee folder
$scriptDir = dirname($_SERVER['PHP_SELF']);
$isInEmployeeFolder = strpos($scriptDir, '/employee') !== false || $scriptDir === '/main' || $scriptDir === '/main/';

// Set base path for links
$basePath = ($scriptDir === '/main' || $scriptDir === '/main/' || (!str_contains($scriptDir, 'employee') && !str_contains($scriptDir, 'procurement'))) ? 'employee/' : '';
?>
<aside class="sidebar" id="sidebar">
  <div style="display:flex;align-items:center;gap:10px;padding:8px 6px;">
    <div class="sidebar-brand">
        <div style="font-weight:700; color:var(--gold-2);">JAJR Company</div>
      <div style="font-size:12px; color:#9CA3AF;">Owned by Arzadon</div>
    </div>
  </div>
  
    <!-- Backdrop for mobile sidebar -->
    <div id="sidebarBackdrop" class="sidebar-backdrop" aria-hidden="true"></div>
  
    <!-- Floating mobile open button (visible via CSS) -->
    <button id="mobileOpenBtn" aria-label="Open menu" class="mobile-open-btn">
      <i class="fa-solid fa-bars"></i>
    </button>
    
  <!-- Admin/Super Admin Only: Dashboard -->
  <?php if ($isAdmin): ?>
    <a href="dashboard.php" class="menu-item <?= $current === 'dashboard.php' ? 'active' : '' ?>" data-target="dashboard.php"><span class="icon">🏠</span><span class="label">Dashboard</span></a>

  <?php endif; ?>

  <!-- All Users: Site Attendance -->
  <a href="select_employee.php" class="menu-item <?= $current === 'select_employee.php' ? 'active' : '' ?>" data-target="select_employee.php"><span class="icon">📋</span><span class="label">Site Attendance</span></a>

  <!-- Super Admin Only: Overtime Request Management -->
  <?php if ($isAdmin): ?>
    <a href="notification.php" class="menu-item <?= $current === 'notification.php' ? 'active' : '' ?>" data-target="notification.php">
      <span class="icon">🔔</span>
      <span class="label">Overtime Requests</span>
      <?php if ($pendingOvertimeCount > 0): ?>
        <span class="notification-badge"><?php echo $pendingOvertimeCount; ?></span>
      <?php endif; ?>
    </a>
  <?php endif; ?>

  <!-- All Users: My Notifications (Non-Super Admin only) -->
  <?php if (!$isAdmin): ?>
    <a href="my_notifications.php" class="menu-item <?= $current === 'my_notifications.php' ? 'active' : '' ?>" data-target="my_notifications.php">
      <span class="icon">📨</span>
      <span class="label">My Notifications</span>
      <?php if ($unreadNotifCount > 0): ?>
        <span class="notification-badge"><?php echo $unreadNotifCount; ?></span>
      <?php endif; ?>
    </a>
  <?php endif; ?>

  <!-- All Users: Employee List -->
  <a href="employees.php" class="menu-item <?= $current === 'employees.php' ? 'active' : '' ?>" data-target="employees.php"><span class="icon">👥</span><span class="label">Employee List</span></a>

  <!-- Admin/Super Admin Only: Reports -->
  <?php if ($isAdmin): ?>
    <a href="weekly_report.php" class="menu-item <?= $current === 'weekly_report.php' ? 'active' : '' ?>" data-target="weekly_report.php"><span class="icon">📅</span><span class="label">Payroll</span></a>

  <?php endif; ?>

  <?php if ($isAdmin): ?>
   <a href="cash_advance.php" class="menu-item <?= $current === 'cash_advance.php' ? 'active' : '' ?>" data-target="cash_advance.php"><span class="icon">💵</span><span class="label">Cash Advance</span></a>

  <?php endif; ?>

  <!-- Admin/Super Admin Only: Billing -->
  <?php if ($isAdmin): ?>
    <a href="billing.php" class="menu-item <?= $current === 'billing.php' ? 'active' : '' ?>" data-target="billing.php"><span class="icon">💰</span><span class="label">Billing</span></a>

  <?php endif; ?>

  <!-- Admin/Super Admin Only: Documents -->
  <?php if ($isAdmin): ?>
    <a href="documents.php" class="menu-item <?= $current === 'documents.php' ? 'active' : '' ?>" data-target="documents.php"><span class="icon">🏥</span><span class="label">Documents</span></a>

  <?php endif; ?>

  <!-- Admin/Super Admin Only: Activity Logs -->
  <?php if ($isAdmin): ?>
    <a href="logs.php" class="menu-item <?= $current === 'logs.php' ? 'active' : '' ?>" data-target="logs.php"><span class="icon">🗂️</span><span class="label">Activity Logs</span></a>

  <?php endif; ?>

  <!-- Admin/Super Admin/Engineer Only: Procurement (External Link) -->
  <?php if ($isAdmin || $userRole === 'Engineer'): ?>
    <a href="<?php echo $basePath; ?>procurement_redirect.php" class="menu-item"><span class="icon">🛒</span><span class="label">Procurement</span></a>
  <?php endif; ?>

  <!-- Admin/Super Admin Only: Settings -->
  <!-- ALL USERS: Settings (Visible to Everyone) -->
  <a href="settings.php" class="menu-item <?= $current === 'settings.php' ? 'active' : '' ?>" data-target="settings.php"><span class="icon">⚙️</span><span class="label">Settings</span></a>



  <div style="flex:1"></div>
  <a href="../logout.php" class="menu-item logout"><span class="icon">🚪</span><span class="label">Log Out</span></a>

  </aside>
  <script src="../assets/js/main.js"></script>

