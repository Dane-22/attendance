<?php
/**
 * Audit Report Date Range Selector - Super Admin Only
 * Allows Super Admin to choose custom date range before generating report
 */

require_once __DIR__ . '/../conn/db_connection.php';
require_once __DIR__ . '/../functions.php';
session_start();

// Super Admin or Developer access only (case-insensitive)
$allowedPositions = ['super admin', 'developer'];
$currentPosition = strtolower($_SESSION['position'] ?? '');
if (empty($_SESSION['logged_in']) || !in_array($currentPosition, $allowedPositions)) {
    header('Location: ../login.php');
    exit;
}

// Predefined date ranges
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$thisWeekStart = date('Y-m-d', strtotime('monday this week'));
$thisWeekEnd = date('Y-m-d', strtotime('sunday this week'));
$lastWeekStart = date('Y-m-d', strtotime('monday last week'));
$lastWeekEnd = date('Y-m-d', strtotime('sunday last week'));
$thisMonthStart = date('Y-m-01');
$thisMonthEnd = date('Y-m-t');
$lastMonthStart = date('Y-m-01', strtotime('first day of last month'));
$lastMonthEnd = date('Y-m-t', strtotime('last day of last month'));

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startDate = $_POST['start_date'] ?? $today;
    $endDate = $_POST['end_date'] ?? $today;
    
    // Validate dates
    if ($startDate && $endDate && $startDate <= $endDate) {
        header("Location: generate_audit_report.php?start_date=$startDate&end_date=$endDate");
        exit;
    } else {
        $error = "Please select a valid date range. Start date must be before or equal to end date.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Audit Report - Date Range Selector</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="css/light-theme.css">
    <script src="js/theme.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --orange: #FFA500;
            --black: #000000;
            --gold-2: #FFD700;
        }
        body {
            background: linear-gradient(135deg, var(--black) 0%, #1a1a1a 100%);
            color: #ffffff;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
        }
        .main-content {
            margin-left: 16rem;
            padding: 2rem;
            min-height: 100vh;
        }
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
        }
        .selector-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 165, 0, 0.2);
            border-radius: 16px;
            padding: 2rem;
        }
        .date-input {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 165, 0, 0.3);
            border-radius: 8px;
            color: white;
            padding: 12px 16px;
            width: 100%;
            font-size: 16px;
        }
        .date-input:focus {
            outline: none;
            border-color: var(--orange);
            background: rgba(255, 255, 255, 0.15);
        }
        .date-input::-webkit-calendar-picker-indicator {
            filter: invert(1);
            cursor: pointer;
        }
        .preset-btn {
            background: rgba(255, 165, 0, 0.15);
            border: 1px solid rgba(255, 165, 0, 0.3);
            border-radius: 8px;
            color: #ffffff;
            padding: 10px 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: left;
        }
        .preset-btn:hover {
            background: rgba(255, 165, 0, 0.3);
            border-color: var(--orange);
        }
        .preset-btn.active {
            background: var(--orange);
            color: var(--black);
            font-weight: 600;
        }
        .generate-btn {
            background: linear-gradient(135deg, #FFA500 0%, #FF8C00 100%);
            border: none;
            border-radius: 8px;
            color: #000000;
            padding: 14px 32px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .generate-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(255, 165, 0, 0.4);
        }
        .back-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: #ffffff;
            padding: 14px 24px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        .super-admin-badge {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #000000;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }
        .date-preview {
            background: rgba(255, 165, 0, 0.1);
            border: 1px solid rgba(255, 165, 0, 0.2);
            border-radius: 8px;
            padding: 16px;
            margin-top: 16px;
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-2">
                <h1 class="text-3xl font-bold text-white">Generate Audit Report</h1>
                <span class="super-admin-badge"><i class="fas fa-crown mr-1"></i>SUPER ADMIN</span>
            </div>
            <p class="text-gray-400">Select a date range to generate the operational audit report</p>
        </div>

        <?php if (isset($error)): ?>
        <div class="mb-6 bg-red-500/20 border border-red-500/50 rounded-lg p-4 flex items-center gap-3">
            <i class="fas fa-exclamation-circle text-red-400 text-xl"></i>
            <span class="text-red-200"><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Preset Date Ranges -->
            <div class="lg:col-span-1">
                <div class="selector-card">
                    <h3 class="text-lg font-semibold text-orange-400 mb-4">
                        <i class="fas fa-bolt mr-2"></i>Quick Select
                    </h3>
                    <div class="space-y-2">
                        <button type="button" class="preset-btn w-full" onclick="setDates('<?= $today ?>', '<?= $today ?>', this)">
                            <div class="flex justify-between items-center">
                                <span><i class="fas fa-calendar-day mr-2"></i>Today</span>
                                <span class="text-xs opacity-70"><?= date('M d', strtotime($today)) ?></span>
                            </div>
                        </button>
                        <button type="button" class="preset-btn w-full" onclick="setDates('<?= $yesterday ?>', '<?= $yesterday ?>', this)">
                            <div class="flex justify-between items-center">
                                <span><i class="fas fa-calendar mr-2"></i>Yesterday</span>
                                <span class="text-xs opacity-70"><?= date('M d', strtotime($yesterday)) ?></span>
                            </div>
                        </button>
                        <button type="button" class="preset-btn w-full" onclick="setDates('<?= $thisWeekStart ?>', '<?= $thisWeekEnd ?>', this)">
                            <div class="flex justify-between items-center">
                                <span><i class="fas fa-calendar-week mr-2"></i>This Week</span>
                                <span class="text-xs opacity-70"><?= date('M d', strtotime($thisWeekStart)) ?> - <?= date('M d', strtotime($thisWeekEnd)) ?></span>
                            </div>
                        </button>
                        <button type="button" class="preset-btn w-full" onclick="setDates('<?= $lastWeekStart ?>', '<?= $lastWeekEnd ?>', this)">
                            <div class="flex justify-between items-center">
                                <span><i class="fas fa-history mr-2"></i>Last Week</span>
                                <span class="text-xs opacity-70"><?= date('M d', strtotime($lastWeekStart)) ?> - <?= date('M d', strtotime($lastWeekEnd)) ?></span>
                            </div>
                        </button>
                        <button type="button" class="preset-btn w-full" onclick="setDates('<?= $thisMonthStart ?>', '<?= $thisMonthEnd ?>', this)">
                            <div class="flex justify-between items-center">
                                <span><i class="fas fa-calendar-alt mr-2"></i>This Month</span>
                                <span class="text-xs opacity-70"><?= date('M d', strtotime($thisMonthStart)) ?> - <?= date('M d', strtotime($thisMonthEnd)) ?></span>
                            </div>
                        </button>
                        <button type="button" class="preset-btn w-full" onclick="setDates('<?= $lastMonthStart ?>', '<?= $lastMonthEnd ?>', this)">
                            <div class="flex justify-between items-center">
                                <span><i class="fas fa-backward mr-2"></i>Last Month</span>
                                <span class="text-xs opacity-70"><?= date('M d', strtotime($lastMonthStart)) ?> - <?= date('M d', strtotime($lastMonthEnd)) ?></span>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Custom Date Range -->
            <div class="lg:col-span-2">
                <div class="selector-card h-full">
                    <h3 class="text-lg font-semibold text-orange-400 mb-4">
                        <i class="fas fa-sliders-h mr-2"></i>Custom Date Range
                    </h3>
                    
                    <form method="POST" action="">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">
                                    <i class="fas fa-play-circle mr-2 text-green-400"></i>Start Date
                                </label>
                                <input type="date" name="start_date" id="start_date" class="date-input" 
                                       value="<?= $today ?>" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">
                                    <i class="fas fa-stop-circle mr-2 text-red-400"></i>End Date
                                </label>
                                <input type="date" name="end_date" id="end_date" class="date-input" 
                                       value="<?= $today ?>" required>
                            </div>
                        </div>

                        <!-- Date Preview -->
                        <div class="date-preview">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-sm text-gray-400 mb-1">Report Period</div>
                                    <div class="text-lg font-semibold text-orange-400" id="datePreview">
                                        <?= date('F d, Y', strtotime($today)) ?> - <?= date('F d, Y', strtotime($today)) ?>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm text-gray-400 mb-1">Duration</div>
                                    <div class="text-lg font-semibold text-white" id="durationPreview">1 day</div>
                                </div>
                            </div>
                        </div>

                        <!-- Info Box -->
                        <div class="mt-6 bg-blue-500/10 border border-blue-500/30 rounded-lg p-4">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-info-circle text-blue-400 mt-1"></i>
                                <div class="text-sm text-blue-200">
                                    <strong>Report Contents:</strong> The generated report will include Executive Summary, 
                                    Workforce Analytics, Operational Efficiency metrics, Anomaly Detection, and 
                                    Strategic Recommendations for the selected period.
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-6 flex flex-col sm:flex-row gap-4">
                            <a href="audit.php" class="back-btn">
                                <i class="fas fa-arrow-left"></i>
                                Back to Audit
                            </a>
                            <button type="submit" class="generate-btn">
                                <i class="fas fa-file-pdf"></i>
                                Generate Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Recent Reports -->
        <div class="mt-8">
            <h3 class="text-lg font-semibold text-gray-300 mb-4">
                <i class="fas fa-history mr-2"></i>Report Guidelines
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="selector-card py-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 rounded-full bg-green-500/20 flex items-center justify-center">
                            <i class="fas fa-clock text-green-400"></i>
                        </div>
                        <h4 class="font-medium text-white">Daily Reports</h4>
                    </div>
                    <p class="text-sm text-gray-400">Best for operational snapshots and daily standup reviews. Recommended for field supervisors.</p>
                </div>
                <div class="selector-card py-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 rounded-full bg-blue-500/20 flex items-center justify-center">
                            <i class="fas fa-calendar-week text-blue-400"></i>
                        </div>
                        <h4 class="font-medium text-white">Weekly Reports</h4>
                    </div>
                    <p class="text-sm text-gray-400">Standard reporting period for payroll verification and workforce planning.</p>
                </div>
                <div class="selector-card py-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 rounded-full bg-purple-500/20 flex items-center justify-center">
                            <i class="fas fa-chart-line text-purple-400"></i>
                        </div>
                        <h4 class="font-medium text-white">Monthly Reports</h4>
                    </div>
                    <p class="text-sm text-gray-400">Comprehensive analysis for board presentations and strategic planning.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function setDates(start, end, btnElement) {
            document.getElementById('start_date').value = start;
            document.getElementById('end_date').value = end;
            
            // Remove active class from all preset buttons
            document.querySelectorAll('.preset-btn').forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked button
            btnElement.classList.add('active');
            
            updatePreview();
        }

        function updatePreview() {
            const start = new Date(document.getElementById('start_date').value);
            const end = new Date(document.getElementById('end_date').value);
            
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('datePreview').textContent = 
                start.toLocaleDateString('en-US', options) + ' - ' + end.toLocaleDateString('en-US', options);
            
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            
            document.getElementById('durationPreview').textContent = diffDays + ' day' + (diffDays > 1 ? 's' : '');
        }

        // Update preview when date inputs change
        document.getElementById('start_date').addEventListener('change', function() {
            document.querySelectorAll('.preset-btn').forEach(btn => btn.classList.remove('active'));
            updatePreview();
        });
        
        document.getElementById('end_date').addEventListener('change', function() {
            document.querySelectorAll('.preset-btn').forEach(btn => btn.classList.remove('active'));
            updatePreview();
        });

        // Initialize preview
        updatePreview();
    </script>
</body>
</html>
