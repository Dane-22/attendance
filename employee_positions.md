# Employee Positions in select_employee.php

## Overview
This document lists the positions that are referenced in `employee/select_employee.php` and their associated access/permissions.

## Positions Found

### 1. QR Scan
- **Purpose**: Temporary session position for QR code auto time-in requests
- **Usage**: 
  - Line 20: `$_SESSION['position'] = 'QR Scan';`
  - Created when an employee scans their QR code to clock in/out without being fully logged in
  - Sets a temporary authenticated session (`$_SESSION['qr_temp_session'] = true`)
- **Permissions**: Limited - only allows QR-based clock in/out operations

### 2. Super Admin
- **Purpose**: Full administrative access to project/branch management
- **Usage**:
  - Line 507: Shows "Add Project" button in the branch selection header
  - Line 533: Displays the "Add Project" modal for creating new projects
- **Permissions**:
  - Can add new deployment projects/branches
  - Can delete projects (via `removeBranch` function)
  - Full access to project management features

## Position-Based Features

| Feature | Super Admin | QR Scan | Other Positions |
|---------|-------------|---------|-----------------|
| Add Project Button | ✅ | ❌ | ❌ |
| Add Project Modal | ✅ | ❌ | ❌ |
| Delete Project | ✅ | ❌ | ❌ |
| View Employees | ✅ | ✅ | ✅ |
| Mark Attendance | ✅ | ✅ (via QR) | ✅ |
| QR Auto Time-in | ❌ | ✅ | ❌ |

## Session Variables
The page checks `$_SESSION['position']` to determine what UI elements to render and what actions the user can perform.

## Related Files
- `employee/function/employees_function.php` - Likely contains position-related logic
- `login_api.php` - Sets the position during authentication
