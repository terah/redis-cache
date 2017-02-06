<?php declare(strict_types=1);

namespace Terah\RedisCache;

class NullCache implements CacheInterface
{
    /**
     * @param string $namespace
     * @return CacheInterface
     */
    public function setNamespace(string $namespace) : CacheInterface
    {
        return $this;
    }

    /**
     * @param int $defaultTtl
     * @return CacheInterface
     */
    public function setDefaultTtl(int $defaultTtl) : CacheInterface
    {
        return $this;
    }

    /**
     * @param string    $key
     * @param mixed    $data
     * @param int $ttl
     * @return bool
     */
    public function set(string $key, $data, int $ttl=0) : bool
    {
        return true;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get(string $key)
    {
        return true;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function exists(string $key) : bool
    {
        return true;
    }

    /**
     * @param string $key
     * @return \DateTime
     */
    public function expires(string $key) : \DateTime
    {
        return new \DateTime();
    }

    /**
     * @param string   $key
     * @param \Closure $callback
     * @param int|null $ttl
     * @return null
     */
    public function remember(string $key, \Closure $callback, int $ttl=0)
    {
        return $callback->__invoke();
    }

    /**
     * @param string $keyOrDirectory
     * @return bool
     */
    public function delete(string $keyOrDirectory) : bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function allKeys() : array
    {
        return [];
    }

    /**
     * @return bool
     */
    public function flush() : bool
    {
        return true;
    }

    /**
     * @param string $key
     * @return int
     */
    public function getTtl(string $key) : int
    {
        return 0;
    }
}