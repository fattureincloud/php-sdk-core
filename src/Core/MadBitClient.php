<?php

namespace MadBit\SDK\Core;

use MadBit\SDK\Exceptions\MadBitResponseException;
use MadBit\SDK\Exceptions\MadBitSDKException;
use MadBit\SDK\HttpClients\MadBitGuzzleHttpClient;

class MadBitClient
{
    /**
     * @const string Production API URL.
     */
    const BASE_API_URL = 'https://api-v2.fattureincloud.it';

    /**
     * @const int The timeout in seconds for a normal request.
     */
    const DEFAULT_REQUEST_TIMEOUT = 30;

    /**
     * @const int The timeout in seconds for a request that contains file uploads.
     */
    const DEFAULT_FILE_UPLOAD_REQUEST_TIMEOUT = 60;

    /**
     * @var int the number of calls that have been made to APIs
     */
    public static $requestCount = 0;

    /**
     * @var MadBitGuzzleHttpClient HTTP client
     */
    protected $httpClient;

    /**
     * Instantiates a new MadBitClient object.
     */
    public function __construct()
    {
        $this->httpClient = new MadBitGuzzleHttpClient();
    }

    /**
     * Returns the HTTP client handler.
     *
     * @return MadBitGuzzleHttpClient
     */
    public function getHttpClient(): MadBitGuzzleHttpClient
    {
        return $this->httpClient;
    }

    /**
     * Returns the base API URL.
     *
     * @return string
     */
    public function getBaseApiUrl(): string
    {
        return static::BASE_API_URL;
    }

    /**
     * Prepares the request for sending to the client handler.
     *
     * @param MadBitRequest $request
     *
     * @throws MadBitSDKException
     *
     * @return array
     */
    public function prepareRequestMessage(MadBitRequest $request): array
    {
        $method = $request->getMethod();
        $url = $this->getBaseApiUrl().$request->getUrl();

        // Set the access token
        $accessToken = $request->getAccessToken();
        if ($accessToken) {
            $request->setHeaders([
                'Authorization' => 'Bearer '.$accessToken,
            ]);
        }

        // If we're sending files they should be sent as multipart/form-data
        if ($request->containsFileUploads()) {
            $requestBody = $request->getMultipartBody();
            $request->setHeaders([
                'Content-Type' => 'multipart/form-data; boundary='.$requestBody->getBoundary(),
            ]);
        } elseif (in_array($method, ['POST', 'PUT'])) {
            $requestBody = $request->getJsonEncodedBody();
            $request->setHeaders([
                'Content-Type' => 'application/json',
            ]);
        } else {
            $requestBody = $request->getUrlEncodedBody();
            $request->setHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]);
        }

        return [
            $url,
            $request->getMethod(),
            $request->getHeaders(),
            $requestBody->getBody(),
        ];
    }

    /**
     * Makes the request to API and returns the result.
     *
     * @param MadBitRequest $request
     *
     * @throws MadBitSDKException
     * @throws MadBitResponseException
     *
     * @return MadBitResponse
     */
    public function sendRequest(MadBitRequest $request): MadBitResponse
    {
        if ('MadBit\SDK\MadBitRequest' === get_class($request)) {
            $request->validateAccessToken();
        }

        list($url, $method, $headers, $body) = $this->prepareRequestMessage($request);

        // Since file uploads can take a while, we need to give more time for uploads
        $timeOut = static::DEFAULT_REQUEST_TIMEOUT;
        if ($request->containsFileUploads()) {
            $timeOut = static::DEFAULT_FILE_UPLOAD_REQUEST_TIMEOUT;
        }

        // Should throw `MadBitSDKException` exception on HTTP client error.
        // Don't catch to allow it to bubble up.
        $rawResponse = $this->httpClient->send($url, $method, $body, $headers, $timeOut);

        ++static::$requestCount;

        $returnResponse = new MadBitResponse(
            $request,
            $rawResponse->getBody(),
            $rawResponse->getHttpResponseCode(),
            $rawResponse->getHeaders()
        );

        if ($returnResponse->isError()) {
            throw $returnResponse->getThrownException();
        }

        return $returnResponse;
    }
}
