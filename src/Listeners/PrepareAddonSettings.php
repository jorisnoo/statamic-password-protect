<?php

namespace Noo\PasswordProtect\Listeners;

use Illuminate\Validation\ValidationException;
use Noo\PasswordProtect\Fieldtypes\ProtectedPassword;
use Noo\PasswordProtect\Security\PasswordAccess;
use Noo\PasswordProtect\Security\PasswordSettings;
use Noo\PasswordProtect\Security\StaticCacheGuard;
use Statamic\Events\AddonSettingsSaving;

class PrepareAddonSettings
{
    public function __construct(
        private PasswordSettings $passwords,
        private PasswordAccess $access,
        private StaticCacheGuard $staticCache,
    ) {
    }

    public function handle(AddonSettingsSaving $event): void
    {
        $settings = $event->settings;

        if ($settings->addon()->id() !== PasswordSettings::ADDON) {
            return;
        }

        $password = $this->passwords->passwordHash($settings);
        $previous = $this->passwords->settings();
        $existing = $this->passwords->passwordHash($previous);
        $existingIsHash = $this->passwords->isPasswordHashed($previous);
        $existingEnabled = $this->passwords->isEnabled($previous);
        $existingSalt = $this->passwords->authorizationSalt($previous);

        if ($password === ProtectedPassword::UNCHANGED) {
            $password = $existing !== null && ! $existingIsHash
                ? $this->passwords->hash($existing)
                : $existing;
            $settings->set('password', $password);
        } elseif ($password !== null && ! ($password === $existing && $existingIsHash)) {
            $password = $this->passwords->hash($password);
            $settings->set('password', $password);
        }

        if ($password === null && filter_var($settings->get('enabled'), FILTER_VALIDATE_BOOL)) {
            throw ValidationException::withMessages(['password' => __('A password is required to enable protection.')]);
        }

        $settings->set('password_hashed', $password !== null);

        $enabled = $this->passwords->isEnabled($settings);
        $passwordChanged = $password !== $existing;
        $salt = $existingSalt;

        if (! $salt || $passwordChanged || $enabled !== $existingEnabled) {
            $salt = bin2hex(random_bytes(32));
        }

        $settings->set('authorization_salt', $salt);
        $fingerprint = $password !== null ? $this->access->fingerprint($password, $salt) : null;

        $this->staticCache->synchronize($enabled, $fingerprint);
    }
}
