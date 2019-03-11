<?php

namespace CWP\Core\Tests\Extension;

use CWP\Core\Extension\CWPVersionExtension;
use PHPUnit_Framework_MockObject_MockObject;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Core\Manifest\VersionProvider;
use SilverStripe\Dev\SapphireTest;

class CWPVersionExtensionTest extends SapphireTest
{
    /**
     * @var VersionProvider|PHPUnit_Framework_MockObject_MockObject
     */
    protected $versionProvider;

    /**
     * @var LeftAndMain|PHPUnit_Framework_MockObject_MockObject
     */
    protected $leftAndMain;

    protected function setUp()
    {
        parent::setUp();

        $this->versionProvider = $this->createMock(VersionProvider::class);
        $this->leftAndMain = $this->createMock(LeftAndMain::class);

        $this->leftAndMain
            ->expects($this->atLeastOnce())
            ->method('getVersionProvider')
            ->willReturn($this->versionProvider);
    }

    /**
     * @param array $modules
     * @param string $expected
     * @dataProvider getVersionProvider
     */
    public function testGetVersion($modules, $expected)
    {
        $this->versionProvider->expects($this->once())
            ->method('getModuleVersionFromComposer')
            ->willReturn($modules);

        $extension = new CWPVersionExtension();
        $extension->setOwner($this->leftAndMain);

        $result = $extension->getCWPVersionNumber();
        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public function getVersionProvider()
    {
        return [
            'dev version' => [['cwp/cwp-core' => '2.3.x-dev'], '2.3'],
            'stable version' => [['cwp/cwp-core' => '2.2.0'], '2.2'],
            'not found' => [[], ''],
        ];
    }
}
