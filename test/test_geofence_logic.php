<?php
/**
 * Geofence Logic Unit Testing Suite
 * File: test/test_geofence_logic.php
 * 
 * This script tests the Phase 2 geofence validation logic by simulating
 * various scenarios and validating the responses from validate_geofence.php
 * 
 * Usage:
 * - Browser: http://localhost/main/test/test_geofence_logic.php
 * - CLI: php test/test_geofence_logic.php (requires php-cli)
 * 
 * @author QA Automation Engineer
 * @version 1.0.0
 */

// Set error reporting for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON header if running via browser
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');
}

// Include required files
$rootDir = dirname(__DIR__);
require_once $rootDir . '/conn/db_connection.php';
require_once $rootDir . '/functions.php';

/**
 * Test Configuration
 */
$testConfig = [
    'branch_id' => 21,              // BCDA - Admin
    'branch_lat' => 16.5969775,     // Actual latitude from DB
    'branch_lng' => 120.3077657,    // Actual longitude from DB
    'geofence_radius' => 250,       // Actual radius from DB (meters)
];

/**
 * Test Employee IDs (Must exist in database)
 * These should be configured to match your test employees
 */
$testEmployees = [
    'worker' => [
        'id' => 1,
        'role' => 'Worker',
        'description' => 'Regular Employee (Hard Enforcement)'
    ],
    'manager' => [
        'id' => 2,
        'role' => 'Manager',
        'description' => 'Manager (Can Override)'
    ],
    'admin' => [
        'id' => 3,
        'role' => 'Admin',
        'description' => 'Admin (Can Override)'
    ]
];

/**
 * Test Result Container
 */
$testResults = [
    'timestamp' => date('Y-m-d H:i:s'),
    'total_tests' => 0,
    'passed' => 0,
    'failed' => 0,
    'tests' => []
];

/**
 * Simulate validate_geofence.php logic
 * This replicates the core validation functions for isolated testing
 */
class GeofenceTester {
    private $db;
    private $haversineRadius = 6371000; // Earth radius in meters

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Calculate distance using Haversine formula
     */
    public function haversineMeters($lat1, $lon1, $lat2, $lon2) {
        $R = $this->haversineRadius;
        $phi1 = deg2rad($lat1);
        $phi2 = deg2rad($lat2);
        $dPhi = deg2rad($lat2 - $lat1);
        $dLambda = deg2rad($lon2 - $lon1);

        $a = sin($dPhi / 2) * sin($dPhi / 2) +
             cos($phi1) * cos($phi2) *
             sin($dLambda / 2) * sin($dLambda / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $R * $c;
    }

    /**
     * Get employee role from database
     */
    public function getEmployeeRole($employeeId) {
        $sql = "SELECT position FROM employees WHERE id = ? AND status = 'Active' LIMIT 1";
        $stmt = mysqli_prepare($this->db, $sql);
        
        if (!$stmt) {
            return null;
        }
        
        mysqli_stmt_bind_param($stmt, 'i', $employeeId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $employee = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        return $employee['position'] ?? null;
    }

    /**
     * Check if employee can override geofence
     */
    public function canOverride($position) {
        $overrideRoles = ['Admin', 'Super Admin', 'Manager', 'Supervisor'];
        return in_array($position, $overrideRoles);
    }

    /**
     * Validate GPS timestamp (anti-spoofing)
     */
    public function validateTimestamp($gpsTimestamp) {
        $serverTimestamp = time();
        $timeDiff = abs($serverTimestamp - $gpsTimestamp);
        $maxAllowedDiff = 300; // 5 minutes
        
        return [
            'valid' => $timeDiff <= $maxAllowedDiff,
            'diff_seconds' => $timeDiff,
            'gps_timestamp' => $gpsTimestamp,
            'server_timestamp' => $serverTimestamp
        ];
    }

    /**
     * Validate accuracy
     */
    public function validateAccuracy($accuracy) {
        $criticalThreshold = 500; // meters
        $warningThreshold = 100; // meters
        
        return [
            'valid' => $accuracy <= $criticalThreshold,
            'critical' => $accuracy > $criticalThreshold,
            'poor' => $accuracy > $warningThreshold,
            'accuracy_meters' => $accuracy,
            'warning_threshold' => $warningThreshold,
            'critical_threshold' => $criticalThreshold
        ];
    }

    /**
     * Main geofence validation logic (simulated)
     */
    public function validateGeofence($params) {
        $branchId = $params['branch_id'] ?? 0;
        $employeeId = $params['employee_id'] ?? 0;
        $lat = floatval($params['lat'] ?? 0);
        $lng = floatval($params['lng'] ?? 0);
        $accuracy = floatval($params['accuracy'] ?? 0);
        $gpsTimestamp = intval($params['gps_timestamp'] ?? time());
        
        // Get branch info
        $branchInfo = $this->getBranchInfo($branchId);
        if (!$branchInfo) {
            return [
                'success' => false,
                'error' => 'Branch not found',
                'action' => 'block'
            ];
        }
        
        // Get employee role
        $employeeRole = $this->getEmployeeRole($employeeId);
        if (!$employeeRole) {
            return [
                'success' => false,
                'error' => 'Employee not found or inactive',
                'action' => 'block'
            ];
        }
        
        // Check if can override
        $canOverride = $this->canOverride($employeeRole);
        
        // Calculate distance
        $distance = $this->haversineMeters(
            $lat, $lng,
            $branchInfo['lat'], $branchInfo['lng']
        );
        
        $isWithinGeofence = $distance <= $branchInfo['radius'];
        $outsideByMeters = $isWithinGeofence ? 0 : ($distance - $branchInfo['radius']);
        
        // Validation chain
        $validations = [];
        
        // 1. Accuracy validation
        $accuracyValidation = $this->validateAccuracy($accuracy);
        $validations['accuracy'] = $accuracyValidation;
        
        if ($accuracyValidation['critical']) {
            return [
                'success' => false,
                'action' => 'accuracy_block',
                'is_valid' => false,
                'distance_meters' => round($distance, 2),
                'accuracy_meters' => $accuracy,
                'accuracy_validation' => $accuracyValidation,
                'override_reason' => 'GPS accuracy too poor (>' . $accuracyValidation['critical_threshold'] . 'm)',
                'can_override' => $canOverride,
                'employee_role' => $employeeRole,
                'requires_override' => false
            ];
        }
        
        // 2. Timestamp validation (anti-spoofing)
        $timestampValidation = $this->validateTimestamp($gpsTimestamp);
        $validations['timestamp'] = $timestampValidation;
        
        if (!$timestampValidation['valid']) {
            return [
                'success' => false,
                'action' => 'security_block',
                'is_valid' => false,
                'distance_meters' => round($distance, 2),
                'accuracy_meters' => $accuracy,
                'timestamp_validation' => $timestampValidation,
                'override_reason' => 'Possible location spoofing detected (timestamp ' . $timestampValidation['diff_seconds'] . 's old)',
                'can_override' => false, // Security blocks cannot be overridden
                'employee_role' => $employeeRole,
                'requires_override' => false
            ];
        }
        
        // 3. Geofence validation
        $validations['geofence'] = [
            'within_geofence' => $isWithinGeofence,
            'distance' => round($distance, 2),
            'radius' => $branchInfo['radius'],
            'outside_by_meters' => round($outsideByMeters, 2)
        ];
        
        if (!$isWithinGeofence) {
            // Outside geofence - determine action based on role
            if ($canOverride) {
                return [
                    'success' => true,
                    'action' => 'allow_override',
                    'is_valid' => false,
                    'distance_meters' => round($distance, 2),
                    'outside_by_meters' => round($outsideByMeters, 2),
                    'accuracy_meters' => $accuracy,
                    'geofence_radius' => $branchInfo['radius'],
                    'override_reason' => 'Outside geofence by ' . round($outsideByMeters, 0) . ' meters. Manager override required.',
                    'can_override' => true,
                    'employee_role' => $employeeRole,
                    'requires_override' => true,
                    'validations' => $validations
                ];
            } else {
                // Worker - hard block
                return [
                    'success' => false,
                    'action' => 'block',
                    'is_valid' => false,
                    'distance_meters' => round($distance, 2),
                    'outside_by_meters' => round($outsideByMeters, 2),
                    'accuracy_meters' => $accuracy,
                    'geofence_radius' => $branchInfo['radius'],
                    'override_reason' => 'You are outside the allowed geofence area by ' . round($outsideByMeters, 0) . ' meters. Please move closer to the branch.',
                    'can_override' => false,
                    'employee_role' => $employeeRole,
                    'requires_override' => false,
                    'validations' => $validations
                ];
            }
        }
        
        // All validations passed
        return [
            'success' => true,
            'action' => 'success',
            'is_valid' => true,
            'distance_meters' => round($distance, 2),
            'accuracy_meters' => $accuracy,
            'geofence_radius' => $branchInfo['radius'],
            'can_override' => $canOverride,
            'employee_role' => $employeeRole,
            'requires_override' => false,
            'validations' => $validations
        ];
    }

    /**
     * Get branch information
     */
    private function getBranchInfo($branchId) {
        $sql = "SELECT id, branch_name, lat, `long` AS lng, geofence_radius_meters as radius 
                FROM branches 
                WHERE id = ? 
                LIMIT 1";
        
        $stmt = mysqli_prepare($this->db, $sql);
        if (!$stmt) {
            return null;
        }
        
        mysqli_stmt_bind_param($stmt, 'i', $branchId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $branch = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        return $branch;
    }
}

/**
 * Test Runner
 */
class GeofenceTestRunner {
    private $tester;
    private $config;
    private $results;
    private $db;

    public function __construct($db, $config) {
        $this->db = $db;
        $this->tester = new GeofenceTester($db);
        $this->config = $config;
        $this->results = [
            'timestamp' => date('Y-m-d H:i:s'),
            'total_tests' => 0,
            'passed' => 0,
            'failed' => 0,
            'tests' => []
        ];
    }

    /**
     * Run a single test case
     */
    private function runTest($testName, $testParams, $expectedAction) {
        $this->results['total_tests']++;
        
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "TEST CASE: {$testName}\n";
        echo str_repeat("=", 70) . "\n";
        
        // Run validation
        $result = $this->tester->validateGeofence($testParams);
        
        // Determine pass/fail
        $passed = ($result['action'] === $expectedAction);
        
        if ($passed) {
            $this->results['passed']++;
            echo "✅ PASSED - Action: {$result['action']}\n";
        } else {
            $this->results['failed']++;
            echo "❌ FAILED - Expected: {$expectedAction}, Got: {$result['action']}\n";
        }
        
        // Display test details
        echo "\nTest Parameters:\n";
        echo json_encode($testParams, JSON_PRETTY_PRINT) . "\n";
        
        echo "\nResponse:\n";
        echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
        
        // Store result
        $this->results['tests'][] = [
            'name' => $testName,
            'passed' => $passed,
            'expected_action' => $expectedAction,
            'actual_action' => $result['action'],
            'parameters' => $testParams,
            'response' => $result
        ];
        
        // Log to database for audit trail
        $this->logTestResult($testName, $testParams, $result, $passed);
        
        return $result;
    }

    /**
     * Log test results to activity_logs
     */
    private function logTestResult($testName, $params, $result, $passed) {
        global $db;
        
        $details = sprintf(
            'Geofence Test: %s | Employee: %s | Location: %s, %s | Result: %s | Action: %s',
            $testName,
            $params['employee_id'] ?? 'N/A',
            $params['lat'] ?? 'N/A',
            $params['lng'] ?? 'N/A',
            $passed ? 'PASSED' : 'FAILED',
            $result['action']
        );
        
        $sql = "INSERT INTO activity_logs (user_id, action, details, ip_address, created_at) 
                VALUES (?, 'Geofence QA Test', ?, '127.0.0.1', NOW())";
        
        $stmt = mysqli_prepare($db, $sql);
        if ($stmt) {
            $userId = $params['employee_id'] ?? 0;
            mysqli_stmt_bind_param($stmt, 'is', $userId, $details);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    /**
     * Run all test scenarios
     */
    public function runAllTests() {
        echo "\n";
        echo str_repeat("#", 70) . "\n";
        echo "# GEOFENCE PHASE 2 - AUTOMATED TEST SUITE\n";
        echo "# " . date('Y-m-d H:i:s') . "\n";
        echo str_repeat("#", 70) . "\n";
        
        $branchLat = $this->config['branch_lat'];
        $branchLng = $this->config['branch_lng'];
        $branchId = $this->config['branch_id'];
        
        // ============================================
        // TEST CASE 1: Inside Geofence (Worker)
        // Expected: success
        // ============================================
        $this->runTest(
            'CASE 1: Inside Geofence (Worker)',
            [
                'branch_id' => $branchId,
                'employee_id' => 1, // Worker
                'lat' => $branchLat + 0.00045, // ~50m from center
                'lng' => $branchLng,
                'accuracy' => 10,
                'gps_timestamp' => time()
            ],
            'success'
        );
        
        // ============================================
        // TEST CASE 2: Outside Geofence (Worker)
        // Expected: block
        // ============================================
        $this->runTest(
            'CASE 2: Outside Geofence (Worker) - Should BLOCK',
            [
                'branch_id' => $branchId,
                'employee_id' => 1, // Worker
                'lat' => $branchLat + 0.002, // ~220m outside
                'lng' => $branchLng + 0.002,
                'accuracy' => 15,
                'gps_timestamp' => time()
            ],
            'block'
        );
        
        // ============================================
        // TEST CASE 3: Outside Geofence (Manager)
        // Expected: allow_override
        // ============================================
        $this->runTest(
            'CASE 3: Outside Geofence (Manager) - Should allow OVERRIDE',
            [
                'branch_id' => $branchId,
                'employee_id' => 2, // Manager
                'lat' => $branchLat + 0.002, // ~220m outside
                'lng' => $branchLng + 0.002,
                'accuracy' => 15,
                'gps_timestamp' => time()
            ],
            'allow_override'
        );
        
        // ============================================
        // TEST CASE 4: Spoofed Timestamp
        // Expected: security_block
        // ============================================
        $this->runTest(
            'CASE 4: Spoofed Timestamp (2 hours ago) - Should SECURITY BLOCK',
            [
                'branch_id' => $branchId,
                'employee_id' => 1, // Worker
                'lat' => $branchLat + 0.00045, // Inside
                'lng' => $branchLng,
                'accuracy' => 10,
                'gps_timestamp' => time() - (2 * 60 * 60) // 2 hours ago
            ],
            'security_block'
        );
        
        // ============================================
        // TEST CASE 5: Low Accuracy (>500m)
        // Expected: accuracy_block
        // ============================================
        $this->runTest(
            'CASE 5: Low Accuracy (>500m) - Should ACCURACY BLOCK',
            [
                'branch_id' => $branchId,
                'employee_id' => 1, // Worker
                'lat' => $branchLat + 0.00045, // Inside
                'lng' => $branchLng,
                'accuracy' => 600, // Very poor accuracy
                'gps_timestamp' => time()
            ],
            'accuracy_block'
        );
        
        // Print summary
        $this->printSummary();
        
        return $this->results;
    }

    /**
     * Print test summary
     */
    private function printSummary() {
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "TEST SUMMARY\n";
        echo str_repeat("=", 70) . "\n";
        echo "Total Tests: " . $this->results['total_tests'] . "\n";
        echo "Passed: " . $this->results['passed'] . "\n";
        echo "Failed: " . $this->results['failed'] . "\n";
        echo "Success Rate: " . round(($this->results['passed'] / $this->results['total_tests']) * 100, 1) . "%\n";
        echo str_repeat("=", 70) . "\n";
    }

    /**
     * Get results as array
     */
    public function getResults() {
        return $this->results;
    }
}

/**
 * Main Execution
 */
try {
    // Initialize test runner
    $runner = new GeofenceTestRunner($db, $testConfig);
    
    // Run all tests
    $results = $runner->runAllTests();
    
    // Output JSON (for browser)
    if (php_sapi_name() !== 'cli') {
        // Also output clean JSON at the end
        echo "\n\n" . str_repeat("-", 70) . "\n";
        echo "JSON OUTPUT FOR AUTOMATED PROCESSING:\n";
        echo str_repeat("-", 70) . "\n";
        echo json_encode($results, JSON_PRETTY_PRINT);
    }
    
} catch (Exception $e) {
    $error = [
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ];
    
    if (php_sapi_name() !== 'cli') {
        echo json_encode($error, JSON_PRETTY_PRINT);
    } else {
        echo "ERROR: " . $e->getMessage() . "\n";
        echo $e->getTraceAsString() . "\n";
    }
}

/**
 * CLI Output Helper
 * If running from command line, format output nicely
 */
if (php_sapi_name() === 'cli') {
    echo "\n\n";
    echo "How to run via Browser:\n";
    echo "1. Start WAMP/LAMP server\n";
    echo "2. Open: http://localhost/main/test/test_geofence_logic.php\n";
    echo "3. View results in browser or use ?format=json for API testing\n";
    echo "\n";
    echo "How to run via CLI:\n";
    echo "1. Open terminal\n";
    echo "2. Navigate to project root\n";
    echo "3. Run: php test/test_geofence_logic.php\n";
    echo "\n";
}
