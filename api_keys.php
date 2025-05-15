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
            header('Location: api_keys.php?success=1');
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
            header('Location: api_keys.php?success=1');
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
            header('Location: api_keys.php?success=1');
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
    <title>API Keys - TinyURL</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <a href="/" class="text-2xl font-bold">TinyURL</a>
            <div class="flex items-center">
                <a href="index" class="mr-4 hover:underline">Home</a>
                <a href="profile" class="mr-4 hover:underline">Profile</a>
                <a href="api_keys" class="mr-4 font-bold">API Keys</a>
                <a href="api_docs" class="mr-4 hover:underline">API Docs</a>
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
                                            <button onclick="copyToClipboard('<?php echo htmlspecialchars($key['api_key']); ?>')" class="text-blue-600 hover:underline">Copy</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-2xl font-bold mb-4">Using Your API Keys</h2>
            <p class="mb-4">Your API keys allow you to access the TinyURL API programmatically. Include your API key in all API requests.</p>
            
            <div class="mb-4">
                <h3 class="text-xl font-semibold mb-2">API Endpoints</h3>
                <ul class="list-disc pl-6">
                    <li class="mb-2"><strong>Shorten URL:</strong> <code class="bg-gray-100 px-1 py-0.5 rounded">/api/shorten</code></li>
                    <li class="mb-2"><strong>Get URL Statistics:</strong> <code class="bg-gray-100 px-1 py-0.5 rounded">/api/stats</code></li>
                    <li class="mb-2"><strong>List URLs:</strong> <code class="bg-gray-100 px-1 py-0.5 rounded">/api/list</code></li>
                </ul>
            </div>
            
            <p class="mb-2">For detailed documentation and code examples, visit the <a href="api_docs" class="text-blue-600 hover:underline">API Documentation</a> page.</p>
        </div>
    </div>

    <script>
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
        
        // Copy API key to clipboard
        function copyToClipboard(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            alert('API key copied to clipboard!');
        }
    </script>
</body>
</html>