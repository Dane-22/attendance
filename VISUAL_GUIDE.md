# Visual Guide - Branch Management Integration

## File Structure After Integration

```
attendance_web/
│
├── employee/
│   ├── select_employee.php ✅ (INTEGRATED - 2009 lines)
│   │   ├── PHP Backend (Lines 25-102)
│   │   │   ├── Branch add handler
│   │   │   ├── Branch delete handler
│   │   │   └── Branches fetch query
│   │   │
│   │   ├── HTML (Lines 1225-1275)
│   │   │   ├── Branch header with button
│   │   │   ├── Branch grid display
│   │   │   └── Add Branch modal
│   │   │
│   │   ├── CSS (Lines 1155-1227)
│   │   │   ├── Button styling
│   │   │   ├── Card styling
│   │   │   └── Modal styling
│   │   │
│   │   └── JavaScript (Lines 1812-2009)
│   │       ├── Modal management
│   │       ├── Form handling
│   │       ├── CRUD operations
│   │       └── UI updates
│   │
│   └── [OTHER FILES - unchanged]
│
├── Database/
│   └── branches table ✅ (SQL setup required)
│
└── [OTHER PROJECT FILES]
```

---

## Code Location Map

### PHP Backend
```
select_employee.php: Lines 25-102

25   ├─ Check if POST with branch_action
30   ├─ Add Branch Handler
     │  ├─ Check admin role
     │  ├─ Validate branch name
     │  ├─ Check for duplicates
     │  └─ Insert to database
50   │
51   └─ Delete Branch Handler
        ├─ Check admin role
        ├─ Get branch details
        ├─ Check for employees
        └─ Delete from database
```

### HTML UI
```
select_employee.php: Lines 1225-1275

1225 ├─ Branch Selection Container
     │
1226 ├─ Branch Header
     │  ├─ Title: "Select Deployment Branch"
     │  └─ Add Branch Button (yellow)
     │
1232 ├─ Branch Grid
     │  └─ Branch Cards (looped from database)
     │
1241 └─ Add Branch Modal
        ├─ Header
        ├─ Input field for branch name
        └─ Cancel/Add buttons
```

### CSS Styling
```
select_employee.php: Lines 1155-1227

1155 ├─ .branch-header
     │  └─ Flexbox layout for title + button
1161 │
1162 ├─ .btn-add-branch
     │  ├─ Yellow background (#FFD700)
     │  ├─ Hover effects
     │  └─ Flex layout
1172 │
1173 ├─ .btn-remove-branch
     │  ├─ Red background (#dc2626)
     │  ├─ Position absolute on cards
     │  └─ Opacity toggle on hover
1187 │
1188 ├─ #branchMessage
     │  ├─ Success styling
     │  └─ Error styling
1204 │
1205 └─ @media responsive
        └─ Mobile layout adjustments
```

### JavaScript Functions
```
select_employee.php: Lines 1812-2009

1813 ├─ Initialize admin check
1820 │
1821 ├─ closeAddBranchModal()
     │  └─ Hide modal, reset form, clear messages
1826 │
1827 ├─ submitAddBranch(event)
     │  ├─ Prevent default
     │  ├─ Validate input
     │  ├─ POST to select_employee.php
     │  └─ Handle response
1863 │
1864 ├─ addBranchCardToUI(id, name)
     │  ├─ Create new card element
     │  ├─ Add delete button
     │  └─ Attach click handler
1884 │
1885 ├─ removeBranch(id, name)
     │  ├─ Stop propagation
     │  ├─ Confirm action
     │  ├─ POST to delete endpoint
     │  └─ Update UI with animation
1945 │
1946 ├─ showBranchMessage(msg, type)
     │  └─ Display success/error
1958 │
1959 ├─ showGlobalMessage(msg, type)
     │  └─ Delegate to showSuccess/showError
1967 │
1968 ├─ selectBranch(card)
     │  ├─ Remove selected class from all
     │  ├─ Add selected class to clicked
     │  └─ Load employees
1978 │
1979 └─ Attach click handlers to branch cards
        └─ Call selectBranch() on click
```

---

## Data Flow Diagram

### Adding a Branch

```
User clicks "Add Branch" button
        ↓
Modal opens (JavaScript)
        ↓
User enters branch name
        ↓
User clicks "Add Branch" button
        ↓
submitAddBranch(event) triggered
        ↓
Validate name (2-255 chars)
        ↓
POST request to select_employee.php
        ↓
PHP Handler: $_POST['branch_action'] === 'add_branch'
        ↓
Check if user is Admin/Manager/Supervisor
        ↓
Validate branch name format
        ↓
Check for duplicate (SELECT query)
        ↓
INSERT into branches table
        ↓
Return JSON { success: true, branch_id: X, branch_name: Y }
        ↓
JavaScript receives response
        ↓
addBranchCardToUI() creates new card
        ↓
New branch appears in grid
        ↓
Modal closes after 1.5s
```

### Deleting a Branch

```
User hovers over branch card
        ↓
Red X button becomes visible (CSS opacity)
        ↓
User clicks X button
        ↓
removeBranch(id, name) called
        ↓
confirm() dialog appears
        ↓
User confirms deletion
        ↓
POST request to select_employee.php
        ↓
PHP Handler: $_POST['branch_action'] === 'delete_branch'
        ↓
Check if user is Admin/Manager/Supervisor
        ↓
Get branch details (SELECT query)
        ↓
Check employee count (COUNT query)
        ↓
If employees exist → Return error
        ↓
If no employees → DELETE from branches
        ↓
Return JSON { success: true/false, message: "" }
        ↓
JavaScript receives response
        ↓
If success: Animate removal + show message
        ↓
If error: Show error message
        ↓
Card removed from DOM
```

### Selecting a Branch

```
User clicks on branch card
        ↓
selectBranch(cardElement) called
        ↓
Remove .selected class from all cards
        ↓
Add .selected class to clicked card
        ↓
Set selectedBranch variable
        ↓
Enable search input
        ↓
Call loadEmployees(branch, showMarked)
        ↓
POST employees data request (existing logic)
        ↓
Employees grid populated with matching employees
```

---

## Component Interaction

```
┌─────────────────────────────────────────┐
│         select_employee.php             │
└─────────────────────────────────────────┘
        ↑                   ↑              ↑
        │                   │              │
    [DB Query]          [AJAX POST]    [Click/Submit]
        │                   │              │
        ↓                   ↓              ↓
    ┌───────┐        ┌──────────┐    ┌──────────┐
    │ MySQL │        │ JavaScript│   │ HTML/CSS │
    │(branches)│     │ Functions │   │ Elements │
    └───────┘        └──────────┘    └──────────┘
```

---

## Database Table Structure

```
branches (table)
├─ id (INT AUTO_INCREMENT PRIMARY KEY)
├─ branch_name (VARCHAR 255 UNIQUE)
├─ created_at (TIMESTAMP DEFAULT CURRENT_TIMESTAMP)
├─ updated_at (TIMESTAMP ON UPDATE)
├─ is_active (TINYINT DEFAULT 1)
│
└─ Indexes:
   ├─ idx_branch_name (for fast lookups)
   └─ idx_is_active (for filtering)
```

---

## User Permission Flow

```
User Login
        ↓
Check $_SESSION['role']
        ↓
    ┌───┴────────────────┐
    │                    │
Regular Employee    Admin/Manager/Supervisor
    │                    │
    ├─ See branches  ✓   ├─ See branches  ✓
    ├─ Select branch ✓   ├─ Select branch ✓
    ├─ Add button    ✗   ├─ Add button    ✓
    └─ Delete button ✗   └─ Delete button ✓
```

---

## Timeline - What Happens When

### On Page Load
```
1. PHP: Check user role
2. PHP: Fetch branches from database
3. HTML: Render branch cards
4. CSS: Apply styles (hidden delete buttons)
5. JavaScript: Attach event listeners
6. JavaScript: Check if admin → show/hide add button
```

### On "Add Branch" Click
```
1. JavaScript: Stop default
2. JavaScript: Open modal (CSS display)
3. JavaScript: Focus input field
```

### On Modal Submit
```
1. JavaScript: Validate input (2-255 chars)
2. JavaScript: POST to select_employee.php
3. PHP: Validate again
4. PHP: Check if duplicate
5. PHP: Insert to database
6. PHP: Return JSON response
7. JavaScript: Create new card UI
8. JavaScript: Reset form
9. JavaScript: Show success message
10. JavaScript: Close modal after 1.5s
```

### On Branch Card Click
```
1. JavaScript: Stop propagation
2. JavaScript: Update CSS class
3. JavaScript: POST employee load request
4. PHP: Fetch employees for branch
5. PHP: Return JSON employees
6. JavaScript: Render employee grid
```

### On Delete Button Click
```
1. JavaScript: Show confirmation dialog
2. JavaScript: POST delete request (if confirmed)
3. PHP: Validate branch exists
4. PHP: Count active employees
5. PHP: If count > 0, return error
6. PHP: If count = 0, DELETE from database
7. PHP: Return JSON response
8. JavaScript: Animate removal
9. JavaScript: Remove from DOM
10. JavaScript: Show success/error message
```

---

## Security Checkpoints

```
Input → Validation → Sanitization → Database
        ↓              ↓               ↓
    Check role    Check length    Prepared statements
    Check length  Check for XSS   Unique constraint
    Check format  Check duplicates SQL injection prevention
```

---

Everything is contained within one file: **select_employee.php** ✅
