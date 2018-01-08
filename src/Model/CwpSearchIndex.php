<?php

namespace CWP\Core\Model;

use CWP\CWP\Extensions\CwpSearchBoostExtension;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\FullTextSearch\Search\Queries\SearchQuery;
use SilverStripe\FullTextSearch\Solr\SolrIndex;
use SilverStripe\FullTextSearch\Solr\Stores\SolrConfigStore;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\View\ArrayData;

/**
 * Abstract wrapper for all cwp-core features
 *
 * Can be extended by user indexes to include these features. {@see CwpSolrIndex} for an example
 */
abstract class CwpSearchIndex extends SolrIndex
{

    /**
     * Copy all fields into both search and spellcheck data source
     *
     * @var array
     * @config
     */
    private static $copy_fields = [
        '_text',
        '_spellcheckText',
    ];

    /**
     * Default dictionary to use. This will overwrite the 'spellcheck.dictionary' option for searches given,
     * unless set to empty.
     *
     * '_spellcheck' is a predefined by the cwp infrastructure, which is configured
     * to be built from the '_spellcheckText' field. You can't rename this within CWP.
     *
     * @var string
     * @config
     */
    private static $dictionary = '_spellcheck';

    public function init()
    {
        // Add optional boost
        if (class_exists(CwpSearchBoostExtension::class)
            && SiteTree::has_extension(CwpSearchBoostExtension::class)
        ) {
            $this->setFieldBoosting(SiteTree::class . '_SearchBoost', SiteTree::config()->get('search_boost'));
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
                $this->getIndexName(),
                'synonyms.txt',
                $siteConfig->SearchSynonyms
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
        $xml .= "\n\t\t<field name='_spellcheckText' type='textSpellHtml' indexed='true' "
            . "stored='false' multiValued='true' />";

        return $xml;
    }

    /**
     *
     * @param SearchQuery $query
     * @param int $offset
     * @param int $limit
     * @param array $params
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
