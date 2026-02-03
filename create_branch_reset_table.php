<?php
// Script to add branch_reset_log table
require('conn/db_connection.php');

// SQL to create the table
$sql = "
DROP TABLE IF EXISTS `branch_reset_log`;
CREATE TABLE IF NOT EXISTS `branch_reset_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reset_date` date NOT NULL,
  `employees_affected` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reset_date` (`reset_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
";

if (mysqli_query($db, $sql)) {
    echo "Table branch_reset_log created successfully.<br>";

    // Insert initial record
    $insert_sql = "INSERT INTO `branch_reset_log` (`reset_date`, `employees_affected`) VALUES (CURDATE(), 0)";
    if (mysqli_query($db, $insert_sql)) {
        echo "Initial record inserted successfully.<br>";
    } else {
        echo "Error inserting initial record: " . mysqli_error($db) . "<br>";
    }
} else {
    echo "Error creating table: " . mysqli_error($db) . "<br>";
}

mysqli_close($db);
?>