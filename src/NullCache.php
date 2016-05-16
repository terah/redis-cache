<?php

namespace Terah\RedisCache;

class NullCache implements CacheInterface
{
    /**
     * @param string $namespace
     * @return $this
     */
    public function setNamespace($namespace)
    {
        return $this;
    }

    /**
     * @param int $defaultTtl
     * @return $this
     */
    public function setDefaultTtl($defaultTtl)
    {
        return $this;
    }

    /**
     * @param string    $key
     * @param mixed    $data
     * @param null|int $ttl
     * @return bool
     */
    public function set($key, $data, $ttl = null)
    {
        return true;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        return true;
    }

    /**
     * @param $key
     * @return bool
     */
    public function exists($key)
    {
        return true;
    }

    /**
     * @param $key
     * @return \DateTime
     */
    public function expires($key)
    {
        return null;
    }

    /**
     * @param string   $key
     * @param callable $callback
     * @param int|null $ttl
     * @return null
     */
    public function remember($key, callable $callback, $ttl = null)
    {
        return $callback->__invoke();
    }

    /**
     * @param string $keyOrDirectory
     * @return bool
     */
    public function delete($keyOrDirectory)
    {
        return true;
    }

    /**
     * @return array
     */
    public function allKeys()
    {
        return [];
    }

    /**
     * @return bool
     */
    public function flush()
    {
        return true;
    }

    /**
     * @param $key
     * @return int
     */
    public function getTtl($key)
    {
        return null;
    }
}