<?php

namespace MadBit\SDK\Core;

use MadBit\SDK\Exceptions\MadBitResponseException;
use MadBit\SDK\Exceptions\MadBitSDKException;

class MadBitResponse
{
    /**
     * @var int the HTTP status code response from API
     */
    protected $httpStatusCode;

    /**
     * @var array the headers returned from API
     */
    protected $headers;

    /**
     * @var string the raw body of the response from API
     */
    protected $body;

    /**
     * @var array the decoded body of the API response
     */
    protected $decodedBody = [];

    /**
     * @var MadBitRequest the original request that returned this response
     */
    protected $request;

    /**
     * @var MadBitResponseException the exception thrown by this request
     */
    protected $thrownException;

    /**
     * Creates a new Response entity.
     *
     * @param MadBitRequest $request
     * @param null|string   $body
     * @param null|int      $httpStatusCode
     * @param null|array    $headers
     */
    public function __construct(MadBitRequest $request, $body = null, $httpStatusCode = null, array $headers = [])
    {
        $this->request = $request;
        $this->body = $body;
        $this->httpStatusCode = $httpStatusCode;
        $this->headers = $headers;

        $this->decodeBody();
    }

    /**
     * Return the original request that returned this response.
     *
     * @return MadBitRequest
     */
    public function getRequest(): MadBitRequest
    {
        return $this->request;
    }

    /**
     * Return the MadBitApp entity used for this response.
     *
     * @return MadBitApp
     */
    public function getApp(): MadBitApp
    {
        return $this->request->getApp();
    }

    /**
     * Return the access token that was used for this response.
     *
     * @return null|string
     */
    public function getAccessToken()
    {
        return $this->request->getAccessToken();
    }

    /**
     * Return the HTTP status code for this response.
     *
     * @return int
     */
    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    /**
     * Return the HTTP headers for this response.
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Return the raw body response.
     *
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Return the decoded body response.
     *
     * @return array
     */
    public function getDecodedBody(): array
    {
        return $this->decodedBody;
    }

    /**
     * Returns true if API returned an error message.
     *
     * @return bool
     */
    public function isError(): bool
    {
        return isset($this->decodedBody['error']);
    }

    /**
     * Throws the exception.
     *
     * @throws MadBitSDKException
     */
    public function throwException()
    {
        throw $this->thrownException;
    }

    /**
     * Instantiates an exception to be thrown later.
     */
    public function makeException()
    {
        $this->thrownException = MadBitResponseException::create($this);
    }

    /**
     * Returns the exception that was thrown for this request.
     *
     * @return null|MadBitResponseException
     */
    public function getThrownException()
    {
        return $this->thrownException;
    }

    /**
     * Convert the raw response into an array if possible.
     *
     * MadBit APIs will return 3 types of responses:
     * - JSON
     *    Most responses from API are JSON
     * - application/x-www-form-urlencoded key/value pairs
     *    Happens on the `/oauth/token` endpoint when exchanging
     *    a short-lived access token for a long-lived access token
     * - raw binary data
     *    When a file is returned by the APIs
     * - And sometimes nothing :/ but that'd be a bug or an empty file.
     */
    public function decodeBody()
    {
        $this->decodedBody = json_decode($this->body, true);

        if (null === $this->decodedBody) {
            $this->decodedBody = [];
            parse_str($this->body, $this->decodedBody);
        } elseif (is_bool($this->decodedBody)) {
            $this->decodedBody = ['success' => $this->decodedBody];
        } elseif (is_numeric($this->decodedBody)) {
            $this->decodedBody = ['id' => $this->decodedBody];
        }

        if (!is_array($this->decodedBody)) {
            $this->decodedBody = [];
        }

        if ($this->isError()) {
            $this->makeException();
        }
    }
}
