<?php

namespace Noo\PasswordProtect\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Noo\PasswordProtect\Security\PasswordAccess;
use Noo\PasswordProtect\Security\PasswordSettings;

class PasswordProtectController
{
    public function __construct(
        private PasswordSettings $passwords,
        private PasswordAccess $access,
    ) {}

    public function show(Request $request): View
    {
        $title = $this->passwords->settings($request)?->get('title');

        return view('statamic-password-protect::password', [
            'title' => $title,
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $address = inet_pton($request->ip() ?? '') ?: '';
        // Count IPv6 clients by network rather than by individual privacy addresses.
        $key = 'password-protect:'.bin2hex(strlen($address) === 16 ? substr($address, 0, 8) : $address);
        if (RateLimiter::tooManyAttempts($key, 5)) {
            abort(429, __('Too many password attempts.'), ['Retry-After' => RateLimiter::availableIn($key)]);
        }

        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $settings = $this->passwords->settings($request);
        $storedHash = $this->passwords->passwordHash($settings);
        $authorizationSalt = $this->passwords->authorizationSalt($settings) ?? PasswordSettings::LEGACY_SALT;

        if ($this->passwords->isEnabled($settings)
            && $this->passwords->verify($request->string('password')->toString(), $settings)) {
            $this->access->grant($request, $storedHash, $authorizationSalt);

            return redirect('/')
                ->withCookie($this->access->cookie($storedHash, $authorizationSalt));
        }

        RateLimiter::hit($key, 60);

        return back()->withErrors([
            'password' => __('Incorrect password.'),
        ]);
    }
}
