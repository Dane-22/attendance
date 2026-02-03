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

</head>
<body>
  <div class="app-shell">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="main-content">
      <!-- Success/Error Messages -->
      <div id="successMessage" class="success-message"></div>
      <div id="errorMessage" class="error-message"></div>

      <!-- DEBUG INFO - Press Ctrl+Shift+D to show -->
      <div id="debugInfo" style="background: red; color: white; padding: 10px; margin-bottom: 10px; display: none;">
          Debug Info:<br>
          User Role: "<?php echo $userRole; ?>"<br>
          Position: <?php echo $position; ?><br>
          Time: <?php echo $currentTime; ?> (PH Time)<br>
          Timezone: <?php echo date_default_timezone_get(); ?>
      </div>

      <!-- Header -->
      <div class="header-card">
        <div class="header-left">
          <div>
            <div class="welcome">Select Employee for Attendance</div>
            <div class="text-sm text-gray">
                Employee Code: <strong><?php echo htmlspecialchars($employeeCode); ?></strong> 
                Position: <?php echo htmlspecialchars($position); ?>
            </div>
          </div>
        </div>
        <div class="text-sm text-gray">
            Today (PH): <?php echo date('F d, Y'); ?><br>
            Current Time (PH): <?php echo $currentTime; ?>
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

      <!-- Branch Selection -->
      <div class="branch-selection">
        <div class="branch-header">
          <div class="branch-title">Select Deployment Branch</div>
          <!-- DEBUG: Always show Add Branch button -->
          <button class="btn-add-branch" id="addBranchBtn" title="Add new branch">
            <i class="fas fa-plus"></i> Add Branch
          </button>
        </div>
        <div class="branch-grid" id="branchGrid">
          <?php foreach ($branches as $branch): ?>
          <div class="branch-card" data-branch-id="<?php echo htmlspecialchars($branch['id']); ?>" data-branch="<?php echo htmlspecialchars($branch['branch_name']); ?>">
            <!-- DEBUG: Always show delete button -->
            <button class="btn-remove-branch" onclick="removeBranch(<?php echo htmlspecialchars($branch['id']); ?>, '<?php echo htmlspecialchars($branch['branch_name']); ?>')" title="Delete branch">
              <i class="fas fa-times"></i>
            </button>
            <div class="branch-name"><?php echo htmlspecialchars($branch['branch_name']); ?></div>
            <div class="branch-desc">Deploy employees to this branch for attendance</div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Add Branch Modal -->
      <div id="addBranchModal" class="modal-backdrop">
        <div class="modal-panel" style="width: 420px;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="margin: 0; color: #FFD700; font-size: 18px;">Add New Branch</h3>
            <button onclick="closeAddBranchModal()" style="background: none; border: none; color: #888; font-size: 24px; cursor: pointer; padding: 0;">
              <i class="fas fa-times"></i>
            </button>
          </div>
          
          <form id="addBranchForm" onsubmit="submitAddBranch(event)">
            <div class="form-row">
              <label style="font-size: 12px; color: #FFD700; font-weight: 600; margin-bottom: 6px; display: block;">Branch Name</label>
              <input 
                type="text" 
                id="branchNameInput" 
                name="branch_name" 
                placeholder="Enter branch name (e.g., Main Office, Branch A)" 
                required 
                style="background: transparent; border: 1px solid rgba(255,255,255,0.04); padding: 0.6rem 0.75rem; border-radius: 8px; color: #ffffff; width: 100%;"
              />
              <small style="color: #888; font-size: 11px; margin-top: 4px; display: block;">Branch names must be unique and 2-255 characters</small>
            </div>

            <div style="display: flex; gap: 8px; margin-top: 16px; justify-content: flex-end;">
              <button type="button" onclick="closeAddBranchModal()" style="background: transparent; border: 1px solid rgba(255,255,255,0.1); color: #888; padding: 0.6rem 1rem; border-radius: 6px; cursor: pointer; font-weight: 600;">
                Cancel
              </button>
              <button type="submit" style="background: #FFD700; border: none; color: #0b0b0b; padding: 0.6rem 1rem; border-radius: 6px; cursor: pointer; font-weight: 600;">
                <i class="fas fa-plus"></i> Add Branch
              </button>
            </div>
          </form>
        </div>
      </div>

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
      
      <div class="potanginamo" style="display: flex; justify-content: space-between; align-items: center;">
      <!-- Filter Options -->
      <div class="filter-options-container" style="width: 54%;">
        <div class="filter-options-title">Filters:</div>
        <div class="filter-options">
          <div class="status-filter">
            <!-- <label for="statusFilter" style="font-size: 12px; color: #888; margin-bottom: 4px; display: block;">Filter by Status:</label> -->
            <select id="statusFilter" class="status-filter-select">
              <option value="available">Available (Not Marked)</option>
              <option value="all">All Employees</option>
              <option value="present">Present</option>
            </select>
          </div>
          
          <!-- Hide this toggle since we have status filter now -->
          <div class="toggle-switch" style="display: none;">
            <span class="toggle-label">Show All Employees</span>
            <label class="toggle">
              <input type="checkbox" id="showMarkedToggle">
              <span class="slider"></span>
            </label>
          </div>
          
          <div class="view-options">
            <button class="view-option-btn active" data-view="list" title="List View">
              <i class="fas fa-list"></i>
              <span>List View</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Search Bar -->
      <div class="search-container" style="width: 45%;">
        <input type="text" id="searchInput" class="search-input" placeholder="Search employees by name or ID..." disabled style="width: 100%; max-width: 100%;">
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
          <div>Please select a deployment branch to view all available employees</div>
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
    </main>
  </div>

  <script src="../assets/js/sidebar-toggle.js"></script>
  <script src="js/attendance.js"></script>
</body>
</html>