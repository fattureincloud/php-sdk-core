<?php

namespace MadBit\SDK\PseudoRandomString;

use MadBit\SDK\Exceptions\MadBitSDKException;

class McryptPseudoRandomStringGenerator implements PseudoRandomStringGeneratorInterface
{
    use PseudoRandomStringGeneratorTrait;

    /**
     * @const string The error message when generating the string fails.
     */
    const ERROR_MESSAGE = 'Unable to generate a cryptographically secure pseudo-random string from mcrypt_create_iv(). ';

    /**
     * @throws MadBitSDKException
     */
    public function __construct()
    {
        if (!function_exists('mcrypt_create_iv')) {
            throw new MadBitSDKException(
                static::ERROR_MESSAGE.
                'The function mcrypt_create_iv() does not exist.'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPseudoRandomString(int $length): string
    {
        $this->validateLength($length);

        $binaryString = mcrypt_create_iv($length, MCRYPT_DEV_URANDOM);

        if (false === $binaryString) {
            throw new MadBitSDKException(
                static::ERROR_MESSAGE.
                'mcrypt_create_iv() returned an error.'
            );
        }

        return $this->binToHex($binaryString, $length);
    }
}
