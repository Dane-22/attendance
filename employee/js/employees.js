
    // ===== MODAL FUNCTIONALITY =====
    const openAddDesktop = document.getElementById('openAddDesktop');
    const openAddMobile = document.getElementById('openAddMobile');
    const addModal = document.getElementById('addModal');
    const closeAdd = document.getElementById('closeAdd');
    const editModal = document.getElementById('editModal');
    
    function openAddModal() {
      addModal.style.display = 'flex';
    }
    
    function closeAddModal() {
      addModal.style.display = 'none';
    }
    
    openAddDesktop?.addEventListener('click', openAddModal);
    openAddMobile?.addEventListener('click', openAddModal);
    closeAdd?.addEventListener('click', closeAddModal);
    
    addModal?.addEventListener('click', (e) => {
      if(e.target === addModal) {
        closeAddModal();
      }
    });

    // ===== EDIT MODAL FUNCTIONALITY =====
    let currentEditEmployeeId = null;

    function openEditModal(event, employeeId) {
      event.stopPropagation();
      currentEditEmployeeId = employeeId;
      
      // Load employee data via AJAX
      fetch(`get_employee_data.php?id=${employeeId}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const employee = data.employee;
            
            // Populate form fields
            document.getElementById('editEmployeeId').textContent = employee.employee_code;
            document.getElementById('editEmployeeIdInput').value = employee.id;
            document.getElementById('editEmployeeCode').value = employee.employee_code;
            document.getElementById('editFirstName').value = employee.first_name;
            document.getElementById('editMiddleName').value = employee.middle_name || '';
            document.getElementById('editLastName').value = employee.last_name;
            document.getElementById('editEmail').value = employee.email;
            document.getElementById('editPhone').value = employee.phone || '';
            document.getElementById('editPosition').value = employee.position;
            document.getElementById('editDepartment').value = employee.department || '';
            document.getElementById('editStatus').value = employee.status;
            
            // Update profile image preview
            const profileImagePreview = document.getElementById('profileImagePreview');
            const profileImageInitials = document.getElementById('profileImageInitials');
            
            if (employee.profile_image) {
              profileImagePreview.innerHTML = `<img src="uploads/${employee.profile_image}" alt="Profile" onerror="this.style.display='none'; document.getElementById('profileImageInitials').style.display='flex';">`;
              profileImageInitials.style.display = 'none';
              profileImageInitials.textContent = (employee.first_name[0] + employee.last_name[0]).toUpperCase();
            } else {
              profileImagePreview.innerHTML = '';
              profileImageInitials.style.display = 'flex';
              profileImageInitials.textContent = (employee.first_name[0] + employee.last_name[0]).toUpperCase();
              profileImagePreview.appendChild(profileImageInitials);
            }
            
            // Show modal
            editModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
          } else {
            alert('Error loading employee data: ' + (data.message || 'Unknown error'));
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error loading employee data. Please try again.');
        });
    }

    function closeEditModal() {
      editModal.style.display = 'none';
      document.body.style.overflow = 'auto';
      currentEditEmployeeId = null;
    }

    function previewProfileImage(input) {
      const preview = document.getElementById('profileImagePreview');
      const initials = document.getElementById('profileImageInitials');
      
      if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
          preview.innerHTML = `<img src="${e.target.result}" alt="Profile Preview" style="width:100%;height:100%;object-fit:cover;">`;
          initials.style.display = 'none';
        }
        
        reader.readAsDataURL(input.files[0]);
      }
    }

    // Close modal when clicking outside
    editModal?.addEventListener('click', function(e) {
      if (e.target === this) {
        closeEditModal();
      }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeEditModal();
        closeAddModal();
      }
    });

    // ===== EMPLOYEE ACTIONS =====
    function deleteEmployee(event, employeeId, employeeName) {
      event.stopPropagation();
      
      if (confirm(`Are you sure you want to delete employee "${employeeName}"? This action cannot be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="${employeeId}">
        `;
        document.body.appendChild(form);
        form.submit();
      }
    }

    function viewEmployeeProfile(employeeId) {
      // In a real application, this would redirect to a profile page
      // For now, open the edit modal
      openEditModal({stopPropagation: () => {}}, employeeId);
    }

    // ===== PAGINATION FUNCTIONS =====
    function changePageSize(newSize) {
      const url = new URL(window.location.href);
      url.searchParams.set('per_page', newSize);
      url.searchParams.set('page', '1'); // Reset to first page when changing size
      window.location.href = url.toString();
    }

    function jumpToPage() {
      const pageInput = document.getElementById('pageJumpInput');
      let page = parseInt(pageInput.value);
      const totalPages = <?php echo $totalPages; ?>;
      const currentView = '<?php echo $currentView; ?>';
      const perPage = <?php echo $perPage; ?>;
      
      if (isNaN(page) || page < 1 || page > totalPages) {
        pageInput.value = <?php echo $page; ?>;
        alert(`Please enter a page number between 1 and ${totalPages}`);
        return;
      }
      
      const url = new URL(window.location.href);
      url.searchParams.set('page', page);
      window.location.href = url.toString();
    }

    // Handle Enter key on page jump input
    document.getElementById('pageJumpInput')?.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        jumpToPage();
      }
    });

    // Auto-close edit forms when clicking outside on mobile
    document.addEventListener('click', function(e) {
      if (window.innerWidth <= 768) {
        const openDetails = document.querySelector('details[open]');
        if (openDetails && !openDetails.contains(e.target)) {
          openDetails.removeAttribute('open');
        }
      }
    });

    // ===== SEARCH FUNCTIONALITY =====
    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearch');

    // Show/hide clear button based on input
    searchInput?.addEventListener('input', function() {
      const hasValue = this.value.trim().length > 0;
      clearSearchBtn.style.display = hasValue ? 'flex' : 'none';
    });

    // Clear search
    clearSearchBtn?.addEventListener('click', function() {
      searchInput.value = '';
      this.style.display = 'none';
      performSearch();
    });

    // Perform search on Enter key
    searchInput?.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        performSearch();
      }
    });

    // Debounced search (search after user stops typing for 500ms)
    let searchTimeout;
    searchInput?.addEventListener('input', function() {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        performSearch();
      }, 500);
    });

    function performSearch() {
      const searchTerm = searchInput.value.trim();
      const url = new URL(window.location.href);
      
      if (searchTerm) {
        url.searchParams.set('search', searchTerm);
      } else {
        url.searchParams.delete('search');
      }
      
      // Reset to first page when searching
      url.searchParams.set('page', '1');
      
      window.location.href = url.toString();
    }

    // Initialize clear button visibility on page load
    if (searchInput && searchInput.value.trim()) {
      clearSearchBtn.style.display = 'flex';
    }
