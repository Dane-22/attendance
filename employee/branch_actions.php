<?php
// employee/branch_actions.php
// Handles INSERT and DELETE operations for branch management

require_once __DIR__ . '/../conn/db_connection.php';
session_start();

// Check if user is logged in and has admin/supervisor privileges
// Adjust this based on your role system
$isAdmin = isset($_SESSION['role']) && in_array($_SESSION['role'], ['Admin', 'Manager', 'Supervisor']);

if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // ADD BRANCH
    if ($action === 'add_branch') {
        $branch_name = isset($_POST['branch_name']) ? trim($_POST['branch_name']) : '';

        if (empty($branch_name)) {
            echo json_encode(['success' => false, 'message' => 'Branch name is required']);
            exit();
        }

        // Validate branch name length
        if (strlen($branch_name) < 2) {
            echo json_encode(['success' => false, 'message' => 'Branch name must be at least 2 characters']);
            exit();
        }

        if (strlen($branch_name) > 255) {
            echo json_encode(['success' => false, 'message' => 'Branch name cannot exceed 255 characters']);
            exit();
        }

        // Check if branch already exists
        $checkQuery = "SELECT id FROM branches WHERE branch_name = ?";
        $checkStmt = mysqli_prepare($db, $checkQuery);
        mysqli_stmt_bind_param($checkStmt, 's', $branch_name);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);

        if (mysqli_num_rows($checkResult) > 0) {
            echo json_encode(['success' => false, 'message' => 'Branch already exists']);
            exit();
        }

        // Insert new branch
        $insertQuery = "INSERT INTO branches (branch_name, created_at) VALUES (?, NOW())";
        $insertStmt = mysqli_prepare($db, $insertQuery);
        mysqli_stmt_bind_param($insertStmt, 's', $branch_name);

        if (mysqli_stmt_execute($insertStmt)) {
            $branch_id = mysqli_insert_id($db);
            echo json_encode([
                'success' => true,
                'message' => 'Branch added successfully',
                'branch_id' => $branch_id,
                'branch_name' => $branch_name
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error adding branch: ' . mysqli_error($db)]);
        }
        exit();
    }

    // DELETE BRANCH
    if ($action === 'delete_branch') {
        $branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;

        if ($branch_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid branch ID']);
            exit();
        }

        // Get branch name before deletion (for response message)
        $getBranchQuery = "SELECT branch_name FROM branches WHERE id = ?";
        $getBranchStmt = mysqli_prepare($db, $getBranchQuery);
        mysqli_stmt_bind_param($getBranchStmt, 'i', $branch_id);
        mysqli_stmt_execute($getBranchStmt);
        $getBranchResult = mysqli_stmt_get_result($getBranchStmt);

        if (mysqli_num_rows($getBranchResult) === 0) {
            echo json_encode(['success' => false, 'message' => 'Branch not found']);
            exit();
        }

        $branchRow = mysqli_fetch_assoc($getBranchResult);
        $branch_name = $branchRow['branch_name'];

        // Check if branch has active employees
        $checkEmployeesQuery = "SELECT COUNT(*) as count FROM employees WHERE branch_name = ? AND status = 'Active'";
        $checkEmployeesStmt = mysqli_prepare($db, $checkEmployeesQuery);
        mysqli_stmt_bind_param($checkEmployeesStmt, 's', $branch_name);
        mysqli_stmt_execute($checkEmployeesStmt);
        $checkEmployeesResult = mysqli_stmt_get_result($checkEmployeesStmt);
        $employeeCount = mysqli_fetch_assoc($checkEmployeesResult);

        if ($employeeCount['count'] > 0) {
            echo json_encode([
                'success' => false,
                'message' => "Cannot delete branch with active employees. ({$employeeCount['count']} employees assigned)"
            ]);
            exit();
        }

        // Delete the branch
        $deleteQuery = "DELETE FROM branches WHERE id = ?";
        $deleteStmt = mysqli_prepare($db, $deleteQuery);
        mysqli_stmt_bind_param($deleteStmt, 'i', $branch_id);

        if (mysqli_stmt_execute($deleteStmt)) {
            echo json_encode([
                'success' => true,
                'message' => "Branch '{$branch_name}' has been deleted successfully"
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting branch: ' . mysqli_error($db)]);
        }
        exit();
    }

    // GET ALL BRANCHES
    if ($action === 'get_branches') {
        $query = "SELECT id, branch_name, created_at FROM branches WHERE is_active = 1 ORDER BY branch_name ASC";
        $result = mysqli_query($db, $query);

        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'Error fetching branches']);
            exit();
        }

        $branches = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $branches[] = [
                'id' => $row['id'],
                'branch_name' => $row['branch_name'],
                'created_at' => $row['created_at']
            ];
        }

        echo json_encode([
            'success' => true,
            'branches' => $branches
        ]);
        exit();
    }
}

// Invalid request
echo json_encode(['success' => false, 'message' => 'Invalid action']);
exit();
?>
