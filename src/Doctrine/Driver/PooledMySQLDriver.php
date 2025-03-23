<?php
/*
 *  This file is part of ODY framework.
 *
 *  @link     https://ody.dev
 *  @document https://ody.dev/docs
 *  @license  https://github.com/ody-dev/ody-foundation/blob/master/LICENSE
 */

namespace Ody\DB\Doctrine\Driver;

use Doctrine\DBAL\Connection as DoctrineConnection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\AbstractPostgreSQLDriver;
use Doctrine\DBAL\Driver\API\ExceptionConverter;
use Doctrine\DBAL\Driver\API\MySQL\ExceptionConverter as MySQLExceptionConverter;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\MySQLSchemaManager;
use Doctrine\DBAL\VersionAwarePlatformDriver;
use Ody\DB\ConnectionPool\ConnectionPoolAdapter;
use Swoole\Coroutine;

/**
 * Pooled MySQL Driver that integrates with Swoole connection pooling
 */
class PooledMySQLDriver extends AbstractPostgreSQLDriver
{
    /**
     * Connection pools per connection name
     *
     * @var array<string, ConnectionPoolAdapter>
     */
    private static array $pools = [];

    /**
     * {@inheritdoc}
     */
    public function connect(array $params): Driver\Connection
    {
        $cid = Coroutine::getCid();
        var_dump($params);
        // If not in a coroutine or pooling is disabled, use standard PDO connection
        if ($cid === -1 || !($params['use_pooling'] ?? false) || !extension_loaded('swoole')) {
            error_log('using standard connection cid: ' . $cid);
            return $this->createStandardConnection($params);
        }

        $name = $params['connection_name'] ?? 'default';

        // Create or get the pool for this connection name
        $pool = $this->getPool($params, $name);

        try {
            // Get a PDO instance from the pool
            $pdo = $pool->borrow();

            // Return a connection wrapper that knows about our pool
            return new PooledPDOConnection($pdo, $pool);
        } catch (\Throwable $e) {
            // Log the failure and fall back to standard connection
            logger()->error("Failed to get connection from pool: " . $e->getMessage());
            return $this->createStandardConnection($params);
        }
    }

    /**
     * Create a standard PDO connection without pooling
     *
     * @param array $params
     * @return Driver\Connection
     */
    private function createStandardConnection(array $params): Driver\Connection
    {
        // Build DSN string
        $dsn = 'mysql:';

        if (isset($params['host']) && $params['host'] !== '') {
            $dsn .= 'host=' . $params['host'] . ';';
        }

        if (isset($params['port'])) {
            $dsn .= 'port=' . $params['port'] . ';';
        }

        if (isset($params['dbname'])) {
            $dsn .= 'dbname=' . $params['dbname'] . ';';
        }

        if (isset($params['unix_socket'])) {
            $dsn .= 'unix_socket=' . $params['unix_socket'] . ';';
        }

        if (isset($params['charset'])) {
            $dsn .= 'charset=' . $params['charset'] . ';';
        }

        $driverOptions = $params['driverOptions'] ?? [];

        $connection = new \PDO(
            $dsn,
            $params['user'] ?? '',
            $params['password'] ?? '',
            $driverOptions
        );

        return new PooledPDOConnection($connection);
    }

    /**
     * Get or create a connection pool
     *
     * @param array $params
     * @param string $name
     * @return ConnectionPoolAdapter
     */
    private function getPool(array $params, string $name): ConnectionPoolAdapter
    {
        if (!isset(self::$pools[$name])) {
            $poolSize = $params['pool_size'] ?? 32;

            $adapterConfig = [
                'host' => $params['host'] ?? 'localhost',
                'port' => $params['port'] ?? 3306,
                'db_name' => $params['dbname'] ?? '',
                'charset' => $params['charset'] ?? 'utf8mb4',
                'username' => $params['user'] ?? '',
                'password' => $params['password'] ?? '',
                'options' => $params['driverOptions'] ?? [],
            ];

            self::$pools[$name] = new ConnectionPoolAdapter($adapterConfig, $poolSize);
            logger()->info("Created new connection pool: $name with size: $poolSize");
        }

        return self::$pools[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function getSchemaManager(DoctrineConnection $conn, AbstractPlatform $platform): AbstractSchemaManager
    {
        return new MySQLSchemaManager($conn, $platform);
    }

    /**
     * {@inheritdoc}
     */
    public function getExceptionConverter(): ExceptionConverter
    {
        return new MySQLExceptionConverter();
    }

    /**
     * {@inheritdoc}
     */
    public function createDatabasePlatformForVersion($version): AbstractPlatform
    {
        return new MySQLPlatform();
    }
}