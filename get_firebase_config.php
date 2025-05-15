<?php
header('Content-Type: application/json');
require_once 'config/config.php';

// Return Firebase configuration for client-side use
echo json_encode([
    'apiKey' => FIREBASE_API_KEY,
    'authDomain' => $_ENV['FIREBASE_AUTH_DOMAIN'] ?? '',
    'projectId' => $_ENV['FIREBASE_PROJECT_ID'] ?? '',
    'storageBucket' => $_ENV['FIREBASE_STORAGE_BUCKET'] ?? '',
    'messagingSenderId' => $_ENV['FIREBASE_MESSAGING_SENDER_ID'] ?? '',
    'appId' => $_ENV['FIREBASE_APP_ID'] ?? '',
    'measurementId' => $_ENV['FIREBASE_MEASUREMENT_ID'] ?? ''
]);
?>