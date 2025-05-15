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
$url = isset($data['url']) ? $data['url'] : '';

if (empty($apiKey)) {
    http_response_code(401);
    echo json_encode(['error' => 'API key is required']);
    exit;
}

// Validate URL
if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Valid URL is required']);
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

// Generate short code
$shortCode = generateShortCode();

// Insert URL into database
$stmt = $conn->prepare("INSERT INTO urls (user_id, original_url, short_code, created_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("iss", $userId, $url, $shortCode);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to shorten URL']);
    exit;
}

$urlId = $stmt->insert_id;
$stmt->close();

// Return shortened URL
$shortUrl = BASE_URL . '/' . $shortCode;
echo json_encode([
    'success' => true,
    'url_id' => $urlId,
    'original_url' => $url,
    'short_url' => $shortUrl,
    'short_code' => $shortCode
]);
?>