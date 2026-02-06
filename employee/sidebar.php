<?php
// employee/sidebar.php
// Determine active page
$current = basename($_SERVER['PHP_SELF']);

// Get user role from session
$userRole = isset($_SESSION['position']) ? $_SESSION['position'] : 'Employee';

// Check if user is Admin or Super Admin
$isAdmin = in_array($userRole, ['Admin', 'Super Admin']);
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

  <!-- All Users: Employee List -->
  <a href="employees.php" class="menu-item <?= $current === 'employees.php' ? 'active' : '' ?>" data-target="employees.php"><span class="icon">👥</span><span class="label">Employee List</span></a>

  <!-- Admin/Super Admin Only: Reports -->
  <?php if ($isAdmin): ?>
    <a href="weekly_report.php" class="menu-item <?= $current === 'weekly_report.php' ? 'active' : '' ?>" data-target="weekly_report.php"><span class="icon">📅</span><span class="label">Reports</span></a>
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

  <!-- Admin/Super Admin Only: Settings -->
   <!-- ALL USERS: Settings (Visible to Everyone) -->
  <a href="settings.php" class="menu-item <?= $current === 'settings.php' ? 'active' : '' ?>" data-target="settings.php"><span class="icon">⚙️</span><span class="label">Settings</span></a>


  <div style="flex:1"></div>
  <a href="../logout.php" class="menu-item logout"><span class="icon">🚪</span><span class="label">Log Out</span></a>
  </aside>
  <script src="../assets/js/main.js"></script>
