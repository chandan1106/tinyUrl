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

// Check if user already exists
$user = getUserByFirebaseUid($firebaseUid);

if ($user) {
    http_response_code(409);
    echo "User already exists";
    exit;
}

// Create new user
$userId = createUser($firebaseUid, $email);

if (!$userId) {
    http_response_code(500);
    echo "Failed to create user";
    exit;
}

// Set session variables
$_SESSION['user_id'] = $userId;
$_SESSION['firebase_uid'] = $firebaseUid;
$_SESSION['email'] = $email;

// Return success
http_response_code(200);
echo "Registration successful";
?>