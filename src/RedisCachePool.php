<?php declare(strict_types=1);

namespace Terah\RedisCache;

use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Terah\Assert\Assert;
use Redis;

/**
 * Class RedisCachePool
 *
 * @package Terah\RedisCache
 * @method CacheInterface setNamespace(string $namespace)
 * @method CacheInterface setDefaultTtl(int $defaultTtl)
 * @method bool set(string $key, $data, int $ttl=null)
 * @method mixed get(string $key, bool $stopLogging=false)
 * @method bool exists(string $key)
 * @method DateTime expires(string $key)
 * @method mixed remember(string $key, callable $callback, int $ttl=null)
 * @method bool delete(string $keyOrDirectory)
 * @method string[] allKeys(string $pattern='*')
 * @method bool flush()
 * @method int incr(string $key, int $ttl=3600)
 * @method float incrByFloat(string $key, int $ttl=3600)
 */
class RedisCachePool
{
    protected ?LoggerInterface $logger = null;

    /** @var CacheInterface[] */
    protected array $caches         = [];

    /** @var string[] */
    protected array $globalFlush    = [];


    public function setLogger(LoggerInterface $logger) : RedisCachePool
    {
        $this->logger           = $logger;
        foreach ( $this->caches as $name => $cache )
        {
            $cache->setLogger($logger);
        }

        return $this;
    }


    public function getLogger() : LoggerInterface
    {
        return $this->logger;
    }

    /**
     * RedisCachePool constructor.
     *
     * @param array  $config
     * @param Redis[][] $redisServers
     * @param LoggerInterface $logger
     * @param string $env
     */
    public function __construct(array $config, array $redisServers, LoggerInterface $logger, string $env)
    {
        $this->setLogger($logger);

        $servers                = [
            'read'                  => [],
            'write'                 => [],
            'delete'                => [],
            'all'                   => [],
        ];
        $failedServers          = [];
        foreach ( $redisServers['hosts'] as $type => $hosts )
        {
            foreach ( $hosts as $host )
            {
                $parts                  = explode(':', $host);
                $hostname               = $parts[0];
                $port                   = isset($parts[1]) ? $parts[1] : $redisServers['port'];
                $timeout                = $redisServers['timeout'];
                $password               = $redisServers['password'];
                $context                = $redisServers['context'];
                $username               = $redisServers['username'];
                $tls                    = $redisServers['tls'] ?? false;
                $tls                    = $tls ? 'tls://' : '';
                try
                {
                    if ( ! in_array("{$hostname}:{$port}", $failedServers) )
                    {
                        if ( ! array_key_exists($host, $servers['all']) )
                        {
                            $redis                  = new Redis;
                            $redis->pconnect($tls.$hostname, (int)$port, $timeout, null, 0, 0, $context);
                            if ( $password )
                            {
                                $credentials           = $username ? [$username => $password] : $password;
                                $redis->auth($credentials);
                            }
                            $servers['all'][$host]  = $redis;
                        }
                        $servers[$type][$host]  = $servers['all'][$host];
                    }
                }
                catch ( Exception $e )
                {
                    $failedServers[] = "{$hostname}:{$port}";
                    $this->logger->error($e->getMessage(), compact('hostname', 'port', 'timeout'));
                }
            }
        }
        foreach ( ['read', 'write', 'delete'] as $type )
        {
            if ( ! empty($redisServers['hosts'][$type]) && empty($servers[$type]) )
            {
                $this->logger->error("There are no {$type} redis servers online.", ['config' => $redisServers, 'online' => $servers]);

                throw new Exception("There are no {$type} redis servers online.");
            }
        }

        foreach ( $config as $name => $conf )
        {
            $conf               = (object)$conf;
            Assert::that($conf)->propertiesExist(['default_ttl', 'global_flush']);

            $this->caches[$name]        = (new RedisCache($servers, $conf->default_ttl, $name, $env))->setLogger($logger);
            $this->globalFlush[$name]   = $conf->global_flush;
        }
    }


    public function getCache(string $cache='default') : CacheInterface
    {
        Assert::that($this->caches)->keyExists($cache, "Could not load cache pool by name ({$cache})");

        return $this->caches[$cache];
    }


    public function wipe() : bool
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


    public function __call(string $method, array $arguments)
    {
        return call_user_func_array([$this->getCache(), $method], $arguments);
    }

}
