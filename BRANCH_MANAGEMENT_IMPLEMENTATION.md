# Branch Management Feature Implementation Guide

## Overview
This guide provides all the code needed to implement the 'Add Branch' and 'Remove Branch' functionality in your select_employee.php file.

---

## 1. DATABASE SETUP

### SQL Query to Create Branches Table

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

**Run this in phpMyAdmin or your database client:**
1. Go to phpMyAdmin
2. Select your database
3. Go to SQL tab
4. Paste the query above
5. Click Execute

---

## 2. BACKEND PHP SCRIPT (branch_actions.php)

**File Location:** `/employee/branch_actions.php`

This file is already created in your project and handles:
- Adding new branches
- Deleting branches (with validation)
- Fetching all active branches

---

## 3. SELECT_EMPLOYEE.PHP MODIFICATIONS

### A. Update the PHP section to fetch branches from database

Replace this section in select_employee.php (around line 22):

```php
// Get available branches
$branchesQuery = "SELECT DISTINCT branch_name FROM employees WHERE branch_name IS NOT NULL AND branch_name != '' ORDER BY branch_name";
$branchesResult = mysqli_query($db, $branchesQuery);
$branches = [];
while ($row = mysqli_fetch_assoc($branchesResult)) {
    $branches[] = $row['branch_name'];
}
```

With this:

```php
// Get available branches from branches table
$branchesQuery = "SELECT id, branch_name FROM branches WHERE is_active = 1 ORDER BY branch_name ASC";
$branchesResult = mysqli_query($db, $branchesQuery);
$branches = [];
while ($row = mysqli_fetch_assoc($branchesResult)) {
    $branches[] = [
        'id' => $row['id'],
        'branch_name' => $row['branch_name']
    ];
}
```

---

### B. Update the HTML Branch Selection Section

Find this section in select_employee.php (around line 1122-1135):

```html
<!-- Branch Selection -->
<div class="branch-selection">
  <div class="branch-title">Select Deployment Branch</div>
  <div class="branch-grid">
    <?php foreach ($branches as $branch): ?>
    <div class="branch-card" data-branch="<?php echo htmlspecialchars($branch); ?>">
      <div class="branch-name"><?php echo htmlspecialchars($branch); ?></div>
      <div class="branch-desc">Deploy employees to this branch</div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
```

Replace with this:

```html
<!-- Branch Selection -->
<div class="branch-selection">
  <div class="branch-header">
    <div class="branch-title">Select Deployment Branch</div>
    <button class="btn-add-branch" id="addBranchBtn" title="Add new branch">
      <i class="fas fa-plus"></i> Add Branch
    </button>
  </div>
  <div class="branch-grid" id="branchGrid">
    <?php foreach ($branches as $branch): ?>
    <div class="branch-card" data-branch-id="<?php echo htmlspecialchars($branch['id']); ?>" data-branch="<?php echo htmlspecialchars($branch['branch_name']); ?>">
      <button class="btn-remove-branch" onclick="removeBranch(<?php echo htmlspecialchars($branch['id']); ?>, '<?php echo htmlspecialchars($branch['branch_name']); ?>')" title="Delete branch">
        <i class="fas fa-times"></i>
      </button>
      <div class="branch-name"><?php echo htmlspecialchars($branch['branch_name']); ?></div>
      <div class="branch-desc">Deploy employees to this branch</div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Add Branch Modal -->
<div id="addBranchModal" class="modal-backdrop">
  <div class="modal-panel" style="width: 420px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 style="margin: 0; color: #FFD700; font-size: 18px;">Add New Branch</h3>
      <button onclick="closeAddBranchModal()" style="background: none; border: none; color: #888; font-size: 24px; cursor: pointer; padding: 0;">
        <i class="fas fa-times"></i>
      </button>
    </div>
    
    <form id="addBranchForm" onsubmit="submitAddBranch(event)">
      <div class="form-row">
        <label style="font-size: 12px; color: #FFD700; font-weight: 600; margin-bottom: 6px; display: block;">Branch Name</label>
        <input 
          type="text" 
          id="branchNameInput" 
          name="branch_name" 
          placeholder="Enter branch name (e.g., Main Office, Branch A)" 
          required 
          style="background: transparent; border: 1px solid rgba(255,255,255,0.04); padding: 0.6rem 0.75rem; border-radius: 8px; color: #ffffff; width: 100%;"
        />
        <small style="color: #888; font-size: 11px; margin-top: 4px; display: block;">Branch names must be unique and 2-255 characters</small>
      </div>

      <div style="display: flex; gap: 8px; margin-top: 16px; justify-content: flex-end;">
        <button type="button" onclick="closeAddBranchModal()" style="background: transparent; border: 1px solid rgba(255,255,255,0.1); color: #888; padding: 0.6rem 1rem; border-radius: 6px; cursor: pointer; font-weight: 600;">
          Cancel
        </button>
        <button type="submit" style="background: #FFD700; border: none; color: #0b0b0b; padding: 0.6rem 1rem; border-radius: 6px; cursor: pointer; font-weight: 600;">
          <i class="fas fa-plus"></i> Add Branch
        </button>
      </div>
    </form>
  </div>
</div>
```

---

### C. Add CSS Styles

Add these CSS styles to the `<style>` section in select_employee.php (add before the closing `</style>` tag):

```css
/* Branch Management Styles */
.branch-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.branch-title {
    font-size: 16px;
    font-weight: 700;
    color: #FFD700;
}

.btn-add-branch {
    background: #FFD700;
    color: #0b0b0b;
    border: none;
    padding: 8px 14px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
}

.btn-add-branch:hover {
    background: #FFC800;
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(255, 215, 0, 0.3);
}

.branch-card {
    background: #2a2a2a;
    border: 2px solid #333;
    border-radius: 6px;
    padding: 12px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.branch-card:hover {
    border-color: #FFD700;
    transform: translateY(-2px);
}

.branch-card.selected {
    border-color: #FFD700;
    background: #3a3a3a;
}

.branch-card.selected::after {
    content: '✓';
    position: absolute;
    top: 4px;
    left: 8px;
    color: #FFD700;
    font-size: 14px;
    font-weight: bold;
}

.btn-remove-branch {
    position: absolute;
    top: 6px;
    right: 6px;
    background: #dc2626;
    color: white;
    border: none;
    border-radius: 4px;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s ease;
    opacity: 0;
    padding: 0;
}

.branch-card:hover .btn-remove-branch {
    opacity: 1;
}

.btn-remove-branch:hover {
    background: #b91c1c;
    transform: scale(1.1);
}

/* Modal for Add Branch */
.modal-backdrop {
    position: fixed;
    inset: 0;
    display: none;
    align-items: center;
    justify-content: center;
    background: linear-gradient(0deg, rgba(0,0,0,0.6), rgba(0,0,0,0.6));
    z-index: 80;
}

.modal-backdrop.show {
    display: flex;
}

.modal-panel {
    background: #1a1a1a;
    padding: 1.1rem;
    border-radius: 12px;
    box-shadow: 0 30px 80px rgba(0,0,0,0.8);
    border: 1px solid rgba(255,255,255,0.03);
    max-width: 94%;
}

.form-row {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.6rem;
    flex-direction: column;
}

.form-row input {
    background: transparent;
    border: 1px solid rgba(255,255,255,0.04);
    padding: 0.6rem 0.75rem;
    border-radius: 8px;
    color: #ffffff;
    width: 100%;
}

.form-row input:focus {
    outline: none;
    border-color: #FFD700;
    box-shadow: 0 0 0 2px rgba(255, 215, 0, 0.1);
}

.form-row input::placeholder {
    color: rgba(255,255,255,0.4);
}

/* Success/Error Messages in Modal */
#branchMessage {
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 12px;
    font-size: 12px;
    display: none;
}

#branchMessage.success {
    background: rgba(22, 163, 74, 0.2);
    border: 1px solid #16a34a;
    color: #16a34a;
    display: block;
}

#branchMessage.error {
    background: rgba(220, 38, 38, 0.2);
    border: 1px solid #dc2626;
    color: #dc2626;
    display: block;
}

/* Responsive */
@media (max-width: 768px) {
    .branch-header {
        flex-direction: column;
        align-items: stretch;
    }

    .btn-add-branch {
        width: 100%;
        justify-content: center;
    }

    .branch-grid {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    }

    .modal-panel {
        width: 90% !important;
    }
}
```

---

### D. Add JavaScript Functions

Add these JavaScript functions to the `<script>` section in select_employee.php (add before the closing `</script>` tag):

```javascript
// ===== BRANCH MANAGEMENT FUNCTIONS =====

// Open Add Branch Modal
document.getElementById('addBranchBtn').addEventListener('click', function() {
    document.getElementById('addBranchModal').classList.add('show');
    document.getElementById('branchNameInput').focus();
});

// Close Add Branch Modal
function closeAddBranchModal() {
    document.getElementById('addBranchModal').classList.remove('show');
    document.getElementById('addBranchForm').reset();
    clearBranchMessage();
}

// Close modal when clicking backdrop
document.getElementById('addBranchModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddBranchModal();
    }
});

// Submit Add Branch Form
function submitAddBranch(event) {
    event.preventDefault();
    
    const branchName = document.getElementById('branchNameInput').value.trim();
    
    if (!branchName) {
        showBranchMessage('Branch name is required', 'error');
        return;
    }

    if (branchName.length < 2) {
        showBranchMessage('Branch name must be at least 2 characters', 'error');
        return;
    }

    // Disable submit button
    const submitBtn = document.querySelector('#addBranchForm button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';

    const formData = new FormData();
    formData.append('action', 'add_branch');
    formData.append('branch_name', branchName);

    fetch('branch_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showBranchMessage('Branch added successfully!', 'success');
            document.getElementById('addBranchForm').reset();
            
            // Add new branch card to the grid
            addBranchCardToUI(data.branch_id, data.branch_name);
            
            // Close modal after 1.5 seconds
            setTimeout(() => {
                closeAddBranchModal();
            }, 1500);
        } else {
            showBranchMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showBranchMessage('Failed to add branch', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

// Add Branch Card to UI
function addBranchCardToUI(branchId, branchName) {
    const branchGrid = document.getElementById('branchGrid');
    
    const branchCard = document.createElement('div');
    branchCard.className = 'branch-card';
    branchCard.setAttribute('data-branch-id', branchId);
    branchCard.setAttribute('data-branch', branchName);
    branchCard.innerHTML = `
        <button class="btn-remove-branch" onclick="removeBranch(${branchId}, '${branchName.replace(/'/g, "\\'")}')" title="Delete branch">
            <i class="fas fa-times"></i>
        </button>
        <div class="branch-name">${branchName}</div>
        <div class="branch-desc">Deploy employees to this branch</div>
    `;
    
    branchGrid.appendChild(branchCard);
    
    // Re-attach click handler for new card
    branchCard.addEventListener('click', function() {
        selectBranch(this);
    });
}

// Remove Branch
function removeBranch(branchId, branchName) {
    event.stopPropagation(); // Prevent triggering branch selection
    
    if (!confirm(`Are you sure you want to delete the branch "${branchName}"?\n\nThis action cannot be undone.`)) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'delete_branch');
    formData.append('branch_id', branchId);

    // Show loading state
    const branchCard = document.querySelector(`[data-branch-id="${branchId}"]`);
    const removeBtn = branchCard.querySelector('.btn-remove-branch');
    const originalContent = removeBtn.innerHTML;
    removeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    removeBtn.disabled = true;

    fetch('branch_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Animate removal
            branchCard.style.transition = 'all 0.3s ease';
            branchCard.style.opacity = '0';
            branchCard.style.transform = 'scale(0.9)';
            
            setTimeout(() => {
                branchCard.remove();
                showGlobalMessage(data.message, 'success');
                
                // Reset selected branch if deleted
                if (selectedBranch === branchName) {
                    selectedBranch = null;
                    document.getElementById('employeeContainer').innerHTML = `
                        <div class="no-employees">
                            <i class="fas fa-users" style="font-size: 36px; color: #444; margin-bottom: 10px;"></i>
                            <div>Branch deleted. Please select another deployment branch</div>
                        </div>
                    `;
                }
            }, 300);
        } else {
            removeBtn.innerHTML = originalContent;
            removeBtn.disabled = false;
            showGlobalMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        removeBtn.innerHTML = originalContent;
        removeBtn.disabled = false;
        showGlobalMessage('Failed to delete branch', 'error');
    });
}

// Show Branch Message in Modal
function showBranchMessage(message, type) {
    let messageEl = document.getElementById('branchMessage');
    if (!messageEl) {
        messageEl = document.createElement('div');
        messageEl.id = 'branchMessage';
        document.getElementById('addBranchForm').insertBefore(messageEl, document.getElementById('addBranchForm').firstChild);
    }
    
    messageEl.textContent = message;
    messageEl.className = type;
}

// Clear Branch Message
function clearBranchMessage() {
    const messageEl = document.getElementById('branchMessage');
    if (messageEl) {
        messageEl.className = '';
        messageEl.textContent = '';
    }
}

// Show Global Message (success/error at top)
function showGlobalMessage(message, type) {
    if (type === 'success') {
        showSuccess(message);
    } else {
        showError(message);
    }
}

// Update branch selection handler
function selectBranch(cardElement) {
    // Remove selected class from all cards
    document.querySelectorAll('.branch-card').forEach(c => c.classList.remove('selected'));
    
    // Add selected class to clicked card
    cardElement.classList.add('selected');
    selectedBranch = cardElement.dataset.branch;
    
    // Enable search
    document.getElementById('searchInput').disabled = false;
    
    // Load employees
    loadEmployees(selectedBranch, showMarked);
}

// Attach click handlers to branch cards
document.querySelectorAll('.branch-card').forEach(card => {
    card.addEventListener('click', function() {
        selectBranch(this);
    });
});
```

---

## 4. INTEGRATION CHECKLIST

- [ ] Run the SQL query to create the branches table
- [ ] Create/confirm branch_actions.php exists in /employee/
- [ ] Update the PHP branches fetching code in select_employee.php
- [ ] Replace the HTML branch selection section
- [ ] Add the new CSS styles to the style section
- [ ] Add the JavaScript functions before closing script tag
- [ ] Test 'Add Branch' button - opens modal
- [ ] Test adding a branch - appears in the grid
- [ ] Test removing a branch - deleted with confirmation
- [ ] Test branch selection - employees load correctly
- [ ] Test responsive design on mobile

---

## 5. NOTES

- The 'Remove Branch' button only appears on hover over the branch card
- You cannot delete branches that have active employees assigned
- Branch names must be unique (database enforces this)
- The modal closes automatically after successful branch addition
- All operations have proper error handling and user feedback
- The feature maintains the existing dark theme (#0b0b0b, #FFD700)

---

## 6. TROUBLESHOOTING

**Issue:** "Unauthorized access" error when adding/removing branches
**Solution:** Check if your user role is set correctly. Update the role check in branch_actions.php line 12 if needed.

**Issue:** Branch doesn't appear after adding
**Solution:** Check browser console for errors. Ensure branch_actions.php is in the correct path.

**Issue:** Cannot delete branch
**Solution:** Branches with active employees cannot be deleted. Remove/reassign employees first.
