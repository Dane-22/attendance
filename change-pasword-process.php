<?php
// change-password-process.php

function syncToProcurement($employee_id, $hashed_password) {
    // TAMA NA ITONG URL: Kinabit natin ang /api/sync-password base sa code ng kasama mo
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
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "X-API-KEY: $secret_key"
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return json_decode($response, true);
}

// EXAMPLE NG PAG-CALL (Ilagay mo sa part ng code mo kung saan nag-success ang update sa Attendance DB)
/*
if ($update_attendance_db_success) {
    $employee_id = "PRO-2026-0001"; // Kunin mula sa session o database
    $new_hash = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
    
    $sync = syncToProcurement($employee_id, $new_hash);
}
*/