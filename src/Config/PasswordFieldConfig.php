<?php

namespace CWP\Core\Config;

use SilverStripe\Forms\PasswordField;

/**
 * Marks password fields as `autocomplete="off"` so browsers do not offer to fill them.
 *
 * Applied in _config/config.yml. Disabling restores the framework default, which leaves the
 * browser to decide.
 */
class PasswordFieldConfig
{
    use FeatureToggle;

    public static function apply(): void
    {
        if (static::isEnabled()) {
            return;
        }

        static::restoreDefault(PasswordField::class, 'autocomplete', false);
    }
}
