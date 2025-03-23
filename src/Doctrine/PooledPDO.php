<?php

class PooledPDO extends \PDO
{
    protected $pool;

    public function setPool($pool)
    {
        $this->pool = $pool;
        return $this;
    }

    // Auto-return to pool after operations
    public function __destruct()
    {
        if ($this->pool) {
            $this->pool->return($this);
        }
    }
}