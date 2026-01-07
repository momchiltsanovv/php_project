# Mini Social Media - PHP Project

A simple social media application built with PHP and MySQL, featuring user authentication and profile management.

## Features

- User registration and login
- User profile page with bio
- Profile editing functionality
- Secure password hashing
- Session management
- Modern, responsive UI design

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server (or PHP built-in server)

## Installation

1. **Database Setup**
   - Create a MySQL database
   - Import the schema file:
     ```bash
     mysql -u root -p < database/schema.sql
     ```
   - Or manually run the SQL commands in `database/schema.sql`

2. **Database Configuration**
   - Edit `config/database.php` and update the database credentials:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'your_username');
     define('DB_PASS', 'your_password');
     define('DB_NAME', 'mini_social_media');
     ```

3. **Run the Application**
   - Using PHP built-in server:
     ```bash
     php -S localhost:8000
     ```
   - Or configure Apache/Nginx to point to the project directory

4. **Access the Application**
   - Open your browser and navigate to `http://localhost:8000`
   - You'll be redirected to the login page

## Demo Account

- Username: `demo_user`
- Password: `password123`

## Project Structure

```
php_project/
├── assets/
│   └── css/
│       └── style.css          # Main stylesheet
├── config/
│   ├── database.php           # Database connection
│   └── session.php            # Session management
├── database/
│   └── schema.sql             # Database schema
├── index.php                  # Home page (redirects)
├── login.php                  # Login page
├── register.php               # Registration page
├── profile.php                # User profile page
└── README.md                  # This file
```

## Security Features

- Password hashing using PHP's `password_hash()` function
- Prepared statements to prevent SQL injection
- Session-based authentication
- Input validation and sanitization
- XSS protection using `htmlspecialchars()`

## Notes

- Make sure your web server has write permissions if you plan to add file uploads
- For production use, consider:
  - Using HTTPS
  - Adding CSRF protection
  - Implementing rate limiting
  - Adding email verification
  - Using environment variables for sensitive configuration

