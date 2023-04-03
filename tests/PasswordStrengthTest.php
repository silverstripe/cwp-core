<?php

namespace CWP\Core\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Member;
use SilverStripe\Security\PasswordValidator;
use SilverStripe\Core\Injector\Injector;

/**
 * Indeed it appears to only be testing config settings, however that isn't the main goal of this minor test suite. The
 * goal is more to catch 'regressions' should someone alter the values, given that the minimums tested here are a
 * requirement for compliance. The tests should still pass if passwords are strengthened with more checks or higher
 * character limits, for example. The values were previously removed due to duplication. However on inspection I could
 * not find where they were duplicated. I assume framework defaults - however I couldn't find where they were set there
 * either. This is merely an extra layer of assurance.
 *
 * E.g. the TestNames have no default in the core, and are not configurable. I didn't look too hard at mid-method
 * fallbacks, but it seemed a logical conclusion to add this back in via the use of Injector as seen in the
 * _config/sercurity.yml section of this PR. To ensure this is set I run the test - not because it's not a config
 * setting, but because it's also an Integration test - the PasswordValidator is always fetched via the way it's
 * created in use (not directly with new or only with Injector via create).
 *
 * This is my justification for adding this wee test suite.
 *
 * @group integration
 * @group compliance
 */
class PasswordStrengthTest extends SapphireTest
{
    protected function setUp(): void
    {
        // Out of the box, SapphireTest::setUp will deregister the PasswordValidator.
        // However, we need a PasswordValidator for these tests to work.
        $validator = Member::password_validator() ?: new PasswordValidator();
        parent::setUp();
        Member::set_password_validator($validator);
    }

    public function testPasswordMinLength()
    {
        $passwordValidator = Member::password_validator();
        $this->assertGreaterThanOrEqual(10, $passwordValidator->getMinLength());
    }

    public function testMinTestScore()
    {
        $passwordValidator = Member::password_validator();
        $this->assertGreaterThanOrEqual(3, $passwordValidator->getMinTestScore());
    }

    public function testHistoricCheckCount()
    {
        $passwordValidator = Member::password_validator();
        $this->assertGreaterThanOrEqual(6, $passwordValidator->getHistoricCount());
    }

    public function testTestNamesInclude()
    {
        $passwordValidator = Member::password_validator();
        $this->assertContains('lowercase', $passwordValidator->getTestNames());
        $this->assertContains('uppercase', $passwordValidator->getTestNames());
        $this->assertContains('digits', $passwordValidator->getTestNames());
        $this->assertContains('punctuation', $passwordValidator->getTestNames());
    }
}
