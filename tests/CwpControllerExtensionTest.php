<?php

class CwpControllerExtensionTest extends SapphireTest
{

    function testRedirectsSSLToDomain()
    {
        Session::set("loggedInAs", null);

        $ctrl = new Controller();
        $req = new SS_HTTPRequest('GET', '/');
        $dataModel = new DataModel();

        Config::inst()->nest();

        $directorClass = $this->getMockClass('Director', array('forceSSL', 'is_https'));
        Injector::inst()->registerNamedService('Director', new $directorClass);

        // Expecting this to call forceSSL to forcedomain.org.
        $directorClass::staticExpects($this->any())
            ->method('is_https')
            ->will($this->returnValue(false));
        $directorClass::staticExpects($this->once())
            ->method('forceSSL')
            ->with($this->anything(), $this->equalTo('forcedomain.org'));

        Config::inst()->update('CwpControllerExtension', 'ssl_redirection_enabled', true);
        Config::inst()->update('CwpControllerExtension', 'ssl_redirection_force_domain', 'forcedomain.org');
        $response = $ctrl->handleRequest($req, $dataModel);

        Injector::inst()->unregisterAllObjects();
        Config::inst()->unnest();
    }

    function testRedirectsSSLToCurrentDomain()
    {
        Session::set("loggedInAs", null);

        $ctrl = new Controller();
        $req = new SS_HTTPRequest('GET', '/');
        $dataModel = new DataModel();

        $directorClass = $this->getMockClass('Director', array('forceSSL', 'is_https'));
        Injector::inst()->registerNamedService('Director', new $directorClass);

        // Expecting this to call forceSSL to current domain.
        $directorClass::staticExpects($this->any())
            ->method('is_https')
            ->will($this->returnValue(false));
        $directorClass::staticExpects($this->once())
            ->method('forceSSL')
            ->with($this->anything());

        Config::inst()->update('CwpControllerExtension', 'ssl_redirection_enabled', true);
        Config::inst()->update('CwpControllerExtension', 'ssl_redirection_force_domain', false);
        $response = $ctrl->handleRequest($req, $dataModel);

        Injector::inst()->unregisterAllObjects();
    }

    function testRequiresLoginForTest()
    {
        Session::set("loggedInAs", null);

        $ctrl = new Controller();
        $req = new SS_HTTPRequest('GET', '/');
        $dataModel = new DataModel();

        $directorClass = $this->getMockClass('Director', array('isTest'));
        Injector::inst()->registerNamedService('Director', new $directorClass);

        $directorClass::staticExpects($this->any())
            ->method('isTest')
            ->will($this->returnValue(true));

        try {
            $response = $ctrl->handleRequest($req, $dataModel);
        } catch (Exception $e) {
            $this->assertEquals($e->getResponse()->getStatusCode(), '401', 'Forces BasicAuth on test');

            // We need to pop manually, since throwing an SS_HTTPResponse_Exception in onBeforeInit hijacks
            // the normal Controller control flow and confuses TestRunner (as they share global controller stack).
            $ctrl->popCurrent();
        }
    }

    function testRequiresLoginForNonTest()
    {
        Session::set("loggedInAs", null);

        $ctrl = new Controller();
        $req = new SS_HTTPRequest('GET', '/');
        $dataModel = new DataModel();

        $directorClass = $this->getMockClass('Director', array('isTest'));
        Injector::inst()->registerNamedService('Director', new $directorClass);

        $directorClass::staticExpects($this->any())
            ->method('isTest')
            ->will($this->returnValue(false));

        $response = $ctrl->handleRequest($req, $dataModel);
        $this->assertEquals($response->getStatusCode(), '200', 'Does not force BasicAuth on live');
    }

    function testRequiresLoginForLiveWhenEnabled()
    {
        Session::set("loggedInAs", null);

        $ctrl = new Controller();
        $req = new SS_HTTPRequest('GET', '/');
        $dataModel = new DataModel();

        Config::inst()->nest();

        Config::inst()->update('CwpControllerExtension', 'live_basicauth_enabled', true);
        $directorClass = $this->getMockClass('Director', array('isTest', 'isLive'));
        Injector::inst()->registerNamedService('Director', new $directorClass);

        $directorClass::staticExpects($this->any())
            ->method('isTest')
            ->will($this->returnValue(false));

        $directorClass::staticExpects($this->any())
            ->method('isLive')
            ->will($this->returnValue(true));

        try {
            $response = $ctrl->handleRequest($req, $dataModel);
        } catch (Exception $e) {
            $this->assertEquals($e->getResponse()->getStatusCode(), '401', 'Forces BasicAuth on live (optionally)');

            // We need to pop manually, since throwing an SS_HTTPResponse_Exception in onBeforeInit hijacks
            // the normal Controller control flow and confuses TestRunner (as they share global controller stack).
            $ctrl->popCurrent();
        }

        Config::inst()->unnest();
    }
}
