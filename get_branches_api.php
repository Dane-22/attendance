<?php
if (file_exists(__DIR__ . '/conn/db_connection.php')) {
    require_once __DIR__ . '/conn/db_connection.php';
} else {
    require_once __DIR__ . '/db_connection.php';
}

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

$sql = "SELECT id, branch_name FROM branches WHERE is_active = 1 ORDER BY branch_name ASC";
$result = mysqli_query($db, $sql);

$branches = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $branches[] = [
            'id' => (int)$row['id'],
            'branch_name' => $row['branch_name'],
        ];
    }
}

echo json_encode(['success' => true, 'branches' => $branches]);

mysqli_close($db);
?>
