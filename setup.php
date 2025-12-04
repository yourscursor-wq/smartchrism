<?php
require_once("config.php");

$queries = [
  "CREATE TABLE IF NOT EXISTS admins (
      id INT AUTO_INCREMENT PRIMARY KEY,
      email VARCHAR(100) UNIQUE NOT NULL,
      password VARCHAR(255) NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  )",
  "CREATE TABLE IF NOT EXISTS products (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(255) NOT NULL,
      price DECIMAL(10,2) NOT NULL,
      category VARCHAR(100),
      description TEXT,
      image VARCHAR(255),
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  )",
  "CREATE TABLE IF NOT EXISTS orders (
      id INT AUTO_INCREMENT PRIMARY KEY,
      customer_name VARCHAR(255),
      phone VARCHAR(50),
      email VARCHAR(100),
      product_name VARCHAR(255),
      total_amount DECIMAL(10,2),
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  )",
  "CREATE TABLE IF NOT EXISTS settings (
      id INT AUTO_INCREMENT PRIMARY KEY,
      shop_name VARCHAR(255),
      email VARCHAR(100),
      location VARCHAR(255),
      paybill VARCHAR(50),
      account VARCHAR(50)
  )"
];

foreach ($queries as $sql) {
  $conn->query($sql);
}

// Default admin
$defaultAdmin = "brianmoses237@gmail.com";
$defaultPass = password_hash("admin123", PASSWORD_DEFAULT);
$conn->query("INSERT IGNORE INTO admins (email, password) VALUES ('$defaultAdmin', '$defaultPass')");

// Default shop info
$conn->query("INSERT IGNORE INTO settings (shop_name, email, location, paybill, account)
VALUES ('Smart Chrism Shop', 'brianmoses237@gmail.com', 'Nairobi OTC 2nd Floor Wholesale Mall', '247247', '0705399169')");

// Sample products
$products = [
  ['Nike Air Max', 6500, 'Sneakers', 'Comfortable everyday sneaker', 'uploads/nike_air_max.jpg'],
  ['Adidas Ultraboost', 7200, 'Running', 'Lightweight performance running shoe', 'uploads/adidas_ultraboost.jpg'],
  ['Converse All Star', 4800, 'Casual', 'Classic canvas style sneaker', 'uploads/converse_allstar.jpg'],
  ['Puma Drift Cat', 5600, 'Sport', 'Sleek low-profile motorsport shoe', 'uploads/puma_driftcat.jpg'],
  ['Jordan Retro 4', 8900, 'Premium', 'Iconic retro basketball shoe', 'uploads/jordan_retro4.jpg']
];

foreach ($products as $p) {
  [$name, $price, $cat, $desc, $img] = $p;
  $conn->query("INSERT IGNORE INTO products (name, price, category, description, image)
    VALUES ('$name', '$price', '$cat', '$desc', '$img')");
}

// Redirect to dashboard
header("Location: admin/dashboard.php");
exit();
?>
