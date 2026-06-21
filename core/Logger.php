<?php

namespace Core;

class Logger
{
    private static ?string $logFile = null;
    private static array $levels = [
        'DEBUG' => 0,
        'INFO' => 1,
        'WARNING' => 2,
        'ERROR' => 3,
        'CRITICAL' => 4
    ];

    public static function initialize(): void
    {
        $logDir = sys_get_temp_dir() . '/vsitoa_logs';

        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        if (!is_dir($logDir)) {
            self::$logFile = '';
            return;
        }

        self::$logFile = $logDir . '/app_' . date('Y-m-d') . '.log';
    }

    public static function debug(string $message, array $context = []): void
    {
        self::log('DEBUG', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::log('CRITICAL', $message, $context);
    }

    public static function log(string $level, string $message, array $context = []): void
    {
        if (!isset(self::$levels[$level])) {
            $level = 'INFO';
        }

        $minLevel = Config::get('app.debug') ? 'DEBUG' : 'INFO';
        if (self::$levels[$level] < self::$levels[$minLevel]) {
            return;
        }

        if (self::$logFile === null) {
            self::initialize();
        }

        if (empty(self::$logFile)) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $request = new Request();
        $userId = Auth::check() ? Auth::id() : null;
        $adminId = Auth::adminCheck() ? Auth::adminId() : null;

        $logEntry = [
            'timestamp' => $timestamp,
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'user_id' => $userId,
            'admin_id' => $adminId,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->getMethod()
        ];

        $formattedEntry = self::formatLogEntry($logEntry);

        try {
            file_put_contents(self::$logFile, $formattedEntry, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            return;
        }

        if ($level === 'CRITICAL') {
            $criticalLogFile = dirname(self::$logFile) . '/critical_' . date('Y-m-d') . '.log';
            try {
                file_put_contents($criticalLogFile, $formattedEntry, FILE_APPEND | LOCK_EX);
            } catch (\Throwable $e) {
            }
        }

        if (Config::get('log.database', false)) {
            self::logToDatabase($logEntry);
        }
    }

    private static function formatLogEntry(array $entry): string
    {
        $json = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $json . PHP_EOL;
    }

    private static function logToDatabase(array $entry): void
    {
        try {
            Database::insert('system_logs', [
                'level' => $entry['level'],
                'message' => $entry['message'],
                'context' => json_encode($entry['context']),
                'user_id' => $entry['user_id'],
                'admin_id' => $entry['admin_id'],
                'ip_address' => $entry['ip'],
                'user_agent' => $entry['user_agent'],
                'url' => $entry['url'],
                'method' => $entry['method'],
                'created_at' => $entry['timestamp']
            ]);
        } catch (\Exception $e) {
        }
    }

    public static function logUserActivity(string $action, array $details = []): void
    {
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();

        self::info("User activity: {$action}", array_merge([
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'action' => $action
        ], $details));

        try {
            Database::insert('user_activities', [
                'user_id' => $user['user_id'],
                'action' => $action,
                'details' => json_encode($details),
                'ip_address' => (new Request())->ip(),
                'user_agent' => (new Request())->userAgent(),
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
        }
    }

    public static function logAdminActivity(string $action, array $details = []): void
    {
        if (!Auth::adminCheck()) {
            return;
        }

        $admin = Auth::admin();

        self::info("Admin activity: {$action}", array_merge([
            'admin_id' => $admin['admin_id'],
            'username' => $admin['username'],
            'role' => $admin['role'],
            'action' => $action
        ], $details));

        try {
            Database::insert('admin_logs', [
                'admin_id' => $admin['admin_id'],
                'action' => $action,
                'details' => json_encode($details),
                'ip_address' => (new Request())->ip(),
                'user_agent' => (new Request())->userAgent(),
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
        }
    }

    public static function logSecurity(string $event, array $details = []): void
    {
        $request = new Request();

        self::warning("Security event: {$event}", array_merge([
            'event' => $event,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'user_id' => Auth::check() ? Auth::id() : null
        ], $details));

        try {
            Database::insert('security_logs', [
                'event' => $event,
                'details' => json_encode($details),
                'user_id' => Auth::check() ? Auth::id() : null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
        }
    }

    public static function logApiRequest(string $endpoint, string $method, int $statusCode, float $responseTime, array $details = []): void
    {
        $request = new Request();

        self::info("API Request: {$method} {$endpoint}", [
            'endpoint' => $endpoint,
            'method' => $method,
            'status_code' => $statusCode,
            'response_time' => $responseTime,
            'ip' => $request->ip(),
            'user_id' => Auth::check() ? Auth::id() : null,
            'details' => $details
        ]);

        try {
            Database::insert('api_logs', [
                'endpoint' => $endpoint,
                'method' => $method,
                'status_code' => $statusCode,
                'response_time' => $responseTime,
                'user_id' => Auth::check() ? Auth::id() : null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'details' => json_encode($details),
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
        }
    }

    public static function logPerformance(string $operation, float $duration, array $details = []): void
    {
        self::debug("Performance: {$operation}", array_merge([
            'operation' => $operation,
            'duration' => $duration,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ], $details));
    }

    public static function getRecentLogs(int $limit = 100, ?string $level = null): array
    {
        $logDir = sys_get_temp_dir() . '/vsitoa_logs';
        $logFile = $logDir . '/app_' . date('Y-m-d') . '.log';

        if (!file_exists($logFile)) {
            return [];
        }

        $logs = [];
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lines = array_reverse(array_slice($lines, -$limit));

        foreach ($lines as $line) {
            $log = json_decode($line, true);
            if ($log && (!$level || $log['level'] === $level)) {
                $logs[] = $log;
            }
        }

        return $logs;
    }

    public static function cleanOldLogs(int $days = 30): void
    {
        $logDir = sys_get_temp_dir() . '/vsitoa_logs';
        if (!is_dir($logDir)) {
            return;
        }
        $cutoffTime = time() - ($days * 24 * 60 * 60);

        foreach (glob($logDir . '/*.log') as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
            }
        }
    }

    public static function getStatistics(?string $date = null): array
    {
        $date = $date ?? date('Y-m-d');
        $logDir = sys_get_temp_dir() . '/vsitoa_logs';
        $logFile = $logDir . "/app_{$date}.log";

        if (!file_exists($logFile)) {
            return [];
        }

        $stats = [
            'total' => 0,
            'by_level' => [],
            'by_hour' => [],
            'top_ips' => [],
            'top_users' => []
        ];

        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $log = json_decode($line, true);
            if (!$log) continue;

            $stats['total']++;

            $level = $log['level'];
            $stats['by_level'][$level] = ($stats['by_level'][$level] ?? 0) + 1;

            $hour = date('H', strtotime($log['timestamp']));
            $stats['by_hour'][$hour] = ($stats['by_hour'][$hour] ?? 0) + 1;

            $ip = $log['ip'] ?? 'unknown';
            $stats['top_ips'][$ip] = ($stats['top_ips'][$ip] ?? 0) + 1;

            if (!empty($log['user_id'])) {
                $userId = $log['user_id'];
                $stats['top_users'][$userId] = ($stats['top_users'][$userId] ?? 0) + 1;
            }
        }

        arsort($stats['top_ips']);
        arsort($stats['top_users']);
        ksort($stats['by_hour']);

        $stats['top_ips'] = array_slice($stats['top_ips'], 0, 10, true);
        $stats['top_users'] = array_slice($stats['top_users'], 0, 10, true);

        return $stats;
    }

    public static function exportLogs(string $startDate, string $endDate, string $format = 'json'): string
    {
        $logs = [];
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($start, $interval, $end);

        $logDir = sys_get_temp_dir() . '/vsitoa_logs';

        foreach ($period as $date) {
            $logFile = $logDir . '/app_' . $date->format('Y-m-d') . '.log';
            if (file_exists($logFile)) {
                $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    $log = json_decode($line, true);
                    if ($log) {
                        $logs[] = $log;
                    }
                }
            }
        }

        if (empty(self::$logFile)) {
            return '';
        }

        $exportFile = $logDir . '/export_' . date('Y-m-d_H-i-s') . '.' . $format;

        try {
            if ($format === 'json') {
                file_put_contents($exportFile, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } elseif ($format === 'csv') {
                $fp = fopen($exportFile, 'w');
                if (!empty($logs)) {
                    fputcsv($fp, array_keys($logs[0]));
                    foreach ($logs as $log) {
                        fputcsv($fp, $log);
                    }
                }
                fclose($fp);
            }
        } catch (\Throwable $e) {
            return '';
        }

        return $exportFile;
    }
}
