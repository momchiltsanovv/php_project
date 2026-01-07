# Database Setup Guide

## Step 1: Make sure MySQL is running

On macOS:
```bash
# Check if MySQL is running
brew services list

# Start MySQL if not running
brew services start mysql
# OR
mysql.server start
```

On Linux:
```bash
# Check status
sudo systemctl status mysql

# Start MySQL
sudo systemctl start mysql
```

On Windows:
- Open Services and make sure MySQL service is running

## Step 2: Create the Database

You have two options:

### Option A: Using MySQL Command Line (Recommended)

1. Open terminal and run:
```bash
mysql -u root -p < database/schema.sql
```

2. Enter your MySQL root password when prompted

### Option B: Using MySQL Workbench or phpMyAdmin

1. Open MySQL Workbench or phpMyAdmin
2. Create a new database called `mini_social_media`
3. Copy and paste the contents of `database/schema.sql` and execute it

## Step 3: Update Database Credentials

Edit `config/database.php` and update these values if needed:

```php
const DB_HOST = 'localhost';      // Usually 'localhost'
const DB_USER = 'root';           // Your MySQL username
const DB_PASS = '';               // Your MySQL password (empty if no password)
const DB_NAME = 'mini_social_media'; // Database name
```

**Common configurations:**
- If you have a password: `const DB_PASS = 'your_password';`
- If using MAMP/XAMPP: `const DB_USER = 'root';` and `const DB_PASS = 'root';`
- If using a different port: `const DB_HOST = 'localhost:3307';`

## Step 4: Test the Connection

1. Start your PHP server:
```bash
php -S localhost:8000
```

2. Open your browser and go to:
```
http://localhost:8000/test_connection.php
```

3. You should see a success message if everything is working!

## Troubleshooting

### "Connection refused" or "Can't connect to MySQL server"
- Make sure MySQL is running (see Step 1)
- Check if MySQL is on a different port

### "Access denied for user"
- Verify your username and password in `config/database.php`
- Make sure the user has permissions to access the database

### "Unknown database 'mini_social_media'"
- The database doesn't exist yet - run the schema.sql file (see Step 2)

### "Table 'users' doesn't exist"
- The database exists but tables weren't created - run the schema.sql file again

## Quick Test Command

To quickly test if MySQL is accessible:
```bash
mysql -u root -p -e "SHOW DATABASES;"
```

