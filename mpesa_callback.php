<?php
// M-Pesa STK Push Callback Handler
// This endpoint receives payment confirmation from Safaricom

require_once('config.php');

header('Content-Type: application/json');

// Log the callback for debugging
$raw = file_get_contents('php://input');
error_log("M-Pesa Callback received: " . $raw);

$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Invalid request']);
    exit;
}

// Extract callback data
$body = $data['Body'] ?? [];
$stkCallback = $body['stkCallback'] ?? [];
$merchantRequestID = $stkCallback['MerchantRequestID'] ?? '';
$checkoutRequestID = $stkCallback['CheckoutRequestID'] ?? '';
$resultCode = $stkCallback['ResultCode'] ?? 1;
$resultDesc = $stkCallback['ResultDesc'] ?? 'Unknown error';
$callbackMetadata = $stkCallback['CallbackMetadata'] ?? [];
$items = $callbackMetadata['Item'] ?? [];

// Extract payment details
$mpesaReceiptNumber = '';
$transactionDate = '';
$phoneNumber = '';
$amount = 0;

foreach ($items as $item) {
    $name = $item['Name'] ?? '';
    $value = $item['Value'] ?? '';
    
    switch ($name) {
        case 'MpesaReceiptNumber':
            $mpesaReceiptNumber = $value;
            break;
        case 'TransactionDate':
            $transactionDate = $value;
            break;
        case 'PhoneNumber':
            $phoneNumber = $value;
            break;
        case 'Amount':
            $amount = floatval($value);
            break;
    }
}

// Find order by MerchantRequestID or CheckoutRequestID
// We'll search orders table - you may need to store these IDs when creating the order
// For now, we'll try to match by phone and recent pending orders

$orderId = null;
if ($mpesaReceiptNumber) {
    // Try to find order by phone number and amount (most recent pending)
    $stmt = $conn->prepare("
        SELECT id FROM orders 
        WHERE customer_phone = ? 
        AND total_amount = ? 
        AND status = 'pending'
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param('sd', $phoneNumber, $amount);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $orderId = $row['id'];
        }
        $stmt->close();
    }
}

if ($orderId && $resultCode == 0) {
    // Payment successful - update order status
    $updateStmt = $conn->prepare("
        UPDATE orders 
        SET status = 'paid', 
            mpesa_code = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    if ($updateStmt) {
        $updateStmt->bind_param('si', $mpesaReceiptNumber, $orderId);
        $updateStmt->execute();
        $updateStmt->close();
        
        error_log("Order #{$orderId} marked as paid. M-Pesa Receipt: {$mpesaReceiptNumber}");
    }
    
    // Respond to Safaricom
    echo json_encode([
        'ResultCode' => 0,
        'ResultDesc' => 'Callback processed successfully'
    ]);
} else {
    // Payment failed or order not found
    if ($orderId) {
        // Mark order as failed
        $updateStmt = $conn->prepare("
            UPDATE orders 
            SET status = 'cancelled'
            WHERE id = ?
        ");
        if ($updateStmt) {
            $updateStmt->bind_param('i', $orderId);
            $updateStmt->execute();
            $updateStmt->close();
        }
    }
    
    error_log("M-Pesa payment failed. ResultCode: {$resultCode}, Desc: {$resultDesc}");
    
    echo json_encode([
        'ResultCode' => 0, // Always return 0 to acknowledge receipt
        'ResultDesc' => 'Callback received'
    ]);
}

