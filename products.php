<?php
require_once("config.php");
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$products = $conn->query("SELECT * FROM products ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Products - Smart Chrism Shop</title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="admin-body">
  <div class="sidebar">
      <h2>Smart Chrism</h2>
      <ul>
          <li><a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">Dashboard</a></li>
          <li><a href="add product.php" class="<?= basename($_SERVER['PHP_SELF']) == 'add product.php' ? 'active' : '' ?>">Add Product</a></li>
          <li><a href="products.php" class="<?= basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : '' ?>">Manage Products</a></li>
          <li><a href="analytics.php" class="<?= basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : '' ?>">Analytics</a></li>
          <li><a href="logout.php">Logout</a></li>
      </ul>
  </div>

  <div class="content">
    <h1>Products</h1>
    <a href="add product.php" class="btn">Add Product</a>
    <table>
      <tr><th>ID</th><th>Image</th><th>Name</th><th>Price</th><th>Category</th><th>Action</th></tr>
      <?php while($p = $products->fetch_assoc()): ?>
      <tr>
        <td><?php echo (int)$p['id']; ?></td>
        <td>
          <?php
            $img = $p['image'] ?? '';
            // If full URL already stored, use it as-is; otherwise prefix with "./"
            $imgSrc = (strpos($img, 'http://') === 0 || strpos($img, 'https://') === 0)
              ? $img
              : './' . ltrim($img, '/');
          ?>
          <?php if ($img): ?>
            <img src="<?php echo htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8'); ?>" width="50" alt="Product image">
          <?php else: ?>
            <span>No image</span>
          <?php endif; ?>
        </td>
        <td><?php echo htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td>KSh <?php echo number_format($p['price'],2); ?></td>
        <td><?php echo htmlspecialchars($p['category'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td>
          <a href="edit_product.php?id=<?php echo (int)$p['id']; ?>">Edit</a> |
          <a href="delete.php?id=<?php echo (int)$p['id']; ?>">Delete</a>
        </td>
      </tr>
      <?php endwhile; ?>
    </table>
  </div>
</body>
</html>
