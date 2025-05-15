<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the short code from the URL
$shortCode = isset($_GET['code']) ? $_GET['code'] : '';

if (empty($shortCode)) {
    header('Location: index_simple.php');
    exit;
}

// Check if the URL exists in session
if (isset($_SESSION['urls'][$shortCode])) {
    $url = $_SESSION['urls'][$shortCode];
    $originalUrl = $url['original_url'];
    
    // Update click count
    $_SESSION['urls'][$shortCode]['click_count']++;
    
    // Redirect to the original URL
    header('Location: ' . $originalUrl);
    exit;
} else {
    // Try to check in the database if available
    if (file_exists('config/config.php')) {
        require_once 'config/config.php';
        
        if (isset($conn) && $conn instanceof mysqli) {
            // Look up the original URL in the database
            $stmt = $conn->prepare("SELECT id, original_url FROM urls WHERE short_code = ?");
            if ($stmt) {
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
                }
                
                $stmt->close();
            }
        }
    }
    
    // URL not found in session or database
    header('Location: index_simple.php?error=url_not_found');
    exit;
}
?>