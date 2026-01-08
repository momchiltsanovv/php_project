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

// Handle post creation
$post_error = '';
$post_success = '';

// Create uploads directory if it doesn't exist
$upload_dir = 'uploads/posts/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_post'])) {
    $content = trim($_POST['content'] ?? '');
    $image_path = null;
    
    // Debug: Log what we received
    error_log("Post content received: '" . $content . "' (length: " . strlen($content) . ")");
    error_log("Files received: " . (isset($_FILES['post_image']) ? 'yes' : 'no'));
    
    // Handle image upload
    if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['post_image'];
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        // Validate file type
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            // finfo_close() is deprecated in PHP 8.5+, objects are freed automatically
        } else {
            // Fallback to file extension check
            $mime_type = mime_content_type($file['tmp_name']);
        }
        
        if (!in_array($mime_type, $allowed_types)) {
            $post_error = 'Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed. (Detected: ' . $mime_type . ')';
        } elseif ($file['size'] > $max_size) {
            $post_error = 'Image size is too large. Maximum size is 5MB. (Your file: ' . round($file['size'] / 1024 / 1024, 2) . 'MB)';
        } else {
            // Generate unique filename
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = uniqid('post_', true) . '_' . time() . '.' . $file_extension;
            $target_path = $upload_dir . $filename;
            
            // Ensure directory exists and is writable
            if (!is_writable($upload_dir)) {
                $post_error = 'Upload directory is not writable. Please check permissions.';
            } elseif (move_uploaded_file($file['tmp_name'], $target_path)) {
                $image_path = $target_path;
            } else {
                $post_error = 'Failed to upload image. Please check directory permissions.';
            }
        }
    } elseif (isset($_FILES['post_image']) && $_FILES['post_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Handle upload errors
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive.',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive.',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.'
        ];
        $error_code = $_FILES['post_image']['error'];
        $post_error = 'Upload error: ' . ($upload_errors[$error_code] ?? 'Unknown error (code: ' . $error_code . ')');
    }
    
    // Validate content (can be empty if image is provided, or both can be provided)
    if (empty($content) && empty($image_path)) {
        $post_error = 'Please provide either text content or an image.';
    } elseif (!empty($content) && strlen($content) > 1000) {
        $post_error = 'Post content is too long (max 1000 characters).';
    } else {
        // Check if image_path column exists
        $check_column = $conn->query("SHOW COLUMNS FROM posts LIKE 'image_path'");
        $has_image_column = $check_column->num_rows > 0;
        
        // Debug: Log what we have
        if ($image_path) {
            error_log("Image path: " . $image_path);
            error_log("Has image column: " . ($has_image_column ? 'yes' : 'no'));
        }
        
        // Insert post with or without image
        $stmt = null;
        
        if ($image_path && $has_image_column) {
            // Insert post with image (and optional text content)
            error_log("Inserting post with image and content. Content: '" . $content . "', Image: " . $image_path);
            $stmt = $conn->prepare("INSERT INTO posts (user_id, content, image_path) VALUES (?, ?, ?)");
            if (!$stmt) {
                $post_error = 'Database error: ' . $conn->error;
            } else {
                // Content can be empty string if only image is provided, or can have text
                $stmt->bind_param("iss", $user_id, $content, $image_path);
            }
        } elseif ($image_path && !$has_image_column) {
            // Image uploaded but column doesn't exist
            $post_error = 'Image upload is not available. Please run migrate_add_image_column.php to update your database.';
            // Still insert text-only post if there's content
            if (!empty($content)) {
                $stmt = $conn->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
                if (!$stmt) {
                    $post_error .= ' Also failed to create text post: ' . $conn->error;
                } else {
                    $stmt->bind_param("is", $user_id, $content);
                }
            }
        } else {
            // Insert text-only post
            $stmt = $conn->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
            if (!$stmt) {
                $post_error = 'Database error: ' . $conn->error;
            } else {
                $stmt->bind_param("is", $user_id, $content);
            }
        }
        
        // Execute the statement if we have one and no errors
        if (empty($post_error) && $stmt !== null) {
            if ($stmt->execute()) {
                $post_success = 'Post created successfully!';
                // Clear the form by redirecting
                header('Location: feed.php');
                exit();
            } else {
                $post_error = 'Failed to create post: ' . $stmt->error;
                error_log("Post creation failed: " . $stmt->error);
                error_log("User ID: " . $user_id . ", Content: '" . $content . "', Image: " . ($image_path ?? 'none'));
            }
            $stmt->close();
        } elseif ($stmt !== null) {
            // If there was an error but we still have a statement, close it
            $stmt->close();
        } elseif (empty($post_error)) {
            // No statement was created but no error was set
            $post_error = 'Unable to create post. Please check that you have either text or an image.';
            error_log("No statement created. Content: '" . $content . "', Image: " . ($image_path ?? 'none') . ", Has column: " . ($has_image_column ?? 'unknown'));
        }
    }
}

// Handle like/unlike
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_like'])) {
    $post_id = intval($_POST['post_id']);
    
    // Check if already liked
    $stmt = $conn->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Unlike
        $stmt = $conn->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $post_id, $user_id);
        $stmt->execute();
    } else {
        // Like
        $stmt = $conn->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $post_id, $user_id);
        $stmt->execute();
    }
    $stmt->close();
    header('Location: feed.php');
    exit();
}

// Handle post edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_post'])) {
    $post_id = intval($_POST['post_id']);
    $new_content = trim($_POST['edit_content'] ?? '');
    
    // Verify user owns this post
    $stmt = $conn->prepare("SELECT user_id FROM posts WHERE id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post_owner = $result->fetch_assoc();
    $stmt->close();
    
    if ($post_owner && $post_owner['user_id'] == $user_id) {
        // Validate content
        if (strlen($new_content) > 1000) {
            $post_error = 'Post content is too long (max 1000 characters).';
        } else {
            // Update post content
            $stmt = $conn->prepare("UPDATE posts SET content = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("sii", $new_content, $post_id, $user_id);
            
            if ($stmt->execute()) {
                $post_success = 'Post updated successfully!';
                header('Location: feed.php');
                exit();
            } else {
                $post_error = 'Failed to update post.';
            }
            $stmt->close();
        }
    } else {
        $post_error = 'You can only edit your own posts.';
    }
}

// Handle comment creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $post_id = intval($_POST['post_id']);
    $content = trim($_POST['comment_content'] ?? '');
    
    if (!empty($content) && strlen($content) <= 500) {
        $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $post_id, $user_id, $content);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: feed.php');
    exit();
}

// Get current user info
$stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$current_user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Check if image_path column exists for posts
$check_image_column = $conn->query("SHOW COLUMNS FROM posts LIKE 'image_path'");
$has_image_column = $check_image_column->num_rows > 0;

// Get posts from user and their friends
$posts_query = "
    SELECT p.*, u.id as user_id, u.username, u.first_name, u.last_name,
           (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
           (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as is_liked,
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
           (p.user_id = ?) as is_owner
    FROM posts p
    INNER JOIN users u ON p.user_id = u.id
    WHERE p.user_id = ? 
       OR p.user_id IN (
           SELECT friend_id FROM friendships WHERE user_id = ? AND status = 'accepted'
           UNION
           SELECT user_id FROM friendships WHERE friend_id = ? AND status = 'accepted'
       )
    ORDER BY p.created_at DESC
    LIMIT 50
";

$stmt = $conn->prepare($posts_query);
$stmt->bind_param("iiiii", $user_id, $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$posts_result = $stmt->get_result();
$posts = [];
while ($post = $posts_result->fetch_assoc()) {
    // Get comments for this post
    $comments_stmt = $conn->prepare("
        SELECT c.*, u.username, u.first_name, u.last_name 
        FROM comments c 
        INNER JOIN users u ON c.user_id = u.id 
        WHERE c.post_id = ? 
        ORDER BY c.created_at ASC
    ");
    $comments_stmt->bind_param("i", $post['id']);
    $comments_stmt->execute();
    $comments_result = $comments_stmt->get_result();
    $post['comments'] = [];
    while ($comment = $comments_result->fetch_assoc()) {
        $post['comments'][] = $comment;
    }
    $comments_stmt->close();
    $posts[] = $post;
}
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed - Echo</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h2 class="nav-brand">Echo</h2>
            <div class="nav-links">
                <a href="feed.php" class="nav-link active">Feed</a>
                <a href="search.php" class="nav-link">Search</a>
                <a href="profile.php" class="nav-link">Profile</a>
                <a href="?logout=1" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="feed-container">
            <!-- Create Post Form -->
            <div class="post-card create-post">
                <h3>What's on your mind?</h3>
                
                <?php if (!empty($post_error)): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($post_error); ?></div>
                <?php endif; ?>
                
                <?php if (!empty($post_success)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($post_success); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="" class="post-form" enctype="multipart/form-data">
                    <textarea name="content" rows="3" placeholder="Share something with your friends..." maxlength="1000"></textarea>
                    
                    <div class="image-upload-section">
                        <label for="post_image" class="image-upload-label">
                            <span class="upload-icon">📷</span>
                            <span class="upload-text">Add Photo</span>
                        </label>
                        <input type="file" id="post_image" name="post_image" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" style="display: none;" onchange="previewImage(this)">
                        <div id="image-preview" class="image-preview" style="display: none;">
                            <img id="preview-img" src="" alt="Preview">
                            <button type="button" class="remove-image" onclick="removeImage()">×</button>
                        </div>
                    </div>
                    
                    <div class="post-actions">
                        <button type="submit" name="create_post" class="btn btn-primary">Post</button>
                    </div>
                </form>
            </div>
            
            <!-- Posts Feed -->
            <div class="posts-feed">
                <?php if (empty($posts)): ?>
                    <div class="post-card">
                        <p class="empty-feed">No posts yet. Start by creating a post or following some users!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="post-card">
                            <div class="post-header">
                                <div class="post-author-avatar">
                                    <?php echo strtoupper(substr($post['first_name'], 0, 1) . substr($post['last_name'], 0, 1)); ?>
                                </div>
                                <div class="post-author-info">
                                    <strong><?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']); ?></strong>
                                    <span class="post-username">@<?php echo htmlspecialchars($post['username']); ?></span>
                                    <span class="post-time"><?php echo timeAgo($post['created_at']); ?></span>
                                </div>
                            </div>
                            
                            <?php if ($has_image_column && !empty($post['image_path']) && file_exists($post['image_path'])): ?>
                                <div class="post-image">
                                    <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="Post image">
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($post['content'])): ?>
                                <div class="post-content">
                                    <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="post-actions-bar">
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <button type="submit" name="toggle_like" class="btn-like <?php echo $post['is_liked'] ? 'liked' : ''; ?>">
                                        <?php echo $post['is_liked'] ? '❤️' : '🤍'; ?>
                                    </button>
                                </form>
                                <button class="btn-comment" onclick="toggleComments(<?php echo $post['id']; ?>)">💬</button>
                                <?php if ($post['is_owner']): ?>
                                    <button class="btn-edit" onclick="openEditModal(<?php echo $post['id']; ?>, '<?php echo htmlspecialchars(addslashes($post['content'])); ?>')">✏️</button>
                                <?php endif; ?>
                            </div>
                            
                            <div class="post-stats">
                                <strong><?php echo $post['like_count']; ?> likes</strong>
                                <?php if ($post['comment_count'] > 0): ?>
                                    <strong><?php echo $post['comment_count']; ?> comments</strong>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Comments Section -->
                            <div class="comments-section" id="comments-<?php echo $post['id']; ?>" style="display: none;">
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
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <form method="POST" action="" class="comment-form">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <div class="comment-input-group">
                                        <input type="text" name="comment_content" placeholder="Write a comment..." maxlength="500" required>
                                        <button type="submit" name="add_comment" class="btn btn-small">Post</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Edit Post Modal -->
    <div id="editModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Post</h2>
                <button class="modal-close" onclick="closeEditModal()">×</button>
            </div>
            <form method="POST" action="" id="editPostForm">
                <input type="hidden" name="post_id" id="edit_post_id">
                <div class="form-group" style="padding: 20px; margin: 0;">
                    <textarea name="edit_content" id="edit_content" rows="5" maxlength="1000" required style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: var(--radius); font-size: 14px; font-family: inherit; resize: vertical; min-height: 120px; background: var(--bg-secondary); color: var(--text-color);"></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" name="edit_post" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function toggleComments(postId) {
            const commentsSection = document.getElementById('comments-' + postId);
            commentsSection.style.display = commentsSection.style.display === 'none' ? 'block' : 'none';
        }
        
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview-img').src = e.target.result;
                    document.getElementById('image-preview').style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function removeImage() {
            document.getElementById('post_image').value = '';
            document.getElementById('image-preview').style.display = 'none';
            document.getElementById('preview-img').src = '';
        }
        
        function openEditModal(postId, content) {
            document.getElementById('edit_post_id').value = postId;
            // Convert HTML line breaks back to newlines for editing
            const textContent = content.replace(/<br\s*\/?>/gi, '\n');
            document.getElementById('edit_content').value = textContent;
            document.getElementById('editModal').style.display = 'flex';
            document.getElementById('edit_content').focus();
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
            document.getElementById('edit_content').value = '';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeEditModal();
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

