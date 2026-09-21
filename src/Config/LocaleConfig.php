<?php

namespace CWP\Core\Config;

use SilverStripe\i18n\i18n;

/**
 * Sets the site locale to en_GB, which drives date formatting and the locale dropdowns.
 *
 * Applied in _config/config.yml. Disabling restores the framework default of en_US.
 */
class LocaleConfig
{
    use FeatureToggle;

    public static function apply(): void
    {
        if (static::isEnabled()) {
            return;
        }

        static::restoreDefault(i18n::class, 'default_locale', 'en_GB');
    }
}
