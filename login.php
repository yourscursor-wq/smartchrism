<?php
require_once("config.php");
session_start();

// Redirect if already logged in
if (isset($_SESSION['admin'])) {
    header("Location: dashboard.php");
    exit();
}

$msg = "";
$msgType = "";
$email = "";

// Check for logout success message
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $msg = "You have been successfully logged out.";
    $msgType = "success";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation
    if (empty($email)) {
        $msg = "Email is required.";
        $msgType = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "Invalid email format.";
        $msgType = "error";
    } elseif (empty($password)) {
        $msg = "Password is required.";
        $msgType = "error";
    } else {
        // Use prepared statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, email, password FROM admins WHERE email = ? LIMIT 1");
        
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                
                // Verify password
                if (password_verify($password, $row['password'])) {
                    // Set session variables
                    $_SESSION['admin'] = $row['email'];
                    $_SESSION['admin_id'] = $row['id'];
                    
                    // Regenerate session ID for security
                    session_regenerate_id(true);
                    
                    // Redirect to dashboard
                    header("Location: dashboard.php");
                    exit();
                } else {
                    // Generic error message to prevent user enumeration
                    $msg = "Invalid email or password.";
                    $msgType = "error";
                    // Don't reveal which field was wrong
                }
            } else {
                // Generic error message to prevent user enumeration
                $msg = "Invalid email or password.";
                $msgType = "error";
            }
            
            $stmt->close();
        } else {
            error_log("Database error in login: " . $conn->error);
            $msg = "Database error. Please try again later.";
            $msgType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Smart Chrism Shop</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-form-wrapper">
            <div class="login-header">
                <h1>Smart Chrism Shop</h1>
                <h2>Admin Login</h2>
            </div>
            
            <?php if ($msg): ?>
                <div class="message <?= htmlspecialchars($msgType, ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form" action="">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email"
                        name="email" 
                        placeholder="Enter your email" 
                        value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                        required
                        autocomplete="email"
                        autofocus
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password"
                        name="password" 
                        placeholder="Enter your password" 
                        required
                        autocomplete="current-password"
                    >
                </div>
                
                <button type="submit" class="btn btn-primary">Login</button>
                
                <div class="login-footer">
                    <p><a href="index.html">← Back to Home</a></p>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

