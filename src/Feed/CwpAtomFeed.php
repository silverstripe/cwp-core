<?php

namespace CWP\Core\Feed;

/**
 * CwpAtomFeed class
 *
 * This class is used to create an Atom feed.
 * @package cwp-core
 */
use CWP\Core\Config\FeatureToggle;
use SilverStripe\Control\Controller;
use SilverStripe\Control\RSS\RSSFeed;
use SilverStripe\Core\Convert;
use SilverStripe\Model\List\SS_List;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\View\Requirements;

class CwpAtomFeed extends RSSFeed
{
    use FeatureToggle;

    public function __construct(
        SS_List $entries,
        $link,
        $title,
        ?string $description = null,
        $titleField = "Title",
        $descriptionField = "Content",
        $authorField = null,
        $lastModified = null,
        $etag = null
    ) {
        parent::__construct(
            $entries,
            $link,
            $title,
            $description,
            $titleField,
            $descriptionField,
            $authorField,
            $lastModified
        );

        // Templates are found by class hierarchy, so the Atom template would be picked up whether or
        // not it is set here. Point the feed back at the framework's RSS template when disabled.
        $this->setTemplate(static::isEnabled() ? __CLASS__ : RSSFeed::class);
    }

    /**
     * Include an link to the feed
     *
     * @param string $url URL of the feed
     * @param string $title Title to show
     */
    public static function linkToFeed($url, $title = null)
    {
        if (!static::isEnabled()) {
            parent::linkToFeed($url, $title);
            return;
        }

        $title = Convert::raw2xml($title);
        Requirements::insertHeadTags(
            '<link rel="alternate" type="application/atom+xml" title="' . $title .
            '" href="' . $url . '" />'
        );
    }

    /**
     * Output the feed to the browser
     *
     * @return DBHTMLText
     */
    public function outputToBrowser()
    {
        $output = parent::outputToBrowser();

        if (static::isEnabled()) {
            $response = Controller::curr()->getResponse();
            $response->addHeader("Content-Type", "application/atom+xml");
        }

        return $output;
    }
}
