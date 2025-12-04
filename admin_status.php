<?php
require_once('config.php');
session_start();

header('Content-Type: application/json');

$loggedIn = isset($_SESSION['admin_id']);
$email = $loggedIn ? ($_SESSION['admin'] ?? '') : '';

echo json_encode([
    'ok' => true,
    'loggedIn' => $loggedIn,
    'email' => $email
]);

