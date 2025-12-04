<?php
// Simple STK Push Test - Run this directly in browser
// Usage: http://localhost/kk/public/test_stk.php?phone=0712345678&amount=10

require_once('config.php');

$phone = $_GET['phone'] ?? '254708374149'; // Default sandbox test number
$amount = floatval($_GET['amount'] ?? 10);

// Normalize phone
$phoneDigits = preg_replace('/\D+/', '', $phone);
if (strpos($phoneDigits, '0') === 0 && strlen($phoneDigits) === 10) {
    $phoneDigits = '254' . substr($phoneDigits, 1);
} elseif (strpos($phoneDigits, '254') !== 0 && strlen($phoneDigits) === 9) {
    $phoneDigits = '254' . $phoneDigits;
}

$amount = max(1, (int)round($amount));

echo "<h2>M-Pesa STK Push Test</h2>";
echo "<p><strong>Phone:</strong> $phoneDigits</p>";
echo "<p><strong>Amount:</strong> $amount KSh</p>";
echo "<hr>";

// Check config
if (MPESA_CONSUMER_KEY === 'YOUR_CONSUMER_KEY_HERE' || MPESA_CONSUMER_SECRET === 'YOUR_CONSUMER_SECRET_HERE') {
    die("<p style='color:red'>ERROR: M-Pesa credentials not configured in config.php</p>");
}

$env = strtolower(MPESA_ENV) === 'production' ? 'production' : 'sandbox';
$baseUrl = $env === 'production' ? 'https://api.safaricom.co.ke' : 'https://sandbox.safaricom.co.ke';

echo "<p><strong>Environment:</strong> $env</p>";
echo "<p><strong>Base URL:</strong> $baseUrl</p>";
echo "<p><strong>Shortcode:</strong> " . MPESA_SHORTCODE . "</p>";
echo "<hr>";

// Get token
echo "<h3>Step 1: Get OAuth Token</h3>";
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
    die("<p style='color:red'>cURL Error: $curlError</p>");
}

$tokenData = json_decode($tokenResponse, true);
$accessToken = $tokenData['access_token'] ?? null;

if (!$accessToken) {
    echo "<p style='color:red'>Failed to get token. Response:</p>";
    echo "<pre>" . htmlspecialchars($tokenResponse) . "</pre>";
    die();
}

echo "<p style='color:green'>✓ Token obtained: " . substr($accessToken, 0, 20) . "...</p>";
echo "<hr>";

// Send STK Push
echo "<h3>Step 2: Send STK Push</h3>";
$timestamp = date('YmdHis');
$password = base64_encode(MPESA_SHORTCODE . MPESA_PASSKEY . $timestamp);

$stkPayload = [
    'BusinessShortCode' => (string)MPESA_SHORTCODE,
    'Password' => $password,
    'Timestamp' => $timestamp,
    'TransactionType' => 'CustomerPayBillOnline',
    'Amount' => $amount,
    'PartyA' => $phoneDigits,
    'PartyB' => (string)MPESA_SHORTCODE,
    'PhoneNumber' => $phoneDigits,
    'CallBackURL' => MPESA_CALLBACK_URL,
    'AccountReference' => 'TEST-' . time(),
    'TransactionDesc' => 'Test Payment',
];

echo "<p><strong>Payload:</strong></p>";
echo "<pre>" . json_encode($stkPayload, JSON_PRETTY_PRINT) . "</pre>";

$stkUrl = $baseUrl . '/mpesa/stkpush/v1/processrequest';

$ch = curl_init($stkUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $accessToken,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($stkPayload));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$stkResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> $httpCode</p>";

if ($curlError) {
    echo "<p style='color:red'>cURL Error: $curlError</p>";
} else {
    $stkData = json_decode($stkResponse, true);
    echo "<p><strong>Response:</strong></p>";
    echo "<pre>" . json_encode($stkData, JSON_PRETTY_PRINT) . "</pre>";
    
    if (isset($stkData['ResponseCode']) && $stkData['ResponseCode'] === '0') {
        echo "<p style='color:green'><strong>✓ SUCCESS!</strong> Check your phone ($phoneDigits) for the M-Pesa prompt.</p>";
        echo "<p><strong>Customer Message:</strong> " . ($stkData['CustomerMessage'] ?? 'N/A') . "</p>";
    } else {
        $responseCode = $stkData['ResponseCode'] ?? 'Unknown';
        $responseDesc = $stkData['ResponseDescription'] ?? 'Unknown error';
        echo "<p style='color:red'><strong>✗ FAILED</strong></p>";
        echo "<p><strong>Response Code:</strong> $responseCode</p>";
        echo "<p><strong>Description:</strong> $responseDesc</p>";
    }
}

echo "<hr>";
echo "<p><a href='?phone=$phone&amount=$amount'>Test Again</a> | <a href='dashboard.php'>Dashboard</a></p>";

