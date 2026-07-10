<?php

namespace Noo\PasswordProtect\Listeners;

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
        $existing = $this->passwords->passwordHash();
        $existingIsHash = $this->passwords->isPasswordHashed();
        $existingEnabled = $this->passwords->isEnabled();
        $existingSalt = $this->passwords->authorizationSalt();

        if ($password === ProtectedPassword::UNCHANGED && $existing) {
            $password = $existingIsHash
                ? $existing
                : $this->passwords->hash($existing);
            $settings->set('password', $password);
        } elseif ($password && ! ($password === $existing && $existingIsHash)) {
            $password = $this->passwords->hash($password);
            $settings->set('password', $password);
        }

        $settings->set('password_hashed', (bool) $password);

        $enabled = $this->passwords->isEnabled($settings);
        $passwordChanged = $password !== $existing;
        $salt = $existingSalt;

        if (! $salt || $passwordChanged || $enabled !== $existingEnabled) {
            $salt = bin2hex(random_bytes(32));
        }

        $settings->set('authorization_salt', $salt);
        $fingerprint = $password ? $this->access->fingerprint($password, $salt) : null;

        $this->staticCache->synchronize($enabled, $fingerprint);
    }
}
