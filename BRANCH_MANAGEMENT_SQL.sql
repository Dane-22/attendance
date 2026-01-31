-- =====================================================
-- BRANCHES TABLE CREATION
-- =====================================================
-- SQL Query to create the branches table for branch management

CREATE TABLE IF NOT EXISTS branches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    branch_name VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active TINYINT DEFAULT 1
);

-- Create index on branch_name for faster queries
CREATE INDEX idx_branch_name ON branches(branch_name);

-- Optional: Create index on is_active for filtering
CREATE INDEX idx_is_active ON branches(is_active);

-- Optional: Populate with initial branches (if you have existing branches from employees table)
-- Run this only if you want to migrate existing branches
-- INSERT INTO branches (branch_name) 
-- SELECT DISTINCT branch_name FROM employees 
-- WHERE branch_name IS NOT NULL AND branch_name != '' 
-- ON DUPLICATE KEY UPDATE updated_at = NOW();

-- =====================================================
-- EMPLOYEES TABLE UPDATE: CURRENT BRANCH TRACKING
-- =====================================================
-- Adds a real-time current branch column separate from employees.branch_name (original branch)

ALTER TABLE employees
    ADD COLUMN current_branch_id INT NULL AFTER branch_name,
    ADD INDEX idx_employees_current_branch_id (current_branch_id),
    ADD CONSTRAINT fk_employees_current_branch
        FOREIGN KEY (current_branch_id) REFERENCES branches(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL;

-- Optional: Backfill current_branch_id based on the existing employees.branch_name text.
-- Run this after you have inserted/migrated branches.
UPDATE employees e
JOIN branches b ON b.branch_name = e.branch_name
SET e.current_branch_id = b.id
WHERE e.current_branch_id IS NULL;
