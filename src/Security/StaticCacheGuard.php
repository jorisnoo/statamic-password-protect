<?php

namespace Noo\PasswordProtect\Security;

use Illuminate\Contracts\Cache\Repository;
use Statamic\StaticCaching\StaticCacheManager;

class StaticCacheGuard
{
    private const CACHE_KEY = 'statamic-password-protect:static-cache-fingerprint';

    private mixed $originalStrategy;

    public function __construct(
        private StaticCacheManager $staticCache,
        private Repository $cache,
    ) {
        $this->originalStrategy = config('statamic.static_caching.strategy');
    }

    public function synchronize(bool $enabled, ?string $fingerprint = null): void
    {
        if (! $enabled) {
            $this->cache->forget($this->cacheKey());
            $this->setStrategy($this->originalStrategy);

            return;
        }

        if ($this->originalStrategy && $fingerprint && $this->cache->get($this->cacheKey()) !== $fingerprint) {
            $this->setStrategy($this->originalStrategy);
            $this->staticCache->flush();
            $this->cache->forever($this->cacheKey(), $fingerprint);
        }

        $this->setStrategy(null);
    }

    private function setStrategy(mixed $strategy): void
    {
        config(['statamic.static_caching.strategy' => $strategy]);
    }

    private function cacheKey(): string
    {
        return self::CACHE_KEY.':'.hash('sha256', gethostname().'|'.public_path());
    }
}
