<?php

namespace CWP\Core\Model;

use CWP\Core\Model\CwpSearchIndex,
    SilverStripe\CMS\Model\SiteTree;

/**
 * Default search index
 */
class SolrSearchIndex extends CwpSearchIndex
{

    /**
     * @return void
     */
    public function init()
    {
        $this->addClass(SiteTree::class);

        // By default, we only add text fields that are 'visible' to users (where the content is directly visible on
        // the website), along with the 'meta' fields that are commonly used to boost / refine search results
        $this->addFulltextField('Title');
        $this->addFulltextField('MenuTitle');
        $this->addFulltextField('Content');
        $this->addFulltextField('MetaDescription');
        $this->addFulltextField('ExtraMeta');

        // Adds 'ShowInSearch' boolean field to Solr document so we can later ensure that only documents included in
        // search are returned by Solr.
        $this->addFilterField('ShowInSearch');

        parent::init();
    }

}
