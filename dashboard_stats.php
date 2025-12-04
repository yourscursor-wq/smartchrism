<?php
// Dashboard Statistics API Endpoint
// Returns JSON with all dashboard statistics for auto-refresh

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once('config.php');
session_start();

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get statistics with error handling
$stats = [
    'ok' => true,
    'products' => 0,
    'orders' => 0,
    'pendingOrders' => 0,
    'paidOrders' => 0,
    'shippedOrders' => 0,
    'cancelledOrders' => 0,
    'revenue' => 0.0,
    'admins' => 0,
    'users' => 0,
    'recentOrders' => 0,
    'newMessages' => 0,
    'totalMessages' => 0
];

// Products
$productsResult = $conn->query("SELECT COUNT(*) AS total FROM products");
if ($productsResult) {
    $stats['products'] = (int)$productsResult->fetch_assoc()['total'];
}

// Orders - Total
$ordersResult = $conn->query("SELECT COUNT(*) AS total FROM orders");
if ($ordersResult) {
    $stats['orders'] = (int)$ordersResult->fetch_assoc()['total'];
}

// Orders by status
$statusResult = $conn->query("SELECT status, COUNT(*) AS total FROM orders GROUP BY status");
if ($statusResult) {
    while ($row = $statusResult->fetch_assoc()) {
        $status = strtolower($row['status'] ?? '');
        $count = (int)$row['total'];
        switch ($status) {
            case 'pending':
                $stats['pendingOrders'] = $count;
                break;
            case 'paid':
                $stats['paidOrders'] = $count;
                break;
            case 'shipped':
                $stats['shippedOrders'] = $count;
                break;
            case 'cancelled':
                $stats['cancelledOrders'] = $count;
                break;
        }
    }
}

// Total revenue from paid/shipped orders
$incomeResult = $conn->query("SELECT SUM(total_amount) AS total FROM orders WHERE status IN ('paid', 'shipped')");
if ($incomeResult) {
    $incomeRow = $incomeResult->fetch_assoc();
    $stats['revenue'] = (float)($incomeRow['total'] ?? 0);
}

// Admin accounts
$adminsResult = $conn->query("SELECT COUNT(*) AS total FROM admins");
if ($adminsResult) {
    $stats['admins'] = (int)$adminsResult->fetch_assoc()['total'];
}

// Registered customers (users table)
$usersResult = $conn->query("SELECT COUNT(*) AS total FROM users");
if ($usersResult) {
    $stats['users'] = (int)$usersResult->fetch_assoc()['total'];
}

// Recent orders (last 7 days)
$recentOrdersResult = $conn->query("SELECT COUNT(*) AS total FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
if ($recentOrdersResult) {
    $stats['recentOrders'] = (int)$recentOrdersResult->fetch_assoc()['total'];
}

// Contact messages
$tableCheck = $conn->query("SHOW TABLES LIKE 'contact_messages'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $newMessagesResult = $conn->query("SELECT COUNT(*) AS total FROM contact_messages WHERE status = 'new'");
    if ($newMessagesResult) {
        $stats['newMessages'] = (int)$newMessagesResult->fetch_assoc()['total'];
    }

    $allMessagesResult = $conn->query("SELECT COUNT(*) AS total FROM contact_messages");
    if ($allMessagesResult) {
        $stats['totalMessages'] = (int)$allMessagesResult->fetch_assoc()['total'];
    }
}

echo json_encode($stats);

