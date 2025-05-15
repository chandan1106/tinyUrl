<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

// Set response content type to JSON
header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get request data
$data = json_decode(file_get_contents('php://input'), true);

// Validate API key
$apiKey = isset($data['api_key']) ? $data['api_key'] : '';
$shortCode = isset($data['short_code']) ? $data['short_code'] : '';

if (empty($apiKey)) {
    http_response_code(401);
    echo json_encode(['error' => 'API key is required']);
    exit;
}

if (empty($shortCode)) {
    http_response_code(400);
    echo json_encode(['error' => 'Short code is required']);
    exit;
}

// Check if API key exists and get associated user
$stmt = $conn->prepare("SELECT user_id FROM api_keys WHERE api_key = ? AND active = 1");
$stmt->bind_param("s", $apiKey);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid API key']);
    exit;
}

$row = $result->fetch_assoc();
$userId = $row['user_id'];
$stmt->close();

// Get URL stats
$stmt = $conn->prepare("
    SELECT id, original_url, short_code, created_at, click_count 
    FROM urls 
    WHERE short_code = ? AND user_id = ?
");
$stmt->bind_param("si", $shortCode, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'URL not found or not owned by this user']);
    exit;
}

$url = $result->fetch_assoc();
$stmt->close();

// Return URL stats
echo json_encode([
    'success' => true,
    'url_id' => $url['id'],
    'original_url' => $url['original_url'],
    'short_url' => BASE_URL . '/' . $url['short_code'],
    'short_code' => $url['short_code'],
    'created_at' => $url['created_at'],
    'click_count' => $url['click_count']
]);
?>