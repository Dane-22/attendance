<?php
// employee/sidebar.php
// Determine active page
$current = basename($_SERVER['PHP_SELF']);

// Get user role from session
$userRole = isset($_SESSION['position']) ? $_SESSION['position'] : 'Employee';

// Check if user is Admin or Super Admin
$isAdmin = in_array($userRole, ['Admin', 'Super Admin']);
$isSuperAdmin = ($userRole === 'Super Admin');

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
    
  <!-- Engineer Only: Dashboard -->
  <?php if ($userRole === 'Engineer' || $userRole === 'Admin'): ?>
    <a href="eng_dashboard.php" class="menu-item <?= $current === 'eng_dashboard.php' ? 'active' : '' ?>" data-target="eng_dashboard.php"><span class="icon">🏗️</span><span class="label">Dashboard</span></a>
  <?php endif; ?>

  <!-- Admin/Super Admin Only: Dashboard -->
  <?php if ($isSuperAdmin): ?>
    <a href="dashboard.php" class="menu-item <?= $current === 'dashboard.php' ? 'active' : '' ?>" data-target="dashboard.php"><span class="icon">🏠</span><span class="label">Dashboard</span></a>

  <?php endif; ?>

  <!-- All Users: Site Attendance -->
  <a href="select_employee.php" class="menu-item <?= $current === 'select_employee.php' ? 'active' : '' ?>" data-target="select_employee.php"><span class="icon">📋</span><span class="label">Site Attendance</span></a>

  <!-- Super Admin Only: Overtime Request Management -->
<?php if ($isSuperAdmin): ?>
    <a href="notification.php" class="menu-item <?= $current === 'notification.php' ? 'active' : '' ?>" data-target="notification.php">
      <span class="icon">🔔</span>
      <span class="label">Notification</span>
      <?php if ($pendingOvertimeCount > 0): ?>
        <span class="notification-badge"><?php echo $pendingOvertimeCount; ?></span>
      <?php endif; ?>
    </a>
<?php endif; ?>

  <!-- Admin Only: View Notifications (read-only) -->
  <?php if ($userRole === 'Admin'): ?>
    <a href="admin_notification.php" class="menu-item <?= $current === 'admin_notification.php' ? 'active' : '' ?>" data-target="admin_notification.php">
      <span class="icon">🔔</span>
      <span class="label">Notifications</span>
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

 

  <!-- Admin/Super Admin Only: Documents -->
  <?php if ($isAdmin): ?>
    <a href="documents.php" class="menu-item <?= $current === 'documents.php' ? 'active' : '' ?>" data-target="documents.php"><span class="icon">🏥</span><span class="label">Documents</span></a>

  <?php endif; ?>

  <!-- Admin/Super Admin Only: Activity Logs -->
  <?php if ($isAdmin): ?>
    <a href="logs.php" class="menu-item <?= $current === 'logs.php' ? 'active' : '' ?>" data-target="logs.php"><span class="icon">🗂️</span><span class="label">Activity Logs</span></a>

  <?php endif; ?>

  <!-- Admin/Super Admin Only: Finance Dropdown -->
  <?php if ($isAdmin): ?>
    <div class="menu-dropdown">
      <button class="menu-item dropdown-toggle <?= in_array($current, ['weekly_report.php', 'overtime.php', 'billing.php', 'cash_advance.php']) ? 'active' : '' ?>" onclick="toggleDropdown(this)">
        <span class="icon">💰</span>
        <span class="label">Finance</span>
        <span class="dropdown-arrow">▼</span>
      </button>
      <div class="dropdown-menu <?= in_array($current, ['weekly_report.php', 'overtime.php', 'billing.php', 'cash_advance.php']) ? 'show' : '' ?>">
        <a href="weekly_report.php" class="dropdown-item <?= $current === 'weekly_report.php' ? 'active' : '' ?>" data-target="weekly_report.php">Weekly Report</a>
        <a href="overtime.php" class="dropdown-item <?= $current === 'overtime.php' ? 'active' : '' ?>" data-target="overtime.php">Overtime</a>
        <a href="billing.php" class="dropdown-item <?= $current === 'billing.php' ? 'active' : '' ?>" data-target="billing.php">Billing</a>
        <a href="cash_advance.php" class="dropdown-item <?= $current === 'cash_advance.php' ? 'active' : '' ?>" data-target="cash_advance.php">Cash Advance</a>
      </div>
    </div>
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

  <!-- Dropdown Styles -->
  <style>
    .menu-dropdown {
      position: relative;
    }
    .dropdown-toggle {
      width: 100%;
      background: none;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .dropdown-arrow {
      margin-left: auto;
      font-size: 10px;
      transition: transform 0.2s ease;
    }
    .dropdown-toggle.active .dropdown-arrow {
      transform: rotate(180deg);
    }
    .dropdown-menu {
      display: none;
      background: rgba(0,0,0,0.1);
      padding: 5px 0;
    }
    .dropdown-menu.show {
      display: block;
    }
    .dropdown-item {
      display: block;
      padding: 10px 15px 10px 45px;
      color: var(--text-primary, #E5E7EB);
      text-decoration: none;
      font-size: 15px;
      border-left: 3px solid transparent;
    }
    .dropdown-toggle .label {
      font-size: 16px;
      font-weight: 600;
    }
    .dropdown-item:hover {
      background: rgba(255,255,255,0.1);
    }
    .dropdown-item.active {
      background: rgba(255,255,255,0.15);
      border-left-color: var(--gold-2, #FFD700);
      color: var(--gold-2, #FFD700);
    }
  </style>

  <!-- Dropdown Toggle Script -->
  <script>
    function toggleDropdown(button) {
      const dropdown = button.nextElementSibling;
      const arrow = button.querySelector('.dropdown-arrow');
      dropdown.classList.toggle('show');
      arrow.style.transform = dropdown.classList.contains('show') ? 'rotate(180deg)' : 'rotate(0deg)';
    }
  </script>

  <script src="../assets/js/main.js"></script>

