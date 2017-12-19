<?php

namespace CWP\Core\Extension;

use SilverStripe\ORM\DataExtension;

class CwpHtmlEditorConfig extends DataExtension
{

    /**
     * @return string
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

        return 'cwp';
    }
}
