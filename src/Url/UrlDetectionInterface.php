<?php

namespace MadBit\SDK\Url;

interface UrlDetectionInterface
{
    /**
     * Get the currently active URL.
     *
     * @return string
     */
    public function getCurrentUrl(): string;
}
