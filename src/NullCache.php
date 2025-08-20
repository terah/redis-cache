<?php declare(strict_types=1);

namespace Terah\RedisCache;

use DateTime;
use Closure;
use Psr\Log\LoggerInterface;

class NullCache implements CacheInterface
{
    protected ?LoggerInterface $logger = null;


    public function setNamespace(string $namespace) : CacheInterface
    {
        return $this;
    }


    public function setDefaultTtl(int $defaultTtl) : CacheInterface
    {
        return $this;
    }


    public function set(string $key, mixed $data, int $ttl=0) : bool
    {
        return true;
    }


    public function incr(string $key, int $ttl=3600) : int
    {
        return 0;
    }


    public function incrByFloat(string $key, float $value, int $ttl=3600) : float
    {
        return 0;
    }


    public function get(string $key, bool $stopLogging=false) : mixed
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


    public function allKeys(string $pattern='*') : array
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


    public function getRaw(string $key) : mixed
    {
        return null;
    }


    public function setLogger(LoggerInterface $logger) : CacheInterface
    {
        $this->logger           = $logger;

        return $this;
    }


    public function recordEvent(string $key, int $secondsWindow) : CacheInterface
    {
        return $this;
    }


    public function getEventCount(string $key, int $secondsWindow) : int
    {
        return 0;
    }
}
