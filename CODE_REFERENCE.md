# Code Reference Guide - Echo Social Media

This document maps all features to their implementation locations in the codebase. Use this as a reference when answering questions about where features are implemented.

## Table of Contents
1. [Authentication & Session Management](#authentication--session-management)
2. [User Profile Features](#user-profile-features)
3. [Posts](#posts)
4. [Comments](#comments)
5. [Likes](#likes)
6. [Follow/Unfollow System](#followunfollow-system)
7. [Search Functionality](#search-functionality)
8. [Image Uploads](#image-uploads)
9. [Database Schema](#database-schema)
10. [Styling & UI](#styling--ui)

---

## Authentication & Session Management

### Login
- **File**: `login.php`
- **Location**: Lines 13-48
- **Key Functions**:
  - Form handling: Lines 13-48
  - Password verification: Line 29 (`password_verify()`)
  - Session creation: Lines 30-34
  - Redirect logic: Lines 6-9, 36

### Registration
- **File**: `register.php`
- **Location**: Lines 14-58
- **Key Functions**:
  - Form validation: Lines 23-30
  - Username/email uniqueness check: Lines 35-42
  - Password hashing: Line 44 (`password_hash()`)
  - User insertion: Line 45

### Logout
- **File**: `config/session.php`
- **Location**: Lines 32-49
- **Key Functions**:
  - Session cleanup: Lines 35-44
  - Cookie deletion: Lines 37-42
  - Session destruction: Line 45
- **Called from**: `feed.php` (line 66), `profile.php` (line 99), `search.php` (line 8)

### Session Management
- **File**: `config/session.php`
- **Functions**:
  - `isLoggedIn()`: Lines 11-13 - Checks if user is logged in
  - `requireLogin()`: Lines 16-21 - Redirects to login if not authenticated
  - `getCurrentUserId()`: Lines 24-26 - Returns current user ID

---

## User Profile Features

### View Profile
- **File**: `profile.php`
- **Location**: Lines 15-26
- **Key Code**:
  - User data fetch: Lines 16-21
  - Profile stats (posts, followers, following): Lines 28-49
  - Posts display: Lines 61-90

### Edit Profile
- **File**: `profile.php`
- **Location**: Lines 127-153
- **Key Code**:
  - Form handling: Lines 127-153
  - Update query: Line 135
  - Session refresh: Lines 141-147

### Delete Profile
- **File**: `profile.php`
- **Location**: Lines 232-260
- **Key Code**:
  - Image cleanup: Lines 236-246
  - User deletion: Line 249
  - Logout after deletion: Line 254
- **UI**: Lines 400-410 (Danger Zone section)

### Followers/Following Lists
- **File**: `profile.php`
- **Location**: 
  - Backend queries: Lines 58-95
  - Modal display: Lines 376-435
  - Clickable stats: Lines 270-274

---

## Posts

### Create Post
- **File**: `feed.php`
- **Location**: Lines 30-120
- **Key Code**:
  - Form handling: Lines 30-120
  - Image upload: Lines 40-95
  - Post insertion: Lines 97-119
- **UI Form**: Lines 310-340

### Edit Post
- **File**: `feed.php`
- **Location**: Lines 183-217
- **Key Code**:
  - Ownership verification: Lines 189-194
  - Content update: Line 202
  - Modal JavaScript: Lines 472-484
- **UI**: Lines 432-450 (Edit Modal)

### Delete Post
- **File**: `profile.php`
- **Location**: Lines 162-197
- **Key Code**:
  - Ownership check: Lines 167-172
  - Image deletion: Lines 175-178
  - Post deletion: Line 181
- **UI**: Lines 396-400 (Delete button in post-meta)

### Display Posts in Feed
- **File**: `feed.php`
- **Location**: Lines 245-288
- **Key Code**:
  - Posts query: Lines 246-262
  - Comments fetching: Lines 270-285
  - Display loop: Lines 355-423

### Display Posts on Profile
- **File**: `profile.php`
- **Location**: Lines 61-90
- **Key Code**:
  - Posts query: Lines 62-75
  - Comments fetching: Lines 77-88
  - Display loop: Lines 382-445

---

## Comments

### Add Comment
- **File**: `feed.php`
- **Location**: Lines 219-232
- **Key Code**:
  - Comment insertion: Line 225
  - Form handling: Lines 220-231
- **UI Form**: Lines 414-420

### Delete Comment
- **File**: `profile.php`
- **Location**: Lines 199-231
- **Key Code**:
  - Ownership verification: Lines 204-209
  - Comment deletion: Line 212
- **UI**: Lines 418-422 (Delete button)

### Display Comments
- **File**: `feed.php`
- **Location**: Lines 270-285, 398-411
- **Key Code**:
  - Comments query: Lines 271-277
  - Display loop: Lines 400-410
- **Toggle functionality**: JavaScript function `toggleComments()` at line 453

---

## Likes

### Toggle Like
- **File**: `feed.php`
- **Location**: Lines 122-181
- **Key Code**:
  - Like check: Lines 125-131
  - Like insertion: Line 135
  - Like deletion: Line 137
- **UI**: Lines 381-386 (Like button)

### Like Count Display
- **File**: `feed.php`
- **Location**: 
  - Query: Line 248 (in posts query)
  - Display: Line 394

---

## Follow/Unfollow System

### Follow/Unfollow Action
- **File**: `profile.php`
- **Location**: Lines 103-117
- **Key Code**:
  - Follow check: Lines 103-117
  - Follow insertion: Line 110
  - Unfollow deletion: Line 106
- **UI**: Lines 285-291 (Follow button)

### Follow Status Check
- **File**: `profile.php`
- **Location**: Lines 51-59
- **Key Code**:
  - Status query: Lines 54-58

### Followers Count
- **File**: `profile.php`
- **Location**: Lines 35-41
- **Key Code**:
  - Count query: Lines 36-40

### Following Count
- **File**: `profile.php`
- **Location**: Lines 43-49
- **Key Code**:
  - Count query: Lines 44-48

---

## Search Functionality

### Search Users
- **File**: `search.php`
- **Location**: Lines 43-75
- **Key Code**:
  - Search query: Lines 47-58
  - Search term handling: Line 44
  - Results display: Lines 77-159

### Display All Users
- **File**: `search.php`
- **Location**: Lines 77-95
- **Key Code**:
  - Users query: Lines 78-88
  - Display loop: Lines 105-157

---

## Image Uploads

### Image Upload Handling
- **File**: `feed.php`
- **Location**: Lines 40-95
- **Key Code**:
  - File validation: Lines 42-60
  - MIME type check: Lines 47-52
  - File size check: Line 55
  - Unique filename: Lines 62-65
  - File move: Line 68
  - Directory creation: Lines 70-73

### Image Display
- **File**: `feed.php`
- **Location**: Lines 368-372
- **Key Code**:
  - Image check: Line 368
  - Image tag: Line 370

### Image Preview (JavaScript)
- **File**: `feed.php`
- **Location**: Lines 458-464
- **Function**: `previewImage()`

---

## Database Schema

### Database Connection
- **File**: `config/database.php`
- **Location**: Lines 1-34
- **Key Functions**:
  - `getDBConnection()`: Lines 11-32
  - Connection configuration: Lines 4-9

### Database Schema
- **File**: `database/schema.sql`
- **Tables**:
  - `users`: Lines 6-17
  - `posts`: Lines 20-30 (with CASCADE delete)
  - `friendships`: Lines 33-44 (with CASCADE delete)
  - `comments`: Lines 47-57 (with CASCADE delete)
  - `likes`: Lines 60-70 (with CASCADE delete)

---

## Styling & UI

### Main Stylesheet
- **File**: `assets/css/style.css`
- **Key Sections**:
  - CSS Variables: Lines 8-30
  - Navbar: Lines 49-99
  - Forms: Lines 129-180
  - Buttons: Lines 183-234
  - Post Cards: Lines 451-650
  - Profile Styles: Lines 350-450
  - Modals: Lines 1122-1217
  - Followers Modal: Lines 1219-1321

### Dark Theme
- **File**: `assets/css/style.css`
- **Location**: Lines 8-30 (CSS Variables)
- **Key Colors**:
  - Background: `#000000`
  - Cards: `#1a1a1a`
  - Text: `#f5f5f5`
  - Primary (Pink): `#e91e63`

### Responsive Design
- **File**: `assets/css/style.css`
- **Location**: Lines 1237-1285
- **Media Query**: `@media (max-width: 768px)`

---

## Key JavaScript Functions

### Comment Toggle
- **File**: `feed.php`
- **Location**: Lines 453-456
- **Function**: `toggleComments(postId)`

### Edit Post Modal
- **File**: `feed.php`
- **Location**: Lines 472-499
- **Functions**:
  - `openEditModal()`: Lines 472-479
  - `closeEditModal()`: Lines 481-484

### Image Preview
- **File**: `feed.php`
- **Location**: Lines 458-464
- **Function**: `previewImage(input)`

### Followers Modal
- **File**: `profile.php`
- **Location**: Lines 537-545
- **Function**: `closeFollowersModal()`

---

## Security Features

### SQL Injection Prevention
- **Method**: Prepared statements
- **Example**: `feed.php` line 97: `$stmt = $conn->prepare("INSERT INTO posts...")`
- **Used throughout**: All database queries use `prepare()` and `bind_param()`

### XSS Prevention
- **Method**: `htmlspecialchars()`
- **Example**: `feed.php` line 376: `<?php echo nl2br(htmlspecialchars($post['content'])); ?>`
- **Used throughout**: All user-generated content is escaped

### Authentication Checks
- **File**: `config/session.php`
- **Function**: `requireLogin()` - Lines 16-21
- **Used in**: All protected pages (feed.php, profile.php, search.php)

### Ownership Verification
- **Example**: `profile.php` lines 167-172 (post deletion)
- **Pattern**: Check `user_id` matches `current_user_id` before allowing actions

---

## File Structure

```
php_project/
├── config/
│   ├── database.php      # Database connection
│   └── session.php       # Session management
├── database/
│   └── schema.sql        # Database schema
├── assets/
│   └── css/
│       └── style.css     # All styling
├── uploads/
│   └── posts/            # Uploaded images
├── feed.php              # Main feed page
├── profile.php           # User profiles
├── search.php            # User search
├── login.php             # Login page
├── register.php          # Registration page
└── index.php             # Redirect handler
```

---

## Common Questions & Answers

**Q: Where is user authentication handled?**
A: Login in `login.php` (lines 13-48), session management in `config/session.php`, logout in `config/session.php` (lines 32-49).

**Q: Where are posts created?**
A: `feed.php` lines 30-120 handles post creation, including image uploads.

**Q: Where is the follow/unfollow functionality?**
A: `profile.php` lines 103-117 handles follow/unfollow actions.

**Q: Where are comments added?**
A: `feed.php` lines 219-232 handles comment creation.

**Q: Where is the database connection configured?**
A: `config/database.php` lines 1-34 contains the database connection function.

**Q: Where is the dark theme defined?**
A: `assets/css/style.css` lines 8-30 contains CSS variables for the dark theme.

**Q: How are SQL injections prevented?**
A: All queries use prepared statements with `prepare()` and `bind_param()` throughout the codebase.

**Q: Where are image uploads handled?**
A: `feed.php` lines 40-95 handles image validation, storage, and file management.

