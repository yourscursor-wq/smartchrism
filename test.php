<?php
// Simple test endpoint to verify PHP is working
header('Content-Type: application/json');
echo json_encode(['ok' => true, 'message' => 'PHP is working!', 'timestamp' => date('Y-m-d H:i:s')]);
?>
