<?php

namespace CWP\Core\Model;

use SilverStripe\FullTextSearch\Solr\SolrIndex,
    SilverStripe\CMS\Model\SiteTree,
    SilverStripe\SiteConfig\SiteConfig,
    SilverStripe\FullTextSearch\Search\Queries\SearchQuery;

/**
 * Abstract wrapper for all cwp-core features
 *
 * Can be extended by user indexes to include these features. {@see SolrSearchIndex} for an example
 */
abstract class CwpSearchIndex extends SolrIndex
{

    /**
     * Copy all fields into both search and spellcheck data source
     *
     * @var array
     * @config
     */
    private static $copy_fields = array(
        '_text',
        '_spellcheckText'
    );

    /**
     * Default dictionary to use. This will overwrite the 'spellcheck.dictionary' option for searches given,
     * unless set to empty.
     *
     * '_spellcheck' is a predefined by the cwp infrastructure, which is configured to be built from the '_spellcheckText' field.
     * You can't rename this within CWP.
     *
     * @var string
     * @config
     */
    private static $dictionary = '_spellcheck';

    public function init()
    {
        // Add optional boost
        if (SiteTree::has_extension('CwpSearchBoostExtension')) {
            $this->setFieldBoosting('SiteTree_SearchBoost', SiteTree::config()->search_boost);
        }
    }

    /**
     * Upload config for this index to the given store
     *
     * @param SolrConfigStore $store
     */
    public function uploadConfig($store)
    {
        parent::uploadConfig($store);

        // Upload configured synonyms {@see SynonymsSiteConfig}
        $siteConfig = SiteConfig::current_site_config();
        if ($siteConfig->SearchSynonyms) {
            $store->uploadString(
                $this->getIndexName(), 'synonyms.txt', $siteConfig->SearchSynonyms
            );
        }
    }

    /**
     *
     * @return string
     */
    public function getFieldDefinitions()
    {
        $xml = parent::getFieldDefinitions();
        $xml .= "\n\n\t\t<!-- Additional custom fields for spell checking -->";
        $xml .= "\n\t\t<field name='_spellcheckText' type='textSpellHtml' indexed='true' stored='false' multiValued='true' />";

        return $xml;
    }

    /**
     *
     * @param \CWP\Core\Model\SearchQuery $query
     * @param type $offset
     * @param type $limit
     * @param type $params
     * @return ArrayData
     */
    public function search(SearchQuery $query, $offset = -1, $limit = -1, $params = [])
    {
        // Override dictionary if given
        if ($dictionary = $this->config()->dictionary) {
            $params["spellcheck.dictionary"] = $dictionary;
        }

        return parent::search($query, $offset, $limit, $params);
    }

}
