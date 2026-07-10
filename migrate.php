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

    // 0. Add missing columns to users table
    echo "0️⃣  Ensuring users table has all required columns...\n";
    $userColumnsToAdd = [
        'wallet_address' => "ALTER TABLE users ADD COLUMN wallet_address VARCHAR(255) DEFAULT NULL",
        'referral_code' => "ALTER TABLE users ADD COLUMN referral_code VARCHAR(20) DEFAULT NULL",
        'referred_by' => "ALTER TABLE users ADD COLUMN referred_by INT DEFAULT NULL",
        'status' => "ALTER TABLE users ADD COLUMN status ENUM('active', 'unverified', 'suspended') DEFAULT 'active'",
        'email_verified' => "ALTER TABLE users ADD COLUMN email_verified TINYINT(1) DEFAULT 0",
        'earning_balance' => "ALTER TABLE users ADD COLUMN earning_balance DECIMAL(18,8) DEFAULT 0",
        'advisor_balance' => "ALTER TABLE users ADD COLUMN advisor_balance DECIMAL(18,8) DEFAULT 0",
        'total_withdrawn' => "ALTER TABLE users ADD COLUMN total_withdrawn DECIMAL(18,8) DEFAULT 0",
        'total_earned' => "ALTER TABLE users ADD COLUMN total_earned DECIMAL(18,8) DEFAULT 0",
    ];
    foreach ($userColumnsToAdd as $col => $sql) {
        $exists = \Core\Database::fetch("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = ?", [$col]);
        if (!$exists || $exists['cnt'] == 0) {
            \Core\Database::query($sql);
            echo "   ✅ Added column: $col\n";
        } else {
            echo "   ⏭️  Column $col already exists\n";
        }
    }
    echo "\n";

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

    // 5. Create ads table
    echo "5️⃣  Creating ads table...\n";
    \Core\Database::query("CREATE TABLE IF NOT EXISTS ads (
        ad_id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        ad_title VARCHAR(255) NOT NULL,
        ad_category VARCHAR(50) DEFAULT 'website',
        ad_type VARCHAR(20) NOT NULL,
        target_url VARCHAR(500) NOT NULL,
        description TEXT,
        preview_image VARCHAR(255),
        view_time INT DEFAULT 30,
        auto_redirect TINYINT(1) DEFAULT 0,
        timer_type VARCHAR(20) DEFAULT 'countdown',
        cost_per_view DECIMAL(18,8) NOT NULL,
        total_views INT NOT NULL,
        remaining_views INT NOT NULL,
        spent_amount DECIMAL(18,8) DEFAULT 0,
        total_budget DECIMAL(18,8) NOT NULL,
        platform_fee_percent DECIMAL(5,2) DEFAULT 20,
        target_countries TEXT,
        device_type VARCHAR(20) DEFAULT 'all',
        browser VARCHAR(20) DEFAULT 'all',
        user_level VARCHAR(20) DEFAULT 'all',
        status ENUM('pending','active','paused','completed','archived') DEFAULT 'pending',
        started_at TIMESTAMP NULL,
        completed_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        INDEX idx_user_id (user_id),
        INDEX idx_status (status),
        INDEX idx_ad_type (ad_type)
    )");
    echo "   ✅ Ads table created/verified\n\n";

    // 6. Create ad_views table
    echo "6️⃣  Creating ad_views table...\n";
    \Core\Database::query("CREATE TABLE IF NOT EXISTS ad_views (
        view_id INT PRIMARY KEY AUTO_INCREMENT,
        ad_id INT NOT NULL,
        viewer_user_id INT NOT NULL,
        ip_address VARCHAR(45),
        user_agent VARCHAR(255),
        view_time INT NOT NULL,
        actual_view_time INT DEFAULT 0,
        is_valid TINYINT(1) DEFAULT 0,
        earned_amount DECIMAL(18,8) DEFAULT 0,
        fraud_score INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ad_id) REFERENCES ads(ad_id),
        FOREIGN KEY (viewer_user_id) REFERENCES users(id),
        INDEX idx_ad_id (ad_id),
        INDEX idx_viewer (viewer_user_id),
        INDEX idx_valid (is_valid)
    )");
    echo "   ✅ Ad views table created/verified\n\n";

    // 7. Create deposits table
    echo "7️⃣  Creating deposits table...\n";
    \Core\Database::query("CREATE TABLE IF NOT EXISTS deposits (
        deposit_id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        currency VARCHAR(10) NOT NULL,
        amount DECIMAL(18,8) NOT NULL,
        wallet_address VARCHAR(255),
        txid VARCHAR(255),
        status ENUM('pending','approved','rejected') DEFAULT 'pending',
        admin_notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        INDEX idx_user_id (user_id),
        INDEX idx_status (status)
    )");
    echo "   ✅ Deposits table created/verified\n\n";

    // 8. Create withdrawals table
    echo "8️⃣  Creating withdrawals table...\n";
    \Core\Database::query("CREATE TABLE IF NOT EXISTS withdrawals (
        withdrawal_id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        currency VARCHAR(10) NOT NULL,
        amount DECIMAL(18,8) NOT NULL,
        wallet_address VARCHAR(255) NOT NULL,
        status ENUM('pending','processing','paid','rejected') DEFAULT 'pending',
        txid VARCHAR(255),
        admin_notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        processed_at TIMESTAMP NULL,
        FOREIGN KEY (user_id) REFERENCES users(id),
        INDEX idx_user_id (user_id),
        INDEX idx_status (status)
    )");
    echo "   ✅ Withdrawals table created/verified\n\n";

    // 9. Create wallet_transactions table
    echo "9️⃣  Creating wallet_transactions table...\n";
    \Core\Database::query("CREATE TABLE IF NOT EXISTS wallet_transactions (
        transaction_id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        amount DECIMAL(18,8) NOT NULL,
        balance_before DECIMAL(18,8),
        balance_after DECIMAL(18,8),
        description TEXT,
        reference_id INT,
        reference_type VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        INDEX idx_user_id (user_id),
        INDEX idx_type (type)
    )");
    echo "   ✅ Wallet transactions table created/verified\n\n";

    // 10. Create earnings table
    echo "🔟  Creating earnings table...\n";
    \Core\Database::query("CREATE TABLE IF NOT EXISTS earnings (
        earning_id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        source VARCHAR(50),
        source_id INT,
        amount DECIMAL(18,8) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        INDEX idx_user_id (user_id)
    )");
    echo "   ✅ Earnings table created/verified\n\n";

    // 11. Create referrals table
    echo "1️⃣1️⃣  Creating referrals table...\n";
    \Core\Database::query("CREATE TABLE IF NOT EXISTS referrals (
        referral_id INT PRIMARY KEY AUTO_INCREMENT,
        referrer_id INT NOT NULL,
        referred_user_id INT NOT NULL,
        commission_rate DECIMAL(5,2) DEFAULT 10,
        level INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (referrer_id) REFERENCES users(id),
        FOREIGN KEY (referred_user_id) REFERENCES users(id),
        INDEX idx_referrer (referrer_id)
    )");
    echo "   ✅ Referrals table created/verified\n\n";

    // 12. Create referral_earnings table
    echo "1️⃣2️⃣  Creating referral_earnings table...\n";
    \Core\Database::query("CREATE TABLE IF NOT EXISTS referral_earnings (
        earning_id INT PRIMARY KEY AUTO_INCREMENT,
        referrer_id INT NOT NULL,
        source_user_id INT NOT NULL,
        amount DECIMAL(18,8) NOT NULL,
        commission_rate DECIMAL(5,2),
        source_type VARCHAR(50),
        source_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (referrer_id) REFERENCES users(id),
        FOREIGN KEY (source_user_id) REFERENCES users(id),
        INDEX idx_referrer (referrer_id)
    )");
    echo "   ✅ Referral earnings table created/verified\n\n";

    // 13. Create referral_clicks table
    echo "1️⃣3️⃣  Creating referral_clicks table...\n";
    \Core\Database::query("CREATE TABLE IF NOT EXISTS referral_clicks (
        click_id INT PRIMARY KEY AUTO_INCREMENT,
        referrer_id INT NOT NULL,
        referral_code VARCHAR(20),
        ip_address VARCHAR(45),
        user_agent VARCHAR(255),
        landing_page VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (referrer_id) REFERENCES users(id),
        INDEX idx_referrer (referrer_id),
        INDEX idx_code (referral_code)
    )");
    echo "   ✅ Referral clicks table created/verified\n\n";

    // 14. Create user_login_log table
    echo "1️⃣4️⃣  Creating user_login_log table...\n";
    \Core\Database::query("CREATE TABLE IF NOT EXISTS user_login_log (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT,
        ip_address VARCHAR(45),
        user_agent VARCHAR(255),
        success TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id)
    )");
    echo "   ✅ User login log table created/verified\n\n";

    // 15. Create support_tickets table
    echo "1️⃣5️⃣  Creating support_tickets table...\n";
    \Core\Database::query("CREATE TABLE IF NOT EXISTS support_tickets (
        ticket_id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        status ENUM('open','in_progress','resolved','closed') DEFAULT 'open',
        priority ENUM('low','medium','high') DEFAULT 'medium',
        category VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        INDEX idx_user_id (user_id),
        INDEX idx_status (status)
    )");
    echo "   ✅ Support tickets table created/verified\n\n";

    // 16. Create support_responses table
    echo "1️⃣6️⃣  Creating support_responses table...\n";
    \Core\Database::query("CREATE TABLE IF NOT EXISTS support_responses (
        response_id INT PRIMARY KEY AUTO_INCREMENT,
        ticket_id INT NOT NULL,
        user_id INT NOT NULL,
        message TEXT NOT NULL,
        is_admin TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ticket_id) REFERENCES support_tickets(ticket_id),
        FOREIGN KEY (user_id) REFERENCES users(id),
        INDEX idx_ticket (ticket_id)
    )");
    echo "   ✅ Support responses table created/verified\n\n";

    // 17. Create user_stickers table
    echo "1️⃣7️⃣  Creating user_stickers table...\n";
    \Core\Database::query("CREATE TABLE IF NOT EXISTS user_stickers (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        sticker_type VARCHAR(100) NOT NULL,
        granted_by INT,
        granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        revoked_at TIMESTAMP NULL,
        FOREIGN KEY (user_id) REFERENCES users(id),
        INDEX idx_user_id (user_id)
    )");
    echo "   ✅ User stickers table created/verified\n\n";

    // 18. Create user_features table
    echo "1️⃣8️⃣  Creating user_features table...\n";
    \Core\Database::query("CREATE TABLE IF NOT EXISTS user_features (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        feature_name VARCHAR(100) NOT NULL,
        enabled TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        INDEX idx_user_id (user_id)
    )");
    echo "   ✅ User features table created/verified\n\n";

    // 19. Create verification_requests table
    echo "1️⃣9️⃣  Creating verification_requests table...\n";
    \Core\Database::query("CREATE TABLE IF NOT EXISTS verification_requests (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        document_type VARCHAR(50),
        document_path VARCHAR(255),
        status ENUM('pending','approved','rejected') DEFAULT 'pending',
        admin_notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        reviewed_at TIMESTAMP NULL,
        FOREIGN KEY (user_id) REFERENCES users(id),
        INDEX idx_user_id (user_id),
        INDEX idx_status (status)
    )");
    echo "   ✅ Verification requests table created/verified\n\n";

    // 20. Check if admin exists
    echo "2️⃣0️⃣  Checking admin account...\n";
    $adminExists = \Core\Database::fetch(
        "SELECT id FROM admins WHERE username = ?",
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
