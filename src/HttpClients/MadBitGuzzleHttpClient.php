<?php

namespace MadBit\SDK\HttpClients;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use MadBit\SDK\Exceptions\MadBitSDKException;
use MadBit\SDK\Http\ApiRawResponse;
use Psr\Http\Message\ResponseInterface;

class MadBitGuzzleHttpClient implements MadBitHttpClientInterface
{
    /**
     * @var Client the Guzzle client
     */
    protected $guzzleClient;

    /**
     * @param null|Client $guzzleClient
     */
    public function __construct(Client $guzzleClient = null)
    {
        $this->guzzleClient = $guzzleClient ?: new Client();
    }

    /**
     * {@inheritdoc}
     */
    public function send($url, $method, $body, array $headers, $timeOut): ApiRawResponse
    {
        $options = [
            'headers' => $headers,
            'body' => $body,
            'timeout' => $timeOut,
            'connect_timeout' => 10,
        ];

        try {
            $rawResponse = $this->guzzleClient->request($method, $url, $options);
        } catch (RequestException $e) {
            $rawResponse = $e->getResponse();
            if (!$rawResponse instanceof ResponseInterface) {
                throw new MadBitSDKException($e->getMessage(), $e->getCode());
            }
        } catch (GuzzleException $e) {
            throw new MadBitSDKException($e->getMessage(), $e->getCode());
        }

        $rawHeaders = $this->getHeadersAsString($rawResponse);
        $rawBody = $rawResponse->getBody();
        $httpStatusCode = $rawResponse->getStatusCode();

        return new ApiRawResponse($rawHeaders, $rawBody, $httpStatusCode);
    }

    /**
     * Returns the Guzzle array of headers as a string.
     *
     * @param ResponseInterface $response the Guzzle response
     *
     * @return string
     */
    public function getHeadersAsString(ResponseInterface $response): string
    {
        $headers = $response->getHeaders();
        $rawHeaders = [];
        foreach ($headers as $name => $values) {
            $rawHeaders[] = $name.': '.implode(', ', $values);
        }

        return implode("\r\n", $rawHeaders);
    }
}
