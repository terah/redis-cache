<?php declare(strict_types=1);

namespace Terah\RedisCache;

use Psr\Log\LoggerInterface;
use DateTime;
use Closure;


interface CacheInterface
{

    public function setNamespace(string $namespace) : CacheInterface;


    public function setDefaultTtl(int $defaultTtl) : CacheInterface;


    public function set(string $key, mixed $data, int $ttl=0) : bool;


    public function get(string $key, bool $stopLogging=false) : mixed;


    public function exists(string $key) : bool;


    public function expires(string $key) : DateTime;


    public function remember(string $key, Closure $callback, int $ttl=0, bool $stopLogging=false);


    public function delete(string $keyOrDirectory) : bool;


    public function allKeys(string $pattern='*') : array;


    public function flush() : bool;


    public function getTtl(string $key) : int;


    public function incr(string $key, int $ttl=3600) : int;


    public function incrByFloat(string $key, float $value, int $ttl=3600) : float;


    public function getRaw(string $key) : mixed;


    public function setLogger(LoggerInterface $logger) : CacheInterface;


    public function recordEvent(string $key, int $secondsWindow) : CacheInterface;


    public function getEventCount(string $key, int $secondsWindow) : int;
}
