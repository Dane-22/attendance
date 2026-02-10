<?php
// employee/attendance.php (Manual Admin-Set Attendance)
require_once __DIR__ . '/../conn/db_connection.php';
require_once __DIR__ . '/../functions.php';
session_start();

// Admin check - ensure logged in
if (empty($_SESSION['logged_in'])) { 
    header('Location: ../login.php'); 
    exit; 
}

$msg = '';
$selected_date = $_GET['date'] ?? date('Y-m-d'); // Get date from query parameter or use today
$selected_branch = $_GET['branch'] ?? ''; // Get branch filter from query parameter
$view = $_GET['view'] ?? 'unmarked'; // 'unmarked' or 'marked'
$today = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = intval($_POST['employee_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    $attendance_date = $_POST['attendance_date'] ?? $selected_date;

    if ($employee_id > 0 && in_array($status, ['Present', 'Absent'])) {
        // Get employee's details from employees table
        $branch_sql = "SELECT first_name, last_name FROM employees WHERE id = ? LIMIT 1";
        $branch_stmt = mysqli_prepare($db, $branch_sql);
        mysqli_stmt_bind_param($branch_stmt, 'i', $employee_id);
        mysqli_stmt_execute($branch_stmt);
        $branch_res = mysqli_stmt_get_result($branch_stmt);
        $employee = mysqli_fetch_assoc($branch_res);
        $employee_branch = 'Not Assigned';
        
        // Check existing record for the selected date
        $check_sql = "SELECT id FROM attendance WHERE employee_id = ? AND attendance_date = ? LIMIT 1";
        $check_stmt = mysqli_prepare($db, $check_sql);
        mysqli_stmt_bind_param($check_stmt, 'is', $employee_id, $attendance_date);
        mysqli_stmt_execute($check_stmt);
        $check_res = mysqli_stmt_get_result($check_stmt);
        $existing = mysqli_fetch_assoc($check_res);

        if ($existing) {
            $update_sql = "UPDATE attendance SET status = ?, branch_name = ?, updated_at = NOW() WHERE id = ?";
            $up = mysqli_prepare($db, $update_sql);
            mysqli_stmt_bind_param($up, 'ssi', $status, $employee_branch, $existing['id']);
            mysqli_stmt_execute($up);
            $msg = 'Attendance updated for ' . htmlspecialchars($attendance_date) . '.';
            
            // Log the attendance update
            $employee_name = $employee['first_name'] . ' ' . $employee['last_name'];
            logActivity($db, 'Updated Attendance', "Updated attendance for {$employee_name} (ID: {$employee_id}) to {$status} on {$attendance_date} at {$employee_branch}");
        } else {
            $insert_sql = "INSERT INTO attendance (employee_id, status, branch_name, attendance_date, created_at) VALUES (?, ?, ?, ?, NOW())";
            $ins = mysqli_prepare($db, $insert_sql);
            mysqli_stmt_bind_param($ins, 'isss', $employee_id, $status, $employee_branch, $attendance_date);
            mysqli_stmt_execute($ins);
            $msg = 'Attendance recorded for ' . htmlspecialchars($attendance_date) . '.';
            
            // Log the attendance recording
            $employee_name = $employee['first_name'] . ' ' . $employee['last_name'];
            logActivity($db, 'Marked Attendance', "Marked {$employee_name} (ID: {$employee_id}) as {$status} on {$attendance_date} at {$employee_branch}");
        }
        
        // Refresh the page with the same filters
        $query_params = [
            'date=' . urlencode($attendance_date),
            'view=' . urlencode($view)
        ];
        if ($selected_branch) {
            $query_params[] = 'branch=' . urlencode($selected_branch);
        }
        header("Location: attendance.php?" . implode('&', $query_params));
        exit;
    }
}

// Build the main query based on view type
$params = [];
$param_types = 's';

if ($view == 'unmarked') {
    // Only show employees WITHOUT attendance for selected date
    $attendance_sql = "SELECT 
                        e.*,
                        'Not Assigned' as display_branch
                      FROM employees e
                      LEFT JOIN attendance a ON e.id = a.employee_id 
                        AND a.attendance_date = ?
                      WHERE a.id IS NULL";
} else {
    // Only show employees WITH attendance for selected date
    $attendance_sql = "SELECT 
                        e.*,
                        a.status as attendance_status,
                        CASE 
                            WHEN a.branch_name IS NOT NULL AND a.branch_name != '' 
                            THEN a.branch_name 
                            ELSE 'Not Assigned' 
                        END as display_branch,
                        a.id as attendance_id
                      FROM employees e
                      INNER JOIN attendance a ON e.id = a.employee_id 
                        AND a.attendance_date = ?";
}

// Add branch filter if selected
if ($selected_branch) {
    if ($view == 'unmarked') {
        // For unmarked employees, no branch filter since they don't have branches
        // $attendance_sql .= " AND 1=1"; // No filter needed
    } else {
        $attendance_sql .= " AND a.branch_name = ?";
        $params[] = $selected_branch;
        $param_types .= 's';
    }
}

$attendance_sql .= " ORDER BY e.last_name, e.first_name";

// Prepare and execute the query
$attendance_stmt = mysqli_prepare($db, $attendance_sql);
if ($selected_branch) {
    mysqli_stmt_bind_param($attendance_stmt, $param_types, $selected_date, $selected_branch);
} else {
    mysqli_stmt_bind_param($attendance_stmt, 's', $selected_date);
}
mysqli_stmt_execute($attendance_stmt);
$attendance_res = mysqli_stmt_get_result($attendance_stmt);

// Get distinct branches for filter dropdown
$branch_res = mysqli_query($db, "
    SELECT DISTINCT a.branch_name
    FROM attendance a
    WHERE a.branch_name IS NOT NULL AND a.branch_name != ''
    ORDER BY a.branch_name
");

// Summary counts for header
if ($selected_branch) {
    $count_sql = "SELECT a.status, COUNT(*) as c 
                  FROM attendance a
                  LEFT JOIN employees e ON a.employee_id = e.id
                  WHERE a.attendance_date = ? 
                  AND (
                    CASE 
                        WHEN a.branch_name IS NOT NULL AND a.branch_name != '' 
                        THEN a.branch_name 
                        ELSE e.branch_name 
                    END
                  ) = ?
                  GROUP BY a.status";
    $count_stmt = mysqli_prepare($db, $count_sql);
    mysqli_stmt_bind_param($count_stmt, 'ss', $selected_date, $selected_branch);
    mysqli_stmt_execute($count_stmt);
    $counts_q = mysqli_stmt_get_result($count_stmt);
} else {
    $count_sql = "SELECT status, COUNT(*) as c FROM attendance WHERE attendance_date = ? GROUP BY status";
    $count_stmt = mysqli_prepare($db, $count_sql);
    mysqli_stmt_bind_param($count_stmt, 's', $selected_date);
    mysqli_stmt_execute($count_stmt);
    $counts_q = mysqli_stmt_get_result($count_stmt);
}

$presentCount = 0;
$absentCount = 0;

if ($counts_q) {
    while ($r = mysqli_fetch_assoc($counts_q)) {
        if ($r['status'] === 'Present') $presentCount = intval($r['c']);
        if ($r['status'] === 'Absent') $absentCount = intval($r['c']);
    }
}

// Get total employees count with branch filter if applicable
// Get total employees count (no branch filter since employees don't have permanent branches)
$total_emp_sql = "SELECT COUNT(*) as total FROM employees WHERE status = 'Active'";
$total_res = mysqli_query($db, $total_emp_sql);

$total_row = mysqli_fetch_assoc($total_res);
$totalEmployees = $total_row['total'] ?? 0;
$pendingCount = max(0, $totalEmployees - $presentCount - $absentCount);
$markedCount = $presentCount + $absentCount;

// Function to get attendance status and branch (for marked view)
function get_employee_attendance_info($db, $emp_id, $date) {
    $s = mysqli_prepare($db, "
        SELECT 
            a.status,
            CASE 
                WHEN a.branch_name IS NOT NULL AND a.branch_name != '' 
                THEN a.branch_name 
                ELSE e.branch_name 
            END as branch_name
        FROM employees e
        LEFT JOIN attendance a ON e.id = a.employee_id AND a.attendance_date = ?
        WHERE e.id = ?
        LIMIT 1
    ");
    mysqli_stmt_bind_param($s, 'si', $date, $emp_id);
    mysqli_stmt_execute($s);
    $r = mysqli_stmt_get_result($s);
    $row = mysqli_fetch_assoc($r);
    return [
        'status' => $row['status'] ?? '',
        'branch_name' => $row['branch_name'] ?? ''
    ];
}

// Handle undo absent
if ($_POST['action'] === 'undo_absent') {
    $employeeId = intval($_POST['employee_id']);
    $branch = $_POST['branch'] ?? '';
    
    try {
        // Delete the absent record for today
        $today = date('Y-m-d');
        
        $stmt = $pdo->prepare("DELETE FROM attendance WHERE employee_id = ? AND DATE(date) = ? AND status = 'Absent'");
        $stmt->execute([$employeeId, $today]);
        
        // Also clear absent notes if you store them separately
        $stmt = $pdo->prepare("UPDATE employees SET absent_notes = '' WHERE id = ?");
        $stmt->execute([$employeeId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Absent status undone successfully'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to undo absent: ' . $e->getMessage()
        ]);
    }
    exit();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Manual Attendance — JAJR</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="css/light-theme.css">
  <script src="js/theme.js"></script>
  <style>
    .branch-badge {
      background-color: #4a5568;
      color: white;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 12px;
      font-weight: 500;
      display: inline-block;
    }
    .branch-badge.muted {
      background-color: #718096;
      color: #e2e8f0;
    }
    .tabs {
      display: flex;
      gap: 8px;
      margin-bottom: 16px;
      border-bottom: 2px solid #374151;
      padding-bottom: 8px;
    }
    .tab {
      padding: 10px 20px;
      border-radius: 6px 6px 0 0;
      background: #374151;
      color: #9ca3af;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.2s;
    }
    .tab:hover {
      background: #4b5563;
      color: #e5e7eb;
    }
    .tab.active {
      background: #1f2937;
      color: white;
      border-bottom: 3px solid #3b82f6;
    }
    .locked-status {
      padding: 8px 12px;
      background: #374151;
      border-radius: 6px;
      color: #9ca3af;
      font-size: 14px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
  </style>
</head>
<body class="dark-engineering">
  <?php include __DIR__ . '/sidebar.php'; ?>

  <!-- Mobile header (hamburger + title) -->
  <div class="mobile-header" aria-hidden="false">
    <button id="sidebarToggle" class="hamburger" aria-expanded="false" aria-controls="sidebar"><i class="fa-solid fa-bars"></i></button>
    <div style="flex:1;display:flex;align-items:center;justify-content:center;"><strong>Manual Attendance</strong></div>
  </div>

  <main class="main-content with-mobile-header">
    <div class="container">
      <div class="header" style="align-items:flex-start;">
        <h1>Manual Attendance</h1>
        <div class="text-muted">
          Viewing: <?php echo htmlspecialchars($selected_date); ?>
          <?php if ($selected_branch): ?>
            | Branch: <?php echo htmlspecialchars($selected_branch); ?>
          <?php endif; ?>
          <?php if ($selected_date != $today): ?>
            <span style="margin-left: 10px; color: #ff9800;">
              <i class="fa-solid fa-calendar-day"></i> Not Today
            </span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Summary cards -->
      <div class="summary-grid">
        <div class="summary-card">
          <div class="summary-number"><?php echo $presentCount; ?></div>
          <div class="summary-label">Total Present</div>
        </div>
        <div class="summary-card">
          <div class="summary-number"><?php echo $absentCount; ?></div>
          <div class="summary-label">Total Absent</div>
        </div>
        <div class="summary-card">
          <div class="summary-number"><?php echo $pendingCount; ?></div>
          <div class="summary-label">Unmarked</div>
        </div>
        <div class="summary-card">
          <div class="summary-number"><?php echo $markedCount; ?></div>
          <div class="summary-label">Total Marked</div>
        </div>
      </div>

      <?php if ($msg): ?>
        <div class="card" style="margin-bottom:12px; background:#1e3a8a; color:white;">
          <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($msg); ?>
        </div>
      <?php endif; ?>

      <!-- Tabs -->
      <div class="tabs">
        <a href="?date=<?php echo urlencode($selected_date); ?>&branch=<?php echo urlencode($selected_branch); ?>&view=unmarked" 
           class="tab <?php echo $view == 'unmarked' ? 'active' : ''; ?>">
          <i class="fa-solid fa-user-clock"></i> Unmarked (<?php echo $pendingCount; ?>)
        </a>
        <a href="?date=<?php echo urlencode($selected_date); ?>&branch=<?php echo urlencode($selected_branch); ?>&view=marked" 
           class="tab <?php echo $view == 'marked' ? 'active' : ''; ?>">
          <i class="fa-solid fa-clipboard-check"></i> Marked (<?php echo $markedCount; ?>)
        </a>
      </div>

      <div class="attendance-controls" style="width:100%;display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:12px;">
        <form method="GET" action="" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
          <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
          
          <input type="date" name="date" class="date-input" value="<?php echo htmlspecialchars($selected_date); ?>" 
                 max="<?php echo date('Y-m-d'); ?>" onchange="this.form.submit()">
          
          <select name="branch" class="select-input" onchange="this.form.submit()">
            <option value="">All Branches</option>
            <?php 
            if ($branch_res && mysqli_num_rows($branch_res) > 0) {
                mysqli_data_seek($branch_res, 0);
                while ($branch = mysqli_fetch_assoc($branch_res)): 
            ?>
              <option value="<?php echo htmlspecialchars($branch['branch_name']); ?>" 
                <?php echo $selected_branch == $branch['branch_name'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($branch['branch_name']); ?>
              </option>
            <?php 
                endwhile;
            }
            ?>
          </select>
          
          <button type="button" class="btn" onclick="window.location.href='attendance.php?view=<?php echo urlencode($view); ?>'">
            <i class="fa-solid fa-calendar-day"></i> Today
          </button>
          
          <?php if ($selected_branch): ?>
            <button type="button" class="btn btn-secondary" 
                    onclick="window.location.href='attendance.php?date=<?php echo urlencode($selected_date); ?>&view=<?php echo urlencode($view); ?>'">
              <i class="fa-solid fa-filter-circle-xmark"></i> Clear Branch
            </button>
          <?php endif; ?>
        </form>
        
        <input type="search" id="search" class="search-input" placeholder="Search employee...">
      </div>

      <div class="attendance-card card">
        <div class="table-wrapper">
          <table class="attendance-table">
            <thead>
              <tr>
                <th>Employee</th>
                <th>Code</th>
                <th>Branch</th>
                <th>Status</th>
                <th class="col-actions">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              if ($attendance_res && mysqli_num_rows($attendance_res) > 0) {
                  mysqli_data_seek($attendance_res, 0); 
                  while ($row = mysqli_fetch_assoc($attendance_res)): 
                    $employee_id = $row['id'];
                    $employee_name = htmlspecialchars(($row['last_name'] ?? '') . ', ' . ($row['first_name'] ?? ''));
                    $employee_code = htmlspecialchars($row['employee_code'] ?? '');
                    $branch_name = $row['display_branch'] ?? '';
                    
                    // For marked view, get the attendance status
                    if ($view == 'marked') {
                        $attendance_info = get_employee_attendance_info($db, $employee_id, $selected_date);
                        $status = $attendance_info['status'];
                        $branch_name = $attendance_info['branch_name'] ?: $branch_name;
                    } else {
                        $status = '';
                    }
              ?>
                <tr class="attendance-row" data-search="<?php echo htmlspecialchars(strtolower($row['first_name'].' '.$row['last_name'].' '.$employee_code.' '.$branch_name)); ?>">
                  <td class="emp-col">
                    <div class="emp-meta">
                      <div class="initials"><?php echo strtoupper(substr($row['first_name'] ?? '', 0, 1) . substr($row['last_name'] ?? '', 0, 1)); ?></div>
                      <div class="emp-name-block">
                        <div class="employee-name"><?php echo $employee_name; ?></div>
                        <div class="employee-role muted">&nbsp;</div>
                      </div>
                    </div>
                  </td>
                  <td class="emp-code"><?php echo $employee_code; ?></td>
                  <td class="emp-branch">
                    <?php if (!empty($branch_name)): ?>
                      <span class="branch-badge"><?php echo htmlspecialchars($branch_name); ?></span>
                    <?php else: ?>
                      <span class="branch-badge muted">No Branch Set</span>
                    <?php endif; ?>
                  </td>
                  <td class="emp-status">
                    <?php if ($view == 'marked'): ?>
                      <?php if ($status === 'Present'): ?>
                        <span class="badge present">Present</span>
                      <?php elseif ($status === 'Absent'): ?>
                        <span class="badge absent">Absent</span>
                      <?php else: ?>
                        <span class="badge muted">Not set</span>
                      <?php endif; ?>
                    <?php else: ?>
                      <span class="badge muted">Not marked</span>
                    <?php endif; ?>
                  </td>
                  <td class="col-actions">
                    <?php if ($view == 'unmarked'): ?>
                      <!-- Unmarked view - can mark attendance -->
                      <div class="actions">
                        <form method="POST" style="display:inline-block;margin:0;">
                          <input type="hidden" name="employee_id" value="<?php echo $employee_id; ?>">
                          <input type="hidden" name="status" value="Present">
                          <input type="hidden" name="attendance_date" value="<?php echo htmlspecialchars($selected_date); ?>">
                          <button type="submit" class="btn btn-present" title="Mark Present">
                            <i class="fa-solid fa-circle-check"></i>&nbsp;Present
                          </button>
                        </form>
                        <form method="POST" style="display:inline-block;margin:0;">
                          <input type="hidden" name="employee_id" value="<?php echo $employee_id; ?>">
                          <input type="hidden" name="status" value="Absent">
                          <input type="hidden" name="attendance_date" value="<?php echo htmlspecialchars($selected_date); ?>">
                          <button type="submit" class="btn btn-absent" title="Mark Absent">
                            <i class="fa-solid fa-circle-xmark"></i>&nbsp;Absent
                          </button>
                        </form>
                      </div>
                    <?php else: ?>
                      <!-- Marked view - view only or edit option -->
                      <div class="actions">
                        <?php if ($selected_date == $today): ?>
                          <!-- Allow editing for today's attendance -->
                          <form method="POST" style="display:inline-block;margin:0;">
                            <input type="hidden" name="employee_id" value="<?php echo $employee_id; ?>">
                            <input type="hidden" name="status" value="Present">
                            <input type="hidden" name="attendance_date" value="<?php echo htmlspecialchars($selected_date); ?>">
                            <button type="submit" class="btn btn-present <?php echo $status == 'Present' ? 'disabled' : ''; ?>" 
                                    title="Change to Present" <?php echo $status == 'Present' ? 'disabled' : ''; ?>>
                              <i class="fa-solid fa-circle-check"></i>&nbsp;Present
                            </button>
                          </form>
                          <form method="POST" style="display:inline-block;margin:0;">
                            <input type="hidden" name="employee_id" value="<?php echo $employee_id; ?>">
                            <input type="hidden" name="status" value="Absent">
                            <input type="hidden" name="attendance_date" value="<?php echo htmlspecialchars($selected_date); ?>">
                            <button type="submit" class="btn btn-absent <?php echo $status == 'Absent' ? 'disabled' : ''; ?>" 
                                    title="Change to Absent" <?php echo $status == 'Absent' ? 'disabled' : ''; ?>>
                              <i class="fa-solid fa-circle-xmark"></i>&nbsp;Absent
                            </button>
                          </form>
                        <?php else: ?>
                          <!-- Past dates - view only -->
                          <span class="locked-status">
                            <i class="fa-solid fa-lock"></i> Already marked
                          </span>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php 
                  endwhile;
              } else {
                  $message = $view == 'unmarked' 
                    ? 'All employees have been marked for this date.' 
                    : 'No attendance records found for this date.';
                  echo '<tr><td colspan="5" style="text-align: center; padding: 40px;" class="text-muted">' . $message . '</td></tr>';
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card" style="margin-top: 16px; background: #1f2937; color: #9ca3af; padding: 12px;">
        <i class="fa-solid fa-info-circle"></i> 
        <strong>Note:</strong> 
        <?php if ($view == 'unmarked'): ?>
          Employees will be removed from this list once marked. View marked employees in the "Marked" tab.
        <?php else: ?>
          Attendance for past dates cannot be edited. Only today's attendance can be modified.
        <?php endif; ?>
      </div>

    </div>
  </main>

  <script>
    // Simple client-side search
    document.getElementById('search')?.addEventListener('input', function(e){
      const q = e.target.value.toLowerCase();
      document.querySelectorAll('.attendance-row').forEach(row => {
        const searchText = row.getAttribute('data-search').toLowerCase();
        row.style.display = searchText.includes(q) ? '' : 'none';
      });
    });
    
    // Auto-submit date picker and branch filter on change
    document.querySelector('input[name="date"]')?.addEventListener('change', function() {
      this.form.submit();
    });
    
    document.querySelector('select[name="branch"]')?.addEventListener('change', function() {
      this.form.submit();
    });
    
    // Sidebar toggle for mobile
    document.getElementById('sidebarToggle')?.addEventListener('click', function() {
      const sidebar = document.getElementById('sidebar');
      if (sidebar) {
        sidebar.classList.toggle('open');
        this.setAttribute('aria-expanded', sidebar.classList.contains('open'));
      }
    });
  </script>
</body>
</html>