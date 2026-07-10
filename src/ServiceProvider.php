<?php

namespace Noo\PasswordProtect;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Http\Request;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\RateLimiter;
use Noo\PasswordProtect\Fieldtypes\ProtectedPassword;
use Noo\PasswordProtect\Http\Middleware\PasswordProtect;
use Noo\PasswordProtect\Listeners\PrepareAddonSettings;
use Noo\PasswordProtect\Listeners\ProtectAlternateDeliveryRoute;
use Noo\PasswordProtect\Security\AlternateDeliveryRouteProtector;
use Noo\PasswordProtect\Security\PasswordAccess;
use Noo\PasswordProtect\Security\PasswordSettings;
use Noo\PasswordProtect\Security\StaticCacheGuard;
use Statamic\Events\AddonSettingsSaving;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $fieldtypes = [
        ProtectedPassword::class,
    ];

    protected $listen = [
        AddonSettingsSaving::class => [
            PrepareAddonSettings::class,
        ],
        RouteMatched::class => [
            ProtectAlternateDeliveryRoute::class,
        ],
    ];

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
        $this->configureRateLimiter();
        $this->configureAuthorizationCookie();
        $this->protectAlternateDeliveryRoutes();

        $passwords = app(PasswordSettings::class);
        $passwords->migrateLegacyPassword();

        $passwordHash = $passwords->passwordHash();
        $authorizationSalt = $passwords->authorizationSalt();
        app(StaticCacheGuard::class)->synchronize(
            $passwords->isEnabled(),
            $passwordHash && $authorizationSalt
                ? app(PasswordAccess::class)->fingerprint($passwordHash, $authorizationSalt)
                : null,
        );
    }

    private function configureRateLimiter(): void
    {
        RateLimiter::for('password-protect', fn (Request $request) => [
            Limit::perMinute(5)->by('password-protect:'.$request->ip()),
            Limit::perMinute(100)->by('password-protect:global'),
        ]);
    }

    private function configureAuthorizationCookie(): void
    {
        EncryptCookies::except(PasswordAccess::COOKIE);
    }

    private function protectAlternateDeliveryRoutes(): void
    {
        $protector = app(AlternateDeliveryRouteProtector::class);

        foreach ($this->app['router']->getRoutes()->getRoutes() as $route) {
            $protector->protect($route);
        }

        $apiMiddleware = config('statamic.api.middleware', 'api');

        if (is_string($apiMiddleware)) {
            $this->app['router']->pushMiddlewareToGroup($apiMiddleware, PasswordProtect::class);
        }

        $graphqlMiddleware = (array) config('graphql.route.middleware', []);

        if (! in_array(PasswordProtect::class, $graphqlMiddleware, true)) {
            config(['graphql.route.middleware' => [...$graphqlMiddleware, PasswordProtect::class]]);
        }
    }
}
