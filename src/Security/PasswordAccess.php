<?php

namespace Noo\PasswordProtect\Security;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

class PasswordAccess
{
    public const COOKIE = 'password_protect_authorization';

    public const SESSION = 'password_protect_authorization';

    public function fingerprint(string $passwordHash, string $authorizationSalt): string
    {
        return hash_hmac('sha256', $passwordHash.'|'.$authorizationSalt, (string) config('app.key'));
    }

    public function grant(Request $request, string $passwordHash, string $authorizationSalt): void
    {
        if (! $request->hasSession()) {
            return;
        }

        $request->session()->regenerate();
        $request->session()->put(self::SESSION, $this->fingerprint($passwordHash, $authorizationSalt));
    }

    public function isAuthorized(Request $request, string $passwordHash, string $authorizationSalt): bool
    {
        $expected = $this->fingerprint($passwordHash, $authorizationSalt);
        $sessionValue = $request->hasSession() ? $request->session()->get(self::SESSION) : null;
        $cookieValue = $request->cookie(self::COOKIE);

        return $this->matches($expected, $sessionValue)
            || $this->matches($expected, $cookieValue);
    }

    public function cookie(string $passwordHash, string $authorizationSalt): Cookie
    {
        return cookie(
            self::COOKIE,
            $this->fingerprint($passwordHash, $authorizationSalt),
            0,
            '/',
            config('session.domain'),
            config('session.secure'),
            true,
            false,
            config('session.same_site', 'lax'),
        );
    }

    private function matches(string $expected, mixed $actual): bool
    {
        return is_string($actual) && strlen($actual) === strlen($expected) && hash_equals($expected, $actual);
    }
}
