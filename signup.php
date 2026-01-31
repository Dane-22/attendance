<?php
// signup.php
require_once __DIR__ . '/conn/db_connection.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $firstname = trim($_POST['firstname'] ?? '');
    $middlename = trim($_POST['middlename'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $employee_id = trim($_POST['employee_id'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation
    if (empty($firstname) || empty($lastname) || empty($employee_id) || empty($email) || empty($password)) {
        $errors[] = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    } else {
        // Check if employee ID already exists (prepared)
        $stmt = mysqli_prepare($db, "SELECT id FROM employees WHERE employee_code = ?");
        if ($stmt) {
          mysqli_stmt_bind_param($stmt, 's', $employee_id);
          mysqli_stmt_execute($stmt);
          mysqli_stmt_store_result($stmt);
          if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = 'Employee ID already exists.';
          }
          mysqli_stmt_close($stmt);
        }

        // Check if email already exists
        if (empty($errors)) {
          $stmt = mysqli_prepare($db, "SELECT id FROM employees WHERE email = ?");
          if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
              $errors[] = 'Email already exists.';
            }
            mysqli_stmt_close($stmt);
          }
        }

        // If no errors, insert into database
        if (empty($errors)) {
          // Hash the password using MD5 (per request)
          $hashed_password = md5($password);

          // Start transaction to ensure both inserts succeed or fail together
          mysqli_begin_transaction($db);
          
          try {
            // Insert into employees table
            $stmt = mysqli_prepare($db, "INSERT INTO employees (employee_code, first_name, middle_name, last_name, email, password_hash, position, status) VALUES (?, ?, ?, ?, ?, ?, 'Employee', 'Active')");
            if (!$stmt) {
              throw new Exception('Database error: could not prepare statement for employees.');
            }
            
            mysqli_stmt_bind_param($stmt, 'ssssss', $employee_id, $firstname, $middlename, $lastname, $email, $hashed_password);
            if (!mysqli_stmt_execute($stmt)) {
              throw new Exception('Database error: ' . mysqli_stmt_error($stmt));
            }
            
            $employee_insert_id = mysqli_insert_id($db);
            mysqli_stmt_close($stmt);
            
            // // Insert into attendance table with status 'time_out' as default
            // $stmt2 = mysqli_prepare($db, "INSERT INTO attendance (employee_id, status, attendance_date, created_at) VALUES (?, 'time_out', CURDATE(), CURRENT_TIMESTAMP)");
            // if (!$stmt2) {
            //   throw new Exception('Database error: could not prepare statement for attendance.');
            // }
            
            // mysqli_stmt_bind_param($stmt2, 's', $employee_id);
            // if (!mysqli_stmt_execute($stmt2)) {
            //   throw new Exception('Database error: ' . mysqli_stmt_error($stmt2));
            // }
            
            // mysqli_stmt_close($stmt2);
            
            // // Commit transaction
            // mysqli_commit($db);
            
            $success = true;
            $success_message = 'Account created successfully! You can now login.';
            
          } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($db);
            $errors[] = $e->getMessage();
          }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Sign Up — JAJR Company</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="assets/style_auth.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="auth-bg text-white fade-in">
  <div class="min-h-screen flex items-center justify-center px-6">
    <div class="auth-card w-full max-w-4xl grid grid-cols-1 md:grid-cols-2 overflow-hidden">

      <div class="auth-left hidden md:flex flex-col justify-center items-start gap-6 px-8 bg-black/20">
        <div class="w-full text-left">
          <h2 class="text-3xl font-bold">Create Account</h2>
          <p class="mt-2 small-muted">Join JAJR and start tracking attendance and projects.</p>
        </div>

        <div class="mt-6">
          <!-- Pulsing grid SVG -->
          <svg width="220" height="220" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" aria-hidden>
            <rect width="100" height="100" fill="rgba(255,255,255,0.02)" />
            <g stroke="rgba(255,255,255,0.06)" class="grid-pulse">
              <path d="M0 10 H100"/>
              <path d="M0 30 H100"/>
              <path d="M0 50 H100"/>
              <path d="M0 70 H100"/>
              <path d="M0 90 H100"/>
            </g>
          </svg>
        </div>

        <div class="mt-auto text-sm small-muted">Already have an account? <a href="login.php" data-transition class="text-orange-400">Sign in</a></div>
      </div>

      <div class="auth-right px-8 py-10">
        <h3 class="text-2xl font-semibold">Sign Up</h3>
        <p class="small-muted mt-2">Create your engineering account.</p>

        <?php if ($success): ?>
          <div class="mt-4 text-green-300 bg-green-900/20 p-3 rounded">
            <div><?php echo htmlspecialchars($success_message); ?></div>
            <div class="mt-2">
              <a href="login.php" class="text-orange-400 underline">Click here to login</a>
            </div>
          </div>
        <?php elseif (!empty($errors)): ?>
          <div class="mt-4 text-red-300 bg-red-900/20 p-3 rounded">
            <?php foreach($errors as $err) echo '<div>'.htmlspecialchars($err).'</div>'; ?>
          </div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST" class="mt-6 space-y-4">
          <div>
            <label class="block text-sm">First Name *</label>
            <input name="firstname" class="mt-2 w-full p-3 rounded input-field" placeholder="Daniel" 
                   value="<?php echo htmlspecialchars($_POST['firstname'] ?? ''); ?>" required />
          </div>
          
          <div>
            <label class="block text-sm">Middle Name</label>
            <input name="middlename" class="mt-2 w-full p-3 rounded input-field" placeholder="Obaldo" 
                   value="<?php echo htmlspecialchars($_POST['middlename'] ?? ''); ?>" />
          </div>
          
          <div>
            <label class="block text-sm">Last Name *</label>
            <input name="lastname" class="mt-2 w-full p-3 rounded input-field" placeholder="Rillera" 
                   value="<?php echo htmlspecialchars($_POST['lastname'] ?? ''); ?>" required />
          </div>
          
          <div>
            <label class="block text-sm">Employee Code *</label>
            <input name="employee_id" class="mt-2 w-full p-3 rounded input-field" placeholder="E12345" 
                   value="<?php echo htmlspecialchars($_POST['employee_id'] ?? ''); ?>" required />
          </div>
          
          <div>
            <label class="block text-sm">Email *</label>
            <input name="email" type="email" class="mt-2 w-full p-3 rounded input-field" placeholder="you@gmail.com" 
                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required />
          </div>
          
          <div class="relative">
            <label class="block text-sm">Password *</label>
            <div class="relative">
              <input type="password" name="password" id="password" class="mt-2 w-full p-3 pr-10 rounded input-field" placeholder="Create a strong password" required />
              <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 pr-3 flex items-center mt-2" aria-label="Show password">
                <i class="fas fa-eye text-gray-400 hover:text-gray-300"></i>
              </button>
            </div>
            <!-- <p class="text-xs text-gray-400 mt-1">Minimum 6 characters</p> -->
          </div>         
          <div>
            <button type="submit" class="w-full py-3 rounded btn-glow bg-gradient-to-r from-orange-400 to-black text-black font-semibold">
              Create Account
            </button>
          </div>
        </form>
        <?php endif; ?>

      </div>

    </div>
  </div>

 <script>
    // Password toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
      const togglePassword = document.getElementById('togglePassword');
      const passwordInput = document.getElementById('password');
      
      if (togglePassword && passwordInput) {
        const eyeIcon = togglePassword.querySelector('i');
        
        togglePassword.addEventListener('click', function() {
          // Toggle the password input type
          const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
          passwordInput.setAttribute('type', type);
          
          // Toggle the eye icon
          if (type === 'text') {
            eyeIcon.classList.remove('fa-eye');
            eyeIcon.classList.add('fa-eye-slash');
            togglePassword.setAttribute('aria-label', 'Hide password');
          } else {
            eyeIcon.classList.remove('fa-eye-slash');
            eyeIcon.classList.add('fa-eye');
            togglePassword.setAttribute('aria-label', 'Show password');
          }
        });
      }
    });
  </script>

  <script src="assets/js/auth.js" defer></script>

</body>
</html>