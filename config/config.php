<?php
// Load environment variables
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Database configuration
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'tinyurl_db');

// Base URL for shortened URLs
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST']);

// Firebase configuration
define('FIREBASE_API_KEY', $_ENV['FIREBASE_API_KEY'] ?? '');

// Connect to MySQL database
$conn = null;
try {
    // Check if database exists, if not create it
    $tempConn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    if (!$tempConn->select_db(DB_NAME)) {
        $tempConn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    }
    $tempConn->close();
    
    // Connect to the database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Set character set
    $conn->set_charset("utf8mb4");
    
    // Check if tables exist, if not create them
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows == 0) {
        // Create users table
        $conn->query("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            firebase_uid VARCHAR(128) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL,
            display_name VARCHAR(255) DEFAULT '',
            photo_url TEXT,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (firebase_uid),
            INDEX (email)
        )");
        
        // Create URLs table
        $conn->query("CREATE TABLE IF NOT EXISTS urls (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            original_url TEXT NOT NULL,
            short_code VARCHAR(10) NOT NULL UNIQUE,
            click_count INT DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX (short_code),
            INDEX (user_id)
        )");
        
        // Create API keys table
        $conn->query("CREATE TABLE IF NOT EXISTS api_keys (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            api_key VARCHAR(64) NOT NULL UNIQUE,
            active BOOLEAN DEFAULT TRUE,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX (api_key),
            INDEX (user_id)
        )");
    }
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}
?>