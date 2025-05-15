<?php
session_start();
require_once 'config/config.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index');
    exit;
}

$userId = $_SESSION['user_id'];

// Get user's API keys
$apiKeys = [];
$stmt = $conn->prepare("SELECT id, api_key, active, created_at FROM api_keys WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $apiKeys[] = $row;
}
$stmt->close();

// Handle API key generation
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'generate') {
        // Generate a new API key
        $apiKey = bin2hex(random_bytes(16)); // 32 character hex string
        
        // Insert API key into database
        $stmt = $conn->prepare("INSERT INTO api_keys (user_id, api_key, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("is", $userId, $apiKey);
        
        if ($stmt->execute()) {
            $message = "API key generated successfully!";
            // Refresh the page to show the new key
            header('Location: api_docs?success=1');
            exit;
        } else {
            $message = "Error: Could not generate API key.";
        }
        $stmt->close();
    } elseif ($_POST['action'] === 'toggle' && isset($_POST['key_id'])) {
        $keyId = $_POST['key_id'];
        $active = isset($_POST['active']) ? 1 : 0;
        
        // Update API key status
        $stmt = $conn->prepare("UPDATE api_keys SET active = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("iii", $active, $keyId, $userId);
        
        if ($stmt->execute()) {
            $message = "API key status updated successfully!";
            header('Location: api_docs?success=1');
            exit;
        } else {
            $message = "Error: Could not update API key status.";
        }
        $stmt->close();
    } elseif ($_POST['action'] === 'delete' && isset($_POST['key_id'])) {
        $keyId = $_POST['key_id'];
        
        // Delete API key
        $stmt = $conn->prepare("DELETE FROM api_keys WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $keyId, $userId);
        
        if ($stmt->execute()) {
            $message = "API key deleted successfully!";
            header('Location: api_docs?success=1');
            exit;
        } else {
            $message = "Error: Could not delete API key.";
        }
        $stmt->close();
    }
}

// Check for success message from redirect
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $message = "Operation completed successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation - TinyURL</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/atom-one-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/languages/json.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/languages/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/languages/php.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <a href="/" class="text-2xl font-bold">TinyURL</a>
            <div class="flex items-center">
                <a href="index" class="mr-4 hover:underline">Home</a>
                <a href="profile" class="mr-4 hover:underline">Profile</a>
                <a href="api_keys" class="mr-4 hover:underline">API Keys</a>
                <a href="api_docs" class="mr-4 font-bold">API Docs</a>
                <button id="logoutBtn" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded">Logout</button>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-4">
        <?php if ($message): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                <p><?php echo htmlspecialchars($message); ?></p>
            </div>
        <?php endif; ?>

        <div class="bg-white p-6 rounded-lg shadow-md mb-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold">API Keys</h2>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="generate">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                        Generate New API Key
                    </button>
                </form>
            </div>
            
            <?php if (empty($apiKeys)): ?>
                <p class="text-gray-500">You don't have any API keys yet. Generate one to get started.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white">
                        <thead>
                            <tr>
                                <th class="py-2 px-4 border-b text-left">API Key</th>
                                <th class="py-2 px-4 border-b text-left">Status</th>
                                <th class="py-2 px-4 border-b text-left">Created</th>
                                <th class="py-2 px-4 border-b text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($apiKeys as $key): ?>
                                <tr>
                                    <td class="py-2 px-4 border-b font-mono">
                                        <?php echo htmlspecialchars($key['api_key']); ?>
                                    </td>
                                    <td class="py-2 px-4 border-b">
                                        <?php if ($key['active']): ?>
                                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Active</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-2 px-4 border-b">
                                        <?php echo date('M j, Y', strtotime($key['created_at'])); ?>
                                    </td>
                                    <td class="py-2 px-4 border-b">
                                        <div class="flex space-x-2">
                                            <form method="POST" action="" class="inline">
                                                <input type="hidden" name="action" value="toggle">
                                                <input type="hidden" name="key_id" value="<?php echo $key['id']; ?>">
                                                <?php if ($key['active']): ?>
                                                    <button type="submit" class="text-yellow-600 hover:underline">Deactivate</button>
                                                <?php else: ?>
                                                    <input type="hidden" name="active" value="1">
                                                    <button type="submit" class="text-green-600 hover:underline">Activate</button>
                                                <?php endif; ?>
                                            </form>
                                            <form method="POST" action="" class="inline" onsubmit="return confirm('Are you sure you want to delete this API key?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="key_id" value="<?php echo $key['id']; ?>">
                                                <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-md mb-6">
            <h2 class="text-2xl font-bold mb-4">API Documentation</h2>
            
            <div class="mb-8">
                <h3 class="text-xl font-semibold mb-2">Authentication</h3>
                <p class="mb-4">All API requests require an API key. Include your API key in the request body as <code class="bg-gray-100 px-1 py-0.5 rounded">api_key</code>.</p>
                <div class="bg-gray-800 text-white p-4 rounded-lg overflow-x-auto">
                    <pre><code class="language-json">{
  "api_key": "your_api_key_here",
  // other parameters
}</code></pre>
                </div>
            </div>
            
            <div class="mb-8">
                <h3 class="text-xl font-semibold mb-2">1. Shorten URL</h3>
                <p class="mb-2"><strong>Endpoint:</strong> <code class="bg-gray-100 px-1 py-0.5 rounded">/api/shorten</code></p>
                <p class="mb-2"><strong>Method:</strong> POST</p>
                <p class="mb-4"><strong>Description:</strong> Creates a shortened URL from a long URL.</p>
                
                <div class="mb-4">
                    <h4 class="text-lg font-medium mb-2">Request Parameters:</h4>
                    <ul class="list-disc pl-6 mb-4">
                        <li><code class="bg-gray-100 px-1 py-0.5 rounded">api_key</code> (required): Your API key</li>
                        <li><code class="bg-gray-100 px-1 py-0.5 rounded">url</code> (required): The URL to shorten</li>
                    </ul>
                    
                    <div class="bg-gray-800 text-white p-4 rounded-lg overflow-x-auto">
                        <pre><code class="language-json">{
  "api_key": "your_api_key_here",
  "url": "https://example.com/very/long/url/that/needs/shortening"
}</code></pre>
                    </div>
                </div>
                
                <div>
                    <h4 class="text-lg font-medium mb-2">Response:</h4>
                    <div class="bg-gray-800 text-white p-4 rounded-lg overflow-x-auto">
                        <pre><code class="language-json">{
  "success": true,
  "url_id": 123,
  "original_url": "https://example.com/very/long/url/that/needs/shortening",
  "short_url": "http://yourdomain.com/abc123",
  "short_code": "abc123"
}</code></pre>
                    </div>
                </div>
            </div>
            
            <div class="mb-8">
                <h3 class="text-xl font-semibold mb-2">2. Get URL Statistics</h3>
                <p class="mb-2"><strong>Endpoint:</strong> <code class="bg-gray-100 px-1 py-0.5 rounded">/api/stats</code></p>
                <p class="mb-2"><strong>Method:</strong> POST</p>
                <p class="mb-4"><strong>Description:</strong> Retrieves statistics for a shortened URL.</p>
                
                <div class="mb-4">
                    <h4 class="text-lg font-medium mb-2">Request Parameters:</h4>
                    <ul class="list-disc pl-6 mb-4">
                        <li><code class="bg-gray-100 px-1 py-0.5 rounded">api_key</code> (required): Your API key</li>
                        <li><code class="bg-gray-100 px-1 py-0.5 rounded">short_code</code> (required): The short code of the URL</li>
                    </ul>
                    
                    <div class="bg-gray-800 text-white p-4 rounded-lg overflow-x-auto">
                        <pre><code class="language-json">{
  "api_key": "your_api_key_here",
  "short_code": "abc123"
}</code></pre>
                    </div>
                </div>
                
                <div>
                    <h4 class="text-lg font-medium mb-2">Response:</h4>
                    <div class="bg-gray-800 text-white p-4 rounded-lg overflow-x-auto">
                        <pre><code class="language-json">{
  "success": true,
  "url_id": 123,
  "original_url": "https://example.com/very/long/url/that/needs/shortening",
  "short_url": "http://yourdomain.com/abc123",
  "short_code": "abc123",
  "created_at": "2023-06-15 14:30:45",
  "click_count": 42
}</code></pre>
                    </div>
                </div>
            </div>
            
            <div class="mb-8">
                <h3 class="text-xl font-semibold mb-2">3. List URLs</h3>
                <p class="mb-2"><strong>Endpoint:</strong> <code class="bg-gray-100 px-1 py-0.5 rounded">/api/list</code></p>
                <p class="mb-2"><strong>Method:</strong> POST</p>
                <p class="mb-4"><strong>Description:</strong> Lists all URLs created with your API key.</p>
                
                <div class="mb-4">
                    <h4 class="text-lg font-medium mb-2">Request Parameters:</h4>
                    <ul class="list-disc pl-6 mb-4">
                        <li><code class="bg-gray-100 px-1 py-0.5 rounded">api_key</code> (required): Your API key</li>
                        <li><code class="bg-gray-100 px-1 py-0.5 rounded">limit</code> (optional): Number of URLs to return (default: 10)</li>
                        <li><code class="bg-gray-100 px-1 py-0.5 rounded">offset</code> (optional): Offset for pagination (default: 0)</li>
                    </ul>
                    
                    <div class="bg-gray-800 text-white p-4 rounded-lg overflow-x-auto">
                        <pre><code class="language-json">{
  "api_key": "your_api_key_here",
  "limit": 5,
  "offset": 0
}</code></pre>
                    </div>
                </div>
                
                <div>
                    <h4 class="text-lg font-medium mb-2">Response:</h4>
                    <div class="bg-gray-800 text-white p-4 rounded-lg overflow-x-auto">
                        <pre><code class="language-json">{
  "success": true,
  "total": 42,
  "limit": 5,
  "offset": 0,
  "urls": [
    {
      "id": 123,
      "original_url": "https://example.com/very/long/url/that/needs/shortening",
      "short_url": "http://yourdomain.com/abc123",
      "short_code": "abc123",
      "created_at": "2023-06-15 14:30:45",
      "click_count": 42
    },
    // More URLs...
  ]
}</code></pre>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow-md mb-6">
            <h2 class="text-2xl font-bold mb-4">Code Examples</h2>
            
            <div class="mb-8">
                <h3 class="text-xl font-semibold mb-2">JavaScript (Fetch API)</h3>
                <div class="bg-gray-800 text-white p-4 rounded-lg overflow-x-auto">
                    <pre><code class="language-javascript">// Shorten a URL
async function shortenUrl(apiKey, url) {
  const response = await fetch('http://yourdomain.com/api/shorten', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      api_key: apiKey,
      url: url
    })
  });
  
  return await response.json();
}

// Example usage
shortenUrl('your_api_key_here', 'https://example.com/long/url')
  .then(data => console.log('Shortened URL:', data.short_url))
  .catch(error => console.error('Error:', error));</code></pre>
                </div>
            </div>
            
            <div class="mb-8">
                <h3 class="text-xl font-semibold mb-2">PHP (cURL)</h3>
                <div class="bg-gray-800 text-white p-4 rounded-lg overflow-x-auto">
                    <pre><code class="language-php">&lt;?php
// Shorten a URL
function shortenUrl($apiKey, $url) {
    $data = [
        'api_key' => $apiKey,
        'url' => $url
    ];
    
    $ch = curl_init('http://yourdomain.com/api/shorten');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

// Example usage
$result = shortenUrl('your_api_key_here', 'https://example.com/long/url');
echo "Shortened URL: " . $result['short_url'];
?&gt;</code></pre>
                </div>
            </div>
            
            <div>
                <h3 class="text-xl font-semibold mb-2">Python (requests)</h3>
                <div class="bg-gray-800 text-white p-4 rounded-lg overflow-x-auto">
                    <pre><code class="language-python">import requests
import json

# Shorten a URL
def shorten_url(api_key, url):
    data = {
        'api_key': api_key,
        'url': url
    }
    
    response = requests.post(
        'http://yourdomain.com/api/shorten',
        headers={'Content-Type': 'application/json'},
        data=json.dumps(data)
    )
    
    return response.json()

# Example usage
result = shorten_url('your_api_key_here', 'https://example.com/long/url')
print(f"Shortened URL: {result['short_url']}")</code></pre>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize syntax highlighting
        document.addEventListener('DOMContentLoaded', () => {
            hljs.highlightAll();
        });
        
        // Handle logout
        $('#logoutBtn').click(function() {
            $.ajax({
                url: 'auth/logout',
                type: 'POST',
                success: function() {
                    window.location.href = 'index';
                }
            });
        });
    </script>
</body>
</html>