<?php

namespace Noo\PasswordProtect;

use Noo\PasswordProtect\Http\Middleware\PasswordProtect;
use Statamic\Facades\Addon;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $middlewareGroups = [
        'web' => [
            PasswordProtect::class,
        ],
    ];

    protected $routes = [
        'web' => __DIR__.'/../routes/web.php',
    ];

    public function bootAddon(): void
    {
        if ($this->isPasswordProtectionEnabled()) {
            config(['statamic.static_caching.strategy' => null]);
        }
    }

    protected function isPasswordProtectionEnabled(): bool
    {
        try {
            $addon = Addon::get('jorisnoo/statamic-password-protect');

            return $addon
                && $addon->setting('enabled')
                && $addon->setting('password');
        } catch (\Exception $e) {
            return false;
        }
    }
}
