<?php

namespace CWP\Core\Config;

use SilverStripe\Security\Validation\EntropyPasswordValidator;
use SilverStripe\Security\Validation\PasswordValidator;
use SilverStripe\Security\Validation\RulesPasswordValidator;

/**
 * Requires passwords of at least 10 characters drawn from at least 3 of lowercase, uppercase,
 * digits and punctuation, and blocks reuse of the last 6 passwords, in line with NZISM.
 *
 * Applied in _config/security.yml. Disabling restores the framework default, which scores
 * passwords on entropy instead.
 */
class PasswordStrengthConfig
{
    use FeatureToggle;

    /**
     * The validator properties _config/security.yml sets, and which disabling takes back off.
     *
     * @config
     */
    private static array $validator_properties = [
        'MinLength' => 10,
        'MinTestScore' => 3,
        'HistoricCount' => 6,
        'TestNames' => [
            'lowercase',
            'uppercase',
            'digits',
            'punctuation',
        ],
    ];

    public static function apply(): void
    {
        if (static::isEnabled()) {
            return;
        }

        static::restoreDefault(RulesPasswordValidator::class, 'min_length', 10);
        static::restoreDefault(RulesPasswordValidator::class, 'min_test_score', 3);
        static::restoreDefault(PasswordValidator::class, 'historic_count', 6);

        foreach (static::config()->get('validator_properties') as $property => $moduleValue) {
            static::dropInjectorProperty(PasswordValidator::class, $property, $moduleValue);
        }

        static::restoreInjectorKey(
            PasswordValidator::class,
            'class',
            RulesPasswordValidator::class,
            EntropyPasswordValidator::class
        );
    }
}
