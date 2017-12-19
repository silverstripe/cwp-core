<?php

namespace CWP\Core\Tests;

use CWP\Core\Feed\CwpAtomFeed;
use CWP\Core\Tests\AtomFeedTest\AtomTagsStub;
use CWP\Core\Tests\AtomFeedTest\ItemA;
use CWP\Core\Tests\AtomFeedTest\ItemB;
use CWP\Core\Tests\AtomFeedTest\ItemC;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\ArrayList;

class AtomFeedTest extends SapphireTest
{
    protected static $original_host;

    public function testAtomFeed()
    {
        $list = new ArrayList();
        $list->push(new ItemA());
        $list->push(new ItemB());
        $list->push(new ItemC());

        $atomFeed = new CwpAtomFeed(
            $list,
            "http://www.example.com",
            "Test Atom Feed",
            "Test Atom Feed Description"
        );
        $content = $atomFeed->outputToBrowser();

        //Debug::message($content);
        $this->assertContains('<link href="http://www.example.org/item-a/" />', $content);
        $this->assertContains('<link href="http://www.example.com/item-b.html" />', $content);
        $this->assertContains('<link href="http://www.example.com/item-c.html" />', $content);

        $this->assertContains('<title type="html">ItemA</title>', $content);
        $this->assertContains('<title type="html">ItemB</title>', $content);
        $this->assertContains('<title type="html">ItemC</title>', $content);

        $this->assertContains("\tItemA Content\n", $content);
        $this->assertContains("\tItemB Content\n", $content);
        $this->assertContains("\tItemC Content\n", $content);
    }

    public function testRenderWithTemplate()
    {
        $atomFeed = new CwpAtomFeed(new ArrayList(), "", "", "");
        $content = $atomFeed->outputToBrowser();
        // test we have switched from a RSS feed test template tot he AtomFeed template
        $this->assertNotContains('<title>Test Custom Template</title>', $content);
    }

    public function testLinkToFeed()
    {
        $link = AtomTagsStub::linkToFeed('atomLinkUrl', 'Atom feed of this blog');
        $this->assertContains('atomLinkUrl', $link);
        $this->assertContains('Atom feed of this blog', $link);
        $this->assertContains('application/atom+xml', $link);
    }

    protected function setUp()
    {
        parent::setUp();
        Config::modify()->set(Director::class, 'alternate_base_url', '/');
        if (!self::$original_host) {
            self::$original_host = $_SERVER['HTTP_HOST'];
        }
        $_SERVER['HTTP_HOST'] = 'www.example.org';
    }

    protected function tearDown()
    {
        parent::tearDown();
        Config::modify()->set(Director::class, 'alternate_base_url', null);
        $_SERVER['HTTP_HOST'] = self::$original_host;
    }
}
