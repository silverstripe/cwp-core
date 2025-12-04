<?php

namespace CWP\Core\Tests\AtomFeedTest;

use SilverStripe\Dev\TestOnly;
use SSilverStripe\Model\ModelData;

class ItemC extends ModelData implements TestOnly
{
    // ItemC tests fields - Title has casting, Content doesn't.
    private static $casting = [
        'Title' => 'Varchar',
        'AltContent' => 'Text',
    ];

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
