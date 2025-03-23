<?php

declare(strict_types=1);

namespace Ody\DB\Swoole\PDO;

use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use PDO;
use PDOStatement as NativePDOStatement;

/**
 * PDOStatement implementation for Doctrine DBAL
 */
class PDOStatement implements Statement
{
    /**
     * @param NativePDOStatement $statement The PDO statement to wrap
     */
    public function __construct(private NativePDOStatement $statement)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function bindValue($param, $value, $type = PDO::PARAM_STR): bool
    {
        return $this->statement->bindValue($param, $value, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function bindParam($param, &$variable, $type = PDO::PARAM_STR, $length = null): bool
    {
        return $this->statement->bindParam($param, $variable, $type, $length ?: 0);
    }

    /**
     * {@inheritdoc}
     */
    public function execute($params = null): Result
    {
        $this->statement->execute($params);
        return new PDOResult($this->statement);
    }
}