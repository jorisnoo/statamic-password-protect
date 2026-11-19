<?php

namespace Noo\PasswordProtect\Security;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Statamic\Addons\Addon;
use Statamic\Contracts\Addons\Settings;
use Statamic\Facades\Addon as AddonFacade;

class PasswordSettings
{
    public const ADDON = 'jorisnoo/statamic-password-protect';

    // Old plaintext settings had no generation. Tokens are still HMAC-bound to the
    // application key and password; the next settings save generates a random salt.
    public const LEGACY_SALT = 'legacy';

    public function addon(): ?Addon
    {
        return AddonFacade::get(self::ADDON);
    }

    public function settings(?Request $request = null): ?Settings
    {
        if ($request === null) {
            return $this->addon()?->settings();
        }

        if (! $request->attributes->has(self::class)) {
            $request->attributes->set(self::class, $this->settings());
        }

        return $request->attributes->get(self::class);
    }

    public function isEnabled(?Settings $settings = null): bool
    {
        $settings ??= $this->settings();

        return $settings
            && filter_var($settings->get('enabled'), FILTER_VALIDATE_BOOL)
            && filled($this->passwordHash($settings));
    }

    public function passwordHash(?Settings $settings = null): ?string
    {
        $password = ($settings ?? $this->settings())?->raw()['password'] ?? null;

        return is_string($password) && $password !== '' ? $password : null;
    }

    public function authorizationSalt(?Settings $settings = null): ?string
    {
        $salt = ($settings ?? $this->settings())?->raw()['authorization_salt'] ?? null;

        return is_string($salt) && $salt !== '' ? $salt : null;
    }

    public function isPasswordHashed(?Settings $settings = null): bool
    {
        $settings ??= $this->settings();
        $markedAsHashed = filter_var(
            $settings?->raw()['password_hashed'] ?? false,
            FILTER_VALIDATE_BOOL,
        );

        $password = $this->passwordHash($settings);

        return $markedAsHashed && $password && $this->isHash($password);
    }

    public function hash(string $password): string
    {
        return Hash::make($password);
    }

    public function isHash(string $value): bool
    {
        return password_get_info($value)['algo'] !== null;
    }

    public function verify(string $password, Settings $settings): bool
    {
        $hash = $this->passwordHash($settings);
        if ($hash === null) {
            return false;
        }

        // Before hashes were introduced, even a hash-shaped string was a literal password.
        if (! filter_var($settings->raw()['password_hashed'] ?? false, FILTER_VALIDATE_BOOL)) {
            return hash_equals($hash, $password);
        }

        try {
            return Hash::check($password, $hash);
        } catch (RuntimeException) {
            return false;
        }
    }
}
