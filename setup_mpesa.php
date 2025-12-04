<?php
// M-Pesa Setup Guide and Configuration Helper
require_once('config.php');
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$currentConfig = [
    'env' => MPESA_ENV,
    'consumer_key' => MPESA_CONSUMER_KEY,
    'consumer_secret' => MPESA_CONSUMER_SECRET,
    'shortcode' => MPESA_SHORTCODE,
    'passkey' => MPESA_PASSKEY,
    'callback_url' => MPESA_CALLBACK_URL
];

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_config'])) {
    // This is just a guide - actual config should be edited in config.php
    $message = 'Please edit config.php directly to update M-Pesa credentials. See instructions below.';
    $messageType = 'info';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M-Pesa Setup Guide - Smart Chrism Shop</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .setup-container { max-width: 900px; margin: 20px auto; padding: 20px; }
        .config-section { background: #f8f9fa; padding: 20px; margin: 15px 0; border-radius: 8px; border-left: 4px solid #007bff; }
        .config-section h3 { margin-top: 0; color: #007bff; }
        .code-block { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 5px; overflow-x: auto; font-family: 'Courier New', monospace; margin: 10px 0; }
        .step { margin: 20px 0; padding: 15px; background: white; border-radius: 5px; }
        .step-number { display: inline-block; width: 30px; height: 30px; background: #007bff; color: white; border-radius: 50%; text-align: center; line-height: 30px; margin-right: 10px; font-weight: bold; }
        .current-config { background: #fff3cd; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #ffc107; }
        .current-config strong { display: inline-block; width: 150px; }
        .btn-setup { background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 10px 5px; }
        .btn-setup:hover { background: #218838; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 15px 0; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 15px 0; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 15px 0; }
    </style>
</head>
<body class="admin-body">
    <div class="sidebar">
        <h2>Smart Chrism</h2>
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="setup_mpesa.php" class="active">M-Pesa Setup</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>

    <div class="content setup-container">
        <h1>M-Pesa STK Push Setup Guide</h1>
        
        <?php if ($message): ?>
            <div class="<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="current-config">
            <h3>Current Configuration Status</h3>
            <p><strong>Environment:</strong> <?= strtoupper($currentConfig['env']) ?></p>
            <p><strong>Consumer Key:</strong> <?= ($currentConfig['consumer_key'] === 'YOUR_CONSUMER_KEY_HERE' || empty($currentConfig['consumer_key'])) ? '<span style="color:red">NOT SET</span>' : '<span style="color:green">SET (' . substr($currentConfig['consumer_key'], 0, 20) . '...)</span>' ?></p>
            <p><strong>Consumer Secret:</strong> <?= ($currentConfig['consumer_secret'] === 'YOUR_CONSUMER_SECRET_HERE' || empty($currentConfig['consumer_secret'])) ? '<span style="color:red">NOT SET</span>' : '<span style="color:green">SET</span>' ?></p>
            <p><strong>Shortcode:</strong> <?= $currentConfig['shortcode'] ?></p>
            <p><strong>Passkey:</strong> <?= ($currentConfig['passkey'] === 'YOUR_PASSKEY_HERE' || empty($currentConfig['passkey'])) ? '<span style="color:red">NOT SET</span>' : '<span style="color:green">SET</span>' ?></p>
            <p><strong>Callback URL:</strong> <?= htmlspecialchars($currentConfig['callback_url']) ?></p>
        </div>

        <div class="config-section">
            <h3>📋 Step 1: Get M-Pesa Daraja API Credentials</h3>
            <div class="step">
                <span class="step-number">1</span>
                <strong>Register/Login to Safaricom Developer Portal</strong>
                <p>Go to: <a href="https://developer.safaricom.co.ke/" target="_blank">https://developer.safaricom.co.ke/</a></p>
                <p>Create an account or login if you already have one.</p>
            </div>
            
            <div class="step">
                <span class="step-number">2</span>
                <strong>Create an App</strong>
                <p>1. Go to "My Apps" section</p>
                <p>2. Click "Create App"</p>
                <p>3. Fill in app details</p>
                <p>4. Select "Lipa na M-Pesa Online" as one of the APIs</p>
            </div>
            
            <div class="step">
                <span class="step-number">3</span>
                <strong>Get Your Credentials</strong>
                <p>After creating the app, you'll get:</p>
                <ul>
                    <li><strong>Consumer Key</strong> - Long alphanumeric string</li>
                    <li><strong>Consumer Secret</strong> - Long alphanumeric string</li>
                </ul>
            </div>
            
            <div class="step">
                <span class="step-number">4</span>
                <strong>Get Shortcode and Passkey</strong>
                <p>1. Go to "Lipa na M-Pesa Online" section</p>
                <p>2. You'll see your <strong>Shortcode</strong> (e.g., 600000 for sandbox)</p>
                <p>3. Click "Generate Password" to get your <strong>Passkey</strong></p>
                <p class="warning"><strong>Note:</strong> For sandbox testing, use Shortcode: <code>600000</code></p>
            </div>
        </div>

        <div class="config-section">
            <h3>⚙️ Step 2: Configure config.php</h3>
            <p>Open <code>public/config.php</code> and find the M-Pesa configuration section (around line 106-132).</p>
            
            <p><strong>For Sandbox (Testing):</strong></p>
            <div class="code-block">
// M-Pesa Paybill configuration
define('MPESA_ENV', 'sandbox');
define('MPESA_CONSUMER_KEY', 'YOUR_CONSUMER_KEY_FROM_DARAJA');
define('MPESA_CONSUMER_SECRET', 'YOUR_CONSUMER_SECRET_FROM_DARAJA');
define('MPESA_SHORTCODE', '600000');  // Sandbox shortcode
define('MPESA_PASSKEY', 'YOUR_PASSKEY_FROM_DARAJA');
define('MPESA_CALLBACK_URL', 'https://your-ngrok-url.ngrok.io/kk/public/api/mpesa_callback.php');
            </div>

            <p><strong>For Production (Live):</strong></p>
            <div class="code-block">
// M-Pesa Paybill configuration
define('MPESA_ENV', 'production');
define('MPESA_CONSUMER_KEY', 'YOUR_PRODUCTION_CONSUMER_KEY');
define('MPESA_CONSUMER_SECRET', 'YOUR_PRODUCTION_CONSUMER_SECRET');
define('MPESA_SHORTCODE', 'YOUR_PAYBILL_NUMBER');  // Your actual Paybill
define('MPESA_PASSKEY', 'YOUR_PRODUCTION_PASSKEY');
define('MPESA_CALLBACK_URL', 'https://yourdomain.com/kk/public/api/mpesa_callback.php');
            </div>

            <div class="warning">
                <strong>⚠️ Important:</strong>
                <ul>
                    <li>Replace ALL placeholder values with your actual credentials</li>
                    <li>For local testing, use <strong>ngrok</strong> to make callback URL accessible</li>
                    <li>Never commit your credentials to public repositories</li>
                </ul>
            </div>
        </div>

        <div class="config-section">
            <h3>🔗 Step 3: Setup Callback URL (For Local Testing)</h3>
            <div class="step">
                <span class="step-number">1</span>
                <strong>Install ngrok</strong>
                <p>Download from: <a href="https://ngrok.com/download" target="_blank">https://ngrok.com/download</a></p>
            </div>
            
            <div class="step">
                <span class="step-number">2</span>
                <strong>Start ngrok</strong>
                <div class="code-block">
ngrok http 80
                </div>
                <p>This will give you a public URL like: <code>https://abc123.ngrok.io</code></p>
            </div>
            
            <div class="step">
                <span class="step-number">3</span>
                <strong>Update Callback URL in config.php</strong>
                <div class="code-block">
define('MPESA_CALLBACK_URL', 'https://abc123.ngrok.io/kk/public/api/mpesa_callback.php');
                </div>
                <p>Replace <code>abc123.ngrok.io</code> with your actual ngrok URL</p>
            </div>
        </div>

        <div class="config-section">
            <h3>✅ Step 4: Test Your Configuration</h3>
            <p>After updating config.php, test your setup:</p>
            <p>
                <a href="api/test_mpesa.php" class="btn-setup" target="_blank">Test M-Pesa Configuration</a>
                <a href="test_stk.php?phone=254708374149&amount=10" class="btn-setup" target="_blank">Test STK Push</a>
            </p>
        </div>

        <div class="config-section">
            <h3>📝 Quick Reference</h3>
            <table style="width:100%; border-collapse: collapse;">
                <tr style="background:#f0f0f0;">
                    <th style="padding:10px; text-align:left;">Setting</th>
                    <th style="padding:10px; text-align:left;">Sandbox Value</th>
                    <th style="padding:10px; text-align:left;">Where to Get</th>
                </tr>
                <tr>
                    <td style="padding:10px; border-bottom:1px solid #ddd;">MPESA_ENV</td>
                    <td style="padding:10px; border-bottom:1px solid #ddd;"><code>'sandbox'</code></td>
                    <td style="padding:10px; border-bottom:1px solid #ddd;">-</td>
                </tr>
                <tr>
                    <td style="padding:10px; border-bottom:1px solid #ddd;">MPESA_CONSUMER_KEY</td>
                    <td style="padding:10px; border-bottom:1px solid #ddd;">From Daraja Portal</td>
                    <td style="padding:10px; border-bottom:1px solid #ddd;">My Apps → Your App → Consumer Key</td>
                </tr>
                <tr>
                    <td style="padding:10px; border-bottom:1px solid #ddd;">MPESA_CONSUMER_SECRET</td>
                    <td style="padding:10px; border-bottom:1px solid #ddd;">From Daraja Portal</td>
                    <td style="padding:10px; border-bottom:1px solid #ddd;">My Apps → Your App → Consumer Secret</td>
                </tr>
                <tr>
                    <td style="padding:10px; border-bottom:1px solid #ddd;">MPESA_SHORTCODE</td>
                    <td style="padding:10px; border-bottom:1px solid #ddd;"><code>'600000'</code></td>
                    <td style="padding:10px; border-bottom:1px solid #ddd;">Lipa na M-Pesa Online → Shortcode</td>
                </tr>
                <tr>
                    <td style="padding:10px; border-bottom:1px solid #ddd;">MPESA_PASSKEY</td>
                    <td style="padding:10px; border-bottom:1px solid #ddd;">From Daraja Portal</td>
                    <td style="padding:10px; border-bottom:1px solid #ddd;">Lipa na M-Pesa Online → Generate Password</td>
                </tr>
                <tr>
                    <td style="padding:10px;">MPESA_CALLBACK_URL</td>
                    <td style="padding:10px;">ngrok URL</td>
                    <td style="padding:10px;">ngrok http 80 → Copy HTTPS URL</td>
                </tr>
            </table>
        </div>

        <div class="success">
            <h3>🎯 Next Steps</h3>
            <ol>
                <li>Get your credentials from <a href="https://developer.safaricom.co.ke/" target="_blank">Daraja Portal</a></li>
                <li>Edit <code>public/config.php</code> and replace placeholder values</li>
                <li>Setup ngrok for callback URL (if testing locally)</li>
                <li>Test using the test links above</li>
                <li>Try placing an order from the shop frontend</li>
            </ol>
        </div>

        <p style="margin-top:30px;">
            <a href="dashboard.php" class="btn-setup">← Back to Dashboard</a>
        </p>
    </div>
</body>
</html>

