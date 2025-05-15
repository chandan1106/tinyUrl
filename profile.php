<?php
session_start();
require_once 'config/config.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$userId = $_SESSION['user_id'];
$email = $_SESSION['email'];

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

// Handle API key activation/deactivation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['key_id']) && isset($_POST['action'])) {
    $keyId = $_POST['key_id'];
    $action = $_POST['action'];
    
    if ($action === 'activate' || $action === 'deactivate') {
        $active = ($action === 'activate') ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE api_keys SET active = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("iii", $active, $keyId, $userId);
        $stmt->execute();
        $stmt->close();
        
        header('Location: profile.php');
        exit;
    }
}

// Get user's URL stats
$totalUrls = 0;
$totalClicks = 0;

$stmt = $conn->prepare("SELECT COUNT(*) as total_urls, SUM(click_count) as total_clicks FROM urls WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$totalUrls = $row['total_urls'] ?? 0;
$totalClicks = $row['total_clicks'] ?? 0;
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - TinyURL</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <a href="/" class="text-2xl font-bold">TinyURL</a>
            <div>
                <a href="index.php" class="mr-4">Home</a>
                <a href="profile.php" class="mr-4 font-bold">Profile</a>
                <button id="logoutBtn" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded">Logout</button>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-4">
        <div class="bg-white p-6 rounded-lg shadow-md mb-6">
            <h2 class="text-2xl font-bold mb-4">Profile</h2>
            <p class="mb-2"><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div class="bg-blue-50 p-4 rounded">
                    <h3 class="text-lg font-semibold">Total URLs</h3>
                    <p class="text-3xl font-bold text-blue-600"><?php echo $totalUrls; ?></p>
                </div>
                <div class="bg-green-50 p-4 rounded">
                    <h3 class="text-lg font-semibold">Total Clicks</h3>
                    <p class="text-3xl font-bold text-green-600"><?php echo $totalClicks; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-md">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold">API Keys</h2>
                <button id="generateKeyBtn" class="bg-green-500 hover:bg-green-600 px-4 py-2 rounded text-white">
                    Generate New Key
                </button>
            </div>
            
            <?php if (empty($apiKeys)): ?>
                <p class="text-gray-500">You don't have any API keys yet.</p>
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
                                        <form method="POST" action="" class="inline">
                                            <input type="hidden" name="key_id" value="<?php echo $key['id']; ?>">
                                            <?php if ($key['active']): ?>
                                                <input type="hidden" name="action" value="deactivate">
                                                <button type="submit" class="text-red-600 hover:underline">Deactivate</button>
                                            <?php else: ?>
                                                <input type="hidden" name="action" value="activate">
                                                <button type="submit" class="text-green-600 hover:underline">Activate</button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <div class="mt-6">
                <h3 class="text-xl font-bold mb-2">API Usage</h3>
                <div class="bg-gray-100 p-4 rounded font-mono text-sm">
                    <p class="mb-2">// Example API request to shorten URL</p>
                    <pre class="overflow-x-auto">
fetch('<?php echo BASE_URL; ?>/api/shorten.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        api_key: 'YOUR_API_KEY',
        url: 'https://example.com'
    })
})
.then(response => response.json())
.then(data => console.log(data));</pre>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Handle logout
        $('#logoutBtn').click(function() {
            $.ajax({
                url: 'auth/logout.php',
                type: 'POST',
                success: function() {
                    window.location.href = 'index.php';
                }
            });
        });
        
        // Handle API key generation
        $('#generateKeyBtn').click(function() {
            $.ajax({
                url: 'api/generate_key.php',
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('New API key generated: ' + response.api_key);
                        window.location.reload();
                    } else {
                        alert('Error: ' + response.error);
                    }
                },
                error: function() {
                    alert('Failed to generate API key. Please try again.');
                }
            });
        });
    </script>
</body>
</html>