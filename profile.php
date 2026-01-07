<?php
require_once 'config/database.php';
require_once 'config/session.php';

// Require login to view profile
requireLogin();

$conn = getDBConnection();
$user_id = getCurrentUserId();

// Fetch user data from database
$stmt = $conn->prepare("SELECT id, username, email, first_name, last_name, bio, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Handle logout
if (isset($_GET['logout'])) {
    logout();
}

// Handle profile update
$update_error = '';
$update_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    
    if (empty($first_name) || empty($last_name)) {
        $update_error = 'First name and last name are required.';
    } else {
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, bio = ? WHERE id = ?");
        $stmt->bind_param("sssi", $first_name, $last_name, $bio, $user_id);
        
        if ($stmt->execute()) {
            $update_success = 'Profile updated successfully!';
            // Refresh user data
            $stmt = $conn->prepare("SELECT id, username, email, first_name, last_name, bio, created_at FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
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
                    <p class="email"><?php echo htmlspecialchars($user['email']); ?></p>
                    <p class="member-since">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
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
            </div>
        </div>
    </div>
</body>
</html>

