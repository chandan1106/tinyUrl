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
        
        header('Location: profile');
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
                <a href="index" class="mr-4">Home</a>
                <a href="profile" class="mr-4 font-bold">Profile</a>
                <a href="api_keys" class="mr-4">API Keys</a>
                <a href="api_docs" class="mr-4">API Docs</a>
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
                <a href="api_keys" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                    Manage API Keys
                </a>
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($apiKeys, 0, 3) as $key): ?>
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
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (count($apiKeys) > 3): ?>
                    <div class="mt-4 text-center">
                        <a href="api_keys" class="text-blue-600 hover:underline">View all API keys</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
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
    </script>
</body>
</html>