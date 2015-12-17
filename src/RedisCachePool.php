<?php

namespace Terah\RedisCache;

use function Terah\Assert\Assert;
use Terah\ColourLog\Logger;
use Terah\ColourLog\LoggerTrait;
use Redis;

class RedisCachePool
{
    use LoggerTrait;
    /**
     * @var RedisCache[]
     */
    protected $caches       = [];

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
            Assert($conf)->keysExist(['default_ttl', 'global_flush']);

            $this->caches = [
                'cache'         => (new RedisCache($redis, $conf->default_ttl, $name))->setLogger($logger),
                'global_flush'  => $conf->global_flush,
            ];
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

}

//<?php
//
//namespace Terah\CachePools;
//
//use Psr\Log\NullLogger;
//use Terah\ColourLog\LoggerTrait;
//
//class CachePools {
//
//    use LoggerTrait;
//    /**
//     * @var \Pool[]
//     */
//    protected $pools    = [];
//
//    /**
//     * @var array
//     */
//    protected $config   = [];
//
//    /**
//     * @var \Interfaces\DriverInterface[]
//     */
//    protected $engines  = [];
//
//    /**
//     * @param array $config
//     *
//     * @throws \Exception
//     */
//    public function __construct(array $config=[])
//    {
//        $this->config  = $config;
//        $drivers        = \DriverList::getAllDrivers();
//        foreach ( $config as $name => $conf )
//        {
//            if ( empty($conf['engine']) )
//            {
//                continue;
//            }
//            if ( ! isset($drivers[$conf['engine']]) )
//            {
//                throw new \Exception("Invalid cache driver specified ({$conf['engine']})");
//            }
//            if ( empty($this->engines[$conf['engine']]) )
//            {
//                $driver_class                       = $drivers[$conf['engine']];
//                $this->engines[$conf['engine']]     = new $driver_class();
//                $this->engines[$conf['engine']]->setOptions($conf);
//                $this->config[$name]['duration']    = isset($conf['duration']) ? $conf['duration'] : null;
//            }
//            $this->pools[$name] = new \Pool($this->engines[$conf['engine']]);
//            $this->pools[$name]->setNamespace($name);
//            $this->logger       = new NullLogger();
//        }
//    }
//
//    public function __call($name, $arguments)
//    {
//        try
//        {
//            if ( ! $method = preg_match('/^(getIn|setIn|rememberIn)/', '', $name) )
//            {
//                throw new \Exception("Could not find a valid method in cache class ({$name})");
//            }
//            // MyPoolName becomes my_pool_name
//            $pool = strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', preg_replace('/^(getIn|setIn|rememberIn)/', '', $name)));
//            if ( !isset($this->pools[$pool]) )
//            {
//                throw new \Exception("Could not find a valid cache pool by the name ({$pool})");
//            }
//            $method = substr($method, 0, -2);
//            $method = "$this->{$method}";
//            $arguments[] = $pool;
//            return call_user_func_array($method, $arguments);
//        }
//        catch (\Exception $e)
//        {
//            $this->logger->error($e->getMessage());
//            return false;
//        }
//    }
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
//
//    /**
//     * @param string $pool
//     *
//     * @return bool
//     * @throws \Exception
//     */
//    public function wipe($pool='default')
//    {
//        try
//        {
//            $pools = is_null($pool) ? $this->pools : [$this->getPool($pool)];
//            foreach ( $pools as $cachePool )
//            {
//                $cachePool->flush();
//            }
//            return true;
//        }
//        catch (\Exception $e)
//        {
//            $this->logger->error($e->getMessage());
//            return false;
//        }
//    }
//
//    /**
//     * @param        $name
//     * @param string $pool
//     *
//     * @return bool
//     * @throws \Exception
//     */
//    public function delete($name, $pool='default')
//    {
//        try
//        {
//            return $this->getPool($pool)->getItem($name)->clear();
//        }
//        catch (\Exception $e)
//        {
//            $this->logger->error($e->getMessage());
//            return false;
//        }
//    }
//
//    /**
//     * Set the key with the result of a callback if no cache hit
//     *
//     * @param string  $name
//     * @param callable  $func
//     * @param string    $pool
//     * @param \DateTime|int|string $ttl
//     * @return mixed
//     */
//    public function remember($name, callable $func, $pool='default', $ttl=null)
//    {
//        $data = $this->get($name, $pool);
//        if ( ! is_null($data) && $data !== false )
//        {
//            $this->logger->debug("Cache hit on key {$name}");
//            return $data;
//        }
//        $this->logger->debug("Cache miss on key {$name}");
//        $data = $func->__invoke();
//        try
//        {
//            if ( ! $this->set($name, $data, $pool, $ttl) )
//            {
//                throw new \Exception("Could not save data to cache");
//            }
//            return $data;
//        }
//        catch (\Exception $e)
//        {
//            $this->logger->error($e->getMessage());
//            return false;
//        }
//    }
//
//    /**
//     * @param string $pool
//     * @returns \\Pool
//     * @throws \Exception
//     */
//    public function getPool($pool='default')
//    {
//        if ( empty($this->pools[$pool]) && $pool === 'default' )
//        {
//            reset($this->pools);
//            $pool = key($this->pools);
//        }
//        if ( !empty($this->pools[$pool]) )
//        {
//            return $this->pools[$pool];
//        }
//        throw new \Exception("Could not load cache pool by name ({$pool})");
//    }
//}
