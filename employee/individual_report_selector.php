<?php
/**
 * Individual Employee Report Selector
 * Allows selecting an employee and date range for individual attendance report
 */

require_once __DIR__ . '/../conn/db_connection.php';
require_once __DIR__ . '/../functions.php';
session_start();

// Check authentication - Admin, Super Admin, or Developer
if (empty($_SESSION['logged_in']) || !in_array($_SESSION['position'], ['Admin', 'Super Admin', 'Developer'])) {
    header('Location: ../login.php');
    exit;
}

// Predefined date ranges
$today = date('Y-m-d');
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
    $employeeId = $_POST['employee_id'] ?? '';
    
    // Validate
    if (!$employeeId) {
        $error = "Please select an employee.";
    } elseif ($startDate && $endDate && $startDate <= $endDate) {
        header("Location: export_individual_excel.php?start_date=$startDate&end_date=$endDate&employee_id=$employeeId");
        exit;
    } else {
        $error = "Please select a valid date range.";
    }
}

// Fetch all active employees for dropdown
$employeesQuery = "SELECT id, first_name, last_name, employee_code, position 
                  FROM employees 
                  ORDER BY last_name, first_name";
$employeesResult = mysqli_query($db, $employeesQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Individual Employee Report</title>
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
        .date-input, .employee-select {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 165, 0, 0.3);
            border-radius: 8px;
            color: white;
            padding: 12px 16px;
            width: 100%;
            font-size: 16px;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px;
            padding-right: 40px;
        }
        .employee-select option {
            background: #1a1a1a;
            color: white;
            padding: 8px;
        }
        .employee-select option:hover,
        .employee-select option:focus,
        .employee-select option:active {
            background: #2a2a2a;
        }
        .date-input:focus, .employee-select:focus {
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
            background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
            border: none;
            border-radius: 8px;
            color: #ffffff;
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
            box-shadow: 0 4px 20px rgba(59, 130, 246, 0.4);
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
        .date-preview {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
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
            <h1 class="text-3xl font-bold text-white mb-2">Individual Employee Report</h1>
            <p class="text-gray-400">Generate attendance report for a specific employee</p>
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
                    <h3 class="text-lg font-semibold text-blue-400 mb-4">
                        <i class="fas fa-bolt mr-2"></i>Quick Select
                    </h3>
                    <div class="space-y-2">
                        <button type="button" class="preset-btn w-full" onclick="setDates('<?= $today ?>', '<?= $today ?>', this)">
                            <div class="flex justify-between items-center">
                                <span><i class="fas fa-calendar-day mr-2"></i>Today</span>
                                <span class="text-xs opacity-70"><?= date('M d', strtotime($today)) ?></span>
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

            <!-- Report Configuration -->
            <div class="lg:col-span-2">
                <div class="selector-card h-full">
                    <h3 class="text-lg font-semibold text-blue-400 mb-4">
                        <i class="fas fa-user mr-2"></i>Employee & Date Range
                    </h3>
                    
                    <form method="POST" action="">
                        <!-- Employee Selection -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                <i class="fas fa-user-circle mr-2 text-blue-400"></i>Select Employee *
                            </label>
                            <select name="employee_id" id="employee_id" class="employee-select" required>
                                <option value="">-- Choose an Employee --</option>
                                <?php while ($emp = mysqli_fetch_assoc($employeesResult)): ?>
                                <option value="<?= $emp['id'] ?>">
                                    <?= htmlspecialchars($emp['last_name'] . ', ' . $emp['first_name']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">
                                    <i class="fas fa-play-circle mr-2 text-green-400"></i>Start Date *
                                </label>
                                <input type="date" name="start_date" id="start_date" class="date-input" 
                                       value="<?= $today ?>" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">
                                    <i class="fas fa-stop-circle mr-2 text-red-400"></i>End Date *
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
                                    <div class="text-lg font-semibold text-blue-400" id="datePreview">
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
                                    <strong>Report Contents:</strong> Individual attendance report includes employee details,
                                    summary statistics (total hours, days present/absent), and daily breakdown with
                                    time in/out, hours worked, status, and branch location.
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
                                <i class="fas fa-file-excel"></i>
                                Generate Excel Report
                            </button>
                        </div>
                    </form>
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
