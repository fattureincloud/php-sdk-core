<?php

namespace MadBit\SDK\Http;

interface RequestBodyInterface
{
    /**
     * Get the body of the request to send to API.
     *
     * @return string
     */
    public function getBody(): string;
}
