<?php
/**
 * Takes care of configuring Solr while defaulting to backwards-compatible configuration ('legacy').
 *
 * The default will be changed to 'cwp-4' on recipe 1.1.0, but you can already change that on your project using
 * the config system. Also, newly created projects will default to that as well via cwp-installer configuration
 * (see mysite/_config/config.yml).
 */

class CwpSolr {

	private static $options = array(
		'version' => 'legacy'
	);

	/**
	 * Configure Solr.
	 *
	 * $cwpOptions - An array consisting of:
	 *
	 * 'version' - select the Solr configuration to use when in CWP. One of:
	 *
	 * * 'legacy': legacy mode for backwards-compatibility. Do not use unless strictly necessary, will be removed.
	 * * 'cwp-4': preferred version, uses secured 4.x service available on CWP
	 * * 'cwp-3': uses secured 3.x service available on CWP (buggy)
	 * * 'local-4': this can be use for development using silverstripe-localsolr package, 4.x branch
	 * * 'local-3': as above, but for Solr 3.x
	 */
	static function configure() {
		if(!class_exists('Solr')) return;

		$options = Config::inst()->get('CwpSolr', 'options');

		switch($options['version']) {
			case 'cwp-4':
			case 'cwp-3':
				$solrOptions = self::options_for_cwp($options);
				break;
			case 'local-4':
			case 'local-3':
				$solrOptions = self::options_for_local($options);
				break;
			case 'legacy':
			default:
				$solrOptions = self::options_from_environment();
				break;
		}

		if(file_exists(BASE_PATH.'/mysite/conf/extras')) {
			$solrOptions['extraspath'] = BASE_PATH.'/mysite/conf/extras';
		}

		Solr::configure_server($solrOptions);
	}

	static function options_from_environment() {
		return array(
			'host' => defined('SOLR_SERVER') ? SOLR_SERVER : 'localhost',
			'port' => defined('SOLR_PORT') ? SOLR_PORT : 8983,
			'path' => defined('SOLR_PATH') ? SOLR_PATH : '/solr/',
			'version' => defined('SOLR_VERSION') ? SOLR_VERSION : 3,

			'indexstore' => array(
				'mode' => defined('SOLR_MODE') ? SOLR_MODE : 'file',
				'auth' => defined('SOLR_AUTH') ? SOLR_AUTH : NULL,

				// Allow storing the solr index and config data in an arbitrary location,
				// e.g. outside of the webroot
				'path' => defined('SOLR_INDEXSTORE_PATH') ? SOLR_INDEXSTORE_PATH : BASE_PATH . '/.solr',
				'remotepath' => defined('SOLR_REMOTE_PATH') ? SOLR_REMOTE_PATH : null
			)
		);
	}

	static function options_for_cwp($options) {
		$version = $options['version'];

		return array(
			'host' => SOLR_SERVER,
			'port' => SOLR_PORT,
			'path' => ($version === 'cwp-3') ? '/v3/' : '/v4/',
			'version' => ($version === 'cwp-3') ? 3 : 4,

			'indexstore' => array(
				'mode' => 'CwpSolrConfigStore',
				'path' => ''
			)
		);
	}

	static function options_for_local($options) {
		$version = $options['version'];

		return array(
			'host' => defined('SOLR_SERVER') ? SOLR_SERVER : 'localhost',
			'port' => defined('SOLR_PORT') ? SOLR_PORT : 8983,
			'path' => defined('SOLR_PATH') ? SOLR_PATH : '/solr/',
			'version' => ($version === 'local-3') ? 3 : 4,

			'indexstore' => array(
				'mode' => defined('SOLR_MODE') ? SOLR_MODE : 'file',
				'auth' => defined('SOLR_AUTH') ? SOLR_AUTH : NULL,

				// Allow storing the solr index and config data in an arbitrary location,
				// e.g. outside of the webroot
				'path' => defined('SOLR_INDEXSTORE_PATH') ? SOLR_INDEXSTORE_PATH : BASE_PATH . '/.solr',
				'remotepath' => defined('SOLR_REMOTE_PATH') ? SOLR_REMOTE_PATH : null
			)
		);
	}
}
