<?php

declare(strict_types=1);

namespace Ody\DB\Swoole\PDO;

use Closure;
use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\ParameterType;
use PDO;
use Swoole\Coroutine as Co;
use Swoole\Coroutine\Context;
use Throwable;
use function time;

/**
 * PDO Connection implementation for Swoole
 */
class Connection implements ConnectionInterface
{
    /**
     * @param ConnectionPoolInterface $pool Connection pool
     * @param int $retryDelay Delay between retry attempts in milliseconds
     * @param int $maxAttempts Maximum number of connection retry attempts
     * @param int $connectionDelay Maximum time to wait for a connection in seconds
     * @param Closure|null $connectConstructor Constructor for non-pooled connections
     */
    public function __construct(
        private ConnectionPoolInterface $pool,
        private int                     $retryDelay,
        private int                     $maxAttempts,
        private int                     $connectionDelay,
        private ?Closure                $connectConstructor = null,
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function prepare(string $sql): Statement
    {
        $pdo = $this->getNativeConnection();
        $pdoStatement = $pdo->prepare($sql);

        $stats = $this->connectionStats();
        if ($stats instanceof ConnectionStats) {
            $stats->counter++;
        }

        return new PDOStatement($pdoStatement);
    }

    /**
     * Get the PDO connection from the pool or create a new one
     */
    public function getNativeConnection(): PDO
    {
        $context = $this->getContext();
        [$connection] = $context[self::class] ?? [null, null];

        if (!$connection instanceof PDO) {
            $lastException = null;

            for ($i = 0; $i < $this->maxAttempts; $i++) {
                try {
                    [$connection, $stats] = match (true) {
                        $this->connectConstructor === null => $this->pool->get($this->connectionDelay),
                        default => [($this->connectConstructor)(), new ConnectionStats(0, 0)]
                    };

                    if (!$connection instanceof PDO) {
                        throw new \RuntimeException("No connection available in pool (attempt $i)");
                    }

                    $this->ping($connection, $i);
                    $context[self::class] = [$connection, $stats];

                    Co::defer(fn() => $this->onDefer());
                    break;
                } catch (Throwable $e) {
                    $lastException = $e;
                    Co::usleep($this->retryDelay * 1000);
                }
            }

            if (!$connection instanceof PDO) {
                throw $lastException ?? new \RuntimeException('Connection could not be initiated');
            }
        }

        return $connection;
    }

    /**
     * Get the coroutine context
     *
     * @throws \RuntimeException If the context is not available
     */
    private function getContext(): Context
    {
        $cid = Co::getCid();
        $context = Co::getContext($cid);

        if (!$context instanceof Context) {
            throw new \RuntimeException('Connection Co::Context unavailable');
        }

        return $context;
    }

    /**
     * Test if a connection is still valid
     *
     * @throws \RuntimeException If the connection is invalid
     */
    private function ping(PDO $pdo, int $attempt): void
    {
        try {
            $pdo->query('SELECT 1');
        } catch (\PDOException $e) {
            throw new \RuntimeException(
                "Connection ping failed. Trying reconnect (attempt $attempt). Reason: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $sql): Result
    {
        $pdo = $this->getNativeConnection();
        $pdoStatement = $pdo->query($sql);

        $stats = $this->connectionStats();
        if ($stats instanceof ConnectionStats) {
            $stats->counter++;
        }

        return new PDOResult($pdoStatement);
    }

    /**
     * Get the stats for the current connection
     */
    public function connectionStats(): ?ConnectionStats
    {
        $context = $this->getContext();
        [, $stats] = $context[self::class] ?? [null, null];
        return $stats;
    }

    /**
     * Called when the coroutine is finished to return the connection to the pool
     */
    private function onDefer(): void
    {
        if ($this->connectConstructor) {
            return;
        }

        $context = $this->getContext();
        [$connection, $stats] = $context[self::class] ?? [null, null];

        if (!$connection instanceof PDO) {
            return;
        }

        if ($stats instanceof ConnectionStats) {
            $stats->lastInteraction = time();
        }

        $this->pool->put($connection);
        unset($context[self::class]);
    }

    /**
     * {@inheritdoc}
     */
    public function quote($value, $type = ParameterType::STRING): string
    {
        return $this->getNativeConnection()->quote($value, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function exec(string $sql): int
    {
        $pdo = $this->getNativeConnection();
        $result = $pdo->exec($sql);

        $stats = $this->connectionStats();
        if ($stats instanceof ConnectionStats) {
            $stats->counter++;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId($name = null): string
    {
        return (string)$this->getNativeConnection()->lastInsertId($name);
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction(): void
    {
        $this->getNativeConnection()->beginTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): void
    {
        $this->getNativeConnection()->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function rollBack(): void
    {
        $this->getNativeConnection()->rollBack();
    }

    public function getServerVersion(): string
    {
        // TODO: Implement getServerVersion() method.
        return '';
    }
}