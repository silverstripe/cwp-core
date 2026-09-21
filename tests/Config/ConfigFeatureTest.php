<?php

namespace CWP\Core\Tests\Config;

use CWP\Core\Config\CmsStylesConfig;
use CWP\Core\Config\LocaleConfig;
use CWP\Core\Config\MemberSecurityConfig;
use CWP\Core\Config\OEmbedConfig;
use CWP\Core\Config\PasswordEncryptionConfig;
use CWP\Core\Config\PasswordFieldConfig;
use CWP\Core\Config\PasswordStrengthConfig;
use CWP\Core\Config\QueuedJobsConfig;
use CWP\Core\Config\SessionConfig;
use CWP\Core\Config\SslConfig;
use CWP\Core\Config\TextExtractionConfig;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\Middleware\CanonicalURLMiddleware;
use SilverStripe\Control\Session;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\PasswordField;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Security\Validation\EntropyPasswordValidator;
use SilverStripe\Security\Validation\PasswordValidator;
use SilverStripe\Security\Validation\RulesPasswordValidator;
use SilverStripe\i18n\i18n;

/**
 * Covers the feature classes that own configuration rather than behaviour. Each applies its
 * defaults through YAML and only acts on apply() when its flag is off, so these tests assert what
 * the site is left with once a feature has been disabled.
 */
class ConfigFeatureTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testDefaultsAreLeftInPlaceWhileEnabled()
    {
        SessionConfig::apply();
        MemberSecurityConfig::apply();

        $this->assertSame(1440, Config::inst()->get(Session::class, 'timeout'));
        $this->assertSame(5, Config::inst()->get(Member::class, 'lock_out_after_incorrect_logins'));
    }

    public function testEveryFeatureIsEnabledByDefault()
    {
        foreach ($this->featureClasses() as $class) {
            $this->assertTrue(
                $class::isEnabled(),
                $class . ' should ship enabled, so an existing project is unaffected'
            );
        }
    }

    public function testDisablingSessionConfigRestoresFrameworkDefaults()
    {
        Config::modify()->set(SessionConfig::class, 'enabled', false);
        SessionConfig::apply();

        $this->assertSame(0, Config::inst()->get(Session::class, 'timeout'));
        $this->assertTrue(Config::inst()->get(LeftAndMain::class, 'session_keepalive_ping'));
    }

    public function testDisablingAFeatureLeavesAProjectConfiguredValueAlone()
    {
        Config::modify()->set(Session::class, 'timeout', 3600);
        Config::modify()->set(SessionConfig::class, 'enabled', false);
        SessionConfig::apply();

        $this->assertSame(
            3600,
            Config::inst()->get(Session::class, 'timeout'),
            'Only the value this module set is reverted'
        );
    }

    public function testDisablingMemberSecurityConfigRestoresFrameworkDefaults()
    {
        Config::modify()->set(MemberSecurityConfig::class, 'enabled', false);
        MemberSecurityConfig::apply();

        $this->assertSame(10, Config::inst()->get(Member::class, 'lock_out_after_incorrect_logins'));
    }

    public function testDisablingLocaleConfigRestoresFrameworkDefault()
    {
        // The test suite runs on en_US, so put the module's own value back before disabling
        Config::modify()->set(i18n::class, 'default_locale', 'en_GB');
        Config::modify()->set(LocaleConfig::class, 'enabled', false);
        LocaleConfig::apply();

        $this->assertSame('en_US', Config::inst()->get(i18n::class, 'default_locale'));
    }

    public function testDisablingPasswordFieldConfigRestoresFrameworkDefault()
    {
        $this->assertFalse(Config::inst()->get(PasswordField::class, 'autocomplete'));

        Config::modify()->set(PasswordFieldConfig::class, 'enabled', false);
        PasswordFieldConfig::apply();

        $this->assertNull(Config::inst()->get(PasswordField::class, 'autocomplete'));
    }

    public function testDisablingPasswordEncryptionConfigRestoresFrameworkDefault()
    {
        $this->assertSame('pbkdf2_sha512', Config::inst()->get(Security::class, 'password_encryption_algorithm'));

        Config::modify()->set(PasswordEncryptionConfig::class, 'enabled', false);
        PasswordEncryptionConfig::apply();

        $this->assertSame('blowfish', Config::inst()->get(Security::class, 'password_encryption_algorithm'));
    }

    public function testPasswordStrengthConfigAppliesNzismRules()
    {
        $this->assertSame(10, Config::inst()->get(RulesPasswordValidator::class, 'min_length'));
        $this->assertSame(3, Config::inst()->get(RulesPasswordValidator::class, 'min_test_score'));
        $this->assertSame(6, Config::inst()->get(PasswordValidator::class, 'historic_count'));
    }

    public function testDisablingPasswordStrengthConfigRestoresEntropyValidator()
    {
        // SapphireTest::setUp() calls Member::set_password_validator(null), which removes this
        // service outright, so put back what _config/security.yml sets before disabling.
        $this->setModulePasswordValidatorSpec();
        Config::modify()->set(PasswordStrengthConfig::class, 'enabled', false);
        PasswordStrengthConfig::apply();

        $spec = Config::inst()->get(Injector::class, PasswordValidator::class);

        $this->assertSame(EntropyPasswordValidator::class, $spec['class']);
        $this->assertEmpty($spec['properties'] ?? []);
        $this->assertSame(8, Config::inst()->get(RulesPasswordValidator::class, 'min_length'));
    }

    public function testSslConfigForcesSslOnLoginAndApiUrls()
    {
        $spec = Config::inst()->get(Injector::class, CanonicalURLMiddleware::class);

        // ForceSSL itself is switched off again on dev, where the test suite runs
        $this->assertSame(['/^Security/', '/^api/'], $spec['properties']['ForceSSLPatterns']);
        $this->assertSame(['live', 'test'], $spec['properties']['EnabledEnvs']);
    }

    public function testDisablingSslConfigStopsForcingSsl()
    {
        Config::modify()->set(SslConfig::class, 'enabled', false);
        $this->forceSslOn();
        SslConfig::apply();

        $spec = Config::inst()->get(Injector::class, CanonicalURLMiddleware::class);

        $this->assertFalse($spec['properties']['ForceSSL']);
        $this->assertArrayNotHasKey('ForceSSLPatterns', $spec['properties']);
        $this->assertArrayNotHasKey('EnabledEnvs', $spec['properties']);
    }

    /**
     * The redirect domain is stored as an unresolved backtick string, so it has to be matched as one.
     */
    public function testDisablingSslConfigDropsTheSecureDomain()
    {
        Config::modify()->set(SslConfig::class, 'enabled', false);
        $this->forceSslOn();
        Config::modify()->merge(Injector::class, CanonicalURLMiddleware::class, [
            'properties' => ['ForceSSLDomain' => '`CWP_SECURE_DOMAIN`'],
        ]);
        SslConfig::apply();

        $spec = Config::inst()->get(Injector::class, CanonicalURLMiddleware::class);

        $this->assertArrayNotHasKey('ForceSSLDomain', $spec['properties']);
    }

    /**
     * ForceSSL with no patterns redirects every URL rather than none, so the patterns can only be
     * taken away together with ForceSSL itself.
     */
    public function testDisablingSslConfigLeavesAProjectsOwnForceSslAlone()
    {
        $this->forceSslOn();
        $this->setInjectorProperty(CanonicalURLMiddleware::class, 'ForceSSL', 'onlyOnTuesdays');
        Config::modify()->set(SslConfig::class, 'enabled', false);
        SslConfig::apply();

        $spec = Config::inst()->get(Injector::class, CanonicalURLMiddleware::class);

        $this->assertSame('onlyOnTuesdays', $spec['properties']['ForceSSL']);
        $this->assertSame(
            ['/^Security/', '/^api/'],
            $spec['properties']['ForceSSLPatterns'],
            'Dropping the patterns on their own would force HTTPS on every URL'
        );
    }

    public function testDisablingPasswordStrengthConfigLeavesAProjectsOwnValidatorAlone()
    {
        $this->setModulePasswordValidatorSpec();
        $this->setInjectorKey(PasswordValidator::class, 'class', 'App\Security\StrictPasswordValidator');
        Config::modify()->set(PasswordStrengthConfig::class, 'enabled', false);
        PasswordStrengthConfig::apply();

        $spec = Config::inst()->get(Injector::class, PasswordValidator::class);

        $this->assertSame('App\Security\StrictPasswordValidator', $spec['class']);
    }

    public function testDisablingCmsStylesConfigOnlyRemovesTheCwpStylesheet()
    {
        Config::modify()->merge(LeftAndMain::class, 'extra_requirements_css', ['app:css/admin.css']);
        Config::modify()->set(CmsStylesConfig::class, 'enabled', false);
        CmsStylesConfig::apply();

        $css = Config::inst()->get(LeftAndMain::class, 'extra_requirements_css');

        $this->assertNotContains('cwp/cwp-core:css/custom.css', $css);
        $this->assertContains('app:css/admin.css', $css);
    }

    /**
     * extra_requirements_css also takes a map of stylesheet to options. Renumbering that on the way
     * past turns each option array into a filename, and the CMS fatals on the next request.
     */
    public function testDisablingCmsStylesConfigKeepsTheKeysOnAKeyedStylesheetList()
    {
        Config::modify()->merge(LeftAndMain::class, 'extra_requirements_css', [
            'app:css/admin.css' => ['media' => 'screen'],
        ]);
        Config::modify()->set(CmsStylesConfig::class, 'enabled', false);
        CmsStylesConfig::apply();

        $css = Config::inst()->get(LeftAndMain::class, 'extra_requirements_css');

        $this->assertSame(['media' => 'screen'], $css['app:css/admin.css']);
        $this->assertNotContains('cwp/cwp-core:css/custom.css', $css);
    }

    /**
     * The same list keyed by stylesheet, with this module's own entry as one of the keys.
     */
    public function testDisablingCmsStylesConfigRemovesTheCwpStylesheetWhenItIsAKey()
    {
        Config::modify()->set(LeftAndMain::class, 'extra_requirements_css', [
            'cwp/cwp-core:css/custom.css' => ['media' => 'screen'],
            'app:css/admin.css' => ['media' => 'print'],
        ]);
        Config::modify()->set(CmsStylesConfig::class, 'enabled', false);
        CmsStylesConfig::apply();

        $css = Config::inst()->get(LeftAndMain::class, 'extra_requirements_css');

        $this->assertArrayNotHasKey('cwp/cwp-core:css/custom.css', $css);
        $this->assertArrayHasKey('app:css/admin.css', $css);
    }

    public function testDisablingOEmbedConfigLeavesTheServiceUsable()
    {
        $service = OEmbedConfig::config()->get('client_service');

        // _config/oembed.yml is skipped on dev, where the test suite runs, so put back what it
        // would have set. The backticks are deliberate: Injector resolves those to environment
        // variables when it builds the service, so the config layer holds them literally, and
        // seeding a resolved value here would pass against a guard that can never match.
        $this->setInjectorKey($service, 'class', 'GuzzleHttp\Client');
        $this->setInjectorKey($service, 'constructor', [
            ['proxy' => '`SS_OUTBOUND_PROXY`:`SS_OUTBOUND_PROXY_PORT`'],
        ]);

        Config::modify()->set(OEmbedConfig::class, 'enabled', false);
        OEmbedConfig::apply();

        $spec = Config::inst()->get(Injector::class, $service);

        $this->assertArrayNotHasKey('constructor', $spec);
        $this->assertSame('GuzzleHttp\Client', $spec['class'], 'The class the framework defines is left in place');
    }

    /**
     * The proxy value is composed from environment variables, and Injector resolves those only
     * when it builds the service. Reading it back from config yields the unresolved string, so
     * that is what the teardown has to compare against.
     */
    public function testOEmbedConfigComparesAgainstTheUnresolvedProxyValue()
    {
        $service = OEmbedConfig::config()->get('client_service');

        Environment::setEnv('SS_OUTBOUND_PROXY', 'http://proxy.example.com');
        Environment::setEnv('SS_OUTBOUND_PROXY_PORT', '3128');

        $this->setInjectorKey($service, 'class', 'GuzzleHttp\Client');
        $this->setInjectorKey($service, 'constructor', [
            ['proxy' => '`SS_OUTBOUND_PROXY`:`SS_OUTBOUND_PROXY_PORT`'],
        ]);

        Config::modify()->set(OEmbedConfig::class, 'enabled', false);
        OEmbedConfig::apply();

        $spec = Config::inst()->get(Injector::class, $service);

        $this->assertArrayNotHasKey(
            'constructor',
            $spec,
            'The proxy argument is dropped even though the environment variables are set'
        );
    }

    /**
     * A project that configured its own proxy keeps it, the same as every other feature here.
     */
    public function testDisablingOEmbedConfigLeavesAProjectsOwnProxyAlone()
    {
        $service = OEmbedConfig::config()->get('client_service');

        $this->setInjectorKey($service, 'class', 'GuzzleHttp\Client');
        $this->setInjectorKey($service, 'constructor', [
            ['proxy' => 'http://project-proxy.example.com:8080'],
        ]);

        Config::modify()->set(OEmbedConfig::class, 'enabled', false);
        OEmbedConfig::apply();

        $spec = Config::inst()->get(Injector::class, $service);

        $this->assertSame(
            [['proxy' => 'http://project-proxy.example.com:8080']],
            $spec['constructor'],
            'A proxy this module did not set is left in place'
        );
    }

    /**
     * Where this module overrides a service another module defines, disabling has to put that
     * module's value back. Dropping the key would leave the service with nothing to build.
     */
    public function testDisablingTextExtractionConfigReturnsTheCacheToTheModulesOwnImplementation()
    {
        $service = TextExtractionConfig::config()->get('cache_service');
        $this->setInjectorKey($service, 'class', TextExtractionConfig::config()->get('cache_class'));

        Config::modify()->set(TextExtractionConfig::class, 'enabled', false);
        TextExtractionConfig::apply();

        $spec = Config::inst()->get(Injector::class, $service);

        $this->assertSame(
            TextExtractionConfig::config()->get('cache_class_default'),
            $spec['class'],
            'The service name is an interface, so it cannot be left without a class'
        );
    }

    public function testDisablingQueuedJobsConfigReturnsTheRunnerToTheModulesOwnDefault()
    {
        $service = QueuedJobsConfig::config()->get('service');
        Config::modify()->merge(Injector::class, $service, [
            'properties' => ['queueRunner' => QueuedJobsConfig::config()->get('runner')],
        ]);

        Config::modify()->set(QueuedJobsConfig::class, 'enabled', false);
        QueuedJobsConfig::apply();

        $spec = Config::inst()->get(Injector::class, $service);

        $this->assertSame(
            QueuedJobsConfig::config()->get('runner_default'),
            $spec['properties']['queueRunner'],
            'A service with no runner cannot process jobs at all'
        );
    }

    /**
     * Every feature class has to appear in _config/features.yml, which the README points at as the
     * one place to see what this module does.
     */
    public function testEveryFeatureClassIsListedInTheFeaturesConfig()
    {
        $features = file_get_contents(__DIR__ . '/../../_config/features.yml');

        foreach ($this->featureClasses() as $class) {
            $this->assertStringContainsString(
                $class . ":\n  enabled: true",
                $features,
                $class . ' is missing from _config/features.yml'
            );
        }
    }

    /**
     * @return string[]
     */
    private function featureClasses(): array
    {
        $classes = [];
        foreach (glob(__DIR__ . '/../../src/Config/*Config.php') as $file) {
            $classes[] = 'CWP\\Core\\Config\\' . basename($file, '.php');
        }

        $this->assertNotEmpty($classes);

        return $classes;
    }

    /**
     * Write one top level key of an Injector service definition, the way a project's own YAML would.
     */
    private function setInjectorKey(string $service, string $key, mixed $value): void
    {
        $spec = Config::inst()->get(Injector::class, $service);
        $spec = is_array($spec) ? $spec : [];
        $spec[$key] = $value;

        Config::modify()->set(Injector::class, $service, $spec);
    }

    /**
     * Write one property of an Injector service definition, the way a project's own YAML would.
     */
    private function setInjectorProperty(string $service, string $property, mixed $value): void
    {
        $spec = Config::inst()->get(Injector::class, $service);
        $spec = is_array($spec) ? $spec : [];
        $spec['properties'][$property] = $value;

        Config::modify()->set(Injector::class, $service, $spec);
    }

    /**
     * The password validator service as _config/security.yml defines it.
     */
    private function setModulePasswordValidatorSpec(): void
    {
        Config::modify()->set(Injector::class, PasswordValidator::class, [
            'class' => RulesPasswordValidator::class,
            'properties' => PasswordStrengthConfig::config()->get('validator_properties'),
        ]);
    }

    /**
     * The test suite runs on dev, where _config/security.yml switches ForceSSL back off. Put the
     * test and live value back so there is something for SslConfig to turn off.
     */
    private function forceSslOn(): void
    {
        Config::modify()->merge(Injector::class, CanonicalURLMiddleware::class, [
            'properties' => ['ForceSSL' => true],
        ]);
    }
}
