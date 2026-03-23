<?php

namespace Noo\PasswordProtect\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Statamic\Facades\Addon;
use Symfony\Component\HttpFoundation\Response;

class PasswordProtect
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isEnabled()) {
            return $next($request);
        }

        if ($this->shouldBypass($request)) {
            return $next($request);
        }

        return redirect()->guest(route('statamic.password-protect.show'));
    }

    protected function isEnabled(): bool
    {
        $addon = Addon::get('jorisnoo/statamic-password-protect');

        return $addon
            && $addon->setting('enabled')
            && $addon->setting('password');
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

        if ($request->session()->get('password_protect_authorized')) {
            return true;
        }

        return false;
    }

    protected function isCpRoute(Request $request): bool
    {
        $cpRoute = config('statamic.cp.route', 'cp');

        return str_starts_with(ltrim($request->getPathInfo(), '/'), $cpRoute);
    }

    protected function isPasswordRoute(Request $request): bool
    {
        $name = $request->route()?->getName();

        return $name && str_starts_with($name, 'statamic.password-protect.');
    }

    protected function isAuthenticated(): bool
    {
        return auth()->guard(config('statamic.users.guards.cp.guard', 'web'))->check();
    }
}
