<?php

namespace CWP\Core\Config;

use SilverStripe\Security\Member;
use SilverStripe\Security\Security;

/**
 * Locks an account for 15 minutes after 5 failed logins, emails the member when their password
 * changes, and leaves login attempt recording off.
 *
 * Applied in _config/config.yml. Disabling restores the framework defaults, which allow 10 attempts
 * before a lockout.
 */
class MemberSecurityConfig
{
    use FeatureToggle;

    public static function apply(): void
    {
        if (static::isEnabled()) {
            return;
        }

        static::restoreDefault(Member::class, 'lock_out_after_incorrect_logins', 5);
        static::restoreDefault(Member::class, 'lock_out_delay_mins', 15);
        static::restoreDefault(Member::class, 'notify_password_change', true);
        static::restoreDefault(Security::class, 'login_recording', false);
    }
}
