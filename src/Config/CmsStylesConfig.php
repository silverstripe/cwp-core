<?php

namespace CWP\Core\Config;

use SilverStripe\Admin\LeftAndMain;

/**
 * Loads this module's stylesheet into the CMS, which adjusts the admin interface to suit
 * government sites.
 *
 * Applied in _config/config.yml. Disabling drops the stylesheet and leaves any other
 * requirements alone.
 */
class CmsStylesConfig
{
    use FeatureToggle;

    /**
     * The stylesheet this module adds to LeftAndMain.extra_requirements_css.
     *
     * @config
     */
    private static string $stylesheet = 'cwp/cwp-core:css/custom.css';

    public static function apply(): void
    {
        if (static::isEnabled()) {
            return;
        }

        static::removeFromArray(
            LeftAndMain::class,
            'extra_requirements_css',
            [static::config()->get('stylesheet')]
        );
    }
}
