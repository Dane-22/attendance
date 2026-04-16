<?php
// update_env.php - Set correct database credentials

$envPath = __DIR__ . '/.env';

$newEnv = <<<'ENV'
# Database Configuration
DB_HOST=localhost
DB_USER=jajr_user
DB_PASS=SecurePass123!
DB_SCHEMA=attendance_v2

# API Keys
GEMINI_API_KEY=AIzaSyBWu7rBWpsDaaTLG8qktsT5drCq0EYBeT0

# Web Push Notification VAPID Keys
VAPID_PUBLIC_KEY=BKyvFnHq0kFWpxvQzyb8VxujX4UTwvriApiwxrWzhQd78Lh0SAriugpMyOqidm3MPVfiRRaZGo6MTsM8Xdi0Rzs
VAPID_PRIVATE_KEY=FK3kGs6a6XA-s9fc985L56MxtqRg9WID-1EPo6vAfyE
VAPID_SUBJECT=mailto:admin@jajr.com
ENV;

file_put_contents($envPath, $newEnv);
echo ".env updated successfully with correct credentials!<br><br>";
echo "<a href='/'>Click to test the app</a>";
?>
