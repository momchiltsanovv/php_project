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

// Get followers count (people who follow this user)
$stmt = $conn->prepare("SELECT COUNT(*) as followers_count FROM friendships WHERE friend_id = ?");
$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$followers_result = $stmt->get_result()->fetch_assoc();
$stats['followers_count'] = $followers_result['followers_count'];
$stmt->close();

// Get following count (people this user follows)
$stmt = $conn->prepare("SELECT COUNT(*) as following_count FROM friendships WHERE user_id = ?");
$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$following_result = $stmt->get_result()->fetch_assoc();
$stats['following_count'] = $following_result['following_count'];
$stmt->close();

// Handle followers/following list requests
$show_followers = isset($_GET['show']) && $_GET['show'] === 'followers';
$show_following = isset($_GET['show']) && $_GET['show'] === 'following';

$followers_list = [];
$following_list = [];

if ($show_followers) {
    // Get list of followers (people who follow this user)
    $stmt = $conn->prepare("
        SELECT u.id, u.username, u.first_name, u.last_name, u.bio
        FROM friendships f
        INNER JOIN users u ON f.user_id = u.id
        WHERE f.friend_id = ?
        ORDER BY u.first_name, u.last_name
    ");
    $stmt->bind_param("i", $profile_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $followers_list[] = $row;
    }
    $stmt->close();
}

if ($show_following) {
    // Get list of people this user follows
    $stmt = $conn->prepare("
        SELECT u.id, u.username, u.first_name, u.last_name, u.bio
        FROM friendships f
        INNER JOIN users u ON f.friend_id = u.id
        WHERE f.user_id = ?
        ORDER BY u.first_name, u.last_name
    ");
    $stmt->bind_param("i", $profile_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $following_list[] = $row;
    }
    $stmt->close();
}

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
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
           (p.user_id = ?) as is_owner
    FROM posts p
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
    LIMIT 20
");
$stmt->bind_param("ii", $current_user_id, $profile_user_id);
$stmt->execute();
$posts_result = $stmt->get_result();
$user_posts = [];
while ($post = $posts_result->fetch_assoc()) {
    // Get comments for this post
    $comments_stmt = $conn->prepare("
        SELECT c.*, u.username, u.first_name, u.last_name,
               (c.user_id = ?) as is_comment_owner
        FROM comments c 
        INNER JOIN users u ON c.user_id = u.id 
        WHERE c.post_id = ? 
        ORDER BY c.created_at ASC
    ");
    $comments_stmt->bind_param("ii", $current_user_id, $post['id']);
    $comments_stmt->execute();
    $comments_result = $comments_stmt->get_result();
    $post['comments'] = [];
    while ($comment = $comments_result->fetch_assoc()) {
        $post['comments'][] = $comment;
    }
    $comments_stmt->close();
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

// Handle post deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    $post_id = intval($_POST['post_id']);
    
    // Verify user owns this post
    $stmt = $conn->prepare("SELECT user_id, image_path FROM posts WHERE id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post_data = $result->fetch_assoc();
    $stmt->close();
    
    if ($post_data && $post_data['user_id'] == $current_user_id) {
        // Delete associated image file if it exists
        if (!empty($post_data['image_path']) && file_exists($post_data['image_path'])) {
            @unlink($post_data['image_path']);
        }
        
        // Delete the post (cascade will handle likes and comments)
        $stmt = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $post_id, $current_user_id);
        
        if ($stmt->execute()) {
            $update_success = 'Post deleted successfully!';
        } else {
            $update_error = 'Failed to delete post.';
        }
        $stmt->close();
        
        // Refresh the page
        header('Location: profile.php' . ($is_own_profile ? '' : '?user_id=' . $profile_user_id));
        exit();
    } else {
        $update_error = 'You can only delete your own posts.';
    }
}

// Handle comment deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment'])) {
    $comment_id = intval($_POST['comment_id']);
    
    // Verify user owns this comment
    $stmt = $conn->prepare("SELECT user_id FROM comments WHERE id = ?");
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $comment_data = $result->fetch_assoc();
    $stmt->close();
    
    if ($comment_data && $comment_data['user_id'] == $current_user_id) {
        $stmt = $conn->prepare("DELETE FROM comments WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $comment_id, $current_user_id);
        
        if ($stmt->execute()) {
            $update_success = 'Comment deleted successfully!';
        } else {
            $update_error = 'Failed to delete comment.';
        }
        $stmt->close();
        
        // Refresh the page
        header('Location: profile.php' . ($is_own_profile ? '' : '?user_id=' . $profile_user_id));
        exit();
    } else {
        $update_error = 'You can only delete your own comments.';
    }
}

// Handle profile deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_profile']) && $is_own_profile) {
    // Get all posts with images to delete image files
    $stmt = $conn->prepare("SELECT image_path FROM posts WHERE user_id = ? AND image_path IS NOT NULL");
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $image_paths = [];
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['image_path'])) {
            $image_paths[] = $row['image_path'];
        }
    }
    $stmt->close();
    
    // Delete image files
    foreach ($image_paths as $image_path) {
        if (file_exists($image_path)) {
            @unlink($image_path);
        }
    }
    
    // Delete the user (CASCADE will handle posts, comments, likes, friendships)
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $current_user_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        
        // Logout and redirect to login
        logout();
        exit();
    } else {
        $update_error = 'Failed to delete profile. Please try again.';
        $stmt->close();
    }
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
            <h2 class="nav-brand">Echo</h2>
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
                    <div class="profile-stats">
                        <span><strong><?php echo $stats['post_count']; ?></strong> posts</span>
                        <a href="?user_id=<?php echo $profile_user_id; ?>&show=followers" class="profile-stat-link">
                            <strong><?php echo $stats['followers_count']; ?></strong> followers
                        </a>
                        <a href="?user_id=<?php echo $profile_user_id; ?>&show=following" class="profile-stat-link">
                            <strong><?php echo $stats['following_count']; ?></strong> following
                        </a>
                    </div>
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
                        
                        <div style="margin-top: 30px; padding-top: 30px; border-top: 1px solid var(--border-color);">
                            <h3 style="color: var(--error-color); margin-bottom: 16px; font-size: 16px;">Danger Zone</h3>
                            <p style="color: var(--text-secondary); font-size: 14px; margin-bottom: 16px;">
                                Once you delete your profile, there is no going back. All your posts, comments, and data will be permanently deleted.
                            </p>
                            <form method="POST" action="" onsubmit="return confirm('Are you absolutely sure you want to delete your profile? This action cannot be undone. All your posts, comments, and data will be permanently deleted.');">
                                <button type="submit" name="delete_profile" class="btn btn-delete-profile">Delete Profile</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Followers/Following Modal -->
                <?php if ($show_followers || $show_following): ?>
                    <div class="modal-overlay" onclick="closeFollowersModal()">
                        <div class="modal-content followers-modal" onclick="event.stopPropagation();">
                            <div class="modal-header">
                                <h2><?php echo $show_followers ? 'Followers' : 'Following'; ?></h2>
                                <button class="close-button" onclick="closeFollowersModal()">×</button>
                            </div>
                            <div class="modal-body">
                                <?php if ($show_followers): ?>
                                    <?php if (empty($followers_list)): ?>
                                        <p class="empty-state">No followers yet.</p>
                                    <?php else: ?>
                                        <div class="followers-list">
                                            <?php foreach ($followers_list as $follower): ?>
                                                <div class="follower-item">
                                                    <a href="profile.php?user_id=<?php echo $follower['id']; ?>" class="follower-link">
                                                        <div class="follower-avatar">
                                                            <?php echo strtoupper(substr($follower['first_name'], 0, 1) . substr($follower['last_name'], 0, 1)); ?>
                                                        </div>
                                                        <div class="follower-info">
                                                            <strong><?php echo htmlspecialchars($follower['first_name'] . ' ' . $follower['last_name']); ?></strong>
                                                            <span class="follower-username">@<?php echo htmlspecialchars($follower['username']); ?></span>
                                                            <?php if (!empty($follower['bio'])): ?>
                                                                <p class="follower-bio"><?php echo htmlspecialchars($follower['bio']); ?></p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </a>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if ($show_following): ?>
                                    <?php if (empty($following_list)): ?>
                                        <p class="empty-state">Not following anyone yet.</p>
                                    <?php else: ?>
                                        <div class="followers-list">
                                            <?php foreach ($following_list as $following): ?>
                                                <div class="follower-item">
                                                    <a href="profile.php?user_id=<?php echo $following['id']; ?>" class="follower-link">
                                                        <div class="follower-avatar">
                                                            <?php echo strtoupper(substr($following['first_name'], 0, 1) . substr($following['last_name'], 0, 1)); ?>
                                                        </div>
                                                        <div class="follower-info">
                                                            <strong><?php echo htmlspecialchars($following['first_name'] . ' ' . $following['last_name']); ?></strong>
                                                            <span class="follower-username">@<?php echo htmlspecialchars($following['username']); ?></span>
                                                            <?php if (!empty($following['bio'])): ?>
                                                                <p class="follower-bio"><?php echo htmlspecialchars($following['bio']); ?></p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </a>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
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
                                        <button class="btn-comment" onclick="toggleComments(<?php echo $post['id']; ?>)">💬 <?php echo $post['comment_count']; ?></button>
                                        <?php if ($post['is_owner'] && $is_own_profile): ?>
                                            <form method="POST" action="" style="display: inline; margin-left: auto;" onsubmit="return confirm('Are you sure you want to delete this post? This action cannot be undone.');">
                                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                <button type="submit" name="delete_post" class="btn-delete-post">Delete Post</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Comments Section -->
                                    <div class="comments-section" id="comments-<?php echo $post['id']; ?>" style="display: none;">
                                        <?php if (!empty($post['comments'])): ?>
                                            <div class="comments-list">
                                                <?php foreach ($post['comments'] as $comment): ?>
                                                    <div class="comment">
                                                        <div class="comment-author-avatar">
                                                            <?php echo strtoupper(substr($comment['first_name'], 0, 1) . substr($comment['last_name'], 0, 1)); ?>
                                                        </div>
                                                        <div class="comment-content">
                                                            <strong><?php echo htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']); ?></strong>
                                                            <span><?php echo htmlspecialchars($comment['content']); ?></span>
                                                            <small><?php echo timeAgo($comment['created_at']); ?></small>
                                                        </div>
                                                        <?php if ($comment['is_comment_owner']): ?>
                                                            <form method="POST" action="" style="display: inline; margin-left: auto;" onsubmit="return confirm('Are you sure you want to delete this comment?');">
                                                                <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                                <button type="submit" name="delete_comment" class="btn-delete-comment" title="Delete comment">Delete Comment</button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="comments-list">
                                                <p style="color: var(--text-secondary); font-size: 14px; padding: 8px 0;">No comments yet.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function toggleComments(postId) {
            const commentsSection = document.getElementById('comments-' + postId);
            if (commentsSection.style.display === 'none') {
                commentsSection.style.display = 'block';
            } else {
                commentsSection.style.display = 'none';
            }
        }
        
        function closeFollowersModal() {
            const url = new URL(window.location);
            url.searchParams.delete('show');
            window.location.href = url.toString();
        }
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeFollowersModal();
            }
        });
    </script>
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

