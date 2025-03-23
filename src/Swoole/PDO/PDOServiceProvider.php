<?php

declare(strict_types=1);

namespace Ody\DB\Swoole\PDO;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Ody\Foundation\Providers\ServiceProvider;
use Swoole\Runtime;

/**
 * Service provider for Swoole PDO connection pool
 */
class PDOServiceProvider extends ServiceProvider
{
    /**
     * Register services with the container
     */
    public function register(): void
    {
        // Register the connection pool factory
        $this->container->singleton(ConnectionPoolFactory::class, function () {
            return new ConnectionPoolFactory();
        });

        // Register the connection pool
        $this->container->singleton(ConnectionPoolInterface::class, function ($app) {
            $connectionParams = $this->getConnectionParams();
            $factory = $app->make(ConnectionPoolFactory::class);
            return $factory($connectionParams);
        });

        // Register the driver middleware
        $this->container->singleton(DriverMiddleware::class, function ($app) {
            return new DriverMiddleware($app->make(ConnectionPoolInterface::class));
        });

        // Register DBAL configuration
        $this->container->singleton('db.config', function ($app) {
            $config = new Configuration();
            $config->setMiddlewares([$app->make(DriverMiddleware::class)]);
            return $config;
        });

        // Register DBAL connection factory
        $this->container->bind('db.connection', function ($app) {
            $connectionParams = $this->getConnectionParams();
            $configuration = $app->make('db.config');
            return DriverManager::getConnection($connectionParams, $configuration);
        });
    }

    /**
     * Get database connection parameters from configuration
     *
     * @return array The connection parameters
     */
    private function getConnectionParams(): array
    {
        $environment = config('app.environment', 'local');
        $dbConfig = config("database.environments.{$environment}", []);

        return [
            'host' => $dbConfig['host'] ?? 'localhost',
            'port' => $dbConfig['port'] ?? 3306,
            'dbname' => $dbConfig['database'] ?? $dbConfig['db_name'] ?? '',
            'user' => $dbConfig['username'] ?? '',
            'password' => $dbConfig['password'] ?? '',
            'charset' => $dbConfig['charset'] ?? 'utf8mb4',
            'driverClass' => Driver::class,
            'pool_size' => $dbConfig['pool_size'] ?? 32,
            'connectionTtl' => $dbConfig['connection_ttl'] ?? ConnectionPoolFactory::DEFAULT_CONNECTION_TTL,
            'usedTimes' => $dbConfig['query_limit'] ?? ConnectionPoolFactory::DEFAULT_USAGE_LIMIT,
            'retry' => [
                'maxAttempts' => $dbConfig['retry_attempts'] ?? 2,
                'delay' => $dbConfig['retry_delay'] ?? 1000,
            ],
            'connectionDelay' => $dbConfig['connection_delay'] ?? 2,
            'useConnectionPool' => $dbConfig['use_connection_pool'] ?? true,
        ];
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Skip runtime hooks in console commands
        if ($this->isRunningInConsole()) {
            return;
        }

        // Enable coroutine hooks for PDO
        Runtime::enableCoroutine(SWOOLE_HOOK_ALL);

        // On server shutdown, close the connection pool
        register_shutdown_function(function () {
            if ($this->container->has(ConnectionPoolInterface::class)) {
                $pool = $this->container->make(ConnectionPoolInterface::class);
                $pool->close();
            }
        });
    }

    /**
     * Publish configuration
     */
    protected function publishConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../../../config/doctrine-swoole.php' => 'doctrine-swoole.php'
        ], 'ody/doctrine-swoole');
    }
}