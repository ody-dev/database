<?php

namespace Ody\DB\Doctrine\Driver;

use Doctrine\DBAL\Driver\Result;
use PDO;
use PDOStatement;

/**
 * Pooled PDO Result implementation
 */
class PooledPDOResult implements Result
{
    /**
     * @var PDOStatement
     */
    private PDOStatement $stmt;

    /**
     * Constructor
     *
     * @param PDOStatement $stmt
     */
    public function __construct(PDOStatement $stmt)
    {
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