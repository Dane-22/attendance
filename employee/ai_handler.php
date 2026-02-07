<?php
// employee/ai_handler.php - SIMPLE FIX FOR GEMINI 2.5
require_once __DIR__ . '/../conn/db_connection.php';
session_start();

header('Content-Type: application/json');

$sessionPosition = $_SESSION['position'] ?? '';
$sessionRole = $_SESSION['role'] ?? '';
$sessionUserRole = $_SESSION['user_role'] ?? '';
$isPrivileged = in_array($sessionPosition, ['Admin', 'Super Admin', 'Engineer'], true)
    || in_array($sessionRole, ['Admin', 'Super Admin', 'Engineer'], true)
    || in_array($sessionUserRole, ['Admin', 'Super Admin', 'Engineer'], true);

// Allow access if logged in via either session style used in the app.
$isLoggedIn = (!empty($_SESSION['logged_in'])) || (!empty($_SESSION['employee_code']));

if (!$isLoggedIn || !$isPrivileged) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$rawInput = file_get_contents('php://input');
$jsonData = json_decode($rawInput, true);
$message = trim($_POST['message'] ?? $jsonData['message'] ?? '');
$page = (string)($_POST['page'] ?? $jsonData['page'] ?? '');

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

$pageKey = basename($page);
$pageHelp = '';
$instructionsFile = __DIR__ . '/../include/ai_instructions.md';

if (is_file($instructionsFile)) {
    $all = (string)file_get_contents($instructionsFile);
    $target = ($pageKey !== '') ? $pageKey : 'default';

    $normalized = str_replace("\r\n", "\n", $all);

    $pattern = '/^##\s*' . preg_quote($target, '/') . '\s*\n(.*?)(?=\n##\s*|\z)/ms';
    if (preg_match($pattern, $normalized, $m)) {
        $pageHelp = trim($m[1]);
    } else {
        $fallbackPattern = '/^##\s*default\s*\n(.*?)(?=\n##\s*|\z)/ms';
        if (preg_match($fallbackPattern, $normalized, $m2)) {
            $pageHelp = trim($m2[1]);
        }
    }
}

$full_prompt = "You are JAJR Company AI Assistant.\n\n";
if ($pageHelp !== '') {
    $full_prompt .= "Help Instructions:\n" . $pageHelp . "\n\n";
}
$full_prompt .= "Use this context: $context\n\nUser Question: $message";

// Use Gemini 2.5 Flash (from your test results)
$api_key = getenv('GEMINI_API_KEY') ?: '';
if ($api_key === '') {
    http_response_code(500);
    echo json_encode([
        'error' => 'AI is not configured (missing GEMINI_API_KEY).',
        'hint' => 'Set an environment variable GEMINI_API_KEY on the server and restart the web server.'
    ]);
    exit;
}
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

$caBundle = getenv('CURL_CA_BUNDLE') ?: (getenv('SSL_CERT_FILE') ?: '');
$allowInsecure = getenv('ALLOW_INSECURE_SSL') === '1';

$curlOpts = [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_SSL_VERIFYPEER => $allowInsecure ? false : true,
    CURLOPT_SSL_VERIFYHOST => $allowInsecure ? 0 : 2,
    CURLOPT_TIMEOUT => 15
];

if (!$allowInsecure && $caBundle !== '' && file_exists($caBundle)) {
    $curlOpts[CURLOPT_CAINFO] = $caBundle;
}

curl_setopt_array($ch, $curlOpts);

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