<?php
// Debug M-Pesa STK Push - Shows detailed error information
header('Content-Type: application/json');

// Allow CORS for debugging
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once('config.php');

$debug = [
    'timestamp' => date('Y-m-d H:i:s'),
    'config' => [],
    'test' => []
];

// Check configuration
$debug['config']['env'] = strtolower(MPESA_ENV) === 'production' ? 'production' : 'sandbox';
$debug['config']['consumer_key_set'] = !empty(MPESA_CONSUMER_KEY) && MPESA_CONSUMER_KEY !== 'YOUR_CONSUMER_KEY_HERE';
$debug['config']['consumer_secret_set'] = !empty(MPESA_CONSUMER_SECRET) && MPESA_CONSUMER_SECRET !== 'YOUR_CONSUMER_SECRET_HERE';
$debug['config']['shortcode'] = MPESA_SHORTCODE;
$debug['config']['passkey_set'] = !empty(MPESA_PASSKEY) && MPESA_PASSKEY !== 'YOUR_PASSKEY_HERE';
$debug['config']['callback_url'] = MPESA_CALLBACK_URL;
$debug['config']['callback_accessible'] = strpos(MPESA_CALLBACK_URL, 'example.com') === false && strpos(MPESA_CALLBACK_URL, 'localhost') === false;

// Test OAuth if credentials are set
if ($debug['config']['consumer_key_set'] && $debug['config']['consumer_secret_set']) {
    $env = $debug['config']['env'];
    $baseUrl = $env === 'production' ? 'https://api.safaricom.co.ke' : 'https://sandbox.safaricom.co.ke';
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
    
    $debug['test']['oauth'] = [
        'http_code' => $httpCode,
        'curl_error' => $curlError ?: null,
        'response' => json_decode($tokenResponse, true),
        'success' => $httpCode === 200 && isset(json_decode($tokenResponse, true)['access_token'])
    ];
} else {
    $debug['test']['oauth'] = ['error' => 'Credentials not configured'];
}

// Check if test data provided
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    
    if (is_array($data)) {
        $phone = trim($data['phone'] ?? '');
        $total = isset($data['total']) ? (float)$data['total'] : 0;
        
        if ($phone && $total > 0) {
            // Normalize phone
            $phoneDigits = preg_replace('/\D+/', '', $phone);
            if (strpos($phoneDigits, '0') === 0 && strlen($phoneDigits) === 10) {
                $phoneDigits = '254' . substr($phoneDigits, 1);
            } elseif (strpos($phoneDigits, '254') !== 0 && strlen($phoneDigits) === 9) {
                $phoneDigits = '254' . $phoneDigits;
            }
            
            $debug['test']['phone_normalized'] = $phoneDigits;
            $debug['test']['amount'] = max(1, (int)round($total));
            $debug['test']['phone_valid'] = strlen($phoneDigits) === 12 && substr($phoneDigits, 0, 4) === '2547';
        }
    }
}

echo json_encode($debug, JSON_PRETTY_PRINT);

