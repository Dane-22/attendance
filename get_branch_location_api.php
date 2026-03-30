<?php
// get_branch_location_api.php

require_once __DIR__ . '/conn/db_connection.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$branchName = trim((string)($_POST['branch_name'] ?? $_GET['branch_name'] ?? ''));

if ($branchName === '') {
    echo json_encode(['success' => false, 'message' => 'Missing branch_name']);
    exit;
}

$sql = "SELECT id, branch_name, branch_address, lat, `long`, geofence_radius_meters, location_verified
        FROM branches
        WHERE branch_name = ?
        LIMIT 1";

$stmt = mysqli_prepare($db, $sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed', 'error' => mysqli_error($db)]);
    exit;
}

mysqli_stmt_bind_param($stmt, 's', $branchName);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Branch not found']);
    exit;
}

$branch = [
    'id' => (int)$row['id'],
    'branch_name' => $row['branch_name'],
    'branch_address' => $row['branch_address'],
    'latitude' => $row['lat'],
    'longitude' => $row['long'],
    'geofence_radius_meters' => (int)($row['geofence_radius_meters'] ?? 200),
    'location_verified' => (int)($row['location_verified'] ?? 0),
];

echo json_encode(['success' => true, 'branch' => $branch]);

mysqli_close($db);
