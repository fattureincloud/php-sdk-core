<?php

namespace MadBit\SDK\PseudoRandomString;

use MadBit\SDK\Exceptions\MadBitSDKException;

class UrandomPseudoRandomStringGenerator implements PseudoRandomStringGeneratorInterface
{
    use PseudoRandomStringGeneratorTrait;

    /**
     * @const string The error message when generating the string fails.
     */
    const ERROR_MESSAGE = 'Unable to generate a cryptographically secure pseudo-random string from /dev/urandom. ';

    /**
     * @throws MadBitSDKException
     */
    public function __construct()
    {
        if (ini_get('open_basedir')) {
            throw new MadBitSDKException(
                static::ERROR_MESSAGE.
                'There is an open_basedir constraint that prevents access to /dev/urandom.'
            );
        }

        if (!is_readable('/dev/urandom')) {
            throw new MadBitSDKException(
                static::ERROR_MESSAGE.
                'Unable to read from /dev/urandom.'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPseudoRandomString(int $length): string
    {
        $this->validateLength($length);

        $stream = fopen('/dev/urandom', 'rb');
        if (!is_resource($stream)) {
            throw new MadBitSDKException(
                static::ERROR_MESSAGE.
                'Unable to open stream to /dev/urandom.'
            );
        }

        if (!defined('HHVM_VERSION')) {
            stream_set_read_buffer($stream, 0);
        }

        $binaryString = fread($stream, $length);
        fclose($stream);

        if (!$binaryString) {
            throw new MadBitSDKException(
                static::ERROR_MESSAGE.
                'Stream to /dev/urandom returned no data.'
            );
        }

        return $this->binToHex($binaryString, $length);
    }
}
