<?php

namespace Terah\RedisCache\Test;

use Terah\RedisCache\RedisCache;

class RedisCacheTest extends \PHPUnit_Framework_TestCase
{
    /** @var RedisCache */
    protected $redisCache   = null;

    protected $key          = '/my-test-key';

    public function setUp()
    {
        $redis              = new \Redis();
        $redis->connect('127.0.0.1', 6379);
        $this->redisCache = new RedisCache($redis, 60 * 10, 'my_cache_test');
    }

    public function testSet()
    {
        $data           =  ['asdfasdf' => 'acdasdcasd', 'cadcadscads' => 'cacee'];
        $expiresAt      = time() + (60 * 30);
        $this->redisCache->set($this->key, $data, 60 * 30);
        $expires        = $this->redisCache->expires($this->key);
        $expires        = $expires->getTimestamp();
        $fetchedData    = $this->redisCache->get($this->key);
        $this->assertEquals($expiresAt, $expires);
        $this->assertEquals(json_encode($data), json_encode($fetchedData));
    }

    public function testGet()
    {
        $data           =  ['asdfasdf' => 'acdasdcasd', 'cadcadscads' => 'cacee'];
        $this->redisCache->set($this->key, $data, 60 * 30);
        $fetchedData   = $this->redisCache->get($this->key);
        $this->assertEquals(json_encode($data), json_encode($fetchedData));
    }

    public function testExists()
    {
        $data           =  ['asdfasdf' => 'acdasdcasd', 'cadcadscads' => 'cacee'];
        $this->redisCache->set($this->key, $data, 60 * 30);
        $exists         = $this->redisCache->exists($this->key);
        $this->assertTrue($exists);
    }

    public function testRemember()
    {
        $data           =  ['asdfasdf' => 'acdasdcasd', 'cadcadscads' => 'cacee'];
        $callback = function () use ($data) {
            return   $data;
        };
        $this->redisCache->remember($this->key, $callback, 60 * 10);
        $fetchedData   = $this->redisCache->remember($this->key, $callback, 60 * 10);
        $this->assertEquals(json_encode($data), json_encode($fetchedData));
    }

    public function testDelete()
    {
        $data           =  ['asdfasdf' => 'acdasdcasd', 'cadcadscads' => 'cacee'];
        $this->redisCache->set($this->key, $data, 60 * 30);
        $exists         = $this->redisCache->exists($this->key);
        $this->assertTrue($exists);
        $this->redisCache->delete($this->key);
        $exists         = $this->redisCache->exists($this->key);
        $this->assertFalse($exists);
    }

    public function testflush()
    {
        $data           =  ['asdfasdf' => 'acdasdcasd', 'cadcadscads' => 'cacee'];
        $this->redisCache->set($this->key, $data, 60 * 30);
        $exists         = $this->redisCache->exists($this->key);
        $this->assertTrue($exists);
        $this->redisCache->flush();
        $exists         = $this->redisCache->exists($this->key);
        $this->assertFalse($exists);
    }

    public function testHierarchicalKeys()
    {
        $data           = ['asdfasdf' => 'acdasdcasd', 'cadcadscads' => 'cacee'];
        $keys           = [
            '/asdf-asdc123/asdfccasd/asdfadsf',
            '/asdf-asdc123/asdfccasd/asdfadsf/casdcasdc',
            '/asdf-asdc123/asdfccasd/asdfadsf/zasde_-asd./asdfadsf',
            '/asdf-asdc123/asdfccasd/asdfadsf/asdfadsf-asdf-asdf',
        ];
        sort($keys);
        foreach ( $keys as $key )
        {
            $this->redisCache->set($key, $data, 600);
        }
        $allKeys    = $this->redisCache->allKeys();
        sort($allKeys);
        $this->assertEquals(json_encode($keys), json_encode($allKeys));
        $this->redisCache->delete('/asdf-asdc123/');
        $allKeys    = $this->redisCache->allKeys();
        sort($allKeys);
        $this->assertEquals(json_encode([]), json_encode($allKeys));
    }

    public function testExpires()
    {
        $data           =  ['asdfasdf' => 'acdasdcasd', 'cadcadscads' => 'cacee'];
        $this->redisCache->set($this->key, $data, 10);
        $exists         = $this->redisCache->exists($this->key);
        $this->assertTrue($exists);
        sleep(11);
        $exists         = $this->redisCache->exists($this->key);
        $this->assertFalse($exists);
    }

    public function tearDown()
    {
        $this->redisCache->flush();
    }
}