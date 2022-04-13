<?php

namespace CWP\Core\Control;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Middleware\HTTPMiddleware;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;

/**
 * Initialises CWP-specific configuration settings, to avoid _config.php.
 */
class InitialisationMiddleware implements HTTPMiddleware
{
    use Configurable;

    /**
     * Disable the automatically added 'X-XSS-Protection' header that is added to all responses. This should be left
     * alone in most circumstances to include the header. Refer to Mozilla Developer Network for more information:
     * https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-XSS-Protection
     *
     * @config
     * @var bool
     */
    private static $xss_protection_enabled = true;

    /**
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
     * Provide a value for the HTTP Strict Transport Security header.
     * This header is only respected if you also redirect to SSL.
     *
     * Example configuration (short max-age, excluding dev environments):
     * ```yml
     * ---
     * Name: appsecurity
     * After: '#cwpsecurity'
     * Except:
     *   environment: dev
     * ---
     * CWP\Core\Control\InitialisationMiddleware:
     *   strict_transport_security: 'max-age=300'
     * SilverStripe\Core\Injector\Injector:
     *   SilverStripe\Control\Middleware\CanonicalURLMiddleware:
     *     properties:
     *       ForceSSL: true
     *       ForceSSLPatterns: null
     * ```
     *
     * Note: This is enabled by default in `cwp/installer` starting with 2.4.x,
     * see `app/_config/security.yml`.
     *
     * @see https://www.cwp.govt.nz/developer-docs/en/2/working_with_projects/security/
     * @config
     * @var string
     */
    private static $strict_transport_security = null;

    public function process(HTTPRequest $request, callable $delegate)
    {
        if ($this->config()->get('egress_proxy_default_enabled')) {
            $this->configureEgressProxy();
        }

        $this->configureProxyDomainExclusions();

        $response = $delegate($request);

        if ($this->config()->get('xss_protection_enabled') && $response) {
            $response->addHeader('X-XSS-Protection', '1; mode=block');
        }

        $hsts = $this->config()->get('strict_transport_security');
        if ($hsts && $response) {
            $response->addHeader('Strict-Transport-Security', $hsts);
        }

        return $response;
    }

    /**
     * If the outbound egress proxy details have been defined in environment variables, configure the proxy
     * variables that are used to configure it.
     */
    protected function configureEgressProxy()
    {
        if (!Environment::getEnv('SS_OUTBOUND_PROXY')
            || !Environment::getEnv('SS_OUTBOUND_PROXY_PORT')
        ) {
            return;
        }

        $proxy = Environment::getEnv('SS_OUTBOUND_PROXY');
        $proxyPort = Environment::getEnv('SS_OUTBOUND_PROXY_PORT');

        /*
         * This sets the environment variables so they are available in
         * external calls executed by exec() such as curl.
         * Environment::setEnv() would only availabe in context of SilverStripe.
         * Environment::getEnv() will fallback to getenv() and will therefore
         * fetch the variables
         */
        putenv('http_proxy=' .  $proxy . ':' . $proxyPort);
        putenv('https_proxy=' . $proxy . ':' . $proxyPort);
    }

    /**
     * Configure any domains that should be excluded from egress proxy rules and provide them to the environment
     */
    protected function configureProxyDomainExclusions()
    {
        $noProxy = $this->config()->get('egress_proxy_exclude_domains');
        if (empty($noProxy)) {
            return;
        }

        if (!is_array($noProxy)) {
            $noProxy = [$noProxy];
        }

        // Merge with exsiting if needed.
        if (Environment::getEnv('NO_PROXY')) {
            $noProxy = array_merge(explode(',', Environment::getEnv('NO_PROXY') ?? ''), $noProxy);
        }

        /*
         * Set the environment varial for NO_PROXY the same way the
         * proxy variables are set above
         */
        putenv('NO_PROXY=' . implode(',', array_unique($noProxy ?? [])));
    }
}
