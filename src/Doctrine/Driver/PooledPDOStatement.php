<?php
/*
 *  This file is part of ODY framework.
 *
 *  @link     https://ody.dev
 *  @document https://ody.dev/docs
 *  @license  https://github.com/ody-dev/ody-foundation/blob/master/LICENSE
 */

namespace Ody\DB\Doctrine\Driver;

use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\ParameterType;
use PDO;
use PDOStatement;

/**
 * Pooled PDO Statement implementation
 */
class PooledPDOStatement implements Statement
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
    public function bindValue(int|string $param, mixed $value, ParameterType $type): void
    {
        $pdoType = match ($type) {
            ParameterType::NULL => PDO::PARAM_NULL,
            ParameterType::INTEGER => PDO::PARAM_INT,
            ParameterType::STRING => PDO::PARAM_STR,
            ParameterType::BINARY => PDO::PARAM_LOB,
            ParameterType::BOOLEAN => PDO::PARAM_BOOL,
            ParameterType::LARGE_OBJECT => PDO::PARAM_LOB,
            default => PDO::PARAM_STR,
        };

        $this->stmt->bindValue($param, $value, $pdoType);
    }

    /**
     * {@inheritdoc}
     */
    public function bindParam(int|string $param, mixed &$variable, ParameterType $type, ?int $length = null): void
    {
        $pdoType = match ($type) {
            ParameterType::NULL => PDO::PARAM_NULL,
            ParameterType::INTEGER => PDO::PARAM_INT,
            ParameterType::STRING => PDO::PARAM_STR,
            ParameterType::BINARY => PDO::PARAM_LOB,
            ParameterType::BOOLEAN => PDO::PARAM_BOOL,
            ParameterType::LARGE_OBJECT => PDO::PARAM_LOB,
            default => PDO::PARAM_STR,
        };

        if ($length === null) {
            $this->stmt->bindParam($param, $variable, $pdoType);
        } else {
            $this->stmt->bindParam($param, $variable, $pdoType, $length);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function execute(): Result
    {
        $this->stmt->execute();
        return new PooledPDOResult($this->stmt);
    }
}