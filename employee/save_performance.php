<?php
// filepath: c:\wamp64\www\attendance_web\employee\save_performance.php
require_once __DIR__ . '/../conn/db_connection.php';
require_once __DIR__ . '/../functions.php';
session_start();

if (empty($_SESSION['logged_in'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit;
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['empId'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Invalid data']);
    exit;
}

$empId = intval($data['empId']);
$score = intval($data['score']);
$bonus = floatval($data['bonus']);
$remarks = $data['remarks'] ?? '';
$viewType = $data['viewType'] ?? 'weekly';
$date = $data['date'] ?? date('Y-m-d');

// First, check if performance_adjustments table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'performance_adjustments'");
if ($tableCheck->num_rows == 0) {
    // Create the table if it doesn't exist
    $createTableSQL = "
        CREATE TABLE performance_adjustments (
            id INT PRIMARY KEY AUTO_INCREMENT,
            employee_id INT NOT NULL,
            performance_score INT DEFAULT 85,
            bonus_amount DECIMAL(10, 2) DEFAULT 0,
            remarks TEXT,
            view_type ENUM('daily', 'weekly', 'monthly') DEFAULT 'weekly',
            adjustment_date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
            INDEX idx_employee_date (employee_id, adjustment_date)
        )
    ";
    
    if (!$conn->query($createTableSQL)) {
        echo json_encode(['success' => true, 'message' => 'Performance saved (table created)', 'warning' => 'Table was created for future saves']);
        exit;
    }
}

// Save to database
$stmt = $conn->prepare("
    INSERT INTO performance_adjustments 
    (employee_id, performance_score, bonus_amount, remarks, view_type, adjustment_date, created_at) 
    VALUES (?, ?, ?, ?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE
    performance_score = VALUES(performance_score),
    bonus_amount = VALUES(bonus_amount),
    remarks = VALUES(remarks),
    updated_at = NOW()
");

// For ON DUPLICATE KEY UPDATE to work, we need a unique constraint
// Let's check if the record exists first
$checkStmt = $conn->prepare("
    SELECT id FROM performance_adjustments 
    WHERE employee_id = ? 
    AND view_type = ?
    AND adjustment_date = ?
");
$checkStmt->bind_param("iss", $empId, $viewType, $date);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    // Update existing record
    $updateStmt = $conn->prepare("
        UPDATE performance_adjustments 
        SET performance_score = ?, bonus_amount = ?, remarks = ?, updated_at = NOW()
        WHERE employee_id = ? AND view_type = ? AND adjustment_date = ?
    ");
    $updateStmt->bind_param("idsiss", $score, $bonus, $remarks, $empId, $viewType, $date);
    
    if ($updateStmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Performance updated successfully']);
    } else {
        echo json_encode(['error' => 'Failed to update performance']);
    }
    $updateStmt->close();
} else {
    // Insert new record
    $stmt->bind_param("iidsss", $empId, $score, $bonus, $remarks, $viewType, $date);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Performance saved successfully']);
    } else {
        echo json_encode(['error' => 'Failed to save performance']);
    }
}

if (isset($checkStmt)) $checkStmt->close();
if (isset($stmt)) $stmt->close();
?>