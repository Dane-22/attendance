// ===== EMPLOYEE ATTENDANCE MANAGEMENT SCRIPT =====

    // Global variables
    let selectedBranch = null;
    let currentStatusFilter = 'available'; // 'all', 'present', 'absent', or 'available'
    let currentView = 'list';
    let currentEmployees = [];
    let currentSearchTerm = '';
    let searchDebounceTimer = null;
    let isBeforeCutoff = '<?php echo $isBeforeCutoff ? "true" : "false"; ?>';
    let cutoffTime = '<?php echo $cutoffTime; ?>';
    let currentTime = '<?php echo $currentTime; ?>';

    function formatTime(t) {
      if (!t) return '--';
      const s = String(t);
      const trimmed = s.trim();
      if (!trimmed) return '--';

      // Accept both DATETIME (YYYY-MM-DD HH:MM:SS) and TIME (HH:MM:SS)
      const parts = trimmed.split(' ');
      const last = parts[parts.length - 1];

      // Handle ISO format like YYYY-MM-DDTHH:MM:SS
      const isoParts = last.split('T');
      const timePart = isoParts[isoParts.length - 1];
      return timePart;
    }

    // Pagination variables
    let currentPage = 1;
    let perPage = 10;
    let totalEmployees = 0;
    let totalPages = 1;
    let isLoading = false;
    let pendingAction = null;
    let pendingFinalizeTimer = null;
    let pendingCountdownTimer = null;

    let lastActionByEmployee = {};

    // Initialize page size from localStorage
    const savedPageSize = localStorage.getItem('employeePageSize');
    if (savedPageSize) {
        perPage = parseInt(savedPageSize);
        document.getElementById('pageSizeSelect').value = perPage;
        document.getElementById('pageSizeSelectBottom').value = perPage;
    }

    // Branch selection
    document.querySelectorAll('.branch-card').forEach(card => {
      card.addEventListener('click', function() {
        // Remove selected class from all cards
        document.querySelectorAll('.branch-card').forEach(c => c.classList.remove('selected'));

        // Add selected class to clicked card
        this.classList.add('selected');
        selectedBranch = this.dataset.branch;

        // Enable search
        document.getElementById('searchInput').disabled = false;

        // Reset to page 1 when branch changes
        currentPage = 1;
        
        // Load employees
        reloadEmployees();
      });
    });

    // Status filter dropdown
    document.getElementById('statusFilter').addEventListener('change', function() {
        currentStatusFilter = this.value;
        // Reset to page 1 when filter changes
        currentPage = 1;
        // If searching, ignore filter and reload search results; otherwise reload branch
        reloadEmployees();
    });

    // Search functionality
    document.getElementById('searchInput').addEventListener('input', function() {
        const raw = (this.value || '').trim();
        currentSearchTerm = raw;

        if (searchDebounceTimer) {
          clearTimeout(searchDebounceTimer);
        }

        searchDebounceTimer = setTimeout(() => {
          currentPage = 1;
          reloadEmployees();
        }, 300);
    });

    function reloadEmployees() {
      const hasSearch = !!(currentSearchTerm && currentSearchTerm.trim());

      if (hasSearch) {
        // Search mode: across all employees across all branches, ignore dropdown filter
        loadEmployees('', currentPage, perPage, 'all', currentSearchTerm);
        return;
      }

      // Normal mode: requires selected branch
      if (!selectedBranch) return;
      loadEmployees(selectedBranch, currentPage, perPage, currentStatusFilter, '');
    }

    // Load employees function with pagination
    function loadEmployees(branch, page = 1, perPage = 10, statusFilter = 'all', searchTerm = '') {
      if (isLoading) return;
      
      const container = document.getElementById('employeeContainer');
      container.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin" style="font-size: 18px; margin-bottom: 10px;"></i><div>Loading employees...</div></div>';
      
      // Show loading in pagination
      showPaginationLoading(true);

      isLoading = true;

      const formData = new FormData();
      formData.append('action', 'load_employees');
      formData.append('branch', branch);
      formData.append('status_filter', statusFilter);
      formData.append('page', page);
      formData.append('per_page', perPage);
      formData.append('search_term', searchTerm || '');

      console.log('DEBUG: Loading employees - Branch:', branch, 'Status Filter:', statusFilter, 'Page:', page, 'Per Page:', perPage);

      fetch('select_employee.php', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
      })
      .then(response => {
        console.log('DEBUG: Response status:', response.status);
        if (!response.ok) {
          throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        console.log('DEBUG: Response data:', data);
        if (data.success) {
          currentEmployees = data.employees;
          isBeforeCutoff = data.is_before_cutoff;
          currentTime = data.current_time;
          cutoffTime = data.cutoff_time;
          
          // Update time display in header
          updateTimeDisplay();
          
          // Update pagination info
          if (data.pagination) {
            currentPage = data.pagination.page;
            perPage = data.pagination.per_page;
            totalEmployees = data.pagination.total;
            totalPages = data.pagination.total_pages;
            
            console.log('DEBUG: Pagination info:', data.pagination);
            // Update pagination controls
            updatePaginationControls();
          }
          
          renderEmployees(currentEmployees);
        } else {
          console.error('DEBUG: Server returned error:', data.message);
          container.innerHTML = '<div class="no-employees"><i class="fas fa-exclamation-triangle" style="font-size: 36px; color: #dc2626; margin-bottom: 10px;"></i><div>Error: ' + data.message + '</div><div style="font-size: 11px; margin-top: 10px; color: #888;">Please check browser console for details</div></div>';
          hidePagination();
        }
      })
      .catch(error => {
        console.error('DEBUG: Fetch error:', error);
        container.innerHTML = '<div class="no-employees"><i class="fas fa-exclamation-triangle" style="font-size: 36px; color: #dc2626; margin-bottom: 10px;"></i><div>Failed to load employees</div><div style="font-size: 11px; margin-top: 10px; color: #888;">Check browser console (F12) for details</div><div style="font-size: 11px; margin-top: 5px; color: #888;">Error: ' + error.message + '</div></div>';
        hidePagination();
      })
      .finally(() => {
        isLoading = false;
        showPaginationLoading(false);
      });
    }

    // Function to update time display
    function updateTimeDisplay() {
      const timeAlert = document.querySelector('.time-alert');
      const timeAlertContent = document.querySelector('.time-alert-content');
      
      if (timeAlert && timeAlertContent) {
        if (isBeforeCutoff) {
          timeAlert.className = 'time-alert before-cutoff';
          timeAlert.querySelector('i').className = 'fas fa-clock';
          document.querySelector('.time-alert-title').textContent = 'Before 9:00 AM Cutoff (Philippine Time)';
          document.querySelector('.time-alert-message').innerHTML = `
            Current Philippine Time: <strong>${currentTime}</strong> | 
            Mark employees as Present before 9:00 AM (PH Time). After cutoff, unmarked employees will be automatically marked as Absent.
          `;
        } else {
          timeAlert.className = 'time-alert after-cutoff';
          timeAlert.querySelector('i').className = 'fas fa-exclamation-triangle';
          document.querySelector('.time-alert-title').textContent = 'After 9:00 AM Cutoff (Philippine Time)';
          document.querySelector('.time-alert-message').innerHTML = `
            Current Philippine Time: <strong>${currentTime}</strong> | 
            Unmarked employees have been automatically marked as Absent. You can still override to mark as Present (Late).
          `;
        }
      }
    }

    // Function to update pagination controls
    function updatePaginationControls() {
      if (totalEmployees === 0 || totalPages === 1) {
        hidePagination();
        return;
      }
      
      showPagination();
      
      // Calculate display range
      const from = Math.min((currentPage - 1) * perPage + 1, totalEmployees);
      const to = Math.min(currentPage * perPage, totalEmployees);
      
      // Update pagination info
      document.getElementById('paginationFrom').textContent = from;
      document.getElementById('paginationTo').textContent = to;
      document.getElementById('paginationTotal').textContent = totalEmployees;
      document.getElementById('currentPage').textContent = currentPage;
      document.getElementById('totalPages').textContent = totalPages;
      document.getElementById('pageJumpInput').value = currentPage;
      
      // Generate pagination buttons
      generatePaginationButtons('paginationButtonsTop');
      generatePaginationButtons('paginationButtonsBottom');
    }
    
    function generatePaginationButtons(containerId) {
      const container = document.getElementById(containerId);
      let html = '';
      
      // Previous button
      html += `<button class="page-btn" onclick="goToPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
                <i class="fas fa-chevron-left"></i>
              </button>`;
      
      // First page
      html += `<button class="page-btn ${currentPage === 1 ? 'active' : ''}" onclick="goToPage(1)">1</button>`;
      
      // Ellipsis if needed
      if (currentPage > 3) {
        html += '<span class="page-dots">...</span>';
      }
      
      // Pages around current page
      for (let i = Math.max(2, currentPage - 1); i <= Math.min(totalPages - 1, currentPage + 1); i++) {
        if (i > 1 && i < totalPages) {
          html += `<button class="page-btn ${currentPage === i ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>`;
        }
      }
      
      // Ellipsis if needed
      if (currentPage < totalPages - 2) {
        html += '<span class="page-dots">...</span>';
      }
      
      // Last page (if not first page)
      if (totalPages > 1) {
        html += `<button class="page-btn ${currentPage === totalPages ? 'active' : ''}" onclick="goToPage(${totalPages})">${totalPages}</button>`;
      }
      
      // Next button
      html += `<button class="page-btn" onclick="goToPage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
                <i class="fas fa-chevron-right"></i>
              </button>`;
      
      container.innerHTML = html;
    }
    
    function goToPage(page) {
      if (page < 1 || page > totalPages || page === currentPage || isLoading) return;
      
      currentPage = page;
      reloadEmployees();
      
      // Scroll to top of employee container
      document.getElementById('employeeContainer').scrollIntoView({ behavior: 'smooth' });
    }
    
    function jumpToPage() {
      const pageInput = document.getElementById('pageJumpInput');
      let page = parseInt(pageInput.value);
      
      if (isNaN(page) || page < 1 || page > totalPages) {
        pageInput.value = currentPage;
        return;
      }
      
      goToPage(page);
    }
    
    function changePageSize(newSize) {
      perPage = parseInt(newSize);
      currentPage = 1; // Reset to first page when changing page size
      
      // Save to localStorage
      localStorage.setItem('employeePageSize', perPage);
      
      // Update both select elements
      document.getElementById('pageSizeSelect').value = perPage;
      document.getElementById('pageSizeSelectBottom').value = perPage;
      
      reloadEmployees();
    }
    
    function showPagination() {
      document.getElementById('paginationTop').style.display = 'flex';
      document.getElementById('paginationBottom').style.display = 'flex';
    }
    
    function hidePagination() {
      document.getElementById('paginationTop').style.display = 'none';
      document.getElementById('paginationBottom').style.display = 'none';
    }
    
    function showPaginationLoading(show) {
      const loadingHTML = '<span class="pagination-loading"><i class="fas fa-spinner fa-spin"></i></span>';
      
      if (show) {
        document.getElementById('paginationFrom').innerHTML += loadingHTML;
      } else {
        const fromEl = document.getElementById('paginationFrom');
        const loadingEl = fromEl.querySelector('.pagination-loading');
        if (loadingEl) {
          loadingEl.remove();
        }
      }
    }

    // Render employees - ONLY LIST VIEW
    function renderEmployees(employees) {
      const container = document.getElementById('employeeContainer');

      if (employees.length === 0) {
        // Show appropriate message based on current filter
        let message = '';
        if (currentStatusFilter === 'present') {
          message = 'No employees marked as Present today';
        } else if (currentStatusFilter === 'available' || currentStatusFilter === 'absent') {
          if (isBeforeCutoff) {
            message = 'All employees have been marked! No available employees.';
          } else {
            message = 'No available employees. All have been marked or auto-absent.';
          }
        } else {
          message = 'No employees found';
        }
        
        container.innerHTML = `<div class="no-employees">
          <i class="fas fa-users" style="font-size: 36px; color: #444; margin-bottom: 10px;"></i>
          <div>${message}</div>
        </div>`;
        return;
      }

      const formatHours = (h) => {
        const n = Number(h);
        if (!isFinite(n) || n <= 0) return '0.00';
        return n.toFixed(2);
      };

      const escapeAttr = (s) => String(s ?? '').replace(/"/g, '&quot;');
      const escapeJsString = (s) => String(s ?? '').replace(/'/g, "\\'");

      let html = `
        <div class="employee-table-wrap">
          <table class="employee-table">
            <thead>
              <tr>
                <th style="width: 48px;">#</th>
                <th>Employee</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Total Hours</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
      `;

      employees.forEach((employee, index) => {
        const name = employee.name || '';
        const initials = name.trim().split(/\s+/).slice(0, 2).map(p => p[0] || '').join('').toUpperCase() || '?';
        const timeIn = formatTime(employee.time_in);
        const timeOut = formatTime(employee.time_out);
        const totalHours = formatHours(employee.total_hours);

        const hasOpenShift = !!employee.time_in && !employee.time_out;

        const menuId = `emp-menu-${employee.id}`;

        html += `
          <tr id="employee-${employee.id}" data-shift-id="${employee.shift_id || ''}" data-has-open-shift="${hasOpenShift ? '1' : '0'}">
            <td class="mono" style="font-weight: bold; color: #FFD700;">${index + 1}</td>
            <td>
              <div class="employee-cell">
                <div class="employee-avatar" aria-hidden="true">${escapeAttr(initials)}</div>
                <div class="employee-meta">
                  <div class="employee-name">${escapeAttr(name)}</div>
                  <div class="employee-sub employee-branch">
                    <span class="employee-branch-label">Current Branch:</span>
                    <span class="employee-branch-value">${escapeAttr(employee.logged_branch || '--')}</span>
                  </div>
                </div>
              </div>
            </td>
            <td class="mono time-in-cell">${escapeAttr(timeIn)}</td>
            <td class="mono time-out-cell">${escapeAttr(timeOut)}</td>
            <td class="mono">${escapeAttr(totalHours)}</td>
            <td>
              <div class="actions-cell">
                <button class="${hasOpenShift ? 'btn-present-late' : 'btn-present'} btn-shift-toggle"
                        onclick="toggleShift(${employee.id}, '${escapeJsString(name)}')"
                        title="${hasOpenShift ? 'Time Out' : 'Time In'}">
                  <i class="fas ${hasOpenShift ? 'fa-sign-out-alt' : 'fa-sign-in-alt'}"></i> ${hasOpenShift ? 'Time Out' : 'Time In'}
                </button>
                <div class="kebab-menu">
                  <button class="kebab-btn" onclick="toggleEmployeeMenu('${menuId}', ${employee.id})" aria-label="Options">
                    <i class="fas fa-ellipsis-v"></i>
                  </button>
                  <div class="kebab-dropdown" id="${menuId}" style="display: none;">
                    <button class="kebab-item" onclick="openTimeLogsModal(${employee.id}, '${escapeJsString(name)}')">
                      <i class="fas fa-clock"></i> Time Logs Today
                    </button>
                    <button class="kebab-item" onclick="showTransferDropdown(${employee.id}, '${escapeJsString(name)}', '${escapeJsString(employee.logged_branch)}')">
                      <i class="fas fa-exchange-alt"></i> Transfer
                    </button>
                    <button class="kebab-item" onclick="undoLastAction(${employee.id}, '${escapeJsString(name)}')">
                      <i class="fas fa-rotate-left"></i> Undo last action
                    </button>
                  </div>
                </div>
              </div>
            </td>
          </tr>
        `;
      });

      html += `
            </tbody>
          </table>
        </div>
      `;

      container.innerHTML = html;
    }

    function toggleEmployeeMenu(menuId, employeeId) {
      const menu = document.getElementById(menuId);
      if (!menu) return;
      const isOpen = menu.style.display !== 'none';

      document.querySelectorAll('.kebab-dropdown').forEach(el => {
        el.style.display = 'none';
      });

      menu.style.display = isOpen ? 'none' : 'block';
    }

    function closeTimeLogsModal() {
      const modal = document.getElementById('timeLogsModal');
      if (!modal) return;
      modal.classList.remove('show');
      const body = document.getElementById('timeLogsBody');
      if (body) body.textContent = '';
    }

    function openTimeLogsModal(employeeId, employeeName) {
      const modal = document.getElementById('timeLogsModal');
      const title = document.getElementById('timeLogsTitle');
      const body = document.getElementById('timeLogsBody');
      if (!modal || !title || !body) return;

      title.textContent = `Time Logs Today — ${employeeName}`;
      body.textContent = 'Loading...';
      modal.classList.add('show');

      const formData = new FormData();
      formData.append('action', 'get_shift_logs');
      formData.append('employee_id', employeeId);
      formData.append('limit', '50');

      fetch('select_employee.php', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
      })
      .then(r => r.json())
      .then(data => {
        if (!data || !data.success) {
          body.textContent = (data && data.message) ? data.message : 'Unable to load logs';
          return;
        }

        const logs = Array.isArray(data.logs) ? data.logs : [];
        if (logs.length === 0) {
          body.textContent = 'No logs found for today.';
          return;
        }

        body.innerHTML = logs.map(l => {
          const tin = formatTime(l.time_in || '--');
          const tout = formatTime(l.time_out || '--');
          return `<div class="time-log-row"><span class="mono">${tin}</span><span class="time-log-sep">→</span><span class="mono">${tout}</span></div>`;
        }).join('');
      })
      .catch(err => {
        console.error(err);
        body.textContent = 'Failed to load logs';
      });
    }

    function toggleShift(employeeId, employeeName) {
      const row = document.getElementById(`employee-${employeeId}`);
      const hasOpen = row ? row.dataset.hasOpenShift === '1' : false;
      const shiftId = row ? (row.dataset.shiftId ? parseInt(row.dataset.shiftId, 10) : null) : null;

      if (hasOpen) {
        performClockOut(employeeId, shiftId, employeeName);
        return;
      }

      performClockIn(employeeId, employeeName, selectedBranch);
    }

    async function undoLastAction(employeeId, employeeName) {
      const action = lastActionByEmployee[String(employeeId)];
      if (!action) {
        showError(`No action to undo for ${employeeName}`);
        return;
      }

      try {
        if (action.type === 'clock_in') {
          const form = new FormData();
          form.append('employee_id', employeeId);
          form.append('action', 'undo_clock_in');
          form.append('shift_id', action.shiftId);

          const resp = await fetch('api/clock_in.php', {
            method: 'POST',
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: form
          });
          const text = await resp.text();
          let data = null;
          try { data = JSON.parse(text); } catch (e) { data = null; }
          if (!resp.ok || !data) throw new Error(data?.message || `Undo failed (HTTP ${resp.status})`);
          if (!data.success) throw new Error(data.message || 'Unable to undo');

          showSuccess(`${employeeName} time-in undone`);
          delete lastActionByEmployee[String(employeeId)];
          reloadEmployees();
          return;
        }

        if (action.type === 'clock_out') {
          const form = new FormData();
          form.append('employee_id', employeeId);
          form.append('action', 'undo_clock_out');
          form.append('shift_id', action.shiftId);

          const resp = await fetch('api/clock_out.php', {
            method: 'POST',
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: form
          });
          const text = await resp.text();
          let data = null;
          try { data = JSON.parse(text); } catch (e) { data = null; }
          if (!resp.ok || !data) throw new Error(data?.message || `Undo failed (HTTP ${resp.status})`);
          if (!data.success) throw new Error(data.message || 'Unable to undo');

          showSuccess(`${employeeName} time-out undone`);
          delete lastActionByEmployee[String(employeeId)];
          reloadEmployees();
          return;
        }

        if (action.type === 'transfer') {
          const oldBranch = action.oldBranch || '';
          if (!oldBranch) throw new Error('Unable to undo (missing previous branch)');

          const undoForm = new FormData();
          undoForm.append('action', 'undo_transfer');
          undoForm.append('employee_id', employeeId);
          undoForm.append('branch_name', oldBranch);

          const resp = await fetch('update_deployment.php', {
            method: 'POST',
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: undoForm
          });
          const undoText = await resp.text();
          let undoData = null;
          try { undoData = JSON.parse(undoText); } catch (e) { undoData = null; }
          if (!resp.ok || !undoData) throw new Error(undoData?.message || `Undo failed (HTTP ${resp.status})`);
          if (!undoData.success) throw new Error(undoData.message || 'Undo failed');

          showSuccess(`${employeeName} transfer undone (back to ${oldBranch})`);
          delete lastActionByEmployee[String(employeeId)];
          reloadEmployees();
          return;
        }

        throw new Error('Unknown action type');
      } catch (e) {
        console.error(e);
        showError(e.message || 'Undo failed');
      }
    }

    document.addEventListener('click', function(e) {
      const target = e.target;
      if (!(target instanceof Element)) return;
      if (target.closest('.kebab-menu')) return;
      document.querySelectorAll('.kebab-dropdown').forEach(el => {
        el.style.display = 'none';
      });
    });

    function performClockIn(employeeId, employeeName, branchName) {
      if (!branchName) {
        showError('Please select a branch first');
        return;
      }

      const formData = new FormData();
      formData.append('employee_id', employeeId);
      formData.append('branch_name', branchName);

      fetch('api/clock_in.php', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
      })
      .then(async (r) => {
        const text = await r.text();
        let data = null;
        try {
          data = JSON.parse(text);
        } catch (e) {
          data = null;
        }

        if (!r.ok) {
          const msg = data?.message || `Request failed (HTTP ${r.status})`;
          throw new Error(msg);
        }

        if (!data) {
          throw new Error('Invalid server response');
        }

        if (data.success) {
          showSuccess(`${employeeName} time-in recorded (${data.time_in || ''})`);
          if (data.shift_id) {
            lastActionByEmployee[String(employeeId)] = {
              type: 'clock_in',
              shiftId: data.shift_id
            };
          }
          const row = document.getElementById(`employee-${employeeId}`);
          if (row) {
            const timeCell = row.querySelector('.time-in-cell');
            if (timeCell) timeCell.textContent = formatTime(data.time_in);

            if (data.shift_id) {
              row.dataset.shiftId = String(data.shift_id);
            }
            row.dataset.hasOpenShift = '1';

            const btn = row.querySelector('.btn-shift-toggle');
            if (btn) {
              btn.classList.remove('btn-present');
              btn.classList.add('btn-present-late');
              btn.innerHTML = '<i class="fas fa-sign-out-alt"></i> Time Out';
              btn.title = 'Time Out';
            }
          }

          setTimeout(() => {
            reloadEmployees();
          }, 300);
          return;
        }

        throw new Error(data.message || 'Failed to Time In');
      })
      .catch(err => {
        console.error(err);
        showError(err.message || 'Failed to Time In');
      });
    }

    function performClockOut(employeeId, shiftId, employeeName) {
      const formData = new FormData();
      formData.append('employee_id', employeeId);
      if (shiftId) formData.append('shift_id', shiftId);

      fetch('api/clock_out.php', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
      })
      .then(async (r) => {
        const text = await r.text();
        let data = null;
        try {
          data = JSON.parse(text);
        } catch (e) {
          data = null;
        }

        if (!r.ok) {
          const msg = data?.message || `Request failed (HTTP ${r.status})`;
          throw new Error(msg);
        }

        if (!data) {
          throw new Error('Invalid server response');
        }

        if (data.success) {
          showSuccess(`${employeeName} time-out recorded (${data.time_out || ''})`);
          if (shiftId) {
            lastActionByEmployee[String(employeeId)] = {
              type: 'clock_out',
              shiftId: shiftId
            };
          }
          const row = document.getElementById(`employee-${employeeId}`);
          if (row) {
            const timeCell = row.querySelector('.time-out-cell');
            if (timeCell) timeCell.textContent = formatTime(data.time_out);

            row.dataset.hasOpenShift = '0';
            row.dataset.shiftId = '';

            const btn = row.querySelector('.btn-shift-toggle');
            if (btn) {
              btn.classList.remove('btn-present-late');
              btn.classList.add('btn-present');
              btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Time In';
              btn.title = 'Time In';
            }
          }

          setTimeout(() => {
            reloadEmployees();
          }, 300);
          return;
        }

        throw new Error(data.message || 'Failed to Time Out');
      })
      .catch(err => {
        console.error(err);
        showError(err.message || 'Failed to Time Out');
      });
    }

    function showTransferDropdown(employeeId, employeeName, currentBranch) {
  // Remove any existing dropdown/modal
  let existing = document.getElementById('transferModal');
  if (existing) existing.remove();

  // Assume branches are available globally via window.allBranches or fetch them via AJAX if needed
  let branches = window.allBranches || [];
  if (!branches.length) {
    // Try to get from DOM if not set (PHP can render a JS array)
    if (window.branchesFromPHP) branches = window.branchesFromPHP;
  }
  // Filter out current branch
  const options = branches.filter(b => b.branch_name !== currentBranch);
  if (!options.length) {
    showError('No other branches available for transfer');
    return;
  }

  // Build modal
  const modal = document.createElement('div');
  modal.id = 'transferModal';
  modal.style.position = 'fixed';
  modal.style.top = '0';
  modal.style.left = '0';
  modal.style.width = '100vw';
  modal.style.height = '100vh';
  modal.style.background = 'rgba(0,0,0,0.3)';
  modal.style.display = 'flex';
  modal.style.alignItems = 'center';
  modal.style.justifyContent = 'center';
  modal.style.zIndex = '2147483647';
  modal.style.pointerEvents = 'auto';

  modal.innerHTML = `
    <div style="background: #222; padding: 24px 32px; border-radius: 12px; box-shadow: 0 2px 32px #000; min-width: 320px; max-width: 96vw;">
      <h3 style="color: #FFD700; font-size: 18px; margin-bottom: 16px;">Transfer ${employeeName}</h3>
      <div style="margin-bottom: 16px;">
        <label for="transferBranchSelect" style="color: #fff; font-size: 14px;">Select branch:</label>
        <select id="transferBranchSelect" style="width: 100%; padding: 8px; margin-top: 6px; border-radius: 6px;">
          ${options.map(b => `<option value="${b.branch_name}">${b.branch_name}</option>`).join('')}
        </select>
      </div>
      <div style="display: flex; gap: 8px; justify-content: flex-end;">
        <button id="cancelTransferBtn" style="background: #444; color: #fff; border: none; padding: 8px 16px; border-radius: 6px;">Cancel</button>
        <button id="confirmTransferBtn" style="background: #FFD700; color: #222; border: none; padding: 8px 16px; border-radius: 6px; font-weight: bold;">Transfer</button>
      </div>
    </div>
  `;
  document.body.appendChild(modal);
  setTimeout(() => {
    const select = document.getElementById('transferBranchSelect');
    if (select) select.focus();
  }, 20);

  document.getElementById('cancelTransferBtn').onclick = () => modal.remove();
  document.getElementById('confirmTransferBtn').onclick = () => {
    const branchName = document.getElementById('transferBranchSelect').value;
    modal.remove();
    transferEmployee(employeeId, employeeName, branchName);
  };
}

    function transferEmployee(employeeId, employeeName, toBranch) {
      if (!toBranch) {
        showError('Please select a branch to transfer');
        return;
      }

      const employeeElement = document.getElementById(`employee-${employeeId}`);
      if (employeeElement) {
        employeeElement.style.transition = 'all 0.3s ease';
        employeeElement.style.boxShadow = '0 0 20px rgba(255, 208, 0, 0.6)';
        employeeElement.style.transform = 'scale(1.02)';
      }

      const formData = new FormData();
      formData.append('employee_id', employeeId);
      formData.append('branch_name', toBranch);

      fetch('update_deployment.php', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
      })
      .then(async (r) => {
        const text = await r.text();
        let data = null;
        try {
          data = JSON.parse(text);
        } catch (e) {
          data = null;
        }

        if (!r.ok) {
          const msg = data?.message || `Request failed (HTTP ${r.status})`;
          throw new Error(msg);
        }

        if (!data) {
          throw new Error('Invalid server response');
        }

        if (data.success) {
          const oldBranch = data.old_branch || '';
          const newBranch = data.new_branch || toBranch;
          showSuccess(`${employeeName} transferred to ${newBranch}`);

          lastActionByEmployee[String(employeeId)] = {
            type: 'transfer',
            oldBranch: oldBranch,
            newBranch: newBranch
          };
          showUndoSnackbar(`Last action saved for undo: transferred ${employeeName} to ${newBranch}`);

          setTimeout(() => {
            reloadEmployees();
          }, 300);
          return;
        }

        throw new Error(data.message || 'Failed to transfer employee');
      })
      .catch(error => {
        console.error('Error:', error);
        if (employeeElement) {
          employeeElement.style.boxShadow = '';
          employeeElement.style.transform = '';
        }
        showError(error.message || 'Failed to transfer employee');
      });
    }

    function showSuccess(message) {
      const el = document.getElementById('successMessage');
      el.textContent = message;
      el.style.display = 'block';
      document.getElementById('errorMessage').style.display = 'none';
      setTimeout(() => el.style.display = 'none', 5000);
    }

    function showError(message) {
      const errorMessage = document.getElementById('errorMessage');
      errorMessage.textContent = message;
      errorMessage.style.display = 'block';
      document.getElementById('successMessage').style.display = 'none';
      setTimeout(() => {
        errorMessage.style.display = 'none';
      }, 4000);
    }

    let undoSnackbarTimer = null;
    let undoSnackbarHandler = null;

    function hideUndoSnackbar() {
      const el = document.getElementById('undoSnackbar');
      if (!el) return;
      el.style.display = 'none';
      if (undoSnackbarTimer) {
        clearTimeout(undoSnackbarTimer);
        undoSnackbarTimer = null;
      }
      undoSnackbarHandler = null;
    }

    function showUndoSnackbar(message, onUndo, timeoutMs = 0) {
      const el = document.getElementById('undoSnackbar');
      const textEl = document.getElementById('undoSnackbarText');
      const closeBtn = document.getElementById('undoSnackbarClose');
      if (!el || !textEl || !closeBtn) return;

      if (undoSnackbarTimer) {
        clearTimeout(undoSnackbarTimer);
        undoSnackbarTimer = null;
      }

      undoSnackbarHandler = typeof onUndo === 'function' ? onUndo : null;
      textEl.textContent = message;
      el.style.display = 'flex';

      closeBtn.onclick = () => hideUndoSnackbar();

      if (timeoutMs && timeoutMs > 0) {
        undoSnackbarTimer = setTimeout(() => {
          hideUndoSnackbar();
        }, timeoutMs);
      }
    }

    // Auto-refresh every minute to check cutoff time (Philippine Time)
    setInterval(() => {
      const now = new Date();
      
      // Convert to Philippine Time in JavaScript (UTC+8)
      const utc = now.getTime() + (now.getTimezoneOffset() * 60000);
      const phTime = new Date(utc + (8 * 3600000)); // UTC+8
      
      const hours = phTime.getHours().toString().padStart(2, '0');
      const minutes = phTime.getMinutes().toString().padStart(2, '0');
      currentTime = `${hours}:${minutes}`;
      
      // Check if we just passed cutoff time
      const wasBeforeCutoff = isBeforeCutoff;
      isBeforeCutoff = currentTime < cutoffTime;
      
      if (wasBeforeCutoff && !isBeforeCutoff && selectedBranch) {
        // We just passed cutoff time, reload employees
        reloadEmployees();
        
        // Update time alert
        updateTimeDisplay();
      }
    }, 60000); // Check every minute

    // ===== BRANCH MANAGEMENT FUNCTIONS (INTEGRATED) =====
    
    const isAdminUser = !!document.getElementById('addBranchBtn');
    
    // DEBUG: Add Branch button found, attaching click handler
    if (isAdminUser && document.getElementById('addBranchBtn')) {
        console.log('DEBUG: Add Branch button found, attaching click handler');
        document.getElementById('addBranchBtn').addEventListener('click', function() {
            console.log('DEBUG: Add Branch button clicked');
            document.getElementById('addBranchModal').classList.add('show');
            document.getElementById('branchNameInput').focus();
        });
    } else {
        console.log('DEBUG: Add Branch button NOT found or isAdminUser is false');
    }

    function closeAddBranchModal() {
        document.getElementById('addBranchModal').classList.remove('show');
        document.getElementById('addBranchForm').reset();
        clearBranchMessage();
    }

    document.getElementById('addBranchModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeAddBranchModal();
        }
    });

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

        const submitBtn = document.querySelector('#addBranchForm button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';

        const formData = new FormData();
        formData.append('branch_action', 'add_branch');
        formData.append('branch_name', branchName);

        fetch(window.location.pathname, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(async (response) => {
            const text = await response.text();
            let data = null;
            try {
                data = JSON.parse(text);
            } catch (e) {
                data = null;
            }

            if (!response.ok) {
                throw new Error(data?.message || `Request failed (HTTP ${response.status})`);
            }

            if (!data) {
                const snippet = (text || '').trim().slice(0, 200);
                throw new Error(snippet ? `Non-JSON response: ${snippet}` : 'Empty server response');
            }

            return data;
        })
        .then(data => {
            if (data.success) {
                showBranchMessage('Branch added successfully!', 'success');
                document.getElementById('addBranchForm').reset();
                addBranchCardToUI(data.branch_id, data.branch_name);

                if (data.branch_id) {
                    const addedBranchId = data.branch_id;
                    const addedBranchName = data.branch_name || branchName;
                    showUndoSnackbar(`Branch added: ${addedBranchName}`, async () => {
                        const undoForm = new FormData();
                        undoForm.append('branch_action', 'delete_branch');
                        undoForm.append('branch_id', addedBranchId);

                        const resp = await fetch(window.location.pathname, {
                            method: 'POST',
                            body: undoForm
                        });
                        const undoData = await resp.json();
                        if (!undoData || !undoData.success) {
                            throw new Error(undoData?.message || 'Undo failed');
                        }

                        const card = document.querySelector(`[data-branch-id="${addedBranchId}"]`);
                        if (card) {
                            card.remove();
                        }
                        showSuccess('Branch addition undone');
                    }, 5000);
                }

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

    function addBranchCardToUI(branchId, branchName) {
        const branchGrid = document.getElementById('branchGrid');
        
        const branchCard = document.createElement('div');
        branchCard.className = 'branch-card';
        branchCard.setAttribute('data-branch-id', branchId);
        branchCard.setAttribute('data-branch', branchName);
        branchCard.innerHTML = `
            ${isAdminUser ? `<button class="btn-remove-branch" onclick="removeBranch(event, ${branchId}, '${branchName.replace(/'/g, "\\'")}')" title="Delete branch">
                <i class="fas fa-times"></i>
            </button>` : ''}
            <div class="branch-name">${branchName}</div>
            <div class="branch-desc">Deploy employees to this branch</div>
        `;
        
        branchGrid.appendChild(branchCard);
        
        branchCard.addEventListener('click', function() {
            selectBranch(this);
        });
    }

    function removeBranch(e, branchId, branchName) {
        if (e && typeof e.stopPropagation === 'function') {
            e.stopPropagation();
        }
        
        if (!confirm(`Are you sure you want to delete the branch "${branchName}"?`)) {
            return;
        }

        const formData = new FormData();
        formData.append('branch_action', 'delete_branch');
        formData.append('branch_id', branchId);

        const branchCard = document.querySelector(`[data-branch-id="${branchId}"]`);
        const removeBtn = branchCard.querySelector('.btn-remove-branch');
        const originalContent = removeBtn.innerHTML;
        removeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        removeBtn.disabled = true;

        fetch(window.location.pathname, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(async (response) => {
            const text = await response.text();
            let data = null;
            try {
                data = JSON.parse(text);
            } catch (e) {
                data = null;
            }

            if (!response.ok) {
                throw new Error(data?.message || `Request failed (HTTP ${response.status})`);
            }

            if (!data) {
                const snippet = (text || '').trim().slice(0, 200);
                throw new Error(snippet ? `Non-JSON response: ${snippet}` : 'Empty server response');
            }

            return data;
        })
        .then(data => {
            if (data.success) {
                branchCard.style.transition = 'all 0.3s ease';
                branchCard.style.opacity = '0';
                branchCard.style.transform = 'scale(0.9)';
                
                setTimeout(() => {
                    branchCard.remove();
                    showGlobalMessage(data.message, 'success');
                    
                    if (selectedBranch === branchName) {
                        selectedBranch = null;
                        document.getElementById('employeeContainer').innerHTML = `
                            <div class="no-employees">
                                <i class="fas fa-users" style="font-size: 36px; color: #444; margin-bottom: 10px;"></i>
                                <div>Branch deleted. Please select another deployment branch</div>
                            </div>
                        `;
                        hidePagination();
                    }
                }, 300);

                showUndoSnackbar(`Branch deleted: ${branchName}`, async () => {
                    const undoForm = new FormData();
                    undoForm.append('branch_action', 'undo_delete_branch');
                    undoForm.append('branch_id', branchId);
                    undoForm.append('branch_name', branchName);

                    const resp = await fetch(window.location.pathname, {
                        method: 'POST',
                        body: undoForm
                    });
                    const undoData = await resp.json();
                    if (!undoData || !undoData.success) {
                        throw new Error(undoData?.message || 'Undo failed');
                    }
                    addBranchCardToUI(undoData.branch_id || branchId, undoData.branch_name || branchName);
                    showSuccess('Branch deletion undone');
                }, 5000);
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
            showGlobalMessage(error?.message || 'Failed to delete branch', 'error');
        });
    }

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

    function clearBranchMessage() {
        const messageEl = document.getElementById('branchMessage');
        if (messageEl) {
            messageEl.className = '';
            messageEl.textContent = '';
        }
    }

    function showGlobalMessage(message, type) {
        if (type === 'success') {
            showSuccess(message);
        } else {
            showError(message);
        }
    }

    function selectBranch(cardElement) {
        document.querySelectorAll('.branch-card').forEach(c => c.classList.remove('selected'));
        cardElement.classList.add('selected');
        selectedBranch = cardElement.dataset.branch;
        document.getElementById('searchInput').disabled = false;
        // Reset to page 1 when selecting a branch
        currentPage = 1;
        reloadEmployees();
    }

    // Attach click handlers to initial branch cards
    document.querySelectorAll('.branch-card').forEach(card => {
        card.addEventListener('click', function() {
            selectBranch(this);
        });
    });

    // DEBUG: Show debug info with keyboard shortcut
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.shiftKey && e.key === 'D') {
            document.getElementById('debugInfo').style.display = document.getElementById('debugInfo').style.display === 'none' ? 'block' : 'none';
        }
    });