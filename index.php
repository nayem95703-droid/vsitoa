<?php
declare(strict_types=1);

session_start();

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
