<?php

namespace Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;
    private static array $config = [];

    /**
     * Initialize database connection
     */
    public static function initialize(): void
    {
        self::$config = Config::get('database');
        
        if (!self::$config) {
            throw new \Exception('Database configuration not found');
        }
    }

    /**
     * Get database connection instance
     */
    public static function getInstance(): PDO
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
                self::$config['host'],
                self::$config['port'],
                self::$config['name'],
                self::$config['charset']
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true
            ];
            
            // Set character set using the appropriate constant for this PHP version
            if (class_exists('Pdo\Mysql')) {
                // PHP 8.5+
                $options[\Pdo\Mysql::ATTR_INIT_COMMAND] = "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci";
            } elseif (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
                // PHP 8.0-8.4
                $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci";
            }

            self::$instance = new PDO(
                $dsn,
                self::$config['user'],
                self::$config['password'],
                $options
            );

        } catch (PDOException $e) {
            Logger::error("Database connection failed: " . $e->getMessage());
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Execute query and return statement
     */
    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::getInstance()->prepare($sql);
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

        self::query($sql, array_values($data));
        return (int) self::getInstance()->lastInsertId();
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
        return $stmt->rowCount();
    }

    /**
     * Delete records
     */
    public static function delete(string $table, string $where, array $params = []): int
    {
        $sql = sprintf('DELETE FROM %s WHERE %s', $table, $where);
        $stmt = self::query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Begin transaction
     */
    public static function beginTransaction(): void
    {
        self::getInstance()->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public static function commit(): void
    {
        self::getInstance()->commit();
    }

    /**
     * Rollback transaction
     */
    public static function rollback(): void
    {
        self::getInstance()->rollback();
    }

    /**
     * Check if transaction is active
     */
    public static function inTransaction(): bool
    {
        return self::getInstance()->inTransaction();
    }

    /**
     * Get last insert ID
     */
    public static function lastInsertId(): string
    {
        return self::getInstance()->lastInsertId();
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
        return self::getInstance()->exec($sql);
    }

    /**
     * Close connection
     */
    public static function close(): void
    {
        self::$instance = null;
    }
}
