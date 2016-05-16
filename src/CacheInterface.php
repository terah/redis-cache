<?php

namespace Terah\RedisCache;

interface CacheInterface
{
    /**
     * @param string $namespace
     * @return $this
     */
    public function setNamespace($namespace);

    /**
     * @param int $defaultTtl
     * @return $this
     */
    public function setDefaultTtl($defaultTtl);

    /**
     * @param string $key
     * @param mixed $data
     * @param null|int $ttl
     * @return bool
     */
    public function set($key, $data, $ttl=null);

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key);

    /**
     * @param $key
     */
    public function exists($key);

    /**
     * @param $key
     * @return \DateTime
     */
    public function expires($key);

    /**
     * @param string $key
     * @param callable $callback
     * @param int|null $ttl
     * @return null
     */
    public function remember($key, callable $callback, $ttl=null);

    /**
     * @param string $keyOrDirectory
     * @return bool
     */
    public function delete($keyOrDirectory);


    /**
     * @return array
     */
    public function allKeys();


    /**
     * @return bool
     */
    public function flush();

    /**
     * @param $key
     * @return int
     */
    public function getTtl($key);
}