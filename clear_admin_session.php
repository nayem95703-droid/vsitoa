<?php
if (php_sapi_name() !== 'cli') {
    http_response_code(404);
    exit;
}

define('ROOT_PATH', __DIR__);
require_once 'vendor/autoload.php';
require_once 'core/Config.php';
require_once 'core/Database.php';
require_once 'core/Auth.php';

session_start();

echo "Clearing admin session...\n";

unset($_SESSION['admin_jwt_token']);
unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);

setcookie('admin_jwt_token', '', time() - 3600, '/', '', false, true);

echo "Admin session cleared.\n";

\Core\Auth::initialize();

if (\Core\Auth::adminCheck()) {
    echo "ERROR: Admin still detected after clear!\n";
} else {
    echo "SUCCESS: No admin session detected.\n";
}

echo "Done.\n";
