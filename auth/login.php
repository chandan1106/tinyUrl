<?php
session_start();
require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method not allowed";
    exit;
}

// Get ID token from request
$idToken = isset($_POST['idToken']) ? $_POST['idToken'] : '';

if (empty($idToken)) {
    http_response_code(400);
    echo "ID token is required";
    exit;
}

// Validate Firebase token
$userData = validateFirebaseToken($idToken);

if (!$userData) {
    http_response_code(401);
    echo "Invalid ID token";
    exit;
}

$firebaseUid = $userData['localId'];
$email = $userData['email'];

// Check if user exists in database
$user = getUserByFirebaseUid($firebaseUid);

if (!$user) {
    // Create user if not exists
    $userId = createUser($firebaseUid, $email);
    
    if (!$userId) {
        http_response_code(500);
        echo "Failed to create user";
        exit;
    }
} else {
    $userId = $user['id'];
}

// Set session variables
$_SESSION['user_id'] = $userId;
$_SESSION['firebase_uid'] = $firebaseUid;
$_SESSION['email'] = $email;

// Return success
http_response_code(200);
echo "Login successful";
?>