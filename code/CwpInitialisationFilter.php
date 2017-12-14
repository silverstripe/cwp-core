<?php

namespace CWP\Core\Config;

use SilverStripe\Control\RequestFilter,
    SilverStripe\Core\Config\Config,
    SilverStripe\Control\HTTPRequest,
    SilverStripe\Control\HTTPResponse;

/**
 * Initialises CWP-specific configuration settings, to avoid _config.php.
 */
class CwpInitialisationFilter implements RequestFilter
{

    /**
     * @var boolean
     *
     * Enable egress proxy. This works on the principle of setting http(s)_proxy environment variables,
     * 	which will be automatically picked up by curl. This means RestfulService and raw curl
     * 	requests should work out of the box. Stream-based requests need extra manual configuration.
     * 	Refer to https://www.cwp.govt.nz/guides/core-technical-documentation/common-web-platform-core/en/how-tos/external_http_requests_with_proxy
     */
    private static $egress_proxy_default_enabled = true;

    /**
     * @var array
     *
     * Configure the list of domains to bypass proxy by setting the NO_PROXY environment variable.
     * 'services.cwp.govt.nz' needs to be present for Solr and Docvert internal CWP integration.
     * 'localhost' is necessary for accessing services on the same instance such as tika-server for text extraction.
     */
    private static $egress_proxy_exclude_domains = array(
        'services.cwp.govt.nz',
        'localhost'
    );

    /**
     *
     * @param  HTTPRequest $request
     * @return boolean
     */
    public function preRequest(HTTPRequest $request)
    {
        if (Config::inst()->get('CwpInitialisationFilter', 'egress_proxy_default_enabled')) {
            if (defined('SS_OUTBOUND_PROXY') && defined('SS_OUTBOUND_PROXY_PORT')) {
                putenv('http_proxy=' . SS_OUTBOUND_PROXY . ':' . SS_OUTBOUND_PROXY_PORT);
                putenv('https_proxy=' . SS_OUTBOUND_PROXY . ':' . SS_OUTBOUND_PROXY_PORT);
            }
        }

        $noProxy = Config::inst()->get('CwpInitialisationFilter', 'egress_proxy_exclude_domains');

        if (!empty($noProxy)) {
            if (!is_array($noProxy)) {
                $noProxy = array($noProxy);
            }

            // Merge with exsiting if needed.
            if (getenv('NO_PROXY')) {
                $noProxy = array_merge(explode(',', getenv('NO_PROXY')), $noProxy);
            }

            putenv('NO_PROXY=' . implode(',', array_unique($noProxy)));
        }

        return true;
    }

    /**
     *
     * @param \CWP\Core\Config\SS_HTTPRequest $request
     * @param \CWP\Core\Config\SS_HTTPResponse $response
     * @return boolean
     */
    public function postRequest(HTTPRequest $request, HTTPResponse $response)
    {
        return true;
    }

}
