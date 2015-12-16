<?php

namespace Terah\RedisCache\Test;

use Terah\RedisCache\RedisCache;

class RedisCacheTest
{
    /** @var RedisCache */
    protected $redisCache = null;

    public function setUp()
    {
        $redis              = new \Redis();
        $redis->connect('127.0.0.1', 6379);
        $this->redisCache = new RedisCache($redis, 60 * 10, '/my_cache_test/');
    }

    public function setTest()
    {
        $this->redisCache->set('my-test-key', ['asdf' => 'asdf'], 60 * 30);
    }

    public function getTest()
    {
        $data = $this->redisCache->get('my-test-key');
    }

    public function existsTest()
    {
        $exists = $this->redisCache->exists('my-test-key');
    }

    public function rememberTest()
    {
        $data = $this->redisCache->remember('my-test-key', function() { return ['asdf' => 'asdf']; }, 60 * 10);
    }

    public function testDelete()
    {
        $this->redisCache->delete('my-test-key');
    }

    public function testFlushCache()
    {
        $this->redisCache->flushCache('my-test-key');
    }
}