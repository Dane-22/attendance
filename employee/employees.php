<?php
// employee/employees.php
session_start();
require_once __DIR__ . '/../conn/db_connection.php';
require_once __DIR__ . '/function/employees_function.php';

// ===== CHECK USER ROLE =====
// Check if user is logged in
if (!isset($_SESSION['employee_code'])) {
    header('Location: ../login.php');
    exit();
}


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
<body class="dark-engineering">
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
                  <button class="action-btn action-btn-delete" onclick="deleteEmployee(event, <?php echo $e['id']; ?>, '<?php echo htmlspecialchars($e['first_name'] . ' ' . $e['last_name']); ?>')">
                    <i class="fa-solid fa-trash"></i>
                    Delete
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
                <div class="employee-row-actions">
                  <button class="row-action-btn row-action-qr" onclick="generateQRCode(event, <?php echo $e['id']; ?>, '<?php echo htmlspecialchars($e['first_name'] . ' ' . $e['last_name']); ?>', '<?php echo htmlspecialchars($e['employee_code']); ?>', '<?php echo htmlspecialchars($e['email']); ?>', '<?php echo htmlspecialchars($e['position']); ?>')" title="Generate QR Code">
                    <i class="fa-solid fa-qrcode"></i>
                  </button>
                  <?php if ($isSuperAdmin): ?>
                  <button class="row-action-btn row-action-delete" onclick="deleteEmployee(event, <?php echo $e['id']; ?>, '<?php echo htmlspecialchars($e['first_name'] . ' ' . $e['last_name']); ?>')" title="Delete">
                    <i class="fa-solid fa-trash"></i>
                  </button>
                  <button class="row-action-btn row-action-edit" onclick="openEditModal(event, <?php echo $e['id']; ?>)" title="Edit">
                    <i class="fa-solid fa-pen-to-square"></i>
                  </button>
                  <?php endif; ?>
                </div>
              </div>
            <?php endif; ?>
            
          <?php endwhile; ?>
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
                <input type="file" id="profileImageInput" name="profile_image" accept="image/*" onchange="previewProfileImage(this)">
                <label for="profileImageInput" class="file-input-label">
                  <i class="fas fa-cloud-upload-alt"></i> Choose New Profile Image
                </label>
              </div>
              <div class="file-info">
                Max file size: 5MB • Formats: JPG, PNG, GIF, WebP
              </div>
            </div>
          </div>
        </div>

        <div class="form-actions">
          <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
          <button type="submit" class="btn-save">
            <i class="fas fa-save"></i> Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Add Employee Modal -->
  <div class="modal-backdrop" id="addModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center;">
    <div class="modal-panel" style="background: #0b0b0b; border: 1px solid rgba(255,215,0,0.3); border-radius: 12px; padding: 2rem; max-width: 500px; width: 90%;">
      <h3 style="margin-top:0; color: #FFD700; margin-bottom: 1.5rem;">Add New Employee</h3>
      <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="form-row" style="margin-bottom: 1rem;">
          <input name="employee_code" required placeholder="Employee code" style="width: 100%; padding: 0.75rem; border: 1px solid rgba(255,215,0,0.3); border-radius: 8px; background: rgba(0,0,0,0.5); color: white;">
        </div>
        <div class="form-row" style="margin-bottom: 1rem;">
          <input name="first_name" required placeholder="First name" style="width: 100%; padding: 0.75rem; border: 1px solid rgba(255,215,0,0.3); border-radius: 8px; background: rgba(0,0,0,0.5); color: white;">
        </div>
        <div class="form-row" style="margin-bottom: 1rem;">
          <input name="middle_name" placeholder="Middle Name" style="width: 100%; padding: 0.75rem; border: 1px solid rgba(255,215,0,0.3); border-radius: 8px; background: rgba(0,0,0,0.5); color: white;">
        </div>
        <div class="form-row" style="margin-bottom: 1rem;">
          <input name="last_name" required placeholder="Last name" style="width: 100%; padding: 0.75rem; border: 1px solid rgba(255,215,0,0.3); border-radius: 8px; background: rgba(0,0,0,0.5); color: white;">
        </div>
        <div class="form-row" style="margin-bottom: 1rem;">
          <input name="email" type="email" placeholder="Email" style="width: 100%; padding: 0.75rem; border: 1px solid rgba(255,215,0,0.3); border-radius: 8px; background: rgba(0,0,0,0.5); color: white;">
        </div>
        <div class="form-row" style="margin-bottom: 1rem;">
          <input name="position" placeholder="Position" style="width: 100%; padding: 0.75rem; border: 1px solid rgba(255,215,0,0.3); border-radius: 8px; background: rgba(0,0,0,0.5); color: white;">
        </div>
        <div class="form-row" style="margin-bottom: 1.5rem;">
          <input name="password" type="password" placeholder="Password (optional)" style="width: 100%; padding: 0.75rem; border: 1px solid rgba(255,215,0,0.3); border-radius: 8px; background: rgba(0,0,0,0.5); color: white;">
        </div>
        <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:8px;">
          <button type="button" class="btn" id="closeAdd" style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.3); padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer;">Cancel</button>
          <button type="submit" class="add-btn" style="background: linear-gradient(135deg, #FFD700, #FFA500); color: #0b0b0b; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; cursor: pointer;">Add Employee</button>
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
        const qrUrl = `${baseUrl}?auto_timein=1&emp_id=${id}&emp_code=${encodeURIComponent(code)}`;
        
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
  <script src="js/employees.js.php"></script>
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