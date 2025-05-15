<?php
session_start();
require_once '../config/config.php';
require_once '../includes/functions.php';

// Set response content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Generate a new API key
$apiKey = bin2hex(random_bytes(16)); // 32 character hex string

// Insert API key into database
$stmt = $conn->prepare("INSERT INTO api_keys (user_id, api_key, created_at) VALUES (?, ?, NOW())");
$stmt->bind_param("is", $userId, $apiKey);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to generate API key']);
    exit;
}

$keyId = $stmt->insert_id;
$stmt->close();

// Return the new API key
echo json_encode([
    'success' => true,
    'key_id' => $keyId,
    'api_key' => $apiKey
]);
?>