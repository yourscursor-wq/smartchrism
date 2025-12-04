<?php
// Quick check - shows what error M-Pesa is returning
// Run this after a failed payment attempt

require_once('config.php');
session_start();

if (!isset($_SESSION['admin_id'])) {
    die('Admin access required');
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Check M-Pesa Error Logs</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        pre { background: #f5f5f5; padding: 15px; border: 1px solid #ddd; overflow-x: auto; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <h1>M-Pesa Error Log Checker</h1>
    
    <h2>Recent PHP Error Log Entries (M-Pesa related)</h2>
    <?php
    $errorLogPath = ini_get('error_log');
    if (empty($errorLogPath)) {
        $errorLogPath = 'C:\\xampp\\apache\\logs\\error.log';
    }
    
    if (file_exists($errorLogPath)) {
        $lines = file($errorLogPath);
        $mpesaLines = array_filter($lines, function($line) {
            return stripos($line, 'm-pesa') !== false || stripos($line, 'mpesa') !== false;
        });
        
        if (count($mpesaLines) > 0) {
            echo "<p>Found " . count($mpesaLines) . " M-Pesa related log entries (showing last 50):</p>";
            echo "<pre>";
            echo htmlspecialchars(implode('', array_slice($mpesaLines, -50)));
            echo "</pre>";
        } else {
            echo "<p class='error'>No M-Pesa entries found in error log.</p>";
            echo "<p>Error log location: $errorLogPath</p>";
        }
    } else {
        echo "<p class='error'>Error log file not found at: $errorLogPath</p>";
        echo "<p>Check your php.ini error_log setting.</p>";
    }
    ?>
    
    <hr>
    
    <h2>Test STK Push Directly</h2>
    <p><a href="test_stk.php?phone=254708374149&amount=10" target="_blank">Test STK Push (Opens in new tab)</a></p>
    
    <hr>
    
    <h2>Configuration Check</h2>
    <p><strong>Environment:</strong> <?= strtoupper(MPESA_ENV) ?></p>
    <p><strong>Shortcode:</strong> <?= MPESA_SHORTCODE ?></p>
    <p><strong>Consumer Key:</strong> <?= substr(MPESA_CONSUMER_KEY, 0, 20) ?>...</p>
    <p><strong>Passkey Set:</strong> <?= (MPESA_PASSKEY !== 'YOUR_PASSKEY_HERE' && !empty(MPESA_PASSKEY)) ? '<span class="success">Yes</span>' : '<span class="error">No</span>' ?></p>
    <p><strong>Callback URL:</strong> <?= MPESA_CALLBACK_URL ?></p>
    
    <hr>
    <p><a href="dashboard.php">← Back to Dashboard</a></p>
</body>
</html>

