<?php
/**
 * MySQL Connection Test
 * Access: http://localhost/kk/test_db.php
 * This will help diagnose MySQL connection issues
 */

echo "<h2>MySQL Connection Diagnostic Test</h2>";
echo "<hr>";

// Test different configurations
$configs = [
    ['host' => 'localhost', 'port' => 3306, 'user' => 'root', 'pass' => ''],
    ['host' => '127.0.0.1', 'port' => 3306, 'user' => 'root', 'pass' => ''],
    ['host' => 'localhost', 'port' => 3307, 'user' => 'root', 'pass' => ''],
    ['host' => '127.0.0.1', 'port' => 3307, 'user' => 'root', 'pass' => ''],
];

$connected = false;

foreach ($configs as $config) {
    echo "<h3>Testing: {$config['host']}:{$config['port']} (user: {$config['user']})</h3>";
    
    $conn = @new mysqli($config['host'], $config['user'], $config['pass'], '', $config['port']);
    
    if ($conn->connect_error) {
        echo "<p style='color:red;'>✗ Failed: " . $conn->connect_error . "</p>";
        echo "<p>Error Code: " . $conn->connect_errno . "</p>";
    } else {
        echo "<p style='color:green;font-weight:bold;'>✓ SUCCESS! Connected on {$config['host']}:{$config['port']}</p>";
        
        // Test database creation
        $dbname = 'smartchrism';
        $createDb = $conn->query("CREATE DATABASE IF NOT EXISTS `$dbname`");
        if ($createDb) {
            echo "<p style='color:green;'>✓ Database '$dbname' created/verified</p>";
        } else {
            echo "<p style='color:orange;'>⚠ Database creation: " . $conn->error . "</p>";
        }
        
        // Show MySQL version
        $version = $conn->query("SELECT VERSION()");
        if ($version) {
            $row = $version->fetch_array();
            echo "<p>MySQL Version: <strong>" . $row[0] . "</strong></p>";
        }
        
        $conn->close();
        $connected = true;
        echo "<hr>";
        echo "<h3 style='color:green;'>✅ Working Configuration Found!</h3>";
        echo "<p>Update your config.php with:</p>";
        echo "<pre style='background:#f0f0f0;padding:10px;border-radius:5px;'>";
        echo "\$servername = \"{$config['host']}\";\n";
        if ($config['port'] != 3306) {
            echo "\$servername = \"{$config['host']}:{$config['port']}\";\n";
        }
        echo "\$username = \"{$config['user']}\";\n";
        echo "\$password = \"{$config['pass']}\";\n";
        echo "</pre>";
        break;
    }
    echo "<hr>";
}

if (!$connected) {
    echo "<h3 style='color:red;'>❌ All Connection Attempts Failed</h3>";
    echo "<h4>Possible Issues:</h4>";
    echo "<ul>";
    echo "<li><strong>MySQL not running:</strong> Open XAMPP Control Panel and start MySQL</li>";
    echo "<li><strong>Wrong port:</strong> Check XAMPP Control Panel for MySQL port number</li>";
    echo "<li><strong>Password set:</strong> If you set a MySQL password, update config.php</li>";
    echo "<li><strong>Firewall blocking:</strong> Check Windows Firewall settings</li>";
    echo "<li><strong>Another MySQL running:</strong> Stop other MySQL services</li>";
    echo "</ul>";
    echo "<h4>Next Steps:</h4>";
    echo "<ol>";
    echo "<li>Open XAMPP Control Panel</li>";
    echo "<li>Click 'Start' next to MySQL</li>";
    echo "<li>Wait for it to turn green</li>";
    echo "<li>Refresh this page</li>";
    echo "</ol>";
}

echo "<hr>";
echo "<p><a href='index.html'>← Back to Home</a></p>";

?>

