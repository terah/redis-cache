<?php

namespace Terah\RedisCache;

trait RedisCacheTrait
{
    /** @var RedisCache */
    protected $cache;

    /**
     * Sets a cache.
     *
     * @param RedisCache $cache
     * @return $this
     */
    public function setCache(RedisCache $cache)
    {
        $this->cache = $cache;
        return $this;
    }
}
