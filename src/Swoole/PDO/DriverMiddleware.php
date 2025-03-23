<?php

declare(strict_types=1);

namespace Ody\DB\Swoole\PDO;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware as MiddlewareInterface;

/**
 * Middleware for integrating the pool with Doctrine DBAL
 */
class DriverMiddleware implements MiddlewareInterface
{
    /**
     * @param ConnectionPoolInterface $connectionPool The connection pool to use
     */
    public function __construct(private ConnectionPoolInterface $connectionPool)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function wrap(DriverInterface $driver): DriverInterface
    {
        return new Driver($this->connectionPool);
    }
}