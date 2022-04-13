<?php declare(strict_types=1);

namespace CWP\Core\PasswordEncryptor;

use Exception;
use SilverStripe\Security\PasswordEncryptor_PHPHash;

/**
 * Provides Password-Based Key Derivation Function hashing for passwords, using the provided algorithm (default
 * is SHA512), which is NZISM compliant under version 3.2 section 17.2.
 */
class PBKDF2 extends PasswordEncryptor_PHPHash
{
    /**
     * The number of internal iterations for hash_pbkdf2() to perform for the derivation. Please note that if you
     * change this from the default value you will break existing hashes stored in the database, so these would
     * need to be regenerated.
     *
     * @var int
     */
    protected $iterations = 30000;

    /**
     * @param string $algorithm
     * @param int|null $iterations
     * @throws Exception If the provided algorithm is not available in the current environment
     */
    public function __construct(string $algorithm, int $iterations = null)
    {
        parent::__construct($algorithm);

        if ($iterations !== null) {
            $this->iterations = $iterations;
        }
    }

    /**
     * @return int
     */
    public function getIterations(): int
    {
        return $this->iterations;
    }

    public function encrypt($password, $salt = null, $member = null)
    {
        return hash_pbkdf2(
            $this->getAlgorithm() ?? '',
            (string) $password,
            (string) $salt,
            $this->getIterations() ?? 0
        );
    }
}
