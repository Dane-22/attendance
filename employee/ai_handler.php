<?php
// employee/ai_handler.php - SIMPLE FIX FOR GEMINI 2.5
require_once __DIR__ . '/../conn/db_connection.php';
session_start();

if (empty($_SESSION['logged_in']) || !in_array($_SESSION['position'], ['Admin', 'Super Admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$rawInput = file_get_contents('php://input');
$jsonData = json_decode($rawInput, true);
$message = trim($_POST['message'] ?? $jsonData['message'] ?? '');

if (empty($message)) {
    echo json_encode(['error' => 'Message is required']);
    exit;
}

// Context Gathering
$current_date = date('Y-m-d');
$branches_query = "SELECT branch_name FROM branches WHERE is_active = 1";
$branches_res = mysqli_query($db, $branches_query);
$branches = [];
while ($row = mysqli_fetch_assoc($branches_res)) { 
    $branches[] = $row['branch_name']; 
}

$total_employees_query = "SELECT COUNT(*) as count FROM employees WHERE status = 'Active'";
$total_employees_res = mysqli_query($db, $total_employees_query);
$total_employees = mysqli_fetch_assoc($total_employees_res)['count'];

$context = "Current Date: $current_date. Active Branches: " . implode(', ', $branches) . ". Total Active Employees: $total_employees.";
$full_prompt = "You are JAJR Company AI Assistant. Use this context: $context\n\nUser Question: $message";

// Use Gemini 2.5 Flash (from your test results)
$api_key = 'AIzaSyCF8wJtyvp7e4IcryIfM-nobZoXUWiokjo';
$model_name = 'models/gemini-2.5-flash'; // EXACTLY as shown in your test
$url = "https://generativelanguage.googleapis.com/v1/{$model_name}:generateContent?key=" . $api_key;

$payload = [
    "contents" => [
        [
            "parts" => [
                ["text" => $full_prompt]
            ]
        ]
    ],
    "generationConfig" => [
        "temperature" => 0.7,
        "maxOutputTokens" => 1024
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
    CURLOPT_TIMEOUT => 15
]);

$result = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo json_encode(['error' => 'cURL Error: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}
curl_close($ch);

if ($http_code === 200) {
    $res_data = json_decode($result, true);
    
    if (isset($res_data['candidates'][0]['content']['parts'][0]['text'])) {
        $reply = trim($res_data['candidates'][0]['content']['parts'][0]['text']);
        echo json_encode([
            'response' => $reply,
            'model_used' => 'gemini-2.5-flash',
            'status' => 'success'
        ]);
    } else {
        echo json_encode([
            'error' => 'Unexpected response format',
            'debug' => $res_data
        ]);
    }
} else {
    echo json_encode([
        'error' => "API Error ($http_code)",
        'debug' => $result
    ]);
}
?>