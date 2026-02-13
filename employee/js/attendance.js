// ===== EMPLOYEE ATTENDANCE MANAGEMENT SCRIPT =====

    // Global variables
    let selectedBranch = null;
    let currentStatusFilter = 'available'; // 'all', 'present', 'absent', or 'available'
    let currentView = 'list';
    let currentEmployees = [];
    let currentSearchTerm = '';
    let searchDebounceTimer = null;
    const initialAttendanceConfig = (window.attendanceConfig && typeof window.attendanceConfig === 'object')
      ? window.attendanceConfig
      : {};
    let isBeforeCutoff = !!initialAttendanceConfig.isBeforeCutoff;
    let cutoffTime = String(initialAttendanceConfig.cutoffTime || '09:00');
    let currentTime = String(initialAttendanceConfig.currentTime || '');

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
    let lastGlobalAction = null;

    // ... existing pagination code ...

    // Update global undo button visibility
    function updateGlobalUndoUI() {
        const container = document.getElementById('globalUndoContainer');
        const btn = document.getElementById('btnGlobalUndo');
        if (!container || !btn) return;

        container.style.display = 'flex';

        if (lastGlobalAction) {
            btn.disabled = false;
            const actionLabel = lastGlobalAction.type.replace('_', ' ');
            btn.title = `Undo ${actionLabel} for ${lastGlobalAction.employeeName}`;
        } else {
            btn.disabled = true;
            btn.title = 'Nothing to undo';
        }
    }

    // Hook up global undo button
    document.addEventListener('DOMContentLoaded', () => {
        const globalUndoBtn = document.getElementById('btnGlobalUndo');
        if (globalUndoBtn) {
            globalUndoBtn.addEventListener('click', () => {
                if (lastGlobalAction) {
                    undoLastAction(lastGlobalAction.employeeId, lastGlobalAction.employeeName);
                }
            });
        }

        updateGlobalUndoUI();
    });

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

    // Status filter (inline buttons or fallback dropdown)
    const statusButtonsWrap = document.getElementById('statusFilterButtons');
    if (statusButtonsWrap) {
        statusButtonsWrap.querySelectorAll('[data-status]').forEach(btn => {
            btn.addEventListener('click', () => {
                const next = String(btn.getAttribute('data-status') || '').trim();
                if (!next) return;
                currentStatusFilter = next;

                statusButtonsWrap.querySelectorAll('.status-pill').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                currentPage = 1;
                reloadEmployees();
            });
        });
    } else {
        const statusSelect = document.getElementById('statusFilter');
        if (statusSelect) {
            statusSelect.addEventListener('change', function() {
                currentStatusFilter = this.value;
                // Reset to page 1 when filter changes
                currentPage = 1;
                // If searching, ignore filter and reload search results; otherwise reload branch
                reloadEmployees();
            });
        }
    }

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
      if (!selectedBranch) return;
      loadEmployees(selectedBranch, currentPage, perPage, currentStatusFilter, currentSearchTerm);
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

          updateBranchStats(data.branch_summary);
          
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
          updateBranchStats(null);
        }
      })
      .catch(error => {
        console.error('DEBUG: Fetch error:', error);
        container.innerHTML = '<div class="no-employees"><i class="fas fa-exclamation-triangle" style="font-size: 36px; color: #dc2626; margin-bottom: 10px;"></i><div>Failed to load employees</div><div style="font-size: 11px; margin-top: 10px; color: #888;">Check browser console (F12) for details</div><div style="font-size: 11px; margin-top: 5px; color: #888;">Error: ' + error.message + '</div></div>';
        hidePagination();
        updateBranchStats(null);
      })
      .finally(() => {
        isLoading = false;
        showPaginationLoading(false);
      });
    }

    function updateBranchStats(summary) {
      const totalEl = document.getElementById('statTotalWorkers');
      const presentEl = document.getElementById('statPresent');
      const absentEl = document.getElementById('statAbsent');
      const presentListEl = document.getElementById('statPresentList');
      const absentListEl = document.getElementById('statAbsentList');

      // DEBUG: Log what we're receiving
      console.log('DEBUG updateBranchStats:', summary);
      console.log('DEBUG present_names:', summary?.present_names);
      console.log('DEBUG absent_names:', summary?.absent_names);
      console.log('DEBUG DOM elements:', { presentListEl, absentListEl });

      if (!totalEl || !presentEl || !absentEl) return;

      if (!summary) {
        totalEl.textContent = '--';
        presentEl.textContent = '--';
        absentEl.textContent = '--';
        if (presentListEl) presentListEl.innerHTML = '';
        if (absentListEl) absentListEl.innerHTML = '';
        return;
      }

      totalEl.textContent = String(summary.total_workers ?? 0);
      presentEl.textContent = String(summary.present ?? 0);
      absentEl.textContent = String(summary.absent ?? 0);

      const getEmployeeDisplayName = (emp) => {
        if (!emp) return '';
        if (emp.name) return String(emp.name);
        const first = emp.first_name ? String(emp.first_name) : '';
        const middle = emp.middle_name ? String(emp.middle_name) : '';
        const last = emp.last_name ? String(emp.last_name) : '';
        return `${first} ${middle} ${last}`.replace(/\s+/g, ' ').trim();
      };

      const presentNamesFromSummary = Array.isArray(summary.present_names) ? summary.present_names : null;
      const absentNamesFromSummary = Array.isArray(summary.absent_names) ? summary.absent_names : null;

      const presentEmployees = presentNamesFromSummary && presentNamesFromSummary.length ? presentNamesFromSummary.map(n => ({ name: n })) : Array.isArray(currentEmployees) ? currentEmployees.filter(e => (e && e.time_in && !e.time_out) || (e && e.attendance_status === 'Present')) : [];
      const absentEmployees = absentNamesFromSummary && absentNamesFromSummary.length ? absentNamesFromSummary.map(n => ({ name: n })) : Array.isArray(currentEmployees) ? currentEmployees.filter(e => e && e.attendance_status === 'Absent') : [];

      const renderNameList = (el, list, totalCount) => {
        if (!el) return;
        if (!Array.isArray(list) || list.length === 0) {
          el.innerHTML = '';
          return;
        }

        const max = 5;
        const allNames = list
          .map(getEmployeeDisplayName)
          .filter(Boolean);

        const isExpanded = String(el.dataset.expanded || '0') === '1';
        const visibleNames = isExpanded ? allNames : allNames.slice(0, max);

        const extra = Math.max(0, allNames.length - visibleNames.length);

        const rows = visibleNames.map((n, idx) => `<div class="stat-list-item">${idx + 1}. ${escapeHtml(n)}</div>`);
        if (extra > 0) {
          rows.push(`<button type="button" class="stat-list-more" data-action="expand">and ${extra} more</button>`);
        } else if (isExpanded && allNames.length > max) {
          rows.push(`<button type="button" class="stat-list-more" data-action="collapse">show less</button>`);
        }

        el.innerHTML = rows.join('');

        const moreBtn = el.querySelector('.stat-list-more[data-action]');
        if (moreBtn) {
          moreBtn.addEventListener('click', (ev) => {
            ev.preventDefault();
            const action = String(moreBtn.getAttribute('data-action') || '');
            if (action === 'expand') el.dataset.expanded = '1';
            if (action === 'collapse') el.dataset.expanded = '0';
            renderNameList(el, list, totalCount);
          }, { once: true });
        }
      };

      renderNameList(presentListEl, presentEmployees, summary.present);
      renderNameList(absentListEl, absentEmployees, summary.absent);
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
      // Filter out "Absent" employees when viewing "Available" list
      if (currentStatusFilter === 'available') {
          employees = employees.filter(emp => {
          // Exclude employees marked as "Absent"
          return emp.attendance_status !== 'Absent';
        });
      }
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

      const showNotesColumn = currentStatusFilter === 'absent';
      const isSummaryView = currentStatusFilter === 'all';

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
                ${showNotesColumn ? '<th>Notes</th>' : ''}
                <th>${isSummaryView ? 'Remarks' : 'Actions'}</th>
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
        const hasAttendanceToday = !!employee.has_attendance_today;
        const isAbsent = employee.attendance_status === 'Absent';

        const menuId = `emp-menu-${employee.id}`;

        const absentNotes = employee.absent_notes || '';

        html += `
          <tr id="employee-${employee.id}" data-shift-id="${employee.shift_id || ''}" data-has-open-shift="${hasOpenShift ? '1' : '0'}">
            <td class="mono" style="font-weight: bold; color: #FFD700;">${index + 1}</td>
            <td>
              <div class="employee-cell">
                <div class="employee-avatar" aria-hidden="true">${escapeAttr(initials)}</div>
                <div class="employee-meta">
                  <div class="employee-name">${escapeAttr(name)}</div>
                  <div class="employee-sub employee-branch">
                    <span class="employee-branch-label">Current Project:</span>
                    <span class="employee-branch-value">${escapeAttr(employee.logged_branch || '--')}</span>
                  </div>
                </div>
              </div>
            </td>
            <td class="mono time-in-cell">${escapeAttr(timeIn)}</td>
            <td class="mono time-out-cell">${escapeAttr(timeOut)}</td>
            <td class="mono">${escapeAttr(totalHours)}</td>
            ${showNotesColumn ? `<td>
              <button class="kebab-item" style="background: transparent; border: none; padding: 0; color: #FFD700; cursor: pointer; text-align: left;" onclick="showAbsentNotesModal(${employee.id}, '${escapeJsString(name)}', '${escapeJsString(absentNotes)}')" title="Add/Edit notes">
                ${escapeAttr(absentNotes || 'Add notes')}
              </button>
            </td>` : ''}
            <td>
              ${isSummaryView ? (() => {
                let remarkClass = 'remark-badge';
                let remarkText = '';
                let remarkIcon = '';
                
                if (isAbsent) {
                  remarkClass += ' remark-absent';
                  remarkText = 'Absent';
                  remarkIcon = 'fa-user-times';
                } else if (hasOpenShift) {
                  remarkClass += ' remark-present';
                  remarkText = 'Present';
                  remarkIcon = 'fa-check-circle';
                } else if (timeOut !== '--') {
                  remarkClass += ' remark-timeout';
                  remarkText = 'Time Out';
                  remarkIcon = 'fa-sign-out-alt';
                } else {
                  remarkClass += ' remark-available';
                  remarkText = 'Available';
                  remarkIcon = 'fa-clock';
                }
                
                return `<span class="${remarkClass}"><i class="fas ${remarkIcon}"></i> ${remarkText}</span>`;
              })() : `
              <div class="actions-cell">
                ${!hasAttendanceToday ? `
                <button class="btn-absent"
                        onclick="markAbsent(${employee.id}, '${escapeJsString(name)}')"
                        title="Mark Absent">
                  <i class="fas fa-user-times"></i> Mark Absent
                </button>
                ` : ''}
                <button class="${hasOpenShift ? 'btn-present-late' : 'btn-present'} btn-shift-toggle"
                        onclick="toggleShift(${employee.id}, '${escapeJsString(name)}')"
                        title="${isAbsent ? 'Cannot Time In/Out: Absent' : (hasOpenShift ? 'Time Out' : 'Time In')}"
                        ${isAbsent ? 'disabled' : ''}>
                  <i class="fas ${isAbsent ? 'fa-user-times' : (hasOpenShift ? 'fa-sign-out-alt' : 'fa-sign-in-alt')}"></i> ${isAbsent ? 'Absent' : (hasOpenShift ? 'Time Out' : 'Time In')}
                </button>
                <div class="kebab-menu">
                  <button class="kebab-btn" onclick="toggleEmployeeMenu('${menuId}', ${employee.id})" aria-label="Options">
                    <i class="fas fa-ellipsis-v"></i>
                  </button>
                  <div class="kebab-dropdown" id="${menuId}" style="display: none;">
                    <button class="kebab-item" onclick="openTimeLogsModal(${employee.id}, '${escapeJsString(name)}')">
                      <i class="fas fa-clock"></i> Time Logs Today
                    </button>
                    <button class="kebab-item" onclick="showOvertimeModal(${employee.id}, '${escapeJsString(name)}', '${escapeJsString(employee.total_ot_hrs || '')}')">
                      <i class="fas fa-hourglass-half"></i> Overtime
                    </button>
                    <button class="kebab-item" onclick="showTransferDropdown(${employee.id}, '${escapeJsString(name)}', '${escapeJsString(employee.logged_branch)}')">
                      <i class="fas fa-exchange-alt"></i> Transfer
                    </button>
                  </div>
                </div>
              </div>
              `}
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

    async function markAbsent(employeeId, employeeName) {
      if (!selectedBranch) {
        showError('Please select a project first');
        return;
      }

      const ok = confirm(`Mark ${employeeName} as Absent?`);
      if (!ok) return;

      try {
    // Save absent action to lastActionByEmployee BEFORE attempting to mark absent
    const actionObj = {
      type: 'absent',
      employeeId: employeeId,
      employeeName: employeeName,
      timestamp: new Date().toISOString()
    };
    lastActionByEmployee[String(employeeId)] = actionObj;
    lastGlobalAction = actionObj;
    updateGlobalUndoUI();

    await saveAbsentNotes(employeeId, '');
    
    // Show undo option
    showUndoSnackbar(`${employeeName} marked as absent`, async () => {
      await undoLastAction(employeeId, employeeName);
    }, 5000);
    
  } catch (e) {
    // Remove from lastActionByEmployee if failed
    delete lastActionByEmployee[String(employeeId)];
    throw e;
  }
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
    // Handle clock_in undo
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
      try { 
        data = JSON.parse(text); 
      } catch (e) { 
        console.error('Failed to parse response:', text);
        data = null; 
      }
      
      if (!resp.ok || !data) {
        throw new Error(data?.message || `Undo failed (HTTP ${resp.status})`);
      }
      
      if (!data.success) {
        throw new Error(data.message || 'Unable to undo');
      }

      showSuccess(`${employeeName} time-in undone`);
      delete lastActionByEmployee[String(employeeId)];
      reloadEmployees();
      return;
    }

    // Handle clock_out undo
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
      try { 
        data = JSON.parse(text); 
      } catch (e) { 
        console.error('Failed to parse response:', text);
        data = null; 
      }
      
      if (!resp.ok || !data) {
        throw new Error(data?.message || `Undo failed (HTTP ${resp.status})`);
      }
      
      if (!data.success) {
        throw new Error(data.message || 'Unable to undo');
      }

      showSuccess(`${employeeName} time-out undone`);
      delete lastActionByEmployee[String(employeeId)];
      reloadEmployees();
      return;
    }

    // Handle transfer undo
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
      try { 
        undoData = JSON.parse(undoText); 
      } catch (e) { 
        console.error('Failed to parse response:', undoText);
        undoData = null; 
      }
      
      if (!resp.ok || !undoData) {
        throw new Error(undoData?.message || `Undo failed (HTTP ${resp.status})`);
      }
      
      if (!undoData.success) {
        throw new Error(undoData.message || 'Undo failed');
      }

      showSuccess(`${employeeName} transfer undone (back to ${oldBranch})`);
      delete lastActionByEmployee[String(employeeId)];
      reloadEmployees();
      return;
    }

    // Handle absent undo
    if (action.type === 'absent') {

      // To undo absent, we need to mark the employee as present
      // You'll need to call your API endpoint that undoes absent marking
      const formData = new FormData();
      formData.append('action', 'undo_absent');
      formData.append('employee_id', employeeId);
      formData.append('branch', selectedBranch);

      const resp = await fetch('select_employee.php', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
      });
      
      const text = await resp.text();
      let data = null;
      try { 
        data = JSON.parse(text); 
      } catch (e) { 
        console.error('Failed to parse response:', text);
        data = null; 
      }
      
      if (!resp.ok || !data) {
        throw new Error(data?.message || `Undo absent failed (HTTP ${resp.status})`);
      }
      
      if (!data.success) {
        throw new Error(data.message || 'Unable to undo absent');
      }

      showSuccess(`${employeeName} absent status undone`);
      delete lastActionByEmployee[String(employeeId)];
      if (lastGlobalAction && lastGlobalAction.type === 'absent' && String(lastGlobalAction.employeeId) === String(employeeId)) {
        lastGlobalAction = null;
        updateGlobalUndoUI();
      }
      reloadEmployees();
      return;
    }

    throw new Error('Unknown action type');
  } catch (e) {
    console.error('Undo error:', e);
    showError(e.message || 'Undo failed');
  }
}

// Add this debugging function
function debugUndo() {
  console.log('lastActionByEmployee:', lastActionByEmployee);
  console.log('Current selectedBranch:', selectedBranch);
  
  // Check if API endpoint exists
  fetch('api/clock_in.php', { method: 'HEAD' })
    .then(response => {
      console.log('clock_in.php exists:', response.ok);
    })
    .catch(err => {
      console.error('clock_in.php not found:', err);
    });
}

// Call it when undo fails
// debugUndo();

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
        showError('Please select a project first');
        return;
      }

      // Confirm before proceeding with Time In
      const ok = confirm(`Confirm Time In for ${employeeName}?`);
      if (!ok) return;

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
          if (data.auto_transferred) {
            const fromBranch = data.from_branch || '--';
            const toBranch = data.to_branch || branchName;
            showSuccess(`${employeeName} time-in recorded (${data.time_in || ''}) — auto-transferred from ${fromBranch} to ${toBranch}`);
          } else {
            showSuccess(`${employeeName} time-in recorded (${data.time_in || ''})`);
          }
          if (data.shift_id) {
            const actionObj = {
              type: 'clock_in',
              shiftId: data.shift_id,
              employeeId: employeeId,
              employeeName: employeeName
            };
            lastActionByEmployee[String(employeeId)] = actionObj;
            lastGlobalAction = actionObj;
            updateGlobalUndoUI();
          }
          reloadEmployees();
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
            const actionObj = {
              type: 'clock_out',
              shiftId: shiftId,
              employeeId: employeeId,
              employeeName: employeeName
            };
            lastActionByEmployee[String(employeeId)] = actionObj;
            lastGlobalAction = actionObj;
            updateGlobalUndoUI();
          }
          reloadEmployees();
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
        showError('No other projects available for transfer');
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
            <label for="transferBranchSelect" style="color: #fff; font-size: 14px;">Select project:</label>
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

    function showAbsentNotesModal(employeeId, employeeName, currentNotes) {
      let existing = document.getElementById('absentNotesModal');
      if (existing) existing.remove();

      const modal = document.createElement('div');
      modal.id = 'absentNotesModal';
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

      const safeNotes = String(currentNotes ?? '');

      modal.innerHTML = `
        <div style="background: #222; padding: 24px 32px; border-radius: 12px; box-shadow: 0 2px 32px #000; min-width: 360px; max-width: 96vw;">
          <h3 style="color: #FFD700; font-size: 18px; margin-bottom: 16px;">Absent Notes — ${escapeHtml(employeeName)}</h3>
          <div style="margin-bottom: 16px;">
            <label for="absentNotesText" style="color: #fff; font-size: 14px;">Notes:</label>
            <textarea id="absentNotesText" style="width: 100%; padding: 10px; margin-top: 6px; border-radius: 6px; min-height: 120px; resize: vertical;">${escapeHtml(safeNotes)}</textarea>
          </div>
          <div style="display: flex; gap: 8px; justify-content: flex-end;">
            <button id="cancelAbsentNotesBtn" style="background: #444; color: #fff; border: none; padding: 8px 16px; border-radius: 6px;">Cancel</button>
            <button id="saveAbsentNotesBtn" style="background: #FFD700; color: #222; border: none; padding: 8px 16px; border-radius: 6px; font-weight: bold;">Save</button>
          </div>
        </div>
      `;

      document.body.appendChild(modal);

      const textarea = document.getElementById('absentNotesText');
      if (textarea) {
        setTimeout(() => textarea.focus(), 20);
      }

      document.getElementById('cancelAbsentNotesBtn').onclick = () => modal.remove();
      document.getElementById('saveAbsentNotesBtn').onclick = async () => {
        const notes = (document.getElementById('absentNotesText')?.value ?? '').trim();
        await saveAbsentNotes(employeeId, notes);
        modal.remove();
      };
    }

    async function saveAbsentNotes(employeeId, notes) {
      if (!selectedBranch) {
        showError('Please select a project first');
        return;
      }

      const formData = new FormData();
      formData.append('action', 'save_absent_notes');
      formData.append('employee_id', String(employeeId));
      formData.append('branch', String(selectedBranch));
      formData.append('notes', String(notes));

      try {
        const resp = await fetch('select_employee.php', {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: formData
        });
        const text = await resp.text();
        let data = null;
        try { data = JSON.parse(text); } catch (e) { data = null; }
        if (!resp.ok || !data) throw new Error(data?.message || `Request failed (HTTP ${resp.status})`);
        if (!data.success) throw new Error(data.message || 'Failed to save notes');

        showSuccess('Absent notes saved');
        reloadEmployees();
      } catch (e) {
        console.error(e);
        showError(e.message || 'Failed to save notes');
      }
    }

    function escapeHtml(s) {
      return String(s ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function showOvertimeModal(employeeId, employeeName, currentOvertime) {
      let existing = document.getElementById('overtimeModal');
      if (existing) existing.remove();

      const modal = document.createElement('div');
      modal.id = 'overtimeModal';
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

      const safeValue = String(currentOvertime ?? '');

      modal.innerHTML = `
        <div style="background: #222; padding: 24px 32px; border-radius: 12px; box-shadow: 0 2px 32px #000; min-width: 360px; max-width: 96vw;">
          <h3 style="color: #FFD700; font-size: 18px; margin-bottom: 16px;">Request Overtime — ${escapeHtml(employeeName)}</h3>
          <div style="margin-bottom: 16px;">
            <label for="overtimeInput" style="color: #fff; font-size: 14px;">Total overtime hours:</label>
            <input id="overtimeInput" type="text" value="${escapeHtml(safeValue)}" style="width: 100%; padding: 10px; margin-top: 6px; border-radius: 6px; color: #000;" placeholder="e.g. 2 or 2.5" />
          </div>
          <div style="margin-bottom: 16px;">
            <label for="overtimeReason" style="color: #fff; font-size: 14px;">Reason for overtime:</label>
            <textarea id="overtimeReason" style="width: 100%; padding: 10px; margin-top: 6px; border-radius: 6px; min-height: 80px; resize: vertical; color: #000;" placeholder="e.g. Project deadline, Urgent task..."></textarea>
          </div>
          <div style="display: flex; gap: 8px; justify-content: flex-end;">
            <button id="cancelOvertimeBtn" style="background: #444; color: #fff; border: none; padding: 8px 16px; border-radius: 6px;">Cancel</button>
            <button id="saveOvertimeBtn" style="background: #FFD700; color: #222; border: none; padding: 8px 16px; border-radius: 6px; font-weight: bold;">Request Overtime</button>
          </div>
        </div>
      `;

      document.body.appendChild(modal);

      const input = document.getElementById('overtimeInput');
      if (input) {
        setTimeout(() => input.focus(), 20);
      }

      document.getElementById('cancelOvertimeBtn').onclick = () => modal.remove();
      document.getElementById('saveOvertimeBtn').onclick = async () => {
        const totalOt = (document.getElementById('overtimeInput')?.value ?? '').trim();
        const reason = (document.getElementById('overtimeReason')?.value ?? '').trim();
        if (!reason) {
          alert('Please provide a reason for overtime');
          return;
        }
        await requestOvertime(employeeId, totalOt, reason);
        modal.remove();
      };
    }

    async function requestOvertime(employeeId, totalOtHrs, overtimeReason) {
      if (!selectedBranch) {
        showError('Please select a project first');
        return;
      }

      const formData = new FormData();
      formData.append('action', 'request_overtime');
      formData.append('employee_id', String(employeeId));
      formData.append('branch', String(selectedBranch));
      formData.append('total_ot_hrs', String(totalOtHrs));
      formData.append('overtime_reason', String(overtimeReason));

      try {
        const resp = await fetch('select_employee.php', {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: formData
        });
        const text = await resp.text();
        console.log('DEBUG: Raw response:', text);
        let data = null;
        try { data = JSON.parse(text); } catch (e) { console.error('DEBUG: JSON parse error:', e); console.error('DEBUG: Response text:', text); throw new Error('Invalid JSON response from server'); }
        if (!resp.ok || !data) throw new Error(data?.message || `Request failed (HTTP ${resp.status})`);
        if (!data || !data.success) throw new Error(data?.message || 'Failed to request overtime');

        showSuccess(data.message || 'Overtime request sent to Super Admin for approval');
        alert('✅ Overtime request has been successfully sent to Super Admin for approval!');
        reloadEmployees();
      } catch (e) {
        console.error(e);
        showError(e.message || 'Failed to request overtime');
      }
    }

    function transferEmployee(employeeId, employeeName, toBranch) {
      if (!toBranch) {
        showError('Please select a project to transfer');
        return;
      }

      const employeeElement = document.getElementById(`employee-${employeeId}`);
      if (employeeElement) {
        employeeElement.style.transition = 'all 0.3s ease';
        employeeElement.style.boxShadow = '0 0 20px rgba(255, 208, 0, 0.6)';
        employeeElement.style.transform = 'scale(1.02)';
      }

      const row = document.getElementById(`employee-${employeeId}`);
      const oldBranch = row ? row.querySelector('.employee-branch-value')?.textContent.trim() : '';

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
          const newBranch = data.new_branch || toBranch;
          showSuccess(`${employeeName} transferred to ${newBranch}`);

          const actionObj = {
            type: 'transfer',
            oldBranch: oldBranch,
            newBranch: newBranch,
            employeeId: employeeId,
            employeeName: employeeName
          };
          lastActionByEmployee[String(employeeId)] = actionObj;
          lastGlobalAction = actionObj;
          updateGlobalUndoUI();

          reloadEmployees();
          return;
        }

        throw new Error(data.message || 'Failed to Transfer');
      })
      .catch(err => {
        console.error(err);
        showError(err.message || 'Failed to Transfer');
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
    
    // Branch list rendering (search + pagination)
    let allBranches = Array.isArray(window.branchesFromPHP) ? [...window.branchesFromPHP] : [];
    window.allBranches = allBranches;

    let branchPage = 1;
    const branchPerPage = 6;
    let branchSearchTerm = '';

    function escapeHtml(s) {
      return String(s ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function getFilteredBranches() {
      const term = (branchSearchTerm || '').trim().toLowerCase();
      if (!term) return allBranches;
      return allBranches.filter(b => String(b.branch_name ?? '').toLowerCase().includes(term));
    }

    function renderBranchPager(totalItems, totalPages) {
      const pager = document.getElementById('branchPager');
      if (!pager) return;

      if (totalPages <= 1) {
        pager.innerHTML = '';
        return;
      }

      const maxButtons = 7;
      let start = Math.max(1, branchPage - 2);
      let end = Math.min(totalPages, branchPage + 2);

      // Expand range to meet maxButtons where possible
      while ((end - start + 1) < Math.min(maxButtons, totalPages)) {
        if (start > 1) start--;
        else if (end < totalPages) end++;
        else break;
      }

      let html = '';
      html += `<button class="page-btn" type="button" ${branchPage === 1 ? 'disabled' : ''} data-branch-page="${branchPage - 1}"><i class="fas fa-chevron-left"></i></button>`;
      html += `<button class="page-btn ${branchPage === 1 ? 'active' : ''}" type="button" data-branch-page="1">1</button>`;

      if (start > 2) {
        html += '<span class="page-dots">...</span>';
      }

      for (let p = Math.max(2, start); p <= Math.min(totalPages - 1, end); p++) {
        html += `<button class="page-btn ${branchPage === p ? 'active' : ''}" type="button" data-branch-page="${p}">${p}</button>`;
      }

      if (end < totalPages - 1) {
        html += '<span class="page-dots">...</span>';
      }

      if (totalPages > 1) {
        html += `<button class="page-btn ${branchPage === totalPages ? 'active' : ''}" type="button" data-branch-page="${totalPages}">${totalPages}</button>`;
      }
      html += `<button class="page-btn" type="button" ${branchPage === totalPages ? 'disabled' : ''} data-branch-page="${branchPage + 1}"><i class="fas fa-chevron-right"></i></button>`;

      pager.innerHTML = html;

      pager.querySelectorAll('[data-branch-page]').forEach(btn => {
        btn.addEventListener('click', () => {
          const next = parseInt(btn.getAttribute('data-branch-page') || '', 10);
          if (!Number.isFinite(next)) return;
          const clamped = Math.min(totalPages, Math.max(1, next));
          if (clamped === branchPage) return;
          branchPage = clamped;
          renderBranchGrid();
        });
      });
    }

    function renderBranchGrid() {
      const grid = document.getElementById('branchGrid');
      if (!grid) return;

      const filtered = getFilteredBranches();
      const totalItems = filtered.length;
      const totalPages = Math.max(1, Math.ceil(totalItems / branchPerPage));
      branchPage = Math.min(totalPages, Math.max(1, branchPage));

      const startIdx = (branchPage - 1) * branchPerPage;
      const pageItems = filtered.slice(startIdx, startIdx + branchPerPage);

      grid.innerHTML = pageItems.map(b => {
        const id = escapeHtml(b.id);
        const name = String(b.branch_name ?? '');
        const nameEsc = escapeHtml(name);
        const nameJs = name.replace(/'/g, "\\'");
        return `
          <div class="branch-card" data-branch-id="${id}" data-branch="${nameEsc}">
            ${isAdminUser ? `<button class="btn-remove-branch" onclick="removeBranch(event, ${id}, '${nameJs}')" title="Delete project"><i class="fas fa-times"></i></button>` : ''}
            <div class="branch-name">${nameEsc}</div>
            <div class="branch-desc">Deploy employees to this project for attendance</div>
          </div>
        `;
      }).join('');

      // Attach click handlers
      grid.querySelectorAll('.branch-card').forEach(card => {
        card.addEventListener('click', function() {
          selectBranch(this);
        });
      });

      // Preserve selection highlight if selectedBranch is visible on this page
      if (selectedBranch) {
        grid.querySelectorAll('.branch-card').forEach(card => {
          if (card.dataset.branch === selectedBranch) {
            card.classList.add('selected');
          }
        });
      }

      renderBranchPager(totalItems, totalPages);
    }

    // Hook up branch search input
    const branchSearchInput = document.getElementById('branchSearchInput');
    if (branchSearchInput) {
      branchSearchInput.addEventListener('input', () => {
        branchSearchTerm = branchSearchInput.value || '';
        branchPage = 1;
        renderBranchGrid();
      });
    }

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
        const modal = document.getElementById('addBranchModal');
        const form = document.getElementById('addBranchForm');
        if (modal) modal.classList.remove('show');
        if (form) form.reset();
        clearBranchMessage();
    }

    const addBranchModal = document.getElementById('addBranchModal');
    if (addBranchModal) {
        addBranchModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddBranchModal();
            }
        });
    }

    function submitAddBranch(event) {
        event.preventDefault();
        
        const branchName = document.getElementById('branchNameInput').value.trim();
        
        if (!branchName) {
            showBranchMessage('Project name is required', 'error');
            return;
        }

        if (branchName.length < 2) {
            showBranchMessage('Project name must be at least 2 characters', 'error');
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
                showBranchMessage('Project added successfully!', 'success');
                document.getElementById('addBranchForm').reset();
                addBranchCardToUI(data.branch_id, data.branch_name);

                if (data.branch_id) {
                    const addedBranchId = data.branch_id;
                    const addedBranchName = data.branch_name || branchName;
                    showUndoSnackbar(`Project added: ${addedBranchName}`, async () => {
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

                        allBranches = allBranches.filter(b => String(b.id) !== String(addedBranchId));
                        window.allBranches = allBranches;
                        renderBranchGrid();
                        showSuccess('Project addition undone');
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
            showBranchMessage('Failed to add project', 'error');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    }

    function addBranchCardToUI(branchId, branchName) {
        const idNum = parseInt(String(branchId), 10);
        const id = Number.isFinite(idNum) ? idNum : branchId;
        const name = String(branchName ?? '').trim();
        if (!name) return;

        // Avoid duplicates
        const exists = allBranches.some(b => String(b.id) === String(id));
        if (!exists) {
          allBranches.push({ id, branch_name: name });
          window.allBranches = allBranches;
        }
        renderBranchGrid();
    }

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

        const branchCard = document.querySelector(`[data-branch-id="${branchId}"]`);
        const removeBtn = branchCard ? branchCard.querySelector('.btn-remove-branch') : null;
        const originalContent = removeBtn ? removeBtn.innerHTML : '';
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
                if (branchCard) {
                  branchCard.style.transition = 'all 0.3s ease';
                  branchCard.style.opacity = '0';
                  branchCard.style.transform = 'scale(0.9)';
                }

                setTimeout(() => {
                    allBranches = allBranches.filter(b => String(b.id) !== String(branchId));
                    window.allBranches = allBranches;
                    renderBranchGrid();
                    showGlobalMessage(data.message, 'success');

                    if (selectedBranch === branchName) {
                        selectedBranch = null;
                        document.getElementById('employeeContainer').innerHTML = `
                            <div class="no-employees">
                                <i class="fas fa-users" style="font-size: 36px; color: #444; margin-bottom: 10px;"></i>
                                <div>Project deleted. Please select another deployment project</div>
                            </div>
                        `;
                        hidePagination();
                    }
                }, 300);

                showUndoSnackbar(`Project deleted: ${branchName}`, async () => {
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
                    showSuccess('Project deletion undone');
                }, 5000);
            } else {
                if (removeBtn) {
                  removeBtn.innerHTML = originalContent;
                  removeBtn.disabled = false;
                }
                showGlobalMessage(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (removeBtn) {
              removeBtn.innerHTML = originalContent;
              removeBtn.disabled = false;
            }
            showGlobalMessage(error?.message || 'Failed to delete project', 'error');
        })
        .finally(() => {
            if (removeBtn) {
              removeBtn.innerHTML = originalContent;
              removeBtn.disabled = false;
            }
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
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            renderBranchGrid();
        });
    } else {
        renderBranchGrid();
    }

    // DEBUG: Show debug info with keyboard shortcut
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.shiftKey && e.key === 'D') {
            document.getElementById('debugInfo').style.display = document.getElementById('debugInfo').style.display === 'none' ? 'block' : 'none';
        }
    });