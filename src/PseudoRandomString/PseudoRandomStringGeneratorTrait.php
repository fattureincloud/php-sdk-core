<?php

namespace MadBit\SDK\PseudoRandomString;

use InvalidArgumentException;

trait PseudoRandomStringGeneratorTrait
{
    /**
     * Validates the length argument of a random string.
     *
     * @param int $length the length to validate
     *
     * @throws InvalidArgumentException
     */
    public function validateLength(int $length)
    {
        if (!is_int($length)) {
            throw new InvalidArgumentException('getPseudoRandomString() expects an integer for the string length');
        }

        if ($length < 1) {
            throw new InvalidArgumentException('getPseudoRandomString() expects a length greater than 1');
        }
    }

    /**
     * Converts binary data to hexadecimal of arbitrary length.
     *
     * @param string $binaryData the binary data to convert to hex
     * @param int    $length     the length of the string to return
     *
     * @return string
     */
    public function binToHex(string $binaryData, int $length): string
    {
        return substr(bin2hex($binaryData), 0, $length);
    }
}
