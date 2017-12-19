<?php

namespace CWP\Core\Tests;

use SilverStripe\ORM\ArrayList;
use CWP\Core\Feed\CwpAtomFeed;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Director;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Control\Controller;
use SilverStripe\View\ViewableData;

class AtomFeedTest extends SapphireTest
{

    protected static $original_host;

    public function testAtomFeed()
    {
        $list = new ArrayList();
        $list->push(new AtomFeedTest_ItemA());
        $list->push(new AtomFeedTest_ItemB());
        $list->push(new AtomFeedTest_ItemC());

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
        $link = AtomTags_Test::linkToFeed('atomLinkUrl', 'Atom feed of this blog');
        $this->assertContains('atomLinkUrl', $link);
        $this->assertContains('Atom feed of this blog', $link);
        $this->assertContains('application/atom+xml', $link);
    }

    public function setUp()
    {
        parent::setUp();
        Config::inst()->update(Director::class, 'alternate_base_url', '/');
        if (!self::$original_host) {
            self::$original_host = $_SERVER['HTTP_HOST'];
        }
        $_SERVER['HTTP_HOST'] = 'www.example.org';
    }

    public function tearDown()
    {
        parent::tearDown();
        Config::inst()->update(Director::class, 'alternate_base_url', null);
        $_SERVER['HTTP_HOST'] = self::$original_host;
    }
}

class AtomFeedTest_ItemA extends ViewableData
{
    // Atom-feed items must have $casting/$db information.
    private static $casting = array(
        'Title' => 'Varchar',
        'Content' => 'Text',
        'AltContent' => 'Text',
    );

    public $Title = 'ItemA';

    public function Title()
    {
        return "ItemA";
    }

    public function Content()
    {
        return "ItemA Content";
    }

    public function AltContent()
    {
        return "ItemA AltContent";
    }
    
    public function Link($action = null)
    {
        return Controller::join_links("item-a/", $action);
    }
}

class AtomFeedTest_ItemB extends ViewableData
{
    // ItemB tests without $casting

    public $Title = 'ItemB';

    public function Title()
    {
        return "ItemB";
    }

    public function AbsoluteLink()
    {
        return "http://www.example.com/item-b.html";
    }

    public function Content()
    {
        return "ItemB Content";
    }

    public function AltContent()
    {
        return "ItemB AltContent";
    }
}

class AtomFeedTest_ItemC extends ViewableData
{
    // ItemC tests fields - Title has casting, Content doesn't.
    private static $casting = array(
        'Title' => 'Varchar',
        'AltContent' => 'Text',
    );

    public $Title = 'ItemC';

    public function Title()
    {
        return "ItemC";
    }

    public function Content()
    {
        return "ItemC Content";
    }

    public $AltContent = "ItemC AltContent";

    public function Link()
    {
        return "item-c.html";
    }

    public function AbsoluteLink()
    {
        return "http://www.example.com/item-c.html";
    }
}

/**
 * Class to wrap cwpAtomFeed::linkToFeed so it can be tested
 * would be better if we could return the tags directly from Requirements
 * @subpackage tests
 */
class AtomTags_Test
{
    public static function linkToFeed($url, $title = null)
    {
        $link = '<link rel="alternate" type="application/atom+xml" title="' . $title .
            '" href="' . $url . '" />';
        CwpAtomFeed::linkToFeed($url, $title);
        return $link;
    }
}
