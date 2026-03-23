<?php

namespace Noo\PasswordProtect\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Statamic\Facades\Addon;

class PasswordProtectController
{
    public function show(): View
    {
        $addon = Addon::get('jorisnoo/statamic-password-protect');
        $title = $addon?->setting('title');

        return view('statamic-password-protect::password', [
            'title' => $title,
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $addon = Addon::get('jorisnoo/statamic-password-protect');
        $storedPassword = $addon?->setting('password');

        if ($request->input('password') === $storedPassword) {
            $request->session()->put('password_protect_authorized', true);

            return redirect()->intended('/');
        }

        return back()->withErrors([
            'password' => __('Incorrect password.'),
        ]);
    }
}
