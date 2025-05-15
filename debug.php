<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>TinyURL Debug Information</h1>";

// Check PHP version
echo "<h2>PHP Version</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";

// Check if required extensions are loaded
echo "<h2>PHP Extensions</h2>";
$requiredExtensions = ['mysqli', 'curl', 'json', 'session'];
echo "<ul>";
foreach ($requiredExtensions as $ext) {
    echo "<li>$ext: " . (extension_loaded($ext) ? "Loaded ✅" : "Not Loaded ❌") . "</li>";
}
echo "</ul>";

// Check file permissions
echo "<h2>File Permissions</h2>";
$files = [
    'index.php',
    'config/config.php',
    'includes/functions.php',
    'auth/login.php',
    'auth/register.php',
    'auth/logout.php'
];
echo "<ul>";
foreach ($files as $file) {
    if (file_exists($file)) {
        $perms = fileperms($file);
        $permsOctal = substr(sprintf('%o', $perms), -4);
        echo "<li>$file: Exists ✅ (Permissions: $permsOctal)</li>";
    } else {
        echo "<li>$file: Does not exist ❌</li>";
    }
}
echo "</ul>";

// Check database connection
echo "<h2>Database Connection</h2>";
if (file_exists('config/config.php')) {
    require_once 'config/config.php';
    
    if (isset($conn) && $conn instanceof mysqli) {
        if ($conn->connect_error) {
            echo "<p>Database connection failed: " . $conn->connect_error . " ❌</p>";
        } else {
            echo "<p>Database connection successful ✅</p>";
            
            // Check if tables exist
            $tables = ['users', 'urls', 'api_keys'];
            echo "<h3>Database Tables</h3>";
            echo "<ul>";
            foreach ($tables as $table) {
                $result = $conn->query("SHOW TABLES LIKE '$table'");
                if ($result && $result->num_rows > 0) {
                    echo "<li>$table: Exists ✅</li>";
                } else {
                    echo "<li>$table: Does not exist ❌</li>";
                }
            }
            echo "</ul>";
        }
    } else {
        echo "<p>Database connection variable not found ❌</p>";
    }
} else {
    echo "<p>Config file not found ❌</p>";
}

// Check session status
echo "<h2>Session Status</h2>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<p>Session is active ✅</p>";
    echo "<p>Session ID: " . session_id() . "</p>";
    
    echo "<h3>Session Variables</h3>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
} else {
    echo "<p>Session is not active ❌</p>";
}

// Check server information
echo "<h2>Server Information</h2>";
echo "<ul>";
echo "<li>Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "</li>";
echo "<li>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</li>";
echo "<li>Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "</li>";
echo "</ul>";

// Check if .htaccess is working
echo "<h2>.htaccess Status</h2>";
$htaccessTest = @file_get_contents('.htaccess');
if ($htaccessTest !== false) {
    echo "<p>.htaccess file exists ✅</p>";
} else {
    echo "<p>.htaccess file does not exist or is not readable ❌</p>";
}

// Display PHP error log location
echo "<h2>PHP Error Log</h2>";
echo "<p>Error log path: " . ini_get('error_log') . "</p>";
?>