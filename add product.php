<?php
require_once("config.php");
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$current = basename($_SERVER['PHP_SELF']);
$msg = "";
$msgType = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate input
    $name = trim($_POST['name'] ?? '');
    $price = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT);
    $cat = trim($_POST['category'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    // Validate inputs
    if (empty($name)) {
        $msg = "Product name is required.";
        $msgType = "error";
    } elseif ($price === false || $price <= 0) {
        $msg = "Please enter a valid price.";
        $msgType = "error";
    } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $msg = "Please select a valid image file.";
        $msgType = "error";
    } else {
        // Validate file upload
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = $_FILES['image']['type'];
        $fileSize = $_FILES['image']['size'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($fileType, $allowedTypes)) {
            $msg = "Invalid file type. Please upload an image (JPEG, PNG, GIF, or WebP).";
            $msgType = "error";
        } elseif ($fileSize > $maxSize) {
            $msg = "File size too large. Maximum size is 5MB.";
            $msgType = "error";
        } else {
            // Generate unique filename
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('product_', true) . '.' . $extension;

            // Store images in the public "uploads" folder so they are accessible online
            // Physical path on server
            $uploadDir = __DIR__ . '/uploads/';

            // Create uploads directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Full filesystem path and relative web path
            $imagePath   = $uploadDir . $filename;
            $relativePath = 'uploads/' . $filename;

            // Move uploaded file
            if (move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
                // Use prepared statement to prevent SQL injection
                $stmt = $conn->prepare("INSERT INTO products (name, price, category, description, image) VALUES (?, ?, ?, ?, ?)");
                
                if ($stmt) {
                    $stmt->bind_param("sdsss", $name, $price, $cat, $desc, $relativePath);
                    
                    if ($stmt->execute()) {
                        $msg = "Product added successfully!";
                        $msgType = "success";
                        // Clear form data
                        $name = $price = $cat = $desc = "";
                    } else {
                        $msg = "Error adding product: " . $stmt->error;
                        $msgType = "error";
                        // Remove uploaded file if database insert failed
                        if (file_exists($imagePath)) {
                            unlink($imagePath);
                        }
                    }
                    $stmt->close();
                } else {
                    $msg = "Database error: " . $conn->error;
                    $msgType = "error";
                    // Remove uploaded file if database prepare failed
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                }
            } else {
                $msg = "Error uploading file. Please try again.";
                $msgType = "error";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Smart Chrism Shop</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="admin-body">
    <div class="sidebar">
        <h2>Smart Chrism</h2>
        <ul>
            <li><a href="dashboard.php" class="<?= $current == 'dashboard.php' ? 'active' : '' ?>">Dashboard</a></li>
            <li><a href="add product.php" class="<?= $current == 'add product.php' ? 'active' : '' ?>">Add Product</a></li>
            <li><a href="products.php" class="<?= $current == 'products.php' ? 'active' : '' ?>">Manage Products</a></li>
            <li><a href="analytics.php" class="<?= $current == 'analytics.php' ? 'active' : '' ?>">Analytics</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>
    <div class="content">
        <h1>Add Product</h1>
        <?php if ($msg): ?>
            <p class="<?= $msgType ?>"><?= htmlspecialchars($msg) ?></p>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="name" placeholder="Product Name" value="<?= isset($name) ? htmlspecialchars($name) : '' ?>" required>
            <input type="number" name="price" step="0.01" min="0" placeholder="Price" value="<?= isset($price) ? htmlspecialchars($price) : '' ?>" required>
            <input type="text" name="category" placeholder="Category" value="<?= isset($cat) ? htmlspecialchars($cat) : '' ?>">
            <textarea name="description" placeholder="Description"><?= isset($desc) ? htmlspecialchars($desc) : '' ?></textarea>
            <input type="file" name="image" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" required>
            <button class="btn" type="submit">Save</button>
        </form>
    </div>
</body>
</html>
