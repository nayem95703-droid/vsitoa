<?php
// v4 — force fresh Vercel build cache
// রিকোয়েস্টের ইউআরএল থেকে সাব-ফোল্ডার পরিষ্কার করা
$request_uri = $_SERVER['REQUEST_URI'];
$request_uri = str_replace('/vsitoa', '', $request_uri);
$_SERVER['REQUEST_URI'] = $request_uri;

// Debug endpoint — identifies the exact file being executed
if ($request_uri === '/debug-runtime') {
    $loggerPath = realpath(__DIR__ . '/../core/Logger.php');
    echo json_encode([
        'file' => __FILE__,
        'cwd' => getcwd(),
        'root' => defined('ROOT_PATH') ? ROOT_PATH : 'undefined',
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

// আপনার মেইন ফোল্ডারের আসল index.php ফাইলকে কল করা
require __DIR__ . '/../index.php';