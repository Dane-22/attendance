<?php
// test_push_notification.php - Test Web Push Notification System
// Place this file in the project root and access via browser as Super Admin

// Suppress PHP errors to prevent HTML in JSON response
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

require_once __DIR__ . '/conn/db_connection.php';
require_once __DIR__ . '/functions.php';

session_start();

// Check Super Admin access
if (empty($_SESSION['logged_in']) || $_SESSION['position'] !== 'Super Admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Super Admin access required']);
    exit;
}

// Check if VAPID keys are configured
$vapidPublicKey = getenv('VAPID_PUBLIC_KEY');
$vapidPrivateKey = getenv('VAPID_PRIVATE_KEY');

if (!$vapidPublicKey || !$vapidPrivateKey) {
    echo json_encode([
        'error' => 'VAPID keys not configured',
        'setup_instructions' => 'Add VAPID_PUBLIC_KEY and VAPID_PRIVATE_KEY to your .env file'
    ]);
    exit;
}

// Get current user's subscriptions
$userId = $_SESSION['employee_id'];
$sql = "SELECT COUNT(*) as count FROM push_subscriptions WHERE user_id = ?";
$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$subscriptionCount = mysqli_fetch_assoc($result)['count'];
mysqli_stmt_close($stmt);

// Handle test notification request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_test') {
    $title = $_POST['title'] ?? 'Test Notification';
    $message = $_POST['message'] ?? 'This is a test from JAJR Dashboard';
    
    $result = sendPushNotification(
        $db,
        $userId,
        $title,
        $message,
        '/employee/dashboard.php'
    );
    
    echo json_encode($result);
    exit;
}

// Display test interface
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Push Notification Test - JAJR</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-page: #0b0b0b;
            --card-bg: #161616;
            --gold-1: #FFD66B;
            --gold-2: #D4AF37;
            --text-white: #ffffff;
        }
        body {
            background: var(--bg-page);
            color: var(--text-white);
            font-family: 'Inter', system-ui, sans-serif;
            padding: 2rem;
            max-width: 800px;
            margin: 0 auto;
        }
        h1 {
            background: linear-gradient(90deg, var(--gold-1), var(--gold-2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }
        .subtitle {
            color: #9CA3AF;
            margin-bottom: 2rem;
        }
        .card {
            background: var(--card-bg);
            border: 1px solid rgba(255, 165, 0, 0.3);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .card h2 {
            color: var(--gold-2);
            margin-top: 0;
            font-size: 1.2rem;
        }
        .status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .status.ok {
            background: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
        }
        .status.warning {
            background: rgba(255, 165, 0, 0.2);
            color: #FFA500;
        }
        .status.error {
            background: rgba(244, 67, 54, 0.2);
            color: #F44336;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #D1D5DB;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 165, 0, 0.3);
            border-radius: 8px;
            color: white;
            font-family: inherit;
        }
        textarea {
            min-height: 80px;
            resize: vertical;
        }
        button {
            background: linear-gradient(135deg, var(--gold-2) 0%, var(--gold-1) 100%);
            border: none;
            border-radius: 8px;
            color: #0b0b0b;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            cursor: pointer;
            font-size: 1rem;
        }
        button:hover {
            opacity: 0.9;
        }
        pre {
            background: rgba(0, 0, 0, 0.3);
            padding: 1rem;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 0.85rem;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        .info-item {
            background: rgba(255, 255, 255, 0.03);
            padding: 1rem;
            border-radius: 8px;
        }
        .info-label {
            color: #9CA3AF;
            font-size: 0.85rem;
        }
        .info-value {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--gold-1);
        }
        #result {
            display: none;
        }
    </style>
</head>
<body>
    <h1>🔔 Push Notification Test</h1>
    <p class="subtitle">Test the Web Push Notification system for Super Admin alerts</p>

    <div class="card">
        <h2>System Status</h2>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">VAPID Keys</div>
                <div class="status <?php echo ($vapidPublicKey && $vapidPrivateKey) ? 'ok' : 'error'; ?>">
                    <?php echo ($vapidPublicKey && $vapidPrivateKey) ? '✓ Configured' : '✗ Missing'; ?>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Active Subscriptions</div>
                <div class="info-value"><?php echo $subscriptionCount; ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">User ID</div>
                <div class="info-value"><?php echo $userId; ?></div>
            </div>
        </div>
    </div>

    <?php if ($subscriptionCount === 0): ?>
    <div class="card">
        <h2>⚠️ No Subscriptions Found</h2>
        <p>You need to enable push notifications first:</p>
        <ol>
            <li>Go to <a href="employee/audit.php" style="color: var(--gold-1);">Attendance Audit</a></li>
            <li>Click "Enable Notifications" in the widget at bottom-right</li>
            <li>Allow browser permission when prompted</li>
            <li>Return to this page and refresh</li>
        </ol>
    </div>
    <?php endif; ?>

    <?php if ($subscriptionCount > 0): ?>
    <div class="card">
        <h2>Send Test Notification</h2>
        <form id="testForm">
            <div class="form-group">
                <label for="title">Notification Title</label>
                <input type="text" id="title" name="title" value="Test Notification" required>
            </div>
            <div class="form-group">
                <label for="message">Notification Message</label>
                <textarea id="message" name="message" required>This is a test notification from JAJR Dashboard</textarea>
            </div>
            <button type="submit">Send Test Notification</button>
        </form>
    </div>

    <div class="card" id="result">
        <h2>Result</h2>
        <pre id="resultContent"></pre>
    </div>
    <?php endif; ?>

    <div class="card">
        <h2>VAPID Public Key</h2>
        <p style="font-size: 0.85rem; color: #9CA3AF;">Use this key when configuring the frontend:</p>
        <pre style="word-break: break-all;"><?php echo htmlspecialchars($vapidPublicKey ?? 'Not configured'); ?></pre>
    </div>

    <script>
        document.getElementById('testForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('action', 'send_test');
            formData.append('title', document.getElementById('title').value);
            formData.append('message', document.getElementById('message').value);
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const responseText = await response.text();
                let result;
                
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    // If not valid JSON, show the raw response
                    document.getElementById('result').style.display = 'block';
                    document.getElementById('resultContent').textContent = 'Server returned non-JSON response:\n' + responseText.substring(0, 500);
                    alert('Server error - check console for details');
                    return;
                }
                
                document.getElementById('result').style.display = 'block';
                document.getElementById('resultContent').textContent = JSON.stringify(result, null, 2);
                
                if (result.success) {
                    alert('Notification sent successfully! Check your device.');
                } else {
                    alert('Failed to send: ' + (result.errors?.[0] || result.error || 'Unknown error'));
                }
            } catch (error) {
                alert('Network Error: ' + error.message);
            }
        });
    </script>
</body>
</html>
