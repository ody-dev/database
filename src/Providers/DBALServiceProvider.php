<?php
/*
 *  This file is part of ODY framework.
 *
 *  @link     https://ody.dev
 *  @document https://ody.dev/docs
 *  @license  https://github.com/ody-dev/ody-foundation/blob/master/LICENSE
 */

namespace Ody\DB\Providers;

use Ody\DB\Doctrine\PooledConnectionManager;
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
        // Register DBAL connection manager
//        $this->container->singleton('db.dbal.manager', function () {
//            return new PooledConnectionManager();
//        });
//
//        // Register a factory callback for DBAL connections
//        $this->container->bind('db.dbal.connection', function ($app, $params = []) {
//            $name = $params['name'] ?? 'default';
//            $config = $params['config'] ?? DBAL::getConfig($name);
//
//            // Get connection directly without using the DBAL facade
//            return PooledConnectionManager::getConnection($config, $name);
//        });
//
//        // Register the default Connection as a singleton
//        $this->container->singleton(Connection::class, function ($app) {
//            return $app->make('db.dbal.connection');
//        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Skip initialization during console commands if needed
        if ($this->isRunningInConsole()) {
            return;
        }

//        // Get database configuration from app config
//        $environment = config('app.environment', 'local');
//        $dbConfig = config("database.environments.{$environment}", []);
//
//        // Convert to DBAL format
//        $doctrineConfig = [
//            'driver' => 'pdo_mysql',
//            'host' => $dbConfig['host'] ?? 'localhost',
//            'port' => $dbConfig['port'] ?? 3306,
//            'dbname' => $dbConfig['database'] ?? $dbConfig['db_name'] ?? '',
//            'user' => $dbConfig['username'] ?? '',
//            'password' => $dbConfig['password'] ?? '',
//            'charset' => $dbConfig['charset'] ?? 'utf8mb4',
//            'wrapperClass' => \Ody\DB\Doctrine\PooledConnection::class,
//        ];
//
//        // Add pooling configuration if enabled
//        if (config('database.enable_connection_pool', false) && extension_loaded('swoole')) {
//            $doctrineConfig['use_pooling'] = true;
//            $doctrineConfig['pool_size'] = config('database.pool_size', 32);
//
//            // Initialize connection pooling
//            PooledConnectionManager::initialize();
//        }
//
//        // Set the configuration for the default connection
//        DBAL::setConfig($doctrineConfig);
//
//        // Pre-initialize the connection pool if needed
//        if (config('database.enable_connection_pool', false) &&
//            extension_loaded('swoole') &&
//            config('database.pre_initialize_pool', false)) {
//
//            // Prepare the pool but don't get a connection yet
//            PooledConnectionManager::getPool($doctrineConfig, 'default');
//        }

        // Publish configuration
        $this->publishes([
            __DIR__ . '/../../config/doctrine.php' => 'doctrine.php'
        ], 'ody/doctrine');
    }
}