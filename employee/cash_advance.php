<?php
// employee/cash_advance.php - Cash Advance Ledger
require_once __DIR__ . '/../conn/db_connection.php';
require_once __DIR__ . '/../functions.php';
session_start();

// Check if user is logged in
if (empty($_SESSION['logged_in'])) {
    header('Location: ../login.php');
    exit;
}

$employeeId = $_SESSION['employee_id'] ?? null;
$employeeName = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
$position = $_SESSION['position'] ?? 'Employee';
$isAdmin = in_array($position, ['Admin', 'Super Admin']);

// Handle AJAX add transaction request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_transaction_ajax'])) {
    header('Content-Type: application/json');
    
    $empId = intval($_POST['employee_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $particular = $_POST['particular'] ?? 'Cash Advance';
    $reason = trim($_POST['reason'] ?? '');
    
    if ($empId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid employee']);
        exit;
    }
    
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid amount']);
        exit;
    }
    
    $query = "INSERT INTO cash_advances (employee_id, amount, particular, reason, status, request_date) VALUES (?, ?, ?, ?, 'Approved', NOW())";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, 'idss', $empId, $amount, $particular, $reason);
    
    if (mysqli_stmt_execute($stmt)) {
        $newId = mysqli_insert_id($db);
        echo json_encode(['success' => true, 'id' => $newId]);
        logActivity($db, 'Cash Advance Added', "Added {$particular} of ₱{$amount} for employee #{$empId}");
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add transaction']);
    }
    mysqli_stmt_close($stmt);
    exit;
}

// Handle AJAX update requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_update'])) {
    header('Content-Type: application/json');
    
    $transId = intval($_POST['transaction_id'] ?? 0);
    $field = $_POST['field'] ?? '';
    $value = $_POST['value'] ?? '';
    
    if ($transId <= 0 || !in_array($field, ['particular', 'amount'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }
    
    // Sanitize and validate
    if ($field === 'amount') {
        $value = floatval($value);
        if ($value <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid amount']);
            exit;
        }
    } else {
        $value = in_array($value, ['Cash Advance', 'Payment']) ? $value : 'Cash Advance';
    }
    
    $query = "UPDATE cash_advances SET {$field} = ? WHERE id = ?";
    $stmt = mysqli_prepare($db, $query);
    
    if ($field === 'amount') {
        mysqli_stmt_bind_param($stmt, 'di', $value, $transId);
    } else {
        mysqli_stmt_bind_param($stmt, 'si', $value, $transId);
    }
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
        logActivity($db, 'Cash Advance Updated', "Updated {$field} for transaction #{$transId}");
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed']);
    }
    mysqli_stmt_close($stmt);
    exit;
}

// Fetch all employees
$employees = [];
if ($isAdmin) {
    $empQuery = "SELECT id, first_name, last_name, employee_code FROM employees WHERE status = 'active' ORDER BY last_name, first_name";
    $empResult = mysqli_query($db, $empQuery);
    while ($row = mysqli_fetch_assoc($empResult)) {
        $employees[] = $row;
    }
}

// Fetch cash advance records with running balance calculation
$transactions = [];
$employeeList = [];

// First, get all employees and calculate their balances
if ($isAdmin) {
    $empQuery = "SELECT id, first_name, last_name, employee_code, daily_rate, position 
                 FROM employees 
                 WHERE status = 'active' 
                 ORDER BY last_name, first_name";
    $empResult = mysqli_query($db, $empQuery);
} else {
    $empQuery = "SELECT id, first_name, last_name, employee_code, daily_rate, position 
                 FROM employees 
                 WHERE id = ? AND status = 'active'";
    $stmt = mysqli_prepare($db, $empQuery);
    mysqli_stmt_bind_param($stmt, 'i', $employeeId);
    mysqli_stmt_execute($stmt);
    $empResult = mysqli_stmt_get_result($stmt);
}

// Calculate balances for each employee
while ($emp = mysqli_fetch_assoc($empResult)) {
    $empId = $emp['id'];
    
    // Get all transactions for this employee (removed status filter for debugging)
    $caQuery = "SELECT * FROM cash_advances 
                WHERE employee_id = ?
                ORDER BY request_date ASC";
    $caStmt = mysqli_prepare($db, $caQuery);
    mysqli_stmt_bind_param($caStmt, 'i', $empId);
    mysqli_stmt_execute($caStmt);
    $caResult = mysqli_stmt_get_result($caStmt);
    
    $balance = 0;
    $totalCA = 0;
    $totalPaid = 0;
    $lastTransaction = null;
    $transactionCount = 0;
    
    while ($ca = mysqli_fetch_assoc($caResult)) {
        if ($ca['particular'] === 'Payment') {
            $balance -= $ca['amount'];
            $totalPaid += $ca['amount'];
        } else {
            $balance += $ca['amount'];
            $totalCA += $ca['amount'];
        }
        $lastTransaction = $ca;
        $transactionCount++;
    }
    
    $emp['balance'] = $balance;
    $emp['total_ca'] = $totalCA;
    $emp['total_paid'] = $totalPaid;
    $emp['transaction_count'] = $transactionCount;
    $emp['last_transaction'] = $lastTransaction;
    $emp['last_date'] = $lastTransaction ? $lastTransaction['request_date'] : null;
    
    $employeeList[] = $emp;
    mysqli_stmt_close($caStmt);
}

// Calculate totals
$totalEmployees = count($employeeList);
$totalWithBalance = 0;
$totalOutstanding = 0;
$totalCA = 0;
$totalPaid = 0;

foreach ($employeeList as $emp) {
    if ($emp['balance'] > 0) {
        $totalWithBalance++;
    }
    $totalOutstanding += $emp['balance'];
    $totalCA += $emp['total_ca'];
    $totalPaid += $emp['total_paid'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash Advance - JAJR Company</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="css/select_employee.css">
    <link rel="icon" type="image/x-icon" href="../assets/img/profile/jajr-logo.png">
    <style>
        .cash-advance-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .stat-box {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
        }
        
        .stat-box h4 {
            color: #888;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        
        .stat-box .amount {
            color: #FFD700;
            font-size: 24px;
            font-weight: 700;
        }
        
        .request-form {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-group label {
            display: block;
            color: #888;
            font-size: 13px;
            margin-bottom: 6px;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            background: #2a2a2a;
            border: 1px solid #444;
            border-radius: 6px;
            padding: 10px 12px;
            color: #fff;
            font-size: 14px;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #FFD700;
        }
        
        .btn-submit {
            background: #FFD700;
            color: #000;
            border: none;
            padding: 10px 24px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            background: linear-gradient(to right, #FFD700, #FFA500);
            color: #000;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
        }
        
        .data-table td {
            padding: 12px;
            border-bottom: 1px solid #333;
            color: #e8e8e8;
            font-size: 13px;
        }
        
        .data-table tr:hover {
            background: rgba(255, 215, 0, 0.05);
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .status-pending {
            background: rgba(255, 193, 7, 0.2);
            color: #FFC107;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }
        
        .status-approved {
            background: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
            border: 1px solid rgba(76, 175, 80, 0.3);
        }
        
        .status-paid {
            background: rgba(33, 150, 243, 0.2);
            color: #2196F3;
            border: 1px solid rgba(33, 150, 243, 0.3);
        }
        
        .status-rejected {
            background: rgba(244, 67, 54, 0.2);
            color: #F44336;
            border: 1px solid rgba(244, 67, 54, 0.3);
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 16px;
        }
        
        .alert-success {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid #4CAF50;
            color: #4CAF50;
        }
        
        .alert-error {
            background: rgba(244, 67, 54, 0.2);
            border: 1px solid #F44336;
            color: #F44336;
        }
        
        .view-tabs { display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 1px solid #333; padding-bottom: 10px; }
        .view-tab { background: #2a2a2a; border: 1px solid #444; color: #888; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.3s ease; }
        .view-tab:hover { background: #333; color: #fff; }
        .view-tab.active { background: #FFD700; color: #000; border-color: #FFD700; }
        .view-content { display: none; }
        .view-content.active { display: block; }
    </style>
</head>
<body>
    <div class="app-shell">
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="header-card">
                <div class="header-left">
                    <div>
                        <div class="welcome">Cash Advance</div>
                        <div class="text-sm text-gray">Request and track cash advances</div>
                    </div>
                </div>
                <div class="text-sm text-gray">
                    Today: <?php echo date('F d, Y'); ?>
                </div>
            </div>
            
            <div class="cash-advance-container">
                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-box">
                        <h4>Total Employees</h4>
                        <div class="amount"><?php echo $totalEmployees; ?></div>
                    </div>
                    <div class="stat-box">
                        <h4>With Outstanding Balance</h4>
                        <div class="amount"><?php echo $totalWithBalance; ?></div>
                    </div>
                    <div class="stat-box">
                        <h4>Total Cash Advance</h4>
                        <div class="amount">₱<?php echo number_format($totalCA, 2); ?></div>
                    </div>
                    <div class="stat-box">
                        <h4>Total Paid</h4>
                        <div class="amount" style="color: #4CAF50;">₱<?php echo number_format($totalPaid, 2); ?></div>
                    </div>
                    <div class="stat-box">
                        <h4>Outstanding Balance</h4>
                        <div class="amount">₱<?php echo number_format($totalOutstanding, 2); ?></div>
                    </div>
                </div>
                
                <!-- View Tabs -->
                <div class="view-tabs">
                    <button class="view-tab active" onclick="switchView('employee')" id="tab-employee">
                        <i class="fas fa-users mr-2"></i>Employee View
                    </button>
                </div>
                
                <!-- Employee View -->
                <div id="view-employee" class="view-content active">
                    <div class="report-card">
                        <h3 style="color: #FFD700; margin-bottom: 16px; font-size: 16px;">
                            <i class="fas fa-users mr-2"></i>Employee Cash Advance Summary
                        </h3>
                        
                        <div class="report-table" style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Last Date</th>
                                        <th>Employee</th>
                                        <th>Total CA</th>
                                        <th>Total Paid</th>
                                        <th>Balance</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($employeeList as $emp): ?>
                                    <tr data-emp-id="<?php echo $emp['id']; ?>">
                                        <td>
                                            <?php if ($emp['last_date']): ?>
                                                <?php echo date('M d, Y', strtotime($emp['last_date'])); ?>
                                            <?php else: ?>
                                                <span style="color: #666;">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($emp['last_name'] . ', ' . $emp['first_name']); ?></strong>
                                        </td>
                                        <td>₱<?php echo number_format($emp['total_ca'], 2); ?></td>
                                        <td style="color: #4CAF50;">₱<?php echo number_format($emp['total_paid'], 2); ?></td>
                                        <td class="balance-cell" style="color: <?php echo $emp['balance'] > 0 ? '#FFD700' : '#888'; ?>">
                                            ₱<?php echo number_format($emp['balance'], 2); ?>
                                        </td>
                                        <td>
                                            <button class="btn-action" onclick="viewEmployeeHistory(<?php echo $emp['id']; ?>, '<?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>')">
                                                <i class="fas fa-money-bill-wave mr-1"></i> Cash Advance Record
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
            </div>
        </main>
    </div>
    
    <script>
        // Tab switching (only Employee View now)
        function switchView(view) {
            // Only employee view exists now
            document.getElementById('tab-employee').classList.add('active');
            document.getElementById('view-employee').classList.add('active');
        }
        
        // Store current employee ID for adding transactions
        let currentEmployeeIdForAdd = null;
        let currentEmployeeNameForAdd = '';
        
        // Show add transaction form in modal
        function showAddTransactionForm() {
            const content = document.getElementById('modalContent');
            
            content.innerHTML = `
                <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #333;">
                    <h4 style="color: #FFD700; margin: 0 0 10px 0;">Add New Transaction</h4>
                    <p style="color: #888; margin: 0; font-size: 13px;">Employee: ${currentEmployeeNameForAdd}</p>
                </div>
                <form id="addTransactionForm" style="display: grid; gap: 16px;">
                    <div>
                        <label style="display: block; color: #888; font-size: 13px; margin-bottom: 6px;">Particular</label>
                        <select id="newParticular" style="width: 100%; background: #2a2a2a; border: 1px solid #444; border-radius: 6px; padding: 10px 12px; color: #fff; font-size: 14px;">
                            <option value="Cash Advance">Cash Advance</option>
                            <option value="Payment">Payment</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; color: #888; font-size: 13px; margin-bottom: 6px;">Amount (₱)</label>
                        <input type="number" id="newAmount" min="0.01" step="0.01" required placeholder="Enter amount" style="width: 100%; background: #2a2a2a; border: 1px solid #444; border-radius: 6px; padding: 10px 12px; color: #fff; font-size: 14px;">
                    </div>
                    <div>
                        <label style="display: block; color: #888; font-size: 13px; margin-bottom: 6px;">Notes / Reason (Optional)</label>
                        <textarea id="newReason" rows="2" placeholder="Enter notes or reason" style="width: 100%; background: #2a2a2a; border: 1px solid #444; border-radius: 6px; padding: 10px 12px; color: #fff; font-size: 14px;"></textarea>
                    </div>
                    <div style="display: flex; gap: 12px; margin-top: 10px;">
                        <button type="button" onclick="saveNewTransaction()" class="btn-primary" style="flex: 1;">
                            <i class="fas fa-save mr-2"></i>Save Transaction
                        </button>
                        <button type="button" onclick="reloadEmployeeHistory()" class="btn-secondary">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </button>
                    </div>
                </form>
            `;
        }
        
        // Save new transaction
        function saveNewTransaction() {
            const particular = document.getElementById('newParticular').value;
            const amount = parseFloat(document.getElementById('newAmount').value);
            const reason = document.getElementById('newReason').value;
            
            if (!amount || amount <= 0) {
                alert('Please enter a valid amount');
                return;
            }
            
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'add_transaction_ajax=1&employee_id=' + currentEmployeeIdForAdd + '&particular=' + encodeURIComponent(particular) + '&amount=' + amount + '&reason=' + encodeURIComponent(reason)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Transaction added successfully!');
                    // Reload entire page to update all totals
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to add transaction'));
                }
            })
            .catch(error => {
                alert('Error adding transaction');
            });
        }
        
        // Reload employee history after adding
        function reloadEmployeeHistory() {
            viewEmployeeHistory(currentEmployeeIdForAdd, currentEmployeeNameForAdd);
        }
        
        // View employee history
        function viewEmployeeHistory(empId, empName) {
            currentEmployeeIdForAdd = empId;
            currentEmployeeNameForAdd = empName;
            
            fetch('api/get_employee_ca.php?emp_id=' + empId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showEmployeeHistoryModal(data.employee, data.transactions);
                    } else {
                        alert('Error loading history');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading history');
                });
        }
        
        // Show employee history modal with 4 columns
        function showEmployeeHistoryModal(employee, transactions) {
            const modal = document.getElementById('transactionModal');
            const content = document.getElementById('modalContent');
            
            let transactionsHtml = '';
            let runningBalance = 0;
            
            // Calculate from oldest to newest
            const sorted = [...transactions].reverse();
            sorted.forEach(t => {
                if (t.particular === 'Payment') {
                    runningBalance -= parseFloat(t.amount);
                } else {
                    runningBalance += parseFloat(t.amount);
                }
                
                const particularClass = t.particular === 'Cash Advance' ? 'particular-ca' : 'particular-payment';
                
                transactionsHtml += `
                    <tr data-trans-id="${t.id}">
                        <td>${new Date(t.request_date).toLocaleDateString()}</td>
                        <td>
                            <select class="editable-particular" onchange="updateModalTransaction(${t.id}, 'particular', this.value)">
                                <option value="Cash Advance" ${t.particular === 'Cash Advance' ? 'selected' : ''}>Cash Advance</option>
                                <option value="Payment" ${t.particular === 'Payment' ? 'selected' : ''}>Payment</option>
                            </select>
                        </td>
                        <td>
                            <input type="number" class="editable-amount" value="${t.amount}" min="0.01" step="0.01" onchange="updateModalTransaction(${t.id}, 'amount', this.value)">
                        </td>
                        <td style="color: #FFD700; font-weight: 700; text-align: right;">₱${runningBalance.toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                    </tr>
                `;
            });
            
            content.innerHTML = `
                <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #333;">
                    <h4 style="color: #FFD700; margin: 0 0 10px 0;">${employee.last_name}, ${employee.first_name}</h4>
                    <p style="color: #888; margin: 0; font-size: 13px;">Code: ${employee.employee_code}</p>
                </div>
                <div style="max-height: 400px; overflow-y: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #2a2a2a;">
                                <th style="padding: 10px; text-align: left; font-size: 12px; color: #FFD700;">Date</th>
                                <th style="padding: 10px; text-align: left; font-size: 12px; color: #FFD700;">Particular</th>
                                <th style="padding: 10px; text-align: right; font-size: 12px; color: #FFD700;">Amount</th>
                                <th style="padding: 10px; text-align: right; font-size: 12px; color: #FFD700;">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${transactionsHtml || '<tr><td colspan="4" style="text-align: center; padding: 20px; color: #666;">No transactions found</td></tr>'}
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #333; display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: #888;">Current Balance:</span>
                    <span style="color: #FFD700; font-size: 24px; font-weight: 700;">₱${parseFloat(employee.balance).toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                </div>
            `;
            
            modal.style.display = 'flex';
        }
        
        // Update transaction from modal
        function updateModalTransaction(id, field, value) {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'ajax_update=1&transaction_id=' + id + '&field=' + field + '&value=' + encodeURIComponent(value)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the page to update all balances
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to update'));
                }
            })
            .catch(error => {
                alert('Error updating value');
            });
        }
        
        function closeModal() {
            document.getElementById('transactionModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('transactionModal');
            if (e.target === modal) {
                closeModal();
            }
        });
    </script>
    
    <!-- Employee History Modal -->
    <div id="transactionModal" class="modal-backdrop" style="display: none;">
        <div class="modal-panel" style="max-width: 700px;">
            <div class="modal-header">
                <h3 class="text-yellow-400">
                    <i class="fas fa-history mr-2"></i>Cash Advance History
                </h3>
                <button type="button" onclick="closeModal()" class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="modalContent">
                <!-- Content will be dynamically inserted -->
            </div>
            <div class="modal-footer">
                <button type="button" onclick="showAddTransactionForm()" class="btn-primary">
                    <i class="fas fa-plus mr-2"></i>Add Transaction
                </button>
                <button type="button" onclick="closeModal()" class="btn-secondary">Close</button>
            </div>
        </div>
    </div>
</body>
</html>
<?php
// Create cash_advances table if it doesn't exist
$tableCheck = mysqli_query($db, "SHOW TABLES LIKE 'cash_advances'");
if (mysqli_num_rows($tableCheck) == 0) {
    $createTable = "CREATE TABLE cash_advances (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        particular VARCHAR(50) DEFAULT 'Cash Advance',
        reason TEXT,
        status ENUM('Pending', 'Approved', 'Paid', 'Rejected') DEFAULT 'Pending',
        request_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        approved_date DATETIME NULL,
        paid_date DATETIME NULL,
        approved_by INT NULL,
        FOREIGN KEY (employee_id) REFERENCES employees(id)
    )";
    mysqli_query($db, $createTable);
}
?>
