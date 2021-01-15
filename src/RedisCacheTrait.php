<?php declare(strict_types=1);

namespace Terah\RedisCache;

trait RedisCacheTrait
{
    protected ?CacheInterface $cache = null;

    /**
     * Sets a cache.
     *
     * @param CacheInterface $cache
     * @return $this
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache            = $cache;

        return $this;
    }

    /**
     * Gets a cache.
     *
     * @return CacheInterface
     */
    public function getCache() : CacheInterface
    {
        return $this->cache;
    }
}
