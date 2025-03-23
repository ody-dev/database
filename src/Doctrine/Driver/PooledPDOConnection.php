<?php
/*
 *  This file is part of ODY framework.
 *
 *  @link     https://ody.dev
 *  @document https://ody.dev/docs
 *  @license  https://github.com/ody-dev/ody-foundation/blob/master/LICENSE
 */

namespace Ody\DB\Doctrine\Driver;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use Ody\DB\ConnectionPool\ConnectionPoolAdapter;
use Ody\DB\Migrations\Database\Adapter\MysqlAdapter;
use PDO;

/**
 * Pooled PDO Connection implementation for Doctrine DBAL
 */
class PooledPDOConnection implements Connection
{
    /**
     * The PDO instance
     *
     * @var PDO
     */
    private PDO $pdo;

    /**
     * The connection pool adapter
     *
     * @var ConnectionPoolAdapter|null
     */
    private ?ConnectionPoolAdapter $pool;

    /**
     * Whether the connection is in a transaction
     *
     * @var bool
     */
    private bool $inTransaction = false;

    /**
     * Constructor
     *
     * @param PDO $pdo
     * @param ConnectionPoolAdapter|null $pool
     */
    public function __construct(PDO $pdo, ?ConnectionPoolAdapter $pool = null)
    {
        $this->pdo = $pdo;
        $this->pool = $pool;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare(string $sql): Statement
    {
        return new PooledPDOStatement($this->pdo->prepare($sql));
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $sql): Result
    {
        return new PooledPDOResult($this->pdo->query($sql, PDO::FETCH_ASSOC));
    }

    /**
     * {@inheritdoc}
     */
    public function quote(string $value): string
    {
        return $this->pdo->quote($value);
    }

    /**
     * {@inheritdoc}
     */
    public function exec(string $sql): int
    {
        return $this->pdo->exec($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId(?string $name = null): string
    {
        if ($name !== null) {
            return $this->pdo->lastInsertId($name);
        }

        return $this->pdo->lastInsertId();
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction(): void
    {
        $this->inTransaction = true;
        $this->pdo->beginTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): void
    {
        $result = $this->pdo->commit();
        $this->inTransaction = false;
    }

    /**
     * {@inheritdoc}
     */
    public function rollBack(): void
    {
        $result = $this->pdo->rollBack();
        $this->inTransaction = false;
    }

    /**
     * Get native PDO instance
     *
     * @return PDO
     */
    public function getNativeConnection(): PDO
    {
        return $this->pdo;
    }

    /**
     * Return connection to pool when object is destroyed
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Return the connection to the pool when finished
     *
     * @return void
     */
    public function close(): void
    {
        // Only return to pool if not in a transaction
        if ($this->pool !== null && !$this->inTransaction) {
            try {
                $this->pool->return($this->pdo);
            } catch (\Throwable $e) {
                logger()->error("Failed to return connection to pool: " . $e->getMessage());
            }
        }
    }

    public function getServerVersion(): string
    {
        // TODO: Implement getServerVersion() method.
        return MysqlAdapter::getServerVersion();
    }
}