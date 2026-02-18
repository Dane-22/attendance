<?php
// login.php - COMPLETE FIXED VERSION WITH SUPER ADMIN SUPPORT
require_once __DIR__ . '/conn/db_connection.php';
require_once __DIR__ . '/functions.php';

session_start();
$errors = [];
$warnings = [];

function procurementApiLogin(string $employeeNo, string $password): array {
    $url = 'https://procurement-api.xandree.com/api/auth/login';
    $payload = json_encode([
        'employee_no' => $employeeNo,
        'password' => $password,
    ]);

    if ($payload === false) {
        return ['ok' => false, 'error' => 'Failed to encode procurement login payload'];
    }

    $ch = curl_init($url);
    if ($ch === false) {
        return ['ok' => false, 'error' => 'Failed to initialize HTTP client'];
    }

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_CONNECTTIMEOUT => 4,
    ]);

    $raw = curl_exec($ch);
    $curlErr = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false) {
        return ['ok' => false, 'error' => $curlErr ?: 'Unknown procurement API network error'];
    }

    $json = json_decode($raw, true);
    if (!is_array($json)) {
        return ['ok' => false, 'error' => 'Invalid JSON response from procurement API', 'http_status' => $status, 'raw' => $raw];
    }

    if ($status < 200 || $status >= 300) {
        $msg = (string)($json['message'] ?? $json['error'] ?? ('Procurement API HTTP ' . $status));
        return ['ok' => false, 'error' => $msg, 'http_status' => $status, 'response' => $json];
    }

    return ['ok' => true, 'http_status' => $status, 'response' => $json];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    $daily_branch = 'Main Branch';
    
    // VALIDATION
    if (empty($identifier) || empty($password)) {
        $errors[] = 'Please fill in all fields.';
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

                // 1.1 PROCUREMENT SSO (BEST-EFFORT)
                // Attempt to login to procurement system using the local employee_code + same password.
                // This will NOT block local login if it fails.
                $_SESSION['procurement_auth'] = null;
                $_SESSION['procurement_auth_error'] = null;

                $procResult = procurementApiLogin((string)$user['employee_code'], (string)$password);
                if (!($procResult['ok'] ?? false)) {
                    $_SESSION['procurement_auth_error'] = $procResult;
                    $warnings[] = 'Logged in successfully, but procurement login failed. You may need to login again when opening Procurement.';
                } else {
                    $_SESSION['procurement_auth'] = $procResult['response'] ?? null;

                    $token = null;
                    if (is_array($_SESSION['procurement_auth'])) {
                        $token = $_SESSION['procurement_auth']['token']
                            ?? $_SESSION['procurement_auth']['access_token']
                            ?? ($_SESSION['procurement_auth']['data']['token'] ?? null)
                            ?? ($_SESSION['procurement_auth']['data']['access_token'] ?? null);
                    }
                    if ($token) {
                        $_SESSION['procurement_token'] = $token;
                    }
                }
                
                // 2. BRANCH INFORMATION
                // Daily branch (where working today - hardcoded to Main Branch)
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

                // 5. CHECK FOR ALL POTENTIAL COLUMNS WITHOUT DEFAULTS
                $problematic_columns = [
                    'is_time_running',
                    'is_overtime_running', 
                    'total_ot_hrs',
                    'total_hours',
                    'overtime_hours'
                ];
                
                $existing_columns = [];
                foreach ($problematic_columns as $column) {
                    $col_sql = "SELECT COUNT(*) as cnt
                               FROM information_schema.COLUMNS
                               WHERE TABLE_SCHEMA = DATABASE()
                                 AND TABLE_NAME = 'attendance'
                                 AND COLUMN_NAME = '$column'";
                    if ($col_res = mysqli_query($db, $col_sql)) {
                        $col_row = mysqli_fetch_assoc($col_res);
                        if (intval($col_row['cnt'] ?? 0) === 1) {
                            $existing_columns[$column] = true;
                        }
                    }
                }
                
                // 6. INSERT OR UPDATE ATTENDANCE WITH DAILY BRANCH
                if (mysqli_stmt_num_rows($check_stmt) == 0) {
                    // FIRST TIME LOGIN TODAY - INSERT NEW
                    $columns = ['employee_id', 'branch_name', 'attendance_date', 'status', 'created_at'];
                    $placeholders = ['?', '?', 'CURDATE()', "'Present'", 'NOW()'];
                    
                    // Add problematic columns with default values
                    $default_values = [
                        'is_time_running' => 0,
                        'is_overtime_running' => 0,
                        'total_ot_hrs' => 0.00,
                        'total_hours' => 0.00,
                        'overtime_hours' => 0.00
                    ];
                    
                    $bind_params = [$user['id'], $daily_branch];
                    $param_types = "is"; // i = integer, s = string
                    
                    foreach ($default_values as $column => $default_value) {
                        if (isset($existing_columns[$column])) {
                            $columns[] = $column;
                            $placeholders[] = '?';
                            $bind_params[] = $default_value;
                            $param_types .= 's'; // Add parameter type
                        }
                    }
                    
                    $att_sql = "INSERT INTO attendance (" . implode(', ', $columns) . ") 
                               VALUES (" . implode(', ', $placeholders) . ")";
                    
                    $att_stmt = mysqli_prepare($db, $att_sql);
                    
                    // Dynamically bind parameters
                    if (count($bind_params) > 0) {
                        mysqli_stmt_bind_param($att_stmt, $param_types, ...$bind_params);
                    }
                } else {
                    // MAY ATTENDANCE NA - UPDATE BRANCH
                    $att_sql = "UPDATE attendance 
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
                
                // 7. REDIRECT
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
  <meta http-equiv="Permissions-Policy" content="camera=*, microphone=()">
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

    /* QR Scanner modal */
    .qr-scan-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.75);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 9999;
      padding: 16px;
    }
    .qr-scan-panel {
      width: 100%;
      max-width: 520px;
      background: rgba(15, 15, 15, 0.95);
      border: 1px solid rgba(255,165,0,0.25);
      border-radius: 14px;
      padding: 16px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }
    .qr-scan-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 12px;
    }
    .qr-scan-title {
      font-weight: 700;
      color: #FFA500;
    }
    .qr-close {
      background: transparent;
      border: 1px solid rgba(255,255,255,0.15);
      color: #fff;
      border-radius: 10px;
      padding: 6px 10px;
      cursor: pointer;
    }
    .qr-scan-status {
      margin-top: 10px;
      font-size: 13px;
      color: rgba(255,255,255,0.75);
    }
    #qrReader {
      width: 100%;
      border-radius: 12px;
      overflow: hidden;
    }
  </style>
</head>
<body class="auth-bg text-white fade-in">
  <!-- QR Scanner Modal (No login required) -->
  <div id="qrScanBackdrop" class="qr-scan-backdrop" aria-hidden="true">
    <div class="qr-scan-panel" role="dialog" aria-modal="true" aria-label="QR Scanner">
      <div class="qr-scan-header">
        <div class="qr-scan-title"><i class="fa-solid fa-camera"></i> Scan Employee QR</div>
        <button type="button" class="qr-close" id="closeQrScannerBtn">Close</button>
      </div>
      <div id="qrReader"></div>
      <div class="qr-scan-status" id="qrScanStatus">Allow camera access, then point at the QR code.</div>
      <!-- QR Result Display -->
      <div id="qrResultArea" style="display: none; padding: 16px; text-align: center;">
        <div id="qrResultMessage" style="font-weight: 600; margin-bottom: 12px;"></div>
        <button type="button" id="scanAnotherBtn" style="background: #FFD700; border: none; color: #0b0b0b; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600;">
          <i class="fa-solid fa-camera"></i> Scan Another
        </button>
      </div>
    </div>
  </div>

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
        <div class="flex items-center justify-between">
          <h3 class="text-2xl font-semibold">Log In</h3>
          <button type="button" id="openQrScannerBtn" aria-label="Open QR Scanner" class="text-orange-400 hover:text-orange-300" style="font-size: 20px; padding: 6px 8px; border-radius: 8px;">
            <i class="fa-solid fa-qrcode"></i>
          </button>
        </div>
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

        <?php if (!empty($warnings)): ?>
          <div class="mt-4 text-yellow-200 bg-yellow-900/20 p-3 rounded">
            <?php foreach($warnings as $warn) echo '<div>'.htmlspecialchars($warn).'</div>'; ?>
          </div>
        <?php endif; ?>

        <form method="POST" class="mt-6 space-y-4">
          <div>
            <label class="block text-sm">Employee Code or Email</label>
            <input name="identifier" class="mt-2 w-full p-3 rounded input-field" 
                   placeholder="e.g. E12345 or you@company.com" 
                   value="<?php echo htmlspecialchars($_POST['identifier'] ?? ''); ?>" />
          </div>
          
          <input type="hidden" name="branch_name" value="Main Branch" />
          
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

        <div class="mt-6 text-sm small-muted">
          <p><strong>Note for Super Admins:</strong> You will see ALL branches in the attendance system.</p>
          <p><strong>Note for Regular Users:</strong> You will only see employees from your assigned branch.</p>
        </div>
      </div>

    </div>
  </div>

  <script src="assets/js/auth.js" defer></script>
  <script src="https://unpkg.com/html5-qrcode"></script>
  <script>
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

    // QR Scanner (kiosk mode: does not require login)
    (function() {
      const openBtn = document.getElementById('openQrScannerBtn');
      const backdrop = document.getElementById('qrScanBackdrop');
      const closeBtn = document.getElementById('closeQrScannerBtn');
      const statusEl = document.getElementById('qrScanStatus');
      const qrReader = document.getElementById('qrReader');
      const qrResultArea = document.getElementById('qrResultArea');
      const qrResultMessage = document.getElementById('qrResultMessage');
      const scanAnotherBtn = document.getElementById('scanAnotherBtn');

      let qr = null;
      let isRunning = false;

      function setStatus(msg) {
        if (statusEl) statusEl.textContent = msg;
      }

      function showResult(success, message) {
        if (qrReader) qrReader.style.display = 'none';
        if (statusEl) statusEl.style.display = 'none';
        if (qrResultArea) {
          qrResultArea.style.display = 'block';
          qrResultMessage.textContent = message;
          qrResultMessage.style.color = success ? '#10b981' : '#ef4444';
        }
      }

      function resetScanner() {
        if (qrReader) qrReader.style.display = 'block';
        if (statusEl) {
          statusEl.style.display = 'block';
          statusEl.textContent = 'Allow camera access, then point at the QR code.';
        }
        if (qrResultArea) qrResultArea.style.display = 'none';
        startScanner();
      }

      async function stopScanner() {
        if (!qr) return;
        if (!isRunning) return;
        try {
          await qr.stop();
        } catch (e) {
          // ignore
        }
        try {
          await qr.clear();
        } catch (e) {
          // ignore
        }
        isRunning = false;
      }

      // Parse employee code/ID from QR text
      function parseEmployeeFromQR(text) {
        // Try to extract from URL format: .../select_employee.php?auto_timein=1&emp_id=123&emp_code=ABC
        const empIdMatch = text.match(/[?&]emp_id=(\d+)/);
        const empCodeMatch = text.match(/[?&]emp_code=([^&]+)/);
        
        if (empIdMatch || empCodeMatch) {
          return {
            emp_id: empIdMatch ? empIdMatch[1] : null,
            emp_code: empCodeMatch ? decodeURIComponent(empCodeMatch[1]) : null
          };
        }
        
        // If QR contains only employee code (plain text)
        const plainCode = text.trim();
        if (plainCode && !plainCode.includes('/')) {
          return { emp_id: null, emp_code: plainCode };
        }
        
        return null;
      }

      // Call QR clock API via AJAX
      async function processClockIn(empId, empCode) {
        const url = `${window.location.origin}/employee/api/qr_clock.php`;
        const formData = new FormData();
        formData.append('action', 'in');
        formData.append('employee_id', empId);
        if (empCode) formData.append('employee_code', empCode);

        try {
          const response = await fetch(url, {
            method: 'POST',
            body: formData
          });
          
          const text = await response.text();
          let data;
          try {
            data = JSON.parse(text);
          } catch (e) {
            return { success: false, message: 'Server: ' + text.substring(0, 80) };
          }
          
          if (data.success) {
            return { success: true, message: data.message };
          } else if (data.already_in) {
            // Already clocked in, trigger clock-out
            return await processClockOut(empId, empCode);
          } else {
            return { success: false, message: data.message || 'Failed to record time-in' };
          }
        } catch (err) {
          return { success: false, message: 'Error: ' + (err.message || 'Cannot connect') };
        }
      }

      // Call QR clock-out API via AJAX
      async function processClockOut(empId, empCode) {
        const url = `${window.location.origin}/employee/api/qr_clock.php`;
        const formData = new FormData();
        formData.append('action', 'out');
        formData.append('employee_id', empId);
        if (empCode) formData.append('employee_code', empCode);

        try {
          const response = await fetch(url, {
            method: 'POST',
            body: formData
          });
          
          const data = await response.json();
          
          if (data.success) {
            return { success: true, message: data.message };
          } else {
            return { success: false, message: data.message || 'Failed to record time-out' };
          }
        } catch (err) {
          return { success: false, message: 'Error: ' + (err.message || 'Cannot connect') };
        }
      }

      async function startScanner() {
        if (typeof Html5Qrcode === 'undefined') {
          setStatus('QR scanner library is still loading. Please try again.');
          return;
        }

        if (!qr) {
          qr = new Html5Qrcode('qrReader');
        }

        setStatus('Starting camera...');

        const config = { fps: 10, qrbox: { width: 260, height: 260 } };

        try {
          const startWith = async (cameraConfig) => {
            await qr.start(
              cameraConfig,
              config,
              async (decodedText) => {
                // Parse employee info from QR
                const empInfo = parseEmployeeFromQR(decodedText);
                if (!empInfo || (!empInfo.emp_id && !empInfo.emp_code)) {
                  setStatus('Invalid QR code. Please scan a valid employee QR.');
                  return;
                }

                setStatus('Processing...');
                await stopScanner();

                // Process clock-in/out via AJAX
                const result = await processClockIn(empInfo.emp_id, empInfo.emp_code);
                showResult(result.success, result.message);
              },
              () => {
                // ignore scan errors to avoid spamming UI
              }
            );
            isRunning = true;
            setStatus('Scanning...');
          };

          try {
            await startWith({ facingMode: { exact: 'environment' } });
          } catch (e1) {
            await startWith({ facingMode: 'environment' });
          }
        } catch (e) {
          console.error(e);
          const name = e && (e.name || e.toString());
          const msg = (e && e.message) ? e.message : '';

          if (name && String(name).includes('NotAllowedError')) {
            setStatus('Camera blocked. Check Chrome site settings: Camera must be Allowed for this site.');
          } else if (name && String(name).includes('NotFoundError')) {
            setStatus('No camera found on this device.');
          } else if (msg && msg.toLowerCase().includes('permission')) {
            setStatus('Camera permission denied. Please allow camera access and try again.');
          } else {
            setStatus('Unable to start camera. Please try again or refresh the page.');
          }
        }
      }

      function openModal() {
        backdrop.style.display = 'flex';
        backdrop.setAttribute('aria-hidden', 'false');
        if (qrReader) qrReader.style.display = 'block';
        if (statusEl) statusEl.style.display = 'block';
        if (qrResultArea) qrResultArea.style.display = 'none';
        setStatus('Allow camera access, then point at the QR code.');
        setTimeout(() => startScanner(), 150);
      }

      async function closeModal() {
        await stopScanner();
        backdrop.style.display = 'none';
        backdrop.setAttribute('aria-hidden', 'true');
      }

      if (openBtn) openBtn.addEventListener('click', openModal);
      if (closeBtn) closeBtn.addEventListener('click', closeModal);
      if (scanAnotherBtn) scanAnotherBtn.addEventListener('click', resetScanner);
      if (backdrop) {
        backdrop.addEventListener('click', function(e) {
          if (e.target === backdrop) closeModal();
        });
      }
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && backdrop && backdrop.style.display === 'flex') {
          closeModal();
        }
      });
    })();
  </script>
</body>
</html>