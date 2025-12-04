<?php
/**
 * Test Admin Login - Diagnostic Tool
 * Access via: http://localhost/kk/public/test_admin_login.php
 * This will help identify why login is failing
 */
require_once('config.php');

$adminEmail = 'yegoniccc@gmail.com';
$adminPassword = '@ODero#2030$2616@';

echo "<h2>Admin Login Diagnostic Test</h2>";
echo "<hr>";

// Test 1: Database connection
echo "<h3>1. Database Connection Test</h3>";
if ($conn && $conn->ping()) {
    echo "<p style='color:green;'>✓ Database connected: <strong>{$dbname}</strong></p>";
} else {
    echo "<p style='color:red;'>✗ Database connection failed</p>";
    exit;
}

// Test 2: Check if admins table exists
echo "<h3>2. Admins Table Check</h3>";
$tableCheck = $conn->query("SHOW TABLES LIKE 'admins'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    echo "<p style='color:green;'>✓ Admins table exists</p>";
} else {
    echo "<p style='color:red;'>✗ Admins table does NOT exist. Please run database.sql first.</p>";
    exit;
}

// Test 3: Check if admin account exists
echo "<h3>3. Admin Account Check</h3>";
$stmt = $conn->prepare("SELECT id, email, password, name FROM admins WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $adminEmail);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result ? $result->fetch_assoc() : null;
$stmt->close();

if ($admin) {
    echo "<p style='color:green;'>✓ Admin account found!</p>";
    echo "<ul>";
    echo "<li>ID: <strong>{$admin['id']}</strong></li>";
    echo "<li>Email: <strong>{$admin['email']}</strong></li>";
    echo "<li>Name: <strong>{$admin['name']}</strong></li>";
    echo "<li>Password Hash: <code style='font-size:11px;'>{$admin['password']}</code></li>";
    echo "</ul>";
} else {
    echo "<p style='color:red;'>✗ Admin account NOT found with email: <strong>{$adminEmail}</strong></p>";
    echo "<p>All admins in database:</p>";
    $allAdmins = $conn->query("SELECT id, email, name FROM admins");
    if ($allAdmins && $allAdmins->num_rows > 0) {
        echo "<ul>";
        while ($row = $allAdmins->fetch_assoc()) {
            echo "<li>ID: {$row['id']}, Email: {$row['email']}, Name: {$row['name']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No admins found in database.</p>";
    }
    echo "<p><strong>Solution:</strong> Run <a href='setup_admin.php'>setup_admin.php</a> or execute setup_admin.sql in phpMyAdmin.</p>";
    exit;
}

// Test 4: Password verification
echo "<h3>4. Password Verification Test</h3>";
if (password_verify($adminPassword, $admin['password'])) {
    echo "<p style='color:green;'><strong>✓ PASSWORD VERIFICATION: SUCCESS</strong></p>";
    echo "<p>The password <strong>@ODero#2030$2616@</strong> correctly matches the hash in the database.</p>";
} else {
    echo "<p style='color:red;'><strong>✗ PASSWORD VERIFICATION: FAILED</strong></p>";
    echo "<p>The password does NOT match the hash in the database.</p>";
    echo "<p><strong>Solution:</strong> Update the password hash:</p>";
    
    $newHash = password_hash($adminPassword, PASSWORD_DEFAULT);
    echo "<pre style='background:#f0f0f0;padding:10px;'>";
    echo "UPDATE admins SET password = '{$newHash}' WHERE email = '{$adminEmail}';";
    echo "</pre>";
}

// Test 5: Simulate login API call
echo "<h3>5. Simulated Login API Test</h3>";
echo "<p>Testing the same logic as admin_login.php:</p>";

$testEmail = trim($adminEmail);
$testPassword = $adminPassword;

$stmt = $conn->prepare('SELECT id, email, password FROM admins WHERE email = ? LIMIT 1');
if ($stmt) {
    $stmt->bind_param('s', $testEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    $testAdmin = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    
    if ($testAdmin && password_verify($testPassword, $testAdmin['password'])) {
        echo "<p style='color:green;'><strong>✓ LOGIN SIMULATION: SUCCESS</strong></p>";
        echo "<p>Session would be set with:</p>";
        echo "<ul>";
        echo "<li>Session admin: <strong>{$testAdmin['email']}</strong></li>";
        echo "<li>Session admin_id: <strong>{$testAdmin['id']}</strong></li>";
        echo "</ul>";
    } else {
        echo "<p style='color:red;'><strong>✗ LOGIN SIMULATION: FAILED</strong></p>";
        if (!$testAdmin) {
            echo "<p>Reason: Admin account not found (email doesn't match)</p>";
        } else {
            echo "<p>Reason: Password verification failed</p>";
        }
    }
} else {
    echo "<p style='color:red;'>✗ Database prepare failed: " . $conn->error . "</p>";
}

// Test 6: Generate correct hash if needed
echo "<h3>6. Password Hash Generator</h3>";
$correctHash = password_hash($adminPassword, PASSWORD_DEFAULT);
echo "<p>Fresh password hash for <strong>@ODero#2030$2616@</strong>:</p>";
echo "<pre style='background:#f0f0f0;padding:10px;'>{$correctHash}</pre>";

// Test 7: SQL Update Statement
echo "<h3>7. SQL Update Statement</h3>";
echo "<p>Run this SQL in phpMyAdmin to update the admin password:</p>";
echo "<pre style='background:#f0f0f0;padding:10px;'>";
echo "USE smartchrism;\n";
echo "UPDATE admins SET password = '{$correctHash}' WHERE email = '{$adminEmail}';\n";
echo "SELECT id, email, name FROM admins WHERE email = '{$adminEmail}';";
echo "</pre>";

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>If password verification failed, run the SQL UPDATE statement above</li>";
echo "<li>If admin account doesn't exist, run <a href='setup_admin.php'>setup_admin.php</a></li>";
echo "<li>Try logging in again at <a href='index.html'>index.html</a></li>";
echo "</ol>";

?>
