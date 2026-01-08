<?php
require_once 'config/database.php';
require_once 'config/session.php';

requireLogin();

// Handle logout
if (isset($_GET['logout'])) {
    logout();
}

$conn = getDBConnection();
$user_id = getCurrentUserId();

// Handle follow/unfollow
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_follow'])) {
    $friend_id = intval($_POST['friend_id']);
    
    if ($friend_id != $user_id) {
        // Check if already following
        $stmt = $conn->prepare("SELECT id FROM friendships WHERE user_id = ? AND friend_id = ?");
        $stmt->bind_param("ii", $user_id, $friend_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Unfollow
            $stmt = $conn->prepare("DELETE FROM friendships WHERE user_id = ? AND friend_id = ?");
            $stmt->bind_param("ii", $user_id, $friend_id);
            $stmt->execute();
        } else {
            // Follow
            $stmt = $conn->prepare("INSERT INTO friendships (user_id, friend_id, status) VALUES (?, ?, 'accepted')");
            $stmt->bind_param("ii", $user_id, $friend_id);
            $stmt->execute();
        }
        $stmt->close();
    }
    header('Location: search.php' . (isset($_GET['q']) ? '?q=' . urlencode($_GET['q']) : ''));
    exit();
}

// Search functionality
$search_query = trim($_GET['q'] ?? '');
$users = [];

if (!empty($search_query)) {
    $search_term = '%' . $search_query . '%';
    $stmt = $conn->prepare("
        SELECT u.id, u.username, u.first_name, u.last_name, u.bio,
               (SELECT COUNT(*) FROM friendships WHERE user_id = ? AND friend_id = u.id) as is_following,
               (SELECT COUNT(*) FROM posts WHERE user_id = u.id) as post_count
        FROM users u
        WHERE u.id != ? 
          AND (u.username LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)
        ORDER BY u.first_name, u.last_name
        LIMIT 50
    ");
    $stmt->bind_param("issss", $user_id, $user_id, $search_term, $search_term, $search_term);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($user = $result->fetch_assoc()) {
        $users[] = $user;
    }
    $stmt->close();
} else {
    // Show all users except current user
    $stmt = $conn->prepare("
        SELECT u.id, u.username, u.first_name, u.last_name, u.bio,
               (SELECT COUNT(*) FROM friendships WHERE user_id = ? AND friend_id = u.id) as is_following,
               (SELECT COUNT(*) FROM posts WHERE user_id = u.id) as post_count
        FROM users u
        WHERE u.id != ?
        ORDER BY u.first_name, u.last_name
        LIMIT 50
    ");
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($user = $result->fetch_assoc()) {
        $users[] = $user;
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Users - Echo</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h2 class="nav-brand">Echo</h2>
            <div class="nav-links">
                <a href="feed.php" class="nav-link">Feed</a>
                <a href="search.php" class="nav-link active">Search</a>
                <a href="profile.php" class="nav-link">Profile</a>
                <a href="?logout=1" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="search-container">
            <h1>Discover People</h1>
            
            <form method="GET" action="" class="search-form">
                <input type="text" name="q" placeholder="Search by name or username..." 
                       value="<?php echo htmlspecialchars($search_query); ?>" 
                       class="search-input">
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
            
            <div class="users-list">
                <?php if (empty($users)): ?>
                    <div class="empty-state">
                        <p><?php echo empty($search_query) ? 'No users found.' : 'No users match your search.'; ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <div class="user-card">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                            </div>
                            <div class="user-info">
                                <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                                <p class="user-username">@<?php echo htmlspecialchars($user['username']); ?></p>
                                <?php if ($user['bio']): ?>
                                    <p class="user-bio"><?php echo htmlspecialchars($user['bio']); ?></p>
                                <?php endif; ?>
                                <p class="user-stats"><?php echo $user['post_count']; ?> posts</p>
                            </div>
                            <div class="user-actions">
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="friend_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" name="toggle_follow" class="btn <?php echo $user['is_following'] ? 'btn-secondary' : 'btn-primary'; ?>">
                                        <?php echo $user['is_following'] ? '✓ Following' : '+ Follow'; ?>
                                    </button>
                                </form>
                                <a href="profile.php?user_id=<?php echo $user['id']; ?>" class="btn btn-outline">View Profile</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

