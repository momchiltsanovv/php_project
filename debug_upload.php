<?php
// Debug script to check image upload functionality
require_once 'config/database.php';
require_once 'config/session.php';

requireLogin();

$conn = getDBConnection();

echo "<h2>Image Upload Debug Information</h2>";
echo "<style>body { font-family: Arial, sans-serif; padding: 20px; background: #000; color: #f5f5f5; } .success { color: #42b72a; } .error { color: #ed4956; } .info { color: #0095f6; } code { background: #1a1a1a; padding: 2px 6px; border-radius: 3px; }</style>";

// Check database column
echo "<h3>1. Database Column Check</h3>";
$check = $conn->query("SHOW COLUMNS FROM posts LIKE 'image_path'");
if ($check->num_rows > 0) {
    echo "<p class='success'>✓ image_path column exists</p>";
} else {
    echo "<p class='error'>✗ image_path column does NOT exist</p>";
    echo "<p class='info'>Run: <a href='migrate_add_image_column.php' style='color: #0095f6;'>migrate_add_image_column.php</a></p>";
}

// Check upload directory
echo "<h3>2. Upload Directory Check</h3>";
$upload_dir = 'uploads/posts/';
if (file_exists($upload_dir)) {
    echo "<p class='success'>✓ Directory exists: " . $upload_dir . "</p>";
} else {
    echo "<p class='error'>✗ Directory does not exist: " . $upload_dir . "</p>";
    if (mkdir($upload_dir, 0777, true)) {
        echo "<p class='success'>✓ Created directory</p>";
    } else {
        echo "<p class='error'>✗ Failed to create directory</p>";
    }
}

if (file_exists($upload_dir)) {
    if (is_writable($upload_dir)) {
        echo "<p class='success'>✓ Directory is writable</p>";
    } else {
        echo "<p class='error'>✗ Directory is NOT writable</p>";
        echo "<p class='info'>Current permissions: " . substr(sprintf('%o', fileperms($upload_dir)), -4) . "</p>";
    }
}

// Check PHP upload settings
echo "<h3>3. PHP Upload Settings</h3>";
echo "<p class='info'>upload_max_filesize: " . ini_get('upload_max_filesize') . "</p>";
echo "<p class='info'>post_max_size: " . ini_get('post_max_size') . "</p>";
echo "<p class='info'>max_file_uploads: " . ini_get('max_file_uploads') . "</p>";
echo "<p class='info'>file_uploads: " . (ini_get('file_uploads') ? 'Enabled' : 'Disabled') . "</p>";

// Check if finfo is available
echo "<h3>4. File Info Functions</h3>";
if (function_exists('finfo_open')) {
    echo "<p class='success'>✓ finfo_open() is available</p>";
} else {
    echo "<p class='error'>✗ finfo_open() is NOT available</p>";
}

if (function_exists('mime_content_type')) {
    echo "<p class='success'>✓ mime_content_type() is available</p>";
} else {
    echo "<p class='error'>✗ mime_content_type() is NOT available</p>";
}

$conn->close();

echo "<hr>";
echo "<p><a href='feed.php' style='color: #0095f6;'>Go to Feed</a></p>";
?>

