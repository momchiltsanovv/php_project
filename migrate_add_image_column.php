<?php
// Migration script to add image_path column to posts table
require_once 'config/database.php';

echo "<h2>Database Migration: Adding Image Support</h2>";
echo "<style>body { font-family: Arial, sans-serif; padding: 20px; background: #000; color: #f5f5f5; } .success { color: #42b72a; } .error { color: #ed4956; } .info { color: #0095f6; }</style>";

try {
    $conn = getDBConnection();
    
    // Check if column already exists
    $check = $conn->query("SHOW COLUMNS FROM posts LIKE 'image_path'");
    
    if ($check->num_rows > 0) {
        echo "<p class='info'>ℹ The image_path column already exists. No migration needed.</p>";
    } else {
        // Add the column
        $sql = "ALTER TABLE posts ADD COLUMN image_path VARCHAR(255) DEFAULT NULL AFTER content";
        
        if ($conn->query($sql)) {
            echo "<p class='success'>✓ Successfully added image_path column to posts table!</p>";
            echo "<p class='success'>✓ Image upload functionality is now enabled!</p>";
        } else {
            echo "<p class='error'>✗ Failed to add column: " . $conn->error . "</p>";
        }
    }
    
    $conn->close();
    
    echo "<hr>";
    echo "<p><a href='feed.php' style='color: #0095f6;'>Go to Feed</a> | <a href='setup_database.php' style='color: #0095f6;'>Run Full Setup</a></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}
?>

