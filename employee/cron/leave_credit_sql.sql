-- Leave Credit System SQL
-- Run these queries directly in your database

-- =====================================================
-- STEP 1: Create Tables (if they don't exist)
-- =====================================================

CREATE TABLE IF NOT EXISTS employee_leaves (
  id INT NOT NULL AUTO_INCREMENT,
  employee_id INT NOT NULL,
  total_leaves DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  used_leaves DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  remaining_leaves DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  last_credited_month DATE DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY unique_employee (employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS leave_transactions (
  id INT NOT NULL AUTO_INCREMENT,
  employee_id INT NOT NULL,
  transaction_type ENUM('credit', 'debit', 'adjustment') NOT NULL,
  amount DECIMAL(5,2) NOT NULL,
  description VARCHAR(255) NOT NULL,
  reference_date DATE NOT NULL,
  created_by INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_employee_date (employee_id, reference_date),
  KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- STEP 2: Initialize Leave Records for Active Employees
-- =====================================================
-- This creates empty records for employees who don't have one yet

INSERT INTO employee_leaves (employee_id, total_leaves, used_leaves, remaining_leaves, last_credited_month)
SELECT 
  e.id,
  0.00 AS total_leaves,
  0.00 AS used_leaves,
  0.00 AS remaining_leaves,
  NULL AS last_credited_month
FROM employees e
WHERE e.status = 'Active'
  AND NOT EXISTS (
    SELECT 1 FROM employee_leaves el WHERE el.employee_id = e.id
  );

-- =====================================================
-- STEP 3: Credit All Active Employees for April 2026
-- =====================================================
-- Run this to give 1.00 leave credit to all employees who haven't received April credit

-- First, see who needs credit:
SELECT e.id, e.first_name, e.last_name, e.employee_code, el.last_credited_month
FROM employees e
LEFT JOIN employee_leaves el ON e.id = el.employee_id
WHERE e.status = 'Active'
  AND (el.last_credited_month IS NULL OR el.last_credited_month < '2026-04-01');

-- Then, apply the credit:
UPDATE employee_leaves el
JOIN employees e ON el.employee_id = e.id
SET 
  el.total_leaves = el.total_leaves + 1.00,
  el.remaining_leaves = el.remaining_leaves + 1.00,
  el.last_credited_month = '2026-04-01'
WHERE e.status = 'Active'
  AND (el.last_credited_month IS NULL OR el.last_credited_month < '2026-04-01');

-- Log the transactions for audit trail:
INSERT INTO leave_transactions (employee_id, transaction_type, amount, description, reference_date, created_by, created_at)
SELECT 
  e.id,
  'credit',
  1.00,
  'Monthly leave credit',
  '2026-04-01',
  0,
  NOW()
FROM employees e
LEFT JOIN employee_leaves el ON e.id = el.employee_id
WHERE e.status = 'Active'
  AND (el.last_credited_month IS NULL OR el.last_credited_month = '2026-04-01');

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

-- Check current leave balances:
SELECT e.first_name, e.last_name, el.total_leaves, el.used_leaves, el.remaining_leaves, el.last_credited_month
FROM employee_leaves el
JOIN employees e ON el.employee_id = e.id
ORDER BY el.last_credited_month DESC;

-- Check transaction history:
SELECT lt.*, e.first_name, e.last_name 
FROM leave_transactions lt
JOIN employees e ON lt.employee_id = e.id
ORDER BY lt.created_at DESC
LIMIT 20;

-- =====================================================
-- MONTHLY AUTOMATION (for cron job or manual run)
-- =====================================================
-- For May 2026 and beyond, use these queries each month:

-- Credit all active employees for current month:
-- UPDATE employee_leaves el
-- JOIN employees e ON el.employee_id = e.id
-- SET 
--   el.total_leaves = el.total_leaves + 1.00,
--   el.remaining_leaves = el.remaining_leaves + 1.00,
--   el.last_credited_month = DATE_FORMAT(CURDATE(), '%Y-%m-01')
-- WHERE e.status = 'Active'
--   AND (el.last_credited_month IS NULL OR el.last_credited_month < DATE_FORMAT(CURDATE(), '%Y-%m-01'));
