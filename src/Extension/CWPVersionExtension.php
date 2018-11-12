<?php

namespace CWP\Core\Extension;

use SilverStripe\Core\Extension;
use SilverStripe\Core\Manifest\VersionProvider;

class CWPVersionExtension extends Extension
{
    /**
     * Gets the version of cwp/cwp-core and returns the major.minor version from it
     *
     * @return string
     */
    public function getCWPVersionNumber()
    {
        /** @var VersionProvider $versionProvider */
        $versionProvider = $this->owner->getVersionProvider();

        $modules = $versionProvider->getModuleVersionFromComposer(['cwp/cwp-core']);
        if (empty($modules)) {
            return '';
        }

        // Example: "2.2.x-dev"
        $cwpCore = $modules['cwp/cwp-core'];
        return (string) substr($cwpCore, 0, strpos($cwpCore, '.', 2));
    }
}
