-- overtime_requests.sql
-- Create table for overtime approval workflow

CREATE TABLE IF NOT EXISTS overtime_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    branch_name VARCHAR(255) NOT NULL,
    request_date DATE NOT NULL,
    requested_hours DECIMAL(5,2) NOT NULL,
    overtime_reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    requested_by VARCHAR(255) NOT NULL,
    requested_by_user_id INT NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_by VARCHAR(255) NULL,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT NULL,
    attendance_id INT NULL,
    INDEX idx_employee_date (employee_id, request_date),
    INDEX idx_status (status),
    INDEX idx_requested_at (requested_at),
    INDEX idx_requested_by_user (requested_by_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
