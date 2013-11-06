<?php

class CwpControllerExtensionTest extends SapphireTest {

	function testRequiresLogin() {
		Session::set("loggedInAs", null);

		$ctrl = new Controller();
		$req = new SS_HTTPRequest('GET', '/');
		$dataModel = new DataModel();

		Config::inst()->nest();
		Config::inst()->update('Director', 'environment_type', 'test');

		try {
			$response = $ctrl->handleRequest($req, $dataModel);
		} catch (Exception $e) {
			$this->assertEquals($e->getResponse()->getStatusCode(), '401', 'Forces BasicAuth on test');

			// We need to pop manually, since throwing an SS_HTTPResponse_Exception in onBeforeInit hijacks
			// the normal Controller control flow and confuses TestRunner (as they share global controller stack).
			$ctrl->popCurrent();
		}

		Config::inst()->update('Director', 'environment_type', 'live');
		$response = $ctrl->handleRequest($req, $dataModel);
		$this->assertEquals($response->getStatusCode(), '200', 'Does not force BasicAuth on live');

		Config::inst()->unnest();
	}

}

