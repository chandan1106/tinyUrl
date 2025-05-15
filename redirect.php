<?php
require_once 'config/config.php';

// Get the short code from the URL
$shortCode = isset($_GET['code']) ? $_GET['code'] : '';

if (empty($shortCode)) {
    header('Location: index.php');
    exit;
}

// Look up the original URL in the database
$stmt = $conn->prepare("SELECT id, original_url FROM urls WHERE short_code = ?");
$stmt->bind_param("s", $shortCode);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $originalUrl = $row['original_url'];
    $urlId = $row['id'];
    
    // Update click count
    $updateStmt = $conn->prepare("UPDATE urls SET click_count = click_count + 1 WHERE id = ?");
    $updateStmt->bind_param("i", $urlId);
    $updateStmt->execute();
    $updateStmt->close();
    
    // Redirect to the original URL
    header('Location: ' . $originalUrl);
    exit;
} else {
    // Short URL not found
    header('Location: index.php?error=url_not_found');
    exit;
}

$stmt->close();
$conn->close();
?>