<?php

namespace CWP\Core\Config;

use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\Session;

/**
 * Expires idle sessions after 24 minutes and stops the CMS holding a session open with keepalive
 * pings, so an unattended CMS window logs out.
 *
 * Applied in _config/config.yml. Disabling restores the framework and admin defaults, unless the
 * project has set its own values.
 */
class SessionConfig
{
    use FeatureToggle;

    public static function apply(): void
    {
        if (static::isEnabled()) {
            return;
        }

        static::restoreDefault(Session::class, 'timeout', 1440);
        static::restoreDefault(LeftAndMain::class, 'session_keepalive_ping', false);
    }
}
