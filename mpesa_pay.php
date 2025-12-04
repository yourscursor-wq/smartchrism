<?php
// M-Pesa STK Push payment for Smart Chrism Shop
header('Content-Type: application/json');

require_once('config.php');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// Read JSON body
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST;
}

$name    = trim($data['name'] ?? '');
$email   = trim($data['email'] ?? '');
$phone   = trim($data['phone'] ?? '');
$address = trim($data['address'] ?? '');
$total   = isset($data['total']) ? (float)$data['total'] : 0;

if ($name === '' || $email === '' || $phone === '' || $address === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Name, email, phone and address are required']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid email address']);
    exit;
}
if ($total <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid order amount']);
    exit;
}

// Normalize Kenyan phone number to 2547XXXXXXXX format
// Accepts: 0712345678, +254712345678, 254712345678, 712345678
$phoneDigits = preg_replace('/\D+/', '', $phone);

// Remove leading + if present (already removed by preg_replace, but keeping for clarity)
$phoneDigits = ltrim($phoneDigits, '+');

// Validate it's a Kenyan mobile number (should start with 7 after normalization)
if (empty($phoneDigits)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Phone number is required']);
    exit;
}

// Convert to international format (254XXXXXXXXX)
if (strpos($phoneDigits, '0') === 0 && strlen($phoneDigits) === 10) {
    // Format: 0712345678 -> 254712345678
    $phoneDigits = '254' . substr($phoneDigits, 1);
} elseif (strpos($phoneDigits, '254') === 0 && strlen($phoneDigits) === 12) {
    // Format: 254712345678 -> keep as is
    $phoneDigits = $phoneDigits;
} elseif (strpos($phoneDigits, '7') === 0 && strlen($phoneDigits) === 9) {
    // Format: 712345678 -> 254712345678
    $phoneDigits = '254' . $phoneDigits;
} else {
    http_response_code(400);
    echo json_encode([
        'ok' => false, 
        'error' => 'Invalid Kenyan phone number format. Use: 0712345678, +254712345678, or 254712345678'
    ]);
    exit;
}

// Final validation: must be 12 digits starting with 2547
if (strlen($phoneDigits) !== 12 || substr($phoneDigits, 0, 4) !== '2547') {
    http_response_code(400);
    echo json_encode([
        'ok' => false, 
        'error' => 'Invalid Kenyan mobile number. Must start with 07, +2547, or 2547'
    ]);
    exit;
}

// Ensure M-Pesa configuration is present
if (
    empty(MPESA_CONSUMER_KEY) ||
    empty(MPESA_CONSUMER_SECRET) ||
    empty(MPESA_SHORTCODE) ||
    empty(MPESA_PASSKEY) ||
    MPESA_CONSUMER_KEY === 'YOUR_CONSUMER_KEY_HERE' ||
    MPESA_CONSUMER_SECRET === 'YOUR_CONSUMER_SECRET_HERE' ||
    MPESA_PASSKEY === 'YOUR_PASSKEY_HERE'
) {
    http_response_code(500);
    echo json_encode([
        'ok' => false, 
        'error' => 'M-Pesa is not configured. Please set your real MPESA_* credentials in public/config.php',
        'hint' => 'Get your credentials from https://developer.safaricom.co.ke/'
    ]);
    exit;
}

// First, create an order record as pending
$orderId = null;
$stmt = $conn->prepare("
    INSERT INTO orders (user_id, customer_name, customer_email, customer_phone, shipping_addr, payment_method, mpesa_code, total_amount, status)
    VALUES (NULL, ?, ?, ?, ?, 'mpesa', NULL, ?, 'pending')
");
if ($stmt) {
    $stmt->bind_param('ssssd', $name, $email, $phoneDigits, $address, $total);
    if ($stmt->execute()) {
        $orderId = $conn->insert_id;
        
        // Save order items if provided
        if (isset($data['items']) && is_array($data['items'])) {
            $itemStmt = $conn->prepare("
                INSERT INTO order_items (order_id, product_id, title, quantity, unit_price)
                VALUES (?, ?, ?, ?, ?)
            ");
            foreach ($data['items'] as $item) {
                $productId = isset($item['id']) ? intval($item['id']) : 0;
                $title = $item['title'] ?? 'Product';
                $quantity = isset($item['qty']) ? intval($item['qty']) : 1;
                $unitPrice = isset($item['price']) ? floatval($item['price']) : 0;
                
                if ($itemStmt) {
                    $itemStmt->bind_param('iisid', $orderId, $productId, $title, $quantity, $unitPrice);
                    $itemStmt->execute();
                }
            }
            if ($itemStmt) {
                $itemStmt->close();
            }
        }
    }
    $stmt->close();
}

if (!$orderId) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Failed to create order record']);
    exit;
}

// Get OAuth token from Safaricom
$env = strtolower(MPESA_ENV) === 'production' ? 'production' : 'sandbox';
$baseUrl = $env === 'production'
    ? 'https://api.safaricom.co.ke'
    : 'https://sandbox.safaricom.co.ke';

$tokenUrl = $baseUrl . '/oauth/v1/generate?grant_type=client_credentials';

$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Basic ' . base64_encode(MPESA_CONSUMER_KEY . ':' . MPESA_CONSUMER_SECRET)
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$tokenResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

if (curl_errno($ch) || $curlError) {
    error_log("M-Pesa Token Request Error: " . $curlError . " | HTTP Code: " . $httpCode);
    curl_close($ch);
    http_response_code(500);
    echo json_encode([
        'ok' => false, 
        'error' => 'Failed to contact M-Pesa authentication service',
        'debug' => $env === 'sandbox' ? 'Check your Consumer Key and Secret' : 'Network error'
    ]);
    exit;
}
curl_close($ch);

$tokenData = json_decode($tokenResponse, true);
$accessToken = $tokenData['access_token'] ?? null;

if (!$accessToken) {
    error_log("M-Pesa Token Response: " . $tokenResponse . " | HTTP Code: " . $httpCode);
    http_response_code(500);
    $errorMsg = isset($tokenData['error']) ? $tokenData['error'] : 'Failed to obtain M-Pesa access token';
    $errorDesc = isset($tokenData['error_description']) ? $tokenData['error_description'] : 'Invalid credentials or network issue';
    echo json_encode([
        'ok' => false, 
        'error' => $errorMsg . ': ' . $errorDesc,
        'debug' => $httpCode !== 200 ? "HTTP $httpCode" : 'Check your Consumer Key and Secret'
    ]);
    exit;
}

// Prepare STK Push request
$timestamp = date('YmdHis');
$password  = base64_encode(MPESA_SHORTCODE . MPESA_PASSKEY . $timestamp);

// M-Pesa requires amount as integer (whole shillings, minimum 1)
$amount = max(1, (int)round($total));

// Ensure shortcode is string (some APIs require this)
$shortcode = (string)MPESA_SHORTCODE;

$stkPayload = [
    'BusinessShortCode' => $shortcode,
    'Password'          => $password,
    'Timestamp'         => $timestamp,
    'TransactionType'   => 'CustomerPayBillOnline',
    'Amount'            => $amount,
    'PartyA'            => $phoneDigits,
    'PartyB'            => $shortcode,
    'PhoneNumber'       => $phoneDigits,
    'CallBackURL'       => MPESA_CALLBACK_URL,
    'AccountReference'  => 'SmartChrism-' . $orderId,
    'TransactionDesc'   => 'Smart Chrism Shoe Order #' . $orderId,
];

// Log full payload for debugging (without password)
$debugPayload = $stkPayload;
$debugPayload['Password'] = '[HIDDEN]';
error_log("M-Pesa STK Push Payload: " . json_encode($debugPayload));

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
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Log the request for debugging
error_log("M-Pesa STK Push Request - Order #$orderId - Phone: $phoneDigits - Amount: $amount (original: $total) - Shortcode: $shortcode");

$stkResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

if (curl_errno($ch) || $curlError) {
    error_log("M-Pesa STK Push Error: " . $curlError . " | HTTP Code: " . $httpCode);
    curl_close($ch);
    http_response_code(500);
    echo json_encode([
        'ok' => false, 
        'error' => 'Failed to send M-Pesa STK Push request',
        'debug' => $curlError ?: 'Network error'
    ]);
    exit;
}
curl_close($ch);

$stkData = json_decode($stkResponse, true);

// Log the response for debugging
error_log("M-Pesa STK Push Response: " . json_encode($stkData) . " | HTTP Code: " . $httpCode);

if ($httpCode !== 200) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'M-Pesa API returned error (HTTP ' . $httpCode . ')',
        'response' => $stkData
    ]);
    exit;
}

// Check if response structure is valid
if (!isset($stkData['ResponseCode'])) {
    // Response might be in different format or error occurred
    $errorMsg = 'Invalid response from M-Pesa API';
    if (isset($stkData['errorMessage'])) {
        $errorMsg = $stkData['errorMessage'];
    } elseif (isset($stkData['error'])) {
        $errorMsg = is_array($stkData['error']) ? json_encode($stkData['error']) : $stkData['error'];
    }
    
    error_log("M-Pesa STK Push Invalid Response: " . json_encode($stkData));
    
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $errorMsg . '. Please check your M-Pesa configuration.',
        'rawResponse' => $stkData,
        'debug' => 'Check config.php for correct MPESA_* values'
    ]);
    exit;
}

if ($stkData['ResponseCode'] !== '0') {
    // Use Safaricom's error message or provide user-friendly STK Push error
    $responseCode = $stkData['ResponseCode'] ?? 'Unknown';
    $responseDesc = $stkData['ResponseDescription'] ?? 'Unknown error';
    $errorMessage = $stkData['errorMessage'] ?? $responseDesc;
    
    error_log("M-Pesa STK Push Failed - ResponseCode: $responseCode - Description: $responseDesc - Full Response: " . json_encode($stkData));
    
    // Common error codes and user-friendly messages
    $userMessage = $errorMessage;
    if ($responseCode == '1032') {
        $userMessage = 'Request cancelled by user. Please try again.';
    } elseif ($responseCode == '1037') {
        $userMessage = 'Timeout waiting for customer response. Please try again.';
    } elseif ($responseCode == '1031') {
        $userMessage = 'Request cancelled. Please ensure your phone number is registered with M-Pesa.';
    } elseif ($responseCode == '1014') {
        $userMessage = 'Invalid shortcode. Please check your Paybill number in config.php';
    } elseif ($responseCode == '2001') {
        $userMessage = 'Invalid phone number format. Use: 0712345678 or 254712345678';
    } elseif (stripos($responseDesc, 'transaction') !== false || stripos($errorMessage, 'transaction') !== false) {
        // If error mentions transaction code, it's likely a configuration issue
        $userMessage = 'M-Pesa STK Push failed. Please check: 1) Your phone number is registered with M-Pesa, 2) Shortcode and Passkey are correct in config.php, 3) Callback URL is accessible. Error: ' . $responseDesc;
    }
    
    http_response_code(500);
    echo json_encode([
        'ok'    => false,
        'error' => $userMessage,
        'responseCode' => $responseCode,
        'responseDescription' => $responseDesc,
        'fullResponse' => $stkData,
        'debug' => $env === 'sandbox' ? 'Check your Shortcode (should be 600000) and Passkey from Daraja portal' : 'Check your production credentials'
    ]);
    exit;
}

// Use Safaricom's official CustomerMessage or provide modern STK Push instruction
$customerMessage = $stkData['CustomerMessage'] ?? 'STK Push initiated. Please check your phone for the M-Pesa prompt and enter your PIN to complete payment.';

// Log success
error_log("M-Pesa STK Push Success - Order #$orderId - CheckoutRequestID: " . ($stkData['CheckoutRequestID'] ?? 'N/A'));

echo json_encode([
    'ok'                 => true,
    'order_id'           => $orderId,
    'CheckoutRequestID'  => $stkData['CheckoutRequestID'] ?? null,
    'MerchantRequestID'  => $stkData['MerchantRequestID'] ?? null,
    'CustomerMessage'    => $customerMessage,
]);


