<?php

/**
 * General CWP configuration
 *
 * More configuration is applied in cwp/_config/config.yml for APIs that use
 * {@link Config} instead of setting statics directly.
 * NOTE: Put your custom site configuration into mysite/_config/config.yml
 * and if absolutely necessary if you can't use the yml file, mysite/_config.php instead.
 */

use SilverStripe\Core\Environment;
use SilverStripe\HybridSessions\HybridSession;
use SilverStripe\i18n\i18n;

// set the system locale to en_GB. This also means locale dropdowns
// and date formatting etc will default to this locale. Note there is no
// English (New Zealand) option
i18n::set_locale('en_GB');

// default to the binary being in the usual path on Linux
if (!Environment::getEnv('WKHTMLTOPDF_BINARY')) {
    Environment::setEnv('WKHTMLTOPDF_BINARY', '/usr/local/bin/wkhtmltopdf');
}

// Automatically configure session key for activedr with hybridsessions module
if (Environment::getEnv('CWP_INSTANCE_DR_TYPE')
    && Environment::getEnv('CWP_INSTANCE_DR_TYPE') === 'active'
    && Environment::getEnv('SS_SESSION_KEY')
    && class_exists(HybridSession::class)
) {
    HybridSession::init(Environment::getEnv('SS_SESSION_KEY'));
}
