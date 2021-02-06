<?php

namespace MadBit\SDK\PseudoRandomString;

use Exception;
use MadBit\SDK\Exceptions\MadBitSDKException;

class RandomBytesPseudoRandomStringGenerator implements PseudoRandomStringGeneratorInterface
{
    use PseudoRandomStringGeneratorTrait;

    /**
     * @const string The error message when generating the string fails.
     */
    const ERROR_MESSAGE = 'Unable to generate a cryptographically secure pseudo-random string from random_bytes(). ';

    /**
     * @throws MadBitSDKException
     */
    public function __construct()
    {
        if (!function_exists('random_bytes')) {
            throw new MadBitSDKException(
                static::ERROR_MESSAGE.
                'The function random_bytes() does not exist.'
            );
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function getPseudoRandomString(int $length): string
    {
        $this->validateLength($length);

        return $this->binToHex(random_bytes($length), $length);
    }
}
