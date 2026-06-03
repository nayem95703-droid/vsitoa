<?php
if (php_sapi_name() !== 'cli') {
    http_response_code(404);
    exit;
}

define('ROOT_PATH', __DIR__);
require_once 'vendor/autoload.php';
require_once 'core/Config.php';
require_once 'core/Database.php';

Core\Database::initialize();

$newPassword = $argv[1] ?? null;
if (is_string($newPassword) && $newPassword !== '') {
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    Core\Database::update('admins', ['password' => $hash], 'username = ?', ['admin']);
}

$admin = Core\Database::fetch('SELECT admin_id, username, password FROM admins WHERE username = ?', ['admin']);

if ($admin) {
    echo "Found admin: " . $admin['username'] . "\n";
    echo "Password hash: " . $admin['password'] . "\n";
    if (is_string($newPassword) && $newPassword !== '') {
        echo "Verify provided password: " . (password_verify($newPassword, $admin['password']) ? 'YES' : 'NO') . "\n";
    }
} else {
    echo "No admin user found in admins table.\n";
}
