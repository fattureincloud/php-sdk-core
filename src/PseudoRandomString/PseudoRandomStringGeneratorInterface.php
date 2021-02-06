<?php

namespace MadBit\SDK\PseudoRandomString;

use InvalidArgumentException;
use MadBit\SDK\Exceptions\MadBitSDKException;

interface PseudoRandomStringGeneratorInterface
{
    /**
     * Get a cryptographically secure pseudo-random string of arbitrary length.
     *
     * @see http://sockpuppet.org/blog/2014/02/25/safely-generate-random-numbers/
     *
     * @param int $length the length of the string to return
     *
     * @throws MadBitSDKException
     * @throws InvalidArgumentException
     *
     * @return string
     */
    public function getPseudoRandomString(int $length): string;
}
