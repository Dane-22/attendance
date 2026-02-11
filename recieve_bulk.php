<?php
// 1. Headers para sa API communication
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

// 2. Database Connection
require_once 'conn/db_connection.php'; 

// 3. Basahin ang JSON data na pinadala (yung JSON array)
$json_data = file_get_contents("php://input");
$branches = json_decode($json_data, true);

if (!empty($branches) && is_array($branches)) {
    try {
        $count = 0;
        
        // Gagamit tayo ng Prepared Statement para sa security
        // Nilagyan natin ng "ON DUPLICATE KEY UPDATE" para kung existing na yung ID, i-uupdate lang
        $sql = "INSERT INTO branches (id, branch_name, branch_address, created_at, is_active) 
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                branch_name = VALUES(branch_name), 
                branch_address = VALUES(branch_address), 
                is_active = VALUES(is_active)";
        
        $stmt = $db->prepare($sql);

        foreach ($branches as $row) {
            // Siguraduhin na tumutugma sa columns ng table mo
            $stmt->execute([
                $row['id'],
                $row['branch_name'],
                $row['branch_address'],
                $row['created_at'],
                $row['is_active']
            ]);
            $count++;
        }

        echo json_encode([
            "status" => "success",
            "message" => "Successfully received and saved $count branches."
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Database Error: " . $e->getMessage()
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Invalid or empty JSON data received."
    ]);
}
?>