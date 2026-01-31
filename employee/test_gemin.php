<?php
// test_gemini.php
// Ilagay ito sa root folder at i-access sa browser

$api_key = 'AIzaSyCF8wJtyvp7e4IcryIfM-nobZoXUWiokjo';

// Test 1: List available models
echo "<h2>Testing Gemini API Key</h2>";
echo "<h3>API Key: " . substr($api_key, 0, 10) . "...</h3>";

$list_url = "https://generativelanguage.googleapis.com/v1/models?key=" . $api_key;
$ch = curl_init($list_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT => 10
]);

$result = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "<h3>Test 1: List Models</h3>";
echo "HTTP Code: " . $http_code . "<br>";

if ($http_code === 200) {
    $data = json_decode($result, true);
    echo "<pre>" . print_r($data, true) . "</pre>";
    
    // List available models
    if (isset($data['models'])) {
        echo "<h4>Available Models:</h4>";
        foreach ($data['models'] as $model) {
            echo $model['name'] . " - " . $model['displayName'] . "<br>";
        }
    }
} else {
    echo "Error: " . $result . "<br>";
    echo "cURL Error: " . $curl_error . "<br>";
}

// Test 2: Try simple generateContent
echo "<h3>Test 2: Simple Generate Content</h3>";
$test_models = ['gemini-pro', 'models/gemini-pro'];
foreach ($test_models as $model) {
    echo "Trying model: " . $model . "<br>";
    
    $url = "https://generativelanguage.googleapis.com/v1/{$model}:generateContent?key=" . $api_key;
    
    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => "Hello, how are you?"]
                ]
            ]
        ]
    ];
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 10
    ]);
    
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: " . $http_code . "<br>";
    if ($http_code === 200) {
        $data = json_decode($result, true);
        echo "Success! Response: " . $data['candidates'][0]['content']['parts'][0]['text'] . "<br>";
        break;
    } else {
        echo "Failed: " . $result . "<br><br>";
    }
}
?>