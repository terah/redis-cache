<?php

namespace Terah\RedisCache;

trait RedisCacheTrait
{
    /** @var CacheInterface */
    protected $cache;

    /**
     * Sets a cache.
     *
     * @param CacheInterface $cache
     * @return $this
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
        return $this;
    }
}
