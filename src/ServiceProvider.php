<?php

namespace Noo\PasswordProtect;

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Events\RouteMatched;
use Noo\PasswordProtect\Fieldtypes\ProtectedPassword;
use Noo\PasswordProtect\Http\Middleware\PasswordProtect;
use Noo\PasswordProtect\Http\Middleware\PreventProtectedPageCaching;
use Noo\PasswordProtect\Listeners\PrepareAddonSettings;
use Noo\PasswordProtect\Listeners\ProtectAlternateDeliveryRoute;
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
        'statamic.web' => [
            PreventProtectedPageCaching::class,
        ],
    ];

    protected $routes = [
        'actions' => __DIR__.'/../routes/actions.php',
    ];

    public function bootAddon(): void
    {
        $this->configureAuthorizationCookie();
        // The frontend controller always uses statamic.web, even with a custom site group.
        $this->app['router']->prependMiddlewareToGroup('statamic.web', PasswordProtect::class);

        $passwords = app(PasswordSettings::class);
        $settings = $passwords->settings();

        $passwordHash = $passwords->passwordHash($settings);
        $authorizationSalt = $passwords->authorizationSalt($settings) ?? PasswordSettings::LEGACY_SALT;
        app(StaticCacheGuard::class)->synchronize(
            $passwords->isEnabled($settings),
            $passwordHash !== null
                ? app(PasswordAccess::class)->fingerprint($passwordHash, $authorizationSalt)
                : null,
        );
    }

    private function configureAuthorizationCookie(): void
    {
        EncryptCookies::except(PasswordAccess::COOKIE);
    }
}
