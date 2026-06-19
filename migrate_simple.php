<?php
/**
 * Simple Database Migration Script
 * Sets up all required tables for authentication and admin functionality
 */

// Load environment variables
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Database configuration
$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? 3306;
$database = $_ENV['DB_DATABASE'] ?? 'vsitoa';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';

try {
    echo "🔄 Starting database migrations...\n\n";

    // Connect to database
    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "✅ Connected to database: $database\n\n";

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
        last_login_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_username (username),
        INDEX idx_status (status)
    )";
    
    $pdo->exec($adminTableSQL);
    echo "   ✅ Admins table created/verified\n\n";

    // 2. Create email verifications table
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
    
    $pdo->exec($emailVerTable);
    echo "   ✅ Email verifications table created/verified\n\n";

    // 3. Create password resets table
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
    
    $pdo->exec($passwordResetTable);
    echo "   ✅ Password resets table created/verified\n\n";

    // 4. Create login attempts table
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
    
    $pdo->exec($loginAttemptsTable);
    echo "   ✅ Login attempts table created/verified\n\n";

    // 5. Check and create admin account
    echo "5️⃣  Checking admin account...\n";
    $stmt = $pdo->prepare("SELECT admin_id FROM admins WHERE username = ?");
    $stmt->execute(['admin']);
    $adminExists = $stmt->fetch();

    if (!$adminExists) {
        echo "   ⚠️  Admin account not found, creating default admin...\n";
        // Password: admin123
        $hashedPassword = '$2y$10$YIQfkVDr2F0xIGmxvZ0CeOYf0EgLGZfZkQx8PZlKc8JZT4WzCpT2K';
        
        $stmt = $pdo->prepare("INSERT INTO admins (username, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['admin', 'admin@vsitoa.com', $hashedPassword, 'superadmin', 'active']);
        
        echo "   ✅ Default admin account created\n";
        echo "      Username: admin\n";
        echo "      Password: admin123\n";
        echo "      ⚠️  IMPORTANT: Change password after first login!\n\n";
    } else {
        echo "   ✅ Admin account already exists\n\n";
    }

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ All migrations completed successfully!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    echo "🔑 Admin Login Credentials:\n";
    echo "   URL: http://localhost:8000/vsitoa/admin/login\n";
    echo "   Password: admin123\n\n";

    echo "🔗 Quick Links:\n";
    echo "   Home: http://localhost:8000/vsitoa/\n";
    echo "   Admin: http://localhost:8000/vsitoa/admin\n";
    echo "   Login: http://localhost:8000/vsitoa/login\n";
    echo "   User Register: http://localhost:8000/vsitoa/register\n\n";

} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
