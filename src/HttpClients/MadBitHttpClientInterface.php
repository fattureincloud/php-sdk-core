<?php

namespace MadBit\SDK\HttpClients;

use MadBit\SDK\Exceptions\MadBitSDKException;
use MadBit\SDK\Http\ApiRawResponse;

interface MadBitHttpClientInterface
{
    /**
     * Sends a request to the server and returns the raw response.
     *
     * @param string $url     the endpoint to send the request to
     * @param string $method  the request method
     * @param string $body    the body of the request
     * @param array  $headers the request headers
     * @param int    $timeOut the timeout in seconds for the request
     *
     * @throws MadBitSDKException
     *
     * @return ApiRawResponse raw response from the server
     */
    public function send($url, $method, $body, array $headers, $timeOut);
}
