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