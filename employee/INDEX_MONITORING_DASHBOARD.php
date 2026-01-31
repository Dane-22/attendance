<?php
/**
 * MONITORING DASHBOARD - RESOURCE INDEX
 * =====================================
 * This file serves as a hub for all monitoring dashboard resources
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Dashboard - Resource Index</title>
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
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            padding: 2rem;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 3rem;
            padding-bottom: 2rem;
            border-bottom: 3px solid #d4af37;
        }
        
        .header h1 {
            color: #d4af37;
            font-size: 3rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }
        
        .header p {
            color: #a0a0a0;
            font-size: 1.1rem;
        }
        
        .resources-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .resource-card {
            background: rgba(26, 26, 26, 0.8);
            border: 2px solid rgba(212, 175, 55, 0.2);
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s cubic-bezier(0.23, 1, 0.320, 1);
            position: relative;
            overflow: hidden;
        }
        
        .resource-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(212, 175, 55, 0.1), transparent);
            transition: left 0.5s ease;
        }
        
        .resource-card:hover {
            border-color: rgba(212, 175, 55, 0.5);
            background: rgba(26, 26, 26, 0.95);
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(212, 175, 55, 0.15);
        }
        
        .resource-card:hover::before {
            left: 100%;
        }
        
        .resource-icon {
            font-size: 2.5rem;
            color: #d4af37;
            margin-bottom: 1rem;
        }
        
        .resource-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.5rem;
        }
        
        .resource-desc {
            color: #a0a0a0;
            font-size: 0.95rem;
            margin-bottom: 1rem;
            line-height: 1.5;
        }
        
        .resource-links {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .resource-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #d4af37;
            text-decoration: none;
            font-size: 0.9rem;
            padding: 0.5rem;
            border-radius: 4px;
            transition: all 0.2s ease;
        }
        
        .resource-link:hover {
            background: rgba(212, 175, 55, 0.1);
            color: #FFD700;
        }
        
        .resource-link i {
            font-size: 1rem;
        }
        
        .status-badge {
            display: inline-block;
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 0.75rem;
        }
        
        .section-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #d4af37;
            margin: 2rem 0 1rem 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .quick-actions {
            background: linear-gradient(135deg, rgba(212, 175, 55, 0.1), rgba(212, 175, 55, 0.05));
            border: 2px solid rgba(212, 175, 55, 0.3);
            border-radius: 12px;
            padding: 2rem;
            margin: 2rem 0;
        }
        
        .quick-actions h3 {
            color: #d4af37;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }
        
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .action-button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, #d4af37, #FFD700);
            color: #0a0a0a;
            padding: 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(212, 175, 55, 0.3);
        }
        
        .features-list {
            background: rgba(26, 26, 26, 0.6);
            border-left: 4px solid #d4af37;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        
        .features-list h4 {
            color: #d4af37;
            margin-bottom: 1rem;
        }
        
        .features-list ul {
            list-style: none;
            color: #a0a0a0;
        }
        
        .features-list li {
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .features-list li:before {
            content: '✓';
            color: #10b981;
            font-weight: 700;
            margin-right: 0.5rem;
        }
        
        .footer {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(212, 175, 55, 0.1);
            color: #7a7a7a;
            margin-top: 3rem;
        }
        
        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.75rem;
            }
            
            .resources-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        
        <!-- HEADER -->
        <div class="header">
            <h1>
                <i class="fas fa-chart-line"></i>
                Monitoring Dashboard
            </h1>
            <p>Professional Real-Time Analytics with Dark Engineering Theme</p>
        </div>
        
        <!-- QUICK ACTIONS -->
        <div class="quick-actions">
            <h3>🚀 Get Started Now</h3>
            <div class="action-buttons">
                <a href="monitoring_dashboard.php" class="action-button">
                    <i class="fas fa-eye"></i> View Dashboard
                </a>
                <a href="test_monitoring_dashboard.php" class="action-button">
                    <i class="fas fa-flask"></i> Run Tests
                </a>
                <a href="QUICK_SETUP.php" class="action-button">
                    <i class="fas fa-bolt"></i> Quick Setup
                </a>
                <a href="MONITORING_DASHBOARD_GUIDE.php" class="action-button">
                    <i class="fas fa-book"></i> Full Guide
                </a>
            </div>
        </div>
        
        <!-- RESOURCES -->
        <div class="section-title">
            <i class="fas fa-folder-open"></i>
            Available Resources
        </div>
        
        <div class="resources-grid">
            
            <!-- Card 1: Standalone Dashboard -->
            <div class="resource-card">
                <div class="resource-icon">
                    <i class="fas fa-dashboard"></i>
                </div>
                <div class="status-badge">Recommended</div>
                <div class="resource-title">Monitoring Dashboard</div>
                <p class="resource-desc">Complete standalone page with full HTML/CSS. Access directly without integration.</p>
                <div class="resource-links">
                    <a href="monitoring_dashboard.php" class="resource-link">
                        <i class="fas fa-external-link-alt"></i>
                        View Page
                    </a>
                    <a href="#" class="resource-link" onclick="copyPath('monitoring_dashboard.php'); return false;">
                        <i class="fas fa-copy"></i>
                        Copy Path
                    </a>
                </div>
            </div>
            
            <!-- Card 2: Component Version -->
            <div class="resource-card">
                <div class="resource-icon">
                    <i class="fas fa-cogs"></i>
                </div>
                <div class="status-badge">Most Popular</div>
                <div class="resource-title">Dashboard Component</div>
                <p class="resource-desc">Modular component for integrating into your existing dashboard.php file.</p>
                <div class="resource-links">
                    <a href="#" class="resource-link" onclick="copyPath('monitoring_dashboard_component.php'); return false;">
                        <i class="fas fa-copy"></i>
                        Copy Include
                    </a>
                    <a href="QUICK_SETUP.php" class="resource-link">
                        <i class="fas fa-arrow-right"></i>
                        Integration Guide
                    </a>
                </div>
            </div>
            
            <!-- Card 3: Verification Tool -->
            <div class="resource-card">
                <div class="resource-icon">
                    <i class="fas fa-flask-vial"></i>
                </div>
                <div class="status-badge">Helpful</div>
                <div class="resource-title">Verification Test</div>
                <p class="resource-desc">Check if your setup is complete and all components are working correctly.</p>
                <div class="resource-links">
                    <a href="test_monitoring_dashboard.php" class="resource-link">
                        <i class="fas fa-play-circle"></i>
                        Run Tests
                    </a>
                    <a href="test_monitoring_dashboard.php?details=1" class="resource-link">
                        <i class="fas fa-info-circle"></i>
                        View Details
                    </a>
                </div>
            </div>
            
            <!-- Card 4: Quick Setup -->
            <div class="resource-card">
                <div class="resource-icon">
                    <i class="fas fa-lightning-bolt"></i>
                </div>
                <div class="status-badge">Fast</div>
                <div class="resource-title">Quick Setup</div>
                <p class="resource-desc">Quick reference guide for rapid integration into your dashboard.</p>
                <div class="resource-links">
                    <a href="QUICK_SETUP.php" class="resource-link">
                        <i class="fas fa-book"></i>
                        Read Guide
                    </a>
                    <a href="#copy-code" class="resource-link">
                        <i class="fas fa-code"></i>
                        Copy Code
                    </a>
                </div>
            </div>
            
            <!-- Card 5: Full Documentation -->
            <div class="resource-card">
                <div class="resource-icon">
                    <i class="fas fa-book-open"></i>
                </div>
                <div class="status-badge">Complete</div>
                <div class="resource-title">Full Documentation</div>
                <p class="resource-desc">Comprehensive guide covering features, customization, and troubleshooting.</p>
                <div class="resource-links">
                    <a href="MONITORING_DASHBOARD_GUIDE.php" class="resource-link">
                        <i class="fas fa-document"></i>
                        Read Documentation
                    </a>
                    <a href="README_MONITORING_DASHBOARD.md" class="resource-link">
                        <i class="fas fa-markdown"></i>
                        View README
                    </a>
                </div>
            </div>
            
            <!-- Card 6: Integration Examples -->
            <div class="resource-card">
                <div class="resource-icon">
                    <i class="fas fa-code"></i>
                </div>
                <div class="status-badge">Code</div>
                <div class="resource-title">Code Examples</div>
                <p class="resource-desc">Copy-paste integration examples and code snippets.</p>
                <div class="resource-links">
                    <a href="#" class="resource-link" onclick="showCodeExample(); return false;">
                        <i class="fas fa-clipboard"></i>
                        View Examples
                    </a>
                    <a href="MONITORING_DASHBOARD_GUIDE.php#examples" class="resource-link">
                        <i class="fas fa-link"></i>
                        In Guide
                    </a>
                </div>
            </div>
            
        </div>
        
        <!-- FEATURES -->
        <div class="section-title">
            <i class="fas fa-star"></i>
            Key Features
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
            
            <div class="features-list">
                <h4>📊 Summary Cards</h4>
                <ul>
                    <li>Total Manpower</li>
                    <li>On-Site Today</li>
                    <li>Absent Today</li>
                    <li>Active Branches</li>
                </ul>
            </div>
            
            <div class="features-list">
                <h4>📋 Data Tables</h4>
                <ul>
                    <li>Branch Headcount</li>
                    <li>Gold Progress Bars</li>
                    <li>Status Indicators</li>
                    <li>Capacity Visualization</li>
                </ul>
            </div>
            
            <div class="features-list">
                <h4>📈 Real-Time Data</h4>
                <ul>
                    <li>Last 5 Activities</li>
                    <li>Employee Names (Gold)</li>
                    <li>Branch Info</li>
                    <li>Timestamps</li>
                </ul>
            </div>
            
            <div class="features-list">
                <h4>🎨 Design</h4>
                <ul>
                    <li>Glassmorphism Cards</li>
                    <li>Dark Engineering Theme</li>
                    <li>Smooth Animations</li>
                    <li>Mobile Responsive</li>
                </ul>
            </div>
            
            <div class="features-list">
                <h4>🛡️ Security</h4>
                <ul>
                    <li>Prepared Statements</li>
                    <li>SQL Injection Safe</li>
                    <li>Session Protected</li>
                    <li>Input Validation</li>
                </ul>
            </div>
            
            <div class="features-list">
                <h4>⚡ Performance</h4>
                <ul>
                    <li>Real-Time Queries</li>
                    <li>Optimized CSS</li>
                    <li>Minimal DOM</li>
                    <li>Fast Load Time</li>
                </ul>
            </div>
            
        </div>
        
        <!-- INTEGRATION METHODS -->
        <div class="section-title">
            <i class="fas fa-directions"></i>
            Integration Methods
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            
            <div style="background: rgba(26, 26, 26, 0.8); border: 1px solid rgba(212, 175, 55, 0.15); border-radius: 12px; padding: 1.5rem;">
                <h3 style="color: #d4af37; margin-bottom: 1rem;">
                    <i class="fas fa-check-circle"></i> Method 1: Standalone
                </h3>
                <p style="color: #a0a0a0; margin-bottom: 1rem;">
                    Access the complete dashboard directly as a separate page.
                </p>
                <code style="background: rgba(212, 175, 55, 0.1); padding: 0.5rem; border-radius: 4px; color: #FFD700; display: block; margin-bottom: 1rem;">
                    /employee/monitoring_dashboard.php
                </code>
                <p style="color: #7a7a7a; font-size: 0.9rem;">
                    <i class="fas fa-star"></i> Best for: Quick viewing, testing, or standalone use
                </p>
            </div>
            
            <div style="background: rgba(26, 26, 26, 0.8); border: 1px solid rgba(212, 175, 55, 0.15); border-radius: 12px; padding: 1.5rem;">
                <h3 style="color: #d4af37; margin-bottom: 1rem;">
                    <i class="fas fa-star"></i> Method 2: Component (Recommended)
                </h3>
                <p style="color: #a0a0a0; margin-bottom: 1rem;">
                    Include the component in your existing dashboard.php file.
                </p>
                <code style="background: rgba(212, 175, 55, 0.1); padding: 0.5rem; border-radius: 4px; color: #FFD700; display: block; margin-bottom: 1rem;">
                    &lt;?php include __DIR__ . '/monitoring_dashboard_component.php'; ?&gt;
                </code>
                <p style="color: #7a7a7a; font-size: 0.9rem;">
                    <i class="fas fa-star"></i> Best for: Integration, customization, professional use
                </p>
            </div>
            
        </div>
        
        <!-- COLORS & THEME -->
        <div class="section-title">
            <i class="fas fa-palette"></i>
            Dark Engineering Theme
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
            
            <div style="background: rgba(26, 26, 26, 0.8); border: 1px solid rgba(212, 175, 55, 0.15); border-radius: 12px; padding: 1.5rem; text-align: center;">
                <div style="width: 60px; height: 60px; background: #d4af37; border-radius: 8px; margin: 0 auto 1rem;"></div>
                <div style="color: #d4af37; font-weight: 600; margin-bottom: 0.25rem;">Primary Gold</div>
                <code style="color: #a0a0a0; font-size: 0.85rem;">#d4af37</code>
            </div>
            
            <div style="background: rgba(26, 26, 26, 0.8); border: 1px solid rgba(212, 175, 55, 0.15); border-radius: 12px; padding: 1.5rem; text-align: center;">
                <div style="width: 60px; height: 60px; background: #FFD700; border-radius: 8px; margin: 0 auto 1rem;"></div>
                <div style="color: #FFD700; font-weight: 600; margin-bottom: 0.25rem;">Light Gold</div>
                <code style="color: #a0a0a0; font-size: 0.85rem;">#FFD700</code>
            </div>
            
            <div style="background: rgba(26, 26, 26, 0.8); border: 1px solid rgba(212, 175, 55, 0.15); border-radius: 12px; padding: 1.5rem; text-align: center;">
                <div style="width: 60px; height: 60px; background: #0a0a0a; border: 1px solid #333; border-radius: 8px; margin: 0 auto 1rem;"></div>
                <div style="color: #ffffff; font-weight: 600; margin-bottom: 0.25rem;">Black Bg</div>
                <code style="color: #a0a0a0; font-size: 0.85rem;">#0a0a0a</code>
            </div>
            
            <div style="background: rgba(26, 26, 26, 0.8); border: 1px solid rgba(212, 175, 55, 0.15); border-radius: 12px; padding: 1.5rem; text-align: center;">
                <div style="width: 60px; height: 60px; background: #1a1a1a; border-radius: 8px; margin: 0 auto 1rem;"></div>
                <div style="color: #ffffff; font-weight: 600; margin-bottom: 0.25rem;">Dark Gray</div>
                <code style="color: #a0a0a0; font-size: 0.85rem;">#1a1a1a</code>
            </div>
            
        </div>
        
        <!-- REQUIREMENTS -->
        <div class="section-title">
            <i class="fas fa-check-list"></i>
            Requirements
        </div>
        
        <div style="background: rgba(26, 26, 26, 0.8); border: 1px solid rgba(212, 175, 55, 0.15); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                
                <div>
                    <h4 style="color: #d4af37; margin-bottom: 0.75rem;">Database Tables</h4>
                    <ul style="list-style: none; color: #a0a0a0;">
                        <li>✓ employees</li>
                        <li>✓ attendance</li>
                    </ul>
                </div>
                
                <div>
                    <h4 style="color: #d4af37; margin-bottom: 0.75rem;">PHP Version</h4>
                    <ul style="list-style: none; color: #a0a0a0;">
                        <li>✓ PHP 7.2+</li>
                        <li>✓ MySQLi Support</li>
                    </ul>
                </div>
                
                <div>
                    <h4 style="color: #d4af37; margin-bottom: 0.75rem;">Frontend</h4>
                    <ul style="list-style: none; color: #a0a0a0;">
                        <li>✓ FontAwesome 6.4.0</li>
                        <li>✓ Tailwind CSS (optional)</li>
                    </ul>
                </div>
                
                <div>
                    <h4 style="color: #d4af37; margin-bottom: 0.75rem;">Browser Support</h4>
                    <ul style="list-style: none; color: #a0a0a0;">
                        <li>✓ Chrome 90+</li>
                        <li>✓ Firefox 88+</li>
                    </ul>
                </div>
                
            </div>
        </div>
        
        <!-- FOOTER -->
        <div class="footer">
            <p>
                <i class="fas fa-heart" style="color: #d4af37;"></i>
                Professional Monitoring Dashboard | Dark Engineering Theme
            </p>
            <p style="margin-top: 0.5rem; color: #5a5a5a;">
                Real-Time Analytics • Responsive Design • Security First
            </p>
        </div>
        
    </div>
    
    <script>
        function copyPath(path) {
            const text = `<?php include __DIR__ . '/${path}'; ?>`;
            navigator.clipboard.writeText(text).then(() => {
                alert('Include statement copied to clipboard!\n\n' + text);
            });
        }
        
        function showCodeExample() {
            alert('Integration code:\n\n<?php include __DIR__ . "/monitoring_dashboard_component.php"; ?>\n\nAdd this to your dashboard.php file in the main content area.');
        }
    </script>
</body>
</html>
