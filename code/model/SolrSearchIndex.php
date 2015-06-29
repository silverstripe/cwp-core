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

}
