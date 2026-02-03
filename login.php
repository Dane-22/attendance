<?php
// login.php - COMPLETE FIXED VERSION WITH SUPER ADMIN SUPPORT
require_once __DIR__ . '/conn/db_connection.php';
require_once __DIR__ . '/functions.php';

session_start();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    $daily_branch = $_POST['branch_name'] ?? '';
    
    // VALIDATION
    if (empty($identifier) || empty($password) || empty($daily_branch)) {
        $errors[] = 'Please fill in all fields including branch selection.';
    } else {
        // CHECK USER
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $sql = "SELECT * FROM employees WHERE email = ? AND status = 'Active'";
        } else {
            $sql = "SELECT * FROM employees WHERE employee_code = ? AND status = 'Active'";
        }
        
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, "s", $identifier);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        
        if ($user) {
            $stored_hash = $user['password_hash'];
            $password_valid = false;
            
            // DUAL PASSWORD VERIFICATION
            // First check if it's a password_hash() format
            if (strpos($stored_hash, '$2y$') === 0) {
                // It's a password_hash() format - use password_verify()
                if (password_verify($password, $stored_hash)) {
                    $password_valid = true;
                }
            } else {
                // It's NOT a password_hash() format - try MD5
                if (md5($password) === $stored_hash) {
                    $password_valid = true;
                    
                    // AUTO-UPGRADE: Convert MD5 hash to password_hash() for next login
                    $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    $update_sql = "UPDATE employees SET password_hash = ? WHERE id = ?";
                    $update_stmt = mysqli_prepare($db, $update_sql);
                    mysqli_stmt_bind_param($update_stmt, "si", $new_hash, $user['id']);
                    mysqli_stmt_execute($update_stmt);
                    mysqli_stmt_close($update_stmt);
                }
            }
            
            if ($password_valid) {
                // ========== SET ALL SESSION VARIABLES PROPERLY ==========
                
                // 1. BASIC USER INFO
                $_SESSION['employee_id'] = $user['id'];
                $_SESSION['employee_code'] = $user['employee_code'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['position'] = $user['position']; // CRITICAL!
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = date('Y-m-d H:i:s');
                
                // 2. BRANCH INFORMATION
                // Daily branch (where working today - from form)
                $_SESSION['daily_branch'] = $daily_branch;
                
                // Assigned branch (permanent assignment - from database)
                $_SESSION['assigned_branch'] = $user['branch_name'] ?? 'Main Branch';
                
                // 3. BRANCH FOR ATTENDANCE FILTERING
                // Super Admin should see ALL branches by default
                // Regular users see only their assigned branch
                if ($user['position'] === 'Super Admin') {
                    $_SESSION['branch_name'] = 'all'; // Super Admin sees all
                } else {
                    $_SESSION['branch_name'] = $user['branch_name'] ?? 'Main Branch'; // Regular users see assigned branch
                }
                
                // 4. CHECK IF MAY ATTENDANCE NA FOR TODAY
                $check_sql = "SELECT id FROM attendance 
                             WHERE employee_id = ? AND attendance_date = CURDATE()";
                $check_stmt = mysqli_prepare($db, $check_sql);
                mysqli_stmt_bind_param($check_stmt, "i", $user['id']);
                mysqli_stmt_execute($check_stmt);
                mysqli_stmt_store_result($check_stmt);

                $hasRunningCol = false;
                $col_sql = "SELECT COUNT(*) as cnt
                            FROM information_schema.COLUMNS
                            WHERE TABLE_SCHEMA = DATABASE()
                              AND TABLE_NAME = 'attendance'
                              AND COLUMN_NAME = 'is_time_running'";
                if ($col_res = mysqli_query($db, $col_sql)) {
                    $col_row = mysqli_fetch_assoc($col_res);
                    $hasRunningCol = intval($col_row['cnt'] ?? 0) === 1;
                }
                
                // 5. INSERT OR UPDATE ATTENDANCE WITH DAILY BRANCH
                if (mysqli_stmt_num_rows($check_stmt) == 0) {
                    // FIRST TIME LOGIN TODAY - INSERT NEW
                    $att_sql = $hasRunningCol
                        ? "INSERT INTO attendance 
                               (employee_id, branch_name, attendance_date, status, created_at, is_time_running) 
                               VALUES (?, ?, CURDATE(), 'Present', NOW(), 0)"
                        : "INSERT INTO attendance 
                               (employee_id, branch_name, attendance_date, status, created_at) 
                               VALUES (?, ?, CURDATE(), 'Present', NOW())";
                    $att_stmt = mysqli_prepare($db, $att_sql);
                    mysqli_stmt_bind_param($att_stmt, "is", $user['id'], $daily_branch);
                } else {
                    // MAY ATTENDANCE NA - UPDATE BRANCH
                    $att_sql = $hasRunningCol
                        ? "UPDATE attendance 
                               SET branch_name = ?, updated_at = NOW(), is_time_running = 0 
                               WHERE employee_id = ? AND attendance_date = CURDATE()"
                        : "UPDATE attendance 
                               SET branch_name = ?, updated_at = NOW() 
                               WHERE employee_id = ? AND attendance_date = CURDATE()";
                    $att_stmt = mysqli_prepare($db, $att_sql);
                    mysqli_stmt_bind_param($att_stmt, "si", $daily_branch, $user['id']);
                }
                
                mysqli_stmt_execute($att_stmt);
                mysqli_stmt_close($check_stmt);
                
                // Log the login activity
                $user_name = $user['first_name'] . ' ' . $user['last_name'];
                logActivity($db, 'Logged In', "User {$user_name} logged in from branch: {$daily_branch}");
                
                // 6. REDIRECT
                header('Location: employee/select_employee.php');
                exit();
                
            } else {
                $errors[] = 'Invalid password.';
            }
        } else {
            $errors[] = 'No account found or account is inactive.';
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Log In — JAJR Company</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/style_auth.css">
  <link rel="icon" type="image/x-icon" href="assets/img/profile/jajr-logo.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* Additional styles for select dropdown */
    select.input-field {
      -webkit-appearance: none;
      -moz-appearance: none;
      appearance: none;
      background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23FFA500' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
      background-repeat: no-repeat;
      background-position: right 1rem center;
      background-size: 1em;
      padding-right: 2.5rem;
    }
    select.input-field:focus {
      outline: none;
      border-color: #FFA500;
      box-shadow: 0 0 0 3px rgba(255, 165, 0, 0.1);
    }
    select.input-field option {
      background-color: #1a202c;
      color: #e2e8f0;
    }
    .super-admin-note {
      background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
      color: white;
      padding: 10px;
      border-radius: 6px;
      margin-bottom: 15px;
      font-size: 14px;
      text-align: center;
    }
    
    /* Password field with eye icon */
    .password-wrapper {
      position: relative;
    }
    
    .password-toggle {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: #FFA500;
      cursor: pointer;
      padding: 5px;
      font-size: 18px;
      z-index: 10;
    }
    
    .password-toggle:hover {
      color: #ffcc00;
    }
    
    .password-field {
      padding-right: 45px !important;
    }
    
    /* Style for when password is visible */
    .no-select {
      -webkit-user-select: none;
      -moz-user-select: none;
      -ms-user-select: none;
      user-select: none;
    }
  </style>
</head>
<body class="auth-bg text-white fade-in">
  <div class="min-h-screen flex items-center justify-center px-6">
    <div class="auth-card w-full max-w-4xl grid grid-cols-1 md:grid-cols-2 overflow-hidden">

      <div class="auth-left hidden md:flex flex-col justify-center items-start gap-6 px-8 bg-black/20">
        <div class="w-full text-left">
          <h2 class="text-3xl font-bold">Welcome Back</h2>
          <p class="mt-2 small-muted">Sign in to manage attendance and engineering resources.</p>
        </div>

        <div class="mt-6">
          <!-- SVG engineering gear -->
          <svg width="220" height="220" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden>
            <defs>
              <linearGradient id="og" x1="0" x2="1"><stop offset="0" stop-color="#FFA500"/><stop offset="1" stop-color="#000"/></linearGradient>
            </defs>
            <g class="gear-anim" transform="translate(50,50)">
              <circle cx="0" cy="0" r="28" stroke="url(#og)" stroke-width="3" fill="rgba(255,255,255,0.02)" />
              <g stroke="url(#og)" stroke-width="2">
                <path d="M0 -35 L0 -45" />
                <path d="M0 35 L0 45" />
                <path d="M-35 0 L-45 0" />
                <path d="M35 0 L45 0" />
              </g>
            </g>
          </svg>
        </div>

        <div class="mt-auto text-sm small-muted">Not a member? <a href="signup.php" data-transition class="text-orange-400">Create account</a></div>
      </div>

      <div class="auth-right px-8 py-10">
        <h3 class="text-2xl font-semibold">Log In</h3>
        <p class="small-muted mt-2">Use your Employee Code or Email to sign in.</p>

        <?php 
        // Check if Super Admin is logging in (for demo purposes)
        $identifier = $_POST['identifier'] ?? '';
        if (!empty($identifier)) {
            // Check if this is a Super Admin account
            if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                $check_sql = "SELECT position FROM employees WHERE email = ?";
            } else {
                $check_sql = "SELECT position FROM employees WHERE employee_code = ?";
            }
            $check_stmt = mysqli_prepare($db, $check_sql);
            mysqli_stmt_bind_param($check_stmt, "s", $identifier);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            $check_user = mysqli_fetch_assoc($check_result);
            
            if ($check_user && $check_user['position'] === 'Super Admin') {
                echo '<div class="super-admin-note">
                        <i class="fa-solid fa-crown mr-2"></i>
                        Super Admin detected: You will see ALL branches in attendance
                      </div>';
            }
        }
        ?>

        <?php if (!empty($errors)): ?>
          <div class="mt-4 text-red-300 bg-red-900/20 p-3 rounded">
            <?php foreach($errors as $err) echo '<div>'.htmlspecialchars($err).'</div>'; ?>
          </div>
        <?php endif; ?>

        <form method="POST" class="mt-6 space-y-4">
          <div>
            <label class="block text-sm">Employee Code or Email</label>
            <input name="identifier" class="mt-2 w-full p-3 rounded input-field" 
                   placeholder="e.g. E12345 or you@company.com" 
                   value="<?php echo htmlspecialchars($_POST['identifier'] ?? ''); ?>" />
          </div>
          
          <!-- Branch Selection Field -->
          <div>
            <label class="block text-sm font-medium">Select Today's Working Branch</label>
            <select name="branch_name" required class="mt-2 w-full p-3 rounded input-field focus:ring-2 focus:ring-orange-400 focus:border-transparent">
                <option value="">-- Where are you working today? --</option>
                <option value="Main Branch" <?php echo (($_POST['branch_name'] ?? '') === 'Main Branch') ? 'selected' : ''; ?>>Main Branch</option>
                <option value="BCDA" <?php echo (($_POST['branch_name'] ?? '') === 'BCDA') ? 'selected' : ''; ?>>BCDA</option>
                <option value="STO. Rosario" <?php echo (($_POST['branch_name'] ?? '') === 'STO. Rosario') ? 'selected' : ''; ?>>STO. Rosario</option>
                <option value="Panicsican" <?php echo (($_POST['branch_name'] ?? '') === 'Panicsican') ? 'selected' : ''; ?>>Panicsican</option>
                <option value="Dallangayan" <?php echo (($_POST['branch_name'] ?? '') === 'Dallangayan') ? 'selected' : ''; ?>>Dallangayan</option>
                <option value="Pias (Sunadara)" <?php echo (($_POST['branch_name'] ?? '') === 'Pias (Sunadara)') ? 'selected' : ''; ?>>Pias (Sunadara)</option>
                <option value="Pias (Office)" <?php echo (($_POST['branch_name'] ?? '') === 'Pias (Office') ? 'selected' : ''; ?>>Pias (Office)</option>
                <option value="Capitol" <?php echo (($_POST['branch_name'] ?? '') === 'Capitol') ? 'selected' : ''; ?>>Capitol</option>
                <option value="Test" <?php echo (($_POST['branch_name'] ?? '') === 'Test') ? 'selected' : ''; ?>>Test</option>
              </select>
            <p class="text-xs text-gray-400 mt-1">Select the branch where you're working today</p>
          </div>
          
          <!-- Password Field with Eye Icon -->
          <div>
            <label class="block text-sm">Password</label>
            <div class="password-wrapper mt-2">
              <input type="password" name="password" id="passwordInput" 
                     class="password-field w-full p-3 rounded input-field" 
                     placeholder="••••••••" />
              <button type="button" class="password-toggle" id="togglePassword">
                <i class="fas fa-eye"></i>
              </button>
            </div>
          </div>
          
          <div class="flex items-center justify-between">
            <a href="#" class="text-sm text-orange-400">Forgot password?</a>
          </div>
          <div>
            <button type="submit" class="w-full py-3 rounded btn-glow bg-gradient-to-r from-orange-400 to-black text-black font-semibold">
              Sign In
            </button>
          </div>
        </form>

        <!-- <div class="mt-6 text-sm small-muted">
          <p><strong>Note for Super Admins:</strong> You will see ALL branches in the attendance system.</p>
          <p><strong>Note for Regular Users:</strong> You will only see employees from your assigned branch.</p>
        </div>
      </div> -->

    </div>
  </div>

  <script src="assets/js/auth.js" defer></script>
  <script>
    // Auto-select branch for Super Admin demo
    document.addEventListener('DOMContentLoaded', function() {
      const identifierInput = document.querySelector('input[name="identifier"]');
      const branchSelect = document.querySelector('select[name="branch_name"]');
      
      identifierInput?.addEventListener('blur', function() {
        const identifier = this.value.toLowerCase();
        // If it looks like a Super Admin account, auto-select Main Branch
        if (identifier.includes('admin') || identifier.includes('super')) {
          branchSelect.value = 'Main Branch';
        }
      });
      
      // SIMPLE PASSWORD TOGGLE - NO ANTI-COPY COMPLICATIONS
      const togglePassword = document.getElementById('togglePassword');
      const passwordInput = document.getElementById('passwordInput');
      
      if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
          // Toggle the type attribute
          const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
          passwordInput.setAttribute('type', type);
          
          // Toggle the eye icon
          const icon = this.querySelector('i');
          if (type === 'password') {
            icon.className = 'fas fa-eye';
            icon.title = 'Show password';
          } else {
            icon.className = 'fas fa-eye-slash';
            icon.title = 'Hide password';
          }
        });
      }
    });
  </script>
</body>
</html>