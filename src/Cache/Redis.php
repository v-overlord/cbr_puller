<?php

namespace CbrPuller\Cache;

use CbrPuller\Config;
use Psr\Log\LoggerInterface;

class Redis extends AbstractCache
{
    protected \Redis $redis;

    protected string $cacheName = 'Redis cache';

    protected string $prefixName = 'cbrPuller_';

    public function __construct(public LoggerInterface $logger)
    {
        $this->tryCatch(function () {
            $redis = new \Redis();
            $redis->connect($_ENV['REDIS_HOST'], (int)$_ENV['REDIS_PORT']);
            $this->redis = $redis;

            if (Config::$resetCache) {
                $this->reset();
            }
        });
    }

    public function get(string $key): mixed
    {
        return $this->tryCatch(function () use ($key) {
            if ($this->exists($key)) {
                $key = $this->buildAndGetKey($key);
                $cachedValue = $this->redis->get($key);

                $this->logger->debug("{cacheName}: Cache hit for the key '{key}'!", [
                    'cacheName' => $this->cacheName,
                    'key' => $key
                ]);

                return unserialize($cachedValue);
            }

            return null;
        });
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        return (bool) $this->tryCatch(function () use ($key, $value, $ttl) {
            $key = $this->buildAndGetKey($key);
            $value = serialize($value);

            $this->logger->debug("{cacheName}: Set the key '{key}' in the cache!", [
                'cacheName' => $this->cacheName,
                'key' => $key
            ]);

            if ($ttl === null) {
                return $this->redis->set($key, $value);
            } else {
                return $this->redis->setex($key, $ttl, $value);
            }
        });
    }

    function exists(string $key): bool
    {
        return (bool) $this->tryCatch(function () use ($key) {
            return $this->redis->exists($this->buildAndGetKey($key));
        });
    }

    public function reset(): bool
    {
        return (bool) $this->tryCatch(function () {
            $this->redis->del($this->redis->keys($this->prefixName));

            $this->logger->debug("{cacheName}: The cache has been completely emptied!", ['cacheName' => $this->cacheName]);

            return true;
        });
    }

    protected function buildAndGetKey(string $key): string
    {
        return $this->prefixName . $key;
    }
}