<?php
// Example script demonstrating how to use the TinyURL API

// Your API key
$apiKey = 'YOUR_API_KEY_HERE';

// Base URL of your TinyURL application
$baseUrl = 'http://yourdomain.com';

/**
 * Shorten a URL using the TinyURL API
 * 
 * @param string $apiKey Your API key
 * @param string $url The URL to shorten
 * @param string $baseUrl Base URL of the TinyURL application
 * @return array API response
 */
function shortenUrl($apiKey, $url, $baseUrl) {
    $data = [
        'api_key' => $apiKey,
        'url' => $url
    ];
    
    $ch = curl_init($baseUrl . '/api/shorten');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status_code' => $httpCode,
        'response' => json_decode($response, true)
    ];
}

/**
 * Get statistics for a shortened URL
 * 
 * @param string $apiKey Your API key
 * @param string $shortCode The short code of the URL
 * @param string $baseUrl Base URL of the TinyURL application
 * @return array API response
 */
function getUrlStats($apiKey, $shortCode, $baseUrl) {
    $data = [
        'api_key' => $apiKey,
        'short_code' => $shortCode
    ];
    
    $ch = curl_init($baseUrl . '/api/stats');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status_code' => $httpCode,
        'response' => json_decode($response, true)
    ];
}

/**
 * List all URLs created with your API key
 * 
 * @param string $apiKey Your API key
 * @param int $limit Number of URLs to return (default: 10)
 * @param int $offset Offset for pagination (default: 0)
 * @param string $baseUrl Base URL of the TinyURL application
 * @return array API response
 */
function listUrls($apiKey, $limit = 10, $offset = 0, $baseUrl) {
    $data = [
        'api_key' => $apiKey,
        'limit' => $limit,
        'offset' => $offset
    ];
    
    $ch = curl_init($baseUrl . '/api/list');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status_code' => $httpCode,
        'response' => json_decode($response, true)
    ];
}

// Example usage
echo "<h1>TinyURL API Example</h1>";

// Example 1: Shorten a URL
echo "<h2>Example 1: Shorten a URL</h2>";
$urlToShorten = "https://example.com/very/long/url/that/needs/shortening";
$result = shortenUrl($apiKey, $urlToShorten, $baseUrl);

echo "<pre>";
echo "Status Code: " . $result['status_code'] . "\n";
echo "Response: ";
print_r($result['response']);
echo "</pre>";

// If the shortening was successful, get stats for the URL
if ($result['status_code'] == 200 && isset($result['response']['short_code'])) {
    $shortCode = $result['response']['short_code'];
    
    // Example 2: Get URL statistics
    echo "<h2>Example 2: Get URL Statistics</h2>";
    $statsResult = getUrlStats($apiKey, $shortCode, $baseUrl);
    
    echo "<pre>";
    echo "Status Code: " . $statsResult['status_code'] . "\n";
    echo "Response: ";
    print_r($statsResult['response']);
    echo "</pre>";
}

// Example 3: List URLs
echo "<h2>Example 3: List URLs</h2>";
$listResult = listUrls($apiKey, 5, 0, $baseUrl);

echo "<pre>";
echo "Status Code: " . $listResult['status_code'] . "\n";
echo "Response: ";
print_r($listResult['response']);
echo "</pre>";
?>