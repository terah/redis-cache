<?php


namespace Terah\RedisCache;

use function Terah\Assert\Assert;

class RedisCache
{
    protected $redisClient  = null;

    protected $defaultTtl   = null;

    public function __construct(\Redis $redisClient, $defaultTtl=null)
    {
        $this->redisClient  = $redisClient;
        $this->defaultTtl   = $defaultTtl;
    }

    public function set($key, $data, $ttl=null)
    {
        Assert($key)->string()->notEmpty();
        Assert($ttl)->int()->range(1, 315360000); // Max 10 years..
        // todo: check strtotime can handle this...
        $expiration = strtotime('+' . $ttl . ' seconds');
        $data       = serialize(['data' => $data, 'expiration' => $expiration]);
        return $this->redisClient->setEx($key, $ttl, $data);
    }

    public function get($key)
    {
        $data = $this->redisClient->get($key);
        return array_key_exists('data', $data) ? $data['data'] : null;
    }

    public function exists($key)
    {
        return $this->redisClient->exists($key);
    }

    public function remember($key, $callback, $ttl=null)
    {
        $data = $this->get($key);
        if ( ! is_null($data) )
        {
            return $data;
        }
        $data = $callback->__invoke();
        if ( is_null($data) )
        {
            return null;
        }
        $this->set($key, $data, $ttl);
        return $data;
    }

    public function delete($key)
    {
        return $this->redisClient->del($key);
    }

}