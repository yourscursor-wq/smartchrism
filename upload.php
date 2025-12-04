<?php

// Simple image upload handler for product images
// Supports both HTML form submissions (returns HTML page) and AJAX calls (returns JSON)

$isAjax = isset($_POST['ajax']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
function respond_json($payload, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function fail($message, $status = 400) {
    global $isAjax;
    if ($isAjax) {
        respond_json(['ok' => false, 'error' => $message], $status);
    } else {
        die($message);
    }
}

// Directory (relative to this file) where images will be stored
$uploadDir = __DIR__ . '/uploads/';
$publicPath = 'uploads/'; // Path used in the final image URL

// Create upload directory if it doesn't exist
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Only proceed if a file was sent
if (!isset($_FILES['id_card']) || $_FILES['id_card']['error'] !== UPLOAD_ERR_OK) {
    fail('No image file uploaded or upload error.');
}

$file     = $_FILES['id_card'];
$fileName = $file['name'];
$tmpPath  = $file['tmp_name'];
$fileSize = $file['size'];

// Allowed image extensions
$allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if (!in_array($ext, $allowedExt)) {
    fail('Invalid image format. Allowed: JPG, JPEG, PNG, GIF');
}

// Optional: limit file size (e.g. 5MB)
$maxSize = 5 * 1024 * 1024;
if ($fileSize > $maxSize) {
    fail('File too large. Max 5MB allowed.');
}

// Generate a safe unique file name
$newName = 'IMG_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
$targetPath = $uploadDir . $newName;

// Move uploaded file
if (!is_uploaded_file($tmpPath) || !move_uploaded_file($tmpPath, $targetPath)) {
    fail('Failed to move uploaded file.');
}

// Build the public URL (works both locally and online if the "public" folder is web root)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$basePath = $basePath === '.' ? '' : $basePath;
$imageUrl = $protocol . $host . $basePath . '/' . $publicPath . $newName;

if ($isAjax) {
    respond_json([
        'ok' => true,
        'url' => $imageUrl,
        'filename' => $newName
    ]);
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Image Upload Result</title>
</head>
<body>
    <h2>Image Uploaded Successfully!</h2>
    <p><strong>File name:</strong> <?php echo htmlspecialchars($newName, ENT_QUOTES, 'UTF-8'); ?></p>
    <p><strong>Image URL (online):</strong><br>
        <a href="<?php echo htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank">
            <?php echo htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8'); ?>
        </a>
    </p>
    <p><strong>Preview:</strong></p>
    <img src="<?php echo htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Uploaded image" style="max-width:300px;height:auto;">
</body>
</html>
