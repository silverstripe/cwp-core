<?php

namespace CWP\Core\Tests\PasswordEncryptor;

use CWP\Core\PasswordEncryptor\PBKDF2;
use League\Flysystem\Exception;
use SilverStripe\Dev\SapphireTest;

class PBKDF2Test extends SapphireTest
{
    public function testGetIterations()
    {
        $encryptor = new PBKDF2('sha512', 12345);
        $this->assertSame(12345, $encryptor->getIterations());
    }

    public function testEncrypt()
    {
        $encryptor = new PBKDF2('sha512', 10000);
        $salt = 'predictablesaltforunittesting';
        $result = $encryptor->encrypt('opensesame', $salt);
        $this->assertSame(
            '6bafcacb90',
            substr($result, 0, 10),
            'Hashed password with predictable salt did not match fixtured expectation'
        );
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Hash algorithm "foobar" not found
     */
    public function testThrowsExceptionWhenInvalidAlgorithmIsProvided()
    {
        new PBKDF2('foobar');
    }
}
