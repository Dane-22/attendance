<?php
// install_webpush.php - Helper to install web-push-php library
// Run this script once to set up the required dependencies

// Check if composer is available
$composerExists = false;
exec('composer --version 2>&1', $output, $returnCode);
if ($returnCode === 0) {
    $composerExists = true;
}

if (!$composerExists) {
    // Try to download composer
    echo "Composer not found. Attempting to download...\n";
    
    $composerUrl = 'https://getcomposer.org/download/2.6.6/composer.phar';
    $composerPhar = __DIR__ . '/composer.phar';
    
    if (!file_exists($composerPhar)) {
        file_put_contents($composerPhar, file_get_contents($composerUrl));
        echo "Composer downloaded.\n";
    }
    
    // Use downloaded composer
    $composerCmd = 'php ' . escapeshellarg($composerPhar);
} else {
    $composerCmd = 'composer';
}

// Create composer.json if it doesn't exist
$composerJson = __DIR__ . '/composer.json';
if (!file_exists($composerJson)) {
    $config = [
        'name' => 'jajr/attendance',
        'description' => 'JAJR Attendance System',
        'require' => [
            'minishlink/web-push' => '^8.0'
        ],
        'autoload' => [
            'psr-4' => [
                'JAJR\\' => 'src/'
            ]
        ]
    ];
    file_put_contents($composerJson, json_encode($config, JSON_PRETTY_PRINT));
    echo "Created composer.json\n";
}

// Install dependencies
echo "Installing web-push-php library...\n";
exec($composerCmd . ' install 2>&1', $output, $returnCode);

if ($returnCode === 0) {
    echo "\n✅ web-push-php installed successfully!\n";
    echo "You can now use the library for push notifications.\n";
} else {
    echo "\n❌ Installation failed. Output:\n";
    echo implode("\n", $output);
}
