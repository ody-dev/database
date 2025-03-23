<?php

declare(strict_types=1);

namespace Ody\DB\Swoole\PDO;

use Doctrine\DBAL\Driver\AbstractMySQLDriver;
use PDO;
use function filter_var;

/**
 * Swoole PDO MySQL driver for Doctrine DBAL
 */
class Driver extends AbstractMySQLDriver
{
    /**
     * @param ConnectionPoolInterface|null $pool Connection pool instance
     */
    public function __construct(private ?ConnectionPoolInterface $pool = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = []): Connection
    {
        if (!$this->pool instanceof ConnectionPoolInterface) {
            throw new \RuntimeException('Connection pool should be initialized');
        }

        // Extract connection parameters
        $retryMaxAttempts = (int)($params['retry']['maxAttempts'] ?? 1);
        $retryDelay = (int)($params['retry']['delay'] ?? 0);
        $connectionDelay = (int)($params['connectionDelay'] ?? 0);
        $usePool = filter_var($params['useConnectionPool'] ?? true, FILTER_VALIDATE_BOOLEAN);

        // Create constructor for non-pooled connections
        $connectConstructor = null;
        if (!$usePool) {
            $connectConstructor = function () use ($params) {
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

                if (isset($params['options']) && is_array($params['options'])) {
                    $options = array_merge($options, $params['options']);
                }

                return new PDO(
                    $dsn,
                    $params['user'] ?? '',
                    $params['password'] ?? '',
                    $options
                );
            };
        }

        // Create a new connection with the pool
        return new Connection($this->pool, $retryDelay, $retryMaxAttempts, $connectionDelay, $connectConstructor);
    }
}