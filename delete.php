<?php
require_once("config.php");
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Check if product ID is provided and validate it
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Product ID is required.";
    header("Location: products.php");
    exit();
}

// Sanitize and validate product ID
$id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

if ($id === false || $id <= 0) {
    $_SESSION['error'] = "Invalid product ID.";
    header("Location: products.php");
    exit();
}

// Get product information before deleting (to delete image file)
$stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
if (!$stmt) {
    error_log("Failed to prepare statement: " . $conn->error);
    $_SESSION['error'] = "Database error. Please try again.";
    header("Location: products.php");
    exit();
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $_SESSION['error'] = "Product not found.";
    header("Location: products.php");
    exit();
}

$product = $result->fetch_assoc();
$imagePath = $product['image'];
$stmt->close();

// Delete the product from database
$deleteStmt = $conn->prepare("DELETE FROM products WHERE id = ?");
if (!$deleteStmt) {
    error_log("Failed to prepare delete statement: " . $conn->error);
    $_SESSION['error'] = "Database error. Please try again.";
    header("Location: products.php");
    exit();
}

$deleteStmt->bind_param("i", $id);

if ($deleteStmt->execute()) {
    // Delete the associated image file if it exists
    if (!empty($imagePath)) {
        // Image path is stored as 'uploads/filename.jpg' relative to this directory
        $fullImagePath = __DIR__ . '/' . ltrim($imagePath, '/');
        
        // Also try the path as-is in case it's already absolute
        $pathsToTry = [
            $fullImagePath,
            $imagePath,
        ];
        
        $imageDeleted = false;
        foreach ($pathsToTry as $path) {
            if (file_exists($path) && is_file($path)) {
                if (@unlink($path)) {
                    error_log("Deleted image file: " . $path);
                    $imageDeleted = true;
                    break;
                } else {
                    error_log("Failed to delete image file (permission issue?): " . $path);
                }
            }
        }
        
        if (!$imageDeleted) {
            error_log("Image file not found or could not be deleted: " . $imagePath);
            // Continue with deletion even if image deletion fails
            // The database record is already deleted
        }
    }
    
    $_SESSION['success'] = "Product deleted successfully.";
    $deleteStmt->close();
    header("Location: products.php");
    exit();
} else {
    error_log("Failed to delete product: " . $deleteStmt->error);
    $_SESSION['error'] = "Failed to delete product. Please try again.";
    $deleteStmt->close();
    header("Location: products.php");
    exit();
}
?>
