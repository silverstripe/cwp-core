<?php

namespace CWP\Core\Config;

/**
 * Lets CMS authors import a Word document into a page, using the platform's Docvert service.
 *
 * Applied in _config/documentconverter.yml, and only where silverstripe/documentconverter is
 * installed and DOCVERT_USERNAME is set. Disabling removes the import from the page edit form.
 */
class DocumentConverterConfig
{
    use FeatureToggle;

    /**
     * @config
     */
    private static string $page_extension = 'SilverStripe\DocumentConverter\PageExtension';

    public static function apply(): void
    {
        if (static::isEnabled() || !class_exists('Page')) {
            return;
        }

        static::removeFromArray('Page', 'extensions', [static::config()->get('page_extension')]);
    }
}
