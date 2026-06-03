<?php

namespace Core;

class Config
{
    private static array $config = [];
    private static bool $loaded = false;

    /**
     * Load configuration from environment and config files
     */
    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        // Load environment variables
        $envFile = ROOT_PATH . '/.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '#') === 0) {
                    continue;
                }
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }

        // Set default config values
        $envBasePath = isset($_ENV['APP_BASE_PATH']) ? trim((string) $_ENV['APP_BASE_PATH']) : '';
        if ($envBasePath !== '') {
            $envBasePath = '/' . ltrim($envBasePath, '/');
            $envBasePath = rtrim($envBasePath, '/');
        }

        self::$config = [
            'app' => [
                'name' => $_ENV['APP_NAME'] ?? 'VSItoA',
                'url' => $_ENV['APP_URL'] ?? 'http://localhost',
                'base_path' => $envBasePath !== '' ? $envBasePath : rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/'),
                'env' => $_ENV['APP_ENV'] ?? 'production',
                'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'timezone' => 'UTC',
                'locale' => 'en_US',
                'version' => '1.0.0'
            ],
            'database' => [
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'port' => $_ENV['DB_PORT'] ?? 3306,
                'name' => $_ENV['DB_DATABASE'] ?? ($_ENV['DB_NAME'] ?? 'vsitoa'),
                'user' => $_ENV['DB_USERNAME'] ?? ($_ENV['DB_USER'] ?? 'root'),
                'password' => $_ENV['DB_PASSWORD'] ?? ($_ENV['DB_PASS'] ?? ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => ''
            ],
            'security' => [
                'jwt_secret' => $_ENV['JWT_SECRET'] ?? 'default-secret-change-this',
                'encryption_key' => $_ENV['ENCRYPTION_KEY'] ?? 'default-encryption-key-32-chars',
                'allowed_origins' => explode(',', $_ENV['ALLOWED_ORIGINS'] ?? 'http://localhost'),
                'max_login_attempts' => $_ENV['MAX_LOGIN_ATTEMPTS'] ?? 5,
                'login_lockout_time' => $_ENV['LOGIN_LOCKOUT_TIME'] ?? 900,
                'session_lifetime' => 86400, // 24 hours
                'password_min_length' => 8,
                'require_email_verification' => true
            ],
            'mail' => [
                'host' => $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com',
                'port' => $_ENV['MAIL_PORT'] ?? 587,
                'username' => $_ENV['MAIL_USERNAME'] ?? '',
                'password' => $_ENV['MAIL_PASSWORD'] ?? '',
                'from_email' => $_ENV['MAIL_FROM_EMAIL'] ?? 'noreply@vsitoa.com',
                'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'VSItoA',
                'encryption' => 'tls'
            ],
            'crypto' => [
                'btc_wallet' => $_ENV['BTC_WALLET_ADDRESS'] ?? '',
                'trx_wallet' => $_ENV['TRX_WALLET_ADDRESS'] ?? '',
                'eth_wallet' => $_ENV['ETH_WALLET_ADDRESS'] ?? '',
                'usdt_wallet' => $_ENV['USDT_WALLET_ADDRESS'] ?? ''
            ],
            'rates' => [
                'cost_per_view' => floatval($_ENV['COST_PER_VIEW'] ?? 0.001),
                'minimum_withdrawal' => floatval($_ENV['MINIMUM_WITHDRAWAL'] ?? 0.01),
                'referral_commission' => floatval($_ENV['REFERRAL_COMMISSION'] ?? 10),
                'platform_fee' => floatval($_ENV['PLATFORM_FEE'] ?? 20)
            ],
            'cache' => [
                'redis_host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
                'redis_port' => $_ENV['REDIS_PORT'] ?? 6379,
                'redis_password' => $_ENV['REDIS_PASSWORD'] ?? null,
                'default_ttl' => 3600
            ],
            'upload' => [
                'max_file_size' => intval($_ENV['MAX_FILE_SIZE'] ?? 5242880),
                'upload_path' => $_ENV['UPLOAD_PATH'] ?? 'uploads/',
                'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']
            ],
            'queue' => [
                'connection' => $_ENV['QUEUE_CONNECTION'] ?? 'database',
                'default_queue' => 'default',
                'failed_jobs_table' => 'failed_jobs'
            ]
        ];

        self::$loaded = true;
    }

    /**
     * Get configuration value
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::$loaded) {
            self::load();
        }

        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Set configuration value
     */
    public static function set(string $key, mixed $value): void
    {
        if (!self::$loaded) {
            self::load();
        }

        $keys = explode('.', $key);
        $config = &self::$config;

        foreach ($keys as $k) {
            if (!isset($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config = $value;
    }

    /**
     * Check if configuration key exists
     */
    public static function has(string $key): bool
    {
        return self::get($key) !== null;
    }

    /**
     * Get all configuration
     */
    public static function all(): array
    {
        if (!self::$loaded) {
            self::load();
        }

        return self::$config;
    }
}
