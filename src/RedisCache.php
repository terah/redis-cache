<?php declare(strict_types=1);

namespace Terah\RedisCache;

use Terah\Assert\Assert;
use Redis;
use DateTime;
use Closure;
use Psr\Log\LoggerInterface;


class RedisCache implements CacheInterface
{
    protected ?LoggerInterface $logger = null;


    public function setLogger(LoggerInterface $logger) : CacheInterface
    {
        $this->logger           = $logger;

        return $this;
    }


    public function getLogger() : LoggerInterface
    {
        return $this->logger;
    }

    /** @var Redis[][]  */
    protected array $redisClients = [];

    protected int $defaultTtl   = 0;

     protected string $namespace = '';

    protected string $env       = '';

    /**
     * RedisCache constructor.
     * @param Redis[][] $redisClients
     * @param int $defaultTtl
     * @param string $namespace
     * @param string $env
     */
    public function __construct(array $redisClients, int $defaultTtl=0, string $namespace='', string $env='')
    {
        $this->redisClients     = $redisClients;
        $this->setDefaultTtl($defaultTtl);
        $this->setNamespace($namespace);
        $this->setEnv($env);
    }


    public function setNamespace(string $namespace) : CacheInterface
    {
        Assert::that($namespace)
            ->nullOr()
            ->regex('/^[a-z0-9_-]+$/', 'Namespace must be null or alphanumeric with _- characters');
        $this->namespace        = empty($namespace) ? '' : $namespace . ':::';

        return $this;
    }


    public function setEnv(string $env) : CacheInterface
    {
        Assert::that($env)
            ->nullOr()
            ->regex('/^[a-z0-9_-]+$/', 'Env must be null or alphanumeric with _- characters');
        $this->env              = empty($env) ? '' : $env . ':::';

        return $this;
    }


    public function setDefaultTtl(int $defaultTtl) : CacheInterface
    {
        Assert::that($defaultTtl)
            ->int('Default ttl must be an int between 1 and 315360000')
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


    public function incr(string $key, int $ttl=3600) : int
    {
        $key                    = $this->_formatKey($key);
        $result                 = 0;
        foreach ( $this->redisClients['write'] as $client )
        {
            $result                 = $client->incr($key);
            $client->expire($key, $ttl);
        }

        return $result;
    }


    public function incrByFloat(string $key, float $value, int $ttl=3600) : float
    {
        $key                    = $this->_formatKey($key);
        $result                 = 0;
        foreach ( $this->redisClients['write'] as $client )
        {
            $result                 = $client->incrByFloat($key, $value);
            $client->expire($key, $ttl);
        }

        return $result;
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


    public function getRaw(string $key)
    {
        $key                    = $this->_formatKey($key);
        // todo: Only supporting one read client at this time.
        foreach ( $this->redisClients['read'] as $client )
        {
            /** @var Redis $client */
            $data                   = $client->get($key);

            if ( $data )
            {
                $this->_logAction("Cache hit on key {$key}");

                return $data;
            }
            $this->_logAction("Cache miss on key {$key}");
        }

        return null;
    }


    public function exists(string $key) : bool
    {
        $key                    = $this->_formatKey($key);
        // todo: Only supporting one read client at this time.
        foreach ( $this->redisClients['read'] as $client )
        {
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
        $prefix                 = "{$this->env}{$this->namespace}";
        // todo: Only supporting one read client at this time.
        foreach ( $this->redisClients['read'] as $client )
        {
            /** @var Redis $client */
            $keys                   = $client->keys($prefix . '*');
            if ( empty($prefix) )
            {
                return $keys;
            }
            $namespaceLen           = strlen($prefix);
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
        $prefix                 = "{$this->env}{$this->namespace}";
        foreach ( $this->redisClients['delete'] as $client )
        {
            $keys                   = $client->keys($prefix . '*');
            foreach ( $keys as $key )
            {
                $client->del($key);
            }
        }

        return true;
    }


    public function getTtl(string $key) : int
    {
        $key    = $this->_formatKey($key);
        // todo: Only supporting one read client at this time.
        foreach ( $this->redisClients['read'] as $client )
        {
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
        Assert::that($key)->notEmpty()->regex($regex, $errorMessage);

        return "{$this->env}{$this->namespace}{$key}";
    }


    protected function _getTtl(int $ttl) : int
    {
        $ttl                    = $ttl ?: $this->defaultTtl;
        Assert::that($ttl)->int()->range(1, 315360000); // Max 10 years..

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
