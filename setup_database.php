<?php
// Database setup script for Docker MySQL
require_once 'config/database.php';

echo "<h2>Docker MySQL Database Setup</h2>";
echo "<style>body { font-family: Arial, sans-serif; padding: 20px; } code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>";

// Step 1: Test connection without database
echo "<h3>Step 1: Testing MySQL Connection...</h3>";
$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, null, DB_PORT);

if ($conn->connect_error) {
    echo "<p class='error'>✗ Cannot connect to MySQL: " . $conn->connect_error . "</p>";
    echo "<p><strong>Possible issues:</strong></p>";
    echo "<ul>";
    echo "<li>MySQL container might not be running</li>";
    echo "<li>Password might be required - update DB_PASS in config/database.php</li>";
    echo "<li>Check: <code>docker ps</code> to see if container is running</li>";
    echo "</ul>";
    exit;
} else {
    echo "<p class='success'>✓ Successfully connected to MySQL server!</p>";
}

// Step 2: Create database if it doesn't exist
echo "<h3>Step 2: Creating Database...</h3>";
$result = $conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
if ($result) {
    echo "<p class='success'>✓ Database '" . DB_NAME . "' is ready!</p>";
} else {
    echo "<p class='error'>✗ Failed to create database: " . $conn->error . "</p>";
    $conn->close();
    exit;
}

// Step 3: Select the database
$conn->select_db(DB_NAME);

// Step 4: Create users table
echo "<h3>Step 3: Creating Users Table...</h3>";
$create_table = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    bio TEXT,
    profile_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($create_table)) {
    echo "<p class='success'>✓ Users table created!</p>";
} else {
    echo "<p class='error'>✗ Failed to create table: " . $conn->error . "</p>";
}

// Create posts table
echo "<h3>Step 4: Creating Posts Table...</h3>";
$create_posts = "CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($create_posts)) {
    echo "<p class='success'>✓ Posts table created!</p>";
    
    // Check if image_path column exists, if not add it (for existing databases)
    $check_column = $conn->query("SHOW COLUMNS FROM posts LIKE 'image_path'");
    if ($check_column->num_rows == 0) {
        $add_column = $conn->query("ALTER TABLE posts ADD COLUMN image_path VARCHAR(255) DEFAULT NULL AFTER content");
        if ($add_column) {
            echo "<p class='success'>✓ Added image_path column to existing posts table!</p>";
        }
    }
} else {
    echo "<p class='error'>✗ Failed to create posts table: " . $conn->error . "</p>";
}

// Create friendships table
echo "<h3>Step 5: Creating Friendships Table...</h3>";
$create_friendships = "CREATE TABLE IF NOT EXISTS friendships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    friend_id INT NOT NULL,
    status ENUM('pending', 'accepted') DEFAULT 'accepted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_friendship (user_id, friend_id),
    INDEX idx_user_id (user_id),
    INDEX idx_friend_id (friend_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($create_friendships)) {
    echo "<p class='success'>✓ Friendships table created!</p>";
} else {
    echo "<p class='error'>✗ Failed to create friendships table: " . $conn->error . "</p>";
}

// Create comments table
echo "<h3>Step 6: Creating Comments Table...</h3>";
$create_comments = "CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_post_id (post_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($create_comments)) {
    echo "<p class='success'>✓ Comments table created!</p>";
} else {
    echo "<p class='error'>✗ Failed to create comments table: " . $conn->error . "</p>";
}

// Create likes table
echo "<h3>Step 7: Creating Likes Table...</h3>";
$create_likes = "CREATE TABLE IF NOT EXISTS likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (post_id, user_id),
    INDEX idx_post_id (post_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($create_likes)) {
    echo "<p class='success'>✓ Likes table created!</p>";
} else {
    echo "<p class='error'>✗ Failed to create likes table: " . $conn->error . "</p>";
}

// Step 8: Check if demo user exists, if not create it
echo "<h3>Step 8: Setting Up Demo User...</h3>";
$check_user = $conn->query("SELECT id FROM users WHERE username = 'demo_user'");
if ($check_user->num_rows == 0) {
    $hashed_password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'; // password123
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, first_name, last_name, bio) VALUES (?, ?, ?, ?, ?, ?)");
    $username = 'demo_user';
    $email = 'demo@example.com';
    $first_name = 'Demo';
    $last_name = 'User';
    $bio = 'This is a demo profile!';
    $stmt->bind_param("ssssss", $username, $email, $hashed_password, $first_name, $last_name, $bio);
    
    if ($stmt->execute()) {
        echo "<p class='success'>✓ Demo user created!</p>";
        echo "<p><strong>Login credentials:</strong></p>";
        echo "<ul>";
        echo "<li>Username: <code>demo_user</code></li>";
        echo "<li>Password: <code>password123</code></li>";
        echo "</ul>";
    } else {
        echo "<p class='error'>✗ Failed to create demo user: " . $stmt->error . "</p>";
    }
    $stmt->close();
} else {
    echo "<p class='info'>ℹ Demo user already exists</p>";
}

// Step 9: Count users
$count_result = $conn->query("SELECT COUNT(*) as count FROM users");
$count = $count_result->fetch_assoc()['count'];
echo "<p><strong>Total users in database:</strong> " . $count . "</p>";

$conn->close();

echo "<hr>";
echo "<h3 class='success'>✓ Database setup complete!</h3>";
echo "<p>You can now:</p>";
echo "<ul>";
echo "<li><a href='test_connection.php'>Test the connection</a></li>";
echo "<li><a href='login.php'>Go to login page</a></li>";
echo "</ul>";
?>

