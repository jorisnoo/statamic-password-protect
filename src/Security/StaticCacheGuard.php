<?php

namespace Noo\PasswordProtect\Security;

use Illuminate\Contracts\Cache\Repository;
use Statamic\StaticCaching\StaticCacheManager;

class StaticCacheGuard
{
    private const CACHE_KEY = 'statamic-password-protect:static-cache-fingerprint';

    public function __construct(
        private StaticCacheManager $staticCache,
        private Repository $cache,
    ) {}

    public function synchronize(bool $enabled, ?string $fingerprint = null): void
    {
        if (! $enabled) {
            $this->cache->forget($this->cacheKey());

            return;
        }

        if (config('statamic.static_caching.strategy') && $fingerprint && $this->cache->get($this->cacheKey()) !== $fingerprint) {
            $this->staticCache->flush();
            $this->cache->forever($this->cacheKey(), $fingerprint);
        }
    }

    private function cacheKey(): string
    {
        return self::CACHE_KEY.':'.hash('sha256', gethostname().'|'.public_path());
    }
}
