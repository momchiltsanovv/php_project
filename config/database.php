<?php
// Database configuration for Docker MySQL
const DB_HOST = '127.0.0.1';  // Use 127.0.0.1 for Docker MySQL
const DB_PORT = 3306;          // Default MySQL port (change if your Docker port is different)
const DB_USER = 'root';
const DB_PASS = '1234';            // Set your MySQL root password if you have one
const DB_NAME = 'mini_social_media';

// Create database connection
function getDBConnection() {
    try {
        // Connect to Docker MySQL using TCP/IP with port
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error . 
                "\n\nTroubleshooting for Docker MySQL:\n" .
                "1. Make sure Docker MySQL container is running: docker ps\n" .
                "2. Check your Docker port mapping (should be 3306:3306 or similar)\n" .
                "3. Verify credentials in config/database.php\n" .
                "4. Test connection: docker exec -it <container_name> mysql -u root -p\n" .
                "5. Create the database: docker exec -i <container_name> mysql -u root -p < database/schema.sql\n" .
                "6. Or import via: mysql -h 127.0.0.1 -P " . DB_PORT . " -u root -p < database/schema.sql");
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        die("Database connection error: " . $e->getMessage());
    }
}


