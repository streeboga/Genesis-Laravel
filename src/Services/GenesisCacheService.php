<?php

namespace Streeboga\GenesisLaravel\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;

class GenesisCacheService
{
    public function __construct(
        protected CacheRepository $cache,
        protected bool $enabled = true,
        protected int $ttl = 3600,
        protected string $prefix = 'genesis:'
    ) {
    }

    public function get(string $key): mixed
    {
        if (!$this->enabled) {
            return null;
        }

        return $this->cache->get($this->prefix . $key);
    }

    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (!$this->enabled) {
            return false;
        }

        return $this->cache->put($this->prefix . $key, $value, $ttl ?? $this->ttl);
    }

    public function forget(string $key): bool
    {
        if (!$this->enabled) {
            return false;
        }

        return $this->cache->forget($this->prefix . $key);
    }

    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        if (!$this->enabled) {
            return $callback();
        }

        return $this->cache->remember($this->prefix . $key, $ttl ?? $this->ttl, $callback);
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getTtl(): int
    {
        return $this->ttl;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }
}


