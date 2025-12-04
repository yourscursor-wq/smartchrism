<?php
/**
 * Database Setup Script - Smart Chrism Shop
 * 
 * This script will:
 * 1. Create the database if it doesn't exist
 * 2. Create all required tables
 * 3. Insert default admin account
 * 4. Insert default shop settings
 * 5. Create sample products (optional)
 * 
 * Access: http://localhost/kk/setup_database.php
 * 
 * IMPORTANT: Delete this file after setup for security!
 */

require_once('config.php');

// Security: Only allow if database is empty or if explicitly enabled
$allowSetup = true; // Set to false after initial setup

if (!$allowSetup) {
    die('Database setup is disabled. If you need to reset, enable $allowSetup in setup_database.php');
}

$errors = [];
$success = [];

// Step 1: Create database (already done in config.php, but verify)
$dbCheck = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'smartchrism'");
if (!$dbCheck || $dbCheck->num_rows == 0) {
    $createDb = $conn->query("CREATE DATABASE IF NOT EXISTS `smartchrism` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    if ($createDb) {
        $success[] = "Database 'smartchrism' created successfully";
        $conn->select_db('smartchrism');
    } else {
        $errors[] = "Failed to create database: " . $conn->error;
    }
} else {
    $success[] = "Database 'smartchrism' already exists";
    $conn->select_db('smartchrism');
}

// Step 2: Create tables
$tables = [
    'admins' => "
        CREATE TABLE IF NOT EXISTS admins (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(190) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            name VARCHAR(120) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'users' => "
        CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            phone VARCHAR(50) DEFAULT NULL,
            password VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'products' => "
        CREATE TABLE IF NOT EXISTS products (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            category VARCHAR(120),
            price DECIMAL(10,2) NOT NULL DEFAULT 0,
            stock INT NOT NULL DEFAULT 0,
            image VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'orders' => "
        CREATE TABLE IF NOT EXISTS orders (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED DEFAULT NULL,
            customer_name VARCHAR(150) NOT NULL,
            customer_email VARCHAR(190) NOT NULL,
            customer_phone VARCHAR(60),
            shipping_addr VARCHAR(255) NOT NULL,
            payment_method VARCHAR(50) DEFAULT 'mpesa',
            mpesa_code VARCHAR(60) DEFAULT NULL,
            total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            status VARCHAR(20) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'order_items' => "
        CREATE TABLE IF NOT EXISTS order_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id INT UNSIGNED NOT NULL,
            product_id INT UNSIGNED DEFAULT 0,
            title VARCHAR(255) NOT NULL,
            quantity INT NOT NULL,
            unit_price DECIMAL(10,2) NOT NULL,
            INDEX idx_order_id (order_id),
            INDEX idx_product_id (product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'settings' => "
        CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            shop_name VARCHAR(255),
            email VARCHAR(100),
            location VARCHAR(255),
            paybill VARCHAR(50),
            account VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'contact_messages' => "
        CREATE TABLE IF NOT EXISTS contact_messages (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            email VARCHAR(190) NOT NULL,
            subject VARCHAR(190) DEFAULT NULL,
            message TEXT NOT NULL,
            status ENUM('new','read','replied') DEFAULT 'new',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    "
];

foreach ($tables as $tableName => $sql) {
    if ($conn->query($sql)) {
        $success[] = "Table '$tableName' created/verified successfully";
    } else {
        $errors[] = "Failed to create table '$tableName': " . $conn->error;
    }
}

// Step 3: Insert default admin account
$adminEmail = 'yegoniccc@gmail.com';
$adminPassword = '@ODero#2030$2616@';
$adminName = 'Smart Chrism Admin';

// Check if admin exists
$checkAdmin = $conn->prepare("SELECT id FROM admins WHERE email = ?");
$checkAdmin->bind_param('s', $adminEmail);
$checkAdmin->execute();
$adminExists = $checkAdmin->get_result()->num_rows > 0;
$checkAdmin->close();

if (!$adminExists) {
    $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
    $insertAdmin = $conn->prepare("INSERT INTO admins (email, password, name) VALUES (?, ?, ?)");
    $insertAdmin->bind_param('sss', $adminEmail, $hashedPassword, $adminName);
    
    if ($insertAdmin->execute()) {
        $success[] = "Default admin account created: $adminEmail";
    } else {
        $errors[] = "Failed to create admin account: " . $insertAdmin->error;
    }
    $insertAdmin->close();
} else {
    $success[] = "Admin account already exists: $adminEmail";
}

// Step 4: Insert default shop settings
$checkSettings = $conn->query("SELECT id FROM settings LIMIT 1");
if ($checkSettings->num_rows == 0) {
    $shopName = 'Smart Chrism Shop';
    $shopEmail = 'brianmoses237@gmail.com';
    $location = 'Nairobi OTC 2nd Floor Wholesale Mall';
    $paybill = '247247';
    $account = '0705399169';
    
    $insertSettings = $conn->prepare("INSERT INTO settings (shop_name, email, location, paybill, account) VALUES (?, ?, ?, ?, ?)");
    $insertSettings->bind_param('sssss', $shopName, $shopEmail, $location, $paybill, $account);
    
    if ($insertSettings->execute()) {
        $success[] = "Default shop settings created";
    } else {
        $errors[] = "Failed to create settings: " . $insertSettings->error;
    }
    $insertSettings->close();
} else {
    $success[] = "Shop settings already exist";
}

// Step 5: Insert sample products (optional - only if table is empty)
$checkProducts = $conn->query("SELECT COUNT(*) as count FROM products");
$productCount = $checkProducts->fetch_assoc()['count'];

if ($productCount == 0) {
    $sampleProducts = [
        ['Nike Air Max', 'Comfortable everyday sneaker with air cushioning', 'Sneakers', 6500.00, 12, 'uploads/nike_air_max.jpg'],
        ['Adidas Ultraboost', 'Lightweight performance running shoe', 'Running', 7200.00, 8, 'uploads/adidas_ultraboost.jpg'],
        ['Converse All Star', 'Classic canvas style sneaker', 'Casual', 4800.00, 20, 'uploads/converse_allstar.jpg'],
        ['Puma Drift Cat', 'Sleek low-profile motorsport shoe', 'Sport', 5600.00, 15, 'uploads/puma_driftcat.jpg'],
        ['Jordan Retro 4', 'Iconic retro basketball shoe', 'Premium', 8900.00, 5, 'uploads/jordan_retro4.jpg']
    ];
    
    $insertProduct = $conn->prepare("INSERT INTO products (name, description, category, price, stock, image) VALUES (?, ?, ?, ?, ?, ?)");
    $inserted = 0;
    
    foreach ($sampleProducts as $product) {
        $insertProduct->bind_param('sssdis', $product[0], $product[1], $product[2], $product[3], $product[4], $product[5]);
        if ($insertProduct->execute()) {
            $inserted++;
        }
    }
    $insertProduct->close();
    
    if ($inserted > 0) {
        $success[] = "$inserted sample products inserted";
    }
} else {
    $success[] = "Products table already has $productCount products";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Smart Chrism Shop</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #25D366;
            margin-bottom: 30px;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 12px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 12px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #25D366;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px 10px 0;
        }
        .btn:hover {
            background: #128C7E;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗄️ Database Setup - Smart Chrism Shop</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <h3>❌ Errors:</h3>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success">
                <h3>✅ Success:</h3>
                <ul>
                    <?php foreach ($success as $msg): ?>
                        <li><?= htmlspecialchars($msg) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (empty($errors)): ?>
            <div class="info">
                <h3>🎉 Database Setup Complete!</h3>
                <p><strong>Default Admin Credentials:</strong></p>
                <ul>
                    <li><strong>Email:</strong> <?= htmlspecialchars($adminEmail) ?></li>
                    <li><strong>Password:</strong> <?= htmlspecialchars($adminPassword) ?></li>
                </ul>
                <p><em>Please change the password after first login!</em></p>
            </div>
            
            <div class="warning">
                <strong>⚠️ Security Notice:</strong>
                <p>For security, delete or disable this setup file after installation:</p>
                <code>setup_database.php</code>
            </div>
            
            <div style="margin-top: 30px;">
                <a href="login.php" class="btn">🔐 Go to Admin Login</a>
                <a href="index.html" class="btn btn-secondary">🏠 Go to Homepage</a>
            </div>
        <?php else: ?>
            <div class="error">
                <h3>⚠️ Setup Incomplete</h3>
                <p>Please fix the errors above and try again.</p>
                <p>Check:</p>
                <ul>
                    <li>MySQL service is running in XAMPP</li>
                    <li>Database credentials in config.php are correct</li>
                    <li>MySQL user has CREATE privileges</li>
                </ul>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px;">
            <p><strong>Database Information:</strong></p>
            <ul>
                <li>Database: <code>smartchrism</code></li>
                <li>Host: <code><?= htmlspecialchars($servername) ?></code></li>
                <li>Tables Created: <?= count($tables) ?></li>
            </ul>
        </div>
    </div>
</body>
</html>

