<?php
// Create Order - Manual Payment Method

// CORS headers (if needed for cross-origin requests)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Error handling (production settings)
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log');

require_once('config.php');

// Check database connection
if (!$conn || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database connection failed']);
    exit;
}

session_start();

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
$items   = $data['items'] ?? [];

// Validation
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
$phoneDigits = preg_replace('/\D+/', '', $phone);
if (strpos($phoneDigits, '0') === 0 && strlen($phoneDigits) === 10) {
    $phoneDigits = '254' . substr($phoneDigits, 1);
} elseif (strpos($phoneDigits, '254') === 0 && strlen($phoneDigits) === 12) {
    $phoneDigits = $phoneDigits;
} elseif (strpos($phoneDigits, '7') === 0 && strlen($phoneDigits) === 9) {
    $phoneDigits = '254' . $phoneDigits;
} else {
    http_response_code(400);
    echo json_encode([
        'ok' => false, 
        'error' => 'Invalid Kenyan phone number format. Use: 0712345678, +254712345678, or 254712345678'
    ]);
    exit;
}

// Get Paybill and Account from settings or use defaults
$paybill = '247247';
$account = '0705399169';

$settingsQuery = $conn->query("SELECT paybill, account FROM settings LIMIT 1");
if ($settingsQuery && $settingsQuery->num_rows > 0) {
    $settings = $settingsQuery->fetch_assoc();
    if (!empty($settings['paybill'])) {
        $paybill = $settings['paybill'];
    }
    if (!empty($settings['account'])) {
        $account = $settings['account'];
    }
}

// Ensure orders table has correct structure
$conn->query("
    CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        customer_name VARCHAR(150) NOT NULL,
        customer_email VARCHAR(190) NOT NULL,
        customer_phone VARCHAR(60),
        shipping_addr VARCHAR(255) NOT NULL,
        payment_method VARCHAR(50) DEFAULT 'mpesa',
        mpesa_code VARCHAR(60) DEFAULT NULL,
        total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
        status VARCHAR(20) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Ensure order_items table exists
$conn->query("
    CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT DEFAULT 0,
        title VARCHAR(255) NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        INDEX idx_order_id (order_id)
    )
");

// Create order record as pending
$orderId = null;
$errorMsg = '';

// Try to insert order - handle different table structures
$stmt = $conn->prepare("
    INSERT INTO orders (user_id, customer_name, customer_email, customer_phone, shipping_addr, payment_method, mpesa_code, total_amount, status)
    VALUES (NULL, ?, ?, ?, ?, 'mpesa', NULL, ?, 'pending')
");

if ($stmt) {
    $stmt->bind_param('ssssd', $name, $email, $phoneDigits, $address, $total);
    if ($stmt->execute()) {
        $orderId = $conn->insert_id;
        
        // Save order items if provided and table exists
        if (!empty($items) && is_array($items)) {
            $itemStmt = $conn->prepare("
                INSERT INTO order_items (order_id, product_id, title, quantity, unit_price)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            if ($itemStmt) {
                foreach ($items as $item) {
                    $productId = isset($item['id']) ? intval($item['id']) : 0;
                    $title = $item['title'] ?? ($item['name'] ?? 'Product');
                    $quantity = isset($item['qty']) ? intval($item['qty']) : (isset($item['quantity']) ? intval($item['quantity']) : 1);
                    $unitPrice = isset($item['price']) ? floatval($item['price']) : 0;
                    
                    $itemStmt->bind_param('iisid', $orderId, $productId, $title, $quantity, $unitPrice);
                    if (!$itemStmt->execute()) {
                        error_log("Failed to insert order item: " . $itemStmt->error);
                    }
                }
                $itemStmt->close();
            } else {
                error_log("Failed to prepare order_items statement: " . $conn->error);
            }
        }
    } else {
        $errorMsg = $stmt->error;
        error_log("Failed to create order: " . $errorMsg);
    }
    $stmt->close();
} else {
    $errorMsg = $conn->error;
    error_log("Failed to prepare order statement: " . $errorMsg);
}

if (!$orderId) {
    http_response_code(500);
    echo json_encode([
        'ok' => false, 
        'error' => 'Failed to create order record. ' . ($errorMsg ?: 'Database error.'),
        'debug' => $errorMsg
    ]);
    exit;
}

// Return success with order details
echo json_encode([
    'ok' => true,
    'order_id' => $orderId,
    'total' => $total,
    'paybill' => $paybill,
    'account' => $account,
    'message' => 'Order created successfully. Please complete payment using the instructions provided.'
]);

?>

