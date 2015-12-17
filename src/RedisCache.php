<?php

namespace Terah\RedisCache;

use function Terah\Assert\Assert;

class RedisCache
{
    protected $redisClient  = null;

    protected $defaultTtl   = null;

    protected $namespace    = null;

    /**
     * RedisCache constructor.
     * @param \Redis $redisClient
     * @param null $defaultTtl
     * @param null $namespace
     */
    public function __construct(\Redis $redisClient, $defaultTtl=null, $namespace=null)
    {
        $this->redisClient  = $redisClient;
        $this->setDefaultTtl($defaultTtl);
        $this->setNamespace($namespace);
    }

    /**
     * @param string $namespace
     * @return $this
     */
    public function setNamespace($namespace)
    {
        Assert($namespace)
            ->nullOr('Namespace must be null or alphanumeric with /_-. characters and start/end in a trailing slash')
            ->regex('/^\/[a-z0-9/_-.]+\/$/', 'Namespace must be null or alphanumeric with /_-. characters and start/end in a trailing slash');
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * @param int $defaultTtl
     * @return $this
     */
    public function setDefaultTtl($defaultTtl)
    {
        Assert($defaultTtl)
            ->int('Default ttl must be an int between 1 and 315360000')
            ->range(1, 315360000, 'Default ttl must be an int between 1 and 315360000'); // Max 10 years..
        $this->defaultTtl = $defaultTtl;
        return $this;
    }

    /**
     * @param string $key
     * @param mixed $data
     * @param null|int $ttl
     * @return bool
     */
    public function set($key, $data, $ttl=null)
    {
        $ttl            = $this->_getTtl($ttl);
        $key            = $this->_formatKey($key);
        $expiration     = strtotime('+' . $ttl . ' seconds');
        $data           = serialize(['data' => $data, 'expiration' => $expiration]);

        return $this->redisClient->setEx($key, $ttl, $data);
    }

    /**
     * @param string $key
     * @return null
     */
    public function get($key)
    {
        $key    = $this->_formatKey($key);
        $data   = $this->redisClient->get($key);

        return array_key_exists('data', $data) ? $data['data'] : null;
    }

    public function exists($key)
    {
        $key    = $this->_formatKey($key);

        return $this->redisClient->exists($key);
    }

    /**
     * @param string $key
     * @param callable $callback
     * @param int|null $ttl
     * @return null
     */
    public function remember($key, callable $callback, $ttl=null)
    {
        $ttl    = $this->_getTtl($ttl);
        $key    = $this->_formatKey($key);
        $data   = $this->get($key);
        if ( ! is_null($data) )
        {
            return $data;
        }
        $data   = $callback->__invoke();
        if ( is_null($data) )
        {
            return null;
        }
        $this->set($key, $data, $ttl);

        return $data;
    }

    /**
     * @param string $keyOrDirectory
     * @return bool
     */
    public function delete($keyOrDirectory)
    {
        $keyOrDirectory    = $this->_formatKey($keyOrDirectory, true);
        // Is the is 'directory' of keys? Match and delete
        if ( ! preg_match('/\/$/', $keyOrDirectory) )
        {
            $this->redisClient->delete($keyOrDirectory);
            return true;
        }
        $keys = $this->redisClient->keys($keyOrDirectory);
        foreach ( $keys as $key )
        {
            $this->redisClient->delete($key);
        }

        return true;
    }

    /**
     * @return bool
     */
    public function flushCache()
    {
        $keys = $this->redisClient->keys($this->namespace);
        foreach ( $keys as $key )
        {
            $this->redisClient->delete($key);
        }

        return true;
    }

    /**
     * @param string $key
     * @param bool $allowDirectory
     * @return string
     */
    protected function _formatKey($key, $allowDirectory=false)
    {
        Assert($key)->regex('/^[a-z0-9/-._]$/', 'Invalid key format specified');
        if ( ! $allowDirectory )
        {
            Assert($key)->notRegex('/\/$/', 'Keys cannot end in a / character');
        }

        return $this->namespace . $key;
    }

    /**
     * @param null|int $ttl
     * @return null
     */
    protected function _getTtl($ttl)
    {
        $ttl = ! is_null($ttl) ? $ttl : $this->defaultTtl;
        Assert($ttl)->int()->range(1, 315360000); // Max 10 years..
        return $ttl;
    }


}