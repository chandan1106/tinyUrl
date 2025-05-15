<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base URL
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST']);

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Simple URL shortening without database
$shortUrl = '';
$originalUrl = '';
$message = '';

// Store URLs in session if database is not available
if (!isset($_SESSION['urls'])) {
    $_SESSION['urls'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['url'])) {
    $originalUrl = trim($_POST['url']);
    
    if (filter_var($originalUrl, FILTER_VALIDATE_URL)) {
        // Generate a simple short code
        $shortCode = substr(md5(uniqid()), 0, 6);
        
        // Store in session
        $_SESSION['urls'][$shortCode] = [
            'original_url' => $originalUrl,
            'created_at' => date('Y-m-d H:i:s'),
            'click_count' => 0
        ];
        
        $shortUrl = BASE_URL . '/r.php?code=' . $shortCode;
        $message = "URL shortened successfully!";
    } else {
        $message = "Error: Please enter a valid URL.";
    }
}

// Get user's URLs from session
$userUrls = [];
foreach ($_SESSION['urls'] as $code => $data) {
    $userUrls[] = [
        'original_url' => $data['original_url'],
        'short_code' => $code,
        'short_url' => BASE_URL . '/r.php?code=' . $code,
        'created_at' => $data['created_at'],
        'click_count' => $data['click_count']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TinyURL - Simple Version</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <a href="/" class="text-2xl font-bold">TinyURL (Simple)</a>
            <div>
                <a href="index.php" class="bg-green-500 hover:bg-green-600 px-4 py-2 rounded">Full Version</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-4">
        <div class="bg-white p-6 rounded-lg shadow-md mb-6">
            <h2 class="text-2xl font-bold mb-4">Shorten a URL</h2>
            <form method="POST" action="">
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
    </div>

    <script>
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