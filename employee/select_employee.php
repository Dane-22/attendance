<?php
// employee/select_employee.php
session_start();

// ===== SET PHILIPPINE TIME ZONE =====
date_default_timezone_set('Asia/Manila'); // Philippine Time (UTC+8)

// Check if this is a QR scan auto time-in request
$isQRScan = isset($_GET['auto_timein']) && isset($_GET['emp_id']);
$isBranchSelectMode = isset($_GET['select_branch']) && $_GET['select_branch'] == '1';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // If it's a QR scan, create temporary session to allow the request
    if ($isQRScan) {
        // Create temporary authenticated session for QR scan
        $_SESSION['logged_in'] = true;
        $_SESSION['employee_id'] = intval($_GET['emp_id']);
        $_SESSION['employee_code'] = isset($_GET['emp_code']) ? $_GET['emp_code'] : '';
        $_SESSION['position'] = 'QR Scan';
        $_SESSION['qr_temp_session'] = true; // Mark as temporary
    } else {
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
}

require('../conn/db_connection.php');
require('function/attendance.php');
require('function/clock_functions.php');

// ===== QR SCAN AUTO TIME-IN/OUT (Direct Function Calls - No HTTP/cURL) =====
$qrScanResult = null;
$qrEmployeeId = isset($_GET['emp_id']) ? intval($_GET['emp_id']) : 0;
$qrEmployeeCode = isset($_GET['emp_code']) ? $_GET['emp_code'] : '';

if (isset($_GET['auto_timein']) && $qrEmployeeId) {
    if ($isBranchSelectMode) {
        // Branch selection mode: show branch selector instead of auto clock-in
        $qrScanResult = [
            'success' => true,
            'select_branch' => true,
            'message' => 'Please select your branch/project'
        ];
    } else {
        // Original auto clock-in mode
        // Fetch employee details and branch
        $empStmt = mysqli_prepare($db, "SELECT e.id, e.first_name, e.last_name, e.employee_code, b.branch_name 
            FROM employees e 
            LEFT JOIN branches b ON b.id = e.branch_id 
            WHERE e.id = ? AND e.status = 'Active' LIMIT 1");
        mysqli_stmt_bind_param($empStmt, 'i', $qrEmployeeId);
        mysqli_stmt_execute($empStmt);
        $empResult = mysqli_stmt_get_result($empStmt);
        $employee = mysqli_fetch_assoc($empResult);
        mysqli_stmt_close($empStmt);
        
        if ($employee) {
            $branchName = $employee['branch_name'] ?? 'System';
            
            // Call clock-in function directly (no HTTP/cURL)
            $clockInResult = performClockIn($db, $qrEmployeeId, $employee['employee_code'], $branchName);
            
            if ($clockInResult['success']) {
                $qrScanResult = [
                    'success' => true,
                    'message' => $employee['first_name'] . ' ' . $employee['last_name'] . ' time-in recorded at ' . ($clockInResult['time_in'] ?? date('h:i A')),
                    'time_in' => $clockInResult['time_in'] ?? null
                ];
            } else {
                $msg = $clockInResult['message'] ?? '';

                // If already clocked in, auto-trigger clock out
                if (stripos($msg, 'already clocked in') !== false) {
                    $clockOutResult = performClockOut($db, $qrEmployeeId, $employee['employee_code'], $branchName);
                    
                    if ($clockOutResult['success']) {
                        $qrScanResult = [
                            'success' => true,
                            'message' => $employee['first_name'] . ' ' . $employee['last_name'] . ' time-out recorded at ' . ($clockOutResult['time_out'] ?? date('h:i A')),
                            'time_out' => $clockOutResult['time_out'] ?? null
                        ];
                    } else {
                        $qrScanResult = [
                            'success' => false,
                            'message' => $clockOutResult['message'] ?? 'Failed to record time-out'
                        ];
                    }
                } else {
                    $qrScanResult = [
                        'success' => false,
                        'message' => $msg !== '' ? $msg : 'Failed to record time-in'
                    ];
                }
            }
        } else {
            $qrScanResult = [
                'success' => false,
                'message' => 'Employee not found'
            ];
        }
    }
}

// ===== HANDLE QR CLOCK WITH BRANCH SELECTION (AJAX) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'qr_clock_with_branch') {
    header('Content-Type: application/json');
    
    $employeeId = intval($_POST['employee_id'] ?? 0);
    $employeeCode = $_POST['employee_code'] ?? '';
    $branchName = $_POST['branch_name'] ?? '';
    $branchId = intval($_POST['branch_id'] ?? 0);
    $latitude = floatval($_POST['latitude'] ?? 0);
    $longitude = floatval($_POST['longitude'] ?? 0);
    $accuracy = floatval($_POST['accuracy'] ?? 0);
    $locationVerified = intval($_POST['location_verified'] ?? 0);
    
    if (!$employeeId || !$branchName) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit();
    }
    
    // Verify employee
    $empStmt = mysqli_prepare($db, "SELECT id, first_name, last_name FROM employees WHERE id = ? AND employee_code = ? AND status = 'Active' LIMIT 1");
    mysqli_stmt_bind_param($empStmt, 'is', $employeeId, $employeeCode);
    mysqli_stmt_execute($empStmt);
    $empResult = mysqli_stmt_get_result($empStmt);
    $employee = mysqli_fetch_assoc($empResult);
    mysqli_stmt_close($empStmt);
    
    if (!$employee) {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        exit();
    }
    
    // Check if already clocked in
    $checkSql = "SELECT id FROM attendance WHERE employee_id = ? AND attendance_date = CURDATE() AND time_in IS NOT NULL AND time_out IS NULL";
    $checkStmt = mysqli_prepare($db, $checkSql);
    mysqli_stmt_bind_param($checkStmt, 'i', $employeeId);
    mysqli_stmt_execute($checkStmt);
    mysqli_stmt_store_result($checkStmt);
    $alreadyIn = mysqli_stmt_num_rows($checkStmt) > 0;
    mysqli_stmt_close($checkStmt);
    
    $result = null;
    if ($alreadyIn) {
        // Clock out
        $result = performClockOut($db, $employeeId, $employeeCode, $branchName);
    } else {
        // Clock in with selected branch
        $result = performClockIn($db, $employeeId, $employeeCode, $branchName);
    }
    
    // If clock-in/out succeeded and we have location data, save it
    if ($result['success'] && $latitude && $longitude) {
        $shiftId = $result['shift_id'] ?? null;
        if ($shiftId) {
            // Insert into location_logs
            $logSql = "INSERT INTO location_logs 
                (employee_id, attendance_id, action_type, latitude, longitude, 
                 accuracy_meters, branch_id, is_validated, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $logStmt = mysqli_prepare($db, $logSql);
            $actionType = $alreadyIn ? 'clock_out' : 'qr_scan';
            mysqli_stmt_bind_param($logStmt, 'iissdiii', 
                $employeeId, $shiftId, $actionType, $latitude, $longitude, $accuracy, $branchId, $locationVerified);
            mysqli_stmt_execute($logStmt);
            mysqli_stmt_close($logStmt);
            
            // Update attendance record with location
            $updateSql = "UPDATE attendance 
                          SET clock_in_lat = ?, clock_in_lng = ?, 
                              location_accuracy = ?, location_verified = ?
                          WHERE id = ?";
            $updateStmt = mysqli_prepare($db, $updateSql);
            mysqli_stmt_bind_param($updateStmt, 'dddii',
                $latitude, $longitude, $accuracy, $locationVerified, $shiftId);
            mysqli_stmt_execute($updateStmt);
            mysqli_stmt_close($updateStmt);
        }
    }
    
    echo json_encode($result);
    exit();
}

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
  <style>
    .qr-result-banner {
      padding: 16px 24px;
      border-radius: 8px;
      margin: 16px 0;
      font-weight: 600;
      text-align: center;
    }
    .qr-result-banner.success {
      background: rgba(16, 185, 129, 0.2);
      border: 2px solid #10b981;
      color: #10b981;
    }
    .qr-result-banner.error {
      background: rgba(239, 68, 68, 0.2);
      border: 2px solid #ef4444;
      color: #ef4444;
    }
    
    /* QR Branch Selector Modal */
    .qr-branch-selector-modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.9);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 9999;
    }
    
    .qr-branch-selector-content {
      background: #1a1a1a;
      border: 2px solid #FFD700;
      border-radius: 16px;
      padding: 32px;
      max-width: 600px;
      width: 90%;
      max-height: 80vh;
      overflow-y: auto;
    }
    
    .qr-branch-selector-content h3 {
      color: #FFD700;
      margin: 0 0 8px 0;
      font-size: 24px;
    }
    
    .qr-branch-selector-content h3 i {
      margin-right: 10px;
    }
    
    .qr-branch-selector-content p {
      color: #888;
      margin: 0 0 20px 0;
    }
    
    .qr-branch-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 12px;
      margin: 20px 0;
    }
    
    .qr-branch-card {
      background: #2a2a2a;
      border: 2px solid transparent;
      border-radius: 12px;
      padding: 16px;
      cursor: pointer;
      transition: all 0.2s;
      text-align: center;
    }
    
    .qr-branch-card:hover {
      border-color: #FFD700;
      background: #333;
    }
    
    .qr-branch-card.selected {
      border-color: #10b981;
      background: rgba(16, 185, 129, 0.15);
    }
    
    .qr-branch-name {
      font-weight: 600;
      color: #ffffff;
      font-size: 16px;
    }
    
    .qr-branch-address {
      font-size: 12px;
      color: #888;
      margin-top: 6px;
    }
    
    /* Location Status */
    .location-status {
      margin: 20px 0;
      padding: 16px;
      border-radius: 8px;
      background: #2a2a2a;
      text-align: center;
    }
    
    .location-checking {
      color: #FFD700;
    }
    
    .location-checking i {
      margin-right: 8px;
    }
    
    .location-valid {
      color: #10b981;
      font-weight: 600;
    }
    
    .location-valid i {
      font-size: 20px;
      margin-right: 8px;
    }
    
    .location-invalid {
      color: #ef4444;
      font-weight: 600;
    }
    
    .location-invalid i {
      font-size: 20px;
      margin-right: 8px;
    }
    
    /* Buttons */
    .qr-branch-actions {
      display: flex;
      gap: 12px;
      justify-content: center;
      margin-top: 24px;
    }
    
    .btn-confirm {
      background: #10b981;
      color: white;
      border: none;
      padding: 14px 28px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 16px;
      cursor: pointer;
      transition: all 0.2s;
    }
    
    .btn-confirm:hover:not(:disabled) {
      background: #059669;
      transform: translateY(-1px);
    }
    
    .btn-confirm:disabled {
      background: #444;
      color: #888;
      cursor: not-allowed;
    }
    
    .btn-secondary {
      background: transparent;
      border: 2px solid #FFD700;
      color: #FFD700;
      padding: 14px 28px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 16px;
      cursor: pointer;
      transition: all 0.2s;
    }
    
    .btn-secondary:hover {
      background: #FFD700;
      color: #0b0b0b;
    }
    
    /* Mobile Responsive Styles */
    @media (max-width: 640px) {
      .qr-branch-selector-content {
        padding: 24px 20px;
        max-width: 95%;
        width: 95%;
        margin: 0 10px;
      }
      
      .qr-branch-selector-content h3 {
        font-size: 18px;
      }
    }
    
    @media (max-width: 380px) {
      .qr-branch-selector-content {
        padding: 20px 16px;
      }
    }
  </style>

</head>
<body>
  <div class="app-shell">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="main-content">
      <!-- QR Scan Result Message -->
      <?php if ($qrScanResult): ?>
      <div class="qr-result-banner <?php echo $qrScanResult['success'] ? 'success' : 'error'; ?>">
        <i class="fas <?php echo $qrScanResult['success'] ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
        <?php echo htmlspecialchars($qrScanResult['message']); ?>
      </div>
      <?php endif; ?>

      <!-- QR Branch Selection Modal -->
      <?php if ($isBranchSelectMode && $qrScanResult && $qrScanResult['select_branch']): ?>
      <div id="qrBranchSelector" class="qr-branch-selector-modal">
        <div class="qr-branch-selector-content">
          <h3><i class="fas fa-building"></i> Select Your Project/Branch</h3>
          <p>Please select which project you're working at today:</p>
          
          <!-- Branch Grid -->
          <div class="qr-branch-grid" id="qrBranchGrid">
            <?php foreach ($branches as $branch): ?>
            <div class="qr-branch-card" 
                 data-branch="<?php echo htmlspecialchars($branch['branch_name']); ?>"
                 data-branch-id="<?php echo htmlspecialchars($branch['id']); ?>"
                 data-lat="<?php echo htmlspecialchars($branch['lat'] ?? ''); ?>"
                 data-lng="<?php echo htmlspecialchars($branch['long'] ?? ''); ?>"
                 data-radius="<?php echo htmlspecialchars($branch['geofence_radius_meters'] ?? 200); ?>">
              <div class="qr-branch-name"><?php echo htmlspecialchars($branch['branch_name']); ?></div>
              <?php if ($branch['address']): ?>
              <div class="qr-branch-address"><?php echo htmlspecialchars($branch['address']); ?></div>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
          
          <!-- Location Status Display -->
          <div id="locationStatus" class="location-status" style="display: none;">
            <div class="location-checking">
              <i class="fas fa-spinner fa-spin"></i> Getting your location...
            </div>
            <div class="location-valid" style="display: none;">
              <i class="fas fa-check-circle"></i> Location verified! You are within the project area.
            </div>
            <div class="location-invalid" style="display: none;">
              <i class="fas fa-exclamation-triangle"></i> <span id="locationErrorMsg"></span>
            </div>
          </div>
          
          <!-- Actions -->
          <div class="qr-branch-actions">
            <button id="confirmBranchBtn" class="btn-confirm" disabled>
              <i class="fas fa-check"></i> Confirm & Clock In
            </button>
            <button id="retryLocationBtn" class="btn-secondary" style="display: none;">
              <i class="fas fa-refresh"></i> Retry Location
            </button>
          </div>
        </div>
      </div>
      <?php endif; ?>

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

              <div class="form-row" style="margin-top: 12px;">
                <label style="font-size: 12px; color: #FFD700; font-weight: 600; margin-bottom: 6px; display: block;">Order Number (9 digits)</label>
                <input 
                  type="text" 
                  id="orderNumberInput" 
                  name="order_number" 
                  placeholder="Enter 9-digit order number (e.g., 123456789)" 
                  maxlength="9"
                  pattern="[0-9]{9}"
                  style="background: transparent; border: 1px solid rgba(255,255,255,0.04); padding: 0.6rem 0.75rem; border-radius: 8px; color: #ffffff; width: 100%;"
                />
                <small style="color: #888; font-size: 11px; margin-top: 4px; display: block;">Optional: Enter a unique 9-digit order number</small>
              </div>

              <div class="form-row" style="margin-top: 12px;">
                <label style="font-size: 12px; color: #FFD700; font-weight: 600; margin-bottom: 6px; display: block;">Exact Address</label>
                <textarea 
                  id="branchAddressInput" 
                  name="branch_address" 
                  placeholder="Enter exact address (e.g., 123 Main St, City, Province)" 
                  rows="3"
                  style="background: transparent; border: 1px solid rgba(255,255,255,0.04); padding: 0.6rem 0.75rem; border-radius: 8px; color: #ffffff; width: 100%; resize: vertical;"
                ></textarea>
                <small style="color: #888; font-size: 11px; margin-top: 4px; display: block;">Enter the complete address for this project location</small>
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

      <!-- Branch Statistics -->
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

      <script>
        window.attendanceConfig = {
          cutoffTime: <?php echo json_encode($cutoffTime); ?>,
          currentTime: <?php echo json_encode($currentTime); ?>
        };
        window.branchesFromPHP = <?php echo json_encode($branches); ?>;
      </script>

      <?php
      $qrEmployeeId = isset($_GET['emp_id']) ? intval($_GET['emp_id']) : 0;
      $autoTimein = isset($_GET['auto_timein']) ? 1 : 0;
      $qrEmployeeBranch = '';
      
      if ($qrEmployeeId && $autoTimein) {
          $branchStmt = mysqli_prepare($db, "SELECT b.branch_name 
              FROM employees e 
              LEFT JOIN branches b ON b.id = e.branch_id 
              WHERE e.id = ? LIMIT 1");
          if ($branchStmt) {
              mysqli_stmt_bind_param($branchStmt, 'i', $qrEmployeeId);
              mysqli_stmt_execute($branchStmt);
              $branchResult = mysqli_stmt_get_result($branchStmt);
              if ($branchRow = mysqli_fetch_assoc($branchResult)) {
                  $qrEmployeeBranch = $branchRow['branch_name'];
              }
              mysqli_stmt_close($branchStmt);
          }
      }
      ?>
      <script>
        window.qrScanData = {
          enabled: <?php echo $autoTimein ? 'true' : 'false'; ?>,
          employeeBranch: <?php echo json_encode($qrEmployeeBranch); ?>
        };
      </script>
      <script src="../assets/js/sidebar-toggle.js"></script>
      <script src="js/attendance.js?v=4"></script>

      <!-- QR Scan Auto-Select Branch -->
      <script>
      (function() {
        if (!window.qrScanData || !window.qrScanData.enabled || !window.qrScanData.employeeBranch) return;

        const empBranch = window.qrScanData.employeeBranch;
        console.log('QR Scan: Auto-selecting branch', empBranch);

        document.addEventListener('DOMContentLoaded', function() {
          setTimeout(function() {
            const branchCards = document.querySelectorAll('.branch-card');
            branchCards.forEach(function(card) {
              if (card.dataset.branch === empBranch) {
                console.log('QR Scan: Selecting branch', empBranch);
                card.click();
              }
            });
          }, 1000);
        });
      })();
      </script>

      <!-- QR Branch Selection with Location Verification -->
      <script>
      (function() {
        const isBranchSelectMode = <?php echo $isBranchSelectMode ? 'true' : 'false'; ?>;
        if (!isBranchSelectMode) return;
        
        const branchCards = document.querySelectorAll('.qr-branch-card');
        const confirmBtn = document.getElementById('confirmBranchBtn');
        const retryBtn = document.getElementById('retryLocationBtn');
        const locationStatus = document.getElementById('locationStatus');
        const locationChecking = locationStatus.querySelector('.location-checking');
        const locationValid = locationStatus.querySelector('.location-valid');
        const locationInvalid = locationStatus.querySelector('.location-invalid');
        const locationErrorMsg = document.getElementById('locationErrorMsg');
        
        let selectedBranch = null;
        let selectedBranchData = null;
        let currentPosition = null;
        let isLocationValid = false;
        
        // Branch card selection
        branchCards.forEach(card => {
          card.addEventListener('click', function() {
            // Remove selected class from all
            branchCards.forEach(c => c.classList.remove('selected'));
            // Add to clicked
            this.classList.add('selected');
            
            selectedBranch = this.dataset.branch;
            selectedBranchData = {
              id: this.dataset.branchId,
              name: this.dataset.branch,
              lat: parseFloat(this.dataset.lat) || null,
              lng: parseFloat(this.dataset.lng) || null,
              radius: parseInt(this.dataset.radius) || 200
            };
            
            // Start location verification
            verifyLocation();
          });
        });
        
        // Get GPS and verify location
        function verifyLocation() {
          locationStatus.style.display = 'block';
          locationChecking.style.display = 'block';
          locationValid.style.display = 'none';
          locationInvalid.style.display = 'none';
          confirmBtn.disabled = true;
          retryBtn.style.display = 'none';
          isLocationValid = false;
          
          if (!navigator.geolocation) {
            showLocationError('Geolocation is not supported by your browser');
            return;
          }
          
          navigator.geolocation.getCurrentPosition(
            function(position) {
              currentPosition = position;
              validatePosition(position);
            },
            function(error) {
              let errorMsg = 'Unable to get your location';
              switch(error.code) {
                case error.PERMISSION_DENIED:
                  errorMsg = 'Location access denied. Please enable location permissions.';
                  break;
                case error.POSITION_UNAVAILABLE:
                  errorMsg = 'Location information unavailable.';
                  break;
                case error.TIMEOUT:
                  errorMsg = 'Location request timed out.';
                  break;
              }
              showLocationError(errorMsg);
            },
            {
              enableHighAccuracy: true,
              timeout: 10000,
              maximumAge: 60000
            }
          );
        }
    if (!window.qrScanData || !window.qrScanData.enabled || !window.qrScanData.employeeBranch) return;

    const empBranch = window.qrScanData.employeeBranch;
    console.log('QR Scan: Auto-selecting branch', empBranch);

    document.addEventListener('DOMContentLoaded', function() {
      setTimeout(function() {
        const branchCards = document.querySelectorAll('.branch-card');
        branchCards.forEach(function(card) {
          if (card.dataset.branch === empBranch) {
            console.log('QR Scan: Selecting branch', empBranch);
            card.click();
          }
        });
      }, 1000);
    });
  })();
  </script>

  <!-- QR Branch Selection with Location Verification -->
  <script>
  (function() {
    const isBranchSelectMode = <?php echo $isBranchSelectMode ? 'true' : 'false'; ?>;
    if (!isBranchSelectMode) return;
    
    const branchCards = document.querySelectorAll('.qr-branch-card');
    const confirmBtn = document.getElementById('confirmBranchBtn');
    const retryBtn = document.getElementById('retryLocationBtn');
    const locationStatus = document.getElementById('locationStatus');
    const locationChecking = locationStatus.querySelector('.location-checking');
    const locationValid = locationStatus.querySelector('.location-valid');
    const locationInvalid = locationStatus.querySelector('.location-invalid');
    const locationErrorMsg = document.getElementById('locationErrorMsg');
    
    let selectedBranch = null;
    let selectedBranchData = null;
    let currentPosition = null;
    let isLocationValid = false;
    
    // Branch card selection
    branchCards.forEach(card => {
      card.addEventListener('click', function() {
        // Remove selected class from all
        branchCards.forEach(c => c.classList.remove('selected'));
        // Add to clicked
        this.classList.add('selected');
        
        selectedBranch = this.dataset.branch;
        selectedBranchData = {
          id: this.dataset.branchId,
          name: this.dataset.branch,
          lat: parseFloat(this.dataset.lat) || null,
          lng: parseFloat(this.dataset.lng) || null,
          radius: parseInt(this.dataset.radius) || 200
        };
        
        // Start location verification
        verifyLocation();
      });
    });
    
    // Get GPS and verify location
    function verifyLocation() {
      locationStatus.style.display = 'block';
      locationChecking.style.display = 'block';
      locationValid.style.display = 'none';
      locationInvalid.style.display = 'none';
      confirmBtn.disabled = true;
      retryBtn.style.display = 'none';
      isLocationValid = false;
      
      if (!navigator.geolocation) {
        showLocationError('Geolocation is not supported by your browser');
        return;
      }
      
      navigator.geolocation.getCurrentPosition(
        function(position) {
          currentPosition = position;
          validatePosition(position);
        },
        function(error) {
          let errorMsg = 'Unable to get your location';
          switch(error.code) {
            case error.PERMISSION_DENIED:
              errorMsg = 'Location access denied. Please enable location permissions.';
              break;
            case error.POSITION_UNAVAILABLE:
              errorMsg = 'Location information unavailable.';
              break;
            case error.TIMEOUT:
              errorMsg = 'Location request timed out.';
              break;
          }
          showLocationError(errorMsg);
        },
        {
          enableHighAccuracy: true,
          timeout: 10000,
          maximumAge: 60000
        }
      );
    }
    
    // Validate position against branch geofence
    function validatePosition(position) {
      const empLat = position.coords.latitude;
      const empLng = position.coords.longitude;
      const accuracy = position.coords.accuracy;
      
      // If branch has no coordinates, allow anyway
      if (!selectedBranchData.lat || !selectedBranchData.lng) {
        showLocationValid();
        return;
      }
      
      // Calculate distance using haversine formula
      const distance = calculateDistance(
        empLat, empLng,
        selectedBranchData.lat, selectedBranchData.lng
      );
      
      // Check if within radius
      if (distance <= selectedBranchData.radius) {
        showLocationValid();
      } else {
        showLocationError(
          'You are not in the location yet. ' +
          'Distance: ' + Math.round(distance) + 'm (allowed: ' + selectedBranchData.radius + 'm)'
        );
      }
    }
    
    // Haversine formula for distance calculation
    function calculateDistance(lat1, lng1, lat2, lng2) {
      const R = 6371000; // Earth's radius in meters
      const phi1 = lat1 * Math.PI / 180;
      const phi2 = lat2 * Math.PI / 180;
      const deltaPhi = (lat2 - lat1) * Math.PI / 180;
      const deltaLambda = (lng2 - lng1) * Math.PI / 180;
      
      const a = Math.sin(deltaPhi/2) * Math.sin(deltaPhi/2) +
                Math.cos(phi1) * Math.cos(phi2) *
                Math.sin(deltaLambda/2) * Math.sin(deltaLambda/2);
      const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
      
      return R * c;
    }
    
    function showLocationValid() {
      locationChecking.style.display = 'none';
      locationValid.style.display = 'block';
      locationInvalid.style.display = 'none';
      confirmBtn.disabled = false;
      retryBtn.style.display = 'none';
      isLocationValid = true;
    }
    
    function showLocationError(msg) {
      locationChecking.style.display = 'none';
      locationValid.style.display = 'none';
      locationInvalid.style.display = 'block';
      locationErrorMsg.textContent = msg;
      confirmBtn.disabled = true;
      retryBtn.style.display = 'inline-block';
      isLocationValid = false;
      
      // Alert the user
      alert('You are not in the location yet. ' + msg);
    }
    
    // Retry location button
    retryBtn.addEventListener('click', function() {
      if (selectedBranch) {
        verifyLocation();
      }
    });
    
    // Confirm button - proceed with clock-in/out
    confirmBtn.addEventListener('click', function() {
      if (!isLocationValid || !selectedBranch) {
        alert('Please select a branch and verify your location first.');
        return;
      }
      
      // Proceed with clock-in API call
      const formData = new FormData();
      formData.append('action', 'qr_clock_with_branch');
      formData.append('employee_id', '<?php echo $qrEmployeeId; ?>');
      formData.append('employee_code', '<?php echo htmlspecialchars($qrEmployeeCode); ?>');
      formData.append('branch_name', selectedBranch);
      formData.append('branch_id', selectedBranchData.id);
      formData.append('latitude', currentPosition.coords.latitude);
      formData.append('longitude', currentPosition.coords.longitude);
      formData.append('accuracy', currentPosition.coords.accuracy);
      formData.append('location_verified', 1);
      
      fetch('select_employee.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          document.getElementById('qrBranchSelector').style.display = 'none';
          // Show success message
          const banner = document.createElement('div');
          banner.className = 'qr-result-banner success';
          banner.innerHTML = '<i class="fas fa-check-circle"></i> ' + (data.message || 'Clock in/out successful');
          document.querySelector('.main-content').insertBefore(banner, document.querySelector('.main-content').firstChild);
          // Auto-select the branch in the main UI
          autoSelectBranch(selectedBranch);
        } else {
          alert(data.message || 'Failed to clock in/out');
        }
      })
      .catch(err => {
        alert('Error: ' + err.message);
      });
    });
    
    // Auto-select branch in main UI
    function autoSelectBranch(branchName) {
      const mainBranchCards = document.querySelectorAll('.branch-card');
      mainBranchCards.forEach(function(card) {
        if (card.dataset.branch === branchName) {
          card.click();
        }
      });
    }
  })();
  </script>

</body>
</html>