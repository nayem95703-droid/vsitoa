<?php
define('ROOT_PATH', __DIR__);
require_once ROOT_PATH . '/core/Config.php';
require_once ROOT_PATH . '/core/Database.php';
Core\Config::load();
Core\Database::initialize();

// Fix admins table
echo "=== Fixing admins table ===\n";

$coreCols = ['email', 'status', 'last_login', 'permissions'];
foreach ($coreCols as $col) {
    $exists = Core\Database::fetch("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admins' AND COLUMN_NAME = ?", [$col]);
    if (!$exists || $exists['cnt'] == 0) {
        $defs = [
            'email' => "ALTER TABLE admins ADD COLUMN email VARCHAR(100) DEFAULT 'admin@vsitoa.com'",
            'status' => "ALTER TABLE admins ADD COLUMN status ENUM('active','inactive','suspended') DEFAULT 'active'",
            'last_login' => "ALTER TABLE admins ADD COLUMN last_login TIMESTAMP NULL",
            'permissions' => "ALTER TABLE admins ADD COLUMN permissions JSON DEFAULT NULL",
        ];
        Core\Database::query($defs[$col]);
        echo "  Added: $col\n";
    } else {
        echo "  Exists: $col\n";
    }
}

// Check if admins table has admin_id column (code uses admin_id)
$hasAdminId = Core\Database::fetch("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admins' AND COLUMN_NAME = 'admin_id'");
if (!$hasAdminId || $hasAdminId['cnt'] == 0) {
    echo "\n  admins table uses 'id' but code references 'admin_id'. Adding admin_id alias...\n";
    // Can't alias a column, so we add admin_id as a unique column and populate it
    Core\Database::query("ALTER TABLE admins ADD COLUMN admin_id INT UNIQUE");
    // Copy id to admin_id
    Core\Database::query("UPDATE admins SET admin_id = id WHERE admin_id IS NULL");
    echo "  Added admin_id column\n";
}

// Check admin record
$admin = Core\Database::fetch("SELECT * FROM admins WHERE username = 'admin'");
if ($admin) {
    echo "\nAdmin found! ID: " . $admin['id'] . ", admin_id: " . ($admin['admin_id'] ?? 'null') . ", status: " . $admin['status'] . "\n";
    
    // Verify password works
    $testPw = password_verify('admin123', $admin['password']);
    echo "Password 'admin123' valid: " . ($testPw ? 'YES' : 'NO') . "\n";
    
    if (!$testPw) {
        echo "Resetting password to admin123...\n";
        $newHash = password_hash('admin123', PASSWORD_DEFAULT);
        Core\Database::query("UPDATE admins SET password = ? WHERE username = 'admin'", [$newHash]);
        echo "Password reset done.\n";
    }
} else {
    echo "\nNo admin found. Creating...\n";
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    Core\Database::insert('admins', [
        'username' => 'admin',
        'password' => $hashedPassword,
        'role' => 'superadmin',
        'status' => 'active'
    ]);
    // Get the new admin and set admin_id
    $newAdmin = Core\Database::fetch("SELECT id FROM admins WHERE username = 'admin'");
    if ($newAdmin) {
        Core\Database::query("UPDATE admins SET admin_id = ? WHERE id = ?", [$newAdmin['id'], $newAdmin['id']]);
    }
    echo "Admin created! Password: admin123\n";
}

echo "\nDone!\n";
