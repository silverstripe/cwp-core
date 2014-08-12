<?php

class CwpStatsReportTest extends SapphireTest {

	protected static $fixture_file = 'cwp-core/tests/CwpStatsReportTest.yml';

	public function testCount() {
		// Publish all pages apart from page3.
		$this->objFromFixture('Page', 'page1')->doPublish();
		$this->objFromFixture('Page', 'page2')->doPublish();
		$this->objFromFixture('Page', 'page3')->doPublish();

		// Add page5s to a subsite, if the module is installed.
		$page5s = $this->objFromFixture('Page', 'page5s');
		if(class_exists('Subsite')) {
			$subsite = Subsite::create();
			$subsite->Title = 'subsite';
			$subsite->write();

			$page5s->SubsiteID = $subsite->ID;
			$page5s->write();
		}
		$page5s->doPublish();

		$report = CwpStatsReport::create();
		$records = $report->sourceRecords(array())->toArray();
		$i = 0;
		$this->assertEquals($records[$i++]['Count'], 4, 'Four pages in total, across locales, subsites, live only.');
		if(class_exists('Subsite')) {
			$this->assertEquals($records[$i++]['Count'], 3, 'Three pages in the main site, if subsites installed.');
			$this->assertEquals($records[$i++]['Count'], 1, 'One page in the subsite, if subsites installed');
		}
		$this->assertEquals($records[$i++]['Count'], 1, 'One file in total.');
	}

}
