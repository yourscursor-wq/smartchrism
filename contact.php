<?php
require_once("config.php");

// Create contact_messages table if it doesn't exist
$createTableQuery = "CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('new', 'read', 'replied') DEFAULT 'new'
)";

if (!$conn->query($createTableQuery)) {
    error_log("Failed to create contact_messages table: " . $conn->error);
}

$msg = "";
$msgType = "";
$name = "";
$email = "";
$message = "";

// Handle both form POST and JSON POST requests
$input = file_get_contents('php://input');
$jsonData = json_decode($input, true);
$isJson = is_array($jsonData);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate input (support both form data and JSON)
    $name = trim($isJson ? ($jsonData['name'] ?? '') : ($_POST['name'] ?? ''));
    $email = filter_var(trim($isJson ? ($jsonData['email'] ?? '') : ($_POST['email'] ?? '')), FILTER_SANITIZE_EMAIL);
    $message = trim($isJson ? ($jsonData['message'] ?? '') : ($_POST['message'] ?? ''));
    
    // Validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required.";
    } elseif (strlen($name) > 255) {
        $errors[] = "Name is too long (maximum 255 characters).";
    } elseif (!preg_match("/^[a-zA-Z\s'-]+$/", $name)) {
        $errors[] = "Name contains invalid characters.";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address.";
    } elseif (strlen($email) > 255) {
        $errors[] = "Email is too long (maximum 255 characters).";
    }
    
    if (empty($message)) {
        $errors[] = "Message is required.";
    } elseif (strlen($message) < 10) {
        $errors[] = "Message is too short (minimum 10 characters).";
    } elseif (strlen($message) > 5000) {
        $errors[] = "Message is too long (maximum 5000 characters).";
    }
    
    if (empty($errors)) {
        // Store message in database
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
        
        if ($stmt) {
            $stmt->bind_param("sss", $name, $email, $message);
            
            if ($stmt->execute()) {
                // If JSON request, return JSON response
                if ($isJson) {
                    header('Content-Type: application/json');
                    echo json_encode(['ok' => true, 'message' => 'Thank you! Your message has been sent successfully.']);
                    exit;
                }
                
                // Try to send email
                try {
                    if (class_exists('PHPMailer')) {
                        $mail = new PHPMailer();
                        $mail->setFrom($email, $name);
                        $mail->addAddress("brianmoses237@gmail.com");
                        $mail->Subject = "Smart Chrism Contact Message from " . htmlspecialchars($name);
                        $mail->Body = "Name: " . htmlspecialchars($name) . "\n";
                        $mail->Body .= "Email: " . htmlspecialchars($email) . "\n\n";
                        $mail->Body .= "Message:\n" . htmlspecialchars($message);
                        
                        if ($mail->send()) {
                            $msg = "Thank you! Your message has been sent successfully.";
                            $msgType = "success";
                            // Clear form data
                            $name = $email = $message = "";
                        } else {
                            // Message saved to database but email failed
                            $msg = "Your message has been received. We'll get back to you soon!";
                            $msgType = "success";
                            error_log("PHPMailer Error: " . ($mail->ErrorInfo ?? "Unknown error"));
                            $name = $email = $message = "";
                        }
                    } else {
                        // PHPMailer not available, but message saved
                        $msg = "Your message has been received. We'll get back to you soon!";
                        $msgType = "success";
                        $name = $email = $message = "";
                    }
                } catch (Exception $e) {
                    // Email failed but message saved to database
                    $msg = "Your message has been received. We'll get back to you soon!";
                    $msgType = "success";
                    error_log("Email error: " . $e->getMessage());
                    $name = $email = $message = "";
                }
            } else {
                if ($isJson) {
                    header('Content-Type: application/json');
                    http_response_code(500);
                    echo json_encode(['ok' => false, 'error' => 'Error saving message. Please try again.']);
                    exit;
                }
                $msg = "Error saving message. Please try again.";
                $msgType = "error";
                error_log("Database error: " . $stmt->error);
            }
            $stmt->close();
        } else {
            if ($isJson) {
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode(['ok' => false, 'error' => 'Database error. Please try again.']);
                exit;
            }
            $msg = "Database error. Please try again.";
            $msgType = "error";
            error_log("Prepare error: " . $conn->error);
        }
    } else {
        if ($isJson) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => implode(" ", $errors)]);
            exit;
        }
        $msg = implode(" ", $errors);
        $msgType = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - Smart Chrism Shop</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <img src="logo.png" class="logo" alt="Smart Chrism Shop" onerror="this.style.display='none'">
        <nav>
            <a href="index.html">Home</a>
            <a href="shop.php">Shop</a>
            <a href="contact.php" class="active">Contact</a>
        </nav>
    </header>

    <main class="contact-container">
        <h2>Contact Us</h2>
        
        <div style="text-align:center;margin-bottom:20px;">
            <a href="https://wa.me/254705399169?text=Hello%20Smart%20Chrism%20Shop%2C%20I%20would%20like%20to%20contact%20you" 
               target="_blank" 
               class="btn whatsapp-btn" 
               style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;font-size:16px;text-decoration:none;">
                <span>💬</span> Chat on WhatsApp
            </a>
        </div>
        
        <p style="text-align:center;color:#666;margin-bottom:20px;">
            Or send us a message using the form below
        </p>
        
        <?php if ($msg): ?>
            <div class="message <?= htmlspecialchars($msgType) ?>">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <input 
                    type="text" 
                    name="name" 
                    placeholder="Your Name" 
                    value="<?= htmlspecialchars($name) ?>"
                    required
                    maxlength="255"
                    pattern="[a-zA-Z\s'-]+"
                    title="Name can only contain letters, spaces, hyphens, and apostrophes"
                >
            </div>
            
            <div class="form-group">
                <input 
                    type="email" 
                    name="email" 
                    placeholder="Your Email" 
                    value="<?= htmlspecialchars($email) ?>"
                    required
                    maxlength="255"
                >
            </div>
            
            <div class="form-group">
                <textarea 
                    name="message" 
                    placeholder="Message..." 
                    required
                    minlength="10"
                    maxlength="5000"
                    rows="6"
                ><?= htmlspecialchars($message) ?></textarea>
                <small>Minimum 10 characters, maximum 5000 characters</small>
            </div>
            
            <button class="btn" type="submit">Send Message</button>
        </form>
    </main>

    <!-- Floating WhatsApp Button -->
    <a href="https://wa.me/254705399169?text=Hello%20Smart%20Chrism%20Shop%2C%20I%20need%20help" 
       target="_blank" 
       class="whatsapp-float" 
       title="Chat with us on WhatsApp">
      <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M16 0C7.164 0 0 7.164 0 16c0 2.825.744 5.48 2.044 7.78L0 32l8.5-2.044C10.72 31.256 13.28 32 16 32c8.836 0 16-7.164 16-16S24.836 0 16 0zm0 29.333c-2.36 0-4.58-.64-6.5-1.76l-.465-.28-4.82 1.16 1.16-4.7-.28-.465C4.64 22.58 4 20.36 4 18c0-6.624 5.376-12 12-12s12 5.376 12 12-5.376 12-12 12z" fill="white"/>
        <path d="M23.5 18.5c-.3-.15-1.75-.86-2.02-.96-.27-.1-.47-.15-.67.15-.2.3-.77.96-.94 1.16-.17.2-.35.22-.65.07-.3-.15-1.27-.47-2.42-1.5-.9-.8-1.5-1.78-1.68-2.08-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.07-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.67-.51-.17-.01-.37-.01-.57-.01-.2 0-.52.07-.8.35-.28.28-1.08 1.05-1.08 2.56 0 1.51 1.1 2.97 1.25 3.17.15.2 2.15 3.28 5.22 4.6.72.3 1.28.48 1.72.62.73.22 1.4.19 1.93.12.58-.08 1.75-.72 2-1.42.25-.7.25-1.3.17-1.42-.08-.12-.3-.2-.6-.35z" fill="white"/>
      </svg>
    </a>

    <footer>
        <p>&copy; <?= date('Y') ?> Smart Chrism Shop.</p>
        <p style="margin-top:10px;">
            <a href="https://wa.me/254705399169" target="_blank" style="color:#25D366;text-decoration:none;">
                💬 WhatsApp: 0705399169
            </a>
        </p>
    </footer>
</body>
</html>
