<?php

namespace CWP\Core\Config;

use SilverStripe\Control\RequestFilter;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Environment;

/**
 * Initialises CWP-specific configuration settings, to avoid _config.php.
 */
class CwpInitialisationFilter implements RequestFilter
{

    /**
     * @var boolean
     *
     * Enable egress proxy. This works on the principle of setting http(s)_proxy environment variables,
     *  which will be automatically picked up by curl. This means RestfulService and raw curl
     *  requests should work out of the box. Stream-based requests need extra manual configuration.
     *  Refer to https://www.cwp.govt.nz/guides/core-technical-documentation/common-web-platform-core/en/how-tos/external_http_requests_with_proxy
     *
     * @config
     * @var bool
     */
    private static $egress_proxy_default_enabled = true;

    /**
     * @var array
     *
     * Configure the list of domains to bypass proxy by setting the NO_PROXY environment variable.
     * 'services.cwp.govt.nz' needs to be present for Solr and Docvert internal CWP integration.
     * 'localhost' is necessary for accessing services on the same instance such as tika-server for text extraction.
     *
     * @config
     * @var string[]
     */
    private static $egress_proxy_exclude_domains = [
        'services.cwp.govt.nz',
        'localhost',
    ];

    /**
     *
     * @param  HTTPRequest $request
     * @return boolean
     */
    public function preRequest(HTTPRequest $request)
    {
        if (Config::inst()->get(__CLASS__, 'egress_proxy_default_enabled')) {
            if (Environment::getEnv('SS_OUTBOUND_PROXY') && Environment::getEnv('SS_OUTBOUND_PROXY_PORT')) {
                $proxy = Environment::getEnv('SS_OUTBOUND_PROXY');
                $proxyPort = Environment::getEnv('SS_OUTBOUND_PROXY_PORT');

                Environment::setEnv('http_proxy', $proxy . ':' . $proxyPort);
                Environment::setEnv('https_proxy', $proxy. ':' . $proxyPort);
            }
        }

        $noProxy = Config::inst()->get(__CLASS__, 'egress_proxy_exclude_domains');

        if (!empty($noProxy)) {
            if (!is_array($noProxy)) {
                $noProxy = [$noProxy];
            }

            // Merge with exsiting if needed.
            if (Environment::getEnv('NO_PROXY')) {
                $noProxy = array_merge(explode(',', Environment::getEnv('NO_PROXY')), $noProxy);
            }

            Environment::setEnv('NO_PROXY', implode(',', array_unique($noProxy)));
        }

        return true;
    }

    public function postRequest(HTTPRequest $request, HTTPResponse $response)
    {
        return true;
    }
}
