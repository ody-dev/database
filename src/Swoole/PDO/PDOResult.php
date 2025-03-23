<?php

declare(strict_types=1);

namespace Ody\DB\Swoole\PDO;

use Doctrine\DBAL\Driver\Result;
use PDO;
use PDOStatement;

/**
 * PDOResult implementation for Doctrine DBAL
 */
class PDOResult implements Result
{
    /**
     * @param PDOStatement $statement The PDO statement to wrap
     */
    public function __construct(private PDOStatement $statement)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function fetchNumeric(): array|false
    {
        return $this->statement->fetch(PDO::FETCH_NUM);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAssociative(): array|false
    {
        return $this->statement->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchOne(): mixed
    {
        return $this->statement->fetchColumn();
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAllNumeric(): array
    {
        return $this->statement->fetchAll(PDO::FETCH_NUM);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAllAssociative(): array
    {
        return $this->statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchFirstColumn(): array
    {
        return $this->statement->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * {@inheritdoc}
     */
    public function rowCount(): int
    {
        return $this->statement->rowCount();
    }

    /**
     * {@inheritdoc}
     */
    public function columnCount(): int
    {
        return $this->statement->columnCount();
    }

    /**
     * {@inheritdoc}
     */
    public function free(): void
    {
        $this->statement->closeCursor();
    }
}