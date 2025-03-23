<?php

declare(strict_types=1);

namespace Ody\DB\Swoole\PDO;

use Closure;
use PDO;
use Swoole\Coroutine\Channel;
use WeakMap;
use function gc_collect_cycles;
use function time;

/**
 * Pool for managing PDO connections
 */
class ConnectionPool implements ConnectionPoolInterface
{
    /**
     * Channel for connection storage
     */
    private Channel $chan;

    /**
     * Map of connections to their stats
     *
     * @var WeakMap<PDO, ConnectionStats>
     */
    private WeakMap $map;

    /**
     * @param Closure $constructor Function to create new connections
     * @param int $size Maximum number of connections in the pool
     * @param int|null $connectionTtl Maximum idle time for a connection in seconds
     * @param int|null $connectionUseLimit Maximum number of queries a connection can process
     */
    public function __construct(
        private Closure $constructor,
        private int     $size,
        private ?int    $connectionTtl = null,
        private ?int    $connectionUseLimit = null
    )
    {
        if ($this->size < 0) {
            throw new \RuntimeException('Expected connection pool size > 0');
        }

        $this->chan = new Channel($this->size);
        $this->map = new WeakMap();
    }

    /**
     * {@inheritdoc}
     */
    public function get(float $timeout = -1): array
    {
        if ($this->chan->isEmpty()) {
            $this->make();
        }

        /** @var PDO|null $connection */
        $connection = $this->chan->pop($timeout);
        if (!$connection instanceof PDO) {
            return [null, null];
        }

        return [
            $connection,
            $this->map[$connection] ?? null,
        ];
    }

    /**
     * Create a new connection and add it to the pool
     */
    private function make(): void
    {
        if ($this->size <= $this->capacity()) {
            return;
        }

        /** @var PDO $connection */
        $connection = ($this->constructor)();

        // Allocate to map only after successful push (to prevent channel overflow in concurrent scenarios)
        if ($this->chan->push($connection, 1)) {
            $this->map[$connection] = new ConnectionStats(
                time(),
                1,
                $this->connectionTtl,
                $this->connectionUseLimit
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function capacity(): int
    {
        return $this->map->count();
    }

    /**
     * {@inheritdoc}
     */
    public function put(PDO $connection): void
    {
        $stats = $this->map[$connection] ?? null;
        if (!$stats || $stats->isOverdue()) {
            $this->remove($connection);
            return;
        }

        if ($this->size <= $this->chan->length()) {
            $this->remove($connection);
            return;
        }

        /** To prevent hypothetical freeze if channel is full */
        if (!$this->chan->push($connection, 1)) {
            $this->remove($connection);
        }
    }

    /**
     * Remove a connection from the pool
     */
    private function remove(PDO $connection): void
    {
        $this->map->offsetUnset($connection);
        unset($connection);
    }

    /**
     * {@inheritdoc}
     */
    public function length(): int
    {
        return $this->chan->length();
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        $this->chan->close();
        gc_collect_cycles();
    }

    /**
     * {@inheritdoc}
     */
    public function stats(): array
    {
        return $this->chan->stats();
    }
}