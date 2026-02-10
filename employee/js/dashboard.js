// Tab switching
function switchTab(tabName, event) {
  // Hide all tabs
  document.querySelectorAll('.tab-content').forEach(tab => {
    tab.classList.remove('active');
  });
  
  document.querySelectorAll('.tab').forEach(tab => {
    tab.classList.remove('active');
  });
  
  // Show selected tab
  document.getElementById(tabName + '-tab').classList.add('active');
  
  // Activate tab button
  event.target.classList.add('active');
  
  // Re-render charts if needed
  if (tabName === 'trends' && typeof renderTrendChart === 'function') {
    setTimeout(() => {
      renderTrendChart();
    }, 100);
  }
}

// Chart rendering functions
function renderAttendanceChart() {
  const ctx = document.getElementById('attendanceChart');
  if (!ctx) return;
  
  const data = window.dashboardData?.overviewData || { labels: [], present: [], absent: [] };
  
  new Chart(ctx.getContext('2d'), {
    type: 'bar',
    data: {
      labels: data.labels,
      datasets: [
        {
          label: 'Present',
          data: data.present,
          backgroundColor: '#10b981',
          borderColor: '#10b981',
          borderWidth: 1
        },
        {
          label: 'Absent',
          data: data.absent,
          backgroundColor: '#ef4444',
          borderColor: '#ef4444',
          borderWidth: 1
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          title: {
            display: true,
            text: 'Number of Employees'
          }
        }
      },
      plugins: {
        legend: {
          position: 'top',
        },
        title: {
          display: true,
          text: 'Company Attendance (Last 7 Days)'
        }
      }
    }
  });
}

function renderWeeklyPatternChart() {
  const ctx = document.getElementById('weeklyPatternChart');
  if (!ctx) return;
  
  const weeklyPattern = window.dashboardData?.weeklyPattern || [];
  const labels = weeklyPattern.map(item => item.day);
  const data = weeklyPattern.map(item => item.rate);
  
  new Chart(ctx.getContext('2d'), {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{
        label: 'Your Attendance Rate (%)',
        data: data,
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        borderColor: '#3b82f6',
        borderWidth: 2,
        fill: true,
        tension: 0.4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          max: 100,
          title: {
            display: true,
            text: 'Attendance Rate %'
          }
        }
      },
      plugins: {
        legend: {
          position: 'top',
        },
        title: {
          display: true,
          text: 'Your Weekly Attendance Pattern'
        }
      }
    }
  });
}

function renderTrendChart() {
  const ctx = document.getElementById('trendChart');
  if (!ctx) return;
  
  const monthlyTrend = window.dashboardData?.monthlyTrend || [];
  const months = monthlyTrend.map(item => item.month);
  const rates = monthlyTrend.map(item => item.rate);
  
  // Reverse for chronological order
  const sortedMonths = [...months].reverse();
  const sortedRates = [...rates].reverse();
  
  new Chart(ctx.getContext('2d'), {
    type: 'line',
    data: {
      labels: sortedMonths,
      datasets: [{
        label: 'Your Attendance Rate Trend',
        data: sortedRates,
        backgroundColor: 'rgba(16, 185, 129, 0.1)',
        borderColor: '#10b981',
        borderWidth: 3,
        fill: true,
        tension: 0.3
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          max: 100,
          title: {
            display: true,
            text: 'Attendance Rate %'
          }
        },
        x: {
          title: {
            display: true,
            text: 'Month'
          }
        }
      },
      plugins: {
        legend: {
          position: 'top',
        },
        title: {
          display: true,
          text: 'Your Attendance Trend (Last 6 Months)'
        }
      }
    }
  });
}

// Initialize charts when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
  console.log('Dashboard loaded');
  
  try {
    if (typeof renderAttendanceChart === 'function') {
      renderAttendanceChart();
    }
    if (typeof renderWeeklyPatternChart === 'function') {
      renderWeeklyPatternChart();
    }
    
    // Check if trend tab is active on load
    if (document.getElementById('trends-tab') && document.getElementById('trends-tab').classList.contains('active')) {
      setTimeout(() => {
        if (typeof renderTrendChart === 'function') {
          renderTrendChart();
        }
      }, 100);
    }
  } catch (error) {
    console.error('Error loading charts:', error);
  }
  
  // Force show analytics section
  const analyticsSection = document.querySelector('.analytics-section');
  if (analyticsSection) {
    analyticsSection.style.display = 'block';
  }
});

// Admin Quick Actions JavaScript (only if user is admin)
if (window.dashboardData?.isAdmin) {
  // Close modal when clicking outside
  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('quick-action-modal')) {
      e.target.classList.remove('active');
    }
  });
}

function closeQuickActionModal(modalId) {
  document.getElementById(modalId).classList.remove('active');
}

// Button 1: Instant Payroll Export
function quickActionInstantExport() {
  fetch('quick_actions.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'action=instant_export'
  })
  .then(r => r.json())
  .then(data => {
    if (data.success && data.url) {
      window.open(data.url, '_blank');
    } else {
      alert('Export failed: ' + (data.message || 'Unknown error'));
    }
  })
  .catch(err => {
    console.error('Instant export error:', err);
    alert('Failed to initiate export. Please try again.');
  });
}

// Button 2: Search & Log Attendance
function quickActionSearchAttendance() {
  document.getElementById('modal-search-attendance').classList.add('active');
  document.getElementById('search-attendance-input').focus();
}

let searchDebounceTimer;
function searchEmployees(query) {
  clearTimeout(searchDebounceTimer);
  const resultsDiv = document.getElementById('search-attendance-results');
  
  if (!query.trim()) {
    resultsDiv.innerHTML = '';
    return;
  }
  
  searchDebounceTimer = setTimeout(() => {
    fetch('quick_actions.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=search_employees&q=' + encodeURIComponent(query)
    })
    .then(r => r.json())
    .then(data => {
      if (data.success && data.employees) {
        renderEmployeeSearchResults(data.employees);
      } else {
        resultsDiv.innerHTML = '<div style="text-align: center; color: #808080; padding: 20px;">No employees found</div>';
      }
    })
    .catch(err => {
      console.error('Search error:', err);
      resultsDiv.innerHTML = '<div style="text-align: center; color: #ef4444; padding: 20px;">Search failed</div>';
    });
  }, 300);
}

function renderEmployeeSearchResults(employees) {
  const resultsDiv = document.getElementById('search-attendance-results');
  if (!employees.length) {
    resultsDiv.innerHTML = '<div style="text-align: center; color: #808080; padding: 20px;">No employees found</div>';
    return;
  }
  
  resultsDiv.innerHTML = employees.map(emp => `
    <div class="quick-action-list-item">
      <div>
        <div class="emp-name">${escapeHtml(emp.name)}</div>
        <div class="emp-code">${escapeHtml(emp.code)}</div>
      </div>
      <button class="action-btn-small" onclick="logAttendanceForEmployee(${emp.id}, '${escapeJsString(emp.name)}')">
        <i class="fas fa-clock"></i> Log
      </button>
    </div>
  `).join('');
}

function logAttendanceForEmployee(empId, empName) {
  if (confirm(`Confirm Time In/Out for ${empName}?`)) {
    // Redirect to attendance page with employee pre-selected
    window.open(`select_employee.php?employee_id=${empId}&action=log`, '_blank');
    closeQuickActionModal('modal-search-attendance');
  }
}

// Button 3: View Missing Logs
function quickActionMissingLogs() {
  document.getElementById('modal-missing-logs').classList.add('active');
  loadMissingLogs();
}

function loadMissingLogs() {
  const resultsDiv = document.getElementById('missing-logs-results');
  resultsDiv.innerHTML = '<div style="text-align: center; color: #808080; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
  
  fetch('quick_actions.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'action=missing_logs'
  })
  .then(r => r.json())
  .then(data => {
    if (data.success && data.employees) {
      renderMissingLogs(data.employees);
    } else {
      resultsDiv.innerHTML = '<div style="text-align: center; color: #808080; padding: 20px;">Unable to load missing logs</div>';
    }
  })
  .catch(err => {
    console.error('Missing logs error:', err);
    resultsDiv.innerHTML = '<div style="text-align: center; color: #ef4444; padding: 20px;">Failed to load data</div>';
  });
}

function renderMissingLogs(employees) {
  const resultsDiv = document.getElementById('missing-logs-results');
  if (!employees.length) {
    resultsDiv.innerHTML = '<div style="text-align: center; color: #10b981; padding: 20px;"><i class="fas fa-check-circle"></i> All employees have logged attendance today!</div>';
    return;
  }
  
  resultsDiv.innerHTML = employees.map(emp => `
    <div class="quick-action-list-item">
      <div>
        <div class="emp-name">${escapeHtml(emp.name)}</div>
        <div class="emp-code">${escapeHtml(emp.code)}</div>
      </div>
      <button class="action-btn-small" onclick="logAttendanceForEmployee(${emp.id}, '${escapeJsString(emp.name)}')">
        <i class="fas fa-sign-in-alt"></i> Time In
      </button>
    </div>
  `).join('');
}

// Button 4: Recent Activity Logs
function quickActionRecentActivity() {
  document.getElementById('modal-recent-activity').classList.add('active');
  loadRecentActivity();
}

function loadRecentActivity() {
  const resultsDiv = document.getElementById('recent-activity-results');
  resultsDiv.innerHTML = '<div style="text-align: center; color: #808080; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
  
  fetch('quick_actions.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'action=recent_activity'
  })
  .then(r => r.json())
  .then(data => {
    if (data.success && data.logs) {
      renderRecentActivity(data.logs);
    } else {
      resultsDiv.innerHTML = '<div style="text-align: center; color: #808080; padding: 20px;">Unable to load activity logs</div>';
    }
  })
  .catch(err => {
    console.error('Activity logs error:', err);
    resultsDiv.innerHTML = '<div style="text-align: center; color: #ef4444; padding: 20px;">Failed to load logs</div>';
  });
}

function renderRecentActivity(logs) {
  const resultsDiv = document.getElementById('recent-activity-results');
  if (!logs.length) {
    resultsDiv.innerHTML = '<div style="text-align: center; color: #808080; padding: 20px;">No recent activity</div>';
    return;
  }
  
  resultsDiv.innerHTML = logs.map(log => `
    <div class="quick-action-activity-item">
      <div class="activity-action">${escapeHtml(log.action)}</div>
      <div class="activity-details">${escapeHtml(log.details)}</div>
      <div class="activity-meta">
        <i class="fas fa-user"></i> ${escapeHtml(log.user || 'System')} 
        <i class="fas fa-clock" style="margin-left: 8px;"></i> ${formatDateTime(log.created_at)}
      </div>
    </div>
  `).join('');
}

// Utility functions
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

function escapeJsString(str) {
  return str.replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '\\"');
}

function formatDateTime(datetime) {
  if (!datetime) return 'Unknown';
  const date = new Date(datetime);
  return date.toLocaleString('en-US', { 
    month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' 
  });
}
