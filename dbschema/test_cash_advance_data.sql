-- Add test data to cash_advances table
-- Run this SQL to see the table in action

-- First, check if you have any employees
SELECT id, first_name, last_name, employee_code FROM employees WHERE status = 'active' LIMIT 5;

-- Add a test cash advance (replace 1 with an actual employee_id from your database)
INSERT INTO cash_advances (employee_id, amount, particular, reason, status, request_date) 
VALUES (1, 500.00, 'Cash Advance', 'Emergency medical expense', 'Approved', NOW());

-- Add a test payment (to show balance reduction)
INSERT INTO cash_advances (employee_id, amount, particular, reason, status, request_date) 
VALUES (1, 100.00, 'Payment', 'Partial payment', 'Approved', DATE_ADD(NOW(), INTERVAL 1 DAY));

-- View all transactions
SELECT * FROM cash_advances ORDER BY request_date DESC;
