<?php

namespace Noo\PasswordProtect\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Noo\PasswordProtect\Security\PasswordSettings;
use Symfony\Component\HttpFoundation\Response;

class PreventProtectedPageCaching
{
    public function __construct(private PasswordSettings $passwords) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // This middleware runs inside Statamic's cache writer. Cache-Control alone
        // does not prevent the full-measure driver from writing a public file.
        if ($this->passwords->isEnabled($this->passwords->settings($request))) {
            $response->headers->set('X-Statamic-Uncacheable', 'true');
        }

        return $response;
    }
}
