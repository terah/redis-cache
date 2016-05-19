<?php

namespace Terah\RedisCache;

use function Terah\Assert\Assert;
use Terah\ColourLog\LoggerTrait;

class RedisCache implements CacheInterface
{
    use LoggerTrait;

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
            ->nullOr('Namespace must be null or alphanumeric with _- characters')
            ->regex('/^[a-z0-9_-]+$/', 'Namespace must be null or alphanumeric with _- characters');
        $this->namespace = empty($namespace) ? '' : $namespace . ':::';
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

        return $this->redisClient->setex($key, $ttl, $data);
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        $key    = $this->_formatKey($key);
        $data   = $this->redisClient->get($key);
        $data   = unserialize($data);

        if ( is_array($data) && array_key_exists('data', $data) )
        {
            $this->_logAction("Cache hit on key {$key}");
            return $data['data'];
        }
        $this->_logAction("Cache miss on key {$key}");
        return null;
    }

    public function exists($key)
    {
        $key    = $this->_formatKey($key);

        return $this->redisClient->exists($key);
    }

    /**
     * @param $key
     * @return \DateTime
     */
    public function expires($key)
    {
        $key    = $this->_formatKey($key);

        $ttl    = $this->redisClient->ttl($key);

        return (new \DateTime)->setTimestamp(time() + $ttl);
    }

    /**
     * @param string $key
     * @param \Closure $callback
     * @param int|null $ttl
     * @return null
     */
    public function remember($key, \Closure $callback, $ttl=null)
    {
        $ttl    = $this->_getTtl($ttl);
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
        $keys           = $this->redisClient->keys($keyOrDirectory . '*');
        $count          = 0;
        foreach ( $keys as $key )
        {
            $this->redisClient->delete($key);
            $count++;
        }
        $this->_logAction("Cache delete on key: {$keyOrDirectory} ({$count} keys deleted)");
        return true;
    }

    /**
     * @return array
     */
    public function allKeys()
    {
        $keys = $this->redisClient->keys($this->namespace . '*');
        if ( empty($this->namespace) )
        {
            return $keys;
        }
        $namespaceLen   = strlen($this->namespace);
        foreach ( $keys as $idx => $key )
        {
            $keys[$idx] = substr($key, $namespaceLen);
        }
        return $keys;
    }

    /**
     * @return bool
     */
    public function flush()
    {
        $keys = $this->redisClient->keys($this->namespace . '*');
        foreach ( $keys as $key )
        {
            $this->redisClient->delete($key);
        }

        return true;
    }

    /**
     * @param $key
     * @return int
     */
    public function getTtl($key)
    {
        $key    = $this->_formatKey($key);
        return $this->redisClient->ttl($key);
    }

    /**
     * @param string $key
     * @param bool $allowDirectory
     * @return string
     */
    protected function _formatKey($key, $allowDirectory=false)
    {
        $regex          = '@^/[a-zA-Z0-9._-]+((/[a-zA-Z0-9._-]+)*)$@';
        $errorMessage   = "The set key format must be in a directory like structure i.e '/dirname/dirname/dirname' where dirname is alphanumeric and ._- character'. %s given";
        if ( $allowDirectory )
        {
            $regex          = '@^/[a-zA-Z0-9._-]+((/[a-zA-Z0-9._-]+)*)(/|)$@';
            $errorMessage   = "The set key format must be in a directory like structure i.e '/dirname/dirname/dirname' where dirname is alphanumeric and ._- character'. %s given";
        }
        Assert($key)->notEmpty()->regex($regex, $errorMessage);
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

    /**
     * @param string $message
     * @return bool|null
     */
    protected function _logAction($message)
    {
        if ( ! $this->logger )
        {
            return true;
        }
        return $this->logger->debug($message);
    }

}