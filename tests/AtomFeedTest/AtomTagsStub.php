<?php

namespace CWP\Core\Tests\AtomFeedTest;

use CWP\Core\Feed\CwpAtomFeed;
use SilverStripe\Dev\TestOnly;

/**
 * Class to wrap CwpAtomFeed::linkToFeed so it can be tested
 * would be better if we could return the tags directly from Requirements
 */
class AtomTagsStub implements TestOnly
{
    public static function linkToFeed($url, $title = null)
    {
        $link = '<link rel="alternate" type="application/atom+xml" title="' . $title .
            '" href="' . $url . '" />';
        CwpAtomFeed::linkToFeed($url, $title);
        return $link;
    }
}
