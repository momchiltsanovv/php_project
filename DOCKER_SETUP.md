# Docker MySQL Setup Guide

## Quick Start

### Option 1: Using docker-compose (Recommended)

1. **Update docker-compose.yml** if needed (especially the MySQL root password)

2. **Start MySQL container:**
   ```bash
   docker-compose up -d
   ```

3. **Update config/database.php** with your MySQL root password:
   ```php
   const DB_PASS = 'rootpassword';  // Match the password in docker-compose.yml
   ```

4. **The database will be created automatically** from schema.sql when the container starts for the first time.

5. **Test the connection:**
   ```bash
   php -S localhost:8000
   ```
   Then visit: `http://localhost:8000/test_connection.php`

### Option 2: Manual Docker Setup

If you're running MySQL container manually:

1. **Find your MySQL container:**
   ```bash
   docker ps
   ```

2. **Check the port mapping:**
   ```bash
   docker port <container_name>
   ```
   Usually it's `3306:3306` (host:container)

3. **Update config/database.php:**
   - If port is different (e.g., `3307:3306`), change:
     ```php
     const DB_PORT = 3307;  // Use the host port
     ```
   - Update password if you set one:
     ```php
     const DB_PASS = 'your_password';
     ```

4. **Create the database:**
   ```bash
   # Method 1: Import via docker exec
   docker exec -i <container_name> mysql -u root -p < database/schema.sql
   
   # Method 2: Import from host (if port is mapped)
   mysql -h 127.0.0.1 -P 3306 -u root -p < database/schema.sql
   ```

## Common Docker Commands

```bash
# Start MySQL container
docker-compose up -d

# Stop MySQL container
docker-compose down

# View logs
docker-compose logs mysql

# Access MySQL CLI
docker exec -it <container_name> mysql -u root -p

# Or from host (if port is mapped)
mysql -h 127.0.0.1 -P 3306 -u root -p

# Check if container is running
docker ps | grep mysql
```

## Troubleshooting

### "Connection refused"
- Make sure the container is running: `docker ps`
- Check the port mapping: `docker port <container_name>`
- Verify the port in `config/database.php` matches the host port

### "Access denied"
- Check your MySQL root password in `config/database.php`
- If using docker-compose, check `MYSQL_ROOT_PASSWORD` in docker-compose.yml
- Try: `docker exec -it <container_name> mysql -u root -p`

### "Unknown database"
- The database wasn't created. Run the schema:
  ```bash
  docker exec -i <container_name> mysql -u root -p < database/schema.sql
  ```

### Port Already in Use
If port 3306 is already in use, change the host port in docker-compose.yml:
```yaml
ports:
  - "3307:3306"  # Use 3307 on host, 3306 in container
```
Then update `config/database.php`:
```php
const DB_PORT = 3307;
```

