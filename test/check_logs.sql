-- =============================================================================
-- Geofence Phase 2 QA - Database Verification Script
-- File: test/check_logs.sql
-- 
-- This script queries the database to verify that "Block" and "Override" 
-- actions from the QA tests were properly recorded in:
-- - geofence_violations table
-- - activity_logs table
-- - manager_overrides table (if applicable)
-- 
-- Run this in MySQL/PHPMyAdmin after executing test_geofence_logic.php
-- =============================================================================

-- =============================================================================
-- SECTION 1: GEOFENCE VIOLATIONS VERIFICATION
-- =============================================================================

-- Check all violations recorded today from QA tests
SELECT 
    '=== GEOFENCE VIOLATIONS TODAY ===' AS section;

SELECT 
    gv.id AS violation_id,
    CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
    e.position AS employee_role,
    e.employee_code,
    b.branch_name,
    ROUND(gv.distance_from_branch, 2) AS distance_m,
    gv.geofence_radius_meters AS radius_m,
    ROUND(gv.accuracy_meters, 1) AS accuracy_m,
    gv.status,
    gv.violation_count,
    gv.created_at,
    CASE 
        WHEN gv.violation_count >= 5 THEN '🔴 CRITICAL'
        WHEN gv.violation_count >= 3 THEN '🟠 HIGH'
        ELSE '🟡 NORMAL'
    END AS urgency_level
FROM geofence_violations gv
JOIN employees e ON gv.employee_id = e.id
JOIN branches b ON gv.branch_id = b.id
WHERE DATE(gv.created_at) = CURDATE()
    AND (gv.device_info LIKE '%test%' OR gv.device_info LIKE '%Geofence QA%')
ORDER BY gv.created_at DESC;

-- Summary statistics for today's violations
SELECT 
    '=== VIOLATION SUMMARY TODAY ===' AS section;

SELECT 
    COUNT(*) AS total_violations,
    SUM(CASE WHEN e.position = 'Worker' THEN 1 ELSE 0 END) AS worker_violations,
    SUM(CASE WHEN e.position IN ('Manager', 'Admin', 'Super Admin') THEN 1 ELSE 0 END) AS manager_violations,
    SUM(CASE WHEN gv.accuracy_meters > 100 THEN 1 ELSE 0 END) AS poor_accuracy_violations,
    SUM(CASE WHEN gv.accuracy_meters > 500 THEN 1 ELSE 0 END) AS critical_accuracy_violations,
    AVG(gv.distance_from_branch) AS avg_distance_m,
    MAX(gv.distance_from_branch) AS max_distance_m,
    AVG(gv.accuracy_meters) AS avg_accuracy_m
FROM geofence_violations gv
JOIN employees e ON gv.employee_id = e.id
WHERE DATE(gv.created_at) = CURDATE();


-- =============================================================================
-- SECTION 2: ACTIVITY LOGS VERIFICATION
-- =============================================================================

-- Check all QA test activities logged today
SELECT 
    '=== ACTIVITY LOGS (QA TESTS) TODAY ===' AS section;

SELECT 
    al.id AS log_id,
    CONCAT(e.first_name, ' ', e.last_name) AS user_name,
    e.position AS user_role,
    al.action,
    al.details,
    al.ip_address,
    al.created_at
FROM activity_logs al
LEFT JOIN employees e ON al.user_id = e.id
WHERE DATE(al.created_at) = CURDATE()
    AND (al.action LIKE '%Geofence%' OR al.action LIKE '%QA%' OR al.action LIKE '%Test%')
ORDER BY al.created_at DESC;

-- Summary of test actions by type
SELECT 
    '=== TEST ACTION SUMMARY ===' AS section;

SELECT 
    al.action,
    COUNT(*) AS count,
    GROUP_CONCAT(DISTINCT CONCAT(e.first_name, ' ', e.last_name) SEPARATOR ', ') AS users
FROM activity_logs al
LEFT JOIN employees e ON al.user_id = e.id
WHERE DATE(al.created_at) = CURDATE()
    AND (al.action LIKE '%Geofence%' OR al.action LIKE '%QA%')
GROUP BY al.action;


-- =============================================================================
-- SECTION 3: MANAGER OVERRIDES VERIFICATION
-- =============================================================================

-- Check all manager overrides recorded today
SELECT 
    '=== MANAGER OVERRIDES TODAY ===' AS section;

SELECT 
    mo.id AS override_id,
    CONCAT(emp.first_name, ' ', emp.last_name) AS employee_name,
    emp.position AS employee_role,
    CONCAT(mgr.first_name, ' ', mgr.last_name) AS override_by,
    mgr.position AS manager_role,
    mo.override_type,
    mo.reason,
    mo.original_status,
    mo.new_status,
    mo.distance_meters,
    ROUND(mo.accuracy_meters, 1) AS accuracy_m,
    mo.created_at,
    CASE 
        WHEN mo.override_type = 'geofence' THEN '📍 Geofence Override'
        WHEN mo.override_type = 'accuracy' THEN '🎯 Accuracy Override'
        WHEN mo.override_type = 'timestamp' THEN '⏰ Timestamp Override'
        ELSE '❓ Unknown'
    END AS override_icon
FROM manager_overrides mo
JOIN employees emp ON mo.employee_id = emp.id
JOIN employees mgr ON mo.override_by = mgr.id
WHERE DATE(mo.created_at) = CURDATE()
ORDER BY mo.created_at DESC;

-- Summary of overrides by type
SELECT 
    '=== OVERRIDES BY TYPE ===' AS section;

SELECT 
    mo.override_type,
    COUNT(*) AS count,
    SUM(CASE WHEN mo.override_type = 'geofence' THEN 1 ELSE 0 END) AS geofence_overrides,
    SUM(CASE WHEN mo.override_type = 'accuracy' THEN 1 ELSE 0 END) AS accuracy_overrides,
    SUM(CASE WHEN mo.override_type = 'timestamp' THEN 1 ELSE 0 END) AS timestamp_overrides,
    AVG(mo.distance_meters) AS avg_distance_m
FROM manager_overrides mo
WHERE DATE(mo.created_at) = CURDATE()
GROUP BY mo.override_type;


-- =============================================================================
-- SECTION 4: LOCATION LOGS VERIFICATION
-- =============================================================================

-- Check location logs from QA tests
SELECT 
    '=== LOCATION LOGS (QA TESTS) TODAY ===' AS section;

SELECT 
    ll.id AS log_id,
    CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
    e.position AS employee_role,
    ROUND(ll.lat, 6) AS latitude,
    ROUND(ll.lng, 6) AS longitude,
    ROUND(ll.accuracy_meters, 1) AS accuracy_m,
    ROUND(ll.distance_from_branch, 2) AS distance_m,
    ll.is_geofence_violation,
    ll.flagged_accuracy,
    ll.server_timestamp_diff,
    ll.created_at
FROM location_logs ll
JOIN employees e ON ll.employee_id = e.id
WHERE DATE(ll.created_at) = CURDATE()
    AND (ll.device_info LIKE '%test%' OR ll.override_reason LIKE '%QA%')
ORDER BY ll.created_at DESC
LIMIT 50;

-- Summary statistics for location logs
SELECT 
    '=== LOCATION LOGS SUMMARY ===' AS section;

SELECT 
    COUNT(*) AS total_logs,
    SUM(CASE WHEN ll.is_geofence_violation = 1 THEN 1 ELSE 0 END) AS violation_logs,
    SUM(CASE WHEN ll.flagged_accuracy = 1 THEN 1 ELSE 0 END) AS poor_accuracy_logs,
    SUM(CASE WHEN ll.server_timestamp_diff > 300 THEN 1 ELSE 0 END) AS spoofing_detected,
    AVG(ll.accuracy_meters) AS avg_accuracy_m,
    MAX(ll.accuracy_meters) AS max_accuracy_m,
    AVG(ll.distance_from_branch) AS avg_distance_m,
    MAX(ll.distance_from_branch) AS max_distance_m
FROM location_logs ll
JOIN employees e ON ll.employee_id = e.id
WHERE DATE(ll.created_at) = CURDATE();


-- =============================================================================
-- SECTION 5: COMBINED VERIFICATION QUERY
-- =============================================================================

-- Master verification: Check if all expected records exist
SELECT 
    '=== MASTER VERIFICATION CHECK ===' AS section;

SELECT 
    'Violations Recorded' AS check_item,
    COUNT(*) AS count,
    CASE WHEN COUNT(*) > 0 THEN '✅ PASS' ELSE '❌ FAIL' END AS status
FROM geofence_violations 
WHERE DATE(created_at) = CURDATE()

UNION ALL

SELECT 
    'Activity Logs Recorded' AS check_item,
    COUNT(*),
    CASE WHEN COUNT(*) > 0 THEN '✅ PASS' ELSE '❌ FAIL' END
FROM activity_logs 
WHERE DATE(created_at) = CURDATE() 
    AND action LIKE '%Geofence%'

UNION ALL

SELECT 
    'Location Logs Recorded' AS check_item,
    COUNT(*),
    CASE WHEN COUNT(*) > 0 THEN '✅ PASS' ELSE '⚠️ WARNING' END
FROM location_logs 
WHERE DATE(created_at) = CURDATE()

UNION ALL

SELECT 
    'Manager Overrides Recorded' AS check_item,
    COUNT(*),
    CASE WHEN COUNT(*) > 0 THEN '✅ PASS (if tests included overrides)' ELSE 'ℹ️ N/A' END
FROM manager_overrides 
WHERE DATE(created_at) = CURDATE();


-- =============================================================================
-- SECTION 6: EMPLOYEE VIOLATION FLAGS
-- =============================================================================

-- Check if employee violation flags were updated
SELECT 
    '=== EMPLOYEE VIOLATION FLAGS ===' AS section;

SELECT 
    e.id,
    CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
    e.position,
    e.geofence_violation_count,
    e.last_geofence_violation,
    e.violation_flag,
    CASE 
        WHEN e.violation_flag = 'red' THEN '🔴 Critical'
        WHEN e.violation_flag = 'yellow' THEN '🟠 Warning'
        WHEN e.violation_flag = 'green' THEN '🟢 Normal'
        ELSE '⚪ No Flag'
    END AS flag_status
FROM employees e
WHERE e.geofence_violation_count > 0
ORDER BY e.geofence_violation_count DESC
LIMIT 20;


-- =============================================================================
-- SECTION 7: ADMIN NOTIFICATIONS
-- =============================================================================

-- Check if admin notifications were triggered
SELECT 
    '=== ADMIN NOTIFICATIONS TODAY ===' AS section;

SELECT 
    an.id AS notification_id,
    an.type,
    an.message,
    CONCAT(e.first_name, ' ', e.last_name) AS related_employee,
    an.urgency,
    an.status,
    an.created_at,
    CASE 
        WHEN an.urgency = 'critical' THEN '🔴'
        WHEN an.urgency = 'high' THEN '🟠'
        WHEN an.urgency = 'medium' THEN '🟡'
        ELSE '🔵'
    END AS urgency_icon
FROM admin_notifications an
LEFT JOIN employees e ON an.employee_id = e.id
WHERE DATE(an.created_at) = CURDATE()
    AND an.type LIKE '%Geofence%'
ORDER BY an.created_at DESC;


-- =============================================================================
-- SECTION 8: DETAILED ANALYSIS QUERIES
-- =============================================================================

-- Find employees with multiple violations today (potential problematic users)
SELECT 
    '=== EMPLOYEES WITH MULTIPLE VIOLATIONS TODAY ===' AS section;

SELECT 
    e.id,
    CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
    e.position,
    e.branch_id,
    b.branch_name,
    COUNT(gv.id) AS violation_count,
    MAX(gv.created_at) AS last_violation,
    AVG(gv.distance_from_branch) AS avg_distance_m,
    MAX(gv.distance_from_branch) AS max_distance_m
FROM employees e
JOIN geofence_violations gv ON e.id = gv.employee_id
JOIN branches b ON e.branch_id = b.id
WHERE DATE(gv.created_at) = CURDATE()
GROUP BY e.id, e.first_name, e.last_name, e.position, e.branch_id, b.branch_name
HAVING COUNT(gv.id) >= 2
ORDER BY violation_count DESC;


-- Find violations by action type
SELECT 
    '=== VIOLATIONS BY ACTION TYPE ===' AS section;

SELECT 
    CASE 
        WHEN gv.distance_from_branch > gv.geofence_radius_meters THEN 'Outside Geofence'
        WHEN gv.accuracy_meters > 500 THEN 'Poor Accuracy'
        ELSE 'Other'
    END AS violation_type,
    COUNT(*) AS count,
    AVG(gv.distance_from_branch) AS avg_distance,
    AVG(gv.accuracy_meters) AS avg_accuracy
FROM geofence_violations gv
WHERE DATE(gv.created_at) = CURDATE()
GROUP BY violation_type;


-- =============================================================================
-- SECTION 9: QA TEST SPECIFIC QUERIES
-- =============================================================================

-- Query to check for specific test case patterns
SELECT 
    '=== QA TEST CASE VERIFICATION ===' AS section;

-- Check if Case 2 (Outside Geofence Worker) was recorded
SELECT 
    'Case 2: Outside Geofence (Worker - BLOCK)' AS test_case,
    CASE 
        WHEN COUNT(*) > 0 THEN '✅ RECORDED'
        ELSE '❌ NOT FOUND'
    END AS status,
    COUNT(*) AS record_count
FROM geofence_violations gv
JOIN employees e ON gv.employee_id = e.id
WHERE DATE(gv.created_at) = CURDATE()
    AND e.position = 'Worker'
    AND gv.distance_from_branch > gv.geofence_radius_meters;

-- Check if Case 4 (Spoofed Timestamp) pattern exists
SELECT 
    'Case 4: Spoofed Timestamp Pattern' AS test_case,
    CASE 
        WHEN COUNT(*) > 0 THEN '⚠️ POSSIBLE SPOOFING ATTEMPTS FOUND'
        ELSE '✅ NO SPOOFING PATTERNS'
    END AS status,
    COUNT(*) AS suspicious_count
FROM location_logs ll
WHERE DATE(ll.created_at) = CURDATE()
    AND ll.server_timestamp_diff > 300; -- More than 5 minutes difference


-- =============================================================================
-- SECTION 10: CLEANUP COMMANDS (Optional)
-- =============================================================================

-- UNCOMMENT THESE LINES TO CLEAN UP TEST DATA AFTER QA

-- DELETE FROM geofence_violations WHERE DATE(created_at) = CURDATE() AND device_info LIKE '%QA%';
-- DELETE FROM activity_logs WHERE DATE(created_at) = CURDATE() AND action LIKE '%Geofence QA%';
-- DELETE FROM location_logs WHERE DATE(created_at) = CURDATE() AND device_info LIKE '%QA%';
-- DELETE FROM manager_overrides WHERE DATE(created_at) = CURDATE() AND reason LIKE '%QA%';
-- DELETE FROM admin_notifications WHERE DATE(created_at) = CURDATE() AND type LIKE '%Geofence%';

-- =============================================================================
-- INSTRUCTIONS
-- =============================================================================

/*
HOW TO USE THIS SCRIPT:

1. Run test_geofence_logic.php first to generate test data:
   - Via browser: http://localhost/main/test/test_geofence_logic.php
   - Via CLI: php test/test_geofence_logic.php

2. Run this SQL script in MySQL/PHPMyAdmin:
   - Open PHPMyAdmin
   - Select the attendance database
   - Go to SQL tab
   - Copy and paste this entire script
   - Click "Go" to execute

3. Review the results:
   - Look for ✅ PASS indicators
   - Check that violations were recorded
   - Verify activity logs exist
   - Confirm location logs were created

4. Expected results after running QA tests:
   - 5 test cases should create at least 5 activity logs
   - Cases 2, 3, 4, 5 should create geofence_violations entries
   - Location logs should be recorded for each test
   - Manager overrides may be empty (unless override tests run)

5. To clean up test data:
   - Uncomment the DELETE statements in Section 10
   - Re-run the script
   - Comment them out again when done

TROUBLESHOOTING:

- If no records found: Ensure tests were actually run today
- If tables don't exist: Run the Phase 2 migration first
- If counts are low: Some test cases may not create violations
  (e.g., Case 1 - Inside Geofence should NOT create violations)

For questions, refer to GEOFENCING_PHASE2_IMPLEMENTATION.md
*/
