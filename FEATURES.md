# Echo Social Media - Features List

Your Echo social media app includes the following features:

## Core Features

### 1. **User Authentication**
- User registration with validation
- Login/Logout functionality
- Session management
- Secure password hashing (bcrypt)
- Redirect protection (logged-in users can't access login/register)

### 2. **User Profiles**
- View your own profile
- View other users' profiles
- Edit your profile (first name, last name, bio)
- Profile statistics (post count, followers count, following count)
- User posts display on profile
- **Delete profile** - Permanently delete account and all associated data
- Clickable followers/following counts with modal lists

### 3. **News Feed** (`feed.php`)
- Create new posts (text and images)
- View posts from yourself and people you follow
- Posts show author, timestamp, and content
- Real-time post creation
- Like and comment counts displayed
- Expandable comments section

### 4. **Posts System**
- Create text posts (up to 1000 characters)
- **Image uploads** - Upload images with posts
- **Edit posts** - Edit your own posts
- **Delete posts** - Delete your own posts (removes images too)
- View all posts in chronological order
- Posts display on user profiles
- Post statistics (like count, comment count)
- Image preview before posting

### 5. **Likes System**
- Like/unlike posts
- See total likes on each post
- Visual indicator when you've liked a post (red heart)
- One like per user per post
- Like count displayed on feed and profile

### 6. **Comments System**
- Comment on any post (up to 500 characters)
- View all comments on a post
- Comments show author, timestamp, and avatar
- **Delete comments** - Delete your own comments
- Expandable/collapsible comments section
- Comments displayed on both feed and profile pages

### 7. **Friends/Following System**
- Follow other users
- Unfollow users
- See posts from people you follow in your feed
- Follow status visible on profiles
- **Followers count** - See how many people follow you
- **Following count** - See how many people you follow
- **Clickable lists** - Click followers/following counts to see full lists
- Modal display of followers/following with user profiles

### 8. **User Search** (`search.php`)
- Search users by name or username
- Browse all users
- Follow/unfollow from search results
- View user profiles from search
- See user statistics (post count)
- Real-time search functionality

## Design & UI Features

### 9. **Dark Theme**
- Modern dark theme with black background
- Dark gray cards and borders
- Light text for readability
- Purple-pink accent colors for buttons
- Instagram-inspired layout
- Responsive design for mobile devices

### 10. **User Interface**
- Clean, modern navigation bar
- Sticky navbar for easy access
- Modal dialogs for editing and viewing lists
- Smooth transitions and animations
- Image preview functionality
- Confirmation dialogs for destructive actions

## Database Tables

1. **users** - User accounts and profiles
2. **posts** - User posts/content (with image_path column)
3. **friendships** - Following relationships
4. **comments** - Comments on posts
5. **likes** - Likes on posts

All tables use CASCADE DELETE for data integrity.

## Navigation

- **Feed** - Main news feed with posts
- **Search** - Discover and follow users
- **Profile** - Your profile or view others
- **Logout** - Sign out

## Security Features

- SQL injection prevention (prepared statements)
- XSS prevention (htmlspecialchars)
- Password hashing (bcrypt)
- Session security
- Ownership verification for edits/deletes
- File upload validation (MIME type, file size)

## How to Use

1. **Register/Login** - Create an account or login
2. **Create Posts** - Go to Feed and share text or images
3. **Follow Users** - Search for users and click "Follow"
4. **Interact** - Like and comment on posts
5. **View Profiles** - Click on any user to see their profile and posts
6. **Manage Content** - Edit or delete your posts and comments
7. **Explore** - Click followers/following counts to see full lists

## Implemented Features (Previously in "Next Steps")

✅ Post images/attachments
✅ Post editing
✅ Post deletion
✅ Comment deletion
✅ Profile deletion
✅ Followers/following counts
✅ Clickable followers/following lists

## Future Enhancements (Optional)

- Profile picture uploads
- Notifications system
- Direct messaging
- Hashtags
- User mentions (@username)
- Activity feed
- Friend suggestions
- Post sharing
- Stories feature

