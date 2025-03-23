<?php

namespace Ody\DB\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Ody\Support\Config;
use Swoole\Coroutine;

/**
 * Connection manager for Doctrine DBAL
 */
class DBALConnectionManager
{
    /**
     * Connection instances
     *
     * @var array<string, Connection>
     */
    private static array $connections = [];

    /**
     * Active connections per coroutine
     *
     * @var array
     */
    private static array $coroutineConnections = [];

    /**
     * Default connection configuration
     *
     * @var array|null
     */
    private static ?array $defaultConfig = null;

    /**
     * Set default connection configuration
     *
     * @param array $config
     * @return void
     */
    public static function setDefaultConfig(array $config): void
    {
        self::$defaultConfig = $config;
    }

    /**
     * Get a DBAL connection
     *
     * @param string $name Connection name
     * @param array|null $config Custom configuration (overrides default)
     * @return Connection
     */
    public static function getConnection(string $name = 'default', ?array $config = null): Connection
    {
        // In Swoole coroutine environment
        $cid = Coroutine::getCid();
        if ($cid >= 0) {
            return self::getCoroutineConnection($name, $config);
        }

        // Not in a coroutine, use regular connection caching
        if (!isset(self::$connections[$name]) || $config !== null) {
            self::$connections[$name] = self::createConnection($name, $config);
        }

        return self::$connections[$name];
    }

    /**
     * Get a connection for the current coroutine
     *
     * @param string $name
     * @param array|null $config
     * @return Connection
     */
    private static function getCoroutineConnection(string $name, ?array $config = null): Connection
    {
        $cid = Coroutine::getCid();

        // If already have a connection for this coroutine and name, return it
        if (isset(self::$coroutineConnections[$cid][$name]) && $config === null) {
            return self::$coroutineConnections[$cid][$name];
        }

        // Create new connection
        $connection = self::createConnection($name, $config);

        // Store for this coroutine
        if (!isset(self::$coroutineConnections[$cid])) {
            self::$coroutineConnections[$cid] = [];
        }
        self::$coroutineConnections[$cid][$name] = $connection;

        // Auto-return on coroutine end
        Coroutine::defer(function () use ($cid, $name) {
            if (isset(self::$coroutineConnections[$cid][$name])) {
                $connection = self::$coroutineConnections[$cid][$name];

                // Close the connection, which will return it to the pool if it's a pooled connection
                if ($connection instanceof PooledConnection) {
                    $connection->close();
                } elseif (method_exists($connection, 'close')) {
                    $connection->close();
                }

                // Remove from active connections
                unset(self::$coroutineConnections[$cid][$name]);

                // If all connections for this coroutine are gone, remove the cid entry
                if (empty(self::$coroutineConnections[$cid])) {
                    unset(self::$coroutineConnections[$cid]);
                }
            }
        });

        return $connection;
    }

    /**
     * Create a new connection
     *
     * @param string $name
     * @param array|null $config
     * @return Connection
     */
    private static function createConnection(string $name, ?array $config = null): Connection
    {
        // Merge default config with provided config
        $connectionParams = $config ?? self::$defaultConfig ?? self::getConfigFromApp();

        // Add connection name if using pooling
        if (($connectionParams['use_pooling'] ?? false) && !isset($connectionParams['connection_name'])) {
            $connectionParams['connection_name'] = $name;
        }

        // Use wrapperClass if not specified
        if (($connectionParams['use_pooling'] ?? false) && !isset($connectionParams['wrapperClass'])) {
            $connectionParams['wrapperClass'] = PooledConnection::class;
        }

        // Get connection from DriverManager
        return DriverManager::getConnection($connectionParams);
    }

    /**
     * Get configuration from application config
     *
     * @return array
     */
    private static function getConfigFromApp(): array
    {
        // Get config from container if available
        $config = app(Config::class);

        if (!$config) {
            throw new \RuntimeException('No default configuration found for DBAL connection');
        }

        $environment = $config->get('app.environment', 'local');
        $dbConfig = $config->get("database.environments.{$environment}", []);

        // Convert to DBAL format
        $connectionParams = [
            'driver' => 'pdo_mysql',
            'host' => $dbConfig['host'] ?? 'localhost',
            'port' => $dbConfig['port'] ?? 3306,
            'dbname' => $dbConfig['database'] ?? $dbConfig['db_name'] ?? '',
            'user' => $dbConfig['username'] ?? '',
            'password' => $dbConfig['password'] ?? '',
            'charset' => $dbConfig['charset'] ?? 'utf8mb4',
        ];

        // Add pooling configuration if enabled
        if ($config->get('database.enable_connection_pool', false)) {
            $connectionParams['use_pooling'] = true;
            $connectionParams['pool_size'] = $config->get('database.pool_size', 32);
        }

        return $connectionParams;
    }

    /**
     * Close all connections
     *
     * @return void
     */
    public static function closeAll(): void
    {
        // Close regular connections
        foreach (self::$connections as $name => $connection) {
            if ($connection instanceof PooledConnection) {
                $connection->close();
            } elseif (method_exists($connection, 'close')) {
                $connection->close();
            }
        }
        self::$connections = [];

        // Close coroutine connections
        foreach (self::$coroutineConnections as $cid => $connections) {
            foreach ($connections as $name => $connection) {
                if ($connection instanceof PooledConnection) {
                    $connection->close();
                } elseif (method_exists($connection, 'close')) {
                    $connection->close();
                }
            }
        }
        self::$coroutineConnections = [];
    }
}