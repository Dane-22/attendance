<?php
/**
 * Geofence Violation Notification Handler
 * Handles automatic notifications for geofence violations
 * Integrates with existing admin_notifications system
 */

require_once __DIR__ . '/conn/db_connection.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

// Get request data
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$employeeId = isset($_POST['employee_id']) ? (int)$_POST['employee_id'] : (isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0);
$branchId = isset($_POST['branch_id']) ? (int)$_POST['branch_id'] : (isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : 0);
$violationData = $_POST['violation_data'] ?? $_GET['violation_data'] ?? '';

/**
 * Send geofence violation notification to admins
 * @param mysqli $db Database connection
 * @param int $employeeId Employee ID
 * @param int $branchId Branch ID
 * @param array $violationData Violation details
 */
function sendGeofenceViolationNotification($db, $employeeId, $branchId, $violationData) {
    try {
        // Get employee details
        $empSql = "SELECT e.first_name, e.last_name, e.employee_code, e.position, 
                         b.branch_name, b.geofence_radius_meters
                  FROM employees e
                  JOIN branches b ON e.branch_id = b.id
                  WHERE e.id = ? AND b.id = ?";
        
        $empStmt = mysqli_prepare($db, $empSql);
        if (!$empStmt) {
            throw new Exception('Failed to prepare employee query');
        }
        
        mysqli_stmt_bind_param($empStmt, 'ii', $employeeId, $branchId);
        mysqli_stmt_execute($empStmt);
        $empResult = mysqli_stmt_get_result($empStmt);
        $employee = mysqli_fetch_assoc($empResult);
        mysqli_stmt_close($empStmt);
        
        if (!$employee) {
            throw new Exception('Employee or branch not found');
        }
        
        // Check violation threshold
        $today = date('Y-m-d');
        $thresholdSql = "SELECT COUNT(*) as violation_count 
                        FROM geofence_violations 
                        WHERE employee_id = ? AND violation_date = ? AND status = 'active'";
        
        $thresholdStmt = mysqli_prepare($db, $thresholdSql);
        mysqli_stmt_bind_param($thresholdStmt, 'is', $employeeId, $today);
        mysqli_stmt_execute($thresholdStmt);
        $thresholdResult = mysqli_stmt_get_result($thresholdStmt);
        $thresholdData = mysqli_fetch_assoc($thresholdResult);
        mysqli_stmt_close($thresholdStmt);
        
        $violationCount = $thresholdData['violation_count'] ?? 0;
        
        // Determine notification type and message
        $notificationType = 'Geofence Violation';
        $urgency = 'medium';
        
        if ($violationCount >= 5) {
            $urgency = 'critical';
            $notificationType = 'Critical Geofence Violation';
        } elseif ($violationCount >= 3) {
            $urgency = 'high';
            $notificationType = 'Multiple Geofence Violations';
        }
        
        // Create notification message
        $message = sprintf(
            '%s: %s %s (%s) - %s at %s. Distance: %dm (Radius: %dm). Total violations today: %d.',
            $notificationType,
            $employee['first_name'],
            $employee['last_name'],
            $employee['employee_code'],
            $employee['position'],
            $employee['branch_name'],
            $violationData['distance'] ?? 'Unknown',
            $employee['geofence_radius_meters'],
            $violationCount + 1
        );
        
        // Add additional context
        if (!empty($violationData['accuracy']) && $violationData['accuracy'] > 100) {
            $message .= ' Poor GPS accuracy detected.';
        }
        
        if (!empty($violationData['timestamp_diff']) && $violationData['timestamp_diff'] > 300) {
            $message .= ' Possible location spoofing detected.';
        }
        
        // Insert notification for all admins
        $adminSql = "SELECT id FROM employees WHERE position IN ('Admin', 'Super Admin', 'Manager') AND status = 'Active'";
        $adminResult = mysqli_query($db, $adminSql);
        
        $notificationsInserted = 0;
        while ($admin = mysqli_fetch_assoc($adminResult)) {
            $notifSql = "INSERT INTO admin_notifications (
                type, message, employee_id, branch_id, 
                urgency, created_at, status, metadata
            ) VALUES (?, ?, ?, ?, ?, NOW(), 'unread', ?)";
            
            $notifStmt = mysqli_prepare($db, $notifSql);
            if ($notifStmt) {
                $metadata = json_encode([
                    'violation_count' => $violationCount + 1,
                    'distance' => $violationData['distance'] ?? null,
                    'accuracy' => $violationData['accuracy'] ?? null,
                    'latitude' => $violationData['latitude'] ?? null,
                    'longitude' => $violationData['longitude'] ?? null,
                    'urgency' => $urgency,
                    'employee_position' => $employee['position'],
                    'branch_name' => $employee['branch_name']
                ]);
                
                mysqli_stmt_bind_param($notifStmt, 'ssisss', 
                    $notificationType, $message, $employeeId, $branchId, $urgency, $metadata);
                mysqli_stmt_execute($notifStmt);
                mysqli_stmt_close($notifStmt);
                $notificationsInserted++;
            }
        }
        
        // Log the notification
        logApiActivity($db, $employeeId, 'Geofence Violation Notification', 
            sprintf('Sent %d notifications for violation #%d', $notificationsInserted, $violationCount + 1));
        
        return [
            'success' => true,
            'notifications_sent' => $notificationsInserted,
            'violation_count' => $violationCount + 1,
            'urgency' => $urgency,
            'message' => $message
        ];
        
    } catch (Exception $e) {
        error_log("Geofence notification error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Check and send periodic violation summary notifications
 * @param mysqli $db Database connection
 */
function sendViolationSummaryNotifications($db) {
    $today = date('Y-m-d');
    
    // Get employees with multiple violations today
    $summarySql = "SELECT e.id, e.first_name, e.last_name, e.employee_code, 
                          COUNT(gv.id) as violation_count,
                          MAX(gv.distance_from_branch) as max_distance,
                          AVG(gv.accuracy_meters) as avg_accuracy
                   FROM employees e
                   JOIN geofence_violations gv ON e.id = gv.employee_id
                   WHERE gv.violation_date = ? AND gv.status = 'active'
                   GROUP BY e.id, e.first_name, e.last_name, e.employee_code
                   HAVING violation_count >= 3
                   ORDER BY violation_count DESC";
    
    $summaryStmt = mysqli_prepare($db, $summarySql);
    if ($summaryStmt) {
        mysqli_stmt_bind_param($summaryStmt, 's', $today);
        mysqli_stmt_execute($summaryStmt);
        $summaryResult = mysqli_stmt_get_result($summaryStmt);
        
        while ($employee = mysqli_fetch_assoc($summaryResult)) {
            $summaryMessage = sprintf(
                'Daily Violation Summary: %s %s (%s) has %d violations today. Max distance: %dm. Avg accuracy: %.1fm.',
                $employee['first_name'],
                $employee['last_name'],
                $employee['employee_code'],
                $employee['violation_count'],
                $employee['max_distance'],
                $employee['avg_accuracy']
            );
            
            // Send to admins
            $adminSql = "SELECT id FROM employees WHERE position IN ('Admin', 'Super Admin') AND status = 'Active'";
            $adminResult = mysqli_query($db, $adminSql);
            
            while ($admin = mysqli_fetch_assoc($adminResult)) {
                $notifSql = "INSERT INTO admin_notifications (
                    type, message, employee_id, created_at, status
                ) VALUES ('Violation Summary', ?, ?, NOW(), 'unread')";
                
                $notifStmt = mysqli_prepare($db, $notifSql);
                if ($notifStmt) {
                    mysqli_stmt_bind_param($notifStmt, 'si', $summaryMessage, $employee['id']);
                    mysqli_stmt_execute($notifStmt);
                    mysqli_stmt_close($notifStmt);
                }
            }
        }
        mysqli_stmt_close($summaryStmt);
    }
}

/**
 * Get active geofence violations for dashboard
 * @param mysqli $db Database connection
 * @param array $filters Optional filters
 */
function getActiveViolations($db, $filters = []) {
    $today = date('Y-m-d');
    $whereConditions = ["gv.violation_date = ?"];
    $params = [$today];
    $types = 's';
    
    // Add filters
    if (!empty($filters['branch_id'])) {
        $whereConditions[] = "gv.branch_id = ?";
        $params[] = $filters['branch_id'];
        $types .= 'i';
    }
    
    if (!empty($filters['employee_id'])) {
        $whereConditions[] = "gv.employee_id = ?";
        $params[] = $filters['employee_id'];
        $types .= 'i';
    }
    
    if (!empty($filters['urgency'])) {
        $whereConditions[] = "gv.violation_count >= ?";
        $params[] = $filters['urgency'] === 'high' ? 3 : 5;
        $types .= 'i';
    }
    
    $sql = "SELECT gv.*, e.first_name, e.last_name, e.employee_code, e.position,
                   b.branch_name, b.geofence_radius_meters
            FROM geofence_violations gv
            JOIN employees e ON gv.employee_id = e.id
            JOIN branches b ON gv.branch_id = b.id
            WHERE " . implode(' AND ', $whereConditions) . "
            AND gv.status = 'active'
            ORDER BY gv.created_at DESC";
    
    $stmt = mysqli_prepare($db, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $violations = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $violations[] = $row;
        }
        
        mysqli_stmt_close($stmt);
        return $violations;
    }
    
    return [];
}

// Handle API requests
try {
    switch ($action) {
        case 'send_notification':
            if ($employeeId <= 0 || $branchId <= 0) {
                throw new Exception('Missing employee_id or branch_id');
            }
            
            $violationData = json_decode($violationData, true) ?: [];
            $result = sendGeofenceViolationNotification($db, $employeeId, $branchId, $violationData);
            echo json_encode($result);
            break;
            
        case 'get_violations':
            $filters = [
                'branch_id' => $_GET['branch_id'] ?? null,
                'employee_id' => $_GET['employee_id'] ?? null,
                'urgency' => $_GET['urgency'] ?? null
            ];
            $violations = getActiveViolations($db, $filters);
            echo json_encode([
                'success' => true,
                'violations' => $violations,
                'count' => count($violations)
            ]);
            break;
            
        case 'send_summary':
            sendViolationSummaryNotifications($db);
            echo json_encode(['success' => true, 'message' => 'Summary notifications sent']);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

mysqli_close($db);
?>
