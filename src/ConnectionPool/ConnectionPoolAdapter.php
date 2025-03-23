<?php
/*
 *  This file is part of ODY framework.
 *
 *  @link     https://ody.dev
 *  @document https://ody.dev/docs
 *  @license  https://github.com/ody-dev/ody-foundation/blob/master/LICENSE
 */

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
        'current_active' => 0,
    ];

    public function __construct(array $config, int $size = 64)
    {
        // Set performance-optimized PDO attributes
        $defaultOptions = [
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_AUTOCOMMIT => true,
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
     * @return false|PDOProxy
     */
    public function borrow(): false|PDOProxy
    {
        $this->metrics['borrowed_total']++;
        $this->metrics['current_active']++;

        try {
            $pdo = $this->pool->get();

            // Some additional validation/ping could be done here
            // to ensure the connection is still valid

            return $pdo;
        } catch (\Throwable $e) {
            $this->metrics['errors_total']++;

            // If there's an error getting from the pool, create a direct PDO connection
            // This is a fallback for when the pool is having issues
            logger()->error("Error getting connection from pool: " . $e->getMessage());

            // Rethrow the exception to be handled by caller
            throw $e;
        }
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

        $this->metrics['returned_total']++;
        $this->metrics['current_active'] = max(0, $this->metrics['current_active'] - 1);

        // Handle both PDOProxy and regular PDO instances
        if ($connection instanceof PDOProxy) {
            // Swoole PDOProxy instances are already designed to be returned to the pool
            try {
                $this->pool->put($connection);
            } catch (\Throwable $e) {
                // If there's an issue returning to the pool (e.g., the reset() method not found)
                // We'll log it but not throw, as this is a non-critical operation
                logger()->error("Error returning connection to pool: " . $e->getMessage());
                $this->metrics['errors_total']++;
            }
        } else {
            // For non-proxy connections, we can't return them to the Swoole pool
            // They will be garbage collected normally
            logger()->debug("Non-proxy connection will be garbage collected instead of returned to pool");
        }
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