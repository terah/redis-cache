<?php

namespace Terah\RedisCache;

use function Terah\Assert\Assert;
use Terah\ColourLog\Logger;
use Terah\ColourLog\LoggerTrait;
use Redis;

/**
 * Class RedisCachePool
 *
 * @package Terah\RedisCache
 * @method RedisCache setNamespace(string $namespace)
 * @method RedisCache setDefaultTtl(int $defaultTtl)
 * @method bool set(string $key, mixed $data, int $ttl=null)
 * @method mixed get(string $key)
 * @method bool exists(string $key)
 * @method \DateTime expires(string $key)
 * @method mixed remember(string $key, callable $callback, int $ttl=null)
 * @method bool delete(string $keyOrDirectory)
 * @method string[] allKeys()
 * @method bool flush()
 */
class RedisCachePool
{
    use LoggerTrait;
    /**
     * @var RedisCache[]
     */
    protected $caches       = [];

    /** @var string[] */
    protected $globalFlush  = [];
    /**
     * RedisCachePool constructor.
     *
     * @param array  $config
     * @param Redis $redis
     * @param Logger $logger
     */
    public function __construct(array $config=[], Redis $redis, Logger $logger)
    {
        $this->setLogger($logger);
        foreach ( $config as $name => $conf )
        {
            $conf = (object)$conf;
            Assert($conf)->propertiesExist(['default_ttl', 'global_flush']);

            $this->caches[$name]        = (new RedisCache($redis, $conf->default_ttl, $name))->setLogger($logger);
            $this->globalFlush[$name]   = $conf->global_flush;
        }
    }

    /**
     * @param string $cache
     * @returns RedisCache
     * @throws \Exception
     */
    public function getCache($cache='default')
    {
        Assert($this->caches)->keyExists($cache, "Could not load cache pool by name ({$cache})");
        return $this->caches[$cache];
    }

    /**
     * @return bool
     */
    public function wipe()
    {
        foreach ( $this->globalFlush as $name => $doFlush )
        {
            if ( $doFlush && array_key_exists($name, $this->caches) && ! empty( $this->caches[$name] ) )
            {
                $this->caches[$name]->flush();
            }
        }
        return true;
    }

    /**
     * @param       $method
     * @param array $arguments
     * @return mixed
     */
    public function __call($method, array $arguments)
    {
        return call_user_func_array(array($this->getCache('default'), $method), $arguments);
    }


}



//

//
//    /**
//     * @param string $name
//     * @param string $pool
//     *
//     * @return \Interfaces\ItemInterface
//     */
//    public function get($name, $pool='default')
//    {
//        try
//        {
//            return $this->getPool($pool)->getItem($name)->get();
//        }
//        catch (\Exception $e)
//        {
//            $this->logger->error($e->getMessage());
//            return false;
//        }
//    }
//
//    /**
//     * @param           $name
//     * @param           $value
//     * @param string    $pool
//     * @param \DateTime|int|string $ttl
//     *
//     * @return bool
//     * @throws \Exception
//     */
//    public function set($name, $value, $pool='default', $ttl=null)
//    {
//        try
//        {
//            if ( is_null($ttl) )
//            {
//                $ttl = is_null($this->config[$pool]['duration']) ? null : new \DateTime($this->config[$pool]['duration']);
//            }
//            $ttl = ! is_string($ttl) ? $ttl : new \DateTime($ttl);
//            return $this->getPool($pool)->getItem($name)->set($value, $ttl);
//        }
//        catch (\Exception $e)
//        {
//            $this->logger->error($e->getMessage());
//            return false;
//        }
//    }

