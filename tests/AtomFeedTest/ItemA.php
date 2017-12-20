<?php

namespace CWP\Core\Tests\AtomFeedTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;
use SilverStripe\View\ViewableData;

class ItemA extends ViewableData implements TestOnly
{
    // Atom-feed items must have $casting/$db information.
    private static $casting = [
        'Title' => 'Varchar',
        'Content' => 'Text',
        'AltContent' => 'Text',
    ];

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
