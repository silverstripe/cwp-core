<?php

namespace CWP\Core\Config;

use Monolog\Logger;
use Psr\Log\LoggerInterface;
use SilverStripe\Core\Injector\Injector;

/**
 * Sends errors to syslog, which the platform routes to Graylog.
 *
 * Applied in _config/logging.yml. The handler is pushed onto the shared logger through Injector,
 * and that logger is built during boot before any _config.php runs, so disabling has to pull the
 * handler off the instance as well as out of the config.
 */
class LoggingConfig
{
    use FeatureToggle;

    /**
     * Injector service name of the syslog handler this module pushes.
     *
     * @config
     */
    private static string $handler_service = 'Monolog\Handler\HandlerInterface.silverstripe';

    /**
     * Name of the Injector `calls` entry that pushes the handler.
     *
     * @config
     */
    private static string $handler_call = 'pushSilverStripeSyslogHandler';

    public static function apply(): void
    {
        if (static::isEnabled()) {
            return;
        }

        // Nothing else registers this call, so dropping it leaves any handler another module
        // pushed onto the shared logger in place.
        static::dropInjectorCall(LoggerInterface::class, static::config()->get('handler_call'), [
            'pushHandler',
            ['%$' . static::config()->get('handler_service')],
        ]);

        $logger = Injector::inst()->get(LoggerInterface::class);
        if (!$logger instanceof Logger) {
            return;
        }

        $handler = Injector::inst()->get(static::config()->get('handler_service'));
        $logger->setHandlers(array_values(array_filter(
            $logger->getHandlers(),
            fn ($pushed) => $pushed !== $handler
        )));
    }
}
