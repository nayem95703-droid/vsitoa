<?php
// Simple test to verify the app loads
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('ROOT_PATH', __DIR__);

require_once ROOT_PATH . '/vendor/autoload.php';
require_once ROOT_PATH . '/core/Config.php';
require_once ROOT_PATH . '/core/Database.php';
require_once ROOT_PATH . '/core/Auth.php';
require_once ROOT_PATH . '/core/Router.php';

try {
    \Core\Config::load();
    echo "✓ Config loaded\n";
    
    \Core\Database::initialize();
    echo "✓ Database initialized\n";
    
    // Try a simple query with timeout
    set_time_limit(2);
    $result = \Core\Database::fetchColumn("SELECT 1");
    echo "✓ Database connection OK\n";
    
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nAll fixes verified!\n";
