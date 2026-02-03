<?php
if (file_exists(__DIR__ . '/conn/db_connection.php')) {
    require_once __DIR__ . '/conn/db_connection.php';
} else {
    require_once __DIR__ . '/db_connection.php';
}

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

function json_error($message, $statusCode = 400) {
    http_response_code($statusCode);
    echo json_encode(["success" => false, "message" => $message]);
    exit;
}

function get_bearer_token() {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $auth = '';

    if (isset($headers['Authorization'])) {
        $auth = $headers['Authorization'];
    } elseif (isset($headers['authorization'])) {
        $auth = $headers['authorization'];
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth = $_SERVER['HTTP_AUTHORIZATION'];
    }

    if (!$auth) return null;

    if (preg_match('/Bearer\s+(.*)$/i', $auth, $m)) {
        return trim($m[1]);
    }

    return null;
}

function parse_app_token($token) {
    if (!$token) return null;
    if (!preg_match('/^user_(\d+)_([^\s]+)$/', $token, $m)) {
        return null;
    }
    return [
        'user_id' => (int)$m[1],
        'employee_code' => $m[2],
    ];
}

function is_md5_hash($value) {
    return is_string($value) && preg_match('/^[a-f0-9]{32}$/i', $value);
}

function is_hex_32($value) {
    return is_string($value) && preg_match('/^[a-f0-9]{32}$/i', $value);
}

$employee_id = isset($_POST['employee_id']) ? (int)$_POST['employee_id'] : 0;
if ($employee_id <= 0) {
    json_error('employee_id is required');
}

$token = get_bearer_token();
$tokenData = parse_app_token($token);

if ($tokenData) {
    if ($tokenData['user_id'] !== $employee_id) {
        json_error('Unauthorized: token does not match employee_id', 401);
    }
}

$allowedFields = [
    'first_name' => 's',
    'middle_name' => 's',
    'last_name' => 's',
    'email' => 's',
];

$updates = [];
$types = '';
$params = [];

foreach ($allowedFields as $field => $type) {
    if (isset($_POST[$field])) {
        $value = trim((string)$_POST[$field]);
        $updates[] = "`$field` = ?";
        $types .= $type;
        $params[] = $value;
    }
}

$wantsPasswordChange = isset($_POST['current_password']) || isset($_POST['new_password']);
if ($wantsPasswordChange) {
    $current_password = isset($_POST['current_password']) ? (string)$_POST['current_password'] : '';
    $new_password = isset($_POST['new_password']) ? (string)$_POST['new_password'] : '';

    if ($current_password === '' || $new_password === '') {
        json_error('current_password and new_password are required for password change');
    }

    $stmt = mysqli_prepare($db, 'SELECT password_hash FROM employees WHERE id = ? LIMIT 1');
    if (!$stmt) {
        json_error('Database error (prepare failed)', 500);
    }

    mysqli_stmt_bind_param($stmt, 'i', $employee_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);

    if (!$row) {
        json_error('Employee not found', 404);
    }

    $storedHash = (string)($row['password_hash'] ?? '');

    $verified = false;
    if (is_hex_32($storedHash)) {
        $verified = strtolower($storedHash) === strtolower(md5($current_password));
    } else {
        $verified = password_verify($current_password, $storedHash);
    }

    if (!$verified) {
        json_error('Current password is incorrect', 401);
    }

    $usePasswordHash = isset($_POST['use_password_hash']) && (string)$_POST['use_password_hash'] === '1';
    $newHash = $usePasswordHash ? password_hash($new_password, PASSWORD_DEFAULT) : md5($new_password);

    $updates[] = '`password_hash` = ?';
    $types .= 's';
    $params[] = $newHash;
}

$profileImagePath = null;
if (isset($_FILES['profile_image']) && isset($_FILES['profile_image']['tmp_name']) && is_uploaded_file($_FILES['profile_image']['tmp_name'])) {
    $uploadsDir = __DIR__ . '/uploads';
    if (!is_dir($uploadsDir)) {
        @mkdir($uploadsDir, 0755, true);
    }

    $originalName = (string)($_FILES['profile_image']['name'] ?? '');
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if ($ext === '') {
        $ext = 'jpg';
    }

    $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowedExt, true)) {
        json_error('Invalid image type. Allowed: jpg, jpeg, png, webp');
    }

    $fileName = 'profile_' . uniqid('', true) . '.' . $ext;
    $dest = $uploadsDir . '/' . $fileName;

    if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $dest)) {
        json_error('Failed to upload image', 500);
    }

    $profileImagePath = $fileName;
    $updates[] = '`profile_image` = ?';
    $types .= 's';
    $params[] = $profileImagePath;
}

if (count($updates) === 0) {
    json_error('No fields to update');
}

$updates[] = '`updated_at` = CURRENT_TIMESTAMP';

$sql = 'UPDATE employees SET ' . implode(', ', $updates) . ' WHERE id = ? LIMIT 1';
$typesFinal = $types . 'i';
$params[] = $employee_id;

$stmt = mysqli_prepare($db, $sql);
if (!$stmt) {
    json_error('Database error (prepare failed)', 500);
}

mysqli_stmt_bind_param($stmt, $typesFinal, ...$params);
$ok = mysqli_stmt_execute($stmt);
$errno = mysqli_errno($db);
$err = mysqli_error($db);
mysqli_stmt_close($stmt);

if (!$ok) {
    if ($errno === 1062) {
        json_error('Duplicate value (email or employee_code already exists)', 409);
    }
    json_error('Update failed: ' . $err, 500);
}

$stmt = mysqli_prepare($db, 'SELECT id, employee_code, first_name, last_name, email, branch_name, position, status, profile_image, daily_rate FROM employees WHERE id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $employee_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$user = $res ? mysqli_fetch_assoc($res) : null;
mysqli_stmt_close($stmt);

if (!$user) {
    json_error('Employee not found after update', 500);
}

echo json_encode([
    'success' => true,
    'message' => 'Profile updated successfully',
    'user_data' => [
        'id' => (int)$user['id'],
        'employee_code' => $user['employee_code'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'email' => $user['email'],
        'branch_name' => $user['branch_name'],
        'position' => $user['position'],
        'status' => $user['status'],
        'profile_image' => $user['profile_image'],
        'daily_rate' => $user['daily_rate'],
    ]
]);

mysqli_close($db);
?>
