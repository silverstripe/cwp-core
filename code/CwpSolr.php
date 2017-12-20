<?php

namespace CWP\Core\Search;

use Exception;
use InvalidArgumentException;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\FullTextSearch\Solr\Solr;

/**
 * CwpSolr configures Solr in a CWP-compatible manner.
 */
class CwpSolr
{
    use Configurable;

    /**
     *
     * @var array
     */
    private static $options;

    /**
     * Configure Solr.
     *
     * $options - An array consisting of:
     *
     * 'extraspath' - (String) Where to find Solr core configuartion files.
     *     Defaults to '<BASE_PATH>/mysite/conf/extras'.
     * 'version' - select the Solr configuration to use when in CWP. One of:
     * * 'cwp-4': preferred version, uses secured 4.x service available on CWP
     * * 'local-4': this can be use for development using silverstripe-localsolr package, 4.x branch
     */
    public static function configure()
    {
        if (!class_exists(Solr::class)) {
            return;
        }

        // get options from configuration
        $options = static::config()->get('options');

        // get version specific options
        switch ($options['version']) {
            case 'cwp-4':
                $solrOptions = self::options_for_cwp($options);
                break;
            case 'local-4':
                $solrOptions = self::options_for_local($options);
                break;
            default:
                throw new InvalidArgumentException(sprintf(
                    'Solr version "%s" is not supported on CWP. Please use "local-4" on local ' .
                        'and "cwp-4" on production. For preferred configuration see ' .
                        'https://www.cwp.govt.nz/developer-docs/.',
                    $options['version']
                ));
                break;
        }

        // Allow users to override extras path.
        // CAUTION: CWP does not permit usage of customised solrconfig.xml.
        if (isset($options['extraspath']) && file_exists($options['extraspath'])) {
            $solrOptions['extraspath'] = $options['extraspath'];
        } elseif (file_exists(BASE_PATH . '/mysite/conf/extras')) {
            $solrOptions['extraspath'] = BASE_PATH . '/mysite/conf/extras';
        }

        Solr::configure_server($solrOptions);
    }

    /**
     * @throws Exception
     */
    public static function options_from_environment()
    {
        throw new Exception(
            'CwpSolr::options_from_environment has been deprecated, in favour of implicit Solr ' .
            'configuration provided by the CwpSolr class in the cwp-core module. For preferred configuration see ' .
            'https://www.cwp.govt.nz/developer-docs/.'
        );
    }

    /**
     * @param array $options
     * @return array
     */
    public static function options_for_cwp($options)
    {
        $version = $options['version'];

        return [
            'host' => Environment::getEnv('SOLR_SERVER'),
            'port' => Environment::getEnv('SOLR_PORT'),
            'path' => '/v4/',
            'version' => 4,
            'indexstore' => [
                'mode' => CwpSolrConfigStore::class,
                'path' => '/v4',
            ],
        ];
    }

    /**
     *
     * @param array $options
     * @return array
     */
    public static function options_for_local($options)
    {
        return [
            'host' => Environment::getEnv('SOLR_SERVER') ? Environment::getEnv('SOLR_SERVER') : 'localhost',
            'port' => Environment::getEnv('SOLR_PORT') ? Environment::getEnv('SOLR_PORT') : 8983,
            'path' => Environment::getEnv('SOLR_PATH') ? Environment::getEnv('SOLR_PATH') : '/solr/',
            'version' => 4,
            'indexstore' => [
                'mode' => Environment::getEnv('SOLR_MODE') ? Environment::getEnv('SOLR_MODE') : 'file',
                'auth' => Environment::getEnv('SOLR_AUTH') ? Environment::getEnv('SOLR_AUTH') : null,
                // Allow storing the solr index and config data in an arbitrary location,
                // e.g. outside of the webroot
                'path' => Environment::getEnv('SOLR_INDEXSTORE_PATH')
                    ? Environment::getEnv('SOLR_INDEXSTORE_PATH')
                    : BASE_PATH . '/.solr',
                'remotepath' => Environment::getEnv('SOLR_REMOTE_PATH')
                    ? Environment::getEnv('SOLR_REMOTE_PATH')
                    : null
            ]
        ];
    }
}
