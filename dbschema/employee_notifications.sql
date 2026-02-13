-- employee_notifications.sql
-- Create table for employee notification system (Option B)

CREATE TABLE IF NOT EXISTS employee_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    overtime_request_id INT NOT NULL,
    notification_type ENUM('overtime_approved', 'overtime_rejected') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    INDEX idx_employee_read (employee_id, is_read),
    INDEX idx_created (created_at DESC),
    FOREIGN KEY (overtime_request_id) REFERENCES overtime_requests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
