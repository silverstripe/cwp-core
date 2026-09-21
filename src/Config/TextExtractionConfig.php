<?php

namespace CWP\Core\Config;

use SilverStripe\Assets\File;

/**
 * Extracts searchable text from uploaded files, and caches it on the filesystem rather than in the
 * database.
 *
 * Applied in _config/textextraction.yml, and only where silverstripe/textextraction is installed.
 * Disabling stops files being made text extractable and returns the cache to that module's default.
 */
class TextExtractionConfig
{
    use FeatureToggle;

    /**
     * Injector service name of the text cache, and the implementation this module points it at.
     *
     * @config
     */
    private static string $cache_service = 'SilverStripe\TextExtraction\Cache\FileTextCache';

    /**
     * @config
     */
    private static string $cache_class = 'SilverStripe\TextExtraction\Cache\FileTextCache\Cache';

    /**
     * The implementation silverstripe/textextraction points the cache service at, which disabling
     * puts back.
     *
     * @config
     */
    private static string $cache_class_default = 'SilverStripe\TextExtraction\Cache\FileTextCache\Database';

    /**
     * @config
     */
    private static string $file_extension = 'SilverStripe\TextExtraction\Extension\FileTextExtractable';

    public static function apply(): void
    {
        if (static::isEnabled()) {
            return;
        }

        static::restoreInjectorKey(
            static::config()->get('cache_service'),
            'class',
            static::config()->get('cache_class'),
            static::config()->get('cache_class_default')
        );
        static::removeFromArray(File::class, 'extensions', [static::config()->get('file_extension')]);
    }
}
