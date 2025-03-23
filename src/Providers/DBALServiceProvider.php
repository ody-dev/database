<?php

namespace Ody\DB\Providers;

use Doctrine\DBAL\Connection;
use Ody\DB\Doctrine\DBAL;
use Ody\DB\Doctrine\DBALConnectionManager;
use Ody\Foundation\Providers\ServiceProvider;

class DBALServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        // Register DBAL facade as a singleton
        $this->container->singleton('db.dbal', function () {
            return new \Ody\DB\Doctrine\DBAL();
        });

        // Register the connection manager
        $this->container->singleton('db.dbal.connection_manager', function () {
            return new DBALConnectionManager();
        });

        // Register the default connection as a singleton
        $this->container->singleton(Connection::class, function ($app) {
            return DBALConnectionManager::getConnection();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Skip initialization during console commands
        if ($this->isRunningInConsole()) {
            return;
        }

        // Get database configuration
        $dbConfig = config('database.environments')[config('app.environment', 'local')];

        // Convert config to Doctrine format
        $doctrineConfig = [
            'driver' => 'pdo_mysql',
            'host' => $dbConfig['host'] ?? 'localhost',
            'port' => $dbConfig['port'] ?? 3306,
            'dbname' => $dbConfig['database'] ?? $dbConfig['db_name'] ?? '',
            'user' => $dbConfig['username'] ?? '',
            'password' => $dbConfig['password'] ?? '',
            'charset' => $dbConfig['charset'] ?? 'utf8mb4',
            'wrapperClass' => \Ody\DB\Doctrine\PooledConnection::class,
        ];

        // Add pooling configuration if enabled
        if (config('database.enable_connection_pool', false)) {
            $doctrineConfig['use_pooling'] = true;
            $doctrineConfig['pool_size'] = config('database.pool_size', 32);
        }

        // Boot DBAL with configuration
        DBAL::boot($doctrineConfig);
    }
}