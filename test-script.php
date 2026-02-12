<?php
// C:\wamp64\www\main\test-script.php

function syncToProcurement($employee_id, $hashed_password) {
    // ⚠️ TAMA NA ITONG URL PARA SA ONLINE SYSTEM NIYO
    $api_url = "https://procurement.xandree.com/api/sync-password"; 
    $secret_key = "7d6a4f9b2c8e5a1f3d0b2c4e6a8f0d1e2b4c6e8a0f1d3b5c7e9a1f3d5b7c9e";

    $post_data = [
        'employee_id' => $employee_id,
        'new_password_hash' => $hashed_password
    ];

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Importante ito para sa HTTPS connection sa localhost
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "X-API-KEY: $secret_key"
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    return [
        'status_code' => $http_code,
        'curl_error' => $curl_error,
        'response' => json_decode($response, true)
    ];
}

// ⚠️ Gamitin ang EXACT employee_id na nasa database nila (e.g., PRO-2026-0001)
$test_emp_id = "PRO-2026-0001"; 
$test_hash = password_hash("password123", PASSWORD_BCRYPT);

$result = syncToProcurement($test_emp_id, $test_hash);

header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);