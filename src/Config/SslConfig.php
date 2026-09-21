<?php

namespace CWP\Core\Config;

use SilverStripe\Control\Middleware\CanonicalURLMiddleware;

/**
 * Redirects the login and API URL patterns to HTTPS on test and live environments, and sends the
 * whole site to HTTPS on test where basic authentication would otherwise pass credentials in the
 * clear. CWP_SECURE_DOMAIN redirects those patterns to a specific domain.
 *
 * Applied in _config/security.yml. Disabling stops this module forcing HTTPS at the application
 * layer; the platform still terminates TLS, but nothing redirects a plain HTTP request.
 */
class SslConfig
{
    use FeatureToggle;

    /**
     * The URL patterns _config/security.yml forces to HTTPS. Set to null on test, where the whole
     * site is forced instead.
     *
     * @config
     */
    private static array $patterns = [
        '/^Security/',
        '/^api/',
    ];

    /**
     * The environments _config/security.yml enables the middleware for.
     *
     * @config
     */
    private static array $enabled_envs = [
        'live',
        'test',
    ];

    /**
     * The redirect domain _config/security.yml sets, exactly as written there. Injector resolves the
     * backticks when it builds the middleware, so this is also the value config reads back.
     *
     * @config
     */
    private static string $secure_domain = '`CWP_SECURE_DOMAIN`';

    public static function apply(): void
    {
        if (static::isEnabled()) {
            return;
        }

        // ForceSSL with no patterns redirects every URL, so the patterns only come off together
        // with the ForceSSL this module sets.
        if (static::injectorValue(CanonicalURLMiddleware::class, ['properties', 'ForceSSL']) !== true) {
            return;
        }

        static::restoreInjectorProperty(CanonicalURLMiddleware::class, 'ForceSSL', true, false);
        static::dropInjectorProperty(
            CanonicalURLMiddleware::class,
            'ForceSSLPatterns',
            static::config()->get('patterns')
        );
        static::dropInjectorProperty(
            CanonicalURLMiddleware::class,
            'EnabledEnvs',
            static::config()->get('enabled_envs')
        );
        static::dropInjectorProperty(
            CanonicalURLMiddleware::class,
            'ForceSSLDomain',
            static::config()->get('secure_domain')
        );
    }
}
