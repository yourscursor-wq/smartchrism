<?php
// Test M-Pesa STK Push Configuration
// Access this file directly to test your M-Pesa setup

require_once('config.php');
session_start();

// Only allow admin access
if (!isset($_SESSION['admin_id'])) {
    die('Access denied. Please login as admin first.');
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>M-Pesa STK Push Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
        .section { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre { background: #fff; padding: 10px; border: 1px solid #ddd; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>M-Pesa STK Push Configuration Test</h1>
    
    <?php
    $env = strtolower(MPESA_ENV) === 'production' ? 'production' : 'sandbox';
    $baseUrl = $env === 'production' ? 'https://api.safaricom.co.ke' : 'https://sandbox.safaricom.co.ke';
    
    echo "<div class='section'>";
    echo "<h2>Configuration Check</h2>";
    echo "<p><strong>Environment:</strong> " . strtoupper($env) . "</p>";
    echo "<p><strong>Base URL:</strong> $baseUrl</p>";
    echo "<p><strong>Consumer Key:</strong> " . (empty(MPESA_CONSUMER_KEY) || MPESA_CONSUMER_KEY === 'YOUR_CONSUMER_KEY_HERE' ? '<span class="error">NOT SET</span>' : '<span class="success">SET</span>') . "</p>";
    echo "<p><strong>Consumer Secret:</strong> " . (empty(MPESA_CONSUMER_SECRET) || MPESA_CONSUMER_SECRET === 'YOUR_CONSUMER_SECRET_HERE' ? '<span class="error">NOT SET</span>' : '<span class="success">SET</span>') . "</p>";
    echo "<p><strong>Shortcode:</strong> " . (empty(MPESA_SHORTCODE) ? '<span class="error">NOT SET</span>' : MPESA_SHORTCODE) . "</p>";
    echo "<p><strong>Passkey:</strong> " . (empty(MPESA_PASSKEY) || MPESA_PASSKEY === 'YOUR_PASSKEY_HERE' ? '<span class="error">NOT SET</span>' : '<span class="success">SET</span>') . "</p>";
    echo "<p><strong>Callback URL:</strong> " . htmlspecialchars(MPESA_CALLBACK_URL) . "</p>";
    echo "</div>";
    
    // Test OAuth token
    echo "<div class='section'>";
    echo "<h2>OAuth Token Test</h2>";
    
    if (empty(MPESA_CONSUMER_KEY) || empty(MPESA_CONSUMER_SECRET) || 
        MPESA_CONSUMER_KEY === 'YOUR_CONSUMER_KEY_HERE' || 
        MPESA_CONSUMER_SECRET === 'YOUR_CONSUMER_SECRET_HERE') {
        echo "<p class='error'>Cannot test - Consumer Key or Secret not configured</p>";
    } else {
        $tokenUrl = $baseUrl . '/oauth/v1/generate?grant_type=client_credentials';
        
        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . base64_encode(MPESA_CONSUMER_KEY . ':' . MPESA_CONSUMER_SECRET)
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $tokenResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            echo "<p class='error'>cURL Error: $curlError</p>";
        } elseif ($httpCode !== 200) {
            echo "<p class='error'>HTTP Error: $httpCode</p>";
            echo "<pre>" . htmlspecialchars($tokenResponse) . "</pre>";
        } else {
            $tokenData = json_decode($tokenResponse, true);
            if (isset($tokenData['access_token'])) {
                echo "<p class='success'>✓ OAuth token obtained successfully</p>";
                echo "<p><strong>Token (first 20 chars):</strong> " . substr($tokenData['access_token'], 0, 20) . "...</p>";
            } else {
                echo "<p class='error'>Failed to get access token</p>";
                echo "<pre>" . htmlspecialchars($tokenResponse) . "</pre>";
            }
        }
    }
    echo "</div>";
    
    // Test callback URL accessibility
    echo "<div class='section'>";
    echo "<h2>Callback URL Test</h2>";
    $callbackUrl = MPESA_CALLBACK_URL;
    if (strpos($callbackUrl, 'example.com') !== false || strpos($callbackUrl, 'localhost') !== false) {
        echo "<p class='warning'>⚠ Callback URL appears to be a placeholder. For production, use a publicly accessible HTTPS URL.</p>";
        echo "<p>For local testing, use ngrok: <code>ngrok http 80</code> then update MPESA_CALLBACK_URL</p>";
    } else {
        echo "<p class='success'>Callback URL configured: $callbackUrl</p>";
    }
    echo "</div>";
    
    // Instructions
    echo "<div class='section'>";
    echo "<h2>Common Issues & Solutions</h2>";
    echo "<ul>";
    echo "<li><strong>Invalid credentials:</strong> Check your Consumer Key and Secret at <a href='https://developer.safaricom.co.ke/' target='_blank'>developer.safaricom.co.ke</a></li>";
    echo "<li><strong>Invalid shortcode:</strong> Use 600000 for sandbox, your actual Paybill for production</li>";
    echo "<li><strong>Invalid passkey:</strong> Get your Lipa na M-Pesa Online Passkey from Daraja portal</li>";
    echo "<li><strong>Callback URL not accessible:</strong> Use ngrok for local testing or deploy to a public server</li>";
    echo "<li><strong>Phone number not receiving prompt:</strong> Ensure phone is registered with M-Pesa and use sandbox test numbers for testing</li>";
    echo "</ul>";
    echo "</div>";
    ?>
    
    <p><a href="dashboard.php">← Back to Dashboard</a></p>
</body>
</html>

