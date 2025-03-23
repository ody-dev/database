<?php

declare(strict_types=1);

namespace Ody\DB\Swoole\PDO;

use function time;

/**
 * Tracks the statistics and state of a database connection
 */
class ConnectionStats
{
    /**
     * @param int $lastInteraction Timestamp of the last interaction with this connection
     * @param int $counter How many queries this connection has processed
     * @param int|null $ttl Maximum idle time in seconds before the connection is considered expired
     * @param int|null $counterLimit Maximum number of queries a connection can process before being retired
     */
    public function __construct(
        public int   $lastInteraction,
        public int   $counter,
        private ?int $ttl = null,
        private ?int $counterLimit = null,
    )
    {
    }

    /**
     * Check if the connection should be retired based on TTL or query count
     */
    public function isOverdue(): bool
    {
        if (!$this->counterLimit && !$this->ttl) {
            return false;
        }

        $counterOverflow = $this->counterLimit !== null && $this->counter > $this->counterLimit;
        $ttlOverdue = $this->ttl !== null && time() - $this->lastInteraction > $this->ttl;

        return $counterOverflow || $ttlOverdue;
    }
}