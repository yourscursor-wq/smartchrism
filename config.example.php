<?php
// Smart Chrism Shop - Configuration Example
// 
// INSTRUCTIONS:
// 1. Copy this file to config.php
// 2. Update all placeholder values with your actual credentials
// 3. Never commit config.php to version control
//

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";  // Your MySQL password
$dbname = "smartchrism";
$port = 3306; // Default MySQL port, change to 3307 if needed

// Error reporting (production settings)
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 0); // Always 0 in production
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log');

// Create connection with error handling
$conn = @new mysqli($servername, $username, $password, '', $port);

// If connection failed, try port 3307 (common XAMPP alternative)
if ($conn->connect_error) {
    error_log("MySQL connection failed on port $port: " . $conn->connect_error);
    $port = 3307;
    $conn = @new mysqli($servername, $username, $password, '', $port);
    
    if ($conn->connect_error) {
        error_log("MySQL connection also failed on port $port: " . $conn->connect_error);
    } else {
        error_log("MySQL connected successfully on port $port");
    }
}

// Check connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Connection failed: " . $conn->connect_error);
}

// Validate database name
if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbname)) {
    error_log("Invalid database name: " . $dbname);
    die("Invalid database name");
}

// Create database if not exists
$createDbQuery = "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if (!$conn->query($createDbQuery)) {
    error_log("Failed to create database: " . $conn->error);
    die("Failed to create database: " . $conn->error);
}

// Select database
if (!$conn->select_db($dbname)) {
    error_log("Failed to select database: " . $conn->error);
    die("Failed to select database: " . $conn->error);
}

// Set charset to UTF-8
if (!$conn->set_charset("utf8mb4")) {
    error_log("Error setting charset: " . $conn->error);
    $conn->set_charset("utf8");
}

// Define root path
define("SITE_ROOT", dirname(__FILE__));

// Define base URL
if (!defined("BASE_URL")) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    define("BASE_URL", $protocol . $host . ($path !== '.' ? $path . '/' : '/'));
}

// PHPMailer path (optional)
$phpmailerPath = __DIR__ . '/admin/phpmailer/PHPMailerAutoload.php';
$phpmailerAltPath = __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';

if (file_exists($phpmailerPath)) {
    require_once $phpmailerPath;
} elseif (file_exists($phpmailerAltPath)) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer') && !class_exists('PHPMailer')) {
        class PHPMailer {
            public $Subject = '';
            public $Body = '';
            public $ErrorInfo = 'PHPMailer not installed';
            
            public function setFrom($email, $name = '') {
                return true;
            }
            
            public function addAddress($email, $name = '') {
                return true;
            }
            
            public function send() {
                error_log("PHPMailer not installed - email functionality disabled");
                return false;
            }
        }
    }
}

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    ini_set('session.cookie_secure', $isHttps ? 1 : 0);
}

// Timezone setting
date_default_timezone_set('Africa/Nairobi');

// ================ M-Pesa Configuration ================
// 
// GET YOUR CREDENTIALS FROM: https://developer.safaricom.co.ke/
//
// 1. Login to Daraja Portal
// 2. Create an App → Select "Lipa na M-Pesa Online"
// 3. Copy Consumer Key and Consumer Secret
// 4. Get Shortcode (Paybill) and Passkey
// 5. Update values below
//
// =====================================================

if (!defined('MPESA_ENV')) {
    define('MPESA_ENV', 'production'); // or 'sandbox' for testing
}

if (!defined('MPESA_CONSUMER_KEY')) {
    // From: Daraja Portal → My Apps → Your App → Consumer Key
    define('MPESA_CONSUMER_KEY', 'YOUR_CONSUMER_KEY_HERE');
}

if (!defined('MPESA_CONSUMER_SECRET')) {
    // From: Daraja Portal → My Apps → Your App → Consumer Secret
    define('MPESA_CONSUMER_SECRET', 'YOUR_CONSUMER_SECRET_HERE');
}

if (!defined('MPESA_SHORTCODE')) {
    // From: Daraja Portal → Lipa na M-Pesa Online → Shortcode
    // This is your actual Paybill number (NOT 600000)
    define('MPESA_SHORTCODE', 'YOUR_PAYBILL_NUMBER_HERE');
}

if (!defined('MPESA_PASSKEY')) {
    // From: Daraja Portal → Lipa na M-Pesa Online → Generate Password
    define('MPESA_PASSKEY', 'YOUR_PASSKEY_HERE');
}

if (!defined('MPESA_CALLBACK_URL')) {
    // Must be HTTPS and publicly accessible
    // Production: 'https://yourdomain.com/kk/mpesa_callback.php'
    // Local testing: Use ngrok → 'https://abc123.ngrok.io/kk/mpesa_callback.php'
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'https://';
    $host = $_SERVER['HTTP_HOST'] ?? 'yourdomain.com';
    $callbackPath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/mpesa_callback.php';
    define('MPESA_CALLBACK_URL', $protocol . $host . $callbackPath);
}

?>
