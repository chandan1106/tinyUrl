<?php
/**
 * Generate a unique short code for URLs
 * 
 * @param int $length Length of the short code
 * @return string The generated short code
 */
function generateShortCode($length = 6) {
    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    
    // Check if code already exists in database
    global $conn;
    
    // Check if table exists first to avoid errors
    $tableExists = false;
    $result = $conn->query("SHOW TABLES LIKE 'urls'");
    if ($result && $result->num_rows > 0) {
        $tableExists = true;
    }
    
    if ($tableExists) {
        $stmt = $conn->prepare("SELECT id FROM urls WHERE short_code = ?");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // If code exists, generate a new one recursively
        if ($result->num_rows > 0) {
            $stmt->close();
            return generateShortCode($length);
        }
        
        $stmt->close();
    }
    
    return $code;
}

/**
 * Validate Firebase ID token
 * 
 * @param string $idToken Firebase ID token
 * @return array User data if token is valid
 */
function validateFirebaseToken($idToken) {
    $apiKey = FIREBASE_API_KEY;
    $url = "https://identitytoolkit.googleapis.com/v1/accounts:lookup?key={$apiKey}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['idToken' => $idToken]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if (isset($data['users']) && count($data['users']) > 0) {
            return $data['users'][0];
        }
    }
    
    return null;
}

/**
 * Get user by Firebase UID
 * 
 * @param string $firebaseUid Firebase UID
 * @return array|null User data or null if not found
 */
function getUserByFirebaseUid($firebaseUid) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE firebase_uid = ?");
    $stmt->bind_param("s", $firebaseUid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    $stmt->close();
    return null;
}

/**
 * Create a new user in the database
 * 
 * @param string $firebaseUid Firebase UID
 * @param string $email User email
 * @param string $displayName User display name (optional)
 * @param string $photoUrl User photo URL (optional)
 * @return int|bool User ID if successful, false otherwise
 */
function createUser($firebaseUid, $email, $displayName = '', $photoUrl = '') {
    global $conn;
    
    // Check if users table has the necessary columns
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'display_name'");
    if ($result->num_rows == 0) {
        // Add columns if they don't exist
        $conn->query("ALTER TABLE users ADD display_name VARCHAR(255) DEFAULT ''");
        $conn->query("ALTER TABLE users ADD photo_url TEXT");
    }
    
    $stmt = $conn->prepare("INSERT INTO users (firebase_uid, email, display_name, photo_url, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssss", $firebaseUid, $email, $displayName, $photoUrl);
    
    if ($stmt->execute()) {
        $userId = $stmt->insert_id;
        $stmt->close();
        return $userId;
    }
    
    $stmt->close();
    return false;
}
?>