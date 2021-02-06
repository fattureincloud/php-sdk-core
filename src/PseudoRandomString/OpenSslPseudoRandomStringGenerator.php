<?php

namespace MadBit\SDK\PseudoRandomString;

use MadBit\SDK\Exceptions\MadBitSDKException;

class OpenSslPseudoRandomStringGenerator implements PseudoRandomStringGeneratorInterface
{
    use PseudoRandomStringGeneratorTrait;

    /**
     * @const string The error message when generating the string fails.
     */
    const ERROR_MESSAGE = 'Unable to generate a cryptographically secure pseudo-random string from openssl_random_pseudo_bytes().';

    /**
     * @throws MadBitSDKException
     */
    public function __construct()
    {
        if (!function_exists('openssl_random_pseudo_bytes')) {
            throw new MadBitSDKException(static::ERROR_MESSAGE.'The function openssl_random_pseudo_bytes() does not exist.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPseudoRandomString(int $length): string
    {
        $this->validateLength($length);

        $wasCryptographicallyStrong = false;
        $binaryString = openssl_random_pseudo_bytes($length, $wasCryptographicallyStrong);

        if (false === $binaryString) {
            throw new MadBitSDKException(static::ERROR_MESSAGE.'openssl_random_pseudo_bytes() returned an unknown error.');
        }

        if (true !== $wasCryptographicallyStrong) {
            throw new MadBitSDKException(static::ERROR_MESSAGE.'openssl_random_pseudo_bytes() returned a pseudo-random string but it was not cryptographically secure and cannot be used.');
        }

        return $this->binToHex($binaryString, $length);
    }
}
