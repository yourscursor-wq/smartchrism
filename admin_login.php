<?php
require_once('config.php');
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!is_array($data)) {
    $data = $_POST;
}

$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Valid email is required']);
    exit;
}
if ($password === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Password is required']);
    exit;
}

// Ensure admin account exists (auto-fix)
$checkAdmin = $conn->prepare('SELECT id, email, password FROM admins WHERE email = ? LIMIT 1');
if (!$checkAdmin) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database error: ' . $conn->error]);
    exit;
}
$checkAdmin->bind_param('s', $email);
$checkAdmin->execute();
$result = $checkAdmin->get_result();
$admin = $result ? $result->fetch_assoc() : null;
$checkAdmin->close();

// If admin doesn't exist, create it with the correct password
if (!$admin) {
    $defaultPassword = '@ODero#2030$2616@';
    $defaultEmail = 'yegoniccc@gmail.com';
    
    // Only auto-create if it's the default admin email
    if ($email === $defaultEmail && $password === $defaultPassword) {
        $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);
        $insertStmt = $conn->prepare('INSERT INTO admins (email, password, name) VALUES (?, ?, ?)');
        $adminName = 'Smart Chrism Admin';
        $insertStmt->bind_param('sss', $defaultEmail, $hashedPassword, $adminName);
        
        if ($insertStmt->execute()) {
            $admin = [
                'id' => $conn->insert_id,
                'email' => $defaultEmail,
                'password' => $hashedPassword
            ];
        }
        $insertStmt->close();
    }
}

if (!$admin) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Invalid credentials - admin account not found']);
    exit;
}

// Verify password
if (!password_verify($password, $admin['password'])) {
    // If password fails, try updating it if it's the default admin
    if ($email === 'yegoniccc@gmail.com' && $password === '@ODero#2030$2616@') {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $updateStmt = $conn->prepare('UPDATE admins SET password = ? WHERE email = ?');
        $updateStmt->bind_param('ss', $newHash, $email);
        $updateStmt->execute();
        $updateStmt->close();
        
        // Try verify again
        if (password_verify($password, $newHash)) {
            $admin['password'] = $newHash;
        } else {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Invalid credentials']);
            exit;
        }
    } else {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Invalid credentials']);
        exit;
    }
}

$_SESSION['admin'] = $admin['email'];
$_SESSION['admin_id'] = $admin['id'];
session_regenerate_id(true);

echo json_encode([
    'ok' => true,
    'email' => $admin['email']
]);

