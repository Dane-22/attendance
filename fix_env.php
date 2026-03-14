<?php
$env = <<<'ENV'
# Add your environment variables here
# Example:
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_SCHEMA=attendance_db
GEMINI_API_KEY=AIzaSyBWu7rBWpsDaaTLG8qktsT5drCq0EYBeT0
# Web Push Notification VAPID Keys
# Generate keys using: npx web-push generate-vapid-keys
VAPID_PUBLIC_KEY=BKyvFnHq0kFWpxvQzyb8VxujX4UTwvriApiwxrWzhQd78Lh0SAriugpMyOqidm3MPVfiRRaZGo6MTsM8Xdi0Rzs
VAPID_PRIVATE_KEY=FK3kGs6a6XA-s9fc985L56MxtqRg9WID-1EPo6vAfyE
VAPID_SUBJECT=mailto:admin@jajr.com
ENV;
file_put_contents(__DIR__ . '/.env', $env);
echo '.env file updated successfully';
?>
