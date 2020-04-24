<?php declare(strict_types=1);

namespace Terah\RedisCache;

use Closure;
use Terah\Asrt\Asrt;
use Redis;
use DateTime;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class RedisCache
 *
 * @package Terah\RedisCache
 */
class RedisCache implements CacheInterface
{
    /** @var Logger */
    protected $logger;

    /** @var Redis[]  */
    protected $redisClients     = [];

    /** @var int  */
    protected $defaultTtl       = 0;

    /** @var string  */
    protected $namespace        = '';


    public function setLogger(Logger $logger=null) : CacheInterface
    {
        $this->logger           = $logger;

        return $this;
    }


    public function getLogger() : Logger
    {
        return $this->logger;
    }


    /**
     * RedisCache constructor.
     * @param Redis[] $redisClients
     * @param int $defaultTtl
     * @param string $namespace
     */
    public function __construct(array $redisClients, int $defaultTtl=0, string $namespace='')
    {
        $this->redisClients     = $redisClients;
        $this->setDefaultTtl($defaultTtl);
        $this->setNamespace($namespace);
    }


    public function setNamespace(string $namespace) : CacheInterface
    {
        Asrt::that($namespace)
            ->nullOr()
            ->regex('/^[a-z0-9_-]+$/', 'Namespace must be null or alphanumeric with _- characters');
        $this->namespace        = empty($namespace) ? '' : $namespace . ':::';

        return $this;
    }


    public function setDefaultTtl(int $defaultTtl) : CacheInterface
    {
        Asrt::that($defaultTtl)
            ->isInt('Default ttl must be an int between 1 and 315360000')
            ->range(1, 315360000, 'Default ttl must be an int between 1 and 315360000'); // Max 10 years..
        $this->defaultTtl       = $defaultTtl;

        return $this;
    }


    public function set(string $key, $data, int $ttl=0) : bool
    {
        $ttl                    = $this->_getTtl($ttl);
        $key                    = $this->_formatKey($key);
        $expiration             = strtotime('+' . $ttl . ' seconds');
        $data                   = serialize(['data' => $data, 'expiration' => $expiration]);

        foreach ( $this->redisClients['write'] as $client )
        {
            /** @var Redis $client */
            $client->setex($key, $ttl, $data);
        }

        return true;
    }


    public function get(string $key, bool $stopLogging=false)
    {
        $key                    = $this->_formatKey($key);
        // todo: Only supporting one read client at this time.
        foreach ( $this->redisClients['read'] as $client )
        {
            /** @var Redis $client */
            $data                   = $client->get($key);
            $data                   = unserialize((string)$data);

            if ( is_array($data) && array_key_exists('data', $data) )
            {
                if ( ! $stopLogging ) $this->_logAction("Cache hit on key {$key}");

                return $data['data'];
            }
            if ( ! $stopLogging )  $this->_logAction("Cache miss on key {$key}");

            return null;
        }

        return null;
    }


    public function exists(string $key) : bool
    {
        $key                    = $this->_formatKey($key);
        // todo: Only supporting one read client at this time.
        foreach ( $this->redisClients['read'] as $client )
        {
            /** @var Redis $client */
            return (bool)$client->exists($key);
        }

        return false;
    }


    public function expires(string $key) : DateTime
    {
        $key                    = $this->_formatKey($key);
        // todo: Only supporting one read client at this time.
        foreach ( $this->redisClients['read'] as $client )
        {
            /** @var Redis $client */
            $ttl                    = $client->ttl($key);

            return (new DateTime)->setTimestamp(time() + $ttl);
        }

        return (new DateTime);
    }


    public function remember(string $key, Closure $callback, int $ttl=0, bool $stopLogging=false)
    {
        $ttl                    = $this->_getTtl($ttl);
        $data                   = $this->get($key, $stopLogging);
        if ( ! is_null($data) )
        {
            return $data;
        }
        $data                   = $callback->__invoke();
        if ( is_null($data) )
        {
            return null;
        }
        $this->set($key, $data, $ttl);

        return $data;
    }


    public function delete(string $keyOrDirectory) : bool
    {
        $keyOrDirectory    = $this->_formatKey($keyOrDirectory, true);
        // Is the is 'directory' of keys? Match and delete

        if ( ! preg_match('/\/$/', $keyOrDirectory) )
        {
            foreach ( $this->redisClients['delete'] as $client )
            {
                /** @var Redis $client */
                $client->del($keyOrDirectory);
            }

            return true;
        }
        $count                  = 0;
        foreach ( $this->redisClients['delete'] as $client )
        {
            /** @var Redis $client */
            $keys                   = $client->keys($keyOrDirectory . '*');
            $count                  = 0;
            foreach ( $keys as $key )
            {
                $client->del($key);
                $count++;
            }
        }
        $this->_logAction("Cache delete on key: {$keyOrDirectory} ({$count} keys deleted)");

        return true;
    }


    public function allKeys() : array
    {
        // todo: Only supporting one read client at this time.
        foreach ( $this->redisClients['read'] as $client )
        {
            /** @var Redis $client */
            $keys                   = $client->keys($this->namespace . '*');
            if ( empty($this->namespace) )
            {
                return $keys;
            }
            $namespaceLen           = strlen($this->namespace);
            foreach ( $keys as $idx => $key )
            {
                $keys[$idx]             = substr($key, $namespaceLen);
            }

            return $keys;
        }

        return [];
    }


    public function flush() : bool
    {
        foreach ( $this->redisClients['delete'] as $client )
        {
            /** @var Redis $client */
            $keys                   = $client->keys($this->namespace . '*');
            foreach ( $keys as $key )
            {
                $client->del($key);
            }
        }

        return true;
    }


    public function getTtl(string $key) : int
    {
        $key                    = $this->_formatKey($key);
        // todo: Only supporting one read client at this time.
        foreach ( $this->redisClients['read'] as $client )
        {
            /** @var Redis $client */
            return $client->ttl($key);
        }

        return 0;
    }


    protected function _formatKey(string $key, bool $allowDirectory=false) : string
    {
        $regex                  = '@^/[a-zA-Z0-9.:_-]+((/[a-zA-Z0-9.:_-]+)*)$@';
        $errorMessage           = "The set key format must be in a directory like structure i.e '/dirname/dirname/dirname' where dirname is alphanumeric and ._- character'. %s given";
        if ( $allowDirectory )
        {
            $regex                  = '@^/[a-zA-Z0-9.:_-]+((/[a-zA-Z0-9.:_-]+)*)(/|)$@';
            $errorMessage           = "The set key format must be in a directory like structure i.e '/dirname/dirname/dirname' where dirname is alphanumeric and ._- character'. %s given";
        }
        Asrt::that($key)->notEmpty()->regex($regex, $errorMessage);

        return $this->namespace . $key;
    }


    protected function _getTtl(int $ttl) : int
    {
        $ttl                    = $ttl ?: $this->defaultTtl;
        Asrt::that($ttl)->isInt()->range(1, 315360000); // Max 10 years..

        return $ttl;
    }


    protected function _logAction(string $message) : bool
    {
        if ( ! $this->logger )
        {
            return true;
        }
        $this->logger->debug($message);

        return true;
    }

}
