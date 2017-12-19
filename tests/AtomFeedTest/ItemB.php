<?php

namespace CWP\Core\Tests\AtomFeedTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\View\ViewableData;

class ItemB extends ViewableData implements TestOnly
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
