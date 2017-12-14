<?php

namespace CWP\Core\Feed;

/**
 * CwpAtomFeed class
 *
 * This class is used to create an Atom feed.
 * @todo Improve documentation
 * @package cwp-core
 */
use SilverStripe\Control\RSS\RSSFeed,
    SilverStripe\ORM\SS_List,
    SilverStripe\Core\Convert,
    SilverStripe\View\Requirements,
    SilverStripe\Control\Controller;

class CwpAtomFeed extends RSSFeed
{

    public function __construct(SS_List $entries, $link, $title, $description = null,
            $titleField = "Title", $descriptionField = "Content", $authorField = null,
            $lastModified = null, $etag = null)
    {
        parent::__construct($entries, $link, $title, $description, $titleField,
            $descriptionField, $authorField, $lastModified
        );

        $this->setTemplate('AtomFeed');
    }

    /**
     * Include an link to the feed
     *
     * @param string $url URL of the feed
     * @param string $title Title to show
     */
    public static function linkToFeed($url, $title = null)
    {
        $title = Convert::raw2xml($title);
        Requirements::insertHeadTags(
            '<link rel="alternate" type="application/atom+xml" title="' . $title .
            '" href="' . $url . '" />');
    }

    /**
     * Output the feed to the browser
     */
    public function outputToBrowser()
    {
        $output = parent::outputToBrowser();
        $response = Controller::curr()->getResponse();
        $response->addHeader("Content-Type", "application/atom+xml");

        return $output;
    }

}
