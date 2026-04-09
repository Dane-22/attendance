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

// ============================================
// DASHBOARD ANALYTICS FUNCTIONS
// ============================================

// Global variable to store analytics data
let dashboardAnalyticsData = null;

// Chart instances for cleanup
let analyticsCharts = {};

// Chart color palette
const chartColors = {
  present: '#10b981',
  late: '#f59e0b',
  absent: '#ef4444',
  gold: '#FFD66B',
  gold2: '#D4AF37',
  blue: '#3b82f6',
  purple: '#8b5cf6',
  pink: '#ec4899',
  cyan: '#06b6d4',
  orange: '#f97316',
  grid: 'rgba(212, 175, 55, 0.1)',
  text: 'rgba(255, 255, 255, 0.7)'
};

// Fetch analytics data from API
async function loadDashboardAnalytics() {
  try {
    showAnalyticsLoading();
    console.log('Fetching analytics data...');
    
    const response = await fetch('api/get_dashboard_analytics.php');
    console.log('Response status:', response.status);
    
    const text = await response.text();
    console.log('Raw response:', text.substring(0, 500));
    
    // Try to parse JSON
    let data;
    try {
      data = JSON.parse(text);
    } catch (parseError) {
      console.error('JSON parse error:', parseError);
      console.error('Response text:', text);
      showAnalyticsError();
      return;
    }
    
    if (data.success) {
      dashboardAnalyticsData = data.data;
      console.log('Analytics data loaded:', dashboardAnalyticsData);
      renderAllAnalyticsCharts();
      updateAnalyticsSummaryCards();
      hideAnalyticsLoading();
    } else {
      console.error('Failed to load analytics:', data.error);
      showAnalyticsError();
    }
  } catch (error) {
    console.error('Error loading analytics:', error);
    showAnalyticsError();
  }
}

// Show loading states
function showAnalyticsLoading() {
  // Don't destroy canvases - just add loading class to wrapper
  document.querySelectorAll('.chart-wrapper').forEach(wrapper => {
    wrapper.classList.add('chart-loading-state');
  });
}

// Hide loading states
function hideAnalyticsLoading() {
  document.querySelectorAll('.chart-wrapper').forEach(wrapper => {
    wrapper.classList.remove('chart-loading-state');
  });
}

// Show error state
function showAnalyticsError() {
  document.querySelectorAll('.chart-wrapper').forEach(wrapper => {
    wrapper.innerHTML = '<div class="chart-no-data"><i class="fas fa-exclamation-triangle"></i><p>Failed to load analytics</p><small>Please try refreshing the page</small></div>';
  });
}

// Update analytics summary cards with data
function updateAnalyticsSummaryCards() {
  if (!dashboardAnalyticsData) return;
  
  const today = dashboardAnalyticsData.today_attendance;
  const overtime = dashboardAnalyticsData.overtime_summary;
  const cashAdvance = dashboardAnalyticsData.cash_advance_summary;
  
  // Update attendance rate card
  const attendanceRateEl = document.getElementById('analytics-attendance-rate');
  if (attendanceRateEl) {
    attendanceRateEl.textContent = today.attendance_rate + '%';
    attendanceRateEl.className = 'metric-value ' + (today.attendance_rate >= 90 ? 'success' : today.attendance_rate >= 75 ? 'warning' : 'danger');
  }
  
  // Update pending overtime card
  const pendingOtEl = document.getElementById('analytics-pending-ot');
  if (pendingOtEl) {
    pendingOtEl.textContent = overtime.pending_count;
    pendingOtEl.className = 'metric-value ' + (overtime.pending_count > 0 ? 'warning' : 'success');
  }
  
  // Update cash advance card
  const caEl = document.getElementById('analytics-pending-ca');
  if (caEl) {
    caEl.textContent = '₱' + cashAdvance.pending_amount.toLocaleString();
    caEl.className = 'metric-value ' + (cashAdvance.pending_amount > 0 ? 'warning' : 'success');
  }
  
  // Update mini stats
  const presentEl = document.getElementById('mini-stat-present');
  if (presentEl) presentEl.textContent = today.present;
  
  const lateEl = document.getElementById('mini-stat-late');
  if (lateEl) lateEl.textContent = today.late;
  
  const absentEl = document.getElementById('mini-stat-absent');
  if (absentEl) absentEl.textContent = today.absent;
  
  const otEl = document.getElementById('mini-stat-ot');
  if (otEl) otEl.textContent = overtime.approved_hours.toFixed(1);
}

// Render all analytics charts
function renderAllAnalyticsCharts() {
  if (!dashboardAnalyticsData) {
    console.error('No analytics data available');
    return;
  }
  
  console.log('Rendering all charts...');
  
  // Check if canvas elements exist
  const canvases = [
    'todayAttendanceChart',
    'attendanceTrendChart', 
    'branchAttendanceChart',
    'overtimeTrendChart',
    'topOvertimeChart',
    'positionDistributionChart',
    'branchDistributionChart'
  ];
  
  canvases.forEach(id => {
    const el = document.getElementById(id);
    console.log(`Canvas ${id}:`, el ? 'Found' : 'NOT FOUND');
  });
  
  renderTodayAttendanceChart();
  renderAttendanceTrendChart();
  renderBranchAttendanceChart();
  renderOvertimeTrendChart();
  renderTopOvertimeChart();
  renderPositionDistributionChart();
  renderBranchDistributionChart();
  
  console.log('All charts rendered');
}

// 1. Today's Attendance Pie Chart
function renderTodayAttendanceChart() {
  const ctx = document.getElementById('todayAttendanceChart');
  console.log('Looking for todayAttendanceChart:', ctx);
  if (!ctx) {
    console.error('todayAttendanceChart canvas not found in DOM');
    // Try to find all canvases on page
    const allCanvases = document.querySelectorAll('canvas');
    console.log('All canvases found:', allCanvases.length, Array.from(allCanvases).map(c => c.id));
    return;
  }
  if (!dashboardAnalyticsData) {
    console.error('No dashboardAnalyticsData available');
    return;
  }
  
  const data = dashboardAnalyticsData.today_attendance;
  
  // Destroy existing chart if any
  if (analyticsCharts.todayAttendance) {
    analyticsCharts.todayAttendance.destroy();
  }
  
  analyticsCharts.todayAttendance = new Chart(ctx.getContext('2d'), {
    type: 'doughnut',
    data: {
      labels: ['Present', 'Late', 'Absent'],
      datasets: [{
        data: [data.present, data.late, data.absent],
        backgroundColor: [chartColors.present, chartColors.late, chartColors.absent],
        borderColor: '#161616',
        borderWidth: 2
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            color: chartColors.text,
            padding: 20,
            usePointStyle: true
          }
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              const label = context.label || '';
              const value = context.parsed;
              const total = data.present + data.late + data.absent;
              const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
              return label + ': ' + value + ' (' + percentage + '%)';
            }
          }
        }
      },
      cutout: '60%'
    }
  });
}

// 2. 7-Day Attendance Trend Chart
function renderAttendanceTrendChart() {
  const ctx = document.getElementById('attendanceTrendChart');
  if (!ctx || !dashboardAnalyticsData) return;
  
  const trendData = dashboardAnalyticsData.attendance_trend;
  const labels = trendData.map(d => d.date);
  const presentData = trendData.map(d => d.present);
  const lateData = trendData.map(d => d.late);
  const absentData = trendData.map(d => d.absent);
  
  if (analyticsCharts.attendanceTrend) {
    analyticsCharts.attendanceTrend.destroy();
  }
  
  analyticsCharts.attendanceTrend = new Chart(ctx.getContext('2d'), {
    type: 'line',
    data: {
      labels: labels,
      datasets: [
        {
          label: 'Present',
          data: presentData,
          borderColor: chartColors.present,
          backgroundColor: chartColors.present + '20',
          fill: true,
          tension: 0.4
        },
        {
          label: 'Late',
          data: lateData,
          borderColor: chartColors.late,
          backgroundColor: chartColors.late + '20',
          fill: true,
          tension: 0.4
        },
        {
          label: 'Absent',
          data: absentData,
          borderColor: chartColors.absent,
          backgroundColor: chartColors.absent + '20',
          fill: true,
          tension: 0.4
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        mode: 'index',
        intersect: false
      },
      plugins: {
        legend: {
          position: 'top',
          labels: {
            color: chartColors.text,
            usePointStyle: true
          }
        }
      },
      scales: {
        x: {
          grid: {
            color: chartColors.grid
          },
          ticks: {
            color: chartColors.text
          }
        },
        y: {
          grid: {
            color: chartColors.grid
          },
          ticks: {
            color: chartColors.text
          },
          beginAtZero: true
        }
      }
    }
  });
}

// 3. Branch Attendance Horizontal Bar Chart
function renderBranchAttendanceChart() {
  const ctx = document.getElementById('branchAttendanceChart');
  if (!ctx || !dashboardAnalyticsData) return;
  
  const branchData = dashboardAnalyticsData.branch_attendance;
  const labels = branchData.map(b => b.branch);
  const rates = branchData.map(b => b.attendance_rate);
  
  if (analyticsCharts.branchAttendance) {
    analyticsCharts.branchAttendance.destroy();
  }
  
  analyticsCharts.branchAttendance = new Chart(ctx.getContext('2d'), {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Attendance Rate %',
        data: rates,
        backgroundColor: rates.map(rate => {
          if (rate >= 90) return chartColors.present;
          if (rate >= 75) return chartColors.late;
          return chartColors.absent;
        }),
        borderRadius: 6
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              return 'Attendance Rate: ' + context.parsed.x + '%';
            }
          }
        }
      },
      scales: {
        x: {
          max: 100,
          grid: {
            color: chartColors.grid
          },
          ticks: {
            color: chartColors.text,
            callback: function(value) {
              return value + '%';
            }
          }
        },
        y: {
          grid: {
            display: false
          },
          ticks: {
            color: chartColors.text
          }
        }
      }
    }
  });
}

// 4. Monthly Overtime Trend Chart
function renderOvertimeTrendChart() {
  const ctx = document.getElementById('overtimeTrendChart');
  if (!ctx || !dashboardAnalyticsData) return;
  
  const otData = dashboardAnalyticsData.overtime_trend;
  const labels = otData.map(d => d.month);
  const totalHours = otData.map(d => d.total_hours);
  const approvedHours = otData.map(d => d.approved_hours);
  
  if (analyticsCharts.overtimeTrend) {
    analyticsCharts.overtimeTrend.destroy();
  }
  
  analyticsCharts.overtimeTrend = new Chart(ctx.getContext('2d'), {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [
        {
          label: 'Total Hours',
          data: totalHours,
          backgroundColor: chartColors.gold + '80',
          borderColor: chartColors.gold,
          borderWidth: 1
        },
        {
          label: 'Approved Hours',
          data: approvedHours,
          backgroundColor: chartColors.blue + '80',
          borderColor: chartColors.blue,
          borderWidth: 1
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'top',
          labels: {
            color: chartColors.text,
            usePointStyle: true
          }
        }
      },
      scales: {
        x: {
          grid: {
            color: chartColors.grid
          },
          ticks: {
            color: chartColors.text
          }
        },
        y: {
          grid: {
            color: chartColors.grid
          },
          ticks: {
            color: chartColors.text
          },
          beginAtZero: true
        }
      }
    }
  });
}

// 5. Top Overtime Employees (Horizontal Bar)
function renderTopOvertimeChart() {
  const ctx = document.getElementById('topOvertimeChart');
  if (!ctx || !dashboardAnalyticsData) return;
  
  const topOtData = dashboardAnalyticsData.top_overtime_employees;
  const labels = topOtData.map(e => e.name);
  const hours = topOtData.map(e => e.hours);
  
  if (analyticsCharts.topOvertime) {
    analyticsCharts.topOvertime.destroy();
  }
  
  analyticsCharts.topOvertime = new Chart(ctx.getContext('2d'), {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Overtime Hours',
        data: hours,
        backgroundColor: [
          chartColors.gold,
          chartColors.gold + 'CC',
          chartColors.gold + '99',
          chartColors.gold + '66',
          chartColors.gold + '44'
        ],
        borderRadius: 6
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        }
      },
      scales: {
        x: {
          grid: {
            color: chartColors.grid
          },
          ticks: {
            color: chartColors.text
          },
          beginAtZero: true
        },
        y: {
          grid: {
            display: false
          },
          ticks: {
            color: chartColors.text
          }
        }
      }
    }
  });
}

// 6. Employee Position Distribution (Pie Chart)
function renderPositionDistributionChart() {
  const ctx = document.getElementById('positionDistributionChart');
  if (!ctx || !dashboardAnalyticsData) return;
  
  const positionData = dashboardAnalyticsData.employee_by_position;
  const labels = positionData.map(p => p.position);
  const counts = positionData.map(p => p.count);
  
  const colors = [chartColors.gold, chartColors.blue, chartColors.purple, chartColors.pink, chartColors.cyan, chartColors.orange];
  
  if (analyticsCharts.positionDistribution) {
    analyticsCharts.positionDistribution.destroy();
  }
  
  analyticsCharts.positionDistribution = new Chart(ctx.getContext('2d'), {
    type: 'pie',
    data: {
      labels: labels,
      datasets: [{
        data: counts,
        backgroundColor: colors,
        borderColor: '#161616',
        borderWidth: 2
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'right',
          labels: {
            color: chartColors.text,
            usePointStyle: true,
            padding: 15
          }
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              const label = context.label || '';
              const value = context.parsed;
              const total = context.dataset.data.reduce((a, b) => a + b, 0);
              const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
              return label + ': ' + value + ' (' + percentage + '%)';
            }
          }
        }
      }
    }
  });
}

// 7. Employee Branch Distribution (Doughnut Chart)
function renderBranchDistributionChart() {
  const ctx = document.getElementById('branchDistributionChart');
  if (!ctx || !dashboardAnalyticsData) return;
  
  const branchData = dashboardAnalyticsData.employee_by_branch.filter(b => b.count > 0);
  const labels = branchData.map(b => b.branch);
  const counts = branchData.map(b => b.count);
  
  const colors = [chartColors.gold, chartColors.blue, chartColors.purple, chartColors.present, chartColors.late, chartColors.cyan, chartColors.pink];
  
  if (analyticsCharts.branchDistribution) {
    analyticsCharts.branchDistribution.destroy();
  }
  
  analyticsCharts.branchDistribution = new Chart(ctx.getContext('2d'), {
    type: 'doughnut',
    data: {
      labels: labels,
      datasets: [{
        data: counts,
        backgroundColor: colors,
        borderColor: '#161616',
        borderWidth: 2
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            color: chartColors.text,
            usePointStyle: true,
            padding: 15
          }
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              const label = context.label || '';
              const value = context.parsed;
              const total = context.dataset.data.reduce((a, b) => a + b, 0);
              const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
              return label + ': ' + value + ' (' + percentage + '%)';
            }
          }
        }
      },
      cutout: '50%'
    }
  });
}

// Immediate check - what's in the DOM right now?
console.log('Script loaded, checking for canvases immediately:');
const immediateCanvases = document.querySelectorAll('canvas');
console.log('Found', immediateCanvases.length, 'canvases:', Array.from(immediateCanvases).map(c => c.id));

// Initialize analytics when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
  console.log('DOM Content Loaded');
  
  // Check for canvases now
  const domCanvases = document.querySelectorAll('canvas');
  console.log('DOMContentLoaded: Found', domCanvases.length, 'canvases:', Array.from(domCanvases).map(c => c.id));
  
  // Load dashboard analytics with delay to ensure DOM is ready
  if (typeof loadDashboardAnalytics === 'function') {
    setTimeout(function() {
      console.log('Loading analytics after delay...');
      loadDashboardAnalytics();
    }, 500);
  }
});

// Fallback: Load on window load if DOMContentLoaded already fired
window.addEventListener('load', function() {
  console.log('Window load event fired');
  const loadCanvases = document.querySelectorAll('canvas');
  console.log('Window.load: Found', loadCanvases.length, 'canvases:', Array.from(loadCanvases).map(c => c.id));
  
  if (!dashboardAnalyticsData && typeof loadDashboardAnalytics === 'function') {
    console.log('Window loaded, analytics not loaded yet, loading now...');
    loadDashboardAnalytics();
  }
});
