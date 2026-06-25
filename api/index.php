<?php
declare(strict_types=1);

// ১. ভেরসেলের ৫০০ এরর স্ক্রিনে দেখার জন্য ফুল এরর রিপোর্টিং অন
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// ২. ফিক্স: .env ফাইলের লোকাল হোস্টের '/vsitoa' রাউটিং জ্যাম কাটানোর জন্য বেস পাথ খালি করা
$_ENV['APP_BASE_PATH'] = '';

try {
    session_start();
} catch (\Throwable $e) {
    $_SESSION = [];
}
if (!isset($_SESSION) || !is_array($_SESSION)) {
    $_SESSION = [];
}

// Debug endpoint — identify exact runtime files
if (($_SERVER['REQUEST_URI'] ?? '') === '/debug-runtime') {
    $loggerPath = realpath(__DIR__ . '/../core/Logger.php'); // মেইন রুটে খোঁজার জন্য ফিক্স
    header('Content-Type: application/json');
    echo json_encode([
        'entry_point' => __FILE__,
        'cwd' => getcwd(),
        'root' => __DIR__,
        'logger_realpath' => $loggerPath ?: 'NOT FOUND',
        'logger_exists' => $loggerPath ? file_exists($loggerPath) : false,
        'logger_line23' => $loggerPath ? rtrim(file($loggerPath)[22] ?? 'N/A') : 'N/A',
        'logger_md5' => $loggerPath ? md5_file($loggerPath) : 'N/A',
        'logger_mtime' => $loggerPath ? filemtime($loggerPath) : 'N/A',
        'server_uri' => $_SERVER['REQUEST_URI'] ?? 'N/A',
        'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'N/A',
        'php_self' => $_SERVER['PHP_SELF'] ?? 'N/A',
    ]);
    exit;
}

// ৩. ফিক্স: ROOT_PATH-কে api/ ফোল্ডার থেকে এক ধাপ বের করে মেইন প্রজেক্ট রুটে সেট করা
define('ROOT_PATH', dirname(__DIR__));

require_once ROOT_PATH . '/vendor/autoload.php';
require_once ROOT_PATH . '/core/Config.php';
require_once ROOT_PATH . '/core/Database.php';
require_once ROOT_PATH . '/core/Auth.php';
require_once ROOT_PATH . '/core/Request.php';
require_once ROOT_PATH . '/core/Response.php';
require_once ROOT_PATH . '/core/Router.php';

if (!class_exists('Config')) {
    class_alias(\Core\Config::class, 'Config');
}

\Core\Config::load();
\Core\Database::initialize();
\Core\Auth::initialize();

$router = new \Core\Router();
$basePath = $_ENV['APP_BASE_PATH'] ?: (\Core\Config::get('app.base_path') ?? '');
$router->setBasePath($basePath);

require ROOT_PATH . '/routes/web.php';
require ROOT_PATH . '/routes/api.php';

$request = new \Core\Request();
$response = new \Core\Response();

// ৪. ফিক্স: রাউটার ডিসপ্যাচকে Try-Catch ব্লকে নেওয়া যেন ক্র্যাশ করলে আসল এরর মেসেজ স্ক্রিনে দেখা যায়
try {
    $router->dispatch($request, $response);
} catch (\Throwable $e) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "--- PHP CRITICAL EXCEPTION CATCHED ---\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString();
    exit;
}