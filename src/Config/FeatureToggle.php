<?php

namespace CWP\Core\Config;

use ReflectionClass;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injector;

/**
 * Shared behaviour for every feature this module ships.
 *
 * Each class using this trait owns one feature, switched off from project YAML with
 * `enabled: false`. The feature's defaults live in this module's YAML; `apply()` only acts when
 * the flag is off, putting the affected config back. Every revert first checks the value still
 * matches what this module set, so a project's own value is left alone.
 */
trait FeatureToggle
{
    use Configurable;

    /**
     * Whether the config this class owns is applied. Turn off from project YAML to opt out of the
     * defaults documented for this feature in the module README.
     *
     * @config
     */
    private static bool $enabled = true;

    public static function isEnabled(): bool
    {
        return (bool) static::config()->get('enabled');
    }

    /**
     * Restore $class.$name to the value declared on the class, but only while it still holds
     * $moduleValue - the value this module sets in its own YAML. Anything else means a project or
     * another module configured it deliberately, and that takes precedence over opting out here.
     */
    protected static function restoreDefault(string $class, string $name, mixed $moduleValue): void
    {
        if (Config::inst()->get($class, $name) !== $moduleValue) {
            return;
        }

        Config::modify()->set($class, $name, static::declaredDefault($class, $name));
    }

    /**
     * Drop entries from the array config at $class.$name, leaving any other entries alone.
     *
     * An entry matches on either its value or its key, because config like
     * {@see SilverStripe\Admin\LeftAndMain::$extra_requirements_css} and the `extensions` lists
     * accept both a plain list and a map keyed by the same identifier. Keys on the entries that
     * stay are preserved, since dropping them changes how the framework reads the rest.
     */
    protected static function removeFromArray(string $class, string $name, array $values): void
    {
        $current = Config::inst()->get($class, $name);
        if (!is_array($current)) {
            return;
        }

        $remaining = [];
        foreach ($current as $key => $entry) {
            if (in_array($key, $values, true) || in_array($entry, $values, true)) {
                continue;
            }
            $remaining[$key] = $entry;
        }

        Config::modify()->set($class, $name, $remaining);
    }

    /**
     * Drop a top level key from an Injector service definition, leaving the rest of the definition
     * intact. Used where this module layers onto a service that no other module configures, so the
     * service falls back to its own default once the key is gone.
     */
    protected static function dropInjectorKey(string $service, string $key, mixed $moduleValue): void
    {
        static::rewriteInjectorSpec($service, [$key], $moduleValue, true, null);
    }

    /**
     * Put a top level key of an Injector service definition back to $fallback, the value another
     * module declares for it. Used where this module overrides a service another module defines:
     * dropping the key would take that module's definition with it rather than reverting to it.
     */
    protected static function restoreInjectorKey(
        string $service,
        string $key,
        mixed $moduleValue,
        mixed $fallback
    ): void {
        static::rewriteInjectorSpec($service, [$key], $moduleValue, false, $fallback);
    }

    /**
     * Drop a property from the `properties` block of an Injector service definition, so the
     * service keeps the default declared on the class.
     */
    protected static function dropInjectorProperty(string $service, string $property, mixed $moduleValue): void
    {
        static::rewriteInjectorSpec($service, ['properties', $property], $moduleValue, true, null);
    }

    /**
     * Put a property of an Injector service definition back to $fallback, the value another module
     * declares for it.
     */
    protected static function restoreInjectorProperty(
        string $service,
        string $property,
        mixed $moduleValue,
        mixed $fallback
    ): void {
        static::rewriteInjectorSpec($service, ['properties', $property], $moduleValue, false, $fallback);
    }

    /**
     * Drop a named `calls` entry from an Injector service definition, leaving calls another module
     * registered on the same service in place.
     */
    protected static function dropInjectorCall(string $service, string $call, mixed $moduleValue): void
    {
        static::rewriteInjectorSpec($service, ['calls', $call], $moduleValue, true, null);
    }

    /**
     * The value an Injector service definition currently holds at $path, or null where the service
     * or any step of the path is not configured.
     */
    protected static function injectorValue(string $service, array $path): mixed
    {
        $value = Config::inst()->get(Injector::class, $service);

        foreach ($path as $step) {
            if (!is_array($value) || !array_key_exists($step, $value)) {
                return null;
            }
            $value = $value[$step];
        }

        return $value;
    }

    /**
     * Change one leaf of an Injector service definition, but only while that leaf still holds
     * $moduleValue, the value this module's YAML sets for it. $drop unsets the leaf; otherwise it is
     * set to $fallback, for services another module defines underneath this one.
     */
    private static function rewriteInjectorSpec(
        string $service,
        array $path,
        mixed $moduleValue,
        bool $drop,
        mixed $fallback
    ): void {
        $spec = Config::inst()->get(Injector::class, $service);
        if (!is_array($spec) || static::injectorValue($service, $path) !== $moduleValue) {
            return;
        }

        $leaf = array_pop($path);
        $cursor = &$spec;
        foreach ($path as $step) {
            $cursor = &$cursor[$step];
        }

        if ($drop) {
            unset($cursor[$leaf]);
        } else {
            $cursor[$leaf] = $fallback;
        }
        unset($cursor);

        Config::modify()->set(Injector::class, $service, $spec);
    }

    /**
     * The value a config property is declared with in PHP, ignoring anything YAML has merged on
     * top. Private statics are not visible to a subclass, so walk up until the declaring class.
     */
    protected static function declaredDefault(string $class, string $name): mixed
    {
        while ($class && class_exists($class)) {
            $defaults = (new ReflectionClass($class))->getDefaultProperties();
            if (array_key_exists($name, $defaults)) {
                return $defaults[$name];
            }
            $class = get_parent_class($class);
        }

        return null;
    }
}
