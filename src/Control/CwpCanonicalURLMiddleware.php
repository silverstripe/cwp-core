<?php

namespace CWP\Core\Control;

use SilverStripe\Control\Director;
use SilverStripe\Control\Middleware\CanonicalURLMiddleware;

/**
 * @internal Used to override CanonicalURLMiddleware to prevent canonical URL causing a redirect on CLI unless
 * explicitly enabled.
 * Note, it is very likely that this class will deprecated after CWP 2.0.
 */
class CwpCanonicalURLMiddleware extends CanonicalURLMiddleware
{
    protected function isEnabled()
    {
        // If CLI, EnabledEnvs must contain CLI
        if (Director::is_cli() && !in_array('cli', $enabledEnvs)) {
            return false;
        }
        return parent::isEnabled();
    }
}
