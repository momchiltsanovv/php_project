<?php
require_once 'config/database.php';
require_once 'config/session.php';

// Require login to view profile
requireLogin();

$conn = getDBConnection();
$current_user_id = getCurrentUserId();

// Get profile user ID (default to current user, or from query param)
$profile_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : $current_user_id;
$is_own_profile = ($profile_user_id == $current_user_id);

// Fetch user data from database
$stmt = $conn->prepare("SELECT id, username, email, first_name, last_name, bio, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Location: feed.php');
    exit();
}

// Get user stats
$stmt = $conn->prepare("SELECT COUNT(*) as post_count FROM posts WHERE user_id = ?");
$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Check if current user is following this profile
$is_following = false;
if (!$is_own_profile) {
    $stmt = $conn->prepare("SELECT id FROM friendships WHERE user_id = ? AND friend_id = ?");
    $stmt->bind_param("ii", $current_user_id, $profile_user_id);
    $stmt->execute();
    $is_following = $stmt->get_result()->num_rows > 0;
    $stmt->close();
}

// Get user's posts
$stmt = $conn->prepare("
    SELECT p.*, 
           (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
    FROM posts p
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
    LIMIT 20
");
$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$posts_result = $stmt->get_result();
$user_posts = [];
while ($post = $posts_result->fetch_assoc()) {
    $user_posts[] = $post;
}
$stmt->close();

// Handle logout
if (isset($_GET['logout'])) {
    logout();
}

// Handle follow/unfollow
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_follow']) && !$is_own_profile) {
    if ($is_following) {
        $stmt = $conn->prepare("DELETE FROM friendships WHERE user_id = ? AND friend_id = ?");
        $stmt->bind_param("ii", $current_user_id, $profile_user_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO friendships (user_id, friend_id, status) VALUES (?, ?, 'accepted')");
        $stmt->bind_param("ii", $current_user_id, $profile_user_id);
        $stmt->execute();
    }
    $stmt->close();
    header('Location: profile.php?user_id=' . $profile_user_id);
    exit();
}

// Handle profile update (only for own profile)
$update_error = '';
$update_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile']) && $is_own_profile) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    
    if (empty($first_name) || empty($last_name)) {
        $update_error = 'First name and last name are required.';
    } else {
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, bio = ? WHERE id = ?");
        $stmt->bind_param("sssi", $first_name, $last_name, $bio, $current_user_id);
        
        if ($stmt->execute()) {
            $update_success = 'Profile updated successfully!';
            // Refresh user data
            $stmt = $conn->prepare("SELECT id, username, email, first_name, last_name, bio, created_at FROM users WHERE id = ?");
            $stmt->bind_param("i", $current_user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
        } else {
            $update_error = 'Failed to update profile.';
        }
        $stmt->close();
    }
}

// Refresh following status after potential changes
if (!$is_own_profile) {
    $stmt = $conn->prepare("SELECT id FROM friendships WHERE user_id = ? AND friend_id = ?");
    $stmt->bind_param("ii", $current_user_id, $profile_user_id);
    $stmt->execute();
    $is_following = $stmt->get_result()->num_rows > 0;
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h2 class="nav-brand">Mini Social Media</h2>
            <div class="nav-links">
                <a href="feed.php" class="nav-link">Feed</a>
                <a href="search.php" class="nav-link">Search</a>
                <a href="profile.php" class="nav-link active">Profile</a>
                <a href="?logout=1" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-avatar">
                    <div class="avatar-circle">
                        <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                    </div>
                </div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
                    <p class="username">@<?php echo htmlspecialchars($user['username']); ?></p>
                    <?php if ($is_own_profile): ?>
                        <p class="email"><?php echo htmlspecialchars($user['email']); ?></p>
                    <?php endif; ?>
                    <p class="member-since">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                    <p class="profile-stats"><?php echo $stats['post_count']; ?> posts</p>
                    <?php if (!$is_own_profile): ?>
                        <form method="POST" action="" style="margin-top: 10px;">
                            <button type="submit" name="toggle_follow" class="btn <?php echo $is_following ? 'btn-secondary' : 'btn-primary'; ?>">
                                <?php echo $is_following ? '✓ Following' : '+ Follow'; ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="profile-content">
                <div class="profile-section">
                    <h2>About</h2>
                    <?php if ($user['bio']): ?>
                        <p class="bio"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                    <?php else: ?>
                        <p class="bio empty">No bio yet. Add one below!</p>
                    <?php endif; ?>
                </div>
                
                <?php if ($is_own_profile): ?>
                    <div class="profile-section">
                        <h2>Edit Profile</h2>
                        
                        <?php if ($update_error): ?>
                            <div class="alert alert-error"><?php echo htmlspecialchars($update_error); ?></div>
                        <?php endif; ?>
                        
                        <?php if ($update_success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($update_success); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" class="profile-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="first_name">First Name</label>
                                    <input type="text" id="first_name" name="first_name" 
                                           value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="last_name">Last Name</label>
                                    <input type="text" id="last_name" name="last_name" 
                                           value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="bio">Bio</label>
                                <textarea id="bio" name="bio" rows="4" 
                                          placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio']); ?></textarea>
                            </div>
                            
                            <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                        </form>
                    </div>
                <?php endif; ?>
                
                <div class="profile-section">
                    <h2>Posts</h2>
                    <?php if (empty($user_posts)): ?>
                        <p class="bio empty">No posts yet.</p>
                    <?php else: ?>
                        <div class="user-posts">
                            <?php foreach ($user_posts as $post): ?>
                                <div class="profile-post">
                                    <?php if (!empty($post['image_path']) && file_exists($post['image_path'])): ?>
                                        <div class="profile-post-image">
                                            <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="Post image">
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($post['content'])): ?>
                                        <p class="post-content"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                    <?php endif; ?>
                                    <div class="post-meta">
                                        <span><?php echo timeAgo($post['created_at']); ?></span>
                                        <span>❤️ <?php echo $post['like_count']; ?></span>
                                        <span>💬 <?php echo $post['comment_count']; ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<?php
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    
    return date('M j, Y', $timestamp);
}
?>

