<?php declare(strict_types=1);

namespace Terah\RedisCache;

interface CacheInterface
{
    /**
     * @param string $namespace
     * @return CacheInterface
     */
    public function setNamespace(string $namespace) : CacheInterface;

    /**
     * @param int $defaultTtl
     * @return CacheInterface
     */
    public function setDefaultTtl(int $defaultTtl) : CacheInterface;

    /**
     * @param string $key
     * @param mixed $data
     * @param int $ttl
     * @return bool
     */
    public function set(string $key, $data, int $ttl=0) : bool;

    /**
     * @param string $key
     * @return mixed
     */
    public function get(string $key);

    /**
     * @param string $key
     * @return bool
     */
    public function exists(string $key) : bool;

    /**
     * @param $key
     * @return \DateTime
     */
    public function expires(string $key) : \DateTime;

    /**
     * @param string $key
     * @param \Closure $callback
     * @param int $ttl
     * @return mixed
     */
    public function remember(string $key, \Closure $callback, int $ttl=0);

    /**
     * @param string $keyOrDirectory
     * @return bool
     */
    public function delete(string $keyOrDirectory) : bool;


    /**
     * @return array
     */
    public function allKeys() : array;


    /**
     * @return bool
     */
    public function flush() : bool;

    /**
     * @param $key
     * @return int
     */
    public function getTtl(string $key) : int;
}
