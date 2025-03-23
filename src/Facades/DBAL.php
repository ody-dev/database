<?php

namespace Ody\DB\Facades;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;

/**
 * Facade for Doctrine DBAL
 */
class DBAL
{
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
     * Get a DBAL connection
     *
     * @param string $name Connection name
     * @param array|null $config Custom connection parameters
     * @return Connection
     */
    public static function connection(string $name = 'default', ?array $config = null): Connection
    {
        return DBALConnectionManager::getConnection($name, $config);
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
     * Quote a string for use in a query
     *
     * @param string $value
     * @param string $connection Connection name
     * @return string
     */
    public static function quote(string $value, string $connection = 'default'): string
    {
        return self::connection($connection)->quote($value);
    }

    /**
     * Close all database connections
     *
     * @return void
     */
    public static function closeAll(): void
    {
        DBALConnectionManager::closeAll();
    }
}