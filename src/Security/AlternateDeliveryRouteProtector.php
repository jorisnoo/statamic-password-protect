<?php

namespace Noo\PasswordProtect\Security;

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Route;
use Illuminate\Session\Middleware\StartSession;
use Noo\PasswordProtect\Http\Middleware\PasswordProtect;
use Rebing\GraphQL\GraphQLController;
use Statamic\Http\Controllers\GlideController;

class AlternateDeliveryRouteProtector
{
    public function protect(Route $route): void
    {
        if (! $this->shouldProtect($route)) {
            return;
        }

        $action = $route->getAction();
        $requiredMiddleware = [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            PasswordProtect::class,
        ];
        $middleware = array_values(array_filter(
            (array) ($action['middleware'] ?? []),
            fn ($middleware) => ! in_array($middleware, $requiredMiddleware, true),
        ));

        $action['middleware'] = [...$requiredMiddleware, ...$middleware];
        $route->setAction($action);
        $route->computedMiddleware = null;
    }

    public function shouldProtect(Route $route): bool
    {
        $name = (string) $route->getName();
        $action = $route->getActionName();

        return str_starts_with($name, 'statamic.api.')
            || str_starts_with($action, 'Statamic\\Http\\Controllers\\API\\')
            || str_starts_with($action, GlideController::class.'@')
            || str_starts_with($action, GraphQLController::class.'@');
    }
}
