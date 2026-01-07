<?php
// Database connection test script
require_once 'config/database.php';

echo "<h2>Testing Database Connection...</h2>";
echo "<style>body { font-family: Arial, sans-serif; padding: 20px; } code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }</style>";

// First, check if we can connect to MySQL at all
echo "<h3>Step 1: Checking MySQL Server (Docker)...</h3>";

$test_conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, null, DB_PORT);

if ($test_conn->connect_error) {
    echo "<p style='color: red;'>✗ Cannot connect to MySQL server</p>";
    echo "<p><strong>Error:</strong> " . $test_conn->connect_error . "</p>";
    echo "<h3>Quick Fixes for Docker MySQL:</h3>";
    echo "<ol>";
    echo "<li><strong>Start MySQL container:</strong><br>";
    echo "<code>docker-compose up -d</code><br>";
    echo "or<br>";
    echo "<code>docker start &lt;container_name&gt;</code></li>";
    echo "<li><strong>Check if container is running:</strong><br>";
    echo "<code>docker ps | grep mysql</code></li>";
    echo "<li><strong>Check port mapping:</strong><br>";
    echo "<code>docker port &lt;container_name&gt;</code></li>";
    echo "<li><strong>Verify port in config/database.php matches Docker port</strong></li>";
    echo "<li><strong>Check MySQL password in config/database.php</strong></li>";
    echo "</ol>";
    exit;
} else {
    echo "<p style='color: green;'>✓ MySQL server is running!</p>";
    $test_conn->close();
}

// Now test the actual database connection
echo "<h3>Step 2: Connecting to Database...</h3>";

try {
    $conn = getDBConnection();
    
    echo "<p style='color: green;'>✓ Successfully connected to MySQL database!</p>";
    echo "<p><strong>Database:</strong> " . DB_NAME . "</p>";
    echo "<p><strong>Host:</strong> " . DB_HOST . ":" . DB_PORT . " (Docker MySQL)</p>";
    
    // Test if users table exists
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✓ Users table exists!</p>";
        
        // Count users
        $count_result = $conn->query("SELECT COUNT(*) as count FROM users");
        $count = $count_result->fetch_assoc()['count'];
        echo "<p><strong>Total users in database:</strong> " . $count . "</p>";
        
        if ($count > 0) {
            echo "<p style='color: green;'>✓ Database is ready to use!</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ Users table does not exist.</p>";
        echo "<p>Run this command to create it:</p>";
        echo "<code>docker exec -i &lt;container_name&gt; mysql -u root -p &lt; database/schema.sql</code><br>";
        echo "or<br>";
        echo "<code>mysql -h " . DB_HOST . " -P " . DB_PORT . " -u root -p &lt; database/schema.sql</code>";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

