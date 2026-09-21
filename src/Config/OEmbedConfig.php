<?php

namespace CWP\Core\Config;

/**
 * Routes oEmbed lookups through the platform egress proxy, so embedding a video or tweet works
 * from an environment with no direct outbound access.
 *
 * Applied in _config/oembed.yml, and only where SS_OUTBOUND_PROXY is set. Disabling leaves the
 * oEmbed HTTP client on its default configuration, which means those lookups go direct.
 */
class OEmbedConfig
{
    use FeatureToggle;

    /**
     * Injector service name of the HTTP client used for oEmbed lookups.
     *
     * @config
     */
    private static string $client_service = 'Psr\Http\Client\ClientInterface.oembed';

    /**
     * The proxy argument _config/oembed.yml sets, exactly as written there. Injector resolves the
     * backticks when it builds the service, so this is also the value config reads back.
     *
     * @config
     */
    private static string $proxy = '`SS_OUTBOUND_PROXY`:`SS_OUTBOUND_PROXY_PORT`';

    public static function apply(): void
    {
        if (static::isEnabled()) {
            return;
        }

        // Nothing else configures a constructor on this service, so the client falls back to the
        // framework's own definition once the proxy argument is gone.
        static::dropInjectorKey(
            static::config()->get('client_service'),
            'constructor',
            [['proxy' => static::config()->get('proxy')]]
        );
    }
}
