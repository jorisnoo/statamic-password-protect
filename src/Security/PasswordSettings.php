<?php

namespace Noo\PasswordProtect\Security;

use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Statamic\Addons\Addon;
use Statamic\Contracts\Addons\Settings;
use Statamic\Facades\Addon as AddonFacade;

class PasswordSettings
{
    public const ADDON = 'jorisnoo/statamic-password-protect';

    public function addon(): ?Addon
    {
        return AddonFacade::get(self::ADDON);
    }

    public function settings(): ?Settings
    {
        return $this->addon()?->settings();
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
        $password = $settings?->raw()['password'] ?? $this->settings()?->raw()['password'] ?? null;

        return is_string($password) && $password !== '' ? $password : null;
    }

    public function authorizationSalt(?Settings $settings = null): ?string
    {
        $salt = $settings?->raw()['authorization_salt']
            ?? $this->settings()?->raw()['authorization_salt']
            ?? null;

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

    public function verify(string $password, string $hash): bool
    {
        if (! $this->isHash($hash)) {
            return false;
        }

        try {
            return Hash::check($password, $hash);
        } catch (RuntimeException) {
            return false;
        }
    }

    public function migrateLegacyPassword(): void
    {
        $settings = $this->settings();
        $password = $this->passwordHash($settings);

        if ($settings && $password && ! $this->isPasswordHashed($settings)) {
            // The saving listener replaces this legacy value with a one-way hash.
            $settings->save();
        }
    }
}
