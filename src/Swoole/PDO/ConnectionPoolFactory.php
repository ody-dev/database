<?php

declare(strict_types=1);

namespace Ody\DB\Swoole\PDO;

use PDO;

/**
 * Factory for creating connection pools
 */
class ConnectionPoolFactory
{
    /**
     * Default connection time-to-live in seconds
     */
    public const DEFAULT_CONNECTION_TTL = 60;

    /**
     * Default usage limit (number of queries) for a connection
     */
    public const DEFAULT_USAGE_LIMIT = 100;

    /**
     * Create a new connection pool from database parameters
     *
     * @param array $params Database connection parameters
     * @return ConnectionPoolInterface
     */
    public function __invoke(array $params): ConnectionPoolInterface
    {
        // Extract pool configuration
        $poolSize = (int)($params['pool_size'] ?? 10);
        $usageLimit = (int)($params['usedTimes'] ?? self::DEFAULT_USAGE_LIMIT);
        $connectionTtl = (int)($params['connectionTtl'] ?? self::DEFAULT_CONNECTION_TTL);

        // Create PDO connection constructor
        $pdoConstructor = function () use ($params) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $params['host'] ?? 'localhost',
                $params['port'] ?? '3306',
                $params['dbname'] ?? '',
                $params['charset'] ?? 'utf8mb4'
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            // Merge custom options
            if (isset($params['options']) && is_array($params['options'])) {
                $options = array_merge($options, $params['options']);
            }

            // Create PDO connection
            try {
                $pdo = new PDO(
                    $dsn,
                    $params['user'] ?? '',
                    $params['password'] ?? '',
                    $options
                );

                // Run a simple query to verify connection
                $pdo->query('SELECT 1');

                return $pdo;
            } catch (\PDOException $e) {
                throw new \RuntimeException(
                    "Failed to connect to database: {$e->getMessage()}",
                    (int)$e->getCode(),
                    $e
                );
            }
        };

        // Return a new connection pool
        return new ConnectionPool(
            $pdoConstructor,
            $poolSize,
            $connectionTtl,
            $usageLimit
        );
    }
}