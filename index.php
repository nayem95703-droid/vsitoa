<?php
declare(strict_types=1);

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
    $loggerPath = realpath(__DIR__ . '/core/Logger.php');
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

define('ROOT_PATH', __DIR__);

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
$basePath = \Core\Config::get('app.base_path');
$router->setBasePath($basePath ?? '');

require ROOT_PATH . '/routes/web.php';
require ROOT_PATH . '/routes/api.php';

$request = new \Core\Request();
$response = new \Core\Response();

$router->dispatch($request, $response);
