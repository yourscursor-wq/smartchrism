<?php
// Smart Chrism Shop - Database Connection

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "smartchrism";
$port = 3306; // Default MySQL port, change to 3307 if needed

// Error reporting (production settings)
// Set to E_ALL for development, E_ALL & ~E_DEPRECATED & ~E_STRICT for production
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 0); // Always 0 in production - errors logged only
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log'); // Log errors to file

// Create connection with error handling
// Try default port first, then fallback to 3307
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

// Validate database name (alphanumeric and underscores only)
if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbname)) {
    error_log("Invalid database name: " . $dbname);
    die("Invalid database name");
}

// Create database if not exists (using backticks for identifier)
$createDbQuery = "CREATE DATABASE IF NOT EXISTS `$dbname`";
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
    // Fallback to utf8 if utf8mb4 is not available
    $conn->set_charset("utf8");
}

// Define root path
define("SITE_ROOT", dirname(__FILE__));

// Define base URL (update with your production domain)
if (!defined("BASE_URL")) {
    // Auto-detect from server or set your production domain
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    define("BASE_URL", $protocol . $host . ($path !== '.' ? $path . '/' : '/'));
}

// PHPMailer path (conditional - only load if it exists)
$phpmailerPath = __DIR__ . '/admin/phpmailer/PHPMailerAutoload.php';
$phpmailerAltPath = __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';

if (file_exists($phpmailerPath)) {
    require_once $phpmailerPath;
} elseif (file_exists($phpmailerAltPath)) {
    // Modern PHPMailer (via Composer)
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    // PHPMailer not found - define a fallback class to prevent errors
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
                error_log("PHPMailer not installed - email functionality disabled. Attempted to send: Subject - " . $this->Subject);
                return false;
            }
        }
    }
}

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    // Session settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    // Only enable secure cookies when using HTTPS
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    ini_set('session.cookie_secure', $isHttps ? 1 : 0);
    
    // Start session if not already started
    // Note: Don't start session here - let individual files start it when needed
    // session_start();
}

// Timezone setting
date_default_timezone_set('Africa/Nairobi'); // Adjust to your timezone

// ================ M-Pesa Paybill Configuration ================
// 
// IMPORTANT: Get your credentials from Safaricom Daraja Portal:
// https://developer.safaricom.co.ke/
//
// Step 1: Login to Daraja Portal
// Step 2: Create an App and select "Lipa na M-Pesa Online"
// Step 3: Copy your Consumer Key and Consumer Secret
// Step 4: Get your Shortcode (Paybill number) and Passkey
// Step 5: Update the values below with your real credentials
//
// See MPESA_SETUP_GUIDE.md for detailed instructions
// ================================================================

if (!defined('MPESA_ENV')) {
    // Set to 'sandbox' for testing, 'production' for live transactions
    define('MPESA_ENV', 'production');
}

if (!defined('MPESA_CONSUMER_KEY')) {
    // Get this from: Daraja Portal → My Apps → Your App → Consumer Key
    // Example: 'abcdefghijklmnopqrstuvwxyz1234567890'
    define('MPESA_CONSUMER_KEY', 'YOUR_CONSUMER_KEY_HERE');
}

if (!defined('MPESA_CONSUMER_SECRET')) {
    // Get this from: Daraja Portal → My Apps → Your App → Consumer Secret
    // Example: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'
    define('MPESA_CONSUMER_SECRET', 'YOUR_CONSUMER_SECRET_HERE');
}

if (!defined('MPESA_SHORTCODE')) {
    // Your actual Paybill/Business shortcode (NOT 600000 - that's for sandbox only)
    // Get this from: Daraja Portal → Lipa na M-Pesa Online → Shortcode
    // Example: '247247' (replace with your actual Paybill number)
    define('MPESA_SHORTCODE', '247247');
}

if (!defined('MPESA_PASSKEY')) {
    // Get this from: Daraja Portal → Lipa na M-Pesa Online → Generate Password
    // This is a long string used to generate the password for each transaction
    // Example: 'abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ'
    define('MPESA_PASSKEY', 'YOUR_PASSKEY_HERE');
}

if (!defined('MPESA_CALLBACK_URL')) {
    // Publicly accessible HTTPS URL that Safaricom will call with payment results
    // 
    // FOR PRODUCTION: Use your actual domain
    // Example: 'https://smartchrismshop.com/kk/mpesa_callback.php'
    //
    // FOR LOCAL TESTING: Use ngrok (ngrok http 80)
    // Example: 'https://abc123.ngrok.io/kk/mpesa_callback.php'
    //
    // IMPORTANT: 
    // - Must be HTTPS (not HTTP)
    // - Must be publicly accessible (not localhost)
    // - Must point to mpesa_callback.php file
    
    // Auto-generate from current server (you can override this manually)
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'https://';
    $host = $_SERVER['HTTP_HOST'] ?? 'yourdomain.com';
    $callbackPath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/mpesa_callback.php';
    
    // Uncomment and set manually if auto-detection doesn't work:
    // define('MPESA_CALLBACK_URL', 'https://yourdomain.com/kk/mpesa_callback.php');
    
    // Auto-generated (fallback):
    define('MPESA_CALLBACK_URL', $protocol . $host . $callbackPath);
}

?>
