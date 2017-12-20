<?php

namespace CWP\Core\Tests;

use CWP\Core\Extension\CwpControllerExtension;
use Exception;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Middleware\CanonicalURLMiddleware;
use SilverStripe\Control\Session;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Kernel;
use SilverStripe\Dev\SapphireTest;

/**
 * Tests for the CWP Core controller extension. Note that tests here will use configuration to mock the
 * responses from {@link Director} method calls.
 */
class CwpControllerExtensionTest extends SapphireTest
{
    /**
     * @var Controller
     */
    protected $controller;

    /**
     * @var CanonicalURLMiddleware
     */
    protected $middlewareMock;

    protected function setUp()
    {
        parent::setUp();

        $this->logOut();

        $this->controller = new Controller();

        $request = new HTTPRequest('GET', '/');
        $request->setSession(new Session([]));

        $this->controller->setRequest($request);

        $this->middlewareMock = $this->getMockBuilder(CanonicalURLMiddleware::class)
            ->setMethods(['setForceSSL'])
            ->getMock();

        Injector::inst()->registerService($this->middlewareMock, CanonicalURLMiddleware::class);
    }

    public function testRedirectsSSLToDomain()
    {
        Config::modify()->set(Director::class, 'alternate_base_url', 'http://nothttps.local');

        // Expecting this to call forceSSL to forcedomain.org.
        $this->middlewareMock->expects($this->once())
            ->method('setForceSSL')
            ->with(true)
            ->will($this->returnSelf());

        Config::modify()->set(CwpControllerExtension::class, 'ssl_redirection_enabled', true);
        Config::modify()->set(CwpControllerExtension::class, 'ssl_redirection_force_domain', 'forcedomain.org');

        $this->controller->handleRequest($this->controller->getRequest());
    }

    public function testRedirectsSSLToCurrentDomain()
    {
        Config::modify()->set(Director::class, 'alternate_base_url', 'http://nothttps.local');

        // Expecting this to call forceSSL to current domain.
        $this->middlewareMock->expects($this->once())
            ->method('setForceSSL')
            ->will($this->returnSelf());

        Config::modify()->set(CwpControllerExtension::class, 'ssl_redirection_enabled', true);
        Config::modify()->set(CwpControllerExtension::class, 'ssl_redirection_force_domain', false);

        $this->controller->handleRequest($this->controller->getRequest());
    }

    public function testRequiresLoginForTest()
    {
        Injector::inst()->get(Kernel::class)->setEnvironment('test');

        try {
            $this->controller->handleRequest($this->controller->getRequest());
        } catch (Exception $e) {
            $this->assertEquals($e->getResponse()->getStatusCode(), '401', 'Forces BasicAuth on test');

            // We need to pop manually, since throwing an SS_HTTPResponse_Exception in onBeforeInit hijacks
            // the normal Controller control flow and confuses TestRunner (as they share global controller stack).
            $this->controller->popCurrent();
        }
    }

    public function testRequiresLoginForNonTest()
    {
        Injector::inst()->get(Kernel::class)->setEnvironment('live');

        $response = $this->controller->handleRequest($this->controller->getRequest());
        $this->assertEquals($response->getStatusCode(), '200', 'Does not force BasicAuth on live');
    }

    public function testRequiresLoginForLiveWhenEnabled()
    {
        Config::modify()->set(CwpControllerExtension::class, 'live_basicauth_enabled', true);

        Injector::inst()->get(Kernel::class)->setEnvironment('live');

        try {
            $this->controller->handleRequest($this->controller->getRequest());
        } catch (Exception $e) {
            $this->assertEquals($e->getResponse()->getStatusCode(), '401', 'Forces BasicAuth on live (optionally)');

            // We need to pop manually, since throwing an SS_HTTPResponse_Exception in onBeforeInit hijacks
            // the normal Controller control flow and confuses TestRunner (as they share global controller stack).
            $this->controller->popCurrent();
        }
    }
}
