<?php

class SolrSearchIndex extends SolrIndex {

	public function init() {
		$this->addClass('SiteTree');
		$this->addAllFulltextFields();
		$this->addFilterField('ShowInSearch');
		
		// Add optional boost
		if(SiteTree::has_extension('CwpSearchBoostExtension')) {
			$this->setFieldBoosting('SiteTree_SearchBoost', SiteTree::config()->search_boost);
		}
	}
	
	/**
	 * Upload config for this index to the given store
	 * 
	 * @param SolrConfigStore $store
	 */
	public function uploadConfig($store) {
		parent::uploadConfig($store);
		
		// Upload configured synonyms {@see SynonymsSiteConfig}
		$siteConfig = SiteConfig::current_site_config();
		if($siteConfig->SearchSynonyms) {
			$store->uploadString(
				$this->getIndexName(),
				'synonyms.txt',
				$siteConfig->SearchSynonyms
			);
		}
	}

}
