<?php

namespace Ody\DB\ConnectionPool;

use PDO;
use Swoole\Database\PDOConfig;
use Swoole\Database\PDOPool;
use Swoole\Database\PDOProxy;

class ConnectionPoolAdapter
{
    private PDOPool $pool;
    private array $metrics = [
        'borrowed_total' => 0,
        'returned_total' => 0,
        'created_total' => 0,
        'errors_total' => 0,
    ];

    public function __construct(array $config, int $size = 64)
    {
        // Set performance-optimized PDO attributes
        $defaultOptions = [
            // Disable prepared statement emulation for better security and performance
            PDO::ATTR_EMULATE_PREPARES => false,

            // Use persistent connections for better performance
            // Note: Be careful with this in a pooled environment
            PDO::ATTR_PERSISTENT => true,

            // Set error mode to exceptions for better error handling
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

            // Set default fetch mode
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

            // Important: Auto-commit should be on for connection pooling
            PDO::ATTR_AUTOCOMMIT => true,

            // Server-side prepared statements (available in MySQL 5.1.17+)
            // Can improve query performance, especially for repeated queries
            // PDO::MYSQL_ATTR_DIRECT_QUERY => false,

            // Increase network buffer size for better performance with large datasets
            // PDO::MYSQL_ATTR_READ_DEFAULT_GROUP => 'max_allowed_packet=16M',

            // Optionally disable strict mode if your application requires it
            // PDO::MYSQL_ATTR_INIT_COMMAND => "SET SESSION sql_mode=''",

            // Set a longer timeout for long-running queries
            // PDO::ATTR_TIMEOUT => 3,
        ];

        // Merge default options with any provided options
        $options = array_merge($defaultOptions, $config['options'] ?? []);

        // Start with base configuration
        $pdoConfig = (new PDOConfig)
            ->withHost($config['host'])
            ->withPort($config['port'])
            ->withDbName($config['db_name'])
            ->withCharset($config['charset'] ?? 'utf8mb4')
            ->withUsername($config['username'])
            ->withPassword($config['password'])
            ->withOptions($options);

        // Create the pool with the configured settings
        $this->pool = new PDOPool($pdoConfig, $size);
        logger()->info("Connection pool initialized with size: $size");
    }

    /**
     * Get a connection from the pool
     *
     * @return PDOProxy
     */
    public function borrow(): PDOProxy
    {
        $this->metrics['borrowed_total']++;
        $pdo = $this->pool->get();

        // Additional runtime checks or warmup could be done here
        // For example: ensure connection is still valid or set session variables

        return $pdo;
    }

    /**
     * Return a connection to the pool
     *
     * @param PDO|PDOProxy|null $connection
     */
    public function return($connection): void
    {
        if ($connection === null) {
            $this->metrics['errors_total']++;
            return;
        }

        // Optionally clean up the connection before returning to pool
        // Reset any session variables, clear warnings, etc.

        $this->metrics['returned_total']++;
        $this->pool->put($connection);
    }

    /**
     * Close the connection pool
     */
    public function close(): void
    {
        $this->pool->close();
    }

    /**
     * Get pool metrics
     *
     * @return array
     */
    public function getMetrics(): array
    {
        return array_merge($this->metrics, [
            'size' => $this->pool->getLength(),
            'idle' => $this->pool->getIdleCount(),
            'active' => $this->pool->getLength() - $this->pool->getIdleCount()
        ]);
    }
}