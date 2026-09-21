<?php

namespace CWP\Core\Extension;

use CWP\Core\Config\FeatureToggle;
use SilverStripe\Core\Extension;
use SilverStripe\Security\Group;

/**
 * @extends Extension<Group>
 */
class CwpHtmlEditorConfig extends Extension
{
    use FeatureToggle;


    /**
     * @return string|null
     *
     * Override the default HtmlEditorConfig from 'cms' to 'cwp' defined in cwp-core/_config.php
     * However if the group has a custom editor configuration set, use that instead.
     */
    public function getHtmlEditorConfig()
    {
        $originalConfig = $this->owner->getField("HtmlEditorConfig");

        if ($originalConfig) {
            return $originalConfig;
        }

        if (!static::isEnabled()) {
            return null;
        }

        return 'cwp';
    }
}
