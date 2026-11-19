<?php

namespace Noo\PasswordProtect\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Noo\PasswordProtect\Security\PasswordAccess;
use Noo\PasswordProtect\Security\PasswordSettings;
use Statamic\Contracts\Addons\Settings;
use Symfony\Component\HttpFoundation\Response;

class PasswordProtect
{
    public function __construct(
        private PasswordSettings $passwords,
        private PasswordAccess $access,
    ) {
    }

    public function handle(Request $request, Closure $next, string $format = 'html'): Response
    {
        $settings = $this->passwords->settings($request);
        if (! $this->passwords->isEnabled($settings)) {
            return $next($request);
        }

        if ($this->shouldBypass($request, $settings)) {
            return $this->preventCaching($next($request));
        }

        return $this->preventCaching(
            $format === 'json' || $request->expectsJson()
                ? response()->json(['message' => __('Password required.')], 401)
                : redirect()->route('statamic.password-protect.show'),
        );
    }

    protected function shouldBypass(Request $request, Settings $settings): bool
    {
        if ($this->isCpRoute($request)) {
            return true;
        }

        if ($this->isPasswordRoute($request)) {
            return true;
        }

        if ($request->hasSession() && $this->isAuthenticated()) {
            return true;
        }

        $passwordHash = $this->passwords->passwordHash($settings);
        $authorizationSalt = $this->passwords->authorizationSalt($settings) ?? PasswordSettings::LEGACY_SALT;

        return $passwordHash !== null && $this->access->isAuthorized($request, $passwordHash, $authorizationSalt);
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
