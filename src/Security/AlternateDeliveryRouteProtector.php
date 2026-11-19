<?php

namespace Noo\PasswordProtect\Security;

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Session\Middleware\StartSession;
use Noo\PasswordProtect\Http\Middleware\PasswordProtect;
use Rebing\GraphQL\GraphQLController;
use Statamic\Http\Controllers\GlideController;

class AlternateDeliveryRouteProtector
{
    public function __construct(private PasswordSettings $passwords) {}

    public function protect(Route $route, Request $request): void
    {
        if (! $this->shouldProtect($route)) {
            return;
        }

        $action = $route->getAction();
        $original = $action['password_protect_middleware'] ?? (array) ($action['middleware'] ?? []);
        $delivery = [];
        if ($this->passwords->isEnabled($this->passwords->settings($request))) {
            // Stateless clients never need a Laravel session. Load an existing CP
            // session only on routes where the wall must recognize its owner.
            if ($request->cookies->has(config('session.cookie'))) {
                $delivery = [EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSession::class];
            }
            $delivery[] = str_starts_with($route->getActionName(), GlideController::class.'@')
                ? PasswordProtect::class
                : PasswordProtect::class.':json';
        }

        $middleware = array_values(array_unique([...$delivery, ...$original]));
        if (($action['middleware'] ?? []) === $middleware) {
            return;
        }

        // Laravel can reuse the route object. Rebuild from its own middleware,
        // not the previous request's cookie or protection state.
        $action['password_protect_middleware'] = $original;
        $action['middleware'] = $middleware;
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
