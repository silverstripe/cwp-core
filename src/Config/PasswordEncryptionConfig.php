<?php

namespace CWP\Core\Config;

use SilverStripe\Security\Security;

/**
 * Hashes member passwords with SHA-512 via PBKDF2, for NZISM compliance.
 *
 * Applied in _config/encryptors.yml. Disabling restores the framework default of blowfish.
 * Existing hashes keep working either way - the algorithm is recorded per member and only new or
 * changed passwords use the configured one.
 */
class PasswordEncryptionConfig
{
    use FeatureToggle;

    public static function apply(): void
    {
        if (static::isEnabled()) {
            return;
        }

        static::restoreDefault(Security::class, 'password_encryption_algorithm', 'pbkdf2_sha512');
    }
}
