<?php
require_once __DIR__ . '/vendor/autoload.php';

$keys = \Minishlink\WebPush\VAPID::createVapidKeys();

echo "=== New VAPID Keys ===\n";
echo "VAPID_PUBLIC_KEY=" . $keys['publicKey'] . "\n";
echo "VAPID_PRIVATE_KEY=" . $keys['privateKey'] . "\n";
echo "\nAdd these to your .env file\n";
