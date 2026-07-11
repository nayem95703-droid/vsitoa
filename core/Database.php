<?php

namespace Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;
    private static array $config = [];
    private static bool $connected = false;

    /**
     * Initialize database connection
     */
    public static function initialize(): void
    {
        self::$config = Config::get('database');
        
        if (!self::$config) {
            self::$config = [];
        }
    }

    /**
     * Get database connection instance
     */
    public static function getInstance(): ?PDO
    {
        if (self::$instance === null) {
            self::connect();
        }
        return self::$instance;
    }

    /**
     * Create database connection
     */
    private static function connect(): void
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                self::$config['host'] ?? 'localhost',
                self::$config['port'] ?? 3306,
                self::$config['name'] ?? 'vsitoa',
                self::$config['charset'] ?? 'utf8mb4'
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            if (class_exists('Pdo\Mysql')) {
                $options[\Pdo\Mysql::ATTR_INIT_COMMAND] = "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci";
            } elseif (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
                $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci";
            }

            self::$instance = new PDO(
                $dsn,
                self::$config['user'] ?? 'root',
                self::$config['password'] ?? '',
                $options
            );

            self::$connected = true;

        } catch (PDOException $e) {
            self::$connected = false;
            if (class_exists(\Core\Logger::class)) {
                \Core\Logger::error('Database connection failed: ' . $e->getMessage());
            }
        }
    }

    /**
     * Execute query and return statement
     */
    public static function query(string $sql, array $params = []): ?\PDOStatement
    {
        $pdo = self::getInstance();
        if (!$pdo) {
            Logger::error('Database query failed: no connection');
            throw new \RuntimeException('Database connection failed. Check database configuration.');
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Get single row
     */
    public static function fetch(string $sql, array $params = []): ?array
    {
        $stmt = self::query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Get multiple rows
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Get single column value
     */
    public static function fetchColumn(string $sql, array $params = [], int $column = 0): mixed
    {
        $stmt = self::query($sql, $params);
        return $stmt->fetchColumn($column);
    }

    /**
     * Insert record and return last insert ID
     */
    public static function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $stmt = self::query($sql, array_values($data));
        if (!$stmt) {
            throw new \RuntimeException("Database insert failed for table '$table'. Check database connection and logs.");
        }
        $pdo = self::getInstance();
        return $pdo ? (int) $pdo->lastInsertId() : 0;
    }

    /**
     * Update records
     */
    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $setParts = [];
        $params = [];

        foreach ($data as $column => $value) {
            $setParts[] = "$column = ?";
            $params[] = $value;
        }

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $table,
            implode(', ', $setParts),
            $where
        );

        $params = array_merge($params, $whereParams);
        $stmt = self::query($sql, $params);
        return $stmt ? $stmt->rowCount() : 0;
    }

    /**
     * Delete records
     */
    public static function delete(string $table, string $where, array $params = []): int
    {
        $sql = sprintf('DELETE FROM %s WHERE %s', $table, $where);
        $stmt = self::query($sql, $params);
        return $stmt ? $stmt->rowCount() : 0;
    }

    /**
     * Begin transaction
     */
    public static function beginTransaction(): void
    {
        $pdo = self::getInstance();
        if ($pdo) {
            $pdo->beginTransaction();
        }
    }

    /**
     * Commit transaction
     */
    public static function commit(): void
    {
        $pdo = self::getInstance();
        if ($pdo) {
            $pdo->commit();
        }
    }

    /**
     * Rollback transaction
     */
    public static function rollback(): void
    {
        $pdo = self::getInstance();
        if ($pdo) {
            $pdo->rollback();
        }
    }

    /**
     * Check if transaction is active
     */
    public static function inTransaction(): bool
    {
        $pdo = self::getInstance();
        return $pdo ? $pdo->inTransaction() : false;
    }

    /**
     * Get last insert ID
     */
    public static function lastInsertId(): string
    {
        $pdo = self::getInstance();
        return $pdo ? $pdo->lastInsertId() : '0';
    }

    /**
     * Check if table exists
     */
    public static function tableExists(string $table): bool
    {
        $dbName = (string) (self::$config['name'] ?? '');
        if ($dbName === '') {
            return false;
        }

        $sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = ? AND table_name = ? LIMIT 1";
        $result = self::fetch($sql, [$dbName, $table]);
        return $result !== null;
    }

    /**
     * Get table columns
     */
    public static function getTableColumns(string $table): array
    {
        $sql = "DESCRIBE $table";
        return self::fetchAll($sql);
    }

    /**
     * Check if a column exists in a table
     */
    public static function columnExists(string $table, string $column): bool
    {
        $dbName = (string) (self::$config['name'] ?? '');
        if ($dbName === '') {
            return false;
        }

        $sql = "SELECT 1 FROM information_schema.columns WHERE table_schema = ? AND table_name = ? AND column_name = ? LIMIT 1";
        $result = self::fetch($sql, [$dbName, $table, $column]);
        return $result !== null;
    }

    /**
     * Execute raw SQL
     */
    public static function exec(string $sql): int
    {
        $pdo = self::getInstance();
        return $pdo ? $pdo->exec($sql) : 0;
    }

    /**
     * Close connection
     */
    public static function close(): void
    {
        self::$instance = null;
        self::$connected = false;
    }
}
