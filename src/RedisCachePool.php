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
 * @method CacheInterface setNamespace(string $namespace)
 * @method CacheInterface setDefaultTtl(int $defaultTtl)
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
     * @var CacheInterface[]
     */
    protected $caches       = [];

    /** @var string[] */
    protected $globalFlush  = [];
    /**
     * RedisCachePool constructor.
     *
     * @param array  $config
     * @param Redis[] $redisServers
     * @param Logger $logger
     */
    public function __construct(array $config=[], array $redisServers, Logger $logger)
    {
        $this->setLogger($logger);

        $servers            = [
            'read'              => [],
            'write'             => [],
            'delete'            => [],
            'all'               => [],
        ];
        foreach ( $redisServers['hosts'] as $type => $hosts )
        {
            foreach ( $hosts as $host )
            {
                $parts      = explode(':', $host);
                $hostname   = $parts[0];
                $port       = isset($parts[1]) ? $parts[1] : $redisServers['port'];
                $timeout    = $redisServers['timeout'];
                $password   = $redisServers['password'];
                if ( ! array_key_exists($host, $servers['all']) )
                {
                    $redis                  = new Redis;
                    $redis->pconnect($hostname, $port, $timeout);
                    $redis->auth($password);
                    $servers['all'][$host]  = $redis;
                }
                $servers[$type][$host] = $servers['all'][$host];
            }
        }

        foreach ( $config as $name => $conf )
        {
            $conf = (object)$conf;
            Assert($conf)->propertiesExist(['default_ttl', 'global_flush']);

            $this->caches[$name]        = (new RedisCache($servers, $conf->default_ttl, $name))->setLogger($logger);
            $this->globalFlush[$name]   = $conf->global_flush;
        }
    }

    /**
     * @param string $cache
     * @returns CacheInterface
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
