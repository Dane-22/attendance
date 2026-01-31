# Quick Start Guide - Branch Management

## Setup (2 minutes)

### Step 1: Create Database Table
Copy and paste this into phpMyAdmin SQL tab:

```sql
CREATE TABLE IF NOT EXISTS branches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    branch_name VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active TINYINT DEFAULT 1
);
CREATE INDEX idx_branch_name ON branches(branch_name);
CREATE INDEX idx_is_active ON branches(is_active);
```

### Step 2: Verify Integration
- ✅ File `/employee/select_employee.php` contains all branch management code
- ✅ No additional files needed
- ✅ No external dependencies

---

## What's Integrated

**File:** `c:\wamp64\www\attendance_web\employee\select_employee.php`

### 1. Backend Actions (Lines 25-102)
- Handle add_branch POST request
- Handle delete_branch POST request  
- Validate branch names (2-255 chars, unique)
- Check for active employees before deletion

### 2. Frontend HTML (Lines 1225-1275)
- Branch header with Add Branch button (yellow #FFD700)
- Branch grid displaying branches from database
- Delete button (red) on each branch card (admin only)
- Modal form for adding new branches

### 3. CSS Styling (Lines 1155-1227)
- Dark theme colors (#0b0b0b, #FFD700, #dc2626)
- Responsive design for mobile
- Smooth animations and transitions
- Hover effects on buttons

### 4. JavaScript Functions (Lines 1812-2009)
- Modal open/close
- Form submission and validation
- Add branch to UI without reload
- Delete branch with confirmation
- Error/success messages

---

## User Roles

### Regular Employees
- Can select branches
- Cannot see Add/Delete buttons

### Admin/Manager/Supervisor
- Can select branches
- See "Add Branch" button
- See delete button (X) on branch cards

---

## Features

✅ **Add Branch** - Click yellow "Add Branch" button
✅ **Delete Branch** - Hover on card, click red X, confirm deletion
✅ **Real-time UI** - Updates without page reload
✅ **Validation** - Branch names 2-255 characters, must be unique
✅ **Employee Protection** - Can't delete branches with active employees
✅ **Dark Theme** - Matches existing design
✅ **Mobile Ready** - Responsive layout

---

## Testing

Visit: `http://yoursite/employee/select_employee.php`

1. Login as Admin/Manager/Supervisor
2. Find "Select Deployment Branch" section
3. Click "Add Branch" button (yellow)
4. Enter branch name, click "Add Branch"
5. New branch appears in grid
6. Hover over any branch card
7. Click red X button to delete
8. Confirm deletion

---

## That's It! 🎉

Everything you need is in one file: `select_employee.php`

No additional files to create or install.
No separate PHP scripts needed.
Just run the SQL once and you're done!
