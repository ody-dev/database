<?php
/*
 *  This file is part of ODY framework.
 *
 *  @link     https://ody.dev
 *  @document https://ody.dev/docs
 *  @license  https://github.com/ody-dev/ody-foundation/blob/master/LICENSE
 */

declare(strict_types=1);

namespace Ody\DB\Migrations\Database\Adapter;

use PDOStatement;
use Ody\DB\Migrations\Database\Element\Structure;
use Ody\DB\Migrations\Database\QueryBuilder\QueryBuilderInterface;
use Ody\DB\Migrations\Exception\DatabaseQueryExecuteException;

interface AdapterInterface
{
    /**
     * @throws DatabaseQueryExecuteException on error
     */
    public function execute(PDOStatement $sql): bool;

    /**
     * @throws DatabaseQueryExecuteException on error
     */
    public function query(string $sql): PDOStatement;

    /**
     * @param mixed[] $data
     * @return mixed last inserted id
     */
    public function insert(string $table, array $data);

    /**
     * @param mixed[] $data
     */
    public function buildInsertQuery(string $table, array $data): PDOStatement;

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $conditions
     */
    public function update(string $table, array $data, array $conditions = [], string $where = ''): bool;

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $conditions
     */
    public function buildUpdateQuery(string $table, array $data, array $conditions = [], string $where = ''): PDOStatement;

    /**
     * @param array<string, mixed> $conditions
     */
    public function delete(string $table, array $conditions = [], string $where = ''): bool;

    /**
     * @param array<string, mixed> $conditions
     */
    public function buildDeleteQuery(string $table, array $conditions = [], string $where = ''): PDOStatement;

    public function buildDoNotCheckForeignKeysQuery(): string;

    public function buildCheckForeignKeysQuery(): string;

    /**
     * @return array<array<string, mixed>>
     */
    public function select(string $sql): array;

    /**
     * @param string[] $fields
     * @param array<string, mixed> $conditions
     * @param string[]|array<string, string> $orders
     * @param string[] $groups
     * @return array<string, mixed>|null
     */
    public function fetch(string $table, array $fields = ['*'], array $conditions = [], array $orders = [], array $groups = []): ?array;

    /**
     * @param string[] $fields
     * @param array<string, mixed> $conditions
     * @param string[]|array<string, string> $orders
     * @param string[] $groups
     * @return array<array<string, mixed>>
     */
    public function fetchAll(string $table, array $fields = ['*'], array $conditions = [], ?string $limit = null, array $orders = [], array $groups = []): array;

    public function getQueryBuilder(): QueryBuilderInterface;

    /**
     * Initiates a transaction
     */
    public function startTransaction(): bool;

    /**
     * Commits a transaction
     */
    public function commit(): bool;

    /**
     * Rolls back a transaction
     */
    public function rollback(): bool;

    public function setCharset(string $charset): AdapterInterface;

    public function getCharset(): ?string;

    public function setCollation(?string $collation): AdapterInterface;

    public function getCollation(): ?string;

    public function getStructure(): Structure;
}
