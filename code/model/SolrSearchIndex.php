<?php

/**
 * Default search index
 */
class SolrSearchIndex extends CwpSearchIndex {

	public function init() {
		$this->addClass('SiteTree');
		$this->addAllFulltextFields();
		$this->addFilterField('ShowInSearch');
		
		parent::init();
	}
}
