<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if config file exists
if (!file_exists('config/config.php')) {
    die("Config file not found. Please create config/config.php");
}

require_once 'config/config.php';

// Simple login form processing
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password";
    } else {
        // For demo purposes, create a user if not exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Create a new user
            $firebaseUid = 'demo_' . md5($email . time());
            $stmt = $conn->prepare("INSERT INTO users (firebase_uid, email, created_at) VALUES (?, ?, NOW())");
            $stmt->bind_param("ss", $firebaseUid, $email);
            
            if ($stmt->execute()) {
                $userId = $stmt->insert_id;
                
                // Set session variables
                $_SESSION['user_id'] = $userId;
                $_SESSION['firebase_uid'] = $firebaseUid;
                $_SESSION['email'] = $email;
                
                $success = "Account created and logged in successfully!";
            } else {
                $error = "Error creating account: " . $conn->error;
            }
        } else {
            // User exists, log them in
            $user = $result->fetch_assoc();
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['firebase_uid'] = $user['firebase_uid'];
            $_SESSION['email'] = $user['email'];
            
            $success = "Logged in successfully!";
        }
        
        $stmt->close();
        
        if ($success) {
            // Redirect to index page after successful login
            header("Location: index.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Login - TinyURL</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h1 class="text-2xl font-bold mb-6 text-center">TinyURL Simple Login</h1>
        
        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-4">
                <label for="email" class="block text-gray-700 mb-2">Email</label>
                <input type="email" id="email" name="email" class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div class="mb-6">
                <label for="password" class="block text-gray-700 mb-2">Password</label>
                <input type="password" id="password" name="password" class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                <p class="text-sm text-gray-500 mt-1">For demo purposes, any password will work</p>
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">Login / Register</button>
        </form>
        
        <div class="mt-4 text-center">
            <p class="text-sm text-gray-600">
                This is a simple login page that bypasses Firebase authentication for testing purposes.
                <br>
                <a href="index.php" class="text-blue-600 hover:underline">Back to main page</a>
            </p>
        </div>
    </div>
</body>
</html>