<?php declare(strict_types=1);

namespace Terah\RedisCache;

use Closure;
use DateTime;
use Psr\Log\LoggerInterface as Logger;

class NullCache implements CacheInterface
{
    /** @var Logger */
    protected $logger;


    public function setNamespace(string $namespace) : CacheInterface
    {
        return $this;
    }


    public function setDefaultTtl(int $defaultTtl) : CacheInterface
    {
        return $this;
    }

    /**
     * @param string    $key
     * @param mixed    $data
     * @param int $ttl
     * @return bool
     */
    public function set(string $key, $data, int $ttl=0) : bool
    {
        return true;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get(string $key, bool $stopLogging=false)
    {
        return true;
    }


    public function exists(string $key) : bool
    {
        return true;
    }


    public function expires(string $key) : DateTime
    {
        return new DateTime();
    }


    public function remember(string $key, Closure $callback, int $ttl=0, bool $stopLogging=false)
    {
        return $callback->__invoke();
    }


    public function delete(string $keyOrDirectory) : bool
    {
        return true;
    }


    public function allKeys() : array
    {
        return [];
    }


    public function flush() : bool
    {
        return true;
    }


    public function getTtl(string $key) : int
    {
        return 0;
    }


    public function setLogger(Logger $logger) : CacheInterface
    {
        $this->logger           = $logger;

        return $this;
    }
}
