<?php

namespace Noo\PasswordProtect\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Noo\PasswordProtect\Security\PasswordAccess;
use Noo\PasswordProtect\Security\PasswordSettings;
use Symfony\Component\HttpFoundation\Response;

class PasswordProtect
{
    public function __construct(
        private PasswordSettings $passwords,
        private PasswordAccess $access,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->passwords->isEnabled()) {
            return $next($request);
        }

        if ($this->shouldBypass($request)) {
            return $this->preventCaching($next($request));
        }

        return $this->preventCaching(
            redirect()->guest(route('statamic.password-protect.show')),
        );
    }

    protected function shouldBypass(Request $request): bool
    {
        if ($this->isCpRoute($request)) {
            return true;
        }

        if ($this->isPasswordRoute($request)) {
            return true;
        }

        if ($this->isAuthenticated()) {
            return true;
        }

        $passwordHash = $this->passwords->passwordHash();
        $authorizationSalt = $this->passwords->authorizationSalt();

        return $passwordHash
            && $authorizationSalt
            && $this->access->isAuthorized($request, $passwordHash, $authorizationSalt);
    }

    protected function isCpRoute(Request $request): bool
    {
        $cpRoute = trim((string) config('statamic.cp.route', 'cp'), '/');
        $path = trim($request->getPathInfo(), '/');

        return $cpRoute !== '' && ($path === $cpRoute || str_starts_with($path, $cpRoute.'/'));
    }

    protected function isPasswordRoute(Request $request): bool
    {
        $name = $request->route()?->getName();

        return in_array($name, [
            'statamic.password-protect.show',
            'statamic.password-protect.verify',
        ], true);
    }

    protected function isAuthenticated(): bool
    {
        $user = auth()->guard(config('statamic.users.guards.cp', 'web'))->user();

        return $user && $user->can('access cp');
    }

    private function preventCaching(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}
