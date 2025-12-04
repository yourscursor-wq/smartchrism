<?php
/**
 * Setup Admin Account - Run this once to create/update admin
 * Access via: http://localhost/kk/public/setup_admin.php
 */
require_once('config.php');

// Admin credentials
$adminEmail = 'yegoniccc@gmail.com';
$adminPassword = '@ODero#2030$2616@';
$adminName = 'Smart Chrism Admin';

// Hash the password
$hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);

echo "<h2>Setting up Admin Account</h2>";
echo "<p>Email: <strong>{$adminEmail}</strong></p>";
echo "<p>Password: <strong>{$adminPassword}</strong></p>";
echo "<p>Hashed Password: <code>{$hashedPassword}</code></p>";
echo "<hr>";

// Check if admin exists
$stmt = $conn->prepare("SELECT id, email FROM admins WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $adminEmail);
$stmt->execute();
$result = $stmt->get_result();
$existing = $result->fetch_assoc();
$stmt->close();

if ($existing) {
    // Update existing admin
    $stmt = $conn->prepare("UPDATE admins SET password = ?, name = ? WHERE email = ?");
    $stmt->bind_param("sss", $hashedPassword, $adminName, $adminEmail);
    
    if ($stmt->execute()) {
        echo "<p style='color:green;'><strong>✓ SUCCESS:</strong> Admin account updated!</p>";
        echo "<p>Admin ID: {$existing['id']}</p>";
    } else {
        echo "<p style='color:red;'><strong>✗ ERROR:</strong> Failed to update admin: " . $stmt->error . "</p>";
    }
    $stmt->close();
} else {
    // Create new admin
    $stmt = $conn->prepare("INSERT INTO admins (email, password, name) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $adminEmail, $hashedPassword, $adminName);
    
    if ($stmt->execute()) {
        $adminId = $conn->insert_id;
        echo "<p style='color:green;'><strong>✓ SUCCESS:</strong> Admin account created!</p>";
        echo "<p>Admin ID: {$adminId}</p>";
    } else {
        echo "<p style='color:red;'><strong>✗ ERROR:</strong> Failed to create admin: " . $stmt->error . "</p>";
    }
    $stmt->close();
}

// Verify the account works
echo "<hr>";
echo "<h3>Verification Test</h3>";

$stmt = $conn->prepare("SELECT id, email, password FROM admins WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $adminEmail);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

if ($admin) {
    if (password_verify($adminPassword, $admin['password'])) {
        echo "<p style='color:green;'><strong>✓ Password verification: SUCCESS</strong></p>";
        echo "<p>You can now log in with:</p>";
        echo "<ul>";
        echo "<li>Email: <strong>{$adminEmail}</strong></li>";
        echo "<li>Password: <strong>{$adminPassword}</strong></li>";
        echo "</ul>";
    } else {
        echo "<p style='color:red;'><strong>✗ Password verification: FAILED</strong></p>";
        echo "<p>The password hash doesn't match. Please run this script again.</p>";
    }
} else {
    echo "<p style='color:red;'><strong>✗ Admin account not found</strong></p>";
}

echo "<hr>";
echo "<p><a href='index.html'>← Back to Shop</a> | <a href='login.php'>Go to Login</a></p>";
?>
