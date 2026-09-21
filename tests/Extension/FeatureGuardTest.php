<?php

namespace CWP\Core\Tests\Extension;

use CWP\Core\Extension\CwpHtmlEditorConfig;
use CWP\Core\Extension\LoginAttemptNotifications;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Group;

/**
 * Covers the extensions that carry their own enabled flag. Each one keeps doing what it always did
 * while the flag is on, and steps out of the way once it is off.
 */
class FeatureGuardTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testHtmlEditorConfigFallsBackToCwpWhileEnabled()
    {
        $group = Group::create();

        $this->assertSame('cwp', $group->getHtmlEditorConfig());
    }

    public function testDisablingHtmlEditorConfigLeavesTheGroupOnTheCmsDefault()
    {
        Config::modify()->set(CwpHtmlEditorConfig::class, 'enabled', false);

        $group = Group::create();

        $this->assertNull($group->getHtmlEditorConfig());
    }

    public function testAnExplicitGroupConfigurationWinsRegardlessOfTheFlag()
    {
        $group = Group::create();
        $group->setField('HtmlEditorConfig', 'restricted');

        $this->assertSame('restricted', $group->getHtmlEditorConfig());

        Config::modify()->set(CwpHtmlEditorConfig::class, 'enabled', false);

        $this->assertSame('restricted', $group->getHtmlEditorConfig());
    }

    public function testLoginAttemptNotificationsReturnEarlyWhenDisabled()
    {
        Config::modify()->set(LoginAttemptNotifications::class, 'enabled', false);

        // No owner is set, so reaching any further than the flag check would fatal.
        $this->assertNull((new LoginAttemptNotifications())->init());
    }
}
