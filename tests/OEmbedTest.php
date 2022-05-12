<?php

namespace CWP\Core\Tests;

use Embed\Http\Crawler;
use GuzzleHttp\Client;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;

class OEmbedTest extends SapphireTest
{
    /**
     * Ensure that the Psr\Http\Client\ClientInterface.oembed created is a
     * GuzzleHttp\Client which can have the CWP outband proxy configuration applied to it
     *
     * This is to ensure the config in cwp-core/_config/oembed.yml aligns with the
     * configured ClientInterface in the required version of framework
     */
    public function testGuzzleProxyConfig()
    {
        $reflClass = new \ReflectionClass(Crawler::class);
        $reflProperty = $reflClass->getProperty('client');
        $reflProperty->setAccessible(true);
        $crawler = Injector::inst()->get(Crawler::class);
        $client = $reflProperty->getValue($crawler);
        $this->assertSame(Client::class, get_class($client));
    }
}
