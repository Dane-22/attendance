<?php
// employee/select_employee.php
session_start();

// ===== SET PHILIPPINE TIME ZONE =====
date_default_timezone_set('Asia/Manila'); // Philippine Time (UTC+8)

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Check if this is an AJAX request
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Session expired. Please refresh the page and login again.']);
        exit();
    } else {
        header('Location: ../login.php');
        exit();
    }
}

require('../conn/db_connection.php');
require('function/attendance.php');
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Select Employee — JAJR Attendance</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="icon" type="image/x-icon" href="../assets/img/profile/jajr-logo.png">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="css/select_employee.css">
  <link rel="stylesheet" href="css/light-theme.css">
  <script src="js/theme.js"></script>

</head>
<body>
  <div class="app-shell">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="main-content">
      <!-- Success/Error Messages -->
      <div id="successMessage" class="success-message"></div>
      <div id="errorMessage" class="error-message"></div>

      <div class="welcome-banner" id="welcomeBanner">
        <div class="welcome-banner-left">Welcome! Please select a project to start!</div>
        <div class="welcome-banner-right">
          <span class="welcome-banner-date"><?php echo date('F j, Y'); ?></span>
          <span class="welcome-banner-time"><?php echo htmlspecialchars($currentTime); ?></span>
        </div>
      </div>

      <div id="undoSnackbar" class="undo-snackbar" aria-live="polite" style="display: none;">
        <div class="undo-snackbar-text" id="undoSnackbarText"></div>
        <button type="button" class="undo-snackbar-close" id="undoSnackbarClose" aria-label="Close">&times;</button>
      </div>

      <!-- DEBUG INFO - Press Ctrl+Shift+D to show -->
      <div id="debugInfo" style="background: red; color: white; padding: 10px; margin-bottom: 10px; display: none;">
          Debug Info:<br>
          User Role: "<?php echo $userRole; ?>"<br>
          Position: <?php echo $position; ?><br>
          Time: <?php echo $currentTime; ?> (PH Time)<br>
          Timezone: <?php echo date_default_timezone_get(); ?>
      </div>

      <div class="branch-stats" id="branchStats" aria-live="polite">
        <div class="stat-card">
          <div class="stat-label">Total Workers</div>
          <div class="stat-value" id="statTotalWorkers">--</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Present</div>
          <div class="stat-value" id="statPresent">--</div>
          <div class="stat-list" id="statPresentList"></div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Absent</div>
          <div class="stat-value" id="statAbsent">--</div>
          <div class="stat-list" id="statAbsentList"></div>
        </div>
      </div>

      <!-- Time Alert -->
      <!-- <div class="time-alert <?php echo $isBeforeCutoff ? 'before-cutoff' : 'after-cutoff'; ?>">
        <?php if ($isBeforeCutoff): ?>
          <i class="fas fa-clock"></i>
          <div class="time-alert-content">
            <div class="time-alert-title">Before 9:00 AM Cutoff (Philippine Time)</div>
            <div class="time-alert-message">
              Current Philippine Time: <strong><?php echo $currentTime; ?></strong> | 
              Mark employees as Present before 9:00 AM (PH Time). After cutoff, unmarked employees will be automatically marked as Absent.
            </div>
          </div>
        <?php else: ?>
          <i class="fas fa-exclamation-triangle"></i>
          <div class="time-alert-content">
            <div class="time-alert-title">After 9:00 AM Cutoff (Philippine Time)</div>
            <div class="time-alert-message">
              Current Philippine Time: <strong><?php echo $currentTime; ?></strong> | 
              Unmarked employees have been automatically marked as Absent. You can still override to mark as Present (Late).
            </div>
          </div>
        <?php endif; ?>
      </div> -->

      <!-- Project Selection -->
      <div class="branch-selection">
        <div class="branch-header">
          <div class="branch-title">Select Deployment Project</div>
          <?php if (($_SESSION['position'] ?? '') === 'Super Admin'): ?>
            <button class="btn-add-branch" id="addBranchBtn" title="Add new project">
              <i class="fas fa-plus"></i> Add Project
            </button>
          <?php endif; ?>
        </div>
        <div class="branch-tools">
          <div class="branch-search">
            <input type="text" id="branchSearchInput" class="branch-search-input" placeholder="Search projects..." autocomplete="off" />
          </div>
          <div class="branch-pager" id="branchPager"></div>
        </div>
        <div class="branch-grid" id="branchGrid">
          <?php foreach ($branches as $branch): ?>
          <div class="branch-card" data-branch-id="<?php echo htmlspecialchars($branch['id']); ?>" data-branch="<?php echo htmlspecialchars($branch['branch_name']); ?>">
            <button class="btn-remove-branch" onclick="removeBranch(event, <?php echo htmlspecialchars($branch['id']); ?>, '<?php echo htmlspecialchars($branch['branch_name']); ?>')" title="Delete project">
              <i class="fas fa-times"></i>
            </button>
            <div class="branch-name"><?php echo htmlspecialchars($branch['branch_name']); ?></div>
            <div class="branch-desc">Deploy employees to this project for attendance</div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Add Project Modal -->
      <?php if (($_SESSION['position'] ?? '') === 'Super Admin'): ?>
        <div id="addBranchModal" class="modal-backdrop">
          <div class="modal-panel" style="width: 420px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
              <h3 style="margin: 0; color: #FFD700; font-size: 18px;">Add New Project</h3>
              <button onclick="closeAddBranchModal()" style="background: none; border: none; color: #888; font-size: 24px; cursor: pointer; padding: 0;">
                <i class="fas fa-times"></i>
              </button>
            </div>
            
            <form id="addBranchForm" onsubmit="submitAddBranch(event)">
              <div class="form-row">
                <label style="font-size: 12px; color: #FFD700; font-weight: 600; margin-bottom: 6px; display: block;">Project Name</label>
                <input 
                  type="text" 
                  id="branchNameInput" 
                  name="branch_name" 
                  placeholder="Enter project name (e.g., Main Office, Project A)" 
                  required 
                  style="background: transparent; border: 1px solid rgba(255,255,255,0.04); padding: 0.6rem 0.75rem; border-radius: 8px; color: #ffffff; width: 100%;"
                />
                <small style="color: #888; font-size: 11px; margin-top: 4px; display: block;">Project names must be unique and 2-255 characters</small>
              </div>

              <div style="display: flex; gap: 8px; margin-top: 16px; justify-content: flex-end;">
                <button type="button" onclick="closeAddBranchModal()" style="background: transparent; border: 1px solid rgba(255,255,255,0.1); color: #888; padding: 0.6rem 1rem; border-radius: 6px; cursor: pointer; font-weight: 600;">
                  Cancel
                </button>
                <button type="submit" style="background: #FFD700; border: none; color: #0b0b0b; padding: 0.6rem 1rem; border-radius: 6px; cursor: pointer; font-weight: 600;">
                  <i class="fas fa-plus"></i> Add Project
                </button>
              </div>
            </form>
          </div>
        </div>
      <?php endif; ?>

      <div id="timeLogsModal" class="modal-backdrop">
        <div class="modal-panel" style="width: 520px;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 id="timeLogsTitle" style="margin: 0; color: #FFD700; font-size: 18px;">Time Logs Today</h3>
            <button onclick="closeTimeLogsModal()" style="background: none; border: none; color: #888; font-size: 24px; cursor: pointer; padding: 0;">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div id="timeLogsBody" class="time-logs-body">Loading...</div>
        </div>
      </div>
      
      <div class="potanginamo">
      <!-- Filter Options -->
      <div class="filter-options-container">
        <div class="filter-options">
          <div class="status-filter">
            <div class="status-filter-buttons" id="statusFilterButtons" role="group" aria-label="Filter by status">
              <button type="button" class="status-pill active" data-status="available">Available</button>
              <button type="button" class="status-pill" data-status="all">Summary</button>
              <button type="button" class="status-pill" data-status="present">Present</button>
              <button type="button" class="status-pill" data-status="absent">Absent</button>
            </div>
          </div>
          
          <!-- Hide this toggle since we have status filter now -->
          <div class="toggle-switch" style="display: none;">
            <span class="toggle-label">Show All Employees</span>
            <label class="toggle">
              <input type="checkbox" id="showMarkedToggle">
              <span class="slider"></span>
            </label>
          </div>
          
          
        </div>
      </div>

      <!-- Search & Undo Row -->
      <div class="search-undo-row">
        <!-- Search Bar -->
        <div class="search-container">
          <input type="text" id="searchInput" class="search-input" placeholder="Search employees by name or ID..." style="max-width: 100%;">
        </div>

        <!-- Global Undo Button -->
        <div id="globalUndoContainer" class="undo-container" style="display: flex;">
          <button id="btnGlobalUndo" class="btn-global-undo" title="Undo last action">
            <i class="fas fa-rotate-left"></i>
            <span>Undo</span>
          </button>
        </div>
      </div>

      <!-- Pagination Top -->
      <div id="paginationTop" class="pagination-container" style="display: none;">
        <div class="pagination-info">
          Showing <strong id="paginationFrom">0</strong> to <strong id="paginationTo">0</strong> of <strong id="paginationTotal">0</strong> employees
        </div>
        <div class="pagination-controls">
          <div class="page-size-selector">
            <span class="page-size-label">Show:</span>
            <select id="pageSizeSelect" class="page-size-select" onchange="changePageSize(this.value)">
              <option value="10">10</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
          </div>
          <div id="paginationButtonsTop" class="pagination-buttons">
            <!-- Pagination buttons will be generated here -->
          </div>
        </div>
      </div>

      <!-- Employee List -->
      <div id="employeeContainer">
        <div class="no-employees">
          <i class="fas fa-users" style="font-size: 36px; color: #444; margin-bottom: 10px;"></i>
          <div>Please select a deployment project to view all available employees</div>
        </div>
      </div>

      <!-- Pagination Bottom -->
      <div id="paginationBottom" class="pagination-container" style="display: none;">
        <div class="pagination-info">
          Page <strong id="currentPage">1</strong> of <strong id="totalPages">1</strong>
        </div>
        <div class="pagination-controls">
          <div class="page-size-selector">
            <span class="page-size-label">Show:</span>
            <select id="pageSizeSelectBottom" class="page-size-select" onchange="changePageSize(this.value)">
              <option value="10">10</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
          </div>
          <div id="paginationButtonsBottom" class="pagination-buttons">
            <!-- Pagination buttons will be generated here -->
          </div>
          <div class="page-jump">
            <input type="number" id="pageJumpInput" class="page-jump-input" min="1" value="1" placeholder="Page">
            <button class="page-jump-btn" onclick="jumpToPage()">Go</button>
          </div>
        </div>
      </div>

      <!-- Quick Tips -->
      <div class="quick-tips-container">
        <div class="quick-tips-header">
          <i class="fas fa-lightbulb"></i>
          <span>Quick Tips</span>
        </div>
        <ul class="quick-tips-list">
          <li><strong>Select a Project:</strong> You must select a deployment project first to view and manage its employees.</li>
          <li><strong>Marking Attendance:</strong> Use the <span style="color: #16a34a;">Time In</span> and <span style="color: #dc2626;">Mark Absent</span> buttons to record daily attendance.</li>
          <li><strong>Search:</strong> You can search for specific employees within the selected project by name or ID.</li>
          <li><strong>Filters:</strong> Use the status pills (Available, Present, etc.) to quickly organize your view.</li>
          <li><strong>Undo:</strong> If you make a mistake, look for the "Undo" button in the right side of the employee search.</li>
        </ul>
      </div>
    </main>
  </div>

  <script>
    window.attendanceConfig = {
      isBeforeCutoff: <?php echo $isBeforeCutoff ? 'true' : 'false'; ?>,
      cutoffTime: <?php echo json_encode($cutoffTime); ?>,
      currentTime: <?php echo json_encode($currentTime); ?>
    };
    window.branchesFromPHP = <?php echo json_encode($branches); ?>;
  </script>
  <script src="../assets/js/sidebar-toggle.js"></script>
  <script src="js/attendance.js"></script>



</body>
</html>