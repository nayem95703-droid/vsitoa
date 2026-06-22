<?php
/**
 * Database Migration Script
 * Sets up all required tables for authentication and admin functionality
 */

define('ROOT_PATH', __DIR__);

require_once ROOT_PATH . '/core/Config.php';
require_once ROOT_PATH . '/core/Logger.php';
require_once ROOT_PATH . '/core/Database.php';

\Core\Config::load();
\Core\Database::initialize();

$migrations = [];

try {
    echo "🔄 Starting database migrations...\n\n";

    // 1. Create admins table
    echo "1️⃣  Creating admins table...\n";
    $adminTableSQL = "CREATE TABLE IF NOT EXISTS admins (
        admin_id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(100) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(50) DEFAULT 'superadmin',
        permissions JSON DEFAULT NULL,
        status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_username (username),
        INDEX idx_status (status)
    )";
    
    \Core\Database::query($adminTableSQL);
    echo "   ✅ Admins table created/verified\n\n";

    // 2. Create email verifications table if missing
    echo "2️⃣  Creating email_verifications table...\n";
    $emailVerTable = "CREATE TABLE IF NOT EXISTS email_verifications (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        token VARCHAR(255) NOT NULL UNIQUE,
        expires_at TIMESTAMP NOT NULL,
        used BOOLEAN DEFAULT FALSE,
        used_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        INDEX idx_token (token),
        INDEX idx_user_id (user_id)
    )";
    
    \Core\Database::query($emailVerTable);
    echo "   ✅ Email verifications table created/verified\n\n";

    // 3. Create password resets table if missing
    echo "3️⃣  Creating password_resets table...\n";
    $passwordResetTable = "CREATE TABLE IF NOT EXISTS password_resets (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        token VARCHAR(255) NOT NULL UNIQUE,
        expires_at TIMESTAMP NOT NULL,
        used BOOLEAN DEFAULT FALSE,
        used_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        INDEX idx_token (token),
        INDEX idx_user_id (user_id)
    )";
    
    \Core\Database::query($passwordResetTable);
    echo "   ✅ Password resets table created/verified\n\n";

    // 4. Create login attempts table if missing
    echo "4️⃣  Creating login_attempts table...\n";
    $loginAttemptsTable = "CREATE TABLE IF NOT EXISTS login_attempts (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NULL,
        username_or_email VARCHAR(255),
        ip_address VARCHAR(45),
        success BOOLEAN DEFAULT FALSE,
        attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
        INDEX idx_ip_address (ip_address),
        INDEX idx_user_id (user_id),
        INDEX idx_attempted_at (attempted_at)
    )";
    
    \Core\Database::query($loginAttemptsTable);
    echo "   ✅ Login attempts table created/verified\n\n";

    // 5. Check if admin exists
    echo "5️⃣  Checking admin account...\n";
    $adminExists = \Core\Database::fetch(
        "SELECT admin_id FROM admins WHERE username = ?",
        ['admin']
    );

    if (!$adminExists) {
        echo "   ⚠️  Admin account not found, creating default admin...\n";
        // Password: admin123
        $hashedPassword = '$2y$10$YIQfkVDr2F0xIGmxvZ0CeOYf0EgLGZfZkQx8PZlKc8JZT4WzCpT2K';
        
        \Core\Database::insert('admins', [
            'username' => 'admin',
            'email' => 'admin@vsitoa.com',
            'password' => $hashedPassword,
            'role' => 'superadmin',
            'status' => 'active'
        ]);
        
        echo "   ✅ Default admin account created\n";
        echo "      Username: admin\n";
        echo "      Password: admin123\n";
        echo "      ⚠️  IMPORTANT: Change password after first login!\n\n";
    } else {
        echo "   ✅ Admin account exists\n\n";
    }

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ All migrations completed successfully!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    echo "🔑 Admin Login Credentials:\n";
    echo "   URL: {$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}/admin/login\n";
    echo "   Username: admin\n";
    echo "   Password: admin123\n\n";

    echo "🔗 Quick Links:\n";
    echo "   Home: {$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}/\n";
    echo "   Admin: {$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}/admin\n";
    echo "   Login: {$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}/login\n";
    
} catch (\Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
