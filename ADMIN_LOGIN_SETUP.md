# VSItoA - Admin Login & Authentication Setup Guide

## 🔐 Quick Start: Admin Login

### Default Credentials
```
URL: http://localhost:8000/vsitoa/admin/login
Password: admin123
```

**Note:** Only password is required (no username field on admin login page)

---

## 📋 Setup Instructions

### Step 1: Create Database
```bash
# Option A: Using MySQL CLI
mysql -u root -p -e "CREATE DATABASE vsitoa;"

# Option B: Using phpMyAdmin
# Create a database named 'vsitoa'
```

### Step 2: Import Database Schema
```bash
# Option A: Using MySQL CLI
mysql -u root -p vsitoa < database/schema.sql

# Option B: Using phpMyAdmin
# Import the file: database/schema.sql

# Option C: Using PHP Migration
php migrate_simple.php
```

### Step 3: Verify Admin Account
After importing the schema, the following test accounts are automatically created:

| Role | Username | Password | Email |
|------|----------|----------|-------|
| Admin | admin | admin123 | admin@vsitoa.com |
| Advertiser | advertiser1 | admin123 | advertiser@vsitoa.com |
| Worker | worker1 | admin123 | worker@vsitoa.com |

### Step 4: Start Application
```bash
# Option A: Using PHP Development Server
php -S 127.0.0.1:8000

# Option B: Using XAMPP
# Start Apache and MySQL modules in XAMPP

# Option C: Using Docker (if available)
docker run -d -p 80:80 -v /path/to/vsitoa:/var/www/html php:8.0-apache
```

### Step 5: Access the Application
```
Home:     http://localhost:8000/vsitoa/ (or http://localhost/vsitoa/)
Admin:    http://localhost:8000/vsitoa/admin
Login:    http://localhost:8000/vsitoa/login
Register: http://localhost:8000/vsitoa/register
```

---

## 🔑 Login Endpoints

### User Login (Email + Password)
**URL:** `/login` (POST)
```json
{
  "email": "advertiser@vsitoa.com",
  "password": "admin123"
}
```

### Admin Login (Password Only)
**URL:** `/admin/login` (POST)
```json
{
  "password": "admin123"
}
```

### User Registration
**URL:** `/register` (POST)
```json
{
  "username": "newuser",
  "email": "newuser@example.com",
  "password": "securepass123",
  "confirm_password": "securepass123"
}
```

---

## 🔧 Database Schema

### Required Tables
The following tables are automatically created from `database/schema.sql`:

1. **users** - User accounts (worker, advertiser, admin)
2. **tasks** - Measurement ads tasks
3. **submissions** - Worker task submissions
4. **transactions** - Payment records
5. **task_analytics** - Task performance metrics
6. **email_verifications** - Email verification tokens
7. **password_resets** - Password reset tokens
8. **login_attempts** - Login attempt tracking
9. **admins** (optional) - Dedicated admin accounts

### Important Columns

**users table:**
```sql
- user_id (INT, PRIMARY KEY)
- username (VARCHAR, UNIQUE)
- email (VARCHAR, UNIQUE)
- password (VARCHAR, bcrypt hashed)
- user_type (ENUM: 'worker', 'advertiser', 'admin')
- is_active (BOOLEAN)
- is_verified (BOOLEAN)
```

**admins table (optional):**
```sql
- admin_id (INT, PRIMARY KEY)
- username (VARCHAR, UNIQUE)
- email (VARCHAR)
- password (VARCHAR, bcrypt hashed)
- role (VARCHAR: 'superadmin')
- status (ENUM: 'active', 'inactive', 'suspended')
```

---

## 🐛 Troubleshooting

### Issue 1: Admin Login Not Working

**Error:** "Invalid credentials"

**Solution:**
1. Verify admin user exists in database:
   ```sql
   SELECT * FROM users WHERE username = 'admin' AND user_type = 'admin';
   ```

2. Check password hash (should be `admin123`):
   ```bash
   php -r "echo password_hash('admin123', PASSWORD_DEFAULT);"
   ```

3. Verify `.env` file has correct database configuration:
   ```
   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=vsitoa
   DB_USERNAME=root
   DB_PASSWORD=
   ```

### Issue 2: Database Connection Error

**Error:** "SQLSTATE[HY000] [1049] Unknown database 'vsitoa'"

**Solution:**
```bash
# Create database first
mysql -u root -p -e "CREATE DATABASE vsitoa;"

# Then import schema
mysql -u root -p vsitoa < database/schema.sql
```

### Issue 3: PDO MySQL Driver Not Loaded

**Error:** "could not find driver"

**Solution:**
1. Enable PDO MySQL in `php.ini`:
   ```ini
   extension=pdo_mysql
   ```

2. Restart PHP/Apache:
   ```bash
   # For XAMPP: Restart Apache and MySQL
   # For CLI: The setting takes effect immediately
   ```

### Issue 4: Session Issues

**Error:** "Undefined $_SESSION variable"

**Solution:**
Make sure `session_start()` is called at the beginning of index.php:
```php
<?php
session_start(); // Must be first line
require_once 'config/database.php';
// ...
```

### Issue 5: Page Redirect Loop

**Cause:** Admin trying to access user pages while logged in as admin

**Solution:**
The system automatically redirects admins to `/admin` when they try to access user pages. This is by design for security.

---

## 🚀 Quick Test Workflow

### 1. Admin Login Test
```bash
curl -X POST http://localhost:8000/vsitoa/admin/login \
  -H "Content-Type: application/json" \
  -d '{"password": "admin123"}'
```

### 2. User Login Test
```bash
curl -X POST http://localhost:8000/vsitoa/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "worker@vsitoa.com",
    "password": "admin123"
  }'
```

### 3. Access Admin Panel
Open browser: `http://localhost:8000/vsitoa/admin`

---

## 📱 Mobile/Vercel Deployment

### For Vercel (Free Hosting)

1. **Add environment variables to Vercel:**
   ```
   DB_HOST=your-db-host
   DB_DATABASE=your-database
   DB_USERNAME=your-username
   DB_PASSWORD=your-password
   JWT_SECRET=your-secret-key
   ```

2. **Update deployment settings:**
   - Framework: PHP
   - Build: None
   - Output directory: ./

3. **Database options:**
   - **PlanetScale** (MySQL compatible, free tier)
   - **MongoDB Atlas** (NoSQL, free tier)
   - **Firebase** (Google's backend)
   - **Supabase** (PostgreSQL, free tier)

4. **For Console.clever (Console.clever.cloud):**
   - Deploy PHP app to Node.js runtime
   - Use environment variables for configuration
   - Add startup script to initialize database

---

## 🔐 Security Best Practices

1. **Change Default Password**
   After first login, change the admin password:
   ```php
   $newPassword = password_hash('your-new-password', PASSWORD_DEFAULT);
   // Update database
   ```

2. **Set Strong JWT Secret**
   In `.env`:
   ```
   JWT_SECRET=your-very-long-random-secret-key-at-least-32-chars
   ```

3. **Enable HTTPS**
   Set in `.env`:
   ```
   APP_URL=https://yourdomain.com
   ```

4. **Restrict Admin Login**
   Add IP whitelist in auth check

---

## 📊 API Endpoints

### Authentication Endpoints
```
POST /login                    - User login
POST /register                 - User registration
POST /admin/login              - Admin login
POST /logout                   - Logout
POST /forgot-password          - Request password reset
POST /reset-password           - Reset password
GET  /verify-email            - Verify email address
```

### Admin Endpoints (Protected)
```
GET  /admin                    - Admin dashboard
GET  /admin/users              - List users
POST /admin/users/{id}/ban     - Ban user
GET  /admin/settings           - Get settings
PUT  /admin/settings           - Update settings
```

---

## 📞 Support

### Common Questions

**Q: How do I change the admin password?**
A: Login as admin, go to Settings → Change Password

**Q: Can I have multiple admin accounts?**
A: Yes, create additional users with `user_type = 'admin'`

**Q: How do I reset the admin password?**
A: Run this SQL query:
```sql
UPDATE users 
SET password = '$2y$10$YIQfkVDr2F0xIGmxvZ0CeOYf0EgLGZfZkQx8PZlKc8JZT4WzCpT2K'
WHERE username = 'admin';
```

**Q: What if I forget the admin password?**
A: Use the SQL query above to reset to `admin123`

---

## ✅ Verification Checklist

- [ ] Database created: `vsitoa`
- [ ] Schema imported: `database/schema.sql`
- [ ] Admin user exists: `SELECT * FROM users WHERE username = 'admin'`
- [ ] PHP server running on port 8000
- [ ] Can access home page: http://localhost:8000/vsitoa/
- [ ] Can access admin login: http://localhost:8000/vsitoa/admin/login
- [ ] Admin login works with password: `admin123`
- [ ] JWT token is generated and stored in session
- [ ] Can access admin dashboard: http://localhost:8000/vsitoa/admin

---

## 📝 Version Information

- **Application:** VSItoA v1.0
- **PHP:** 7.4+ (tested with 8.0+)
- **MySQL:** 5.7+
- **Database:** Free options (PlanetScale, Supabase, MongoDB Atlas)
- **Hosting:** Free options (Vercel, Console.clever, Heroku)

---

**Last Updated:** 2024
**Status:** ✅ Ready for Testing and Deployment
