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
