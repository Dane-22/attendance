<?php
// reset_root_pass.php - Reset MySQL root password to empty
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Resetting MySQL Root Password</h3>";

// First try with empty password
$conn = @mysqli_connect('localhost', 'root', '');
if ($conn) {
    echo "✅ Already connected with empty password!<br>";
    mysqli_close($conn);
    echo "Your root password is already empty. No changes needed.<br>";
    echo "<a href='check_mysql.php'>Back to check</a> | <a href='/'>Go to app</a>";
    exit;
}

// Try various passwords to gain access
$passwords = ['root', 'toor', 'admin', 'password', 'mysql', '123456', 'wamp'];
$connected = false;

foreach ($passwords as $pass) {
    $conn = @mysqli_connect('localhost', 'root', $pass);
    if ($conn) {
        echo "✅ Connected with password: '$pass'<br>";
        $connected = true;
        
        // Reset password to empty
        $result = mysqli_query($conn, "ALTER USER 'root'@'localhost' IDENTIFIED BY ''");
        if ($result) {
            mysqli_query($conn, "FLUSH PRIVILEGES");
            echo "✅ Root password reset to empty successfully!<br><br>";
            echo "<b>Next steps:</b><br>";
            echo "1. Update .env: DB_PASS=<br>";
            echo "2. <a href='check_mysql.php'>Verify connection</a><br>";
            echo "3. <a href='/'>Go to app</a>";
        } else {
            echo "❌ Failed to reset password: " . mysqli_error($conn) . "<br>";
        }
        mysqli_close($conn);
        break;
    }
}

if (!$connected) {
    echo "❌ Could not connect with any known password.<br><br>";
    echo "<b>Manual fix required:</b><br>";
    echo "1. Open Windows Command Prompt as Administrator<br>";
    echo "2. Stop MySQL: <code>net stop wampmysqld64</code><br>";
    echo "3. Start with skip-grant-tables<br>";
    echo "4. Or open MySQL Workbench / phpMyAdmin to reset password<br>";
}
?>
