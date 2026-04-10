# Branch Deletion Documentation

## Overview

This document describes the branch (project) deletion functionality in the JAJR Attendance System. Branch deletion allows Super Admin users to remove unused or obsolete projects from the system.

---

## User Interface

### Location
- **File**: `employee/select_employee.php`
- **Section**: Project Selection Grid (line 519-529)

### How to Delete a Branch

1. Navigate to the **Select Employee** page
2. View the **"Select Deployment Project"** section
3. Each project card displays a delete button (X icon) in the top-right corner
4. Click the X button on the project you want to delete
5. Confirm the deletion in the browser confirmation dialog
6. The project will be removed with a fade-out animation

### UI Elements

```php
@/wamp64/www/main/employee/select_employee.php:522-524
<button class="btn-remove-branch" onclick="removeBranch(event, <?php echo htmlspecialchars($branch['id']); ?>, '<?php echo htmlspecialchars($branch['branch_name']); ?>')" title="Delete project">
  <i class="fas fa-times"></i>
</button>
```

---

## Frontend Implementation

### JavaScript Handler
- **File**: `employee/js/attendance.js`
- **Function**: `removeBranch(e, branchId, branchName)` (line 1861-1937)

### Deletion Flow

1. **Event Prevention**: Stops click event from bubbling to parent card
2. **Confirmation**: Shows browser `confirm()` dialog
3. **Loading State**: Displays spinner on the delete button
4. **AJAX Request**: Sends POST request to server
5. **Animation**: Fades out the branch card on success
6. **UI Update**: Re-renders the branch grid
7. **Message**: Shows success/error notification

### Code Reference

```javascript
@/wamp64/www/main/employee/js/attendance.js:1861-1933
function removeBranch(e, branchId, branchName) {
    if (e && typeof e.stopPropagation === 'function') {
        e.stopPropagation();
    }
    
    if (!confirm(`Are you sure you want to delete the project "${branchName}"?`)) {
        return;
    }

    const formData = new FormData();
    formData.append('branch_action', 'delete_branch');
    formData.append('branch_id', branchId);

    // Loading state
    if (removeBtn) {
      removeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
      removeBtn.disabled = true;
    }

    fetch(window.location.pathname, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(async (response) => {
        // Parse and validate response
    })
    .then(data => {
        if (data.success) {
            // Animate removal
            branchCard.style.transition = 'all 0.3s ease';
            branchCard.style.opacity = '0';
            branchCard.style.transform = 'scale(0.9)';
            
            // Update UI after animation
            setTimeout(() => {
                allBranches = allBranches.filter(b => String(b.id) !== String(branchId));
                renderBranchGrid();
                showGlobalMessage(data.message, 'success');
            }, 300);
        }
    });
}
```

---

## Backend Implementation

### Handler File
- **File**: `employee/branch_actions.php`
- **Action**: `delete_branch` (line 74-127)

### Deletion Logic

1. **Authentication Check**: Verifies user has Admin/Manager/Supervisor role
2. **Validation**: Ensures branch_id is valid (> 0)
3. **Existence Check**: Verifies branch exists in database
4. **Employee Check**: Prevents deletion if active employees are assigned
5. **Deletion**: Executes DELETE query on branches table

### Database Checks

#### Check for Active Employees
```php
@/wamp64/www/main/employee/branch_actions.php:98-112
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
```

#### Delete Query
```php
@/wamp64/www/main/employee/branch_actions.php:114-127
$deleteQuery = "DELETE FROM branches WHERE id = ?";
$deleteStmt = mysqli_prepare($db, $deleteQuery);
mysqli_stmt_bind_param($deleteStmt, 'i', $branch_id);

if (mysqli_stmt_execute($deleteStmt)) {
    echo json_encode([
        'success' => true,
        'message' => "Branch '{$branch_name}' has been deleted successfully"
    ]);
}
```

---

## API Response Format

### Success Response
```json
{
  "success": true,
  "message": "Branch 'Project Name' has been deleted successfully"
}
```

### Error Responses

**Unauthorized Access**
```json
{
  "success": false,
  "message": "Unauthorized access"
}
```

**Invalid Branch ID**
```json
{
  "success": false,
  "message": "Invalid branch ID"
}
```

**Branch Not Found**
```json
{
  "success": false,
  "message": "Branch not found"
}
```

**Active Employees Assigned**
```json
{
  "success": false,
  "message": "Cannot delete branch with active employees. (5 employees assigned)"
}
```

**Database Error**
```json
{
  "success": false,
  "message": "Error deleting branch: [MySQL error message]"
}
```

---

## Security Considerations

1. **Role-based Access**: Only users with `Admin`, `Manager`, or `Supervisor` roles can delete branches
2. **Prepared Statements**: All database queries use prepared statements to prevent SQL injection
3. **Employee Validation**: Branches with active employees cannot be deleted (data integrity protection)
4. **Confirmation Dialog**: Browser confirmation prevents accidental deletions

---

## Related Files

| File | Purpose |
|------|---------|
| `employee/select_employee.php` | Main UI with delete buttons |
| `employee/js/attendance.js` | Frontend JavaScript handler |
| `employee/branch_actions.php` | Backend deletion logic |
| `employee/select_emp.php` | Alternative employee selection interface |
| `employee/function/attendance.php` | Related attendance functions |

---

## Future Enhancements

- **Soft Delete**: Implement `is_active` flag instead of hard delete for recovery options
- **Audit Log**: Track who deleted branches and when
- **Bulk Delete**: Allow deletion of multiple branches at once
- **Employee Reassignment**: Option to move employees to another branch before deletion
- **Undo Functionality**: Complete the undo deletion feature (partially implemented in attendance.js)
