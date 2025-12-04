<?php
require_once('config.php');
session_start();

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

$loggedIn = isset($_SESSION['admin_id']);
$email = $loggedIn ? ($_SESSION['admin'] ?? '') : '';

echo json_encode([
    'ok' => true,
    'loggedIn' => $loggedIn,
    'email' => $email
]);

