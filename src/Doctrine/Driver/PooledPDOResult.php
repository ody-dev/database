<?php

namespace Ody\DB\Doctrine\Driver;

use Doctrine\DBAL\Driver\Result;
use PDO;
use PDOStatement;

/**
 * Pooled PDO Result implementation
 */

/**
 * Pooled PDO Result implementation
 */
class PooledPDOResult implements Result
{
    /**
     * @var PDOStatement|\Swoole\Database\PDOStatementProxy
     */
    private $stmt;

    /**
     * Constructor
     *
     * @param PDOStatement|\Swoole\Database\PDOStatementProxy $stmt
     */
    public function __construct($stmt)
    {
        if (!($stmt instanceof PDOStatement) && !($stmt instanceof \Swoole\Database\PDOStatementProxy)) {
            throw new \InvalidArgumentException(
                'The statement must be an instance of PDOStatement or Swoole\Database\PDOStatementProxy, got: ' .
                (is_object($stmt) ? get_class($stmt) : gettype($stmt))
            );
        }

        $this->stmt = $stmt;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchNumeric(): array|false
    {
        return $this->stmt->fetch(PDO::FETCH_NUM);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAssociative(): array|false
    {
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchOne(): mixed
    {
        return $this->stmt->fetchColumn();
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAllNumeric(): array
    {
        return $this->stmt->fetchAll(PDO::FETCH_NUM);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAllAssociative(): array
    {
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchFirstColumn(): array
    {
        return $this->stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * {@inheritdoc}
     */
    public function rowCount(): int
    {
        return $this->stmt->rowCount();
    }

    /**
     * {@inheritdoc}
     */
    public function columnCount(): int
    {
        return $this->stmt->columnCount();
    }

    /**
     * {@inheritdoc}
     */
    public function free(): void
    {
        $this->stmt->closeCursor();
    }
}
