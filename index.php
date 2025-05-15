<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if config file exists
if (!file_exists('config/config.php')) {
    die("Config file not found. Please create config/config.php");
}

require_once 'config/config.php';

// Check if functions file exists
if (!file_exists('includes/functions.php')) {
    die("Functions file not found. Please create includes/functions.php");
}

require_once 'includes/functions.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Handle URL shortening if form is submitted
$shortUrl = '';
$originalUrl = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['url']) && $isLoggedIn) {
    $originalUrl = trim($_POST['url']);
    
    if (filter_var($originalUrl, FILTER_VALIDATE_URL)) {
        $userId = $_SESSION['user_id'];
        $shortCode = generateShortCode();
        
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO urls (user_id, original_url, short_code, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iss", $userId, $originalUrl, $shortCode);
        
        if ($stmt->execute()) {
            $shortUrl = BASE_URL . '/' . $shortCode;
            $message = "URL shortened successfully!";
        } else {
            $message = "Error: Could not shorten URL. " . $conn->error;
        }
        $stmt->close();
    } else {
        $message = "Error: Please enter a valid URL.";
    }
}

// Get user's URLs if logged in
$userUrls = [];
if ($isLoggedIn) {
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id, original_url, short_code, created_at, click_count FROM urls WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $row['short_url'] = BASE_URL . '/' . $row['short_code'];
        $userUrls[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TinyURL - URL Shortener</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://www.gstatic.com/firebasejs/11.7.3/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/11.7.3/firebase-auth-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/11.7.3/firebase-analytics-compat.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <a href="/" class="text-2xl font-bold">TinyURL</a>
            <div>
                <?php if ($isLoggedIn): ?>
                    <div class="flex items-center">
                        <?php if (!empty($_SESSION['photo_url'])): ?>
                            <img src="<?php echo htmlspecialchars($_SESSION['photo_url']); ?>" alt="Profile" class="w-8 h-8 rounded-full mr-2">
                        <?php endif; ?>
                        <span class="mr-4">
                            <?php echo !empty($_SESSION['display_name']) ? htmlspecialchars($_SESSION['display_name']) : htmlspecialchars($_SESSION['email']); ?>
                        </span>
                        <button id="logoutBtn" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded">Logout</button>
                    </div>
                <?php else: ?>
                    <button id="loginBtn" class="bg-blue-500 hover:bg-blue-700 px-4 py-2 rounded mr-2">Login</button>
                    <button id="registerBtn" class="bg-green-500 hover:bg-green-700 px-4 py-2 rounded">Register</button>
                    <a href="simple_login.php" class="bg-yellow-500 hover:bg-yellow-600 px-4 py-2 rounded ml-2">Simple Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-4">
        <?php if ($isLoggedIn): ?>
            <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                <h2 class="text-2xl font-bold mb-4">Shorten a URL</h2>
                <form id="shortenForm" method="POST" action="">
                    <div class="flex flex-col md:flex-row gap-4">
                        <input type="url" name="url" placeholder="Enter your long URL here" required
                            class="flex-grow p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                            Shorten
                        </button>
                    </div>
                </form>
                
                <?php if ($message): ?>
                    <div class="mt-4 p-3 <?php echo strpos($message, 'Error') === 0 ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?> rounded">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($shortUrl): ?>
                    <div class="mt-4 p-4 bg-gray-100 rounded">
                        <p class="mb-2">Your shortened URL:</p>
                        <div class="flex flex-col md:flex-row gap-2">
                            <input type="text" value="<?php echo htmlspecialchars($shortUrl); ?>" id="shortUrlInput" readonly
                                class="flex-grow p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <button onclick="copyToClipboard()" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                                Copy
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($userUrls)): ?>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-2xl font-bold mb-4">Your URLs</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white">
                            <thead>
                                <tr>
                                    <th class="py-2 px-4 border-b text-left">Original URL</th>
                                    <th class="py-2 px-4 border-b text-left">Short URL</th>
                                    <th class="py-2 px-4 border-b text-left">Created</th>
                                    <th class="py-2 px-4 border-b text-left">Clicks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userUrls as $url): ?>
                                    <tr>
                                        <td class="py-2 px-4 border-b truncate max-w-xs">
                                            <a href="<?php echo htmlspecialchars($url['original_url']); ?>" target="_blank" class="text-blue-600 hover:underline">
                                                <?php echo htmlspecialchars($url['original_url']); ?>
                                            </a>
                                        </td>
                                        <td class="py-2 px-4 border-b">
                                            <a href="<?php echo htmlspecialchars($url['short_url']); ?>" target="_blank" class="text-blue-600 hover:underline">
                                                <?php echo htmlspecialchars($url['short_url']); ?>
                                            </a>
                                        </td>
                                        <td class="py-2 px-4 border-b">
                                            <?php echo date('M j, Y', strtotime($url['created_at'])); ?>
                                        </td>
                                        <td class="py-2 px-4 border-b">
                                            <?php echo $url['click_count']; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="bg-white p-6 rounded-lg shadow-md text-center">
                <h1 class="text-3xl font-bold mb-4">Welcome to TinyURL</h1>
                <p class="text-lg mb-6">Please login to shorten your URLs and manage them.</p>
                <div class="flex justify-center gap-4">
                    <button id="homeLoginBtn" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">Login</button>
                    <button id="homeRegisterBtn" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700">Register</button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Auth Modal -->
    <div id="authModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h2 id="modalTitle" class="text-2xl font-bold">Login</h2>
                <button id="closeModal" class="text-gray-500 hover:text-gray-700">&times;</button>
            </div>
            <div id="authError" class="hidden p-3 mb-4 bg-red-100 text-red-700 rounded"></div>
            <form id="authForm">
                <div class="mb-4">
                    <label for="email" class="block text-gray-700 mb-2">Email</label>
                    <input type="email" id="email" class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div class="mb-6">
                    <label for="password" class="block text-gray-700 mb-2">Password</label>
                    <input type="password" id="password" class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <button type="submit" id="authSubmitBtn" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">Login</button>
            </form>
            
            <div class="mt-4">
                <button id="googleSignInBtn" class="w-full bg-white border border-gray-300 text-gray-700 py-2 px-4 rounded flex items-center justify-center hover:bg-gray-100">
                    <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google logo" class="w-5 h-5 mr-2">
                    Sign in with Google
                </button>
            </div>
            
            <div class="mt-4 text-center">
                <p id="switchAuthMode" class="text-blue-600 hover:underline cursor-pointer">
                    Don't have an account? Register
                </p>
            </div>
        </div>
    </div>

    <script>
        // Firebase configuration
        const firebaseConfig = {
            apiKey: "",
            authDomain: "",
            projectId: "",
            storageBucket: "",
            messagingSenderId: "",
            appId: "",
            measurementId: ""
        };
        
        // Initialize Firebase with error handling
        try {
            firebase.initializeApp(firebaseConfig);
            const analytics = firebase.analytics();
            console.log("Firebase initialized successfully");
        } catch (error) {
            console.error("Firebase initialization error:", error);
            // Show a user-friendly error message
            $('#authError').text("Firebase initialization failed. Please check your configuration.").show();
        }
        
        // Auth modal functionality
        let authMode = 'login';
        
        function showAuthModal(mode) {
            authMode = mode;
            $('#modalTitle').text(mode === 'login' ? 'Login' : 'Register');
            $('#authSubmitBtn').text(mode === 'login' ? 'Login' : 'Register');
            $('#switchAuthMode').html(mode === 'login' 
                ? "Don't have an account? <span class='underline'>Register</span>" 
                : "Already have an account? <span class='underline'>Login</span>");
            $('#authError').hide();
            $('#authModal').removeClass('hidden');
        }
        
        $('#loginBtn, #homeLoginBtn').click(() => showAuthModal('login'));
        $('#registerBtn, #homeRegisterBtn').click(() => showAuthModal('register'));
        $('#closeModal').click(() => $('#authModal').addClass('hidden'));
        
        $('#switchAuthMode').click(() => {
            authMode = authMode === 'login' ? 'register' : 'login';
            showAuthModal(authMode);
        });
        
        // Handle authentication
        $('#authForm').submit(function(e) {
            e.preventDefault();
            const email = $('#email').val();
            const password = $('#password').val();
            
            if (authMode === 'login') {
                firebase.auth().signInWithEmailAndPassword(email, password)
                    .then((userCredential) => {
                        return userCredential.user.getIdToken();
                    })
                    .then((idToken) => {
                        // Send token to backend for session creation
                        $.ajax({
                            url: 'auth/login.php',
                            type: 'POST',
                            data: { idToken },
                            success: function() {
                                window.location.reload();
                            },
                            error: function(xhr) {
                                $('#authError').text(xhr.responseText || 'Login failed').show();
                            }
                        });
                    })
                    .catch((error) => {
                        $('#authError').text(error.message).show();
                    });
            } else {
                firebase.auth().createUserWithEmailAndPassword(email, password)
                    .then((userCredential) => {
                        return userCredential.user.getIdToken();
                    })
                    .then((idToken) => {
                        // Send token to backend for user creation and session
                        $.ajax({
                            url: 'auth/register.php',
                            type: 'POST',
                            data: { idToken },
                            success: function() {
                                window.location.reload();
                            },
                            error: function(xhr) {
                                $('#authError').text(xhr.responseText || 'Registration failed').show();
                            }
                        });
                    })
                    .catch((error) => {
                        $('#authError').text(error.message).show();
                    });
            }
        });
        
        // Google Sign-In
        $('#googleSignInBtn').click(function() {
            const provider = new firebase.auth.GoogleAuthProvider();
            firebase.auth().signInWithPopup(provider)
                .then((result) => {
                    // Get the Google access token
                    const credential = result.credential;
                    const token = credential.accessToken;
                    const user = result.user;
                    
                    // Get Firebase ID token to send to backend
                    return user.getIdToken();
                })
                .then((idToken) => {
                    // Send token to backend for session creation
                    $.ajax({
                        url: 'auth/google_login.php',
                        type: 'POST',
                        data: { idToken },
                        success: function() {
                            window.location.reload();
                        },
                        error: function(xhr) {
                            $('#authError').text(xhr.responseText || 'Google login failed').show();
                        }
                    });
                })
                .catch((error) => {
                    // Handle errors
                    const errorCode = error.code;
                    const errorMessage = error.message;
                    $('#authError').text(errorMessage).show();
                    console.error('Google sign-in error:', error);
                });
        });
        
        // Handle logout
        $('#logoutBtn').click(function() {
            firebase.auth().signOut().then(() => {
                $.ajax({
                    url: 'auth/logout.php',
                    type: 'POST',
                    success: function() {
                        window.location.reload();
                    }
                });
            });
        });
        
        // Copy shortened URL to clipboard
        function copyToClipboard() {
            const shortUrlInput = document.getElementById('shortUrlInput');
            shortUrlInput.select();
            document.execCommand('copy');
            alert('URL copied to clipboard!');
        }
    </script>
</body>
</html>
