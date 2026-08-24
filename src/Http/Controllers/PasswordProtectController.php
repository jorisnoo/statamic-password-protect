<?php

namespace Noo\PasswordProtect\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Noo\PasswordProtect\Security\PasswordAccess;
use Noo\PasswordProtect\Security\PasswordSettings;

class PasswordProtectController
{
    public function __construct(
        private PasswordSettings $passwords,
        private PasswordAccess $access,
    ) {}

    public function show(): View
    {
        $addon = $this->passwords->addon();
        $title = $addon?->setting('title');

        return view('statamic-password-protect::password', [
            'title' => $title,
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $storedHash = $this->passwords->passwordHash();
        $authorizationSalt = $this->passwords->authorizationSalt();

        if ($this->passwords->isEnabled()
            && $storedHash
            && $authorizationSalt
            && $this->passwords->verify($request->string('password')->toString(), $storedHash)) {
            $this->access->grant($request, $storedHash, $authorizationSalt);

            return redirect('/')
                ->withCookie($this->access->cookie($storedHash, $authorizationSalt));
        }

        return back()->withErrors([
            'password' => __('Incorrect password.'),
        ]);
    }
}
