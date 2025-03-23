<?php

declare(strict_types=1);

namespace Ody\DB\Swoole\PDO;

use PDO;

/**
 * Interface for a PDO connection pool
 */
interface ConnectionPoolInterface
{
    /**
     * Get a connection from the pool
     *
     * @param float $timeout Maximum time to wait for a connection
     * @return array{PDO|null, ConnectionStats|null} Connection and its stats or nulls if no connection is available
     */
    public function get(float $timeout = -1): array;

    /**
     * Return a connection to the pool
     *
     * @param PDO $connection The connection to return
     */
    public function put(PDO $connection): void;

    /**
     * Get the total number of managed connections
     */
    public function capacity(): int;

    /**
     * Get the number of idle connections in the pool
     */
    public function length(): int;

    /**
     * Close all connections and the pool
     */
    public function close(): void;

    /**
     * Get statistics about the pool
     *
     * @return array Pool statistics
     */
    public function stats(): array;
}