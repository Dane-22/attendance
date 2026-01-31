<?php
/**
 * MONITORING DASHBOARD - QUICK REFERENCE CARD
 * Quick access to common tasks and links
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Dashboard - Quick Reference</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #0a0a0a 0%, #0f0f0f 100%);
            color: #ffffff;
            font-family: 'Inter', system-ui, sans-serif;
            padding: 1.5rem;
            min-height: 100vh;
        }
        
        .card {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(26, 26, 26, 0.95);
            border: 2px solid #d4af37;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }
        
        .header {
            text-align: center;
            margin-bottom: 2rem;
            border-bottom: 2px solid #d4af37;
            padding-bottom: 1rem;
        }
        
        .header h1 {
            color: #d4af37;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            color: #a0a0a0;
        }
        
        .section {
            margin-bottom: 1.5rem;
        }
        
        .section-title {
            color: #d4af37;
            font-weight: 700;
            margin-bottom: 0.75rem;
            font-size: 1.1rem;
        }
        
        .item {
            background: rgba(212, 175, 55, 0.05);
            border-left: 3px solid #d4af37;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            border-radius: 4px;
        }
        
        .item-title {
            color: #FFD700;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .item-desc {
            color: #a0a0a0;
            font-size: 0.9rem;
        }
        
        .code-block {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(212, 175, 55, 0.2);
            padding: 0.75rem;
            border-radius: 4px;
            color: #FFD700;
            font-family: 'Monaco', 'Courier New', monospace;
            font-size: 0.85rem;
            overflow-x: auto;
            margin: 0.5rem 0;
        }
        
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .button-group {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.75rem;
            flex-wrap: wrap;
        }
        
        .button {
            background: linear-gradient(135deg, #d4af37, #FFD700);
            color: #0a0a0a;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }
        
        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);
        }
        
        .button-secondary {
            background: rgba(212, 175, 55, 0.1);
            color: #d4af37;
            border: 1px solid #d4af37;
        }
        
        .note {
            background: rgba(16, 185, 129, 0.1);
            border-left: 3px solid #10b981;
            padding: 0.75rem;
            border-radius: 4px;
            color: #a0a0a0;
            margin: 1rem 0;
        }
        
        .warning {
            background: rgba(245, 158, 11, 0.1);
            border-left: 3px solid #f59e0b;
            padding: 0.75rem;
            border-radius: 4px;
            color: #a0a0a0;
            margin: 1rem 0;
        }
        
        @media (max-width: 600px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
            
            .card {
                padding: 1.5rem;
            }
            
            .header h1 {
                font-size: 1.4rem;
            }
        }
    </style>
</head>
<body>
    
    <div class="card">
        
        <div class="header">
            <h1>📊 Monitoring Dashboard</h1>
            <p>Quick Reference Card</p>
        </div>
        
        <!-- QUICK NAVIGATION -->
        <div class="section">
            <div class="section-title">🚀 Quick Navigation</div>
            
            <div class="grid-2">
                <div class="item">
                    <div class="item-title">View Dashboard</div>
                    <div class="item-desc">See the standalone dashboard</div>
                    <a href="monitoring_dashboard.php" class="button">
                        <i class="fas fa-eye"></i> Open
                    </a>
                </div>
                
                <div class="item">
                    <div class="item-title">Run Tests</div>
                    <div class="item-desc">Verify setup is complete</div>
                    <a href="test_monitoring_dashboard.php" class="button">
                        <i class="fas fa-flask"></i> Test
                    </a>
                </div>
                
                <div class="item">
                    <div class="item-title">Resource Hub</div>
                    <div class="item-desc">All files and links</div>
                    <a href="INDEX_MONITORING_DASHBOARD.php" class="button">
                        <i class="fas fa-folder"></i> Browse
                    </a>
                </div>
                
                <div class="item">
                    <div class="item-title">Full Documentation</div>
                    <div class="item-desc">Complete guide & help</div>
                    <a href="MONITORING_DASHBOARD_GUIDE.php" class="button">
                        <i class="fas fa-book"></i> Read
                    </a>
                </div>
            </div>
        </div>
        
        <!-- INTEGRATION -->
        <div class="section">
            <div class="section-title">⚡ Quick Integration</div>
            
            <div class="item">
                <div class="item-title">Add to dashboard.php</div>
                <div class="item-desc">Include this in your main content area:</div>
                <div class="code-block">
&lt;?php include __DIR__ . '/monitoring_dashboard_component.php'; ?&gt;
                </div>
                <button onclick="copyToClipboard('<?php include __DIR__ . \\'\\'/monitoring_dashboard_component.php\\'; ?>')" class="button button-secondary">
                    <i class="fas fa-copy"></i> Copy
                </button>
            </div>
        </div>
        
        <!-- KEY FILES -->
        <div class="section">
            <div class="section-title">📁 Key Files</div>
            
            <div class="item">
                <div class="item-title">monitoring_dashboard.php</div>
                <div class="item-desc">Complete standalone page (use directly or integrate)</div>
            </div>
            
            <div class="item">
                <div class="item-title">monitoring_dashboard_component.php</div>
                <div class="item-desc">Modular component for integration (RECOMMENDED)</div>
            </div>
            
            <div class="item">
                <div class="item-title">test_monitoring_dashboard.php</div>
                <div class="item-desc">Verification script to check your setup</div>
            </div>
            
            <div class="item">
                <div class="item-title">MONITORING_DASHBOARD_GUIDE.php</div>
                <div class="item-desc">Comprehensive documentation with examples</div>
            </div>
        </div>
        
        <!-- FEATURES -->
        <div class="section">
            <div class="section-title">✨ Main Features</div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div style="padding: 0.5rem; background: rgba(212, 175, 55, 0.05); border-radius: 4px;">
                    <strong style="color: #FFD700;">4 Summary Cards</strong>
                    <p style="font-size: 0.85rem; color: #a0a0a0; margin-top: 0.25rem;">
                        Total Manpower, On-Site, Absent, Branches
                    </p>
                </div>
                
                <div style="padding: 0.5rem; background: rgba(212, 175, 55, 0.05); border-radius: 4px;">
                    <strong style="color: #FFD700;">Branch Table</strong>
                    <p style="font-size: 0.85rem; color: #a0a0a0; margin-top: 0.25rem;">
                        Headcount with progress bars
                    </p>
                </div>
                
                <div style="padding: 0.5rem; background: rgba(212, 175, 55, 0.05); border-radius: 4px;">
                    <strong style="color: #FFD700;">Activity Ticker</strong>
                    <p style="font-size: 0.85rem; color: #a0a0a0; margin-top: 0.25rem;">
                        Last 5 attendance records
                    </p>
                </div>
                
                <div style="padding: 0.5rem; background: rgba(212, 175, 55, 0.05); border-radius: 4px;">
                    <strong style="color: #FFD700;">Real-Time Data</strong>
                    <p style="font-size: 0.85rem; color: #a0a0a0; margin-top: 0.25rem;">
                        Live database queries
                    </p>
                </div>
            </div>
        </div>
        
        <!-- SQL QUERIES -->
        <div class="section">
            <div class="section-title">🔍 SQL Queries Used</div>
            
            <div class="item">
                <div class="item-title">Total Employees</div>
                <div class="code-block">SELECT COUNT(*) FROM employees WHERE status = 'Active'</div>
            </div>
            
            <div class="item">
                <div class="item-title">Present Today</div>
                <div class="code-block">SELECT COUNT(*) FROM attendance WHERE attendance_date = CURDATE() AND status = 'Present'</div>
            </div>
            
            <div class="item">
                <div class="item-title">Deployment by Branch</div>
                <div class="code-block">SELECT branch_name, COUNT(*) FROM attendance WHERE attendance_date = CURDATE() AND status = 'Present' GROUP BY branch_name</div>
            </div>
            
            <div class="item">
                <div class="item-title">Recent Activity</div>
                <div class="code-block">SELECT a.*, e.first_name, e.last_name FROM attendance a JOIN employees e ON a.employee_id = e.id ORDER BY a.created_at DESC LIMIT 5</div>
            </div>
        </div>
        
        <!-- COLORS -->
        <div class="section">
            <div class="section-title">🎨 Theme Colors</div>
            
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.5rem;">
                <div style="text-align: center;">
                    <div style="width: 100%; height: 40px; background: #d4af37; border-radius: 4px; margin-bottom: 0.5rem;"></div>
                    <small style="color: #a0a0a0;">#d4af37<br>Primary Gold</small>
                </div>
                <div style="text-align: center;">
                    <div style="width: 100%; height: 40px; background: #FFD700; border-radius: 4px; margin-bottom: 0.5rem;"></div>
                    <small style="color: #a0a0a0;">#FFD700<br>Light Gold</small>
                </div>
                <div style="text-align: center;">
                    <div style="width: 100%; height: 40px; background: #0a0a0a; border: 1px solid #333; border-radius: 4px; margin-bottom: 0.5rem;"></div>
                    <small style="color: #a0a0a0;">#0a0a0a<br>Black</small>
                </div>
                <div style="text-align: center;">
                    <div style="width: 100%; height: 40px; background: #1a1a1a; border-radius: 4px; margin-bottom: 0.5rem;"></div>
                    <small style="color: #a0a0a0;">#1a1a1a<br>Dark Gray</small>
                </div>
            </div>
        </div>
        
        <!-- REQUIREMENTS -->
        <div class="section">
            <div class="section-title">✅ Requirements Checklist</div>
            
            <div class="note">
                <strong>Database:</strong> employees & attendance tables
            </div>
            
            <div class="note">
                <strong>PHP:</strong> 7.2+ with MySQLi
            </div>
            
            <div class="note">
                <strong>Icons:</strong> FontAwesome 6.4.0
            </div>
            
            <div class="note">
                <strong>Columns:</strong> attendance_date (not created_at)
            </div>
        </div>
        
        <!-- TROUBLESHOOTING -->
        <div class="section">
            <div class="section-title">🔧 Common Issues</div>
            
            <div class="warning">
                <strong>No data showing?</strong>
                Run test_monitoring_dashboard.php to verify database setup
            </div>
            
            <div class="warning">
                <strong>Styling broken?</strong>
                Check Font Awesome CSS is loaded in &lt;head&gt;
            </div>
            
            <div class="warning">
                <strong>Integration not working?</strong>
                Verify file path is correct and PHP syntax is valid
            </div>
        </div>
        
        <!-- SUPPORT -->
        <div class="section">
            <div class="section-title">📞 Get Help</div>
            
            <div class="grid-2">
                <div style="padding: 1rem; background: rgba(212, 175, 55, 0.05); border-radius: 4px; border-left: 3px solid #d4af37;">
                    <strong style="color: #FFD700;">Quick Help</strong>
                    <p style="color: #a0a0a0; font-size: 0.9rem; margin-top: 0.5rem;">
                        See QUICK_SETUP.php
                    </p>
                </div>
                
                <div style="padding: 1rem; background: rgba(212, 175, 55, 0.05); border-radius: 4px; border-left: 3px solid #d4af37;">
                    <strong style="color: #FFD700;">Full Guide</strong>
                    <p style="color: #a0a0a0; font-size: 0.9rem; margin-top: 0.5rem;">
                        See MONITORING_DASHBOARD_GUIDE.php
                    </p>
                </div>
                
                <div style="padding: 1rem; background: rgba(212, 175, 55, 0.05); border-radius: 4px; border-left: 3px solid #d4af37;">
                    <strong style="color: #FFD700;">Database Issues</strong>
                    <p style="color: #a0a0a0; font-size: 0.9rem; margin-top: 0.5rem;">
                        Run test_monitoring_dashboard.php
                    </p>
                </div>
                
                <div style="padding: 1rem; background: rgba(212, 175, 55, 0.05); border-radius: 4px; border-left: 3px solid #d4af37;">
                    <strong style="color: #FFD700;">All Resources</strong>
                    <p style="color: #a0a0a0; font-size: 0.9rem; margin-top: 0.5rem;">
                        See INDEX_MONITORING_DASHBOARD.php
                    </p>
                </div>
            </div>
        </div>
        
        <!-- FOOTER -->
        <div style="text-align: center; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid rgba(212, 175, 55, 0.1); color: #7a7a7a;">
            <p>Professional Monitoring Dashboard | Dark Engineering Theme</p>
            <p style="font-size: 0.85rem; margin-top: 0.5rem;">Real-Time • Responsive • Secure</p>
        </div>
        
    </div>
    
    <script>
        function copyToClipboard(text) {
            // For demonstration - in real use, include component properly
            const cleanText = "<?php include __DIR__ . '/monitoring_dashboard_component.php'; ?>";
            navigator.clipboard.writeText(cleanText).then(() => {
                alert('Code copied to clipboard!');
            }).catch(() => {
                alert('Failed to copy. Please try again.');
            });
        }
    </script>
</body>
</html>
