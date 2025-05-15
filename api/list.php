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
$limit = isset($data['limit']) ? intval($data['limit']) : 10;
$offset = isset($data['offset']) ? intval($data['offset']) : 0;

if (empty($apiKey)) {
    http_response_code(401);
    echo json_encode(['error' => 'API key is required']);
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

// Get user's URLs
$stmt = $conn->prepare("
    SELECT id, original_url, short_code, created_at, click_count 
    FROM urls 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->bind_param("iii", $userId, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

$urls = [];
while ($row = $result->fetch_assoc()) {
    $row['short_url'] = BASE_URL . '/' . $row['short_code'];
    $urls[] = $row;
}
$stmt->close();

// Get total count
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM urls WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$total = $result->fetch_assoc()['total'];
$stmt->close();

// Return URLs
echo json_encode([
    'success' => true,
    'total' => $total,
    'limit' => $limit,
    'offset' => $offset,
    'urls' => $urls
]);
?>