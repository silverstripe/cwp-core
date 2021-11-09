<?php

namespace CWP\Core\Tests\Control;

use CWP\Core\Control\CwpBasicAuthMiddleware;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\BasicAuth;
use SilverStripe\Security\BasicAuthMiddleware;

class CwpBasicAuthMiddlewareTest extends SapphireTest
{
    /**
     * @var CwpBasicAuthMiddleware
     */
    protected $middleware;

    /**
     * @var array
     */
    protected $originalServersVars = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = Injector::inst()->get(BasicAuthMiddleware::class);
        $this->originalServersVars = $_SERVER;

        Config::modify()->set(BasicAuth::class, 'ignore_cli', false);
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServersVars;

        parent::tearDown();
    }

    public function testSetWhitelistedIpsAcceptsStrings()
    {
        $this->middleware->setWhitelistedIps('127.0.0.1,127.0.0.2');
        $this->assertSame([
            '127.0.0.1',
            '127.0.0.2',
        ], $this->middleware->getWhitelistedIps(), 'Accepts comma delimited strings');
    }

    public function testSetWhitelistedIpsAcceptsArraysOfStrings()
    {
        $this->middleware->setWhitelistedIps(['127.0.0.1']);
        $this->assertSame(['127.0.0.1'], $this->middleware->getWhitelistedIps(), 'Accepts array values');
    }

    public function testSetWhitelistedIpsSupportedNestedStringListsInsideArrays()
    {
        $this->middleware->setWhitelistedIps([
            '127.0.0.1,127.0.0.2', // Example of `CWP_IP_BYPASS_BASICAUTH` env var value
            ' 137.0.0.1 , 127.0.0.2', // Example of `CWP_IP_BYPASS_BASICAUTH` env var value with added spaces
            '127.0.0.3',
            '127.0.0.3', // check results are unique
            '127.0.0.4',
        ]);

        $this->assertSame([
            '127.0.0.1',
            '127.0.0.2',
            '137.0.0.1',
            '127.0.0.3',
            '127.0.0.4',
        ], $this->middleware->getWhitelistedIps(), 'Accepts IP list strings inside arrays');
    }

    /**
     * @param string $currentIp
     * @param int $expected
     * @dataProvider whitelistingProvider
     */
    public function testIpWhitelisting($currentIp, $expected)
    {
        // Enable basic auth everywhere
        $this->middleware->setURLPatterns(['#.*#' => true]);

        // Set a whitelisted IP address
        $_SERVER['REMOTE_ADDR'] = $currentIp;
        $this->middleware->setWhitelistedIps(['127.0.0.1']);

        $response = $this->mockRequest();

        $this->assertEquals($expected, $response->getStatusCode());
    }

    /**
     * @return array[]
     */
    public function whitelistingProvider()
    {
        return [
            'IP not in whitelist' => ['123.456.789.012', 401],
            'IP in whitelist' => ['127.0.0.1', 200],
        ];
    }

    public function testMiddlewareProvidesUatServerPermissions()
    {
        $this->assertArrayHasKey('ACCESS_UAT_SERVER', $this->middleware->providePermissions());
    }

    /**
     * Perform a mock middleware request. Will return 200 if everything is OK.
     *
     * @param string $url
     * @return HTTPResponse
     */
    protected function mockRequest($url = '/foo')
    {
        $request = new HTTPRequest('GET', $url);

        return $this->middleware->process($request, function () {
            return new HTTPResponse('OK', 200);
        });
    }
}
