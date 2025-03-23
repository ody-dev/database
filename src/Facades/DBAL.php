<?php
/*
 *  This file is part of ODY framework.
 *
 *  @link     https://ody.dev
 *  @document https://ody.dev/docs
 *  @license  https://github.com/ody-dev/ody-foundation/blob/master/LICENSE
 */

namespace Ody\DB\Facades;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Ody\DB\Doctrine\PooledConnectionManager;

/**
 * Facade for Doctrine DBAL with connection pooling support
 */
class DBAL
{
    /**
     * Connection configurations
     *
     * @var array
     */
    protected static array $configs = [];

    /**
     * Set the configuration for a connection
     *
     * @param array $config
     * @param string $name
     * @return void
     */
    public static function setConfig(array $config, string $name = 'default'): void
    {
        self::$configs[$name] = $config;
    }

    /**
     * Get the configuration for a connection
     *
     * @param string $name
     * @return array
     * @throws \RuntimeException
     */
    public static function getConfig(string $name = 'default'): array
    {
        if (!isset(self::$configs[$name])) {
            throw new \RuntimeException("No configuration found for connection: {$name}");
        }

        return self::$configs[$name];
    }

    /**
     * Get a connection with pooling support
     *
     * @param string $name Connection name
     * @param array|null $config Custom connection config
     * @return Connection
     */
    public static function connection(string $name = 'default', ?array $config = null): Connection
    {
        $connectionParams = $config ?? self::$configs[$name] ?? null;

        if ($connectionParams === null) {
            // Try to get the connection from the container if available
            if (function_exists('app') && app()->bound('db.dbal.connection')) {
                return app()->make('db.dbal.connection', ['name' => $name]);
            }

            throw new \RuntimeException("No configuration found for connection: {$name}");
        }

        return PooledConnectionManager::getConnection($connectionParams, $name);
    }

    /**
     * Get a query builder
     *
     * @param string $name Connection name
     * @return QueryBuilder
     */
    public static function createQueryBuilder(string $name = 'default'): QueryBuilder
    {
        return self::connection($name)->createQueryBuilder();
    }

    /**
     * Execute a query and return the result
     *
     * @param string $query SQL query
     * @param array $params Query parameters
     * @param array $types Parameter types
     * @param string $connection Connection name
     * @return Result
     */
    public static function executeQuery(string $query, array $params = [], array $types = [], string $connection = 'default'): Result
    {
        return self::connection($connection)->executeQuery($query, $params, $types);
    }

    /**
     * Execute a statement and return the affected rows
     *
     * @param string $query SQL statement
     * @param array $params Statement parameters
     * @param array $types Parameter types
     * @param string $connection Connection name
     * @return int
     */
    public static function executeStatement(string $query, array $params = [], array $types = [], string $connection = 'default'): int
    {
        return self::connection($connection)->executeStatement($query, $params, $types);
    }

    /**
     * Fetch all records as associative arrays
     *
     * @param string $query SQL query
     * @param array $params Query parameters
     * @param array $types Parameter types
     * @param string $connection Connection name
     * @return array
     */
    public static function fetchAllAssociative(string $query, array $params = [], array $types = [], string $connection = 'default'): array
    {
        return self::connection($connection)->fetchAllAssociative($query, $params, $types);
    }

    /**
     * Fetch a single row as an associative array
     *
     * @param string $query SQL query
     * @param array $params Query parameters
     * @param array $types Parameter types
     * @param string $connection Connection name
     * @return array|false
     */
    public static function fetchAssociative(string $query, array $params = [], array $types = [], string $connection = 'default')
    {
        return self::connection($connection)->fetchAssociative($query, $params, $types);
    }

    /**
     * Fetch a single column
     *
     * @param string $query SQL query
     * @param array $params Query parameters
     * @param array $types Parameter types
     * @param string $connection Connection name
     * @return mixed
     */
    public static function fetchOne(string $query, array $params = [], array $types = [], string $connection = 'default')
    {
        return self::connection($connection)->fetchOne($query, $params, $types);
    }

    /**
     * Execute a function within a transaction
     *
     * @param callable $callback
     * @param string $connection Connection name
     * @return mixed The value returned from the callback
     */
    public static function transactional(callable $callback, string $connection = 'default')
    {
        return self::connection($connection)->transactional($callback);
    }

    /**
     * Start a new transaction
     *
     * @param string $connection Connection name
     * @return void
     */
    public static function beginTransaction(string $connection = 'default'): void
    {
        self::connection($connection)->beginTransaction();
    }

    /**
     * Commit the active transaction
     *
     * @param string $connection Connection name
     * @return void
     */
    public static function commit(string $connection = 'default'): void
    {
        self::connection($connection)->commit();
    }

    /**
     * Rollback the active transaction
     *
     * @param string $connection Connection name
     * @return void
     */
    public static function rollBack(string $connection = 'default'): void
    {
        self::connection($connection)->rollBack();
    }

    /**
     * Close all connection pools
     *
     * @return void
     */
    public static function closeAll(): void
    {
        PooledConnectionManager::closeAll();
    }
}