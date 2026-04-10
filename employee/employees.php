<?php
// employee/employees.php
session_start();

// Prevent caching issues that may cause POST requests with stale data
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../conn/db_connection.php';
require_once __DIR__ . '/function/employees_function.php';

// ===== CHECK USER ROLE =====
// Check if user is logged in
if (!isset($_SESSION['employee_code'])) {
    header('Location: ../login.php');
    exit();
}

// Ensure all role variables are defined (for sidebar.php compatibility)
$userRole = $_SESSION['position'] ?? 'Employee';
$isAdmin = in_array($userRole, ['Admin', 'Super Admin']);
$isSuperAdmin = ($userRole === 'Super Admin');
$isDeveloper = ($userRole === 'Developer');

// Ensure database-dependent variables from employees_function.php are defined
if (!isset($msg)) $msg = '';
if (!isset($totalEmployees)) $totalEmployees = 0;
if (!isset($page)) $page = 1;
if (!isset($perPage)) $perPage = 10;
if (!isset($totalPages)) $totalPages = 1;
if (!isset($currentView)) $currentView = 'list';
if (!isset($search)) $search = '';

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Employee List — JAJR</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="css/employees.css">
  <link rel="stylesheet" href="css/light-theme.css">
  <link rel="icon" type="image/x-icon" href="../assets/img/profile/jajr-logo.png">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
 
</head>
<body class="dark-engineering" id="appBody">
  <?php include __DIR__ . '/sidebar.php'; ?>

  <main class="main-content" id="mainContent">
    <div class="container" style="max-width:100%;">
      <div class="header">
        <h1>Employees</h1>
        <div class="text-muted">Manage employee records</div>
      </div>

      <?php if ($msg): ?>
        <div class="card" style="margin-bottom:12px; background: rgba(255,215,0,0.1); border: 1px solid rgba(255,215,0,0.3);">
          <?php echo htmlspecialchars($msg); ?>
        </div>
      <?php endif; ?>



      <div class="top-actions">
        <div class="text-muted">Total Employees: <strong><?php echo $totalEmployees; ?></strong></div>
        <?php if ($isSuperAdmin): ?>
        <button class="add-btn" id="openAddDesktop" style="background: linear-gradient(135deg, #FFD700, #FFA500); color: #0b0b0b; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600;">
          <i class="fa-solid fa-user-plus"></i>&nbsp;Add Employee
        </button>
        <?php endif; ?>
      </div>

      <!-- Search Bar -->
      <div class="search-container">
        <div class="search-input-wrapper">
          <i class="fas fa-search search-icon"></i>
          <input type="text" id="searchInput" class="search-input" placeholder="Search employees by name, code, email, or position..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
          <button type="button" id="clearSearch" class="clear-search-btn" style="display: none;">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>

      <!-- Pagination Top -->
      <div class="pagination-container">
        <div class="pagination-info">
          Showing <strong><?php echo min(($page - 1) * $perPage + 1, $totalEmployees); ?></strong> to 
          <strong><?php echo min($page * $perPage, $totalEmployees); ?></strong> of 
          <strong><?php echo $totalEmployees; ?></strong> employees
        </div>
        <div class="pagination-controls">
          <div class="page-size-selector">
            <span class="page-size-label">Show:</span>
            <select id="pageSizeSelect" class="page-size-select" onchange="changePageSize(this.value)">
              <option value="10" <?php echo $perPage == 10 ? 'selected' : ''; ?>>10</option>
              <option value="25" <?php echo $perPage == 25 ? 'selected' : ''; ?>>25</option>
              <option value="50" <?php echo $perPage == 50 ? 'selected' : ''; ?>>50</option>
              <option value="100" <?php echo $perPage == 100 ? 'selected' : ''; ?>>100</option>
            </select>
          </div>
          <div class="pagination-buttons">
            <?php echo generatePaginationButtons($page, $totalPages, $perPage, $currentView); ?>
          </div>
        </div>
      </div>

      <section class="mt-6">
        <h2 style="margin-bottom:20px;color:#FFD700;font-size:24px;">Existing Employees</h2>
        
        <?php 
        // Check if mobile - force list view on mobile
        $isMobile = preg_match("/(android|iphone|ipad|mobile)/i", $_SERVER['HTTP_USER_AGENT']);
        $viewToUse = $isMobile ? 'list' : $currentView;
        ?>
        
        <div class="employees-<?php echo $viewToUse; ?>-view">
          <?php 
          // Safety check: Ensure $emps is a valid result set
          if (!$emps || !is_object($emps)): 
          ?>
            <div class="employee-row" style="text-align: center; padding: 40px;">
              <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #FFA500; margin-bottom: 16px;"></i>
              <p style="color: #fff; font-size: 18px;">Unable to load employees</p>
              <p style="color: rgba(255,255,255,0.6); font-size: 14px;">Please try refreshing the page or contact support.</p>
              <?php if (isset($db) && mysqli_error($db)): ?>
                <p style="color: rgba(255,255,255,0.4); font-size: 12px; margin-top: 10px;">Error: <?php echo htmlspecialchars(mysqli_error($db)); ?></p>
              <?php endif; ?>
            </div>
          <?php else: ?>
          <?php mysqli_data_seek($emps, 0); while ($e = mysqli_fetch_assoc($emps)): ?>
            
            <?php if ($viewToUse === 'grid'): ?>
              <!-- Grid View Card (optional, you can remove this if you only want list view) -->
              <!-- <article class="employee-card-grid" onclick="viewEmployeeProfile(<?php echo $e['id']; ?>)">
                <div class="employee-badge-grid"><?php echo htmlspecialchars($e['employee_code']); ?></div>
                <div class="card-header">
                  <div class="avatar">
                    <?php if (!empty($e['profile_image']) && file_exists(__DIR__ . '/uploads/' . $e['profile_image'])): ?>
                      <img src="uploads/<?php echo htmlspecialchars($e['profile_image']); ?>" alt="Profile">
                    <?php else: ?>
                      <div class="initials">
                        <?php echo strtoupper(substr($e['first_name'],0,1) . substr($e['last_name'],0,1)); ?>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div class="employee-info">
                    <h3 class="employee-name">
                      <?php echo htmlspecialchars($e['last_name'] . ', ' . $e['first_name']); ?>
                    </h3>
                    <p class="employee-position">
                      <i class="fas fa-briefcase"></i>
                      <?php echo htmlspecialchars($e['position']); ?>
                    </p>
                    <p class="employee-email">
                      <i class="fas fa-envelope"></i>
                      <?php echo htmlspecialchars($e['email']); ?>
                    </p>
                    <span class="employee-status"><?php echo htmlspecialchars($e['status']); ?></span>
                  </div>
                </div>

                <div class="card-actions">
                  <button class="action-btn action-btn-delete" onclick="deleteEmployee(event, <?php echo $e['id']; ?>, '<?php echo htmlspecialchars($e['first_name'] . ' ' . $e['last_name']); ?>')" title="Deactivate employee">
                    <i class="fa-solid fa-user-slash"></i>
                    Deactivate
                  </button>
                  <button class="action-btn action-btn-edit" onclick="openEditModal(event, <?php echo $e['id']; ?>)">
                    <i class="fa-solid fa-pen-to-square"></i>
                    Edit
                  </button>
                </div>
              </article> -->

            <?php elseif ($viewToUse === 'list'): ?>
              <!-- List View Row -->
              <div class="employee-row">
                <div>
                  <div class="employee-row-avatar">
                    <?php if (!empty($e['profile_image']) && file_exists(__DIR__ . '/uploads/' . $e['profile_image'])): ?>
                      <img src="uploads/<?php echo htmlspecialchars($e['profile_image']); ?>" alt="Profile">
                    <?php else: ?>
                      <div class="initials">
                        <?php echo strtoupper(substr($e['first_name'],0,1) . substr($e['last_name'],0,1)); ?>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div class="employee-row-info">
                    <div class="employee-row-name">
                      <?php echo htmlspecialchars($e['last_name'] . ', ' . $e['first_name']); ?>
                    </div>
                    <div class="employee-row-email">
                      <?php echo htmlspecialchars($e['email']); ?>
                    </div>
                  </div>
                </div>
                <div class="employee-row-code">
                  <?php echo htmlspecialchars($e['employee_code']); ?>
                </div>
                <div class="employee-row-position">
                  <?php echo htmlspecialchars($e['position']); ?>
                </div>
                <div class="employee-row-status">
                  <span style="color: #4ade80;"><?php echo htmlspecialchars($e['status']); ?></span>
                </div>
                <div class="employee-row-deduction">
                  <?php if ($isSuperAdmin || $isAdmin): ?>
                    <span class="deduction-badge <?php echo ($e['has_deduction'] ?? 1) ? 'with-deduction' : 'no-deduction'; ?>" 
                          onclick="toggleDeductionStatus(event, <?php echo $e['id']; ?>, <?php echo ($e['has_deduction'] ?? 1) ? 0 : 1; ?>)"
                          title="Click to toggle deduction status">
                      <i class="fas <?php echo ($e['has_deduction'] ?? 1) ? 'fa-file-invoice-dollar' : 'fa-ban'; ?>"></i>
                      <?php echo ($e['has_deduction'] ?? 1) ? 'With Deductions' : 'No Deductions'; ?>
                    </span>
                  <?php else: ?>
                    <span class="deduction-badge <?php echo ($e['has_deduction'] ?? 1) ? 'with-deduction' : 'no-deduction'; ?>">
                      <i class="fas <?php echo ($e['has_deduction'] ?? 1) ? 'fa-file-invoice-dollar' : 'fa-ban'; ?>"></i>
                      <?php echo ($e['has_deduction'] ?? 1) ? 'With Deductions' : 'No Deductions'; ?>
                    </span>
                  <?php endif; ?>
                </div>
                <div class="employee-row-actions">
                  <button class="row-action-btn row-action-qr" onclick="generateQRCode(event, <?php echo $e['id']; ?>, '<?php echo htmlspecialchars($e['first_name'] . ' ' . $e['last_name']); ?>', '<?php echo htmlspecialchars($e['employee_code']); ?>', '<?php echo htmlspecialchars($e['email']); ?>', '<?php echo htmlspecialchars($e['position']); ?>')" title="Generate QR Code">
                    <i class="fa-solid fa-qrcode"></i>
                  </button>
                  <?php if ($isSuperAdmin): ?>
                  <button class="row-action-btn row-action-delete" onclick="deleteEmployee(event, <?php echo $e['id']; ?>, '<?php echo htmlspecialchars($e['first_name'] . ' ' . $e['last_name']); ?>')" title="Deactivate employee">
                    <i class="fa-solid fa-user-slash"></i>
                  </button>
                  <button class="row-action-btn row-action-edit" onclick="openEditModal(event, <?php echo $e['id']; ?>)" title="Edit">
                    <i class="fa-solid fa-pen-to-square"></i>
                  </button>
                  <?php endif; ?>
                </div>
              </div>
            <?php endif; ?>
            
          <?php endwhile; ?>
          <?php endif; ?>
        </div>
      </section>

      <!-- Pagination Bottom -->
      <div class="pagination-container">
        <div class="pagination-info">
          Page <strong><?php echo $page; ?></strong> of <strong><?php echo $totalPages; ?></strong>
        </div>
        <div class="pagination-controls">
          <div class="page-size-selector">
            <span class="page-size-label">Show:</span>
            <select id="pageSizeSelectBottom" class="page-size-select" onchange="changePageSize(this.value)">
              <option value="10" <?php echo $perPage == 10 ? 'selected' : ''; ?>>10</option>
              <option value="25" <?php echo $perPage == 25 ? 'selected' : ''; ?>>25</option>
              <option value="50" <?php echo $perPage == 50 ? 'selected' : ''; ?>>50</option>
              <option value="100" <?php echo $perPage == 100 ? 'selected' : ''; ?>>100</option>
            </select>
          </div>
          <div class="pagination-buttons">
            <?php echo generatePaginationButtons($page, $totalPages, $perPage, $currentView); ?>
          </div>
          <div class="page-jump">
            <input type="number" id="pageJumpInput" class="page-jump-input" min="1" max="<?php echo $totalPages; ?>" value="<?php echo $page; ?>" placeholder="Page">
            <button class="page-jump-btn" onclick="jumpToPage()">Go</button>
          </div>
        </div>
      </div>

      <!-- Floating Add Button for mobile -->
      <?php if ($isSuperAdmin): ?>
      <button class="fab" id="openAddMobile" title="Add employee" style="position: fixed; bottom: 2rem; right: 2rem; width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, #FFD700, #FFA500); color: #0b0b0b; border: none; font-size: 1.5rem; cursor: pointer; z-index: 100;">
        <i class="fa-solid fa-plus"></i>
      </button>
      <?php endif; ?>

    </div>
  </main>

  <!-- Enhanced Edit Employee Modal -->
  <div class="edit-form-modal" id="editModal">
    <div class="edit-form-container">
      <div class="edit-form-header">
        <button class="close-btn" onclick="closeEditModal()">&times;</button>
        <h3>Edit Employee</h3>
        <div class="employee-id-display" id="editEmployeeId">Loading...</div>
      </div>
      <form id="editEmployeeForm" method="POST" enctype="multipart/form-data" class="edit-form-body">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" id="editEmployeeIdInput">

        <!-- Profile Information Section -->
        <div class="form-section">
          <h4 class="section-title">Profile Information</h4>
          <div class="form-row-grid">
            <div class="form-group">
              <label class="form-label required">Employee Code</label>
              <input type="text" name="employee_code" id="editEmployeeCode" class="form-input" required>
            </div>
            <div class="form-group">
              <label class="form-label required">First Name</label>
              <input type="text" name="first_name" id="editFirstName" class="form-input" required>
            </div>
            <div class="form-group">
              <label class="form-label">Middle Name</label>
              <input type="text" name="middle_name" id="editMiddleName" class="form-input">
            </div>
            <div class="form-group">
              <label class="form-label required">Last Name</label>
              <input type="text" name="last_name" id="editLastName" class="form-input" required>
            </div>
          </div>
        </div>

        <!-- Contact Information Section -->
        <div class="form-section">
          <h4 class="section-title">Contact Information</h4>
          <div class="form-row-grid">
            <div class="form-group" style="grid-column: 1 / -1;">
              <label class="form-label required">Email Address</label>
              <input type="email" name="email" id="editEmail" class="form-input" required>
            </div>
          </div>
        </div>

        <!-- Employment Details Section -->
        <div class="form-section">
          <h4 class="section-title">Employment Details</h4>
          <div class="form-row-grid">
            <div class="form-group">
              <label class="form-label required">Position</label>
              <input type="text" name="position" id="editPosition" class="form-input" required>
            </div>
            <div class="form-group">
              <label class="form-label">Status</label>
              <select name="status" id="editStatus" class="form-select">
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
                <option value="On Leave">On Leave</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Daily Rate (₱)</label>
              <input type="number" name="daily_rate" id="editDailyRate" class="form-input" step="0.01" min="0" placeholder="600.00">
            </div>
            <div class="form-group">
              <label class="form-label">Government Deductions</label>
              <label class="toggle-switch">
                <input type="checkbox" name="has_deduction" id="editHasDeduction" value="1" checked>
                <span class="toggle-slider">
                  <span class="toggle-on">With SSS/PhilHealth/PagIBIG</span>
                  <span class="toggle-off">No Deductions</span>
                </span>
              </label>
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
              <div class="text-muted" style="font-size: 0.85rem; color: rgba(255,255,255,0.5); padding: 0.5rem; background: rgba(255,255,255,0.05); border-radius: 6px;">
                <i class="fas fa-info-circle"></i> Use "Reset Password" button below to set password to default (jajrconstruction)
              </div>
            </div>
          </div>
        </div>

        <!-- Profile Image Section -->
        <div class="form-section">
          <h4 class="section-title">Profile Image</h4>
          <div class="profile-image-upload">
            <div class="profile-image-preview" id="profileImagePreview">
              <div class="initials" id="profileImageInitials">JD</div>
            </div>
            <div class="file-upload-area">
              <div class="file-input-wrapper">
                <input type="file" id="profileImageInput" name="profile_image" accept="image/*" onchange="handleImageUpload(this)">
                <label for="profileImageInput" class="file-input-label">
                  <i class="fas fa-cloud-upload-alt"></i> Choose New Profile Image
                </label>
              </div>
              <div class="file-info">
                Max file size: 10MB • Auto-compressed to ~500KB • Formats: JPG, PNG, GIF, WebP
              </div>
            </div>
          </div>
        </div>

        <div class="form-actions">
          <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
          <button type="button" class="btn-reset-password" onclick="resetPasswordToDefault()" style="background: rgba(255, 165, 0, 0.2); color: #FFA500; border: 1px solid rgba(255, 165, 0, 0.5); padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; cursor: pointer;">
            <i class="fas fa-key"></i> Reset Password
          </button>
          <button type="submit" class="btn-save">
            <i class="fas fa-save"></i> Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Add Employee Modal -->
  <div class="add-modal-backdrop" id="addModal">
    <div class="add-modal-panel">
      <h3 class="add-modal-title">Add New Employee</h3>
      <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="add-form-row">
          <input name="employee_code" id="addEmployeeCode" required placeholder="Employee code" class="add-form-input">
          <small id="employeeCodeHint" class="employee-code-hint">Auto-generated based on position</small>
        </div>
        <div class="add-form-row">
          <input name="first_name" required placeholder="First name" class="add-form-input">
        </div>
        <div class="add-form-row">
          <input name="middle_name" placeholder="Middle Name" class="add-form-input">
        </div>
        <div class="add-form-row">
          <input name="last_name" required placeholder="Last name" class="add-form-input">
        </div>
        <div class="add-form-row">
          <input name="email" type="email" placeholder="Email" class="add-form-input">
        </div>
        <div class="add-form-row">
          <select name="position" id="addPosition" required class="add-form-select">
            <option value="">Select Position</option>
            <option value="Worker">Worker</option>
            <option value="Admin">Admin</option>
            <option value="Engineer">Engineer</option>
            <option value="Developer">Developer</option>
            <option value="Super Admin">Super Admin</option>
          </select>
        </div>
        <div class="add-form-row">
          <input name="password" type="password" placeholder="Password (optional)" class="add-form-input">
        </div>
        <div class="add-form-row" style="display: flex; align-items: center; gap: 10px;">
          <input type="checkbox" name="has_deduction" id="addHasDeduction" value="1" checked style="width: 18px; height: 18px; cursor: pointer;">
          <label for="addHasDeduction" style="cursor: pointer; font-size: 0.9rem; color: rgba(255,255,255,0.8);">
            Subject to Government Deductions (SSS/PhilHealth/PagIBIG)
          </label>
        </div>
        <div class="add-modal-actions">
          <button type="button" class="btn-cancel-modal" id="closeAdd">Cancel</button>
          <button type="submit" class="btn-add-employee">Add Employee</button>
        </div>
      </form>
    </div>
  </div>

  <!-- QR Code Modal -->
  <div class="qr-modal" id="qrModal">
    <div class="qr-modal-content">
      <div class="qr-modal-header">
        <h3><i class="fa-solid fa-qrcode"></i> Employee QR Code</h3>
        <button class="qr-close-btn" onclick="closeQRModal()">&times;</button>
      </div>
      <div class="qr-modal-body">
        <div class="qr-employee-info">
          <div class="qr-employee-name" id="qrEmployeeName"></div>
          <div class="qr-employee-code" id="qrEmployeeCode"></div>
        </div>
        <div class="qr-code-container">
          <div id="qrcode"></div>
        </div>
        <div class="qr-instructions">
          Scan this QR code for quick employee identification
        </div>
        <div class="qr-data-preview">
          <div class="qr-data-label">QR Data:</div>
          <div class="qr-data-content" id="qrDataContent"></div>
        </div>
      </div>
      <div class="qr-modal-footer">
        <button class="qr-btn qr-btn-secondary" onclick="closeQRModal()">Close</button>
        <button class="qr-btn qr-btn-primary" onclick="downloadQRCode()">
          <i class="fa-solid fa-download"></i> Download QR
        </button>
      </div>
    </div>
  </div>

  <script>
    let currentQRCode = null;

    function generateQRCode(event, id, name, code, email, position) {
      event.stopPropagation();
      
      // Check if QRCode library is loaded
      if (typeof QRCode === 'undefined') {
        alert('QR Code library not loaded. Please refresh the page.');
        console.error('QRCode library not found');
        return;
      }
      
      try {
        // Build the URL for QR code scanning
        const baseUrl = window.location.origin + '/employee/select_employee.php';
        const qrUrl = `${baseUrl}?auto_timein=1&select_branch=1&emp_id=${id}&emp_code=${encodeURIComponent(code)}`;
        
        // Update modal content
        document.getElementById('qrEmployeeName').textContent = name;
        document.getElementById('qrEmployeeCode').textContent = code;
        document.getElementById('qrDataContent').textContent = qrUrl;
        
        // Clear previous QR code
        const qrContainer = document.getElementById('qrcode');
        qrContainer.innerHTML = '';
        
        // Generate new QR code with URL
        currentQRCode = new QRCode(qrContainer, {
          text: qrUrl,
          width: 280,
          height: 280,
          colorDark: '#000000',
          colorLight: '#ffffff',
          correctLevel: QRCode.CorrectLevel.H
        });
        
        // Show modal
        document.getElementById('qrModal').style.display = 'flex';
      } catch (error) {
        console.error('Error generating QR code:', error);
        alert('Error generating QR code: ' + error.message);
      }
    }

    function closeQRModal() {
      document.getElementById('qrModal').style.display = 'none';
    }

    function downloadQRCode() {
      const qrCanvas = document.querySelector('#qrcode canvas');
      if (qrCanvas) {
        // Create a new canvas with extra space for the employee name
        const newCanvas = document.createElement('canvas');
        const ctx = newCanvas.getContext('2d');
        
        // Set canvas size - QR code size + space for text
        const qrSize = 280;
        const textHeight = 60;
        const padding = 20;
        newCanvas.width = qrSize + (padding * 2);
        newCanvas.height = qrSize + textHeight + (padding * 2);
        
        // Fill white background
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, newCanvas.width, newCanvas.height);
        
        // Draw QR code centered
        ctx.drawImage(qrCanvas, padding, padding, qrSize, qrSize);
        
        // Draw employee name below QR code
        ctx.fillStyle = '#000000';
        ctx.font = 'bold 18px Arial';
        ctx.textAlign = 'center';
        const employeeName = document.getElementById('qrEmployeeName').textContent;
        const employeeCode = document.getElementById('qrEmployeeCode').textContent;
        
        // Draw name
        ctx.fillText(employeeName, newCanvas.width / 2, qrSize + padding + 25);
        
        // Draw employee code below name
        ctx.font = '14px Arial';
        ctx.fillText(employeeCode, newCanvas.width / 2, qrSize + padding + 45);
        
        // Download the new canvas
        const link = document.createElement('a');
        link.download = 'employee-qr-' + document.getElementById('qrEmployeeCode').textContent + '.png';
        link.href = newCanvas.toDataURL('image/png');
        link.click();
      }
    }

    // Close modal when clicking outside
    document.getElementById('qrModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeQRModal();
      }
    });
  </script>
  <script>
    // Apply saved theme from localStorage (set via settings.php)
    (function() {
      const savedTheme = localStorage.getItem('jajr_theme_preference') || 'dark';
      const body = document.getElementById('appBody');
      
      if (savedTheme === 'light' && body) {
        body.classList.remove('dark-engineering');
        body.classList.add('light-mode');
      }
    })();
  </script>
  <script>
    // Image compression before upload - prevents 413 errors
    async function handleImageUpload(input) {
      const file = input.files[0];
      if (!file) return;

      // Show compression status
      const label = input.nextElementSibling;
      const originalText = label.innerHTML;
      label.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Compressing...';

      try {
        // Compress image if larger than 500KB
        if (file.size > 500 * 1024) {
          const compressed = await compressImage(file, 1200, 0.8);
          // Create new File object with compressed data
          const newFile = new File([compressed.blob], file.name, { type: 'image/jpeg' });
          
          // Replace the file in the input
          const dataTransfer = new DataTransfer();
          dataTransfer.items.add(newFile);
          input.files = dataTransfer.files;
          
          console.log(`Compressed: ${(file.size/1024).toFixed(1)}KB → ${(newFile.size/1024).toFixed(1)}KB`);
        }
        
        // Preview the image
        previewProfileImage(input);
      } catch (err) {
        console.error('Compression failed:', err);
        // Fall back to original file
        previewProfileImage(input);
      } finally {
        label.innerHTML = originalText;
      }
    }

    function compressImage(file, maxWidth, quality) {
      return new Promise((resolve, reject) => {
        const img = new Image();
        const url = URL.createObjectURL(file);
        
        img.onload = () => {
          URL.revokeObjectURL(url);
          
          // Calculate new dimensions
          let width = img.width;
          let height = img.height;
          
          if (width > maxWidth) {
            height = (height * maxWidth) / width;
            width = maxWidth;
          }
          
          // Create canvas
          const canvas = document.createElement('canvas');
          canvas.width = width;
          canvas.height = height;
          
          const ctx = canvas.getContext('2d');
          ctx.fillStyle = '#FFFFFF';
          ctx.fillRect(0, 0, width, height);
          ctx.drawImage(img, 0, 0, width, height);
          
          // Convert to blob
          canvas.toBlob((blob) => {
            if (blob) {
              resolve({ blob, width, height });
            } else {
              reject(new Error('Canvas toBlob failed'));
            }
          }, 'image/jpeg', quality);
        };
        
        img.onerror = () => {
          URL.revokeObjectURL(url);
          reject(new Error('Image load failed'));
        };
        
        img.src = url;
      });
    }
  </script>
  <script src="js/employees.js.php"></script>
  <script>
    // Auto-generate employee code based on position selection
    (function() {
      const positionSelect = document.getElementById('addPosition');
      const employeeCodeInput = document.getElementById('addEmployeeCode');
      const employeeCodeHint = document.getElementById('employeeCodeHint');
      const addModal = document.getElementById('addModal');
      
      // Positions that support auto-generation
      const autoGeneratePositions = ['worker', 'admin', 'engineer', 'developer'];
      
      // Store original openAddModal function and override it
      const originalOpenAddModal = window.openAddModal;
      window.openAddModal = function() {
        // Clear hint when opening modal
        if (employeeCodeHint) employeeCodeHint.style.display = 'none';
        if (employeeCodeInput) employeeCodeInput.value = '';
        if (positionSelect) positionSelect.value = '';
        // Call original function
        if (originalOpenAddModal) originalOpenAddModal();
        else if (addModal) addModal.style.display = 'flex';
      };
      
      positionSelect?.addEventListener('change', function() {
        const selectedPosition = this.value.toLowerCase().trim();
        
        if (autoGeneratePositions.includes(selectedPosition)) {
          employeeCodeInput.placeholder = 'Loading...';
          
          // Fetch next employee code from API
          fetch('api/get_next_employee_code.php?position=' + encodeURIComponent(selectedPosition))
            .then(response => response.json())
            .then(data => {
              if (data.success && data.employee_code) {
                employeeCodeInput.value = data.employee_code;
                employeeCodeHint.style.display = 'block';
                employeeCodeHint.textContent = 'Auto-generated ' + selectedPosition.charAt(0).toUpperCase() + selectedPosition.slice(1) + ' code';
              } else {
                employeeCodeHint.style.display = 'none';
              }
              employeeCodeInput.placeholder = 'Employee code';
            })
            .catch(error => {
              console.error('Error fetching employee code:', error);
              employeeCodeHint.style.display = 'none';
              employeeCodeInput.placeholder = 'Employee code';
            });
        } else {
          // For Super Admin or other positions, clear and let user input manually
          employeeCodeInput.value = '';
          employeeCodeInput.placeholder = 'Employee code';
          employeeCodeHint.style.display = 'none';
        }
      });
    })();
  </script>
  <script>
    // Toggle deduction status via AJAX
    function toggleDeductionStatus(event, employeeId, newStatus) {
      event.stopPropagation();
      
      const statusText = newStatus === 1 ? 'WITH deductions' : 'NO deductions';
      if (!confirm(`Change employee deduction status to: ${statusText}?`)) {
        return;
      }
      
      fetch('api/toggle_deduction.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `employee_id=${employeeId}&has_deduction=${newStatus}`
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Reload page to show updated status
          window.location.reload();
        } else {
          alert('Error: ' + (data.message || 'Failed to update deduction status'));
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Error updating deduction status. Please try again.');
      });
    }
  </script>
</body>
</html>

<?php
// Function to generate pagination buttons
function generatePaginationButtons($currentPage, $totalPages, $perPage, $currentView) {
    if ($totalPages <= 1) return '';
    
    $html = '';
    
    // Previous button
    if ($currentPage > 1) {
        $prevPage = $currentPage - 1;
        $html .= '<a href="' . buildEmployeeUrl(['page' => $prevPage]) . '" class="page-btn">';
        $html .= '<i class="fas fa-chevron-left"></i>';
        $html .= '</a>';
    } else {
        $html .= '<span class="page-btn" disabled><i class="fas fa-chevron-left"></i></span>';
    }
    
    // First page
    $html .= '<a href="' . buildEmployeeUrl(['page' => 1]) . '" class="page-btn ' . ($currentPage === 1 ? 'active' : '') . '">1</a>';
    
    // Ellipsis if needed
    if ($currentPage > 3) {
        $html .= '<span class="page-dots">...</span>';
    }
    
    // Pages around current page
    for ($i = max(2, $currentPage - 1); $i <= min($totalPages - 1, $currentPage + 1); $i++) {
        if ($i > 1 && $i < $totalPages) {
            $html .= '<a href="' . buildEmployeeUrl(['page' => $i]) . '" class="page-btn ' . ($currentPage === $i ? 'active' : '') . '">' . $i . '</a>';
        }
    }
    
    // Ellipsis if needed
    if ($currentPage < $totalPages - 2) {
        $html .= '<span class="page-dots">...</span>';
    }
    
    // Last page (if not first page)
    if ($totalPages > 1) {
        $html .= '<a href="' . buildEmployeeUrl(['page' => $totalPages]) . '" class="page-btn ' . ($currentPage === $totalPages ? 'active' : '') . '">' . $totalPages . '</a>';
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $nextPage = $currentPage + 1;
        $html .= '<a href="' . buildEmployeeUrl(['page' => $nextPage]) . '" class="page-btn">';
        $html .= '<i class="fas fa-chevron-right"></i>';
        $html .= '</a>';
    } else {
        $html .= '<span class="page-btn" disabled><i class="fas fa-chevron-right"></i></span>';
    }
    
    return $html;
}
?>